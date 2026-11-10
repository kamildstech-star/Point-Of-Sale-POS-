<?php

namespace App\Domain\Dashboard;

use App\Domain\Operations\Conflict;
use App\Domain\Operations\Decimal;
use App\Domain\Operations\Operation;
use App\Domain\Operations\WorkspaceService;
use App\Domain\Tenancy\RecordScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DashboardService extends Operation
{
    private DecimalAggregate $aggregate;

    private DashboardCapabilities $capabilities;

    private ?array $financialSnapshot = null;

    public function __construct(private DashboardPeriod $period, private bool $forceOwnSales = false)
    {
        $this->aggregate = new DecimalAggregate();
        $this->capabilities = new DashboardCapabilities($this->context());
    }

    public function currencyScale(): int
    {
        return $this->scale();
    }

    private function records(string $table, bool $personal = false): Builder
    {
        return (new RecordScope($this->context(), $this->period->branchId === null ? null : [$this->period->branchId], $personal && ($this->forceOwnSales || $this->capabilities->ownSales())))->query($table);
    }

    private function inPeriod(Builder $query, string $column): Builder
    {
        return $query->where($column, '>=', $this->period->start())->where($column, '<', $this->period->end());
    }

    private function sales(): Builder
    {
        return $this->inPeriod($this->records('sales', true), 'sold_at');
    }

    private function returns(): Builder
    {
        return $this->inPeriod($this->records('sale_returns', true)->where('status', 'posted'), 'posted_at');
    }

    private function returnLines(): Builder
    {
        return $this->records('sale_return_items', true)->whereIn('sale_return_id', $this->returns()->select('sale_returns.id'));
    }

    private function financial(): array
    {
        if ($this->financialSnapshot !== null) {
            return $this->financialSnapshot;
        }
        $currency = $this->context()->tenant()->currency_code;
        if ($this->sales()->where('currency_code', '<>', $currency)->exists()
            || $this->returns()->whereIn('original_sale_id', $this->records('sales', true)->where('currency_code', '<>', $currency)->select('sales.id'))->exists()) {
            throw new Conflict('Mixed-currency reporting requires an approved currency conversion policy.');
        }
        $sales = $this->aggregate->totals($this->sales(), ['gross_sales' => 'subtotal', 'discounts' => 'discount_total', 'tax_charged' => 'tax_total', 'invoiced_sales' => 'grand_total']);
        $returns = $this->aggregate->totals($this->returns(), ['return_credits' => 'total']);
        $lines = $this->aggregate->totals($this->returnLines(), ['returned_tax' => 'tax_amount', 'returned_discount' => 'discount_amount']);
        $totals = array_diff_key($sales, ['count' => true]) + array_diff_key($returns, ['count' => true]) + array_diff_key($lines, ['count' => true]);
        $totals['net_sales_including_tax'] = Decimal::sub($totals['invoiced_sales'], $totals['return_credits']);
        $totals['net_tax'] = Decimal::sub($totals['tax_charged'], $totals['returned_tax']);
        $totals['net_sales_excluding_tax'] = Decimal::sub($totals['net_sales_including_tax'], $totals['net_tax']);

        return $this->financialSnapshot = ['totals' => $totals, 'invoice_count' => $sales['count'], 'posted_return_count' => $returns['count'],
            'voided_invoice_count' => $this->sales()->where('sale_status', 'voided')->count(), 'posted_cancellation_count' => $this->returns()->where('kind', 'cancel')->count(),
            'average_invoice_value_including_tax' => $sales['count'] ? Decimal::round(bcdiv($totals['invoiced_sales'], (string) $sales['count'], 8), $this->scale()) : null,
            'sales_scope' => $this->forceOwnSales || $this->capabilities->ownSales() ? 'own' : 'permitted_branches',
            'basis' => 'Original invoices by sold_at, including invoices later voided; subtract posted return/cancellation credits by posted_at, including credits for older invoices. Never exclude a voided invoice and also subtract its cancellation credit. Invoice count counts original invoices; average invoice value is before return credits.'];
    }

    public function summary(): array
    {
        $result = ['capabilities' => $this->capabilities->toArray()];
        foreach (['sales' => 'financial', 'inventory' => 'inventory', 'purchasing' => 'purchasing', 'shift' => 'shift', 'accounting' => 'accounting', 'approvals' => 'approvals'] as $widget => $method) {
            if ($this->capabilities->allows($widget)) {
                $result[$widget] = $this->$method();
            }
        }
        if ($this->period->compare && $this->capabilities->allows('sales')) {
            $previous = $this->period->previous();
            $comparison = (new self($previous, $this->forceOwnSales))->financial();
            $changes = [];
            foreach (['net_sales_including_tax', 'net_sales_excluding_tax'] as $key) {
                $base = $comparison['totals'][$key];
                $changes[$key] = Decimal::cmp($base, '0') === 0 ? null : Decimal::round(bcdiv(bcmul(Decimal::sub($result['sales']['totals'][$key], $base), '100', 8), ltrim($base, '-'), 8), 2);
            }
            $result['comparison'] = ['from' => $previous->from, 'to' => $previous->to, 'sales' => $comparison, 'change_percent' => $changes, 'basis' => 'Immediately preceding equal number of tenant-local calendar days; percentage uses the absolute previous value; zero baseline is unavailable.'];
        }

        return $result;
    }

    private function dayExpression(string $column): array
    {
        $sql = 'CASE';
        $bindings = [];
        foreach ($this->period->days() as $day => [$from, $until]) {
            $sql .= ' WHEN '.$column.' >= ? AND '.$column.' < ? THEN \''.$day.'\'';
            array_push($bindings, $from, $until);
        }

        return [$sql.' END', $bindings];
    }

    public function trend(): array
    {
        $this->financial(); // Validate the currency contract before any chart values.
        [$salesGroup, $salesBindings] = $this->dayExpression('sales.sold_at');
        [$returnGroup, $returnBindings] = $this->dayExpression('sale_returns.posted_at');
        $sales = $this->aggregate->grouped($this->sales(), ['invoiced' => 'grand_total', 'tax' => 'tax_total'], $salesGroup, $salesBindings);
        $returns = $this->aggregate->grouped($this->returns(), ['credit' => 'total'], $returnGroup, $returnBindings);
        $tax = $this->aggregate->grouped($this->returnLines()->join('sale_returns as posted_return', fn ($j) => $j->on('posted_return.id', '=', 'sale_return_items.sale_return_id')->on('posted_return.tenant_id', '=', 'sale_return_items.tenant_id')),
            ['tax' => 'sale_return_items.tax_amount'], str_replace('sale_returns.posted_at', 'posted_return.posted_at', $returnGroup), $returnBindings);
        $series = [];
        foreach ($this->period->days() as $day => $_) {
            $including = Decimal::sub($sales[$day]['invoiced'] ?? '0', $returns[$day]['credit'] ?? '0');
            $netTax = Decimal::sub($sales[$day]['tax'] ?? '0', $tax[$day]['tax'] ?? '0');
            $series[] = ['date' => $day, 'net_sales_including_tax' => $including, 'net_tax' => $netTax, 'net_sales_excluding_tax' => Decimal::sub($including, $netTax)];
        }

        return ['series' => $series, 'comparison_series' => $this->period->compare ? (new self($this->period->previous(), $this->forceOwnSales))->trend()['series'] : null, 'basis' => $this->financial()['basis']];
    }

    public function branches(): array
    {
        $this->financial();
        $page = $this->records('branches')->orderBy('id')->paginate($this->period->perPage, ['id', 'name', 'code'], 'page', $this->period->page);
        $ids = $page->pluck('id')->all();
        $sales = $this->aggregate->grouped($this->sales()->whereIn('branch_id', $ids), ['invoiced' => 'grand_total', 'tax' => 'tax_total'], 'branch_id');
        $returns = $this->returns()->join('sales as origin', fn ($j) => $j->on('origin.id', '=', 'sale_returns.original_sale_id')->on('origin.tenant_id', '=', 'sale_returns.tenant_id'))->whereIn('origin.branch_id', $ids);
        $credits = $this->aggregate->grouped($returns, ['credit' => 'sale_returns.total'], 'origin.branch_id');
        $tax = $this->returnLines()->join('sale_items as origin_line', fn ($j) => $j->on('origin_line.id', '=', 'sale_return_items.sale_item_id')->on('origin_line.tenant_id', '=', 'sale_return_items.tenant_id'))
            ->join('sales as origin', fn ($j) => $j->on('origin.id', '=', 'origin_line.sale_id')->on('origin.tenant_id', '=', 'origin_line.tenant_id'))->whereIn('origin.branch_id', $ids);
        $taxes = $this->aggregate->grouped($tax, ['tax' => 'sale_return_items.tax_amount'], 'origin.branch_id');
        $items = $page->map(function ($branch) use ($sales, $credits, $taxes) {
            $net = Decimal::sub($sales[$branch->id]['invoiced'] ?? '0', $credits[$branch->id]['credit'] ?? '0');
            $tax = Decimal::sub($sales[$branch->id]['tax'] ?? '0', $taxes[$branch->id]['tax'] ?? '0');

            return ['id' => $branch->id, 'code' => $branch->code, 'name' => $branch->name, 'net_sales_including_tax' => $net, 'net_sales_excluding_tax' => Decimal::sub($net, $tax), 'links' => ['report' => '/api/v1/reports/summary?'.http_build_query(['from' => $this->period->from, 'to' => $this->period->to, 'branch_id' => $branch->id])]];
        })->all();

        return ['items' => $items, 'pagination' => $this->pagination($page)];
    }

    public function topProducts(): array
    {
        $this->financial();
        $sold = $this->records('sale_items', true)->whereIn('sale_id', $this->sales()->select('sales.id'))->selectRaw('variant_id, qty, line_total AS credit, tax_total AS tax');
        $returned = $this->returnLines()->join('sale_items as origin_line', fn ($j) => $j->on('origin_line.id', '=', 'sale_return_items.sale_item_id')->on('origin_line.tenant_id', '=', 'sale_return_items.tenant_id'))
            ->selectRaw('origin_line.variant_id, '.$this->aggregate->negativeSql('sale_return_items.qty').' AS qty, '.$this->aggregate->negativeSql('sale_return_items.credit_amount').' AS credit, '.$this->aggregate->negativeSql('sale_return_items.tax_amount').' AS tax');
        $revenue = $this->aggregate->subtractSql($this->aggregate->sumSql('credit'), $this->aggregate->sumSql('tax'));
        $q = DB::query()->fromSub($sold->unionAll($returned), 'activity')->selectRaw('variant_id, '.$this->aggregate->sumSql('qty').' AS net_units, '.$revenue.' AS net_revenue')->groupBy('variant_id');
        $rows = $this->aggregate->orderDecimal($q, 'net_revenue')->orderBy('variant_id')->limit($this->period->limit)->get();
        $variants = $this->query('catalog_variants')->whereIn('id', $rows->pluck('variant_id'))->get(['id', 'product_id', 'sku', 'base_unit_id'])->keyBy('id');
        $products = $this->query('catalog_products')->whereIn('id', $variants->pluck('product_id'))->pluck('name', 'id');
        $units = $this->query('catalog_units')->whereIn('id', $variants->pluck('base_unit_id'))->pluck('code', 'id');

        return ['items' => $rows->map(function ($row) use ($variants, $products, $units) {
            $variant = $variants[$row->variant_id];

            return ['variant_id' => $variant->id, 'product_id' => $variant->product_id, 'sku' => $variant->sku, 'name' => $products[$variant->product_id], 'unit' => $units[$variant->base_unit_id],
                'net_units' => bcadd((string) $row->net_units, '0', 4), 'net_sales_excluding_tax' => bcadd((string) $row->net_revenue, '0', 4)];
        })->all(), 'basis' => 'Invoice lines by sold time minus all posted return/cancellation lines by posting time, including older invoices. Quantities remain per SKU and base unit. Labels use the current catalog.'];
    }

    public function recentSales(?int $id = null): array
    {
        $fields = ['id', 'invoice_no', 'sold_at', 'branch_id', 'cash_session_id', 'sale_status', 'payment_status', 'currency_code', 'grand_total', 'returned_total'];
        if ($id !== null) {
            $sale = $this->records('sales', true)->where('id', $id)->first($fields) ?? abort(404);
            $sale->sold_at = Carbon::parse($sale->sold_at, config('app.timezone'))->toIso8601String();

            return ['sale' => (array) $sale];
        }
        $page = $this->sales()->orderByDesc('sold_at')->orderByDesc('id')->paginate($this->period->perPage, $fields, 'page', $this->period->page);

        return ['items' => $page->map(function ($sale) {
            $sale->sold_at = Carbon::parse($sale->sold_at, config('app.timezone'))->toIso8601String();

            return (array) $sale;
        })->all(), 'pagination' => $this->pagination($page)];
    }

    public function inventory(): array
    {
        $balances = $this->records('inventory_balances');
        $threshold = app(WorkspaceService::class)->settings()['low_stock_threshold'];
        $quantities = $this->aggregate->grouped((clone $balances)->join('catalog_variants as v', fn ($j) => $j->on('v.id', '=', 'inventory_balances.variant_id')->on('v.tenant_id', '=', 'inventory_balances.tenant_id')),
            ['on_hand' => 'on_hand', 'reserved' => 'reserved'], 'v.base_unit_id');
        $units = $this->query('catalog_units')->whereIn('id', array_keys($quantities))->pluck('code', 'id');
        $byUnit = [];
        foreach ($quantities as $id => $values) {
            $byUnit[] = ['unit_id' => (int) $id, 'unit' => $units[$id], 'on_hand' => $values['on_hand'], 'reserved' => $values['reserved'], 'available' => Decimal::sub($values['on_hand'], $values['reserved'])];
        }

        return ['recorded_skus' => (clone $balances)->distinct()->count('variant_id'), 'recorded_stock_locations' => (clone $balances)->count(),
            'out_of_stock_locations' => (clone $balances)->whereColumn('on_hand', '<=', 'reserved')->count(),
            'low_stock_locations' => $threshold === null ? null : (clone $balances)->whereColumn('on_hand', '>', 'reserved')->whereRaw('on_hand - reserved <= ?', [(string) $threshold])->count(),
            'low_stock_threshold' => $threshold === null ? null : (string) $threshold, 'pending_counts' => $this->records('stock_counts')->where('status', 'draft')->count(),
            'quantities_by_unit' => $byUnit, 'transfers' => null,
            'basis' => 'Current recorded balances in permitted locations. Stockouts/low stock count SKU-location pairs, not all catalog SKUs. Low stock excludes stockouts; an unset threshold is unavailable. Reserved quantities reflect the posted reservation ledger. Different units are never added.'];
    }

    public function purchasing(): array
    {
        $orders = $this->records('purchase_orders');
        $statuses = (clone $orders)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->map(fn ($n) => (int) $n)->all();
        $payables = $this->aggregate->totals($this->records('supplier_payables'), ['outstanding' => 'outstanding_amount']);

        return ['status_counts' => $statuses, 'open_orders' => ($statuses['approved'] ?? 0) + ($statuses['partial'] ?? 0), 'draft_orders' => $statuses['draft'] ?? 0,
            'suppliers_on_orders' => (clone $orders)->distinct()->count('supplier_id'), 'outstanding_payables' => $payables['outstanding'],
            'expected_deliveries' => null, 'overdue_receipts' => null, 'basis' => 'Current permitted purchase documents; open means approved or partial. Expected delivery dates are not stored, so delivery/overdue counts are unavailable.'];
    }

    public function shift(): array
    {
        $own = ! $this->context()->allows('cash.manage') || $this->context()->ownSalesOnly();
        $q = (new RecordScope($this->context(), $this->period->branchId === null ? null : [$this->period->branchId], $own))->query('cash_sessions')->where('status', 'open');
        $count = (clone $q)->count();
        $page = (clone $q)->orderByDesc('id')->paginate($this->period->perPage, ['id', 'register_id', 'opened_at', 'status'], 'page', $this->period->page);
        $sales = $this->aggregate->totals($this->records('sales')->whereIn('cash_session_id', (clone $q)->select('cash_sessions.id')), ['invoiced_sales' => 'grand_total', 'return_credits' => 'returned_total']);

        return ['scope' => $own ? 'own' : 'permitted_branches', 'open_sessions' => $count, 'sessions' => $page->map(fn ($s) => ['id' => $s->id, 'register_id' => $s->register_id, 'opened_at' => Carbon::parse($s->opened_at, config('app.timezone'))->toIso8601String(), 'status' => $s->status])->all(),
            'pagination' => $this->pagination($page), 'invoice_count_in_open_sessions' => $sales['count'], 'net_invoiced_in_open_sessions' => Decimal::sub($sales['invoiced_sales'], $sales['return_credits']),
            'invoice_scope' => $this->context()->ownSalesOnly() ? 'own' : 'permitted_sessions',
            'held_sales' => null, 'basis' => 'Current open sessions, independent of reporting dates. Invoice values belong to the permitted open sessions, subject to any explicit personal-sales restriction; these are not cash collections. Held carts are not implemented.'];
    }

    public function accounting(): array
    {
        $sales = $this->financial();
        $payments = $this->inPeriod($this->records('sale_payments', true), 'recorded_at');
        $refunds = $this->inPeriod($this->records('refunds', true), 'refunded_at');
        $expensesQuery = $this->inPeriod($this->records('retail_expenses')->where('status', 'posted'), 'posted_at');
        $currency = $this->context()->tenant()->currency_code;
        if ((clone $payments)->where('currency_code', '<>', $currency)->exists() || (clone $refunds)->where('currency_code', '<>', $currency)->exists()
            || (clone $expensesQuery)->where('currency_code', '<>', $currency)->exists() || $this->records('sales', true)->where('currency_code', '<>', $currency)->exists()) {
            throw new Conflict('Mixed-currency accounting requires an approved currency conversion policy.');
        }
        $external = $this->aggregate->totals((clone $payments)->where('method_type_snapshot', '<>', 'exchange'), ['amount' => 'amount'])['amount'];
        $externalRefunds = $this->aggregate->totals((clone $refunds)->where('method_type_snapshot', '<>', 'exchange'), ['amount' => 'amount'])['amount'];
        $exchangeIn = $this->aggregate->totals((clone $payments)->where('method_type_snapshot', 'exchange'), ['amount' => 'amount'])['amount'];
        $exchangeOut = $this->aggregate->totals((clone $refunds)->where('method_type_snapshot', 'exchange'), ['amount' => 'amount'])['amount'];
        $expenses = $this->aggregate->totals($expensesQuery, ['amount' => 'amount'])['amount'];
        $balances = $this->aggregate->totals($this->records('sales', true), ['receivables' => $this->aggregate->dueSql(), 'refunds_due' => $this->aggregate->dueSql(true)]);

        return ['net_sales_excluding_tax' => $sales['totals']['net_sales_excluding_tax'], 'posted_expenses' => $expenses,
            'external_collections' => $external, 'external_refunds' => $externalRefunds, 'net_external_collections' => Decimal::sub($external, $externalRefunds),
            'exchange_credit_in' => $exchangeIn, 'exchange_credit_out' => $exchangeOut,
            'current_balances' => ['receivables' => $balances['receivables'], 'refunds_due' => $balances['refunds_due']],
            'closed_shift_variances' => $this->inPeriod($this->records('cash_sessions')->where('status', 'closed')->where('variance', '<>', '0'), 'closed_at')->count(),
            'verified_gross_profit' => null, 'net_profit' => null, 'bank_balance' => null,
            'basis' => 'Period collections/refunds/posted expenses include older invoices. Exchange credit is separate from external collections. Receivables/refunds due are current lifetime balances. Revenue less expenses is not net profit; verified COGS and bank reconciliation are unavailable.'];
    }

    public function approvals(): array
    {
        $definitions = [
            ['inventory.approve', 'stock_adjustments', ['draft'], 'stock-adjustment', '/api/v1/inventory/adjustments/'],
            ['inventory.approve', 'stock_counts', ['draft'], 'stock-count', '/api/v1/inventory/counts/'],
            ['purchases.approve', 'purchase_orders', ['draft'], 'purchase-order', '/api/v1/purchasing/orders/'],
            ['sales.return.approve', 'sale_returns', ['draft', 'approved'], 'sale-return', '/api/v1/returns/'],
            ['expenses.approve', 'retail_expenses', ['draft'], 'expense', '/api/v1/expenses'],
        ];
        $counts = [];
        $items = [];
        foreach ($definitions as [$permission, $table, $statuses, $type, $url]) {
            if (! $this->context()->allows($permission)) {
                continue;
            }
            $q = $this->records($table, $table === 'sale_returns')->whereIn('status', $statuses);
            $counts[$type] = (clone $q)->count();
            foreach ($q->orderByDesc('id')->limit(5)->get(['id', 'status', 'updated_at']) as $row) {
                $items[] = ['type' => $type, 'id' => $row->id, 'status' => $row->status,
                    'updated_at' => Carbon::parse($row->updated_at, config('app.timezone'))->toIso8601String(), 'links' => ['source' => $table === 'retail_expenses' ? $url : $url.$row->id]];
            }
        }
        usort($items, fn ($a, $b) => strcmp($b['updated_at'], $a['updated_at']) ?: strcmp($a['type'], $b['type']));

        return ['counts' => $counts, 'items' => $items, 'per_type_limit' => 5, 'basis' => 'Current actionable documents; up to five per type. Source endpoints revalidate scope and permissions.'];
    }

    public function alerts(): array
    {
        $items = [];
        if ($this->capabilities->allows('inventory')) {
            $threshold = app(WorkspaceService::class)->settings()['low_stock_threshold'];
            $q = $this->records('inventory_balances')->where(fn ($q) => $q->whereColumn('on_hand', '<=', 'reserved')->when($threshold !== null, fn ($q) => $q->orWhereRaw('on_hand - reserved <= ?', [(string) $threshold])))
                ->orderByRaw('CASE WHEN on_hand <= reserved THEN 0 ELSE 1 END')->orderBy('id')->limit($this->period->limit);
            $rows = $q->get(['id', 'location_id', 'variant_id', 'on_hand', 'reserved', 'updated_at']);
            $variants = $this->query('catalog_variants')->whereIn('id', $rows->pluck('variant_id'))->get(['id', 'sku', 'product_id', 'base_unit_id'])->keyBy('id');
            $products = $this->query('catalog_products')->whereIn('id', $variants->pluck('product_id'))->pluck('name', 'id');
            $locations = $this->query('locations')->whereIn('id', $rows->pluck('location_id'))->pluck('name', 'id');
            $units = $this->query('catalog_units')->whereIn('id', $variants->pluck('base_unit_id'))->pluck('code', 'id');
            foreach ($rows as $row) {
                $variant = $variants[$row->variant_id];
                $items[] = ['id' => 'stock:'.$row->id, 'type' => Decimal::cmp((string) $row->on_hand, (string) $row->reserved) <= 0 ? 'out_of_stock' : 'low_stock',
                    'severity' => Decimal::cmp((string) $row->on_hand, (string) $row->reserved) <= 0 ? 'danger' : 'warning', 'location_id' => $row->location_id, 'variant_id' => $row->variant_id,
                    'sku' => $variant->sku, 'product_name' => $products[$variant->product_id], 'location_name' => $locations[$row->location_id], 'unit' => $units[$variant->base_unit_id],
                    'available' => Decimal::sub((string) $row->on_hand, (string) $row->reserved), 'updated_at' => Carbon::parse($row->updated_at, config('app.timezone'))->toIso8601String(),
                    'links' => ['source' => '/api/v1/inventory/balances?'.http_build_query(['location_id' => $row->location_id, 'variant_id' => $row->variant_id])]];
            }
        }

        return ['items' => $items, 'approvals' => $this->capabilities->allows('approvals') ? $this->approvals()['counts'] : [], 'limit' => $this->period->limit,
            'basis' => 'Current stock exceptions, critical first; not historical global notifications. Pending approvals are current scoped counts.'];
    }

    public function cashier(): array
    {
        $own = new RecordScope($this->context(), $this->period->branchId === null ? null : [$this->period->branchId], true);
        $canShift = $this->context()->allows('cash.open');
        $canReceipts = $this->context()->allows('sales.view');
        $sessions = $own->query('cash_sessions')->where('status', 'open');
        $selected = null;
        if ($this->period->cashSessionId !== null) {
            abort_unless($canShift, 403);
            $selected = (clone $sessions)->where('id', $this->period->cashSessionId)->first() ?? abort(404);
        } elseif ($canShift) {
            $selected = (clone $sessions)->orderByDesc('id')->first();
        }
        $rows = $canShift ? (clone $sessions)->orderByDesc('id')->limit(20)->get() : collect();
        if ($selected && ! $rows->contains('id', $selected->id)) {
            $rows->push($selected);
        }
        $registers = $own->query('cash_registers')->whereIn('id', $rows->pluck('register_id'))->get(['id', 'name', 'branch_id'])->keyBy('id');
        $branches = $own->query('branches')->whereIn('id', $registers->pluck('branch_id'))->pluck('name', 'id');
        $headers = $rows->map(function ($session) use ($registers, $branches) {
            $register = $registers[$session->register_id];

            return ['id' => $session->id, 'register_id' => $register->id, 'register_name' => $register->name, 'branch_id' => $register->branch_id, 'branch_name' => $branches[$register->branch_id],
                'opened_at' => Carbon::parse($session->opened_at, config('app.timezone'))->toIso8601String()];
        });
        $shift = null;
        $receipts = null;
        if ($canReceipts) {
            $sales = $own->query('sales');
            if ($selected) {
                $sales->where('cash_session_id', $selected->id);
            }
            if ((clone $sales)->where('currency_code', '<>', $this->context()->tenant()->currency_code)->exists()) {
                throw new Conflict('Mixed-currency reporting requires an approved currency conversion policy.');
            }
            if ($selected) {
                $totals = $this->aggregate->totals($sales, ['invoiced' => 'grand_total', 'credits' => 'returned_total']);
                $shift = ['invoice_count' => $totals['count'], 'net_invoiced_including_tax' => Decimal::sub($totals['invoiced'], $totals['credits'])];
            }
            $page = $this->inPeriod($sales, 'sold_at')->orderByDesc('sold_at')->orderByDesc('id')->paginate($this->period->perPage,
                ['id', 'invoice_no', 'sold_at', 'branch_id', 'cash_session_id', 'sale_status', 'payment_status', 'currency_code', 'grand_total', 'returned_total'], 'page', $this->period->page);
            $receipts = ['items' => $page->map(function ($sale) {
                $sale->sold_at = Carbon::parse($sale->sold_at, config('app.timezone'))->toIso8601String();

                return (array) $sale;
            })->all(), 'pagination' => $this->pagination($page)];
        }
        $held = null;
        if ($this->context()->allows('sales.create') && \Illuminate\Support\Facades\Schema::hasTable('pos_held_carts')) {
            $held = ['count' => $own->query('pos_held_carts')->where('status', 'held')->count()];
        }

        return ['scope' => 'own', 'open_sessions' => $canShift ? (clone $sessions)->count() : null, 'sessions' => $headers->values()->all(), 'sessions_limit' => 20,
            'selected_session' => $selected ? $headers->firstWhere('id', $selected->id) : null, 'shift_totals' => $shift, 'receipts' => $receipts, 'held_carts' => $held,
            'basis' => 'Only your own open shifts and invoices, including when another role grants reports or cash management. Shift totals include tax and lifetime posted credits against your invoices in that shift; they are not cash collections. Recent receipts use the selected reporting dates. Held carts do not reserve stock or record payments.'];
    }

    public function staffActivity(): array
    {
        $this->financial();
        $sales = $this->sales()->select('cashier_membership_id as membership_id');
        $returns = $this->returns()->join('sales as origin', fn ($j) => $j->on('origin.id', '=', 'sale_returns.original_sale_id')->on('origin.tenant_id', '=', 'sale_returns.tenant_id'));
        $returnActors = (clone $returns)->select('origin.cashier_membership_id as membership_id');
        $open = $this->records('cash_sessions')->where('status', 'open');
        $canShifts = $this->context()->allows('cash.open') && $this->context()->allows('cash.manage');
        if (! $canShifts) {
            $open->whereRaw('1 = 0');
        }
        $actors = DB::query()->fromSub($sales->union($returnActors)->union((clone $open)->select('opened_by as membership_id')), 'activity')->select('membership_id')->distinct();
        $page = DB::table('tenant_memberships as m')->where('m.tenant_id', $this->context()->id())->whereIn('m.id', $actors)->join('users as u', 'u.id', '=', 'm.user_id')
            ->orderBy('m.id')->paginate($this->period->perPage, ['m.id', 'u.name'], 'page', $this->period->page);
        $ids = $page->pluck('id');
        $invoiceTotals = $this->aggregate->grouped($this->sales()->whereIn('cashier_membership_id', $ids), ['invoiced' => 'grand_total', 'tax' => 'tax_total'], 'cashier_membership_id');
        $invoiceCounts = $this->sales()->whereIn('cashier_membership_id', $ids)->selectRaw('cashier_membership_id, COUNT(*) AS total')->groupBy('cashier_membership_id')->pluck('total', 'cashier_membership_id');
        $credits = $this->aggregate->grouped((clone $returns)->whereIn('origin.cashier_membership_id', $ids), ['credit' => 'sale_returns.total'], 'origin.cashier_membership_id');
        $returnTax = $this->returnLines()->join('sale_items as original_line', fn ($j) => $j->on('original_line.id', '=', 'sale_return_items.sale_item_id')->on('original_line.tenant_id', '=', 'sale_return_items.tenant_id'))
            ->join('sales as original_sale', fn ($j) => $j->on('original_sale.id', '=', 'original_line.sale_id')->on('original_sale.tenant_id', '=', 'original_line.tenant_id'))->whereIn('original_sale.cashier_membership_id', $ids);
        $taxes = $this->aggregate->grouped($returnTax, ['tax' => 'sale_return_items.tax_amount'], 'original_sale.cashier_membership_id');
        $sessions = (clone $open)->whereIn('opened_by', $ids)->selectRaw('opened_by, COUNT(*) AS total')->groupBy('opened_by')->pluck('total', 'opened_by');

        return ['items' => $page->map(function ($member) use ($invoiceTotals, $invoiceCounts, $credits, $taxes, $sessions, $canShifts) {
            $including = Decimal::sub($invoiceTotals[$member->id]['invoiced'] ?? '0', $credits[$member->id]['credit'] ?? '0');
            $tax = Decimal::sub($invoiceTotals[$member->id]['tax'] ?? '0', $taxes[$member->id]['tax'] ?? '0');

            return ['membership_id' => $member->id, 'name' => $member->name, 'invoice_count' => (int) ($invoiceCounts[$member->id] ?? 0), 'net_sales_excluding_tax' => Decimal::sub($including, $tax), 'open_sessions' => $canShifts ? (int) ($sessions[$member->id] ?? 0) : null];
        })->all(), 'pagination' => $this->pagination($page), 'basis' => 'Staff with invoices or posted credits in this period, or a currently open shift in the selected branch. Names require team view and totals require reports view. This is recorded activity, not attendance or a branch headcount.'];
    }

    private function pagination($page): array
    {
        return ['current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(), 'last_page' => $page->lastPage()];
    }

    public function personalSales(): array
    {
        $own = new self($this->period, true);
        return ['scope' => 'own', 'sales' => $own->financial(), 'series' => $own->trend()['series'], 'receipts' => $own->recentSales(),
            'customers_on_invoices' => $this->context()->allows('customers.view') ? $own->sales()->whereNotNull('customer_id')->distinct()->count('customer_id') : null,
            'assigned_customers' => null, 'follow_ups' => null,
            'basis' => 'Your original invoices by sold_at and posted credits against your invoices by posting time. Personal scope is mandatory even when another role grants reports. Customer count means distinct named customers on your invoices, not assignments; assignments and follow-ups are not stored.'];
    }
}

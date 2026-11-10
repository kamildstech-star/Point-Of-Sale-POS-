<?php

namespace App\Domain\Dashboard;

use App\Domain\Operations\Conflict;
use App\Domain\Operations\Decimal;
use App\Domain\Operations\Operation;
use App\Domain\Tenancy\RecordScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/** Read-only projections of existing posted records; never posting/repricing services. */
final class SpecialistDashboardService extends Operation
{
    private DecimalAggregate $aggregate;

    public function __construct(private DashboardPeriod $period) { $this->aggregate = new DecimalAggregate(); }
    public function currencyScale(): int { return $this->scale(); }
    private function records(string $table): Builder { return (new RecordScope($this->context(), $this->period->branchId === null ? null : [$this->period->branchId]))->query($table); }
    private function dated(Builder $query, string $column): Builder { return $query->where($column, '>=', $this->period->start())->where($column, '<', $this->period->end()); }
    private function pagination($page): array { return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]; }
    private function daySql(string $column): array
    {
        $sql = 'CASE'; $bindings = [];
        foreach ($this->period->days() as $day => [$from, $to]) { $sql .= ' WHEN '.$column.' >= ? AND '.$column.' < ? THEN \''.$day.'\''; array_push($bindings, $from, $to); }
        return [$sql.' END', $bindings];
    }

    public function inventoryTrend(): array
    {
        $movements = $this->dated($this->records('stock_movements'), 'stock_movements.occurred_at')->join('catalog_variants as v', fn ($j) => $j->on('v.id', '=', 'stock_movements.variant_id')->on('v.tenant_id', '=', 'stock_movements.tenant_id'));
        $units = $this->query('catalog_units')->whereIn('id', (clone $movements)->select('v.base_unit_id')->distinct())->orderBy('id')->paginate($this->period->perPage, ['id', 'code'], 'page', $this->period->page);
        [$day, $bindings] = $this->daySql('stock_movements.occurred_at');
        $received = $this->aggregate->sumSql('CASE WHEN quantity_delta > 0 THEN quantity_delta ELSE 0 END');
        $issued = $this->aggregate->sumSql('CASE WHEN quantity_delta < 0 THEN '.$this->aggregate->negativeSql('quantity_delta').' ELSE 0 END');
        $groups = (clone $movements)->whereIn('v.base_unit_id', $units->pluck('id'))->selectRaw($day.' AS bucket, v.base_unit_id', $bindings)->selectRaw($received.' AS received, '.$issued.' AS issued')->groupBy('bucket', 'v.base_unit_id')->get();
        $values = [];
        foreach ($groups as $group) $values[$group->base_unit_id][$group->bucket] = ['received' => bcadd((string) $group->received, '0', 4), 'issued' => bcadd((string) $group->issued, '0', 4)];
        return ['items' => $units->map(fn ($unit) => ['unit_id' => $unit->id, 'unit' => $unit->code, 'series' => array_map(fn ($date) => ['date' => $date] + ($values[$unit->id][$date] ?? ['received' => '0.0000', 'issued' => '0.0000']), array_keys($this->period->days()))])->all(),
            'pagination' => $this->pagination($units), 'transfers' => null,
            'adjustments' => $this->records('stock_adjustments')->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->map(fn ($value) => (int) $value)->all(),
            'basis' => 'Positive and negative posted movement deltas by occurrence date in the business timezone. Issued quantities are displayed positive. Separate base units are never added; only units with period activity are listed and paginated. Adjustment counts are current snapshots. Transfers are not implemented.'];
    }

    public function purchaseOrders(): array
    {
        $page = $this->records('purchase_orders')->whereIn('status', ['approved', 'partial'])->orderByDesc('id')->paginate($this->period->perPage,
            ['id', 'po_number', 'supplier_id', 'location_id', 'status', 'total', 'created_at'], 'page', $this->period->page);
        $suppliers = $this->query('suppliers')->whereIn('id', $page->pluck('supplier_id'))->pluck('name', 'id');
        $locations = $this->records('locations')->whereIn('id', $page->pluck('location_id'))->pluck('name', 'id');
        return ['items' => $page->map(fn ($order) => ['id' => $order->id, 'po_number' => $order->po_number, 'supplier_name' => $suppliers[$order->supplier_id], 'location_name' => $locations[$order->location_id], 'status' => $order->status, 'total' => (string) $order->total])->all(),
            'pagination' => $this->pagination($page), 'expected_deliveries' => null, 'overdue_receipts' => null,
            'basis' => 'Current approved or partially received orders in permitted locations. These are open orders, not dated expected/overdue receipts: delivery dates are not stored.'];
    }

    public function supplierOverview(): array
    {
        $orders = $this->records('purchase_orders');
        $page = $this->query('suppliers')->whereIn('id', (clone $orders)->select('supplier_id'))->orderBy('id')->paginate($this->period->perPage, ['id', 'name'], 'page', $this->period->page);
        $ids = $page->pluck('id');
        $counts = (clone $orders)->whereIn('supplier_id', $ids)->selectRaw("supplier_id, COUNT(*) AS orders_count, SUM(CASE WHEN status IN ('approved', 'partial') THEN 1 ELSE 0 END) AS open_orders")->groupBy('supplier_id')->get()->keyBy('supplier_id');
        $balances = $this->aggregate->grouped($this->records('supplier_payables')->whereIn('supplier_id', $ids), ['outstanding' => 'outstanding_amount'], 'supplier_id');
        return ['items' => $page->map(fn ($supplier) => ['id' => $supplier->id, 'name' => $supplier->name, 'orders' => (int) $counts[$supplier->id]->orders_count, 'open_orders' => (int) $counts[$supplier->id]->open_orders, 'outstanding_payables' => $balances[$supplier->id]['outstanding'] ?? '0.0000'])->all(),
            'pagination' => $this->pagination($page), 'basis' => 'Suppliers on permitted purchase orders; counts and payable balances are current and branch scoped. Not the full directory or a supplier risk score.'];
    }

    public function accountingTrend(): array
    {
        $dashboard = new DashboardService($this->period);
        $sales = $dashboard->trend()['series'];
        $expenses = $this->dated($this->records('retail_expenses')->where('status', 'posted'), 'posted_at');
        if ((clone $expenses)->where('currency_code', '<>', $this->context()->tenant()->currency_code)->exists()) throw new Conflict('Mixed-currency expenses require an approved conversion policy.');
        [$day, $bindings] = $this->daySql('posted_at');
        $expenseDays = $this->aggregate->grouped($expenses, ['expenses' => 'amount'], $day, $bindings);
        $paymentRows = $this->dated($this->records('sale_payments'), 'recorded_at');
        $refundRows = $this->dated($this->records('refunds'), 'refunded_at');
        $currency = $this->context()->tenant()->currency_code;
        if ((clone $paymentRows)->where('currency_code', '<>', $currency)->exists() || (clone $refundRows)->where('currency_code', '<>', $currency)->exists()) throw new Conflict('Mixed-currency payment flows require an approved conversion policy.');
        $payments = $this->aggregate->grouped($paymentRows, ['amount' => 'amount'], 'method_type_snapshot');
        $refunds = $this->aggregate->grouped($refundRows, ['amount' => 'amount'], 'method_type_snapshot');
        $methods = array_values(array_unique([...array_keys($payments), ...array_keys($refunds)])); sort($methods);
        return ['series' => array_map(fn ($point) => ['date' => $point['date'], 'net_sales_excluding_tax' => $point['net_sales_excluding_tax'], 'posted_expenses' => $expenseDays[$point['date']]['expenses'] ?? '0.0000'], $sales),
            'methods' => array_map(fn ($method) => ['method_type' => $method, 'collections' => $payments[$method]['amount'] ?? '0.0000', 'refunds' => $refunds[$method]['amount'] ?? '0.0000', 'net_collections' => Decimal::sub($payments[$method]['amount'] ?? '0', $refunds[$method]['amount'] ?? '0')], $methods),
            'bank_balance' => null, 'verified_gross_profit' => null, 'net_profit' => null,
            'basis' => 'Recognized net sales excluding tax and posted expenses by business-local date. Recorded payment/refund flows are grouped by method type; bank flows are not a bank balance. Exchange credit is separate from external cash. Revenue less expenses is not net profit.'];
    }

    public function accountingExceptions(): array
    {
        $query = $this->dated($this->records('cash_sessions')->where('status', 'closed')->where('variance', '<>', '0'), 'closed_at');
        $count = (clone $query)->count();
        $items = []; $pagination = null;
        // Reports grants an aggregate count, not permission to read register documents.
        if ($this->context()->allows('cash.open')) {
            if (! $this->context()->allows('cash.manage')) $query->where('opened_by', $this->context()->membership()->id);
            $page = $query->orderByDesc('closed_at')->orderByDesc('id')->paginate($this->period->perPage, ['id', 'register_id', 'variance', 'closed_at'], 'page', $this->period->page);
            $items = $page->map(fn ($session) => ['id' => $session->id, 'register_id' => $session->register_id, 'variance' => (string) $session->variance, 'closed_at' => Carbon::parse($session->closed_at, config('app.timezone'))->toIso8601String()])->all();
            $pagination = $this->pagination($page);
        }
        return ['count' => $count, 'items' => $items, 'pagination' => $pagination, 'can_open_shifts' => $this->context()->allows('cash.open'),
            'basis' => 'Closed shifts with recorded non-zero cash variance, by close date in the selected scope. This is an exception count, not a claim that the full financial ledger or bank accounts reconcile. Shift documents require cash.open; only your own documents are listed unless cash.manage also permits other staff shifts. The report count can include additional permitted shifts.'];
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Operations\CutoverService;
use App\Domain\Operations\LegacyCustomerMapper;
use App\Domain\Operations\SaleService;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use App\Rules\DecimalInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    use TenantQueries;

    public function search(Request $request)
    {
        $this->permit('products.view');
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'scan' => ['sometimes', 'boolean']]);
        $tenant = $this->context()->id();
        $q = DB::table('catalog_variants as v')->join('catalog_products as p', fn ($j) => $j->on('p.id', '=', 'v.product_id')->on('p.tenant_id', '=', 'v.tenant_id'))->where('v.tenant_id', $tenant)->where('v.active', true)->where('p.active', true)->orderBy('v.id');
        if (! empty($data['search'])) {
            $search = '%'.addcslashes($data['search'], '%_\\').'%';
            $exact = $data['search'];
            $q->where(function ($s) use ($data, $search, $exact) {
                if (! empty($data['scan'])) {
                    $s->where('v.sku', $exact);
                } else {
                    $s->where('v.sku', 'like', $search)->orWhere('v.name', 'like', $search)->orWhere('p.name', 'like', $search);
                }
                $s->orWhereExists(fn ($b) => $b->selectRaw('1')->from('catalog_barcodes as b')->whereColumn('b.variant_id', 'v.id')->whereColumn('b.tenant_id', 'v.tenant_id')->where('b.barcode', $exact));
            });
            $q->reorder()->orderByRaw('CASE WHEN v.sku = ? OR EXISTS (SELECT 1 FROM catalog_barcodes bx WHERE bx.tenant_id = v.tenant_id AND bx.variant_id = v.id AND bx.barcode = ?) THEN 0 ELSE 1 END', [$exact, $exact])->orderBy('v.id');
        }

        return response()->json($q->select(['v.id', 'v.sku', 'v.name as variant_name', 'p.name', 'v.default_price', 'v.base_unit_id', 'v.tracking_mode'])->paginate(30));
    }

    public function quote(Request $r, SaleService $s)
    {
        $this->permit('sales.create');
        $r->validate(['held_cart_id' => ['nullable', 'required_with:held_cart_version', 'integer', $this->exists('pos_held_carts')], 'held_cart_version' => ['nullable', 'required_with:held_cart_id', 'integer', 'min:1']]);
        $data = $r->validate(['cash_session_id' => ['required', 'integer', $this->exists('cash_sessions')], 'customer_id' => ['nullable', 'integer', $this->exists('customers')], 'price_list_id' => ['nullable', 'integer', $this->exists('catalog_price_lists')], 'exchange_return_id' => ['nullable', 'integer', $this->exists('sale_returns')], 'discount_amount' => ['sometimes', 'string', 'max:20', new DecimalInput()], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.variant_id' => ['required', 'integer', 'distinct', $this->exists('catalog_variants')], 'items.*.qty' => ['required', 'string', 'max:20', new DecimalInput(positive: true)]]);

        $data += $r->only(['held_cart_id', 'held_cart_version']);

        return response()->json(['data' => $s->quote($data)], 201);
    }

    private function payments(): array
    {
        return ['payments' => ['present', 'array', 'max:10'], 'payments.*.method_id' => ['required', 'integer', 'distinct', $this->exists('payment_methods')], 'payments.*.amount' => ['required', 'string', 'max:20', new DecimalInput(positive: true)], 'payments.*.reference' => ['nullable', 'string', 'max:150']];
    }

    public function checkout(Request $r, SaleService $s)
    {
        $this->permit('sales.create');
        $this->permit('payments.record');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'quote_id' => ['required', 'integer', $this->exists('sale_quotes')]] + $this->payments());

        return response()->json(['data' => $s->checkout($data)], 201);
    }

    public function sales(Request $r)
    {
        $this->permit('sales.view');
        $data = $r->validate(['customer_id' => ['nullable', 'integer', $this->exists('customers')], 'due' => ['sometimes', 'boolean'], 'own' => ['sometimes', 'boolean'],
            'branch_id' => ['nullable', 'integer', $this->exists('branches')], 'cash_session_id' => ['nullable', 'integer', $this->exists('cash_sessions')],
            'cashier_membership_id' => ['nullable', 'integer', $this->exists('tenant_memberships')],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'], 'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $q = $this->query('sales')->orderByDesc('id');
        if (! empty($data['own'])) {
            $q->where('cashier_membership_id', $this->context()->membership()->id);
        }
        if (! empty($data['cashier_membership_id'])) {
            abort_unless($this->context()->allows('reports.view') && $this->context()->allows('team.view') && ! $this->context()->ownSalesOnly(), 403);
            $q->where('cashier_membership_id', $data['cashier_membership_id']);
        }
        foreach (['branch_id', 'cash_session_id'] as $key) {
            if (! empty($data[$key])) {
                $q->where($key, $data[$key]);
            }
        }
        if (! empty($data['from'])) {
            $zone = $this->context()->tenant()->timezone;
            if (\Illuminate\Support\Carbon::parse($data['from'], $zone)->diffInDays($data['to']) >= 366) {
                throw \Illuminate\Validation\ValidationException::withMessages(['to' => 'Choose at most 366 calendar days, including both dates.']);
            }
            $period = new \App\Domain\Dashboard\DashboardPeriod($data['from'], $data['to'], $zone);
            $q->where('sold_at', '>=', $period->start())->where('sold_at', '<', $period->end());
        }
        if (! empty($data['customer_id'])) {
            $q->where('customer_id', $data['customer_id']);
        } if (! empty($data['due'])) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('sales', 'returned_total')) {
                $q->whereRaw('paid_total - total_refunded < grand_total - returned_total');
            } else {
                $q->whereColumn('paid_total', '<', 'grand_total');
            }
        }

        return response()->json($q->paginate(50));
    }

    public function sale(Request $request, int $id)
    {
        $this->permit('sales.view');
        $sale = $this->row('sales', $id);
        $request->validate(['own' => ['sometimes', 'boolean']]);
        abort_if($request->boolean('own') && $sale->cashier_membership_id !== $this->context()->membership()->id, 404);
        $sale->sold_at = \Illuminate\Support\Carbon::parse($sale->sold_at, config('app.timezone'))->toIso8601String();
        foreach (\App\Domain\Operations\SaleAmounts::for($sale) as $key => $value) {
            $sale->$key = $value;
        }
        $sale->items = $this->query('sale_items')->where('sale_id', $id)->orderBy('id')->get();
        if (! $this->context()->allows('reports.view')) {
            foreach ($sale->items as $item) {
                unset($item->unit_cost_snapshot);
            }
        }
        $sale->payments = $this->query('sale_payments')->where('sale_id', $id)->orderBy('id')->get();
        if (\Illuminate\Support\Facades\Schema::hasTable('sale_returns')) {
            $sale->returns = $this->query('sale_returns')->where('original_sale_id', $id)->get();
            $sale->refunds = $this->query('refunds')->where('original_sale_id', $id)->get();
        }

        return response()->json(['data' => $sale]);
    }

    public function pay(Request $r, int $id, SaleService $s)
    {
        $this->permit('payments.record');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'cash_session_id' => ['required', 'integer', $this->exists('cash_sessions')]] + $this->payments());
        if (empty($data['payments'])) {
            \App\Domain\Operations\Decimal::invalid('Enter at least one payment.');
        }

        return response()->json(['data' => $s->pay($id, $data)], 201);
    }

    public function sessions(Request $request, SaleService $s)
    {
        $this->permit('cash.open');
        $filters = $request->validate(['own' => ['sometimes', 'boolean'], 'branch_id' => ['nullable', 'integer', $this->exists('branches')], 'document_id' => ['nullable', 'integer', 'min:1']]);
        $q = $this->query('cash_sessions')->orderByDesc('id');
        if (! empty($filters['branch_id'])) {
            (new \App\Domain\Tenancy\RecordScope($this->context(), [(int) $filters['branch_id']]))->apply($q, 'cash_sessions');
        }
        if (! empty($filters['document_id'])) {
            $q->where('id', $filters['document_id']);
        }
        if (! empty($filters['own'])) {
            $q->where('opened_by', $this->context()->membership()->id);
        }
        if (! $request->boolean('history')) {
            $q->where('status', 'open');
        }
        if (! $this->context()->allows('cash.manage')) {
            $q->where('opened_by', $this->context()->membership()->id);
        }

        $page = $q->paginate(50);
        $registers = $this->query('cash_registers')->whereIn('id', $page->pluck('register_id'))->get(['id', 'name', 'branch_id'])->keyBy('id');
        foreach ($page as $session) {
            $session->branch_id = $registers[$session->register_id]->branch_id;
            $session->register_name = $registers[$session->register_id]->name;
        }

        return response()->json($page);
    }

    public function open(Request $r, SaleService $s)
    {
        $this->permit('cash.open');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'register_id' => ['required', 'integer', $this->exists('cash_registers')], 'opening_cash' => ['required', 'string', 'max:20', new DecimalInput()]]);

        return response()->json(['data' => $s->open($data)], 201);
    }

    public function reconciliation(Request $request, int $id, SaleService $s)
    {
        $this->permit('cash.open');
        $request->validate(['own' => ['sometimes', 'boolean']]);
        abort_if($request->boolean('own') && $this->row('cash_sessions', $id)->opened_by !== $this->context()->membership()->id, 404);

        return response()->json(['data' => $s->reconciliation($id)]);
    }

    public function preview(int $id, CutoverService $s)
    {
        $this->permit('settings.manage');

        return response()->json(['data' => $s->preview($id)]);
    }

    public function activate(Request $r, int $id, CutoverService $s)
    {
        $this->permit('settings.manage');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'preview_hash' => ['required', 'string', 'size:64'], 'confirm_locations' => ['required', 'accepted'], 'allow_credit' => ['required', 'boolean'], 'tax_jurisdiction' => ['nullable', 'string', 'max:80']]);

        return response()->json(['data' => $s->activate($id, $data)]);
    }

    public function customerLinks()
    {
        $this->permit('legacy.view');

        return response()->json($this->query('legacy_customer_links')->orderBy('id')->paginate(50));
    }

    public function mapCustomers(LegacyCustomerMapper $s)
    {
        $this->permit('legacy.manage');

        return response()->json(['data' => $s->run()]);
    }

    public function resolveCustomer(Request $r, int $id, CutoverService $s)
    {
        $this->permit('legacy.manage');
        abort_unless($this->context()->isOwner(), 403);
        $data = $r->validate(['customer_id' => ['required', 'integer', $this->exists('customers')]]);

        return $s->atomic(function () use ($id, $data) {
            $this->row('legacy_customer_links', $id);
            $this->query('legacy_customer_links')->where('id', $id)->update(['customer_id' => $data['customer_id'], 'status' => 'reviewed', 'updated_at' => now()]);
            $this->audit('legacy.customer.reviewed', 'legacy_customer_link', $id, $data);

            return response()->json(['data' => ['id' => $id]]);
        });
    }
}

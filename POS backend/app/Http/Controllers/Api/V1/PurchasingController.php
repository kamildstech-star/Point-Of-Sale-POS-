<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Operations\PurchasingService;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use App\Rules\DecimalInput;
use Illuminate\Http\Request;

class PurchasingController extends Controller
{
    use TenantQueries;

    private const TABLES = ['orders' => 'purchase_orders', 'receipts' => 'goods_receipts', 'returns' => 'purchase_returns', 'payables' => 'supplier_payables', 'settlements' => 'supplier_settlements'];

    public function index(Request $request, string $resource)
    {
        $this->permit('purchases.view');
        $data = $request->validate(['branch_id' => ['nullable', 'integer', $this->exists('branches')],
            'document_id' => ['nullable', 'integer', 'min:1'], 'status' => ['nullable', \Illuminate\Validation\Rule::in(['draft', 'approved', 'partial', 'received', 'open'])]]);
        $query = $this->query(self::TABLES[$resource] ?? abort(404));
        if (! empty($data['document_id'])) {
            $query->where('id', $data['document_id']);
        }
        if (! empty($data['branch_id'])) {
            (new \App\Domain\Tenancy\RecordScope($this->context(), [(int) $data['branch_id']]))->apply($query, self::TABLES[$resource]);
        }
        if (! empty($data['status'])) {
            abort_unless($resource === 'orders', 422, 'Order status applies only to orders.');
            $query->whereIn('status', $data['status'] === 'open' ? ['approved', 'partial'] : [$data['status']]);
        }
        $result = $query->orderByDesc('id')->paginate(50);
        foreach ($result as $doc) {
            if (isset($doc->supplier_id)) {
                $doc->supplier_name = $this->row('suppliers', $doc->supplier_id)->name;
            } if (isset($doc->location_id)) {
                $doc->location_name = $this->row('locations', $doc->location_id)->name;
            }
        }

        return response()->json($result);
    }

    public function show(string $resource, int $id)
    {
        $this->permit('purchases.view');
        $doc = $this->row(self::TABLES[$resource] ?? abort(404), $id);
        if ($resource === 'orders') {
            $doc->items = $this->query('purchase_order_items')->where('purchase_order_id', $id)->get();
        }
        if ($resource === 'receipts') {
            $doc->items = $this->query('goods_receipt_items')->where('goods_receipt_id', $id)->get();
        }
        if ($resource === 'returns') {
            $doc->items = $this->query('purchase_return_items')->where('purchase_return_id', $id)->get();
        }
        if (isset($doc->items)) {
            foreach ($doc->items as $line) {
                $line->sku = $this->row('catalog_variants', $line->variant_id)->sku;
            }
        }

        return response()->json(['data' => $doc]);
    }

    public function order(Request $r, PurchasingService $s)
    {
        $this->permit('purchases.manage');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'supplier_id' => ['required', 'integer', $this->exists('suppliers')], 'location_id' => ['required', 'integer', $this->exists('locations')], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.variant_id' => ['required', 'integer', 'distinct', $this->exists('catalog_variants')], 'items.*.ordered_qty' => ['required', 'string', new DecimalInput(positive: true)], 'items.*.unit_cost' => ['required', 'string', new DecimalInput()], 'items.*.tax_rate' => ['sometimes', 'string', new DecimalInput(max: '100')]]);

        return response()->json(['data' => $s->order($data)], 201);
    }

    public function approve(int $id, PurchasingService $s)
    {
        $this->permit('purchases.approve');

        return response()->json(['data' => $s->approve($id)]);
    }

    public function receive(Request $r, int $id, PurchasingService $s)
    {
        $this->permit('purchases.manage');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.purchase_order_item_id' => ['required', 'integer', 'distinct', $this->exists('purchase_order_items')], 'items.*.accepted_qty' => ['required', 'string', new DecimalInput()], 'items.*.rejected_qty' => ['sometimes', 'string', new DecimalInput()]]);

        return response()->json(['data' => $s->receive($id, $data)], 201);
    }

    public function returnGoods(Request $r, int $id, PurchasingService $s)
    {
        $this->permit('purchases.approve');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'reason' => ['required', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.goods_receipt_item_id' => ['required', 'integer', 'distinct', $this->exists('goods_receipt_items')], 'items.*.quantity' => ['required', 'string', new DecimalInput(positive: true)]]);

        return response()->json(['data' => $s->returnGoods($id, $data)], 201);
    }

    public function settle(Request $r, int $id, PurchasingService $s)
    {
        $this->permit('purchases.settle');
        $data = $r->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'amount' => ['required', 'string', new DecimalInput(positive: true)], 'reference' => ['required', 'string', 'max:150']]);

        return response()->json(['data' => $s->settle($id, $data)], 201);
    }

    public function reconcile(PurchasingService $s)
    {
        $this->permit('purchases.view');

        return response()->json(['data' => $s->reconcile()]);
    }
}

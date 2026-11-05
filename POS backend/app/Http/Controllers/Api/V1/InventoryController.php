<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Operations\InventoryPostingService;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use App\Rules\DecimalInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    use TenantQueries;

    public function index(Request $request, string $resource)
    {
        $this->permit('inventory.view');
        $table = ['balances' => 'inventory_balances', 'movements' => 'stock_movements', 'adjustments' => 'stock_adjustments', 'counts' => 'stock_counts', 'reservations' => 'stock_reservations'][$resource] ?? abort(404);
        $data = $request->validate(['location_id' => ['nullable', 'integer', $this->exists('locations')], 'variant_id' => ['nullable', 'integer', $this->exists('catalog_variants')],
            'branch_id' => ['nullable', 'integer', $this->exists('branches')], 'document_id' => ['nullable', 'integer', 'min:1'],
            'stock_state' => ['nullable', \Illuminate\Validation\Rule::in(['low', 'out'])]]);
        $q = DB::table($table.' as d')->where('d.tenant_id', $this->context()->id())->join('locations as l', fn ($j) => $j->on('l.id', '=', 'd.location_id')->on('l.tenant_id', '=', 'd.tenant_id'))->select(['d.*', 'l.name as location_name'])->orderByDesc('d.id');
        (new \App\Domain\Tenancy\RecordScope($this->context()))->apply($q, $table, 'd');
        if (in_array($resource, ['balances', 'movements', 'reservations'])) {
            $q->join('catalog_variants as v', fn ($j) => $j->on('v.id', '=', 'd.variant_id')->on('v.tenant_id', '=', 'd.tenant_id'))->addSelect('v.sku');
        }
        foreach (['location_id', 'variant_id'] as $key) {
            if (! empty($data[$key]) && (! in_array($resource, ['adjustments', 'counts']) || $key !== 'variant_id')) {
                $q->where('d.'.$key, $data[$key]);
            }
        }

        if (! empty($data['branch_id'])) {
            $q->where('l.branch_id', $data['branch_id']);
        }
        if (! empty($data['document_id'])) {
            $q->where('d.id', $data['document_id']);
        }
        if (! empty($data['stock_state'])) {
            abort_unless($resource === 'balances', 422, 'Stock state applies only to balances.');
            if ($data['stock_state'] === 'out') {
                $q->whereColumn('d.on_hand', '<=', 'd.reserved');
            } else {
                $threshold = app(\App\Domain\Operations\WorkspaceService::class)->settings()['low_stock_threshold'];
                if ($threshold === null) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['stock_state' => 'Configure a low stock threshold before filtering low stock.']);
                }
                $q->whereColumn('d.on_hand', '>', 'd.reserved')->whereRaw('d.on_hand - d.reserved <= ?', [(string) $threshold]);
            }
        }

        $rows = $q->paginate(50);
        if (! $this->context()->canViewCosts()) {
            foreach ($rows as $row) {
                unset($row->unit_cost_snapshot);
            }
        }

        return response()->json($rows);
    }

    public function show(string $resource, int $id)
    {
        $this->permit('inventory.view');
        $count = $resource === 'counts';
        abort_unless(in_array($resource, ['counts', 'adjustments']), 404);
        $doc = $this->row($count ? 'stock_counts' : 'stock_adjustments', $id);
        $doc->items = $this->query($count ? 'stock_count_items' : 'stock_adjustment_items')->where($count ? 'count_id' : 'adjustment_id', $id)->get();
        foreach ($doc->items as $line) {
            $line->sku = $this->row('catalog_variants', $line->variant_id)->sku;
        }

        return response()->json(['data' => $doc]);
    }

    public function document(Request $request, string $resource, InventoryPostingService $service)
    {
        $this->permit('inventory.adjust');
        abort_unless(in_array($resource, ['counts', 'adjustments']), 404);
        $count = $resource === 'counts';
        $field = $count ? 'counted_qty' : 'quantity_delta';
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'location_id' => ['required', 'integer', $this->exists('locations')], 'reason' => ['required', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.variant_id' => ['required', 'integer', 'distinct', $this->exists('catalog_variants')], 'items.*.'.$field => ['required', 'string', 'max:20', $count ? new DecimalInput() : new DecimalInput(signed: true, nonzero: true)]]);

        return response()->json(['data' => $service->document($data, $count)], 201);
    }

    public function approve(string $resource, int $id, InventoryPostingService $service)
    {
        $this->permit('inventory.approve');
        abort_unless(in_array($resource, ['counts', 'adjustments']), 404);

        return response()->json(['data' => $service->approve($id, $resource === 'counts')]);
    }

    public function reserve(Request $request, InventoryPostingService $service)
    {
        $this->permit('inventory.reserve');
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'location_id' => ['required', 'integer', $this->exists('locations')], 'variant_id' => ['required', 'integer', $this->exists('catalog_variants')], 'quantity' => ['required', 'string', 'max:20', new DecimalInput(positive: true)], 'reference' => ['required', 'string', 'max:120'], 'minutes' => ['sometimes', 'integer', 'between:1,1440']]);

        return response()->json(['data' => $service->reserve($data)], 201);
    }

    public function release(int $id, InventoryPostingService $service)
    {
        $this->permit('inventory.reserve');

        return response()->json(['data' => $service->release($id)]);
    }

    public function reconcile(InventoryPostingService $service)
    {
        $this->permit('inventory.approve');

        return response()->json(['data' => $service->reconcile()]);
    }
}

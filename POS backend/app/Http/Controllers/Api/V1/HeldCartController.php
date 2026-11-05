<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Operations\HeldCartService;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use App\Rules\DecimalInput;
use Illuminate\Http\Request;

class HeldCartController extends Controller
{
    use TenantQueries;

    private function authorizeCart(HeldCartService $service): void
    {
        $this->permit('sales.create');
        $service->available();
    }

    public function index(Request $request, HeldCartService $service)
    {
        $this->authorizeCart($service);
        $data = $request->validate(['branch_id' => ['nullable', 'integer', $this->exists('branches')], 'page' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'tenant_id' => ['prohibited'], 'created_by' => ['prohibited'], 'user_id' => ['prohibited']]);
        $query = $this->query('pos_held_carts')->where('status', 'held');
        if (! empty($data['branch_id'])) {
            $query->where('branch_id', $data['branch_id']);
        }
        $page = $query->orderByDesc('id')->paginate(10);
        $page->setCollection($page->getCollection()->map(fn ($held) => $service->header($held)));

        return response()->json($page)->header('Cache-Control', 'private, no-store');
    }

    public function show(int $id, HeldCartService $service)
    {
        $this->authorizeCart($service);

        return response()->json(['data' => $service->show($id)])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, HeldCartService $service)
    {
        $this->authorizeCart($service);
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'label' => ['nullable', 'string', 'max:80'],
            'cash_session_id' => ['required', 'integer', $this->exists('cash_sessions')], 'customer_id' => ['nullable', 'integer', $this->exists('customers')],
            'price_list_id' => ['nullable', 'integer', $this->exists('catalog_price_lists')], 'discount_amount' => ['sometimes', 'string', new DecimalInput()],
            'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.variant_id' => ['required', 'integer', 'distinct', $this->exists('catalog_variants')],
            'items.*.qty' => ['required', 'string', new DecimalInput(positive: true)],
            'held_cart_id' => ['nullable', 'required_with:held_cart_version', 'integer', $this->exists('pos_held_carts')], 'held_cart_version' => ['nullable', 'required_with:held_cart_id', 'integer', 'min:1'],
            'tenant_id' => ['prohibited'], 'created_by' => ['prohibited'], 'payments' => ['prohibited'], 'exchange_return_id' => ['prohibited']]);

        return response()->json(['data' => $service->save($data)], 201);
    }

    public function discard(Request $request, int $id, HeldCartService $service)
    {
        $this->authorizeCart($service);
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:100'], 'version' => ['required', 'integer', 'min:1']]);

        return response()->json(['data' => $service->discard($id, $data)]);
    }
}

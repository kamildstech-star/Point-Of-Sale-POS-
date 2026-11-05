<?php

namespace App\Domain\Operations;

use Illuminate\Support\Facades\Schema;

class HeldCartService extends Operation
{
    public function available(): void
    {
        abort_unless(Schema::hasTable('pos_held_carts'), 503, 'Saved carts are not available until the held-cart migration is applied.');
    }

    public function save(array $data): array
    {
        $this->available();

        return $this->once('pos.hold:'.$this->actor(), $data['idempotency_key'], $data, function () use ($data) {
            [$session, , $branch] = app(SaleService::class)->session($data['cash_session_id']);
            if (! empty($data['customer_id']) && ! $this->row('customers', $data['customer_id'])->active) {
                throw new Conflict('Choose an active customer.');
            }
            if (! empty($data['price_list_id'])) {
                $list = $this->row('catalog_price_lists', $data['price_list_id']);
                if (! $list->active || $list->currency_code !== $this->context()->tenant()->currency_code) {
                    throw new Conflict('Choose an active price list in the business currency.');
                }
            }
            $discount = Decimal::value($data['discount_amount'] ?? '0');
            if (Decimal::cmp($discount, '0') > 0 && ! $this->context()->allows('sales.discount')) {
                abort(403);
            }
            if (Decimal::cmp($discount, Decimal::round($discount, $this->scale())) !== 0) {
                Decimal::invalid('Discount must match currency precision.');
            }
            $items = [];
            foreach ($data['items'] as $item) {
                $quantity = Decimal::value($item['qty']);
                $this->variant($item['variant_id'], $quantity);
                $items[] = ['variant_id' => (int) $item['variant_id'], 'qty' => $quantity];
            }
            $cart = ['customer_id' => $data['customer_id'] ?? null, 'price_list_id' => $data['price_list_id'] ?? null, 'discount_amount' => $discount, 'items' => $items];
            $attributes = ['source_session_id' => $session->id, 'label' => $data['label'] ?? null, 'cart_json' => json_encode($cart, JSON_THROW_ON_ERROR)];
            if (! empty($data['held_cart_id'])) {
                $held = $this->assertHeld($data, $branch->id);
                $this->update('pos_held_carts', $held->id, $attributes + ['version' => $held->version + 1]);
                $id = $held->id;
            } else {
                if ($this->query('pos_held_carts')->where('status', 'held')->count() >= 50) {
                    throw new Conflict('Recall or discard a saved cart before holding more. The limit is 50 active carts per staff member.');
                }
                $id = $this->insert('pos_held_carts', $attributes + ['branch_id' => $branch->id, 'created_by' => $this->actor()]);
            }
            $this->audit('pos.cart.held', 'pos_held_cart', $id);

            return $this->header($this->row('pos_held_carts', $id));
        });
    }

    public function header(object $held): array
    {
        return ['id' => $held->id, 'branch_id' => $held->branch_id, 'source_session_id' => $held->source_session_id,
            'label' => $held->label, 'status' => $held->status, 'version' => $held->version,
            'item_count' => count(json_decode($held->cart_json, true, 512, JSON_THROW_ON_ERROR)['items']),
            'updated_at' => \Illuminate\Support\Carbon::parse($held->updated_at, config('app.timezone'))->toIso8601String()];
    }

    public function show(int $id): array
    {
        $this->available();
        $held = $this->row('pos_held_carts', $id);
        if ($held->status !== 'held') {
            throw new Conflict('This cart was already sold or discarded.');
        }
        $cart = json_decode($held->cart_json, true, 512, JSON_THROW_ON_ERROR);
        $variants = $this->query('catalog_variants')->whereIn('id', array_column($cart['items'], 'variant_id'))->get(['id', 'sku', 'name', 'product_id', 'active'])->keyBy('id');
        $products = $this->query('catalog_products')->whereIn('id', $variants->pluck('product_id'))->get(['id', 'name', 'active'])->keyBy('id');
        foreach ($cart['items'] as &$item) {
            $variant = $variants[$item['variant_id']];
            $product = $products[$variant->product_id];
            $item += ['sku' => $variant->sku, 'name' => $product->name, 'active' => (bool) $variant->active && (bool) $product->active];
        }

        return $this->header($held) + ['cart' => $cart];
    }

    public function assertHeld(array $data, int $branchId): object
    {
        $this->available();
        $held = $this->row('pos_held_carts', $data['held_cart_id']);
        if ($held->status !== 'held' || $held->version !== (int) $data['held_cart_version']) {
            throw new Conflict('This saved cart changed or was completed in another tab. Recall it again before continuing.');
        }
        if ($held->branch_id !== $branchId) {
            throw new Conflict('Recall this cart in a register shift in its original branch.');
        }

        return $held;
    }

    public function consume(array $input, int $saleId, int $branchId): void
    {
        if (empty($input['held_cart_id'])) {
            return;
        }
        $held = $this->assertHeld($input, $branchId);
        $this->update('pos_held_carts', $held->id, ['status' => 'sold', 'consumed_sale_id' => $saleId, 'version' => $held->version + 1]);
        $this->audit('pos.cart.sold', 'pos_held_cart', $held->id, ['sale_id' => $saleId]);
    }

    public function discard(int $id, array $data): array
    {
        $this->available();

        return $this->once('pos.discard:'.$this->actor(), $data['idempotency_key'], ['id' => $id] + $data, function () use ($id, $data) {
            $held = $this->row('pos_held_carts', $id);
            $this->assertHeld(['held_cart_id' => $id, 'held_cart_version' => $data['version']], $held->branch_id);
            $this->update('pos_held_carts', $id, ['status' => 'discarded', 'version' => $held->version + 1]);
            $this->audit('pos.cart.discarded', 'pos_held_cart', $id);

            return ['id' => $id, 'status' => 'discarded'];
        });
    }
}

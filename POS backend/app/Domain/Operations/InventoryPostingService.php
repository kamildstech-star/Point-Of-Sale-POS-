<?php

namespace App\Domain\Operations;

class InventoryPostingService extends Operation
{
    public function balance(int $location, int $variant): object
    {
        $this->query('inventory_balances')->insertOrIgnore(['tenant_id' => $this->context()->id(), 'location_id' => $location, 'variant_id' => $variant, 'on_hand' => '0', 'reserved' => '0', 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);

        return $this->query('inventory_balances')->where('location_id', $location)->where('variant_id', $variant)->lockForUpdate()->first();
    }

    public function post(int $location, int $variant, string $delta, string $type, string $referenceType, int $reference, string $event, ?string $cost = null, bool $opening = false, bool $historical = false): int
    {
        return $this->atomic(function () use ($location, $variant, $delta, $type, $referenceType, $reference, $event, $cost, $opening, $historical) {
            $delta = Decimal::value($delta, true);
            $this->location($location, ! $opening);
            $this->variant($variant, $delta, true, $historical);
            $hash = hash('sha256', json_encode([$location, $variant, $delta, $type, $referenceType, $reference, $cost]));
            $existing = $this->query('stock_movements')->where('source_event_id', $event)->first();
            if ($existing) {
                if ($existing->payload_hash !== $hash) {
                    throw new Conflict('A stock event was repeated with different quantities.');
                }

                return $existing->id;
            }
            $this->expire();
            $balance = $this->balance($location, $variant);
            $next = Decimal::add((string) $balance->on_hand, $delta);
            if (Decimal::cmp($next, (string) $balance->reserved) < 0) {
                throw new Conflict('Insufficient available stock. Refresh the quote or release reservations.');
            }
            if (Decimal::cmp($next, '99999999999999') > 0) {
                Decimal::invalid('Stock balance is outside the supported range.');
            }
            $id = $this->insert('stock_movements', ['location_id' => $location, 'variant_id' => $variant, 'actor_id' => $this->actor(), 'quantity_delta' => $delta, 'unit_cost_snapshot' => $cost, 'movement_type' => $type, 'reference_type' => $referenceType, 'reference_id' => $reference, 'source_event_id' => $event, 'payload_hash' => $hash, 'occurred_at' => now()]);
            $this->update('inventory_balances', $balance->id, ['on_hand' => $next, 'version' => $balance->version + 1]);

            if (\Illuminate\Support\Facades\Schema::hasTable('tenant_settings')) {
                $threshold = app(WorkspaceService::class)->settings()['low_stock_threshold'];
                if ($threshold !== null && Decimal::cmp(Decimal::sub($next, (string) $balance->reserved), (string) $threshold) <= 0) {
                    app(WorkspaceService::class)->notify('stock.low', $event, ['sku' => $this->row('catalog_variants', $variant)->sku, 'quantity' => Decimal::sub($next, (string) $balance->reserved)]);
                }
            }

            return $id;
        });
    }

    public function expire(): void
    {
        foreach ($this->query('stock_reservations')->where('status', 'active')->where('expires_at', '<=', now())->orderBy('location_id')->orderBy('variant_id')->lockForUpdate()->get() as $r) {
            $this->releaseInternal($r, 'expired');
        }
    }

    public function reserve(array $data): array
    {
        return $this->once('inventory.reserve', $data['idempotency_key'], $data, function () use ($data) {
            $qty = Decimal::value($data['quantity']);
            if (Decimal::cmp($qty, '0') <= 0) {
                Decimal::invalid('Reservation quantity must be positive.');
            }
            $this->location($data['location_id']);
            $this->variant($data['variant_id'], $qty, true);
            $this->expire();
            $b = $this->balance($data['location_id'], $data['variant_id']);
            if (Decimal::cmp(Decimal::sub((string) $b->on_hand, (string) $b->reserved), $qty) < 0) {
                throw new Conflict('Insufficient stock to reserve.');
            }
            $id = $this->insert('stock_reservations', ['location_id' => $data['location_id'], 'variant_id' => $data['variant_id'], 'quantity' => $qty, 'reference' => $data['reference'], 'created_by' => $this->actor(), 'expires_at' => now()->addMinutes($data['minutes'] ?? 15), 'status' => 'active']);
            $this->update('inventory_balances', $b->id, ['reserved' => Decimal::add((string) $b->reserved, $qty), 'version' => $b->version + 1]);
            $this->audit('inventory.reserved', 'reservation', $id);

            return ['id' => $id];
        });
    }

    public function release(int $id): array
    {
        return $this->atomic(function () use ($id) {
            $r = $this->row('stock_reservations', $id);
            if ($r->status === 'active') {
                $this->releaseInternal($r, 'released');
            }

            return ['id' => $id];
        });
    }

    private function releaseInternal(object $r, string $status): void
    {
        $b = $this->balance($r->location_id, $r->variant_id);
        $this->update('inventory_balances', $b->id, ['reserved' => Decimal::sub((string) $b->reserved, (string) $r->quantity), 'version' => $b->version + 1]);
        $this->update('stock_reservations', $r->id, ['status' => $status]);
        $this->audit('inventory.reservation.'.$status, 'reservation', $r->id);
    }

    public function document(array $data, bool $count): array
    {
        return $this->once($count ? 'inventory.count' : 'inventory.adjustment', $data['idempotency_key'], $data, function () use ($data, $count) {
            $this->location($data['location_id']);
            $this->expire();
            $id = $this->insert($count ? 'stock_counts' : 'stock_adjustments', ['location_id' => $data['location_id'], 'created_by' => $this->actor(), 'reason' => $data['reason'], 'status' => 'draft']);
            $items = $data['items'];
            usort($items, fn ($a, $b) => $a['variant_id'] <=> $b['variant_id']);
            foreach ($items as $item) {
                $qty = Decimal::value($item[$count ? 'counted_qty' : 'quantity_delta'], ! $count);
                $this->variant($item['variant_id'], $qty, true);
                $b = $this->balance($data['location_id'], $item['variant_id']);
                $this->insert($count ? 'stock_count_items' : 'stock_adjustment_items', ['variant_id' => $item['variant_id']] + ($count ? ['count_id' => $id, 'expected_qty' => $b->on_hand, 'counted_qty' => $qty, 'variance_qty' => Decimal::sub($qty, (string) $b->on_hand), 'balance_version' => $b->version] : ['adjustment_id' => $id, 'quantity_delta' => $qty]));
            }
            $this->audit($count ? 'inventory.count.created' : 'inventory.adjustment.created', 'inventory_document', $id);

            return ['id' => $id];
        });
    }

    public function approve(int $id, bool $count): array
    {
        return $this->atomic(function () use ($id, $count) {
            $table = $count ? 'stock_counts' : 'stock_adjustments';
            $doc = $this->row($table, $id);
            if ($doc->status === 'posted') {
                return ['id' => $id, 'status' => 'posted'];
            }
            $this->location($doc->location_id);
            $this->expire();
            $items = $this->query($count ? 'stock_count_items' : 'stock_adjustment_items')->where($count ? 'count_id' : 'adjustment_id', $id)->orderBy('variant_id')->get();
            foreach ($items as $line) {
                if ($count && $this->balance($doc->location_id, $line->variant_id)->version != $line->balance_version) {
                    throw new Conflict('Stock changed after this count started. Create a new count.');
                }
                $this->post($doc->location_id, $line->variant_id, (string) ($count ? $line->variance_qty : $line->quantity_delta), $count ? 'count' : 'adjustment', $table, $id, $table.':'.$id.':'.$line->id);
            }
            $this->update($table, $id, ['status' => 'posted', 'approved_by' => $this->actor(), 'posted_at' => now()]);
            $this->audit('inventory.document.posted', $table, $id);

            return ['id' => $id, 'status' => 'posted'];
        });
    }

    public function reconcile(): array
    {
        return $this->atomic(function () {
            $this->expire();
            $issues = [];
            foreach ($this->query('inventory_balances')->get() as $b) {
                $sum = '0';
                foreach ($this->query('stock_movements')->where('location_id', $b->location_id)->where('variant_id', $b->variant_id)->pluck('quantity_delta') as $delta) {
                    $sum = Decimal::add($sum, (string) $delta);
                }
                $held = '0';
                foreach ($this->query('stock_reservations')->where('location_id', $b->location_id)->where('variant_id', $b->variant_id)->where('status', 'active')->pluck('quantity') as $qty) {
                    $held = Decimal::add($held, (string) $qty);
                }
                if (Decimal::cmp($sum, (string) $b->on_hand) !== 0 || Decimal::cmp($held, (string) $b->reserved) !== 0) {
                    $issues[] = ['balance_id' => $b->id, 'ledger' => $sum, 'on_hand' => $b->on_hand, 'reservations' => $held, 'reserved' => $b->reserved];
                }
            }

            return ['reconciled' => ! $issues, 'issues' => $issues, 'balances' => $this->query('inventory_balances')->count(), 'movements' => $this->query('stock_movements')->count()];
        });
    }
}

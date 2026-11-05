<?php

namespace App\Domain\Operations;

use App\Domain\Tenancy\TenantQueries;
use Illuminate\Support\Facades\DB;

abstract class Operation
{
    use TenantQueries;

    /** A tenant mutex gives a single lock order for inventory, money and cutover.
     * Keep these short DB-only transactions; no HTTP/files/email while locked. */
    public function atomic(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            $tenant = DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            if (! $tenant || ! in_array($tenant->status, ['active', 'trialing'])) {
                throw new Conflict('This business is not active.');
            }
            $member = $this->context()->membership();
            abort_unless(DB::table('tenant_memberships')->where('tenant_id', $tenant->id)->where('id', $member->id)->where('user_id', $member->user_id)->where('role_id', $member->role_id)->where('status', 'active')->exists(), 403);
            $fingerprint = $this->context()->accessFingerprint();
            $this->context()->resolve($tenant->id, $member->user_id);
            abort_unless(hash_equals($fingerprint, $this->context()->accessFingerprint()), 403);
            app(\App\Domain\Billing\TenantAccess::class)->assertWritable();

            return $callback();
        }, 3);
    }

    public function once(string $operation, string $key, array $input, callable $callback): array
    {
        if (! preg_match('/^[A-Za-z0-9:_-]{8,100}$/D', $key)) {
            Decimal::invalid('Supply an idempotency key of 8–100 letters, digits, colons, underscores or hyphens.');
        }

        return $this->atomic(function () use ($operation, $key, $input, $callback) {
            $hash = hash('sha256', json_encode($this->canonical($input), JSON_THROW_ON_ERROR));
            $record = $this->query('idempotency_records')->where('operation', $operation)->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($record) {
                if ($this->context()->restricted() && (($record->actor_membership_id ?? null) !== $this->actor() || ! hash_equals($this->context()->accessFingerprint(), $record->access_fingerprint ?? ''))) {
                    throw new Conflict('This request key belongs to a different actor or access scope.');
                }
                if (! hash_equals($record->request_hash, $hash)) {
                    throw new Conflict('This request key was already used for different data.');
                }

                return json_decode($record->response_json, true, 512, JSON_THROW_ON_ERROR);
            }
            $result = $callback();
            $access = $this->context()->hasAccessSchema() ? ['actor_membership_id' => $this->actor(), 'access_fingerprint' => $this->context()->accessFingerprint()] : [];
            $this->insert('idempotency_records', ['operation' => $operation, 'idempotency_key' => $key, 'request_hash' => $hash, 'response_json' => json_encode($result, JSON_THROW_ON_ERROR)] + $access);

            return $result;
        });
    }

    private function canonical(array $input): array
    {
        if (! array_is_list($input)) {
            ksort($input);
        }
        foreach ($input as &$v) {
            if (is_array($v)) {
                $v = $this->canonical($v);
            }
        }

        return $input;
    }

    protected function insert(string $table, array $data): int
    {
        return DB::table($table)->insertGetId($data + ['tenant_id' => $this->context()->id(), 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function update(string $table, int $id, array $data): void
    {
        $this->query($table)->where('id', $id)->update($data + ['updated_at' => now()]);
    }

    protected function actor(): int
    {
        return $this->context()->membership()->id;
    }

    protected function location(int $id, bool $writable = true): object
    {
        $location = $this->row('locations', $id);
        if ($location->status !== 'active') {
            throw new Conflict('The stock location is inactive.');
        }
        if ($writable && $this->context()->tenant()->legacy_key && ! DB::table('tenants')->where('id', $this->context()->id())->value('legacy_stock_frozen_at')) {
            throw new Conflict('Activate a branch and import legacy opening stock before posting new inventory.');
        }

        return $location;
    }

    protected function variant(int $id, string $quantity, bool $physical = false, bool $historical = false): object
    {
        $variant = $this->row('catalog_variants', $id);
        $product = $this->row('catalog_products', $variant->product_id);
        $unit = $this->row('catalog_units', $variant->base_unit_id);
        if (! $historical && (! $variant->active || ! $product->active || ! $unit->active)) {
            throw new Conflict('The product, variant or unit is inactive.');
        }
        if ($variant->tracking_mode !== 'none') {
            throw new Conflict('Batch and serial posting is not enabled. Use an untracked variant or complete the tracking module before transacting.');
        }
        if (bccomp($quantity, bcadd($quantity, '0', $unit->quantity_precision), 4) !== 0) {
            Decimal::invalid('Quantity does not match the unit precision.');
        }
        if ($physical && ! $product->track_inventory) {
            Decimal::invalid('Stock cannot be posted for a service or non-inventory product.');
        }
        $variant->product = $product;
        $variant->unit = $unit;

        return $variant;
    }

    protected function scale(): int
    {
        // Currency exponent is ISO minor-unit precision, not a branch preference.
        return match ($this->context()->tenant()->currency_code) {
            'JPY', 'KRW', 'VND' => 0,
            'BHD', 'JOD', 'KWD', 'OMR', 'TND' => 3,
            'PKR', 'USD', 'EUR', 'GBP', 'INR', 'AED', 'SAR', 'CAD', 'AUD', 'NZD', 'CHF', 'CNY', 'HKD', 'SGD', 'MYR', 'BDT', 'NPR', 'LKR', 'QAR', 'ZAR', 'THB', 'TRY', 'RUB' => 2,
            default => throw new Conflict('Configure and verify this currency’s minor-unit precision before activating retail operations.'),
        };
    }
}

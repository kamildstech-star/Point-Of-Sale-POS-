<?php

namespace App\Domain\Operations;

use Illuminate\Support\Facades\DB;

class CutoverService extends Operation
{
    public function preview(int $branchId): array
    {
        return $this->atomic(fn () => $this->snapshot($branchId));
    }

    private function snapshot(int $branchId): array
    {
        $branch = $this->row('branches', $branchId);
        $rows = [];
        $issues = [];
        $frozen = DB::table('tenants')->where('id', $this->context()->id())->value('legacy_stock_frozen_at');
        if ($this->context()->tenant()->legacy_key && ! $frozen) {
            foreach ($this->query('stocks')->orderBy('id')->lockForUpdate()->get() as $stock) {
                $mapping = $this->query('legacy_record_mappings')->where('source_table', 'stocks')->where('source_pk', $stock->id)->where('target_table', 'catalog_variants')->first();
                if (! $mapping) {
                    $issues[] = 'Unmapped stock ID '.$stock->id;

                    continue;
                }
                $stored = json_decode($mapping->mapping_json, true, 512, JSON_THROW_ON_ERROR)['source'];
                $current = ['stock' => (array) $stock];
                foreach (['product' => ['products', $stock->product_id], 'school' => ['schools', $stock->school_id], 'size' => ['sizes', $stock->size_id]] as $key => [$table, $id]) {
                    $current[$key] = (array) $this->row($table, $id);
                }
                $identity = $current;
                unset($identity['stock']['quantity'], $identity['stock']['updated_at'], $stored['stock']['quantity'], $stored['stock']['updated_at']);
                if ($identity != $stored) {
                    $issues[] = 'Source identity or price changed for stock ID '.$stock->id.'. Review its catalog mapping.';
                }
                if ($stock->quantity < 0) {
                    $issues[] = 'Negative legacy stock ID '.$stock->id;
                }
                $location = $this->row('locations', $mapping->location_id);
                if ($location->status !== 'active') {
                    $issues[] = 'Inactive mapped location for stock ID '.$stock->id;
                }
                $existing = $this->query('inventory_balances')->where('location_id', $mapping->location_id)->where('variant_id', $mapping->target_pk)->first();
                if ($existing && (Decimal::cmp((string) $existing->on_hand, '0') !== 0 || Decimal::cmp((string) $existing->reserved, '0') !== 0)) {
                    $issues[] = 'Mapped variant already has stock for source ID '.$stock->id;
                }
                $rows[] = ['stock_id' => $stock->id, 'mapping_id' => $mapping->id, 'variant_id' => $mapping->target_pk, 'location_id' => $mapping->location_id, 'quantity' => (string) $stock->quantity, 'unit_cost' => (string) $stock->base_price, 'source_checksum' => hash('sha256', json_encode($current, JSON_THROW_ON_ERROR))];
            }
            if ($this->query('legacy_record_mappings')->where('source_table', 'stocks')->where('target_table', 'catalog_variants')->count() !== count($rows)) {
                $issues[] = 'Mapping count does not match the current source stock rows.';
            }
        }

        return ['branch_id' => $branch->id, 'engine' => $branch->pos_engine, 'legacy_frozen_at' => $frozen, 'opening_rows' => $rows, 'issues' => $issues, 'preview_hash' => hash('sha256', json_encode([$branch->id, $frozen, $rows, $issues], JSON_THROW_ON_ERROR)), 'legacy_freeze_scope' => $this->context()->tenant()->legacy_key ? 'All legacy stock, order, return and catalog mutation routes for this business. Legacy data has no branch ownership.' : null];
    }

    public function activate(int $branchId, array $data): array
    {
        if (! $this->context()->isOwner()) {
            abort(403);
        }

        return $this->once('pos.activate', $data['idempotency_key'], ['branch_id' => $branchId] + $data, function () use ($branchId, $data) {
            $branch = $this->row('branches', $branchId);
            if ($branch->status !== 'active') {
                throw new Conflict('Activate the branch first.');
            }
            $preview = $this->snapshot($branchId);
            if (! hash_equals($preview['preview_hash'], $data['preview_hash']) || $preview['issues']) {
                throw new Conflict('Cutover snapshot changed or has unresolved mapping issues. Review a fresh preview.');
            }
            if (! $data['confirm_locations']) {
                Decimal::invalid('Confirm that the mapped physical stock locations have been reviewed.');
            }
            $instant = now();
            if ($this->context()->tenant()->legacy_key && ! $preview['legacy_frozen_at']) {
                DB::table('tenants')->where('id', $this->context()->id())->update(['legacy_stock_frozen_at' => $instant, 'updated_at' => $instant]);
                $batch = $this->insert('migration_batches', ['batch_code' => 'legacy-opening-v1', 'status' => 'reconciled', 'source_snapshot_at' => $instant, 'reconciliation_json' => json_encode(['cutover_at' => $instant->toIso8601String(), 'source_rows' => $preview['opening_rows'], 'preview_hash' => $preview['preview_hash'], 'history_replayed' => false], JSON_THROW_ON_ERROR)]);
                foreach ($preview['opening_rows'] as $line) {
                    app(InventoryPostingService::class)->post($line['location_id'], $line['variant_id'], $line['quantity'], 'opening', 'migration_batches', $batch, 'legacy-opening:stock:'.$line['stock_id'], $line['unit_cost'], true);
                }
                $this->audit('legacy.stock.frozen', 'migration_batch', $batch, ['source_rows' => count($preview['opening_rows']), 'preview_hash' => $preview['preview_hash']]);
            }
            $this->update('branches', $branchId, ['pos_engine' => 'react', 'allow_credit' => $data['allow_credit'], 'tax_jurisdiction' => $data['tax_jurisdiction'] ?? null, 'pricing_approved_at' => $instant, 'currency_scale' => $this->scale()]);
            app(SaleService::class)->setup();
            $this->audit('pos.branch.activated', 'branch', $branchId, ['pricing' => 'tax-exclusive; fixed discount before tax; half-up per line; currency minor units', 'credit' => $data['allow_credit']]);

            return ['branch_id' => $branchId, 'engine' => 'react', 'opening_rows' => count($preview['opening_rows']), 'cutover_at' => $instant->toIso8601String()];
        });
    }
}

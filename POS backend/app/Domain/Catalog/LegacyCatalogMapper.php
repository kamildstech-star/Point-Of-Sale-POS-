<?php

namespace App\Domain\Catalog;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantService;
use Illuminate\Support\Facades\DB;

class LegacyCatalogMapper
{
    public function run(int $locationId): array
    {
        $context = app(TenantContext::class);
        $tenantId = $context->id();
        abort_unless($context->tenant()->legacy_key === 'best-uniform', 403);
        abort_unless(DB::table('locations')->where('tenant_id', $tenantId)->where('id', $locationId)->where('status', 'active')->exists(), 422);

        return DB::transaction(function () use ($tenantId, $locationId) {
            $tenant = DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->first();
            $batch = DB::table('migration_batches')->where('tenant_id', $tenantId)->where('batch_code', 'legacy-catalog-v1')->first();
            $batchId = $batch?->id ?? DB::table('migration_batches')->insertGetId(['tenant_id' => $tenantId, 'batch_code' => 'legacy-catalog-v1', 'status' => 'running', 'source_snapshot_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $counts = ['total' => 0, 'mapped' => 0, 'unresolved' => 0, 'created' => 0];
            foreach (DB::table('stocks')->where('tenant_id', $tenantId)->orderBy('id')->get() as $stock) {
                $counts['total']++;
                $source = ['stock' => (array) $stock];
                foreach (['product' => ['products', $stock->product_id], 'school' => ['schools', $stock->school_id], 'size' => ['sizes', $stock->size_id]] as $key => [$table, $id]) {
                    $source[$key] = DB::table($table)->where('tenant_id', $tenantId)->where('id', $id)->first();
                }
                $checksum = hash('sha256', json_encode($source, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $existing = DB::table('legacy_record_mappings')->where('tenant_id', $tenantId)->where('source_table', 'stocks')->where('source_pk', $stock->id)->where('target_table', 'catalog_variants')->first();
                $issue = null;
                if ($existing && $existing->source_checksum !== $checksum) {
                    $issue = 'source_changed_after_mapping';
                }
                if ($existing && (int) $existing->location_id !== $locationId) {
                    $issue = 'location_change_requires_review';
                }
                if (! $existing) {
                    if ($tenant->sku_policy !== 'distinct_by_school') {
                        $issue = 'sku_policy_approval_required';
                    } elseif (! $source['product'] || ! $source['school'] || ! $source['size']) {
                        $issue = 'missing_legacy_reference';
                    } elseif ($source['size']->product_id != $stock->product_id) {
                        $issue = 'size_product_mismatch';
                    } elseif (DB::table('stocks')->where('tenant_id', $tenantId)->where('school_id', $stock->school_id)->where('product_id', $stock->product_id)->where('size_id', $stock->size_id)->count() > 1) {
                        $issue = 'duplicate_stock_combination';
                    } elseif ($stock->quantity < 0 || ! preg_match('/^\d+(?:\.\d{1,4})?$/D', (string) $stock->base_price) || ! preg_match('/^\d+(?:\.\d{1,4})?$/D', (string) $stock->sale_price)) {
                        $issue = 'invalid_quantity_or_price';
                    } elseif (mb_strlen($source['size']->size) > 160) {
                        $issue = 'size_label_too_long';
                    } elseif (! empty($stock->product_code) && (mb_strlen($stock->product_code) > 100 || DB::table('stocks')->where('tenant_id', $tenantId)->where('product_code', $stock->product_code)->count() > 1 || DB::table('catalog_barcodes')->where('tenant_id', $tenantId)->where('barcode', $stock->product_code)->exists())) {
                        $issue = 'barcode_collision_or_invalid';
                    } elseif (DB::table('catalog_variants')->where('tenant_id', $tenantId)->where('sku', $this->sku($stock))->exists()) {
                        $issue = 'sku_collision';
                    }
                }
                if ($issue) {
                    DB::table('migration_exceptions')->updateOrInsert(['tenant_id' => $tenantId, 'source_table' => 'stocks', 'source_pk' => $stock->id, 'issue_code' => $issue],
                        ['migration_batch_id' => $batchId, 'resolution_status' => 'unresolved', 'details_json' => json_encode(['snapshot' => $source, 'note' => 'Correct source data or obtain a reviewed mapping; retry without deleting history.'], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
                    $counts['unresolved']++;

                    continue;
                }
                if (! $existing) {
                    app(\App\Domain\Billing\TenantAccess::class)->capacity('products');
                    $unit = $this->reference('catalog_units', $tenantId, ['code' => 'EA'], ['name' => 'Each', 'quantity_precision' => 0, 'active' => true]);
                    $product = DB::table('catalog_products')->insertGetId(['tenant_id' => $tenantId, 'name' => $source['product']->name, 'product_type' => 'variable', 'track_inventory' => true, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
                    $variant = DB::table('catalog_variants')->insertGetId(['tenant_id' => $tenantId, 'product_id' => $product, 'base_unit_id' => $unit, 'sku' => $this->sku($stock),
                        'name' => mb_substr($source['school']->name.' / '.$source['size']->size, 0, 255), 'default_cost' => (string) $stock->base_price, 'default_price' => (string) $stock->sale_price, 'tracking_mode' => 'none', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
                    foreach (['size' => $source['size']->size, 'school' => 'legacy-school-'.$stock->school_id] as $code => $label) {
                        $attribute = $this->reference('catalog_attributes', $tenantId, ['code' => 'legacy-'.$code], ['name' => ucfirst($code), 'active' => true]);
                        $value = $this->reference('catalog_attribute_values', $tenantId, ['attribute_id' => $attribute, 'value' => $label], ['sort_order' => 0]);
                        DB::table('catalog_variant_values')->insert(['tenant_id' => $tenantId, 'variant_id' => $variant, 'attribute_id' => $attribute, 'value_id' => $value, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    if (! empty($stock->product_code)) {
                        DB::table('catalog_barcodes')->insert(['tenant_id' => $tenantId, 'variant_id' => $variant, 'barcode' => $stock->product_code, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    // Preserve raw enums, all original image paths, school margin,
                    // stock prices and labels without assigning new tax meanings.
                    DB::table('legacy_record_mappings')->insert(['tenant_id' => $tenantId, 'migration_batch_id' => $batchId, 'source_table' => 'stocks', 'source_pk' => $stock->id, 'target_table' => 'catalog_variants', 'target_pk' => $variant, 'location_id' => $locationId,
                        'source_checksum' => $checksum, 'mapping_json' => json_encode(['policy' => 'distinct_by_school', 'source' => $source, 'opening_balance_posted' => false, 'image_policy' => 'Original paths preserved; private image copy requires review.'], JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
                    $counts['created']++;
                }
                DB::table('migration_exceptions')->where('tenant_id', $tenantId)->where('source_table', 'stocks')->where('source_pk', $stock->id)->update(['resolution_status' => 'resolved', 'updated_at' => now()]);
                $counts['mapped']++;
            }
            DB::table('migration_batches')->where('id', $batchId)->where('tenant_id', $tenantId)->update(['status' => $counts['unresolved'] ? 'needs_review' : 'reconciled', 'reconciliation_json' => json_encode($counts, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
            app(TenantService::class)->audit('legacy.catalog.reconciled', 'migration_batch', $batchId, $counts);

            return $counts;
        });
    }

    private function sku(object $stock): string
    {
        return 'LEG-S'.$stock->school_id.'-P'.$stock->product_id.'-Z'.$stock->size_id.'-K'.$stock->id;
    }

    private function reference(string $table, int $tenantId, array $key, array $data): int
    {
        return DB::table($table)->where('tenant_id', $tenantId)->where($key)->value('id')
            ?? DB::table($table)->insertGetId($key + $data + ['tenant_id' => $tenantId, 'created_at' => now(), 'updated_at' => now()]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $base = static function (Blueprint $t, string $key) {
            $t->id(); $t->foreignId('tenant_id')->constrained('tenants')->restrictOnDelete();
            $t->unique(['tenant_id', 'id'], $key.'_tenant'); $t->timestamps();
        };
        $fk = static function (Blueprint $t, string $column, string $parent, string $key) {
            $t->unsignedBigInteger($column)->nullable();
            $t->foreign(['tenant_id', $column], $key)->references(['tenant_id', 'id'])->on($parent)->restrictOnDelete();
        };
        $this->create('catalog_categories', function (Blueprint $t) use ($base, $fk) {
            $base($t, 'cc'); $t->string('name'); $t->string('slug', 120); $t->boolean('active')->default(true);
            $t->unique(['tenant_id', 'slug'], 'cc_slug'); $fk($t, 'parent_id', 'catalog_categories', 'cc_parent');
        });
        $this->create('catalog_brands', function (Blueprint $t) use ($base) {
            $base($t, 'cb'); $t->string('name'); $t->string('code', 80); $t->boolean('active')->default(true); $t->unique(['tenant_id', 'code'], 'cb_code');
        });
        $this->create('catalog_units', function (Blueprint $t) use ($base) {
            $base($t, 'cu'); $t->string('name'); $t->string('code', 30); $t->unsignedTinyInteger('quantity_precision')->default(0); $t->boolean('active')->default(true); $t->unique(['tenant_id', 'code'], 'cu_code');
        });
        $this->create('catalog_tax_classes', function (Blueprint $t) use ($base) {
            $base($t, 'ctc'); $t->string('name'); $t->string('code', 80); $t->boolean('active')->default(true); $t->unique(['tenant_id', 'code'], 'ctc_code');
        });
        $this->create('catalog_products', function (Blueprint $t) use ($base, $fk) {
            $base($t, 'cp'); $t->string('name'); $t->text('description')->nullable();
            $t->string('product_type', 20); $t->boolean('track_inventory')->default(true); $t->boolean('active')->default(true);
            $fk($t, 'category_id', 'catalog_categories', 'cp_category'); $fk($t, 'brand_id', 'catalog_brands', 'cp_brand'); $fk($t, 'tax_class_id', 'catalog_tax_classes', 'cp_tax');
            $t->index(['tenant_id', 'active', 'id'], 'cp_listing');
        });
        $this->create('catalog_variants', function (Blueprint $t) use ($base) {
            $base($t, 'cv'); $t->unsignedBigInteger('product_id'); $t->unsignedBigInteger('base_unit_id');
            $t->string('sku', 100); $t->string('name')->nullable(); $t->decimal('default_cost', 19, 4)->default(0); $t->decimal('default_price', 19, 4)->default(0);
            $t->string('tracking_mode', 20)->default('none'); $t->boolean('active')->default(true);
            $t->unique(['tenant_id', 'sku'], 'cv_sku'); $t->unique(['tenant_id', 'product_id', 'id'], 'cv_product_id');
            $t->foreign(['tenant_id', 'product_id'], 'cv_product')->references(['tenant_id', 'id'])->on('catalog_products')->restrictOnDelete();
            $t->foreign(['tenant_id', 'base_unit_id'], 'cv_unit')->references(['tenant_id', 'id'])->on('catalog_units')->restrictOnDelete();
        });
        $this->create('catalog_attributes', function (Blueprint $t) use ($base) {
            $base($t, 'ca'); $t->string('name'); $t->string('code', 80); $t->boolean('active')->default(true); $t->unique(['tenant_id', 'code'], 'ca_code');
        });
        $this->create('catalog_attribute_values', function (Blueprint $t) use ($base) {
            $base($t, 'cav'); $t->unsignedBigInteger('attribute_id'); $t->string('value', 160); $t->integer('sort_order')->default(0);
            $t->unique(['tenant_id', 'attribute_id', 'value'], 'cav_value'); $t->unique(['tenant_id', 'attribute_id', 'id'], 'cav_attribute');
            $t->foreign(['tenant_id', 'attribute_id'], 'cav_parent')->references(['tenant_id', 'id'])->on('catalog_attributes')->restrictOnDelete();
        });
        $this->create('catalog_variant_values', function (Blueprint $t) use ($base) {
            $base($t, 'cvv'); $t->unsignedBigInteger('variant_id'); $t->unsignedBigInteger('attribute_id'); $t->unsignedBigInteger('value_id');
            $t->unique(['tenant_id', 'variant_id', 'attribute_id'], 'cvv_one_value');
            $t->foreign(['tenant_id', 'variant_id'], 'cvv_variant')->references(['tenant_id', 'id'])->on('catalog_variants')->restrictOnDelete();
            $t->foreign(['tenant_id', 'attribute_id', 'value_id'], 'cvv_value')->references(['tenant_id', 'attribute_id', 'id'])->on('catalog_attribute_values')->restrictOnDelete();
        });
        $this->create('catalog_barcodes', function (Blueprint $t) use ($base) {
            $base($t, 'cbc'); $t->unsignedBigInteger('variant_id'); $t->string('barcode', 100); $t->string('symbology', 30)->nullable();
            $t->unique(['tenant_id', 'barcode'], 'cbc_code');
            $t->foreign(['tenant_id', 'variant_id'], 'cbc_variant')->references(['tenant_id', 'id'])->on('catalog_variants')->restrictOnDelete();
        });
        $this->create('catalog_images', function (Blueprint $t) use ($base) {
            $base($t, 'ci'); $t->unsignedBigInteger('product_id'); $t->string('storage_path'); $t->string('original_name');
            $t->foreign(['tenant_id', 'product_id'], 'ci_product')->references(['tenant_id', 'id'])->on('catalog_products')->restrictOnDelete();
        });
        $this->create('catalog_price_lists', function (Blueprint $t) use ($base) {
            $base($t, 'cpl'); $t->string('name'); $t->char('currency_code', 3); $t->boolean('active')->default(true);
        });
        $this->create('catalog_price_items', function (Blueprint $t) use ($base) {
            $base($t, 'cpi'); $t->unsignedBigInteger('price_list_id'); $t->unsignedBigInteger('variant_id');
            $t->decimal('unit_price', 19, 4); $t->decimal('min_qty', 18, 4)->default(1);
            $t->unique(['tenant_id', 'price_list_id', 'variant_id', 'min_qty'], 'cpi_tier');
            $t->foreign(['tenant_id', 'price_list_id'], 'cpi_list')->references(['tenant_id', 'id'])->on('catalog_price_lists')->restrictOnDelete();
            $t->foreign(['tenant_id', 'variant_id'], 'cpi_variant')->references(['tenant_id', 'id'])->on('catalog_variants')->restrictOnDelete();
        });
        $this->create('catalog_tax_rates', function (Blueprint $t) use ($base) {
            $base($t, 'ctr'); $t->unsignedBigInteger('tax_class_id'); $t->string('jurisdiction_code', 80);
            $t->decimal('rate_percent', 7, 4); $t->date('valid_from'); $t->date('valid_to')->nullable();
            $t->foreign(['tenant_id', 'tax_class_id'], 'ctr_class')->references(['tenant_id', 'id'])->on('catalog_tax_classes')->restrictOnDelete();
        });
        $this->create('migration_batches', function (Blueprint $t) use ($base) {
            $base($t, 'mb'); $t->string('batch_code', 80); $t->string('status', 30); $t->dateTime('source_snapshot_at');
            $t->json('reconciliation_json')->nullable(); $t->unique(['tenant_id', 'batch_code'], 'mb_code');
        });
        $this->create('legacy_record_mappings', function (Blueprint $t) use ($base) {
            $base($t, 'lrm'); $t->unsignedBigInteger('migration_batch_id'); $t->string('source_table', 60); $t->unsignedBigInteger('source_pk');
            $t->string('target_table', 60); $t->unsignedBigInteger('target_pk'); $t->unsignedBigInteger('location_id');
            $t->char('source_checksum', 64); $t->json('mapping_json');
            $t->unique(['tenant_id', 'source_table', 'source_pk', 'target_table'], 'lrm_source');
            $t->foreign(['tenant_id', 'target_pk'], 'lrm_variant')->references(['tenant_id', 'id'])->on('catalog_variants')->restrictOnDelete();
            $t->foreign(['tenant_id', 'location_id'], 'lrm_location')->references(['tenant_id', 'id'])->on('locations')->restrictOnDelete();
            $t->foreign(['tenant_id', 'migration_batch_id'], 'lrm_batch')->references(['tenant_id', 'id'])->on('migration_batches')->restrictOnDelete();
        });
        $this->create('migration_exceptions', function (Blueprint $t) use ($base) {
            $base($t, 'me'); $t->unsignedBigInteger('migration_batch_id'); $t->string('source_table', 60); $t->unsignedBigInteger('source_pk');
            $t->string('issue_code', 80); $t->string('resolution_status', 20)->default('unresolved'); $t->json('details_json');
            $t->unique(['tenant_id', 'source_table', 'source_pk', 'issue_code'], 'me_source');
            $t->foreign(['tenant_id', 'migration_batch_id'], 'me_batch')->references(['tenant_id', 'id'])->on('migration_batches')->restrictOnDelete();
        });
    }

    private function create(string $table, Closure $definition): void
    {
        // The deployment helper verifies any empty tables left by an interrupted
        // local DDL operation against the successful rehearsal before resuming.
        if (! Schema::hasTable($table)) Schema::create($table, $definition);
    }
    public function down(): void
    {
        throw new RuntimeException('Catalog and source mappings are retained for rollback. No automatic history deletion.');
    }
};

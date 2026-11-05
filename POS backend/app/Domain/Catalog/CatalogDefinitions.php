<?php

namespace App\Domain\Catalog;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Validation\Rule;

class CatalogDefinitions
{
    public const TABLES = ['categories' => 'catalog_categories', 'brands' => 'catalog_brands', 'units' => 'catalog_units', 'attributes' => 'catalog_attributes', 'attribute-values' => 'catalog_attribute_values', 'tax-classes' => 'catalog_tax_classes', 'tax-rates' => 'catalog_tax_rates', 'price-lists' => 'catalog_price_lists', 'price-list-items' => 'catalog_price_items', 'barcodes' => 'catalog_barcodes', 'variant-values' => 'catalog_variant_values', 'branches' => 'branches', 'locations' => 'locations'];
    public const MONEY = 'regex:/^\d{1,15}(?:\.\d{1,4})?$/D';
    public const QUANTITY = 'regex:/^\d{1,14}(?:\.\d{1,4})?$/D';

    public static function rules(string $resource, ?int $id = null): array
    {
        $tenant = app(TenantContext::class)->id();
        $exists = fn ($table) => Rule::exists($table, 'id')->where('tenant_id', $tenant);
        $unique = fn ($column) => Rule::unique(self::TABLES[$resource], $column)->where('tenant_id', $tenant)->ignore($id);
        $name = ['required', 'string', 'max:255']; $code = ['required', 'string', 'max:40', $unique('code')];
        $active = ['sometimes', 'boolean'];
        return match ($resource) {
            'categories' => ['name' => $name, 'slug' => ['required', 'regex:/^[a-z0-9-]+$/D', 'max:120', $unique('slug')], 'parent_id' => ['nullable', 'integer', $exists('catalog_categories')], 'active' => $active],
            'brands', 'tax-classes', 'attributes' => ['name' => $name, 'code' => $code, 'active' => $active],
            'units' => ['name' => $name, 'code' => $code, 'quantity_precision' => ['required', 'integer', 'between:0,4'], 'active' => $active],
            'attribute-values' => ['attribute_id' => ['required', 'integer', $exists('catalog_attributes')], 'value' => ['required', 'string', 'max:160', Rule::unique('catalog_attribute_values', 'value')->where('tenant_id', $tenant)->where('attribute_id', request('attribute_id'))->ignore($id)], 'sort_order' => ['sometimes', 'integer', 'between:-2147483648,2147483647']],
            'price-lists' => ['name' => $name, 'currency_code' => ['required', Rule::in([app(TenantContext::class)->tenant()->currency_code])], 'active' => $active],
            'price-list-items' => ['price_list_id' => ['required', 'integer', $exists('catalog_price_lists')], 'variant_id' => ['required', 'integer', $exists('catalog_variants')], 'unit_price' => ['required', 'string', self::MONEY], 'min_qty' => ['required', 'string', self::QUANTITY, 'numeric', 'gt:0']],
            'tax-rates' => ['tax_class_id' => ['required', 'integer', $exists('catalog_tax_classes')], 'jurisdiction_code' => ['required', 'string', 'max:80'], 'rate_percent' => ['required', 'numeric', 'between:0,100', 'decimal:0,4'], 'valid_from' => ['required', 'date_format:Y-m-d'], 'valid_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:valid_from']],
            'barcodes' => ['variant_id' => ['required', 'integer', $exists('catalog_variants')], 'barcode' => ['required', 'string', 'max:100', $unique('barcode')], 'symbology' => ['nullable', 'string', 'max:30']],
            'variant-values' => ['variant_id' => ['required', 'integer', $exists('catalog_variants')], 'attribute_id' => ['required', 'integer', $exists('catalog_attributes')], 'value_id' => ['required', 'integer', $exists('catalog_attribute_values')]],
            'branches' => ['name' => $name, 'code' => $code, 'address' => ['nullable', 'string', 'max:2000'], 'phone' => ['nullable', 'string', 'max:40'], 'status' => ['required', Rule::in(['active', 'inactive'])]],
            'locations' => ['name' => $name, 'code' => $code, 'branch_id' => ['nullable', 'integer', $exists('branches')], 'type' => ['required', Rule::in(['store', 'warehouse', 'quarantine', 'in_transit'])], 'status' => ['required', Rule::in(['active', 'inactive'])]],
        };
    }
}

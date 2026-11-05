<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\CatalogDefinitions;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductApiController extends Controller
{
    use TenantQueries;

    public function index(Request $request)
    {
        $this->permit('products.view');
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $query = $this->query('catalog_products')->orderByDesc('id');
        if (! empty($data['search'])) {
            $query->where('name', 'like', '%'.addcslashes($data['search'], '%_\\').'%');
        }

        return response()->json($query->paginate(25));
    }

    public function variants(Request $request)
    {
        $this->permit('products.view');
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $query = $this->query('catalog_variants')->orderBy('id');
        if (! empty($data['search'])) {
            $query->where('sku', 'like', '%'.addcslashes($data['search'], '%_\\').'%');
        }

        $rows = $query->paginate(50);
        if (! $this->context()->canViewCosts()) foreach ($rows as $row) unset($row->default_cost);
        return response()->json($rows);
    }

    public function variant(string $variant)
    {
        $this->permit('products.view');

        $row = $this->row('catalog_variants', $variant);
        if (! $this->context()->canViewCosts()) unset($row->default_cost);
        return response()->json(['data' => $row]);
    }

    public function show(string $product)
    {
        $this->permit('products.view');
        $record = $this->row('catalog_products', $product);
        $record->variants = $this->query('catalog_variants')->where('product_id', $product)->get();
        if (! $this->context()->canViewCosts()) foreach ($record->variants as $variant) unset($variant->default_cost);
        $record->images = $this->query('catalog_images')->where('product_id', $product)->get(['id', 'original_name']);

        return response()->json(['data' => $record]);
    }

    private function productRules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'],
            'product_type' => ['required', Rule::in(['simple', 'variable', 'service', 'digital'])], 'track_inventory' => ['required', 'boolean'], 'active' => ['sometimes', 'boolean'],
            'category_id' => ['nullable', 'integer', $this->exists('catalog_categories')], 'brand_id' => ['nullable', 'integer', $this->exists('catalog_brands')], 'tax_class_id' => ['nullable', 'integer', $this->exists('catalog_tax_classes')]];
    }

    private function variantData(array $input, ?string $id = null): array
    {
        if (isset($input['sku']) && is_string($input['sku'])) {
            $input['sku'] = strtoupper(trim($input['sku']));
        }

        return Validator::make($input, ['sku' => ['required', 'string', 'max:100', $this->unique('catalog_variants', 'sku', $id)], 'name' => ['nullable', 'string', 'max:255'],
            'base_unit_id' => ['required', 'integer', $this->exists('catalog_units')], 'default_cost' => ['required', 'string', CatalogDefinitions::MONEY], 'default_price' => ['required', 'string', CatalogDefinitions::MONEY],
            'tracking_mode' => ['required', Rule::in(['none', 'batch', 'serial'])], 'active' => ['sometimes', 'boolean']])->validate();
    }

    public function store(Request $request)
    {
        $this->permit('products.manage');
        $data = $request->validate($this->productRules() + ['variant' => ['required', 'array']]);
        try {
            $variant = $this->variantData($data['variant']);
        } catch (\Illuminate\Validation\ValidationException $error) {
            throw \Illuminate\Validation\ValidationException::withMessages(
                collect($error->errors())->mapWithKeys(fn ($messages, $field) => ['variant.'.$field => $messages])->all()
            );
        }
        unset($data['variant']);
        if (in_array($data['product_type'], ['service', 'digital'])) {
            $data['track_inventory'] = false;
        }
        $id = DB::transaction(function () use ($data, $variant) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            app(\App\Domain\Billing\TenantAccess::class)->capacity('products');
            $id = DB::table('catalog_products')->insertGetId($data + ['tenant_id' => $this->context()->id(), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('catalog_variants')->insert($variant + ['tenant_id' => $this->context()->id(), 'product_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            $this->audit('catalog.product.created', 'product', $id);

            return $id;
        });

        return response()->json(['data' => ['id' => $id]], 201);
    }

    public function update(Request $request, string $product)
    {
        $this->permit('products.manage');
        $existing = $this->row('catalog_products', $product);
        $data = Validator::make(array_merge((array) $existing, $request->all()), $this->productRules())->validate();
        if (in_array($data['product_type'], ['service', 'digital'])) {
            $data['track_inventory'] = false;
        }
        DB::transaction(function () use ($product, $data) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $before = $this->row('catalog_products', $product);
            if ((bool) $before->track_inventory !== (bool) $data['track_inventory'] && \Illuminate\Support\Facades\Schema::hasTable('stock_movements')) {
                $variants = $this->query('catalog_variants')->where('product_id', $product)->pluck('id');
                if ($this->query('stock_movements')->whereIn('variant_id', $variants)->exists() || $this->query('sale_items')->whereIn('variant_id', $variants)->exists()) {
                    throw new \App\Domain\Operations\Conflict('Inventory tracking cannot change after stock or sale history exists. Create a new product.');
                }
            }
            $this->query('catalog_products')->where('id', $product)->lockForUpdate()->first();
            abort_if($data['product_type'] !== 'variable' && $this->query('catalog_variants')->where('product_id', $product)->count() > 1, 409, 'A product with multiple variants must remain variable.');
            $this->query('catalog_products')->where('id', $product)->update($data + ['updated_at' => now()]);
            $this->audit('catalog.product.updated', 'product', $product);
        });

        return response()->json(['data' => $this->row('catalog_products', $product)]);
    }

    public function destroy(string $product)
    {
        $this->permit('products.manage');
        $this->row('catalog_products', $product);
        DB::transaction(function () use ($product) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->query('catalog_products')->where('id', $product)->update(['active' => false, 'updated_at' => now()]);
            $this->query('catalog_variants')->where('product_id', $product)->update(['active' => false, 'updated_at' => now()]);
            $this->audit('catalog.product.archived', 'product', $product);
        });

        return response()->noContent();
    }

    public function saveVariant(Request $request, string $product, ?string $variant = null)
    {
        $this->permit('products.manage');
        $parent = $this->row('catalog_products', $product);
        $existing = $variant ? (array) $this->row('catalog_variants', $variant) : [];
        foreach (['default_cost', 'default_price'] as $money) {
            if (isset($existing[$money])) {
                $existing[$money] = (string) $existing[$money];
            }
        }
        abort_if($variant && $existing['product_id'] != $product, 404);
        $data = $this->variantData(array_merge($existing, $request->all()), $variant);
        $id = DB::transaction(function () use ($parent, $variant, $product, $data) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            if ($variant && \Illuminate\Support\Facades\Schema::hasTable('stock_movements')) {
                $before = $this->row('catalog_variants', $variant);
                if (($before->base_unit_id != $data['base_unit_id'] || $before->tracking_mode !== $data['tracking_mode']) && ($this->query('stock_movements')->where('variant_id', $variant)->exists() || $this->query('sale_items')->where('variant_id', $variant)->exists() || $this->query('stock_reservations')->where('variant_id', $variant)->exists())) {
                    throw new \App\Domain\Operations\Conflict('Unit and tracking mode cannot change after stock, reservation or sale history exists.');
                }
            }
            $this->query('catalog_products')->where('id', $product)->lockForUpdate()->first();
            abort_if(! $variant && $parent->product_type !== 'variable', 409, 'Only variable products can add more variants.');
            $id = $variant ?: DB::table('catalog_variants')->insertGetId($data + ['tenant_id' => $this->context()->id(), 'product_id' => $product, 'created_at' => now(), 'updated_at' => now()]);
            if ($variant) {
                $this->query('catalog_variants')->where('id', $variant)->update($data + ['updated_at' => now()]);
            }
            $this->audit('catalog.variant.saved', 'variant', $id);

            return $id;
        });

        return response()->json(['data' => $this->row('catalog_variants', $id)], $variant ? 200 : 201);
    }

    public function destroyVariant(string $product, string $variant)
    {
        $this->permit('products.manage');
        DB::transaction(function () use ($product, $variant) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->row('catalog_products', $product);
            $record = $this->row('catalog_variants', $variant);
            abort_unless((string) $record->product_id === $product, 404);
            if ($record->active) {
                $this->query('catalog_variants')->where('id', $variant)->update(['active' => false, 'updated_at' => now()]);
                $this->audit('catalog.variant.archived', 'variant', $variant);
            }
        });

        return response()->noContent();
    }

    public function export()
    {
        $this->permit('products.view');
        $tenantId = $this->context()->id();
        $costs = $this->context()->canViewCosts();

        return response()->streamDownload(function () use ($tenantId, $costs) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $costs ? ['product_id', 'product', 'variant_id', 'sku', 'cost', 'price', 'active'] : ['product_id', 'product', 'variant_id', 'sku', 'price', 'active']);
            $rows = DB::table('catalog_products as p')->join('catalog_variants as v', fn ($join) => $join->on('v.product_id', '=', 'p.id')->on('v.tenant_id', '=', 'p.tenant_id'))->where('p.tenant_id', $tenantId)->orderBy('v.id')->select(['p.id as product_id', 'p.name', 'v.id', 'v.sku', 'v.default_cost', 'v.default_price', 'v.active'])->cursor();
            foreach ($rows as $row) {
                if (! $costs) unset($row->default_cost);
                fputcsv($output, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, (array) $row));
            }
            fclose($output);
        }, 'catalog.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function upload(Request $request, string $product)
    {
        $this->permit('products.manage');
        $this->row('catalog_products', $product);
        $request->validate(['image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=1,min_height=1']]);
        $file = $request->file('image');
        $path = $file->store($this->context()->storagePath('catalog'), 'tenant_private');
        abort_unless($path, 500);
        try {
            $id = DB::transaction(function () use ($product, $path, $file) {
                $id = DB::table('catalog_images')->insertGetId(['tenant_id' => $this->context()->id(), 'product_id' => $product, 'storage_path' => $path, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'created_at' => now(), 'updated_at' => now()]);
                $this->audit('catalog.image.uploaded', 'image', $id);

                return $id;
            });
        } catch (\Throwable $error) {
            Storage::disk('tenant_private')->delete($path);
            throw $error;
        }

        return response()->json(['data' => ['id' => $id]], 201);
    }

    public function image(string $image)
    {
        $this->permit('products.view');
        $record = $this->row('catalog_images', $image);
        abort_unless(str_starts_with($record->storage_path, $this->context()->storagePath('catalog/')) && ! str_contains($record->storage_path, '..') && Storage::disk('tenant_private')->exists($record->storage_path), 404);

        return Storage::disk('tenant_private')->download($record->storage_path, $record->original_name, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}

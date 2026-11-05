<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\CatalogDefinitions;
use App\Domain\Operations\Conflict;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CatalogReferenceController extends Controller
{
    use TenantQueries;

    private function table(string $resource): string
    {
        return CatalogDefinitions::TABLES[$resource] ?? abort(404);
    }

    private function permission(string $resource, bool $write): void
    {
        $this->permit(in_array($resource, ['branches', 'locations']) ? ($write ? 'settings.manage' : 'products.view') : ($write ? 'products.manage' : 'products.view'));
    }

    public function index(Request $request, string $resource)
    {
        $this->permission($resource, false);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100']]);
        $table = $this->table($resource);
        $query = $this->query($table)->orderBy('id');
        if (! empty($data['search'])) {
            $columns = array_intersect(['name', 'code', 'value', 'barcode'], \Illuminate\Support\Facades\Schema::getColumnListing($table));
            if ($columns) {
                $query->where(function ($q) use ($columns, $data) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'like', '%'.addcslashes($data['search'], '%_\\').'%');
                    }
                });
            }
        }

        return response()->json($query->paginate(50));
    }

    public function show(string $resource, string $id)
    {
        $this->permission($resource, false);

        return response()->json(['data' => $this->row($this->table($resource), $id)]);
    }

    public function save(Request $request, string $resource, ?string $id = null)
    {
        $this->permission($resource, true);
        $table = $this->table($resource);
        $existing = $id ? (array) $this->row($table, $id) : [];
        $data = Validator::make(array_merge($existing, $request->all()), CatalogDefinitions::rules($resource, $id ? (int) $id : null), [
            'slug.regex' => 'Use lowercase letters, numbers and hyphens for the category slug, for example school-uniforms.',
            'slug.unique' => 'A category with this slug already exists in this business. Choose a different slug.',
        ])->validate();
        $saved = DB::transaction(function () use ($resource, $id, $table, $data) {
            // Serialize metadata graph/rate edits within a tenant. The composite
            // FKs independently protect against cross-tenant references.
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            if ($resource === 'branches' && ! $id) {
                app(\App\Domain\Billing\TenantAccess::class)->capacity('branches');
            }
            if ($id && in_array($resource, ['branches', 'locations']) && $data['status'] === 'inactive') {
                $this->assertCanDeactivate($resource, $id);
            }
            if ($id && \Illuminate\Support\Facades\Schema::hasTable('inventory_balances')) {
                $before = $this->row($table, $id);
                if ($resource === 'units' && $before->quantity_precision != $data['quantity_precision'] && $this->query('catalog_variants')->where('base_unit_id', $id)->exists()) {
                    throw new \App\Domain\Operations\Conflict('A unit used by variants cannot change quantity precision. Create another unit.');
                }
                if ($resource === 'locations' && ($before->branch_id != ($data['branch_id'] ?? null) || $before->type !== $data['type']) && ($this->query('inventory_balances')->where('location_id', $id)->exists() || $this->query('cash_registers')->where('location_id', $id)->exists())) {
                    throw new \App\Domain\Operations\Conflict('A used stock location cannot change its branch or type.');
                }
            }
            if ($resource === 'categories' && ! empty($data['parent_id'])) {
                $parent = $data['parent_id'];
                $visited = [];
                while ($parent) {
                    if ((string) $parent === (string) $id || isset($visited[$parent])) {
                        throw ValidationException::withMessages(['parent_id' => 'Category hierarchy cannot contain a cycle.']);
                    }
                    $visited[$parent] = true;
                    $parent = $this->row($table, $parent)->parent_id;
                }
            }
            if ($resource === 'variant-values') {
                $value = $this->row('catalog_attribute_values', $data['value_id']);
                if ($value->attribute_id != $data['attribute_id']) {
                    throw ValidationException::withMessages(['value_id' => 'Value does not belong to this attribute.']);
                }
            }
            if ($resource === 'tax-rates') {
                $overlap = $this->query($table)->where('tax_class_id', $data['tax_class_id'])->where('jurisdiction_code', $data['jurisdiction_code'])
                    ->when($id, fn ($q) => $q->where('id', '<>', $id))->where('valid_from', '<=', $data['valid_to'] ?? '9999-12-31')
                    ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $data['valid_from']))->exists();
                if ($overlap) {
                    throw ValidationException::withMessages(['valid_from' => 'This tax rate overlaps an existing effective period.']);
                }
            }
            $attributes = $data + ['updated_at' => now()];
            $saved = $id ?: DB::table($table)->insertGetId($attributes + ['tenant_id' => $this->context()->id(), 'created_at' => now()]);
            if ($id) {
                $this->query($table)->where('id', $id)->update($attributes);
            }
            $this->audit('catalog.reference.saved', $resource, $saved);

            return $saved;
        });

        return response()->json(['data' => $this->row($table, $saved)], $id ? 200 : 201);
    }

    private function assertCanDeactivate(string $resource, string $id): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('inventory_balances')) {
            return;
        }
        $locations = $resource === 'locations' ? [(int) $id] : $this->query('locations')->where('branch_id', $id)->pluck('id')->all();
        $registers = $this->query('cash_registers')->whereIn('location_id', $locations)->pluck('id');
        if ($this->query('cash_sessions')->whereIn('register_id', $registers)->where('status', 'open')->exists()
            || $this->query('inventory_balances')->whereIn('location_id', $locations)->where(fn ($q) => $q->where('on_hand', '<>', 0)->orWhere('reserved', '<>', 0))->exists()) {
            throw new Conflict('Close open register shifts and clear stock and reservations before deactivating this branch or location.');
        }
    }

    public function destroy(string $resource, string $id)
    {
        $this->permission($resource, true);
        $table = $this->table($resource);
        $this->row($table, $id);
        abort_if($resource === 'tax-rates', 409, 'Tax rates are retained for history. Edit the effective end date instead.');
        DB::transaction(function () use ($table, $id, $resource) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->row($table, $id);
            if (in_array($resource, ['branches', 'locations'])) {
                $this->assertCanDeactivate($resource, $id);
                $this->query($table)->where('id', $id)->update(['status' => 'inactive', 'updated_at' => now()]);
                $action = 'deactivated';
            } elseif (in_array($resource, ['categories', 'brands', 'units', 'attributes', 'tax-classes', 'price-lists'])) {
                $this->query($table)->where('id', $id)->update(['active' => false, 'updated_at' => now()]);
                $action = 'archived';
            } else {
                if ($resource === 'attribute-values' && $this->query('catalog_variant_values')->where('value_id', $id)->exists()) {
                    throw new Conflict('Remove this attribute value from its variants before deleting it.');
                }
                // These leaf metadata rows have no historical transaction FKs.
                // Posted sales retain their price, tax and description snapshots.
                $this->query($table)->where('id', $id)->delete();
                $action = 'deleted';
            }
            $this->audit('catalog.reference.'.$action, $resource, $id);
        });

        return response()->noContent();
    }
}

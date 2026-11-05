<?php

namespace Tests\Feature;

use App\Domain\Tenancy\TenantService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\TestCase;

class RecordActionsTest extends TestCase
{
    use BuildAdvancedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildAdvanced();
    }

    public function test_product_and_variant_updates_and_archives_preserve_posted_history(): void
    {
        $sale = $this->sell('1');
        $product = DB::table('catalog_variants')->where('id', $this->variantId)->value('product_id');
        $movements = DB::table('stock_movements')->count();
        $this->patchJson('/api/v1/products/'.$product, ['name' => 'Renamed shirt'])->assertOk();
        $this->patchJson('/api/v1/products/'.$product.'/variants/'.$this->variantId, ['default_price' => '20.1234'])->assertOk();
        $this->deleteJson('/api/v1/products/'.$product.'/variants/'.$this->variantId)->assertNoContent();
        $this->deleteJson('/api/v1/products/'.$product.'/variants/'.$this->variantId)->assertNoContent();
        $this->assertDatabaseHas('catalog_variants', ['id' => $this->variantId, 'active' => false]);
        $this->assertSame($movements, DB::table('stock_movements')->count());
        $this->getJson('/api/v1/sales/'.$sale)->assertOk()->assertJsonPath('data.items.0.name_snapshot', 'Shirt');
        $this->patchJson('/api/v1/products/'.$product.'/variants/'.$this->variantId, ['active' => true])->assertOk();
        $this->assertDatabaseHas('catalog_variants', ['id' => $this->variantId, 'active' => true]);
        $this->deleteJson('/api/v1/products/'.$product)->assertNoContent();
        $this->assertDatabaseHas('catalog_products', ['id' => $product, 'active' => false]);
        $this->assertDatabaseHas('sale_items', ['sale_id' => $sale, 'variant_id' => $this->variantId]);
    }

    public function test_variant_delete_requires_the_correct_product_parent(): void
    {
        $unit = DB::table('catalog_variants')->where('id', $this->variantId)->value('base_unit_id');
        $other = $this->postJson('/api/v1/products', ['name' => 'Other product', 'product_type' => 'simple', 'track_inventory' => true, 'variant' => ['sku' => 'OTHER', 'base_unit_id' => $unit, 'default_cost' => '1', 'default_price' => '2', 'tracking_mode' => 'none']])->assertCreated()->json('data.id');
        $this->deleteJson('/api/v1/products/'.$other.'/variants/'.$this->variantId)->assertNotFound();
        $this->assertDatabaseHas('catalog_variants', ['id' => $this->variantId, 'active' => true]);
    }

    public function test_reference_archiving_and_leaf_deletion_preserve_relationships(): void
    {
        $category = $this->postJson('/api/v1/catalog/categories', ['name' => 'Uniforms', 'slug' => 'uniforms'])->assertCreated()->json('data.id');
        $child = $this->postJson('/api/v1/catalog/categories', ['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $category])->assertCreated()->json('data.id');
        $this->deleteJson('/api/v1/catalog/categories/'.$category)->assertNoContent();
        $this->assertDatabaseHas('catalog_categories', ['id' => $category, 'active' => false]);
        $this->assertDatabaseHas('catalog_categories', ['id' => $child, 'parent_id' => $category]);
        $barcode = $this->postJson('/api/v1/catalog/barcodes', ['variant_id' => $this->variantId, 'barcode' => '12345'])->assertCreated()->json('data.id');
        $this->patchJson('/api/v1/catalog/barcodes/'.$barcode, ['barcode' => '67890'])->assertOk();
        $this->deleteJson('/api/v1/catalog/barcodes/'.$barcode)->assertNoContent();
        $this->assertDatabaseMissing('catalog_barcodes', ['id' => $barcode]);
        $attribute = $this->postJson('/api/v1/catalog/attributes', ['name' => 'Color', 'code' => 'COLOR'])->assertCreated()->json('data.id');
        $value = $this->postJson('/api/v1/catalog/attribute-values', ['attribute_id' => $attribute, 'value' => 'Blue'])->assertCreated()->json('data.id');
        $assignment = $this->postJson('/api/v1/catalog/variant-values', ['variant_id' => $this->variantId, 'attribute_id' => $attribute, 'value_id' => $value])->assertCreated()->json('data.id');
        $this->deleteJson('/api/v1/catalog/attribute-values/'.$value)->assertConflict();
        $this->deleteJson('/api/v1/catalog/variant-values/'.$assignment)->assertNoContent();
        $this->deleteJson('/api/v1/catalog/attribute-values/'.$value)->assertNoContent();
        $this->assertDatabaseMissing('catalog_attribute_values', ['id' => $value]);
        $tax = $this->postJson('/api/v1/catalog/tax-classes', ['name' => 'General', 'code' => 'GENERAL'])->assertCreated()->json('data.id');
        $rate = $this->postJson('/api/v1/catalog/tax-rates', ['tax_class_id' => $tax, 'jurisdiction_code' => 'LOCAL', 'rate_percent' => '5', 'valid_from' => '2026-01-01'])->assertCreated()->json('data.id');
        $this->deleteJson('/api/v1/catalog/tax-rates/'.$rate)->assertConflict();
        $this->assertDatabaseHas('catalog_tax_rates', ['id' => $rate]);
    }

    public function test_customer_supplier_and_payment_deactivation_preserves_sales_and_payments(): void
    {
        $customer = $this->postJson('/api/v1/operations/customers', ['name' => 'Customer'])->assertCreated()->json('data.id');
        $sale = $this->sell('1', $customer);
        $this->patchJson('/api/v1/operations/customers/'.$customer, ['name' => 'Updated customer', 'email' => 'client@example.test'])->assertOk();
        $this->deleteJson('/api/v1/operations/customers/'.$customer)->assertNoContent();
        $this->deleteJson('/api/v1/operations/customers/'.$customer)->assertNoContent();
        $this->assertDatabaseHas('customers', ['id' => $customer, 'name' => 'Updated customer', 'active' => false]);
        $this->assertDatabaseHas('sales', ['id' => $sale, 'customer_id' => $customer]);
        $this->patchJson('/api/v1/operations/customers/'.$customer, ['active' => true])->assertOk();
        $supplier = $this->postJson('/api/v1/operations/suppliers', ['name' => 'Supplier', 'payment_terms_days' => 30])->assertCreated()->json('data.id');
        $this->deleteJson('/api/v1/operations/suppliers/'.$supplier)->assertNoContent();
        $this->assertDatabaseHas('suppliers', ['id' => $supplier, 'active' => false]);
        $this->deleteJson('/api/v1/operations/payment-methods/'.$this->cashId)->assertNoContent();
        $this->assertDatabaseHas('payment_methods', ['id' => $this->cashId, 'active' => false]);
        $this->assertDatabaseHas('sale_payments', ['sale_id' => $sale, 'method_id' => $this->cashId]);
        $this->getJson('/api/v1/sales/'.$sale)->assertOk();
    }

    public function test_register_with_open_shift_cannot_be_deleted_or_deactivated_through_edit(): void
    {
        $register = DB::table('cash_sessions')->where('id', $this->shiftId)->value('register_id');
        $this->deleteJson('/api/v1/operations/registers/'.$register)->assertConflict();
        $this->patchJson('/api/v1/operations/registers/'.$register, ['active' => false])->assertConflict();
        $this->assertDatabaseHas('cash_registers', ['id' => $register, 'active' => true]);
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/close', ['idempotency_key' => 'delete-close-shift', 'counted_cash' => '100.00'])->assertOk();
        $this->deleteJson('/api/v1/operations/registers/'.$register)->assertNoContent();
        $this->assertDatabaseHas('cash_registers', ['id' => $register, 'active' => false]);
        $this->assertDatabaseHas('cash_sessions', ['id' => $this->shiftId, 'status' => 'closed']);
    }

    public function test_branch_location_deactivation_requires_empty_stock_and_no_open_shifts(): void
    {
        $this->deleteJson('/api/v1/catalog/branches/'.$this->branchId)->assertConflict();
        $this->deleteJson('/api/v1/catalog/locations/'.$this->locationId)->assertConflict();
        $this->patchJson('/api/v1/catalog/branches/'.$this->branchId, ['status' => 'inactive'])->assertConflict();
        $this->patchJson('/api/v1/catalog/locations/'.$this->locationId, ['status' => 'inactive'])->assertConflict();
        $location = $this->postJson('/api/v1/catalog/locations', ['name' => 'Empty warehouse', 'code' => 'EMPTY', 'type' => 'warehouse', 'status' => 'active'])->assertCreated()->json('data.id');
        $this->deleteJson('/api/v1/catalog/locations/'.$location)->assertNoContent();
        $this->assertDatabaseHas('locations', ['id' => $location, 'status' => 'inactive']);
    }

    public function test_custom_role_deletion_requires_unassigned_members_and_never_deletes_system_roles(): void
    {
        $role = $this->postJson('/api/v1/roles', ['name' => 'Assistant', 'code' => 'custom-assistant', 'permissions' => ['products.view']])->assertCreated()->json('data.id');
        $member = $this->postJson('/api/v1/team', ['name' => 'Assistant', 'email' => 'assistant@example.test', 'password' => 'fixture-password-long', 'role_id' => $role])->assertCreated()->json('data.id');
        $this->deleteJson('/api/v1/roles/'.$role)->assertConflict();
        $this->deleteJson('/api/v1/team/'.$member)->assertNoContent();
        $this->deleteJson('/api/v1/roles/'.$role)->assertConflict();
        $viewer = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'viewer')->value('id');
        $this->patchJson('/api/v1/team/'.$member, ['role_id' => $viewer, 'status' => 'suspended'])->assertOk();
        $this->deleteJson('/api/v1/roles/'.$role)->assertNoContent();
        $this->assertDatabaseMissing('tenant_role_permissions', ['role_id' => $role]);
        $this->assertDatabaseMissing('tenant_roles', ['id' => $role]);
        $this->deleteJson('/api/v1/roles/'.$viewer)->assertForbidden();
        $owner = DB::table('tenant_memberships')->where('tenant_id', $this->legacyTenant)->where('user_id', $this->owner->id)->value('id');
        $this->deleteJson('/api/v1/team/'.$owner)->assertConflict();
    }

    public function test_access_removal_affects_only_current_business_and_blocks_subsequent_requests(): void
    {
        $otherUser = User::create(['name' => 'Other owner', 'email' => 'other@example.test', 'password' => 'fixture-password-long']);
        $otherTenant = app(TenantService::class)->create($otherUser, ['name' => 'Other shop', 'slug' => 'other-shop', 'timezone' => 'UTC', 'currency_code' => 'PKR']);
        $viewer = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'viewer')->value('id');
        $member = $this->postJson('/api/v1/team', ['name' => 'Other owner', 'email' => $otherUser->email, 'role_id' => $viewer])->assertCreated()->json('data.id');
        $password = $otherUser->password;
        $this->deleteJson('/api/v1/team/'.$member)->assertNoContent();
        $this->assertSame($password, $otherUser->fresh()->password);
        $this->assertDatabaseHas('tenant_memberships', ['tenant_id' => $otherTenant, 'user_id' => $otherUser->id, 'status' => 'active']);
        $this->selectTenant($this->legacyTenant, $otherUser);
        $this->getJson('/api/v1/products')->assertForbidden();
    }

    public function test_foreign_tenant_ids_and_read_only_roles_cannot_mutate_records(): void
    {
        $product = DB::table('catalog_variants')->where('id', $this->variantId)->value('product_id');
        $member = DB::table('tenant_memberships')->where('tenant_id', $this->legacyTenant)->value('id');
        $role = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->value('id');
        $customer = $this->postJson('/api/v1/operations/customers', ['name' => 'Private customer'])->assertCreated()->json('data.id');
        $otherUser = User::create(['name' => 'Other owner', 'email' => 'other@example.test', 'password' => 'fixture-password-long']);
        $otherTenant = app(TenantService::class)->create($otherUser, ['name' => 'Other shop', 'slug' => 'other-shop', 'timezone' => 'UTC', 'currency_code' => 'PKR']);
        DB::table('tenant_subscriptions')->where('tenant_id', $otherTenant)->update(['grandfathered' => true]);
        $this->selectTenant($otherTenant, $otherUser);
        foreach (['products/'.$product, 'products/'.$product.'/variants/'.$this->variantId, 'operations/customers/'.$customer, 'team/'.$member, 'roles/'.$role, 'catalog/locations/'.$this->locationId] as $path) {
            $this->deleteJson('/api/v1/'.$path)->assertNotFound();
        }
        $this->patchJson('/api/v1/operations/customers/'.$customer, ['name' => 'Stolen'])->assertNotFound();
        $viewer = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'viewer')->value('id');
        DB::table('tenant_memberships')->insert(['tenant_id' => $this->legacyTenant, 'user_id' => $otherUser->id, 'role_id' => $viewer, 'status' => 'active']);
        $this->selectTenant($this->legacyTenant, $otherUser);
        foreach (['products/'.$product, 'products/'.$product.'/variants/'.$this->variantId, 'operations/customers/'.$customer, 'team/'.$member, 'roles/'.$role, 'catalog/locations/'.$this->locationId] as $path) {
            $this->deleteJson('/api/v1/'.$path)->assertForbidden();
        }
        $this->assertDatabaseHas('customers', ['id' => $customer, 'name' => 'Private customer', 'active' => true]);
    }

    public function test_internal_exchange_payment_method_cannot_be_modified_or_deactivated(): void
    {
        $method = DB::table('payment_methods')->insertGetId(['tenant_id' => $this->legacyTenant, 'name' => 'Internal exchange credit', 'code' => 'EXCHANGE', 'method_type' => 'exchange', 'active' => true]);
        $this->deleteJson('/api/v1/operations/payment-methods/'.$method)->assertConflict();
        $this->patchJson('/api/v1/operations/payment-methods/'.$method, ['name' => 'Changed', 'method_type' => 'cash'])->assertConflict();
        $this->assertDatabaseHas('payment_methods', ['id' => $method, 'method_type' => 'exchange', 'active' => true]);
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantJobMiddleware;
use App\Domain\Tenancy\TenantService;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildTenantDatabase;
use Tests\TestCase;

class TenancyCatalogTest extends TestCase
{
    use BuildTenantDatabase;
    protected function setUp(): void { parent::setUp(); $this->buildDatabase(); }

    private function otherTenant(): array
    {
        $user = User::create(['name' => 'Other Owner', 'email' => 'other@example.test', 'password' => 'test-password-long', 'role_id' => null]);
        return [app(TenantService::class)->create($user, ['name' => 'Other Shop', 'slug' => 'other-shop', 'timezone' => 'Asia/Karachi', 'currency_code' => 'PKR']), $user];
    }

    public function test_public_onboarding_creates_only_a_membership_identity_and_logs_in(): void
    {
        $response = $this->postJson('/api/v1/auth/register', ['name' => 'New Owner', 'email' => 'new@example.test', 'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password', 'business_name' => 'New Shop', 'slug' => 'new-shop', 'currency_code' => 'PKR', 'timezone' => 'Asia/Karachi'])->assertCreated();
        $tenant = $response->json('data.active_tenant.id');
        $this->assertNotEquals($this->legacyTenant, $tenant);
        $this->assertDatabaseHas('users', ['email' => 'new@example.test', 'role_id' => null]);
        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/context')->assertOk()->assertJsonCount(1, 'data.branches');
        $this->get('/order')->assertRedirect();
        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->postJson('/api/v1/auth/login', ['email' => 'new@example.test', 'password' => 'a-long-test-password'])->assertOk()->assertJsonPath('data.active_tenant.id', $tenant);
    }

    public function test_tenant_a_cannot_list_read_update_archive_export_or_download_tenant_b_data(): void
    {
        Storage::fake('tenant_private');
        [$other, $user] = $this->otherTenant(); $this->selectTenant($other, $user);
        [$product] = $this->createCatalogProduct('SECRET OTHER PRODUCT', 'OTHER-1');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6Rz8AAAAASUVORK5CYII=');
        $image = $this->postJson('/api/v1/products/'.$product.'/images', ['image' => UploadedFile::fake()->createWithContent('photo.png', $png)])->assertCreated()->json('data.id');
        $this->selectTenant($this->legacyTenant);
        $this->getJson('/api/v1/products')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/products/'.$product)->assertNotFound();
        $this->patchJson('/api/v1/products/'.$product, ['name' => 'Stolen'])->assertNotFound();
        $this->deleteJson('/api/v1/products/'.$product)->assertNotFound();
        $this->get('/api/v1/products/export')->assertOk()->assertStreamedContent("product_id,product,variant_id,sku,cost,price,active\n");
        $this->getJson('/api/v1/catalog-images/'.$image)->assertNotFound();
        $this->getJson('/api/v1/legacy/catalog')->assertOk()->assertJsonPath('data.stocks.total', 0);
        $this->postJson('/api/v1/tenants/'.$other.'/activate')->assertNotFound();
    }

    public function test_cross_tenant_parent_submissions_are_rejected_and_database_fks_also_enforce_it(): void
    {
        [$other, $user] = $this->otherTenant(); $this->selectTenant($other, $user);
        $branch = $this->getJson('/api/v1/catalog/branches')->json('data.0.id');
        $unit = $this->postJson('/api/v1/catalog/units', ['code' => 'EA', 'name' => 'Each', 'quantity_precision' => 0])->assertCreated()->json('data.id');
        $this->selectTenant($this->legacyTenant);
        $this->postJson('/api/v1/catalog/locations', ['name' => 'Bad', 'code' => 'BAD', 'branch_id' => $branch, 'type' => 'store', 'status' => 'active'])->assertUnprocessable();
        $this->postJson('/api/v1/products', ['name' => 'Bad', 'product_type' => 'simple', 'track_inventory' => true, 'variant' => ['sku' => 'BAD', 'base_unit_id' => $unit, 'default_cost' => '1', 'default_price' => '2', 'tracking_mode' => 'none']])->assertUnprocessable();
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('locations')->insert(['tenant_id' => $this->legacyTenant, 'branch_id' => $branch, 'code' => 'CROSS', 'name' => 'Invalid', 'type' => 'store', 'status' => 'active']);
    }

    public function test_sku_and_barcode_uniqueness_is_scoped_and_prices_are_validated(): void
    {
        $this->selectTenant($this->legacyTenant); [$product, $unit] = $this->createCatalogProduct();
        $variant = $this->getJson('/api/v1/products/'.$product)->json('data.variants.0.id');
        $this->postJson('/api/v1/catalog/barcodes', ['variant_id' => $variant, 'barcode' => '123456'])->assertCreated();
        $this->postJson('/api/v1/catalog/barcodes', ['variant_id' => $variant, 'barcode' => '123456'])->assertUnprocessable();
        $this->patchJson('/api/v1/products/'.$product.'/variants/'.$variant, ['default_price' => '-1'])->assertUnprocessable();
        $this->patchJson('/api/v1/products/'.$product.'/variants/'.$variant, ['default_price' => '1.12345'])->assertUnprocessable();
        $this->patchJson('/api/v1/products/'.$product.'/variants/'.$variant, ['default_price' => 12.34])->assertUnprocessable();
        [$other, $user] = $this->otherTenant(); $this->selectTenant($other, $user); [$otherProduct] = $this->createCatalogProduct('Other Shirt', 'SHIRT-1');
        $otherVariant = $this->getJson('/api/v1/products/'.$otherProduct)->json('data.variants.0.id');
        $this->postJson('/api/v1/catalog/barcodes', ['variant_id' => $otherVariant, 'barcode' => '123456'])->assertCreated();
    }

    public function test_switching_revalidates_membership_and_revocation_blocks_next_request(): void
    {
        [$other, $user] = $this->otherTenant();
        $viewer = DB::table('tenant_roles')->where('tenant_id', $other)->where('code', 'viewer')->value('id');
        DB::table('tenant_memberships')->insert(['tenant_id' => $other, 'user_id' => $this->owner->id, 'role_id' => $viewer, 'status' => 'active']);
        $this->selectTenant($this->legacyTenant);
        $this->postJson('/api/v1/tenants/'.$other.'/activate')->assertOk()->assertJsonPath('data.active_tenant.id', $other);
        $this->postJson('/api/v1/catalog/units', ['name' => 'No', 'code' => 'NO', 'quantity_precision' => 0])->assertForbidden();
        DB::table('tenant_memberships')->where('tenant_id', $other)->where('user_id', $this->owner->id)->update(['status' => 'suspended']);
        $this->getJson('/api/v1/products')->assertForbidden();
        $this->assertNull(app(TenantContext::class)->tenant());
    }

    public function test_last_owner_and_system_roles_cannot_be_removed_or_cross_assigned(): void
    {
        $this->selectTenant($this->legacyTenant);
        $member = DB::table('tenant_memberships')->where('tenant_id', $this->legacyTenant)->first();
        $viewer = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'viewer')->value('id');
        $this->patchJson('/api/v1/team/'.$member->id, ['role_id' => $viewer, 'status' => 'active'])->assertStatus(409);
        $this->patchJson('/api/v1/roles/'.$member->role_id, ['code' => 'custom-owner', 'name' => 'Changed', 'permissions' => []])->assertForbidden();
        $this->postJson('/api/v1/team', ['name' => 'New Cashier', 'email' => 'cashier@example.test', 'password' => 'long-test-password', 'role_id' => $viewer])->assertCreated()->assertJsonMissingPath('data.password');
    }

    public function test_suspension_blocks_writes_but_allows_reads(): void
    {
        $this->selectTenant($this->legacyTenant);
        DB::table('tenants')->where('id', $this->legacyTenant)->update(['status' => 'suspended']);
        $this->getJson('/api/v1/products')->assertOk();
        $this->postJson('/api/v1/catalog/units', ['code' => 'NO', 'name' => 'No', 'quantity_precision' => 0])->assertForbidden();
    }

    public function test_queue_context_cache_and_storage_are_isolated_and_cleaned_on_failure(): void
    {
        [$other, $user] = $this->otherTenant(); $context = app(TenantContext::class);
        $first = $context->runFor($this->legacyTenant, $this->owner->id, fn ($c) => [$c->cacheKey('products'), $c->storagePath('catalog/photo.png')]);
        $second = $context->runFor($other, $user->id, fn ($c) => [$c->cacheKey('products'), $c->storagePath('catalog/photo.png')]);
        $this->assertNotSame($first, $second);
        try { (new TenantJobMiddleware($other, $user->id))->handle(new \stdClass(), function () { throw new \RuntimeException('Job failed'); }); } catch (\RuntimeException $e) {}
        $this->assertNull($context->tenant());
    }

    public function test_tax_overlaps_and_attribute_parent_mismatch_are_rejected(): void
    {
        $this->selectTenant($this->legacyTenant);
        $tax = $this->postJson('/api/v1/catalog/tax-classes', ['code' => 'GENERAL', 'name' => 'General'])->assertCreated()->json('data.id');
        $rate = ['tax_class_id' => $tax, 'jurisdiction_code' => 'LOCAL', 'rate_percent' => '5.0000', 'valid_from' => '2026-01-01', 'valid_to' => null];
        $this->postJson('/api/v1/catalog/tax-rates', $rate)->assertCreated();
        $this->postJson('/api/v1/catalog/tax-rates', $rate)->assertUnprocessable();
        $first = $this->postJson('/api/v1/catalog/attributes', ['code' => 'COLOR', 'name' => 'Color'])->assertCreated()->json('data.id');
        $second = $this->postJson('/api/v1/catalog/attributes', ['code' => 'SIZE', 'name' => 'Size'])->assertCreated()->json('data.id');
        $value = $this->postJson('/api/v1/catalog/attribute-values', ['attribute_id' => $first, 'value' => 'Red'])->assertCreated()->json('data.id');
        [$product] = $this->createCatalogProduct();
        $variant = $this->getJson('/api/v1/products/'.$product)->json('data.variants.0.id');
        $this->postJson('/api/v1/catalog/variant-values', ['variant_id' => $variant, 'attribute_id' => $second, 'value_id' => $value])->assertUnprocessable();
    }

    public function test_membership_grants_reuse_an_identity_without_resetting_its_password_or_other_roles(): void
    {
        [$other, $user] = $this->otherTenant(); $password = $user->password;
        $original = DB::table('tenant_memberships')->where('user_id', $user->id)->first();
        $this->selectTenant($this->legacyTenant);
        $role = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'viewer')->value('id');
        $this->postJson('/api/v1/team', ['name' => 'Replacement Name', 'email' => $user->email, 'password' => 'replacement-password', 'role_id' => $role])->assertCreated();
        $this->assertSame($password, $user->fresh()->password);
        $this->assertSame('Other Owner', $user->fresh()->name);
        $this->assertDatabaseHas('tenant_memberships', ['id' => $original->id, 'role_id' => $original->role_id]);
        $this->assertSame(2, DB::table('tenant_memberships')->where('user_id', $user->id)->count());
    }

    public function test_category_cycles_and_client_tenant_overrides_do_not_cross_ownership(): void
    {
        $this->selectTenant($this->legacyTenant);
        [$other] = $this->otherTenant();
        $first = $this->postJson('/api/v1/catalog/categories', ['name' => 'First', 'slug' => 'first', 'tenant_id' => $other])->assertCreated()->json('data.id');
        $second = $this->postJson('/api/v1/catalog/categories', ['name' => 'Second', 'slug' => 'second', 'parent_id' => $first])->assertCreated()->json('data.id');
        $this->patchJson('/api/v1/catalog/categories/'.$first, ['parent_id' => $second])->assertUnprocessable();
        $this->assertDatabaseHas('catalog_categories', ['id' => $first, 'tenant_id' => $this->legacyTenant]);
        $this->withSession(['active_tenant_id' => $other])->getJson('/api/v1/products')->assertForbidden();
    }

    public function test_archiving_retains_product_variants_and_csv_prevents_formula_execution(): void
    {
        $this->selectTenant($this->legacyTenant);
        [$product] = $this->createCatalogProduct('=SUM(1,2)', 'FORMULA-1');
        $export = $this->get('/api/v1/products/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=SUM(1,2)", $export);
        $this->deleteJson('/api/v1/products/'.$product)->assertNoContent();
        $this->assertDatabaseHas('catalog_products', ['id' => $product, 'active' => false]);
        $this->assertSame(1, DB::table('catalog_variants')->where('product_id', $product)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog.product.archived', 'tenant_id' => $this->legacyTenant]);
    }

    public function test_custom_roles_require_read_access_and_can_be_edited_only_in_their_tenant(): void
    {
        $this->selectTenant($this->legacyTenant);
        $this->postJson('/api/v1/roles', ['name' => 'Invalid', 'code' => 'custom-invalid', 'permissions' => ['products.manage']])->assertUnprocessable();
        $id = $this->postJson('/api/v1/roles', ['name' => 'Catalog Reader', 'code' => 'custom-reader', 'permissions' => ['products.view']])->assertCreated()->json('data.id');
        $this->patchJson('/api/v1/roles/'.$id, ['name' => 'Catalog Editor', 'code' => 'custom-reader', 'permissions' => ['products.view', 'products.manage']])->assertOk();
        [$other, $user] = $this->otherTenant(); $this->selectTenant($other, $user);
        $this->patchJson('/api/v1/roles/'.$id, ['name' => 'Other', 'code' => 'custom-reader', 'permissions' => []])->assertNotFound();
    }
}

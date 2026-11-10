<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\TestCase;

class SpecialistDashboardTest extends TestCase
{
    use BuildAdvancedDatabase;

    protected function setUp(): void
    {
        parent::setUp(); Carbon::setTestNow(Carbon::parse('2026-11-05 10:00:00', 'UTC'));
        $this->buildAdvanced(); (require database_path('migrations/2026_11_03_160000_create_dashboard_access_foundation.php'))->up();
        config(['dashboard.specialist_enabled' => true, 'dashboard.platform_enabled' => true, 'dashboard.pilot_tenant_ids' => [], 'dashboard.cache_seconds' => 0]);
    }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function endpoint(string $resource, array $filters = []): string { return '/api/v1/dashboard/'.$resource.'?'.http_build_query($filters + ['from' => '2026-11-05', 'to' => '2026-11-05']); }
    private function staff(array $permissions, string $code = 'fixture-specialist'): array
    {
        $user = User::create(['name' => 'Specialist fixture', 'email' => bin2hex(random_bytes(5)).'@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $role = DB::table('tenant_roles')->insertGetId(['tenant_id' => $this->legacyTenant, 'code' => $code, 'name' => 'Fixture role', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()]);
        foreach ($permissions as $permission) DB::table('tenant_role_permissions')->insert(['tenant_id' => $this->legacyTenant, 'role_id' => $role, 'permission_code' => $permission]);
        $member = DB::table('tenant_memberships')->insertGetId(['tenant_id' => $this->legacyTenant, 'user_id' => $user->id, 'role_id' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        return [$user, $member];
    }
    private function restrict(int $member, array $branches): void
    {
        $this->selectTenant($this->legacyTenant);
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'assigned', 'branch_ids' => $branches, 'sales_scope' => 'all', 'additional_role_ids' => []])->assertOk();
    }

    public function test_movement_quantities_reconcile_to_posted_records_without_cost_fields(): void
    {
        $sale = $this->sell(); $this->returnSale($sale);
        $trend = $this->getJson($this->endpoint('inventory-trend'))->assertOk()->assertJsonPath('data.items.0.unit', 'EA')->assertJsonPath('data.items.0.series.0.received', '11.0000')->assertJsonPath('data.items.0.series.0.issued', '2.0000')->assertJsonMissingPath('data.unit_cost_snapshot')->json('data');
        $onHand = DB::table('inventory_balances')->where('location_id', $this->locationId)->value('on_hand');
        $this->assertSame(0, bccomp(bcsub($trend['items'][0]['series'][0]['received'], $trend['items'][0]['series'][0]['issued'], 4), (string) $onHand, 4));
        $this->assertNull($trend['transfers']);
    }

    public function test_movement_units_and_days_are_separate_and_server_paginated(): void
    {
        $unit = $this->postJson('/api/v1/catalog/units', ['code' => 'KG', 'name' => 'Kilogram', 'quantity_precision' => 3])->assertCreated()->json('data.id');
        $product = $this->postJson('/api/v1/products', ['name' => 'Weighted', 'product_type' => 'simple', 'track_inventory' => true, 'variant' => ['sku' => 'WEIGHTED', 'base_unit_id' => $unit, 'default_cost' => '1.0000', 'default_price' => '2.0000', 'tracking_mode' => 'none']])->assertCreated()->json('data.id');
        $variant = DB::table('catalog_variants')->where('product_id', $product)->value('id');
        $adjustment = $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'kg-stock', 'location_id' => $this->locationId, 'reason' => 'KG fixture', 'items' => [['variant_id' => $variant, 'quantity_delta' => '1.250']]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/adjustments/'.$adjustment.'/post')->assertOk();
        $this->getJson($this->endpoint('inventory-trend', ['per_page' => 1]))->assertOk()->assertJsonPath('data.pagination.total', 2)->assertJsonCount(1, 'data.items');
        $this->getJson($this->endpoint('inventory-trend', ['per_page' => 1, 'page' => 2]))->assertOk()->assertJsonPath('data.items.0.unit', 'KG')->assertJsonPath('data.items.0.series.0.received', '1.2500');
    }

    public function test_empty_and_forged_branch_scopes_never_return_inventory_activity(): void
    {
        [$user, $member] = $this->staff(['dashboard.view', 'inventory.view']);
        $this->restrict($member, []); $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('inventory-trend'))->assertOk()->assertJsonCount(0, 'data.items')->assertJsonPath('data.pagination.total', 0);
        $this->getJson($this->endpoint('inventory-trend', ['branch_id' => $this->branchId]))->assertForbidden();
        $this->getJson($this->endpoint('inventory-trend', ['tenant_id' => 999]))->assertUnprocessable();
    }

    public function test_purchase_order_and_supplier_counts_match_source_and_have_no_invented_dates(): void
    {
        $supplier = $this->postJson('/api/v1/operations/suppliers', ['name' => 'Scoped supplier', 'payment_terms_days' => 14])->assertCreated()->json('data.id');
        foreach (['first-po', 'second-po'] as $key) {
            $id = $this->postJson('/api/v1/purchasing/orders', ['idempotency_key' => $key, 'supplier_id' => $supplier, 'location_id' => $this->locationId, 'items' => [['variant_id' => $this->variantId, 'ordered_qty' => '2', 'unit_cost' => '3.50']]])->assertCreated()->json('data.id');
            $this->postJson('/api/v1/purchasing/orders/'.$id.'/approve')->assertOk();
        }
        $this->getJson($this->endpoint('purchase-orders', ['per_page' => 1]))->assertOk()->assertJsonPath('data.pagination.total', 2)->assertJsonCount(1, 'data.items')->assertJsonPath('data.expected_deliveries', null)->assertJsonPath('data.overdue_receipts', null);
        $this->getJson($this->endpoint('supplier-overview'))->assertOk()->assertJsonPath('data.items.0.orders', 2)->assertJsonPath('data.items.0.open_orders', 2)->assertJsonPath('data.items.0.outstanding_payables', '0.0000');
        $this->getJson('/api/v1/purchasing/orders?status=open')->assertOk()->assertJsonPath('total', 2);
    }

    public function test_accounting_trend_and_payment_flows_reconcile_with_the_existing_report(): void
    {
        $this->sell();
        $trend = $this->getJson($this->endpoint('accounting-trend'))->assertOk()->assertJsonPath('data.series.0.net_sales_excluding_tax', '24.6900')->assertJsonPath('data.series.0.posted_expenses', '0.0000')->assertJsonPath('data.methods.0.method_type', 'cash')->assertJsonPath('data.methods.0.net_collections', '24.6900')->assertJsonPath('data.bank_balance', null)->json('data');
        $report = $this->getJson('/api/v1/reports/summary?from=2026-11-05&to=2026-11-05')->assertOk()->json('data');
        $this->assertSame(0, bccomp($report['totals']['net_sales_excluding_tax'], $trend['series'][0]['net_sales_excluding_tax'], 4));
        $this->assertSame(0, bccomp($report['totals']['net_external_collections'], $trend['methods'][0]['net_collections'], 4));
    }

    public function test_accountant_gets_real_variance_count_without_unauthorized_shift_documents(): void
    {
        DB::table('cash_sessions')->where('id', $this->shiftId)->update(['status' => 'closed', 'closed_at' => now(), 'variance' => '1.5000']);
        [$user] = $this->staff(['dashboard.view', 'reports.view']); $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('accounting-exceptions'))->assertOk()->assertJsonPath('data.count', 1)->assertJsonCount(0, 'data.items')->assertJsonPath('data.can_open_shifts', false);
        $this->selectTenant($this->legacyTenant);
        $this->getJson($this->endpoint('accounting-exceptions'))->assertOk()->assertJsonPath('data.items.0.variance', '1.5')->assertJsonPath('data.can_open_shifts', true);
    }

    public function test_associate_view_is_personal_even_with_additional_reporting_grants(): void
    {
        $other = $this->sell(); $own = $this->sell('1');
        [$user, $member] = $this->staff(['dashboard.view', 'sales.view', 'reports.view', 'customers.view'], 'sales_associate');
        DB::table('sales')->where('id', $own)->update(['cashier_membership_id' => $member]); $this->selectTenant($this->legacyTenant, $user);
        $this->getJson('/api/v1/me/context')->assertOk()->assertJsonPath('data.active_tenant.dashboard.profile', 'sales_associate');
        $this->getJson($this->endpoint('personal-sales'))->assertOk()->assertJsonPath('meta.sales_scope', 'own')->assertJsonPath('data.sales.invoice_count', 1)->assertJsonPath('data.receipts.items.0.id', $own)->assertJsonPath('data.assigned_customers', null)->assertJsonMissingPath('data.accounting')->assertJsonMissingPath('data.unit_cost_snapshot');
        $this->getJson($this->endpoint('personal-sales', ['membership_id' => $this->owner->id]))->assertUnprocessable();
        $this->assertNotSame($own, $other);
    }

    public function test_permission_projection_and_pilot_rollback_apply_before_cache_access(): void
    {
        config(['dashboard.cache_seconds' => 30]); $this->getJson($this->endpoint('inventory-trend'))->assertOk();
        config(['dashboard.specialist_enabled' => false]); $this->getJson($this->endpoint('inventory-trend'))->assertForbidden();
        $this->getJson($this->endpoint('summary'))->assertOk();
        config(['dashboard.specialist_enabled' => true, 'dashboard.pilot_tenant_ids' => ['invalid']]); $this->getJson($this->endpoint('inventory-trend'))->assertForbidden();
        config(['dashboard.pilot_tenant_ids' => [(string) $this->legacyTenant]]); $this->getJson($this->endpoint('inventory-trend'))->assertOk();
        [$user] = $this->staff(['dashboard.view', 'products.view']); $this->selectTenant($this->legacyTenant, $user);
        foreach (['inventory-trend', 'purchase-orders', 'supplier-overview', 'accounting-trend', 'accounting-exceptions', 'personal-sales'] as $resource) $this->getJson($this->endpoint($resource))->assertForbidden();
    }

    public function test_platform_aggregate_requires_explicit_grant_and_never_contains_tenant_details(): void
    {
        $this->getJson('/api/v1/platform/dashboard')->assertForbidden();
        $this->artisan('platform:permission', ['action' => 'grant', 'user' => $this->owner->id, '--operator' => 'D6 fixture'])->assertSuccessful();
        $response = $this->getJson('/api/v1/platform/dashboard?from=2026-11-05&to=2026-11-05')->assertOk()->assertJsonPath('meta.scope', 'platform')->assertJsonPath('data.tenants.total', 1)->assertJsonPath('data.mrr', null)->assertJsonMissingPath('data.tenants.items');
        $text = $response->getContent(); foreach ([$this->owner->email, 'Legacy Owner', 'payload_json', 'stripe_customer_id', 'password', 'invoice_no'] as $secret) $this->assertStringNotContainsString($secret, $text);
        $this->getJson('/api/v1/platform/dashboard?tenant_id='.$this->legacyTenant)->assertUnprocessable();
        $this->artisan('platform:permission', ['action' => 'revoke', 'user' => $this->owner->id, '--operator' => 'D6 fixture'])->assertSuccessful();
        $this->getJson('/api/v1/platform/dashboard')->assertForbidden();
    }

    public function test_platform_flag_and_date_limits_fail_closed(): void
    {
        $this->artisan('platform:permission', ['action' => 'grant', 'user' => $this->owner->id, '--operator' => 'D6 fixture'])->assertSuccessful();
        $this->getJson('/api/v1/platform/dashboard?from=2025-01-01&to=2026-11-05')->assertUnprocessable();
        config(['dashboard.platform_enabled' => false]); $this->getJson('/api/v1/platform/dashboard')->assertNotFound();
        $this->getJson('/api/v1/platform/context')->assertOk()->assertJsonPath('data.dashboard_enabled', false);
    }

    public function test_new_projections_never_cross_real_tenants_or_a_warm_cache(): void
    {
        config(['dashboard.cache_seconds' => 30]); $this->sell();
        $this->getJson($this->endpoint('inventory-trend'))->assertOk()->assertJsonPath('data.items.0.series.0.received', '10.0000');
        $this->getJson($this->endpoint('personal-sales'))->assertOk()->assertJsonPath('data.sales.invoice_count', 1);
        $otherUser = User::create(['name' => 'Other owner', 'email' => 'd7-other@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $other = app(\App\Domain\Tenancy\TenantService::class)->create($otherUser, ['name' => 'Other fixture', 'slug' => 'd7-other', 'timezone' => 'Asia/Karachi', 'currency_code' => 'PKR']);
        DB::table('tenant_subscriptions')->where('tenant_id', $other)->update(['grandfathered' => true]);
        $this->selectTenant($other, $otherUser);
        $this->getJson($this->endpoint('inventory-trend'))->assertOk()->assertJsonPath('meta.tenant_id', $other)->assertJsonCount(0, 'data.items');
        foreach (['purchase-orders', 'supplier-overview'] as $resource) $this->getJson($this->endpoint($resource))->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson($this->endpoint('accounting-trend'))->assertOk()->assertJsonPath('data.series.0.net_sales_excluding_tax', '0.0000')->assertJsonCount(0, 'data.methods');
        $this->getJson($this->endpoint('accounting-exceptions'))->assertOk()->assertJsonPath('data.count', 0);
        $this->getJson($this->endpoint('personal-sales'))->assertOk()->assertJsonPath('data.sales.invoice_count', 0);
        foreach (['inventory-trend', 'purchase-orders', 'supplier-overview', 'accounting-trend', 'accounting-exceptions', 'personal-sales'] as $resource) $this->getJson($this->endpoint($resource, ['branch_id' => $this->branchId]))->assertForbidden();
        $this->selectTenant($this->legacyTenant);
        $this->getJson($this->endpoint('inventory-trend'))->assertOk()->assertJsonPath('meta.cache_hit', true)->assertJsonPath('data.items.0.series.0.received', '10.0000');
        $this->selectTenant($other);
        $this->getJson($this->endpoint('inventory-trend'))->assertForbidden();
    }

    public function test_expense_projection_uses_posting_time_and_business_midnight(): void
    {
        $category = $this->postJson('/api/v1/expenses/categories', ['name' => 'Supplies'])->assertCreated()->json('data.id');
        foreach (['before-midnight', 'at-midnight', 'draft-only'] as $key) {
            $id = $this->postJson('/api/v1/expenses', ['idempotency_key' => $key, 'category_id' => $category, 'branch_id' => $this->branchId, 'cash_session_id' => $this->shiftId, 'method_id' => $this->cashId, 'amount' => '4.25', 'description' => 'Fixture expense', 'expense_date' => '2026-11-01'])->assertCreated()->json('data.id');
            if ($key === 'draft-only') continue;
            $this->postJson('/api/v1/expenses/'.$id.'/approve')->assertOk();
            DB::table('retail_expenses')->where('id', $id)->update(['posted_at' => $key === 'before-midnight' ? '2026-11-04 18:59:59' : '2026-11-04 19:00:00']);
        }
        $this->getJson($this->endpoint('accounting-trend'))->assertOk()->assertJsonPath('data.series.0.posted_expenses', '4.2500');
        $this->getJson($this->endpoint('accounting-trend', ['from' => '2026-11-04']))->assertOk()->assertJsonPath('data.series.0.posted_expenses', '4.2500')->assertJsonPath('data.series.1.posted_expenses', '4.2500');
    }

    public function test_assigned_branch_restrictions_apply_to_every_new_projection(): void
    {
        $branch = $this->postJson('/api/v1/catalog/branches', ['code' => 'EMPTY', 'name' => 'Empty permitted branch', 'status' => 'active'])->assertCreated()->json('data.id');
        $this->sell();
        [$user, $member] = $this->staff(['dashboard.view', 'reports.view', 'sales.view', 'inventory.view', 'purchases.view', 'cash.open']);
        $this->restrict($member, [$branch]); $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('inventory-trend'))->assertOk()->assertJsonCount(0, 'data.items');
        foreach (['purchase-orders', 'supplier-overview'] as $resource) $this->getJson($this->endpoint($resource))->assertOk()->assertJsonCount(0, 'data.items');
        $this->getJson($this->endpoint('accounting-trend'))->assertOk()->assertJsonPath('data.series.0.net_sales_excluding_tax', '0.0000')->assertJsonCount(0, 'data.methods');
        $this->getJson($this->endpoint('accounting-exceptions'))->assertOk()->assertJsonPath('data.count', 0);
        $this->getJson($this->endpoint('personal-sales'))->assertOk()->assertJsonPath('data.sales.invoice_count', 0);
        foreach (['inventory-trend', 'purchase-orders', 'supplier-overview', 'accounting-trend', 'accounting-exceptions', 'personal-sales'] as $resource) $this->getJson($this->endpoint($resource, ['branch_id' => $this->branchId]))->assertForbidden();
    }

    public function test_personal_scope_dominates_reporting_union_and_permission_revocation_invalidates_cache(): void
    {
        [$user, $member] = $this->staff(['dashboard.view', 'reports.view', 'sales.view', 'inventory.view']);
        $this->selectTenant($this->legacyTenant);
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'all', 'branch_ids' => [], 'sales_scope' => 'own', 'additional_role_ids' => []])->assertOk();
        $this->selectTenant($this->legacyTenant, $user);
        foreach (['accounting-trend', 'accounting-exceptions'] as $resource) $this->getJson($this->endpoint($resource))->assertForbidden();
        config(['dashboard.cache_seconds' => 30]); $this->getJson($this->endpoint('inventory-trend'))->assertOk();
        $role = DB::table('tenant_memberships')->where('id', $member)->value('role_id');
        DB::table('tenant_role_permissions')->where('role_id', $role)->where('permission_code', 'inventory.view')->delete();
        $this->getJson($this->endpoint('inventory-trend'))->assertForbidden();
    }

    public function test_platform_only_identity_does_not_gain_tenant_membership(): void
    {
        $user = User::create(['name' => 'Platform fixture', 'email' => 'd7-platform@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $this->artisan('platform:permission', ['action' => 'grant', 'user' => $user->id, '--operator' => 'D7 fixture'])->assertSuccessful();
        $this->actingAs($user);
        $this->getJson('/api/v1/platform/dashboard')->assertOk()->assertJsonPath('meta.user_id', $user->id)->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.platform_dashboard_enabled', true);
        $this->getJson($this->endpoint('inventory-trend'))->assertStatus(409);
        $this->assertSame(0, DB::table('tenant_memberships')->where('user_id', $user->id)->count());
    }

    public function test_reporting_and_cash_open_do_not_grant_other_staff_shift_variance_documents(): void
    {
        DB::table('cash_sessions')->where('id', $this->shiftId)->update(['status' => 'closed', 'closed_at' => now(), 'variance' => '1.5000']);
        [$user, $member] = $this->staff(['dashboard.view', 'reports.view', 'cash.open']);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('accounting-exceptions'))->assertOk()->assertJsonPath('data.count', 1)->assertJsonPath('data.can_open_shifts', true)->assertJsonCount(0, 'data.items')->assertJsonPath('data.pagination.total', 0);
        DB::table('cash_sessions')->where('id', $this->shiftId)->update(['opened_by' => $member]);
        $this->getJson($this->endpoint('accounting-exceptions'))->assertOk()->assertJsonPath('data.items.0.id', $this->shiftId)->assertJsonPath('data.pagination.total', 1);
    }

    public function test_foreign_currency_collections_for_older_invoices_are_never_added_to_accounting(): void
    {
        $sale = $this->sell();
        DB::table('sales')->where('id', $sale)->update(['sold_at' => '2026-10-01 10:00:00']);
        DB::table('sale_payments')->where('sale_id', $sale)->update(['currency_code' => 'USD']);
        $this->getJson($this->endpoint('accounting-trend'))->assertConflict();
        $this->getJson($this->endpoint('summary'))->assertConflict();
        DB::table('sale_payments')->where('sale_id', $sale)->update(['currency_code' => 'PKR']);
        $this->getJson($this->endpoint('accounting-trend'))->assertOk()->assertJsonPath('data.series.0.net_sales_excluding_tax', '0.0000')->assertJsonPath('data.methods.0.net_collections', '24.6900');
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Tenancy\RecordScope;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantJobMiddleware;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\TestCase;

class DashboardFoundationTest extends TestCase
{
    use BuildAdvancedDatabase;

    private const ROOT = '/api/v1/dashboard/';

    public function test_d3_profiles_are_server_resolved_and_never_expand_widget_permissions(): void
    {
        $context = $this->getJson('/api/v1/me/context')->assertOk()->assertJsonPath('data.active_tenant.dashboard.profile', 'owner')->json('data.active_tenant');
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.tenant_id', $this->legacyTenant)
            ->assertJsonPath('meta.access_fingerprint', $context['access_fingerprint']);
        [$user, $member, $role] = $this->member(['dashboard.view', 'inventory.view']);
        DB::table('tenant_roles')->where('id', $role)->update(['name' => 'Business Owner']);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson('/api/v1/me/context')->assertOk()->assertJsonPath('data.active_tenant.dashboard.profile', null);
        $admin = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'admin')->value('id');
        DB::table('tenant_memberships')->where('id', $member)->update(['role_id' => $admin]);
        $this->restrict($member, [], 'own');
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson('/api/v1/me/context')->assertOk()->assertJsonPath('data.active_tenant.dashboard.profile', 'admin')
            ->assertJsonPath('data.active_tenant.dashboard.sales_scope', 'own')->assertJsonCount(0, 'data.active_tenant.allowed_branches');
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 0)->assertJsonMissingPath('data.accounting');
        $this->getJson($this->endpoint('branch-performance'))->assertForbidden();
    }

    public function test_d3_signed_branch_and_daily_charts_reconcile_with_reports_and_top_products(): void
    {
        [$second, , $shift] = $this->otherBranch();
        $oldSale = $this->saleInShift($shift, '1');
        DB::table('sales')->where('id', $oldSale)->update(['sold_at' => '2026-10-31 10:00:00']);
        DB::table('catalog_variants')->where('id', $this->variantId)->update(['default_price' => '45.0000']);
        $currentSale = $this->sell('2');
        DB::table('sales')->where('id', $currentSale)->update(['sold_at' => '2026-11-02 10:00:00']);
        $this->returnSale($oldSale);
        $filters = ['from' => '2026-11-01', 'to' => '2026-11-03'];
        $summary = $this->getJson($this->endpoint('summary', $filters))->assertOk()->assertJsonPath('data.sales.totals.net_sales_excluding_tax', '77.6500')->json('data.sales.totals');
        $trend = $this->getJson($this->endpoint('sales-trend', $filters))->assertOk()->assertJsonPath('data.series.0.net_sales_excluding_tax', '0.0000')
            ->assertJsonPath('data.series.1.net_sales_excluding_tax', '90.0000')->assertJsonPath('data.series.2.net_sales_excluding_tax', '-12.3500')->json('data.series');
        $branches = $this->getJson($this->endpoint('branch-performance', $filters))->assertOk()->json('data.items');
        foreach ([$trend, $branches] as $series) {
            $sum = '0';
            foreach ($series as $point) {
                $sum = bcadd($sum, $point['net_sales_excluding_tax'], 4);
            }
            $this->assertSame($summary['net_sales_excluding_tax'], $sum);
        }
        $this->getJson($this->endpoint('top-products', $filters))->assertOk()->assertJsonPath('data.items.0.net_sales_excluding_tax', '77.6500');
        foreach ($branches as $branch) {
            $path = $branch['links']['report'];
            $report = $this->getJson($path)->assertOk()->json('data.totals.net_sales_excluding_tax');
            $this->assertSame(0, bccomp($branch['net_sales_excluding_tax'], $report, 4));
        }
        $this->getJson($this->endpoint('summary', $filters + ['branch_id' => $second]))->assertOk()->assertJsonPath('data.sales.totals.net_sales_excluding_tax', '-12.3500');
    }

    public function test_d3_stock_drilldowns_match_kpis_and_preserve_branch_and_tenant_boundaries(): void
    {
        [$branch, $location] = $this->otherBranch();
        $this->patchJson('/api/v1/workspace/settings', ['receipt_footer' => 'Fixture', 'low_stock_threshold' => '12'])->assertOk();
        $this->getJson($this->endpoint('summary', ['branch_id' => $branch]))->assertOk()->assertJsonPath('data.inventory.low_stock_locations', 1);
        $this->getJson('/api/v1/inventory/balances?branch_id='.$branch.'&stock_state=low')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.location_id', $location);
        $alert = $this->getJson($this->endpoint('alerts', ['branch_id' => $branch]))->assertOk()->json('data.items.0');
        $this->assertSame('Second stock', $alert['location_name']);
        $this->assertSame(DB::table('catalog_variants')->where('id', $this->variantId)->value('sku'), $alert['sku']);
        $this->getJson($alert['links']['source'])->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.location_id', $location);
        [$user, $member] = $this->member(['dashboard.view', 'inventory.view', 'purchases.view']);
        $this->restrict($member, [$this->branchId]);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson('/api/v1/inventory/balances?branch_id='.$branch.'&stock_state=low')->assertUnprocessable();
        $this->getJson($alert['links']['source'])->assertUnprocessable();
        $this->getJson('/api/v1/purchasing/orders?branch_id='.$branch.'&status=open')->assertUnprocessable();
        $this->getJson('/api/v1/inventory/balances?stock_state=forged')->assertUnprocessable();
    }

    public function test_d3_purchasing_and_document_drilldowns_select_only_the_requested_records(): void
    {
        [$branch, $location] = $this->otherBranch();
        $supplier = $this->postJson('/api/v1/operations/suppliers', ['name' => 'D3 supplier', 'payment_terms_days' => 14])->assertCreated()->json('data.id');
        $make = function ($loc, $key) use ($supplier) {
            return $this->postJson('/api/v1/purchasing/orders', ['idempotency_key' => $key, 'supplier_id' => $supplier, 'location_id' => $loc, 'items' => [['variant_id' => $this->variantId, 'ordered_qty' => '1', 'unit_cost' => '3.50']]])->assertCreated()->json('data.id');
        };
        $draft = $make($this->locationId, 'd3-main-draft');
        $open = $make($location, 'd3-second-open');
        $this->postJson('/api/v1/purchasing/orders/'.$open.'/approve')->assertOk();
        $this->getJson('/api/v1/purchasing/orders?branch_id='.$branch.'&status=open')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $open);
        $this->getJson('/api/v1/purchasing/orders?document_id='.$draft)->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $draft);
        $this->getJson('/api/v1/purchasing/orders?branch_id='.$this->branchId.'&status=open')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/purchasing/orders?status=forged')->assertUnprocessable();
        $this->getJson('/api/v1/expenses?document_id=999999')->assertOk()->assertJsonPath('total', 0);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-03 10:00:00', 'UTC'));
        $this->buildAdvanced();
        (require database_path('migrations/2026_11_03_160000_create_dashboard_access_foundation.php'))->up();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function endpoint(string $resource = 'summary', array $filters = []): string
    {
        return self::ROOT.$resource.'?'.http_build_query($filters + ['from' => '2026-11-03', 'to' => '2026-11-03']);
    }

    private function member(?array $permissions = null): array
    {
        $user = User::create(['name' => 'Dashboard staff', 'email' => 'staff-'.bin2hex(random_bytes(3)).'@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $role = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'cashier')->value('id');
        if ($permissions !== null) {
            $role = DB::table('tenant_roles')->insertGetId(['tenant_id' => $this->legacyTenant, 'code' => 'custom-'.bin2hex(random_bytes(4)), 'name' => 'Scoped custom', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($permissions as $permission) {
                DB::table('tenant_role_permissions')->insert(['tenant_id' => $this->legacyTenant, 'role_id' => $role, 'permission_code' => $permission]);
            }
        }
        $id = DB::table('tenant_memberships')->insertGetId(['tenant_id' => $this->legacyTenant, 'user_id' => $user->id, 'role_id' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        return [$user, $id, $role];
    }

    private function restrict(int $member, array $branches, string $sales = 'all', array $roles = []): void
    {
        $this->selectTenant($this->legacyTenant);
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'assigned', 'branch_ids' => $branches, 'sales_scope' => $sales, 'additional_role_ids' => $roles])->assertOk();
    }

    private function otherBranch(): array
    {
        $this->selectTenant($this->legacyTenant);
        $branch = $this->postJson('/api/v1/catalog/branches', ['code' => 'SECOND', 'name' => 'Second branch', 'status' => 'active'])->assertCreated()->json('data.id');
        $location = $this->postJson('/api/v1/catalog/locations', ['code' => 'SECOND', 'name' => 'Second stock', 'branch_id' => $branch, 'type' => 'store', 'status' => 'active'])->assertCreated()->json('data.id');
        $preview = $this->getJson('/api/v1/pos/branches/'.$branch.'/preview')->assertOk()->json('data');
        $this->postJson('/api/v1/pos/branches/'.$branch.'/activate', ['idempotency_key' => 'second-branch-activate', 'preview_hash' => $preview['preview_hash'], 'confirm_locations' => true, 'allow_credit' => true])->assertOk();
        $register = $this->postJson('/api/v1/operations/registers', ['name' => 'Second register', 'branch_id' => $branch, 'location_id' => $location])->assertCreated()->json('data.id');
        $shift = $this->postJson('/api/v1/cash/sessions', ['idempotency_key' => 'second-open-shift', 'register_id' => $register, 'opening_cash' => '0.00'])->assertCreated()->json('data.id');
        $stock = $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'second-stock-setup', 'location_id' => $location, 'reason' => 'Fixture stock', 'items' => [['variant_id' => $this->variantId, 'quantity_delta' => '10']]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/adjustments/'.$stock.'/post')->assertOk();

        return [$branch, $location, $shift, $stock];
    }

    private function saleInShift(int $shift, string $qty = '2'): int
    {
        $previous = $this->shiftId;
        $this->shiftId = $shift;
        try {
            return $this->sell($qty);
        } finally {
            $this->shiftId = $previous;
        }
    }

    public function test_context_is_server_resolved_and_legacy_super_admin_is_not_platform_admin(): void
    {
        $this->getJson('/api/v1/me/context')->assertOk()->assertJsonPath('data.platform_permissions', [])->assertJsonPath('data.active_tenant.id', $this->legacyTenant)
            ->assertJsonPath('data.active_tenant.branch_scope', 'all')->assertJsonPath('data.active_tenant.allowed_branches.0.id', $this->branchId);
        $this->getJson('/api/v1/platform/context')->assertForbidden();
        $this->getJson($this->endpoint('summary', ['tenant_id' => $this->legacyTenant]))->assertUnprocessable();
        $this->getJson($this->endpoint('summary', ['cashier_membership_id' => 1]))->assertUnprocessable();
    }

    public function test_explicit_platform_grant_is_audited_revocable_and_does_not_grant_tenant_access(): void
    {
        $user = User::create(['name' => 'Platform operator', 'email' => 'platform@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $this->artisan('platform:permission', ['action' => 'grant', 'user' => $user->id, '--operator' => 'Test operator'])->assertSuccessful();
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->actingAs($user, 'web')->withSession(['active_tenant_id' => null]);
        $this->getJson('/api/v1/platform/context')->assertOk()->assertJsonPath('data.scope', 'platform');
        $this->getJson('/api/v1/me/context')->assertOk()->assertJsonPath('data.memberships', [])->assertJsonPath('data.active_tenant', null);
        $this->withSession(['active_tenant_id' => $this->legacyTenant])->getJson($this->endpoint())->assertForbidden();
        $this->artisan('platform:permission', ['action' => 'revoke', 'user' => $user->id, '--operator' => 'Test operator'])->assertSuccessful();
        $this->getJson('/api/v1/platform/context')->assertForbidden();
        $this->assertSame(2, DB::table('platform_access_audits')->where('user_id', $user->id)->count());
    }

    public function test_zero_and_unavailable_are_distinct_and_new_api_has_a_kill_switch(): void
    {
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.totals.net_sales_excluding_tax', '0.0000')
            ->assertJsonPath('data.inventory.low_stock_locations', null)->assertJsonPath('data.accounting.net_profit', null)->assertJsonPath('data.shift.held_sales', null);
        config(['dashboard.enabled' => false]);
        $this->getJson($this->endpoint())->assertNotFound();
    }

    public function test_posted_partial_and_damaged_returns_and_cancel_reconcile_with_existing_report(): void
    {
        $sale = $this->sell('3');
        $this->returnSale($sale, '1', 'damaged');
        $void = $this->sell('1');
        $this->postJson('/api/v1/sales/'.$void.'/void', ['idempotency_key' => 'dashboard-void-sale', 'reason' => 'Fixture cancellation'])->assertOk();
        $report = $this->getJson('/api/v1/reports/summary?from=2026-11-03&to=2026-11-03')->assertOk()->json('data');
        $data = $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 2)->assertJsonPath('data.sales.posted_return_count', 2)
            ->assertJsonPath('data.sales.voided_invoice_count', 1)->assertJsonPath('data.sales.posted_cancellation_count', 1)->json('data');
        foreach ($data['sales']['totals'] as $key => $amount) {
            $this->assertSame(0, bccomp($report['totals'][$key], $amount, 4), $key);
        }
        $trend = $this->getJson($this->endpoint('sales-trend'))->assertOk()->json('data.series');
        $this->assertSame($data['sales']['totals']['net_sales_excluding_tax'], $trend[0]['net_sales_excluding_tax']);
        $top = $this->getJson($this->endpoint('top-products'))->assertOk()->json('data.items');
        $this->assertSame($data['sales']['totals']['net_sales_excluding_tax'], $top[0]['net_sales_excluding_tax']);
        $this->assertSame('2.0000', $top[0]['net_units']);
    }

    public function test_return_of_older_invoice_is_recognized_on_post_date_in_summary_trend_and_top_products(): void
    {
        $sale = $this->sell();
        DB::table('sales')->where('id', $sale)->update(['sold_at' => '2026-10-31 10:00:00']);
        $this->returnSale($sale);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 0)->assertJsonPath('data.sales.totals.net_sales_including_tax', '-12.3500');
        $this->getJson($this->endpoint('sales-trend'))->assertOk()->assertJsonPath('data.series.0.net_sales_excluding_tax', '-12.3500');
        $this->getJson($this->endpoint('top-products'))->assertOk()->assertJsonPath('data.items.0.net_sales_excluding_tax', '-12.3500');
    }

    public function test_cashier_dashboard_is_personal_and_never_exposes_cost_profit_or_other_staff_receipts(): void
    {
        $other = $this->sell();
        $own = $this->sell();
        [$user, $member] = $this->member();
        DB::table('sales')->where('id', $own)->update(['cashier_membership_id' => $member]);
        $this->selectTenant($this->legacyTenant, $user);
        $response = $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 1)->assertJsonPath('meta.sales_scope', 'own');
        $this->assertArrayNotHasKey('accounting', $response->json('data'));
        $this->getJson($this->endpoint('recent-sales'))->assertOk()->assertJsonPath('data.pagination.total', 1)->assertJsonPath('data.items.0.id', $own);
        $this->getJson($this->endpoint('recent-sales/'.$other))->assertNotFound();
        $ownData = $this->getJson($this->endpoint('recent-sales/'.$own))->assertOk()->json('data.sale');
        foreach (['customer_snapshot_json', 'cashier_membership_id', 'unit_cost_snapshot'] as $key) {
            $this->assertArrayNotHasKey($key, $ownData);
        }
        $this->getJson($this->endpoint('accounting-summary'))->assertForbidden();
        $this->getJson($this->endpoint('branch-performance'))->assertForbidden();
        $this->getJson('/api/v1/sales/'.$own)->assertOk()->assertJsonMissingPath('data.items.0.unit_cost_snapshot');
        $this->getJson('/api/v1/products/'.DB::table('catalog_variants')->where('id', $this->variantId)->value('product_id'))->assertOk()->assertJsonMissingPath('data.variants.0.default_cost');
        $csv = $this->get('/api/v1/products/export')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('cost', strtok($csv, "\n"));
    }

    public function test_assigned_branch_scope_is_enforced_for_lists_details_writes_context_and_old_reports(): void
    {
        $own = $this->sell();
        [$branch, $location, $shift, $adjustment] = $this->otherBranch();
        $other = $this->saleInShift($shift);
        [$user, $member] = $this->member(['dashboard.view', 'sales.view', 'reports.view', 'inventory.view', 'inventory.adjust', 'inventory.approve', 'cash.open', 'cash.manage', 'payments.record']);
        $this->restrict($member, [$this->branchId]);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 1)->assertJsonPath('meta.branch_ids', [$this->branchId]);
        $this->getJson($this->endpoint('summary', ['branch_id' => $branch]))->assertForbidden();
        $this->getJson('/api/v1/sales')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $own);
        $this->getJson('/api/v1/sales/'.$other)->assertNotFound();
        $this->getJson('/api/v1/inventory/balances')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/inventory/adjustments/'.$adjustment)->assertNotFound();
        $this->getJson('/api/v1/cash/sessions/'.$shift.'/reconciliation')->assertNotFound();
        $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'forged-location-key', 'location_id' => $location, 'reason' => 'Forbidden location', 'items' => [['variant_id' => $this->variantId, 'quantity_delta' => '1']]])->assertUnprocessable();
        $this->getJson('/api/v1/context')->assertOk()->assertJsonCount(1, 'data.branches')->assertJsonCount(1, 'data.locations');
        $this->getJson('/api/v1/me/context')->assertOk()->assertJsonCount(1, 'data.active_tenant.allowed_branches');
        $this->getJson('/api/v1/reports/summary?from=2026-11-03&to=2026-11-03')->assertOk()->assertJsonPath('data.sale_count', 1);
    }

    public function test_assigned_scope_with_no_branches_returns_no_branch_data_even_with_broad_roles(): void
    {
        $this->sell();
        [$user, $member] = $this->member(['dashboard.view', 'sales.view', 'reports.view', 'inventory.view', 'purchases.view']);
        $this->restrict($member, []);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 0)->assertJsonPath('data.inventory.recorded_skus', 0)->assertJsonPath('meta.branch_ids', []);
        $this->getJson('/api/v1/sales')->assertOk()->assertJsonPath('total', 0);
        $this->getJson($this->endpoint('summary', ['branch_id' => $this->branchId]))->assertForbidden();
    }

    public function test_explicit_personal_restriction_overrides_reports_and_cash_management_permissions(): void
    {
        $other = $this->sell();
        $own = $this->sell();
        [$user, $member] = $this->member(['dashboard.view', 'sales.view', 'reports.view', 'cash.open', 'cash.manage']);
        DB::table('sales')->where('id', $own)->update(['cashier_membership_id' => $member]);
        $this->restrict($member, [$this->branchId], 'own');
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 1)->assertJsonMissingPath('data.accounting');
        $this->getJson('/api/v1/sales/'.$other)->assertNotFound();
        $this->getJson('/api/v1/cash/sessions')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/v1/reports/summary?from=2026-11-03&to=2026-11-03')->assertForbidden();
    }

    public function test_multiple_roles_union_grants_without_bypassing_explicit_restrictions(): void
    {
        [$user, $member] = $this->member(['dashboard.view', 'sales.view']);
        $role = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'inventory_clerk')->value('id');
        $this->restrict($member, [], 'own', [$role]);
        $this->selectTenant($this->legacyTenant, $user);
        $context = $this->getJson('/api/v1/me/context')->assertOk()->assertJsonCount(2, 'data.active_tenant.roles')->json('data.active_tenant');
        $this->assertContains('inventory.view', $context['permissions']);
        $this->assertContains('sales.view', $context['permissions']);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.inventory.recorded_skus', 0)->assertJsonPath('data.sales.invoice_count', 0);
    }

    public function test_cache_is_actor_branch_permission_and_data_revision_scoped(): void
    {
        $this->sell();
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.cache_hit', false);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.cache_hit', true);
        $this->sell();
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.cache_hit', false)->assertJsonPath('data.sales.invoice_count', 2);
        [$user, $member, $role] = $this->member(['dashboard.view', 'reports.view', 'sales.view']);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.cache_hit', false);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.cache_hit', true);
        $this->restrict($member, []);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.cache_hit', false)->assertJsonPath('data.sales.invoice_count', 0);
        DB::table('tenant_role_permissions')->where('role_id', $role)->where('permission_code', 'dashboard.view')->delete();
        $this->getJson($this->endpoint())->assertForbidden();
    }

    public function test_suspended_membership_and_forged_tenant_session_are_rejected_after_cache_warmup(): void
    {
        [$user, $member] = $this->member();
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint())->assertOk();
        DB::table('tenant_memberships')->where('id', $member)->update(['status' => 'suspended']);
        $this->getJson($this->endpoint())->assertForbidden();
        $this->selectTenant(99999);
        $this->getJson($this->endpoint())->assertForbidden();
    }

    public function test_dates_use_tenant_local_midnights_including_dst_and_end_is_exclusive(): void
    {
        DB::table('tenants')->where('id', $this->legacyTenant)->update(['timezone' => 'America/New_York']);
        $first = $this->sell();
        $second = $this->sell();
        $outside = $this->sell();
        DB::table('sales')->where('id', $first)->update(['sold_at' => '2026-11-01 04:00:00']);
        DB::table('sales')->where('id', $second)->update(['sold_at' => '2026-11-02 04:59:59']);
        DB::table('sales')->where('id', $outside)->update(['sold_at' => '2026-11-02 05:00:00']);
        $this->getJson($this->endpoint('summary', ['from' => '2026-11-01', 'to' => '2026-11-01']))->assertOk()->assertJsonPath('data.sales.invoice_count', 2)
            ->assertJsonPath('meta.start_inclusive', '2026-11-01T04:00:00+00:00')->assertJsonPath('meta.end_exclusive', '2026-11-02T05:00:00+00:00');
    }

    public function test_invalid_ranges_limits_and_forged_actor_filters_are_rejected(): void
    {
        foreach ([['from' => '2026-11-04'], ['from' => '2026-02-30'], ['from' => '2024-01-01'], ['limit' => 100], ['per_page' => 51], ['branch_ids' => [$this->branchId]], ['user_id' => $this->owner->id]] as $filters) {
            $this->getJson($this->endpoint('summary', $filters))->assertUnprocessable();
        }
    }

    public function test_comparison_uses_equal_calendar_days_and_zero_baseline_has_no_invented_percentage(): void
    {
        $this->sell();
        $this->getJson($this->endpoint('summary', ['compare' => 1]))->assertOk()->assertJsonPath('data.comparison.from', '2026-11-02')->assertJsonPath('data.comparison.to', '2026-11-02')->assertJsonPath('data.comparison.change_percent.net_sales_excluding_tax', null);
        $this->getJson($this->endpoint('sales-trend', ['compare' => 1]))->assertOk()->assertJsonCount(1, 'data.comparison_series');
    }

    public function test_recent_sales_and_branch_performance_are_server_paginated(): void
    {
        $this->sell();
        $latest = $this->sell();
        $this->getJson($this->endpoint('recent-sales', ['per_page' => 1]))->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.id', $latest)->assertJsonPath('data.pagination.total', 2)->assertJsonPath('data.pagination.last_page', 2);
        $this->getJson($this->endpoint('recent-sales', ['per_page' => 1, 'page' => 2]))->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.pagination.current_page', 2);
        $this->getJson($this->endpoint('branch-performance', ['per_page' => 1]))->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.pagination.per_page', 1);
    }

    public function test_inventory_threshold_unit_groups_and_permission_projected_alerts_are_real(): void
    {
        $this->patchJson('/api/v1/workspace/settings', ['receipt_footer' => 'Thank you', 'low_stock_threshold' => '12'])->assertOk();
        $this->getJson($this->endpoint('inventory-summary'))->assertOk()->assertJsonPath('data.recorded_skus', 1)->assertJsonPath('data.low_stock_locations', 1)
            ->assertJsonPath('data.quantities_by_unit.0.on_hand', '10.0000');
        $this->getJson($this->endpoint('alerts'))->assertOk()->assertJsonPath('data.items.0.type', 'low_stock')->assertJsonPath('data.items.0.available', '10.0000');
        [$user] = $this->member(['dashboard.view', 'products.view']);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('inventory-summary'))->assertForbidden();
        $this->getJson($this->endpoint('alerts'))->assertForbidden();
        $this->getJson($this->endpoint('summary'))->assertOk()->assertJsonMissingPath('data.inventory');
    }

    public function test_scoped_exports_never_download_historical_or_newly_unauthorized_scope_files(): void
    {
        Storage::fake('tenant_private');
        $this->sell();
        $old = $this->postJson('/api/v1/workspace/exports', ['type' => 'sales'])->assertCreated()->json('data.id');
        [$user, $member] = $this->member(['dashboard.view', 'sales.view', 'exports.manage']);
        $this->restrict($member, [$this->branchId], 'own');
        $this->selectTenant($this->legacyTenant, $user);
        $this->get('/api/v1/workspace/exports/'.$old.'/download')->assertNotFound();
        $id = $this->postJson('/api/v1/workspace/exports', ['type' => 'sales'])->assertCreated()->assertJsonPath('data.row_count', 0)->json('data.id');
        $this->get('/api/v1/workspace/exports/'.$id.'/download')->assertOk();
        $this->restrict($member, [], 'own');
        $this->selectTenant($this->legacyTenant, $user);
        $this->get('/api/v1/workspace/exports/'.$id.'/download')->assertNotFound();
    }

    public function test_scoped_staff_cannot_widen_their_access_or_read_unscoped_tenant_notifications(): void
    {
        [$user, $member] = $this->member(['dashboard.view', 'team.view', 'team.manage', 'settings.manage', 'alerts.view']);
        $this->restrict($member, []);
        $this->selectTenant($this->legacyTenant, $user);
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'all', 'sales_scope' => 'all', 'branch_ids' => [], 'additional_role_ids' => []])->assertForbidden();
        $this->getJson('/api/v1/workspace/alerts')->assertForbidden();
        $this->getJson('/api/v1/team')->assertForbidden();
    }

    public function test_queue_scope_is_revalidated_and_context_is_restored_even_after_failure(): void
    {
        [$user, $member] = $this->member();
        $this->restrict($member, []);
        $context = app(TenantContext::class);
        $context->resolve($this->legacyTenant, $this->owner->id);
        (new TenantJobMiddleware($this->legacyTenant, $user->id))->handle(new \stdClass(), function () use ($context) {
            $this->assertSame(0, (new RecordScope($context))->query('sales')->count());
            $this->assertTrue($context->branchRestricted());
        });
        $this->assertSame($this->owner->id, $context->membership()->user_id);
        $this->assertFalse($context->branchRestricted());
        try {
            (new TenantJobMiddleware($this->legacyTenant, $user->id))->handle(new \stdClass(), fn () => throw new \RuntimeException('Fixture failure'));
            $this->fail('Fixture must throw.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fixture failure', $exception->getMessage());
        }
        $this->assertSame($this->owner->id, $context->membership()->user_id);
        $this->assertFalse($context->branchRestricted());
        $context->clear();
    }

    public function test_actual_other_tenant_data_and_warm_cache_never_cross_membership_boundaries(): void
    {
        $mine = $this->sell();
        $otherUser = User::create(['name' => 'Other tenant owner', 'email' => 'other-tenant@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $other = app(\App\Domain\Tenancy\TenantService::class)->create($otherUser, ['name' => 'Other business', 'slug' => 'other-business', 'timezone' => 'Asia/Karachi', 'currency_code' => 'PKR']);
        DB::table('tenant_subscriptions')->where('tenant_id', $other)->update(['grandfathered' => true]);
        $branch = DB::table('branches')->where('tenant_id', $other)->value('id');
        $location = DB::table('locations')->where('tenant_id', $other)->value('id');
        $member = DB::table('tenant_memberships')->where('tenant_id', $other)->value('id');
        $register = DB::table('cash_registers')->insertGetId(['tenant_id' => $other, 'branch_id' => $branch, 'location_id' => $location, 'name' => 'Other counter', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $shift = DB::table('cash_sessions')->insertGetId(['tenant_id' => $other, 'register_id' => $register, 'opened_by' => $member, 'opened_at' => now(), 'opening_cash' => '0', 'status' => 'open', 'open_register_id' => $register, 'created_at' => now(), 'updated_at' => now()]);
        $record = (array) DB::table('sales')->where('id', $mine)->first();
        unset($record['id']);
        $foreign = DB::table('sales')->insertGetId(array_replace($record, ['tenant_id' => $other, 'branch_id' => $branch, 'location_id' => $location, 'register_id' => $register, 'cash_session_id' => $shift, 'cashier_membership_id' => $member, 'invoice_no' => 'PRIVATE-OTHER-INVOICE', 'subtotal' => '999', 'grand_total' => '999', 'paid_total' => '999']));
        $this->selectTenant($other, $otherUser);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.totals.invoiced_sales', '999.0000');
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('meta.cache_hit', true);
        $this->selectTenant($this->legacyTenant);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.totals.invoiced_sales', '24.6900');
        $this->getJson($this->endpoint('recent-sales/'.$foreign))->assertNotFound();
        $this->getJson($this->endpoint('summary', ['branch_id' => $branch]))->assertForbidden();
        $this->selectTenant($other);
        $this->getJson($this->endpoint())->assertForbidden();
    }

    public function test_new_assignments_validate_tenant_foreign_keys_owner_safety_and_role_delegation(): void
    {
        [$user, $member] = $this->member(['dashboard.view', 'team.view', 'team.manage']);
        $otherUser = User::create(['name' => 'Other owner', 'email' => 'assignment-owner@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $other = app(\App\Domain\Tenancy\TenantService::class)->create($otherUser, ['name' => 'Other', 'slug' => 'assignment-other', 'timezone' => 'UTC', 'currency_code' => 'USD']);
        $branch = DB::table('branches')->where('tenant_id', $other)->value('id');
        $role = DB::table('tenant_roles')->where('tenant_id', $other)->value('id');
        $payload = ['branch_scope' => 'assigned', 'sales_scope' => 'all', 'branch_ids' => [$branch], 'additional_role_ids' => []];
        $this->patchJson('/api/v1/team/'.$member.'/access', $payload)->assertUnprocessable();
        $payload['branch_ids'] = [];
        $payload['additional_role_ids'] = [$role];
        $this->patchJson('/api/v1/team/'.$member.'/access', $payload)->assertUnprocessable();
        try {
            DB::table('tenant_member_branches')->insert(['tenant_id' => $this->legacyTenant, 'membership_id' => $member, 'branch_id' => $branch]);
            $this->fail('Composite tenant FK must reject an assignment to another business.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('FOREIGN KEY', $exception->getMessage());
        }
        $ownerMember = DB::table('tenant_memberships')->where('tenant_id', $this->legacyTenant)->where('user_id', $this->owner->id)->value('id');
        $this->patchJson('/api/v1/team/'.$ownerMember.'/access', ['branch_scope' => 'assigned', 'sales_scope' => 'own', 'branch_ids' => [], 'additional_role_ids' => []])->assertForbidden();
        $adminRole = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'admin')->value('id');
        $this->selectTenant($this->legacyTenant, $user);
        $this->patchJson('/api/v1/team/'.$member, ['role_id' => $adminRole, 'status' => 'active'])->assertForbidden();
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'all', 'sales_scope' => 'all', 'branch_ids' => [], 'additional_role_ids' => [$adminRole]])->assertForbidden();
    }

    public function test_dashboard_query_count_is_bounded_when_sale_volume_grows(): void
    {
        config(['dashboard.cache_seconds' => 0]);
        $sale = $this->sell();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson($this->endpoint())->assertOk();
        $first = count(DB::getQueryLog());
        $record = (array) DB::table('sales')->where('id', $sale)->first();
        unset($record['id']);
        for ($i = 0; $i < 100; $i++) {
            DB::table('sales')->insert(array_replace($record, ['invoice_no' => 'VOLUME-'.$i]));
        }
        DB::flushQueryLog();
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 101);
        $second = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThan(80, $second);
        $this->assertLessThanOrEqual($first + 2, $second);
    }

    public function test_credit_balances_and_external_cashflow_remain_separate_from_recognized_sales(): void
    {
        $customer = $this->postJson('/api/v1/operations/customers', ['name' => 'Credit customer'])->assertCreated()->json('data.id');
        $sale = $this->sell('2', $customer, '5.00');
        $this->returnSale($sale);
        $this->getJson($this->endpoint('accounting-summary'))->assertOk()->assertJsonPath('data.external_collections', '5.0000')
            ->assertJsonPath('data.current_balances.receivables', '7.3400')->assertJsonPath('data.current_balances.refunds_due', '0.0000')
            ->assertJsonPath('data.net_sales_excluding_tax', '12.3400')->assertJsonPath('data.net_profit', null);
    }

    public function test_unposted_returns_have_no_financial_effect_and_tax_discount_values_reconcile(): void
    {
        $tax = $this->postJson('/api/v1/catalog/tax-classes', ['name' => 'Dashboard tax', 'code' => 'DASH-TAX'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/catalog/tax-rates', ['tax_class_id' => $tax, 'jurisdiction_code' => 'DASH', 'rate_percent' => '10', 'valid_from' => '2020-01-01'])->assertCreated();
        DB::table('branches')->where('id', $this->branchId)->update(['tax_jurisdiction' => 'DASH']);
        DB::table('catalog_products')->update(['tax_class_id' => $tax]);
        $quote = $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->shiftId, 'discount_amount' => '2.00', 'items' => [['variant_id' => $this->variantId, 'qty' => '3']]])->assertCreated()->json('data');
        $sale = $this->postJson('/api/v1/pos/checkout', ['idempotency_key' => 'tax-dashboard-sale', 'quote_id' => $quote['id'], 'payments' => [['method_id' => $this->cashId, 'amount' => $quote['quote']['grand_total']]]])->assertCreated()->json('data.id');
        $ret = $this->postJson('/api/v1/sales/'.$sale.'/returns', ['idempotency_key' => 'tax-dashboard-return', 'reason' => 'Draft then posted', 'items' => [['sale_item_id' => DB::table('sale_items')->where('sale_id', $sale)->value('id'), 'qty' => '1', 'disposition' => 'damaged']]])->assertCreated()->json('data.id');
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.posted_return_count', 0)->assertJsonPath('data.sales.totals.return_credits', '0.0000');
        $this->postJson('/api/v1/returns/'.$ret.'/approve')->assertOk();
        $this->postJson('/api/v1/returns/'.$ret.'/post')->assertOk();
        $report = $this->getJson('/api/v1/reports/summary?from=2026-11-03&to=2026-11-03')->assertOk()->json('data.totals');
        $data = $this->getJson($this->endpoint())->assertOk()->json('data.sales.totals');
        foreach ($data as $key => $amount) {
            $this->assertSame(0, bccomp($report[$key], $amount, 4), $key);
        }
        $this->getJson($this->endpoint('top-products'))->assertOk()->assertJsonPath('data.items.0.net_sales_excluding_tax', $data['net_sales_excluding_tax']);
    }

    public function test_purchasing_payables_approvals_and_mutations_share_the_branch_boundary(): void
    {
        [$branch, $location] = $this->otherBranch();
        $supplier = $this->postJson('/api/v1/operations/suppliers', ['name' => 'Fixture supplier', 'payment_terms_days' => 14])->assertCreated()->json('data.id');
        $order = function (int $loc, string $key) use ($supplier) {
            return $this->postJson('/api/v1/purchasing/orders', ['idempotency_key' => $key, 'supplier_id' => $supplier, 'location_id' => $loc, 'items' => [['variant_id' => $this->variantId, 'ordered_qty' => '2', 'unit_cost' => '5.00']]])->assertCreated()->json('data.id');
        };
        $mine = $order($this->locationId, 'own-purchase-fixture');
        $foreign = $order($location, 'other-purchase-fixture');
        $this->postJson('/api/v1/purchasing/orders/'.$foreign.'/approve')->assertOk();
        $line = DB::table('purchase_order_items')->where('purchase_order_id', $foreign)->value('id');
        $receipt = $this->postJson('/api/v1/purchasing/orders/'.$foreign.'/receipts', ['idempotency_key' => 'other-receipt-fixture', 'items' => [['purchase_order_item_id' => $line, 'accepted_qty' => '2']]])->assertCreated()->json('data.id');
        $payable = DB::table('supplier_payables')->where('goods_receipt_id', $receipt)->value('id');
        [$user, $member] = $this->member(['dashboard.view', 'purchases.view', 'purchases.manage', 'purchases.approve', 'purchases.settle']);
        $this->restrict($member, [$this->branchId]);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('purchasing-summary'))->assertOk()->assertJsonPath('data.draft_orders', 1)->assertJsonPath('data.outstanding_payables', '0.0000')->assertJsonPath('data.overdue_receipts', null);
        $this->getJson($this->endpoint('approvals'))->assertOk()->assertJsonPath('data.counts.purchase-order', 1)->assertJsonPath('data.items.0.id', $mine);
        $this->getJson('/api/v1/purchasing/orders')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/purchasing/receipts/'.$receipt)->assertNotFound();
        $this->getJson('/api/v1/purchasing/payables')->assertOk()->assertJsonPath('total', 0);
        $this->postJson('/api/v1/purchasing/orders/'.$foreign.'/approve')->assertNotFound();
        $this->postJson('/api/v1/purchasing/payables/'.$payable.'/settlements', ['idempotency_key' => 'forged-settlement-fixture', 'amount' => '1.00', 'reference' => 'Forbidden'])->assertNotFound();
    }

    public function test_idempotent_retries_are_bound_to_actor_and_current_access_policy(): void
    {
        [$user, $member] = $this->member(['dashboard.view', 'inventory.view', 'inventory.adjust']);
        $this->restrict($member, [$this->branchId]);
        $this->selectTenant($this->legacyTenant, $user);
        $payload = ['idempotency_key' => 'fixture-stock', 'location_id' => $this->locationId, 'reason' => 'Test stock', 'items' => [['variant_id' => $this->variantId, 'quantity_delta' => '10']]];
        $this->postJson('/api/v1/inventory/adjustments', $payload)->assertConflict()->assertJsonMissingPath('data.id');
        $payload['idempotency_key'] = 'scoped-document-fixture';
        $id = $this->postJson('/api/v1/inventory/adjustments', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/adjustments', $payload)->assertCreated()->assertJsonPath('data.id', $id);
        $this->restrict($member, [$this->branchId], 'own');
        $this->selectTenant($this->legacyTenant, $user);
        $this->postJson('/api/v1/inventory/adjustments', $payload)->assertConflict();
    }

    public function test_platform_only_identity_can_sign_in_without_getting_a_business_membership(): void
    {
        $user = User::create(['name' => 'Platform identity', 'email' => 'platform-login@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $this->artisan('platform:permission', ['action' => 'grant', 'user' => $user->id, '--operator' => 'Test operator'])->assertSuccessful();
        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'fixture-password-long'])->assertOk()->assertJsonPath('data.active_tenant', null)->assertJsonPath('data.permissions', []);
        $this->getJson('/api/v1/platform/context')->assertOk();
        $this->getJson('/api/v1/tenants')->assertOk()->assertJsonPath('data', []);
        $this->postJson('/api/v1/tenants/'.$this->legacyTenant.'/activate')->assertNotFound();
    }

    public function test_mixed_currency_amounts_fail_closed_instead_of_being_added_together(): void
    {
        $sale = $this->sell();
        DB::table('sales')->where('id', $sale)->update(['currency_code' => 'USD']);
        $this->getJson($this->endpoint())->assertConflict();
        $this->getJson($this->endpoint('sales-trend'))->assertConflict();
        $this->getJson($this->endpoint('top-products'))->assertConflict();
    }

    public function test_report_capability_does_not_grant_receipt_or_catalog_detail_permission(): void
    {
        [$user] = $this->member(['dashboard.view', 'reports.view']);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint())->assertOk()->assertJsonPath('data.sales.invoice_count', 0);
        $this->getJson($this->endpoint('recent-sales'))->assertForbidden();
        $this->getJson($this->endpoint('top-products'))->assertForbidden();
    }

    public function test_shift_totals_follow_verified_session_scope_and_distinguish_personal_sales(): void
    {
        $sale = $this->sell();
        [$user, $member] = $this->member();
        // A manager may legitimately ring a sale in another staff member's shift.
        DB::table('cash_sessions')->where('id', $this->shiftId)->update(['opened_by' => $member]);
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('shift-summary'))->assertOk()->assertJsonPath('data.scope', 'own')->assertJsonPath('data.open_sessions', 1)
            ->assertJsonPath('data.invoice_count_in_open_sessions', 1)->assertJsonPath('data.net_invoiced_in_open_sessions', '24.6900');
        $this->getJson($this->endpoint('summary'))->assertOk()->assertJsonPath('data.sales.invoice_count', 0);
        $this->restrict($member, [$this->branchId], 'own');
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('shift-summary'))->assertOk()->assertJsonPath('data.invoice_scope', 'own')->assertJsonPath('data.invoice_count_in_open_sessions', 0);
    }

    public function test_legacy_blade_cannot_bypass_explicit_scopes_even_without_an_active_business_session(): void
    {
        $request = \Illuminate\Http\Request::create('/stocks');
        $response = app(\App\Http\Middleware\AdminMiddleware::class)->handle($request, fn () => response('Existing unrestricted legacy access'));
        $this->assertSame(200, $response->status());
        [$user, $member] = $this->member();
        DB::table('users')->where('id', $user->id)->update(['role_id' => 1]);
        $user->refresh();
        $this->restrict($member, []);
        $this->selectTenant($this->legacyTenant, $user);
        $this->withSession(['active_tenant_id' => null])->getJson('/stocks')->assertForbidden();
        $this->postJson('/api/v1/auth/logout')->assertNoContent();
    }
}

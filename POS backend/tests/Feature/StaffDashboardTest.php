<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use BuildAdvancedDatabase;

    public function test_staff_sales_access_does_not_grant_other_staff_shift_information(): void
    {
        $this->sell('1');
        [$user, , $role] = $this->member('manager');
        DB::table('tenant_role_permissions')->where('role_id', $role)->whereIn('permission_code', ['cash.open', 'cash.manage'])->delete();
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('staff-activity', ['branch_id' => $this->branchId]))->assertOk()->assertJsonPath('data.pagination.total', 1)->assertJsonPath('data.items.0.open_sessions', null);
        $this->getJson($this->endpoint('summary', ['branch_id' => $this->branchId]))->assertOk()->assertJsonMissingPath('data.shift');
    }

    public function test_held_carts_never_cross_tenants_and_composite_foreign_keys_reject_forged_branches(): void
    {
        $held = $this->postJson('/api/v1/pos/held-carts', $this->hold())->assertCreated()->json('data.id');
        $other = app(\App\Domain\Tenancy\TenantService::class)->create($this->owner, ['name' => 'Other shop', 'slug' => 'd4-other-shop', 'currency_code' => 'PKR', 'timezone' => 'UTC']);
        $branch = DB::table('branches')->where('tenant_id', $other)->value('id');
        $this->selectTenant($other);
        $this->getJson('/api/v1/pos/held-carts/'.$held)->assertNotFound();
        $this->getJson('/api/v1/pos/held-carts')->assertOk()->assertJsonPath('total', 0);
        $this->postJson('/api/v1/pos/held-carts/'.$held.'/discard', ['idempotency_key' => 'foreign-cart-discard', 'version' => 1])->assertConflict();
        // An explicitly verified trial fixture permits writes, so the following
        // refusal exercises record isolation rather than the subscription gate.
        DB::table('tenant_subscriptions')->where('tenant_id', $other)->update(['status' => 'trialing', 'verified_at' => now(), 'trial_ends_at' => now()->addDays(14)]);
        $this->postJson('/api/v1/pos/held-carts/'.$held.'/discard', ['idempotency_key' => 'foreign-cart-discard', 'version' => 1])->assertNotFound();
        $this->selectTenant($this->legacyTenant);
        $copy = (array) DB::table('pos_held_carts')->where('id', $held)->first();
        unset($copy['id']);
        $copy['branch_id'] = $branch;
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('pos_held_carts')->insert($copy);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-03 10:00:00', 'UTC'));
        $this->buildAdvanced();
        foreach (['2026_11_03_160000_create_dashboard_access_foundation.php', '2026_11_03_170000_create_pos_held_carts.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function endpoint(string $resource, array $filters = []): string
    {
        return '/api/v1/dashboard/'.$resource.'?'.http_build_query($filters + ['from' => '2026-11-03', 'to' => '2026-11-03']);
    }

    private function member(string $code = 'cashier'): array
    {
        $user = User::create(['name' => ucfirst($code).' Fixture', 'email' => $code.'-'.bin2hex(random_bytes(4)).'@example.test', 'password' => 'fixture-password-long', 'role_id' => null]);
        $role = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', $code)->value('id');
        $id = DB::table('tenant_memberships')->insertGetId(['tenant_id' => $this->legacyTenant, 'user_id' => $user->id, 'role_id' => $role, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        return [$user, $id, $role];
    }

    private function branch(): array
    {
        $this->selectTenant($this->legacyTenant);
        $branch = $this->postJson('/api/v1/catalog/branches', ['code' => 'D4-SECOND', 'name' => 'Second branch', 'status' => 'active'])->assertCreated()->json('data.id');
        $location = $this->postJson('/api/v1/catalog/locations', ['code' => 'D4-SECOND', 'name' => 'Second stock', 'branch_id' => $branch, 'type' => 'store', 'status' => 'active'])->assertCreated()->json('data.id');
        $preview = $this->getJson('/api/v1/pos/branches/'.$branch.'/preview')->assertOk()->json('data');
        $this->postJson('/api/v1/pos/branches/'.$branch.'/activate', ['idempotency_key' => 'd4-second-activate', 'preview_hash' => $preview['preview_hash'], 'confirm_locations' => true, 'allow_credit' => true])->assertOk();
        $register = $this->postJson('/api/v1/operations/registers', ['name' => 'Second register', 'branch_id' => $branch, 'location_id' => $location])->assertCreated()->json('data.id');
        $shift = $this->postJson('/api/v1/cash/sessions', ['idempotency_key' => 'd4-second-shift', 'register_id' => $register, 'opening_cash' => '0.00'])->assertCreated()->json('data.id');
        $adjustment = $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'd4-second-stock', 'location_id' => $location, 'reason' => 'Fixture stock', 'items' => [['variant_id' => $this->variantId, 'quantity_delta' => '10']]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/adjustments/'.$adjustment.'/post')->assertOk();

        return [$branch, $location, $shift];
    }

    private function ownShift(User $user): int
    {
        $this->selectTenant($this->legacyTenant);
        $register = $this->postJson('/api/v1/operations/registers', ['name' => 'Personal counter', 'branch_id' => $this->branchId, 'location_id' => $this->locationId])->assertCreated()->json('data.id');
        $this->selectTenant($this->legacyTenant, $user);

        return $this->postJson('/api/v1/cash/sessions', ['idempotency_key' => 'd4-personal-shift', 'register_id' => $register, 'opening_cash' => '0.00'])->assertCreated()->json('data.id');
    }

    private function hold(array $changes = []): array
    {
        return $changes + ['idempotency_key' => 'd4-hold-key-'.bin2hex(random_bytes(5)), 'cash_session_id' => $this->shiftId, 'label' => 'Fitting room', 'discount_amount' => '0', 'items' => [['variant_id' => $this->variantId, 'qty' => '1']]];
    }

    public function test_profiles_are_explicit_and_existing_access_grants_are_preserved(): void
    {
        $before = DB::table('tenant_role_permissions')->orderBy('role_id')->orderBy('permission_code')->get()->toJson();
        foreach (['manager', 'cashier'] as $code) {
            [$user] = $this->member($code);
            $this->selectTenant($this->legacyTenant, $user);
            $this->getJson('/api/v1/me/context')->assertOk()->assertJsonPath('data.active_tenant.dashboard.profile', $code)->assertJsonPath('data.active_tenant.branch_scope', 'all');
        }
        $this->assertSame($before, DB::table('tenant_role_permissions')->orderBy('role_id')->orderBy('permission_code')->get()->toJson());
    }

    public function test_cashier_summary_is_always_personal_even_with_reports_and_cash_management(): void
    {
        [$user, $member, $role] = $this->member();
        foreach (['reports.view', 'cash.manage'] as $permission) {
            DB::table('tenant_role_permissions')->insert(['tenant_id' => $this->legacyTenant, 'role_id' => $role, 'permission_code' => $permission]);
        }
        $this->shiftId = $this->ownShift($user);
        $own = $this->sell('2');
        $this->selectTenant($this->legacyTenant);
        $other = $this->sell('1');
        $this->returnSale($own);
        $this->selectTenant($this->legacyTenant, $user);
        $response = $this->getJson($this->endpoint('cashier-summary'))->assertOk()->assertJsonPath('meta.sales_scope', 'own')->assertJsonPath('data.scope', 'own')
            ->assertJsonPath('data.open_sessions', 1)->assertJsonPath('data.shift_totals.invoice_count', 1)->assertJsonPath('data.shift_totals.net_invoiced_including_tax', '12.3400')
            ->assertJsonPath('data.receipts.pagination.total', 1)->assertJsonPath('data.receipts.items.0.id', $own)->json('data');
        $this->assertArrayNotHasKey('accounting', $response);
        $this->assertArrayNotHasKey('customer_snapshot_json', $response['receipts']['items'][0]);
        $this->getJson('/api/v1/sales/'.$other.'?own=1')->assertNotFound();
        $this->getJson('/api/v1/sales?own=1&cash_session_id='.$this->shiftId)->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->endpoint('cashier-summary', ['cash_session_id' => 1]))->assertNotFound();
    }

    public function test_cashier_without_a_shift_has_unavailable_shift_totals_and_can_read_own_recent_sales_only(): void
    {
        $this->sell();
        [$user] = $this->member();
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('cashier-summary'))->assertOk()->assertJsonPath('data.selected_session', null)->assertJsonPath('data.shift_totals', null)
            ->assertJsonPath('data.open_sessions', 0)->assertJsonPath('data.receipts.pagination.total', 0)->assertJsonPath('data.held_carts.count', 0);
        $this->getJson($this->endpoint('staff-activity', ['branch_id' => $this->branchId]))->assertForbidden();
    }

    public function test_manager_branch_activity_reconciles_and_source_filters_do_not_widen_scope(): void
    {
        $first = $this->sell('2');
        [$second, , $secondShift] = $this->branch();
        $firstShift = $this->shiftId;
        $this->shiftId = $secondShift;
        $this->sell('1');
        $this->shiftId = $firstShift;
        $this->returnSale($first);
        [$user, $member] = $this->member('manager');
        $this->selectTenant($this->legacyTenant);
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'assigned', 'branch_ids' => [$this->branchId], 'sales_scope' => 'all', 'additional_role_ids' => []])->assertOk();
        $this->selectTenant($this->legacyTenant, $user);
        $activity = $this->getJson($this->endpoint('staff-activity', ['branch_id' => $this->branchId, 'per_page' => 1]))->assertOk()->assertJsonPath('data.pagination.total', 1)->json('data.items.0');
        $this->assertSame('12.3400', $activity['net_sales_excluding_tax']);
        $this->getJson('/api/v1/sales?from=2026-11-03&to=2026-11-03&branch_id='.$this->branchId.'&cashier_membership_id='.$activity['membership_id'])->assertOk()->assertJsonPath('total', 1);
        $this->getJson($this->endpoint('staff-activity'))->assertUnprocessable();
        $this->getJson($this->endpoint('staff-activity', ['branch_id' => $second]))->assertForbidden();
        $this->getJson('/api/v1/cash/sessions?branch_id='.$second)->assertUnprocessable();
        $this->getJson('/api/v1/sales?branch_id='.$second)->assertUnprocessable();
        $this->getJson('/api/v1/operations/registers?branch_id='.$second)->assertUnprocessable();
    }

    public function test_personal_sales_policy_disables_staff_activity_even_for_manager_role(): void
    {
        [$user, $member] = $this->member('manager');
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'assigned', 'branch_ids' => [$this->branchId], 'sales_scope' => 'own', 'additional_role_ids' => []])->assertOk();
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson($this->endpoint('staff-activity', ['branch_id' => $this->branchId]))->assertForbidden();
        $this->getJson('/api/v1/sales?cashier_membership_id=1')->assertForbidden();
        $this->getJson($this->endpoint('summary', ['branch_id' => $this->branchId]))->assertOk()->assertJsonPath('data.sales.invoice_count', 0);
    }

    public function test_hold_is_private_idempotent_and_has_no_inventory_sale_or_payment_effect(): void
    {
        $before = [];
        foreach (['inventory_balances', 'stock_movements', 'stock_reservations', 'sales', 'sale_payments', 'cash_movements'] as $table) {
            $before[$table] = DB::table($table)->get()->toJson();
        }
        $body = $this->hold();
        $id = $this->postJson('/api/v1/pos/held-carts', $body)->assertCreated()->assertJsonPath('data.version', 1)->json('data.id');
        $this->postJson('/api/v1/pos/held-carts', $body)->assertCreated()->assertJsonPath('data.id', $id);
        $this->assertSame(1, DB::table('pos_held_carts')->count());
        $data = $this->getJson('/api/v1/pos/held-carts/'.$id)->assertOk()->assertJsonPath('data.cart.items.0.qty', '1.0000')->json('data');
        $this->assertArrayNotHasKey('default_cost', $data['cart']['items'][0]);
        $this->assertArrayNotHasKey('payments', $data['cart']);
        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->get()->toJson(), $table);
        }
        [$user] = $this->member();
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson('/api/v1/pos/held-carts/'.$id)->assertNotFound();
        $this->getJson('/api/v1/pos/held-carts')->assertOk()->assertJsonPath('total', 0);
        $this->postJson('/api/v1/pos/held-carts/'.$id.'/discard', ['idempotency_key' => 'cross-actor-discard', 'version' => 1])->assertNotFound();
    }

    public function test_held_checkout_requires_fresh_prices_and_consumes_once_across_two_tabs(): void
    {
        $held = $this->postJson('/api/v1/pos/held-carts', $this->hold())->assertCreated()->json('data');
        DB::table('catalog_variants')->where('id', $this->variantId)->update(['default_price' => '15.5000']);
        $input = ['cash_session_id' => $this->shiftId, 'held_cart_id' => $held['id'], 'held_cart_version' => 1, 'items' => [['variant_id' => $this->variantId, 'qty' => '1']]];
        $quote = $this->postJson('/api/v1/pos/quotes', $input)->assertCreated()->assertJsonPath('data.quote.grand_total', '15.50')->json('data');
        $otherTab = $this->postJson('/api/v1/pos/quotes', $input)->assertCreated()->json('data');
        $body = ['idempotency_key' => 'held-sale-once', 'quote_id' => $quote['id'], 'payments' => [['method_id' => $this->cashId, 'amount' => '15.50']]];
        $sale = $this->postJson('/api/v1/pos/checkout', $body)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/pos/checkout', $body)->assertCreated()->assertJsonPath('data.id', $sale);
        $body['quote_id'] = $otherTab['id'];
        $body['idempotency_key'] = 'held-sale-other-tab';
        $this->postJson('/api/v1/pos/checkout', $body)->assertConflict();
        $this->assertSame(1, DB::table('sales')->count());
        $this->assertSame('sold', DB::table('pos_held_carts')->value('status'));
        $this->assertSame($sale, DB::table('pos_held_carts')->value('consumed_sale_id'));
        $this->getJson('/api/v1/pos/held-carts/'.$held['id'])->assertConflict();
    }

    public function test_held_cart_update_discard_and_stale_versions_are_safe(): void
    {
        $held = $this->postJson('/api/v1/pos/held-carts', $this->hold())->assertCreated()->json('data');
        $update = $this->hold(['held_cart_id' => $held['id'], 'held_cart_version' => 1, 'label' => 'Updated label']);
        $this->postJson('/api/v1/pos/held-carts', $update)->assertCreated()->assertJsonPath('data.version', 2);
        $update['idempotency_key'] = 'stale-held-version';
        $this->postJson('/api/v1/pos/held-carts', $update)->assertConflict();
        $this->postJson('/api/v1/pos/held-carts/'.$held['id'].'/discard', ['idempotency_key' => 'discard-stale-version', 'version' => 1])->assertConflict();
        $body = ['idempotency_key' => 'discard-current-version', 'version' => 2];
        $this->postJson('/api/v1/pos/held-carts/'.$held['id'].'/discard', $body)->assertOk()->assertJsonPath('data.status', 'discarded');
        $this->postJson('/api/v1/pos/held-carts/'.$held['id'].'/discard', $body)->assertOk();
        $this->getJson('/api/v1/pos/held-carts')->assertOk()->assertJsonPath('total', 0);
        $this->assertSame(1, DB::table('pos_held_carts')->count());
    }

    public function test_held_carts_survive_closed_shifts_but_need_an_open_shift_in_the_original_branch(): void
    {
        $held = $this->postJson('/api/v1/pos/held-carts', $this->hold())->assertCreated()->json('data');
        $register = DB::table('cash_sessions')->where('id', $this->shiftId)->value('register_id');
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/close', ['idempotency_key' => 'close-before-recall', 'counted_cash' => '100.00'])->assertOk();
        $this->getJson('/api/v1/pos/held-carts/'.$held['id'])->assertOk();
        $new = $this->postJson('/api/v1/cash/sessions', ['idempotency_key' => 'new-shift-for-recall', 'register_id' => $register, 'opening_cash' => '0.00'])->assertCreated()->json('data.id');
        $body = ['cash_session_id' => $new, 'held_cart_id' => $held['id'], 'held_cart_version' => 1, 'items' => [['variant_id' => $this->variantId, 'qty' => '1']]];
        $this->postJson('/api/v1/pos/quotes', $body)->assertCreated();
        [, , $secondShift] = $this->branch();
        $body['cash_session_id'] = $secondShift;
        $this->postJson('/api/v1/pos/quotes', $body)->assertConflict();
    }

    public function test_validation_and_revoked_scope_prevent_forged_holds(): void
    {
        $this->postJson('/api/v1/pos/held-carts', $this->hold(['tenant_id' => $this->legacyTenant]))->assertUnprocessable();
        $this->postJson('/api/v1/pos/held-carts', $this->hold(['payments' => [['amount' => '1']]]))->assertUnprocessable();
        $this->postJson('/api/v1/pos/held-carts', $this->hold(['items' => [['variant_id' => $this->variantId, 'qty' => '1.5']]]))->assertUnprocessable();
        $this->postJson('/api/v1/pos/held-carts', $this->hold(['items' => []]))->assertUnprocessable();
        [$user, $member] = $this->member();
        $this->shiftId = $this->ownShift($user);
        $held = $this->postJson('/api/v1/pos/held-carts', $this->hold())->assertCreated()->json('data');
        $this->selectTenant($this->legacyTenant);
        $this->patchJson('/api/v1/team/'.$member.'/access', ['branch_scope' => 'assigned', 'branch_ids' => [], 'sales_scope' => 'all', 'additional_role_ids' => []])->assertOk();
        $this->selectTenant($this->legacyTenant, $user);
        $this->getJson('/api/v1/pos/held-carts/'.$held['id'])->assertNotFound();
        $this->getJson('/api/v1/pos/held-carts')->assertOk()->assertJsonPath('total', 0);
        $this->postJson('/api/v1/pos/held-carts', $this->hold())->assertUnprocessable();
        $this->getJson($this->endpoint('cashier-summary'))->assertOk()->assertJsonPath('data.held_carts.count', 0);
    }
}

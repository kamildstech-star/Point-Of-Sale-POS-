<?php

namespace Tests\Feature;

use App\Domain\Tenancy\TenantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildTenantDatabase;
use Tests\TestCase;

class InventoryPosTest extends TestCase
{
    use BuildTenantDatabase;

    private int $location;

    private int $branch;

    private int $variant;

    private int $sessionId;

    private int $cash;

    private int $card;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDatabase();
        (require database_path('migrations/2026_10_19_130000_create_inventory_purchasing_pos.php'))->up();
        $this->selectTenant($this->legacyTenant);
        $this->branch = DB::table('branches')->value('id');
        $this->location = DB::table('locations')->value('id');
        $preview = $this->getJson('/api/v1/pos/branches/'.$this->branch.'/preview')->assertOk()->json('data');
        $this->postJson('/api/v1/pos/branches/'.$this->branch.'/activate', ['idempotency_key' => 'activate-branch-1', 'preview_hash' => $preview['preview_hash'], 'confirm_locations' => true, 'allow_credit' => true])->assertOk();
        [$product] = $this->createCatalogProduct();
        $this->variant = DB::table('catalog_variants')->where('product_id', $product)->value('id');
        $register = $this->postJson('/api/v1/operations/registers', ['name' => 'Counter 1', 'branch_id' => $this->branch, 'location_id' => $this->location])->assertCreated()->json('data.id');
        $this->sessionId = $this->postJson('/api/v1/cash/sessions', ['idempotency_key' => 'open-session-1', 'register_id' => $register, 'opening_cash' => '100.00'])->assertCreated()->json('data.id');
        $this->cash = DB::table('payment_methods')->where('code', 'CASH')->value('id');
        $this->card = DB::table('payment_methods')->where('code', 'CARD')->value('id');
    }

    private function stock(string $qty = '10'): int
    {
        $id = $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'stock-'.bin2hex(random_bytes(5)), 'location_id' => $this->location, 'reason' => 'Verified physical opening', 'items' => [['variant_id' => $this->variant, 'quantity_delta' => $qty]]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/adjustments/'.$id.'/post')->assertOk();

        return $id;
    }

    private function quote(array $extra = []): array
    {
        return $this->postJson('/api/v1/pos/quotes', $extra + ['cash_session_id' => $this->sessionId, 'discount_amount' => '0', 'items' => [['variant_id' => $this->variant, 'qty' => '1']]])->assertCreated()->json('data');
    }

    private function checkout(array $q, array $payments, string $key = 'checkout-001')
    {
        return $this->postJson('/api/v1/pos/checkout', ['idempotency_key' => $key, 'quote_id' => $q['id'], 'payments' => $payments]);
    }

    public function test_sale_double_click_receipt_snapshots_and_reconciliation(): void
    {
        $this->stock('1');
        $q = $this->quote();
        $this->assertSame('12.35', $q['quote']['grand_total']);
        $payments = [['method_id' => $this->cash, 'amount' => '12.35']];
        $id = $this->checkout($q, $payments)->assertCreated()->json('data.id');
        $this->assertSame($id, $this->checkout($q, $payments)->assertCreated()->json('data.id'));
        $this->checkout($q, $payments, 'different-key')->assertConflict();
        $this->assertSame(1, DB::table('sales')->count());
        $this->assertSame(1, DB::table('sale_payments')->count());
        $this->assertSame('0.0000', bcadd((string) DB::table('inventory_balances')->value('on_hand'), '0', 4));
        DB::table('catalog_products')->update(['name' => 'Renamed']);
        $this->getJson('/api/v1/sales/'.$id)->assertOk()->assertJsonPath('data.items.0.name_snapshot', 'Shirt')->assertJsonPath('data.payment_status', 'paid');
        $this->getJson('/api/v1/cash/sessions/'.$this->sessionId.'/reconciliation')->assertOk()->assertJsonPath('data.expected_cash', '112.3500');
        $this->postJson('/api/v1/inventory/reconcile')->assertOk()->assertJsonPath('data.reconciled', true);
    }

    public function test_stock_failure_rolls_back_sale_invoice_and_payment(): void
    {
        $q = $this->quote();
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '12.35']])->assertConflict();
        $this->assertSame(0, DB::table('sales')->count());
        $this->assertSame(0, DB::table('sale_payments')->count());
        $this->assertSame(0, DB::table('invoice_sequences')->count());
        $this->stock('1');
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '12.35']])->assertCreated();
    }

    public function test_partial_split_and_later_payments_require_named_customer(): void
    {
        $this->stock('2');
        $q = $this->quote();
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '5']])->assertConflict();
        $customer = $this->postJson('/api/v1/operations/customers', ['name' => 'Retail customer'])->assertCreated()->json('data.id');
        $q = $this->quote(['customer_id' => $customer]);
        $id = $this->checkout($q, [['method_id' => $this->cash, 'amount' => '5'], ['method_id' => $this->card, 'amount' => '3', 'reference' => 'terminal-123']])->assertCreated()->json('data.id');
        $this->getJson('/api/v1/sales/'.$id)->assertJsonPath('data.payment_status', 'partial');
        $payment = ['idempotency_key' => 'remaining-payment', 'cash_session_id' => $this->sessionId, 'payments' => [['method_id' => $this->cash, 'amount' => '4.35']]];
        $this->postJson('/api/v1/sales/'.$id.'/payments', $payment)->assertCreated();
        $this->postJson('/api/v1/sales/'.$id.'/payments', $payment)->assertCreated();
        $this->assertSame(3, DB::table('sale_payments')->count());
        $this->getJson('/api/v1/sales/'.$id)->assertJsonPath('data.payment_status', 'paid');
        $payment['idempotency_key'] = 'overpay-rejected';
        $this->postJson('/api/v1/sales/'.$id.'/payments', $payment)->assertUnprocessable();
    }

    public function test_server_prices_tax_discount_and_quote_expiry(): void
    {
        $this->stock();
        $tax = $this->postJson('/api/v1/catalog/tax-classes', ['name' => 'Standard', 'code' => 'STD'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/catalog/tax-rates', ['tax_class_id' => $tax, 'jurisdiction_code' => 'TEST', 'rate_percent' => '10', 'valid_from' => '2020-01-01'])->assertCreated();
        DB::table('branches')->update(['tax_jurisdiction' => 'TEST']);
        DB::table('catalog_products')->update(['tax_class_id' => $tax]);
        $q = $this->quote(['discount_amount' => '2.00', 'unit_price' => '0.01', 'grand_total' => '0.01']);
        $this->assertSame('11.39', $q['quote']['grand_total']);
        $this->assertSame('1.04', $q['quote']['tax_total']);
        DB::table('catalog_variants')->update(['default_price' => '20']);
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '11.39']])->assertConflict();
        $q = $this->quote();
        DB::table('sale_quotes')->where('id', $q['id'])->update(['expires_at' => now()->subMinute()]);
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '22']])->assertConflict();
        $this->assertSame(0, DB::table('sales')->count());
    }

    public function test_adjustment_approval_stale_count_precision_and_reservations(): void
    {
        $id = $this->stock('5');
        $this->postJson('/api/v1/inventory/adjustments/'.$id.'/post')->assertOk();
        $this->assertSame(1, DB::table('stock_movements')->count());
        $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'bad-fractional', 'location_id' => $this->location, 'reason' => 'Bad', 'items' => [['variant_id' => $this->variant, 'quantity_delta' => '0.5']]])->assertUnprocessable();
        $count = $this->postJson('/api/v1/inventory/counts', ['idempotency_key' => 'count-001', 'location_id' => $this->location, 'reason' => 'Count', 'items' => [['variant_id' => $this->variant, 'counted_qty' => '4']]])->assertCreated()->json('data.id');
        $reservation = ['idempotency_key' => 'reserve-001', 'location_id' => $this->location, 'variant_id' => $this->variant, 'quantity' => '5', 'reference' => 'Customer collection', 'minutes' => 1];
        $r = $this->postJson('/api/v1/inventory/reservations', $reservation)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/counts/'.$count.'/post')->assertConflict();
        $this->checkout($this->quote(), [['method_id' => $this->cash, 'amount' => '12.35']])->assertConflict();
        $this->travel(2)->minutes();
        $this->checkout($this->quote(), [['method_id' => $this->cash, 'amount' => '12.35']])->assertCreated();
        $this->assertSame('expired', DB::table('stock_reservations')->where('id', $r)->value('status'));
        $this->postJson('/api/v1/inventory/reconcile')->assertJsonPath('data.reconciled', true);
    }

    public function test_partial_receipts_returns_settlement_and_retry(): void
    {
        $supplier = $this->postJson('/api/v1/operations/suppliers', ['name' => 'Supplier', 'payment_terms_days' => 30])->assertCreated()->json('data.id');
        $po = $this->postJson('/api/v1/purchasing/orders', ['idempotency_key' => 'purchase-001', 'supplier_id' => $supplier, 'location_id' => $this->location, 'items' => [['variant_id' => $this->variant, 'ordered_qty' => '5', 'unit_cost' => '10', 'tax_rate' => '10']]])->assertCreated()->json('data.id');
        $line = DB::table('purchase_order_items')->where('purchase_order_id', $po)->value('id');
        $receipt = ['idempotency_key' => 'receipt-001', 'items' => [['purchase_order_item_id' => $line, 'accepted_qty' => '3', 'rejected_qty' => '1']]];
        $this->postJson('/api/v1/purchasing/orders/'.$po.'/receipts', $receipt)->assertConflict();
        $this->postJson('/api/v1/purchasing/orders/'.$po.'/approve')->assertOk();
        $gr = $this->postJson('/api/v1/purchasing/orders/'.$po.'/receipts', $receipt)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/purchasing/orders/'.$po.'/receipts', $receipt)->assertCreated()->assertJsonPath('data.id', $gr);
        $this->assertSame('partial', DB::table('purchase_orders')->where('id', $po)->value('status'));
        $this->assertEquals('33', DB::table('supplier_payables')->value('outstanding_amount'));
        $receipt['idempotency_key'] = 'receipt-002';
        $receipt['items'][0]['accepted_qty'] = '3';
        $receipt['items'][0]['rejected_qty'] = '0';
        $this->postJson('/api/v1/purchasing/orders/'.$po.'/receipts', $receipt)->assertConflict();
        $gri = DB::table('goods_receipt_items')->value('id');
        $return = ['idempotency_key' => 'purchase-return-001', 'reason' => 'Damaged', 'items' => [['goods_receipt_item_id' => $gri, 'quantity' => '1']]];
        $this->postJson('/api/v1/purchasing/receipts/'.$gr.'/returns', $return)->assertCreated();
        $this->postJson('/api/v1/purchasing/receipts/'.$gr.'/returns', $return)->assertCreated();
        $return['idempotency_key'] = 'purchase-return-002';
        $return['items'][0]['quantity'] = '3';
        $this->postJson('/api/v1/purchasing/receipts/'.$gr.'/returns', $return)->assertConflict();
        $payable = DB::table('supplier_payables')->value('id');
        $this->postJson('/api/v1/purchasing/payables/'.$payable.'/settlements', ['idempotency_key' => 'settlement-001', 'amount' => '22.00', 'reference' => 'Bank transfer 123'])->assertCreated();
        $this->assertSame('settled', DB::table('supplier_payables')->value('status'));
        $this->assertEquals('2', DB::table('inventory_balances')->value('on_hand'));
        $this->postJson('/api/v1/inventory/reconcile')->assertJsonPath('data.reconciled', true);
        $this->postJson('/api/v1/purchasing/reconcile')->assertOk()->assertJsonPath('data.reconciled', true);
    }

    public function test_tenant_isolation_and_custom_role_denies_operations(): void
    {
        $this->stock();
        $sale = $this->checkout($this->quote(), [['method_id' => $this->cash, 'amount' => '12.35']])->assertCreated()->json('data.id');
        $other = app(TenantService::class)->create($this->owner, ['name' => 'Other', 'slug' => 'other-shop', 'timezone' => 'UTC', 'currency_code' => 'PKR']);
        $this->selectTenant($other);
        $this->getJson('/api/v1/sales/'.$sale)->assertNotFound();
        $this->getJson('/api/v1/inventory/balances')->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->sessionId, 'items' => [['variant_id' => $this->variant, 'qty' => '1']]])->assertUnprocessable();
        $role = $this->postJson('/api/v1/roles', ['name' => 'Reader', 'code' => 'custom-reader', 'permissions' => ['products.view']])->assertCreated()->json('data.id');
        DB::table('tenant_memberships')->where('tenant_id', $other)->update(['role_id' => $role]);
        $this->getJson('/api/v1/inventory/balances')->assertForbidden();
        $this->postJson('/api/v1/pos/branches/'.DB::table('branches')->where('tenant_id', $other)->value('id').'/activate', [])->assertForbidden();
    }

    public function test_idempotency_payload_mismatch_and_metadata_history_guards(): void
    {
        $this->stock();
        $q = $this->quote();
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '12.35']])->assertCreated();
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '1']])->assertConflict();
        $v = DB::table('catalog_variants')->where('id', $this->variant)->first();
        $this->patchJson('/api/v1/products/'.$v->product_id.'/variants/'.$v->id, ['tracking_mode' => 'serial'])->assertConflict();
        $this->patchJson('/api/v1/products/'.$v->product_id, ['track_inventory' => false])->assertConflict();
        $this->patchJson('/api/v1/catalog/units/'.$v->base_unit_id, ['quantity_precision' => 4])->assertConflict();
    }

    public function test_legacy_customer_split_retains_staff_credentials_and_order_references(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('phone')->nullable();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id');
            $t->unsignedBigInteger('user_id')->nullable();
        });
        $customerUser = DB::table('users')->insertGetId(['name' => 'Legacy shopper', 'email' => 'shopper@test.example', 'role_id' => null]);
        $oversizedPhoneUser = DB::table('users')->insertGetId(['name' => 'Needs contact review', 'phone' => str_repeat('1', 41), 'role_id' => null]);
        DB::table('orders')->insert([['tenant_id' => $this->legacyTenant, 'user_id' => $customerUser], ['tenant_id' => $this->legacyTenant, 'user_id' => $this->owner->id], ['tenant_id' => $this->legacyTenant, 'user_id' => 99999], ['tenant_id' => $this->legacyTenant, 'user_id' => $oversizedPhoneUser]]);
        $before = DB::table('users')->orderBy('id')->get()->toJson();
        $this->postJson('/api/v1/legacy/map-customers')->assertOk()->assertJsonPath('data.mapped', 1)->assertJsonPath('data.needs_review', 3);
        $this->postJson('/api/v1/legacy/map-customers')->assertOk()->assertJsonPath('data.existing', 4);
        $this->assertSame($before, DB::table('users')->orderBy('id')->get()->toJson());
        $this->assertSame(1, DB::table('customers')->count());
        $this->assertEquals($customerUser, DB::table('orders')->orderBy('id')->value('user_id'));
    }

    public function test_weighted_goods_and_services_use_unit_precision_and_only_physical_goods_deduct_stock(): void
    {
        $unit = $this->postJson('/api/v1/catalog/units', ['code' => 'KG', 'name' => 'Kilogram', 'quantity_precision' => 3])->assertCreated()->json('data.id');
        $p = $this->postJson('/api/v1/products', ['name' => 'Bulk goods', 'product_type' => 'simple', 'track_inventory' => true, 'variant' => ['sku' => 'BULK', 'base_unit_id' => $unit, 'default_cost' => '1.0000', 'default_price' => '10.0000', 'tracking_mode' => 'none']])->assertCreated()->json('data.id');
        $v = DB::table('catalog_variants')->where('product_id', $p)->value('id');
        $adjustment = $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'weighted-opening', 'location_id' => $this->location, 'reason' => 'Physical opening', 'items' => [['variant_id' => $v, 'quantity_delta' => '1.500']]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/adjustments/'.$adjustment.'/post')->assertOk();
        $service = $this->postJson('/api/v1/products', ['name' => 'Alteration', 'product_type' => 'service', 'track_inventory' => true, 'variant' => ['sku' => 'SERVICE', 'base_unit_id' => DB::table('catalog_units')->where('code', 'EA')->value('id'), 'default_cost' => '0', 'default_price' => '5', 'tracking_mode' => 'none']])->assertCreated()->json('data.id');
        $serviceVariant = DB::table('catalog_variants')->where('product_id', $service)->value('id');
        $q = $this->quote(['items' => [['variant_id' => $v, 'qty' => '0.125'], ['variant_id' => $serviceVariant, 'qty' => '1']]]);
        $this->assertSame('6.25', $q['quote']['grand_total']);
        $this->checkout($q, [['method_id' => $this->cash, 'amount' => '6.25']])->assertCreated();
        $this->assertEquals('1.375', DB::table('inventory_balances')->where('variant_id', $v)->value('on_hand'));
        $this->assertSame(0, DB::table('inventory_balances')->where('variant_id', $serviceVariant)->count());
        $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->sessionId, 'items' => [['variant_id' => $v, 'qty' => '0.0001']]])->assertUnprocessable();
        $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->sessionId, 'items' => [['variant_id' => $v, 'qty' => 0.125]]])->assertUnprocessable();
    }

    public function test_cashier_cannot_discount_or_approve_and_disabled_branch_credit_requires_full_payment(): void
    {
        $this->stock();
        $customer = $this->postJson('/api/v1/operations/customers', ['name' => 'Named buyer'])->assertCreated()->json('data.id');
        DB::table('branches')->where('id', $this->branch)->update(['allow_credit' => false]);
        $this->checkout($this->quote(['customer_id' => $customer]), [])->assertConflict();
        DB::table('tenant_memberships')->where('tenant_id', $this->legacyTenant)->update(['role_id' => DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'cashier')->value('id')]);
        $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->sessionId, 'discount_amount' => '1.00', 'items' => [['variant_id' => $this->variant, 'qty' => '1']]])->assertForbidden();
        $this->postJson('/api/v1/inventory/adjustments/1/post')->assertForbidden();
        $this->getJson('/api/v1/pos/branches/'.$this->branch.'/preview')->assertForbidden();
    }

    public function test_count_posting_and_manual_reservation_release_are_repeatable(): void
    {
        $this->stock('5');
        $count = $this->postJson('/api/v1/inventory/counts', ['idempotency_key' => 'physical-count-1', 'location_id' => $this->location, 'reason' => 'Physical count', 'items' => [['variant_id' => $this->variant, 'counted_qty' => '3']]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/counts/'.$count.'/post')->assertOk();
        $this->postJson('/api/v1/inventory/counts/'.$count.'/post')->assertOk();
        $this->assertEquals('3', DB::table('inventory_balances')->value('on_hand'));
        $this->assertSame(2, DB::table('stock_movements')->count());
        $id = $this->postJson('/api/v1/inventory/reservations', ['idempotency_key' => 'reserve-release-1', 'location_id' => $this->location, 'variant_id' => $this->variant, 'quantity' => '2', 'reference' => 'Collection'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/reservations/'.$id.'/release')->assertOk();
        $this->postJson('/api/v1/inventory/reservations/'.$id.'/release')->assertOk();
        $this->assertEquals('0', DB::table('inventory_balances')->value('reserved'));
        $this->postJson('/api/v1/inventory/reconcile')->assertJsonPath('data.reconciled', true);
    }
}

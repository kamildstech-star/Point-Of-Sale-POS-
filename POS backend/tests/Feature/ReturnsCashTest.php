<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\TestCase;

class ReturnsCashTest extends TestCase
{
    use BuildAdvancedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildAdvanced();
    }

    public function test_partial_return_refund_retries_and_second_return_do_not_overreturn_or_restock_twice(): void
    {
        $sale = $this->sell();
        $ret = $this->returnSale($sale);
        $this->postJson('/api/v1/returns/'.$ret.'/post')->assertOk();
        $this->assertEquals('9', DB::table('inventory_balances')->value('on_hand'));
        $total = (string) DB::table('sale_returns')->where('id', $ret)->value('total');
        $payload = ['idempotency_key' => 'refund-stable-key', 'cash_session_id' => $this->shiftId, 'method_id' => $this->cashId, 'amount' => $total];
        $id = $this->postJson('/api/v1/returns/'.$ret.'/refunds', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/v1/returns/'.$ret.'/refunds', $payload)->assertCreated()->assertJsonPath('data.id', $id);
        $payload['idempotency_key'] = 'refund-overpay-key';
        $this->postJson('/api/v1/returns/'.$ret.'/refunds', $payload)->assertConflict();
        $this->postJson('/api/v1/sales/'.$sale.'/void', ['idempotency_key' => 'double-restock-void', 'reason' => 'Too late'])->assertConflict();
        $this->returnSale($sale);
        $this->assertEquals('10', DB::table('inventory_balances')->value('on_hand'));
        $this->postJson('/api/v1/sales/'.$sale.'/returns', ['idempotency_key' => 'excess-return-key', 'reason' => 'Excess', 'items' => [['sale_item_id' => DB::table('sale_items')->value('id'), 'qty' => '1', 'disposition' => 'resellable']]])->assertConflict();
        $this->getJson('/api/v1/reports/summary?from=2020-01-01&to=2020-01-02')->assertOk()->assertJsonPath('data.reconciled', true);
    }

    public function test_damaged_return_is_quarantined_and_credit_is_reduced_before_cash_refund(): void
    {
        $customer = $this->postJson('/api/v1/operations/customers', ['name' => 'Credit buyer'])->assertCreated()->json('data.id');
        $sale = $this->sell('2', $customer, '5.00');
        $ret = $this->returnSale($sale, '1', 'damaged');
        $this->assertEquals('8', DB::table('inventory_balances')->value('on_hand'));
        $this->assertEquals('1', DB::table('damaged_stock_records')->value('quantity'));
        $this->postJson('/api/v1/returns/'.$ret.'/refunds', ['idempotency_key' => 'not-collected-refund', 'cash_session_id' => $this->shiftId, 'method_id' => $this->cashId, 'amount' => '1.00'])->assertConflict();
        $saleData = $this->getJson('/api/v1/sales/'.$sale)->assertOk()->json('data');
        $this->assertTrue(bccomp($saleData['balance_due'], '20', 2) < 0);
        $second = $this->returnSale($sale);
        $this->postJson('/api/v1/returns/'.$second.'/refunds', ['idempotency_key' => 'collected-refund', 'cash_session_id' => $this->shiftId, 'method_id' => $this->cashId, 'amount' => '5.00'])->assertCreated();
        $this->assertEquals('5', DB::table('sales')->value('total_refunded'));
    }

    public function test_cancel_is_a_full_single_return_and_refund_never_exceeds_collection(): void
    {
        $sale = $this->sell();
        $data = ['idempotency_key' => 'cancel-stable-key', 'reason' => 'Customer canceled'];
        $id = $this->postJson('/api/v1/sales/'.$sale.'/void', $data)->assertOk()->json('data.id');
        $this->postJson('/api/v1/sales/'.$sale.'/void', $data)->assertOk();
        $this->assertSame('voided', DB::table('sales')->value('sale_status'));
        $this->assertEquals('10', DB::table('inventory_balances')->value('on_hand'));
        $data['idempotency_key'] = 'cancel-second-key';
        $this->postJson('/api/v1/sales/'.$sale.'/void', $data)->assertConflict();
        $this->assertSame(1, DB::table('sale_returns')->count());
    }

    public function test_cash_expenses_movements_close_and_reopen_reconcile(): void
    {
        $this->sell();
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/movements', ['idempotency_key' => 'cash-withdraw', 'movement_type' => 'out', 'amount' => '10.00', 'reason' => 'Safe drop'])->assertCreated();
        $category = $this->postJson('/api/v1/expenses/categories', ['name' => 'Supplies'])->assertCreated()->json('data.id');
        $e = $this->postJson('/api/v1/expenses', ['idempotency_key' => 'expense-001', 'category_id' => $category, 'branch_id' => $this->branchId, 'cash_session_id' => $this->shiftId, 'method_id' => $this->cashId, 'amount' => '4.00', 'description' => 'Bags', 'expense_date' => now()->toDateString()])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/expenses/'.$e.'/approve')->assertOk();
        $this->postJson('/api/v1/expenses/'.$e.'/approve')->assertOk();
        $report = $this->getJson('/api/v1/cash/sessions/'.$this->shiftId.'/reconciliation')->assertOk()->json('data');
        $this->assertSame('110.6900', $report['expected_cash']);
        $close = ['idempotency_key' => 'shift-close-001', 'counted_cash' => '110.00'];
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/close', $close)->assertOk()->assertJsonPath('data.variance', '-0.6900');
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/close', $close)->assertOk();
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/movements', ['idempotency_key' => 'closed-movement', 'movement_type' => 'in', 'amount' => '1', 'reason' => 'Blocked'])->assertConflict();
        $this->postJson('/api/v1/cash/sessions', ['idempotency_key' => 'next-shift-open', 'register_id' => DB::table('cash_registers')->value('id'), 'opening_cash' => '110.00'])->assertCreated();
    }

    public function test_linked_exchange_moves_credit_atomically_and_cannot_be_faked_as_a_payment(): void
    {
        $sale = $this->sell();
        $line = DB::table('sale_items')->where('sale_id', $sale)->value('id');
        $ret = $this->postJson('/api/v1/sales/'.$sale.'/returns', ['idempotency_key' => 'exchange-return', 'reason' => 'Change purchase', 'items' => [['sale_item_id' => $line, 'qty' => '1', 'disposition' => 'resellable']]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/returns/'.$ret.'/approve')->assertOk();
        $q = $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->shiftId, 'items' => [['variant_id' => $this->variantId, 'qty' => '1']]])->assertCreated()->json('data');
        $data = ['idempotency_key' => 'exchange-stable', 'quote_id' => $q['id'], 'payments' => [['method_id' => $this->cashId, 'amount' => '0.01']]];
        // Original two-unit line rounds to 24.69; its first return gets 12.35.
        $data['payments'] = [];
        $this->postJson('/api/v1/returns/'.$ret.'/exchange', $data)->assertCreated();
        $this->postJson('/api/v1/returns/'.$ret.'/exchange', $data)->assertCreated();
        $this->assertSame(1, DB::table('exchange_links')->count());
        $this->assertEquals('8', DB::table('inventory_balances')->value('on_hand'));
        $this->assertSame(1, DB::table('refunds')->count());
        $q = $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->shiftId, 'items' => [['variant_id' => $this->variantId, 'qty' => '1']]])->assertCreated()->json('data');
        $this->postJson('/api/v1/pos/checkout', ['idempotency_key' => 'fake-credit-sale', 'quote_id' => $q['id'], 'payments' => [['method_id' => DB::table('payment_methods')->where('code', 'EXCHANGE')->value('id'), 'amount' => '12.35', 'reference' => 'exchange-return:'.$ret]]])->assertConflict();
    }

    public function test_split_returns_reverse_original_tax_and_discount_without_rounding_drift_or_new_catalog_prices(): void
    {
        $tax = $this->postJson('/api/v1/catalog/tax-classes', ['name' => 'Test tax', 'code' => 'TEST-TAX'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/catalog/tax-rates', ['tax_class_id' => $tax, 'jurisdiction_code' => 'TEST', 'rate_percent' => '10', 'valid_from' => '2020-01-01'])->assertCreated();
        DB::table('branches')->where('id', $this->branchId)->update(['tax_jurisdiction' => 'TEST']);
        DB::table('catalog_products')->update(['tax_class_id' => $tax]);
        $q = $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->shiftId, 'discount_amount' => '2.00', 'items' => [['variant_id' => $this->variantId, 'qty' => '3']]])->assertCreated()->json('data');
        $sale = $this->postJson('/api/v1/pos/checkout', ['idempotency_key' => 'taxed-return-sale', 'quote_id' => $q['id'], 'payments' => [['method_id' => $this->cashId, 'amount' => $q['quote']['grand_total']]]])->assertCreated()->json('data.id');
        DB::table('catalog_variants')->where('id', $this->variantId)->update(['default_price' => '999']);
        for ($i = 0; $i < 3; $i++) {
            $this->returnSale($sale, '1', $i === 0 ? 'damaged' : 'resellable');
        }
        $this->assertEquals(0, bccomp((string) DB::table('sale_return_items')->sum('credit_amount'), $q['quote']['grand_total'], 2));
        $this->assertEquals(0, bccomp((string) DB::table('sale_return_items')->sum('tax_amount'), $q['quote']['tax_total'], 2));
        $this->assertEquals(0, bccomp((string) DB::table('sale_return_items')->sum('discount_amount'), $q['quote']['discount_total'], 2));
        $report = $this->getJson('/api/v1/reports/summary?from='.now()->toDateString().'&to='.now()->toDateString())->assertOk()->assertJsonPath('data.reconciled', true)->json('data');
        $this->assertEquals(0, bccomp($report['totals']['net_sales_including_tax'], '0', 2));
        $this->assertEquals(0, bccomp($report['totals']['estimated_gross_margin'], '-10.25', 2));
        $this->assertEquals('9', DB::table('inventory_balances')->value('on_hand'));
    }
}

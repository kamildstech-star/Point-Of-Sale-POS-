<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\TestCase;

class FormValidationTest extends TestCase
{
    use BuildAdvancedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildAdvanced();
        Storage::fake('tenant_private');
    }

    public function test_onboarding_rejects_malformed_fields_without_creating_accounts_or_tenants(): void
    {
        $before = DB::table('tenants')->count();
        $this->postJson('/api/v1/auth/register', ['name' => ' ', 'email' => ['invalid'], 'password' => 'short', 'password_confirmation' => 'different', 'business_name' => ' ', 'slug' => 'Bad Slug!', 'currency_code' => 'pkr', 'timezone' => 'Mars/Olympus'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password', 'business_name', 'slug', 'currency_code', 'timezone']);
        $this->assertSame($before, DB::table('tenants')->count());
        $this->assertDatabaseCount('users', 1);
    }

    public function test_catalog_errors_have_nested_paths_and_invalid_rows_are_never_written(): void
    {
        $unit = DB::table('catalog_units')->value('id');
        $this->postJson('/api/v1/products', ['name' => 'Invalid price', 'product_type' => 'simple', 'track_inventory' => true,
            'variant' => ['sku' => 'BAD', 'base_unit_id' => $unit, 'default_cost' => '-1', 'default_price' => '1e3', 'tracking_mode' => 'none']])
            ->assertUnprocessable()->assertJsonValidationErrors(['variant.default_cost', 'variant.default_price']);
        $this->postJson('/api/v1/catalog/categories', ['name' => 'Category', 'slug' => 'Bad Slug!'])->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->postJson('/api/v1/catalog/units', ['name' => 'Wrong', 'code' => 'WRONG', 'quantity_precision' => 5])->assertUnprocessable()->assertJsonValidationErrors('quantity_precision');
        $this->assertDatabaseCount('catalog_products', 1);
        $this->assertDatabaseMissing('catalog_categories', ['name' => 'Category']);
    }

    public function test_team_and_reference_forms_return_validation_errors_instead_of_database_failures(): void
    {
        $role = DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'viewer')->value('id');
        $this->postJson('/api/v1/team', ['name' => 'Duplicate owner', 'email' => $this->owner->email, 'role_id' => $role])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/v1/team', ['name' => 'New', 'email' => 'new@example.test', 'role_id' => $role])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/api/v1/roles', ['name' => 'Invalid role', 'code' => 'custom-test', 'permissions' => ['sales.create']])->assertUnprocessable()->assertJsonValidationErrors('permissions');
        $this->postJson('/api/v1/operations/suppliers', ['name' => 'Supplier', 'email' => 'invalid', 'payment_terms_days' => 366])->assertUnprocessable()->assertJsonValidationErrors(['email', 'payment_terms_days']);
        $this->postJson('/api/v1/operations/payment-methods', ['name' => 'Bad', 'code' => 'bad code', 'method_type' => 'unknown'])->assertUnprocessable()->assertJsonValidationErrors(['code', 'method_type']);
        $this->assertDatabaseCount('tenant_memberships', 1);
        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_inventory_and_purchasing_validate_decimal_paths_before_creating_documents(): void
    {
        $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'bad-adjust', 'location_id' => $this->locationId, 'reason' => 'Invalid', 'items' => [['variant_id' => $this->variantId, 'quantity_delta' => '1e3']]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity_delta');
        $this->postJson('/api/v1/inventory/counts', ['idempotency_key' => 'bad-count', 'location_id' => $this->locationId, 'reason' => 'Invalid', 'items' => [['variant_id' => $this->variantId, 'counted_qty' => '-1']]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.counted_qty');
        $this->postJson('/api/v1/inventory/reservations', ['idempotency_key' => 'bad-reserve', 'location_id' => $this->locationId, 'variant_id' => $this->variantId, 'quantity' => '0', 'reference' => 'Test', 'minutes' => 1441])
            ->assertUnprocessable()->assertJsonValidationErrors(['quantity', 'minutes']);
        $supplier = $this->postJson('/api/v1/operations/suppliers', ['name' => 'Supplier', 'payment_terms_days' => 30])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/purchasing/orders', ['idempotency_key' => 'bad-po', 'supplier_id' => $supplier, 'location_id' => $this->locationId,
            'items' => [['variant_id' => $this->variantId, 'ordered_qty' => '0', 'unit_cost' => '-1', 'tax_rate' => '100.0001']]])
            ->assertUnprocessable()->assertJsonValidationErrors(['items.0.ordered_qty', 'items.0.unit_cost', 'items.0.tax_rate']);
        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('stock_counts', 0);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('stock_reservations', 0);
    }

    public function test_invalid_quotes_and_payments_cannot_change_sales_stock_or_cash(): void
    {
        $body = ['cash_session_id' => $this->shiftId, 'discount_amount' => '0', 'items' => [['variant_id' => $this->variantId, 'qty' => '1']]];
        $this->postJson('/api/v1/pos/quotes', array_replace($body, ['discount_amount' => '-1']))->assertUnprocessable()->assertJsonValidationErrors('discount_amount');
        $body['items'][0]['qty'] = '0';
        $this->postJson('/api/v1/pos/quotes', $body)->assertUnprocessable()->assertJsonValidationErrors('items.0.qty');
        $body['items'][0]['qty'] = '1';
        $quote = $this->postJson('/api/v1/pos/quotes', $body)->assertCreated()->json('data.id');
        foreach (['-1', '0', '1e3', '1.12345', '1000000000000', 12.34] as $index => $amount) {
            $this->postJson('/api/v1/pos/checkout', ['idempotency_key' => 'bad-payment-'.$index, 'quote_id' => $quote, 'payments' => [['method_id' => $this->cashId, 'amount' => $amount]]])
                ->assertUnprocessable()->assertJsonValidationErrors('payments.0.amount');
        }
        $this->postJson('/api/v1/pos/checkout', ['idempotency_key' => 'unpaid-walk-in', 'quote_id' => $quote, 'payments' => []])->assertConflict();
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_payments', 0);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertEquals('10', DB::table('inventory_balances')->value('on_hand'));
    }

    public function test_cash_expense_and_return_amounts_are_validated_without_browser_checks(): void
    {
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/movements', ['idempotency_key' => 'bad-move', 'movement_type' => 'in', 'amount' => '0', 'reason' => 'Test'])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson('/api/v1/cash/sessions/'.$this->shiftId.'/close', ['idempotency_key' => 'bad-close', 'counted_cash' => '-1'])->assertUnprocessable()->assertJsonValidationErrors('counted_cash');
        $category = $this->postJson('/api/v1/expenses/categories', ['name' => 'Supplies'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/expenses', ['idempotency_key' => 'bad-expense', 'category_id' => $category, 'branch_id' => $this->branchId, 'cash_session_id' => $this->shiftId, 'method_id' => $this->cashId, 'amount' => '1e2', 'description' => 'Test', 'expense_date' => '2026-02-30'])
            ->assertUnprocessable()->assertJsonValidationErrors(['amount', 'expense_date']);
        $sale = $this->sell();
        $item = DB::table('sale_items')->where('sale_id', $sale)->value('id');
        $this->postJson('/api/v1/sales/'.$sale.'/returns', ['idempotency_key' => 'bad-return', 'reason' => 'Test', 'items' => [['sale_item_id' => $item, 'qty' => '0', 'disposition' => 'damaged']]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.qty');
        $this->assertDatabaseCount('retail_expenses', 0);
        $this->assertDatabaseCount('sale_returns', 0);
        $this->assertDatabaseHas('cash_sessions', ['id' => $this->shiftId, 'status' => 'open']);
    }

    public function test_workspace_and_report_validation_returns_actionable_field_errors(): void
    {
        $this->patchJson('/api/v1/workspace/settings', ['receipt_footer' => ' ', 'return_days' => -1, 'low_stock_threshold' => '1e3'])->assertUnprocessable()->assertJsonValidationErrors(['receipt_footer', 'return_days', 'low_stock_threshold']);
        $this->patchJson('/api/v1/workspace/templates/stock.low', ['body_template' => '{{unknown}}', 'active' => true])->assertUnprocessable()->assertJsonValidationErrors('body_template');
        $this->patchJson('/api/v1/workspace/templates/stock.low', ['body_template' => '{{ sku }}: {{quantity}}', 'active' => true])->assertOk();
        $this->assertDatabaseHas('notification_templates', ['tenant_id' => $this->legacyTenant, 'event_code' => 'stock.low', 'body_template' => '{{sku}}: {{quantity}}']);
        $this->postJson('/api/v1/workspace/support', ['subject' => ' ', 'description' => str_repeat('x', 5001)])->assertUnprocessable()->assertJsonValidationErrors(['subject', 'description']);
        $this->postJson('/api/v1/workspace/exports', ['type' => 'secrets'])->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->getJson('/api/v1/reports/summary?from=2026-02-10&to=2026-02-01')->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->getJson('/api/v1/reports/summary?from=2024-01-01&to=2026-01-01')->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->assertDatabaseCount('support_cases', 0);
        $this->assertDatabaseCount('export_jobs', 0);
    }

    public function test_server_rejects_oversized_and_invalid_uploads_independently_of_file_accept(): void
    {
        $product = DB::table('catalog_products')->value('id');
        $this->postJson('/api/v1/products/'.$product.'/images', ['image' => UploadedFile::fake()->createWithContent('image.png', '<?php echo "invalid image";')])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->postJson('/api/v1/workspace/imports', ['file' => UploadedFile::fake()->create('catalog.csv', 2049, 'text/csv')])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertDatabaseCount('catalog_images', 0);
        $this->assertDatabaseCount('import_batches', 0);
        $this->assertSame([], Storage::disk('tenant_private')->allFiles());
    }
}

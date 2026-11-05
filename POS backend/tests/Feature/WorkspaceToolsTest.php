<?php

namespace Tests\Feature;

use App\Domain\Tenancy\TenantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\TestCase;

class WorkspaceToolsTest extends TestCase
{
    use BuildAdvancedDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildAdvanced();
        Storage::fake('tenant_private');
    }

    public function test_import_preview_post_retries_and_invalid_rows_do_not_mutate_stock(): void
    {
        $csv = "sku,name,unit_code,default_cost,default_price,product_type\nCSV-1,Imported,EA,1.2500,2.0000,simple\n";
        $id = $this->postJson('/api/v1/workspace/imports', ['file' => UploadedFile::fake()->createWithContent('products.csv', $csv)])->assertCreated()->assertJsonPath('data.status', 'ready')->json('data.id');
        $this->postJson('/api/v1/workspace/imports/'.$id.'/post')->assertOk();
        $this->postJson('/api/v1/workspace/imports/'.$id.'/post')->assertOk();
        $this->assertSame(2, DB::table('catalog_products')->count());
        $this->assertSame(1, DB::table('stock_movements')->count());
        $bad = $this->postJson('/api/v1/workspace/imports', ['file' => UploadedFile::fake()->createWithContent('products.csv', str_replace('EA,', 'MISSING,', $csv))])->assertCreated()->assertJsonPath('data.status', 'invalid')->json('data.id');
        $this->postJson('/api/v1/workspace/imports/'.$bad.'/post')->assertConflict();
        $this->assertSame(2, DB::table('catalog_products')->count());
    }

    public function test_exports_are_private_expire_and_foreign_tenants_cannot_read_files_or_cases(): void
    {
        $id = $this->postJson('/api/v1/workspace/exports', ['type' => 'catalog'])->assertCreated()->json('data.id');
        $this->get('/api/v1/workspace/exports/'.$id.'/download')->assertOk();
        $case = $this->postJson('/api/v1/workspace/support', ['subject' => 'Test case', 'description' => 'Please inspect request ID'])->assertCreated()->json('data.id');
        $other = app(TenantService::class)->create($this->owner, ['name' => 'Other business', 'slug' => 'other', 'timezone' => 'UTC', 'currency_code' => 'PKR']);
        $this->selectTenant($other);
        $this->get('/api/v1/workspace/exports/'.$id.'/download')->assertNotFound();
        $this->getJson('/api/v1/workspace/support')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/billing/subscription')->assertJsonPath('data.access.writable', false);
        $this->selectTenant($this->legacyTenant);
        $this->travel(2)->days();
        $this->get('/api/v1/workspace/exports/'.$id.'/download')->assertNotFound();
    }

    public function test_receipt_settings_snapshot_alert_templates_and_request_ids(): void
    {
        $this->patchJson('/api/v1/workspace/settings', ['receipt_name' => 'Receipt shop', 'receipt_footer' => 'Thanks from our team', 'tax_registration' => 'TEST-REG', 'return_days' => null, 'low_stock_threshold' => '10'])->assertOk();
        $this->putJson('/api/v1/workspace/templates/stock.low', ['body_template' => '{{sku}}: {{quantity}} remaining', 'active' => true])->assertOk();
        $this->putJson('/api/v1/workspace/templates/stock.low', ['body_template' => '{{password}}', 'active' => true])->assertUnprocessable();
        $sale = $this->sell();
        $receipt = $this->getJson('/api/v1/sales/'.$sale)->assertOk()->assertHeader('X-Request-ID')->json('data');
        $snapshot = json_decode($receipt['branch_snapshot_json'], true);
        $this->assertSame('Receipt shop', $snapshot['receipt']['receipt_name']);
        $this->getJson('/api/v1/workspace/alerts')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson('/api/v1/workspace/templates/stock.low', ['body_template' => '{{sku}}: {{quantity}} remaining', 'active' => false])->assertOk();
        $this->sell('1');
        $this->getJson('/api/v1/workspace/alerts')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/workspace/audit')->assertOk();
        $this->getJson('/api/v1/health/readiness')->assertOk()->assertJsonPath('ready', true);
    }

    public function test_legacy_ledger_namespaces_and_unknown_refunds_are_preserved_for_review(): void
    {
        foreach (['ledger', 'ledgers'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('tenant_id');
                $t->decimal('debit', 19, 4);
                $t->decimal('credit', 19, 4);
                $t->decimal('balance', 19, 4);
            });
            DB::table($table)->insert(['tenant_id' => $this->legacyTenant, 'debit' => '10', 'credit' => '3', 'balance' => '7']);
        }$before = DB::table('ledger')->get()->toJson();
        $this->postJson('/api/v1/legacy/financial-reconciliation')->assertOk()->assertJsonPath('data.refund_history', 'unknown')->assertJsonPath('data.accounting_migration_allowed', false);
        $this->postJson('/api/v1/legacy/financial-reconciliation')->assertOk();
        $this->assertSame(2, DB::table('legacy_financial_snapshots')->count());
        $this->assertSame($before, DB::table('ledger')->get()->toJson());
        $this->assertSame(0, DB::table('refunds')->count());
    }
}

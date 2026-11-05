<?php

namespace Tests\Feature;

use App\Domain\Catalog\LegacyCatalogMapper;
use App\Domain\Tenancy\TenantContext;
use App\Http\Middleware\FreezeLegacyStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildTenantDatabase;
use Tests\TestCase;

class LegacyCutoverTest extends TestCase
{
    use BuildTenantDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDatabase();
        (require database_path('migrations/2026_10_19_130000_create_inventory_purchasing_pos.php'))->up();
        $this->selectTenant($this->legacyTenant);
    }

    private function mappedStock(): int
    {
        $now = now();
        $common = ['tenant_id' => $this->legacyTenant, 'created_at' => $now, 'updated_at' => $now];
        $product = DB::table('products')->insertGetId(['name' => 'Legacy shirt', 'gender' => 1, 'season' => 2] + $common);
        $school = DB::table('schools')->insertGetId(['name' => 'School A', 'percentage' => '15'] + $common);
        $size = DB::table('sizes')->insertGetId(['product_id' => $product, 'size' => '30'] + $common);
        $stock = DB::table('stocks')->insertGetId(['product_id' => $product, 'school_id' => $school, 'size_id' => $size, 'quantity' => 5, 'base_price' => '100.00', 'sale_price' => '150.00', 'product_code' => 'SCHOOL-A-30'] + $common);
        DB::table('tenants')->where('id', $this->legacyTenant)->update(['sku_policy' => 'distinct_by_school']);
        $location = DB::table('locations')->value('id');
        app(TenantContext::class)->runFor($this->legacyTenant, $this->owner->id, fn () => app(LegacyCatalogMapper::class)->run($location));

        return $stock;
    }

    public function test_cutover_uses_current_stock_once_retains_legacy_prices_and_freezes_old_writers(): void
    {
        $stock = $this->mappedStock();
        DB::table('stocks')->where('id', $stock)->update(['quantity' => 4]);
        $branch = DB::table('branches')->value('id');
        $preview = $this->getJson('/api/v1/pos/branches/'.$branch.'/preview')->assertOk()->assertJsonCount(0, 'data.issues')->json('data');
        $this->assertSame('4', $preview['opening_rows'][0]['quantity']);
        $before = DB::table('stocks')->get()->toJson();
        $payload = ['idempotency_key' => 'cutover-owner-review', 'preview_hash' => $preview['preview_hash'], 'confirm_locations' => true, 'allow_credit' => true];
        $this->postJson('/api/v1/pos/branches/'.$branch.'/activate', $payload)->assertOk()->assertJsonPath('data.opening_rows', 1);
        $this->postJson('/api/v1/pos/branches/'.$branch.'/activate', $payload)->assertOk();
        $this->assertSame(1, DB::table('stock_movements')->count());
        $this->assertEquals('4', DB::table('inventory_balances')->value('on_hand'));
        $this->assertSame($before, DB::table('stocks')->get()->toJson());
        $this->assertEquals('150', DB::table('catalog_variants')->value('default_price'));
        $this->assertEquals('15', DB::table('schools')->value('percentage'));
        $batch = DB::table('migration_batches')->where('batch_code', 'legacy-opening-v1')->first();
        $snapshot = json_decode($batch->reconciliation_json, true);
        $this->assertFalse($snapshot['history_replayed']);
        $this->assertSame('4', $snapshot['source_rows'][0]['quantity']);
        Route::post('/test-legacy-write', fn () => DB::table('stocks')->where('id', $stock)->update(['quantity' => 999]))->middleware(['web', FreezeLegacyStock::class]);
        $this->postJson('/test-legacy-write')->assertConflict();
        $this->assertSame($before, DB::table('stocks')->get()->toJson());
        $this->postJson('/api/v1/inventory/reconcile')->assertJsonPath('data.reconciled', true);
        foreach (['stock.create', 'stock.update', 'stock.destroy', 'order.create', 'order.update', 'order.destroy', 'order.cancel', 'return.create', 'return.update', 'return.destroy', 'product.create', 'product.update', 'product.destroy', 'school.create', 'school.update', 'school.destroy', 'size.create', 'size.update'] as $route) {
            $this->assertContains(FreezeLegacyStock::class, Route::getRoutes()->getByName($route)->gatherMiddleware(), $route);
        }
    }

    public function test_cutover_rejects_unreviewed_stock_changes_and_identity_changes(): void
    {
        $stock = $this->mappedStock();
        $branch = DB::table('branches')->value('id');
        $preview = $this->getJson('/api/v1/pos/branches/'.$branch.'/preview')->assertOk()->json('data');
        DB::table('stocks')->where('id', $stock)->update(['quantity' => 3]);
        $this->postJson('/api/v1/pos/branches/'.$branch.'/activate', ['idempotency_key' => 'stale-cutover-request', 'preview_hash' => $preview['preview_hash'], 'confirm_locations' => true, 'allow_credit' => true])->assertConflict();
        DB::table('stocks')->where('id', $stock)->update(['sale_price' => '999']);
        $preview = $this->getJson('/api/v1/pos/branches/'.$branch.'/preview')->assertOk()->json('data');
        $this->assertNotEmpty($preview['issues']);
        $this->assertNull(DB::table('tenants')->value('legacy_stock_frozen_at'));
        $this->assertSame(0, DB::table('stock_movements')->count());
    }

    public function test_new_ledger_cannot_post_before_legacy_cutover_and_scans_are_exact(): void
    {
        $this->mappedStock();
        $variant = DB::table('catalog_variants')->value('id');
        $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'unsafe-adjustment', 'location_id' => DB::table('locations')->value('id'), 'reason' => 'Too early', 'items' => [['variant_id' => $variant, 'quantity_delta' => '1']]])->assertConflict();
        $this->getJson('/api/v1/pos/products?search=SCHOOL-A-30&scan=1')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/pos/products?search=Legacy&scan=1')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/pos/products?search=Legacy')->assertOk()->assertJsonCount(1, 'data');
    }
}

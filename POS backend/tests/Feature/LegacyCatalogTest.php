<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildTenantDatabase;
use Tests\TestCase;

class LegacyCatalogTest extends TestCase
{
    use BuildTenantDatabase;
    protected function setUp(): void { parent::setUp(); $this->buildDatabase(); $this->selectTenant($this->legacyTenant); }
    private function stock(int $school = 1, string $barcode = 'LEGACY-001'): int
    {
        DB::table('products')->insertOrIgnore(['id' => 1, 'name' => 'Shirt', 'image' => 'old/shirt.png', 'gender' => 7, 'season' => 4]);
        DB::table('schools')->insertOrIgnore(['id' => $school, 'name' => 'School '.$school, 'percentage' => '10.00']);
        DB::table('sizes')->insertOrIgnore(['id' => 1, 'product_id' => 1, 'size' => 'Medium']);
        return DB::table('stocks')->insertGetId(['school_id' => $school, 'product_id' => 1, 'size_id' => 1, 'quantity' => 10, 'base_price' => '100.25', 'sale_price' => '125.50', 'product_code' => $barcode]);
    }
    private function map()
    {
        $location = DB::table('locations')->where('tenant_id', $this->legacyTenant)->value('id');
        return $this->postJson('/api/v1/legacy/catalog-map', ['location_id' => $location]);
    }
    private function approve(): void { $this->postJson('/api/v1/legacy/sku-policy', ['policy' => 'distinct_by_school', 'confirmed' => true])->assertOk(); }

    public function test_every_stock_row_is_mapped_distinctly_and_reruns_are_idempotent_without_posting_stock(): void
    {
        $first = $this->stock(); $second = $this->stock(2, 'LEGACY-002'); $before = DB::table('stocks')->get()->toJson();
        $this->approve(); $this->map()->assertOk()->assertJsonPath('data.mapped', 2)->assertJsonPath('data.created', 2);
        $this->map()->assertOk()->assertJsonPath('data.created', 0);
        $this->assertSame(2, DB::table('catalog_variants')->count());
        $this->assertSame(2, DB::table('legacy_record_mappings')->count());
        $this->assertSame($before, DB::table('stocks')->get()->toJson());
        $snapshot = json_decode(DB::table('legacy_record_mappings')->where('source_pk', $first)->value('mapping_json'), true);
        $this->assertSame(7, $snapshot['source']['product']['gender']);
        $this->assertSame('old/shirt.png', $snapshot['source']['product']['image']);
        $this->assertFalse($snapshot['opening_balance_posted']);
        $this->assertNotEquals(DB::table('legacy_record_mappings')->where('source_pk', $first)->value('target_pk'), DB::table('legacy_record_mappings')->where('source_pk', $second)->value('target_pk'));
    }

    public function test_unapproved_policy_duplicate_combinations_and_barcodes_are_explicit_exceptions(): void
    {
        $this->stock();
        $this->map()->assertOk()->assertJsonPath('data.unresolved', 1)->assertJsonPath('data.mapped', 0);
        $this->approve(); $this->stock();
        $this->map()->assertOk()->assertJsonPath('data.unresolved', 2);
        $this->assertSame(0, DB::table('catalog_variants')->count());
        $this->assertDatabaseHas('migration_exceptions', ['issue_code' => 'duplicate_stock_combination', 'resolution_status' => 'unresolved']);
    }

    public function test_source_changes_do_not_silently_rewrite_an_established_mapping(): void
    {
        $stock = $this->stock(); $this->approve(); $this->map()->assertOk();
        DB::table('stocks')->where('id', $stock)->update(['quantity' => 8]);
        $this->map()->assertOk()->assertJsonPath('data.unresolved', 1)->assertJsonPath('data.created', 0);
        $this->assertDatabaseHas('migration_exceptions', ['source_pk' => $stock, 'issue_code' => 'source_changed_after_mapping']);
        $this->assertSame(1, DB::table('catalog_variants')->count());
    }

    public function test_legacy_backfill_rerun_preserves_ids_and_existing_membership_roles(): void
    {
        $this->stock(); $members = DB::table('tenant_memberships')->get()->toJson();
        $this->artisan('legacy:bootstrap', ['--apply' => true])->assertSuccessful();
        $this->assertSame($members, DB::table('tenant_memberships')->get()->toJson());
        $this->assertSame(1, DB::table('tenants')->where('legacy_key', 'best-uniform')->count());
        $this->assertDatabaseHas('stocks', ['tenant_id' => $this->legacyTenant, 'quantity' => 10]);
    }

    public function test_distinct_combinations_with_colliding_barcodes_are_not_merged(): void
    {
        $this->stock(1, 'DUPLICATE'); $this->stock(2, 'DUPLICATE'); $this->approve();
        $this->map()->assertOk()->assertJsonPath('data.unresolved', 2)->assertJsonPath('data.created', 0);
        $this->assertDatabaseHas('migration_exceptions', ['issue_code' => 'barcode_collision_or_invalid']);
        $this->assertSame(0, DB::table('legacy_record_mappings')->count());
    }
}

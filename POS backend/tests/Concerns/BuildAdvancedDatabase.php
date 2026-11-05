<?php

namespace Tests\Concerns;

use App\Domain\Operations\AdvancedInstaller;
use Illuminate\Support\Facades\DB;

trait BuildAdvancedDatabase
{
    use BuildTenantDatabase;

    protected int $branchId;

    protected int $locationId;

    protected int $variantId;

    protected int $shiftId;

    protected int $cashId;

    protected function buildAdvanced(): void
    {
        $this->buildDatabase();
        foreach (['2026_10_19_130000_create_inventory_purchasing_pos.php', '2026_10_20_140000_create_returns_cash_reports.php', '2026_10_20_150000_create_saas_settings_operations.php'] as $f) {
            (require database_path('migrations/'.$f))->up();
        }app(AdvancedInstaller::class)->run();
        $this->selectTenant($this->legacyTenant);
        $this->branchId = DB::table('branches')->value('id');
        $this->locationId = DB::table('locations')->value('id');
        $p = $this->getJson('/api/v1/pos/branches/'.$this->branchId.'/preview')->assertOk()->json('data');
        $this->postJson('/api/v1/pos/branches/'.$this->branchId.'/activate', ['idempotency_key' => 'fixture-activate', 'preview_hash' => $p['preview_hash'], 'confirm_locations' => true, 'allow_credit' => true])->assertOk();
        [$product] = $this->createCatalogProduct();
        $this->variantId = DB::table('catalog_variants')->where('product_id', $product)->value('id');
        $reg = $this->postJson('/api/v1/operations/registers', ['name' => 'Test counter', 'branch_id' => $this->branchId, 'location_id' => $this->locationId])->assertCreated()->json('data.id');
        $this->shiftId = $this->postJson('/api/v1/cash/sessions', ['idempotency_key' => 'fixture-open-shift', 'register_id' => $reg, 'opening_cash' => '100.00'])->assertCreated()->json('data.id');
        $this->cashId = DB::table('payment_methods')->where('tenant_id', $this->legacyTenant)->where('code', 'CASH')->value('id');
        $id = $this->postJson('/api/v1/inventory/adjustments', ['idempotency_key' => 'fixture-stock', 'location_id' => $this->locationId, 'reason' => 'Test stock', 'items' => [['variant_id' => $this->variantId, 'quantity_delta' => '10']]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/inventory/adjustments/'.$id.'/post')->assertOk();
    }

    protected function sell(string $qty = '2', ?int $customer = null, ?string $paid = null): int
    {
        $q = $this->postJson('/api/v1/pos/quotes', ['cash_session_id' => $this->shiftId, 'customer_id' => $customer, 'items' => [['variant_id' => $this->variantId, 'qty' => $qty]], 'discount_amount' => '0'])->assertCreated()->json('data');

        return $this->postJson('/api/v1/pos/checkout', ['idempotency_key' => 'sale-'.bin2hex(random_bytes(5)), 'quote_id' => $q['id'], 'payments' => $paid === '0' ? [] : [['method_id' => $this->cashId, 'amount' => $paid ?? $q['quote']['grand_total']]]])->assertCreated()->json('data.id');
    }

    protected function returnSale(int $sale, string $qty = '1', string $disposition = 'resellable'): int
    {
        $line = DB::table('sale_items')->where('sale_id', $sale)->value('id');
        $id = $this->postJson('/api/v1/sales/'.$sale.'/returns', ['idempotency_key' => 'return-'.bin2hex(random_bytes(5)), 'reason' => 'Customer return', 'items' => [['sale_item_id' => $line, 'qty' => $qty, 'disposition' => $disposition]]])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/returns/'.$id.'/approve')->assertOk();
        $this->postJson('/api/v1/returns/'.$id.'/post')->assertOk();

        return $id;
    }
}

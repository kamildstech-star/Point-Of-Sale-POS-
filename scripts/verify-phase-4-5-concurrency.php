<?php
use App\Domain\Operations\CutoverService;
use App\Domain\Operations\InventoryPostingService;
use App\Domain\Operations\PurchasingService;
use App\Domain\Operations\SaleService;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

require __DIR__.'/phase-4-5-stage.php';
$private = __DIR__.'/../docs/audit/private/';
$check = static function (bool $condition, string $message) { if (! $condition) throw new RuntimeException($message); };
try {
    $owner = User::findOrFail(DB::table('tenant_memberships')->where('status', 'active')->orderBy('id')->value('user_id'));
    $tenant = app(TenantService::class)->create($owner, ['name' => 'Isolated concurrency fixture', 'slug' => 'concurrency-'.bin2hex(random_bytes(5)), 'timezone' => 'Asia/Karachi', 'currency_code' => 'PKR']);
    $report = app(TenantContext::class)->runFor($tenant, $owner->id, function () use ($tenant, $owner, $private, $check) {
        $put = fn (string $table, array $data) => DB::table($table)->insertGetId($data + ['tenant_id' => $tenant, 'created_at' => now(), 'updated_at' => now()]);
        $branch = DB::table('branches')->where('tenant_id', $tenant)->value('id'); $location = DB::table('locations')->where('tenant_id', $tenant)->value('id');
        $cutover = app(CutoverService::class); $preview = $cutover->preview($branch); $cutover->activate($branch, ['idempotency_key' => 'fixture-activate', 'preview_hash' => $preview['preview_hash'], 'confirm_locations' => true, 'allow_credit' => true]);
        $unit = $put('catalog_units', ['name' => 'Each', 'code' => 'EA', 'quantity_precision' => 0]);
        $product = $put('catalog_products', ['name' => 'Concurrency item', 'product_type' => 'simple', 'track_inventory' => true]);
        $variant = $put('catalog_variants', ['product_id' => $product, 'base_unit_id' => $unit, 'sku' => 'FINAL-ITEM', 'default_cost' => '10', 'default_price' => '12.3456', 'tracking_mode' => 'none']);
        $register = $put('cash_registers', ['branch_id' => $branch, 'location_id' => $location, 'name' => 'Fixture counter']);
        $session = app(SaleService::class)->open(['idempotency_key' => 'fixture-open', 'register_id' => $register, 'opening_cash' => '100.00'])['id']; $cash = DB::table('payment_methods')->where('tenant_id', $tenant)->where('code', 'CASH')->value('id');
        $quote = fn () => app(SaleService::class)->quote(['cash_session_id' => $session, 'items' => [['variant_id' => $variant, 'qty' => '1']], 'discount_amount' => '0']);
        $checkout = fn (int $q, string $key) => ['operation' => 'checkout', 'input' => ['idempotency_key' => $key, 'quote_id' => $q, 'payments' => [['method_id' => $cash, 'amount' => '12.35']]]];
        $race = function (array $jobs) use ($private, $tenant, $owner, $check): array {
            $run = $private.'phase45-run-'.bin2hex(random_bytes(8)); mkdir($run, 0700); $workers = []; $paths = [];
            foreach ($jobs as $job) {
                $path = $run.'/job-'.bin2hex(random_bytes(5)).'.json'; file_put_contents($path, json_encode($job + ['tenant' => $tenant, 'owner' => $owner->id], JSON_THROW_ON_ERROR)); $paths[] = $path;
                $process = new Process([PHP_BINARY, __DIR__.'/phase-4-5-worker.php', $path], __DIR__.'/..'); $process->setTimeout(60); $process->start(); $workers[] = $process;
            }
            $deadline = microtime(true) + 35;
            while (count(array_filter($paths, fn ($path) => is_file($path.'.ready'))) !== count($paths)) {
                if (microtime(true) > $deadline || array_filter($workers, fn ($p) => ! $p->isRunning())) throw new RuntimeException('Worker failed to reach the concurrency barrier.'); usleep(20000);
            }
            file_put_contents($run.'/start', 'start'); $out = [];
            foreach ($workers as $worker) { $worker->wait(); $result = json_decode($worker->getOutput(), true, 512, JSON_THROW_ON_ERROR); $check($worker->isSuccessful() && $result['status'] !== 'error', 'Worker error: '.json_encode($result)); $out[] = $result; }
            return $out;
        };
        $inventory = app(InventoryPostingService::class); $inventory->post($location, $variant, '1', 'adjustment', 'fixture', 1, 'fixture-opening');
        $q1 = $quote(); $q2 = $quote(); $last = $race([$checkout($q1['id'], 'last-item-first'), $checkout($q2['id'], 'last-item-second')]);
        $statuses = array_column($last, 'status'); sort($statuses); $check($statuses === ['conflict', 'ok'], 'Exactly one final-item sale must succeed.');
        $inventory->post($location, $variant, '1', 'adjustment', 'fixture', 1, 'fixture-replenish'); $q = $quote(); $job = $checkout($q['id'], 'double-click-key'); $duplicate = $race([$job, $job]);
        $check(array_column($duplicate, 'status') === ['ok', 'ok'] && $duplicate[0]['result']['id'] === $duplicate[1]['result']['id'], 'Duplicate checkout must return one sale.');
        $supplier = $put('suppliers', ['name' => 'Fixture supplier', 'payment_terms_days' => 0]); $purchase = app(PurchasingService::class);
        $po = $purchase->order(['idempotency_key' => 'fixture-order', 'supplier_id' => $supplier, 'location_id' => $location, 'items' => [['variant_id' => $variant, 'ordered_qty' => '3', 'unit_cost' => '10', 'tax_rate' => '10']]])['id']; $purchase->approve($po);
        $line = DB::table('purchase_order_items')->where('tenant_id', $tenant)->where('purchase_order_id', $po)->value('id');
        $receiveJob = ['operation' => 'receipt', 'source_id' => $po, 'input' => ['idempotency_key' => 'duplicate-receipt', 'items' => [['purchase_order_item_id' => $line, 'accepted_qty' => '3', 'rejected_qty' => '0']]]];
        $receipts = $race([$receiveJob, $receiveJob]); $gr = $receipts[0]['result']['id']; $check($gr === $receipts[1]['result']['id'], 'Duplicate receipt must post once.');
        $gri = DB::table('goods_receipt_items')->where('tenant_id', $tenant)->where('goods_receipt_id', $gr)->value('id');
        $returnJob = ['operation' => 'return', 'source_id' => $gr, 'input' => ['idempotency_key' => 'duplicate-return', 'reason' => 'Fixture supplier return', 'items' => [['goods_receipt_item_id' => $gri, 'quantity' => '2']]]];
        $returns = $race([$returnJob, $returnJob]); $check($returns[0]['result']['id'] === $returns[1]['result']['id'], 'Duplicate supplier return must post once.');
        $returnJob['input']['idempotency_key'] = 'last-return-unit'; $returnJob['input']['items'][0]['quantity'] = '1'; $q = $quote(); $saleReturn = $race([$checkout($q['id'], 'sale-versus-return'), $returnJob]); $statuses = array_column($saleReturn, 'status'); sort($statuses); $check($statuses === ['conflict', 'ok'], 'Sale and supplier return cannot both consume the final unit.');
        $q = $quote(); $po2 = $purchase->order(['idempotency_key' => 'fixture-order-2', 'supplier_id' => $supplier, 'location_id' => $location, 'items' => [['variant_id' => $variant, 'ordered_qty' => '1', 'unit_cost' => '10']]])['id']; $purchase->approve($po2); $line2 = DB::table('purchase_order_items')->where('tenant_id', $tenant)->where('purchase_order_id', $po2)->value('id');
        $receiptSale = $race([$checkout($q['id'], 'sale-versus-receipt'), ['operation' => 'receipt', 'source_id' => $po2, 'input' => ['idempotency_key' => 'simultaneous-receipt', 'items' => [['purchase_order_item_id' => $line2, 'accepted_qty' => '1']]]]]);
        $check($receiptSale[1]['status'] === 'ok', 'Receipt must succeed during simultaneous checkout.');
        $reconciled = $inventory->reconcile(); $check($reconciled['reconciled'], 'Inventory ledger must reconcile after all races.');
        $balance = DB::table('inventory_balances')->where('tenant_id', $tenant)->value('on_hand'); $check(bccomp((string) $balance, '0', 4) >= 0, 'Stock cannot become negative.');
        $check(DB::table('goods_receipts')->where('tenant_id', $tenant)->count() === 2, 'Exactly two receipts must exist.');
        $immutable = false; try { DB::table('stock_movements')->where('tenant_id', $tenant)->limit(1)->update(['quantity_delta' => '999']); } catch (Throwable) { $immutable = true; } $check($immutable, 'MariaDB must reject ledger edits.');
        return ['tenant_fixture_id' => $tenant, 'final_item_sales' => $last, 'duplicate_checkout' => $duplicate, 'duplicate_receipt' => $receipts, 'duplicate_return' => $returns, 'sale_vs_return' => $saleReturn, 'sale_vs_receipt' => $receiptSale, 'inventory_reconciliation' => $reconciled, 'ledger_immutable' => true];
    });
    $report = ['passed' => true, 'database' => $stageDatabase, 'verified_at' => date(DATE_ATOM), 'separate_worker_processes' => true] + $report;
    file_put_contents($private.'phase-4-5-concurrency.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)); echo 'MariaDB simultaneous checkout, receipt and supplier-return checks passed. Ledger reconciles; duplicate requests post once.'.PHP_EOL;
} catch (Throwable $e) { file_put_contents($private.'phase-4-5-concurrency-error.json', json_encode(['class' => $e::class, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], JSON_PRETTY_PRINT)); fwrite(STDERR, 'Concurrency verification failed. Inspect private phase-4-5-concurrency-error.json.'.PHP_EOL); exit(1); }

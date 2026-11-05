<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class LegacyAudit extends Command
{
    protected $signature = 'legacy:audit {--output= : Private JSON report path}';

    protected $description = 'Read-only legacy schema, integrity and reconciliation snapshot (no customer details)';

    public function handle(): int
    {
        $report = ['captured_at' => now()->toIso8601String(), 'laravel' => app()->version(), 'routes' => []];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            $issue = null;
            if (str_contains($action, '@')) {
                [$class, $method] = explode('@', $action, 2);
                if (! class_exists($class)) {
                    $issue = 'missing_controller';
                } elseif (! method_exists($class, $method)) {
                    $issue = 'missing_method';
                }
            }
            $report['routes'][] = ['methods' => $route->methods(), 'uri' => $route->uri(), 'action' => $action, 'issue' => $issue];
        }
        $db = DB::connection();
        $driver = $db->getDriverName();
        $report['database'] = ['driver' => $driver];
        try {
            if ($driver === 'mysql') {
                $db->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
                $db->statement('SET TRANSACTION READ ONLY');
                $report['database']['server_version'] = $db->selectOne('SELECT VERSION() AS version')->version;
            } elseif ($driver === 'sqlite') {
                $db->statement('PRAGMA query_only = ON');
                $report['database']['server_version'] = $db->selectOne('SELECT sqlite_version() AS version')->version;
            } else {
                throw new \RuntimeException('Audit engine not supported; implement an engine-specific read-only adapter first.');
            }
            $db->beginTransaction();
            $schema = $db->getSchemaBuilder();
            $tables = array_column($schema->getTables(), 'name');
            sort($tables);
            foreach ($tables as $table) {
                $entry = [
                    'row_count' => $db->table($table)->count(),
                    'columns' => $schema->getColumns($table),
                    'indexes' => $schema->getIndexes($table),
                    'foreign_keys' => $schema->getForeignKeys($table),
                ];
                if ($driver === 'mysql') {
                    $ddl = (array) $db->selectOne('SHOW CREATE TABLE '.$db->getQueryGrammar()->wrapTable($table));
                    $entry['ddl'] = array_values($ddl)[1];
                }
                $report['tables'][$table] = $entry;
            }
            $report['migrations'] = in_array('migrations', $tables, true) ? $db->table('migrations')->orderBy('id')->get()->all() : [];

            // Hash full business rows in stable PK order to compare a restored
            // backup without exporting customer names, phone numbers or hashes.
            $businessTables = ['users', 'roles', 'permission', 'products', 'sizes', 'schools', 'stocks', 'orders', 'order_items', 'order_random_items', 'returns', 'return_items', 'business_dealers', 'ledgers', 'ledger', 'daily_account', 'expenses', 'settings'];
            $businessTables = array_unique(array_merge($businessTables, array_filter($tables, fn ($table) => str_starts_with($table, 'catalog_') || str_starts_with($table, 'tenant') || str_starts_with($table, 'inventory_') || str_starts_with($table, 'stock_') || str_starts_with($table, 'purchase_') || str_starts_with($table, 'supplier_') || str_starts_with($table, 'goods_receipt') || str_starts_with($table, 'sale') || str_starts_with($table, 'cash_') || in_array($table, ['customers', 'suppliers', 'payment_methods', 'invoice_sequences', 'idempotency_records', 'legacy_customer_links', 'branches', 'locations', 'audit_logs', 'migration_batches', 'migration_exceptions', 'legacy_record_mappings']))));
            foreach ($tables as $table) {
                if (in_array('tenant_id', array_column($report['tables'][$table]['columns'], 'name')) || in_array($table, ['plans', 'plan_prices', 'plan_entitlements', 'billing_webhook_events', 'operational_runs'])) {
                    $businessTables[] = $table;
                }
            }
            foreach (array_unique(array_intersect($businessTables, $tables)) as $table) {
                $columns = array_column($report['tables'][$table]['columns'], 'name');
                $hash = hash_init('sha256');
                foreach ($db->table($table)->orderBy(in_array('id', $columns, true) ? 'id' : $columns[0])->cursor() as $row) {
                    hash_update($hash, json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
                }
                $report['baseline']['fingerprints'][$table] = hash_final($hash);
            }
            $sums = ['stocks' => ['quantity'], 'orders' => ['subtotal', 'total', 'discount', 'school_margin', 'received_amount', 'remaining_amount'], 'order_items' => ['quantity', 'returned', 'total'], 'expenses' => ['amount'], 'ledgers' => ['credit', 'debit', 'balance'], 'ledger' => ['credit', 'debit'], 'daily_account' => ['sale', 'total_in', 'total_out', 'closing_balance']];
            foreach ($sums as $table => $fields) {
                if (! isset($report['tables'][$table])) {
                    continue;
                }
                $columns = array_column($report['tables'][$table]['columns'], 'name');
                foreach (array_intersect($fields, $columns) as $field) {
                    $value = $db->table($table)->selectRaw('SUM('.$db->getQueryGrammar()->wrap($field).') AS aggregate')->first()->aggregate;
                    $report['baseline']['sums'][$table][$field] = $value === null ? null : (string) $value;
                }
            }
            if (isset($report['tables']['stocks'])) {
                $report['baseline']['stock_by_id'] = $db->table('stocks')->select('id', 'school_id', 'product_id', 'size_id', 'quantity', 'product_code')->orderBy('id')->get()->all();
                $report['integrity']['duplicate_stock_combinations'] = $db->table('stocks')->select('school_id', 'product_id', 'size_id')->selectRaw('COUNT(*) AS copies')->groupBy('school_id', 'product_id', 'size_id')->havingRaw('COUNT(*) > 1')->get()->all();
                $report['integrity']['duplicate_product_codes'] = $db->table('stocks')->select('product_code')->whereNotNull('product_code')->selectRaw('COUNT(*) AS copies')->groupBy('product_code')->havingRaw('COUNT(*) > 1')->get()->all();
                $report['integrity']['negative_stock'] = $db->table('stocks')->where('quantity', '<', 0)->count();
            }
            $relations = [['stocks', 'school_id', 'schools'], ['stocks', 'product_id', 'products'], ['stocks', 'size_id', 'sizes'], ['sizes', 'product_id', 'products'], ['orders', 'user_id', 'users'], ['order_items', 'order_id', 'orders'], ['order_items', 'product_id', 'products'], ['order_items', 'school_id', 'schools'], ['order_items', 'size_id', 'sizes'], ['users', 'role_id', 'roles']];
            foreach ($relations as [$child, $key, $parent]) {
                if (! isset($report['tables'][$child], $report['tables'][$parent]) || ! $schema->hasColumn($child, $key)) {
                    continue;
                }
                $report['integrity']['orphans']["$child.$key"] = $db->table($child.' as child')->leftJoin($parent.' as parent', 'child.'.$key, '=', 'parent.id')->whereNotNull('child.'.$key)->whereNull('parent.id')->count();
            }
            if (isset($report['tables']['users'])) {
                $report['baseline']['staff_count'] = $db->table('users')->whereIn('role_id', [1, 2])->count();
                $report['baseline']['customer_count'] = $db->table('users')->whereNull('role_id')->count();
            }
            $report['database']['snapshot_complete'] = true;
        } catch (\Throwable $exception) {
            $report['database']['snapshot_complete'] = false;
            $report['database']['error_type'] = $exception::class;
            // Do not print DSNs, credentials, customer values or exception SQL.
        } finally {
            if ($db->transactionLevel() > 0) {
                $db->rollBack();
            }
            if ($driver === 'sqlite') {
                $db->statement('PRAGMA query_only = OFF');
            }
        }
        $path = $this->option('output') ?: storage_path('app/private/legacy-audit.json');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $this->info('Audit report written; snapshot '.($report['database']['snapshot_complete'] ? 'complete' : 'incomplete').'. No business rows modified.');

        return $report['database']['snapshot_complete'] ? self::SUCCESS : self::FAILURE;
    }
}

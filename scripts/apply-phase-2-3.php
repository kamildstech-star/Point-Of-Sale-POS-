<?php

// Run against a verified local restore first, then explicitly against the local
// source. Never changes .env, credentials, monetary fields or stock quantities.
use App\Domain\Tenancy\LegacyTables;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../POS backend/vendor/autoload.php';
$app = require __DIR__.'/../POS backend/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    $mode = $argv[1] ?? '';
    $source = DB::connection()->getConfig();
    if ($source['driver'] !== 'mysql' || ! in_array($source['host'], ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Only the audited local MySQL/MariaDB installation is supported.');
    if ($mode === 'rehearsal') {
        $backup = $argv[2] ?? '';
        if (! preg_match('/^backup_[a-f0-9]{32}$/D', $backup)) throw new RuntimeException('Supply the verified pre-phase backup ID.');
        $evidence = json_decode(file_get_contents(__DIR__.'/../docs/audit/private/'.$backup.'/verification.json'), true, 512, JSON_THROW_ON_ERROR);
        $database = $evidence['staging_database'];
        if (empty($evidence['database_reconciled']) || ! preg_match('/^bu_restore_[a-f0-9]{16}$/D', $database) || $database === $source['database']) throw new RuntimeException('A verified isolated restore is required.');
        config(['database.connections.phase23' => array_merge($source, ['database' => $database, 'url' => null, 'name' => 'phase23']), 'database.default' => 'phase23']);
        DB::purge('phase23');
        if (DB::selectOne('SELECT DATABASE() AS db')->db !== $database) throw new RuntimeException('Unexpected rehearsal target.');
        if (DB::connection()->getName() !== 'phase23') throw new RuntimeException('The rehearsal connection must carry its own connection name.');
    } elseif ($mode !== 'apply-local' || app()->environment('production')) {
        throw new RuntimeException('Use rehearsal <backup-id> first, then apply-local on the local development installation.');
    }
    // Schema is a cached facade. Rebind it after changing the connection so DDL
    // and data/repository queries use the exact same verified database.
    Schema::swap(DB::connection()->getSchemaBuilder());
    $migrationConnection = DB::getDefaultConnection();
    if ($mode === 'apply-local' && ! DB::table('migrations')->where('migration', '2026_10_19_100000_create_tenant_foundation')->exists()) {
        $rehearsalPath = __DIR__.'/../docs/audit/private/phase-2-3-rehearsal.json';
        if (! is_file($rehearsalPath)) throw new RuntimeException('Complete the isolated rehearsal first.');
        $rehearsal = json_decode(file_get_contents($rehearsalPath), true, 512, JSON_THROW_ON_ERROR);
        if (empty($rehearsal['legacy_fields_unchanged']) || ! preg_match('/^bu_restore_[a-f0-9]{16}$/D', $rehearsal['staging_database'] ?? '')) throw new RuntimeException('Valid rehearsal evidence is required.');
        config(['database.connections.schema_reference' => array_merge($source, ['database' => $rehearsal['staging_database'], 'url' => null, 'name' => 'schema_reference'])]);
        $reference = DB::connection('schema_reference')->getSchemaBuilder();
        $local = DB::connection()->getSchemaBuilder();
        $normalize = static function (array $keys, string $database): array {
            foreach ($keys as &$key) if (($key['foreign_schema'] ?? null) === $database) $key['foreign_schema'] = '{self}';
            unset($key); return $keys;
        };
        foreach ($local->getTables() as $entry) {
            $table = $entry['name'];
            if (! str_starts_with($table, 'catalog_') && ! str_starts_with($table, 'tenant') && ! in_array($table, ['branches', 'locations', 'audit_logs', 'migration_batches', 'legacy_record_mappings', 'migration_exceptions'])) continue;
            if (DB::table($table)->count() !== 0 || ! $reference->hasTable($table) || $local->getColumns($table) !== $reference->getColumns($table) || $local->getIndexes($table) !== $reference->getIndexes($table) || $normalize($local->getForeignKeys($table), $source['database']) !== $normalize($reference->getForeignKeys($table), $rehearsal['staging_database'])) throw new RuntimeException('A partial new table requires explicit reconciliation before adoption: '.$table);
        }
    }
    $fingerprints = static function (): array {
        $result = [];
        foreach (array_merge(LegacyTables::BUSINESS, ['users', 'roles', 'permission']) as $table) {
            if (! Schema::hasTable($table)) continue;
            $hash = hash_init('sha256');
            foreach (DB::table($table)->orderBy('id')->cursor() as $row) {
                $data = (array) $row; unset($data['tenant_id']);
                hash_update($hash, json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n");
            }
            $result[$table] = ['count' => DB::table($table)->count(), 'sha256' => hash_final($hash)];
        }
        return $result;
    };
    $before = $fingerprints();
    foreach (['2026_10_19_100000_create_tenant_foundation.php', '2026_10_19_110000_create_universal_catalog.php'] as $file) {
        if (Artisan::call('migrate', ['--database' => $migrationConnection, '--path' => 'database/migrations/'.$file, '--force' => true]) !== 0) throw new RuntimeException('An additive migration failed: '.$file);
    }
    if (Artisan::call('legacy:bootstrap', ['--apply' => true, '--currency' => 'PKR']) !== 0) throw new RuntimeException('Ownership backfill failed.');
    if (Artisan::call('migrate', ['--database' => $migrationConnection, '--path' => 'database/migrations/2026_10_19_120000_enforce_legacy_tenant_ownership.php', '--force' => true]) !== 0) throw new RuntimeException('Ownership constraint migration failed.');
    $legacy = DB::table('tenants')->where('legacy_key', 'best-uniform')->value('id');
    $owner = DB::table('tenant_memberships as m')->join('tenant_roles as r', fn ($j) => $j->on('m.role_id', '=', 'r.id')->on('m.tenant_id', '=', 'r.tenant_id'))->where('m.tenant_id', $legacy)->where('m.status', 'active')->where('r.code', 'owner')->orderBy('m.id')->value('m.user_id');
    $location = DB::table('locations')->where('tenant_id', $legacy)->where('code', 'MAIN')->value('id');
    if (Artisan::call('legacy:map-catalog', ['--owner' => $owner, '--location' => $location, '--approve-distinct' => true]) !== 0) throw new RuntimeException('Catalog reconciliation failed.');
    $after = $fingerprints();
    if ($before !== $after) throw new RuntimeException('Legacy fields changed unexpectedly; retain backups and investigate before continuing.');
    $batch = DB::table('migration_batches')->where('tenant_id', $legacy)->where('batch_code', 'legacy-catalog-v1')->first();
    $report = ['mode' => $mode, 'verified_at' => date(DATE_ATOM), 'staging_database' => $mode === 'rehearsal' ? $database : null, 'legacy_fields_unchanged' => true, 'legacy_tenant_id' => $legacy,
        'legacy_baseline' => $after, 'mapping_reconciliation' => json_decode($batch->reconciliation_json, true), 'schema_migrations' => 3];
    file_put_contents(__DIR__.'/../docs/audit/private/phase-2-3-'.$mode.'.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'Phases 2–3 applied to '.$mode.'. All original legacy fields and counts are unchanged. Mapping: '.$batch->reconciliation_json.PHP_EOL;
} catch (Throwable $error) {
    file_put_contents(__DIR__.'/../docs/audit/private/phase-2-3-error.json', json_encode(['class' => $error::class, 'code' => $error->getCode(), 'driver_code' => $error instanceof \Illuminate\Database\QueryException ? ($error->errorInfo[1] ?? null) : null, 'detail' => $error->getPrevious()?->getMessage(), 'file' => $error->getFile(), 'line' => $error->getLine(), 'statement_kind' => $error instanceof \Illuminate\Database\QueryException ? strtok($error->getSql(), ' ') : null], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    fwrite(STDERR, 'Phase application failed ('.$error::class.'). '.($error instanceof RuntimeException && ! $error instanceof PDOException ? $error->getMessage() : 'Inspect the private logs and current migration status without exposing secrets.').PHP_EOL);
    exit(1);
}

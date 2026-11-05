<?php

// Read-only business-data fingerprints around the additive D4 migration.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$mode = $argv[1] ?? '';
if (! in_array($mode, ['before', 'after'], true)) {
    throw new RuntimeException('Use before or after.');
}
$directory = dirname(__DIR__, 2).'/docs/audit/private';
if (! is_dir($directory)) {
    mkdir($directory, 0770, true);
}
$baselineFile = $directory.'/dashboard-d4-before.json';
if ($mode === 'before' && is_file($baselineFile)) {
    throw new RuntimeException('A D4 baseline already exists; preserve it for comparison.');
}
$baseline = $mode === 'after' ? json_decode(file_get_contents($baselineFile), true, 512, JSON_THROW_ON_ERROR) : null;
$excluded = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens', 'operational_runs'];
$tables = $baseline ? array_keys($baseline['tables']) : array_values(array_filter(array_column(Schema::getTables(), 'name'), fn ($table) => ! in_array($table, $excluded, true)));
sort($tables);
if (DB::getDriverName() === 'mysql') {
    DB::statement('SET TRANSACTION READ ONLY');
}
$result = DB::transaction(function () use ($tables, $baseline) {
    $result = [];
    foreach ($tables as $table) {
        $columns = $baseline['tables'][$table]['columns'] ?? Schema::getColumnListing($table);
        $keys = array_values(array_filter(Schema::getIndexes($table), fn ($index) => $index['primary']));
        $query = DB::table($table)->select($columns);
        foreach (($keys[0]['columns'] ?? $columns) as $column) {
            $query->orderBy($column);
        }
        $hash = hash_init('sha256');
        $count = 0;
        foreach ($query->cursor() as $row) {
            hash_update($hash, serialize((array) $row)."\n");
            $count++;
        }
        $result[$table] = ['columns' => $columns, 'count' => $count, 'sha256' => hash_final($hash)];
    }

    return ['tables' => $result];
});
$result += ['checked_at' => now()->toIso8601String(), 'driver' => DB::getDriverName(), 'environment_checksum' => hash_file('sha256', dirname(__DIR__).'/.env')];
file_put_contents($directory.'/dashboard-d4-'.$mode.'.json', json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
if ($baseline) {
    $changes = [];
    foreach ($tables as $table) {
        if ($baseline['tables'][$table]['count'] !== $result['tables'][$table]['count'] || ! hash_equals($baseline['tables'][$table]['sha256'], $result['tables'][$table]['sha256'])) {
            $changes[] = $table;
        }
    }
    $report = ['checked_at' => $result['checked_at'], 'driver' => $result['driver'], 'migration' => '2026_11_03_170000_create_pos_held_carts',
        'original_tables_checked' => count($tables), 'business_rows_unchanged' => ! $changes, 'changed_tables' => $changes,
        'environment_unchanged' => hash_equals($baseline['environment_checksum'], $result['environment_checksum']),
        'existing_role_grants_unchanged' => ! array_intersect($changes, ['tenant_roles', 'tenant_role_permissions', 'tenant_memberships', 'roles', 'permission']),
        'platform_grants_created' => DB::table('platform_user_permissions')->count(), 'explicit_access_policies_created' => DB::table('tenant_member_access')->count()];
    file_put_contents(dirname($directory).'/dashboard-d4-migration.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    if ($changes || ! $report['environment_unchanged']) {
        throw new RuntimeException('Fingerprint differences require review; see the private evidence.');
    }
    echo 'D4 preservation check passed for '.count($tables).' original tables and the environment file.'.PHP_EOL;
} else {
    echo 'Saved read-only baseline for '.count($tables).' original tables.'.PHP_EOL;
}

<?php
// Local migration rehearsals never reuse the source connection name or Schema builder.
use App\Domain\Operations\OperationsInstaller;
use App\Domain\Tenancy\LegacyTables;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../POS backend/vendor/autoload.php';
$app = require __DIR__.'/../POS backend/bootstrap/app.php'; $app->make(Kernel::class)->bootstrap();
$evidenceRoot = __DIR__.'/../docs/audit/private/';
try {
    $mode = $argv[1] ?? ''; $source = DB::connection()->getConfig();
    if ($source['driver'] !== 'mysql' || ! in_array($source['host'], ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Only the local audited database is supported.');
    if ($mode === 'rehearsal') {
        $backup = $argv[2] ?? ''; if (! preg_match('/^backup_[a-f0-9]{32}$/D', $backup)) throw new RuntimeException('Supply a verified backup ID.');
        $verification = json_decode(file_get_contents($evidenceRoot.$backup.'/verification.json'), true, 512, JSON_THROW_ON_ERROR); $database = $verification['staging_database'];
        if (empty($verification['database_reconciled']) || ! preg_match('/^bu_restore_[a-f0-9]{16}$/D', $database) || $database === $source['database']) throw new RuntimeException('A verified isolated restore is required.');
        config(['database.connections.phase45' => array_merge($source, ['database' => $database, 'name' => 'phase45', 'url' => null]), 'database.default' => 'phase45']); DB::purge('phase45');
        if (DB::selectOne('SELECT DATABASE() AS db')->db !== $database || DB::connection()->getName() !== 'phase45') throw new RuntimeException('Unexpected rehearsal connection.');
    } elseif ($mode === 'apply-local' && ! app()->environment('production')) {
        $rehearsal = json_decode(file_get_contents($evidenceRoot.'phase-4-5-rehearsal.json'), true, 512, JSON_THROW_ON_ERROR);
        $concurrency = json_decode(file_get_contents($evidenceRoot.'phase-4-5-concurrency.json'), true, 512, JSON_THROW_ON_ERROR);
        if (empty($rehearsal['legacy_fields_unchanged']) || empty($concurrency['passed']) || ($concurrency['database'] ?? null) !== ($rehearsal['staging_database'] ?? null)) throw new RuntimeException('Successful migration and concurrency rehearsal is required first.');
    } else throw new RuntimeException('Use rehearsal <backup-id>, then apply-local.');
    Schema::swap(DB::connection()->getSchemaBuilder()); $connection = DB::getDefaultConnection();
    $fingerprints = static function (): array {
        $out = [];
        foreach (array_merge(LegacyTables::BUSINESS, ['users', 'roles', 'permission']) as $table) {
            if (! Schema::hasTable($table)) continue; $hash = hash_init('sha256');
            foreach (DB::table($table)->orderBy('id')->cursor() as $row) hash_update($hash, json_encode((array) $row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n");
            $out[$table] = ['count' => DB::table($table)->count(), 'sha256' => hash_final($hash)];
        }
        return $out;
    };
    $before = $fingerprints(); $envHash = hash_file('sha256', __DIR__.'/../POS backend/.env');
    if (Artisan::call('migrate', ['--database' => $connection, '--path' => 'database/migrations/2026_10_19_130000_create_inventory_purchasing_pos.php', '--force' => true]) !== 0) throw new RuntimeException('Migration failed: '.Artisan::output());
    $installed = app(OperationsInstaller::class)->run(); $after = $fingerprints();
    if ($before !== $after || $envHash !== hash_file('sha256', __DIR__.'/../POS backend/.env')) throw new RuntimeException('Source history or environment changed. Investigate before continuing.');
    $report = ['mode' => $mode, 'verified_at' => date(DATE_ATOM), 'staging_database' => $mode === 'rehearsal' ? $database : null, 'legacy_fields_unchanged' => true, 'environment_unchanged' => true, 'baseline' => $after, 'installation' => $installed, 'branches_activated' => 0];
    file_put_contents($evidenceRoot.'phase-4-5-'.$mode.'.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo 'Phases 4–5 migration and initialization complete for '.$mode.'. Original history and .env unchanged; branch activation awaits owner review.'.PHP_EOL;
} catch (Throwable $e) {
    file_put_contents($evidenceRoot.'phase-4-5-error.json', json_encode(['class' => $e::class, 'message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    fwrite(STDERR, 'Phase 4–5 application failed ('.$e::class.'). Inspect private phase-4-5-error.json.'.PHP_EOL); exit(1);
}

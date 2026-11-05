<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../POS backend/vendor/autoload.php';
$app = require __DIR__.'/../POS backend/bootstrap/app.php'; $app->make(Kernel::class)->bootstrap();
$stageEvidence = json_decode(file_get_contents(__DIR__.'/../docs/audit/private/phase-4-5-rehearsal.json'), true, 512, JSON_THROW_ON_ERROR);
$stageDatabase = $stageEvidence['staging_database'] ?? '';
$stageSource = DB::connection()->getConfig();
if (empty($stageEvidence['legacy_fields_unchanged']) || ! preg_match('/^bu_restore_[a-f0-9]{16}$/D', $stageDatabase) || $stageDatabase === $stageSource['database'] || ! in_array($stageSource['host'], ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Only the verified isolated rehearsal database may be used.');
config(['database.connections.phase45' => array_merge($stageSource, ['database' => $stageDatabase, 'name' => 'phase45', 'url' => null]), 'database.default' => 'phase45']); DB::purge('phase45');
if (DB::selectOne('SELECT DATABASE() AS db')->db !== $stageDatabase || DB::connection()->getName() !== 'phase45') throw new RuntimeException('Unexpected stage connection.');
Schema::swap(DB::connection()->getSchemaBuilder());

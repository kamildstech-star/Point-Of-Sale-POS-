<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class OperationsBackup extends Command
{
    protected $signature = 'operations:backup {--restore-drill}';

    protected $description = 'Run the configured local encrypted backup and optional isolated restore drill; operator CLI only';

    public function handle(): int
    {
        $id = DB::table('operational_runs')->insertGetId(['operation' => $this->option('restore-drill') ? 'backup_restore_drill' : 'backup', 'status' => 'running', 'started_at' => now()]);
        try {
            if (PHP_OS_FAMILY !== 'Windows') {
                throw new \RuntimeException('Configure a production backup driver; the current recovery driver uses Windows DPAPI.');
            }$script = realpath(base_path('../scripts/legacy-recovery.ps1'));
            $backup = new Process(['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $script, '-Action', 'backup'], base_path('..'));
            $backup->setTimeout(1800);
            $backup->mustRun();
            if (! preg_match('/backup_[a-f0-9]{32}/', $backup->getOutput(), $m)) {
                throw new \RuntimeException('Missing verified backup identifier.');
            }$backupId = $m[0];
            if ($this->option('restore-drill')) {
                $restore = new Process(['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $script, '-Action', 'restore', '-BackupId', $backupId], base_path('..'));
                $restore->setTimeout(1800);
                $restore->mustRun();
            }DB::table('operational_runs')->where('id', $id)->update(['status' => 'succeeded', 'backup_id' => $backupId, 'finished_at' => now()]);
            $this->info('Encrypted backup'.($this->option('restore-drill') ? ' and isolated restore drill' : '').' completed: '.$backupId);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::table('operational_runs')->where('id', $id)->update(['status' => 'failed', 'error_code' => class_basename($e), 'finished_at' => now()]);
            $this->error('Operational backup failed. Review private operator logs and backup configuration.');

            return self::FAILURE;
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResolveSupportCase extends Command
{
    protected $signature = 'support:resolve {reference} {--resolution=}';

    protected $description = 'Resolve a tenant support case with an audited operator note; no impersonation';

    public function handle(): int
    {
        $case = DB::table('support_cases')->where('reference', $this->argument('reference'))->first();
        $text = trim((string) $this->option('resolution'));
        if (! $case || $text === '' || mb_strlen($text) > 5000) {
            $this->error('Supply a valid case reference and resolution of 1–5000 characters.');

            return self::FAILURE;
        }DB::transaction(function () use ($case, $text) {
            DB::table('support_cases')->where('id', $case->id)->update(['status' => 'resolved', 'resolution' => $text, 'resolved_at' => now(), 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['tenant_id' => $case->tenant_id, 'actor_user_id' => null, 'action' => 'support.case.resolved', 'subject_type' => 'support_case', 'subject_id' => (string) $case->id, 'details_json' => json_encode(['operator_channel' => 'CLI']), 'occurred_at' => now()]);
        });
        $this->info('Case resolved and audited.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class InitializeAdvanced extends Command
{
    protected $signature = 'operations:initialize-advanced {--apply}';

    protected $description = 'Initialize phase 6/7 permissions and preserved tenant access without creating Stripe subscriptions';

    public function handle(): int
    {
        if (! $this->option('apply')) {
            $this->info('Use --apply after the phase 6/7 additive migrations. No Stripe charge or subscription is created.');

            return self::SUCCESS;
        }$this->line(json_encode(app(\App\Domain\Operations\AdvancedInstaller::class)->run(), JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}

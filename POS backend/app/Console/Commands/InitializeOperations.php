<?php

namespace App\Console\Commands;

use App\Domain\Operations\OperationsInstaller;
use Illuminate\Console\Command;

class InitializeOperations extends Command
{
    protected $signature = 'operations:initialize {--apply : Add new permissions and map safe legacy customers without activating branches}';

    protected $description = 'Initialize phase 4/5 metadata once per business; preserve legacy users, orders and stock';

    public function handle(OperationsInstaller $installer): int
    {
        if (! $this->option('apply')) {
            $this->info('Use --apply after the additive phase 4/5 migration. No branch will be activated.');

            return self::SUCCESS;
        }
        $this->line(json_encode($installer->run(), JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Domain\Tenancy\PlatformAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformPermissionCommand extends Command
{
    protected $signature = 'platform:permission {action : grant or revoke} {user : Existing user ID} {--permission=dashboard.platform.view} {--operator= : Operator identity for the access audit}';

    protected $description = 'Explicitly grant/revoke platform access; never changes tenant membership or permissions';

    public function handle(): int
    {
        $action = $this->argument('action');
        $user = $this->argument('user');
        $permission = $this->option('permission');
        $operator = trim((string) $this->option('operator'));
        if (! in_array($action, ['grant', 'revoke'], true) || ! ctype_digit((string) $user) || ! in_array($permission, PlatformAccess::PERMISSIONS, true) || ! $operator || mb_strlen($operator) > 100) {
            $this->error('Supply grant/revoke, an existing user ID, a supported permission and --operator (at most 100 characters).');

            return self::FAILURE;
        }
        if (! Schema::hasTable('platform_user_permissions') || ! DB::table('users')->where('id', $user)->exists()) {
            $this->error('Apply the D2 migration and select an existing user.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($action, $user, $permission, $operator) {
            $query = DB::table('platform_user_permissions')->where('user_id', $user)->where('permission_code', $permission);
            if ($action === 'grant') {
                $query->insertOrIgnore(['user_id' => $user, 'permission_code' => $permission, 'granted_at' => now()]);
            } else {
                $query->delete();
            }
            DB::table('platform_access_audits')->insert(['user_id' => $user, 'action' => $action, 'permission_code' => $permission, 'operator' => $operator, 'occurred_at' => now()]);
        });
        $this->info('Platform permission '.$action.' recorded. Tenant access is unchanged.');

        return self::SUCCESS;
    }
}

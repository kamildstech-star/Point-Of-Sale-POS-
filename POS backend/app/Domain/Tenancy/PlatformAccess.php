<?php

namespace App\Domain\Tenancy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PlatformAccess
{
    public const PERMISSIONS = ['dashboard.platform.view'];

    public function permissions(int $userId): array
    {
        if (! Schema::hasTable('platform_user_permissions')) {
            return [];
        }

        return DB::table('platform_user_permissions')->where('user_id', $userId)->whereIn('permission_code', self::PERMISSIONS)->orderBy('permission_code')->pluck('permission_code')->all();
    }
}

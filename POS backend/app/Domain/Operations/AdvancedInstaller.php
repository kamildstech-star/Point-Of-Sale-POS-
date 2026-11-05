<?php

namespace App\Domain\Operations;

use App\Domain\Billing\BillingService;
use App\Domain\Tenancy\TenantRoles;
use Illuminate\Support\Facades\DB;

class AdvancedInstaller
{
    public function run(): array
    {
        return DB::transaction(function () {
            app(BillingService::class)->seedPlans();
            $result = [];
            foreach (DB::table('tenants')->orderBy('id')->get() as $t) {
                DB::table('tenants')->where('id', $t->id)->lockForUpdate()->first();
                $done = DB::table('migration_batches')->where('tenant_id', $t->id)->where('batch_code', 'advanced-setup-v1')->exists();
                if (! $done) {
                    app(TenantRoles::class)->upgradeAdvanced($t->id);
                    app(BillingService::class)->initialize($t->id);
                    DB::table('migration_batches')->insert(['tenant_id' => $t->id, 'batch_code' => 'advanced-setup-v1', 'status' => 'reconciled', 'source_snapshot_at' => now(), 'reconciliation_json' => json_encode(['legacy_access' => 'preserved', 'stripe_subscriptions_created' => 0, 'live_charging' => false]), 'created_at' => now(), 'updated_at' => now()]);
                }$result[$t->id] = ['initialized' => true, 'existing_access_preserved' => true];
            }

return $result;
        });
    }
}

<?php

namespace App\Domain\Operations;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantRoles;
use Illuminate\Support\Facades\DB;

class OperationsInstaller
{
    public function run(): array
    {
        $result = [];
        foreach (DB::table('tenants')->whereIn('status', ['active', 'trialing'])->orderBy('id')->get() as $tenant) {
            $owner = DB::table('tenant_memberships as m')->join('tenant_roles as r', fn ($j) => $j->on('r.id', '=', 'm.role_id')->on('r.tenant_id', '=', 'm.tenant_id'))->where('m.tenant_id', $tenant->id)->where('m.status', 'active')->where('r.code', 'owner')->orderBy('m.id')->value('m.user_id');
            if (! $owner) {
                $result[$tenant->id] = ['status' => 'owner_required'];

                continue;
            }
            $result[$tenant->id] = app(TenantContext::class)->runFor($tenant->id, $owner, function () use ($tenant) {
                return app(SaleService::class)->atomic(function () use ($tenant) {
                    if (DB::table('migration_batches')->where('tenant_id', $tenant->id)->where('batch_code', 'operations-setup-v1')->exists()) {
                        return ['status' => 'already_initialized'];
                    }
                    app(TenantRoles::class)->upgradeOperations($tenant->id);
                    app(SaleService::class)->setup();
                    $customers = $tenant->legacy_key === 'best-uniform' ? app(LegacyCustomerMapper::class)->run() : [];
                    DB::table('migration_batches')->insert(['tenant_id' => $tenant->id, 'batch_code' => 'operations-setup-v1', 'status' => 'reconciled', 'source_snapshot_at' => now(), 'reconciliation_json' => json_encode(['customers' => $customers, 'branch_activation' => 'pending_owner_review']), 'created_at' => now(), 'updated_at' => now()]);

                    return ['status' => 'initialized', 'customers' => $customers, 'branch_activation' => 'pending_owner_review'];
                });
            });
        }

        return $result;
    }
}

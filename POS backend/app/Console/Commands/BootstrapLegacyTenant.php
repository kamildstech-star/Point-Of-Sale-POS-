<?php

namespace App\Console\Commands;

use App\Domain\Tenancy\LegacyTables;
use App\Domain\Tenancy\TenantService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BootstrapLegacyTenant extends Command
{
    protected $signature = 'legacy:bootstrap {--apply : Apply the verified ownership backfill} {--currency=PKR}';
    protected $description = 'Plan or idempotently assign existing legacy business records to Best Uniform';

    public function handle(): int
    {
        $owner = User::where('role_id', 1)->whereNotNull('password')->whereHas('role')->orderBy('id')->first();
        if (! $owner) { $this->error('A verified legacy role-1 owner with credentials is required.'); return self::FAILURE; }
        $counts = [];
        foreach (LegacyTables::BUSINESS as $table) if (Schema::hasTable($table)) $counts[$table] = DB::table($table)->whereNull('tenant_id')->count();
        $this->table(['Legacy table', 'Unassigned rows'], collect($counts)->map(fn ($count, $table) => [$table, $count])->values()->all());
        if (! $this->option('apply')) { $this->info('Plan only. No rows changed.'); return self::SUCCESS; }
        $currency = strtoupper($this->option('currency'));
        if (! preg_match('/^[A-Z]{3}$/D', $currency)) { $this->error('Use an ISO currency code.'); return self::FAILURE; }
        DB::transaction(function () use ($owner, $currency, &$tenantId) {
            $tenantId = DB::table('tenants')->where('legacy_key', 'best-uniform')->value('id');
            if (! $tenantId) $tenantId = app(TenantService::class)->create($owner, ['name' => 'Best Uniform', 'slug' => 'best-uniform', 'timezone' => 'Asia/Karachi', 'currency_code' => $currency], 'best-uniform');
            DB::table('tenants')->where('id', $tenantId)->lockForUpdate()->first();
            foreach (LegacyTables::BUSINESS as $table) {
                if (! Schema::hasTable($table)) continue;
                if (DB::table($table)->whereNotNull('tenant_id')->where('tenant_id', '<>', $tenantId)->exists()) throw new \RuntimeException('Mixed legacy ownership requires manual reconciliation.');
                DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
            }
            foreach (User::whereIn('role_id', [1, 2])->whereNotNull('password')->whereHas('role')->with('role')->get() as $staff) {
                if (DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('user_id', $staff->id)->exists()) continue;
                $permissions = json_decode($staff->role->permission ?? '[]', true);
                $permissions = is_array($permissions) ? $permissions : [];
                $code = 'legacy-role-'.$staff->role_id;
                $roleId = DB::table('tenant_roles')->where('tenant_id', $tenantId)->where('code', $staff->role_id === 1 ? 'owner' : $code)->value('id');
                if (! $roleId) {
                    $roleId = DB::table('tenant_roles')->insertGetId(['tenant_id' => $tenantId, 'code' => $code, 'name' => 'Legacy staff', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()]);
                    $mapped = [];
                    if (in_array('13', $permissions)) $mapped[] = 'dashboard.view';
                    if (in_array('2', $permissions)) $mapped = array_merge($mapped, ['products.view', 'products.manage']);
                    if (in_array('5', $permissions)) $mapped[] = 'legacy.view';
                    foreach ($mapped as $permission) DB::table('tenant_role_permissions')->insert(['tenant_id' => $tenantId, 'role_id' => $roleId, 'permission_code' => $permission]);
                }
                DB::table('tenant_memberships')->insert(['tenant_id' => $tenantId, 'user_id' => $staff->id, 'role_id' => $roleId, 'status' => 'active', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        $this->info('Legacy tenant and ownership backfill reconciled. Existing amounts and quantities were not changed.');
        return self::SUCCESS;
    }
}

<?php

namespace App\Domain\Tenancy;

use Illuminate\Support\Facades\DB;

class TenantRoles
{
    public const ADVANCED = ['sales.return', 'sales.return.approve', 'sales.refund', 'sales.void', 'cash.move', 'cash.close', 'expenses.view', 'expenses.manage', 'expenses.approve', 'reports.view', 'billing.manage', 'audit.view', 'imports.manage', 'exports.manage', 'alerts.view', 'support.manage'];

    public const OPERATIONS = ['inventory.view', 'inventory.adjust', 'inventory.approve', 'inventory.reserve', 'purchases.view', 'purchases.manage', 'purchases.approve', 'purchases.settle', 'customers.view', 'customers.manage', 'sales.view', 'sales.create', 'sales.discount', 'payments.record', 'cash.open', 'cash.manage'];

    public const PERMISSIONS = ['dashboard.view', 'products.view', 'products.manage', 'team.view', 'team.manage', 'settings.manage', 'legacy.view', 'legacy.manage', ...self::OPERATIONS, ...self::ADVANCED];

    public function advancedFor(string $role): array
    {
        return match ($role) {
            'owner' => self::ADVANCED,'admin','manager' => array_values(array_diff(self::ADVANCED, ['billing.manage'])),'cashier' => ['sales.return', 'cash.close', 'expenses.view', 'alerts.view'],'accountant' => ['expenses.view', 'expenses.manage', 'expenses.approve', 'reports.view', 'audit.view', 'exports.manage', 'alerts.view', 'support.manage'],'inventory_clerk' => ['imports.manage', 'alerts.view'],default => []
        };
    }

    public function upgradeAdvanced(int $tenant): void
    {
        foreach (self::ADVANCED as $c) {
            DB::table('tenant_permissions')->insertOrIgnore(['code' => $c, 'description' => $c]);
        }foreach (DB::table('tenant_roles')->where('tenant_id', $tenant)->where('is_system', true)->get() as $r) {
            foreach ($this->advancedFor($r->code) as $p) {
                DB::table('tenant_role_permissions')->insertOrIgnore(['tenant_id' => $tenant, 'role_id' => $r->id, 'permission_code' => $p]);
            }
        }
    }

    private function operationsFor(string $role): array
    {
        return match ($role) {
            'owner', 'admin' => self::OPERATIONS,
            'manager' => array_values(array_diff(self::OPERATIONS, ['purchases.settle'])),
            'cashier' => ['inventory.view', 'customers.view', 'customers.manage', 'sales.view', 'sales.create', 'payments.record', 'cash.open'],
            'inventory_clerk' => ['inventory.view', 'inventory.adjust', 'inventory.reserve', 'purchases.view', 'purchases.manage'],
            'accountant' => ['inventory.view', 'purchases.view', 'purchases.settle', 'customers.view', 'sales.view'],
            default => ['inventory.view'],
        };
    }

    /** Explicit one-time upgrade grants only the new keys; old/custom grants survive. */
    public function upgradeOperations(int $tenantId): void
    {
        foreach (self::OPERATIONS as $code) {
            DB::table('tenant_permissions')->insertOrIgnore(['code' => $code, 'description' => $code]);
        }
        foreach (DB::table('tenant_roles')->where('tenant_id', $tenantId)->where('is_system', true)->get() as $role) {
            foreach ($this->operationsFor($role->code) as $permission) {
                DB::table('tenant_role_permissions')->insertOrIgnore(['tenant_id' => $tenantId, 'role_id' => $role->id, 'permission_code' => $permission]);
            }
        }
    }

    public function seed(int $tenantId): void
    {
        foreach (self::PERMISSIONS as $code) {
            DB::table('tenant_permissions')->insertOrIgnore(['code' => $code, 'description' => $code]);
        }
        $roles = [
            'owner' => self::PERMISSIONS,
            'admin' => array_values(array_diff(self::PERMISSIONS, ['legacy.manage', 'billing.manage'])),
            'manager' => ['dashboard.view', 'products.view', 'products.manage', 'team.view'],
            'cashier' => ['dashboard.view', 'products.view'],
            'inventory_clerk' => ['dashboard.view', 'products.view', 'products.manage'],
            'accountant' => ['dashboard.view', 'products.view'],
            'viewer' => ['dashboard.view', 'products.view'],
        ];
        foreach ($roles as $code => $permissions) {
            $role = DB::table('tenant_roles')->where('tenant_id', $tenantId)->where('code', $code)->first();
            if ($role) {
                continue;
            } // Never overwrite established permission changes.
            $id = DB::table('tenant_roles')->insertGetId(['tenant_id' => $tenantId, 'code' => $code, 'name' => ucwords(str_replace('_', ' ', $code)), 'is_system' => true, 'created_at' => now(), 'updated_at' => now()]);
            foreach (array_unique([...$permissions, ...$this->operationsFor($code), ...$this->advancedFor($code)]) as $permission) {
                DB::table('tenant_role_permissions')->insert(['tenant_id' => $tenantId, 'role_id' => $id, 'permission_code' => $permission]);
            }
        }
    }
}

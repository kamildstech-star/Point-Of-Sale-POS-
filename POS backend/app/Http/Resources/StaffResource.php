<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->role;
        $legacyPermissions = json_decode($role?->permission ?? '[]', true);
        $legacyPermissions = is_array($legacyPermissions) ? array_map('strval', $legacyPermissions) : [];

        $tenant = null;
        $permissions = in_array('13', $legacyPermissions, true) ? ['dashboard.view'] : [];
        $active = $request->session()->get('active_tenant_id');
        if ($active && \Illuminate\Support\Facades\Schema::hasTable('tenant_memberships')) {
            try {
                [$tenant, $permissions, $role] = app(\App\Domain\Tenancy\TenantContext::class)->runFor((int) $active, $this->id, function ($context) {
                    $tenant = $context->tenant();
                    return [ ['id' => $tenant->id, 'name' => $tenant->name, 'currency_code' => $tenant->currency_code, 'timezone' => $tenant->timezone, 'status' => $tenant->status, 'is_legacy' => $tenant->legacy_key !== null],
                        $context->permissions(), \Illuminate\Support\Facades\DB::table('tenant_roles')->where('tenant_id', $tenant->id)->where('id', $context->membership()->role_id)->first() ];
                });
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { $request->session()->forget('active_tenant_id'); $permissions = []; }
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $role ? ['id' => $role->id, 'name' => $role->name ?? $role->role, 'code' => $role->code ?? null] : null,
            // Only the shell permission is mapped. Future business APIs require
            // tenant-aware authorization, introduced in phase 2.
            'permissions' => $permissions,
            'active_tenant' => $tenant,
        ];
    }
}

<?php

namespace App\Domain\Tenancy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantContext
{
    private ?object $tenant = null;
    private ?object $membership = null;
    private ?array $permissionSnapshot = null;
    private array $roleSnapshot = [];
    private array $access = ['branch_scope' => 'all', 'sales_scope' => 'all'];
    private bool $accessSchema = false;
    private ?array $branchSnapshot = null;

    public function resolve(int $tenantId, int $userId): void
    {
        $this->clear();
        $membership = DB::table('tenant_memberships')->where('tenant_id', $tenantId)->where('user_id', $userId)->where('status', 'active')->first();
        abort_unless($membership, 403);
        $tenant = DB::table('tenants')->where('id', $tenantId)->first();
        abort_unless($tenant && $tenant->status !== 'archived', 403);
        $this->tenant = $tenant; $this->membership = $membership;
        $this->accessSchema = Schema::hasTable('tenant_member_access');
        $roleIds = [$membership->role_id];
        if ($this->accessSchema) {
            $access = DB::table('tenant_member_access')->where('tenant_id', $tenantId)->where('membership_id', $membership->id)->first();
            if ($access) $this->access = ['branch_scope' => $access->branch_scope, 'sales_scope' => $access->sales_scope];
            $roleIds = [...$roleIds, ...DB::table('tenant_member_roles')->where('tenant_id', $tenantId)->where('membership_id', $membership->id)->pluck('role_id')->all()];
        }
        $this->roleSnapshot = DB::table('tenant_roles')->where('tenant_id', $tenantId)->whereIn('id', $roleIds)->orderBy('id')->get(['id', 'code', 'name'])->all();
    }

    public function id(): int { abort_unless($this->tenant, 409, 'Select a business first.'); return $this->tenant->id; }
    public function tenant(): ?object { return $this->tenant; }
    public function membership(): ?object { return $this->membership; }
    public function clear(): void { $this->tenant = null; $this->membership = null; $this->permissionSnapshot = null; $this->roleSnapshot = []; $this->access = ['branch_scope' => 'all', 'sales_scope' => 'all']; $this->accessSchema = false; $this->branchSnapshot = null; }

    public function permissions(): array
    {
        if (! $this->membership) return [];
        return $this->permissionSnapshot ??= DB::table('tenant_role_permissions')->where('tenant_id', $this->id())->whereIn('role_id', array_column($this->roleSnapshot, 'id'))->distinct()->orderBy('permission_code')->pluck('permission_code')->all();
    }

    public function allows(string $permission): bool { return in_array($permission, $this->permissions(), true); }
    public function canViewCosts(): bool { return (bool) array_intersect($this->permissions(), ['products.manage', 'purchases.view', 'reports.view']); }
    public function isOwner(): bool
    {
        return $this->membership && DB::table('tenant_roles')->where('tenant_id', $this->id())->where('id', $this->membership->role_id)->where('code', 'owner')->exists();
    }

    public function cacheKey(string $key): string { return 'tenant:'.$this->id().':'.$key; }
    public function roles(): array { return $this->roleSnapshot; }
    public function hasAccessSchema(): bool { return $this->accessSchema; }
    // Unknown policy values fail closed instead of widening access.
    public function branchRestricted(): bool { return $this->access['branch_scope'] !== 'all'; }
    public function ownSalesOnly(): bool { return $this->access['sales_scope'] !== 'all'; }
    public function restricted(): bool { return $this->branchRestricted() || $this->ownSalesOnly(); }
    public function allowedBranchIds(): array
    {
        if ($this->branchSnapshot !== null) return $this->branchSnapshot;
        $query = DB::table('branches')->where('tenant_id', $this->id());
        if ($this->branchRestricted()) $query->whereIn('id', DB::table('tenant_member_branches')->where('tenant_id', $this->id())->where('membership_id', $this->membership->id)->select('branch_id'));
        return $this->branchSnapshot = $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
    public function accessFingerprint(): string
    {
        return hash('sha256', json_encode([$this->id(), $this->membership->id, $this->membership->role_id, $this->access, $this->allowedBranchIds(), $this->permissions()], JSON_THROW_ON_ERROR));
    }
    public function revalidate(): void
    {
        $before = $this->accessFingerprint();
        $tenant = $this->id(); $user = $this->membership->user_id;
        $this->resolve($tenant, $user);
        abort_unless(hash_equals($before, $this->accessFingerprint()), 403);
    }
    public function storagePath(string $file): string
    {
        abort_if(str_contains($file, '..') || str_contains($file, '\\') || str_starts_with($file, '/'), 422);
        return 'tenants/'.$this->id().'/'.$file;
    }

    public function runFor(int $tenantId, int $userId, callable $action): mixed
    {
        $previous = [$this->tenant, $this->membership, $this->permissionSnapshot, $this->roleSnapshot, $this->access, $this->accessSchema, $this->branchSnapshot];
        try { $this->resolve($tenantId, $userId); return $action($this); }
        finally { [$this->tenant, $this->membership, $this->permissionSnapshot, $this->roleSnapshot, $this->access, $this->accessSchema, $this->branchSnapshot] = $previous; }
    }
}

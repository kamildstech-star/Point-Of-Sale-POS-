<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\TenantQueries;
use App\Domain\Tenancy\TenantRoles;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    use TenantQueries;

    private function assertGrantable(int $roleId): void
    {
        $role = $this->row('tenant_roles', $roleId);
        abort_if($role->code === 'owner' && ! $this->context()->isOwner(), 403);
        foreach ($this->query('tenant_role_permissions')->where('role_id', $roleId)->pluck('permission_code') as $permission) abort_unless($this->context()->allows($permission), 403);
    }

    public function index()
    {
        $this->permit('team.view');

        return response()->json(['data' => $this->query('tenant_memberships')->get()->map(function ($m) {
            $user = User::find($m->user_id);
            $role = $this->row('tenant_roles', $m->role_id);

            return ['id' => $m->id, 'name' => $user->name, 'email' => $user->email, 'role_id' => $role->id, 'role' => $role->name, 'status' => $m->status];
        })]);
    }

    public function store(Request $request)
    {
        $this->permit('team.manage');
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:12', 'max:255'], 'role_id' => ['required', 'integer', $this->exists('tenant_roles')]]);
        $existing = User::whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])->first();
        if ($existing && ! $existing->isStaff()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'This address belongs to a legacy customer identity and requires reviewed separation.']);
        }
        if (! $existing && empty($data['password'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['password' => 'An initial password is required for a new staff account.']);
        }
        $role = $this->row('tenant_roles', $data['role_id']);
        $this->assertGrantable((int) $data['role_id']);
        abort_if($role->code === 'owner' && ! $this->context()->isOwner(), 403);
        $id = DB::transaction(function () use ($data, $existing) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->context()->revalidate();
            $this->assertGrantable((int) $data['role_id']);
            if ($existing && $this->query('tenant_memberships')->where('user_id', $existing->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'This person already belongs to this business. Edit their existing access instead.']);
            }
            app(\App\Domain\Billing\TenantAccess::class)->capacity('staff');
            // Grant access to an existing staff identity without changing its
            // shared profile/password or any memberships in other businesses.
            $user = $existing ?? User::create(['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'password' => $data['password'], 'role_id' => null]);
            $id = DB::table('tenant_memberships')->insertGetId(['tenant_id' => $this->context()->id(), 'user_id' => $user->id, 'role_id' => $data['role_id'], 'status' => 'active', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $this->audit('team.member.created', 'membership', $id);

            return $id;
        });

        return response()->json(['data' => ['id' => $id]], 201);
    }

    public function update(Request $request, string $member)
    {
        $this->permit('team.manage');
        $this->row('tenant_memberships', $member);
        $data = $request->validate(['role_id' => ['required', 'integer', $this->exists('tenant_roles')], 'status' => ['required', Rule::in(['active', 'suspended'])]]);
        $this->assertGrantable((int) $data['role_id']);
        DB::transaction(function () use ($member, $data) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->context()->revalidate();
            $this->assertGrantable((int) $data['role_id']);
            $record = $this->row('tenant_memberships', $member);
            if ($record->status !== 'active' && $data['status'] === 'active') {
                app(\App\Domain\Billing\TenantAccess::class)->capacity('staff');
            }
            $oldRole = $this->row('tenant_roles', $record->role_id);
            $newRole = $this->row('tenant_roles', $data['role_id']);
            abort_if(($oldRole->code === 'owner' || $newRole->code === 'owner') && ! $this->context()->isOwner(), 403);
            if ($oldRole->code === 'owner' && ($data['status'] !== 'active' || $newRole->code !== 'owner')) {
                $owners = $this->query('tenant_memberships')->where('role_id', $record->role_id)->where('status', 'active')->count();
                abort_if($owners <= 1, 409, 'The last active owner cannot be removed.');
            }
            $this->query('tenant_memberships')->where('id', $member)->update($data + ['updated_at' => now()]);
            $this->audit('team.member.updated', 'membership', $member, $data);
        });

        return response()->json(['data' => ['id' => (int) $member]]);
    }

    public function destroy(string $member)
    {
        $this->permit('team.manage');
        DB::transaction(function () use ($member) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->context()->revalidate();
            $record = $this->row('tenant_memberships', $member);
            $role = $this->row('tenant_roles', $record->role_id);
            abort_if($role->code === 'owner' && ! $this->context()->isOwner(), 403);
            if ($record->status === 'active' && $role->code === 'owner') {
                abort_if($this->query('tenant_memberships')->where('role_id', $role->id)->where('status', 'active')->count() <= 1, 409, 'The last active owner cannot be removed.');
            }
            if ($record->status === 'active') {
                // Memberships are historical actors. Revoking access must not
                // delete the shared identity or memberships in other tenants.
                $this->query('tenant_memberships')->where('id', $member)->update(['status' => 'suspended', 'updated_at' => now()]);
                $this->audit('team.member.access_removed', 'membership', $member);
            }
        });

        return response()->noContent();
    }

    public function destroyRole(string $role)
    {
        $this->permit('team.manage');
        DB::transaction(function () use ($role) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->context()->revalidate();
            abort_if($this->row('tenant_roles', $role)->is_system, 403, 'System roles cannot be deleted.');
            abort_if($this->query('tenant_memberships')->where('role_id', $role)->exists(), 409, 'Reassign all members, including suspended members, before deleting this role.');
            if ($this->context()->hasAccessSchema()) abort_if($this->query('tenant_member_roles')->where('role_id', $role)->exists(), 409, 'Remove additional role assignments before deleting this role.');
            $this->query('tenant_role_permissions')->where('role_id', $role)->delete();
            $this->query('tenant_roles')->where('id', $role)->delete();
            $this->audit('team.role.deleted', 'role', $role);
        });

        return response()->noContent();
    }

    public function roles()
    {
        $this->permit('team.view');

        return response()->json(['data' => $this->query('tenant_roles')->get()->map(function ($role) {
            $role->permissions = $this->query('tenant_role_permissions')->where('role_id', $role->id)->pluck('permission_code');

            return $role;
        }), 'meta' => ['permission_codes' => TenantRoles::PERMISSIONS]]);
    }

    public function saveRole(Request $request, ?string $role = null)
    {
        $this->permit('team.manage');
        if ($role) {
            abort_if($this->row('tenant_roles', $role)->is_system, 403);
        }
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'regex:/^custom-[a-z0-9-]+$/D', 'max:80', $this->unique('tenant_roles', 'code', $role)],
            'permissions' => ['present', 'array', 'max:'.count(TenantRoles::PERMISSIONS)], 'permissions.*' => ['string', 'distinct', Rule::in(TenantRoles::PERMISSIONS)]]);
        foreach ($data['permissions'] as $permission) {
            abort_unless($this->context()->allows($permission), 403);
        }
        foreach (['products.manage' => 'products.view', 'team.manage' => 'team.view', 'settings.manage' => 'products.view', 'legacy.manage' => 'legacy.view', 'inventory.adjust' => 'inventory.view', 'inventory.approve' => 'inventory.view', 'inventory.reserve' => 'inventory.view', 'purchases.manage' => 'purchases.view', 'purchases.approve' => 'purchases.view', 'purchases.settle' => 'purchases.view', 'customers.manage' => 'customers.view', 'sales.create' => 'sales.view', 'sales.discount' => 'sales.create', 'payments.record' => 'sales.view', 'cash.manage' => 'cash.open'] as $manage => $read) {
            if (in_array($manage, $data['permissions'], true) && ! in_array($read, $data['permissions'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['permissions' => $manage.' also requires '.$read.'.']);
            }
        }
        $id = DB::transaction(function () use ($role, $data) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->context()->revalidate();
            foreach ($data['permissions'] as $permission) abort_unless($this->context()->allows($permission), 403);
            $attributes = ['name' => $data['name'], 'code' => $data['code'], 'updated_at' => now()];
            $id = $role ?: DB::table('tenant_roles')->insertGetId($attributes + ['tenant_id' => $this->context()->id(), 'is_system' => false, 'created_at' => now()]);
            if ($role) {
                $this->query('tenant_roles')->where('id', $id)->update($attributes);
            }
            $this->query('tenant_role_permissions')->where('role_id', $id)->delete();
            foreach (array_unique($data['permissions']) as $permission) {
                DB::table('tenant_role_permissions')->insert(['tenant_id' => $this->context()->id(), 'role_id' => $id, 'permission_code' => $permission]);
            }
            $this->audit('team.role.saved', 'role', $id);

            return $id;
        });

        return response()->json(['data' => ['id' => (int) $id]], $role ? 200 : 201);
    }
}

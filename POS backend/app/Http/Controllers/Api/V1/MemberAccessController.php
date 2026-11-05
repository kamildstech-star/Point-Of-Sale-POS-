<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MemberAccessController extends Controller
{
    use TenantQueries;

    public function show(int $member)
    {
        $this->permit('team.manage');
        abort_unless(! $this->context()->restricted() && $this->context()->hasAccessSchema(), 403);
        $this->row('tenant_memberships', $member);
        $policy = $this->query('tenant_member_access')->where('membership_id', $member)->first();

        return response()->json(['data' => ['membership_id' => $member, 'branch_scope' => $policy->branch_scope ?? 'all', 'sales_scope' => $policy->sales_scope ?? 'all',
            'branch_ids' => $this->query('tenant_member_branches')->where('membership_id', $member)->pluck('branch_id'),
            'additional_role_ids' => $this->query('tenant_member_roles')->where('membership_id', $member)->pluck('role_id')]]);
    }

    public function update(Request $request, int $member)
    {
        $this->permit('team.manage');
        abort_unless(! $this->context()->restricted() && $this->context()->hasAccessSchema(), 403);
        $this->row('tenant_memberships', $member);
        $data = $request->validate(['branch_scope' => ['required', Rule::in(['all', 'assigned'])], 'sales_scope' => ['required', Rule::in(['all', 'own'])],
            'branch_ids' => ['present', 'array', 'max:500'], 'branch_ids.*' => ['integer', 'distinct', $this->exists('branches')],
            'additional_role_ids' => ['present', 'array', 'max:10'], 'additional_role_ids.*' => ['integer', 'distinct', $this->exists('tenant_roles')]]);
        if ($data['branch_scope'] === 'all' && $data['branch_ids']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['branch_ids' => 'Choose assigned scope before assigning branches.']);
        }
        DB::transaction(function () use ($data, $member) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $this->context()->revalidate();
            $record = $this->row('tenant_memberships', $member);
            abort_if($this->row('tenant_roles', $record->role_id)->code === 'owner', 403, 'Owner restrictions require a separate ownership transfer.');
            foreach ($data['additional_role_ids'] as $roleId) {
                $role = $this->row('tenant_roles', $roleId);
                abort_if($role->code === 'owner', 403);
                foreach ($this->query('tenant_role_permissions')->where('role_id', $roleId)->pluck('permission_code') as $permission) {
                    abort_unless($this->context()->allows($permission), 403);
                }
            }
            $existing = $this->query('tenant_member_access')->where('membership_id', $member)->first();
            $this->query('tenant_member_access')->updateOrInsert(['tenant_id' => $this->context()->id(), 'membership_id' => $member],
                ['branch_scope' => $data['branch_scope'], 'sales_scope' => $data['sales_scope'], 'created_at' => $existing->created_at ?? now(), 'updated_at' => now()]);
            foreach (['branches' => ['branch', $data['branch_ids']], 'roles' => ['role', array_diff($data['additional_role_ids'], [$record->role_id])]] as $table => [$column, $ids]) {
                $this->query('tenant_member_'.$table)->where('membership_id', $member)->delete();
                foreach ($ids as $id) {
                    DB::table('tenant_member_'.$table)->insert(['tenant_id' => $this->context()->id(), 'membership_id' => $member, $column.'_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            $this->audit('team.member.access_updated', 'membership', $member, $data);
        });

        return $this->show($member);
    }
}

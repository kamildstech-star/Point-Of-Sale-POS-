<?php

namespace App\Domain\Tenancy;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function create(User $owner, array $data, ?string $legacyKey = null): int
    {
        return DB::transaction(function () use ($owner, $data, $legacyKey) {
            $id = DB::table('tenants')->insertGetId([
                'name' => $data['name'], 'slug' => $data['slug'], 'timezone' => $data['timezone'], 'currency_code' => $data['currency_code'],
                'legacy_key' => $legacyKey, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
            app(TenantRoles::class)->seed($id);
            $role = DB::table('tenant_roles')->where('tenant_id', $id)->where('code', 'owner')->value('id');
            DB::table('tenant_memberships')->insert(['tenant_id' => $id, 'user_id' => $owner->id, 'role_id' => $role, 'status' => 'active', 'joined_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $branch = DB::table('branches')->insertGetId(['tenant_id' => $id, 'code' => 'MAIN', 'name' => 'Main branch', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('locations')->insert(['tenant_id' => $id, 'branch_id' => $branch, 'code' => 'MAIN', 'name' => 'Main stock location', 'type' => 'store', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
            if (\Illuminate\Support\Facades\Schema::hasTable('tenant_subscriptions')) {
                app(\App\Domain\Billing\BillingService::class)->initialize($id);
                DB::table('tenant_subscriptions')->where('tenant_id', $id)->update(['grandfathered' => false, 'status' => 'incomplete']);
            }
            DB::table('audit_logs')->insert(['tenant_id' => $id, 'actor_user_id' => $owner->id, 'action' => 'tenant.created', 'subject_type' => 'tenant', 'subject_id' => (string) $id, 'occurred_at' => now()]);

            return $id;
        });
    }

    public function audit(string $action, string $type, int|string|null $id, array $details = []): void
    {
        $context = app(TenantContext::class);
        DB::table('audit_logs')->insert(['tenant_id' => $context->id(), 'actor_user_id' => $context->membership()->user_id,
            'action' => $action, 'subject_type' => $type, 'subject_id' => $id === null ? null : (string) $id,
            'details_json' => json_encode($details, JSON_THROW_ON_ERROR), 'occurred_at' => now()]);
    }
}

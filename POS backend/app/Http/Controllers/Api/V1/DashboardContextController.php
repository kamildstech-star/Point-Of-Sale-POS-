<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Dashboard\DashboardCapabilities;
use App\Domain\Tenancy\PlatformAccess;
use App\Domain\Tenancy\RecordScope;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardContextController extends Controller
{
    public function show(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);
        $memberships = DB::table('tenant_memberships as m')->join('tenants as t', 't.id', '=', 'm.tenant_id')
            ->where('m.user_id', $request->user()->id)->where('m.status', 'active')->where('t.status', '<>', 'archived')
            ->orderBy('t.id')->get(['t.id', 't.name', 't.currency_code', 't.timezone']);
        $tenant = null;
        $active = $request->session()->get('active_tenant_id');
        if ($active !== null) {
            abort_unless(filter_var($active, FILTER_VALIDATE_INT) !== false, 403);
            $tenant = app(TenantContext::class)->runFor((int) $active, $request->user()->id, function ($context) {
                $t = $context->tenant();

                return ['id' => $t->id, 'name' => $t->name, 'currency' => $t->currency_code, 'timezone' => $t->timezone,
                    'membership_id' => $context->membership()->id, 'access_fingerprint' => $context->accessFingerprint(), 'roles' => $context->roles(), 'permissions' => $context->permissions(),
                    'branch_scope' => $context->branchRestricted() ? 'assigned' : 'all', 'sales_scope' => $context->ownSalesOnly() ? 'own' : 'all',
                    'allowed_branches' => (new RecordScope($context))->query('branches')->orderBy('id')->get(['id', 'code', 'name', 'status']),
                    'dashboard' => (new DashboardCapabilities($context))->toArray()];
            });
        }

        return response()->json(['data' => ['user' => ['id' => $request->user()->id, 'name' => $request->user()->name],
            'platform_permissions' => app(PlatformAccess::class)->permissions($request->user()->id), 'memberships' => $memberships,
            'active_tenant' => $tenant, 'dashboard_access_schema_ready' => Schema::hasTable('tenant_member_access')]])->header('Cache-Control', 'private, no-store');
    }

    public function platform(Request $request)
    {
        return response()->json(['data' => ['user_id' => $request->user()->id, 'permissions' => app(PlatformAccess::class)->permissions($request->user()->id), 'scope' => 'platform', 'dashboard_enabled' => (bool) config('dashboard.enabled') && (bool) config('dashboard.platform_enabled')]])->header('Cache-Control', 'private, no-store');
    }
}

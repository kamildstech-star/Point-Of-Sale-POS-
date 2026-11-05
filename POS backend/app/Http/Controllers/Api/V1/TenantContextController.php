<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantService;
use App\Http\Resources\StaffResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TenantContextController extends Controller
{
    public function index(Request $request)
    {
        $data = Schema::hasTable('tenants') ? DB::table('tenants')->join('tenant_memberships as m', 'm.tenant_id', '=', 'tenants.id')
            ->where('m.user_id', $request->user()->id)->where('m.status', 'active')->where('tenants.status', '<>', 'archived')
            ->orderBy('tenants.name')->get(['tenants.id', 'tenants.name', 'tenants.currency_code', 'tenants.status']) : [];
        return response()->json(['data' => $data, 'meta' => ['active_tenant' => $request->session()->get('active_tenant_id'), 'onboarding_available' => Schema::hasTable('tenants')]]);
    }

    public function switch(Request $request, ?string $tenant = null)
    {
        abort_unless(Schema::hasTable('tenants'), 409);
        $id = $tenant ?? $request->input('tenant_id');
        abort_unless(filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0, 404);
        $context = app(TenantContext::class);
        return DB::transaction(function () use ($request, $id, $context) {
            $membership = DB::table('tenant_memberships')->where('tenant_id', $id)->where('user_id', $request->user()->id)->where('status', 'active')->lockForUpdate()->first();
            abort_unless($membership, 404);
            try {
                $context->resolve((int) $id, $request->user()->id);
                $request->session()->put('active_tenant_id', (int) $id);
                $request->session()->regenerate(); $request->session()->regenerateToken();
                app(TenantService::class)->audit('tenant.activated', 'tenant', $id);
                return new StaffResource($request->user());
            } finally { $context->clear(); }
        });
    }

    public static function businessRules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('tenants', 'slug')],
            'timezone' => ['required', 'timezone'], 'currency_code' => ['required', 'string', 'regex:/^[A-Z]{3}$/D']];
    }

    public function store(Request $request)
    {
        $data = $request->validate(self::businessRules());
        $tenantId = app(TenantService::class)->create($request->user(), $data);
        $request->session()->put('active_tenant_id', $tenantId);
        $request->session()->regenerate(); $request->session()->regenerateToken();
        return (new StaffResource($request->user()))->response()->setStatusCode(201);
    }

    public function context()
    {
        $context = app(TenantContext::class);
        return response()->json(['data' => ['tenant' => $context->tenant(), 'permissions' => $context->permissions(),
            'branches' => (new \App\Domain\Tenancy\RecordScope($context))->query('branches')->get(),
            'locations' => (new \App\Domain\Tenancy\RecordScope($context))->query('locations')->get()]]);
    }
}

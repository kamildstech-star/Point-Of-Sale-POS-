<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $context = app(TenantContext::class);
        try {
            $id = $request->session()->get('active_tenant_id');
            abort_unless(is_numeric($id), 409, 'Select a business first.');
            $context->resolve((int) $id, $request->user()->id);
            // These records/settings are tenant-wide and have no branch attribution.
            // Fail closed for explicitly restricted staff; their scoped dashboard,
            // transactions, inventory, purchasing and newly scoped exports remain usable.
            if ($context->restricted()) {
                abort_if($request->is('api/v1/team*', 'api/v1/roles*', 'api/v1/legacy*', 'api/v1/billing*', 'api/v1/pos/branches/*'), 403);
                abort_if($request->is('api/v1/workspace/*') && ! $request->is('api/v1/workspace/exports*'), 403);
                abort_if(! $request->isMethodSafe() && $request->is('api/v1/catalog/*', 'api/v1/products*', 'api/v1/variants*', 'api/v1/images*', 'api/v1/operations/registers*', 'api/v1/operations/payment-methods*'), 403);
                abort_if($context->ownSalesOnly() && $request->is('api/v1/reports/*'), 403);
            }
            $request->attributes->set('tenant_id', $context->id());
            if (! $request->isMethodSafe() && ! $request->is('api/v1/billing/*') && ! $request->is('api/v1/workspace/exports*') && ! $request->is('api/v1/workspace/support*')) {
                abort_unless(in_array($context->tenant()->status, ['active', 'trialing'], true), 403);
            }

            return $next($request);
        } finally {
            $context->clear();
        }
    }
}

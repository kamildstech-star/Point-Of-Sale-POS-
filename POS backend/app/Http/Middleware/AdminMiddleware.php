<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((Auth::check() && Auth::user()->role_id == 1) || Auth::check() && Auth::user()->role_id == 2) {
            // Blade records have no trustworthy branch attribution. Explicitly
            // restricted memberships must use the scoped React/API workflows.
            if (\Illuminate\Support\Facades\Schema::hasTable('tenant_member_access')) {
                $tenant = \Illuminate\Support\Facades\DB::table('tenants')->where('legacy_key', 'best-uniform')->value('id');
                abort_unless($tenant, 403);

                return app(\App\Domain\Tenancy\TenantContext::class)->runFor($tenant, Auth::id(), function ($context) use ($request, $next) {
                    abort_if($context->restricted(), 403);

                    return $next($request);
                });
            }

            return $next($request);
        }

        return redirect()->route('login');
    }
}

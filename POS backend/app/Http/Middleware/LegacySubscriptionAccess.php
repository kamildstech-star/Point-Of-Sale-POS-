<?php

namespace App\Http\Middleware;

use App\Domain\Billing\TenantAccess;
use App\Domain\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacySubscriptionAccess
{
    public function handle(Request $r, Closure $next)
    {
        $name = $r->route()?->getName() ?? '';
        $write = ! $r->isMethodSafe() || preg_match('/\.(destroy|destory|delete|cancel)$/', $name);
        if (! $write || in_array($name, ['logout']) || ! Schema::hasTable('tenant_subscriptions')) {
            return $next($r);
        }

        return DB::transaction(function () use ($r, $next) {
            $tenant = DB::table('tenants')->where('legacy_key', 'best-uniform')->lockForUpdate()->first();
            abort_unless($tenant && in_array($tenant->status, ['active', 'trialing']), 403);

            return app(TenantContext::class)->runFor($tenant->id, $r->user()->id, function () use ($r, $next) {
                abort_unless(app(TenantAccess::class)->writable(), 409, 'The subscription is read-only. Review billing in the React workspace.');

                return $next($r);
            });
        });
    }
}

<?php

namespace App\Http\Middleware;

use App\Domain\Billing\TenantAccess;
use Closure;
use Illuminate\Http\Request;

class SubscriptionAccess
{
    public function handle(Request $request, Closure $next)
    {
        $access = app(TenantAccess::class);
        $recovery = $request->is('api/v1/billing/*') || $request->is('api/v1/workspace/exports*') || $request->is('api/v1/workspace/support*');
        if (! $request->isMethodSafe() && ! $recovery) {
            $access->assertWritable();
        }$feature = match (true) {
            $request->is('api/v1/purchasing/*') => 'purchasing',$request->is('api/v1/returns*') || $request->is('api/v1/sales/*/returns') => 'returns',$request->is('api/v1/reports/*') => 'reports',$request->is('api/v1/workspace/imports*') => 'imports',default => null
        };
        if ($feature) {
            $access->feature($feature);
        }

return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FreezeLegacyStock
{
    public function handle(Request $request, Closure $next)
    {
        // Before phase 4 is migrated the existing Blade workflow is unchanged.
        if (! Schema::hasColumn('tenants', 'legacy_stock_frozen_at')) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next) {
            $tenant = DB::table('tenants')->where('legacy_key', 'best-uniform')->lockForUpdate()->first();
            abort_unless($tenant && in_array($tenant->status, ['active', 'trialing']) && DB::table('tenant_memberships')->where('tenant_id', $tenant->id)->where('user_id', $request->user()?->id)->where('status', 'active')->exists(), 403);
            if ($tenant->legacy_stock_frozen_at) {
                return response()->json(['message' => 'Legacy stock writing is frozen. Use the new POS and inventory workspace. Historical invoices remain available.'], 409);
            }

            return $next($request);
        });
    }
}

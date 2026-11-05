<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\PlatformAccess;
use Closure;
use Illuminate\Http\Request;

class RequirePlatformPermission
{
    public function handle(Request $request, Closure $next, string $permission = 'dashboard.platform.view')
    {
        abort_unless(in_array($permission, app(PlatformAccess::class)->permissions($request->user()->id), true), 403);

        return $next($request);
    }
}

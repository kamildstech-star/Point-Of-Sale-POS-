<?php

namespace App\Http\Middleware;

use App\Http\Constants\AppConst;
use Auth;
use Closure;
use Illuminate\Http\Request;
use Route;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
   
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission_id): Response
    {
        $user = $request->user();
        $role = $user->role()->first();
        $accessableRoutes = json_decode($role->permission);
        $isAccessable     = in_array($permission_id, $accessableRoutes );
        if ($isAccessable == true) {
            return $next($request);
        
        } else {
        
            abort(404);
        } 
    }
}

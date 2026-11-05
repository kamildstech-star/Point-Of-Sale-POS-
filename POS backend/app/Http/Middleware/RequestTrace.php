<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestTrace
{
    public function handle(Request $r, Closure $next)
    {
        if (! $r->is('api/*')) {
            return $next($r);
        }$id = (string) Str::uuid();
        $r->attributes->set('request_id', $id);
        $start = microtime(true);
        $status = 500;
        try {
            $response = $next($r);
            $status = $response->getStatusCode();
            $response->headers->set('X-Request-ID', $id);

            return $response;
        } finally {
            Log::info('api_request', ['request_id' => $id, 'tenant_id' => $r->attributes->get('tenant_id'), 'route' => $r->route()?->uri(), 'method' => $r->method(), 'status' => $status, 'duration_ms' => (int) ((microtime(true) - $start) * 1000)]);
        }
    }
}

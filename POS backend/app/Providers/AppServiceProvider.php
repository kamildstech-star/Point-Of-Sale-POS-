<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /* Register any application services.*/
    public function register(): void
    {
        $this->app->scoped(\App\Domain\Tenancy\TenantContext::class, fn () => new \App\Domain\Tenancy\TenantContext());
    }

    /* Bootstrap any application services. */
    public function boot(): void
    {
        // Phase 1 is session-only: never look up a bearer token or require a
        // personal_access_tokens table in the legacy database.
        \Laravel\Sanctum\Sanctum::getAccessTokenFromRequestUsing(static fn () => null);
        \Illuminate\Support\Facades\Gate::define('tenant-permission', [\App\Policies\TenantPolicy::class, 'permission']);
        \Illuminate\Support\Facades\RateLimiter::for('spa-login', function (\Illuminate\Http\Request $request) {
            $email = $request->input('email');
            $identity = hash('sha256', is_string($email) ? mb_strtolower(trim($email)) : 'invalid-identity');

            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('ip:'.$request->ip()),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('login:'.$identity.'|'.$request->ip()),
            ];
        });
        \Illuminate\Support\Facades\RateLimiter::for('spa-api', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('dashboard-api', fn (\Illuminate\Http\Request $request) => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()->id.':'.$request->session()->get('active_tenant_id')));
        \Illuminate\Support\Facades\RateLimiter::for('stripe-webhooks', fn (\Illuminate\Http\Request $r) => \Illuminate\Cache\RateLimiting\Limit::perMinute(300)->by($r->ip()));
        \Illuminate\Support\Facades\RateLimiter::for('bulk-operations', fn (\Illuminate\Http\Request $r) => \Illuminate\Cache\RateLimiting\Limit::perMinute(12)->by((string) $r->user()?->id));
        Paginator::useBootstrap();
    }
}

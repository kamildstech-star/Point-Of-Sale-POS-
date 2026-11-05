<?php

namespace App\Domain\Tenancy;

class TenantJobMiddleware
{
    public function __construct(public int $tenantId, public int $actorId) {}

    public function handle(object $job, callable $next): mixed
    {
        return app(TenantContext::class)->runFor($this->tenantId, $this->actorId, function ($context) use ($job, $next) {
            abort_unless(in_array($context->tenant()->status, ['active', 'trialing'], true), 403);
            return $next($job);
        });
    }
}

<?php

namespace App\Policies;

use App\Domain\Tenancy\TenantContext;
use App\Models\User;

class TenantPolicy
{
    public function permission(User $user, string $permission): bool
    {
        $context = app(TenantContext::class);
        return $context->membership()?->user_id === $user->id && $context->allows($permission);
    }
}

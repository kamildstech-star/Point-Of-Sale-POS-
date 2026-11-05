<?php

namespace App\Domain\Billing;

use App\Domain\Operations\Conflict;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TenantAccess
{
    public function subscription(): ?object
    {
        if (! Schema::hasTable('tenant_subscriptions')) {
            return null;
        }

return DB::table('tenant_subscriptions')->where('tenant_id', app(TenantContext::class)->id())->first();
    }

    public function writable(): bool
    {
        $s = $this->subscription();
        if (! $s) {
            return true;
        }if ($s->grandfathered && config('stripe_billing.legacy_access')) {
            return true;
        }if (! in_array($s->status, ['active', 'trialing', 'past_due'])) {
            return false;
        }if (! $s->verified_at) {
            return false;
        }$until = $s->status === 'trialing' ? $s->trial_ends_at : $s->current_period_ends_at;
        if (! $until) {
            return false;
        }if ($s->cancel_at && now()->gte($s->cancel_at)) {
            return false;
        }if ($s->status === 'past_due' && (! $s->past_due_since || now()->gt(\Illuminate\Support\Carbon::parse($s->past_due_since)->addDays(config('stripe_billing.grace_days'))))) {
            return false;
        }if (now()->gt(\Illuminate\Support\Carbon::parse($until)->addDays($s->status === 'trialing' ? 0 : config('stripe_billing.grace_days')))) {
            return false;
        }

return true;
    }

    public function assertWritable(): void
    {
        if (! $this->writable()) {
            throw new Conflict('The subscription is read-only. The owner can review billing; existing records and exports remain available.');
        }
    }

    public function entitlement(string $feature): array
    {
        $s = $this->subscription();
        if (! $s) {
            return ['enabled' => true, 'limit' => null];
        }$plan = DB::table('plan_prices')->where('id', $s->plan_price_id)->value('plan_id');
        $e = DB::table('plan_entitlements')->where('plan_id', $plan)->where('feature_code', $feature)->first();

        return ['enabled' => $e ? (bool) $e->enabled : false, 'limit' => $e?->limit_value];
    }

    public function feature(string $code): void
    {
        if (! $this->entitlement($code)['enabled']) {
            throw new Conflict('Your current plan does not enable '.$code.'.');
        }
    }

    public function capacity(string $code, int $additional = 1): void
    {
        $this->assertWritable();
        $e = $this->entitlement($code);
        if (! $e['enabled']) {
            throw new Conflict('Your current plan does not enable '.$code.'.');
        }$table = ['products' => 'catalog_products', 'staff' => 'tenant_memberships', 'branches' => 'branches'][$code] ?? throw new \LogicException('Unknown capacity.');
        $q = DB::table($table)->where('tenant_id', app(TenantContext::class)->id());
        if ($code === 'staff') {
            $q->where('status', 'active');
        }if ($e['limit'] !== null && $q->count() + $additional > $e['limit']) {
            throw new Conflict('The '.$code.' limit for this plan has been reached. Existing records are retained.');
        }
    }

    public function view(): array
    {
        $out = ['writable' => $this->writable(), 'features' => []];
        foreach (['products', 'staff', 'branches', 'purchasing', 'returns', 'reports', 'imports'] as $f) {
            $out['features'][$f] = $this->entitlement($f);
        }

return $out;
    }
}

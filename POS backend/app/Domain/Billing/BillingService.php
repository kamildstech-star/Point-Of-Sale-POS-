<?php

namespace App\Domain\Billing;

use App\Domain\Operations\Conflict;
use App\Domain\Operations\Decimal;
use App\Domain\Tenancy\TenantQueries;
use Illuminate\Support\Facades\DB;

class BillingService
{
    use TenantQueries;

    public const FEATURES = ['products', 'staff', 'branches', 'purchasing', 'returns', 'reports', 'imports'];

    public function initialize(int $tenant): void
    {
        $plan = DB::table('plans')->where('code', 'legacy-access')->value('id') ?? DB::table('plans')->insertGetId(['code' => 'legacy-access', 'name' => 'Preserved local access', 'public' => false, 'active' => true, 'trial_days' => 0, 'created_at' => now(), 'updated_at' => now()]);
        foreach (self::FEATURES as $f) {
            DB::table('plan_entitlements')->insertOrIgnore(['plan_id' => $plan, 'feature_code' => $f, 'enabled' => true, 'limit_value' => null]);
        }
        $price = DB::table('plan_prices')->where('code', 'legacy-local')->value('id') ?? DB::table('plan_prices')->insertGetId(['plan_id' => $plan, 'code' => 'legacy-local', 'currency_code' => 'USD', 'interval' => 'perpetual', 'amount' => '0', 'active' => true, 'checkout_enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('tenant_subscriptions')->insertOrIgnore(['tenant_id' => $tenant, 'plan_price_id' => $price, 'public_ref' => 'tref_'.bin2hex(random_bytes(16)), 'provider' => 'local', 'status' => 'active', 'grandfathered' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function seedPlans(): void
    {
        foreach (['starter' => ['Starter', '19', '190', 1, 3, 1000, false], 'professional' => ['Professional', '49', '490', 3, 10, 10000, true], 'business' => ['Business', '99', '990', 10, 50, 100000, true], 'enterprise' => ['Enterprise', null, null, null, null, null, true]] as $code => [$name,$monthly,$yearly,$stores,$staff,$products,$imports]) {
            $id = DB::table('plans')->where('code', $code)->value('id') ?? DB::table('plans')->insertGetId(['code' => $code, 'name' => $name.' (provisional)', 'public' => true, 'active' => true, 'trial_days' => 0, 'created_at' => now(), 'updated_at' => now()]);
            foreach (self::FEATURES as $f) {
                DB::table('plan_entitlements')->insertOrIgnore(['plan_id' => $id, 'feature_code' => $f, 'enabled' => $f === 'imports' ? $imports : true, 'limit_value' => match ($f) {
                    'branches' => $stores,'staff' => $staff,'products' => $products,default => null
                }]);
            }
            if ($monthly !== null) {
                foreach (['monthly' => $monthly, 'yearly' => $yearly] as $interval => $amount) {
                    DB::table('plan_prices')->insertOrIgnore(['plan_id' => $id, 'code' => $code.'-'.$interval, 'currency_code' => 'USD', 'interval' => $interval, 'amount' => $amount, 'active' => true, 'checkout_enabled' => false, 'stripe_price_id' => null, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function owner(): void
    {
        abort_unless($this->context()->isOwner() || $this->context()->allows('billing.manage'), 403);
    }

    public function atomic(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            $tenant = DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            abort_unless($tenant && $tenant->status !== 'archived', 403);
            $member = $this->context()->membership();
            abort_unless(DB::table('tenant_memberships')->where('tenant_id', $tenant->id)->where('id', $member->id)->where('user_id', $member->user_id)->where('role_id', $member->role_id)->where('status', 'active')->exists(), 403);
            $this->owner();

            return $callback();
        }, 3);
    }

    public function frontend(): string
    {
        $url = rtrim((string) config('stripe_billing.frontend_url'), '/');
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ($scheme !== 'https' && ! in_array($host, ['localhost', '127.0.0.1', '::1'])) || parse_url($url, PHP_URL_USER) || parse_url($url, PHP_URL_QUERY)) {
            throw new Conflict('Configure a trusted FRONTEND_URL for billing redirects.');
        }

        return $url;
    }

    private function price(int $id): object
    {
        $p = DB::table('plan_prices as pp')->join('plans as p', 'p.id', '=', 'pp.plan_id')->where('pp.id', $id)->where('pp.active', true)->where('p.active', true)->where('p.public', true)->select('pp.*', 'p.trial_days')->first();
        if (! $p || ! $p->checkout_enabled || ! preg_match('/^price_[A-Za-z0-9]+$/D', $p->stripe_price_id ?? '')) {
            throw new Conflict('This provisional plan requires a configured Stripe test Price ID and explicit Checkout enablement.');
        }if (strtolower($p->currency_code) !== config('stripe_billing.currency')) {
            throw new Conflict('Plan currency differs from configured SaaS billing currency.');
        }

        return $p;
    }

    public function checkout(int $priceId, string $key): array
    {
        $this->owner();
        if (! preg_match('/^[a-zA-Z0-9:_-]{8,100}$/D', $key)) {
            Decimal::invalid('Supply a valid billing request key.');
        }$gateway = app(StripeGateway::class);
        $gateway->client();
        $front = $this->frontend();
        $price = $this->price($priceId);
        $attempt = $this->atomic(function () use ($priceId, $key) {
            $s = $this->query('tenant_subscriptions')->first() ?? throw new Conflict('Initialize subscriptions first.');
            if ($s->stripe_subscription_id && ! in_array($s->status, ['canceled', 'incomplete_expired'])) {
                throw new Conflict('Use Stripe Customer Portal to manage the existing subscription.');
            }$prior = $this->query('billing_checkout_attempts')->where('request_key', $key)->first();
            if ($prior) {
                if ($prior->plan_price_id != $priceId) {
                    throw new Conflict('Checkout key belongs to another plan.');
                }

                return $prior;
            }$active = $this->query('billing_checkout_attempts')->where('active_slot', 1)->first();
            if ($active) {
                throw new Conflict('A Checkout attempt is already pending. Resume or expire it before creating another.');
            }$id = DB::table('billing_checkout_attempts')->insertGetId(['tenant_id' => $this->context()->id(), 'plan_price_id' => $priceId, 'request_key' => $key, 'public_ref' => 'attempt_'.bin2hex(random_bytes(16)), 'active_slot' => 1, 'status' => 'preparing', 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);

            return $this->row('billing_checkout_attempts', $id);
        });
        if ($attempt->status === 'open' && $attempt->checkout_url && now()->lt($attempt->expires_at)) {
            return ['id' => $attempt->id, 'url' => $attempt->checkout_url];
        }if (! in_array($attempt->status, ['preparing', 'unknown'])) {
            throw new Conflict('This Checkout attempt is no longer open.');
        }if (now()->gte(\Illuminate\Support\Carbon::parse($attempt->created_at)->addHours(23))) {
            throw new Conflict('An unconfirmed older Checkout attempt requires operator reconciliation before retrying.');
        }
        try {
            $remote = $gateway->price($price->stripe_price_id);
            $expected = bcmul((string) $price->amount, '100', 0);
            $interval = $price->interval === 'yearly' ? 'year' : 'month';
            if (empty($remote['active']) || strtolower($remote['currency'] ?? '') !== strtolower($price->currency_code) || (string) ($remote['unit_amount'] ?? '') !== $expected || ($remote['recurring']['interval'] ?? '') !== $interval || ($remote['recurring']['interval_count'] ?? 1) !== 1) {
                throw new Conflict('Stripe Price currency, interval or amount does not match the configured provisional price.');
            }
            $tenant = DB::table('tenants')->where('id', $this->context()->id())->first();
            $s = $this->query('tenant_subscriptions')->first();
            $customer = $tenant->stripe_customer_id;
            if (! $customer) {
                $created = $gateway->customer(['name' => $tenant->name, 'metadata' => ['tenant_ref' => $s->public_ref]], 'tenant-customer:'.$s->public_ref);
                $customer = $created['id'];
                $this->atomic(function () use ($customer) {
                    DB::table('tenants')->where('id', $this->context()->id())->update(['stripe_customer_id' => $customer, 'updated_at' => now()]);
                });
            }
            $existing = $gateway->subscriptions($customer);
            foreach ($existing['data'] ?? [] as $sub) {
                if (! in_array($sub['status'], ['canceled', 'incomplete_expired'])) {
                    throw new Conflict('Stripe already has a subscription for this customer. Reconcile it or use Customer Portal.');
                }
            }if ($existing['has_more'] ?? false) {
                throw new Conflict('Customer subscription history requires operator reconciliation.');
            }
            $metadata = ['tenant_ref' => $s->public_ref, 'attempt_ref' => $attempt->public_ref];
            $subData = ['metadata' => $metadata];
            if ($price->trial_days > 0) {
                $subData['trial_period_days'] = $price->trial_days;
            }
            $session = $gateway->checkout(['mode' => 'subscription', 'customer' => $customer, 'client_reference_id' => $s->public_ref, 'line_items' => [['price' => $price->stripe_price_id, 'quantity' => 1]], 'metadata' => $metadata, 'subscription_data' => $subData, 'success_url' => $front.'/billing/success?session_id={CHECKOUT_SESSION_ID}', 'cancel_url' => $front.'/billing/canceled', 'expires_at' => \Illuminate\Support\Carbon::parse($attempt->expires_at)->timestamp], 'checkout:'.$attempt->public_ref);
            $url = $session['url'] ?? '';
            if (parse_url($url, PHP_URL_HOST) !== 'checkout.stripe.com' || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                throw new Conflict('Stripe did not return a trusted hosted Checkout URL.');
            }
            $this->atomic(function () use ($attempt, $session, $url) {
                $this->query('billing_checkout_attempts')->where('id', $attempt->id)->update(['stripe_session_id' => $session['id'], 'checkout_url' => $url, 'status' => 'open', 'expires_at' => \Illuminate\Support\Carbon::createFromTimestamp($session['expires_at'] ?? now()->addHour()->timestamp), 'updated_at' => now()]);
                $this->audit('billing.checkout.created', 'billing_checkout_attempt', $attempt->id);
            });

            return ['id' => $attempt->id, 'url' => $url];
        } catch (\Throwable $e) {
            $this->atomic(fn () => $this->query('billing_checkout_attempts')->where('id', $attempt->id)->update(['status' => $e instanceof Conflict ? 'failed' : 'unknown', 'active_slot' => $e instanceof Conflict ? null : 1, 'updated_at' => now()]));
            if ($e instanceof Conflict) {
                throw $e;
            }throw new Conflict('Stripe Checkout result is unconfirmed. Retry with the same key; no access is granted by redirects.');
        }
    }

    public function portal(): array
    {
        $this->owner();
        $customer = DB::table('tenants')->where('id', $this->context()->id())->value('stripe_customer_id');
        if (! $customer) {
            throw new Conflict('This tenant has no Stripe Customer yet.');
        }$session = app(StripeGateway::class)->portal(['customer' => $customer, 'return_url' => $this->frontend().'/billing']);
        $url = $session['url'] ?? '';
        if (parse_url($url, PHP_URL_HOST) !== 'billing.stripe.com' || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new Conflict('Stripe did not return a trusted Customer Portal URL.');
        }

        return ['url' => $url];
    }

    public function expireAttempt(int $id): array
    {
        $this->owner();
        $a = $this->row('billing_checkout_attempts', $id);
        if ($a->stripe_session_id) {
            $remote = app(StripeGateway::class)->session($a->stripe_session_id);
            if (($remote['status'] ?? '') === 'complete') {
                throw new Conflict('Checkout completed. Wait for verified subscription synchronization.');
            }if (($remote['status'] ?? '') === 'open') {
                app(StripeGateway::class)->expire($a->stripe_session_id);
            }
        } elseif (in_array($a->status, ['preparing', 'unknown'])) {
            throw new Conflict('An unconfirmed session requires operator reconciliation.');
        }

        return $this->atomic(function () use ($id) {
            $this->query('billing_checkout_attempts')->where('id', $id)->update(['status' => 'expired', 'active_slot' => null, 'updated_at' => now()]);

            return ['id' => $id];
        });
    }
}

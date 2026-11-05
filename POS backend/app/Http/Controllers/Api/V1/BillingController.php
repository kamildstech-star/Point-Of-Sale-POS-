<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Billing\BillingService;
use App\Domain\Billing\StripeWebhookService;
use App\Domain\Billing\TenantAccess;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    use TenantQueries;

    public function plans(BillingService $s)
    {
        $s->owner();
        $plans = DB::table('plans')->where('public', true)->where('active', true)->get(['id', 'code', 'name', 'trial_days']);
        foreach ($plans as $p) {
            $p->prices = DB::table('plan_prices')->where('plan_id', $p->id)->where('active', true)->get(['id', 'code', 'currency_code', 'interval', 'amount', 'checkout_enabled']);
            $p->entitlements = DB::table('plan_entitlements')->where('plan_id', $p->id)->get(['feature_code', 'enabled', 'limit_value']);
        }

return response()->json(['data' => $plans, 'meta' => ['provisional' => true, 'stripe_enabled' => (bool) config('stripe_billing.enabled'), 'live_enabled' => (bool) config('stripe_billing.live_enabled')]]);
    }

    public function subscription(BillingService $s, TenantAccess $access)
    {
        $s->owner();
        $sub = $this->query('tenant_subscriptions')->first();
        if ($sub) {
            $sub->plan = DB::table('plan_prices as pp')->join('plans as p', 'p.id', '=', 'pp.plan_id')->where('pp.id', $sub->plan_price_id)->select('p.name', 'p.code', 'pp.interval', 'pp.currency_code', 'pp.amount')->first();
        }

return response()->json(['data' => ['subscription' => $sub, 'access' => $access->view(), 'pending_checkout' => $this->query('billing_checkout_attempts')->where('active_slot', 1)->first(['id', 'checkout_url', 'status', 'expires_at']), 'provider' => 'stripe']]);
    }

    public function checkout(Request $r, BillingService $s)
    {
        $data = $r->validate(['plan_price_id' => ['required', 'integer', 'exists:plan_prices,id'], 'idempotency_key' => ['required', 'string', 'max:100']]);

        return response()->json(['data' => $s->checkout($data['plan_price_id'], $data['idempotency_key'])]);
    }

    public function expire(int $id, BillingService $s)
    {
        return response()->json(['data' => $s->expireAttempt($id)]);
    }

    public function portal(BillingService $s)
    {
        return response()->json(['data' => $s->portal()]);
    }

    public function invoices(BillingService $s)
    {
        $s->owner();

        return response()->json($this->query('saas_invoices')->orderByDesc('id')->paginate(50));
    }

    public function usage(BillingService $s, TenantAccess $access)
    {
        $s->owner();
        $rows = [];
        foreach (['products' => 'catalog_products', 'branches' => 'branches', 'staff' => 'tenant_memberships'] as $feature => $table) {
            $q = $this->query($table);
            if ($feature === 'staff') {
                $q->where('status', 'active');
            }$rows[] = ['feature' => $feature, 'quantity' => $q->count()] + $access->entitlement($feature);
        }

return response()->json(['data' => $rows]);
    }

    public function webhook(Request $r, StripeWebhookService $s)
    {
        abort_if(strlen($r->getContent()) > 1048576, 413);

        return response()->json(['data' => $s->receive($r->getContent(), (string) $r->header('Stripe-Signature'))], 202);
    }
}

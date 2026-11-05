<?php

namespace App\Domain\Billing;

use App\Domain\Operations\Conflict;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StripeWebhookService
{
    public const EVENTS = ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed', 'customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted', 'invoice.paid', 'invoice.payment_failed'];

    private function id(mixed $value): ?string
    {
        return is_array($value) ? ($value['id'] ?? null) : (is_string($value) ? $value : null);
    }

    private function stamp(mixed $epoch): ?Carbon
    {
        return is_numeric($epoch) && $epoch > 0 ? Carbon::createFromTimestamp((int) $epoch)->setTimezone(config('app.timezone')) : null;
    }

    public function receive(string $raw, string $signature): array
    {
        $secret = config('stripe_billing.webhook_secret');
        if (! config('stripe_billing.enabled') || ! $secret) {
            abort(503, 'Stripe webhook configuration is pending.');
        }
        try {
            $event = \Stripe\Webhook::constructEvent($raw, $signature, $secret, 300)->toArray();
        } catch (\Throwable) {
            abort(400, 'Invalid Stripe signature.');
        }
        if (($event['livemode'] ?? false) && ! config('stripe_billing.live_enabled')) {
            abort(400, 'Live Stripe events are disabled.');
        }if (! in_array($event['type'] ?? '', self::EVENTS)) {
            return ['ignored' => true];
        }
        $id = DB::transaction(function () use ($event, $raw) {
            DB::table('billing_webhook_events')->insertOrIgnore(['provider' => 'stripe', 'provider_event_id' => $event['id'], 'payload_hash' => hash('sha256', $raw), 'payload_json' => $raw, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::table('billing_webhook_events')->where('provider', 'stripe')->where('provider_event_id', $event['id'])->first();
            if (! hash_equals($row->payload_hash, hash('sha256', $raw))) {
                throw new Conflict('Stripe event ID was reused with different data.');
            }

return $row->id;
        });
        $row = DB::table('billing_webhook_events')->where('id', $id)->first();
        if (! in_array($row->status, ['processed', 'ignored', 'superseded'])) {
            \App\Jobs\ProcessStripeWebhook::dispatch($id)->onConnection(config('stripe_billing.queue_connection'))->afterCommit();
        }

return ['accepted' => true, 'event_id' => $id];
    }

    public function process(int $eventId): void
    {
        $record = DB::table('billing_webhook_events')->where('id', $eventId)->where('provider', 'stripe')->first() ?? throw new \RuntimeException('Unknown verified event.');
        if (in_array($record->status, ['processed', 'ignored', 'superseded'])) {
            return;
        }$event = json_decode($record->payload_json, true, 512, JSON_THROW_ON_ERROR);
        $object = $event['data']['object'];
        $customer = $this->id($object['customer'] ?? null);
        $tenant = DB::table('tenants')->where('stripe_customer_id', $customer)->first();
        if (! $customer || ! $tenant) {
            $this->finish($eventId, 'ignored');

            return;
        }$gateway = app(StripeGateway::class);
        $subId = str_starts_with($event['type'], 'customer.subscription.') ? $object['id'] : ($this->id($object['subscription'] ?? $object['parent']['subscription_details']['subscription'] ?? null));
        if (str_starts_with($event['type'], 'checkout.')) {
            $session = $gateway->session($object['id']);
            if ($this->id($session['customer'] ?? null) !== $customer) {
                throw new Conflict('Stripe Checkout customer mismatch.');
            }$subId = $this->id($session['subscription'] ?? null);
            DB::table('billing_checkout_attempts')->where('tenant_id', $tenant->id)->where('stripe_session_id', $session['id'])->update(['status' => ($event['type'] === 'checkout.session.async_payment_failed' ? 'payment_failed' : ($session['status'] ?? 'complete')), 'active_slot' => null, 'updated_at' => now()]);
        }
        if (! $subId) {
            $this->finish($eventId, 'ignored');

            return;
        }
        $generation = DB::transaction(function () use ($tenant, $eventId) {
            DB::table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
            $s = DB::table('tenant_subscriptions')->where('tenant_id', $tenant->id)->first() ?? throw new Conflict('Tenant subscription is not initialized.');
            DB::table('tenant_subscriptions')->where('id', $s->id)->update(['sync_generation' => $s->sync_generation + 1]);
            DB::table('billing_webhook_events')->where('id', $eventId)->update(['status' => 'processing', 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);

            return $s->sync_generation + 1;
        });
        // Always reconcile against Stripe's current state. Payload order is not
        // an access decision. HTTP requests happen outside the tenant mutex.
        $sub = $gateway->subscription($subId);
        if ($this->id($sub['customer'] ?? null) !== $customer) {
            throw new Conflict('Authoritative Stripe subscription belongs to a different customer.');
        }if (($sub['livemode'] ?? false) && ! config('stripe_billing.live_enabled')) {
            throw new Conflict('Live subscription synchronization is disabled.');
        }
        $latest = $sub['latest_invoice'] ?? null;
        if (is_string($latest)) {
            $latest = $gateway->invoice($latest);
        }$eventInvoice = str_starts_with($event['type'], 'invoice.') ? $gateway->invoice($object['id']) : null;
        DB::transaction(function () use ($tenant, $sub, $latest, $eventInvoice, $event, $eventId, $generation, $customer) {
            DB::table('tenants')->where('id', $tenant->id)->lockForUpdate()->first();
            $s = DB::table('tenant_subscriptions')->where('tenant_id', $tenant->id)->first();
            if ($s->sync_generation != $generation) {
                $this->finish($eventId, 'superseded');

                return;
            }
            if ($s->stripe_subscription_id && $s->stripe_subscription_id !== $sub['id'] && ! in_array($s->status, ['canceled', 'incomplete_expired'])) {
                if (in_array($sub['status'], ['canceled', 'incomplete_expired'])) {
                    $this->finish($eventId, 'ignored');

                    return;
                }throw new Conflict('Another Stripe subscription is already attached to this tenant.');
            }
            if (isset($sub['metadata']['tenant_ref']) && $sub['metadata']['tenant_ref'] !== $s->public_ref) {
                throw new Conflict('Stripe subscription metadata does not match its registered tenant.');
            }
            $items = $sub['items']['data'] ?? [];
            if (count($items) !== 1 || ($items[0]['quantity'] ?? 1) !== 1) {
                throw new Conflict('Configured flat-rate billing requires one subscription item with quantity one.');
            }$item = $items[0];
            $priceId = $this->id($item['price'] ?? null);
            $price = DB::table('plan_prices')->where('stripe_price_id', $priceId)->first();
            if (! $price) {
                throw new Conflict('Stripe price is not mapped to a configured local plan.');
            }$start = $this->stamp($item['current_period_start'] ?? $sub['current_period_start'] ?? null);
            $end = $this->stamp($item['current_period_end'] ?? $sub['current_period_end'] ?? null);
            $status = $sub['status'];
            $verifiedPaid = is_array($latest) && (($latest['paid'] ?? false) || ($latest['status'] ?? '') === 'paid');
            $trial = $status === 'trialing';
            $grant = ($status === 'active' && $verifiedPaid) || $trial;
            $data = ['stripe_subscription_id' => $sub['id'], 'provider' => 'stripe', 'status' => $status, 'verified_at' => now(), 'current_period_starts_at' => $start, 'current_period_ends_at' => $end, 'trial_ends_at' => $this->stamp($sub['trial_end'] ?? null), 'cancel_at' => $this->stamp($sub['cancel_at'] ?? (($sub['cancel_at_period_end'] ?? false) ? ($end?->timestamp) : null)), 'past_due_since' => $status === 'past_due' ? ($s->past_due_since ?? now()) : null, 'last_provider_event_at' => now(), 'updated_at' => now()];
            if ($grant) {
                $data['plan_price_id'] = $price->id;
                $data['grandfathered'] = false;
            } elseif (! $s->grandfathered && $status === 'active' && ! $verifiedPaid) {
                $data['status'] = $s->verified_at && $s->current_period_ends_at ? 'active' : 'incomplete';
                $data['current_period_starts_at'] = $s->current_period_starts_at;
                $data['current_period_ends_at'] = $s->current_period_ends_at;
            }
            DB::table('tenant_subscriptions')->where('id', $s->id)->update($data);
            DB::table('subscription_items')->where('tenant_id', $tenant->id)->where('subscription_id', $s->id)->delete();
            DB::table('subscription_items')->insert(['tenant_id' => $tenant->id, 'subscription_id' => $s->id, 'stripe_item_id' => $item['id'], 'stripe_price_id' => $priceId, 'quantity' => 1, 'current_period_starts_at' => $start, 'current_period_ends_at' => $end, 'created_at' => now(), 'updated_at' => now()]);
            foreach ([$eventInvoice, $latest] as $invoice) {
                if (is_array($invoice) && isset($invoice['id'])) {
                    $this->invoice($tenant->id, $s, $price, $invoice, $customer);
                }
            }
            DB::table('billing_checkout_attempts')->where('tenant_id', $tenant->id)->where('active_slot', 1)->when($s->stripe_subscription_id === null || $grant, fn ($q) => $q)->update(['active_slot' => null, 'status' => $grant ? 'complete' : 'subscription_pending', 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['tenant_id' => $tenant->id, 'actor_user_id' => null, 'action' => 'billing.stripe.synchronized', 'subject_type' => 'subscription', 'subject_id' => (string) $s->id, 'details_json' => json_encode(['event_id' => $event['id'], 'status' => $status, 'paid_access' => $grant]), 'occurred_at' => now()]);
            $this->finish($eventId, 'processed');
        }, 3);
    }

    private function invoice(int $tenant, object $sub, object $price, array $invoice, string $customer): void
    {
        if ($this->id($invoice['customer'] ?? null) !== $customer) {
            throw new Conflict('Stripe invoice customer mismatch.');
        }$currency = strtoupper($invoice['currency']);
        $scale = match ($currency) {
            'JPY','KRW','VND' => 0,'BHD','JOD','KWD','OMR','TND' => 3,default => 2
        };
        $money = fn ($v) => bcdiv((string) $v, bcpow('10', (string) $scale, 0), 4);
        $tax = '0';
        foreach ($invoice['total_taxes'] ?? $invoice['total_tax_amounts'] ?? [] as $t) {
            $tax = bcadd($tax, (string) $t['amount'], 0);
        }
        $data = ['tenant_id' => $tenant, 'subscription_id' => $sub->id, 'plan_price_id' => $price->id, 'invoice_no' => $invoice['number'] ?? $invoice['id'], 'public_ref' => $invoice['id'], 'stripe_invoice_id' => $invoice['id'], 'provider' => 'stripe', 'billing_version' => $sub->billing_version, 'currency_code' => $currency, 'subtotal' => $money($invoice['subtotal'] ?? 0), 'tax_total' => $money($tax), 'total' => $money($invoice['total'] ?? 0), 'status' => $invoice['status'] ?? 'draft', 'due_at' => $this->stamp($invoice['due_date'] ?? $invoice['created'] ?? time()), 'paid_at' => $this->stamp($invoice['status_transitions']['paid_at'] ?? null), 'entitlements_applied' => ($invoice['status'] ?? '') === 'paid', 'hosted_invoice_url' => $this->trustedUrl($invoice['hosted_invoice_url'] ?? null), 'invoice_pdf' => $this->trustedUrl($invoice['invoice_pdf'] ?? null), 'updated_at' => now()];
        $old = DB::table('saas_invoices')->where('stripe_invoice_id', $invoice['id'])->first();
        if ($old && $old->tenant_id != $tenant) {
            throw new Conflict('Invoice is already assigned to another tenant.');
        }if (! $old) {
            $id = DB::table('saas_invoices')->insertGetId($data + ['created_at' => now()]);
        } else {
            $id = $old->id;
            if ($old->status !== 'paid') {
                DB::table('saas_invoices')->where('id', $id)->update($data);
            }
        }
        if (($invoice['status'] ?? '') === 'paid') {
            DB::table('saas_invoice_payments')->insertOrIgnore(['tenant_id' => $tenant, 'saas_invoice_id' => $id, 'provider' => 'stripe', 'external_reference' => $invoice['id'], 'amount' => $money($invoice['amount_paid'] ?? 0), 'currency_code' => $currency, 'paid_at' => $this->stamp($invoice['status_transitions']['paid_at'] ?? $invoice['created'] ?? time()), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function trustedUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }$h = parse_url($url, PHP_URL_HOST);

        return parse_url($url, PHP_URL_SCHEME) === 'https' && ($h === 'stripe.com' || str_ends_with((string) $h, '.stripe.com')) ? $url : null;
    }

    private function finish(int $id, string $status): void
    {
        DB::table('billing_webhook_events')->where('id', $id)->update(['status' => $status, 'error_code' => null, 'processed_at' => now(), 'updated_at' => now()]);
    }
}

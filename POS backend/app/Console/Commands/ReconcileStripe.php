<?php

namespace App\Console\Commands;

use App\Jobs\ProcessStripeWebhook;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileStripe extends Command
{
    protected $signature = 'billing:reconcile {--tenant=} {--retry-event=}';

    protected $description = 'Queue authoritative Stripe subscription synchronization or explicitly retry a verified stored event';

    public function handle(): int
    {
        if ($id = $this->option('retry-event')) {
            $row = DB::table('billing_webhook_events')->where('id', $id)->where('provider', 'stripe')->first();
            if (! $row) {
                $this->error('Unknown verified Stripe event.');

                return self::FAILURE;
            }DB::table('billing_webhook_events')->where('id', $id)->update(['status' => 'pending', 'error_code' => null]);
            ProcessStripeWebhook::dispatch((int) $id)->onConnection(config('stripe_billing.queue_connection'));
            $this->info('Stored verified event queued again.');

            return self::SUCCESS;
        }
        foreach (DB::table('tenant_subscriptions as s')->join('tenants as t', 't.id', '=', 's.tenant_id')->whereNotNull('s.stripe_subscription_id')->when($this->option('tenant'), fn ($q) => $q->where('t.id', $this->option('tenant')))->get(['s.stripe_subscription_id', 't.stripe_customer_id']) as $s) {
            $id = 'operator_'.bin2hex(random_bytes(16));
            $raw = json_encode(['id' => $id, 'type' => 'customer.subscription.updated', 'created' => time(), 'data' => ['object' => ['id' => $s->stripe_subscription_id, 'customer' => $s->stripe_customer_id]]]);
            $record = DB::table('billing_webhook_events')->insertGetId(['provider' => 'stripe', 'provider_event_id' => $id, 'payload_hash' => hash('sha256', $raw), 'payload_json' => $raw, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
            ProcessStripeWebhook::dispatch($record)->onConnection(config('stripe_billing.queue_connection'));
        }$this->info('Subscription reconciliation queued.');

        return self::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Billing\StripeGateway;
use App\Domain\Billing\StripeWebhookService;
use App\Jobs\ProcessStripeWebhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\BuildAdvancedDatabase;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use BuildAdvancedDatabase;

    private FakeStripeGateway $stripe;

    private int $priceId;

    private array $payloads = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildAdvanced();
        config(['stripe_billing.enabled' => true, 'stripe_billing.secret' => 'sk_test_fixture_not_a_real_key', 'stripe_billing.webhook_secret' => 'whsec_fixture', 'stripe_billing.queue_connection' => 'database']);
        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);
        $this->priceId = DB::table('plan_prices')->where('code', 'starter-monthly')->value('id');
        DB::table('plan_prices')->where('id', $this->priceId)->update(['stripe_price_id' => 'price_fixtureMonthly', 'checkout_enabled' => true]);
        Queue::fake();
    }

    private function authoritative(string $status = 'active', bool $paid = true): void
    {
        DB::table('tenants')->where('id', $this->legacyTenant)->update(['stripe_customer_id' => 'cus_fixture']);
        $this->stripe->currentInvoice = ['id' => 'in_fixture', 'number' => 'INV-FIXTURE', 'customer' => 'cus_fixture', 'currency' => 'usd', 'subscription' => 'sub_fixture', 'status' => $paid ? 'paid' : 'open', 'paid' => $paid, 'subtotal' => 1900, 'total' => 1900, 'amount_paid' => $paid ? 1900 : 0, 'created' => time(), 'status_transitions' => ['paid_at' => $paid ? time() : null]];
        $this->stripe->current = ['id' => 'sub_fixture', 'customer' => 'cus_fixture', 'livemode' => false, 'metadata' => ['tenant_ref' => DB::table('tenant_subscriptions')->where('tenant_id', $this->legacyTenant)->value('public_ref')], 'status' => $status, 'latest_invoice' => $this->stripe->currentInvoice, 'items' => ['data' => [['id' => 'si_fixture', 'quantity' => 1, 'price' => ['id' => 'price_fixtureMonthly'], 'current_period_start' => time(), 'current_period_end' => now()->addMonth()->timestamp]]], 'trial_end' => $status === 'trialing' ? now()->addDays(7)->timestamp : null];
    }

    private function signed(string $id = 'evt_fixture', string $type = 'customer.subscription.updated', array $extra = []): array
    {
        $object = ['id' => 'sub_fixture', 'customer' => 'cus_fixture'] + $extra;
        if (str_starts_with($type, 'invoice.')) {
            $object['id'] = 'in_fixture';
            $object['parent'] = ['subscription_details' => ['subscription' => 'sub_fixture']];
        }$raw = $this->payloads[$id] ??= json_encode(['id' => $id, 'type' => $type, 'created' => time(), 'livemode' => false, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
        $time = time();
        $signature = 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$raw, 'whsec_fixture');

        return [$raw, $signature];
    }

    private function receiveProcess(string $id = 'evt_fixture', string $type = 'customer.subscription.updated'): void
    {
        [$raw,$signature] = $this->signed($id, $type);
        $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature], $raw)->assertStatus(202);
        $record = DB::table('billing_webhook_events')->where('provider_event_id', $id)->value('id');
        app(StripeWebhookService::class)->process($record);
    }

    public function test_hosted_checkout_customer_idempotency_and_no_access_from_success_url(): void
    {
        $data = ['plan_price_id' => $this->priceId, 'idempotency_key' => 'stripe-checkout-001'];
        $this->postJson('/api/v1/billing/checkout', $data)->assertOk()->assertJsonPath('data.url', 'https://checkout.stripe.com/c/pay/test_fixture');
        $this->postJson('/api/v1/billing/checkout', $data)->assertOk();
        $this->assertCount(1, $this->stripe->checkouts);
        $request = $this->stripe->checkouts[0]['data'];
        $this->assertSame('subscription', $request['mode']);
        $this->assertSame('price_fixtureMonthly', $request['line_items'][0]['price']);
        $this->assertSame('cus_fixture', $request['customer']);
        $this->assertTrue((bool) DB::table('tenant_subscriptions')->value('grandfathered'));
        $this->assertNull(DB::table('tenant_subscriptions')->value('stripe_subscription_id'));
        $this->postJson('/api/v1/billing/checkout', ['plan_price_id' => $this->priceId, 'idempotency_key' => 'another-pending-key'])->assertConflict();
    }

    public function test_unconfigured_prices_unauthorized_roles_and_live_keys_are_rejected(): void
    {
        DB::table('plan_prices')->where('id', $this->priceId)->update(['stripe_price_id' => null]);
        $data = ['plan_price_id' => $this->priceId, 'idempotency_key' => 'disabled-price'];
        $this->postJson('/api/v1/billing/checkout', $data)->assertConflict();
        DB::table('plan_prices')->where('id', $this->priceId)->update(['stripe_price_id' => 'price_fixtureMonthly']);
        config(['stripe_billing.secret' => 'sk_live_not_allowed']);
        $this->postJson('/api/v1/billing/checkout', $data)->assertConflict();
        DB::table('tenant_memberships')->where('tenant_id', $this->legacyTenant)->update(['role_id' => DB::table('tenant_roles')->where('tenant_id', $this->legacyTenant)->where('code', 'cashier')->value('id')]);
        $this->getJson('/api/v1/billing/subscription')->assertForbidden();
        $this->assertCount(0, $this->stripe->checkouts);
    }

    public function test_valid_signatures_queue_processing_invalid_signatures_fail_and_duplicate_events_do_not_duplicate_invoices(): void
    {
        $this->authoritative();
        [$raw,$sig] = $this->signed();
        $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => 't=0,v1=invalid'], $raw)->assertStatus(400);
        $this->receiveProcess();
        Queue::assertPushed(ProcessStripeWebhook::class);
        $this->assertSame('active', DB::table('tenant_subscriptions')->value('status'));
        $this->assertFalse((bool) DB::table('tenant_subscriptions')->value('grandfathered'));
        $this->receiveProcess();
        $this->assertSame(1, DB::table('billing_webhook_events')->count());
        $this->assertSame(1, DB::table('saas_invoices')->count());
        $this->assertSame(1, DB::table('saas_invoice_payments')->count());
    }

    public function test_out_of_order_payment_events_use_current_authoritative_state_and_recurring_failure_grace(): void
    {
        $this->authoritative();
        $this->receiveProcess('evt_paid', 'invoice.paid');
        $this->authoritative('past_due', false);
        $this->receiveProcess('evt_fail', 'invoice.payment_failed');
        $this->getJson('/api/v1/billing/subscription')->assertJsonPath('data.access.writable', true);
        $this->travel(8)->days();
        $this->getJson('/api/v1/billing/subscription')->assertJsonPath('data.access.writable', false);
        $this->postJson('/api/v1/operations/customers', ['name' => 'Blocked'])->assertConflict();
        $this->getJson('/api/v1/sales')->assertOk();
        $this->postJson('/api/v1/workspace/exports', ['type' => 'sales'])->assertCreated();
        $this->travelBack();
        $this->authoritative('active', true);
        $this->receiveProcess('evt_older_failure', 'invoice.payment_failed');
        $this->assertSame('active', DB::table('tenant_subscriptions')->value('status'));
    }

    public function test_cancel_at_period_end_and_plan_limits_are_enforced_without_deleting_data(): void
    {
        $this->authoritative();
        $this->stripe->current['cancel_at_period_end'] = true;
        $this->receiveProcess();
        $this->getJson('/api/v1/billing/subscription')->assertJsonPath('data.access.writable', true);
        $plan = DB::table('plan_prices')->where('id', $this->priceId)->value('plan_id');
        DB::table('plan_entitlements')->where('plan_id', $plan)->where('feature_code', 'products')->update(['limit_value' => 1]);
        $this->postJson('/api/v1/products', ['name' => 'Limit product', 'product_type' => 'simple', 'track_inventory' => true, 'variant' => ['sku' => 'TOO-MANY', 'base_unit_id' => DB::table('catalog_units')->value('id'), 'default_cost' => '0', 'default_price' => '1', 'tracking_mode' => 'none']])->assertConflict();
        $this->postJson('/api/v1/catalog/branches', ['name' => 'Too many stores', 'code' => 'SECOND', 'status' => 'active'])->assertConflict();
        $this->travel(32)->days();
        $this->getJson('/api/v1/billing/subscription')->assertJsonPath('data.access.writable', false);
        $this->assertSame(1, DB::table('catalog_products')->count());
        $this->postJson('/api/v1/billing/portal')->assertOk();
    }

    public function test_portal_upgrade_and_downgrade_follow_verified_price_mapping_and_pending_payment_does_not_upgrade(): void
    {
        $this->authoritative();
        $this->receiveProcess();
        $pro = DB::table('plan_prices')->where('code', 'professional-monthly')->value('id');
        DB::table('plan_prices')->where('id', $pro)->update(['stripe_price_id' => 'price_fixturePro', 'checkout_enabled' => true]);
        $this->stripe->current['items']['data'][0]['price']['id'] = 'price_fixturePro';
        $this->stripe->current['latest_invoice']['paid'] = false;
        $this->stripe->current['latest_invoice']['status'] = 'open';
        $this->receiveProcess('evt_upgrade_unpaid');
        $this->assertEquals($this->priceId, DB::table('tenant_subscriptions')->value('plan_price_id'));
        $this->stripe->current['latest_invoice']['paid'] = true;
        $this->stripe->current['latest_invoice']['status'] = 'paid';
        $this->receiveProcess('evt_upgrade_paid');
        $this->assertEquals($pro, DB::table('tenant_subscriptions')->value('plan_price_id'));
        $this->stripe->current['items']['data'][0]['price']['id'] = 'price_fixtureMonthly';
        $this->receiveProcess('evt_downgrade');
        $this->assertEquals($this->priceId, DB::table('tenant_subscriptions')->value('plan_price_id'));
    }

    public function test_tenant_identifiers_from_clients_and_mismatched_webhooks_cannot_change_another_subscription(): void
    {
        $this->authoritative();
        $this->receiveProcess('evt_first_paid');
        $other = app(\App\Domain\Tenancy\TenantService::class)->create($this->owner, ['name' => 'Second shop', 'slug' => 'second-shop', 'timezone' => 'UTC', 'currency_code' => 'PKR']);
        $this->selectTenant($other);
        $this->getJson('/api/v1/billing/invoices?tenant_id='.$this->legacyTenant)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/billing/subscription?tenant_id='.$this->legacyTenant)->assertJsonPath('data.subscription.tenant_id', $other)->assertJsonPath('data.access.writable', false);
        $this->stripe->current['metadata']['tenant_ref'] = 'another-tenant-reference';
        [$raw,$sig] = $this->signed('evt_mismatch');
        $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $sig], $raw)->assertStatus(202);
        try {
            app(StripeWebhookService::class)->process(DB::table('billing_webhook_events')->where('provider_event_id', 'evt_mismatch')->value('id'));
            $this->fail('Metadata mismatch must fail synchronization.');
        } catch (\App\Domain\Operations\Conflict $e) {
            $this->assertStringContainsString('metadata', $e->getMessage());
        }
        $this->assertNull(DB::table('tenant_subscriptions')->where('tenant_id', $other)->value('stripe_subscription_id'));
        $this->assertSame('active', DB::table('tenant_subscriptions')->where('tenant_id', $this->legacyTenant)->value('status'));
    }

    public function test_queued_webhook_can_retry_and_expiration_cannot_release_an_inflight_checkout(): void
    {
        DB::table('billing_checkout_attempts')->insert(['tenant_id' => $this->legacyTenant, 'plan_price_id' => $this->priceId, 'request_key' => 'preparing-fixture', 'public_ref' => 'attempt_preparing', 'active_slot' => 1, 'status' => 'preparing', 'expires_at' => now()->addHour(), 'created_at' => now(), 'updated_at' => now()]);
        $attempt = DB::table('billing_checkout_attempts')->value('id');
        $this->postJson('/api/v1/billing/checkout/'.$attempt.'/expire')->assertConflict();
        $this->assertEquals(1, DB::table('billing_checkout_attempts')->value('active_slot'));
        $this->authoritative();
        [$raw,$sig] = $this->signed('evt_retry');
        $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $sig], $raw)->assertStatus(202);
        $id = DB::table('billing_webhook_events')->where('provider_event_id', 'evt_retry')->value('id');
        $this->stripe->current['items']['data'][0]['price']['id'] = 'price_unconfigured';
        try {
            app(StripeWebhookService::class)->process($id);
            $this->fail('Unknown price must fail safely.');
        } catch (\App\Domain\Operations\Conflict $e) {
            $this->assertStringContainsString('mapped', $e->getMessage());
        }
        $this->assertTrue((bool) DB::table('tenant_subscriptions')->value('grandfathered'));
        $this->authoritative();
        app(StripeWebhookService::class)->process($id);
        $this->assertSame('processed', DB::table('billing_webhook_events')->where('id', $id)->value('status'));
        $this->assertEquals(2, DB::table('billing_webhook_events')->where('id', $id)->value('attempts'));
    }

    public function test_trialing_unpaid_paused_and_canceled_states_preserve_data_and_apply_access_policy(): void
    {
        foreach (['trialing' => true, 'unpaid' => false, 'paused' => false, 'canceled' => false, 'incomplete' => false, 'incomplete_expired' => false] as $status => $writable) {
            $this->authoritative($status, false);
            $this->receiveProcess('evt_status_'.$status);
            $this->getJson('/api/v1/billing/subscription')->assertJsonPath('data.access.writable', $writable);
        }
        $this->assertSame(1, DB::table('catalog_products')->count());
        $this->assertSame(10, (int) DB::table('inventory_balances')->value('on_hand'));
    }

    public function test_legacy_get_mutations_and_posts_observe_read_only_subscription_access(): void
    {
        DB::table('tenant_subscriptions')->where('tenant_id', $this->legacyTenant)->update(['grandfathered' => false, 'status' => 'unpaid']);
        foreach (['GET' => 'destory', 'POST' => 'update'] as $method => $action) {
            \Illuminate\Support\Facades\Route::match([$method], '/legacy-access-fixture-'.$action, fn () => response('Unexpected write'))->middleware(['web', \App\Http\Middleware\LegacySubscriptionAccess::class])->name('fixture.'.$action);
            $this->call($method, '/legacy-access-fixture-'.$action)->assertStatus(409);
        }
        $this->assertSame(1, DB::table('catalog_products')->count());
    }
}

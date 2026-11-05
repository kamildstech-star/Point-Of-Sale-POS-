<?php

namespace App\Domain\Billing;

use App\Domain\Operations\Conflict;
use Stripe\StripeClient;

class StripeGateway
{
    public function client(): StripeClient
    {
        $secret = (string) config('stripe_billing.secret');
        if (! config('stripe_billing.enabled') || ! preg_match('/^(sk|rk)_(test|live)_/', $secret)) {
            throw new Conflict('Stripe test billing is not configured.');
        }
        if (str_contains($secret, '_live_') && ! config('stripe_billing.live_enabled')) {
            throw new Conflict('Live Stripe charging is disabled pending production approval.');
        }
        // Bound individual requests so retries finish inside the billing job's
        // 90-second timeout. Database queue visibility defaults to 180 seconds.
        $http = new \Stripe\HttpClient\CurlClient();
        $http->setTimeout(10);
        $http->setConnectTimeout(5);
        \Stripe\ApiRequestor::setHttpClient($http);

        return new StripeClient(['api_key' => $secret, 'max_network_retries' => 1]);
    }

    public function customer(array $data, string $key): array
    {
        return $this->client()->customers->create($data, ['idempotency_key' => $key])->toArray();
    }

    public function price(string $id): array
    {
        return $this->client()->prices->retrieve($id, [])->toArray();
    }

    public function subscriptions(string $customer): array
    {
        return $this->client()->subscriptions->all(['customer' => $customer, 'status' => 'all', 'limit' => 100])->toArray();
    }

    public function checkout(array $data, string $key): array
    {
        return $this->client()->checkout->sessions->create($data, ['idempotency_key' => $key])->toArray();
    }

    public function session(string $id): array
    {
        return $this->client()->checkout->sessions->retrieve($id, [])->toArray();
    }

    public function expire(string $id): array
    {
        return $this->client()->checkout->sessions->expire($id, [])->toArray();
    }

    public function portal(array $data): array
    {
        return $this->client()->billingPortal->sessions->create($data)->toArray();
    }

    public function subscription(string $id): array
    {
        return $this->client()->subscriptions->retrieve($id, ['expand' => ['latest_invoice']])->toArray();
    }

    public function invoice(string $id): array
    {
        return $this->client()->invoices->retrieve($id, [])->toArray();
    }
}

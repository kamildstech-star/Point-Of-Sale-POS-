<?php

namespace Tests\Fakes;

use App\Domain\Billing\StripeGateway;

class FakeStripeGateway extends StripeGateway
{
    public int $customerCalls = 0;

    public array $checkouts = [];

    public array $current = [];

    public array $currentInvoice = [];

    public array $existing = [];

    public function customer(array $data, string $key): array
    {
        $this->customerCalls++;

        return ['id' => 'cus_fixture'];
    }

    public function price(string $id): array
    {
        return ['id' => $id, 'active' => true, 'currency' => 'usd', 'unit_amount' => 1900, 'recurring' => ['interval' => 'month', 'interval_count' => 1]];
    }

    public function subscriptions(string $customer): array
    {
        return ['data' => $this->existing, 'has_more' => false];
    }

    public function checkout(array $data, string $key): array
    {
        $this->checkouts[] = ['data' => $data, 'key' => $key];

        return ['id' => 'cs_fixture', 'url' => 'https://checkout.stripe.com/c/pay/test_fixture', 'expires_at' => $data['expires_at']];
    }

    public function session(string $id): array
    {
        return ['id' => $id, 'customer' => 'cus_fixture', 'subscription' => 'sub_fixture', 'status' => 'complete'];
    }

    public function portal(array $data): array
    {
        return ['url' => 'https://billing.stripe.com/p/session/test_fixture'];
    }

    public function subscription(string $id): array
    {
        return $this->current;
    }

    public function invoice(string $id): array
    {
        return $this->currentInvoice;
    }
}

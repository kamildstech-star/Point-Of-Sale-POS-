<?php

namespace App\Console\Commands;

use App\Domain\Billing\StripeGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConfigureStripePrice extends Command
{
    protected $signature = 'billing:configure-price {code} {stripe_price} {--enable-checkout : Explicitly enable this configured price}';

    protected $description = 'Verify and bind a real Stripe Price to a provisional database price; never invent Price IDs';

    public function handle(StripeGateway $gateway): int
    {
        try {
            $p = DB::table('plan_prices')->where('code', $this->argument('code'))->first();
            if (! $p || ! preg_match('/^price_[A-Za-z0-9]+$/D', $this->argument('stripe_price'))) {
                throw new \RuntimeException('Unknown plan price or invalid Price ID.');
            }$remote = $gateway->price($this->argument('stripe_price'));
            if (empty($remote['active']) || ($remote['currency'] ?? '') !== strtolower($p->currency_code) || (string) ($remote['unit_amount'] ?? '') !== bcmul((string) $p->amount, '100', 0) || ($remote['recurring']['interval'] ?? '') !== ($p->interval === 'yearly' ? 'year' : 'month') || ($remote['recurring']['interval_count'] ?? 1) !== 1) {
                throw new \RuntimeException('Stripe Price does not match configured currency, amount and interval.');
            }DB::table('plan_prices')->where('id', $p->id)->update(['stripe_price_id' => $remote['id'], 'checkout_enabled' => (bool) $this->option('enable-checkout'), 'updated_at' => now()]);
            $this->info('Verified Stripe Price configured. No customer was charged.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Price configuration failed: '.class_basename($e).'. Check Stripe test configuration and the price in Stripe Dashboard.');

            return self::FAILURE;
        }
    }
}

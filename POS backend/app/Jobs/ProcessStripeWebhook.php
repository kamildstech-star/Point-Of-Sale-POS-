<?php

namespace App\Jobs;

use App\Domain\Billing\StripeWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 90;

    public function __construct(public int $eventId)
    {
    }

    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(StripeWebhookService $service): void
    {
        try {
            $service->process($this->eventId);
        } catch (\Throwable $e) {
            DB::table('billing_webhook_events')->where('id', $this->eventId)->update(['status' => 'retrying', 'error_code' => class_basename($e), 'updated_at' => now()]);
            Log::warning('stripe_webhook_retry', ['event_record' => $this->eventId, 'error_code' => class_basename($e)]);
            throw new \RuntimeException('Stripe webhook synchronization requires retry.');
        }
    }

    public function failed(?\Throwable $exception): void
    {
        DB::table('billing_webhook_events')->where('id', $this->eventId)->update(['status' => 'failed', 'error_code' => 'retry_limit', 'updated_at' => now()]);
    }
}

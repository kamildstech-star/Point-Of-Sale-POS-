<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OperationsMaintenance extends Command
{
    protected $signature = 'operations:maintenance';

    protected $description = 'Expire private exports, snapshot usage and queue pending verified Stripe events';

    public function handle(): int
    {
        foreach (DB::table('export_jobs')->where('status', 'ready')->where('expires_at', '<=', now())->get() as $e) {
            $prefix = 'tenants/'.$e->tenant_id.'/exports/';
            if (str_starts_with($e->file_path, $prefix) && ! str_contains($e->file_path, '..')) {
                Storage::disk('tenant_private')->delete($e->file_path);
            }DB::table('export_jobs')->where('id', $e->id)->update(['status' => 'expired', 'updated_at' => now()]);
        }foreach (DB::table('tenants')->get(['id']) as $t) {
            foreach (['products' => 'catalog_products', 'branches' => 'branches', 'staff' => 'tenant_memberships'] as $f => $table) {
                $q = DB::table($table)->where('tenant_id', $t->id);
                if ($f === 'staff') {
                    $q->where('status', 'active');
                }DB::table('tenant_usage')->updateOrInsert(['tenant_id' => $t->id, 'feature_code' => $f], ['quantity' => $q->count(), 'observed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
        }foreach (DB::table('billing_webhook_events')->where('provider', 'stripe')->whereIn('status', ['pending', 'retrying', 'processing'])->where('updated_at', '<', now()->subMinutes(5))->where('attempts', '<', 5)->pluck('id') as $id) {
            \App\Jobs\ProcessStripeWebhook::dispatch($id)->onConnection(config('stripe_billing.queue_connection'));
        }$this->info('Maintenance completed without deleting business records.');

        return self::SUCCESS;
    }
}

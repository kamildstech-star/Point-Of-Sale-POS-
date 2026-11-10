<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Dashboard\DashboardPeriod;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Platform-only, aggregate allowlist. No tenant identities, invoices or provider payloads. */
class PlatformDashboardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(config('dashboard.enabled') && config('dashboard.platform_enabled'), 404);
        $data = $request->validate(['from' => ['sometimes', 'date_format:Y-m-d'], 'to' => ['sometimes', 'date_format:Y-m-d'],
            'tenant_id' => ['prohibited'], 'branch_id' => ['prohibited'], 'user_id' => ['prohibited'], 'membership_id' => ['prohibited']]);
        $to = $data['to'] ?? Carbon::now('UTC')->toDateString();
        $from = $data['from'] ?? Carbon::parse($to, 'UTC')->subDays(29)->toDateString();
        if ($from > $to || Carbon::parse($from, 'UTC')->diffInDays(Carbon::parse($to, 'UTC')) >= 366) throw ValidationException::withMessages(['to' => 'Choose an ordered range of at most 366 UTC calendar days.']);
        $period = new DashboardPeriod($from, $to, 'UTC');
        $payload = DB::transaction(function () use ($period) {
            $tenants = DB::table('tenants');
            $statuses = (clone $tenants)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->map(fn ($count) => (int) $count)->all();
            $subs = Schema::hasTable('tenant_subscriptions') ? DB::table('tenant_subscriptions')->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->map(fn ($count) => (int) $count)->all() : null;
            $days = $period->days(); $sql = 'CASE'; $bindings = [];
            foreach ($days as $day => [$start, $end]) { $sql .= ' WHEN created_at >= ? AND created_at < ? THEN \''.$day.'\''; array_push($bindings, $start, $end); }
            $growth = (clone $tenants)->where('created_at', '>=', $period->start())->where('created_at', '<', $period->end())->selectRaw($sql.' END AS day, COUNT(*) AS total', $bindings)->groupBy('day')->pluck('total', 'day');
            return ['tenants' => ['total' => (clone $tenants)->count(), 'non_archived' => (clone $tenants)->where('status', '<>', 'archived')->count(), 'statuses' => $statuses],
                'subscriptions' => $subs, 'tenant_growth' => array_map(fn ($day) => ['date' => $day, 'created' => (int) ($growth[$day] ?? 0)], array_keys($days)),
                'incidents' => [
                    'failed_billing_events' => Schema::hasTable('billing_webhook_events') ? DB::table('billing_webhook_events')->where('status', 'failed')->count() : null,
                    'pending_billing_events' => Schema::hasTable('billing_webhook_events') ? DB::table('billing_webhook_events')->whereIn('status', ['pending', 'processing'])->count() : null,
                    'unpaid_provider_invoices' => Schema::hasTable('saas_invoices') ? DB::table('saas_invoices')->whereIn('status', ['open', 'uncollectible'])->count() : null,
                    'failed_queue_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null],
                'mrr' => null, 'mrr_unavailable_reason' => 'Authoritative recurring revenue including discounts, currency and billing adjustments is not materialized. Provisional plan prices are not MRR.',
                'basis' => 'Tenant creation dates are UTC; current tenant/subscription statuses and processing exceptions are snapshots. Subscription status counts include legacy/grandfathered records and do not assert paid access. Invoice counts describe recorded open/uncollectible invoices, not confirmed failed charges. Platform support receives aggregates only; no tenant identities, transaction details, provider IDs, event payloads or queue payloads.'];
        });
        return response()->json(['data' => $payload, 'meta' => ['scope' => 'platform', 'user_id' => $request->user()->id, 'from' => $from, 'to' => $to, 'timezone' => 'UTC', 'generated_at' => now()->toIso8601String()]])
            ->header('Cache-Control', 'private, no-store')->header('Vary', 'Cookie');
    }
}

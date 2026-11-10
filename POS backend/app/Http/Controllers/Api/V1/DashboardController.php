<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Dashboard\DashboardCapabilities;
use App\Domain\Dashboard\DashboardPeriod;
use App\Domain\Dashboard\DashboardService;
use App\Domain\Dashboard\SpecialistDashboardService;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use TenantQueries;

    private const ENDPOINTS = [
        'summary' => [null, 'summary'], 'sales-trend' => ['sales-trend', 'trend'], 'branch-performance' => ['branch-performance', 'branches'],
        'top-products' => ['top-products', 'topProducts'], 'recent-sales' => ['recent-sales', 'recentSales'], 'alerts' => ['alerts', 'alerts'],
        'approvals' => ['approvals', 'approvals'], 'shift-summary' => ['shift', 'shift'], 'inventory-summary' => ['inventory', 'inventory'],
        'purchasing-summary' => ['purchasing', 'purchasing'], 'accounting-summary' => ['accounting', 'accounting'],
        'cashier-summary' => ['cashier', 'cashier'], 'staff-activity' => ['staff-activity', 'staffActivity'],
        'inventory-trend' => ['inventory-trend', 'inventoryTrend'], 'purchase-orders' => ['purchase-orders', 'purchaseOrders'], 'supplier-overview' => ['supplier-overview', 'supplierOverview'],
        'accounting-trend' => ['accounting-trend', 'accountingTrend'], 'accounting-exceptions' => ['accounting-exceptions', 'accountingExceptions'], 'personal-sales' => ['personal-sales', 'personalSales'],
    ];

    public function index(Request $request, string $resource, ?int $id = null)
    {
        abort_unless(config('dashboard.enabled'), 404);
        $this->permit('dashboard.view');
        [$widget, $method] = self::ENDPOINTS[$resource] ?? abort(404);
        $capabilities = new DashboardCapabilities($this->context());
        if ($widget !== null) {
            abort_unless($capabilities->allows($widget), 403);
        }
        abort_if($id !== null && $resource !== 'recent-sales', 404);
        $period = DashboardPeriod::fromRequest($request, $this->context());
        abort_if($period->cashSessionId !== null && $resource !== 'cashier-summary', 422, 'Select a shift only for the cashier view.');
        abort_if($resource === 'staff-activity' && $period->branchId === null, 422, 'Select a permitted branch for staff activity.');
        // Re-resolve membership/permissions and validate branch selection BEFORE cache access.
        // The fingerprint includes actor, all effective grants and explicit restrictions.
        $revision = (int) $this->query('audit_logs')->max('id');
        $key = 'dashboard:v4:'.$this->context()->id().':'.hash('sha256', json_encode([
            $this->context()->accessFingerprint(), $capabilities->toArray(), $period->key(), $resource, $id,
            $revision, $this->context()->tenant()->currency_code, config('app.timezone'),
        ], JSON_THROW_ON_ERROR));
        $ttl = max(0, min(300, (int) config('dashboard.cache_seconds')));
        $cached = $ttl ? Cache::get($key) : null;
        $hit = is_array($cached);
        if (! $hit) {
            $cached = DB::transaction(function () use ($method, $period, $id) {
                $service = in_array($method, ['inventoryTrend', 'purchaseOrders', 'supplierOverview', 'accountingTrend', 'accountingExceptions'], true) ? new SpecialistDashboardService($period) : new DashboardService($period);

                return ['payload' => $id === null ? $service->$method() : $service->$method($id), 'generated_at' => now()->toIso8601String(), 'currency_scale' => $service->currencyScale()];
            });
            if ($ttl) {
                Cache::put($key, $cached, $ttl);
            }
        }
        $meta = ['tenant_id' => $this->context()->id(), 'access_fingerprint' => $this->context()->accessFingerprint(),
            'scope' => $this->context()->branchRestricted() || $period->branchId !== null ? 'branch' : 'tenant',
            'branch_ids' => $period->branchId === null ? $this->context()->allowedBranchIds() : [$period->branchId],
            'sales_scope' => in_array($resource, ['cashier-summary', 'personal-sales'], true) || $capabilities->ownSales() ? 'own' : 'permitted_branches', 'currency' => $this->context()->tenant()->currency_code,
            'cash_session_id' => $period->cashSessionId,
            'currency_scale' => $cached['currency_scale'], 'timezone' => $period->timezone, 'from' => $period->from, 'to' => $period->to,
            'start_inclusive' => $period->start()->toIso8601String(), 'end_exclusive' => $period->end()->toIso8601String(),
            'generated_at' => $cached['generated_at'], 'data_revision' => $revision, 'cache_hit' => $hit, 'max_cache_age_seconds' => $ttl,
            'includes_unassigned_locations' => ! $this->context()->branchRestricted() && $period->branchId === null];

        return (new DashboardResource(['payload' => $cached['payload'], 'meta' => $meta]))->response()->header('Cache-Control', 'private, no-store')->header('Vary', 'Cookie');
    }
}

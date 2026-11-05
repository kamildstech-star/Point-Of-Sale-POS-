<?php

// Executes real driver SQL against existing records, inside a read-only transaction.
// Does not log in, seed, charge, write cache, create transactions or assign access.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Dashboard\DashboardPeriod;
use App\Domain\Dashboard\DashboardService;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$member = DB::table('tenant_memberships as m')->join('tenant_roles as r', fn ($j) => $j->on('r.id', '=', 'm.role_id')->on('r.tenant_id', '=', 'm.tenant_id'))->where('m.status', 'active')->where('r.code', 'owner')->orderBy('m.id')->first(['m.tenant_id', 'm.user_id']);
if (! $member) {
    throw new RuntimeException('No active owner membership exists for a read-only SQL smoke check.');
}
if (DB::getDriverName() === 'mysql') {
    DB::statement('SET TRANSACTION READ ONLY');
}
$checked = DB::transaction(fn () => app(TenantContext::class)->runFor($member->tenant_id, $member->user_id, function ($context) {
    $today = Carbon::now($context->tenant()->timezone);
    $service = new DashboardService(new DashboardPeriod($today->copy()->subDays(29)->toDateString(), $today->toDateString(), $context->tenant()->timezone));
    $checks = [];
    foreach (['summary', 'trend', 'branches', 'topProducts', 'recentSales', 'inventory', 'purchasing', 'shift', 'accounting', 'approvals', 'alerts'] as $method) {
        $payload = $service->$method();
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid dashboard DTO.');
        }
        json_encode($payload, JSON_THROW_ON_ERROR);
        $checks[$method] = true;
    }

    return $checks;
}));
$report = ['checked_at' => now()->toIso8601String(), 'driver' => DB::getDriverName(), 'read_only' => true, 'existing_records_only' => true, 'checks' => $checked];
file_put_contents(dirname(__DIR__, 2).'/docs/audit/dashboard-d2-sql-smoke.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'Read-only dashboard SQL smoke passed: '.count($checked).' operations on '.DB::getDriverName().'.'.PHP_EOL;

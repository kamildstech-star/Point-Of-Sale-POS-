<?php

// Real-driver SQL validation only. No login, seeding, cache or working-data writes.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domain\Dashboard\DashboardPeriod;
use App\Domain\Dashboard\DashboardService;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$member = DB::table('tenant_memberships as m')->join('tenant_roles as r', fn ($j) => $j->on('r.id', '=', 'm.role_id')->on('r.tenant_id', '=', 'm.tenant_id'))
    ->where('m.status', 'active')->where('r.code', 'owner')->orderBy('m.id')->first(['m.tenant_id', 'm.user_id']);
if (! $member) {
    throw new RuntimeException('An active owner membership is required for this read-only SQL check.');
}
if (DB::getDriverName() === 'mysql') {
    DB::statement('SET TRANSACTION READ ONLY');
}
$checks = DB::transaction(fn () => app(TenantContext::class)->runFor($member->tenant_id, $member->user_id, function ($context) {
    $today = Carbon::now($context->tenant()->timezone);
    $branch = $context->allowedBranchIds()[0] ?? throw new RuntimeException('A permitted branch is required for staff activity SQL verification.');
    $service = new DashboardService(new DashboardPeriod($today->copy()->subDays(29)->toDateString(), $today->toDateString(), $context->tenant()->timezone, $branch));
    $checked = [];
    foreach (['summary', 'trend', 'branches', 'topProducts', 'recentSales', 'inventory', 'purchasing', 'shift', 'accounting', 'approvals', 'alerts', 'cashier', 'staffActivity'] as $method) {
        $data = $service->$method();
        if (! is_array($data)) {
            throw new RuntimeException('Invalid dashboard DTO.');
        }
        json_encode($data, JSON_THROW_ON_ERROR);
        $checked[$method] = true;
    }

    return $checked;
}));
$report = ['checked_at' => now()->toIso8601String(), 'driver' => DB::getDriverName(), 'read_only' => true, 'working_data_writes' => 0, 'checks' => $checks];
file_put_contents(dirname(__DIR__, 2).'/docs/audit/dashboard-d4-sql-smoke.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'Read-only D4 SQL smoke passed: '.count($checks).' dashboard operations.'.PHP_EOL;

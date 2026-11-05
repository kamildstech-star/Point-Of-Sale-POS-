<?php
use App\Domain\Operations\Conflict;
use App\Domain\Operations\PurchasingService;
use App\Domain\Operations\SaleService;
use App\Domain\Tenancy\TenantContext;

require __DIR__.'/phase-4-5-stage.php';
try {
    $jobPath = realpath($argv[1] ?? ''); $privateRoot = realpath(__DIR__.'/../docs/audit/private');
    if (! $jobPath || ! str_starts_with($jobPath, $privateRoot.DIRECTORY_SEPARATOR.'phase45-run-') || ! preg_match('/job-[a-f0-9]+\.json$/D', $jobPath)) throw new RuntimeException('Invalid worker fixture path.');
    $job = json_decode(file_get_contents($jobPath), true, 512, JSON_THROW_ON_ERROR);
    $result = app(TenantContext::class)->runFor($job['tenant'], $job['owner'], function () use ($job, $jobPath) {
        file_put_contents($jobPath.'.ready', 'ready'); $deadline = microtime(true) + 20;
        while (! file_exists(dirname($jobPath).'/start')) { if (microtime(true) > $deadline) throw new RuntimeException('Barrier timed out.'); usleep(10000); }
        return match ($job['operation']) {
            'checkout' => app(SaleService::class)->checkout($job['input']),
            'receipt' => app(PurchasingService::class)->receive($job['source_id'], $job['input']),
            'return' => app(PurchasingService::class)->returnGoods($job['source_id'], $job['input']),
            default => throw new RuntimeException('Unknown fixture operation.'),
        };
    });
    echo json_encode(['status' => 'ok', 'result' => $result], JSON_THROW_ON_ERROR);
} catch (Conflict $e) { echo json_encode(['status' => 'conflict', 'message' => $e->getMessage()]); }
catch (Throwable $e) { echo json_encode(['status' => 'error', 'class' => $e::class, 'message' => $e->getMessage()]); exit(1); }

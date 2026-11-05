<?php

namespace App\Console\Commands;

use App\Domain\Catalog\LegacyCatalogMapper;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MapLegacyCatalog extends Command
{
    protected $signature = 'legacy:map-catalog {--owner= : Legacy owner user ID} {--location= : Verified location ID} {--approve-distinct : Record the business owner-approved distinct-SKU policy}';
    protected $description = 'Idempotently reconcile each legacy stock row to a variant or explicit exception; does not post stock';
    public function handle(): int
    {
        $tenant = DB::table('tenants')->where('legacy_key', 'best-uniform')->first();
        if (! $tenant || ! $this->option('owner') || ! $this->option('location')) { $this->error('Supply verified --owner and --location IDs after legacy:bootstrap.'); return self::FAILURE; }
        return app(TenantContext::class)->runFor($tenant->id, (int) $this->option('owner'), function ($context) {
            abort_unless($context->isOwner() && in_array($context->tenant()->status, ['active', 'trialing']), 403);
            if ($this->option('approve-distinct')) DB::transaction(function () use ($context) {
                DB::table('tenants')->where('id', $context->id())->update(['sku_policy' => 'distinct_by_school', 'sku_policy_approved_at' => now(), 'sku_policy_approved_by' => $context->membership()->user_id, 'updated_at' => now()]);
                app(TenantService::class)->audit('legacy.sku_policy.approved', 'tenant', $context->id(), ['policy' => 'distinct_by_school', 'approval' => 'Explicit business-owner instruction']);
            });
            $result = app(LegacyCatalogMapper::class)->run((int) $this->option('location'));
            $this->line(json_encode($result, JSON_THROW_ON_ERROR)); return self::SUCCESS;
        });
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Catalog\LegacyCatalogMapper;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LegacyMappingController extends Controller
{
    use TenantQueries;

    public function index()
    {
        $this->permit('legacy.view');
        return response()->json(['data' => [
            'stocks' => $this->query('stocks')->orderBy('id')->paginate(25),
            'mappings' => $this->query('legacy_record_mappings')->orderBy('id')->paginate(25, ['*'], 'mapping_page'),
            'exceptions' => $this->query('migration_exceptions')->where('resolution_status', 'unresolved')->orderBy('id')->paginate(25, ['*'], 'exception_page'),
            'batch' => $this->query('migration_batches')->where('batch_code', 'legacy-catalog-v1')->first(),
            'sku_policy' => $this->context()->tenant()->sku_policy,
        ]]);
    }

    public function approve(Request $request)
    {
        $this->permit('legacy.manage'); abort_unless($this->context()->isOwner() && $this->context()->tenant()->legacy_key === 'best-uniform', 403);
        $request->validate(['policy' => ['required', 'in:distinct_by_school'], 'confirmed' => ['required', 'accepted']]);
        DB::transaction(function () {
            DB::table('tenants')->where('id', $this->context()->id())->update(['sku_policy' => 'distinct_by_school', 'sku_policy_approved_at' => now(), 'sku_policy_approved_by' => $this->context()->membership()->user_id, 'updated_at' => now()]);
            $this->audit('legacy.sku_policy.approved', 'tenant', $this->context()->id(), ['policy' => 'distinct_by_school']);
        });
        return response()->json(['data' => ['policy' => 'distinct_by_school']]);
    }

    public function map(Request $request)
    {
        $this->permit('legacy.manage');
        $data = $request->validate(['location_id' => ['required', 'integer', $this->exists('locations')]]);
        return response()->json(['data' => app(LegacyCatalogMapper::class)->run($data['location_id'])]);
    }
}

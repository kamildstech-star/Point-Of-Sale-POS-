<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Operations\DataTransferService;
use App\Domain\Operations\WorkspaceService;
use App\Domain\Tenancy\TenantQueries;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    use TenantQueries;

    public function settings(WorkspaceService $s)
    {
        $this->permit('settings.manage');

        return response()->json(['data' => $s->settings()]);
    }

    public function saveSettings(Request $r, WorkspaceService $s)
    {
        $this->permit('settings.manage');
        $data = $r->validate(['receipt_name' => ['nullable', 'string', 'max:255'], 'receipt_footer' => ['required', 'string', 'max:1000'], 'tax_registration' => ['nullable', 'string', 'max:100'], 'return_days' => ['nullable', 'integer', 'between:0,3650'], 'low_stock_threshold' => ['nullable', 'string', 'regex:/^\d{1,12}(?:\.\d{1,4})?$/D']]);

        return response()->json(['data' => $s->saveSettings($data)]);
    }

    public function auditLog()
    {
        $this->permit('audit.view');
        $rows = $this->query('audit_logs')->orderByDesc('id')->paginate(50);
        foreach ($rows as $row) {
            $data = json_decode($row->details_json ?? '{}', true) ?? [];
            $scrub = function (array $values) use (&$scrub) {
                foreach ($values as $k => &$v) {
                    if (preg_match('/password|secret|token|credential|cookie|authorization/i', (string) $k)) {
                        $v = '[redacted]';
                    } elseif (is_array($v)) {
                        $v = $scrub($v);
                    }
                }

return $values;
            };
            $row->details_json = json_encode($scrub($data));
        }

return response()->json($rows);
    }

    public function alerts()
    {
        $this->permit('alerts.view');

        return response()->json($this->query('tenant_notifications')->orderByDesc('id')->paginate(50));
    }

    public function readAlert(int $id)
    {
        $this->permit('alerts.view');
        $this->row('tenant_notifications', $id);
        $this->query('tenant_notifications')->where('id', $id)->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['data' => ['id' => $id]]);
    }

    public function templates()
    {
        $this->permit('settings.manage');

        return response()->json(['data' => $this->query('notification_templates')->get()]);
    }

    public function saveTemplate(Request $r, string $code, WorkspaceService $s)
    {
        $this->permit('settings.manage');
        abort_unless(in_array($code, ['stock.low', 'billing.review']), 404);
        $data = $r->validate(['body_template' => ['required', 'string', 'max:2000'], 'active' => ['required', 'boolean']]);
        preg_match_all('/{{\s*([^}]+)\s*}}/', $data['body_template'], $matches);
        foreach ($matches[1] as $v) {
            if (! in_array(trim($v), ['sku', 'quantity', 'invoice_no', 'business_name', 'reference'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['body_template' => 'Unknown template variable. Use sku, quantity, invoice_no, business_name or reference.']);
            }
        }
        $data['body_template'] = preg_replace_callback('/{{\s*([^}]+?)\s*}}/', fn ($match) => '{{'.trim($match[1]).'}}', $data['body_template']);

return $s->atomic(function () use ($code, $data) {
            $this->query('notification_templates')->updateOrInsert(['tenant_id' => $this->context()->id(), 'event_code' => $code], $data + ['created_at' => now(), 'updated_at' => now()]);
            $this->audit('notification.template.saved', 'template', null, ['event' => $code]);

            return response()->json(['data' => ['event_code' => $code]]);
        });
    }

    public function imports()
    {
        $this->permit('imports.manage');

        return response()->json($this->query('import_batches')->orderByDesc('id')->paginate(25, ['id', 'type', 'status', 'errors_json', 'posted_at', 'created_at']));
    }

    public function previewImport(Request $r, DataTransferService $s)
    {
        $this->permit('imports.manage');
        $this->permit('products.manage');
        $r->validate(['file' => ['required', 'file', 'max:2048', 'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel']]);

        return response()->json(['data' => $s->preview($r->file('file'))], 201);
    }

    public function postImport(int $id, DataTransferService $s)
    {
        $this->permit('imports.manage');
        $this->permit('products.manage');

        return response()->json(['data' => $s->post($id)]);
    }

    public function exports()
    {
        $this->permit('exports.manage');

        return response()->json($this->query('export_jobs')->orderByDesc('id')->paginate(25, ['id', 'type', 'status', 'expires_at', 'row_count', 'created_at']));
    }

    public function createExport(Request $r, DataTransferService $s)
    {
        $this->permit('exports.manage');
        $data = $r->validate(['type' => ['required', Rule::in(array_keys(DataTransferService::EXPORTS))]]);

        return response()->json(['data' => $s->export($data['type'])], 201);
    }

    public function download(int $id, DataTransferService $s)
    {
        $this->permit('exports.manage');

        return $s->download($id);
    }

    public function support()
    {
        $this->permit('support.manage');

        return response()->json($this->query('support_cases')->orderByDesc('id')->paginate(25));
    }

    public function createSupport(Request $r)
    {
        $this->permit('support.manage');
        $data = $r->validate(['subject' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:5000']]);
        $id = DB::table('support_cases')->insertGetId($data + ['tenant_id' => $this->context()->id(), 'created_by' => $this->context()->membership()->id, 'reference' => 'CASE-'.strtoupper(bin2hex(random_bytes(8))), 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
        $this->audit('support.case.opened', 'support_case', $id);

        return response()->json(['data' => ['id' => $id]], 201);
    }

    public function operations()
    {
        $this->permit('settings.manage');
        $last = Schema::hasTable('operational_runs') ? DB::table('operational_runs')->orderByDesc('id')->first(['operation', 'status', 'started_at', 'finished_at']) : null;

        return response()->json(['data' => ['last_operator_run' => $last, 'stripe_configured' => (bool) config('stripe_billing.enabled'), 'live_charging' => (bool) config('stripe_billing.live_enabled'), 'queue_connection' => config('stripe_billing.queue_connection'), 'backups_scheduled' => (bool) config('retail.scheduled_backups'), 'support' => 'Cases are tenant scoped; operator actions require CLI access and are audited.']]);
    }

    public function readiness()
    {
        try {
            DB::select('SELECT 1');
            Cache::get('retail:readiness');

            return response()->json(['ready' => true]);
        } catch (\Throwable) {
            return response()->json(['ready' => false], 503);
        }
    }
}

<?php

namespace App\Domain\Operations;

use Illuminate\Support\Facades\Schema;

class WorkspaceService extends Operation
{
    public function settings(): array
    {
        $defaults = ['receipt_name' => null, 'receipt_footer' => 'Thank you for your purchase.', 'tax_registration' => null, 'return_days' => null, 'low_stock_threshold' => null];
        if (! Schema::hasTable('tenant_settings')) {
            return $defaults;
        }
        foreach ($this->query('tenant_settings')->get() as $s) {
            if (array_key_exists($s->setting_key, $defaults)) {
                $defaults[$s->setting_key] = json_decode($s->value_json, true, 512, JSON_THROW_ON_ERROR);
            }
        }

        return $defaults;
    }

    public function saveSettings(array $data): array
    {
        return $this->atomic(function () use ($data) {
            foreach ($data as $key => $value) {
                $this->query('tenant_settings')->updateOrInsert(['tenant_id' => $this->context()->id(), 'setting_key' => $key], ['value_json' => json_encode($value, JSON_THROW_ON_ERROR), 'updated_at' => now(), 'created_at' => now()]);
            }$this->audit('workspace.settings.updated', 'tenant', $this->context()->id(), ['keys' => array_keys($data)]);

            return $this->settings();
        });
    }

    public function notify(string $event, string $key, array $variables, array $payload = []): void
    {
        if (! Schema::hasTable('tenant_notifications')) {
            return;
        }$template = $this->query('notification_templates')->where('event_code', $event)->first();
        if ($template && ! $template->active) {
            return;
        }
        $text = $template?->body_template ?? match ($event) {
            'stock.low' => 'Low stock: {{sku}} has {{quantity}} available.','billing.review' => 'Billing payment requires operator review: {{invoice_no}}.',default => 'Workspace event: {{reference}}.'
        };
        $replace = [];
        foreach ($variables as $k => $v) {
            $replace['{{'.$k.'}}'] = (string) $v;
        }$body = strtr($text, $replace);
        $this->query('tenant_notifications')->insertOrIgnore(['tenant_id' => $this->context()->id(), 'event_code' => $event, 'event_key' => hash('sha256', $key), 'body' => $body, 'payload_json' => json_encode($payload, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function legacyReconcile(): array
    {
        return $this->atomic(function () {
            abort_unless($this->context()->tenant()->legacy_key, 403);
            $tables = [];
            $issues = [];
            foreach (['ledger', 'ledgers', 'daily_account', 'orders', 'order_items', 'returns', 'return_items', 'business_dealers'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }$sum = [];
                $rows = $this->query($table)->orderBy('id')->get();
                $hash = hash_init('sha256');
                foreach ($rows as $row) {
                    $raw = json_encode((array) $row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                    $checksum = hash('sha256', $raw);
                    hash_update($hash, $raw."\n");
                    $this->query('legacy_financial_snapshots')->insertOrIgnore(['tenant_id' => $this->context()->id(), 'source_table' => $table, 'source_pk' => $row->id, 'original_json' => $raw, 'source_checksum' => $checksum, 'refund_status' => 'unknown', 'imported_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                    foreach (['debit', 'credit', 'balance', 'sale', 'total_in', 'total_out', 'closing_balance', 'total', 'received_amount', 'remaining_amount'] as $f) {
                        if (isset($row->$f) && is_numeric($row->$f)) {
                            $sum[$f] = Decimal::add($sum[$f] ?? '0', (string) $row->$f);
                        }
                    }
                    if (in_array($table, ['ledger', 'ledgers'])) {
                        if (isset($row->ledgerable_id) && ! $this->query('business_dealers')->where('id', $row->ledgerable_id)->exists()) {
                            $issues[] = ['table' => $table, 'id' => $row->id, 'issue' => 'missing_dealer'];
                        }if (isset($row->balance)) {
                            $issues[] = ['table' => $table, 'id' => $row->id, 'issue' => 'legacy_balance_sign_requires_review', 'recorded_balance' => (string) $row->balance];
                        }
                    }
                    if ($table === 'daily_account' && isset($row->closing_balance,$row->total_in,$row->total_out) && Decimal::cmp((string) $row->closing_balance, Decimal::sub((string) $row->total_in, (string) $row->total_out)) !== 0) {
                        $issues[] = ['table' => $table, 'id' => $row->id, 'issue' => 'closing_basis_requires_review'];
                    }
                }$tables[$table] = ['count' => $rows->count(), 'totals' => $sum, 'sha256' => hash_final($hash)];
            }
            if (($tables['ledger']['count'] ?? 0) > 0 && ($tables['ledgers']['count'] ?? 0) > 0) {
                $issues[] = ['issue' => 'parallel_ledger_namespaces_do_not_merge_automatically'];
            }
            $report = ['tables' => $tables, 'issues' => $issues, 'refund_history' => 'unknown', 'accounting_migration_allowed' => ! $issues && array_sum(array_column($tables, 'count')) === 0, 'note' => 'Recorded figures retained. Dealer balance signs, daily carry-forward and historical refunds require source evidence; no journal or refund is inferred.'];
            $id = $this->insert('financial_reconciliations', ['created_by' => $this->actor(), 'kind' => 'legacy_financials', 'status' => $report['accounting_migration_allowed'] ? 'empty_baseline' : 'needs_review', 'report_json' => json_encode($report, JSON_THROW_ON_ERROR), 'captured_at' => now()]);
            $this->audit('legacy.financials.reconciled', 'financial_reconciliation', $id);

            return ['id' => $id] + $report;
        });
    }
}

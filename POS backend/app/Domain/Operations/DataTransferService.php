<?php

namespace App\Domain\Operations;

use App\Domain\Billing\TenantAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DataTransferService extends Operation
{
    public const EXPORTS = ['catalog' => ['catalog_variants', ['id', 'product_id', 'sku', 'name', 'default_cost', 'default_price', 'active'], 'products.view'], 'sales' => ['sales', ['id', 'invoice_no', 'sold_at', 'customer_id', 'currency_code', 'subtotal', 'discount_total', 'tax_total', 'grand_total', 'returned_total', 'paid_total', 'total_refunded', 'payment_status'], 'sales.view'], 'customers' => ['customers', ['id', 'name', 'phone', 'email', 'active'], 'customers.view'], 'stock-movements' => ['stock_movements', ['id', 'location_id', 'variant_id', 'quantity_delta', 'movement_type', 'source_event_id', 'occurred_at'], 'inventory.view']];

    public function preview(UploadedFile $file): array
    {
        $raw = file_get_contents($file->getRealPath());
        if (! mb_check_encoding($raw, 'UTF-8')) {
            Decimal::invalid('Upload a UTF-8 CSV file.');
        }$stream = fopen('php://temp', 'w+');
        fwrite($stream, $raw);
        rewind($stream);
        $header = fgetcsv($stream);
        if (! $header) {
            Decimal::invalid('CSV header is missing.');
        }$header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        $expected = ['sku', 'name', 'unit_code', 'default_cost', 'default_price', 'product_type'];
        if ($header !== $expected) {
            Decimal::invalid('CSV columns must be: '.implode(',', $expected));
        }$rows = [];
        $errors = [];
        $seen = [];
        $line = 1;
        while (($values = fgetcsv($stream)) !== false) {
            $line++;
            if ($values === [null]) {
                continue;
            }if ($line > 1001) {
                Decimal::invalid('Import at most 1,000 products at a time.');
            }if (count($values) !== count($header)) {
                $errors[] = ['row' => $line, 'message' => 'Column count differs from header.'];

                continue;
            }$row = array_combine($header, $values);
            $row['sku'] = strtoupper(trim($row['sku']));
            $v = Validator::make($row, ['sku' => ['required', 'regex:/^[A-Z0-9_.:-]{1,100}$/D'], 'name' => ['required', 'string', 'max:255'], 'unit_code' => ['required', 'string', 'max:30'], 'default_cost' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,4})?$/D'], 'default_price' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,4})?$/D'], 'product_type' => ['required', 'in:simple,service,digital']]);
            $unit = $this->query('catalog_units')->where('code', $row['unit_code'])->where('active', true)->first();
            if ($v->fails() || ! $unit || isset($seen[$row['sku']]) || $this->query('catalog_variants')->where('sku', $row['sku'])->exists()) {
                $errors[] = ['row' => $line, 'message' => 'Invalid values, inactive/missing unit, or duplicate SKU.'];

                continue;
            }$seen[$row['sku']] = true;
            $rows[] = $row + ['unit_id' => $unit->id];
        }fclose($stream);
        if (! $rows && ! $errors) {
            Decimal::invalid('CSV has no product rows.');
        }$path = $file->store($this->context()->storagePath('imports'), 'tenant_private');
        $id = $this->atomic(fn () => $this->insert('import_batches', ['initiated_by' => $this->actor(), 'type' => 'catalog', 'storage_path' => $path, 'status' => $errors ? 'invalid' : 'ready', 'rows_json' => json_encode($rows, JSON_THROW_ON_ERROR), 'errors_json' => json_encode($errors, JSON_THROW_ON_ERROR)]));

        return ['id' => $id, 'status' => $errors ? 'invalid' : 'ready', 'valid_rows' => count($rows), 'errors' => $errors];
    }

    public function post(int $id): array
    {
        return $this->atomic(function () use ($id) {
            $batch = $this->row('import_batches', $id);
            if ($batch->status === 'posted') {
                return ['id' => $id, 'status' => 'posted'];
            }if ($batch->status !== 'ready') {
                throw new Conflict('Resolve CSV errors and upload a corrected batch before importing.');
            }$rows = json_decode($batch->rows_json, true, 512, JSON_THROW_ON_ERROR);
            app(TenantAccess::class)->capacity('products', count($rows));
            foreach ($rows as $r) {
                $unit = $this->row('catalog_units', $r['unit_id']);
                if (! $unit->active || $this->query('catalog_variants')->where('sku', $r['sku'])->exists()) {
                    throw new Conflict('Catalog changed since preview. Upload a fresh import.');
                }$product = $this->insert('catalog_products', ['name' => $r['name'], 'product_type' => $r['product_type'], 'track_inventory' => $r['product_type'] === 'simple', 'active' => true]);
                $this->insert('catalog_variants', ['product_id' => $product, 'base_unit_id' => $unit->id, 'sku' => $r['sku'], 'default_cost' => $r['default_cost'], 'default_price' => $r['default_price'], 'tracking_mode' => 'none', 'active' => true]);
            }$this->update('import_batches', $id, ['status' => 'posted', 'posted_at' => now()]);
            $this->audit('catalog.import.posted', 'import_batch', $id, ['rows' => count($rows)]);

            return ['id' => $id, 'status' => 'posted', 'rows' => count($rows)];
        });
    }

    public function export(string $type): array
    {
        [$table,$fields,$permission] = self::EXPORTS[$type] ?? abort(404);
        $this->permit($permission);
        if ($type === 'catalog' && ! $this->context()->canViewCosts()) $fields = array_values(array_diff($fields, ['default_cost']));
        $rows = DB::transaction(fn () => $this->query($table)->orderBy('id')->limit(50001)->get($fields));
        if ($rows->count() > 50000) {
            throw new Conflict('Narrow this export through an operator job; interactive exports are limited to 50,000 rows.');
        }$stream = fopen('php://temp', 'w+');
        fputcsv($stream, $fields);
        foreach ($rows as $r) {
            fputcsv($stream, array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, (array) $r));
        }rewind($stream);
        $path = $this->context()->storagePath('exports/'.bin2hex(random_bytes(16)).'.csv');
        Storage::disk('tenant_private')->put($path, stream_get_contents($stream));
        fclose($stream);
        try {
            $access = $this->context()->hasAccessSchema() ? ['access_fingerprint' => $this->context()->accessFingerprint()] : [];
            $id = $this->insert('export_jobs', ['initiated_by' => $this->actor(), 'type' => $type, 'status' => 'ready', 'file_path' => $path, 'expires_at' => now()->addDay(), 'row_count' => $rows->count()] + $access);
            $this->audit('workspace.export.created', 'export_job', $id, ['type' => $type, 'rows' => $rows->count()]);

            return ['id' => $id, 'row_count' => $rows->count(), 'expires_at' => now()->addDay()->toIso8601String()];
        } catch (\Throwable $e) {
            Storage::disk('tenant_private')->delete($path);
            throw $e;
        }
    }

    public function download(int $id)
    {
        $job = $this->row('export_jobs', $id);
        $this->permit(self::EXPORTS[$job->type][2]);
        if ($job->type === 'catalog' && ! $this->context()->canViewCosts()) abort_unless(hash_equals($this->context()->accessFingerprint(), $job->access_fingerprint ?? ''), 403);
        abort_unless($job->status === 'ready' && now()->lt($job->expires_at) && str_starts_with($job->file_path, $this->context()->storagePath('exports/')) && ! str_contains($job->file_path, '..') && Storage::disk('tenant_private')->exists($job->file_path), 404);

        return Storage::disk('tenant_private')->download($job->file_path, $job->type.'.csv', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}

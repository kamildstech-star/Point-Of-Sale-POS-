<?php

namespace App\Domain\Operations;

class ReturnService extends Operation
{
    public function create(int $saleId, array $data): array
    {
        return $this->once('return.create', $data['idempotency_key'], ['sale_id' => $saleId] + $data, function () use ($saleId, $data) {
            $sale = $this->row('sales', $saleId);
            if ($sale->sale_status !== 'completed') {
                throw new Conflict('Only completed sales may be returned.');
            }$days = app(WorkspaceService::class)->settings()['return_days'];
            if ($days !== null && now()->gt(\Illuminate\Support\Carbon::parse($sale->sold_at)->addDays($days))) {
                throw new Conflict('This sale is outside the configured return window.');
            }$this->location($sale->location_id);
            $kind = $data['kind'] ?? 'return';
            if ($kind === 'cancel' && $this->query('sale_returns')->where('original_sale_id', $saleId)->where('status', 'posted')->exists()) {
                throw new Conflict('A sale with posted returns cannot also be cancelled.');
            }
            $id = $this->insert('sale_returns', ['original_sale_id' => $saleId, 'location_id' => $sale->location_id, 'requested_by' => $this->actor(), 'return_no' => 'RET-'.strtoupper(bin2hex(random_bytes(8))), 'kind' => $kind, 'status' => 'draft', 'reason' => $data['reason']]);
            $items = $kind === 'cancel' ? $this->query('sale_items')->where('sale_id', $saleId)->get()->map(fn ($l) => ['sale_item_id' => $l->id, 'qty' => (string) $l->qty, 'disposition' => 'resellable'])->all() : $data['items'];
            foreach ($items as $item) {
                $line = $this->row('sale_items', $item['sale_item_id']);
                if ($line->sale_id != $saleId) {
                    abort(404);
                }$qty = Decimal::value($item['qty']);
                $this->variant($line->variant_id, $qty, false, true);
                if (Decimal::cmp($qty, '0') <= 0 || Decimal::cmp(Decimal::add((string) $line->returned_qty, $qty), (string) $line->qty) > 0) {
                    throw new Conflict('Return quantity exceeds units still returnable.');
                }$this->insert('sale_return_items', ['sale_return_id' => $id, 'sale_item_id' => $line->id, 'qty' => $qty, 'disposition' => $item['disposition']]);
            }
            $this->audit('sale.return.requested', 'sale_return', $id);

            return ['id' => $id];
        });
    }

    public function approve(int $id): array
    {
        return $this->atomic(function () use ($id) {
            $r = $this->row('sale_returns', $id);
            if ($r->status === 'draft') {
                $this->update('sale_returns', $id, ['status' => 'approved', 'approved_by' => $this->actor(), 'approved_at' => now()]);
                $this->audit('sale.return.approved', 'sale_return', $id);
            }

return ['id' => $id];
        });
    }

    private function allocation(string $amount, string $qty, string $sold, int $scale): string
    {
        return Decimal::round(bcmul(bcdiv($qty, $sold, 24), $amount, 8), $scale);
    }

    public function post(int $id): array
    {
        return $this->atomic(function () use ($id) {
            $r = $this->row('sale_returns', $id);
            if ($r->status === 'posted') {
                return ['id' => $id, 'total' => (string) $r->total];
            }if ($r->status !== 'approved') {
                throw new Conflict('Approve the return before posting.');
            }$sale = $this->row('sales', $r->original_sale_id);
            if ($sale->sale_status !== 'completed') {
                throw new Conflict('The sale was already cancelled.');
            }if ($r->kind === 'cancel' && Decimal::cmp((string) $sale->returned_total, '0') > 0) {
                throw new Conflict('Cancellation cannot restock a previously returned sale.');
            }$total = '0';
            foreach ($this->query('sale_return_items')->where('sale_return_id', $id)->orderBy('sale_item_id')->get() as $item) {
                $line = $this->row('sale_items', $item->sale_item_id);
                $newQty = Decimal::add((string) $line->returned_qty, (string) $item->qty);
                if (Decimal::cmp($newQty, (string) $line->qty) > 0) {
                    throw new Conflict('Another return already consumed these units.');
                }$values = [];
                foreach (['discount_amount' => 'discount_total', 'tax_amount' => 'tax_total', 'credit_amount' => 'line_total'] as $field => $source) {
                    $values[$field] = Decimal::sub($this->allocation((string) $line->$source, $newQty, (string) $line->qty, $sale->currency_scale), $this->allocation((string) $line->$source, (string) $line->returned_qty, (string) $line->qty, $sale->currency_scale));
                }$values['gross_amount'] = Decimal::sub(Decimal::add($values['credit_amount'], $values['discount_amount']), $values['tax_amount']);
                $this->update('sale_return_items', $item->id, $values);
                $total = Decimal::add($total, $values['credit_amount']);
                if ($line->track_inventory) {
                    if ($item->disposition === 'resellable') {
                        app(InventoryPostingService::class)->post($r->location_id, $line->variant_id, (string) $item->qty, 'sale_return', 'sale_returns', $id, 'retail-return:'.$item->id, (string) $line->unit_cost_snapshot, false, true);
                    } else {
                        $this->insert('damaged_stock_records', ['return_item_id' => $item->id, 'variant_id' => $line->variant_id, 'location_id' => $r->location_id, 'quantity' => $item->qty, 'status' => 'quarantined']);
                    }
                }$this->update('sale_items', $line->id, ['returned_qty' => $newQty]);
            }
            $this->update('sale_returns', $id, ['status' => 'posted', 'total' => $total, 'posted_at' => now()]);
            $this->update('sales', $sale->id, ['returned_total' => Decimal::add((string) $sale->returned_total, $total), 'sale_status' => $r->kind === 'cancel' ? 'voided' : 'completed']);
            $this->sync($sale->id);
            $this->audit('sale.return.posted', 'sale_return', $id, ['total' => $total, 'kind' => $r->kind]);

            return ['id' => $id, 'total' => $total];
        });
    }

    private function sync(int $sale): void
    {
        $s = $this->row('sales', $sale);
        $this->update('sales', $sale, ['payment_status' => SaleAmounts::for($s)['payment_status']]);
    }

    public function refund(int $id, array $data): array
    {
        return $this->once('sale.refund', $data['idempotency_key'], ['return_id' => $id] + $data, fn () => $this->refundInternal($id, $data));
    }

    private function refundInternal(int $id, array $data, bool $exchange = false): array
    {
        $r = $this->row('sale_returns', $id);
        if ($r->status !== 'posted') {
            throw new Conflict('Post the return before recording a refund.');
        }$sale = $this->row('sales', $r->original_sale_id);
        [, $register] = app(SaleService::class)->session($data['cash_session_id']);
        if ($register->branch_id != $sale->branch_id) {
            throw new Conflict('Refunds require a shift in the original branch.');
        }$amount = Decimal::value($data['amount']);
        $method = $this->row('payment_methods', $data['method_id']);
        if (! $method->active || (! $exchange && ! in_array($method->method_type, ['cash', 'card', 'bank']))) {
            Decimal::invalid('Select an active external refund method.');
        }if (Decimal::cmp($amount, '0') <= 0 || Decimal::cmp($amount, Decimal::round($amount, $sale->currency_scale)) !== 0 || Decimal::cmp($amount, Decimal::sub((string) $r->total, (string) $r->refunded_total)) > 0 || Decimal::cmp($amount, SaleAmounts::for($sale)['refund_due']) > 0) {
            throw new Conflict('Refund exceeds the return credit or the amount collected above the remaining sale value.');
        }if ($method->method_type !== 'cash' && empty(trim($data['reference'] ?? ''))) {
            Decimal::invalid('Record the confirmed refund reference.');
        }if ($method->method_type === 'cash') {
            app(CashService::class)->requireCash($data['cash_session_id'], $amount);
        }
        $key = $this->insert('refunds', ['sale_return_id' => $id, 'original_sale_id' => $sale->id, 'method_id' => $method->id, 'cash_session_id' => $data['cash_session_id'], 'created_by' => $this->actor(), 'amount' => $amount, 'currency_code' => $sale->currency_code, 'method_name_snapshot' => $method->name, 'method_type_snapshot' => $method->method_type, 'reference' => $data['reference'] ?? null, 'refunded_at' => now()]);
        $this->update('sale_returns', $id, ['refunded_total' => Decimal::add((string) $r->refunded_total, $amount)]);
        $this->update('sales', $sale->id, ['total_refunded' => Decimal::add((string) $sale->total_refunded, $amount)]);
        $this->sync($sale->id);
        $this->audit('sale.refund.recorded', 'refund', $key, ['amount' => $amount]);

        return ['id' => $key];
    }

    public function exchange(int $id, array $data): array
    {
        return $this->once('sale.exchange', $data['idempotency_key'], ['return_id' => $id] + $data, function () use ($id, $data) {
            if ($this->query('exchange_links')->where('sale_return_id', $id)->exists()) {
                throw new Conflict('This return is already linked to a replacement.');
            }$this->post($id);
            $r = $this->row('sale_returns', $id);
            $sale = $this->row('sales', $r->original_sale_id);
            $quote = $this->row('sale_quotes', $data['quote_id']);
            $q = json_decode($quote->quote_json, true, 512, JSON_THROW_ON_ERROR);
            if ($q['branch_id'] != $sale->branch_id || ($q['customer_id'] ?? null) != $sale->customer_id) {
                throw new Conflict('Replacement branch and customer must match the original sale.');
            }$credit = SaleAmounts::for($sale)['refund_due'];
            $remaining = Decimal::sub((string) $r->total, (string) $r->refunded_total);
            if (Decimal::cmp($credit, $remaining) > 0) {
                $credit = $remaining;
            }if (Decimal::cmp($credit, $q['grand_total']) > 0) {
                $credit = $q['grand_total'];
            }$payments = $data['payments'];
            if (Decimal::cmp($credit, '0') > 0) {
                $method = $this->query('payment_methods')->where('code', 'EXCHANGE')->first();
                $methodId = $method?->id ?? $this->insert('payment_methods', ['code' => 'EXCHANGE', 'name' => 'Internal exchange credit', 'method_type' => 'exchange', 'active' => true]);
                if ($method && $method->method_type !== 'exchange') {
                    throw new Conflict('EXCHANGE payment code is reserved for linked returns.');
                }$exchangeRefund = ['method_id' => $methodId, 'cash_session_id' => $q['cash_session_id'], 'amount' => $credit, 'reference' => 'exchange-return:'.$id];
                $payments[] = ['method_id' => $methodId, 'amount' => $credit, 'reference' => 'exchange-return:'.$id];
            }
            $replacement = app(SaleService::class)->checkout(['idempotency_key' => 'exchange-checkout:'.$id, 'quote_id' => $quote->id, 'payments' => $payments], $id);
            if (isset($exchangeRefund)) {
                $this->refundInternal($id, $exchangeRefund, true);
            }$this->insert('exchange_links', ['sale_return_id' => $id, 'replacement_sale_id' => $replacement['id'], 'credit_amount' => $credit]);
            $this->audit('sale.exchange.posted', 'sale_return', $id, ['replacement' => $replacement['id'], 'credit' => $credit]);

            return $replacement + ['credit_applied' => $credit];
        });
    }
}

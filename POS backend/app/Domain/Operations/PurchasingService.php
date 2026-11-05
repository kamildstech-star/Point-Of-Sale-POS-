<?php

namespace App\Domain\Operations;

class PurchasingService extends Operation
{
    private function amount(string $qty, string $cost, string $tax): string
    {
        return Decimal::money(bcmul(bcmul($qty, $cost, 8), bcadd('1', bcdiv($tax, '100', 8), 8), 8), $this->scale());
    }

    public function order(array $data): array
    {
        return $this->once('purchase.order', $data['idempotency_key'], $data, function () use ($data) {
            $supplier = $this->row('suppliers', $data['supplier_id']);
            if (! $supplier->active) {
                throw new Conflict('Supplier is inactive.');
            } $this->location($data['location_id']);
            $id = $this->insert('purchase_orders', ['supplier_id' => $supplier->id, 'location_id' => $data['location_id'], 'created_by' => $this->actor(), 'po_number' => 'PO-'.strtoupper(bin2hex(random_bytes(8))), 'status' => 'draft', 'currency_code' => $this->context()->tenant()->currency_code, 'total' => '0']);
            $total = '0';
            foreach ($data['items'] as $item) {
                $qty = Decimal::value($item['ordered_qty']);
                if (Decimal::cmp($qty, '0') <= 0) {
                    Decimal::invalid('Ordered quantity must be positive.');
                }
                $this->variant($item['variant_id'], $qty, true);
                $cost = Decimal::value($item['unit_cost']);
                $tax = Decimal::value($item['tax_rate'] ?? '0');
                if (Decimal::cmp($tax, '100') > 0) {
                    Decimal::invalid('Purchase tax must be between 0 and 100 percent.');
                }
                $amount = $this->amount($qty, $cost, $tax);
                $total = Decimal::add($total, $amount);
                $this->insert('purchase_order_items', ['purchase_order_id' => $id, 'variant_id' => $item['variant_id'], 'ordered_qty' => $qty, 'received_qty' => '0', 'unit_cost' => $cost, 'tax_rate' => $tax, 'line_total' => $amount]);
            }
            $this->update('purchase_orders', $id, ['total' => Decimal::money($total, $this->scale())]);
            $this->audit('purchase.order.created', 'purchase_order', $id);

            return ['id' => $id];
        });
    }

    public function approve(int $id): array
    {
        return $this->atomic(function () use ($id) {
            $po = $this->row('purchase_orders', $id);
            if ($po->status === 'draft') {
                $this->update('purchase_orders', $id, ['status' => 'approved', 'approved_by' => $this->actor()]);
                $this->audit('purchase.order.approved', 'purchase_order', $id);
            }

            return ['id' => $id];
        });
    }

    public function receive(int $poId, array $data): array
    {
        return $this->once('purchase.receipt', $data['idempotency_key'], ['po_id' => $poId] + $data, function () use ($poId, $data) {
            $po = $this->row('purchase_orders', $poId);
            if (! in_array($po->status, ['approved', 'partial'])) {
                throw new Conflict('Approve the order before receiving; fully received orders cannot receive again.');
            }
            $this->location($po->location_id);
            $supplier = $this->row('suppliers', $po->supplier_id);
            $receipt = $this->insert('goods_receipts', ['purchase_order_id' => $poId, 'supplier_id' => $po->supplier_id, 'location_id' => $po->location_id, 'created_by' => $this->actor(), 'status' => 'posted', 'posted_at' => now(), 'total' => '0']);
            $total = '0';
            $items = $data['items'];
            usort($items, fn ($a, $b) => $a['purchase_order_item_id'] <=> $b['purchase_order_item_id']);
            foreach ($items as $item) {
                $line = $this->row('purchase_order_items', $item['purchase_order_item_id']);
                if ($line->purchase_order_id != $poId) {
                    abort(404);
                }
                $accepted = Decimal::value($item['accepted_qty']);
                $rejected = Decimal::value($item['rejected_qty'] ?? '0');
                $this->variant($line->variant_id, $accepted, true);
                $this->variant($line->variant_id, $rejected, true);
                if (Decimal::cmp(Decimal::add($accepted, $rejected), '0') <= 0) {
                    Decimal::invalid('Enter an accepted or rejected quantity.');
                }
                if (Decimal::cmp(Decimal::add($accepted, $rejected), Decimal::sub((string) $line->ordered_qty, (string) $line->received_qty)) > 0) {
                    throw new Conflict('Delivery exceeds the outstanding order quantity. Rejected units remain outstanding.');
                }
                $amount = $this->amount($accepted, (string) $line->unit_cost, (string) $line->tax_rate);
                $total = Decimal::add($total, $amount);
                $id = $this->insert('goods_receipt_items', ['goods_receipt_id' => $receipt, 'purchase_order_item_id' => $line->id, 'variant_id' => $line->variant_id, 'accepted_qty' => $accepted, 'rejected_qty' => $rejected, 'returned_qty' => '0', 'unit_cost' => $line->unit_cost, 'tax_rate' => $line->tax_rate, 'line_total' => $amount]);
                if (Decimal::cmp($accepted, '0') > 0) {
                    app(InventoryPostingService::class)->post($po->location_id, $line->variant_id, $accepted, 'goods_receipt', 'goods_receipts', $receipt, 'receipt:'.$receipt.':'.$id, (string) $line->unit_cost);
                }
                $this->update('purchase_order_items', $line->id, ['received_qty' => Decimal::add((string) $line->received_qty, $accepted)]);
            }
            $this->update('goods_receipts', $receipt, ['total' => $total]);
            $this->insert('supplier_payables', ['goods_receipt_id' => $receipt, 'supplier_id' => $po->supplier_id, 'original_amount' => $total, 'outstanding_amount' => $total, 'due_date' => now()->addDays($supplier->payment_terms_days)->toDateString(), 'status' => Decimal::cmp($total, '0') === 0 ? 'settled' : 'open']);
            $remaining = $this->query('purchase_order_items')->where('purchase_order_id', $poId)->whereColumn('received_qty', '<', 'ordered_qty')->exists();
            $this->update('purchase_orders', $poId, ['status' => $remaining ? 'partial' : 'received']);
            $this->audit('purchase.receipt.posted', 'goods_receipt', $receipt);

            return ['id' => $receipt, 'total' => $total];
        });
    }

    public function returnGoods(int $receiptId, array $data): array
    {
        return $this->once('purchase.return', $data['idempotency_key'], ['receipt_id' => $receiptId] + $data, function () use ($receiptId, $data) {
            $receipt = $this->row('goods_receipts', $receiptId);
            $this->location($receipt->location_id);
            $id = $this->insert('purchase_returns', ['goods_receipt_id' => $receiptId, 'location_id' => $receipt->location_id, 'created_by' => $this->actor(), 'reason' => $data['reason'], 'posted_at' => now(), 'total' => '0']);
            $total = '0';
            $items = $data['items'];
            usort($items, fn ($a, $b) => $a['goods_receipt_item_id'] <=> $b['goods_receipt_item_id']);
            foreach ($items as $item) {
                $line = $this->row('goods_receipt_items', $item['goods_receipt_item_id']);
                if ($line->goods_receipt_id != $receiptId) {
                    abort(404);
                }
                $qty = Decimal::value($item['quantity']);
                if (Decimal::cmp($qty, '0') <= 0) {
                    Decimal::invalid('Return quantity must be positive.');
                } $this->variant($line->variant_id, $qty, true);
                $newReturned = Decimal::add((string) $line->returned_qty, $qty);
                if (Decimal::cmp($newReturned, (string) $line->accepted_qty) > 0) {
                    throw new Conflict('Return exceeds the accepted quantity still returnable.');
                }
                // Cumulative allocation ensures splitting a return cannot create extra cents.
                $allocation = fn (string $q) => Decimal::round(bcmul(bcdiv($q, (string) $line->accepted_qty, 24), (string) $line->line_total, 8), $this->scale());
                $amount = Decimal::sub($allocation($newReturned), $allocation((string) $line->returned_qty));
                $total = Decimal::add($total, $amount);
                $returnLine = $this->insert('purchase_return_items', ['purchase_return_id' => $id, 'goods_receipt_item_id' => $line->id, 'variant_id' => $line->variant_id, 'quantity' => $qty, 'unit_cost' => $line->unit_cost, 'line_total' => $amount]);
                app(InventoryPostingService::class)->post($receipt->location_id, $line->variant_id, '-'.$qty, 'purchase_return', 'purchase_returns', $id, 'purchase-return:'.$id.':'.$returnLine, (string) $line->unit_cost);
                $this->update('goods_receipt_items', $line->id, ['returned_qty' => $newReturned]);
            }
            $payable = $this->query('supplier_payables')->where('goods_receipt_id', $receiptId)->first();
            $balance = Decimal::sub((string) $payable->outstanding_amount, $total);
            $this->update('supplier_payables', $payable->id, ['outstanding_amount' => $balance, 'status' => $this->payableStatus($balance)]);
            $this->update('purchase_returns', $id, ['total' => $total]);
            $this->audit('purchase.return.posted', 'purchase_return', $id);

            return ['id' => $id, 'total' => $total];
        });
    }

    public function settle(int $id, array $data): array
    {
        return $this->once('purchase.settlement', $data['idempotency_key'], ['payable_id' => $id] + $data, function () use ($id, $data) {
            $payable = $this->row('supplier_payables', $id);
            $amount = Decimal::value($data['amount']);
            if (Decimal::cmp($amount, '0') <= 0 || Decimal::cmp($amount, (string) $payable->outstanding_amount) > 0 || Decimal::cmp($amount, Decimal::round($amount, $this->scale())) !== 0) {
                Decimal::invalid('Settlement must be positive, match currency precision and not exceed the payable.');
            }
            $settlement = $this->insert('supplier_settlements', ['payable_id' => $id, 'created_by' => $this->actor(), 'amount' => $amount, 'reference' => $data['reference'], 'posted_at' => now()]);
            $balance = Decimal::sub((string) $payable->outstanding_amount, $amount);
            $this->update('supplier_payables', $id, ['outstanding_amount' => $balance, 'status' => $this->payableStatus($balance)]);
            $this->audit('purchase.settlement.recorded', 'supplier_settlement', $settlement);

            return ['id' => $settlement, 'outstanding' => $balance];
        });
    }

    private function payableStatus(string $balance): string
    {
        return Decimal::cmp($balance, '0') === 0 ? 'settled' : (Decimal::cmp($balance, '0') < 0 ? 'supplier_credit' : 'open');
    }

    public function reconcile(): array
    {
        return $this->atomic(function () {
            $issues = [];
            foreach ($this->query('supplier_payables')->orderBy('id')->get() as $p) {
                $receipt = $this->row('goods_receipts', $p->goods_receipt_id);
                $lines = '0';
                $returns = '0';
                $settled = '0';
                foreach ($this->query('goods_receipt_items')->where('goods_receipt_id', $receipt->id)->pluck('line_total') as $value) {
                    $lines = Decimal::add($lines, (string) $value);
                }
                foreach ($this->query('purchase_returns')->where('goods_receipt_id', $receipt->id)->pluck('total') as $value) {
                    $returns = Decimal::add($returns, (string) $value);
                }
                foreach ($this->query('supplier_settlements')->where('payable_id', $p->id)->pluck('amount') as $value) {
                    $settled = Decimal::add($settled, (string) $value);
                }
                $expected = Decimal::sub(Decimal::sub($lines, $returns), $settled);
                if (Decimal::cmp($lines, (string) $receipt->total) !== 0 || Decimal::cmp($lines, (string) $p->original_amount) !== 0 || Decimal::cmp($expected, (string) $p->outstanding_amount) !== 0) {
                    $issues[] = ['payable_id' => $p->id, 'expected' => $expected, 'outstanding' => $p->outstanding_amount];
                }
            }

            return ['reconciled' => ! $issues, 'issues' => $issues, 'payables' => $this->query('supplier_payables')->count()];
        });
    }
}

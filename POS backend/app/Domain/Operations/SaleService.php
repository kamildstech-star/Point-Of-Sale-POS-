<?php

namespace App\Domain\Operations;

class SaleService extends Operation
{
    public function setup(): void
    {
        foreach (['CASH' => ['Cash', 'cash'], 'CARD' => ['Card (external terminal)', 'card'], 'BANK' => ['Bank transfer (confirmed)', 'bank']] as $code => [$name, $type]) {
            $this->query('payment_methods')->insertOrIgnore(['tenant_id' => $this->context()->id(), 'code' => $code, 'name' => $name, 'method_type' => $type, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function session(int $id): array
    {
        $session = $this->row('cash_sessions', $id);
        $register = $this->row('cash_registers', $session->register_id);
        $branch = $this->row('branches', $register->branch_id);
        $location = $this->location($register->location_id);
        if ($session->status !== 'open' || ! $register->active || $branch->status !== 'active' || $branch->pos_engine !== 'react' || ! $branch->pricing_approved_at) {
            throw new Conflict('Open an active register in a branch enabled for the new POS.');
        }
        if ($location->branch_id != $branch->id || $location->type !== 'store') {
            throw new Conflict('Register location must be a store in this branch.');
        }
        if ($session->opened_by !== $this->actor() && ! $this->context()->allows('cash.manage')) {
            abort(403);
        }

        return [$session, $register, $branch];
    }

    public function open(array $data): array
    {
        return $this->once('cash.open', $data['idempotency_key'], $data, function () use ($data) {
            $register = $this->row('cash_registers', $data['register_id']);
            $branch = $this->row('branches', $register->branch_id);
            $location = $this->location($register->location_id);
            if (! $register->active || $branch->status !== 'active' || $branch->pos_engine !== 'react' || $location->branch_id != $branch->id || $location->type !== 'store') {
                throw new Conflict('Enable this branch and select an active store register.');
            }
            if ($this->query('cash_sessions')->where('register_id', $register->id)->where('status', 'open')->exists()) {
                throw new Conflict('This register already has an open shift.');
            }
            $amount = Decimal::value($data['opening_cash']);
            if (Decimal::cmp($amount, Decimal::round($amount, $this->scale())) !== 0) {
                Decimal::invalid('Opening cash must match currency precision.');
            }
            $id = $this->insert('cash_sessions', ['register_id' => $register->id, 'open_register_id' => $register->id, 'opened_by' => $this->actor(), 'opened_at' => now(), 'opening_cash' => $amount, 'status' => 'open']);
            $this->audit('cash.session.opened', 'cash_session', $id);

            return ['id' => $id];
        });
    }

    public function quote(array $input): array
    {
        return $this->atomic(function () use ($input) {
            app(InventoryPostingService::class)->expire();
            $quote = $this->calculate($input);
            $id = $this->insert('sale_quotes', ['created_by' => $this->actor(), 'input_json' => json_encode($input, JSON_THROW_ON_ERROR), 'quote_json' => json_encode($quote, JSON_THROW_ON_ERROR), 'expires_at' => now()->addMinutes(10)]);

            return ['id' => $id, 'expires_at' => now()->addMinutes(10)->toIso8601String(), 'quote' => $quote];
        });
    }

    private function calculate(array $input): array
    {
        [$session, $register, $branch] = $this->session($input['cash_session_id']);
        if (! empty($input['held_cart_id'])) {
            app(HeldCartService::class)->assertHeld($input, $branch->id);
        }
        $scale = $this->scale();
        if (! empty($input['customer_id']) && ! $this->row('customers', $input['customer_id'])->active) {
            throw new Conflict('Customer is inactive.');
        }
        if (! empty($input['price_list_id'])) {
            $list = $this->row('catalog_price_lists', $input['price_list_id']);
            if (! $list->active || $list->currency_code !== $this->context()->tenant()->currency_code) {
                throw new Conflict('Price list is inactive or has a different currency.');
            }
        }
        $items = $input['items'];
        usort($items, fn ($a, $b) => $a['variant_id'] <=> $b['variant_id']);
        $lines = [];
        $subtotal = '0';
        foreach ($items as $item) {
            $qty = Decimal::value($item['qty']);
            if (Decimal::cmp($qty, '0') <= 0) {
                Decimal::invalid('Sale quantity must be positive.');
            } $v = $this->variant($item['variant_id'], $qty);
            $price = bcadd((string) $v->default_price, '0', 4);
            if (! empty($input['price_list_id'])) {
                $tier = $this->query('catalog_price_items')->where('price_list_id', $input['price_list_id'])->where('variant_id', $v->id)->where('min_qty', '<=', $qty)->orderByDesc('min_qty')->first();
                if ($tier) {
                    $price = bcadd((string) $tier->unit_price, '0', 4);
                }
            }
            $rate = '0.0000';
            if ($v->product->tax_class_id) {
                if (! $branch->tax_jurisdiction || ! $this->row('catalog_tax_classes', $v->product->tax_class_id)->active) {
                    throw new Conflict('Configure the branch tax jurisdiction and active product tax class before selling this item.');
                }
                $today = now($this->context()->tenant()->timezone)->toDateString();
                $rates = $this->query('catalog_tax_rates')->where('tax_class_id', $v->product->tax_class_id)->where('jurisdiction_code', $branch->tax_jurisdiction)->where('valid_from', '<=', $today)->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>=', $today))->get();
                if ($rates->count() !== 1) {
                    throw new Conflict('Exactly one effective tax rate is required for this item and branch.');
                } $rate = bcadd((string) $rates->first()->rate_percent, '0', 4);
            }
            $gross = Decimal::money(Decimal::mul($qty, $price), $scale);
            $subtotal = Decimal::add($subtotal, $gross);
            $lines[] = ['variant_id' => $v->id, 'sku_snapshot' => $v->sku, 'name_snapshot' => $v->product->name.($v->name ? ' / '.$v->name : ''), 'unit_snapshot' => $v->unit->code, 'qty' => $qty, 'unit_price' => $price, 'unit_cost_snapshot' => bcadd((string) $v->default_cost, '0', 4), 'track_inventory' => (bool) $v->product->track_inventory, 'tax_rate' => $rate, 'tax_jurisdiction' => $branch->tax_jurisdiction, 'gross' => $gross];
        }
        $discount = Decimal::value($input['discount_amount'] ?? '0');
        if (Decimal::cmp($discount, $subtotal) > 0 || Decimal::cmp($discount, Decimal::round($discount, $scale)) !== 0) {
            Decimal::invalid('Discount must match currency precision and cannot exceed the subtotal.');
        }
        if (Decimal::cmp($discount, '0') > 0 && ! $this->context()->allows('sales.discount')) {
            abort(403);
        }
        // Allocate the fixed order discount by the remaining base. The final
        // nonzero line receives the residual, so cents always sum exactly.
        $remainingDiscount = $discount;
        $remainingGross = $subtotal;
        $taxTotal = '0';
        $grand = '0';
        foreach ($lines as &$line) {
            $allocated = Decimal::cmp($remainingGross, '0') === 0 ? '0' : Decimal::round(bcmul(bcdiv($line['gross'], $remainingGross, 24), $remainingDiscount, 8), $scale);
            if (Decimal::cmp($allocated, $line['gross']) > 0) {
                $allocated = $line['gross'];
            }
            $remainingDiscount = Decimal::sub($remainingDiscount, $allocated);
            $remainingGross = Decimal::sub($remainingGross, $line['gross']);
            $net = Decimal::sub($line['gross'], $allocated);
            $tax = Decimal::money(bcdiv(Decimal::mul($net, $line['tax_rate']), '100', 8), $scale);
            $line['discount_total'] = Decimal::money($allocated, $scale);
            $line['tax_total'] = $tax;
            $line['line_total'] = Decimal::money(Decimal::add($net, $tax), $scale);
            unset($line['gross']);
            $taxTotal = Decimal::add($taxTotal, $tax);
            $grand = Decimal::add($grand, $line['line_total']);
        }
        unset($line);

        $result = ['cash_session_id' => $session->id, 'register_id' => $register->id, 'branch_id' => $branch->id, 'location_id' => $register->location_id, 'customer_id' => $input['customer_id'] ?? null, 'allow_credit' => (bool) $branch->allow_credit, 'currency_code' => $this->context()->tenant()->currency_code, 'currency_scale' => $scale, 'subtotal' => Decimal::money($subtotal, $scale), 'discount_total' => Decimal::money($discount, $scale), 'tax_total' => Decimal::money($taxTotal, $scale), 'grand_total' => Decimal::money($grand, $scale), 'items' => $lines];
        if (! empty($input['exchange_return_id'])) {
            $r = $this->row('sale_returns', $input['exchange_return_id']);
            $original = $this->row('sales', $r->original_sale_id);
            if ($r->status !== 'posted' || $original->branch_id != $branch->id || $original->customer_id != ($input['customer_id'] ?? null)) {
                throw new Conflict('Post the return and use its original customer and branch before quoting an exchange.');
            }$credit = SaleAmounts::for($original)['refund_due'];
            $remaining = Decimal::sub((string) $r->total, (string) $r->refunded_total);
            if (Decimal::cmp($credit, $remaining) > 0) {
                $credit = $remaining;
            }if (Decimal::cmp($credit, $grand) > 0) {
                $credit = $grand;
            }$result['exchange_credit_amount'] = $credit;
            $result['amount_to_collect'] = Decimal::sub($grand, $credit);
        }

        return $result;
    }

    public function checkout(array $data, ?int $exchangeReturn = null): array
    {
        return $this->once('sale.checkout', $data['idempotency_key'], $data, function () use ($data, $exchangeReturn) {
            $saved = $this->row('sale_quotes', $data['quote_id']);
            if ($saved->created_by !== $this->actor()) {
                abort(403);
            }
            if ($saved->consumed_sale_id) {
                throw new Conflict('This quote was already sold. Open the receipt instead of submitting a new sale.');
            }
            if (now()->gte($saved->expires_at)) {
                throw new Conflict('Quote expired. Request a fresh quote.');
            }
            $input = json_decode($saved->input_json, true, 512, JSON_THROW_ON_ERROR);
            if (! empty($input['exchange_return_id']) && $input['exchange_return_id'] != $exchangeReturn) {
                throw new Conflict('This quote must be completed through its linked exchange.');
            }
            $quote = $this->calculate($input);
            if (json_encode($quote, JSON_THROW_ON_ERROR) !== $saved->quote_json) {
                throw new Conflict('Prices, tax or product details changed. Request a fresh quote.');
            }
            [$session, $register, $branch] = $this->session($quote['cash_session_id']);
            $paid = $this->validatePayments($data['payments'] ?? [], $quote['grand_total'], $quote['currency_scale'], $exchangeReturn);
            if (Decimal::cmp($paid, $quote['grand_total']) < 0 && (! $quote['customer_id'] || ! $branch->allow_credit)) {
                throw new Conflict('Full payment is required for walk-ins and branches without customer credit.');
            }
            $sequence = $this->query('invoice_sequences')->where('branch_id', $branch->id)->where('year', now($this->context()->tenant()->timezone)->year)->lockForUpdate()->first();
            $year = now($this->context()->tenant()->timezone)->year;
            if (! $sequence) {
                $seqId = $this->insert('invoice_sequences', ['branch_id' => $branch->id, 'year' => $year, 'next_number' => 1]);
                $sequence = $this->row('invoice_sequences', $seqId);
            }
            $invoice = 'INV-B'.$branch->id.'-'.$year.'-'.str_pad((string) $sequence->next_number, 7, '0', STR_PAD_LEFT);
            $this->update('invoice_sequences', $sequence->id, ['next_number' => $sequence->next_number + 1]);
            $customer = $quote['customer_id'] ? $this->row('customers', $quote['customer_id']) : null;
            $id = $this->insert('sales', ['branch_id' => $branch->id, 'location_id' => $register->location_id, 'register_id' => $register->id, 'cash_session_id' => $session->id, 'customer_id' => $quote['customer_id'], 'cashier_membership_id' => $this->actor(), 'invoice_no' => $invoice, 'sale_status' => 'completed', 'payment_status' => $this->paymentStatus($paid, $quote['grand_total']), 'currency_code' => $quote['currency_code'], 'currency_scale' => $quote['currency_scale'], 'subtotal' => $quote['subtotal'], 'discount_total' => $quote['discount_total'], 'tax_total' => $quote['tax_total'], 'grand_total' => $quote['grand_total'], 'paid_total' => $paid, 'customer_snapshot_json' => $customer ? json_encode(['name' => $customer->name, 'phone' => $customer->phone, 'email' => $customer->email]) : null, 'branch_snapshot_json' => json_encode(['business' => $this->context()->tenant()->name, 'name' => $branch->name, 'address' => $branch->address, 'phone' => $branch->phone, 'timezone' => $this->context()->tenant()->timezone, 'receipt' => app(WorkspaceService::class)->settings()]), 'sold_at' => now()]);
            foreach ($quote['items'] as $line) {
                $lineId = $this->insert('sale_items', ['sale_id' => $id] + $line);
                if ($line['track_inventory']) {
                    app(InventoryPostingService::class)->post($register->location_id, $line['variant_id'], '-'.$line['qty'], 'sale', 'sales', $id, 'sale:'.$id.':'.$lineId, $line['unit_cost_snapshot']);
                }
            }
            foreach ($data['payments'] ?? [] as $payment) {
                $this->recordPayment($id, $session->id, $payment, $quote['currency_code']);
            }
            $this->update('sale_quotes', $saved->id, ['consumed_sale_id' => $id]);
            app(HeldCartService::class)->consume($input, $id, $branch->id);
            $this->audit('sale.completed', 'sale', $id, ['invoice' => $invoice, 'total' => $quote['grand_total'], 'paid' => $paid]);

            return ['id' => $id, 'invoice_no' => $invoice];
        });
    }

    private function validatePayments(array $payments, string $due, int $scale, ?int $exchangeReturn = null): string
    {
        $total = '0';
        foreach ($payments as $p) {
            $method = $this->row('payment_methods', $p['method_id']);
            if (! $method->active) {
                throw new Conflict('Payment method is inactive.');
            }
            if ($method->method_type === 'exchange' && (! $exchangeReturn || ($p['reference'] ?? '') !== 'exchange-return:'.$exchangeReturn)) {
                throw new Conflict('Exchange credit requires an atomic linked exchange.');
            }
            $amount = Decimal::value($p['amount']);
            if (Decimal::cmp($amount, '0') <= 0 || Decimal::cmp($amount, Decimal::round($amount, $scale)) !== 0) {
                Decimal::invalid('Each payment must be positive and match currency precision.');
            }
            if ($method->method_type !== 'cash' && empty(trim($p['reference'] ?? ''))) {
                Decimal::invalid('Record the confirmed external payment reference for card and bank payments.');
            } $total = Decimal::add($total, $amount);
        }
        if (Decimal::cmp($total, $due) > 0) {
            Decimal::invalid('Applied payments cannot exceed the amount due. Enter cash applied after giving change.');
        }

        return $total;
    }

    private function recordPayment(int $sale, int $session, array $p, string $currency): void
    {
        $method = $this->row('payment_methods', $p['method_id']);
        $this->insert('sale_payments', ['sale_id' => $sale, 'method_id' => $method->id, 'cash_session_id' => $session, 'created_by' => $this->actor(), 'amount' => Decimal::value($p['amount']), 'currency_code' => $currency, 'method_name_snapshot' => $method->name, 'method_type_snapshot' => $method->method_type, 'status' => 'recorded', 'reference' => $p['reference'] ?? null, 'recorded_at' => now()]);
    }

    private function paymentStatus(string $paid, string $total): string
    {
        return Decimal::cmp($paid, $total) === 0 ? 'paid' : (Decimal::cmp($paid, '0') === 0 ? 'unpaid' : 'partial');
    }

    public function pay(int $id, array $data): array
    {
        return $this->once('sale.payment', $data['idempotency_key'], ['sale_id' => $id] + $data, function () use ($id, $data) {
            $sale = $this->row('sales', $id);
            [$session, $register] = $this->session($data['cash_session_id']);
            if ($register->branch_id !== $sale->branch_id || $sale->sale_status !== 'completed') {
                throw new Conflict('Use an open register in the original sale branch.');
            }
            $paid = $this->validatePayments($data['payments'], SaleAmounts::for($sale)['balance_due'], $sale->currency_scale);
            foreach ($data['payments'] as $p) {
                $this->recordPayment($id, $session->id, $p, $sale->currency_code);
            }
            $newPaid = Decimal::add((string) $sale->paid_total, $paid);
            $this->update('sales', $id, ['paid_total' => $newPaid, 'payment_status' => SaleAmounts::for((object) array_merge((array) $sale, ['paid_total' => $newPaid]))['payment_status']]);
            $this->audit('sale.payment.recorded', 'sale', $id, ['amount' => $paid]);

            return ['id' => $id, 'paid_total' => $newPaid];
        });
    }

    public function reconciliation(int $id): array
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('refunds')) {
            return app(CashService::class)->summary($id);
        }
        $session = $this->row('cash_sessions', $id);
        if ($session->opened_by !== $this->actor() && ! $this->context()->allows('cash.manage')) {
            abort(403);
        }
        $totals = [];
        $cash = '0';
        foreach ($this->query('sale_payments')->where('cash_session_id', $id)->where('status', 'recorded')->orderBy('id')->get() as $p) {
            $key = $p->method_id;
            $totals[$key] ??= ['method_id' => $key, 'name' => $p->method_name_snapshot, 'type' => $p->method_type_snapshot, 'amount' => '0.0000', 'count' => 0];
            $totals[$key]['amount'] = Decimal::add($totals[$key]['amount'], (string) $p->amount);
            $totals[$key]['count']++;
            if ($p->method_type_snapshot === 'cash') {
                $cash = Decimal::add($cash, (string) $p->amount);
            }
        }

        return ['session' => $session, 'methods' => array_values($totals), 'expected_cash' => Decimal::add((string) $session->opening_cash, $cash), 'basis' => 'Opening cash plus recorded cash payments. Cash movements, refunds and shift close follow in phase 6.'];
    }
}

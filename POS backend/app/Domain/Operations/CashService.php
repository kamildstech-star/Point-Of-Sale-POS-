<?php

namespace App\Domain\Operations;

class CashService extends Operation
{
    public function summary(int $id): array
    {
        $session = $this->row('cash_sessions', $id);
        if ($session->opened_by != $this->actor() && ! $this->context()->allows('cash.manage')) {
            abort(403);
        }
        $methods = [];
        $cash = '0';
        $refundCash = '0';
        $expenseCash = '0';
        $movement = '0';
        foreach ($this->query('sale_payments')->where('cash_session_id', $id)->get() as $p) {
            $k = $p->method_id;
            $methods[$k] ??= ['method_id' => $k, 'name' => $p->method_name_snapshot, 'type' => $p->method_type_snapshot, 'collections' => '0', 'refunds' => '0', 'count' => 0];
            $methods[$k]['collections'] = Decimal::add($methods[$k]['collections'], (string) $p->amount);
            $methods[$k]['count']++;
            if ($p->method_type_snapshot === 'cash') {
                $cash = Decimal::add($cash, (string) $p->amount);
            }
        }
        foreach ($this->query('refunds')->where('cash_session_id', $id)->get() as $r) {
            $k = $r->method_id;
            $methods[$k] ??= ['method_id' => $k, 'name' => $r->method_name_snapshot, 'type' => $r->method_type_snapshot, 'collections' => '0', 'refunds' => '0', 'count' => 0];
            $methods[$k]['refunds'] = Decimal::add($methods[$k]['refunds'], (string) $r->amount);
            if ($r->method_type_snapshot === 'cash') {
                $refundCash = Decimal::add($refundCash, (string) $r->amount);
            }
        }
        foreach ($this->query('retail_expenses')->where('cash_session_id', $id)->where('status', 'posted')->where('method_type_snapshot', 'cash')->pluck('amount') as $v) {
            $expenseCash = Decimal::add($expenseCash, (string) $v);
        }
        foreach ($this->query('cash_movements')->where('cash_session_id', $id)->pluck('amount') as $v) {
            $movement = Decimal::add($movement, (string) $v);
        }
        foreach ($methods as &$m) {
            $m['amount'] = Decimal::sub($m['collections'], $m['refunds']);
        }unset($m);

        return ['session' => $session, 'methods' => array_values($methods), 'cash_collections' => $cash, 'cash_refunds' => $refundCash, 'cash_expenses' => $expenseCash, 'cash_movements' => $movement, 'expected_cash' => Decimal::sub(Decimal::sub(Decimal::add(Decimal::add((string) $session->opening_cash, $cash), $movement), $refundCash), $expenseCash), 'basis' => 'Opening cash + cash collections + cash in/out − recorded cash refunds − posted cash expenses. Internal exchange credit is excluded from cash.'];
    }

    public function requireCash(int $session, string $amount): void
    {
        if (Decimal::cmp($this->summary($session)['expected_cash'], $amount) < 0) {
            throw new Conflict('Insufficient expected drawer cash. Record a verified cash-in or use the correct confirmed refund method.');
        }
    }

    public function movement(int $id, array $data): array
    {
        return $this->once('cash.movement', $data['idempotency_key'], ['session_id' => $id] + $data, function () use ($id, $data) {
            app(SaleService::class)->session($id);
            $amount = Decimal::value($data['amount']);
            if (Decimal::cmp($amount, '0') <= 0 || Decimal::cmp($amount, Decimal::round($amount, $this->scale())) !== 0) {
                Decimal::invalid('Use a positive amount at currency precision.');
            }if ($data['movement_type'] === 'out') {
                $this->requireCash($id, $amount);
            }$key = $this->insert('cash_movements', ['cash_session_id' => $id, 'actor_id' => $this->actor(), 'movement_type' => $data['movement_type'], 'amount' => $data['movement_type'] === 'out' ? '-'.$amount : $amount, 'reason' => $data['reason'], 'occurred_at' => now()]);
            $this->audit('cash.movement.posted', 'cash_movement', $key);

            return ['id' => $key];
        });
    }

    public function close(int $id, array $data): array
    {
        return $this->once('cash.close', $data['idempotency_key'], ['session_id' => $id] + $data, function () use ($id, $data) {
            app(SaleService::class)->session($id);
            $count = Decimal::value($data['counted_cash']);
            if (Decimal::cmp($count, Decimal::round($count, $this->scale())) !== 0) {
                Decimal::invalid('Counted cash must match currency precision.');
            }$summary = $this->summary($id);
            $variance = Decimal::sub($count, $summary['expected_cash']);
            $this->update('cash_sessions', $id, ['status' => 'closed', 'open_register_id' => null, 'closed_by' => $this->actor(), 'closed_at' => now(), 'counted_cash' => $count, 'expected_cash' => $summary['expected_cash'], 'variance' => $variance, 'closing_methods_json' => json_encode($summary['methods'], JSON_THROW_ON_ERROR)]);
            $this->audit('cash.session.closed', 'cash_session', $id, ['expected' => $summary['expected_cash'], 'counted' => $count, 'variance' => $variance]);

            return ['id' => $id, 'expected_cash' => $summary['expected_cash'], 'variance' => $variance];
        });
    }

    public function expense(array $data): array
    {
        return $this->once('expense.create', $data['idempotency_key'], $data, function () use ($data) {
            $category = $this->row('retail_expense_categories', $data['category_id']);
            if (! $category->active) {
                throw new Conflict('Expense category is inactive.');
            }$this->row('branches', $data['branch_id']);
            $method = $this->row('payment_methods', $data['method_id']);
            if (! $method->active || ! in_array($method->method_type, ['cash', 'card', 'bank'])) {
                Decimal::invalid('Select an active external payment method.');
            }$amount = Decimal::value($data['amount']);
            if (Decimal::cmp($amount, '0') <= 0 || Decimal::cmp($amount, Decimal::round($amount, $this->scale())) !== 0) {
                Decimal::invalid('Expense amount must be positive at currency precision.');
            }if ($method->method_type === 'cash' && empty($data['cash_session_id'])) {
                Decimal::invalid('Cash expenses require an open register shift.');
            }if ($method->method_type !== 'cash' && empty(trim($data['reference'] ?? ''))) {
                Decimal::invalid('Record the confirmed external expense payment reference.');
            }$id = $this->insert('retail_expenses', ['category_id' => $data['category_id'], 'branch_id' => $data['branch_id'], 'cash_session_id' => $data['cash_session_id'] ?? null, 'method_id' => $method->id, 'method_type_snapshot' => $method->method_type, 'created_by' => $this->actor(), 'amount' => $amount, 'currency_code' => $this->context()->tenant()->currency_code, 'description' => $data['description'], 'expense_date' => $data['expense_date'], 'reference' => $data['reference'] ?? null, 'status' => 'draft']);
            $this->audit('expense.created', 'retail_expense', $id);

            return ['id' => $id];
        });
    }

    public function approveExpense(int $id): array
    {
        return $this->atomic(function () use ($id) {
            $e = $this->row('retail_expenses', $id);
            if ($e->status === 'posted') {
                return ['id' => $id];
            }if ($e->cash_session_id) {
                [, $register] = app(SaleService::class)->session($e->cash_session_id);
                if ($register->branch_id != $e->branch_id) {
                    throw new Conflict('Expense branch and register branch differ.');
                }
            }if ($e->method_type_snapshot === 'cash') {
                $this->requireCash($e->cash_session_id, (string) $e->amount);
            }$this->update('retail_expenses', $id, ['status' => 'posted', 'approved_by' => $this->actor(), 'posted_at' => now()]);
            $this->audit('expense.posted', 'retail_expense', $id);

            return ['id' => $id];
        });
    }
}

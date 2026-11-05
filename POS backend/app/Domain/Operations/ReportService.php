<?php

namespace App\Domain\Operations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService extends Operation
{
    public function report(array $filters): array
    {
        $zone = $this->context()->tenant()->timezone;
        $from = Carbon::parse($filters['from'], $zone)->startOfDay()->setTimezone(config('app.timezone'));
        $until = Carbon::parse($filters['to'], $zone)->addDay()->startOfDay()->setTimezone(config('app.timezone'));

        return DB::transaction(function () use ($filters, $from, $until, $zone) {
            DB::table('tenants')->where('id', $this->context()->id())->lockForUpdate()->first();
            $scope = function ($q, string $date) use ($filters, $from, $until) {
                $q->where($date, '>=', $from)->where($date, '<', $until);
                if (! empty($filters['branch_id'])) {
                    $q->where('branch_id', $filters['branch_id']);
                }

return $q;
            };
            $sales = $scope($this->query('sales'), 'sold_at')->get();
            $saleIds = $this->query('sales')->when(! empty($filters['branch_id']), fn ($q) => $q->where('branch_id', $filters['branch_id']))->pluck('id');
            $returns = $this->query('sale_returns')->where('status', 'posted')->whereIn('original_sale_id', $saleIds)->where('posted_at', '>=', $from)->where('posted_at', '<', $until)->get();
            $r = ['gross_sales' => '0', 'discounts' => '0', 'tax_charged' => '0', 'invoiced_sales' => '0', 'return_credits' => '0', 'returned_discount' => '0', 'returned_tax' => '0', 'external_collections' => '0', 'external_refunds' => '0', 'exchange_credit_in' => '0', 'exchange_credit_out' => '0', 'expenses' => '0', 'estimated_cost' => '0', 'estimated_returned_cost' => '0'];
            foreach ($sales as $s) {
                foreach (['subtotal' => 'gross_sales', 'discount_total' => 'discounts', 'tax_total' => 'tax_charged', 'grand_total' => 'invoiced_sales'] as $f => $key) {
                    $r[$key] = Decimal::add($r[$key], (string) $s->$f);
                }foreach ($this->query('sale_items')->where('sale_id', $s->id)->get() as $l) {
                    $r['estimated_cost'] = Decimal::add($r['estimated_cost'], Decimal::mul((string) $l->qty, (string) $l->unit_cost_snapshot));
                }
            }
            foreach ($returns as $ret) {
                $r['return_credits'] = Decimal::add($r['return_credits'], (string) $ret->total);
                foreach ($this->query('sale_return_items')->where('sale_return_id', $ret->id)->get() as $l) {
                    $r['returned_discount'] = Decimal::add($r['returned_discount'], (string) $l->discount_amount);
                    $r['returned_tax'] = Decimal::add($r['returned_tax'], (string) $l->tax_amount);
                    $orig = $this->row('sale_items', $l->sale_item_id);
                    if ($l->disposition === 'resellable') {
                        $r['estimated_returned_cost'] = Decimal::add($r['estimated_returned_cost'], Decimal::mul((string) $l->qty, (string) $orig->unit_cost_snapshot));
                    }
                }
            }
            $methods = [];
            foreach ($this->query('sale_payments')->whereIn('sale_id', $saleIds)->where('recorded_at', '>=', $from)->where('recorded_at', '<', $until)->get() as $p) {
                $key = $p->method_type_snapshot === 'exchange' ? 'exchange_credit_in' : 'external_collections';
                $r[$key] = Decimal::add($r[$key], (string) $p->amount);
                $methods[$p->method_name_snapshot] ??= ['collections' => '0', 'refunds' => '0'];
                $methods[$p->method_name_snapshot]['collections'] = Decimal::add($methods[$p->method_name_snapshot]['collections'], (string) $p->amount);
            }
            foreach ($this->query('refunds')->whereIn('original_sale_id', $saleIds)->where('refunded_at', '>=', $from)->where('refunded_at', '<', $until)->get() as $p) {
                $key = $p->method_type_snapshot === 'exchange' ? 'exchange_credit_out' : 'external_refunds';
                $r[$key] = Decimal::add($r[$key], (string) $p->amount);
                $methods[$p->method_name_snapshot] ??= ['collections' => '0', 'refunds' => '0'];
                $methods[$p->method_name_snapshot]['refunds'] = Decimal::add($methods[$p->method_name_snapshot]['refunds'], (string) $p->amount);
            }
            foreach ($scope($this->query('retail_expenses')->where('status', 'posted'), 'posted_at')->pluck('amount') as $v) {
                $r['expenses'] = Decimal::add($r['expenses'], (string) $v);
            }
            $r['net_sales_including_tax'] = Decimal::sub($r['invoiced_sales'], $r['return_credits']);
            $r['net_tax'] = Decimal::sub($r['tax_charged'], $r['returned_tax']);
            $r['net_sales_excluding_tax'] = Decimal::sub($r['net_sales_including_tax'], $r['net_tax']);
            $r['net_external_collections'] = Decimal::sub($r['external_collections'], $r['external_refunds']);
            $r['estimated_gross_margin'] = Decimal::sub($r['net_sales_excluding_tax'], Decimal::sub($r['estimated_cost'], $r['estimated_returned_cost']));
            $issues = [];
            $due = '0';
            $refundDue = '0';
            foreach ($this->query('sales')->whereIn('id', $saleIds)->get() as $s) {
                $a = SaleAmounts::for($s);
                $due = Decimal::add($due, $a['balance_due']);
                $refundDue = Decimal::add($refundDue, $a['refund_due']);
                $paid = '0';
                $refunded = '0';
                $credit = '0';
                foreach ($this->query('sale_payments')->where('sale_id', $s->id)->pluck('amount') as $v) {
                    $paid = Decimal::add($paid, (string) $v);
                }foreach ($this->query('refunds')->where('original_sale_id', $s->id)->pluck('amount') as $v) {
                    $refunded = Decimal::add($refunded, (string) $v);
                }foreach ($this->query('sale_returns')->where('original_sale_id', $s->id)->where('status', 'posted')->pluck('total') as $v) {
                    $credit = Decimal::add($credit, (string) $v);
                }if (Decimal::cmp($paid, (string) $s->paid_total) || Decimal::cmp($refunded, (string) $s->total_refunded) || Decimal::cmp($credit, (string) $s->returned_total)) {
                    $issues[] = ['sale_id' => $s->id, 'issue' => 'posted_totals_differ'];
                }
            }

            return ['period' => ['from' => $filters['from'], 'to' => $filters['to'], 'timezone' => $zone, 'end_exclusive' => $until->toIso8601String()], 'totals' => $r, 'methods' => $methods, 'sale_count' => $sales->count(), 'return_count' => $returns->count(), 'current_balances' => ['due' => $due, 'refund_due' => $refundDue], 'reconciled' => ! $issues, 'issues' => $issues, 'basis' => 'Sales by sold time; return credits by posted time; collections/refunds/expenses by recorded time, including older invoices. Current balances are lifetime totals. Gross margin uses stored cost estimates and is not net profit or verified COGS.'];
        });
    }
}

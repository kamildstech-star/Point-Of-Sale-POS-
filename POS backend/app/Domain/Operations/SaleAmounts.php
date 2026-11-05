<?php

namespace App\Domain\Operations;

final class SaleAmounts
{
    public static function for(object $sale): array
    {
        $returned = (string) ($sale->returned_total ?? '0');
        $refunded = (string) $sale->total_refunded;
        $net = Decimal::sub((string) $sale->grand_total, $returned);
        $retained = Decimal::sub((string) $sale->paid_total, $refunded);
        $due = Decimal::sub($net, $retained);
        $surplus = Decimal::sub($retained, $net);
        if (Decimal::cmp($due, '0') < 0) {
            $due = '0';
        }if (Decimal::cmp($surplus, '0') < 0) {
            $surplus = '0';
        }
        $status = Decimal::cmp($surplus, '0') > 0 ? 'refund_due' : (Decimal::cmp($refunded, '0') > 0 ? (Decimal::cmp($net, '0') === 0 ? 'refunded' : 'partially_refunded') : (Decimal::cmp($due, '0') === 0 ? 'paid' : (Decimal::cmp($retained, '0') === 0 ? 'unpaid' : 'partial')));

        return ['net_total' => $net, 'retained_payments' => $retained, 'balance_due' => Decimal::round($due, $sale->currency_scale), 'refund_due' => Decimal::round($surplus, $sale->currency_scale), 'payment_status' => $status];
    }
}

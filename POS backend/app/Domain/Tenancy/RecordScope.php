<?php

namespace App\Domain\Tenancy;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Shared by dashboard queries, operational details, posting and exports. */
final class RecordScope
{
    private const LOCATION = ['inventory_balances', 'stock_movements', 'stock_reservations', 'stock_adjustments', 'stock_counts', 'purchase_orders', 'goods_receipts', 'purchase_returns', 'damaged_stock_records'];

    private const PARENTS = [
        'sale_items' => ['sale_id', 'sales'], 'sale_payments' => ['sale_id', 'sales'],
        'sale_returns' => ['original_sale_id', 'sales'], 'refunds' => ['original_sale_id', 'sales'],
        'sale_return_items' => ['sale_return_id', 'sale_returns'], 'exchange_links' => ['sale_return_id', 'sale_returns'],
        'cash_sessions' => ['register_id', 'cash_registers'], 'cash_movements' => ['cash_session_id', 'cash_sessions'],
        'stock_count_items' => ['count_id', 'stock_counts'], 'stock_adjustment_items' => ['adjustment_id', 'stock_adjustments'],
        'purchase_order_items' => ['purchase_order_id', 'purchase_orders'], 'goods_receipt_items' => ['goods_receipt_id', 'goods_receipts'],
        'purchase_return_items' => ['purchase_return_id', 'purchase_returns'], 'supplier_payables' => ['goods_receipt_id', 'goods_receipts'],
        'supplier_settlements' => ['payable_id', 'supplier_payables'],
    ];

    public function __construct(private TenantContext $context, private ?array $branches = null, private bool $ownSales = false)
    {
    }

    public function query(string $table): Builder
    {
        return $this->apply(DB::table($table)->where($table.'.tenant_id', $this->context->id()), $table);
    }

    public function apply(Builder $query, string $table, ?string $alias = null): Builder
    {
        $alias ??= $table;
        $branches = $this->branches ?? ($this->context->branchRestricted() ? $this->context->allowedBranchIds() : null);
        $own = $this->ownSales || $this->context->ownSalesOnly();
        if ($branches !== null) {
            if ($table === 'branches') {
                $query->whereIn($alias.'.id', $branches);
            } elseif (in_array($table, ['locations', 'sales', 'cash_registers', 'retail_expenses', 'invoice_sequences', 'pos_held_carts'], true)) {
                $query->whereIn($alias.'.branch_id', $branches);
            } elseif (in_array($table, self::LOCATION, true)) {
                $query->whereIn($alias.'.location_id', $this->query('locations')->select('locations.id'));
            }
        }
        // Held carts are private working documents, even for managers/owners.
        if ($table === 'pos_held_carts') {
            $query->where($alias.'.created_by', $this->context->membership()->id);
        }
        if ($own && $table === 'sales') {
            $query->where($alias.'.cashier_membership_id', $this->context->membership()->id);
        }
        if ($own && $table === 'cash_sessions') {
            $query->where($alias.'.opened_by', $this->context->membership()->id);
        }
        if ($this->context->restricted() && $table === 'export_jobs') {
            $query->where($alias.'.initiated_by', $this->context->membership()->id)->where($alias.'.access_fingerprint', $this->context->accessFingerprint());
        }
        if (($branches !== null || $own) && isset(self::PARENTS[$table])) {
            [$column, $parent] = self::PARENTS[$table];
            $query->whereIn($alias.'.'.$column, $this->query($parent)->select($parent.'.id'));
        }

        return $query;
    }
}

<?php

namespace App\Domain\Dashboard;

use App\Domain\Tenancy\TenantContext;

final class DashboardCapabilities
{
    public function __construct(private TenantContext $context)
    {
    }

    public function allows(string $widget): bool
    {
        if (! $this->context->allows('dashboard.view')) {
            return false;
        }

        return match ($widget) {
            'inventory-trend' => $this->enhanced() && $this->context->allows('inventory.view'),
            'purchase-orders', 'supplier-overview' => $this->enhanced() && $this->context->allows('purchases.view'),
            'accounting-trend', 'accounting-exceptions' => $this->enhanced() && $this->allows('accounting'),
            'personal-sales' => $this->enhanced() && $this->context->allows('sales.view'),
            'sales', 'sales-trend' => $this->context->allows('sales.view') || $this->context->allows('reports.view'),
            'top-products' => $this->context->allows('products.view') && ($this->context->allows('sales.view') || $this->context->allows('reports.view')),
            'recent-sales' => $this->context->allows('sales.view'),
            'branch-performance', 'accounting' => $this->context->allows('reports.view') && ! $this->context->ownSalesOnly(),
            'inventory' => $this->context->allows('inventory.view'),
            'purchasing' => $this->context->allows('purchases.view'),
            'shift' => $this->context->allows('cash.open'),
            'cashier' => $this->context->allows('cash.open') || $this->context->allows('sales.view') || $this->context->allows('sales.create'),
            'staff-activity' => $this->context->allows('reports.view') && $this->context->allows('team.view') && ! $this->context->ownSalesOnly(),
            'approvals' => (bool) array_intersect($this->context->permissions(), ['inventory.approve', 'purchases.approve', 'sales.return.approve', 'expenses.approve']),
            'alerts' => $this->allows('inventory') || $this->allows('approvals'),
            default => false,
        };
    }

    public function ownSales(): bool
    {
        return $this->context->ownSalesOnly() || ! $this->context->allows('reports.view');
    }

    public function enhanced(): bool
    {
        $pilots = array_map('strval', config('dashboard.pilot_tenant_ids', []));
        return (bool) config('dashboard.specialist_enabled') && (! $pilots || in_array((string) $this->context->id(), $pilots, true));
    }

    public function toArray(): array
    {
        $widgets = array_values(array_filter(['sales', 'sales-trend', 'top-products', 'recent-sales', 'branch-performance', 'accounting', 'inventory', 'purchasing', 'shift', 'approvals', 'alerts', 'cashier', 'staff-activity', 'inventory-trend', 'purchase-orders', 'supplier-overview', 'accounting-trend', 'accounting-exceptions', 'personal-sales'], fn ($widget) => $this->allows($widget)));

        // Presentation only: widget authorization always uses allows(), including
        // explicit branch/personal restrictions and the union of effective grants.
        $codes = array_column($this->context->roles(), 'code');
        $profile = null;
        if ($this->context->allows('dashboard.view')) {
            foreach (['owner', 'admin', 'manager', 'cashier'] as $candidate) {
                if (in_array($candidate, $codes, true)) {
                    $profile = $candidate;
                    break;
                }
            }
        }

        if ($profile === null && $this->enhanced() && in_array('sales_associate', $codes, true)) $profile = 'sales_associate';

        return ['profile' => $profile, 'allowed_widgets' => $widgets, 'specialist_enabled' => $this->enhanced(), 'sales_scope' => $this->ownSales() ? 'own' : 'permitted_branches',
            'cost_estimates' => false, 'can_view_cost_fields' => $this->context->canViewCosts(), 'verified_gross_profit' => false, 'net_profit' => false,
            'held_sales' => $this->context->allows('sales.create') && \Illuminate\Support\Facades\Schema::hasTable('pos_held_carts'), 'stock_transfers' => false, 'expected_delivery_dates' => false, 'bank_balances' => false];
    }
}

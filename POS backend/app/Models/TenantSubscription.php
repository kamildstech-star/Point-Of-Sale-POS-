<?php

namespace App\Models;

class TenantSubscription extends TenantRecord
{
    protected $casts = ['grandfathered' => 'boolean', 'verified_at' => 'datetime', 'current_period_ends_at' => 'datetime'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function price()
    {
        return $this->belongsTo(PlanPrice::class, 'plan_price_id');
    }

    public function items()
    {
        return $this->hasMany(SubscriptionItem::class, 'subscription_id');
    }

    public function invoices()
    {
        return $this->hasMany(SaasInvoice::class, 'subscription_id');
    }
}

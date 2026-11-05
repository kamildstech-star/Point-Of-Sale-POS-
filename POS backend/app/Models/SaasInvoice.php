<?php

namespace App\Models;

class SaasInvoice extends TenantRecord
{
    protected $casts = ['total' => 'decimal:4'];

    public function subscription()
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }
}

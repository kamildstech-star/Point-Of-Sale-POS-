<?php

namespace App\Models;

class SubscriptionItem extends TenantRecord
{
    public function subscription()
    {
        return $this->belongsTo(TenantSubscription::class, 'subscription_id');
    }
}

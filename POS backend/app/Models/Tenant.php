<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $guarded = ['*'];

    protected $hidden = ['stripe_customer_id'];

    public function subscription()
    {
        return $this->hasOne(TenantSubscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(SaasInvoice::class);
    }
}

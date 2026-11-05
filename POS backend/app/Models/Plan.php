<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = ['*'];

    public function prices()
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function entitlements()
    {
        return $this->hasMany(PlanEntitlement::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    protected $guarded = ['*'];

    protected $casts = ['amount' => 'decimal:4', 'checkout_enabled' => 'boolean'];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}

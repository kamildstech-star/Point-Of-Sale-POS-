<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEntitlement extends Model
{
    public $timestamps = false;

    protected $guarded = ['*'];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}

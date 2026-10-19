<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDealers extends Model
{
    use HasFactory;
    protected $table = "business_dealers";

    protected $fillable = [
        "id","name","address","phone_number","description","type",
    ];

    public $timestamps = true;

}

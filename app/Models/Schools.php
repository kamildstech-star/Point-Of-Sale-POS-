<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schools extends Model
{
    use HasFactory;

    protected $table = "schools";

    protected $fillable = [
        "id","name","address","percentage","fixed_price","margin_type",
    ];

    public $timestamps = true;

    public function stock(){
        return $this->hasMany(Stocks::class);
    }
}

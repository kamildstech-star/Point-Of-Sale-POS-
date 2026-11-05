<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        "id","name",
    ];

    public $timestamps = true; 

    public function stock(){
        return $this->hasMany(Stocks::class);
        return $this->belongsToMany(Sizes::class);
    }
}


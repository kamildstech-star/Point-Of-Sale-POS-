<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sizes extends Model
{
    use HasFactory;

    protected $table = 'sizes';

    protected $fillable =[
        "id","product_id","size",
    ];

    public $timestamps = true;

    public function stock(){
        return $this->hasMany(Stocks::class);
    }
    public function Product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

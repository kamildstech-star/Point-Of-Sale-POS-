<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stocks extends Model
{
    use HasFactory;

    protected $table = "stocks";
    
    protected $fillable = [
        "id",
        "quantity",
        "school_id",
        "product_id",
        "size_id",
        "base_price",
        "sale_price",
    ];

    public $timestamps = true;

    public function size(){
        return $this->belongsTo(Sizes::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function school(){
        return $this->belongsTo(Schools::class);
    }
    public function subtractQuantity($quantity){
        $this->quantity -= $quantity;
        $this->save();
    }
}

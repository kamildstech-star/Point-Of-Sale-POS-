<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';
    protected $fillable = [
        "id",
        "school_id",
        "order_id",
        "product_id",
        "size_id",
        "price",
        "returned",
        "total",
        "quantity",
        "discount",
        "school_margin",
        "price_after_discount",
        "margin_after_discount",
    ];
    public $timestamps = true;

    public function stock(){

        return $this->hasMany(Stocks::class, 'school_id', 'school_id')
                    ->where('product_id', 'product_id');
                
    }
    public function size(){
        return $this->belongsTo(Sizes::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }
    public function school(){
        return $this->belongsTo(Schools::class);
    }
}

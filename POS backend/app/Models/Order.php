<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        "id", 
        "customer_name",
        "phone",
        "discount",
        "discount_percentage",
        "item_discount",
        "school_margin",
        "price_after_discount",
        "margin_after_discount",
        "subtotal",
        "total",
        "received_amount",
        "remaining_amount",
        "gst",
        "is_return",
    ];
    public $timestamps = true;

    public function school(){
        return $this->belongsTo(Schools::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function product(){
        return $this->belongsTo(product::class);
    }
    public function size(){
        return $this->belongsTo(Sizes::class);
    }
    public function stock(){
        return $this->hasOne(Stocks::class);
    }
    public function orderItems(){
        return $this->hasMany(OrderItem::class);
    }
    public function orderRandomItems(){
        return $this->hasMany(order_random_items::class);
    }
    public function scopeWithAndWhereHas($query, $relation, $callback = null)
    {
        if (is_callable(value: $callback)) {
            return $query->with([$relation => $callback])
                ->whereHas($relation, $callback);
        }
        return $query->with($relation)->has($relation);
    }
}

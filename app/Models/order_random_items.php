<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class order_random_items extends Model
{
    use HasFactory;
    protected $table = 'order_random_items';
    protected $fillable = [
        "id",
        "order_id",
        "product",
        "price",
        "quantity",
    ];
    public $timestamps = true;
    public function order(){
        return $this->belongsTo(Order::class);
    }
}

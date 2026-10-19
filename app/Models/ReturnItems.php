<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItems extends Model
{
    use HasFactory;

    protected $table = 'return_items';
    protected $fillable = [
        'id',
        'return_id',
        'order_id',
        'school_id',
        'product_id',
        'size_id',
        'order_item_id',
        'quantity',
        'price'
    ];

    public $timestamps = true;
}

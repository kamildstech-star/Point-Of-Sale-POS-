<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
        
class ReturnOrder extends Model
{
    use HasFactory;

    protected $table = "returns";
    protected $fillable = [
        'id',
        'subtotal',
        'total'
    ];

    public $timestamps = true;

    public function returnitems(){
        return $this->hasMany(ReturnItems::class, 'return_id', 'id');
    }
}

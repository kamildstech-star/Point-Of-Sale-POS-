<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyAccounts extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'daily_account';
    protected $fillable = [
        "id",
        "closing_balance",
        "sale",
        "in",
        "out",
        "total_in",
        "total_out",
        "created_at",
        "updated_at",
    ];
    public $timestamps = true;
}

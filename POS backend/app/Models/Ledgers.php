<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Ledgers extends Model
{
    use HasFactory;
    protected $table = 'ledgers';
    protected $fillable = [
        "id",
        "debit",
        "credit",
        "description",
        "balance",
        "balance_type",
        "ledgerable_id",
        "ledgerable_type",
        "created_at",
        "updated_at",
    ];
    public $timestamps = true;

    public function ledgerable(): MorphTo {
        return $this->morphTo();
    }
}

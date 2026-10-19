<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class BussinessDealer extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $table = 'business_dealers';
    protected $fillable = [
        "id",
        "name",
        "credit",
        "address",
        "phone_number",
        "description",
        "type",
        "created_at",
        "updated_at",
    ];
    public $timestamps = true;
    public function ledger(): MorphMany{
        return $this->morphMany(Ledgers::class, 'ledgerable');
    }
}

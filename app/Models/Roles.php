<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Roles extends Model
{
    use HasFactory;
    protected $table = "roles";

    protected $fillable = [
        "id","role",
    ];
    public function users() {
        return $this->hasMany(User::class);
    }
    
    public $timestamps = true;
    
    public function role(): HasOne
    {
        return $this->hasOne(User::class);
    }
}

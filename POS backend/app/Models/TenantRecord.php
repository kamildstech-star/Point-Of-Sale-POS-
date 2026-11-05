<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class TenantRecord extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn (Builder $q) => $q->where($q->getModel()->qualifyColumn('tenant_id'), app(\App\Domain\Tenancy\TenantContext::class)->id()));
    }
}

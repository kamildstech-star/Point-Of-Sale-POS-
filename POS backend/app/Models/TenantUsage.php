<?php

namespace App\Models;

class TenantUsage extends TenantRecord
{
    protected $table = 'tenant_usage';

    protected $casts = ['quantity' => 'integer'];
}

<?php

namespace App\Domain\Tenancy;

class LegacyTables
{
    // Users retain legacy references; new retail identities live in customers.
    // Legacy roles/permission remain the Blade authorization namespace.
    public const BUSINESS = ['products', 'sizes', 'schools', 'stocks', 'orders', 'order_items', 'order_random_items', 'returns', 'return_items', 'business_dealers', 'ledger', 'ledgers', 'daily_account', 'expenses', 'settings', 'logs', 'todolist'];
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BillingPlanSeeder extends Seeder
{
    public function run(): void
    {
        app(\App\Domain\Billing\BillingService::class)->seedPlans();
    }
}

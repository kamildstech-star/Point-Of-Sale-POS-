<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

\Illuminate\Support\Facades\Schedule::command('operations:maintenance')->everyFiveMinutes()->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('billing:reconcile')->hourly()->withoutOverlapping()->when(fn () => config('stripe_billing.enabled'));
\Illuminate\Support\Facades\Schedule::command('operations:backup')->dailyAt('02:00')->timezone('Asia/Karachi')->withoutOverlapping()->when(fn () => config('retail.scheduled_backups'));

<?php

return [
    'enabled' => (bool) env('STRIPE_BILLING_ENABLED', false),
    'key' => env('STRIPE_KEY'), 'secret' => env('STRIPE_SECRET'), 'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => strtolower(env('STRIPE_CURRENCY', 'usd')), 'frontend_url' => env('FRONTEND_URL', 'http://127.0.0.1:5173'),
    'live_enabled' => (bool) env('STRIPE_LIVE_ENABLED', false),
    'legacy_access' => (bool) env('BILLING_LEGACY_ACCESS', true),
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 7),
    'queue_connection' => env('BILLING_QUEUE_CONNECTION', 'database'),
];

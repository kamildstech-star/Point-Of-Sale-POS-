<?php

return [
    'enabled' => env('DASHBOARD_API_ENABLED', true),
    'cache_seconds' => (int) env('DASHBOARD_CACHE_SECONDS', 30),
];

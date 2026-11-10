<?php

return [
    'enabled' => env('DASHBOARD_API_ENABLED', true),
    'cache_seconds' => (int) env('DASHBOARD_CACHE_SECONDS', 30),
    // Opt-in pilot. Empty tenant list means all tenants only after explicit enablement.
    'specialist_enabled' => env('DASHBOARD_SPECIALIST_ENABLED', false),
    'pilot_tenant_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('DASHBOARD_PILOT_TENANT_IDS', ''))), fn ($id) => $id !== '')),
    'platform_enabled' => env('DASHBOARD_PLATFORM_ENABLED', false),
];

<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TenantContextController;
use App\Http\Middleware\RequireLegacyStaff;
use Illuminate\Support\Facades\Route;

Route::post('v1/webhooks/stripe', [\App\Http\Controllers\Api\V1\BillingController::class, 'webhook'])->middleware('throttle:stripe-webhooks');
Route::get('v1/health/readiness', [\App\Http\Controllers\Api\V1\WorkspaceController::class, 'readiness'])->middleware('throttle:stripe-webhooks');

// All foundation endpoints are session-only. The web group guarantees CSRF
// protection even when Origin/Referer is missing or not a stateful SPA domain.
Route::prefix('v1')->middleware('web')->withoutMiddleware(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class)->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:spa-login');
    Route::post('auth/register', [\App\Http\Controllers\Api\V1\RegistrationController::class, 'store'])->middleware('throttle:spa-login');
    Route::middleware(['auth:sanctum', 'throttle:spa-api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me/context', [\App\Http\Controllers\Api\V1\DashboardContextController::class, 'show']);
        Route::get('platform/context', [\App\Http\Controllers\Api\V1\DashboardContextController::class, 'platform'])->middleware(\App\Http\Middleware\RequirePlatformPermission::class);
        Route::get('platform/dashboard', [\App\Http\Controllers\Api\V1\PlatformDashboardController::class, 'index'])->middleware([\App\Http\Middleware\RequirePlatformPermission::class, 'throttle:dashboard-api']);
        Route::middleware(RequireLegacyStaff::class)->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::get('auth/me', [AuthController::class, 'me']);
            Route::get('tenants', [TenantContextController::class, 'index']);
            Route::post('tenants', [TenantContextController::class, 'store']);
            Route::post('tenant/switch', [TenantContextController::class, 'switch']);
            Route::post('tenants/{tenant}/activate', [TenantContextController::class, 'switch']);
            Route::middleware([\App\Http\Middleware\ResolveTenant::class, \App\Http\Middleware\SubscriptionAccess::class])->group(function () {
                Route::get('dashboard/{resource}/{id?}', [\App\Http\Controllers\Api\V1\DashboardController::class, 'index'])->whereNumber('id')->middleware('throttle:dashboard-api');
                Route::get('team/{member}/access', [\App\Http\Controllers\Api\V1\MemberAccessController::class, 'show'])->whereNumber('member');
                Route::patch('team/{member}/access', [\App\Http\Controllers\Api\V1\MemberAccessController::class, 'update'])->whereNumber('member');
                require __DIR__.'/operations.php';
                require __DIR__.'/finance-workspace.php';
                Route::get('context', [TenantContextController::class, 'context']);
                Route::get('team', [\App\Http\Controllers\Api\V1\TeamController::class, 'index']);
                Route::post('team', [\App\Http\Controllers\Api\V1\TeamController::class, 'store']);
                Route::patch('team/{member}', [\App\Http\Controllers\Api\V1\TeamController::class, 'update']);
                Route::delete('team/{member}', [\App\Http\Controllers\Api\V1\TeamController::class, 'destroy']);
                Route::get('roles', [\App\Http\Controllers\Api\V1\TeamController::class, 'roles']);
                Route::post('roles', [\App\Http\Controllers\Api\V1\TeamController::class, 'saveRole']);
                Route::patch('roles/{role}', [\App\Http\Controllers\Api\V1\TeamController::class, 'saveRole']);
                Route::delete('roles/{role}', [\App\Http\Controllers\Api\V1\TeamController::class, 'destroyRole']);
                Route::get('products/export', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'export']);
                Route::get('variants', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'variants']);
                Route::get('variants/{variant}', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'variant']);
                Route::get('products', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'index']);
                Route::post('products', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'store']);
                Route::get('products/{product}', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'show']);
                Route::patch('products/{product}', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'update']);
                Route::delete('products/{product}', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'destroy']);
                Route::post('products/{product}/variants', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'saveVariant']);
                Route::patch('products/{product}/variants/{variant}', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'saveVariant']);
                Route::delete('products/{product}/variants/{variant}', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'destroyVariant']);
                Route::post('products/{product}/images', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'upload']);
                Route::get('catalog-images/{image}', [\App\Http\Controllers\Api\V1\ProductApiController::class, 'image']);
                Route::get('catalog/{resource}', [\App\Http\Controllers\Api\V1\CatalogReferenceController::class, 'index']);
                Route::post('catalog/{resource}', [\App\Http\Controllers\Api\V1\CatalogReferenceController::class, 'save']);
                Route::get('catalog/{resource}/{id}', [\App\Http\Controllers\Api\V1\CatalogReferenceController::class, 'show']);
                Route::patch('catalog/{resource}/{id}', [\App\Http\Controllers\Api\V1\CatalogReferenceController::class, 'save']);
                Route::delete('catalog/{resource}/{id}', [\App\Http\Controllers\Api\V1\CatalogReferenceController::class, 'destroy']);
                Route::get('legacy/catalog', [\App\Http\Controllers\Api\V1\LegacyMappingController::class, 'index']);
                Route::post('legacy/sku-policy', [\App\Http\Controllers\Api\V1\LegacyMappingController::class, 'approve']);
                Route::post('legacy/catalog-map', [\App\Http\Controllers\Api\V1\LegacyMappingController::class, 'map']);
            });
        });
    });
});

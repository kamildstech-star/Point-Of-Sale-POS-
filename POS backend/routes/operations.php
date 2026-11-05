<?php

use App\Http\Controllers\Api\V1\InventoryController as Inventory;
use App\Http\Controllers\Api\V1\OperationsReferenceController as Reference;
use App\Http\Controllers\Api\V1\PosController as Pos;
use App\Http\Controllers\Api\V1\PurchasingController as Purchasing;
use Illuminate\Support\Facades\Route;

// Loaded inside the session-authenticated, CSRF-protected tenant route group.
Route::get('pos/products', [Pos::class, 'search']);
Route::post('pos/quotes', [Pos::class, 'quote']);
Route::post('pos/checkout', [Pos::class, 'checkout']);
Route::get('pos/held-carts', [\App\Http\Controllers\Api\V1\HeldCartController::class, 'index']);
Route::post('pos/held-carts', [\App\Http\Controllers\Api\V1\HeldCartController::class, 'store']);
Route::get('pos/held-carts/{id}', [\App\Http\Controllers\Api\V1\HeldCartController::class, 'show'])->whereNumber('id');
Route::post('pos/held-carts/{id}/discard', [\App\Http\Controllers\Api\V1\HeldCartController::class, 'discard'])->whereNumber('id');
Route::get('sales', [Pos::class, 'sales']);
Route::get('sales/{id}', [Pos::class, 'sale'])->whereNumber('id');
Route::post('sales/{id}/payments', [Pos::class, 'pay'])->whereNumber('id');
Route::get('cash/sessions', [Pos::class, 'sessions']);
Route::post('cash/sessions', [Pos::class, 'open']);
Route::get('cash/sessions/{id}/reconciliation', [Pos::class, 'reconciliation'])->whereNumber('id');
Route::get('pos/branches/{id}/preview', [Pos::class, 'preview'])->whereNumber('id');
Route::post('pos/branches/{id}/activate', [Pos::class, 'activate'])->whereNumber('id');
Route::get('legacy/customer-links', [Pos::class, 'customerLinks']);
Route::post('legacy/map-customers', [Pos::class, 'mapCustomers']);
Route::patch('legacy/customer-links/{id}', [Pos::class, 'resolveCustomer'])->whereNumber('id');
Route::get('operations/{resource}', [Reference::class, 'index']);
Route::get('operations/{resource}/{id}', [Reference::class, 'show'])->whereNumber('id');
Route::post('operations/{resource}', [Reference::class, 'save']);
Route::patch('operations/{resource}/{id}', [Reference::class, 'save'])->whereNumber('id');
Route::delete('operations/{resource}/{id}', [Reference::class, 'destroy'])->whereNumber('id');
Route::post('inventory/reconcile', [Inventory::class, 'reconcile']);
Route::post('inventory/reservations', [Inventory::class, 'reserve']);
Route::post('inventory/reservations/{id}/release', [Inventory::class, 'release'])->whereNumber('id');
Route::get('inventory/{resource}', [Inventory::class, 'index']);
Route::get('inventory/{resource}/{id}', [Inventory::class, 'show'])->whereNumber('id');
Route::post('inventory/{resource}', [Inventory::class, 'document']);
Route::post('inventory/{resource}/{id}/post', [Inventory::class, 'approve'])->whereNumber('id');
Route::post('purchasing/orders', [Purchasing::class, 'order']);
Route::post('purchasing/reconcile', [Purchasing::class, 'reconcile']);
Route::post('purchasing/orders/{id}/approve', [Purchasing::class, 'approve'])->whereNumber('id');
Route::post('purchasing/orders/{id}/receipts', [Purchasing::class, 'receive'])->whereNumber('id');
Route::post('purchasing/receipts/{id}/returns', [Purchasing::class, 'returnGoods'])->whereNumber('id');
Route::post('purchasing/payables/{id}/settlements', [Purchasing::class, 'settle'])->whereNumber('id');
Route::get('purchasing/{resource}', [Purchasing::class, 'index']);
Route::get('purchasing/{resource}/{id}', [Purchasing::class, 'show'])->whereNumber('id');

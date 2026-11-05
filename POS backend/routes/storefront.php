<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\LoginController;

Route::get('/test', [FrontendController::class, 'frontendIndex'])->name('index');
Route::get('/frontend', [FrontendController::class, 'frontendIndex'])->name('frontend');
Route::get('/all-products', [FrontendController::class, 'allProducts'])->name('frontend.allProducts');
Route::get('/frontend/create-account', [FrontendController::class, 'createAccount'])->name('frontend.createAccount');
Route::get('/frontend/account', [FrontendController::class, 'account'])->name('frontend.account');
Route::get('/frontend/addresses', [FrontendController::class, 'address'])->name('frontend.address');
Route::get('/frontend/about', [AboutController::class, 'aboutIndex'])->name('about');
Route::get('/frontend/schools', [FrontendController::class, 'schoolIndex'])->name('schools');
Route::get('/frontend/contact-us', [FrontendController::class, 'contactIndex'])->name('contactUs');
Route::get('/frontend/login', [LoginController::class, 'loginIndex'])->name('frontend-login');
Route::get('/frontend/cart', [CartController::class, 'cartIndex'])->name('frontend-cart');


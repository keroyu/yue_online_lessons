<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\Payment\PayuniController;
use App\Http\Controllers\Purchase\FreePurchaseController;
use App\Http\Controllers\Webhook\PortalyController;
use Illuminate\Support\Facades\Route;

// Portaly Webhook
Route::post('/webhooks/portaly', [PortalyController::class, 'handle'])
    ->name('webhook.portaly');

// PayUni 統一金流
Route::post('/payment/payuni/initiate', [PayuniController::class, 'initiate'])
    ->name('payuni.initiate');
Route::post('/webhooks/payuni', [PayuniController::class, 'notify'])
    ->name('payuni.notify');
// ReturnURL moved to web.php for session/auth support

// 免費課程報名
Route::post('/purchase/free/{course}', [FreePurchaseController::class, 'store'])
    ->name('purchase.free');

// Cart (auth required)
Route::middleware('auth:web')->group(function () {
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/{courseId}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/merge', [CartController::class, 'merge'])->name('cart.merge');
});

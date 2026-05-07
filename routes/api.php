<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Payment\NewebpayController;
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
Route::post('/webhooks/newebpay', [NewebpayController::class, 'notify'])
    ->name('newebpay.notify');
// ReturnURL routes moved to web.php for session/auth support

// 免費課程報名
Route::post('/purchase/free/{course}', [FreePurchaseController::class, 'store'])
    ->name('purchase.free');

// 付款結果輪詢
Route::get('/checkout/order-status', function (\Illuminate\Http\Request $request) {
    $orderNo = $request->query('order', '');
    $order   = \App\Models\Order::where('merchant_order_no', $orderNo)->first();
    return response()->json(['status' => $order?->status ?? 'not_found']);
})->name('checkout.order-status');

// 購物車結帳 (public — guest checkout supported)
Route::post('/checkout/check-email', [CheckoutController::class, 'checkEmail'])
    ->name('checkout.check-email');
Route::post('/checkout/initiate', [CheckoutController::class, 'initiate'])
    ->name('checkout.initiate');


<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PayuniService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayuniController extends Controller
{
    public function __construct(
        protected PayuniService $payuniService
    ) {}

    /**
     * Handle PayUni NotifyURL async callback (server-to-server).
     * Always returns 200 to prevent PayUni retry loops.
     *
     * POST /api/webhooks/payuni
     */
    public function notify(Request $request): Response
    {
        Log::info('PayUni Notify received', [
            'MerID'      => $request->input('MerID'),
            'has_encrypt' => !empty($request->input('EncryptInfo')),
        ]);

        try {
            $this->payuniService->processNotify(
                $request->input('EncryptInfo', ''),
                $request->input('HashInfo', '')
            );
        } catch (\Exception $e) {
            Log::error('PayUni Notify: unexpected exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return '1|OK' to prevent PayUni retry loops
        return response('1|OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle PayUni ReturnURL — browser redirect after payment.
     * Also processes purchase creation to prevent race condition with NotifyURL.
     *
     * POST /payment/payuni/return (web route, CSRF excluded)
     */
    public function return(Request $request): RedirectResponse
    {
        $encryptInfo = $request->input('EncryptInfo', '');
        $hashInfo    = $request->input('HashInfo', '');

        Log::info('PayUni Return received', [
            'has_encrypt' => !empty($encryptInfo),
            'auth'        => auth()->check(),
            'all_inputs'  => array_keys($request->all()),
        ]);

        $data = $this->payuniService->verifyAndDecrypt($encryptInfo, $hashInfo);

        if ($data) {
            $isSuccess  = ($data['Status'] ?? '') === 'SUCCESS' && ($data['TradeStatus'] ?? '') == '1';
            $merTradeNo = $data['MerTradeNo'] ?? '';

            Log::info('PayUni Return: result', [
                'Status'      => $data['Status'] ?? null,
                'TradeStatus' => $data['TradeStatus'] ?? null,
                'MerTradeNo'  => $merTradeNo,
            ]);

            if ($isSuccess) {
                // Safety net: fulfill order on ReturnURL too. fulfillOrder() is idempotent.
                $order = \App\Models\Order::where('merchant_order_no', $merTradeNo)->first();
                if ($order && $order->status !== 'paid') {
                    try {
                        $gatewayTradeNo = $data['TradeNo'] ?? $merTradeNo;
                        app(\App\Services\CheckoutService::class)->fulfillOrder($order, $gatewayTradeNo, 'payuni');
                        Log::info('PayUni Return: order fulfilled (fallback)', ['MerTradeNo' => $merTradeNo]);
                    } catch (\Exception $e) {
                        Log::error('PayUni Return: fallback fulfillOrder failed', [
                            'error'      => $e->getMessage(),
                            'MerTradeNo' => $merTradeNo,
                        ]);
                    }
                }
                return redirect('/payment/success?order=' . $merTradeNo);
            }
        } else {
            Log::warning('PayUni Return: verification failed, falling back to redirect');
        }

        return redirect('/cart')->with('payment_failed', '付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com');
    }
}

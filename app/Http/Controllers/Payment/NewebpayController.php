<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\NewebpayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class NewebpayController extends Controller
{
    public function __construct(
        protected NewebpayService $newebpayService
    ) {}

    /**
     * Handle NewebPay NotifyURL async callback (server-to-server).
     * MUST return plain string "SUCCESS" — any other response causes retry loops.
     *
     * Risk: ReturnURL also fires; idempotency in fulfillOrder prevents double-processing.
     *
     * POST /api/webhooks/newebpay
     */
    public function notify(Request $request): Response
    {
        Log::info('NewebPay Notify received', [
            'MerchantID' => $request->input('MerchantID'),
            'Status'     => $request->input('Status'),
        ]);

        $tradeInfo = $request->input('TradeInfo', '');
        $tradeSha  = $request->input('TradeSha', '');

        // Risk: TradeSha must be verified against the raw hex TradeInfo from POST body.
        if (!$this->newebpayService->verifyTradeSha($tradeSha, $tradeInfo)) {
            Log::warning('NewebPay Notify: TradeSha verification failed');
            return $this->successResponse();
        }

        $params = $this->newebpayService->decryptTradeInfo($tradeInfo);

        if (empty($params)) {
            Log::warning('NewebPay Notify: decryptTradeInfo returned empty');
            return $this->successResponse();
        }

        Log::info('NewebPay Notify: decrypted', [
            'Status'          => $params['Status'] ?? null,
            'MerchantOrderNo' => $params['MerchantOrderNo'] ?? null,
            'TradeNo'         => $params['TradeNo'] ?? null,
        ]);

        if (($params['Status'] ?? '') !== 'SUCCESS') {
            Log::info('NewebPay Notify: non-success status, skipping', ['Status' => $params['Status'] ?? null]);
            return $this->successResponse();
        }

        // Risk: field is MerchantOrderNo (NewebPay), not MerTradeNo (PayUni).
        $merchantOrderNo = $params['MerchantOrderNo'] ?? null;
        if (!$merchantOrderNo) {
            Log::error('NewebPay Notify: missing MerchantOrderNo in decrypted params');
            return $this->successResponse();
        }

        $order = Order::where('merchant_order_no', $merchantOrderNo)->first();
        if (!$order) {
            Log::error('NewebPay Notify: order not found', ['MerchantOrderNo' => $merchantOrderNo]);
            return $this->successResponse();
        }

        try {
            $gatewayTradeNo = $params['TradeNo'] ?? $merchantOrderNo;
            app(CheckoutService::class)->fulfillOrder($order, $gatewayTradeNo, 'newebpay');
            Log::info('NewebPay Notify: order fulfilled', [
                'MerchantOrderNo' => $merchantOrderNo,
                'order_id'        => $order->id,
            ]);
        } catch (\Exception $e) {
            Log::error('NewebPay Notify: fulfillOrder failed', [
                'error'           => $e->getMessage(),
                'MerchantOrderNo' => $merchantOrderNo,
            ]);
        }

        return $this->successResponse();
    }

    /**
     * Handle NewebPay ReturnURL — browser redirect after payment.
     * ONLY decrypts and redirects; does NOT call fulfillOrder (that is NotifyURL's job).
     *
     * POST /payment/newebpay/return (web route, CSRF excluded)
     */
    public function return(Request $request): RedirectResponse
    {
        Log::info('NewebPay Return received', [
            'has_trade_info' => !empty($request->input('TradeInfo')),
        ]);

        $tradeInfo = $request->input('TradeInfo', '');
        $tradeSha  = $request->input('TradeSha', '');

        if (!$this->newebpayService->verifyTradeSha($tradeSha, $tradeInfo)) {
            Log::warning('NewebPay Return: TradeSha verification failed');
            return redirect('/cart')->with('payment_failed', '付款驗證失敗，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com');
        }

        $params = $this->newebpayService->decryptTradeInfo($tradeInfo);

        if (empty($params)) {
            Log::warning('NewebPay Return: decryptTradeInfo returned empty');
            return redirect('/cart')->with('payment_failed', '付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com');
        }

        $isSuccess       = ($params['Status'] ?? '') === 'SUCCESS';
        $merchantOrderNo = $params['MerchantOrderNo'] ?? '';

        Log::info('NewebPay Return: result', [
            'Status'          => $params['Status'] ?? null,
            'MerchantOrderNo' => $merchantOrderNo,
        ]);

        if ($isSuccess && $merchantOrderNo) {
            return redirect('/payment/success?order=' . $merchantOrderNo);
        }

        return redirect('/cart')->with('payment_failed', '付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com');
    }

    /**
     * NewebPay requires the plain string "SUCCESS" (not JSON, not "1|OK").
     * Returning anything else causes NewebPay to retry the notification.
     */
    private function successResponse(): Response
    {
        return response('SUCCESS', 200)->header('Content-Type', 'text/plain');
    }
}

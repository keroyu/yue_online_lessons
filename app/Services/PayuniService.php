<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PayuniService
{
    private string $merKey;
    private string $merIV;
    private string $apiUrl;

    public function __construct()
    {
        $this->merKey = SiteSetting::get('payuni_hash_key', config('services.payuni.hash_key', ''));
        $this->merIV  = SiteSetting::get('payuni_hash_iv', config('services.payuni.hash_iv', ''));
        $sandbox      = config('services.payuni.sandbox', false);
        $prefix       = $sandbox ? 'https://sandbox-' : 'https://';
        $this->apiUrl = $prefix . 'api.payuni.com.tw/api/upp';
    }

    /**
     * Build PayUni UPP form fields for Order-based checkout.
     * Uses Order snapshot data; preserves existing buildPaymentForm() signature.
     *
     * @return array{ endpoint: string, fields: array }
     */
    public function buildOrderPaymentForm(Order $order, array $buyer): array
    {
        $order->loadMissing('items');
        $firstItem = $order->items->first();
        $prodDesc  = $firstItem ? mb_substr($firstItem->course_name, 0, 50) : '課程購買';
        if ($order->items->count() > 1) {
            $prodDesc = mb_substr($prodDesc, 0, 40) . ' 等 ' . $order->items->count() . ' 門課程';
        }

        $merchantId = SiteSetting::get('payuni_merchant_id', config('services.payuni.merchant_id', ''));
        $params = [
            'MerID'       => $merchantId,
            'MerTradeNo'  => $order->merchant_order_no,
            'TradeAmt'    => (int) $order->total_amount,
            'ProdDesc'    => $prodDesc,
            'UsrMail'     => $order->buyer_email,
            'UsrName'     => $order->buyer_name,
            'UsrMobile'   => $order->buyer_phone,
            'ReturnURL'   => url('/payment/payuni/return'),
            'NotifyURL'   => url('/api/webhooks/payuni'),
            'Timestamp'   => time(),
            'Lang'        => 'zh-tw',
        ];

        $encryptInfo = $this->encrypt($params);
        $hashInfo    = $this->hashInfo($encryptInfo);

        return [
            'endpoint' => $this->apiUrl,
            'fields'   => [
                'MerID'       => $merchantId,
                'Version'     => '1.0',
                'EncryptInfo' => $encryptInfo,
                'HashInfo'    => $hashInfo,
            ],
        ];
    }

    /**
     * Verify HashInfo and decrypt EncryptInfo from PayUni callback.
     * Returns decoded params array, or null on failure.
     */
    public function verifyAndDecrypt(string $encryptInfo, string $hashInfo): ?array
    {
        $expectedHash = $this->hashInfo($encryptInfo);
        if (!hash_equals($expectedHash, $hashInfo)) {
            Log::warning('PayUni: HashInfo mismatch', [
                'expected' => $expectedHash,
                'received' => $hashInfo,
            ]);
            return null;
        }
        return $this->decrypt($encryptInfo);
    }

    /**
     * Process PayUni NotifyURL callback.
     * Accepts raw EncryptInfo and HashInfo strings (not the Request object).
     * Returns structured result array; the controller maps it to '1|OK' HTTP response.
     *
     * @return array{ success: bool, error: string }
     */
    public function processNotify(string $encryptInfo, string $hashInfo): array
    {
        $data = $this->verifyAndDecrypt($encryptInfo, $hashInfo);

        if (!$data) {
            Log::warning('PayUni Notify: signature verification failed');
            return ['success' => false, 'error' => 'signature_mismatch'];
        }

        Log::info('PayUni Notify: received', [
            'Status'       => $data['Status'] ?? null,
            'TradeStatus'  => $data['TradeStatus'] ?? null,
            'MerTradeNo'   => $data['MerTradeNo'] ?? null,
            'all_keys'     => array_keys($data),
        ]);

        // Only process successful payments
        if (($data['Status'] ?? '') !== 'SUCCESS' || ($data['TradeStatus'] ?? '') != '1') {
            Log::info('PayUni Notify: non-success status, skipping', ['data' => $data]);
            return ['success' => true, 'error' => ''];
        }

        $merTradeNo = $data['MerTradeNo'] ?? null;
        if (!$merTradeNo) {
            Log::error('PayUni Notify: missing MerTradeNo');
            return ['success' => false, 'error' => 'missing_trade_no'];
        }

        $order = Order::where('merchant_order_no', $merTradeNo)->first();
        if (!$order) {
            Log::error('PayUni Notify: order not found', ['MerTradeNo' => $merTradeNo]);
            return ['success' => false, 'error' => 'order_not_found'];
        }

        try {
            $gatewayTradeNo = $data['TradeNo'] ?? $merTradeNo;
            app(\App\Services\CheckoutService::class)->fulfillOrder($order, $gatewayTradeNo, 'payuni');
            Log::info('PayUni Notify: order fulfilled', ['MerTradeNo' => $merTradeNo, 'order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('PayUni Notify: fulfillOrder failed', ['error' => $e->getMessage(), 'MerTradeNo' => $merTradeNo]);
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => true, 'error' => ''];
    }

    /**
     * Get or create user by email, updating name/phone with latest data.
     */
    public function getOrCreateUser(string $email, ?string $name, ?string $phone): User
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            $updates = [];
            if (!empty($name)) {
                $updates['real_name'] = $name;
            }
            if (!empty($phone)) {
                $updates['phone'] = $phone;
            }
            if (!empty($updates)) {
                $user->update($updates);
            }
            return $user;
        }

        return User::create([
            'email'     => $email,
            'real_name' => $name,
            'phone'     => $phone,
            'role'      => 'member',
        ]);
    }

    // ─── Private crypto helpers (mirror PayuniApi private methods) ───────────

    private function encrypt(array $params): string
    {
        $tag = '';
        $encrypted = openssl_encrypt(
            http_build_query($params),
            'aes-256-gcm',
            trim($this->merKey),
            0,
            trim($this->merIV),
            $tag
        );
        return trim(bin2hex($encrypted . ':::' . base64_encode($tag)));
    }

    private function decrypt(string $encryptStr): array
    {
        [$encryptData, $tag] = explode(':::', hex2bin($encryptStr), 2);
        $decrypted = openssl_decrypt(
            $encryptData,
            'aes-256-gcm',
            trim($this->merKey),
            0,
            trim($this->merIV),
            base64_decode($tag)
        );
        $result = [];
        parse_str((string) $decrypted, $result);
        return $result;
    }

    private function hashInfo(string $encryptStr): string
    {
        return strtoupper(hash('sha256', $this->merKey . $encryptStr . $this->merIV));
    }
}

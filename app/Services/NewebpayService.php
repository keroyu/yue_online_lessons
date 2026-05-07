<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;

class NewebpayService
{
    private string $merchantId;
    private string $hashKey;
    private string $hashIV;
    private string $endpoint;

    public function __construct()
    {
        $this->merchantId = SiteSetting::get('newebpay_merchant_id', config('services.newebpay.merchant_id', ''));
        $this->hashKey    = SiteSetting::get('newebpay_hash_key', config('services.newebpay.hash_key', ''));
        $this->hashIV     = SiteSetting::get('newebpay_hash_iv', config('services.newebpay.hash_iv', ''));

        $env            = SiteSetting::get('newebpay_env', config('services.newebpay.env', 'sandbox'));
        $host           = $env === 'production' ? 'core.newebpay.com' : 'ccore.newebpay.com';
        $this->endpoint = "https://{$host}/MPG/mpg_gateway";
    }

    /**
     * Build NewebPay MPG form fields for frontend auto-submit.
     *
     * @return array{ endpoint: string, fields: array }
     */
    public function buildPaymentForm(Order $order, array $buyer): array
    {
        $order->loadMissing('items');
        $firstItem = $order->items->first();
        $itemDesc  = $firstItem ? mb_substr($firstItem->course_name, 0, 50) : '課程購買';
        if ($order->items->count() > 1) {
            $itemDesc = mb_substr($itemDesc, 0, 40) . ' 等 ' . $order->items->count() . ' 門課程';
        }

        $tradeParams = [
            'MerchantID'      => $this->merchantId,
            'RespondType'     => 'JSON',
            'TimeStamp'       => time(),
            'Version'         => '2.3',
            'MerchantOrderNo' => $order->merchant_order_no,
            'Amt'             => (int) $order->total_amount,
            'ItemDesc'        => $itemDesc,
            'Email'           => $order->buyer_email,
            'LoginType'       => 0,
            'ReturnURL'       => url('/payment/newebpay/return'),
            'NotifyURL'       => url('/api/webhooks/newebpay'),
        ];

        $tradeInfo = $this->encryptTradeInfo($tradeParams);
        $tradeSha  = $this->buildTradeSha($tradeInfo);

        return [
            'endpoint' => $this->endpoint,
            'fields'   => [
                'MerchantID' => $this->merchantId,
                'TradeInfo'  => $tradeInfo,
                'TradeSha'   => $tradeSha,
                'Version'    => '2.3',
            ],
        ];
    }

    /**
     * Verify TradeSha using the raw TradeInfo hex string from the POST body.
     * Risk: must use the hex string BEFORE decryption, not the decrypted plaintext.
     */
    public function verifyTradeSha(string $tradeSha, string $tradeInfo): bool
    {
        $expected = $this->buildTradeSha($tradeInfo);
        return hash_equals($expected, strtoupper($tradeSha));
    }

    /**
     * Decrypt TradeInfo (hex-encoded AES-256-CBC) from webhook/return POST body.
     * Returns parsed params array; empty array on any failure.
     *
     * Risk notes:
     * - OPENSSL_ZERO_PADDING disables auto-unpadding (NewebPay padding is manual PKCS7).
     * - Must check openssl_decrypt return for false before proceeding.
     * - PKCS7 pad byte must be in range 1–16; 0 or >16 means corrupt data.
     */
    public function decryptTradeInfo(string $tradeInfo): array
    {
        $decoded = hex2bin($tradeInfo);
        if ($decoded === false) {
            Log::warning('NewebPay: hex2bin failed on TradeInfo');
            return [];
        }

        $decrypted = openssl_decrypt(
            $decoded,
            'AES-256-CBC',
            $this->hashKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->hashIV
        );

        if ($decrypted === false) {
            Log::warning('NewebPay: openssl_decrypt failed', [
                'openssl_error' => openssl_error_string(),
            ]);
            return [];
        }

        $decrypted = $this->pkcs7Unpad($decrypted);
        if ($decrypted === null) {
            Log::warning('NewebPay: PKCS7 unpadding failed — invalid pad byte');
            return [];
        }

        $result = [];
        parse_str($decrypted, $result);
        return $result;
    }

    private function encryptTradeInfo(array $params): string
    {
        $encrypted = openssl_encrypt(
            http_build_query($params),
            'AES-256-CBC',
            $this->hashKey,
            OPENSSL_RAW_DATA,
            $this->hashIV
        );
        return bin2hex($encrypted);
    }

    private function buildTradeSha(string $tradeInfo): string
    {
        return strtoupper(hash('sha256', "HashKey={$this->hashKey}&{$tradeInfo}&HashIV={$this->hashIV}"));
    }

    /**
     * Strip PKCS7 padding after CBC decrypt with OPENSSL_ZERO_PADDING.
     * Returns null if the pad byte is invalid (0 or > block size 16).
     */
    private function pkcs7Unpad(string $data): ?string
    {
        $len = strlen($data);
        if ($len === 0) {
            return null;
        }
        $pad = ord($data[$len - 1]);
        if ($pad < 1 || $pad > 16) {
            return null;
        }
        return substr($data, 0, $len - $pad);
    }
}

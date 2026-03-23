<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PayuniService
{
    private string $merKey;
    private string $merIV;
    private string $apiUrl;

    public function __construct()
    {
        $this->merKey = config('services.payuni.hash_key', '');
        $this->merIV  = config('services.payuni.hash_iv', '');
        $sandbox      = config('services.payuni.sandbox', false);
        $prefix       = $sandbox ? 'https://sandbox-' : 'https://';
        $this->apiUrl = $prefix . 'api.payuni.com.tw/api/upp';
    }

    /**
     * Generate a unique MerTradeNo encoding the courseId for later parsing.
     * Format: YC{courseId:04d}{YmdHis}{rand4} (26 chars, PayUni max = 28)
     */
    public function generateMerTradeNo(int $courseId): string
    {
        return sprintf('YC%04d%s%04d', $courseId, date('YmdHis'), rand(1000, 9999));
    }

    /**
     * Parse courseId from MerTradeNo.
     */
    public function parseCourseId(string $merTradeNo): ?int
    {
        // New format: YC{4-digit courseId}{14-digit datetime}{4-digit rand}
        if (preg_match('/^YC(\d{4})/', $merTradeNo, $matches)) {
            return (int) $matches[1];
        }
        // Legacy format: YUE-C{courseId}-...
        if (preg_match('/^YUE-C(\d+)-/', $merTradeNo, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Build PayUni UPP form fields for frontend auto-submit.
     * Amount uses display_price to respect active promotions.
     *
     * @return array{ endpoint: string, fields: array }
     */
    public function buildPaymentForm(Course $course, string $email, string $merTradeNo, ?string $name = null, ?string $phone = null): array
    {
        $params = [
            'MerID'       => config('services.payuni.merchant_id'),
            'MerTradeNo'  => $merTradeNo,
            'TradeAmt'    => (int) $course->display_price,
            'ProdDesc'    => mb_substr($course->name, 0, 50),
            'UsrMail'     => $email,
            'ReturnURL'   => url('/api/payment/payuni/return'),
            'NotifyURL'   => url('/api/webhooks/payuni'),
            'Timestamp'   => time(),
            'Lang'        => 'zh-tw',
        ];

        if ($name) {
            $params['UsrName'] = $name;
        }
        if ($phone) {
            $params['UsrMobile'] = $phone;
        }

        $encryptInfo = $this->encrypt($params);
        $hashInfo    = $this->hashInfo($encryptInfo);

        return [
            'endpoint' => $this->apiUrl,
            'fields'   => [
                'MerID'       => config('services.payuni.merchant_id'),
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

        // Idempotency check
        if (Purchase::where('payuni_trade_no', $merTradeNo)->exists()) {
            Log::info('PayUni Notify: duplicate, skipping', ['MerTradeNo' => $merTradeNo]);
            return ['success' => true, 'error' => ''];
        }

        // Parse courseId from MerTradeNo
        $courseId = $this->parseCourseId($merTradeNo);
        $course   = $courseId ? Course::find($courseId) : null;

        if (!$course) {
            Log::error('PayUni Notify: course not found', ['MerTradeNo' => $merTradeNo, 'courseId' => $courseId]);
            return ['success' => false, 'error' => 'course_not_found'];
        }

        // PayUni may return email as 'Email' or 'UsrMail' depending on UPP version
        $email = $data['Email'] ?? $data['UsrMail'] ?? null;
        $name  = $data['UsrName'] ?? $data['Name'] ?? null;
        $phone = $data['UsrMobile'] ?? $data['Mobile'] ?? null;

        if (!$email) {
            Log::error('PayUni Notify: missing email', ['MerTradeNo' => $merTradeNo]);
            return ['success' => false, 'error' => 'missing_email'];
        }

        try {
            $user = $this->getOrCreateUser($email, $name, $phone);

            $purchase = Purchase::create([
                'user_id'            => $user->id,
                'course_id'          => $course->id,
                'payuni_trade_no'    => $merTradeNo,
                'buyer_email'        => $email,
                'amount'             => $data['TradeAmt'] ?? 0,
                'currency'           => 'TWD',
                'status'             => 'paid',
                'type'               => 'paid',
                'source'             => 'payuni',
                'webhook_received_at' => now(),
            ]);

            Log::info('PayUni Notify: purchase created', [
                'purchase_id' => $purchase->id,
                'user_id'     => $user->id,
                'course_id'   => $course->id,
                'MerTradeNo'  => $merTradeNo,
            ]);

            // Auto-subscribe for drip courses
            if ($course->course_type === 'drip') {
                $dripService = app(\App\Services\DripService::class);
                $dripService->subscribe($user, $course);
            }

        } catch (\Exception $e) {
            Log::error('PayUni Notify: failed to create purchase', [
                'error'      => $e->getMessage(),
                'MerTradeNo' => $merTradeNo,
            ]);
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

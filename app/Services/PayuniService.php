<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Payuni\Sdk\PayuniApi;

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
     * Format: YUE-C{courseId:06d}-{YmdHis}-{rand4}
     */
    public function generateMerTradeNo(int $courseId): string
    {
        return sprintf('YUE-C%06d-%s-%04d', $courseId, date('YmdHis'), rand(1000, 9999));
    }

    /**
     * Parse courseId from MerTradeNo.
     */
    public function parseCourseId(string $merTradeNo): ?int
    {
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
    public function buildPaymentForm(Course $course, string $email, string $merTradeNo): array
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
     * Returns '1|OK' on success (required by PayUni), logs errors.
     */
    public function processNotify(Request $request): string
    {
        $encryptInfo = $request->input('EncryptInfo', '');
        $hashInfo    = $request->input('HashInfo', '');

        $data = $this->verifyAndDecrypt($encryptInfo, $hashInfo);

        if (!$data) {
            Log::warning('PayUni Notify: signature verification failed');
            return '1|FAIL';
        }

        Log::info('PayUni Notify: received', [
            'Status'       => $data['Status'] ?? null,
            'TradeStatus'  => $data['TradeStatus'] ?? null,
            'MerTradeNo'   => $data['MerTradeNo'] ?? null,
        ]);

        // Only process successful payments
        if (($data['Status'] ?? '') !== 'SUCCESS' || ($data['TradeStatus'] ?? '') != '1') {
            Log::info('PayUni Notify: non-success status, skipping', ['data' => $data]);
            return '1|OK';
        }

        $merTradeNo = $data['MerTradeNo'] ?? null;
        if (!$merTradeNo) {
            Log::error('PayUni Notify: missing MerTradeNo');
            return '1|OK';
        }

        // Idempotency check
        if (Purchase::where('payuni_trade_no', $merTradeNo)->exists()) {
            Log::info('PayUni Notify: duplicate, skipping', ['MerTradeNo' => $merTradeNo]);
            return '1|OK';
        }

        // Parse courseId from MerTradeNo
        $courseId = $this->parseCourseId($merTradeNo);
        $course   = $courseId ? Course::find($courseId) : null;

        if (!$course) {
            Log::error('PayUni Notify: course not found', ['MerTradeNo' => $merTradeNo, 'courseId' => $courseId]);
            return '1|OK';
        }

        $email = $data['UsrMail'] ?? null;
        $name  = $data['UsrName'] ?? null;
        $phone = $data['UsrMobile'] ?? null;

        if (!$email) {
            Log::error('PayUni Notify: missing email', ['MerTradeNo' => $merTradeNo]);
            return '1|OK';
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
                $dripService = app(DripService::class);
                $dripService->subscribe($user, $course);
            }

            app(DripService::class)->checkAndConvert($user, $course);

        } catch (\Exception $e) {
            Log::error('PayUni Notify: failed to create purchase', [
                'error'      => $e->getMessage(),
                'MerTradeNo' => $merTradeNo,
            ]);
        }

        return '1|OK';
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

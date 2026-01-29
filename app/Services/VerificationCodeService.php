<?php

namespace App\Services;

use App\Models\VerificationCode;
use Illuminate\Support\Facades\Log;

class VerificationCodeService
{
    private const CODE_LENGTH = 6;
    private const EXPIRATION_MINUTES = 10;
    private const RATE_LIMIT_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function generate(string $email): array
    {
        // Check if locked or rate limited
        $lastCode = VerificationCode::where('email', $email)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastCode) {
            // Check lockout first (security: prevent bypassing lockout by requesting new code)
            if ($lastCode->isLocked()) {
                $waitMinutes = now()->diffInMinutes($lastCode->locked_until) + 1;
                return [
                    'success' => false,
                    'error' => "嘗試次數過多，請 {$waitMinutes} 分鐘後再試",
                    'locked' => true,
                ];
            }

            // Skip rate limit check if code has expired (allow sending new code)
            if (!$lastCode->isExpired()) {
                // Check rate limit
                $secondsSinceLastCode = now()->diffInSeconds($lastCode->created_at, true);
                if ($secondsSinceLastCode < self::RATE_LIMIT_SECONDS) {
                    $waitSeconds = self::RATE_LIMIT_SECONDS - $secondsSinceLastCode;
                    return [
                        'success' => false,
                        'error' => "請等待 {$waitSeconds} 秒後再發送驗證碼",
                        'wait_seconds' => $waitSeconds,
                    ];
                }
            }
        }

        // Generate new code
        $code = $this->generateCode();

        VerificationCode::create([
            'email' => $email,
            'code' => $code,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRATION_MINUTES),
            'created_at' => now(),
        ]);

        Log::info("Verification code generated for {$email}: {$code}");

        return [
            'success' => true,
            'code' => $code,
        ];
    }

    public function validate(string $email, string $code): array
    {
        $verification = VerificationCode::where('email', $email)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verification) {
            return [
                'success' => false,
                'error' => '驗證碼錯誤，請重新輸入',
            ];
        }

        // Check if locked
        if ($verification->isLocked()) {
            $waitMinutes = now()->diffInMinutes($verification->locked_until) + 1;
            return [
                'success' => false,
                'error' => "嘗試次數過多，請 {$waitMinutes} 分鐘後再試",
                'locked' => true,
            ];
        }

        // Check if expired
        if ($verification->isExpired()) {
            return [
                'success' => false,
                'error' => '驗證碼已過期，請重新發送',
                'expired' => true,
            ];
        }

        // Check code
        if ($verification->code !== $code) {
            $verification->attempts++;

            // Lock if max attempts reached
            if ($verification->attempts >= self::MAX_ATTEMPTS) {
                $verification->locked_until = now()->addMinutes(self::LOCKOUT_MINUTES);
                $verification->save();

                return [
                    'success' => false,
                    'error' => '嘗試次數過多，請 ' . self::LOCKOUT_MINUTES . ' 分鐘後再試',
                    'locked' => true,
                ];
            }

            $verification->save();
            $remaining = self::MAX_ATTEMPTS - $verification->attempts;

            return [
                'success' => false,
                'error' => "驗證碼錯誤，請重新輸入（剩餘 {$remaining} 次嘗試）",
            ];
        }

        // Valid - delete used verification codes for this email
        VerificationCode::where('email', $email)->delete();

        return [
            'success' => true,
        ];
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }
}

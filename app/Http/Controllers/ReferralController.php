<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidateReferralRequest;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class ReferralController extends Controller
{
    /** 同一 IP 連續驗證失敗達此次數後節流（防枚舉推薦碼）。 */
    private const MAX_FAILURES = 5;

    /** 節流等待秒數。 */
    private const DECAY_SECONDS = 60;

    public function __construct(private ReferralService $referralService) {}

    /**
     * 結帳推薦碼即時驗證（公開，支援 guest）。
     * 失敗計數節流：同一 IP 連續失敗 5 次 → 60 秒冷卻；成功重置。比照 CouponController。
     */
    public function validateCode(ValidateReferralRequest $request): JsonResponse
    {
        $throttleKey = 'referral-validate:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILURES)) {
            return response()->json(['message' => '嘗試次數過多，請於 60 秒後再試'], 429);
        }

        $result = $this->referralService->validateAtCheckout($request->input('referral_code'), [
            'user_id' => auth()->id(),
            'email'   => $request->input('buyer_email'),
        ]);

        if (! $result['success']) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        RateLimiter::clear($throttleKey);

        return response()->json(['success' => true, 'rate' => $result['rate']]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class CouponController extends Controller
{
    /** 同一 IP 連續驗證失敗達此次數後節流（FR-019）。 */
    private const MAX_FAILURES = 5;

    /** 節流等待秒數（FR-019）。 */
    private const DECAY_SECONDS = 60;

    public function __construct(private CouponService $couponService) {}

    /**
     * 套用折扣碼：server-side 驗證並回傳折扣資訊（公開，支援 guest）。
     * 失敗計數節流：同一 IP 連續失敗 5 次 → 60 秒冷卻；成功重置。
     */
    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'         => ['required', 'string', 'max:6'],
            'course_ids'   => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        $throttleKey = 'coupon-apply:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILURES)) {
            return response()->json(['message' => '嘗試次數過多，請於 60 秒後再試'], 429);
        }

        // 伺服器依 course_ids 重算小計（整數元），不信任前端傳入金額。
        $subtotal = (int) round(
            Course::whereIn('id', $data['course_ids'])->get()->sum(fn ($c) => $c->display_price)
        );

        $result = $this->couponService->validateForCart($data['code'], $data['course_ids'], $subtotal);

        if (!$result['success']) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }

        RateLimiter::clear($throttleKey);

        return response()->json([
            'success'  => true,
            'code'     => $result['code'],
            'type'     => $result['type'],
            'label'    => $result['label'],
            'discount' => $result['discount'],
            'original' => $result['original'],
            'payable'  => $result['payable'],
        ]);
    }

    /**
     * 清除 session 中的自動帶入折扣碼（US5：用戶移除自動帶入碼時呼叫）。
     */
    public function clear(Request $request): JsonResponse
    {
        $request->session()->forget('checkout_coupon');

        return response()->json(['success' => true]);
    }
}

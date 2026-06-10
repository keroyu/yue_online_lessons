<?php

namespace App\Services;

use App\Models\CouponCode;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class CouponService
{
    /** 折後實付金額下限（金流不接受低於 NT$1 的交易）。 */
    private const MIN_PAYABLE = 1;

    /**
     * 套用前驗證折扣碼（前台 apply-coupon / 結帳前重驗共用）。
     * 涵蓋：存在 + 啟用、未過期、未達上限、課程適用性、折後 ≥ NT$1。
     *
     * @param  string  $code       使用者輸入的代碼（不分大小寫）
     * @param  int[]   $courseIds  購物車中的 course_id 陣列
     * @param  int     $subtotal   折扣前小計（整數元）
     * @return array{success: bool, error?: string, coupon?: CouponCode,
     *               discount?: int, payable?: int, original?: int, label?: string,
     *               code?: string, type?: string}
     */
    public function validateForCart(string $code, array $courseIds, int $subtotal): array
    {
        $code = strtoupper(trim($code));

        // 不套用 active() scope，以便區分「無效 / 過期 / 達上限」的錯誤訊息。
        $coupon = CouponCode::where('code', $code)->first();

        if (!$coupon || !$coupon->is_active) {
            return ['success' => false, 'error' => '折扣碼無效'];
        }

        if ($coupon->isExpired()) {
            return ['success' => false, 'error' => '折扣碼已過期'];
        }

        if ($coupon->isExhausted()) {
            return ['success' => false, 'error' => '折扣碼已達使用上限'];
        }

        if (!$coupon->isSiteWide() && !in_array($coupon->course_id, $courseIds, true)) {
            return ['success' => false, 'error' => '此折扣碼不適用於購物車中的課程'];
        }

        $discount = $this->calculateDiscount($coupon, $subtotal);
        $payable  = $subtotal - $discount;

        if ($payable < self::MIN_PAYABLE) {
            return ['success' => false, 'error' => '折扣金額超過訂單上限，無法套用'];
        }

        return [
            'success'  => true,
            'coupon'   => $coupon,
            'code'     => $coupon->code,
            'type'     => $coupon->type,
            'label'    => $this->label($coupon),
            'discount' => $discount,
            'original' => $subtotal,
            'payable'  => $payable,
        ];
    }

    /**
     * 計算折抵金額（整數元）。不做下限判定（由 validateForCart 統一處理）。
     * fixed：min(value, subtotal)；ratio：subtotal - round(subtotal * value)。
     */
    public function calculateDiscount(CouponCode $coupon, int $subtotal): int
    {
        $subtotal = (int) round($subtotal);

        if ($coupon->type === 'fixed') {
            return min((int) round($coupon->value), $subtotal);
        }

        // ratio：先算折後實付並四捨五入至整數元，再回推折抵金額。
        $payable = (int) round($subtotal * (float) $coupon->value);

        return $subtotal - $payable;
    }

    /**
     * 顯示標籤：fixed → 「折抵 NT$XXX」；ratio → 「X折優惠」（0.6 → 「六折優惠」）。
     */
    public function label(CouponCode $coupon): string
    {
        if ($coupon->type === 'fixed') {
            return '折抵 NT$' . number_format((int) round($coupon->value));
        }

        $digits = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
        $n      = (int) round((float) $coupon->value * 100); // 0.6 → 60, 0.85 → 85
        $tens   = intdiv($n, 10);
        $units  = $n % 10;

        $text = $digits[$tens] . ($units > 0 ? $digits[$units] : '') . '折';

        return $text . '優惠';
    }

    /**
     * 付款確認後兌現：原子 increment used_count（軟限制，不強制檢查上限）。
     * 由 CheckoutService::fulfillOrder() 呼叫。查無代碼則記錄 warning 後略過。
     */
    public function redeem(string $code): void
    {
        $code   = strtoupper(trim($code));
        $coupon = CouponCode::where('code', $code)->first();

        if (!$coupon) {
            Log::warning('CouponService: redeem skipped, coupon not found', ['code' => $code]);
            return;
        }

        $coupon->increment('used_count');
    }

    /**
     * 後台統計：指定折扣碼於最近 $days 天（依 webhook_received_at）的成效。
     * $days = null → 全部期間，不套用時間範圍條件。來源 = orders 表 status='paid'。
     *
     * @return array{count: int, revenue: float, discountTotal: float,
     *               details: array<array{email: string, paid_at: ?string,
     *               total: float, original: ?float}>}
     */
    public function stats(CouponCode $coupon, ?int $days): array
    {
        $query = Order::query()
            ->where('coupon_code', $coupon->code)
            ->where('status', 'paid');

        if ($days !== null) {
            $query->where('webhook_received_at', '>=', now()->subDays($days));
        }

        $orders = $query->orderByDesc('webhook_received_at')->get();

        return [
            'count'         => $orders->count(),
            'revenue'       => (float) $orders->sum('total_amount'),
            'discountTotal' => (float) $orders->sum('discount_amount'),
            'details'       => $orders->map(fn (Order $o) => [
                'email'    => $o->buyer_email,
                'paid_at'  => $o->webhook_received_at?->toIso8601String(),
                'total'    => (float) $o->total_amount,
                'original' => $o->original_amount !== null ? (float) $o->original_amount : null,
            ])->values()->all(),
        ];
    }
}

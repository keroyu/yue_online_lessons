<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Referral code validation, reward payout and activation evaluation.
 *
 * All point mutations go through PointService (single write point for users.points).
 */
class ReferralService
{
    public function __construct(private PointService $pointService) {}

    /**
     * Validate a referral code at checkout, BEFORE the order is created (FR-018).
     * Returns a structured result; the controller maps `error` to a 422 `message`.
     *
     * @param  array{email?: ?string, user_id?: ?int}  $buyer  buyer identity for self-referral check
     * @return array{success: bool, error?: string, referrer?: User, rate?: int}
     */
    public function validateAtCheckout(string $code, array $buyer): array
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return ['success' => false, 'error' => '推薦碼不存在，請再次確認'];
        }

        $referrer = User::where('referral_code', $code)->first();

        if (! $referrer) {
            return ['success' => false, 'error' => '推薦碼不存在，請再次確認'];
        }

        // Self-referral: buyer is the referrer (by logged-in id or by email).
        $buyerId = $buyer['user_id'] ?? null;
        $buyerEmail = isset($buyer['email']) ? strtolower(trim((string) $buyer['email'])) : null;
        if (($buyerId && (int) $buyerId === $referrer->id)
            || ($buyerEmail && $buyerEmail === strtolower($referrer->email))) {
            return ['success' => false, 'error' => '不可使用自己的推薦碼'];
        }

        if (! $referrer->isReferralActive()) {
            return ['success' => false, 'error' => '此推薦碼目前無法使用'];
        }

        return [
            'success'  => true,
            'referrer' => $referrer,
            'rate'     => (int) SiteSetting::get('referral_reward_rate', 10),
        ];
    }

    /**
     * Pay out the referral reward after payment is confirmed (FR-020).
     * Idempotent per order: skips if a reward ledger row already exists for this order.
     *
     * Amount = round(total_amount * rate / 100) rounded to the nearest ten (half-up).
     * Matures after `referral_maturity_days`.
     */
    public function reward(Order $order): void
    {
        if (! $order->referrer_user_id || ! $order->referrer) {
            return;
        }

        $alreadyRewarded = $order->referrer
            ->pointTransactions()
            ->where('type', 'earn_referral')
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->exists();

        if ($alreadyRewarded) {
            return;
        }

        $rate = (int) $order->referral_rate;
        $reward = $this->computeReward((int) round($order->total_amount), $rate);

        if ($reward <= 0) {
            return;
        }

        $maturityDays = (int) SiteSetting::get('referral_maturity_days', 14);

        $this->pointService->award(
            $order->referrer,
            $reward,
            'earn_referral',
            'order',
            $order->id,
            null,
            now()->addDays($maturityDays),
        );

        // Re-snapshot the actual awarded amount on the order (built from paid total).
        $order->update(['referral_reward_points' => $reward]);
    }

    /**
     * Referral performance grouped by referrer (US5). Powers the 積分與推薦 admin page.
     * $days = null → all time. Includes each referrer's current spendable point balance.
     *
     * @return array<int, array{referrer_name: string, referrer_email: ?string, referral_code: ?string,
     *               current_points: int, order_count: int, revenue: int, reward_points: int}>
     */
    public function performanceRows(?int $days): array
    {
        $query = Order::query()
            ->whereNotNull('referrer_user_id')
            ->where('status', 'paid');

        if ($days) {
            $query->where('orders.webhook_received_at', '>=', now()->subDays($days));
        }

        return $query
            ->join('users', 'users.id', '=', 'orders.referrer_user_id')
            ->groupBy('orders.referrer_user_id', 'users.nickname', 'users.email', 'users.referral_code', 'users.points')
            ->select([
                'orders.referrer_user_id',
                'users.nickname as referrer_name',
                'users.email as referrer_email',
                'users.referral_code',
                'users.points as current_points',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(orders.total_amount) as revenue'),
                DB::raw('SUM(orders.referral_reward_points) as reward_points'),
            ])
            ->orderByDesc('reward_points')
            ->get()
            ->map(fn ($r) => [
                'referrer_name'  => $r->referrer_name ?: '（未命名）',
                'referrer_email' => $r->referrer_email,
                'referral_code'  => $r->referral_code,
                'current_points' => (int) $r->current_points,
                'order_count'    => (int) $r->order_count,
                'revenue'        => (int) round($r->revenue),
                'reward_points'  => (int) $r->reward_points,
            ])
            ->all();
    }

    /**
     * Compute reward points: paid amount × rate%, rounded to the nearest ten (half-up).
     */
    public function computeReward(int $paidAmount, int $rate): int
    {
        $raw = $paidAmount * $rate / 100;

        return (int) (round($raw / 10, 0, PHP_ROUND_HALF_UP) * 10);
    }

    /**
     * Light up the buyer's referral eligibility once their lifetime paid purchases
     * cross the threshold (FR-016). One-way flag — never cleared (FR-025).
     */
    public function evaluateActivation(User $user): void
    {
        if ($user->isReferralActive()) {
            return;
        }

        $threshold = (int) SiteSetting::get('referral_threshold_amount', 3000);

        $paid = (int) Purchase::where('user_id', $user->id)
            ->where('type', 'paid')
            ->sum('amount');

        if ($paid >= $threshold) {
            $user->update(['referral_activated_at' => now()]);
        }
    }
}

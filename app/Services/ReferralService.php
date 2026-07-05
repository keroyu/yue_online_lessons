<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Models\User;

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

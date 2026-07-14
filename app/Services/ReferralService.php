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
            'discount' => max(0, (int) SiteSetting::get('referral_discount_amount', 150)),
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
                'referrer_user_id' => (int) $r->referrer_user_id, // powers the US8 detail drill-down
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
     * Read-only detail bundle for a referrer, shown in the 推薦成效 drill-down modal (US8):
     * their own point ledger + their own transactions + the orders they referred.
     * Each list is capped at the 50 most recent rows (matches the member ledger modal; not exhaustive).
     *
     * @return array{referrer: array, point_transactions: array, own_transactions: array, referred_orders: array}
     */
    public function referrerDetail(User $user): array
    {
        // (1) The referrer's own point ledger (same shape as MemberController@show).
        $pointTransactions = $user->pointTransactions()
            ->limit(50)
            ->get()
            ->map(fn ($tx) => [
                'created_at'   => $tx->created_at->toIso8601String(),
                'type'         => $tx->type,
                'amount'       => $tx->amount,
                'note'         => $tx->note,
                'available_at' => $tx->available_at->toIso8601String(),
                'is_matured'   => $tx->available_at->lessThanOrEqualTo(now()),
            ])
            ->all();

        // (2) The referrer's own transactions (what they bought).
        $ownTransactions = Purchase::with(['course:id,name', 'order:id,merchant_order_no'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($p) => [
                'id'                => $p->id,
                'course_name'       => $p->course?->name,
                'amount'            => (int) round($p->amount),
                'discount_amount'   => (int) round($p->discount_amount),
                'status'            => $p->status,
                'type'              => $p->type,
                'type_label'        => $p->type_label,
                'merchant_order_no' => $p->order?->merchant_order_no,
                'created_at'        => $p->created_at->toIso8601String(),
            ])
            ->all();

        // (3) The orders this referrer brought in (drill-down of the 推薦成效 aggregate).
        $referredOrders = Order::where('referrer_user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($o) => [
                'id'                     => $o->id,
                'merchant_order_no'      => $o->merchant_order_no,
                'buyer_email'            => $o->buyer_email,
                'total_amount'           => (int) round($o->total_amount),
                'status'                 => $o->status,
                'referral_reward_points' => (int) $o->referral_reward_points,
                'referral_rate'          => $o->referral_rate !== null ? (int) $o->referral_rate : null,
                'created_at'             => $o->created_at->toIso8601String(),
                'webhook_received_at'    => $o->webhook_received_at?->toIso8601String(),
            ])
            ->all();

        return [
            'referrer' => [
                'id'             => $user->id,
                'name'           => $user->nickname ?: $user->real_name ?: '（未命名）',
                'email'          => $user->email,
                'referral_code'  => $user->referral_code,
                'current_points' => (int) $user->points,
            ],
            'point_transactions' => $pointTransactions,
            'own_transactions'   => $ownTransactions,
            'referred_orders'    => $referredOrders,
        ];
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

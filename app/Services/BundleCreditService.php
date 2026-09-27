<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bundle credits: the perk (e.g. group consultations) sold alongside a
 * high-ticket course (011 US37).
 *
 * Everything lives on existing tables (D141): the perk's name and per-credit
 * price are `courses` columns, the granted quantity is a `course_plans` column,
 * and the balance is a pair of `purchases` columns — that table is already one
 * row per (user, course), which is exactly the grain a balance needs.
 *
 * There is deliberately NO ledger (D141, user decision), so every write here is
 * the only record of itself. That is why deductions are guarded UPDATEs rather
 * than read-modify-write: a wrong balance has nothing to be reconciled against.
 */
class BundleCreditService
{
    public const DISPLAY_TZ = 'Asia/Taipei';

    public function __construct(private PointService $pointService)
    {
    }

    /**
     * How many credits this course/plan combination is supposed to have granted.
     *
     * The course-level default is the no-plans fallback only (FR-202): on a
     * course that does have plans, a purchase with no plan is a legacy
     * full-access record, and inventing a quantity for it would hand credits to
     * people who were never sold any.
     */
    public function defaultQuantityFor(Course $course, ?CoursePlan $plan): int
    {
        if (! $course->has_bundle) {
            return 0;
        }

        if ($plan) {
            return (int) $plan->bundle_quantity;
        }

        return $course->plans()->exists() ? 0 : (int) $course->bundle_default_quantity;
    }

    /**
     * Top the purchase up to what its plan owes it (FR-203).
     *
     * One rule answers both cases: re-granting the same plan is a no-op
     * (delta 0), and an upgrade grants only the difference. `bundle_granted`
     * makes it idempotent, and because only this method moves it, credits the
     * member already spent are never handed back.
     *
     * Callers MUST already be inside their own transaction — a sale that
     * commits without its credits is a debt nobody will notice.
     *
     * @return int credits actually granted
     */
    public function syncPlanGrant(Purchase $purchase): int
    {
        $course = $purchase->course;

        if (! $course || ! $course->has_bundle) {
            return 0;
        }

        $target = $this->defaultQuantityFor($course, $purchase->plan);
        $delta = max(0, $target - (int) $purchase->bundle_granted);

        if ($delta === 0) {
            return 0;
        }

        Purchase::whereKey($purchase->id)->update([
            'bundle_balance' => DB::raw('bundle_balance + ' . $delta),
            'bundle_granted' => DB::raw('bundle_granted + ' . $delta),
        ]);

        return $delta;
    }

    /**
     * Spend one credit each for the given members (FR-211).
     *
     * The caller sends user ids off a list the browser is holding, so the owning
     * purchase and its balance are re-read here: this endpoint changes other
     * people's credits (D144). Members with too few credits are skipped and
     * reported rather than failing the batch — the admin has already sent the
     * invitations by the time they press this.
     *
     * @param  array<int>  $userIds
     * @return array{consumed: int, skipped: array<string>}
     */
    public function consume(Course $course, array $userIds, int $quantity = 1): array
    {
        if ($userIds === [] || $quantity < 1) {
            return ['consumed' => 0, 'skipped' => []];
        }

        $purchases = Purchase::where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->paidStatus()
            ->with('user:id,email')
            ->get();

        $consumed = 0;
        $skipped = [];

        foreach ($purchases as $purchase) {
            // Guarded UPDATE, not decrement-after-read: the balance can never
            // go below zero even if this list is stale (FR-205).
            $affected = Purchase::whereKey($purchase->id)
                ->where('bundle_balance', '>=', $quantity)
                ->decrement('bundle_balance', $quantity);

            if ($affected > 0) {
                $consumed++;
            } else {
                $skipped[] = $purchase->user?->email ?? $purchase->buyer_email;
            }
        }

        Log::info('Bundle credits consumed', [
            'course_id' => $course->id,
            'requested' => count($userIds),
            'consumed' => $consumed,
            'skipped' => count($skipped),
        ]);

        return ['consumed' => $consumed, 'skipped' => $skipped];
    }

    /**
     * Buy one more credit with points (FR-206).
     *
     * One call = one credit: `bundle_redeem_points` is the per-credit price
     * (D146). `bundle_granted` is untouched — it answers "what the plan owed",
     * and a top-up was never owed.
     *
     * @return array{success: bool, error?: string, status?: int, balance?: int, points?: int}
     */
    public function redeemWithPoints(Purchase $purchase): array
    {
        $course = $purchase->course;
        $cost = (int) ($course?->bundle_redeem_points ?? 0);

        if (! $course || ! $course->has_bundle || $cost <= 0) {
            return ['success' => false, 'status' => 422, 'error' => '此課程的福利無法以積分加購'];
        }

        $user = $purchase->user;

        if (! $user) {
            return ['success' => false, 'status' => 422, 'error' => '找不到會員資料'];
        }

        try {
            DB::transaction(function () use ($user, $cost, $purchase) {
                // Atomic deduct — throws on insufficient balance, rolling back
                // the credit along with the points (FR-206).
                $this->pointService->redeemDeduct($user, $cost, 'purchase', $purchase->id, 'redeem_bundle');

                Purchase::whereKey($purchase->id)->increment('bundle_balance');
            });
        } catch (\RuntimeException $e) {
            return ['success' => false, 'error' => '可用積分不足'];
        }

        Log::info('Bundle credit redeemed with points', [
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'cost' => $cost,
        ]);

        return [
            'success' => true,
            'balance' => (int) $purchase->fresh()->bundle_balance,
            'points' => (int) $user->fresh()->points,
        ];
    }

    /**
     * Students holding this course, for the admin roster modal (FR-208/FR-209).
     *
     * A single-table query with two eager loads: plan, balance and join date are
     * all columns of the same purchase row, which is the direct payoff of
     * keeping the balance here (D141).
     *
     * @param  string|null  $planId  'all' (default), a plan id, or 'none'
     * @return array<int, array<string, mixed>>
     */
    public function roster(Course $course, ?string $planId = 'all', bool $withCredit = false): array
    {
        $query = Purchase::where('course_id', $course->id)
            ->paidStatus()
            ->with(['user:id,nickname,real_name,email', 'plan:id,name']);

        if ($planId === 'none') {
            $query->whereNull('course_plan_id');
        } elseif (filled($planId) && $planId !== 'all') {
            $query->where('course_plan_id', (int) $planId);
        }

        if ($withCredit) {
            $query->where('bundle_balance', '>', 0);
        }

        return $query->orderBy('created_at')
            ->get()
            ->map(fn (Purchase $purchase) => [
                'user_id' => $purchase->user_id,
                'name' => $purchase->user?->nickname ?: ($purchase->user?->real_name ?: '—'),
                'email' => $purchase->user?->email ?? $purchase->buyer_email,
                // Reader is always in Taipei; the column is UTC.
                'joined_at' => $purchase->created_at?->timezone(self::DISPLAY_TZ)->format('Y-m-d H:i'),
                'plan_name' => $purchase->plan?->name,
                'bundle_balance' => (int) $purchase->bundle_balance,
            ])
            ->values()
            ->all();
    }
}

<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Consultation credits sold alongside a course: group sessions an admin
 * deducts on high-ticket courses (011 US37), 1-on-1 sessions the customer
 * self-books on ordinary ones (011 US38).
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

    public const INELIGIBLE_MESSAGE = '只有付費的一般課程（非序列信）或客製服務可以設定諮詢次數';

    public function __construct(private PointService $pointService)
    {
    }

    /**
     * Which courses may carry consultation credits at all (011 FR-222).
     *
     * High-ticket courses spend them through the admin roster (US37). Ordinary
     * courses spend them by self-booking (US38), which only makes sense for
     * something that was paid for: a free claim never grants, and a drip course
     * is a mail sequence with no sales page to book from.
     */
    public static function canCarryBundle(?string $type, ?string $courseType, mixed $price): bool
    {
        if ($type === 'high_ticket') {
            return true;
        }

        return $courseType !== 'drip' && (float) $price > 0;
    }

    /**
     * The bundle fields in a course form submission that actually set
     * something (011 FR-222).
     *
     * The course form always posts every bundle field, and FormData turns the
     * untouched defaults into "0" — so "is it filled" would flag every save of
     * an ineligible course, with the error landing on a field that is not even
     * rendered. A zero quantity and an unticked unlimited box set nothing.
     *
     * @return array<int, string>
     */
    public static function settingKeys(array $input): array
    {
        return array_keys(array_filter([
            'bundle_name'             => filled($input['bundle_name'] ?? null),
            'bundle_redeem_points'    => filled($input['bundle_redeem_points'] ?? null),
            'bundle_default_quantity' => (int) ($input['bundle_default_quantity'] ?? 0) > 0,
            'bundle_unlimited'        => filter_var($input['bundle_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]));
    }

    /**
     * Whether credits on this course are spent by the customer booking a slot
     * rather than by an admin deducting from the roster (011 FR-223 / D151).
     *
     * Derived from the course type rather than stored: a flag would admit two
     * combinations nobody wants (self-booked group sessions, admin-deducted
     * 1-on-1s).
     */
    public function isSelfBooking(Course $course): bool
    {
        return $course->has_bundle && $course->type !== 'high_ticket';
    }

    /**
     * Grant credits for a storefront sale — checkout, Portaly, or redeeming the
     * whole course with points (011 FR-224).
     *
     * High-ticket courses still grant nothing here: their credits follow the
     * plan chosen at conversion, and storefront paths have no plan (US37).
     * Callers MUST be inside the transaction that created the purchase.
     *
     * @return int credits actually granted
     */
    public function grantOnStorefrontSale(Purchase $purchase): int
    {
        $course = $purchase->course;

        if (! $course || ! $this->isSelfBooking($course)) {
            return 0;
        }

        return $this->syncPlanGrant($purchase);
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
     * Whether this course/plan combination grants the perk without counting
     * (011 FR-213).
     *
     * Read from the tier every time, never snapshotted onto the purchase: a
     * quantity is a batch already handed over, but "unlimited" is a standing
     * entitlement, so flipping a tier covers everyone already holding it
     * without reconversion (D148).
     *
     * The course-level flag is the no-plans fallback only, exactly like
     * bundle_default_quantity.
     */
    public function isUnlimitedFor(Course $course, ?CoursePlan $plan): bool
    {
        if (! $course->has_bundle) {
            return false;
        }

        if ($plan) {
            return (bool) $plan->bundle_unlimited;
        }

        return $course->plans()->exists() ? false : (bool) $course->bundle_unlimited;
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

        // Unlimited tiers have no number to reconcile, so both columns stay at
        // zero — and because the flag is not snapshotted, switching back to a
        // limited tier later grants that tier in full (FR-214).
        if ($this->isUnlimitedFor($course, $purchase->plan)) {
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
     * Unlimited members are reported separately rather than silently passed
     * over: there is nothing to spend, but the admin pressing this must be told
     * so, or the result reads as "deducted" (FR-215).
     *
     * @param  array<int>  $userIds
     * @return array{consumed: int, skipped: array<string>, unlimited: array<string>}
     */
    public function consume(Course $course, array $userIds, int $quantity = 1): array
    {
        if ($userIds === [] || $quantity < 1) {
            return ['consumed' => 0, 'skipped' => [], 'unlimited' => []];
        }

        $purchases = Purchase::where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->paidStatus()
            ->with(['user:id,email', 'plan'])
            ->get();

        $consumed = 0;
        $skipped = [];
        $unlimited = [];

        foreach ($purchases as $purchase) {
            $email = $purchase->user?->email ?? $purchase->buyer_email;

            if ($this->isUnlimitedFor($course, $purchase->plan)) {
                $unlimited[] = $email;

                continue;
            }

            // Guarded UPDATE, not decrement-after-read: the balance can never
            // go below zero even if this list is stale (FR-205).
            $affected = Purchase::whereKey($purchase->id)
                ->where('bundle_balance', '>=', $quantity)
                ->decrement('bundle_balance', $quantity);

            if ($affected > 0) {
                $consumed++;
            } else {
                $skipped[] = $email;
            }
        }

        Log::info('Bundle credits consumed', [
            'course_id' => $course->id,
            'requested' => count($userIds),
            'consumed' => $consumed,
            'skipped' => count($skipped),
            'unlimited' => count($unlimited),
        ]);

        return ['consumed' => $consumed, 'skipped' => $skipped, 'unlimited' => $unlimited];
    }

    /**
     * Save each tier's bundle settings, posted with the course form (FR-202).
     *
     * Scoped to this course's own plans: the ids come from a form, and a plan id
     * from another course must not be writable through it.
     *
     * @param  array<int|string, array{quantity?: mixed, unlimited?: mixed}>  $rows
     */
    public function syncPlanSettings(Course $course, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $ownPlanIds = $course->plans()->pluck('id')->all();

        foreach ($rows as $planId => $row) {
            if (! in_array((int) $planId, $ownPlanIds, true)) {
                continue;
            }

            CoursePlan::whereKey((int) $planId)->update([
                'bundle_quantity' => max(0, (int) ($row['quantity'] ?? 0)),
                'bundle_unlimited' => (bool) ($row['unlimited'] ?? false),
            ]);
        }
    }

    /**
     * Backfill credits for members who already hold the course (FR-219).
     *
     * The case this exists for: a course that had no perk gets one, and every
     * existing student sits at zero because grants only run at a sale. Automatic
     * backfilling was rejected — it would fire while the admin is still typing
     * numbers, and there is no ledger to unwind it with (D149). So this is a
     * button, pressed when the admin means it.
     *
     * Safe to press twice: syncPlanGrant() tops up to what the plan owes, so
     * anyone already at that number (and anyone unlimited) comes back unchanged.
     *
     * @param  array<int>  $userIds
     * @return array{granted: int, unchanged: int}
     */
    public function grantToMembers(Course $course, array $userIds): array
    {
        if ($userIds === [] || ! $course->has_bundle) {
            return ['granted' => 0, 'unchanged' => 0];
        }

        $purchases = Purchase::where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->paidStatus()
            ->with('plan')
            ->get();

        $granted = 0;
        $unchanged = 0;

        foreach ($purchases as $purchase) {
            $this->syncPlanGrant($purchase) > 0 ? $granted++ : $unchanged++;
        }

        Log::info('Bundle credits backfilled', [
            'course_id' => $course->id,
            'requested' => count($userIds),
            'granted' => $granted,
            'unchanged' => $unchanged,
        ]);

        return ['granted' => $granted, 'unchanged' => $unchanged];
    }

    /**
     * Hand one credit back (or give one out) by hand (FR-221).
     *
     * The counterpart of consume(), and the only way to undo a mis-click: there
     * is no ledger to roll back (D141), and grantToMembers() cannot help because
     * it tops up to what the plan owes — someone who already received that is
     * "unchanged" no matter how many credits they just lost.
     *
     * `bundle_granted` is deliberately NOT moved: it answers "what the plan
     * owed", and a manual adjustment was never owed. Moving it would also shrink
     * a later backfill (FR-203 tops up to the difference).
     *
     * @param  array<int>  $userIds
     * @return array{credited: int, unlimited: array<string>}
     */
    public function credit(Course $course, array $userIds, int $quantity = 1): array
    {
        if ($userIds === [] || $quantity < 1 || ! $course->has_bundle) {
            return ['credited' => 0, 'unlimited' => []];
        }

        $purchases = Purchase::where('course_id', $course->id)
            ->whereIn('user_id', $userIds)
            ->paidStatus()
            ->with(['user:id,email', 'plan'])
            ->get();

        $credited = 0;
        $unlimited = [];

        foreach ($purchases as $purchase) {
            if ($this->isUnlimitedFor($course, $purchase->plan)) {
                $unlimited[] = $purchase->user?->email ?? $purchase->buyer_email;

                continue;
            }

            Purchase::whereKey($purchase->id)->increment('bundle_balance', $quantity);
            $credited++;
        }

        Log::info('Bundle credits added by hand', [
            'course_id' => $course->id,
            'requested' => count($userIds),
            'credited' => $credited,
            'unlimited' => count($unlimited),
        ]);

        return ['credited' => $credited, 'unlimited' => $unlimited];
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
            return ['success' => false, 'status' => 422, 'error' => '此課程的諮詢無法以積分加購'];
        }

        // Buying more of an uncapped perk is meaningless; the button is hidden,
        // and this is the guard behind it (FR-217).
        if ($this->isUnlimitedFor($course, $purchase->plan)) {
            return ['success' => false, 'status' => 422, 'error' => '此方案的諮詢為無限次，無需加購'];
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
            // bundle_unlimited must be in the select: isUnlimitedFor() reads it
            // off this relation, and a trimmed column would silently be false.
            ->with(['user:id,nickname,real_name,email', 'plan:id,name,bundle_unlimited']);

        if ($planId === 'none') {
            $query->whereNull('course_plan_id');
        } elseif (filled($planId) && $planId !== 'all') {
            $query->where('course_plan_id', (int) $planId);
        }

        if ($withCredit) {
            // Unlimited members always qualify. Resolved as a small id lookup
            // rather than a join, so the roster stays a single-table read (D148).
            $unlimitedPlanIds = $course->plans()->where('bundle_unlimited', true)->pluck('id');
            $courseLevelUnlimited = $this->isUnlimitedFor($course, null);

            $query->where(function ($inner) use ($unlimitedPlanIds, $courseLevelUnlimited) {
                $inner->where('bundle_balance', '>', 0);

                if ($unlimitedPlanIds->isNotEmpty()) {
                    $inner->orWhereIn('course_plan_id', $unlimitedPlanIds);
                }

                if ($courseLevelUnlimited) {
                    $inner->orWhereNull('course_plan_id');
                }
            });
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
                'unlimited' => $this->isUnlimitedFor($course, $purchase->plan),
            ])
            ->values()
            ->all();
    }
}

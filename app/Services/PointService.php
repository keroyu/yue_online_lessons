<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for all point mutations.
 *
 * `users.points` is a denormalised cache of the user's MATURED available balance.
 * Every write goes through this service inside a DB transaction; no other code may
 * touch `users.points` directly (constitution III / FR-003).
 */
class PointService
{
    /**
     * Write a ledger entry.
     *
     * Instant-mature entries (available_at <= now) update the users.points cache
     * synchronously. Delayed entries (referral reward) do NOT touch the cache; they
     * are folded in later by syncMatured()/matureDue().
     */
    public function award(
        User $user,
        int $amount,
        string $type,
        ?string $refType = null,
        ?int $refId = null,
        ?string $note = null,
        ?Carbon $availableAt = null
    ): PointTransaction {
        $availableAt = $availableAt ?? now();

        return DB::transaction(function () use ($user, $amount, $type, $refType, $refId, $note, $availableAt) {
            $isMatured = $availableAt->lessThanOrEqualTo(now());

            $tx = PointTransaction::create([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'type'           => $type,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'note'           => $note,
                'available_at'   => $availableAt,
                'matured_synced' => $isMatured,
            ]);

            if ($isMatured && $amount !== 0) {
                User::whereKey($user->id)->increment('points', $amount);
                $user->refresh();
            }

            return $tx;
        });
    }

    /**
     * Deduct points for a redemption. Atomic + guarded — the conditional UPDATE
     * guarantees the balance can never go negative under concurrency.
     * Throws RuntimeException on insufficient balance (caller catches to rollback).
     */
    public function redeemDeduct(User $user, int $cost, string $refType, int $refId): PointTransaction
    {
        return DB::transaction(function () use ($user, $cost, $refType, $refId) {
            // Ensure just-matured referral points are spendable (F1: matured == available).
            $this->foldMatured($user);

            $affected = User::whereKey($user->id)
                ->where('points', '>=', $cost)
                ->update(['points' => DB::raw('points - ' . (int) $cost)]);

            if ($affected === 0) {
                throw new \RuntimeException('可用積分不足');
            }

            $tx = PointTransaction::create([
                'user_id'        => $user->id,
                'amount'         => -$cost,
                'type'           => 'redeem_course',
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'available_at'   => now(),
                'matured_synced' => true,
            ]);

            $user->refresh();

            return $tx;
        });
    }

    /**
     * Return the user's available (matured) balance, folding in any just-matured
     * referral entries first so maturity takes effect immediately on read.
     */
    public function availableBalance(User $user): int
    {
        $this->syncMatured($user);

        return (int) $user->points;
    }

    /**
     * Fold this user's now-matured (but not yet synced) entries into the cache.
     * Public wrapper — runs its own transaction.
     */
    public function syncMatured(User $user): void
    {
        DB::transaction(fn () => $this->foldMatured($user));
        $user->refresh();
    }

    /**
     * Core maturation for a single user. Assumes it runs inside a transaction.
     * Idempotent: matured_synced guards against double counting.
     */
    private function foldMatured(User $user): void
    {
        $due = PointTransaction::where('user_id', $user->id)
            ->where('matured_synced', false)
            ->where('available_at', '<=', now())
            ->lockForUpdate()
            ->get();

        if ($due->isEmpty()) {
            return;
        }

        $sum = (int) $due->sum('amount');
        PointTransaction::whereIn('id', $due->pluck('id'))->update(['matured_synced' => true]);

        if ($sum !== 0) {
            User::whereKey($user->id)->increment('points', $sum);
        }
    }

    /**
     * Backstop batch maturation for the scheduler (points:mature).
     * Folds every now-due, not-yet-synced entry into its owner's cache, grouped by user.
     * On-read/on-spend syncMatured() already guarantees correctness for active users;
     * this covers long-dormant accounts and final consistency. Returns the affected row count.
     */
    public function matureDue(): int
    {
        return DB::transaction(function () {
            $due = PointTransaction::where('matured_synced', false)
                ->where('available_at', '<=', now())
                ->lockForUpdate()
                ->get();

            if ($due->isEmpty()) {
                return 0;
            }

            foreach ($due->groupBy('user_id') as $userId => $rows) {
                $sum = (int) $rows->sum('amount');
                if ($sum !== 0) {
                    User::whereKey($userId)->increment('points', $sum);
                }
            }

            PointTransaction::whereIn('id', $due->pluck('id'))->update(['matured_synced' => true]);

            return $due->count();
        });
    }

    /**
     * Void the not-yet-matured referral reward for an order on refund (FR-024).
     * Order-level idempotent: a reward that already matured (synced to cache) is left
     * untouched — the 14-day refund window guarantees rewards being voided are unmatured,
     * so users.points never needs adjusting and can never go negative (SC-006).
     */
    public function voidReferral(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $rewards = PointTransaction::where('type', 'earn_referral')
                ->where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->where('matured_synced', false)
                ->lockForUpdate()
                ->get();

            foreach ($rewards as $reward) {
                // Write an offsetting entry for audit, then mark the pair settled so
                // neither is ever folded into the cache. The reversal shares the reward's
                // available_at so the +/- pair always crosses the maturity boundary
                // together and reconcile() (which sums all matured rows) stays balanced.
                PointTransaction::create([
                    'user_id'        => $reward->user_id,
                    'amount'         => -$reward->amount,
                    'type'           => 'refund_reversal',
                    'reference_type' => 'order',
                    'reference_id'   => $order->id,
                    'available_at'   => $reward->available_at,
                    'matured_synced' => true,
                ]);

                $reward->update(['matured_synced' => true]);
            }
        });
    }

    /**
     * Reconcile the users.points cache against the ledger (points:reconcile / SC-002).
     * Returns the list of users whose cache drifts from SUM(matured ledger).
     *
     * @return array<int, array{user_id: int, cached: int, ledger: int}>
     */
    public function reconcile(): array
    {
        $ledger = PointTransaction::where('available_at', '<=', now())
            ->selectRaw('user_id, SUM(amount) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $drift = [];

        User::query()->select('id', 'points')->chunkById(500, function ($users) use ($ledger, &$drift) {
            foreach ($users as $user) {
                $expected = (int) ($ledger[$user->id] ?? 0);
                if ((int) $user->points !== $expected) {
                    $drift[] = [
                        'user_id' => $user->id,
                        'cached'  => (int) $user->points,
                        'ledger'  => $expected,
                    ];
                }
            }
        });

        return $drift;
    }
}

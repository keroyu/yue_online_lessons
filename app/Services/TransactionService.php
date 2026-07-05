<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Manually create a transaction (system_assigned or gift).
     *
     * @param  User    $user
     * @param  Course  $course
     * @param  string  $type  'system_assigned' | 'gift'
     * @return array{success: bool, purchase?: Purchase, error?: string}
     */
    public function createManual(User $user, Course $course, string $type): array
    {
        // Check if a paid purchase already exists
        $existingPaid = Purchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->paidStatus()
            ->first();

        if ($existingPaid) {
            return ['success' => false, 'error' => '該會員已擁有此課程'];
        }

        // Check if a refunded purchase exists — update it instead of inserting to avoid UNIQUE constraint
        $existingRefunded = Purchase::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->refundedStatus()
            ->first();

        if ($existingRefunded) {
            $existingRefunded->update([
                'status'  => 'paid',
                'source'  => 'manual',
                'type'    => $type,
                'amount'  => 0,
                'currency' => 'TWD',
            ]);

            Log::info('Manual transaction created (updated refunded record)', [
                'purchase_id' => $existingRefunded->id,
                'user_id'     => $user->id,
                'course_id'   => $course->id,
                'type'        => $type,
            ]);

            return ['success' => true, 'purchase' => $existingRefunded];
        }

        // Create a new purchase record
        $purchase = Purchase::create([
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'buyer_email' => $user->email,
            'amount'      => 0,
            'currency'    => 'TWD',
            'status'      => 'paid',
            'source'      => 'manual',
            'type'        => $type,
        ]);

        Log::info('Manual transaction created', [
            'purchase_id' => $purchase->id,
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'type'        => $type,
        ]);

        return ['success' => true, 'purchase' => $purchase];
    }

    /**
     * Mark a purchase as refunded, revoking course access.
     *
     * @param  Purchase $purchase
     * @return array{success: bool, error?: string}
     */
    public function refund(Purchase $purchase): array
    {
        if ($purchase->status === 'refunded') {
            return ['success' => false, 'error' => '此交易已退款'];
        }

        // Referral-linked orders can only be refunded within the reward maturity window
        // (FR-023); beyond it the reward has matured and may already be spent.
        $order = $purchase->order;
        if ($order?->referrer_user_id) {
            $maturityDays = (int) SiteSetting::get('referral_maturity_days', 14);
            $deadline = ($order->webhook_received_at ?? $order->created_at)->copy()->addDays($maturityDays);

            if (now()->greaterThan($deadline)) {
                return ['success' => false, 'error' => '此訂單已超過退款期限，含推薦回饋的訂單僅能於 ' . $maturityDays . ' 天內退款'];
            }
        }

        $purchase->update(['status' => 'refunded']);

        // Void the not-yet-matured referral reward (order-level idempotent, FR-024).
        // The activation flag is never reverted (FR-025).
        if ($order?->referrer_user_id) {
            app(PointService::class)->voidReferral($order);
        }

        Log::info('Transaction refunded', [
            'purchase_id' => $purchase->id,
            'user_id'     => $purchase->user_id,
            'course_id'   => $purchase->course_id,
        ]);

        return ['success' => true];
    }
}

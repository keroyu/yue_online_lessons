<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedemptionService
{
    public function __construct(
        private PointService $pointService,
        private DripService $dripService,
    ) {
    }

    /**
     * Redeem a course with points (all-or-nothing, no payment gateway).
     * Mirrors the free-enrollment path: create a Purchase to grant ownership.
     *
     * @return array{success: bool, error?: string, purchase?: Purchase}
     */
    public function redeem(User $user, Course $course): array
    {
        if (! $course->is_redeemable) {
            return ['success' => false, 'error' => '此課程無法以積分兌換'];
        }

        if (Purchase::where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            return ['success' => false, 'error' => '您已擁有此課程'];
        }

        try {
            $purchase = DB::transaction(function () use ($user, $course) {
                // Atomic deduct — throws on insufficient balance, rolling back the whole tx.
                $this->pointService->redeemDeduct($user, (int) $course->redeem_points, 'course', $course->id);

                return Purchase::create([
                    'user_id'             => $user->id,
                    'course_id'           => $course->id,
                    'buyer_email'         => $user->email,
                    'amount'              => 0,
                    'currency'            => 'TWD',
                    'status'              => 'paid',
                    'type'                => 'paid',
                    'source'              => 'points',
                    'webhook_received_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            // Insufficient balance — do not leak the throw to the controller (constitution VII).
            return ['success' => false, 'error' => '可用積分不足'];
        }

        // Match the free-enrollment fulfilment side effects for delivery consistency.
        if ($course->course_type === 'drip') {
            $this->dripService->subscribe($user, $course);
        }
        $this->dripService->checkAndConvert($user, $course);

        Log::info('Course redeemed with points', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'cost'      => (int) $course->redeem_points,
        ]);

        return ['success' => true, 'purchase' => $purchase];
    }
}

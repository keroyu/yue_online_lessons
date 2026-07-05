<?php

namespace Tests\Feature\Points;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\PointService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralRefundTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(int $price): Course
    {
        return Course::create([
            'name' => 'C', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => $price, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
    }

    /** Build a paid, referral-linked order and return [referrer, purchase]. */
    private function paidReferralOrder(): array
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(3500);
        $checkout = app(CheckoutService::class);

        $order = $checkout->createOrder(null, [$course->id], [
            'name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '0900000000',
        ], [], null, ['referrer_id' => $referrer->id, 'rate' => 10]);

        $checkout->fulfillOrder($order->fresh(), 'trade-1', 'payuni');

        $purchase = Purchase::where('order_id', $order->id)->firstOrFail();

        return [$referrer->fresh(), $purchase, $order->fresh()];
    }

    public function test_refund_within_window_voids_unmatured_reward_no_negative(): void
    {
        [$referrer, $purchase] = $this->paidReferralOrder();

        // Reward exists, pending, cache still zero.
        $this->assertSame(350, $referrer->pendingPoints());
        $this->assertSame(0, (int) $referrer->points);

        $result = app(TransactionService::class)->refund($purchase);
        $this->assertTrue($result['success']);

        // Offset written; reward neutralised; balance never negative.
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $referrer->id,
            'type'    => 'refund_reversal',
            'amount'  => -350,
        ]);
        $this->assertSame(0, (int) $referrer->fresh()->points);
        $this->assertGreaterThanOrEqual(0, (int) $referrer->fresh()->points);

        // Ledger and cache stay reconciled.
        $this->assertEmpty(app(PointService::class)->reconcile());
    }

    public function test_refund_after_window_is_blocked(): void
    {
        [, $purchase, $order] = $this->paidReferralOrder();

        // Push the payment 15 days into the past → past the 14-day window.
        $order->update(['webhook_received_at' => now()->subDays(15)]);

        $result = app(TransactionService::class)->refund($purchase->fresh());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('退款期限', $result['error']);
        $this->assertSame('paid', $purchase->fresh()->status); // not refunded
    }
}

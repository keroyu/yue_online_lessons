<?php

namespace Tests\Feature\Points;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralRewardTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(int $price): Course
    {
        return Course::create([
            'name'            => 'Paid Lecture',
            'slug'            => 'paid-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => $price,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    public function test_reward_paid_to_ten_and_matures_in_14_days(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(3500);

        $checkout = app(CheckoutService::class);
        $order = $checkout->createOrder(null, [$course->id], [
            'name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '0900000000',
        ], [], null, ['referrer_id' => $referrer->id, 'rate' => 10]);

        // Not paid yet → no reward on the ledger.
        $this->assertDatabaseMissing('point_transactions', [
            'user_id' => $referrer->id,
            'type'    => 'earn_referral',
        ]);

        $checkout->fulfillOrder($order->fresh(), 'trade-123', 'payuni');

        // 3500 * 10% = 350, rounded to nearest ten = 350.
        $tx = $referrer->pointTransactions()->where('type', 'earn_referral')->first();
        $this->assertNotNull($tx);
        $this->assertSame(350, $tx->amount);
        $this->assertFalse((bool) $tx->matured_synced);
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $tx->available_at->timestamp, 5);

        // Pending (not matured) → not yet in the available cache.
        $this->assertSame(0, (int) $referrer->fresh()->points);
        $this->assertSame(350, $referrer->fresh()->pendingPoints());
    }

    public function test_reward_rounds_half_up_to_nearest_ten(): void
    {
        $svc = app(ReferralService::class);
        $this->assertSame(300, $svc->computeReward(2999, 10)); // 299.9 → 300
        $this->assertSame(300, $svc->computeReward(2950, 10)); // 295.0 → 300 (half-up)
        $this->assertSame(290, $svc->computeReward(2940, 10)); // 294.0 → 290
        $this->assertSame(0, $svc->computeReward(0, 10));
    }

    public function test_reward_is_idempotent_per_order(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(3500);
        $checkout = app(CheckoutService::class);
        $order = $checkout->createOrder(null, [$course->id], [
            'name' => 'Buyer', 'email' => 'buyer2@example.com', 'phone' => '0900000000',
        ], [], null, ['referrer_id' => $referrer->id, 'rate' => 10]);

        $svc = app(ReferralService::class);
        $order = $order->fresh()->load('referrer');
        $svc->reward($order);
        $svc->reward($order); // second call must not double-pay

        $this->assertSame(1, $referrer->pointTransactions()->where('type', 'earn_referral')->count());
    }

    public function test_buyer_activation_lights_up_on_threshold(): void
    {
        $buyer = User::factory()->create(['referral_activated_at' => null]);
        $course = $this->makeCourse(3000);

        Purchase::create([
            'user_id' => $buyer->id, 'course_id' => $course->id, 'buyer_email' => $buyer->email,
            'amount' => 3000, 'currency' => 'TWD', 'status' => 'paid', 'type' => 'paid', 'source' => 'payuni',
        ]);

        app(ReferralService::class)->evaluateActivation($buyer);

        $this->assertNotNull($buyer->fresh()->referral_activated_at);
    }

    public function test_buyer_below_threshold_not_activated(): void
    {
        $buyer = User::factory()->create(['referral_activated_at' => null]);
        $course = $this->makeCourse(2000);

        Purchase::create([
            'user_id' => $buyer->id, 'course_id' => $course->id, 'buyer_email' => $buyer->email,
            'amount' => 2000, 'currency' => 'TWD', 'status' => 'paid', 'type' => 'paid', 'source' => 'payuni',
        ]);

        app(ReferralService::class)->evaluateActivation($buyer);

        $this->assertNull($buyer->fresh()->referral_activated_at);
    }
}

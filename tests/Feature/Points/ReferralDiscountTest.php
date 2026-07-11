<?php

namespace Tests\Feature\Points;

use App\Models\CouponCode;
use App\Models\Course;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralDiscountTest extends TestCase
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

    private function referralFor(User $referrer, int $discount = 150, int $rate = 10): array
    {
        return ['referrer_id' => $referrer->id, 'rate' => $rate, 'discount' => $discount];
    }

    private function buyer(): array
    {
        return ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '0900000000'];
    }

    public function test_referral_discount_reduces_total_and_is_snapshotted(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(3500);

        $order = app(CheckoutService::class)->createOrder(
            null, [$course->id], $this->buyer(), [], null, $this->referralFor($referrer)
        );

        $this->assertSame(3350, (int) $order->total_amount);
        $this->assertSame(150, (int) $order->referral_discount_amount);
    }

    public function test_reward_is_computed_on_discounted_paid_amount(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(3500);

        $checkout = app(CheckoutService::class);
        $order = $checkout->createOrder(
            null, [$course->id], $this->buyer(), [], null, $this->referralFor($referrer)
        );
        $checkout->fulfillOrder($order->fresh(), 'trade-d1', 'payuni');

        // 3350 * 10% = 335 → half-up to nearest ten = 340.
        $tx = $referrer->pointTransactions()->where('type', 'earn_referral')->first();
        $this->assertNotNull($tx);
        $this->assertSame(340, $tx->amount);
    }

    public function test_discount_stacks_after_coupon(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(3500);
        CouponCode::create([
            'code'      => 'STACK1',
            'type'      => 'fixed',
            'value'     => 500,
            'is_active' => true,
        ]);

        $order = app(CheckoutService::class)->createOrder(
            null, [$course->id], $this->buyer(), [], 'STACK1', $this->referralFor($referrer)
        );

        // 3500 - 500 (coupon) - 150 (referral) = 2850
        $this->assertSame(500, (int) $order->discount_amount);
        $this->assertSame(150, (int) $order->referral_discount_amount);
        $this->assertSame(2850, (int) $order->total_amount);
    }

    public function test_discount_is_clamped_so_payable_stays_at_least_one(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(100);

        $order = app(CheckoutService::class)->createOrder(
            null, [$course->id], $this->buyer(), [], null, $this->referralFor($referrer, 150)
        );

        $this->assertSame(99, (int) $order->referral_discount_amount);
        $this->assertSame(1, (int) $order->total_amount);
    }

    public function test_zero_discount_setting_keeps_referral_and_reward(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);
        $course = $this->makeCourse(3500);

        $checkout = app(CheckoutService::class);
        $order = $checkout->createOrder(
            null, [$course->id], $this->buyer(), [], null, $this->referralFor($referrer, 0)
        );

        $this->assertSame(3500, (int) $order->total_amount);
        $this->assertSame(0, (int) $order->referral_discount_amount);
        $this->assertSame($referrer->id, (int) $order->referrer_user_id);

        $checkout->fulfillOrder($order->fresh(), 'trade-d2', 'payuni');
        $tx = $referrer->pointTransactions()->where('type', 'earn_referral')->first();
        $this->assertSame(350, $tx->amount);
    }

    public function test_validate_api_returns_discount_from_setting(): void
    {
        $referrer = User::factory()->create(['referral_activated_at' => now()]);

        $res = $this->postJson('/api/checkout/validate-referral', [
            'referral_code' => $referrer->referral_code,
            'buyer_email'   => 'buyer@example.com',
        ]);
        $res->assertOk()->assertJson(['success' => true, 'discount' => 150]);

        SiteSetting::set('referral_discount_amount', '200');
        $res = $this->postJson('/api/checkout/validate-referral', [
            'referral_code' => $referrer->referral_code,
            'buyer_email'   => 'buyer@example.com',
        ]);
        $res->assertOk()->assertJson(['discount' => 200]);
    }

    public function test_admin_can_save_discount_amount_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/settings/points', [
            'referral_threshold_amount' => 3000,
            'referral_reward_rate'      => 10,
            'homework_reward_points'    => 100,
            'referral_maturity_days'    => 14,
            'referral_discount_amount'  => 250,
        ])->assertRedirect();

        $this->assertSame('250', SiteSetting::get('referral_discount_amount'));

        $this->actingAs($admin)->post('/admin/settings/points', [
            'referral_threshold_amount' => 3000,
            'referral_reward_rate'      => 10,
            'homework_reward_points'    => 100,
            'referral_maturity_days'    => 14,
            'referral_discount_amount'  => -5,
        ])->assertSessionHasErrors('referral_discount_amount');
    }
}

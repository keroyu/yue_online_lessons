<?php

namespace Tests\Feature\Points;

use App\Models\Course;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Purchase;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US8 — 推薦人明細檢視: the 推薦成效 drill-down endpoint returns the referrer's own point
 * ledger, their own transactions, and the orders they referred (all read-only, admin-only).
 */
class ReferrerDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function course(): Course
    {
        return Course::create([
            'name' => 'C', 'slug' => 'c', 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
    }

    private function referredOrder(User $referrer, string $no, int $total, int $reward): Order
    {
        return Order::create([
            'user_id'                => User::factory()->create()->id,
            'buyer_name'             => 'B', 'buyer_email' => "{$no}@example.com", 'buyer_phone' => '0900000000',
            'total_amount'           => $total, 'currency' => 'TWD', 'payment_gateway' => 'payuni',
            'merchant_order_no'      => $no, 'status' => 'paid',
            'referrer_user_id'       => $referrer->id, 'referral_rate' => 10, 'referral_reward_points' => $reward,
            'webhook_received_at'    => now(),
        ]);
    }

    public function test_detail_returns_ledger_own_transactions_and_referred_orders(): void
    {
        $admin = $this->admin();
        $referrer = User::factory()->create(['nickname' => 'Ref', 'points' => 300]);
        $course = $this->course();

        // (1) referrer's own point ledger
        PointTransaction::create([
            'user_id' => $referrer->id, 'amount' => 200, 'type' => 'earn_referral',
            'reference_type' => 'order', 'reference_id' => 1,
            'available_at' => now()->subDay(), 'matured_synced' => true,
        ]);

        // (2) referrer's own transaction (they bought a course)
        Purchase::create([
            'user_id' => $referrer->id, 'course_id' => $course->id, 'buyer_email' => $referrer->email,
            'amount' => 1000, 'currency' => 'TWD', 'status' => 'paid', 'type' => 'paid',
        ]);

        // (3) an order this referrer brought in
        $this->referredOrder($referrer, 'ORD-1', 1500, 150);

        $this->actingAs($admin)
            ->getJson("/admin/referrals/{$referrer->id}/detail")
            ->assertOk()
            ->assertJsonPath('referrer.id', $referrer->id)
            ->assertJsonPath('referrer.current_points', 300)
            ->assertJsonCount(1, 'point_transactions')
            ->assertJsonPath('point_transactions.0.amount', 200)
            ->assertJsonPath('point_transactions.0.is_matured', true)
            ->assertJsonCount(1, 'own_transactions')
            ->assertJsonPath('own_transactions.0.course_name', 'C')
            ->assertJsonPath('own_transactions.0.amount', 1000)
            ->assertJsonPath('own_transactions.0.type_label', '已付款')
            ->assertJsonCount(1, 'referred_orders')
            ->assertJsonPath('referred_orders.0.merchant_order_no', 'ORD-1')
            ->assertJsonPath('referred_orders.0.total_amount', 1500)
            ->assertJsonPath('referred_orders.0.referral_reward_points', 150);
    }

    public function test_detail_is_admin_only(): void
    {
        $referrer = User::factory()->create();
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)
            ->get("/admin/referrals/{$referrer->id}/detail")
            ->assertRedirect('/');
    }

    public function test_performance_rows_include_referrer_user_id(): void
    {
        $ref = User::factory()->create(['nickname' => 'Ref']);
        $this->referredOrder($ref, 'ORD-9', 1000, 100);

        $rows = app(ReferralService::class)->performanceRows(null);

        $this->assertCount(1, $rows);
        $this->assertSame($ref->id, $rows[0]['referrer_user_id']);
    }
}

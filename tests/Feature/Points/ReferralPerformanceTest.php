<?php

namespace Tests\Feature\Points;

use App\Models\Order;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the merged 積分與推薦 page:
 *   - ReferralService::performanceRows aggregation (incl. ONLY_FULL_GROUP_BY safety)
 *   - SettingsController@showPoints payload + range param
 *   - /admin/referrals → settings.points redirect
 */
class ReferralPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private int $seq = 0;

    private function referredOrder(User $referrer, int $total, int $reward, int $daysAgo): Order
    {
        $this->seq++;
        $buyer = User::factory()->create();

        return Order::create([
            'user_id'                => $buyer->id,
            'buyer_name'             => 'Buyer',
            'buyer_email'            => "buyer{$this->seq}@example.com",
            'buyer_phone'            => '0900000000',
            'total_amount'           => $total,
            'currency'               => 'TWD',
            'payment_gateway'        => 'payuni',
            'merchant_order_no'      => "TEST-{$this->seq}",
            'status'                 => 'paid',
            'referrer_user_id'       => $referrer->id,
            'referral_rate'          => 10,
            'referral_reward_points' => $reward,
            'webhook_received_at'    => now()->subDays($daysAgo),
        ]);
    }

    public function test_performance_rows_aggregate_per_referrer(): void
    {
        $a = User::factory()->create(['nickname' => 'Ann', 'points' => 500]);
        $b = User::factory()->create(['nickname' => 'Bob', 'points' => 0]);

        // Ann: 2 orders → reward 300; Bob: 1 order → reward 500 (ranks above Ann).
        $this->referredOrder($a, 1000, 100, 3);
        $this->referredOrder($a, 2000, 200, 5);
        $this->referredOrder($b, 4000, 500, 2);

        // Noise that must be excluded: a non-referral paid order + a pending referral order.
        Order::create([
            'user_id' => User::factory()->create()->id, 'buyer_name' => 'X', 'buyer_email' => 'x@example.com',
            'buyer_phone' => '0900000000', 'total_amount' => 9999, 'currency' => 'TWD', 'payment_gateway' => 'payuni',
            'merchant_order_no' => 'NOREF', 'status' => 'paid', 'webhook_received_at' => now(),
        ]);
        $this->referredOrder($a, 5000, 500, 1)->update(['status' => 'pending']);

        $rows = app(ReferralService::class)->performanceRows(30);

        $this->assertCount(2, $rows);
        // Ordered by reward_points desc → Bob first.
        $this->assertSame('Bob', $rows[0]['referrer_name']);
        $this->assertSame(1, $rows[0]['order_count']);
        $this->assertSame(4000, $rows[0]['revenue']);
        $this->assertSame(500, $rows[0]['reward_points']);
        $this->assertSame(0, $rows[0]['current_points']);

        $this->assertSame('Ann', $rows[1]['referrer_name']);
        $this->assertSame(2, $rows[1]['order_count']);
        $this->assertSame(3000, $rows[1]['revenue']);
        $this->assertSame(300, $rows[1]['reward_points']);
        $this->assertSame(500, $rows[1]['current_points']); // current spendable balance
    }

    public function test_range_window_filters_by_webhook_date(): void
    {
        $recent = User::factory()->create(['nickname' => 'Recent']);
        $old = User::factory()->create(['nickname' => 'Old']);
        $this->referredOrder($recent, 1000, 100, 3);   // within 7 & 30 days
        $this->referredOrder($old, 1000, 100, 20);     // within 30, outside 7

        $svc = app(ReferralService::class);
        $this->assertCount(1, $svc->performanceRows(7));   // only Recent
        $this->assertCount(2, $svc->performanceRows(30));  // both
        $this->assertCount(2, $svc->performanceRows(null)); // all-time
    }

    public function test_show_points_page_includes_referral_payload_and_range(): void
    {
        $admin = $this->admin();
        $ref = User::factory()->create(['nickname' => 'Ref']);
        $this->referredOrder($ref, 1000, 100, 3);
        $this->referredOrder(User::factory()->create(['nickname' => 'Old']), 1000, 100, 20);

        // Default range = 30 → both referrers present.
        $this->actingAs($admin)
            ->get('/admin/settings/points')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Admin/Settings/Points')
                ->has('points.referral_reward_rate')
                ->where('referral.range', '30')
                ->has('referral.rows', 2));

        // range=7 → only the recent referrer.
        $this->actingAs($admin)
            ->get('/admin/settings/points?range=7')
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('referral.range', '7')
                ->has('referral.rows', 1)
                ->where('referral.rows.0.referrer_name', 'Ref'));
    }

    public function test_legacy_referrals_route_redirects_to_points_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/referrals')
            ->assertRedirect('/admin/settings/points');
    }
}

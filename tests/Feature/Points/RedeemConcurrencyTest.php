<?php

namespace Tests\Feature\Points;

use App\Models\User;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeemConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** SC-001: the guarded conditional deduct can never drive the balance negative. */
    public function test_deduct_to_zero_then_further_deduct_is_blocked_and_never_negative(): void
    {
        $svc = app(PointService::class);
        $user = User::factory()->create();
        $svc->award($user, 1000, 'admin_grant', 'admin', null, 'seed');

        // Exact deduct → balance 0
        $svc->redeemDeduct($user->fresh(), 1000, 'course', 1);
        $this->assertSame(0, (int) $user->fresh()->points);

        // One more point must be refused, balance stays 0 (never negative)
        try {
            $svc->redeemDeduct($user->fresh(), 1, 'course', 2);
            $this->fail('Expected insufficient-balance exception');
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertSame(0, (int) $user->fresh()->points);
    }

    /** A failed deduct writes no ledger row (transaction rolled back). */
    public function test_failed_deduct_writes_no_ledger_row(): void
    {
        $svc = app(PointService::class);
        $user = User::factory()->create();
        $svc->award($user, 100, 'admin_grant', 'admin', null, 'seed');

        try {
            $svc->redeemDeduct($user->fresh(), 500, 'course', 9);
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertDatabaseMissing('point_transactions', [
            'user_id' => $user->id,
            'type'    => 'redeem_course',
        ]);
        $this->assertSame(100, (int) $user->fresh()->points);
    }
}

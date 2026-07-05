<?php

namespace Tests\Feature\Points;

use App\Models\PointTransaction;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReconcileTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_drift_across_instant_and_pending_and_matured(): void
    {
        $svc = app(PointService::class);
        $user = User::factory()->create();

        // Instant-mature grant → in cache immediately.
        $svc->award($user, 500, 'admin_grant', 'admin', null, 'grant');

        // Pending referral reward → NOT in cache, NOT in matured sum yet.
        $svc->award($user, 300, 'earn_referral', 'order', 1, null, now()->addDays(14));

        $this->assertSame(500, (int) $user->fresh()->points);
        $this->assertEmpty($svc->reconcile());

        // Time passes: the referral matures. Backstop batch folds it in.
        PointTransaction::where('user_id', $user->id)
            ->where('type', 'earn_referral')
            ->update(['available_at' => now()->subMinute()]);
        $svc->matureDue();

        $this->assertSame(800, (int) $user->fresh()->points);
        $this->assertEmpty($svc->reconcile());
    }

    public function test_reconcile_detects_injected_drift(): void
    {
        $svc = app(PointService::class);
        $user = User::factory()->create();
        $svc->award($user, 100, 'admin_grant', 'admin', null, null);

        // Corrupt the cache directly (simulating a rogue write) → reconcile must catch it.
        DB::table('users')->where('id', $user->id)->update(['points' => 999]);

        $drift = $svc->reconcile();
        $this->assertCount(1, $drift);
        $this->assertSame($user->id, $drift[0]['user_id']);
        $this->assertSame(999, $drift[0]['cached']);
        $this->assertSame(100, $drift[0]['ledger']);
    }
}

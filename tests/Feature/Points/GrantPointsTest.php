<?php

namespace Tests\Feature\Points;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantPointsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'member']);
    }

    public function test_grant_writes_ledger_and_counts_immediately(): void
    {
        $member = $this->member();

        $this->actingAs($this->admin())
            ->postJson("/admin/members/{$member->id}/grant-points", ['amount' => 300, 'note' => '活動獎勵'])
            ->assertOk()
            ->assertJson(['success' => true, 'points' => 300]);

        $this->assertSame(300, (int) $member->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id'        => $member->id,
            'type'           => 'admin_grant',
            'amount'         => 300,
            'note'           => '活動獎勵',
            'matured_synced' => true,
        ]);
    }

    public function test_grant_rejects_zero_negative_and_blank(): void
    {
        $member = $this->member();
        $admin = $this->admin();

        foreach ([0, -50, null, 'abc'] as $bad) {
            $this->actingAs($admin)
                ->postJson("/admin/members/{$member->id}/grant-points", ['amount' => $bad])
                ->assertStatus(422);
        }

        $this->assertSame(0, (int) $member->fresh()->points);
        $this->assertDatabaseMissing('point_transactions', [
            'user_id' => $member->id,
            'type'    => 'admin_grant',
        ]);
    }

    public function test_non_admin_cannot_grant(): void
    {
        $member = $this->member();

        // AdminMiddleware redirects non-admins to home (302) rather than 403.
        $this->actingAs($this->member())
            ->post("/admin/members/{$member->id}/grant-points", ['amount' => 100])
            ->assertRedirect('/');

        $this->assertSame(0, (int) $member->fresh()->points);
    }
}

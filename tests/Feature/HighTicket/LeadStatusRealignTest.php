<?php

namespace Tests\Feature\HighTicket;

use App\Models\HighTicketLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 011 FR-055 — the one-off re-filing of historical leads.
 *
 * Worth a test despite being a two-line UPDATE: it rewrites real production
 * rows in a way `down()` cannot undo, and getting the mapping backwards would
 * mark people who never replied as having completed a consultation.
 */
class LeadStatusRealignTest extends TestCase
{
    use RefreshDatabase;

    private function lead(string $status, string $email): HighTicketLead
    {
        return HighTicketLead::create([
            'name' => 'L', 'email' => $email, 'course_id' => 1,
            'status' => $status, 'booked_at' => now(),
        ]);
    }

    private function runMigration(): void
    {
        (require base_path('database/migrations/2026_08_08_000002_realign_lead_statuses_to_consultation_vocabulary.php'))->up();
    }

    public function test_it_remaps_the_two_statuses_that_changed_meaning(): void
    {
        $contacted = $this->lead('contacted', 'a@example.com');
        $noResponse = $this->lead('no_response', 'b@example.com');

        $this->runMigration();

        $this->assertSame('pending', $contacted->fresh()->status, '已聯繫 → 待面談');
        $this->assertSame('cancelled', $noResponse->fresh()->status, '未回應 → 已取消');
    }

    public function test_it_leaves_the_statuses_whose_meaning_did_not_change(): void
    {
        $kept = [
            'pending'   => $this->lead('pending', 'c@example.com'),
            'converted' => $this->lead('converted', 'd@example.com'),
            'closed'    => $this->lead('closed', 'e@example.com'),
        ];

        $this->runMigration();

        foreach ($kept as $status => $lead) {
            $this->assertSame($status, $lead->fresh()->status);
        }
    }

    /** No invented timestamps — we do not know when these went cold. */
    public function test_the_remapped_rows_get_no_cancellation_timestamp(): void
    {
        $lead = $this->lead('no_response', 'f@example.com');

        $this->runMigration();

        $this->assertNull($lead->fresh()->cancelled_at);
    }

    /**
     * The remap must leave them bookable again: US16 blocks `no_response` but
     * allows `cancelled`, and somebody who never replied deserves another go.
     */
    public function test_a_remapped_lead_is_no_longer_blocked_from_rebooking(): void
    {
        $lead = $this->lead('no_response', 'g@example.com');

        $this->runMigration();

        $this->assertContains($lead->fresh()->status, ['closed', 'cancelled'], '必須落在 US16 的放行集合裡');
    }

    public function test_running_it_twice_changes_nothing_further(): void
    {
        $this->lead('contacted', 'h@example.com');
        $this->lead('no_response', 'i@example.com');

        $this->runMigration();
        $before = DB::table('high_ticket_leads')->orderBy('id')->pluck('status')->all();

        $this->runMigration();

        $this->assertSame($before, DB::table('high_ticket_leads')->orderBy('id')->pluck('status')->all());
    }
}

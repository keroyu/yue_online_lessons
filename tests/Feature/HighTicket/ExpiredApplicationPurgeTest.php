<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\HighTicketLead;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 FR-068 — an application nobody confirmed leaves no trace.
 *
 * The hour-long hold already frees the slot; this drops the lead row too, so
 * the Leads list only ever shows people who actually completed a booking or
 * whom an admin is working. The status guard is what keeps it safe: once a
 * human has touched the lead, the sweeper leaves it alone.
 */
class ExpiredApplicationPurgeTest extends TestCase
{
    use RefreshDatabase;

    private function service(): HighTicketBookingService
    {
        return app(HighTicketBookingService::class);
    }

    private function makeLead(array $overrides = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'               => 'Applicant',
            'email'              => 'a' . uniqid() . '@example.com',
            'course_id'          => 1,
            'status'             => 'pending',
            'booked_at'          => now(),
            'confirm_token'      => uniqid(),
            'confirm_expires_at' => now()->subMinute(),
            'confirmed_at'       => null,
        ], $overrides));
    }

    public function test_expired_unconfirmed_application_is_deleted(): void
    {
        $lead = $this->makeLead();

        $this->assertSame(1, $this->service()->purgeExpiredApplications());
        $this->assertDatabaseMissing('high_ticket_leads', ['id' => $lead->id]);
    }

    public function test_hold_still_running_is_left_alone(): void
    {
        $lead = $this->makeLead(['confirm_expires_at' => now()->addMinutes(30)]);

        $this->assertSame(0, $this->service()->purgeExpiredApplications());
        $this->assertDatabaseHas('high_ticket_leads', ['id' => $lead->id]);
    }

    public function test_confirmed_booking_is_never_purged(): void
    {
        // Expiry has passed, but the link was clicked in time — this is a real booking.
        $lead = $this->makeLead(['confirmed_at' => now()->subMinutes(90)]);

        $this->assertSame(0, $this->service()->purgeExpiredApplications());
        $this->assertDatabaseHas('high_ticket_leads', ['id' => $lead->id]);
    }

    /** A cancelled booking was confirmed once, so it keeps its history. */
    public function test_cancelled_booking_is_never_purged(): void
    {
        $lead = $this->makeLead([
            'status'       => 'cancelled',
            'confirmed_at' => now()->subDay(),
            'cancelled_at' => now()->subHour(),
        ]);

        $this->assertSame(0, $this->service()->purgeExpiredApplications());
        $this->assertDatabaseHas('high_ticket_leads', ['id' => $lead->id]);
    }

    /** Waitlist entries never had a token to expire — confirm_expires_at is null. */
    public function test_waitlisted_lead_is_never_purged(): void
    {
        $lead = $this->makeLead(['confirm_token' => null, 'confirm_expires_at' => null]);

        $this->assertSame(0, $this->service()->purgeExpiredApplications());
        $this->assertDatabaseHas('high_ticket_leads', ['id' => $lead->id]);
    }

    /**
     * The safety rail: an admin who moved the lead off 'pending' is working it,
     * and a sweeper must not delete somebody's live follow-up.
     */
    public function test_lead_an_admin_has_touched_is_never_purged(): void
    {
        foreach (['contacted', 'converted', 'closed', 'no_response'] as $status) {
            $lead = $this->makeLead(['status' => $status]);

            $this->assertSame(0, $this->service()->purgeExpiredApplications(), "status={$status} 不該被刪");
            $this->assertDatabaseHas('high_ticket_leads', ['id' => $lead->id]);
        }
    }

    /** Deleting a lead must not leave slots pointing at a row that is gone. */
    public function test_purge_leaves_no_orphaned_slots(): void
    {
        $lead = $this->makeLead();

        $slot = ConsultationSlot::create([
            'starts_at'  => Carbon::parse('2026-09-01 10:00')->utc(),
            'lead_id'    => $lead->id,
            'held_until' => now()->subMinute(),
        ]);

        $this->service()->purgeExpiredApplications();

        $this->assertDatabaseMissing('high_ticket_leads', ['id' => $lead->id]);
        $this->assertNull($slot->fresh()->lead_id, '時段仍指向已刪除的 lead');
        $this->assertNull($slot->fresh()->held_until);
    }

    /** The scheduled command releases holds and purges in one pass. */
    public function test_command_releases_slots_and_purges_leads(): void
    {
        $lead = $this->makeLead();
        $slot = ConsultationSlot::create([
            'starts_at'  => Carbon::parse('2026-09-02 10:00')->utc(),
            'lead_id'    => $lead->id,
            'held_until' => now()->subMinute(),
        ]);

        $this->artisan('booking:release-holds')->assertSuccessful();

        $this->assertNull($slot->fresh()->lead_id);
        $this->assertDatabaseMissing('high_ticket_leads', ['id' => $lead->id]);
    }
}

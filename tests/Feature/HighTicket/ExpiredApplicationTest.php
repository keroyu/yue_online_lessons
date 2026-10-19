<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Services\ConsultationSlotService;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 FR-262 (revising FR-068) — an application nobody confirmed in time
 * loses its slot and its link, but the lead stays: somebody who filled in the
 * whole wizard is at least as worth following up as somebody who only got
 * through the screening, and those were always kept.
 */
class ExpiredApplicationTest extends TestCase
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
            'phone'              => '0912345678',
            'booked_at'          => now(),
            'confirm_token'      => uniqid(),
            'confirm_expires_at' => now()->subMinute(),
            'confirmed_at'       => null,
        ], $overrides));
    }

    public function test_expired_unconfirmed_application_is_kept_pending_without_its_link(): void
    {
        $lead = $this->makeLead();

        $this->assertSame(1, $this->service()->expireUnconfirmedApplications());

        $lead->refresh();
        $this->assertSame('pending', $lead->status, '留在待處理，方便主動聯絡');
        $this->assertSame('0912345678', $lead->phone, '填過的資料都在');
        $this->assertNull($lead->confirm_token, '逾時的確認連結作廢');
        $this->assertNotNull($lead->confirm_expires_at, '保留逾時時間，後台據此標示「未完成 Email 確認」');
        $this->assertNull($lead->confirmed_at);
    }

    public function test_a_second_pass_does_not_count_it_again(): void
    {
        $this->makeLead();

        $this->service()->expireUnconfirmedApplications();

        $this->assertSame(0, $this->service()->expireUnconfirmedApplications());
    }

    public function test_hold_still_running_is_left_alone(): void
    {
        $lead = $this->makeLead(['confirm_expires_at' => now()->addMinutes(30)]);

        $this->assertSame(0, $this->service()->expireUnconfirmedApplications());
        $this->assertNotNull($lead->fresh()->confirm_token);
    }

    public function test_confirmed_booking_is_untouched(): void
    {
        $lead = $this->makeLead(['confirmed_at' => now()->subMinutes(90)]);

        $this->assertSame(0, $this->service()->expireUnconfirmedApplications());
        $this->assertNotNull($lead->fresh()->confirm_token);
    }

    public function test_lead_an_admin_has_touched_keeps_its_status(): void
    {
        foreach (['contacted', 'converted', 'closed', 'no_response'] as $status) {
            $lead = $this->makeLead(['status' => $status]);

            $this->service()->expireUnconfirmedApplications();

            $this->assertSame($status, $lead->fresh()->status);
        }
    }

    public function test_expiry_releases_the_held_slot(): void
    {
        $lead = $this->makeLead();
        $slot = ConsultationSlot::create([
            'starts_at'  => Carbon::parse('2026-09-01 10:00')->utc(),
            'lead_id'    => $lead->id,
            'held_until' => now()->subMinute(),
        ]);

        $this->service()->expireUnconfirmedApplications();

        $this->assertNull($slot->fresh()->lead_id);
        $this->assertNull($slot->fresh()->held_until);
        $this->assertDatabaseHas('high_ticket_leads', ['id' => $lead->id]);
    }

    public function test_the_expired_link_can_no_longer_confirm(): void
    {
        $lead = $this->makeLead();
        $token = $lead->confirm_token;

        $this->service()->expireUnconfirmedApplications();

        $this->get("/booking/confirm/{$token}");
        $this->assertNull($lead->fresh()->confirmed_at);
    }

    /** The scheduled command releases holds and expires applications in one pass. */
    public function test_command_releases_slots_and_keeps_leads(): void
    {
        $lead = $this->makeLead();
        $slot = ConsultationSlot::create([
            'starts_at'  => Carbon::parse('2026-09-02 10:00')->utc(),
            'lead_id'    => $lead->id,
            'held_until' => now()->subMinute(),
        ]);

        $this->artisan('booking:release-holds')->assertSuccessful();

        $this->assertNull($slot->fresh()->lead_id);
        $this->assertDatabaseHas('high_ticket_leads', ['id' => $lead->id, 'status' => 'pending']);
    }

    /** Kept, not blocking: booking again refreshes the same row with a new link. */
    public function test_booking_again_after_expiry_reuses_the_lead(): void
    {
        Mail::fake();
        $course = Course::create([
            'name' => 'HT Course', 'slug' => 'ht-' . uniqid(), 'tagline' => 'tag', 'description' => 'desc',
            'price' => 58000, 'instructor_name' => 'Tester', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni', 'high_ticket_hide_price' => true,
        ]);
        $at = Carbon::parse('+1 day 10:00', ConsultationSlotService::DISPLAY_TZ)->utc();
        for ($i = 0; $i < 4; $i++) {
            ConsultationSlot::create(['starts_at' => $at->copy()->addMinutes($i * ConsultationSlot::UNIT_MINUTES)]);
        }
        $payload = [
            'name' => 'Applicant', 'email' => 'again@example.com', 'phone' => '0912345678',
            'occupation' => '設計師', 'bottleneck' => '收入不穩', 'expertise' => '設計',
            'commitments' => [true, true, true], 'slot_starts_at' => $at->toIso8601String(),
        ];

        $this->postJson("/course/{$course->id}/book", $payload)->assertOk();
        $this->travel(2)->hours();
        $this->service()->expireUnconfirmedApplications();

        $this->postJson("/course/{$course->id}/book", $payload)->assertOk();

        $lead = HighTicketLead::sole();
        $this->assertNotNull($lead->confirm_token, '新的確認連結');
        $this->assertTrue($lead->confirm_expires_at->isFuture());
    }
}

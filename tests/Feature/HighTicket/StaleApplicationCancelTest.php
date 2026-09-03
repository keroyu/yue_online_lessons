<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 US32 — a week after the gate, an unfinished application is 已取消.
 *
 * The sweep shares its population with the US26 nudge, so what is under test is
 * mostly who it must NOT touch: the waitlisted, the booked, the admin's work in
 * progress, and anybody who is still inside the week.
 */
class StaleApplicationCancelTest extends TestCase
{
    use RefreshDatabase;

    private function makeHighTicketCourse(): Course
    {
        return Course::create([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 58000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ])->fresh();
    }

    /** Cleared step 1 eight days ago and never reached step 2. */
    private function abandoned(Course $course, array $overrides = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'             => 'Applicant',
            'email'            => 'applicant@example.com',
            'course_id'        => $course->id,
            'status'           => 'pending',
            'booked_at'        => now()->subDays(8),
            'screened_at'      => now()->subDays(8),
            'screening_score'  => 7,
            'screen_timeline'  => 'immediate',
            'screen_budget'    => '10k_50k',
            'screen_authority' => 'self',
            'screen_pain'      => 'moderate',
            'screen_next_step' => 'start_now',
        ], $overrides));
    }

    private function sweep(): int
    {
        return app(HighTicketBookingService::class)->cancelStaleApplications();
    }

    public function test_an_application_abandoned_for_a_week_becomes_cancelled(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse());

        $this->assertSame(1, $this->sweep());
        $this->assertSame('cancelled', $lead->fresh()->status);
    }

    /** No booking was called off, so the timestamp that says one was must stay null. */
    public function test_the_sweep_does_not_stamp_cancelled_at(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse());

        $this->sweep();

        $this->assertNull($lead->fresh()->cancelled_at);
    }

    public function test_an_application_inside_the_window_is_left_alone(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse(), ['screened_at' => now()->subDays(6)]);

        $this->assertSame(0, $this->sweep());
        $this->assertSame('pending', $lead->fresh()->status);
    }

    /** A phone means step 2 was filled in — very likely someone waiting for a slot. */
    public function test_a_waitlisted_application_is_never_cancelled(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse(), ['phone' => '0912345678']);

        $this->assertSame(0, $this->sweep());
        $this->assertSame('pending', $lead->fresh()->status);
    }

    public function test_a_confirmed_booking_is_never_cancelled(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse(), [
            'phone'        => '0912345678',
            'confirmed_at' => now()->subDays(7),
        ]);

        $this->assertSame(0, $this->sweep());
        $this->assertSame('pending', $lead->fresh()->status);
    }

    /** Anything an admin moved by hand is somebody's work in progress. */
    public function test_a_lead_an_admin_moved_is_never_cancelled(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse(), ['status' => 'contacted']);

        $this->assertSame(0, $this->sweep());
        $this->assertSame('contacted', $lead->fresh()->status);
    }

    /** Old rows from before the screening existed are a different population. */
    public function test_a_lead_with_no_screening_is_never_cancelled(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse(), [
            'screened_at'      => null,
            'screening_score'  => null,
            'screen_timeline'  => null,
            'screen_budget'    => null,
            'screen_authority' => null,
            'screen_pain'      => null,
            'screen_next_step' => null,
        ]);

        $this->assertSame(0, $this->sweep());
        $this->assertSame('pending', $lead->fresh()->status);
    }

    public function test_a_declined_application_stays_declined(): void
    {
        $lead = $this->abandoned($this->makeHighTicketCourse(), [
            'status'      => 'declined',
            'declined_at' => now()->subDays(8),
        ]);

        $this->assertSame(0, $this->sweep());
        $this->assertSame('declined', $lead->fresh()->status);
    }

    /** Coming back and re-answering the gate must not leave them at 已取消. */
    public function test_re_screening_revives_a_swept_application(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->abandoned($course);
        $this->sweep();

        $this->postJson("/course/{$course->id}/screen", [
            'name'             => 'Applicant',
            'email'            => 'applicant@example.com',
            'screen_timeline'  => 'immediate',
            'screen_budget'    => '10k_50k',
            'screen_authority' => 'self',
            'screen_pain'      => 'moderate',
            'screen_next_step' => 'start_now',
            'screen_ack'       => true,
        ])->assertOk()->assertJson(['passed' => true]);

        $this->assertSame('pending', $lead->fresh()->status);
    }

    public function test_the_command_reports_what_it_cancelled(): void
    {
        $this->abandoned($this->makeHighTicketCourse());

        $this->artisan('booking:cancel-stale-applications')
            ->expectsOutputToContain('已將 1 筆未完成的申請歸類為已取消')
            ->assertSuccessful();
    }
}

<?php

namespace Tests\Feature\HighTicket;

use App\Mail\TemplatedMail;
use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 011 US26 — one nudge for applications that cleared the gate and stopped.
 *
 * The screening records everybody at step 1 (FR-125), so these rows exist by
 * design. What is under test is that the mail finds exactly them: not the
 * declined, not the ones who finished, not the archive, and never twice.
 */
class ApplicationResumeReminderTest extends TestCase
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

    private function seedTemplate(): void
    {
        EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_application_resume'], [
            'name'       => '申請未完成提醒',
            'event_type' => 'high_ticket_application_resume',
            'subject'    => '【還差幾步】{{course_name}}',
            'body_md'    => 'Hi {{user_name}}，接著填：{{booking_url}}',
        ]);
    }

    /** A lead that cleared step 1 and never reached step 2. */
    private function abandoned(Course $course, array $overrides = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'             => 'Applicant',
            'email'            => 'applicant@example.com',
            'course_id'        => $course->id,
            'status'           => 'pending',
            'booked_at'        => now()->subHours(5),
            'screened_at'      => now()->subHours(5),
            'screening_score'  => 7,
            'screen_timeline'  => 'immediate',
            'screen_budget'    => '10k_50k',
            'screen_authority' => 'self',
            'screen_pain'      => 'moderate',
            'screen_next_step' => 'start_now',
        ], $overrides));
    }

    private function sendNudges(): int
    {
        return app(HighTicketBookingService::class)->sendApplicationResumeReminders();
    }

    public function test_an_abandoned_application_gets_one_nudge_with_a_resume_link(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $lead = $this->abandoned($course);

        $this->assertSame(1, $this->sendNudges());

        $token = $lead->fresh()->resume_token;
        $this->assertNotNull($token, '沒有 token 就沒有回來的路');

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) =>
            str_contains($mail->htmlBody, "/course/{$course->slug}?resume={$token}"));

        $this->assertNotNull($lead->fresh()->resume_reminder_sent_at);
    }

    /** Once, ever — this is a nudge, not a campaign. */
    public function test_the_nudge_is_never_sent_twice(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $this->abandoned($course);

        $this->assertSame(1, $this->sendNudges());
        $this->assertSame(0, $this->sendNudges(), '第二次執行不得再寄');

        $this->assertCount(1, Mail::sent(TemplatedMail::class));
    }

    public function test_a_fresh_screening_is_left_alone(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        // Still in the tab, quite possibly still typing.
        $this->abandoned($course, ['screened_at' => now()->subMinutes(30)]);

        $this->assertSame(0, $this->sendNudges());
        Mail::assertNothingSent();
    }

    /** Turning this on must not mail every abandoned screening in the archive. */
    public function test_an_old_screening_is_not_nudged(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $this->abandoned($course, ['screened_at' => now()->subDays(20)]);

        $this->assertSame(0, $this->sendNudges());
        Mail::assertNothingSent();
    }

    public function test_a_declined_application_is_never_nudged(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $this->abandoned($course, ['status' => 'declined', 'declined_at' => now()]);

        $this->assertSame(0, $this->sendNudges());
        Mail::assertNothingSent();
    }

    /** Having a phone means they got past step 2 — a different situation. */
    public function test_a_completed_application_is_not_nudged(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $this->abandoned($course, ['phone' => '0912345678']);

        $this->assertSame(0, $this->sendNudges());
        Mail::assertNothingSent();
    }

    /** An admin working the lead by hand does not need the robot writing too. */
    public function test_an_admin_moved_lead_is_not_nudged(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $this->abandoned($course, ['status' => 'contacted']);

        $this->assertSame(0, $this->sendNudges());
        Mail::assertNothingSent();
    }

    public function test_a_missing_template_sends_nothing_and_does_not_stamp(): void
    {
        EmailTemplate::query()->delete();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $lead = $this->abandoned($course);

        $this->assertSame(0, $this->sendNudges());
        $this->assertNull($lead->fresh()->resume_reminder_sent_at, '沒寄出就不能蓋章，否則永遠補不回來');
    }

    public function test_the_command_reports_what_it_sent(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $this->abandoned($this->makeHighTicketCourse());

        $this->artisan('booking:send-resume-reminders')
            ->expectsOutputToContain('已寄出 1 封續填提醒')
            ->assertSuccessful();
    }

    // ── 回站落點（FR-137） ─────────────────────────────────────────────────

    /** The link drops them at step 2 with the gate already cleared. */
    public function test_the_resume_link_reports_the_screening_as_cleared(): void
    {
        $this->seedTemplate();
        Mail::fake();
        $course = $this->makeHighTicketCourse();
        $lead = $this->abandoned($course);
        $this->sendNudges();

        $this->get("/course/{$course->slug}?resume={$lead->fresh()->resume_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('bookingDraft.screening_cleared', true)
                ->where('bookingDraft.resume', false)
                ->where('bookingDraft.email', 'applicant@example.com'));
    }

    /**
     * A declined lead reloading its own page must not be handed a cleared gate
     * on the strength of its own prefilled answers.
     */
    public function test_failing_answers_are_never_reported_as_cleared(): void
    {
        $course = $this->makeHighTicketCourse();
        $lead = $this->abandoned($course, [
            'status'          => 'declined',
            'declined_at'     => now(),
            'screen_budget'   => 'none',
            'screening_score' => 8,
            'resume_token'    => str_repeat('c', 64),
        ]);

        $this->get("/course/{$course->slug}?resume={$lead->resume_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('bookingDraft.screening_cleared', false));
    }
}

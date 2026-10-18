<?php

namespace Tests\Feature\HighTicket;

use App\Jobs\SendScreeningDeclineMailJob;
use App\Mail\TemplatedMail;
use App\Models\Course;
use App\Models\EmailSuppression;
use App\Models\EmailTemplate;
use App\Models\HighTicketLead;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 011 US43 / FR-260 — the refusal email an hour after the screening gate.
 *
 * What these protect: it is sent late (so nobody re-screening within the hour
 * gets it), at most once per lead, from the course's own body when there is one,
 * and as marketing mail so the suppression list applies.
 */
class ScreeningDeclineMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailTemplateSeeder::class);
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
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
        ], $overrides))->fresh();
    }

    private function payload(bool $passing, array $overrides = []): array
    {
        $answers = $passing
            ? [
                'screen_timeline'  => 'immediate',
                'screen_budget'    => '10k_50k',
                'screen_authority' => 'self',
                'screen_pain'      => 'severe',
                'screen_next_step' => 'start_now',
            ]
            : [
                'screen_timeline'  => 'exploring',
                'screen_budget'    => 'none',
                'screen_authority' => 'not_considered',
                'screen_pain'      => 'curious',
                'screen_next_step' => 'advice_only',
            ];

        return array_merge([
            'name'              => 'Applicant',
            'email'             => 'applicant@example.com',
            'screen_ack'        => true,
            'screen_experience' => '1_3y',
            'screen_learning'   => 'paid_course',
        ], $answers, $overrides);
    }

    private function declinedLead(Course $course, array $overrides = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'        => 'Applicant',
            'email'       => 'applicant@example.com',
            'course_id'   => $course->id,
            'status'      => 'declined',
            'declined_at' => now(),
            'screened_at' => now(),
            'booked_at'   => now(),
        ], $overrides));
    }

    // ── scheduling ──

    public function test_a_refusal_schedules_the_email_an_hour_later(): void
    {
        Queue::fake();
        $course = $this->makeCourse();

        $this->postJson("/course/{$course->id}/screen", $this->payload(false))
            ->assertOk()->assertJson(['passed' => false]);

        $lead = HighTicketLead::first();

        Queue::assertPushed(SendScreeningDeclineMailJob::class, function ($job) use ($lead) {
            return $job->leadId === $lead->id
                && $job->delay !== null
                && abs(now()->addMinutes(60)->diffInSeconds($job->delay)) < 5;
        });
    }

    public function test_a_refusal_of_an_existing_application_also_schedules_it(): void
    {
        Queue::fake();
        $course = $this->makeCourse();
        $lead = HighTicketLead::create([
            'name' => 'Applicant', 'email' => 'applicant@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->postJson("/course/{$course->id}/screen", $this->payload(false))->assertOk();

        Queue::assertPushed(SendScreeningDeclineMailJob::class, fn ($job) => $job->leadId === $lead->id);
    }

    public function test_a_pass_schedules_nothing(): void
    {
        Queue::fake();
        $course = $this->makeCourse();

        $this->postJson("/course/{$course->id}/screen", $this->payload(true))
            ->assertOk()->assertJson(['passed' => true]);

        Queue::assertNotPushed(SendScreeningDeclineMailJob::class);
    }

    public function test_a_lead_already_mailed_is_not_scheduled_again(): void
    {
        Queue::fake();
        $course = $this->makeCourse();
        $this->declinedLead($course, ['screening_decline_mailed_at' => now()->subDay()]);

        $this->postJson("/course/{$course->id}/screen", $this->payload(false))->assertOk();

        Queue::assertNotPushed(SendScreeningDeclineMailJob::class);
    }

    // ── the job ──

    public function test_the_job_sends_the_site_wide_template(): void
    {
        Mail::fake();
        $course = $this->makeCourse(['name' => '個人品牌實戰']);
        $lead = $this->declinedLead($course, ['name' => '小明']);

        (new SendScreeningDeclineMailJob($lead->id))->handle();

        $template = EmailTemplate::forEvent('high_ticket_screening_declined')->first();

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($template) {
            return $mail->hasTo('applicant@example.com')
                && $mail->emailSubject === str_replace('{{course_name}}', '個人品牌實戰', $template->subject)
                && str_contains($mail->textBody, '您好 小明')
                && str_contains($mail->textBody, '個人品牌實戰')
                && !str_contains($mail->textBody, '{{');
        });
        Mail::assertSentCount(1);
        $this->assertNotNull($lead->fresh()->screening_decline_mailed_at);
    }

    public function test_a_course_body_replaces_the_template_body_but_not_the_subject(): void
    {
        Mail::fake();
        $course = $this->makeCourse([
            'name'                         => '個人品牌實戰',
            'high_ticket_decline_email_md' => "嗨 {{user_name}}，先從**免費電子書**開始吧：https://example.com/ebook",
        ]);
        $lead = $this->declinedLead($course, ['name' => '小明']);

        (new SendScreeningDeclineMailJob($lead->id))->handle();

        $template = EmailTemplate::forEvent('high_ticket_screening_declined')->first();

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($template) {
            return $mail->emailSubject === str_replace('{{course_name}}', '個人品牌實戰', $template->subject)
                && str_contains($mail->textBody, '嗨 小明')
                && str_contains($mail->htmlBody, '<strong>免費電子書</strong>')
                && !str_contains($mail->textBody, '謝謝您申請');
        });
    }

    public function test_the_course_body_renders_as_markdown_even_when_the_template_is_html(): void
    {
        Mail::fake();
        EmailTemplate::forEvent('high_ticket_screening_declined')->update([
            'body_type' => 'html',
            'body_md'   => '<p>HTML template</p>',
        ]);
        $course = $this->makeCourse(['high_ticket_decline_email_md' => '**粗體**']);
        $lead = $this->declinedLead($course);

        (new SendScreeningDeclineMailJob($lead->id))->handle();

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $mail) => str_contains($mail->htmlBody, '<strong>粗體</strong>'));
        $this->assertSame('html', EmailTemplate::forEvent('high_ticket_screening_declined')->value('body_type'), '覆寫不可寫回模板');
    }

    public function test_nothing_is_sent_if_the_lead_passed_in_the_meantime(): void
    {
        Mail::fake();
        $course = $this->makeCourse();
        $lead = $this->declinedLead($course);
        $lead->update(['status' => 'pending', 'declined_at' => null]);

        (new SendScreeningDeclineMailJob($lead->id))->handle();

        Mail::assertNothingSent();
        $this->assertNull($lead->fresh()->screening_decline_mailed_at);
    }

    public function test_nothing_is_sent_once_the_booking_is_confirmed(): void
    {
        Mail::fake();
        $course = $this->makeCourse();
        $lead = $this->declinedLead($course, ['confirmed_at' => now()]);

        (new SendScreeningDeclineMailJob($lead->id))->handle();

        Mail::assertNothingSent();
    }

    public function test_two_queued_jobs_send_one_email(): void
    {
        Mail::fake();
        $course = $this->makeCourse();
        $lead = $this->declinedLead($course);

        (new SendScreeningDeclineMailJob($lead->id))->handle();
        (new SendScreeningDeclineMailJob($lead->id))->handle();

        Mail::assertSentCount(1);
    }

    public function test_a_missing_template_sends_nothing_and_leaves_the_lead_unclaimed(): void
    {
        Mail::fake();
        EmailTemplate::forEvent('high_ticket_screening_declined')->delete();
        $course = $this->makeCourse();
        $lead = $this->declinedLead($course);

        (new SendScreeningDeclineMailJob($lead->id))->handle();

        Mail::assertNothingSent();
        $this->assertNull($lead->fresh()->screening_decline_mailed_at);
    }

    public function test_a_failed_send_releases_the_claim_for_the_retry(): void
    {
        $course = $this->makeCourse();
        $lead = $this->declinedLead($course);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Resend down'));

        try {
            (new SendScreeningDeclineMailJob($lead->id))->handle();
            $this->fail('the exception must reach the queue so it retries');
        } catch (\RuntimeException $e) {
            $this->assertSame('Resend down', $e->getMessage());
        }

        $this->assertNull($lead->fresh()->screening_decline_mailed_at);
    }

    public function test_it_goes_out_as_marketing_mail_so_complaints_block_it(): void
    {
        $course = $this->makeCourse();
        EmailSuppression::create(['email' => 'complained@example.com', 'reason' => 'complaint', 'suppressed_at' => now()]);
        $blocked = $this->declinedLead($course, ['email' => 'complained@example.com']);
        $clean = $this->declinedLead($course, ['email' => 'clean@example.com']);

        (new SendScreeningDeclineMailJob($blocked->id))->handle();
        (new SendScreeningDeclineMailJob($clean->id))->handle();

        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();

        // A complaint only blocks marketing mail (D28), so the complained address
        // dropping out is the proof of the tag — the listener strips the header itself.
        $this->assertCount(1, $messages);
        $this->assertSame('clean@example.com', $messages->first()->getOriginalMessage()->getTo()[0]->getAddress());
    }

    // ── admin course form ──

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function updatePayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'name'                   => $course->name,
            'slug'                   => $course->slug,
            'tagline'                => $course->tagline,
            'description'            => $course->description,
            'price'                  => 58000,
            'instructor_name'        => $course->instructor_name,
            'type'                   => 'high_ticket',
            'content_category'       => 'monetization',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
            'high_ticket_screening'  => true,
        ], $overrides);
    }

    public function test_admin_saves_a_course_body_and_blank_becomes_null(): void
    {
        $admin = $this->admin();
        $course = $this->makeCourse();

        $this->actingAs($admin)
            ->put("/admin/courses/{$course->id}", $this->updatePayload($course, ['high_ticket_decline_email_md' => '先看電子書']))
            ->assertSessionHasNoErrors();
        $this->assertSame('先看電子書', $course->fresh()->high_ticket_decline_email_md);

        $this->actingAs($admin)
            ->put("/admin/courses/{$course->id}", $this->updatePayload($course, ['high_ticket_decline_email_md' => '   ']))
            ->assertSessionHasNoErrors();
        $this->assertNull($course->fresh()->high_ticket_decline_email_md);
    }

    public function test_admin_course_body_over_10000_characters_is_rejected(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->admin())
            ->put("/admin/courses/{$course->id}", $this->updatePayload($course, ['high_ticket_decline_email_md' => str_repeat('a', 10001)]))
            ->assertSessionHasErrors('high_ticket_decline_email_md');
    }

    public function test_admin_edit_page_carries_the_field_and_the_template_body_as_placeholder(): void
    {
        $course = $this->makeCourse(['high_ticket_decline_email_md' => '自訂內文']);
        $templateBody = EmailTemplate::forEvent('high_ticket_screening_declined')->value('body_md');

        $this->actingAs($this->admin())
            ->get("/admin/courses/{$course->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('course.high_ticket_decline_email_md', '自訂內文')
                ->where('defaultDeclineEmailMd', $templateBody));
    }
}

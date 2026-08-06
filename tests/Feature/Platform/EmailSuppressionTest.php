<?php

namespace Tests\Feature\Platform;

use App\Jobs\SendDripEmailJob;
use App\Mail\DripLessonMail;
use App\Mail\TemplatedMail;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\EmailSuppression;
use App\Models\HighTicketLead;
use App\Models\Lesson;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\DripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * 000 US9 — Resend bounce/complaint webhook → suppression list → mail gate.
 */
class EmailSuppressionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => 'Course ' . uniqid(),
            'slug'            => 'course-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 0,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ], $overrides));
    }

    private function bouncedWebhookPayload(array $to, string $bounceType = 'Permanent'): array
    {
        return [
            'type' => 'email.bounced',
            'data' => [
                'to'     => $to,
                'bounce' => [
                    'type'    => $bounceType,
                    'subType' => 'General',
                    'message' => 'The email account does not exist.',
                ],
            ],
        ];
    }

    private function complainedWebhookPayload(array $to): array
    {
        return [
            'type' => 'email.complained',
            'data' => [
                'to' => $to,
            ],
        ];
    }

    // ── webhook → suppression facts ──

    public function test_permanent_bounce_creates_suppression_row(): void
    {
        $this->postJson('/resend/webhook', $this->bouncedWebhookPayload(['dead@example.com']))
            ->assertOk();

        $row = EmailSuppression::where('email', 'dead@example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame('bounce', $row->reason);
    }

    public function test_transient_bounce_does_not_create_suppression_row(): void
    {
        $this->postJson('/resend/webhook', $this->bouncedWebhookPayload(['full-inbox@example.com'], 'Transient'))
            ->assertOk();

        $this->assertNull(EmailSuppression::where('email', 'full-inbox@example.com')->first());
    }

    public function test_undetermined_bounce_does_not_create_suppression_row(): void
    {
        $this->postJson('/resend/webhook', $this->bouncedWebhookPayload(['unclear@example.com'], 'Undetermined'))
            ->assertOk();

        $this->assertNull(EmailSuppression::where('email', 'unclear@example.com')->first());
    }

    public function test_complaint_creates_suppression_row(): void
    {
        $this->postJson('/resend/webhook', $this->complainedWebhookPayload(['angry@example.com']))
            ->assertOk();

        $row = EmailSuppression::where('email', 'angry@example.com')->first();
        $this->assertNotNull($row);
        $this->assertSame('complaint', $row->reason);
    }

    public function test_duplicate_bounce_event_is_idempotent(): void
    {
        $payload = $this->bouncedWebhookPayload(['repeat@example.com']);

        $this->postJson('/resend/webhook', $payload)->assertOk();
        $this->postJson('/resend/webhook', $payload)->assertOk();

        $this->assertSame(1, EmailSuppression::where('email', 'repeat@example.com')->count());
    }

    public function test_complaint_upgrades_to_bounce_but_bounce_never_downgrades(): void
    {
        $email = 'upgrade@example.com';

        $this->postJson('/resend/webhook', $this->complainedWebhookPayload([$email]))->assertOk();
        $this->assertSame('complaint', EmailSuppression::reasonFor($email));

        $this->postJson('/resend/webhook', $this->bouncedWebhookPayload([$email]))->assertOk();
        $this->assertSame('bounce', EmailSuppression::reasonFor($email));

        // A later complaint from the same dead address must not undo the bounce.
        $this->postJson('/resend/webhook', $this->complainedWebhookPayload([$email]))->assertOk();
        $this->assertSame('bounce', EmailSuppression::reasonFor($email));
        $this->assertSame(1, EmailSuppression::where('email', $email)->count());
    }

    // ── mail gating ──

    public function test_marketing_mail_is_blocked_for_bounced_recipient(): void
    {
        EmailSuppression::create(['email' => 'bounced@example.com', 'reason' => 'bounce', 'suppressed_at' => now()]);

        $result = Mail::to('bounced@example.com')->send(new DripLessonMail(
            lessonTitle: 'Lesson 1',
            htmlContent: '<p>hi</p>',
            hasVideo: false,
            classroomUrl: 'https://example.test/classroom',
            unsubscribeUrl: 'https://example.test/unsub',
            courseName: 'Course',
        ));

        $this->assertNull($result);
    }

    public function test_marketing_mail_is_blocked_for_complained_recipient(): void
    {
        EmailSuppression::create(['email' => 'complained@example.com', 'reason' => 'complaint', 'suppressed_at' => now()]);

        $result = Mail::to('complained@example.com')->send(new DripLessonMail(
            lessonTitle: 'Lesson 1',
            htmlContent: '<p>hi</p>',
            hasVideo: false,
            classroomUrl: 'https://example.test/classroom',
            unsubscribeUrl: 'https://example.test/unsub',
            courseName: 'Course',
        ));

        $this->assertNull($result);
    }

    public function test_marketing_mail_still_sends_to_clean_recipient(): void
    {
        $result = Mail::to('clean@example.com')->send(new DripLessonMail(
            lessonTitle: 'Lesson 1',
            htmlContent: '<p>hi</p>',
            hasVideo: false,
            classroomUrl: 'https://example.test/classroom',
            unsubscribeUrl: 'https://example.test/unsub',
            courseName: 'Course',
        ));

        $this->assertNotNull($result);
    }

    public function test_transactional_mail_is_blocked_for_bounced_recipient(): void
    {
        EmailSuppression::create(['email' => 'bounced@example.com', 'reason' => 'bounce', 'suppressed_at' => now()]);

        $result = Mail::to('bounced@example.com')->send(new TemplatedMail('主旨', '<p>body</p>', 'body'));

        $this->assertNull($result);
    }

    public function test_transactional_mail_still_sends_to_complained_recipient(): void
    {
        EmailSuppression::create(['email' => 'complained@example.com', 'reason' => 'complaint', 'suppressed_at' => now()]);

        // Complaint only blocks marketing mail (D28) — the recipient still needs
        // to receive mail they themselves triggered, e.g. a booking confirmation.
        $result = Mail::to('complained@example.com')->send(new TemplatedMail('主旨', '<p>body</p>', 'body'));

        $this->assertNotNull($result);
    }

    // ── drip sequence ──

    public function test_drip_processing_skips_suppressed_subscription(): void
    {
        Queue::fake();

        $course = $this->makeCourse(['course_type' => 'drip', 'drip_interval_days' => 3]);
        Lesson::create([
            'course_id'      => $course->id,
            'title'          => 'L0',
            'video_platform' => 'vimeo',
            'video_id'       => '1032766965',
            'sort_order'     => 0,
        ]);
        Lesson::create([
            'course_id'      => $course->id,
            'title'          => 'L1',
            'video_platform' => 'vimeo',
            'video_id'       => '1032766965',
            'sort_order'     => 1,
        ]);

        $user = User::factory()->create(['email' => 'suppressed-subscriber@example.com']);
        EmailSuppression::create(['email' => $user->email, 'reason' => 'bounce', 'suppressed_at' => now()]);

        $subscription = DripSubscription::create([
            'user_id'           => $user->id,
            'course_id'         => $course->id,
            'subscribed_at'     => now()->subDays(10),
            'emails_sent'       => 0,
            'status'            => 'active',
            'unsubscribe_token' => uniqid(),
        ]);
        $subscription->load('user', 'course');

        $sent = app(DripService::class)->processSubscription($subscription);

        $this->assertSame(0, $sent);
        $this->assertSame(0, $subscription->fresh()->emails_sent);
        Queue::assertNotPushed(SendDripEmailJob::class);
    }

    // ── admin visibility ──

    public function test_leads_page_shows_suppression_status(): void
    {
        $course = $this->makeCourse();
        HighTicketLead::create([
            'name' => 'Dead End', 'email' => 'dead-lead@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);
        EmailSuppression::create(['email' => 'dead-lead@example.com', 'reason' => 'bounce', 'suppressed_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertOk();

        $this->assertSame(
            'bounce',
            $response->viewData('page')['props']['suppressionsByEmail']['dead-lead@example.com']
        );
    }

    // ── webhook secret / auth ──

    public function test_admin_can_save_resend_webhook_secret_and_empty_keeps_old_value(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/settings/payment', [
            'resend_webhook_secret' => 'whsec_testsecret',
        ])->assertRedirect();

        $this->assertSame('whsec_testsecret', SiteSetting::get('resend_webhook_secret'));

        $this->actingAs($admin)->post('/admin/settings/payment', [
            'resend_webhook_secret' => '',
        ])->assertRedirect();

        $this->assertSame('whsec_testsecret', SiteSetting::get('resend_webhook_secret'));
    }

    public function test_webhook_rejects_unsigned_request_when_secret_is_configured(): void
    {
        SiteSetting::set('resend_webhook_secret', 'whsec_' . base64_encode('a-real-secret'));

        $this->postJson('/resend/webhook', $this->bouncedWebhookPayload(['victim@example.com']))
            ->assertStatus(403);

        $this->assertNull(EmailSuppression::where('email', 'victim@example.com')->first());
    }

    public function test_webhook_accepts_unsigned_request_when_secret_is_not_configured(): void
    {
        // Regression guard for FR-018: no secret configured means the endpoint
        // has no authentication at all — noted as an accepted risk (D27), but a
        // future change accidentally requiring a secret should not break this.
        $this->postJson('/resend/webhook', $this->bouncedWebhookPayload(['no-secret@example.com']))
            ->assertOk();

        $this->assertNotNull(EmailSuppression::where('email', 'no-secret@example.com')->first());
    }
}

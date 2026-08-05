<?php

namespace Tests\Feature\Drip;

use App\Models\Course;
use App\Models\DripConversionTarget;
use App\Models\DripSubscription;
use App\Models\EmailTemplate;
use App\Models\Lesson;
use App\Models\User;
use App\Services\DripService;
use App\Services\HighTicketBookingService;
use App\Services\HighTicketLeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 010 US13/US14 — reaching the funnel goal stops the sequence.
 *
 * Booking a target high-ticket course marks the subscription 'booked';
 * actually buying it (any channel) marks it 'converted'. Both stop sending,
 * and neither unlocks the remaining lessons any more — the unlock cursor
 * (emails_sent) freezes. Pre-change converted subscribers keep full access
 * through the unlock_all flag.
 */
class FunnelStopTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

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

    private function makeDripCourse(): Course
    {
        return $this->makeCourse(['course_type' => 'drip', 'drip_interval_days' => 3]);
    }

    private function makeTargetCourse(array $overrides = []): Course
    {
        return $this->makeCourse(array_merge(['price' => 50000], $overrides));
    }

    /**
     * sqlite's CHECK on the type enum predates 'high_ticket' (that ALTER is
     * MySQL-only), so the type is set in memory — booking only reads it.
     */
    private function makeHighTicketCourse(): Course
    {
        $course = $this->makeTargetCourse(['high_ticket_hide_price' => true]);
        $course->type = 'high_ticket';

        return $course;
    }

    private function makeLessons(Course $course, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Lesson::create([
                'course_id'      => $course->id,
                'title'          => "L{$i}",
                'video_platform' => 'vimeo',
                'video_id'       => '1032766965',
                'sort_order'     => $i,
            ]);
        }
    }

    private function subscribe(Course $drip, User $user, int $emailsSent = 2, string $status = 'active'): DripSubscription
    {
        return DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $drip->id,
            'subscribed_at' => now()->subDays(6),
            'emails_sent'   => $emailsSent,
            'status'        => $status,
        ]);
    }

    private function link(Course $drip, Course $target): void
    {
        DripConversionTarget::create([
            'drip_course_id'   => $drip->id,
            'target_course_id' => $target->id,
        ]);
    }

    private function bookingTemplate(): void
    {
        EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_booking_confirmation'], [
            'name'       => '預約確認信',
            'event_type' => 'high_ticket_booking_confirmation',
            'subject'    => '{{course_name}} 預約確認',
            'body_md'    => 'Hi {{user_name}}',
        ]);
    }

    public function test_high_ticket_booking_marks_subscription_booked(): void
    {
        Mail::fake();
        $this->bookingTemplate();

        $drip   = $this->makeDripCourse();
        $target = $this->makeHighTicketCourse();
        $this->link($drip, $target);

        $user = User::create(['email' => 'booker@example.com', 'role' => 'member']);
        $sub  = $this->subscribe($drip, $user);

        // The sequence stops when the booking is confirmed, not when the form
        // is submitted (011 D35).
        $result = $this->applyAndConfirm($target);

        $this->assertSame('confirmed', $result['state']);
        $this->assertSame('booked', $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->status_changed_at);
    }

    public function test_booking_with_unknown_email_does_not_fail(): void
    {
        Mail::fake();
        $this->bookingTemplate();

        $drip   = $this->makeDripCourse();
        $target = $this->makeHighTicketCourse();
        $this->link($drip, $target);

        $result = $this->applyAndConfirm($target, ['name' => 'Stranger', 'email' => 'nobody@example.com']);

        $this->assertSame('confirmed', $result['state']);
        $this->assertDatabaseHas('high_ticket_leads', ['email' => 'nobody@example.com']);
    }

    public function test_booked_subscription_upgrades_to_converted_on_purchase(): void
    {
        $drip   = $this->makeDripCourse();
        $target = $this->makeTargetCourse();
        $this->link($drip, $target);

        $user = $this->userWith('upgrade@example.com');
        $sub  = $this->subscribe($drip, $user, status: 'booked');

        app(DripService::class)->checkAndConvert($user, $target);

        $this->assertSame('converted', $sub->fresh()->status);
    }

    public function test_booked_subscription_stops_receiving_emails(): void
    {
        $drip = $this->makeDripCourse();
        $this->makeLessons($drip, 5);

        $user = $this->userWith('nomail@example.com');
        $sub  = $this->subscribe($drip, $user, emailsSent: 2, status: 'booked');

        // 6 days / 3-day interval → 3 lessons should be due, but the funnel is over.
        $this->assertSame(0, app(DripService::class)->processDailyEmails());
        $this->assertSame(2, $sub->fresh()->emails_sent);
    }

    public function test_new_conversion_freezes_unlock_at_emails_sent(): void
    {
        $drip = $this->makeDripCourse();
        $this->makeLessons($drip, 5);
        $target = $this->makeTargetCourse();
        $this->link($drip, $target);

        $user = $this->userWith('frozen@example.com');
        $sub  = $this->subscribe($drip, $user, emailsSent: 2);

        app(DripService::class)->checkAndConvert($user, $target);
        $sub->refresh();

        $service = app(DripService::class);
        $lessons = $drip->lessons()->orderBy('sort_order')->get();

        $this->assertFalse($sub->unlock_all);
        $this->assertTrue($service->isLessonUnlocked($sub, $lessons[1]));   // already sent
        $this->assertFalse($service->isLessonUnlocked($sub, $lessons[2]));  // not sent yet
        $this->assertFalse($service->isLessonUnlocked($sub, $lessons[4]));
    }

    public function test_legacy_converted_subscription_keeps_full_access(): void
    {
        $drip = $this->makeDripCourse();
        $this->makeLessons($drip, 5);

        $user = $this->userWith('legacy@example.com');
        $sub  = $this->subscribe($drip, $user, emailsSent: 2, status: 'converted');
        $sub->update(['unlock_all' => true]);

        $service = app(DripService::class);
        $lessons = $drip->lessons()->orderBy('sort_order')->get();

        $this->assertTrue($service->isLessonUnlocked($sub, $lessons[4]));
    }

    public function test_admin_gift_triggers_conversion(): void
    {
        Mail::fake();

        $drip   = $this->makeDripCourse();
        $target = $this->makeTargetCourse();
        $this->link($drip, $target);

        $member = $this->userWith('gifted@example.com');
        $sub    = $this->subscribe($drip, $member);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->postJson('/admin/members/gift-course', [
                'member_ids' => [$member->id],
                'course_id'  => $target->id,
            ])
            ->assertOk();

        $this->assertSame('converted', $sub->fresh()->status);
    }

    public function test_lead_conversion_triggers_drip_conversion(): void
    {
        $drip   = $this->makeDripCourse();
        $target = $this->makeTargetCourse();
        $this->link($drip, $target);

        $user = $this->userWith('closed@example.com');
        $sub  = $this->subscribe($drip, $user);

        $lead = \App\Models\HighTicketLead::create([
            'name'      => 'Closed Deal',
            'email'     => 'closed@example.com',
            'course_id' => $target->id,
            'status'    => 'contacted',
            'booked_at' => now(),
        ]);

        app(HighTicketLeadService::class)->convertLead($lead, $target->id, 38000);

        $this->assertSame('converted', $sub->fresh()->status);
    }

    private function userWith(string $email): User
    {
        return User::create(['email' => $email, 'role' => 'member']);
    }
}

<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\User;
use App\Services\HighTicketLeadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * 011 US39 — today / 7-day / 30-day new bookings, shown beside the drip
 * subscriber counts on the dashboard and the leads page.
 *
 * A booking counts at `confirmed_at`, once per email, applications only
 * (FR-234, FR-235); the window edges are shared with the subscriber counts
 * (FR-236).
 */
class NewBookingStatsTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        // 2026-10-07 12:00 Taipei. Taipei "today" starts at 2026-10-06 16:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-10-07 04:00:00', 'UTC'));

        $this->course = Course::create([
            'name' => '主課程', 'slug' => 'c-' . uniqid(), 'tagline' => 't', 'description' => 'd',
            'price' => 50000, 'instructor_name' => 'I', 'type' => 'high_ticket', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function lead(string $email, ?string $confirmedUtc, array $attributes = []): HighTicketLead
    {
        return HighTicketLead::create(array_merge([
            'name'         => '王小明',
            'email'        => $email,
            'phone'        => '0912345678',
            'course_id'    => $this->course->id,
            'status'       => 'pending',
            'booked_at'    => now(),
            'confirmed_at' => $confirmedUtc ? Carbon::parse($confirmedUtc, 'UTC') : null,
        ], $attributes));
    }

    private function counts(): array
    {
        return app(HighTicketLeadService::class)->newBookingCounts();
    }

    public function test_today_follows_the_taipei_calendar_day(): void
    {
        $this->lead('a@example.com', '2026-10-06 15:59:00');
        $this->lead('b@example.com', '2026-10-06 16:00:00');

        $this->assertSame(
            ['today' => 1, 'last_7_days' => 2, 'last_30_days' => 2],
            $this->counts(),
        );
    }

    public function test_window_edges_are_whole_taipei_days(): void
    {
        $this->lead('a@example.com', '2026-09-30 16:00:00');
        $this->lead('b@example.com', '2026-09-30 15:59:00');
        $this->lead('c@example.com', '2026-09-07 16:00:00');
        $this->lead('d@example.com', '2026-09-07 15:59:00');

        $this->assertSame(
            ['today' => 0, 'last_7_days' => 1, 'last_30_days' => 3],
            $this->counts(),
        );
    }

    public function test_unconfirmed_applications_do_not_count(): void
    {
        $this->lead('a@example.com', null);

        $this->assertSame(0, $this->counts()['last_30_days']);
    }

    public function test_self_booked_credit_consultations_do_not_count(): void
    {
        $this->lead('a@example.com', '2026-10-07 01:00:00', ['kind' => 'credit']);

        $this->assertSame(0, $this->counts()['today']);
    }

    public function test_one_email_counts_once(): void
    {
        $this->lead('same@example.com', '2026-10-07 01:00:00');
        $this->lead('same@example.com', '2026-10-07 02:00:00');

        $this->assertSame(1, $this->counts()['today']);
    }

    public function test_later_cancellation_or_decline_still_counts(): void
    {
        $this->lead('a@example.com', '2026-10-07 01:00:00', ['status' => 'cancelled', 'cancelled_at' => now()]);
        $this->lead('b@example.com', '2026-10-07 01:00:00', ['status' => 'declined']);

        $this->assertSame(2, $this->counts()['today']);
    }

    public function test_dashboard_and_every_leads_tab_receive_both_stats(): void
    {
        $this->lead('a@example.com', '2026-10-07 01:00:00');
        $admin = User::factory()->create(['role' => 'admin']);

        $urls = ['/admin', '/admin/high-ticket-leads?tab=booking', '/admin/high-ticket-leads?tab=subscribers'];

        foreach ($urls as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('newBookingStats.today', 1)
                    ->has('newSubscriberStats'));
        }
    }
}

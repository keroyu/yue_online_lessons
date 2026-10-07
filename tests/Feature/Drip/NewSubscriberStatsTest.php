<?php

namespace Tests\Feature\Drip;

use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\User;
use App\Services\DripService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * 010 US19 — today / 7-day / 30-day new subscriber counts on the admin
 * dashboard and the leads page.
 *
 * Counts are distinct people across every drip course (FR-043), bucketed by
 * the Taipei calendar day (FR-044), and ignore the current status (FR-045).
 */
class NewSubscriberStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 2026-10-07 12:00 Taipei. Taipei "today" starts at 2026-10-06 16:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-10-07 04:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeDripCourse(string $slug): Course
    {
        return Course::create([
            'name'               => "Drip {$slug}",
            'slug'               => $slug,
            'tagline'            => 'tag',
            'description'        => 'desc',
            'price'              => 0,
            'instructor_name'    => 'Tester',
            'type'               => 'lecture',
            'status'             => 'selling',
            'course_type'        => 'drip',
            'drip_interval_days' => 3,
            'is_published'       => true,
            'is_visible'         => true,
            'payment_gateway'    => 'payuni',
        ]);
    }

    private function subscribe(User $user, Course $course, string $utc, string $status = 'active'): void
    {
        DripSubscription::create([
            'user_id'       => $user->id,
            'course_id'     => $course->id,
            'subscribed_at' => Carbon::parse($utc, 'UTC'),
            'status'        => $status,
        ]);
    }

    private function counts(): array
    {
        return app(DripService::class)->newSubscriberCounts();
    }

    public function test_today_follows_the_taipei_calendar_day(): void
    {
        $course = $this->makeDripCourse('a');

        // 23:59 Taipei yesterday — not today, but within 7 days.
        $this->subscribe(User::factory()->create(), $course, '2026-10-06 15:59:00');
        // 00:00 Taipei today.
        $this->subscribe(User::factory()->create(), $course, '2026-10-06 16:00:00');

        $this->assertSame(
            ['today' => 1, 'last_7_days' => 2, 'last_30_days' => 2],
            $this->counts(),
        );
    }

    public function test_window_edges_are_whole_taipei_days(): void
    {
        $course = $this->makeDripCourse('a');

        // 7-day window starts 2026-10-01 00:00 Taipei = 2026-09-30 16:00 UTC.
        $this->subscribe(User::factory()->create(), $course, '2026-09-30 16:00:00');
        $this->subscribe(User::factory()->create(), $course, '2026-09-30 15:59:00');
        // 30-day window starts 2026-09-08 00:00 Taipei = 2026-09-07 16:00 UTC.
        $this->subscribe(User::factory()->create(), $course, '2026-09-07 16:00:00');
        $this->subscribe(User::factory()->create(), $course, '2026-09-07 15:59:00');

        $this->assertSame(
            ['today' => 0, 'last_7_days' => 1, 'last_30_days' => 3],
            $this->counts(),
        );
    }

    public function test_one_person_claiming_several_courses_counts_once(): void
    {
        $a = $this->makeDripCourse('a');
        $b = $this->makeDripCourse('b');
        $user = User::factory()->create();

        $this->subscribe($user, $a, '2026-10-07 01:00:00');
        $this->subscribe($user, $b, '2026-10-07 02:00:00');

        $this->assertSame(
            ['today' => 1, 'last_7_days' => 1, 'last_30_days' => 1],
            $this->counts(),
        );
    }

    public function test_current_status_does_not_matter(): void
    {
        $course = $this->makeDripCourse('a');

        $this->subscribe(User::factory()->create(), $course, '2026-10-07 01:00:00', 'unsubscribed');
        $this->subscribe(User::factory()->create(), $course, '2026-10-07 01:00:00', 'converted');

        $this->assertSame(2, $this->counts()['today']);
    }

    public function test_dashboard_receives_the_stats(): void
    {
        $this->subscribe(User::factory()->create(), $this->makeDripCourse('a'), '2026-10-07 01:00:00');

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('newSubscriberStats.today', 1)
                ->where('newSubscriberStats.last_7_days', 1)
                ->where('newSubscriberStats.last_30_days', 1));
    }

    public function test_every_leads_tab_receives_the_stats(): void
    {
        $this->subscribe(User::factory()->create(), $this->makeDripCourse('a'), '2026-10-07 01:00:00');
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['booking', 'subscribers'] as $tab) {
            $this->actingAs($admin)
                ->get("/admin/high-ticket-leads?tab={$tab}")
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('newSubscriberStats.today', 1));
        }
    }
}

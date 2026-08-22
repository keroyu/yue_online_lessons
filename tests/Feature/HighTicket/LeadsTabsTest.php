<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\HighTicketLead;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 011 US8 — the leads admin page hosts two tabs (booking list / subscriber list).
 *
 * The tab is driven by ?tab= so the URL is shareable, and only the active tab's
 * data is assembled (FR-022). The subscriber tab picks one drip course from a
 * dropdown that must never expose non-drip courses (FR-023), and the two tabs
 * keep their filter params in separate namespaces (FR-024).
 */
class LeadsTabsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function consultant(): User
    {
        return User::factory()->create(['role' => 'member', 'is_sales_consultant' => true]);
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

    private function makeDripCourse(array $overrides = []): Course
    {
        return $this->makeCourse(array_merge([
            'course_type'        => 'drip',
            'drip_interval_days' => 3,
        ], $overrides));
    }

    private function subscribe(Course $course, string $email, string $status = 'active'): DripSubscription
    {
        $user = User::factory()->create(['email' => $email]);

        return DripSubscription::create([
            'user_id'           => $user->id,
            'course_id'         => $course->id,
            'subscribed_at'     => now(),
            'emails_sent'       => 1,
            'status'            => $status,
            'status_changed_at' => now(),
            'unsubscribe_token' => uniqid(),
        ]);
    }

    public function test_default_tab_is_booking_and_skips_subscriber_data(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/HighTicketLeads/Index')
                ->where('tab', 'booking')
                ->where('subscriberData', null)
                ->has('leads'));
    }

    public function test_unknown_tab_value_falls_back_to_booking(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?tab=nonsense')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('tab', 'booking'));
    }

    public function test_booking_tab_still_filters_by_status_and_course(): void
    {
        $course = $this->makeCourse();
        HighTicketLead::create([
            'name' => 'Wanted', 'email' => 'wanted@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);
        HighTicketLead::create([
            'name' => 'Filtered', 'email' => 'filtered@example.com',
            'course_id' => $course->id, 'status' => 'closed', 'booked_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?status=pending')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'booking')
                ->has('leads.data', 1)
                ->where('leads.data.0.email', 'wanted@example.com'));
    }

    public function test_subscribers_tab_returns_subscriber_data_for_the_selected_course(): void
    {
        $drip = $this->makeDripCourse();
        Lesson::create([
            'course_id' => $drip->id, 'title' => 'L0',
            'video_platform' => 'vimeo', 'video_id' => '1032766965', 'sort_order' => 0,
        ]);
        $this->subscribe($drip, 'sub@example.com');

        $this->actingAs($this->admin())
            ->get("/admin/high-ticket-leads?tab=subscribers&sub_course={$drip->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'subscribers')
                ->where('subscriberData.course.id', $drip->id)
                ->where('subscriberData.course.total_lessons', 1)
                ->where('subscriberData.stats.total', 1)
                ->where('subscriberData.stats.active', 1)
                ->has('subscriberData.subscribers.data', 1)
                ->has('subscriberData.lessonStats', 1));
    }

    public function test_course_dropdown_only_lists_drip_courses(): void
    {
        $drip = $this->makeDripCourse(['name' => 'Drip One']);
        $this->makeCourse(['name' => 'Plain Course']);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?tab=subscribers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('dripCourseOptions', 1)
                ->where('dripCourseOptions.0.id', $drip->id));
    }

    public function test_non_drip_sub_course_falls_back_to_the_first_drip_course(): void
    {
        $drip = $this->makeDripCourse();
        $plain = $this->makeCourse();
        $this->subscribe($plain, 'leak@example.com');

        $this->actingAs($this->admin())
            ->get("/admin/high-ticket-leads?tab=subscribers&sub_course={$plain->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('subscriberData.course.id', $drip->id)
                ->has('subscriberData.subscribers.data', 0));
    }

    public function test_subscribers_tab_without_any_drip_course_renders_empty(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?tab=subscribers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'subscribers')
                ->where('subscriberData', null)
                ->has('dripCourseOptions', 0));
    }

    public function test_sub_status_filters_subscribers(): void
    {
        $drip = $this->makeDripCourse();
        $this->subscribe($drip, 'active@example.com', 'active');
        $this->subscribe($drip, 'gone@example.com', 'unsubscribed');

        $this->actingAs($this->admin())
            ->get("/admin/high-ticket-leads?tab=subscribers&sub_course={$drip->id}&sub_status=unsubscribed")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('subscriberData.subscribers.data', 1)
                ->where('subscriberData.subscribers.data.0.user.email', 'gone@example.com')
                // Stats stay unfiltered so the summary cards always show the whole course.
                ->where('subscriberData.stats.total', 2));
    }

    public function test_sales_consultant_can_open_both_tabs(): void
    {
        $drip = $this->makeDripCourse();
        $this->subscribe($drip, 'sub@example.com');
        $consultant = $this->consultant();

        $this->actingAs($consultant)->get('/admin/high-ticket-leads')->assertOk();

        $this->actingAs($consultant)
            ->get("/admin/high-ticket-leads?tab=subscribers&sub_course={$drip->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('subscriberData.subscribers.data', 1));
    }

    /**
     * 011 US3 — the status pills show each status' share of the funnel, so the
     * counts behind them must ignore the status filter: clicking 待面談 may not
     * turn 待面談 into 100%.
     */
    public function test_status_counts_ignore_the_status_filter(): void
    {
        $course = $this->makeCourse();
        foreach (['pending', 'pending', 'pending', 'contacted'] as $i => $status) {
            HighTicketLead::create([
                'name' => "Lead {$i}", 'email' => "lead{$i}@example.com",
                'course_id' => $course->id, 'status' => $status, 'booked_at' => now(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?status=pending')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 3)
                ->where('statusCounts.pending', 3)
                ->where('statusCounts.contacted', 1));
    }

    public function test_status_counts_follow_the_search_and_course_filter(): void
    {
        $wanted = $this->makeCourse();
        $other  = $this->makeCourse();

        HighTicketLead::create([
            'name' => 'In scope', 'email' => 'in@example.com',
            'course_id' => $wanted->id, 'status' => 'pending', 'booked_at' => now(),
        ]);
        HighTicketLead::create([
            'name' => 'Other course', 'email' => 'out@example.com',
            'course_id' => $other->id, 'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/high-ticket-leads?course_id={$wanted->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('statusCounts.pending', 1)
                ->missing('statusCounts.closed'));
    }

    public function test_old_course_subscribers_route_is_gone(): void
    {
        $drip = $this->makeDripCourse();

        $this->actingAs($this->admin())
            ->get("/admin/courses/{$drip->id}/subscribers")
            ->assertNotFound();
    }

    /**
     * 011 US9 補充 (T161) — the leads list's expandable questionnaire row shows
     * the booked slot range instead of the bonus code, which means the lead's
     * consultation_slots units must actually be eager-loaded onto the prop.
     */
    public function test_booking_leads_include_their_consultation_slots(): void
    {
        $course = $this->makeCourse();
        $lead = HighTicketLead::create([
            'name' => 'Booked', 'email' => 'booked@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);
        $start = now()->addDay()->startOfHour();
        ConsultationSlot::create(['starts_at' => $start, 'lead_id' => $lead->id]);
        ConsultationSlot::create(['starts_at' => $start->copy()->addMinutes(15), 'lead_id' => $lead->id]);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('leads.data.0.slots', 2));
    }

    /**
     * 011 US18 (FR-074) — the consultant filter must reach the status pills too.
     * Asserting both the list and statusCounts in one test is the point: the
     * failure this guards against is exactly the two drifting apart.
     */
    public function test_consultant_filter_narrows_both_the_list_and_the_status_counts(): void
    {
        $course = $this->makeCourse();
        $mine   = $this->consultant();
        $theirs = $this->consultant();

        foreach ([['a', 'pending', $mine], ['b', 'converted', $mine], ['c', 'pending', $theirs]] as [$key, $status, $owner]) {
            HighTicketLead::create([
                'name' => "Lead {$key}", 'email' => "{$key}@example.com",
                'course_id' => $course->id, 'status' => $status,
                'consultant_id' => $owner->id, 'booked_at' => now(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get("/admin/high-ticket-leads?consultant={$mine->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 2)
                ->where('statusCounts.pending', 1)
                ->where('statusCounts.converted', 1));
    }

    public function test_consultant_none_returns_only_unassigned_leads(): void
    {
        $course     = $this->makeCourse();
        $consultant = $this->consultant();

        HighTicketLead::create([
            'name' => 'Assigned', 'email' => 'assigned@example.com',
            'course_id' => $course->id, 'status' => 'pending',
            'consultant_id' => $consultant->id, 'booked_at' => now(),
        ]);
        HighTicketLead::create([
            'name' => 'Orphan', 'email' => 'orphan@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?consultant=none')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.email', 'orphan@example.com')
                ->where('statusCounts.pending', 1));
    }

    public function test_consultant_filter_stacks_with_status_and_search(): void
    {
        $course     = $this->makeCourse();
        $consultant = $this->consultant();

        HighTicketLead::create([
            'name' => 'Ada Wanted', 'email' => 'ada@example.com',
            'course_id' => $course->id, 'status' => 'pending',
            'consultant_id' => $consultant->id, 'booked_at' => now(),
        ]);
        // Same consultant + same search hit, wrong status.
        HighTicketLead::create([
            'name' => 'Ada Closed', 'email' => 'ada2@example.com',
            'course_id' => $course->id, 'status' => 'closed',
            'consultant_id' => $consultant->id, 'booked_at' => now(),
        ]);
        // Same consultant + right status, misses the search.
        HighTicketLead::create([
            'name' => 'Bob', 'email' => 'bob@example.com',
            'course_id' => $course->id, 'status' => 'pending',
            'consultant_id' => $consultant->id, 'booked_at' => now(),
        ]);
        // Matches status + search but belongs to someone else — without this the
        // test would pass on the status/search filters alone.
        HighTicketLead::create([
            'name' => 'Ada Elsewhere', 'email' => 'ada3@example.com',
            'course_id' => $course->id, 'status' => 'pending',
            'consultant_id' => $this->consultant()->id, 'booked_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/high-ticket-leads?consultant={$consultant->id}&status=pending&search=Ada")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.email', 'ada@example.com')
                // Search stays in the denominator, status does not (FR-067).
                ->where('statusCounts.pending', 1)
                ->where('statusCounts.closed', 1));
    }

    public function test_unknown_consultant_id_returns_an_empty_list_rather_than_erroring(): void
    {
        $course = $this->makeCourse();
        HighTicketLead::create([
            'name' => 'Someone', 'email' => 'someone@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?consultant=999999')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('leads.data', 0));
    }

    public function test_consultant_options_list_staff_only(): void
    {
        $admin      = $this->admin();
        $consultant = $this->consultant();
        User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->assertInertia(function ($page) use ($admin, $consultant) {
                $page->has('consultantOptions', 2);
                $ids = collect($page->toArray()['props']['consultantOptions'])->pluck('id')->all();
                $this->assertEqualsCanonicalizing([$admin->id, $consultant->id], $ids);
            });
    }

    /**
     * D70 — the filter is a viewing tool, not a permission gate: a sales
     * consultant still lands on the whole list and is never auto-scoped.
     */
    public function test_sales_consultant_is_not_auto_filtered_to_themselves(): void
    {
        $course     = $this->makeCourse();
        $consultant = $this->consultant();

        HighTicketLead::create([
            'name' => 'Theirs', 'email' => 'theirs@example.com',
            'course_id' => $course->id, 'status' => 'pending',
            'consultant_id' => $consultant->id, 'booked_at' => now(),
        ]);
        HighTicketLead::create([
            'name' => 'Unassigned', 'email' => 'nobody@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->actingAs($consultant)
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 2)
                ->where('filters.consultant', null));
    }

    /**
     * 011 US9 補充 (T162) — "序列信起始時間" needs subscribed_at on dripByEmail,
     * which previously only shipped course_name/status.
     */
    public function test_booking_tab_drip_by_email_includes_subscribed_at(): void
    {
        $course = $this->makeCourse();
        HighTicketLead::create([
            'name' => 'Warmed', 'email' => 'warmed@test',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);
        $drip = $this->makeDripCourse();
        $this->subscribe($drip, 'warmed@test');

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('dripByEmail.warmed@test.0.subscribed_at'));
    }

    // ── 011 US28 — the meeting-time quick filters (FR-144 … FR-147) ─────────

    /** Noon in Taipei, so "today" has both a past and a future hour in it. */
    private function freezeAtTaipeiNoon(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', 'Asia/Taipei'));
    }

    /**
     * A lead holding two consecutive 15-minute units.
     *
     * `$taipei` is wall-clock Taipei; it is converted here rather than at the
     * call sites because writing a Taipei-tz Carbon straight into the column
     * would store the local reading as if it were UTC.
     */
    private function leadMetAt(Course $course, string $email, string $taipei, array $overrides = []): HighTicketLead
    {
        $lead = HighTicketLead::create(array_merge([
            'name'      => $email,
            'email'     => $email,
            'course_id' => $course->id,
            'status'    => 'pending',
            'booked_at' => now(),
        ], $overrides));

        $start = Carbon::parse($taipei, 'Asia/Taipei')->utc();
        ConsultationSlot::create(['starts_at' => $start, 'lead_id' => $lead->id]);
        ConsultationSlot::create(['starts_at' => $start->copy()->addMinutes(15), 'lead_id' => $lead->id]);

        return $lead;
    }

    /** @return array<int, string> the emails the list came back with */
    private function listedEmails(array $query = []): array
    {
        $page = $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?' . http_build_query($query))
            ->assertOk()
            ->viewData('page');

        return collect($page['props']['leads']['data'])->pluck('email')->all();
    }

    public function test_met_today_returns_only_leads_whose_slot_is_today_in_taipei(): void
    {
        $this->freezeAtTaipeiNoon();
        $course = $this->makeCourse();

        $this->leadMetAt($course, 'this-afternoon@example.com', '2026-08-22 14:00');
        $this->leadMetAt($course, 'this-morning@example.com', '2026-08-22 09:00');
        $this->leadMetAt($course, 'three-days-ago@example.com', '2026-08-19 15:00');
        $this->leadMetAt($course, 'tomorrow@example.com', '2026-08-23 10:00');

        $this->assertEqualsCanonicalizing(
            ['this-afternoon@example.com', 'this-morning@example.com'],
            $this->listedEmails(['met' => 'today']),
            '今日 = 台北日曆日，含今天稍晚才要進行的場次，不含明天'
        );
    }

    public function test_met_7d_covers_six_days_back_and_excludes_older_and_future(): void
    {
        $this->freezeAtTaipeiNoon();
        $course = $this->makeCourse();

        $this->leadMetAt($course, 'today@example.com', '2026-08-22 14:00');
        $this->leadMetAt($course, 'six-days-ago@example.com', '2026-08-16 15:00');
        $this->leadMetAt($course, 'eight-days-ago@example.com', '2026-08-14 15:00');
        $this->leadMetAt($course, 'tomorrow@example.com', '2026-08-23 10:00');

        $this->assertEqualsCanonicalizing(
            ['today@example.com', 'six-days-ago@example.com'],
            $this->listedEmails(['met' => '7d'])
        );
    }

    /**
     * FR-144 — the boundary is Taipei's. 00:30 Taipei on the 22nd is 16:30 UTC
     * on the 21st, which a naive whereDate() on the stored column would file
     * under yesterday.
     */
    public function test_met_boundaries_follow_taipei_not_utc(): void
    {
        $this->freezeAtTaipeiNoon();
        $course = $this->makeCourse();

        $this->leadMetAt($course, 'just-after-midnight@example.com', '2026-08-22 00:30');
        $this->leadMetAt($course, 'just-before-midnight@example.com', '2026-08-21 23:30');

        $this->assertSame(
            ['just-after-midnight@example.com'],
            $this->listedEmails(['met' => 'today'])
        );
    }

    /**
     * Cancelled and declined bookings hand their units back (FR-138), so those
     * leads drop out of every meeting-time filter. That is the intent: a
     * follow-up list is people who were actually met.
     */
    public function test_leads_without_slots_are_absent_from_the_meeting_filters(): void
    {
        $this->freezeAtTaipeiNoon();
        $course = $this->makeCourse();

        $this->leadMetAt($course, 'met@example.com', '2026-08-22 14:00');
        HighTicketLead::create([
            'name' => 'No slot', 'email' => 'no-slot@example.com',
            'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
        ]);

        $this->assertSame(['met@example.com'], $this->listedEmails(['met' => 'today']));
        $this->assertCount(2, $this->listedEmails(), '沒有時間篩選時兩筆都要在');
    }

    /**
     * FR-146 — the pills share the filter, so the list and the funnel can never
     * describe two different populations.
     */
    public function test_met_filter_narrows_the_status_counts_too(): void
    {
        $this->freezeAtTaipeiNoon();
        $course = $this->makeCourse();

        $this->leadMetAt($course, 'today-pending@example.com', '2026-08-22 14:00');
        $this->leadMetAt($course, 'today-converted@example.com', '2026-08-22 16:00', ['status' => 'converted']);
        $this->leadMetAt($course, 'old-pending@example.com', '2026-07-01 15:00');

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?met=today')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('statusCounts.pending', 1)
                ->where('statusCounts.converted', 1)
                ->where('filters.met', 'today'));
    }

    public function test_met_filter_stacks_with_the_consultant_and_search_filters(): void
    {
        $this->freezeAtTaipeiNoon();
        $course = $this->makeCourse();
        $mine   = $this->consultant();

        $this->leadMetAt($course, 'wanted@example.com', '2026-08-22 14:00', ['consultant_id' => $mine->id]);
        $this->leadMetAt($course, 'today-someone-else@example.com', '2026-08-22 16:00');
        $this->leadMetAt($course, 'mine-but-old@example.com', '2026-07-01 15:00', ['consultant_id' => $mine->id]);

        $this->assertSame(
            ['wanted@example.com'],
            $this->listedEmails(['met' => 'today', 'consultant' => $mine->id])
        );
        $this->assertSame(
            ['wanted@example.com'],
            $this->listedEmails(['met' => 'today', 'search' => 'wanted@'])
        );
    }

    /**
     * FR-144 — `met` is an interface key, not user data: a value nobody
     * recognises must widen back to the full list rather than produce an empty
     * one that reads as "nobody was met this week".
     */
    public function test_unrecognised_met_value_is_ignored_rather_than_emptying_the_list(): void
    {
        $this->freezeAtTaipeiNoon();
        $course = $this->makeCourse();

        $this->leadMetAt($course, 'today@example.com', '2026-08-22 14:00');
        $this->leadMetAt($course, 'ages-ago@example.com', '2026-01-05 15:00');

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads?met=last-fortnight')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('leads.data', 2)
                ->where('filters.met', null));
    }

    /**
     * FR-147 — the course dropdown is gone, so the option list it existed to
     * fill must stop being queried and shipped. The `?course_id=` condition
     * itself stays (see the status-counts test above).
     */
    public function test_course_option_list_is_no_longer_shipped_to_the_page(): void
    {
        $this->makeCourse(['type' => 'high_ticket']);

        $this->actingAs($this->admin())
            ->get('/admin/high-ticket-leads')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->missing('highTicketCourses'));
    }
}

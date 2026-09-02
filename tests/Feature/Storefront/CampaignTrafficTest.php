<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\CourseDailyStat;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\SiteAnalyticsService;
use App\Services\TrafficSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 002 US18 — the daily aggregate carries a campaign dimension, and the
 * per-course traffic report joins views onto orders so a link that brought
 * visitors and no sale is visible instead of missing.
 */
class CampaignTrafficTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disableCookieEncryption();
        Mail::fake();
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name' => 'Campaign Course', 'slug' => 'campaign-' . uniqid(),
            'tagline' => 't', 'description' => 'd', 'price' => 1000,
            'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ], $overrides));
    }

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin-campaign@example.com'], ['role' => 'admin']);
    }

    private function trafficRows(Course $course): array
    {
        return $this->actingAs($this->admin())
            ->get("/admin/courses/{$course->id}/traffic")
            ->assertOk()
            ->viewData('page')['props']['traffic']['sources'];
    }

    /** A paid order attributed to one source/campaign. */
    private function paidOrder(Course $course, array $source): void
    {
        $user = User::create([
            'email' => 'buyer-' . uniqid() . '@example.com', 'name' => 'B', 'role' => 'member',
        ]);

        $order = Order::create(array_merge([
            'user_id'      => $user->id,
            'buyer_name'   => 'B',
            'buyer_email'  => $user->email,
            'buyer_phone'  => '0912345678',
            'total_amount' => 1000,
            'status'       => 'paid',
            'payment_gateway' => 'payuni',
        ], $source));

        OrderItem::create([
            'order_id' => $order->id, 'course_id' => $course->id,
            'course_name' => $course->name, 'unit_price' => 1000,
        ]);
    }

    // ── FR-041/044: the campaign dimension, and the dedup key that feeds it ──

    public function test_two_campaigns_on_the_same_day_are_separate_rows(): void
    {
        $course = $this->makeCourse();

        $this->get("/course/{$course->id}?utm_source=instagram&utm_campaign=post-a")->assertOk();
        $this->get("/course/{$course->id}?utm_source=instagram&utm_campaign=post-b")->assertOk();

        $rows = CourseDailyStat::where('course_id', $course->id)->get();

        $this->assertCount(2, $rows, '換一條連結進來就是另一次到訪');
        $this->assertEqualsCanonicalizing(
            ['post-a', 'post-b'],
            $rows->pluck('utm_campaign')->all(),
        );
        $this->assertSame([1, 1], $rows->pluck('views')->all());
    }

    public function test_the_same_campaign_twice_in_one_session_is_counted_once(): void
    {
        $course = $this->makeCourse();

        $this->get("/course/{$course->id}?utm_source=instagram&utm_campaign=post-a")->assertOk();
        $this->get("/course/{$course->id}?utm_source=instagram&utm_campaign=post-a")->assertOk();

        $this->assertSame(1, (int) CourseDailyStat::where('course_id', $course->id)->sum('views'));
    }

    public function test_a_different_source_on_the_same_day_is_counted_again(): void
    {
        // The old dedup key was course + date alone, so the second arrival was
        // silently swallowed even though it came from another platform (D49).
        $course = $this->makeCourse();

        $this->get("/course/{$course->id}?utm_source=instagram")->assertOk();
        $this->get("/course/{$course->id}?utm_source=threads")->assertOk();

        $this->assertSame(2, (int) CourseDailyStat::where('course_id', $course->id)->sum('views'));
    }

    // ── FR-042: one normalisation rule on both sides ────────────────────────

    public function test_campaign_case_and_padding_collapse_into_one_row(): void
    {
        $course = $this->makeCourse();
        $svc = app(SiteAnalyticsService::class);

        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram', 'campaign' => 'Summer'], 'views', 3);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram', 'campaign' => ' summer '], 'views', 2);

        $rows = CourseDailyStat::where('course_id', $course->id)->get();

        $this->assertCount(1, $rows);
        $this->assertSame('summer', $rows[0]->utm_campaign);
        $this->assertSame(5, $rows[0]->views);
    }

    public function test_report_matches_an_order_to_its_views_regardless_of_campaign_case(): void
    {
        $course = $this->makeCourse();

        app(SiteAnalyticsService::class)->bump(
            $course->id,
            ['channel' => 'social', 'source' => 'instagram', 'campaign' => 'summer'],
            'views',
            10,
        );

        $this->paidOrder($course, ['utm_source' => 'instagram', 'utm_campaign' => 'Summer']);

        $rows = $this->trafficRows($course);

        $this->assertCount(1, $rows, '大小寫不同的同一個 campaign 必須合成一列');
        $this->assertSame(10, $rows[0]['views']);
        $this->assertSame(1, $rows[0]['order_count']);
    }

    // ── FR-045: the point of the story — zero-conversion links stay visible ──

    public function test_a_link_with_views_and_no_orders_appears_in_the_report(): void
    {
        $course = $this->makeCourse();

        app(SiteAnalyticsService::class)->bump(
            $course->id,
            ['channel' => 'social', 'source' => 'threads', 'campaign' => 'quiet-post'],
            'views',
            300,
        );

        $rows = $this->trafficRows($course);

        $this->assertCount(1, $rows);
        $this->assertSame('threads', $rows[0]['source']);
        $this->assertSame('quiet-post', $rows[0]['campaign']);
        $this->assertSame(300, $rows[0]['views']);
        $this->assertSame(0, $rows[0]['order_count']);
        $this->assertSame(0.0, $rows[0]['revenue']);
    }

    public function test_an_order_with_no_recorded_views_still_appears(): void
    {
        // Orders predating the aggregate table have no views to join onto; the
        // row must survive with views = 0 rather than vanish.
        $course = $this->makeCourse();

        $this->paidOrder($course, ['utm_source' => 'instagram', 'utm_campaign' => 'legacy']);

        $rows = $this->trafficRows($course);

        $this->assertCount(1, $rows);
        $this->assertSame(0, $rows[0]['views']);
        $this->assertSame(1, $rows[0]['order_count']);
    }

    public function test_every_row_carries_the_channel_resolved_by_the_server(): void
    {
        // FR-046: the front end must not classify anything itself any more.
        $course = $this->makeCourse();

        $this->paidOrder($course, ['utm_source' => 'instagram', 'utm_medium' => 'paid_social']);
        $this->paidOrder($course, ['utm_source' => 'google']);

        $channels = array_column($this->trafficRows($course), 'channel');

        $this->assertEqualsCanonicalizing(['paid', 'search'], $channels);
    }

    public function test_rows_without_any_source_are_labelled_direct(): void
    {
        $course = $this->makeCourse();

        $this->paidOrder($course, []);

        $rows = $this->trafficRows($course);

        $this->assertSame('direct', $rows[0]['source']);
        $this->assertSame('(直接造訪)', $rows[0]['display_source']);
        $this->assertSame('', $rows[0]['campaign']);
    }

    // ── FR-041 invariant: the reports above this table do not move ───────────

    public function test_channel_and_funnel_reports_are_unchanged_by_the_new_dimension(): void
    {
        $course = $this->makeCourse();
        $svc = app(SiteAnalyticsService::class);

        // The same platform split across three campaigns must still sum to one
        // channel row and one funnel row — SUM over an extra dimension.
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram', 'campaign' => 'a'], 'views', 4);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram', 'campaign' => 'b'], 'views', 6);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram', 'campaign' => ''], 'views', 5);

        $channels = $svc->channelReport(null);

        $this->assertCount(1, $channels);
        $this->assertSame('social', $channels[0]['channel']);
        $this->assertSame(15, $channels[0]['views']);
        $this->assertCount(1, $channels[0]['sources'], '來源層仍然只有 instagram 一列');
        $this->assertSame(15, $channels[0]['sources'][0]['views']);

        $funnel = $svc->funnelReport(null);

        $this->assertCount(1, $funnel);
        $this->assertSame(15, $funnel[0]['views']);
    }

    // ── the campaign that a purchase is filed under ─────────────────────────

    public function test_purchase_counters_are_filed_under_the_orders_campaign(): void
    {
        $course = $this->makeCourse();

        $order = Order::create([
            'user_id'      => User::create(['email' => 'p@example.com', 'name' => 'P', 'role' => 'member'])->id,
            'buyer_name'   => 'P',
            'buyer_email'  => 'p@example.com',
            'buyer_phone'  => '0912345678',
            'total_amount' => 1000,
            'status'       => 'paid',
            'payment_gateway' => 'payuni',
            'utm_source'   => 'instagram',
            'utm_campaign' => 'Launch',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'course_id' => $course->id,
            'course_name' => $course->name, 'unit_price' => 1000,
        ]);

        app(SiteAnalyticsService::class)->recordPurchase($order->load('items'));

        $row = CourseDailyStat::where('course_id', $course->id)->firstOrFail();

        $this->assertSame('launch', $row->utm_campaign);
        $this->assertSame('instagram', $row->source);
        $this->assertSame(1, $row->purchases);
    }

    public function test_campaign_is_truncated_to_the_column_width(): void
    {
        $this->assertSame(
            100,
            mb_strlen(TrafficSourceService::normaliseCampaign(str_repeat('x', 250))),
        );
    }
}

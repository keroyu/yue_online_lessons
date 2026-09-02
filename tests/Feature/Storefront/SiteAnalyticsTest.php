<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\CourseDailyStat;
use App\Models\DripSubscription;
use App\Models\Post;
use App\Models\PostCtaClick;
use App\Models\User;
use App\Services\SiteAnalyticsService;
use App\Services\TrafficSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SiteAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'             => 'Analytics Course',
            'slug'             => 'analytics-course-' . uniqid(),
            'tagline'          => 'tag',
            'description'      => 'desc',
            'price'            => 1000,
            'instructor_name'  => 'Tester',
            'type'             => 'lecture',
            'status'           => 'selling',
            'course_type'      => 'standard',
            'is_published'     => true,
            'is_visible'       => true,
            'payment_gateway'  => 'payuni',
        ], $overrides));
    }

    private function makePost(): Post
    {
        return Post::create([
            'title'        => '測試文章',
            'slug'         => 'test-post-' . uniqid(),
            'body_md'      => 'hello',
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }

    // --- capture (middleware + cookies) ---

    public function test_middleware_captures_utm_on_any_page_into_cookies(): void
    {
        $response = $this->get('/?utm_source=instagram&utm_medium=social');

        $response->assertOk();
        $response->assertCookie(TrafficSourceService::COOKIE_LAST);
        $response->assertCookie(TrafficSourceService::COOKIE_FIRST);
    }

    public function test_first_touch_cookie_is_not_overwritten_by_new_source(): void
    {
        $service = app(TrafficSourceService::class);

        $first = json_encode(['utm_source' => 'instagram'], JSON_UNESCAPED_UNICODE);
        $request = Request::create('/?utm_source=google', 'GET', cookies: [
            TrafficSourceService::COOKIE_FIRST => $first,
        ]);

        $service->capture($request);

        $queued = collect(\Illuminate\Support\Facades\Cookie::getQueuedCookies());
        $this->assertTrue($queued->contains(fn ($c) => $c->getName() === TrafficSourceService::COOKIE_LAST));
        $this->assertFalse($queued->contains(fn ($c) => $c->getName() === TrafficSourceService::COOKIE_FIRST));
    }

    public function test_bot_user_agent_is_not_captured(): void
    {
        $service = app(TrafficSourceService::class);
        $request = Request::create('/?utm_source=instagram');
        $request->headers->set('User-Agent', 'facebookexternalhit/1.1');

        $service->capture($request);

        $this->assertEmpty(\Illuminate\Support\Facades\Cookie::getQueuedCookies());
    }

    // --- channel classification ---

    public function test_classify_channel_rules(): void
    {
        $s = app(TrafficSourceService::class);

        // fbclid is appended to EVERY outbound Meta click (organic posts, bio
        // links, DMs) — it means "came from Meta", never "came from an ad".
        $this->assertSame('social', $s->classifyChannel(['fbclid' => 'x']));
        $this->assertSame('paid', $s->classifyChannel(['gclid' => 'x']));
        $this->assertSame('paid', $s->classifyChannel(['utm_medium' => 'cpc']));
        $this->assertSame('social', $s->classifyChannel(['utm_source' => 'Instagram']));
        $this->assertSame('search', $s->classifyChannel(['utm_source' => 'google']));
        $this->assertSame('email', $s->classifyChannel(['utm_source' => 'newsletter']));
        // US14: the drip stamps its own source so the email channel can be
        // split into 連鎖信 / 電子報 instead of one undifferentiated bucket.
        $this->assertSame('email', $s->classifyChannel(['utm_source' => 'drip']));
        $this->assertSame('video', $s->classifyChannel(['utm_source' => 'youtube']));
        $this->assertSame('referral', $s->classifyChannel(['referrer_domain' => 'blog.example.com']));
        $this->assertSame('direct', $s->classifyChannel(null));
        $this->assertSame('direct', $s->classifyChannel([]));
    }

    // --- source resolution (US13) ---

    public function test_resolve_source_maps_referrer_hosts_to_platform(): void
    {
        $s = app(TrafficSourceService::class);

        // No UTM at all — the referrer host alone decides both channel and source.
        $this->assertSame(
            ['channel' => 'social', 'source' => 'instagram'],
            $s->resolveSource(['referrer_domain' => 'l.instagram.com'])
        );
        $this->assertSame(
            ['channel' => 'social', 'source' => 'threads'],
            $s->resolveSource(['referrer_domain' => 'threads.net'])
        );
        $this->assertSame(
            ['channel' => 'social', 'source' => 'facebook'],
            $s->resolveSource(['referrer_domain' => 'm.facebook.com'])
        );
        $this->assertSame(
            ['channel' => 'search', 'source' => 'google'],
            $s->resolveSource(['referrer_domain' => 'google.com.tw'])
        );
        $this->assertSame(
            ['channel' => 'video', 'source' => 'youtube'],
            $s->resolveSource(['referrer_domain' => 'youtu.be'])
        );
    }

    public function test_resolve_source_maps_utm_and_click_ids(): void
    {
        $s = app(TrafficSourceService::class);

        // Bare fbclid: we know it is Meta but not which surface — say so.
        $this->assertSame(['channel' => 'social', 'source' => 'meta'], $s->resolveSource(['fbclid' => 'x']));
        $this->assertSame(['channel' => 'paid', 'source' => 'google'], $s->resolveSource(['gclid' => 'x']));
        $this->assertSame(['channel' => 'paid', 'source' => 'tiktok'], $s->resolveSource(['ttclid' => 'x']));
        $this->assertSame(['channel' => 'social', 'source' => 'threads'], $s->resolveSource(['utm_source' => 'Threads']));
        $this->assertSame(['channel' => 'email', 'source' => 'newsletter'], $s->resolveSource(['utm_source' => 'edm']));
        $this->assertSame(['channel' => 'direct', 'source' => 'direct'], $s->resolveSource(null));
        $this->assertSame(['channel' => 'direct', 'source' => 'direct'], $s->resolveSource([]));
    }

    /**
     * 002 FR-024 — fbclid must not imply paid traffic.
     *
     * Meta appends fbclid to every outbound click from Facebook and Instagram,
     * including organic bio links. Treating it as an ad signal buried all
     * organic IG traffic under 付費廣告 > Facebook.
     */
    public function test_fbclid_is_not_a_paid_signal(): void
    {
        $s = app(TrafficSourceService::class);

        // The reported bug: IG bio click, Meta appends fbclid, referrer is the
        // Instagram link shim. Must land in social/instagram, not paid/facebook.
        $this->assertSame(
            ['channel' => 'social', 'source' => 'instagram'],
            $s->resolveSource(['fbclid' => 'x', 'referrer_domain' => 'l.instagram.com'])
        );

        // Same click without a referrer (in-app browser strips it).
        $this->assertSame(
            ['channel' => 'social', 'source' => 'meta'],
            $s->resolveSource(['fbclid' => 'x'])
        );

        // An explicit tag must beat the click id Meta bolted on afterwards —
        // otherwise the admin cannot correct the attribution by tagging links.
        $this->assertSame(
            ['channel' => 'social', 'source' => 'instagram'],
            $s->resolveSource(['fbclid' => 'x', 'utm_source' => 'instagram'])
        );

        // Genuine ad traffic is identified by the medium the advertiser sets.
        $this->assertSame(
            ['channel' => 'paid', 'source' => 'instagram'],
            $s->resolveSource(['fbclid' => 'x', 'utm_source' => 'instagram', 'utm_medium' => 'paid_social'])
        );
        $this->assertSame(
            ['channel' => 'paid', 'source' => 'meta'],
            $s->resolveSource(['fbclid' => 'x', 'utm_medium' => 'cpc'])
        );
    }

    /** gclid / ttclid are only ever emitted by ad clicks, so they stay paid. */
    public function test_google_and_tiktok_click_ids_remain_paid(): void
    {
        $s = app(TrafficSourceService::class);

        $this->assertSame(['channel' => 'paid', 'source' => 'google'], $s->resolveSource(['gclid' => 'x']));
        $this->assertSame(['channel' => 'paid', 'source' => 'tiktok'], $s->resolveSource(['ttclid' => 'x']));
        // Organic Google search keeps its own channel.
        $this->assertSame(['channel' => 'search', 'source' => 'google'], $s->resolveSource(['referrer_domain' => 'google.com']));
    }

    public function test_resolve_source_keeps_raw_value_when_platform_is_unknown(): void
    {
        $s = app(TrafficSourceService::class);

        // FR-023: the host itself is actionable information, do not flatten to "other".
        $this->assertSame(
            ['channel' => 'referral', 'source' => 'blog.example.com'],
            $s->resolveSource(['referrer_domain' => 'blog.example.com'])
        );
        $this->assertSame(
            ['channel' => 'referral', 'source' => 'partner-site'],
            $s->resolveSource(['utm_source' => 'partner-site'])
        );
    }

    public function test_utm_source_wins_over_referrer_domain(): void
    {
        $s = app(TrafficSourceService::class);

        $this->assertSame(
            ['channel' => 'email', 'source' => 'newsletter'],
            $s->resolveSource(['utm_source' => 'newsletter', 'referrer_domain' => 'l.instagram.com'])
        );
    }

    // --- view counting ---

    public function test_course_view_increments_daily_stat_once_per_session(): void
    {
        $course = $this->makeCourse();

        $this->get("/course/{$course->slug}")->assertOk();
        $this->get("/course/{$course->slug}")->assertOk(); // same session → dedup

        $stat = CourseDailyStat::where('course_id', $course->id)->first();
        $this->assertNotNull($stat);
        $this->assertSame(1, $stat->views);
        $this->assertSame('direct', $stat->channel);
    }

    public function test_utm_landing_view_is_classified_from_live_params_not_cookie(): void
    {
        $course = $this->makeCourse();

        // First hit carries UTM but no tf_last cookie yet (it is only queued
        // for the response) — must still classify as social, not direct.
        $this->get("/course/{$course->slug}?utm_source=instagram&utm_medium=social&utm_campaign=2026-kol-sushi")
            ->assertOk();

        $stat = CourseDailyStat::where('course_id', $course->id)->first();
        $this->assertNotNull($stat);
        $this->assertSame('social', $stat->channel);
        $this->assertSame(1, $stat->views);
    }

    public function test_organic_social_referrer_is_counted_as_social_with_platform(): void
    {
        $course = $this->makeCourse();

        // No UTM anywhere — this used to land in "referral" (US13 changes it).
        $this->withHeaders(['Referer' => 'https://l.instagram.com/'])
            ->get("/course/{$course->slug}")
            ->assertOk();

        $stat = CourseDailyStat::where('course_id', $course->id)->first();
        $this->assertNotNull($stat);
        $this->assertSame('social', $stat->channel);
        $this->assertSame('instagram', $stat->source);
        $this->assertSame(1, $stat->views);
    }

    public function test_same_channel_different_sources_are_separate_rows(): void
    {
        $course = $this->makeCourse();
        $svc = app(SiteAnalyticsService::class);

        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram'], 'views', 8);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'threads'], 'views', 3);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram'], 'views', 2);

        $this->assertSame(2, CourseDailyStat::where('course_id', $course->id)->count());
        $this->assertSame(10, CourseDailyStat::where('source', 'instagram')->value('views'));
        $this->assertSame(3, CourseDailyStat::where('source', 'threads')->value('views'));
    }

    public function test_bot_view_is_not_counted(): void
    {
        $course = $this->makeCourse();

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
            ->get("/course/{$course->slug}")
            ->assertOk();

        $this->assertSame(0, CourseDailyStat::count());
    }

    // --- add to cart beacon ---

    public function test_add_to_cart_beacon_increments_counter(): void
    {
        $course = $this->makeCourse();

        $this->postJson('/api/track/add-to-cart', ['course_id' => $course->id])
            ->assertNoContent();

        $stat = CourseDailyStat::where('course_id', $course->id)->first();
        $this->assertSame(1, $stat->add_to_cart);
    }

    public function test_add_to_cart_beacon_is_throttled(): void
    {
        $course = $this->makeCourse();

        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/track/add-to-cart', ['course_id' => $course->id])->assertNoContent();
        }

        $this->postJson('/api/track/add-to-cart', ['course_id' => $course->id])
            ->assertStatus(429);
    }

    // --- blog CTA redirect ---

    public function test_go_redirect_counts_click_and_appends_utm(): void
    {
        $post = $this->makePost();
        $course = $this->makeCourse();

        $response = $this->get("/go/post/{$post->id}/course/{$course->id}");

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringContainsString('utm_source=blog', $target);
        $this->assertStringContainsString('utm_medium=cta', $target);

        $click = PostCtaClick::where('post_id', $post->id)->where('course_id', $course->id)->first();
        $this->assertSame(1, $click->clicks);

        // second click same day → same row, clicks = 2
        $this->get("/go/post/{$post->id}/course/{$course->id}");
        $this->assertSame(2, $click->fresh()->clicks);
        $this->assertSame(1, PostCtaClick::count());
    }

    /**
     * US14 / FR-027 — a reader who arrived from one of our letters keeps that
     * attribution through the post's CTA.
     *
     * The blog is a waypoint on the 信 → 文章 → 商品 path, not its source;
     * overwriting here would credit every such sale to the blog and leave the
     * email channel reading zero — the one number the stamping exists to show.
     */
    public function test_go_redirect_keeps_an_email_source_but_still_counts(): void
    {
        $post = $this->makePost();
        $course = $this->makeCourse();

        $response = $this->withUnencryptedCookie(
            TrafficSourceService::COOKIE_LAST,
            json_encode(['utm_source' => 'drip', 'utm_medium' => 'email']),
        )->get("/go/post/{$post->id}/course/{$course->id}");

        $target = $response->headers->get('Location');
        $this->assertStringNotContainsString('utm_source=blog', $target);

        // The blog's own pull is measured by this counter, so it loses nothing.
        $this->assertSame(1, PostCtaClick::where('post_id', $post->id)->value('clicks'));
    }

    public function test_go_redirect_still_overwrites_a_non_email_source(): void
    {
        $post = $this->makePost();
        $course = $this->makeCourse();

        $response = $this->withUnencryptedCookie(
            TrafficSourceService::COOKIE_LAST,
            json_encode(['utm_source' => 'instagram']),
        )->get("/go/post/{$post->id}/course/{$course->id}");

        // Social → post → course: the post's CTA really is the proximate cause.
        $this->assertStringContainsString('utm_source=blog', $response->headers->get('Location'));
    }

    // --- funnel report ---

    public function test_admin_analytics_page_aggregates_funnel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->makeCourse();

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($course->id, ['channel' => 'social'], 'views', 10);
        $svc->bump($course->id, ['channel' => 'social'], 'add_to_cart', 4);
        $svc->bump($course->id, ['channel' => 'social'], 'checkouts', 2);
        $svc->bump($course->id, ['channel' => 'social'], 'purchases', 1);
        $svc->bump($course->id, ['channel' => 'social'], 'revenue', 1000);

        $response = $this->actingAs($admin)->get('/admin/analytics');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Index')
            ->where('funnel.0.views', 10)
            ->where('funnel.0.add_to_cart', 4)
            ->where('funnel.0.purchases', 1)
        );
    }

    // --- drip conversions in the funnel (US16) ---

    /** A subscription that reached the funnel goal $daysAgo days ago. */
    private function convertedSub(Course $dripCourse, int $daysAgo = 0, string $status = 'converted'): DripSubscription
    {
        return DripSubscription::create([
            'user_id'           => User::factory()->create()->id,
            'course_id'         => $dripCourse->id,
            'subscribed_at'     => now()->subDays($daysAgo + 10),
            'emails_sent'       => 3,
            'status'            => $status,
            'status_changed_at' => now()->subDays($daysAgo),
        ]);
    }

    /**
     * FR-033: a free drip course never reaches the purchase column on its own —
     * claiming does not create an order — so its conversions have to come from
     * the subscriptions that later bought a target course.
     */
    public function test_drip_conversions_merge_into_the_drip_course_row(): void
    {
        $drip = $this->makeCourse(['course_type' => 'drip', 'price' => 0]);

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($drip->id, ['channel' => 'social'], 'views', 40);
        $this->convertedSub($drip);
        $this->convertedSub($drip);

        $row = collect($svc->funnelReport(30))->firstWhere('course_id', $drip->id);

        $this->assertSame(2, $row['purchases']);
        $this->assertSame(2, $row['drip_conversions']);
    }

    /** Every row carries the key, so the front end never has to guard for it. */
    public function test_rows_without_conversions_report_zero(): void
    {
        $course = $this->makeCourse();

        app(SiteAnalyticsService::class)->bump($course->id, ['channel' => 'social'], 'views', 5);

        $row = collect(app(SiteAnalyticsService::class)->funnelReport(30))->firstWhere('course_id', $course->id);

        $this->assertSame(0, $row['drip_conversions']);
        $this->assertSame(0, $row['purchases']);
    }

    /** Booking a consultation is not a sale; buying afterwards flips it to converted. */
    public function test_booked_subscribers_are_not_counted(): void
    {
        $drip = $this->makeCourse(['course_type' => 'drip', 'price' => 0]);

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($drip->id, ['channel' => 'social'], 'views', 10);
        $this->convertedSub($drip, 0, 'booked');

        $row = collect($svc->funnelReport(30))->firstWhere('course_id', $drip->id);

        $this->assertSame(0, $row['purchases']);
    }

    /** FR-034: the period filters on when the conversion happened. */
    public function test_conversions_outside_the_period_are_excluded(): void
    {
        $drip = $this->makeCourse(['course_type' => 'drip', 'price' => 0]);

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($drip->id, ['channel' => 'social'], 'views', 10);
        $this->convertedSub($drip, 2);
        $this->convertedSub($drip, 45);

        $row = collect($svc->funnelReport(30))->firstWhere('course_id', $drip->id);
        $this->assertSame(1, $row['drip_conversions']);

        $all = collect($svc->funnelReport(null))->firstWhere('course_id', $drip->id);
        $this->assertSame(2, $all['drip_conversions']);
    }

    /** Conversions with no traffic rows must not vanish from the report. */
    public function test_a_drip_course_with_no_traffic_rows_still_appears(): void
    {
        $drip = $this->makeCourse(['course_type' => 'drip', 'price' => 0]);

        $this->convertedSub($drip);

        $row = collect(app(SiteAnalyticsService::class)->funnelReport(30))
            ->firstWhere('course_id', $drip->id);

        $this->assertNotNull($row);
        $this->assertSame(0, $row['views']);
        $this->assertSame(1, $row['purchases']);
        $this->assertSame($drip->name, $row['course_name']);
    }

    /** D43: subscriptions carry no channel, so a channel view must not claim them. */
    public function test_channel_filter_excludes_drip_conversions(): void
    {
        $drip = $this->makeCourse(['course_type' => 'drip', 'price' => 0]);

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($drip->id, ['channel' => 'social'], 'views', 10);
        $this->convertedSub($drip);

        $row = collect($svc->funnelReport(30, 'social'))->firstWhere('course_id', $drip->id);

        $this->assertSame(0, $row['purchases']);
        $this->assertSame(0, $row['drip_conversions']);
    }

    /** FR-035: revenue stays "what this course collected", which for a freebie is nothing. */
    public function test_drip_conversions_do_not_add_revenue(): void
    {
        $drip = $this->makeCourse(['course_type' => 'drip', 'price' => 0]);

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($drip->id, ['channel' => 'social'], 'views', 10);
        $this->convertedSub($drip);

        $row = collect($svc->funnelReport(30))->firstWhere('course_id', $drip->id);

        $this->assertSame(0, $row['revenue']);
    }

    public function test_channel_report_nests_sources_and_totals_reconcile(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->makeCourse();

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram'], 'views', 8);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram'], 'purchases', 2);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'threads'], 'views', 3);
        $svc->bump($course->id, ['channel' => 'search', 'source' => 'google'], 'views', 5);

        $report = collect($svc->channelReport(null))->keyBy('channel');

        // Channel level is the sum of its sources, sorted by views desc.
        $this->assertSame(11, $report['social']['views']);
        $this->assertSame(2, $report['social']['purchases']);
        $this->assertCount(2, $report['social']['sources']);
        $this->assertSame('instagram', $report['social']['sources'][0]['source']);
        $this->assertSame(8, $report['social']['sources'][0]['views']);
        $this->assertSame('threads', $report['social']['sources'][1]['source']);
        $this->assertSame(5, $report['search']['views']);

        $this->actingAs($admin)->get('/admin/analytics')->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('channels.0.channel', 'social')
                ->where('channels.0.sources.0.source', 'instagram')
            );
    }

    public function test_legacy_rows_without_source_are_kept_as_a_blank_bucket(): void
    {
        $course = $this->makeCourse();
        $svc = app(SiteAnalyticsService::class);

        // Pre-US13 call signature (no $source) — must not crash or be dropped.
        $svc->bump($course->id, ['channel' => 'social'], 'views', 4);
        $svc->bump($course->id, ['channel' => 'social', 'source' => 'instagram'], 'views', 6);

        $report = collect($svc->channelReport(null))->firstWhere('channel', 'social');

        $this->assertSame(10, $report['views']);
        $this->assertSame(['instagram', ''], array_column($report['sources'], 'source'));
    }

    public function test_analytics_page_is_admin_only(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/admin/analytics')->assertRedirect('/');
    }
}

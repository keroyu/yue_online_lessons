<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\CourseDailyStat;
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

        $this->assertSame('paid', $s->classifyChannel(['fbclid' => 'x']));
        $this->assertSame('social', $s->classifyChannel(['utm_source' => 'Instagram']));
        $this->assertSame('search', $s->classifyChannel(['utm_source' => 'google']));
        $this->assertSame('email', $s->classifyChannel(['utm_source' => 'newsletter']));
        $this->assertSame('video', $s->classifyChannel(['utm_source' => 'youtube']));
        $this->assertSame('referral', $s->classifyChannel(['referrer_domain' => 'blog.example.com']));
        $this->assertSame('direct', $s->classifyChannel(null));
        $this->assertSame('direct', $s->classifyChannel([]));
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

    // --- funnel report ---

    public function test_admin_analytics_page_aggregates_funnel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $course = $this->makeCourse();

        $svc = app(SiteAnalyticsService::class);
        $svc->bump($course->id, 'social', 'views', 10);
        $svc->bump($course->id, 'social', 'add_to_cart', 4);
        $svc->bump($course->id, 'social', 'checkouts', 2);
        $svc->bump($course->id, 'social', 'purchases', 1);
        $svc->bump($course->id, 'social', 'revenue', 1000);

        $response = $this->actingAs($admin)->get('/admin/analytics');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Index')
            ->where('funnel.0.views', 10)
            ->where('funnel.0.add_to_cart', 4)
            ->where('funnel.0.purchases', 1)
        );
    }

    public function test_analytics_page_is_admin_only(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/admin/analytics')->assertRedirect('/');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Post;
use App\Services\SiteAnalyticsService;
use App\Services\TrafficSourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackController extends Controller
{
    public function __construct(
        private SiteAnalyticsService $analytics,
        private TrafficSourceService $trafficSource,
    ) {
    }

    /**
     * Add-to-cart beacon (002 US10, D15) — fired by useCart after a successful
     * add; the single counting path that covers both auth and guest carts.
     *
     * POST /api/track/add-to-cart
     */
    public function addToCart(Request $request): Response
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $this->analytics->recordAddToCart((int) $validated['course_id'], $request);

        return response()->noContent();
    }

    /**
     * Blog post → course CTA redirect (002 US10, D17): count the click, then
     * 302 to the course page with blog UTM attribution appended.
     *
     * A reader who arrived from one of our letters keeps that source instead
     * (FR-027 / D37). 信 → 文章 → 商品 is one funnel of our own making, and the
     * post is a waypoint on it, not where the reader came from; overwriting
     * would bank every such sale under the blog and leave the email channel
     * showing zero. Non-email sources still get overwritten — for those, the
     * post's CTA really is the proximate cause.
     *
     * The click is counted either way, so the blog's own pull stays measurable
     * through post_cta_clicks regardless of who gets the attribution.
     *
     * GET /go/post/{post}/course/{course}
     */
    public function goPostCourse(Request $request, Post $post, Course $course): RedirectResponse
    {
        $this->analytics->recordCtaClick($post->id, $course->id);

        $slug = $course->slug ?: $course->id;

        if ($this->arrivedFromEmail($request)) {
            return redirect()->to("/course/{$slug}");
        }

        $query = http_build_query([
            'utm_source'   => 'blog',
            'utm_medium'   => 'cta',
            'utm_campaign' => $post->slug,
        ]);

        return redirect()->to("/course/{$slug}?{$query}");
    }

    private function arrivedFromEmail(Request $request): bool
    {
        $source = $this->trafficSource->currentSource($request);

        return $this->trafficSource->resolveSource($source)['channel'] === 'email';
    }
}

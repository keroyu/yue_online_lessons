<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Post;
use App\Services\SiteAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrackController extends Controller
{
    public function __construct(private SiteAnalyticsService $analytics)
    {
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
     * GET /go/post/{post}/course/{course}
     */
    public function goPostCourse(Post $post, Course $course): RedirectResponse
    {
        $this->analytics->recordCtaClick($post->id, $course->id);

        $slug = $course->slug ?: $course->id;
        $query = http_build_query([
            'utm_source'   => 'blog',
            'utm_medium'   => 'cta',
            'utm_campaign' => $post->slug,
        ]);

        return redirect()->to("/course/{$slug}?{$query}");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Services\BlogRssService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private BlogRssService $blogRssService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();

        $courses = Course::visibleToUser($user)
            ->ordered()
            ->select(['id', 'name', 'tagline', 'price', 'original_price', 'promo_ends_at', 'thumbnail', 'instructor_name', 'type', 'course_type', 'status', 'is_published', 'is_visible'])
            ->get()
            ->map(fn ($course) => [
                'id'              => $course->id,
                'name'            => $course->name,
                'tagline'         => $course->tagline,
                'price'           => $course->price,
                'original_price'  => $course->original_price,
                'is_promo_active' => $course->is_promo_active,
                'thumbnail'       => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'product_type'    => $course->type,
                'delivery_mode'   => $course->course_type,
                'status'          => $course->status,
                'is_published'    => $course->is_published,
                'is_visible'      => $course->is_visible,
            ]);

        $settings = SiteSetting::getMany([
            'hero_title', 'hero_description', 'hero_button_label',
            'hero_button_url', 'hero_banner_path',
            'blog_rss_url', 'sns_section_enabled',
        ]);

        $bannerPath = $settings->get('hero_banner_path');

        $hero = [
            'title'        => $settings->get('hero_title'),
            'description'  => $settings->get('hero_description'),
            'button_label' => $settings->get('hero_button_label') ?: null,
            'button_url'   => $settings->get('hero_button_url') ?: null,
            'banner_url'   => $bannerPath ? Storage::url($bannerPath) : null,
        ];

        // Cast explicitly: stored as "0"/"1" text — (bool)"0" is true in PHP
        $snsEnabled = (bool) (int) $settings->get('sns_section_enabled', '0');

        $socialLinks = $snsEnabled
            ? SocialLink::ordered()->get()->map(fn ($link) => [
                'platform' => $link->platform,
                'url'      => $link->url,
            ])->values()->toArray()
            : [];

        // Hide SNS section entirely when toggle off or no links
        if (empty($socialLinks)) {
            $socialLinks = [];
        }

        $rssUrl = $settings->get('blog_rss_url', '');
        $blogArticles = $rssUrl ? $this->blogRssService->getArticles($rssUrl) : [];

        return Inertia::render('Home', [
            'courses'      => $courses,
            'hero'         => $hero,
            'socialLinks'  => $socialLinks,
            'blogArticles' => $blogArticles,
            'isAdmin'      => $isAdmin,
        ]);
    }
}

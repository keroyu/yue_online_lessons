<?php

namespace App\Services;

use App\Http\Controllers\Admin\HomepageSettingController;
use App\Models\HomepageFeaturedCourse;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the shared right-hand sidebar widgets (featured courses / SNS / 近期文章)
 * used on both the homepage and the blog pages, rendered in admin-defined order.
 */
class SidebarService
{
    /**
     * @return array{socialLinks: array, snsProfile: ?array, blogArticles: array, featuredCourses: array, sidebarOrder: array}
     */
    public function widgets(): array
    {
        $settings = SiteSetting::getMany([
            'sns_section_enabled', 'sns_profile_image_path', 'sns_profile_intro',
        ]);

        // Cast explicitly: stored as "0"/"1" text — (bool)"0" is true in PHP
        $snsEnabled = (bool) (int) $settings->get('sns_section_enabled', '0');

        $socialLinks = $snsEnabled
            ? SocialLink::ordered()->get()->map(fn ($link) => [
                'platform' => $link->platform,
                'url'      => $link->url,
            ])->values()->toArray()
            : [];

        // Owner profile (avatar + intro) shown above the SNS links; null when section is off.
        $snsProfilePath = $settings->get('sns_profile_image_path');
        $snsProfile = $snsEnabled ? [
            'image_url' => $snsProfilePath ? Storage::url($snsProfilePath) : null,
            'intro'     => $settings->get('sns_profile_intro'),
        ] : null;

        // "近期文章" widget: featured first, then latest.
        $blogArticles = Post::published()
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->take(5)
            ->get(['slug', 'title', 'excerpt', 'cover_image_path', 'published_at'])
            ->map(fn (Post $post) => [
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'url' => "/blog/{$post->slug}",
                'cover' => $post->cover_url,
                'published_at' => $post->published_at?->toDateString(),
            ])->values()->all();

        $featuredCourses = HomepageFeaturedCourse::ordered()->with('course:id,slug,name,thumbnail')->get()
            ->filter(fn ($item) => $item->course !== null)
            ->map(fn ($item) => [
                'id'        => $item->course->id,
                'name'      => $item->course->name,
                'thumbnail' => $item->course->thumbnail_url,
                'blurb'     => $item->blurb,
                'url'       => '/course/' . ($item->course->slug ?: $item->course->id),
            ])->values()->toArray();

        return [
            'socialLinks'     => $socialLinks,
            'snsProfile'      => $snsProfile,
            'blogArticles'    => $blogArticles,
            'featuredCourses' => $featuredCourses,
            'sidebarOrder'    => HomepageSettingController::sidebarWidgetOrder(),
        ];
    }
}

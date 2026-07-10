<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\HomepageSettingController;
use App\Models\Course;
use App\Models\HomepageFeaturedCourse;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();

        $courses = Course::visibleToUser($user)
            ->ordered()
            ->select(['id', 'slug', 'name', 'tagline', 'price', 'original_price', 'promo_ends_at', 'thumbnail', 'instructor_name', 'type', 'content_category', 'course_type', 'status', 'is_published', 'is_visible'])
            ->get()
            ->map(fn ($course) => [
                'id'              => $course->id,
                'slug'            => $course->slug,
                'name'            => $course->name,
                'tagline'         => $course->tagline,
                'price'           => $course->price,
                'original_price'  => $course->original_price,
                'is_promo_active' => $course->is_promo_active,
                'thumbnail'       => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'product_type'    => $course->type,
                'content_category' => $course->content_category,
                'delivery_mode'   => $course->course_type,
                'status'          => $course->status,
                'is_published'    => $course->is_published,
                'is_visible'      => $course->is_visible,
            ]);

        $settings = SiteSetting::getMany([
            'hero_title', 'hero_description', 'hero_button_label',
            'hero_button_url', 'hero_banner_path',
            'sns_section_enabled',
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

        // Sidebar "近期文章" widget: featured first, then latest.
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

        // Main-column list block: the 5 most-viewed posts.
        $popularPosts = Post::published()
            ->with('tags:id,name')
            ->orderByDesc('view_count')
            ->orderByDesc('published_at')
            ->take(5)
            ->get()
            ->map(fn (Post $post) => [
                'title' => $post->title,
                'preview' => \Illuminate\Support\Str::limit($post->excerpt ?: $this->firstLinePreview($post->body_md), 30),
                'tag' => $post->tags->first()?->name,
                'url' => "/blog/{$post->slug}",
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

        return Inertia::render('Home', [
            'courses'         => $courses,
            'hero'            => $hero,
            'socialLinks'     => $socialLinks,
            'blogArticles'    => $blogArticles,
            'popularPosts'    => $popularPosts,
            'featuredCourses' => $featuredCourses,
            'sidebarOrder'    => HomepageSettingController::sidebarWidgetOrder(),
            'contentCategories' => HomepageSettingController::contentFilterEnabled()
                ? HomepageSettingController::contentCategories()
                : [],
            'isAdmin'         => $isAdmin,
        ]);
    }

    /**
     * First meaningful line of Markdown (heading marks stripped), truncated for a preview.
     */
    private function firstLinePreview(?string $md): string
    {
        foreach (preg_split('/\r\n|\r|\n/', (string) $md) as $line) {
            $line = trim(preg_replace('/^#+\s*/', '', $line));
            if ($line !== '') {
                return \Illuminate\Support\Str::limit($line, 60);
            }
        }

        return '';
    }
}

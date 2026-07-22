<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\HomepageSettingController;
use App\Models\Course;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Services\SidebarService;
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
                'display_price'   => (float) $course->display_price,
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
        ]);

        $bannerPath = $settings->get('hero_banner_path');

        $hero = [
            'title'        => $settings->get('hero_title'),
            'description'  => $settings->get('hero_description'),
            'button_label' => $settings->get('hero_button_label') ?: null,
            'button_url'   => $settings->get('hero_button_url') ?: null,
            'banner_url'   => $bannerPath ? Storage::url($bannerPath) : null,
        ];

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
                'published_at' => $post->published_at?->timezone('Asia/Taipei')->toDateString(),
            ])->values()->all();

        return Inertia::render('Home', [
            'courses'         => $courses,
            'hero'            => $hero,
            'popularPosts'    => $popularPosts,
            'contentCategories' => HomepageSettingController::contentFilterEnabled()
                ? HomepageSettingController::contentCategories()
                : [],
            'isAdmin'         => $isAdmin,
            ...app(SidebarService::class)->widgets(),
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

<?php

namespace Tests\Feature\Storefront;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 002 FR-031 — the "featured" flag belongs to exactly one of the two post
 * lists. It used to leak into the sidebar's "近期文章" (which should be pure
 * chronology) while never reaching the homepage's "熱門文章" (which should
 * put featured posts first, ahead of view count).
 */
class PostListOrderingTest extends TestCase
{
    use RefreshDatabase;

    private function publish(string $slug, array $attrs = []): Post
    {
        return Post::create(array_merge([
            'slug' => $slug,
            'title' => ucfirst($slug),
            'body_md' => "# {$slug}",
            'excerpt' => 'x',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'view_count' => 0,
            'is_featured' => false,
        ], $attrs));
    }

    public function test_recent_articles_widget_ignores_featured_flag(): void
    {
        $old = $this->publish('old', ['published_at' => now()->subDays(5)]);
        $featuredButOlder = $this->publish('featured-older', [
            'published_at' => now()->subDays(3),
            'is_featured' => true,
        ]);
        $newest = $this->publish('newest', ['published_at' => now()->subDay()]);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('blogArticles.0.title', $newest->title)
            ->where('blogArticles.1.title', $featuredButOlder->title)
            ->where('blogArticles.2.title', $old->title));
    }

    public function test_popular_posts_widget_puts_featured_ahead_of_view_count(): void
    {
        $mostViewed = $this->publish('most-viewed', ['view_count' => 100]);
        $featuredButFewerViews = $this->publish('featured-fewer-views', [
            'view_count' => 1,
            'is_featured' => true,
        ]);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('popularPosts.0.title', $featuredButFewerViews->title)
            ->where('popularPosts.1.title', $mostViewed->title));
    }
}

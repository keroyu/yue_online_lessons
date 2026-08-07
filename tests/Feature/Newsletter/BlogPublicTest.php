<?php

namespace Tests\Feature\Newsletter;

use App\Models\Course;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPublicTest extends TestCase
{
    use RefreshDatabase;

    private function publish(string $slug, array $attrs = []): Post
    {
        return Post::create(array_merge([
            'slug' => $slug,
            'title' => ucfirst($slug),
            'body_md' => "# {$slug}\n\ncontent",
            'excerpt' => "excerpt {$slug}",
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $attrs));
    }

    private function makeCourse(array $attrs = []): Course
    {
        return Course::create(array_merge([
            'name' => 'C', 'slug' => 'c-' . uniqid(),
            'tagline' => 't', 'description' => 'd', 'price' => 1000,
            'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true,
            'payment_gateway' => 'payuni',
        ], $attrs));
    }

    public function test_blog_index_lists_only_published(): void
    {
        $this->publish('live');
        Post::create(['slug' => 'draft', 'title' => 'D', 'body_md' => 'x', 'status' => 'draft']);

        $this->get('/blog')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Blog/Index')->has('posts.data', 1));
    }

    public function test_show_renders_published_and_404s_draft(): void
    {
        $this->publish('visible');
        Post::create(['slug' => 'hidden', 'title' => 'H', 'body_md' => 'x', 'status' => 'draft']);

        $this->get('/blog/visible')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Blog/Show')
                ->where('post.slug', 'visible')
                ->has('post.body_html'));

        $this->get('/blog/hidden')->assertNotFound();
    }

    public function test_view_count_increments_once_per_session(): void
    {
        $post = $this->publish('counted');

        $this->get('/blog/counted');
        $this->get('/blog/counted'); // same session — should not double count

        $this->assertSame(1, $post->fresh()->view_count);
    }

    public function test_admin_preview_does_not_increment_view_count(): void
    {
        $post = $this->publish('adminview');
        $admin = User::create(['email' => 'a@example.com', 'role' => 'admin']);

        $this->actingAs($admin)->get('/blog/adminview')->assertOk();

        $this->assertSame(0, $post->fresh()->view_count);
    }

    /** FR-012: the CTA text switches on the linked course's high-ticket type. */
    public function test_related_course_payload_flags_high_ticket(): void
    {
        $highTicket = $this->makeCourse(['type' => 'high_ticket']);
        $post = $this->publish('with-high-ticket', ['related_course_id' => $highTicket->id]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page->component('Blog/Show')
                ->where('post.related_course.is_high_ticket', true));

        $standard = $this->makeCourse();
        $post2 = $this->publish('with-standard', ['related_course_id' => $standard->id]);

        $this->get("/blog/{$post2->slug}")
            ->assertInertia(fn ($page) => $page->component('Blog/Show')
                ->where('post.related_course.is_high_ticket', false));
    }

    public function test_tag_archive_and_empty_state(): void
    {
        $post = $this->publish('tagged');
        $tag = Tag::create(['name' => 'Prompt', 'slug' => 'prompt']);
        $post->tags()->sync([$tag->id]);

        $this->get('/blog/tag/prompt')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Blog/Tag')->has('posts.data', 1));

        // Unknown tag → empty state, not error
        $this->get('/blog/tag/does-not-exist')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Blog/Tag')->has('posts.data', 0));
    }

    public function test_rss_feed_outputs_published_posts(): void
    {
        $this->publish('feed-post');

        $res = $this->get('/blog/feed');

        $res->assertOk();
        $this->assertStringContainsString('application/rss+xml', $res->headers->get('Content-Type'));
        $res->assertSee('feed-post', false);
    }

    public function test_sitemap_includes_posts_and_tags(): void
    {
        $post = $this->publish('mapme');
        $tag = Tag::create(['name' => 'AI', 'slug' => 'ai']);
        $post->tags()->sync([$tag->id]);

        $res = $this->get('/sitemap.xml');

        $res->assertOk();
        $res->assertSee('/blog/mapme', false);
        $res->assertSee('/blog/tag/ai', false);
    }
}

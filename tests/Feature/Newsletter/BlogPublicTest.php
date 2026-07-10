<?php

namespace Tests\Feature\Newsletter;

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

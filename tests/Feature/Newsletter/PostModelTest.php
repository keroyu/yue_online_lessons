<?php

namespace Tests\Feature\Newsletter;

use App\Models\Broadcast;
use App\Models\NewsletterEmailEvent;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_scope_published_excludes_draft_scheduled_and_future(): void
    {
        Post::create(['slug' => 'a', 'title' => 'A', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()->subDay()]);
        Post::create(['slug' => 'b', 'title' => 'B', 'body_md' => 'x', 'status' => 'draft']);
        Post::create(['slug' => 'c', 'title' => 'C', 'body_md' => 'x', 'status' => 'scheduled', 'published_at' => now()->addDay()]);
        Post::create(['slug' => 'd', 'title' => 'D', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()->addDay()]);

        $slugs = Post::published()->pluck('slug')->all();

        $this->assertSame(['a'], $slugs);
    }

    public function test_post_tag_relationship_and_unique_pivot(): void
    {
        $post = Post::create(['slug' => 'p', 'title' => 'P', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()]);
        $tag = Tag::create(['name' => 'Prompt', 'slug' => 'prompt']);

        $post->tags()->sync([$tag->id, $tag->id]);

        $this->assertCount(1, $post->fresh()->tags);
        $this->assertTrue($tag->posts->contains($post));
    }

    public function test_view_count_defaults_zero_and_increments_atomically(): void
    {
        $post = Post::create(['slug' => 'v', 'title' => 'V', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()]);

        $this->assertSame(0, $post->fresh()->view_count);

        $post->increment('view_count');

        $this->assertSame(1, $post->fresh()->view_count);
    }

    public function test_newsletter_subscribed_scope_and_default_status(): void
    {
        $subscribed = User::create(['email' => 's@example.com', 'newsletter_status' => 'subscribed']);
        $dormant = User::create(['email' => 'd@example.com', 'newsletter_status' => 'dormant']);
        $plain = User::create(['email' => 'p@example.com']);

        $this->assertSame('none', $plain->fresh()->newsletter_status);

        $emails = User::newsletterSubscribed()->pluck('email')->all();

        $this->assertSame(['s@example.com'], $emails);
        $this->assertTrue($subscribed->isNewsletterSubscribed());
        $this->assertFalse($dormant->isNewsletterSubscribed());
    }

    public function test_broadcast_opened_count_deduplicates_per_user(): void
    {
        $post = Post::create(['slug' => 'bp', 'title' => 'BP', 'body_md' => 'x', 'status' => 'published', 'published_at' => now()]);
        $broadcast = Broadcast::create(['post_id' => $post->id, 'subject' => 'BP', 'recipients_count' => 2]);
        $u1 = User::create(['email' => 'u1@example.com']);
        $u2 = User::create(['email' => 'u2@example.com']);

        NewsletterEmailEvent::firstOrCreate(['broadcast_id' => $broadcast->id, 'user_id' => $u1->id, 'event_type' => 'opened']);
        NewsletterEmailEvent::firstOrCreate(['broadcast_id' => $broadcast->id, 'user_id' => $u1->id, 'event_type' => 'opened']);
        NewsletterEmailEvent::firstOrCreate(['broadcast_id' => $broadcast->id, 'user_id' => $u2->id, 'event_type' => 'opened']);

        $this->assertSame(2, $broadcast->openedCount());
    }
}

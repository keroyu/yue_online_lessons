<?php

namespace Tests\Feature\Newsletter;

use App\Mail\NewsletterBroadcastMail;
use App\Models\Broadcast;
use App\Models\NewsletterEmailEvent;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@example.com'], ['role' => 'admin']);
    }

    private function publishedPost(): Post
    {
        return Post::create([
            'slug' => 'send-me', 'title' => '寄我', 'body_md' => "內容\n\nhttps://youtu.be/dQw4w9WgXcQ",
            'excerpt' => '摘要', 'status' => 'published', 'published_at' => now()->subDay(),
        ]);
    }

    private function subscriber(string $email): User
    {
        return User::create([
            'email' => $email, 'role' => 'member',
            'newsletter_status' => 'subscribed',
            'newsletter_subscribed_at' => now()->subDays(5),
            'newsletter_unsubscribe_token' => 'tok-' . uniqid(),
        ]);
    }

    public function test_send_broadcast_mails_only_subscribed_and_records_counts(): void
    {
        Mail::fake();
        $post = $this->publishedPost();
        $this->subscriber('a@example.com');
        $this->subscriber('b@example.com');
        // excluded recipients
        User::create(['email' => 'unsub@example.com', 'role' => 'member', 'newsletter_status' => 'unsubscribed']);
        User::create(['email' => 'dorm@example.com', 'role' => 'member', 'newsletter_status' => 'dormant']);

        $this->actingAs($this->admin())
            ->post('/admin/broadcasts', ['post_id' => $post->id])
            ->assertRedirect();

        Mail::assertSent(NewsletterBroadcastMail::class, 2);

        $broadcast = Broadcast::first();
        $this->assertSame(2, $broadcast->recipients_count);
        $this->assertSame(2, $broadcast->sent_count);
        $this->assertSame('sent', $broadcast->status);
    }

    public function test_scheduled_broadcast_is_not_sent_until_due(): void
    {
        Mail::fake();
        $post = $this->publishedPost();
        $this->subscriber('s@example.com');

        // Schedule for the future → creates a scheduled broadcast, nothing sent yet.
        $this->actingAs($this->admin())
            ->post('/admin/broadcasts', ['post_id' => $post->id, 'scheduled_at' => now()->addHour()->format('Y-m-d\TH:i')])
            ->assertRedirect();

        $broadcast = Broadcast::first();
        $this->assertSame('scheduled', $broadcast->status);
        $this->assertNotNull($broadcast->scheduled_at);
        Mail::assertNothingSent();

        // Command before due time → still nothing.
        $this->artisan('newsletter:send-scheduled')->assertSuccessful();
        Mail::assertNothingSent();

        // Move the schedule into the past, then run → it sends.
        $broadcast->update(['scheduled_at' => now()->subMinute()]);
        $this->artisan('newsletter:send-scheduled')->assertSuccessful();

        Mail::assertSent(NewsletterBroadcastMail::class, 1);
        $this->assertSame('sent', $broadcast->fresh()->status);
        $this->assertSame(1, $broadcast->fresh()->recipients_count);
    }

    public function test_past_schedule_time_is_rejected(): void
    {
        $post = $this->publishedPost();

        $this->actingAs($this->admin())
            ->post('/admin/broadcasts', ['post_id' => $post->id, 'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i')])
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_search_posts_endpoint_filters_published(): void
    {
        $this->publishedPost(); // '寄我' slug send-me
        Post::create(['slug' => 'draft-hidden', 'title' => '草稿不該出現', 'body_md' => 'x', 'status' => 'draft']);

        // Matches the published post by slug.
        $this->actingAs($this->admin())
            ->getJson('/admin/broadcasts/search-posts?q=' . urlencode('send-me'))
            ->assertOk()
            ->assertJsonCount(1, 'posts');

        // Draft post is excluded from search (published scope).
        $this->actingAs($this->admin())
            ->getJson('/admin/broadcasts/search-posts?q=' . urlencode('draft-hidden'))
            ->assertOk()
            ->assertJsonCount(0, 'posts');
    }

    public function test_draft_post_cannot_be_broadcast(): void
    {
        Mail::fake();
        $draft = Post::create(['slug' => 'd', 'title' => 'D', 'body_md' => 'x', 'status' => 'draft']);

        $this->actingAs($this->admin())
            ->post('/admin/broadcasts', ['post_id' => $draft->id])
            ->assertSessionHasErrors('post_id');

        $this->assertSame(0, Broadcast::count());
        Mail::assertNothingSent();
    }

    public function test_open_pixel_records_event_and_updates_last_opened(): void
    {
        $post = $this->publishedPost();
        $user = $this->subscriber('open@example.com');
        $broadcast = Broadcast::create(['post_id' => $post->id, 'subject' => 'x', 'recipients_count' => 1]);

        $url = URL::temporarySignedRoute('newsletter.track.open', now()->addDay(), [
            'broadcast' => $broadcast->id, 'user' => $user->id,
        ]);

        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/gif');

        $this->assertDatabaseHas('newsletter_email_events', [
            'broadcast_id' => $broadcast->id, 'user_id' => $user->id, 'event_type' => 'opened',
        ]);
        $this->assertNotNull($user->fresh()->newsletter_last_opened_at);
    }

    public function test_open_dedupes_and_invalid_signature_is_ignored(): void
    {
        $post = $this->publishedPost();
        $user = $this->subscriber('dedupe@example.com');
        $broadcast = Broadcast::create(['post_id' => $post->id, 'subject' => 'x', 'recipients_count' => 1]);

        $url = URL::temporarySignedRoute('newsletter.track.open', now()->addDay(), [
            'broadcast' => $broadcast->id, 'user' => $user->id,
        ]);
        $this->get($url);
        $this->get($url);

        $this->assertSame(1, NewsletterEmailEvent::count());

        // Unsigned request still returns a pixel but records nothing.
        $this->get("/newsletter/track/open?broadcast={$broadcast->id}&user={$user->id}")->assertOk();
        $this->assertSame(1, NewsletterEmailEvent::count());
    }

    public function test_open_reactivates_dormant_subscriber(): void
    {
        $post = $this->publishedPost();
        $user = User::create([
            'email' => 'revive@example.com', 'role' => 'member',
            'newsletter_status' => 'dormant',
            'newsletter_unsubscribe_token' => 'tok-r',
        ]);
        $broadcast = Broadcast::create(['post_id' => $post->id, 'subject' => 'x', 'recipients_count' => 1]);

        $url = URL::temporarySignedRoute('newsletter.track.open', now()->addDay(), [
            'broadcast' => $broadcast->id, 'user' => $user->id,
        ]);
        $this->get($url);

        $this->assertSame('subscribed', $user->fresh()->newsletter_status);
    }

    public function test_clean_dormant_command_marks_inactive_and_exempts_never_mailed(): void
    {
        // Broadcast sent 10 days ago.
        $post = $this->publishedPost();
        Broadcast::create([
            'post_id' => $post->id, 'subject' => 'x', 'status' => 'sent',
            'recipients_count' => 1, 'sent_count' => 1, 'sent_at' => now()->subDays(10),
        ]);

        // Subscribed before that broadcast, never opened → should go dormant.
        $stale = $this->subscriber('stale@example.com');
        $stale->update(['newsletter_subscribed_at' => now()->subDays(30)]);

        // Subscribed AFTER the last broadcast → never mailed, exempt.
        $fresh = $this->subscriber('fresh@example.com');
        $fresh->update(['newsletter_subscribed_at' => now()->subDay()]);

        // Opened recently → active, exempt.
        $active = $this->subscriber('active@example.com');
        $active->update(['newsletter_subscribed_at' => now()->subDays(30), 'newsletter_last_opened_at' => now()->subDays(5)]);

        $this->artisan('newsletter:clean-dormant')->assertSuccessful();

        $this->assertSame('dormant', $stale->fresh()->newsletter_status);
        $this->assertSame('subscribed', $fresh->fresh()->newsletter_status);
        $this->assertSame('subscribed', $active->fresh()->newsletter_status);
    }
}

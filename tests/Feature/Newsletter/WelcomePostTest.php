<?php

namespace Tests\Feature\Newsletter;

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterEmailEvent;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 012 US9 — the welcome mail is an existing article, not a second template.
 *
 * `newsletter_welcome_post_id` has no foreign key behind it, so the thing worth
 * pinning is the descent (FR-017): chosen post → earliest published → the short
 * template that has to survive, because a site with no articles yet is exactly
 * when a welcome mail matters most.
 */
class WelcomePostTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function article(string $title, string $slug, string $publishedAt, string $status = 'published'): Post
    {
        return Post::create([
            'title'        => $title,
            'slug'         => $slug,
            'body_md'      => "內文\n\n第二段",
            'status'       => $status,
            'published_at' => $publishedAt,
        ]);
    }

    private function subscribe(string $email = 'visitor@example.com'): void
    {
        $this->post('/newsletter/quick-subscribe', ['email' => $email, 'nickname' => '訪客']);
    }

    private function sentWelcome(): NewsletterWelcomeMail
    {
        $captured = null;
        Mail::assertSent(NewsletterWelcomeMail::class, function ($mail) use (&$captured) {
            $captured = $mail;

            return true;
        });

        return $captured;
    }

    public function test_without_a_setting_the_earliest_published_post_is_used(): void
    {
        Mail::fake();
        $first = $this->article('最早那篇', 'first-post', '2026-01-01 00:00:00');
        $this->article('比較新的', 'newer-post', '2026-06-01 00:00:00');

        $this->subscribe();

        $this->assertSame($first->id, $this->sentWelcome()->post->id);
    }

    public function test_the_chosen_post_wins(): void
    {
        Mail::fake();
        $this->article('最早那篇', 'first-post', '2026-01-01 00:00:00');
        $chosen = $this->article('我選的', 'chosen-post', '2026-06-01 00:00:00');
        SiteSetting::set('newsletter_welcome_post_id', (string) $chosen->id);

        $this->subscribe();

        $this->assertSame($chosen->id, $this->sentWelcome()->post->id);
    }

    public function test_a_deleted_choice_falls_back_to_the_earliest(): void
    {
        Mail::fake();
        $first = $this->article('最早那篇', 'first-post', '2026-01-01 00:00:00');
        $chosen = $this->article('我選的', 'chosen-post', '2026-06-01 00:00:00');
        SiteSetting::set('newsletter_welcome_post_id', (string) $chosen->id);
        $chosen->delete();

        $this->subscribe();

        $this->assertSame($first->id, $this->sentWelcome()->post->id);
    }

    public function test_a_choice_pulled_back_to_draft_falls_back_to_the_earliest(): void
    {
        Mail::fake();
        $first = $this->article('最早那篇', 'first-post', '2026-01-01 00:00:00');
        $chosen = $this->article('我選的', 'chosen-post', '2026-06-01 00:00:00');
        SiteSetting::set('newsletter_welcome_post_id', (string) $chosen->id);
        $chosen->update(['status' => 'draft']);

        $this->subscribe();

        $this->assertSame($first->id, $this->sentWelcome()->post->id);
    }

    public function test_with_no_published_posts_the_short_template_is_used_and_the_subscription_still_succeeds(): void
    {
        Mail::fake();
        $this->article('還沒發布', 'draft-post', '2026-01-01 00:00:00', 'draft');

        $this->subscribe();

        $this->assertNull($this->sentWelcome()->post);
        $this->assertSame('subscribed', User::where('email', 'visitor@example.com')->first()->newsletter_status);
    }

    public function test_the_welcome_mail_is_titled_after_the_post_and_tagged_as_welcome(): void
    {
        Mail::fake();
        $first = $this->article('最早那篇', 'first-post', '2026-01-01 00:00:00');

        $this->subscribe();
        $mail = $this->sentWelcome();

        $this->assertSame('最早那篇', $mail->envelope()->subject);
        $this->assertStringContainsString('utm_campaign=welcome', $mail->postUrl);
        $this->assertStringContainsString('utm_content=first-post', $mail->postUrl);
        // No broadcast to hang an open event on, so no pixel at all (D15).
        $this->assertNull($mail->openPixelUrl);
    }

    public function test_the_welcome_mail_records_no_open_event(): void
    {
        Mail::fake();
        $this->article('最早那篇', 'first-post', '2026-01-01 00:00:00');

        $this->subscribe();

        $this->assertSame(0, NewsletterEmailEvent::count());
    }

    public function test_an_admin_can_set_the_welcome_post_from_the_broadcast_page(): void
    {
        $chosen = $this->article('我選的', 'chosen-post', '2026-06-01 00:00:00');

        $this->actingAs($this->admin())
            ->patch('/admin/broadcasts/welcome-post', ['post_id' => $chosen->id])
            ->assertRedirect();

        $this->assertSame((string) $chosen->id, (string) SiteSetting::get('newsletter_welcome_post_id'));
    }

    public function test_the_broadcast_page_reports_the_current_welcome_post(): void
    {
        $chosen = $this->article('我選的', 'chosen-post', '2026-06-01 00:00:00');
        SiteSetting::set('newsletter_welcome_post_id', (string) $chosen->id);

        $this->actingAs($this->admin())->get('/admin/broadcasts')->assertInertia(function ($page) use ($chosen) {
            $this->assertSame($chosen->id, $page->toArray()['props']['welcomePostId']);
        });
    }

    public function test_a_non_admin_cannot_set_the_welcome_post(): void
    {
        $chosen = $this->article('我選的', 'chosen-post', '2026-06-01 00:00:00');
        $member = User::create(['email' => 'member@example.com', 'role' => 'member']);

        // AdminMiddleware redirects rather than 403s — the point is that the
        // setting is untouched, not which status code says so.
        $this->actingAs($member)
            ->patch('/admin/broadcasts/welcome-post', ['post_id' => $chosen->id])
            ->assertRedirect('/');

        $this->assertNull(SiteSetting::get('newsletter_welcome_post_id'));
    }
}

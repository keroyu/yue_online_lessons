<?php

namespace Tests\Feature\Newsletter;

use App\Mail\NewsletterBroadcastMail;
use App\Mail\NewsletterWelcomeMail;
use App\Models\Broadcast;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 012 FR-013 — the newsletter footer's brand name must come from the admin's
 * homepage "標題" (site_settings.hero_title), not config('app.name'). The two
 * have drifted apart in production (APP_NAME vs the owner-facing hero title),
 * and the footer was reading the wrong one.
 */
class EmailBrandNameTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(): Post
    {
        return Post::create([
            'slug' => 'brand-name-test',
            'title' => 'T',
            'body_md' => '# T',
            'excerpt' => 'x',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    private function makeUser(): User
    {
        return User::create([
            'email' => 'reader@example.com',
            'newsletter_unsubscribe_token' => 'tok-123',
        ]);
    }

    public function test_broadcast_html_and_text_use_hero_title(): void
    {
        SiteSetting::set('hero_title', '測試品牌名');
        $post = $this->makePost();
        $user = $this->makeUser();
        $broadcast = tap(new Broadcast(['post_id' => $post->id, 'subject' => 'S']), fn ($b) => $b->id = 1);

        $html = (new NewsletterBroadcastMail($broadcast, $user, $post))->render();
        $this->assertStringContainsString('測試品牌名', $html);

        $text = view('emails.newsletter-broadcast-text', [
            'post' => $post,
            'postUrl' => 'https://example.test/blog/x',
            'unsubscribeUrl' => 'https://example.test/newsletter/unsubscribe/tok-123',
        ])->render();
        $this->assertStringContainsString('測試品牌名', $text);
    }

    public function test_welcome_mail_uses_hero_title(): void
    {
        SiteSetting::set('hero_title', '測試品牌名');
        $user = $this->makeUser();

        $html = (new NewsletterWelcomeMail($user))->render();

        $this->assertStringContainsString('測試品牌名', $html);
    }

    public function test_falls_back_to_app_name_when_hero_title_is_unset(): void
    {
        $user = $this->makeUser();

        $html = (new NewsletterWelcomeMail($user))->render();

        $this->assertStringContainsString(config('app.name', '經營者時間銀行'), $html);
    }
}

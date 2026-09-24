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
 * 012 FR-013 — the newsletter footer's brand name comes from the site name the
 * admin maintains (site_settings.site_name), never from config('app.name'):
 * APP_NAME is a system identifier and the two have long been different values
 * in production. `hero_title` stays as the fallback because it was where the
 * outward-facing name lived before 000 US12 gave it a field of its own.
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

    public function test_broadcast_html_and_text_use_the_site_name(): void
    {
        SiteSetting::set('site_name', '測試品牌名');
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

    public function test_welcome_mail_uses_the_site_name(): void
    {
        SiteSetting::set('site_name', '測試品牌名');
        $user = $this->makeUser();

        $html = (new NewsletterWelcomeMail($user))->render();

        $this->assertStringContainsString('測試品牌名', $html);
    }

    public function test_falls_back_to_the_hero_title_then_to_app_name(): void
    {
        SiteSetting::set('hero_title', '舊的品牌名');
        $user = $this->makeUser();

        $this->assertStringContainsString('舊的品牌名', (new NewsletterWelcomeMail($user))->render());

        SiteSetting::set('hero_title', '');

        $this->assertStringContainsString(config('app.name'), (new NewsletterWelcomeMail($user))->render());
    }
}

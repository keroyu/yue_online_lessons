<?php

namespace Tests\Feature\Newsletter;

use App\Mail\NewsletterBroadcastMail;
use App\Mail\NewsletterWelcomeMail;
use App\Models\Broadcast;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 012 US10 — the newsletter carries the whole post; only videos are left as a
 * thumbnail (or a plain notice) pointing back to the site.
 */
class NewsletterFullTextTest extends TestCase
{
    use RefreshDatabase;

    private const BODY = <<<'MD'
第一段文字，寫在信裡就要讀得到。

https://youtu.be/dQw4w9WgXcQ

第二段，句中的網址 https://youtu.be/dQw4w9WgXcQ 不會被換掉。

https://vimeo.com/76979871

![示意圖](/storage/posts/pic.png)

<iframe src="https://www.youtube.com/embed/abc"></iframe>

延伸閱讀：[另一篇](/blog/other-post)
MD;

    private function makePost(): Post
    {
        return Post::create([
            'slug' => 'full-text', 'title' => '全文標題', 'body_md' => self::BODY,
            'excerpt' => '這是摘要不該出現', 'status' => 'published', 'published_at' => now()->subDay(),
        ]);
    }

    private function subscriber(): User
    {
        return User::create([
            'email' => 'reader@example.com', 'role' => 'member',
            'newsletter_status' => 'subscribed',
            'newsletter_unsubscribe_token' => 'tok-reader',
        ]);
    }

    private function broadcastMail(): NewsletterBroadcastMail
    {
        $post = $this->makePost();
        $broadcast = Broadcast::create(['post_id' => $post->id, 'subject' => '主旨', 'recipients_count' => 1]);

        return new NewsletterBroadcastMail($broadcast, $this->subscriber(), $post);
    }

    public function test_html_carries_the_whole_post_without_excerpt_or_cta(): void
    {
        $html = $this->broadcastMail()->render();

        $this->assertStringContainsString('第一段文字，寫在信裡就要讀得到。', $html);
        $this->assertStringContainsString('延伸閱讀', $html);
        $this->assertStringNotContainsString('這是摘要不該出現', $html);
        $this->assertStringNotContainsString('在網站上閱讀全文', $html);
        $this->assertStringContainsString('在網站上閱讀這篇文章', $html);
    }

    public function test_videos_become_thumbnail_or_notice_and_no_iframe_survives(): void
    {
        $html = $this->broadcastMail()->render();

        // YouTube line → thumbnail linking back to the post.
        $this->assertStringContainsString('img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $html);
        $this->assertMatchesRegularExpression('#<a href="[^"]*/blog/full-text[^"]*"[^>]*>\s*<img[^>]+hqdefault#', $html);
        // One notice each for the YouTube line, the Vimeo line and the raw iframe.
        $this->assertSame(3, substr_count($html, '點此到網站觀看'));
        $this->assertStringNotContainsString('vimeocdn', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        // A URL inside a sentence is left as text/link, not replaced.
        $this->assertStringContainsString('句中的網址', $html);
        $this->assertSame(1, substr_count($html, 'hqdefault.jpg'));
    }

    public function test_images_are_absolute_and_capped_and_links_are_tagged(): void
    {
        $html = $this->broadcastMail()->render();

        $this->assertStringContainsString('src="' . url('/storage/posts/pic.png') . '"', $html);
        $this->assertMatchesRegularExpression('#<img(?=[^>]*pic\.png)(?=[^>]*max-width:100%)[^>]*>#', $html);
        $this->assertMatchesRegularExpression('#href="' . preg_quote(url('/blog/other-post'), '#') . '\?[^"]*utm_campaign=broadcast-\d+#', $html);
    }

    public function test_open_pixel_comes_before_the_body(): void
    {
        $html = $this->broadcastMail()->render();

        $pixel = strpos($html, '/newsletter/track/open');
        $body = strpos($html, '第一段文字');

        $this->assertNotFalse($pixel);
        $this->assertLessThan($body, $pixel);
    }

    public function test_plain_text_is_full_text_with_video_notice(): void
    {
        $mail = $this->broadcastMail();

        $mail->assertSeeInText('第一段文字，寫在信裡就要讀得到。');
        $mail->assertSeeInText('延伸閱讀');
        $mail->assertSeeInText('▶ 影片請到網站觀看：' . $mail->postUrl, false);
        $mail->assertDontSeeInText('utm\_', false);
        $mail->assertDontSeeInText('hqdefault.jpg');
        $mail->assertDontSeeInText('這是摘要不該出現');
    }

    public function test_welcome_mail_is_full_text_without_pixel(): void
    {
        $html = (new NewsletterWelcomeMail($this->subscriber(), $this->makePost()))->render();

        $this->assertStringContainsString('第一段文字，寫在信裡就要讀得到。', $html);
        $this->assertStringContainsString('utm_campaign=welcome', $html);
        $this->assertStringNotContainsString('/newsletter/track/open', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }
}

<?php

namespace Tests\Feature\Storefront;

use App\Jobs\SendDripEmailJob;
use App\Mail\DripLessonMail;
use App\Mail\NewsletterBroadcastMail;
use App\Models\Broadcast;
use App\Models\Course;
use App\Models\DripSubscription;
use App\Models\Lesson;
use App\Models\Post;
use App\Models\User;
use App\Services\EmailLinkTagger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 002 US14 — outgoing mail bodies get their first-party links stamped with UTM
 * at send time, so the existing funnel report can tell what a letter brought in.
 *
 * The rules that matter here are the ones that make the stamp safe to apply
 * blindly to every link in every letter: never touch a foreign host, never
 * overwrite an attribution the admin set by hand (FR-026), and never lose a
 * query param or fragment that was already carrying meaning.
 */
class EmailLinkTaggerTest extends TestCase
{
    use RefreshDatabase;

    private const UTM = [
        'utm_source'   => 'drip',
        'utm_medium'   => 'email',
        'utm_campaign' => 'ai-road-map',
        'utm_content'  => 'lesson-2',
    ];

    private function tagger(): EmailLinkTagger
    {
        config(['app.url' => 'https://learn.example.com']);

        return app(EmailLinkTagger::class);
    }

    private function queryOf(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);

        return $params;
    }

    public function test_first_party_absolute_url_gets_every_utm_key(): void
    {
        $tagged = $this->tagger()->tagUrl('https://learn.example.com/course/ai', self::UTM);

        $this->assertSame(self::UTM, $this->queryOf($tagged));
        $this->assertStringStartsWith('https://learn.example.com/course/ai?', $tagged);
    }

    public function test_relative_url_is_first_party(): void
    {
        $tagged = $this->tagger()->tagUrl('/course/ai', self::UTM);

        $this->assertSame(self::UTM, $this->queryOf($tagged));
        $this->assertStringStartsWith('/course/ai?', $tagged);
    }

    public function test_www_prefix_still_counts_as_our_own_host(): void
    {
        $tagged = $this->tagger()->tagUrl('https://www.learn.example.com/course/ai', self::UTM);

        $this->assertSame(self::UTM, $this->queryOf($tagged));
    }

    /** @dataProvider untouchableUrls */
    public function test_urls_that_must_be_left_alone(string $url): void
    {
        $this->assertSame($url, $this->tagger()->tagUrl($url, self::UTM));
    }

    public static function untouchableUrls(): array
    {
        return [
            'external host'   => ['https://portaly.cc/yueyu'],
            'external www'    => ['https://www.youtube.com/watch?v=abc'],
            'mailto'          => ['mailto:hi@example.com'],
            'tel'             => ['tel:+886912345678'],
            'anchor only'     => ['#section-2'],
            'javascript'      => ['javascript:void(0)'],
            'data uri'        => ['data:text/plain,hello'],
            // Already attributed by hand — FR-026 says a manual tag outranks us.
            'has utm_source'  => ['https://learn.example.com/course/ai?utm_source=instagram&utm_medium=bio'],
        ];
    }

    public function test_existing_query_and_fragment_survive(): void
    {
        $tagged = $this->tagger()->tagUrl('/course/ai?coupon=SAVE10#pricing', self::UTM);

        $this->assertSame(
            ['coupon' => 'SAVE10'] + self::UTM,
            $this->queryOf($tagged),
        );
        $this->assertStringEndsWith('#pricing', $tagged);
        // The query has to sit before the fragment or the link is broken.
        $this->assertLessThan(strpos($tagged, '#'), strpos($tagged, '?'));
    }

    public function test_html_hrefs_are_stamped_and_entities_survive_the_round_trip(): void
    {
        $html = '<p>See <a href="https://learn.example.com/course/ai?a=1&amp;b=2">the course</a>'
            . ' or <a href="https://portaly.cc/x">the other place</a>.</p>';

        $tagged = $this->tagger()->tagHtml($html, self::UTM);

        $this->assertStringContainsString('utm_source=drip', $tagged);
        $this->assertStringContainsString('https://portaly.cc/x', $tagged);
        $this->assertStringNotContainsString('portaly.cc/x?utm', $tagged);

        // &amp; must stay a single escape — a second pass would yield &amp;amp;.
        $this->assertStringNotContainsString('&amp;amp;', $tagged);
        $this->assertStringContainsString('a=1&amp;b=2', $tagged);
    }

    public function test_html_handles_single_quoted_hrefs(): void
    {
        $tagged = $this->tagger()->tagHtml("<a href='/course/ai'>x</a>", self::UTM);

        $this->assertStringContainsString('utm_campaign=ai-road-map', $tagged);
    }

    public function test_empty_html_stays_empty(): void
    {
        $this->assertSame('', $this->tagger()->tagHtml('', self::UTM));
    }

    // --- end to end: what the reader actually receives ---

    public function test_a_sequence_letter_ships_with_its_lesson_stamped(): void
    {
        $course = Course::create([
            'name' => '免費電子書', 'slug' => 'free-ebook', 'tagline' => 't', 'description' => 'd',
            'price' => 0, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'drip', 'drip_interval_days' => 2,
            'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);

        // sort_order starts at 1 like the admin produces — the letter number has
        // to come from the running order, not this value (010 FR-022).
        $lessons = [];
        foreach ([1, 2] as $i => $sortOrder) {
            $lessons[] = Lesson::create([
                'course_id' => $course->id, 'title' => "L{$i}",
                'video_platform' => 'vimeo', 'video_id' => '1032766965',
                'sort_order' => $sortOrder,
                'content_md' => "去看看 {{classroom_url}}\n\n也可以直接看[課程頁](/course/free-ebook)，"
                    . "或我朋友的[外站文章](https://example.org/post)。",
            ]);
        }

        $user = User::factory()->create();
        $subscription = DripSubscription::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'subscribed_at' => now(), 'emails_sent' => 2, 'status' => 'active',
            'status_changed_at' => now(), 'unsubscribe_token' => uniqid(),
        ]);

        Mail::fake();
        (new SendDripEmailJob($user->id, $lessons[1]->id, $subscription->id))->handle();

        Mail::assertSent(DripLessonMail::class, function (DripLessonMail $mail) {
            $html = $mail->htmlContent;

            $this->assertStringContainsString('utm_source=drip', $html);
            $this->assertStringContainsString('utm_medium=email', $html);
            $this->assertStringContainsString('utm_campaign=free-ebook', $html);
            // Second in the running order, so lesson-2 — not lesson-3 from sort_order.
            $this->assertStringContainsString('utm_content=lesson-2', $html);
            // Somebody else's site keeps its own URL.
            $this->assertStringContainsString('https://example.org/post"', $html);

            return true;
        });
    }

    public function test_a_broadcast_ships_with_its_post_stamped(): void
    {
        $post = Post::create([
            'slug' => 'my-post', 'title' => '文章', 'body_md' => '內容',
            'excerpt' => '摘要', 'status' => 'published', 'published_at' => now()->subDay(),
        ]);
        $broadcast = Broadcast::create([
            'post_id' => $post->id, 'subject' => '主旨', 'recipients_count' => 1,
        ]);
        $user = User::create([
            'email' => 'reader@example.com', 'role' => 'member',
            'newsletter_status' => 'subscribed',
            'newsletter_unsubscribe_token' => 'tok-' . uniqid(),
        ]);

        $mail = new NewsletterBroadcastMail($broadcast, $user, $post);

        $this->assertStringContainsString('utm_source=newsletter', $mail->postUrl);
        $this->assertStringContainsString('utm_medium=email', $mail->postUrl);
        $this->assertStringContainsString("utm_campaign=broadcast-{$broadcast->id}", $mail->postUrl);
        $this->assertStringContainsString('utm_content=my-post', $mail->postUrl);

        // The unsubscribe link is plumbing, not a campaign destination.
        $this->assertStringNotContainsString('utm_', $mail->unsubscribeUrl);
    }
}

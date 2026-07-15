<?php

namespace Tests\Feature\Classroom;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CloudflareStreamService;
use App\Services\VideoEmbedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cloudflare Stream video source (003 US9): URL/UID parsing, locally-signed
 * playback tokens (RS256 JWT), lesson form validation, and classroom embed_url.
 */
class CloudflareStreamTest extends TestCase
{
    use RefreshDatabase;

    private const UID = '5d5bc37ffcf54c9b82e996823bffbb81';

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function makeCourse(): Course
    {
        return Course::create([
            'name' => 'C', 'slug' => 'c-1', 'tagline' => 't', 'description' => 'd',
            'price' => 1000, 'instructor_name' => 'I', 'type' => 'lecture', 'status' => 'selling',
            'course_type' => 'standard', 'is_published' => true, 'is_visible' => true, 'payment_gateway' => 'payuni',
        ]);
    }

    /** Generate a throwaway RSA keypair and point config at it (base64 PEM, as stored in .env). */
    private function configureSigningKeys(): void
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $pem);

        config([
            'services.cloudflare_stream.customer_code' => 'abc123',
            'services.cloudflare_stream.key_id' => 'test-key-id',
            'services.cloudflare_stream.private_key' => base64_encode($pem),
            'services.cloudflare_stream.token_ttl' => 3600,
        ]);
    }

    private function decodeJwtPayload(string $token): array
    {
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'token must be a three-segment JWT');

        return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    }

    // ── VideoEmbedService::parse ─────────────────────────────────────────────

    public function test_parse_recognizes_cloudflare_url_formats(): void
    {
        $service = new VideoEmbedService();
        $uid = self::UID;

        $inputs = [
            "https://customer-abc123.cloudflarestream.com/{$uid}/watch",
            "https://customer-abc123.cloudflarestream.com/{$uid}/iframe",
            "https://watch.cloudflarestream.com/{$uid}",
            "https://iframe.videodelivery.net/{$uid}",
            $uid, // bare UID copied from the Cloudflare dashboard
        ];

        foreach ($inputs as $input) {
            $result = $service->parse($input);
            $this->assertNotNull($result, "failed to parse: {$input}");
            $this->assertSame('cloudflare', $result['platform'], "wrong platform for: {$input}");
            $this->assertSame($uid, $result['video_id'], "wrong video_id for: {$input}");
        }
    }

    public function test_parse_keeps_existing_platforms_and_rejects_invalid(): void
    {
        $service = new VideoEmbedService();

        $this->assertSame('vimeo', $service->parse('https://vimeo.com/1032766965')['platform']);
        $this->assertSame('youtube', $service->parse('https://youtu.be/dQw4w9WgXcQ')['platform']);

        $this->assertNull($service->parse('https://example.com/whatever'));
        $this->assertNull($service->parse('5d5bc37ffcf54c9b82e996823bffbb8')); // 31 hex chars
        $this->assertNull($service->parse('not-a-video'));
    }

    // ── CloudflareStreamService::signedEmbedUrl ──────────────────────────────

    public function test_signed_embed_url_contains_valid_jwt(): void
    {
        $this->configureSigningKeys();

        $url = app(CloudflareStreamService::class)->signedEmbedUrl(self::UID);

        $this->assertMatchesRegularExpression(
            '#^https://customer-abc123\.cloudflarestream\.com/([^/]+)/iframe$#',
            $url
        );

        preg_match('#\.com/([^/]+)/iframe$#', $url, $m);
        $token = $m[1];
        $this->assertNotSame(self::UID, $token, 'URL must contain a token, not the raw UID');

        $payload = $this->decodeJwtPayload($token);
        $this->assertSame(self::UID, $payload['sub']);
        $this->assertSame('test-key-id', $payload['kid']);
        $this->assertEqualsWithDelta(time() + 3600, $payload['exp'], 5);

        $header = json_decode(base64_decode(strtr(explode('.', $token)[0], '-_', '+/')), true);
        $this->assertSame('RS256', $header['alg']);
        $this->assertSame('test-key-id', $header['kid']);
    }

    public function test_signed_embed_url_falls_back_to_unsigned_without_key(): void
    {
        config([
            'services.cloudflare_stream.customer_code' => 'abc123',
            'services.cloudflare_stream.key_id' => null,
            'services.cloudflare_stream.private_key' => null,
        ]);

        $this->assertSame(
            'https://customer-abc123.cloudflarestream.com/' . self::UID . '/iframe',
            app(CloudflareStreamService::class)->signedEmbedUrl(self::UID)
        );

        // No customer code either: fall back to the generic videodelivery domain
        config(['services.cloudflare_stream.customer_code' => null]);

        $this->assertSame(
            'https://iframe.videodelivery.net/' . self::UID . '/iframe',
            app(CloudflareStreamService::class)->signedEmbedUrl(self::UID)
        );
    }

    // ── StoreLessonRequest ───────────────────────────────────────────────────

    public function test_admin_can_store_lesson_with_bare_uid(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->admin())
            ->post("/admin/courses/{$course->id}/lessons", [
                'title' => 'CF 小節',
                'video_url' => self::UID,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $lesson = Lesson::where('title', 'CF 小節')->firstOrFail();
        $this->assertSame('cloudflare', $lesson->video_platform);
        $this->assertSame(self::UID, $lesson->video_id);
    }

    public function test_store_lesson_rejects_invalid_video_url(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->admin())
            ->post("/admin/courses/{$course->id}/lessons", [
                'title' => '壞連結',
                'video_url' => 'https://example.com/not-a-video',
            ])
            ->assertSessionHasErrors('video_url');

        $this->assertDatabaseMissing('lessons', ['title' => '壞連結']);
    }

    // ── Classroom playback ───────────────────────────────────────────────────

    public function test_classroom_serves_signed_embed_url_for_cloudflare_lesson(): void
    {
        $this->configureSigningKeys();

        $course = $this->makeCourse();
        Lesson::create([
            'course_id' => $course->id,
            'title' => 'CF 影片',
            'video_platform' => 'cloudflare',
            'video_id' => self::UID,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin())->get("/member/classroom/{$course->id}");

        $response->assertOk()->assertInertia(function ($page) {
            $embedUrl = $page->toArray()['props']['currentLesson']['embed_url'];
            $this->assertStringStartsWith('https://customer-abc123.cloudflarestream.com/', $embedUrl);
            $this->assertStringEndsWith('/iframe', $embedUrl);
            $this->assertStringNotContainsString(self::UID, $embedUrl, 'must serve a signed token, not the raw UID');

            preg_match('#\.com/([^/]+)/iframe$#', $embedUrl, $m);
            $payload = $this->decodeJwtPayload($m[1]);
            $this->assertSame(self::UID, $payload['sub']);

            return $page->where('currentLesson.video_platform', 'cloudflare');
        });
    }

    public function test_classroom_vimeo_embed_url_unchanged(): void
    {
        $course = $this->makeCourse();
        Lesson::create([
            'course_id' => $course->id,
            'title' => 'Vimeo 影片',
            'video_platform' => 'vimeo',
            'video_id' => '1032766965',
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin())
            ->get("/member/classroom/{$course->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('currentLesson.embed_url', 'https://player.vimeo.com/video/1032766965'));
    }
}

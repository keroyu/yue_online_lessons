<?php

namespace Tests\Feature\Newsletter;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OgImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_without_image_falls_back_to_generated_og_url(): void
    {
        $post = Post::create([
            'slug' => 'no-image', 'title' => '如何用純程式做出假的預覽圖',
            'body_md' => 'x', 'status' => 'published', 'published_at' => now(),
        ]);

        $this->assertStringEndsWith('/blog/no-image/og.png', $post->og_url);
    }

    public function test_uploaded_cover_takes_precedence_over_generated_card(): void
    {
        $post = Post::create([
            'slug' => 'has-cover', 'title' => 'x', 'body_md' => 'x',
            'status' => 'published', 'published_at' => now(),
            'cover_image_path' => 'post-images/cover.jpg',
        ]);

        $this->assertStringContainsString('cover.jpg', $post->og_url);
        $this->assertStringNotContainsString('og.png', $post->og_url);
    }

    public function test_og_route_returns_a_png_image(): void
    {
        Storage::fake('public');

        $post = Post::create([
            'slug' => 'card', 'title' => '純代碼生成 OG 卡片測試標題',
            'body_md' => 'x', 'status' => 'published', 'published_at' => now(),
        ]);

        $res = $this->get(route('blog.og', $post));

        $res->assertOk();
        $res->assertHeader('Content-Type', 'image/png');
        // PNG magic bytes.
        $this->assertStringStartsWith("\x89PNG", $res->getContent());
        Storage::disk('public')->assertExists("og/{$post->id}-".substr(sha1($post->title.'|經營者時間銀行|v2'), 0, 10).'.png');
    }
}

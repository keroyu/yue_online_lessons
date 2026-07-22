<?php

namespace Tests\Feature\Newsletter;

use App\Services\PostService;
use App\Services\VideoEmbedService;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    private function service(): PostService
    {
        return new PostService(new VideoEmbedService());
    }

    public function test_renders_markdown_to_html(): void
    {
        $html = $this->service()->toHtml("# Hello\n\nsome **bold** text");

        $this->assertStringContainsString('<h1>Hello</h1>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_strips_script_and_style_tags(): void
    {
        $html = $this->service()->toHtml("intro\n\n<script>alert(1)</script>\n\n<style>body{}</style>\n\nend");

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
    }

    public function test_standalone_youtube_line_becomes_responsive_embed(): void
    {
        $html = $this->service()->toHtml("看這支影片：\n\nhttps://www.youtube.com/watch?v=dQw4w9WgXcQ\n\n很讚");

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/dQw4w9WgXcQ', $html);
    }

    public function test_standalone_youtube_shorts_line_becomes_responsive_embed(): void
    {
        $html = $this->service()->toHtml("開頭\n\nhttps://youtube.com/shorts/TQdpBphyzNA\n\n結尾");

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('https://www.youtube.com/embed/TQdpBphyzNA', $html);
    }

    public function test_inline_video_url_inside_sentence_is_not_embedded(): void
    {
        $html = $this->service()->toHtml('請看 https://youtu.be/dQw4w9WgXcQ 這段');

        $this->assertStringNotContainsString('<iframe', $html);
    }

    /**
     * Regression: CJK text contains bytes (e.g. 0x85) that `\R` without /u treats as a
     * line break, corrupting UTF-8 and making CommonMark throw. "遠" (E9 81 A0) etc. carry
     * such bytes; ensure a post mixing CJK with a video line renders without error.
     */
    public function test_cjk_with_video_line_renders_without_encoding_error(): void
    {
        $md = "定期定額買進廣泛分散的指數，才是勝率最高的選擇。\n\nhttps://www.youtube.com/watch?v=dQw4w9WgXcQ\n\n看完影片你就懂了。";

        $html = $this->service()->toHtml($md);

        $this->assertTrue(mb_check_encoding($html, 'UTF-8'));
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('看完影片你就懂了', $html);
    }
}

<?php

namespace App\Services;

use App\Models\Post;
use League\CommonMark\CommonMarkConverter;

class PostService
{
    public function __construct(
        private VideoEmbedService $videoEmbed
    ) {}

    /**
     * Render post Markdown to sanitized HTML for the public web page.
     *
     * Rendered server-side (per D4) so the body ships in the initial payload for SEO.
     * Standalone YouTube/Vimeo URL lines become responsive embeds; script/style stripped.
     */
    public function toHtml(?string $md): string
    {
        $md = $this->embedVideoLines($md ?? '');

        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return $this->sanitize($converter->convert($md)->getContent());
    }

    /**
     * Build the og payload shared to the blade root view for a post page.
     */
    public function ogPayload(Post $post, string $url): array
    {
        $description = $post->meta_description ?: $post->excerpt ?: '';

        return [
            'type' => 'article',
            'title' => $post->seo_title ?: $post->title,
            'description' => $description,
            'url' => $url,
            'image' => $post->og_url,
            'published_time' => optional($post->published_at)->toAtomString(),
        ];
    }

    /**
     * Replace lines that are ONLY a YouTube/Vimeo URL with a raw HTML embed block.
     * Inline URLs inside a sentence are left untouched.
     */
    private function embedVideoLines(string $md): string
    {
        // Split on literal newlines only. NB: `\R` without the /u flag also matches the
        // byte 0x85, which occurs as a UTF-8 continuation byte inside many CJK characters —
        // using it here would split mid-character and corrupt the string.
        $lines = preg_split('/\r\n|\r|\n/', $md);

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || ! preg_match('#^https?://\S+$#u', $trimmed)) {
                continue;
            }

            $parsed = $this->videoEmbed->parse($trimmed);
            if ($parsed === null) {
                continue;
            }

            // Blank lines around the block so CommonMark treats it as an HTML block.
            $lines[$i] = "\n<div class=\"video-embed\"><iframe src=\"{$parsed['embed_url']}\" loading=\"lazy\" allowfullscreen></iframe></div>\n";
        }

        return implode("\n", $lines);
    }

    /**
     * Remove script/style blocks and inline event handlers. Iframes (video embeds) are kept.
     */
    private function sanitize(string $html): string
    {
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('/\son\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\son\w+\s*=\s*'[^']*'/i", '', $html);

        return $html;
    }
}

<?php

namespace App\Services;

use App\Models\Post;
use League\CommonMark\CommonMarkConverter;
use League\HTMLToMarkdown\HtmlConverter;

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
        return $this->replaceVideoLines(
            $md,
            fn (array $parsed) => "<div class=\"video-embed\"><iframe src=\"{$parsed['embed_url']}\" loading=\"lazy\" allowfullscreen></iframe></div>",
        );
    }

    /**
     * Swap every line that is ONLY a recognised video URL for the HTML block
     * `$render` returns. The web page and the newsletter share this one rule
     * so the line that becomes a player on the site is exactly the line that
     * becomes a thumbnail in the mail (012 FR-033).
     *
     * @param callable(array): string $render receives VideoEmbedService::parse() output
     */
    private function replaceVideoLines(string $md, callable $render): string
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
            $lines[$i] = "\n" . $render($parsed) . "\n";
        }

        return implode("\n", $lines);
    }

    /**
     * Render the whole post for the newsletter body (012 US10 / FR-032).
     *
     * Mail cannot play video, so each video becomes a link back to the post:
     * a YouTube thumbnail plus a notice, or the notice alone when there is no
     * keyless thumbnail (Vimeo, hand-written iframes).
     */
    public function toEmailHtml(Post $post, string $postUrl, array $utm): string
    {
        $href = e($postUrl);

        $md = $this->replaceVideoLines($post->body_md ?? '', function (array $parsed) use ($href) {
            if ($parsed['platform'] === 'youtube') {
                $thumb = "https://img.youtube.com/vi/{$parsed['video_id']}/hqdefault.jpg";

                return "<div data-email-video=\"1\" style=\"margin:0 0 20px;\">"
                    . "<a href=\"{$href}\"><img src=\"{$thumb}\" alt=\"觀看影片\" style=\"width:100%;max-width:100%;height:auto;border:0;display:block;\"></a>"
                    . "<p style=\"margin:8px 0 0;font-size:14px;\"><a href=\"{$href}\" style=\"color:#0d9488;\">▶ 這裡有一段影片，點此到網站觀看</a></p>"
                    . "</div>";
            }

            return $this->videoNotice($href);
        });

        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $html = $this->sanitize($converter->convert($md)->getContent());

        // Hand-written embeds cannot play in mail either.
        $html = preg_replace('#<iframe\b[^>]*>.*?</iframe>|<iframe\b[^>]*/?>#is', $this->videoNotice($href), $html);

        // Keep pictures inside the mail column.
        $html = preg_replace('#<img\b(?![^>]*\bstyle=)#i', '<img style="max-width:100%;height:auto;"', $html);

        // Site-relative links and images mean nothing inside a mail client.
        $html = preg_replace_callback(
            '#\b(src|href)=(["\'])(/(?!/)[^"\']*)\2#i',
            fn (array $m) => "{$m[1]}={$m[2]}" . url($m[3]) . $m[2],
            $html,
        );

        return app(EmailLinkTagger::class)->tagHtml($html, $utm);
    }

    /**
     * Plain-text twin of toEmailHtml(), converted from the final HTML so it
     * carries the same tagged links (012 FR-034).
     */
    public function toEmailText(string $html, string $postUrl): string
    {
        // The URL goes in after conversion: the converter escapes `_` in bare
        // text, which would turn `utm_source` into a broken `utm\_source`.
        $placeholder = 'EMAILVIDEOLINK';

        $html = preg_replace(
            '#<div data-email-video="1"[^>]*>.*?</div>#is',
            "<p>▶ 影片請到網站觀看：{$placeholder}</p>",
            $html,
        );

        $text = trim((new HtmlConverter([
            'strip_tags' => true,
            'suppress_errors' => true,
        ]))->convert($html));

        return str_replace($placeholder, $postUrl, $text);
    }

    private function videoNotice(string $href): string
    {
        return "<div data-email-video=\"1\" style=\"margin:0 0 20px;padding:16px;background:#f3f4f6;font-size:14px;\">"
            . "<a href=\"{$href}\" style=\"color:#0d9488;\">▶ 這裡有一段影片，點此到網站觀看</a>"
            . "</div>";
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

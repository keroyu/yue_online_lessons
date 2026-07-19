<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a fallback Open Graph card (1200x630 PNG) for posts that have no
 * uploaded OG/cover image: navy background, large wrapped title, site-name footer.
 * Rendered with GD + a bundled Traditional-Chinese TTF, cached on the public disk
 * keyed by a hash of the title so it regenerates automatically when the title changes.
 */
class OgImageService
{
    private const W = 1200;
    private const H = 630;
    private const MARGIN = 90;
    private const BRAND = '經營者時間銀行';
    private const CACHE_VERSION = 'v3'; // bump to invalidate every cached card

    private string $font;

    private string $logo;

    public function __construct()
    {
        $this->font = resource_path('fonts/NotoSansTC.ttf');
        $this->logo = resource_path('images/og-logo.png');
    }

    /**
     * Absolute URL of the generated card for a post (used as the og:image fallback).
     */
    public function url(Post $post): string
    {
        return route('blog.og', ['post' => $post->slug]);
    }

    /**
     * Ensure the cached PNG exists on the public disk and return its relative path.
     */
    public function resolvePath(Post $post): string
    {
        $disk = Storage::disk('public');
        $path = "og/{$post->id}-{$this->hash($post)}.png";

        if (! $disk->exists($path)) {
            // Drop stale cards for this post (title changed → new hash).
            foreach ($disk->files('og') as $old) {
                if (str_starts_with($old, "og/{$post->id}-")) {
                    $disk->delete($old);
                }
            }
            $disk->put($path, $this->png($post));
        }

        return $path;
    }

    private function hash(Post $post): string
    {
        return substr(sha1($post->title.'|'.self::BRAND.'|'.self::CACHE_VERSION), 0, 10);
    }

    /**
     * Render the card and return raw PNG bytes.
     */
    public function png(Post $post): string
    {
        $im = imagecreatetruecolor(self::W, self::H);
        $navy = imagecolorallocate($im, 0x37, 0x35, 0x57);
        $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
        $teal = imagecolorallocate($im, 0x3F, 0x83, 0xA3);
        imagefill($im, 0, 0, $navy);

        // Brand lockup (logo + name) anchored bottom-left.
        $logoSize = 76;
        $logoTop = self::H - 72 - $logoSize;

        // Title: wrap to fit the space above the lockup, shrinking when needed.
        $maxWidth = self::W - self::MARGIN * 2;
        [$size, $lines, $lineHeight] = $this->fitTitle($post->title, $maxWidth);

        // Vertically center the title block between the top margin and the lockup.
        $blockHeight = count($lines) * $lineHeight;
        $regionBottom = $logoTop - 28;
        $top = (int) max(self::MARGIN, (self::MARGIN + $regionBottom - $blockHeight) / 2);

        $y = $top + (int) ($size * 1.1);
        foreach ($lines as $line) {
            $this->drawBold($im, $size, self::MARGIN, $y, $white, $line);
            $y += $lineHeight;
        }

        // Brand lockup (logo + name + teal underline), anchored bottom-right.
        $brandSize = 30;
        $gap = 24;
        $box = imagettfbbox($brandSize, 0, $this->font, self::BRAND);
        $textWidth = abs($box[2] - $box[0]);

        $logoImg = @imagecreatefrompng($this->logo);
        $hasLogo = (bool) $logoImg;
        $lockupWidth = ($hasLogo ? $logoSize + $gap : 0) + $textWidth;
        $lockupLeft = self::W - self::MARGIN - $lockupWidth;

        $textX = $lockupLeft;
        if ($hasLogo) {
            imagealphablending($im, true);
            imagecopyresampled(
                $im, $logoImg,
                $lockupLeft, $logoTop, 0, 0,
                $logoSize, $logoSize, imagesx($logoImg), imagesy($logoImg)
            );
            imagedestroy($logoImg);
            $textX = $lockupLeft + $logoSize + $gap;
        }

        $brandY = $logoTop + (int) ($logoSize / 2) + (int) ($brandSize / 2) + 2;
        $this->drawBold($im, $brandSize, $textX, $brandY, $white, self::BRAND);
        // Teal underline accent beneath the brand name.
        imagefilledrectangle($im, $textX, $brandY + 12, $textX + $textWidth, $brandY + 16, $teal);

        // Max zlib compression (level 9). The card is a flat navy background with
        // text, so PNG stays ~60KB — comfortably under 150KB.
        ob_start();
        imagepng($im, null, 9);
        $bytes = ob_get_clean();
        imagedestroy($im);

        return $bytes;
    }

    /**
     * Draw text with a faux-bold weight. The bundled TTF is a variable font and
     * GD/FreeType can only render its (light) default instance, so we thicken the
     * strokes by overprinting across a small offset grid.
     */
    private function drawBold($im, int $size, int $x, int $y, int $color, string $text): void
    {
        foreach ([[0, 0], [1, 0], [2, 0], [0, 1], [1, 1], [2, 1]] as [$dx, $dy]) {
            imagettftext($im, $size, 0, $x + $dx, $y + $dy, $color, $this->font, $text);
        }
    }

    /**
     * Pick a font size and character-wrapped lines so the title fills the card
     * without overflowing. CJK has no spaces, so we wrap per character.
     */
    private function fitTitle(string $title, int $maxWidth): array
    {
        $title = trim(preg_replace('/\s+/u', ' ', $title)) ?: '（無標題）';

        foreach ([64, 58, 52, 46, 40] as $size) {
            $lines = $this->wrap($title, $size, $maxWidth);
            $lineHeight = (int) ($size * 1.5);
            if (count($lines) <= 4) {
                return [$size, $lines, $lineHeight];
            }
        }

        // Still too long at the smallest size → clamp to 4 lines with an ellipsis.
        $lines = array_slice($this->wrap($title, 40, $maxWidth), 0, 4);
        $lines[3] = mb_substr($lines[3], 0, max(0, mb_strlen($lines[3]) - 1)).'…';

        return [40, $lines, (int) (40 * 1.5)];
    }

    /**
     * Greedy character-wrap using the real rendered width (imagettfbbox).
     */
    private function wrap(string $text, int $size, int $maxWidth): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $lines = [];
        $current = '';

        foreach ($chars as $ch) {
            $candidate = $current.$ch;
            $box = imagettfbbox($size, 0, $this->font, $candidate);
            $width = abs($box[2] - $box[0]);
            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $ch;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [$text];
    }
}

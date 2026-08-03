<?php

namespace App\Models;

use App\Services\EmailMarkdownService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['name', 'event_type', 'subject', 'body_type', 'body_md'];

    public function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function renderSubject(array $vars): string
    {
        return str_replace(array_keys($vars), array_values($vars), $this->subject);
    }

    /**
     * The one place a template becomes mail HTML (FR-019). Callers must not
     * convert Markdown themselves — four of them used to, and an HTML template
     * would have rendered differently in each.
     */
    public function renderBody(array $vars): string
    {
        $body = $this->substitute($vars);

        // Pasted HTML goes out exactly as written: a sanitizer would strip the
        // inline styles and tables that are email's only layout tools (D19).
        if ($this->body_type === 'html') {
            return $body;
        }

        return EmailMarkdownService::toHtml($body);
    }

    /**
     * The text/plain half of the multipart mail (FR-020). HTML-only mail is a
     * spam-filter penalty on its own (MIME_HTML_ONLY).
     */
    public function renderText(array $vars): string
    {
        $body = $this->substitute($vars);

        // Markdown source is already the plain-text version of itself.
        return $this->body_type === 'html' ? self::htmlToText($body) : $body;
    }

    private function substitute(array $vars): string
    {
        return str_replace(array_keys($vars), array_values($vars), (string) $this->body_md);
    }

    private static function htmlToText(string $html): string
    {
        // A link whose text is already the URL must not print it twice.
        $text = preg_replace_callback(
            '#<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)</a>#is',
            function (array $m): string {
                $href = trim($m[2]);
                $label = trim(self::decode(strip_tags($m[3])));

                return $label === '' || $label === $href ? $href : "{$label} ({$href})";
            },
            $html
        ) ?? $html;

        $text = preg_replace('#<(?:br\s*/?|/p|/div|/h[1-6]|/li|/tr)\s*>#i', "\n", $text) ?? $text;
        $text = self::decode(strip_tags($text));
        $text = preg_replace('/[ \t]+$/m', '', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    private static function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

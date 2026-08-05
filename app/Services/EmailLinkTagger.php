<?php

namespace App\Services;

/**
 * Stamps UTM parameters onto first-party links in outgoing mail bodies
 * (002 US14).
 *
 * Lives here rather than in the mailing modules because the values it writes
 * (`utm_source=drip`) and the values TrafficSourceService reads back
 * (PLATFORM_MAP) are two ends of one vocabulary — splitting them would make
 * "add a source" a two-module change with nothing keeping the halves in step
 * (D36).
 *
 * Stamping happens at send time and never touches stored content (FR-028):
 * lessons and posts keep clean URLs, and a change to the rules applies to the
 * next letter out rather than needing a migration over every body.
 */
class EmailLinkTagger
{
    /** Schemes that are never a page on our site. */
    private const OPAQUE_SCHEMES = ['mailto', 'tel', 'sms', 'javascript', 'data', 'ftp'];

    /**
     * Rewrite every `href` in an HTML fragment.
     *
     * Attribute-level regex rather than a DOM parser (D38): the bodies are
     * small CommonMark output, and the sibling `stripStylesForEmail()` already
     * works this way. A malformed hand-written tag may be missed, which costs
     * that one link its attribution and nothing else.
     */
    public function tagHtml(string $html, array $utm): string
    {
        if ($html === '') {
            return '';
        }

        return preg_replace_callback(
            '/\bhref\s*=\s*(["\'])(.*?)\1/i',
            function (array $m) use ($utm): string {
                // The attribute is escaped in the document, the URL parser needs
                // it raw, and it has to go back escaped exactly once — otherwise
                // CommonMark's `&amp;` becomes `&amp;amp;` on the round trip.
                $raw = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $tagged = htmlspecialchars($this->tagUrl($raw, $utm), ENT_QUOTES, 'UTF-8', false);

                return "href={$m[1]}{$tagged}{$m[1]}";
            },
            $html,
        ) ?? $html;
    }

    /**
     * Append the UTM keys to one URL, or return it untouched when it is not
     * ours to attribute.
     */
    public function tagUrl(string $url, array $utm): string
    {
        $trimmed = trim($url);

        if ($trimmed === '' || !$this->isFirstParty($trimmed)) {
            return $url;
        }

        [$base, $fragment] = $this->splitFragment($trimmed);
        [$path, $query] = $this->splitQuery($base);

        parse_str($query, $params);

        // A hand-written tag is the admin correcting the attribution; ours must
        // give way to it rather than the other way round (FR-026).
        if (isset($params['utm_source']) && trim((string) $params['utm_source']) !== '') {
            return $url;
        }

        foreach ($utm as $key => $value) {
            if ($value !== null && $value !== '' && !isset($params[$key])) {
                $params[$key] = $value;
            }
        }

        if ($params === []) {
            return $url;
        }

        // Query before fragment — the other order is a broken link, not a
        // cosmetic difference.
        return $path . '?' . http_build_query($params) . $fragment;
    }

    /** Ours to attribute: a relative path, or an http(s) URL on our own host. */
    private function isFirstParty(string $url): bool
    {
        if (str_starts_with($url, '#')) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (in_array($scheme, self::OPAQUE_SCHEMES, true)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        // No host means a relative link, which can only resolve to our own site.
        // A scheme we do not recognise with no host (`foo:bar`) is not a path.
        if ($host === null || $host === '') {
            return $scheme === '' || $scheme === 'http' || $scheme === 'https';
        }

        if ($scheme !== '' && $scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        return $this->normalizeHost($host) === $this->normalizeHost(
            (string) parse_url((string) config('app.url'), PHP_URL_HOST)
        );
    }

    private function normalizeHost(string $host): string
    {
        return preg_replace('/^www\./i', '', strtolower($host)) ?? $host;
    }

    /** @return array{0: string, 1: string} base and the `#…` remainder (may be empty) */
    private function splitFragment(string $url): array
    {
        $pos = strpos($url, '#');

        return $pos === false
            ? [$url, '']
            : [substr($url, 0, $pos), substr($url, $pos)];
    }

    /** @return array{0: string, 1: string} path and the raw query string */
    private function splitQuery(string $url): array
    {
        $pos = strpos($url, '?');

        return $pos === false
            ? [$url, '']
            : [substr($url, 0, $pos), substr($url, $pos + 1)];
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class TrafficSourceService
{
    public const COOKIE_FIRST = 'tf_first';
    public const COOKIE_LAST = 'tf_last';

    /** 7-day attribution window (spec 002 US10, D12) */
    private const TTL_MINUTES = 7 * 24 * 60;

    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    private const CLICK_ID_KEYS = ['gclid', 'fbclid', 'ttclid'];

    private const BOT_UA_PATTERN =
        '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|pingdom|gtmetrix/i';

    /**
     * Platform registry — the single source of truth for BOTH channel and
     * source (002 FR-022). Each entry is [channel, utm_source pattern]; the
     * referrer host is matched by comparing its labels against the slug
     * itself, so google.com.tw / m.youtube.com / l.instagram.com all resolve
     * without listing every ccTLD.
     */
    private const PLATFORM_MAP = [
        'instagram'  => ['social', '/instagram|^ig$/'],
        'threads'    => ['social', '/threads/'],
        'facebook'   => ['social', '/facebook|^fb$/'],
        'line'       => ['social', '/^line$/'],
        'twitter'    => ['social', '/twitter|^x$/'],
        'linkedin'   => ['social', '/linkedin/'],
        'google'     => ['search', '/google/'],
        'bing'       => ['search', '/bing/'],
        'yahoo'      => ['search', '/yahoo/'],
        'duckduckgo' => ['search', '/duckduckgo/'],
        'youtube'    => ['video',  '/youtube/'],
        'tiktok'     => ['video',  '/tiktok/'],
        'vimeo'      => ['video',  '/vimeo/'],
        'newsletter' => ['email',  '/email|newsletter|edm|mailchimp|resend/'],
    ];

    /** Short-link and rebranded hosts whose labels do not contain the slug. */
    private const HOST_ALIASES = [
        'youtu.be' => 'youtube',
        'fb.me'    => 'facebook',
        'fb.com'   => 'facebook',
        't.co'     => 'twitter',
        'x.com'    => 'twitter',
        'lnkd.in'  => 'linkedin',
    ];

    /** Click id → [channel, source]; a click id always means paid traffic. */
    private const CLICK_ID_SOURCES = [
        'fbclid' => 'facebook',
        'gclid'  => 'google',
        'ttclid' => 'tiktok',
    ];

    /** Host prefixes that carry no meaning of their own (mobile / link shims). */
    private const HOST_NOISE_PREFIX = '/^(www|l|lm|m)\./';

    /**
     * Capture the traffic source from the current request into first/last-touch
     * cookies. tf_first is written once; tf_last is refreshed on every new source.
     */
    public function capture(Request $request): void
    {
        if ($this->isBot($request->userAgent())) {
            return;
        }

        $data = $this->extractFromRequest($request);
        if (empty($data)) {
            return;
        }

        $data['ts'] = now()->timestamp;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        Cookie::queue(cookie(self::COOKIE_LAST, $json, self::TTL_MINUTES));

        if ($this->firstTouch($request) === null) {
            Cookie::queue(cookie(self::COOKIE_FIRST, $json, self::TTL_MINUTES));
        }
    }

    /** @return array<string, mixed>|null */
    public function firstTouch(Request $request): ?array
    {
        return $this->decodeCookie($request, self::COOKIE_FIRST);
    }

    /** @return array<string, mixed>|null */
    public function lastTouch(Request $request): ?array
    {
        return $this->decodeCookie($request, self::COOKIE_LAST);
    }

    /**
     * Source for classifying the CURRENT request. On the UTM landing hit the
     * tf_last cookie is only queued for the response and absent from the
     * request, so live query params take precedence over the cookie.
     *
     * @return array<string, mixed>|null
     */
    public function currentSource(Request $request): ?array
    {
        return $this->extractFromRequest($request) ?: $this->lastTouch($request);
    }

    /**
     * Same capture rules as the original per-page capture (FR-010):
     * UTM keys trimmed to 100 chars, click ids to 255; referrer host recorded
     * only when no UTM/click id and not on the blacklist (FR-004).
     *
     * @return array<string, string>
     */
    public function extractFromRequest(Request $request): array
    {
        $data = [];

        foreach (self::UTM_KEYS as $key) {
            $val = $request->query($key);
            if (is_string($val) && trim($val) !== '') {
                $data[$key] = mb_substr(trim($val), 0, 100);
            }
        }

        foreach (self::CLICK_ID_KEYS as $key) {
            $val = $request->query($key);
            if (is_string($val) && trim($val) !== '') {
                $data[$key] = mb_substr(trim($val), 0, 255);
            }
        }

        $referer = $request->server('HTTP_REFERER');
        if ($referer) {
            $host = parse_url($referer, PHP_URL_HOST);
            if ($host) {
                $host = preg_replace('/^www\./', '', $host);
                $ownHost = preg_replace('/^www\./', '', parse_url(config('app.url'), PHP_URL_HOST) ?? '');
                $blacklist = [$ownHost, 'payuni.com.tw', 'newebpay.com'];
                if (!in_array($host, $blacklist, true)) {
                    $data['referrer_domain'] = mb_substr($host, 0, 255);
                }
            }
        }

        return $data;
    }

    /**
     * Resolve a traffic source into its channel + platform (002 US13, D31).
     * Both dimensions come from PLATFORM_MAP so they can never drift apart.
     *
     * Order: click id → utm_source → referrer host. An unmatched but present
     * source keeps its raw value rather than collapsing to "other" (FR-023) —
     * the host itself is what makes the referral row actionable.
     *
     * @param array<string, mixed>|null $source
     * @return array{channel: string, source: string}
     */
    public function resolveSource(?array $source): array
    {
        if (empty($source)) {
            return ['channel' => 'direct', 'source' => 'direct'];
        }

        foreach (self::CLICK_ID_SOURCES as $key => $platform) {
            if (!empty($source[$key])) {
                return ['channel' => 'paid', 'source' => $platform];
            }
        }

        $utm = strtolower(trim((string) ($source['utm_source'] ?? '')));
        if ($utm !== '') {
            foreach (self::PLATFORM_MAP as $slug => [$channel, $pattern]) {
                if (preg_match($pattern, $utm)) {
                    return ['channel' => $channel, 'source' => $slug];
                }
            }
        }

        $host = $this->normalizeHost((string) ($source['referrer_domain'] ?? ''));
        if ($host !== '' && ($slug = $this->matchHost($host)) !== null) {
            return ['channel' => self::PLATFORM_MAP[$slug][0], 'source' => $slug];
        }

        foreach ([$utm, $host] as $raw) {
            if ($raw !== '') {
                return ['channel' => 'referral', 'source' => mb_substr($raw, 0, 100)];
            }
        }

        return ['channel' => 'direct', 'source' => 'direct'];
    }

    /**
     * Server-side channel classification — source of truth for course_daily_stats.
     * Value domain: paid / social / search / email / video / referral / direct.
     *
     * @param array<string, mixed>|null $source
     */
    public function classifyChannel(?array $source): string
    {
        return $this->resolveSource($source)['channel'];
    }

    /** Lowercase and drop mobile / link-shim prefixes that carry no meaning. */
    private function normalizeHost(string $host): string
    {
        return (string) preg_replace(self::HOST_NOISE_PREFIX, '', strtolower(trim($host)));
    }

    /** @return string|null platform slug, or null when the host is unknown */
    private function matchHost(string $host): ?string
    {
        if (isset(self::HOST_ALIASES[$host])) {
            return self::HOST_ALIASES[$host];
        }

        foreach (explode('.', $host) as $label) {
            if (isset(self::PLATFORM_MAP[$label])) {
                return $label;
            }
        }

        return null;
    }

    public function isBot(?string $userAgent): bool
    {
        return $userAgent !== null && preg_match(self::BOT_UA_PATTERN, $userAgent) === 1;
    }

    /** @return array<string, mixed>|null */
    private function decodeCookie(Request $request, string $name): ?array
    {
        $raw = $request->cookie($name);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) && !empty($decoded) ? $decoded : null;
    }
}

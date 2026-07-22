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
     * Server-side channel classification — source of truth for course_daily_stats
     * (D16; the regex mapping mirrors Traffic.vue's frontend version).
     * Value domain: paid / social / search / email / video / referral / direct.
     *
     * @param array<string, mixed>|null $source
     */
    public function classifyChannel(?array $source): string
    {
        if (empty($source)) {
            return 'direct';
        }

        if (!empty($source['gclid']) || !empty($source['fbclid']) || !empty($source['ttclid'])) {
            return 'paid';
        }

        $src = strtolower((string) ($source['utm_source'] ?? ''));

        return match (true) {
            (bool) preg_match('/instagram|ig|facebook|fb|threads|twitter|^x$/', $src) => 'social',
            (bool) preg_match('/google|bing|yahoo|duckduckgo/', $src)                 => 'search',
            (bool) preg_match('/email|newsletter|edm|mailchimp|resend/', $src)        => 'email',
            (bool) preg_match('/youtube|tiktok|vimeo/', $src)                         => 'video',
            $src !== '' || !empty($source['referrer_domain'])                          => 'referral',
            default                                                                    => 'direct',
        };
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

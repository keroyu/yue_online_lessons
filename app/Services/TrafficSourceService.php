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
        // Meta without a surface: a bare fbclid tells us the click came from
        // Facebook or Instagram but not which one. Kept as its own bucket so the
        // report never guesses (FR-024).
        'meta'       => ['social', '/^meta$/'],
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
        // Our own two mailing paths, stamped by EmailLinkTagger (US14). Kept as
        // separate sources so the email channel says which letter did the work.
        'drip'       => ['email',  '/^drip$/'],
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

    /**
     * Click ids that are only ever emitted by an ad click, so they do imply paid.
     *
     * fbclid is deliberately NOT here (FR-024): Meta appends it to every
     * outbound click from Facebook and Instagram — organic posts, Stories, DMs
     * and profile bio links included. It identifies the network, not the spend.
     */
    private const PAID_CLICK_IDS = [
        'gclid'  => 'google',
        'ttclid' => 'tiktok',
    ];

    /** utm_medium values that state paid intent — the only reliable paid signal. */
    private const PAID_MEDIUM_PATTERN = '/^(cpc|ppc|paid|paid[_-]?social|ads?|display|banner|retargeting)$/';

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

    /**
     * The attribution columns shared by `orders`, `purchases` and
     * `drip_subscriptions`. One list, because the traffic report applies one
     * set of field names across all three (002 FR-037).
     */
    public const SOURCE_COLUMNS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'referrer_domain', 'gclid', 'fbclid', 'ttclid',
    ];

    /**
     * Ready-to-store attribution for a free claim (002 US17): last touch on the
     * flat columns, first touch as a snapshot — the same split checkout uses.
     *
     * Reads `lastTouch()` and NOT `currentSource()`. The latter lets the live
     * request win over the cookie, which is right for classifying a page view
     * and wrong here: the claim POST carries its own Referer, so
     * currentSource() would return that instead of the campaign that actually
     * brought the visitor in — and the real source would be silently dropped.
     *
     * Every column is present even when unknown, so a claim with no source
     * stores explicit nulls and lands in "(直接造訪)" rather than disappearing.
     *
     * @return array<string, mixed>
     */
    public function claimAttributes(Request $request): array
    {
        $last = $this->lastTouch($request) ?? [];

        $attributes = [];

        foreach (self::SOURCE_COLUMNS as $column) {
            $attributes[$column] = $last[$column] ?? null;
        }

        $attributes['first_touch'] = $this->firstTouch($request);

        return $attributes;
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
     * Order (002 FR-024): explicit paid medium → ad-only click id → utm_source
     * → referrer host → bare fbclid → raw value. Anything the advertiser states
     * outranks anything a network bolted onto the URL, so tagging a link is
     * always enough to correct the attribution. An unmatched but present source
     * keeps its raw value rather than collapsing to "other" (FR-023) — the host
     * itself is what makes the referral row actionable.
     *
     * @param array<string, mixed>|null $source
     * @return array{channel: string, source: string}
     */
    public function resolveSource(?array $source): array
    {
        if (empty($source)) {
            return ['channel' => 'direct', 'source' => 'direct'];
        }

        $utm  = strtolower(trim((string) ($source['utm_source'] ?? '')));
        $host = $this->normalizeHost((string) ($source['referrer_domain'] ?? ''));
        $platform = $this->matchPlatform($utm) ?? ($host !== '' ? $this->matchHost($host) : null);

        // Paid is declared, never inferred from a click id alone.
        if ($this->isPaidMedium($source)) {
            return ['channel' => 'paid', 'source' => $platform ?? $this->clickIdPlatform($source) ?? 'unknown'];
        }

        foreach (self::PAID_CLICK_IDS as $key => $adPlatform) {
            if (!empty($source[$key])) {
                return ['channel' => 'paid', 'source' => $adPlatform];
            }
        }

        if ($platform !== null) {
            return ['channel' => self::PLATFORM_MAP[$platform][0], 'source' => $platform];
        }

        // Meta told us the network but not the surface, and nothing else did either.
        if (!empty($source['fbclid'])) {
            return ['channel' => 'social', 'source' => 'meta'];
        }

        foreach ([$utm, $host] as $raw) {
            if ($raw !== '') {
                return ['channel' => 'referral', 'source' => mb_substr($raw, 0, 100)];
            }
        }

        return ['channel' => 'direct', 'source' => 'direct'];
    }

    /**
     * Aggregate-row form of utm_campaign (002 US18 FR-042): trimmed, lowercased
     * and cut to the column width.
     *
     * Static and public because both sides of the report have to apply exactly
     * this rule — the daily aggregate when it writes, and the traffic report
     * when it groups `orders` to join against those rows. Two copies of the
     * rule would put `Summer` and `summer` on separate rows, with nothing on
     * screen to explain why.
     */
    public static function normaliseCampaign(?string $campaign): string
    {
        return mb_substr(mb_strtolower(trim((string) $campaign)), 0, 100);
    }

    /** @param array<string, mixed> $source */
    private function isPaidMedium(array $source): bool
    {
        $medium = strtolower(trim((string) ($source['utm_medium'] ?? '')));

        return $medium !== '' && preg_match(self::PAID_MEDIUM_PATTERN, $medium) === 1;
    }

    /** Platform implied by whichever click id is present, for paid rows. */
    private function clickIdPlatform(array $source): ?string
    {
        foreach (self::PAID_CLICK_IDS as $key => $platform) {
            if (!empty($source[$key])) {
                return $platform;
            }
        }

        return !empty($source['fbclid']) ? 'meta' : null;
    }

    /** @return string|null platform slug matched from a utm_source value */
    private function matchPlatform(string $utm): ?string
    {
        if ($utm === '') {
            return null;
        }

        foreach (self::PLATFORM_MAP as $slug => [$channel, $pattern]) {
            if (preg_match($pattern, $utm)) {
                return $slug;
            }
        }

        return null;
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

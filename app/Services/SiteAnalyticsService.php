<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseDailyStat;
use App\Models\DripSubscription;
use App\Models\Order;
use App\Models\PostCtaClick;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiteAnalyticsService
{
    public function __construct(private TrafficSourceService $trafficSource)
    {
    }

    /**
     * Count a course page view: bot-filtered, deduped per session per course
     * per day per link, bucketed by the visitor's last-touch channel
     * (FR-014/FR-015).
     */
    public function recordView(Course $course, Request $request): void
    {
        if ($this->trafficSource->isBot($request->userAgent())) {
            return;
        }

        $dims = $this->dims($this->trafficSource->currentSource($request));

        // Deduped on the full dimension set, not on course + date (US18 D49):
        // the old key let whichever link a visitor arrived through first own
        // their whole day, silently swallowing every later arrival — which is
        // exactly the question this report exists to answer.
        $dedupKey = 'viewed_course_' . implode('_', [
            $course->id, now()->toDateString(), $dims['channel'], $dims['source'], $dims['campaign'],
        ]);

        if ($request->hasSession() && $request->session()->get($dedupKey)) {
            return;
        }

        $this->bump($course->id, $dims, 'views');

        if ($request->hasSession()) {
            $request->session()->put($dedupKey, true);
        }
    }

    /** Add-to-cart beacon: single path for auth and guest carts (D15). */
    public function recordAddToCart(int $courseId, Request $request): void
    {
        $this->bump($courseId, $this->dims($this->trafficSource->currentSource($request)), 'add_to_cart');
    }

    /** Checkout stage: called once per order at creation, per order item. */
    public function recordCheckout(Order $order): void
    {
        $dims = $this->dims($this->orderSource($order));
        foreach ($order->items as $item) {
            $this->bump($item->course_id, $dims, 'checkouts');
        }
    }

    /** Purchase stage: called once per order on fulfillment (paid). */
    public function recordPurchase(Order $order): void
    {
        $dims = $this->dims($this->orderSource($order));
        foreach ($order->items as $item) {
            $this->bump($item->course_id, $dims, 'purchases');
            $this->bump($item->course_id, $dims, 'revenue', (int) round($item->unit_price));
        }
    }

    /**
     * The dimensions of one daily-aggregate row: the classified channel and
     * source, plus the campaign carried through as-is.
     *
     * One place, because the campaign has to be normalised exactly once and
     * exactly the same way the report normalises it when reading `orders`
     * (FR-042).
     *
     * @param  array<string, mixed>|null $source
     * @return array{channel: string, source: string, campaign: string}
     */
    private function dims(?array $source): array
    {
        return $this->trafficSource->resolveSource($source) + [
            'campaign' => TrafficSourceService::normaliseCampaign($source['utm_campaign'] ?? null),
        ];
    }

    /**
     * Record blog post → course CTA click (daily aggregate row).
     */
    public function recordCtaClick(int $postId, int $courseId): void
    {
        $date = now()->toDateString();

        $affected = PostCtaClick::where('post_id', $postId)
            ->where('course_id', $courseId)
            ->whereDate('date', $date)
            ->increment('clicks');

        if (!$affected) {
            try {
                PostCtaClick::create([
                    'post_id' => $postId, 'course_id' => $courseId,
                    'date' => $date, 'clicks' => 1,
                ]);
            } catch (QueryException) {
                // Lost the insert race — the row exists now, increment it.
                PostCtaClick::where('post_id', $postId)
                    ->where('course_id', $courseId)
                    ->whereDate('date', $date)
                    ->increment('clicks');
            }
        }
    }

    /**
     * Atomic counter bump on the (course, date, channel, source, campaign)
     * daily row (FR-014). Failures degrade silently — analytics must never
     * break a page (FR-016).
     *
     * The dimensions arrive as one array rather than as trailing positional
     * arguments (FR-043): there have been three of them since US18 and the
     * set only grows, at which point `bump($id, $ch, 'views', 1, $src, $camp)`
     * stops being readable at the call site. Missing keys default to `''`,
     * the same value that marks rows written before each dimension existed.
     *
     * @param array{channel?: string, source?: string, campaign?: string} $dims
     */
    public function bump(int $courseId, array $dims, string $column, int $amount = 1): void
    {
        $date = now()->toDateString();
        $dims = [
            'channel'      => $dims['channel'] ?? '',
            'source'       => $dims['source'] ?? '',
            'utm_campaign' => TrafficSourceService::normaliseCampaign($dims['campaign'] ?? ''),
        ];

        try {
            $affected = $this->dailyRow($courseId, $date, $dims)->increment($column, $amount);

            if (!$affected) {
                try {
                    CourseDailyStat::create($dims + [
                        'course_id' => $courseId, 'date' => $date, $column => $amount,
                    ]);
                } catch (QueryException) {
                    // Lost the insert race — the row exists now, increment it.
                    $this->dailyRow($courseId, $date, $dims)->increment($column, $amount);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SiteAnalyticsService: bump failed', [
                'course_id' => $courseId, 'column' => $column, 'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array{channel: string, source: string, utm_campaign: string} $dims
     * @return \Illuminate\Database\Eloquent\Builder<CourseDailyStat>
     */
    private function dailyRow(int $courseId, string $date, array $dims)
    {
        return CourseDailyStat::where('course_id', $courseId)
            ->whereDate('date', $date)
            ->where($dims);
    }

    /**
     * Funnel rows per course for the admin report.
     *
     * Drip courses get their sequence conversions folded into `purchases`
     * (US16): claiming a freebie never creates an order, so without this every
     * lead magnet reports 成交 0 / 成交率 0% — a number that looks like a
     * verdict but is really just the wrong measurement.
     *
     * @return array<int, array<string, mixed>>
     */
    public function funnelReport(?int $days, ?string $channel = null): array
    {
        $query = CourseDailyStat::query()
            ->when($days, fn ($q) => $q->where('date', '>=', now()->subDays($days)->toDateString()))
            ->when($channel, fn ($q) => $q->where('channel', $channel))
            ->selectRaw('course_id, SUM(views) as views, SUM(add_to_cart) as add_to_cart,'
                . ' SUM(checkouts) as checkouts, SUM(purchases) as purchases, SUM(revenue) as revenue')
            ->groupBy('course_id');

        $courseNames = Course::pluck('name', 'id');

        // Skipped entirely under a channel filter: a subscription carries no
        // channel, and the sale happens weeks later in another session, so
        // there is no honest way to file it under one of them (D43).
        $conversions = $channel === null ? $this->dripConversions($days) : [];

        $rows = $query->get()
            ->map(function ($row) use ($courseNames, &$conversions) {
                $converted = (int) ($conversions[$row->course_id] ?? 0);
                unset($conversions[$row->course_id]);

                return [
                    'course_id'        => $row->course_id,
                    'course_name'      => $courseNames[$row->course_id] ?? "#{$row->course_id}",
                    'views'            => (int) $row->views,
                    'add_to_cart'      => (int) $row->add_to_cart,
                    'checkouts'        => (int) $row->checkouts,
                    // Merged on purpose (D41): the same order also counts on the
                    // target course's row, so this column cannot be summed down.
                    'purchases'        => (int) $row->purchases + $converted,
                    'revenue'          => (int) $row->revenue,
                    'drip_conversions' => $converted,
                ];
            });

        // Courses that converted somebody without recording a single view in
        // the period still have to appear — a sale that vanishes from the
        // report is worse than a row full of zeros.
        foreach ($conversions as $courseId => $converted) {
            $rows->push([
                'course_id'        => $courseId,
                'course_name'      => $courseNames[$courseId] ?? "#{$courseId}",
                'views'            => 0,
                'add_to_cart'      => 0,
                'checkouts'        => 0,
                'purchases'        => (int) $converted,
                'revenue'          => 0,
                'drip_conversions' => (int) $converted,
            ]);
        }

        return $rows->sortByDesc('views')->values()->all();
    }

    /**
     * Subscribers per drip course who reached the funnel goal in the period.
     *
     * Reads the status the drip service already wrote rather than re-deriving
     * "did this person buy a target course" from orders: that comparison lives
     * in DripService::reachGoal(), and a second copy of it would drift (D42).
     *
     * `booked` is excluded — a consultation is not a sale, and one that later
     * becomes a sale moves to `converted` on its own.
     *
     * @return array<int, int> course_id => conversions
     */
    private function dripConversions(?int $days): array
    {
        return DripSubscription::query()
            ->where('status', 'converted')
            ->when($days, fn ($q) => $q->where('status_changed_at', '>=', now()->subDays($days)->startOfDay()))
            ->selectRaw('course_id, COUNT(*) as total')
            ->groupBy('course_id')
            ->pluck('total', 'course_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /** Metric columns carried through every report row. */
    private const METRICS = ['views', 'add_to_cart', 'checkouts', 'purchases', 'revenue'];

    /**
     * Per-channel totals for the same period, each with its source breakdown
     * (US13). One query grouped by channel + source; the channel-level numbers
     * are the sum of their sources, so the expanded rows always reconcile.
     *
     * @return array<int, array<string, mixed>> each row has a nested `sources` list
     */
    public function channelReport(?int $days): array
    {
        $rows = CourseDailyStat::query()
            ->when($days, fn ($q) => $q->where('date', '>=', now()->subDays($days)->toDateString()))
            ->selectRaw('channel, source, SUM(views) as views, SUM(add_to_cart) as add_to_cart,'
                . ' SUM(checkouts) as checkouts, SUM(purchases) as purchases, SUM(revenue) as revenue')
            ->groupBy('channel', 'source')
            ->get();

        $channels = [];

        foreach ($rows as $row) {
            $channels[$row->channel] ??= array_merge(
                ['channel' => $row->channel],
                array_fill_keys(self::METRICS, 0),
                ['sources' => []],
            );

            $source = ['source' => (string) $row->source];
            foreach (self::METRICS as $metric) {
                $source[$metric] = (int) $row->$metric;
                $channels[$row->channel][$metric] += $source[$metric];
            }

            $channels[$row->channel]['sources'][] = $source;
        }

        $byViewsDesc = fn (array $a, array $b) => $b['views'] <=> $a['views'];

        foreach ($channels as &$channel) {
            usort($channel['sources'], $byViewsDesc);
        }
        unset($channel);

        $channels = array_values($channels);
        usort($channels, $byViewsDesc);

        return $channels;
    }

    /**
     * Blog post → course CTA click totals.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ctaReport(?int $days): array
    {
        return PostCtaClick::query()
            ->with(['post:id,title', 'course:id,name'])
            ->when($days, fn ($q) => $q->where('date', '>=', now()->subDays($days)->toDateString()))
            ->selectRaw('post_id, course_id, SUM(clicks) as clicks')
            ->groupBy('post_id', 'course_id')
            ->orderByDesc(DB::raw('SUM(clicks)'))
            ->get()
            ->map(fn ($row) => [
                'post_id'     => $row->post_id,
                'post_title'  => $row->post?->title ?? "#{$row->post_id}",
                'course_id'   => $row->course_id,
                'course_name' => $row->course?->name ?? "#{$row->course_id}",
                'clicks'      => (int) $row->clicks,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed> last-touch source snapshot stored on the order
     *
     * `utm_campaign` rides along as a dimension only — it takes no part in the
     * channel/source classification, so adding it does not move any number the
     * channel report already produced.
     */
    private function orderSource(Order $order): array
    {
        return array_filter([
            'utm_source'      => $order->utm_source,
            'utm_campaign'    => $order->utm_campaign,
            'gclid'           => $order->gclid,
            'fbclid'          => $order->fbclid,
            'ttclid'          => $order->ttclid,
            'referrer_domain' => $order->referrer_domain,
        ]);
    }
}

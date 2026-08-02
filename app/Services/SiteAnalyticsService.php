<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseDailyStat;
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
     * per day, bucketed by the visitor's last-touch channel (FR-014/FR-015).
     */
    public function recordView(Course $course, Request $request): void
    {
        if ($this->trafficSource->isBot($request->userAgent())) {
            return;
        }

        $dedupKey = 'viewed_course_' . $course->id . '_' . now()->toDateString();
        if ($request->hasSession() && $request->session()->get($dedupKey)) {
            return;
        }

        ['channel' => $channel, 'source' => $source] =
            $this->trafficSource->resolveSource($this->trafficSource->currentSource($request));
        $this->bump($course->id, $channel, 'views', 1, $source);

        if ($request->hasSession()) {
            $request->session()->put($dedupKey, true);
        }
    }

    /** Add-to-cart beacon: single path for auth and guest carts (D15). */
    public function recordAddToCart(int $courseId, Request $request): void
    {
        ['channel' => $channel, 'source' => $source] =
            $this->trafficSource->resolveSource($this->trafficSource->currentSource($request));
        $this->bump($courseId, $channel, 'add_to_cart', 1, $source);
    }

    /** Checkout stage: called once per order at creation, per order item. */
    public function recordCheckout(Order $order): void
    {
        ['channel' => $channel, 'source' => $source] =
            $this->trafficSource->resolveSource($this->orderSource($order));
        foreach ($order->items as $item) {
            $this->bump($item->course_id, $channel, 'checkouts', 1, $source);
        }
    }

    /** Purchase stage: called once per order on fulfillment (paid). */
    public function recordPurchase(Order $order): void
    {
        ['channel' => $channel, 'source' => $source] =
            $this->trafficSource->resolveSource($this->orderSource($order));
        foreach ($order->items as $item) {
            $this->bump($item->course_id, $channel, 'purchases', 1, $source);
            $this->bump($item->course_id, $channel, 'revenue', (int) round($item->unit_price), $source);
        }
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
     * Atomic counter bump on the (course, date, channel, source) daily row
     * (FR-014). Failures degrade silently — analytics must never break a
     * page (FR-016). $source defaults to '' so pre-US13 callers still work;
     * that value also marks legacy rows written before the source dimension.
     */
    public function bump(int $courseId, string $channel, string $column, int $amount = 1, string $source = ''): void
    {
        $date = now()->toDateString();

        try {
            $affected = $this->dailyRow($courseId, $date, $channel, $source)->increment($column, $amount);

            if (!$affected) {
                try {
                    CourseDailyStat::create([
                        'course_id' => $courseId, 'date' => $date,
                        'channel' => $channel, 'source' => $source, $column => $amount,
                    ]);
                } catch (QueryException) {
                    // Lost the insert race — the row exists now, increment it.
                    $this->dailyRow($courseId, $date, $channel, $source)->increment($column, $amount);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SiteAnalyticsService: bump failed', [
                'course_id' => $courseId, 'column' => $column, 'error' => $e->getMessage(),
            ]);
        }
    }

    /** @return \Illuminate\Database\Eloquent\Builder<CourseDailyStat> */
    private function dailyRow(int $courseId, string $date, string $channel, string $source)
    {
        return CourseDailyStat::where('course_id', $courseId)
            ->whereDate('date', $date)
            ->where('channel', $channel)
            ->where('source', $source);
    }

    /**
     * Funnel rows per course for the admin report.
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

        return $query->get()
            ->map(fn ($row) => [
                'course_id'   => $row->course_id,
                'course_name' => $courseNames[$row->course_id] ?? "#{$row->course_id}",
                'views'       => (int) $row->views,
                'add_to_cart' => (int) $row->add_to_cart,
                'checkouts'   => (int) $row->checkouts,
                'purchases'   => (int) $row->purchases,
                'revenue'     => (int) $row->revenue,
            ])
            ->sortByDesc('views')
            ->values()
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

    /** @return array<string, mixed> last-touch source snapshot stored on the order */
    private function orderSource(Order $order): array
    {
        return array_filter([
            'utm_source'      => $order->utm_source,
            'gclid'           => $order->gclid,
            'fbclid'          => $order->fbclid,
            'ttclid'          => $order->ttclid,
            'referrer_domain' => $order->referrer_domain,
        ]);
    }
}

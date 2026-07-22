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

        $channel = $this->trafficSource->classifyChannel($this->trafficSource->currentSource($request));
        $this->bump($course->id, $channel, 'views');

        if ($request->hasSession()) {
            $request->session()->put($dedupKey, true);
        }
    }

    /** Add-to-cart beacon: single path for auth and guest carts (D15). */
    public function recordAddToCart(int $courseId, Request $request): void
    {
        $channel = $this->trafficSource->classifyChannel($this->trafficSource->currentSource($request));
        $this->bump($courseId, $channel, 'add_to_cart');
    }

    /** Checkout stage: called once per order at creation, per order item. */
    public function recordCheckout(Order $order): void
    {
        $channel = $this->trafficSource->classifyChannel($this->orderSource($order));
        foreach ($order->items as $item) {
            $this->bump($item->course_id, $channel, 'checkouts');
        }
    }

    /** Purchase stage: called once per order on fulfillment (paid). */
    public function recordPurchase(Order $order): void
    {
        $channel = $this->trafficSource->classifyChannel($this->orderSource($order));
        foreach ($order->items as $item) {
            $this->bump($item->course_id, $channel, 'purchases');
            $this->bump($item->course_id, $channel, 'revenue', (int) round($item->unit_price));
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
     * Atomic counter bump on the (course, date, channel) daily row (FR-014).
     * Failures degrade silently — analytics must never break a page (FR-016).
     */
    public function bump(int $courseId, string $channel, string $column, int $amount = 1): void
    {
        $date = now()->toDateString();

        try {
            $affected = CourseDailyStat::where('course_id', $courseId)
                ->whereDate('date', $date)
                ->where('channel', $channel)
                ->increment($column, $amount);

            if (!$affected) {
                try {
                    CourseDailyStat::create([
                        'course_id' => $courseId, 'date' => $date,
                        'channel' => $channel, $column => $amount,
                    ]);
                } catch (QueryException) {
                    CourseDailyStat::where('course_id', $courseId)
                        ->whereDate('date', $date)
                        ->where('channel', $channel)
                        ->increment($column, $amount);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SiteAnalyticsService: bump failed', [
                'course_id' => $courseId, 'column' => $column, 'error' => $e->getMessage(),
            ]);
        }
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

    /**
     * Per-channel totals for the same period (channel view toggle).
     *
     * @return array<int, array<string, mixed>>
     */
    public function channelReport(?int $days): array
    {
        return CourseDailyStat::query()
            ->when($days, fn ($q) => $q->where('date', '>=', now()->subDays($days)->toDateString()))
            ->selectRaw('channel, SUM(views) as views, SUM(add_to_cart) as add_to_cart,'
                . ' SUM(checkouts) as checkouts, SUM(purchases) as purchases, SUM(revenue) as revenue')
            ->groupBy('channel')
            ->orderByDesc(DB::raw('SUM(views)'))
            ->get()
            ->map(fn ($row) => [
                'channel'     => $row->channel,
                'views'       => (int) $row->views,
                'add_to_cart' => (int) $row->add_to_cart,
                'checkouts'   => (int) $row->checkouts,
                'purchases'   => (int) $row->purchases,
                'revenue'     => (int) $row->revenue,
            ])
            ->all();
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

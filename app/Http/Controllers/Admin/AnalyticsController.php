<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use App\Services\SiteAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Marketing screens (002 US10 + US15): the traffic funnel and the short
     * link manager, as two tabs of one page.
     *
     * Only the active tab's data is assembled — the funnel report is several
     * aggregate queries and there is no reason to run them for someone who came
     * to edit a short link (比照 011 US8 / D24).
     */
    public function index(Request $request, SiteAnalyticsService $analytics): Response
    {
        $tab = $request->query('tab') === 'short-links' ? 'short-links' : 'traffic';

        if ($tab === 'short-links') {
            return Inertia::render('Admin/Analytics/Index', [
                'tab'     => $tab,
                'links'   => ShortLink::adminListing(),
                'baseUrl' => rtrim(config('app.url'), '/'),
            ]);
        }

        // Whitelisted range param, mirrors the Traffic page convention.
        $range = (string) $request->input('range', '30');
        $days = in_array($range, ['7', '30', '90'], true) ? (int) $range : null;
        $channel = $request->input('channel');
        $channel = in_array($channel, ['paid', 'social', 'search', 'email', 'video', 'referral', 'direct'], true)
            ? $channel : null;

        return Inertia::render('Admin/Analytics/Index', [
            'tab'      => $tab,
            'funnel'   => $analytics->funnelReport($days, $channel),
            'channels' => $analytics->channelReport($days),
            'cta'      => $analytics->ctaReport($days),
            'range'    => $days ? (string) $days : 'all',
            'channel'  => $channel,
        ]);
    }
}

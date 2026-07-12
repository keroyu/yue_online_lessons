<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Marketing funnel report (002 US10): per-course views → add to cart →
     * checkouts → purchases with revenue, channel breakdown, blog CTA clicks.
     */
    public function index(Request $request, SiteAnalyticsService $analytics): Response
    {
        // Whitelisted range param, mirrors the Traffic page convention.
        $range = (string) $request->input('range', '30');
        $days = in_array($range, ['7', '30', '90'], true) ? (int) $range : null;
        $channel = $request->input('channel');
        $channel = in_array($channel, ['paid', 'social', 'search', 'email', 'video', 'referral', 'direct'], true)
            ? $channel : null;

        return Inertia::render('Admin/Analytics/Index', [
            'funnel'   => $analytics->funnelReport($days, $channel),
            'channels' => $analytics->channelReport($days),
            'cta'      => $analytics->ctaReport($days),
            'range'    => $days ? (string) $days : 'all',
            'channel'  => $channel,
        ]);
    }
}

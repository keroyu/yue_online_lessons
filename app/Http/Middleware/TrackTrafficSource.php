<?php

namespace App\Http\Middleware;

use App\Services\TrafficSourceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackTrafficSource
{
    public function __construct(private TrafficSourceService $trafficSource)
    {
    }

    /**
     * Site-wide traffic source capture (002 US10, D12): any GET entry point
     * records UTM / click id / referrer into first+last-touch cookies.
     * Admin, api and the /go counter endpoints are excluded.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')
            && !$request->is('admin/*', 'admin', 'api/*', 'go/*', 'up')) {
            $this->trafficSource->capture($request);
        }

        return $next($request);
    }
}

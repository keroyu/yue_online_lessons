<?php

namespace App\Http\Controllers;

use App\Services\SiteIconService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * `/favicon.ico` — requested by name by browsers, feed readers and link
 * unfurlers whether or not the page declares an icon, which is why the
 * generated .ico is served here rather than only linked from the layout.
 *
 * Laravel ships an empty `public/favicon.ico`; it was deleted so that nginx
 * falls through to this route. A 404 is the honest answer when nothing has
 * been uploaded — the browser then falls back to its default mark.
 */
class FaviconController extends Controller
{
    public function __invoke(SiteIconService $icons): SymfonyResponse
    {
        $path = trim((string) \App\Models\SiteSetting::get(SiteIconService::FAVICON_ICO_KEY, ''));
        $disk = Storage::disk('public');

        if ($path === '' || ! $disk->exists($path)) {
            abort(404);
        }

        return new Response($disk->get($path), 200, [
            'Content-Type'  => 'image/x-icon',
            // A favicon changes when the admin uploads a new one, which also
            // changes the filename this points at — a day of caching is safe.
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

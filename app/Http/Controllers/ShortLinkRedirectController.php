<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;

class ShortLinkRedirectController extends Controller
{
    /**
     * Catch-all short link redirect. Registered last in routes/web.php so every
     * named route wins; unknown or disabled slugs are a plain 404.
     */
    public function __invoke(string $slug): RedirectResponse
    {
        $link = ShortLink::active()->where('slug', mb_strtolower($slug))->first();

        abort_if($link === null, 404);

        $link->recordClick();

        // 302 + no-store: the target is expected to change (spec D16)
        return redirect()->away($link->target_url, 302)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}

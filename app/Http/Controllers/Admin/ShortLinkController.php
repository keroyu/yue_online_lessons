<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShortLinkRequest;
use App\Http\Requests\Admin\UpdateShortLinkRequest;
use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShortLinkController extends Controller
{
    public function index(): Response
    {
        $links = ShortLink::orderByDesc('created_at')->get()->map(fn (ShortLink $link) => [
            'id'              => $link->id,
            'slug'            => $link->slug,
            'target_url'      => $link->target_url,
            'name'            => $link->name,
            'is_active'       => $link->is_active,
            'clicks'          => $link->clicks,
            // Stored in UTC (app timezone); the admin reads Taipei time
            'last_clicked_at' => $link->last_clicked_at?->timezone('Asia/Taipei')->format('Y-m-d H:i'),
        ]);

        return Inertia::render('Admin/ShortLinks/Index', [
            'links'   => $links,
            'baseUrl' => rtrim(config('app.url'), '/'),
        ]);
    }

    public function store(StoreShortLinkRequest $request): RedirectResponse
    {
        ShortLink::create([
            'slug'       => $request->input('slug'),
            'target_url' => $request->input('target_url'),
            'name'       => $request->input('name'),
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->back()->with('success', '短網址已新增');
    }

    public function update(UpdateShortLinkRequest $request, ShortLink $shortLink): RedirectResponse
    {
        $shortLink->update([
            'slug'       => $request->input('slug'),
            'target_url' => $request->input('target_url'),
            'name'       => $request->input('name'),
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->back()->with('success', '短網址已更新');
    }

    public function destroy(ShortLink $shortLink): RedirectResponse
    {
        $shortLink->delete();

        return redirect()->back()->with('success', '短網址已刪除');
    }
}

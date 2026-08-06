<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShortLinkRequest;
use App\Http\Requests\Admin\UpdateShortLinkRequest;
use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;

class ShortLinkController extends Controller
{
    /**
     * 管理畫面 moved into 行銷分析 as a tab (002 US15). Kept as a redirect
     * rather than deleted: this path has been handed out in the sidebar and in
     * bookmarks, and a 404 would read as "the feature is gone".
     */
    public function index(): RedirectResponse
    {
        return redirect('/admin/analytics?tab=short-links');
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

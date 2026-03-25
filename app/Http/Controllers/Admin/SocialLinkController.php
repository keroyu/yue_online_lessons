<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSocialLinkRequest;
use App\Http\Requests\Admin\UpdateSocialLinkRequest;
use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;

class SocialLinkController extends Controller
{
    public function store(StoreSocialLinkRequest $request): RedirectResponse
    {
        SocialLink::create([
            'platform'   => $request->input('platform'),
            'url'        => $request->input('url'),
            'sort_order' => (SocialLink::max('sort_order') ?? 0) + 1,
        ]);

        return redirect()->back()->with('success', '連結已新增');
    }

    public function update(UpdateSocialLinkRequest $request, SocialLink $socialLink): RedirectResponse
    {
        $socialLink->update(['url' => $request->input('url')]);

        return redirect()->back()->with('success', '連結已更新');
    }

    public function destroy(SocialLink $socialLink): RedirectResponse
    {
        $socialLink->delete();

        return redirect()->back()->with('success', '連結已刪除');
    }
}

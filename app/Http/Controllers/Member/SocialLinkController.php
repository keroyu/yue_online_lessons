<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreUserSocialLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialLinkController extends Controller
{
    public function store(StoreUserSocialLinkRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->socialLinks()->create([
            'platform'   => $request->input('platform'),
            'url'        => $request->input('url'),
            'sort_order' => ($user->socialLinks()->max('sort_order') ?? 0) + 1,
        ]);

        return back()->with('success', '連結已新增');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $request->user()->socialLinks()->findOrFail($id)->delete();

        return back()->with('success', '連結已刪除');
    }
}

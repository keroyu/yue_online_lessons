<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateHomepageSettingRequest;
use App\Models\Course;
use App\Models\HomepageFeaturedCourse;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomepageSettingController extends Controller
{
    /** Sidebar widget keys in default display order. */
    public const SIDEBAR_WIDGETS = ['featured_courses', 'social', 'blog'];

    /**
     * Return the saved sidebar widget order, normalised so it always
     * contains exactly the known widget keys (missing keys appended).
     */
    public static function sidebarWidgetOrder(): array
    {
        $saved = json_decode(SiteSetting::get('sidebar_widget_order', '[]'), true);
        $saved = is_array($saved) ? array_values(array_intersect($saved, self::SIDEBAR_WIDGETS)) : [];

        foreach (self::SIDEBAR_WIDGETS as $key) {
            if (! in_array($key, $saved, true)) {
                $saved[] = $key;
            }
        }

        return $saved;
    }

    public function edit(): Response
    {
        $settings = SiteSetting::getMany([
            'hero_title', 'hero_description', 'hero_button_label',
            'hero_button_url', 'hero_banner_path',
            'blog_rss_url', 'sns_section_enabled',
        ]);

        $bannerPath = $settings->get('hero_banner_path');

        return Inertia::render('Admin/HomepageSettings/Edit', [
            'settings' => [
                'hero_title'          => $settings->get('hero_title'),
                'hero_description'    => $settings->get('hero_description'),
                'hero_button_label'   => $settings->get('hero_button_label'),
                'hero_button_url'     => $settings->get('hero_button_url'),
                'hero_banner_url'     => $bannerPath ? Storage::url($bannerPath) : null,
                'blog_rss_url'        => $settings->get('blog_rss_url'),
                // Cast to bool: stored as "0"/"1" text — (bool)"0" is true in PHP
                'sns_section_enabled' => (bool) (int) $settings->get('sns_section_enabled', '0'),
            ],
            'socialLinks' => SocialLink::ordered()->get()->map(fn ($link) => [
                'id'       => $link->id,
                'platform' => $link->platform,
                'url'      => $link->url,
            ])->values(),
            'featuredCourses' => HomepageFeaturedCourse::ordered()->with('course:id,name,thumbnail')->get()
                ->filter(fn ($item) => $item->course !== null)
                ->map(fn ($item) => [
                    'id'        => $item->id,
                    'course_id' => $item->course_id,
                    'name'      => $item->course->name,
                    'thumbnail' => $item->course->thumbnail_url,
                    'blurb'     => $item->blurb,
                ])->values(),
            'availableCourses' => Course::orderBy('id', 'desc')->get(['id', 'name'])->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
            ])->values(),
            'sidebarOrder' => self::sidebarWidgetOrder(),
        ]);
    }

    public function updateWidgetOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['string', 'in:' . implode(',', self::SIDEBAR_WIDGETS)],
        ]);

        SiteSetting::set('sidebar_widget_order', json_encode(array_values($validated['order'])));

        return redirect()->back()->with('success', '側欄排序已更新');
    }

    public function update(UpdateHomepageSettingRequest $request): RedirectResponse
    {
        if ($request->hasFile('hero_banner')) {
            $oldPath = SiteSetting::get('hero_banner_path');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('hero_banner')->store('hero-banner', 'public');
            SiteSetting::set('hero_banner_path', $path);
        }

        $oldRssUrl = SiteSetting::get('blog_rss_url', '');
        $newRssUrl = $request->input('blog_rss_url', '');
        if ($oldRssUrl !== $newRssUrl && $oldRssUrl) {
            Cache::forget('blog_articles_' . md5($oldRssUrl));
        }

        SiteSetting::set('hero_title', $request->input('hero_title'));
        SiteSetting::set('hero_description', $request->input('hero_description'));
        SiteSetting::set('hero_button_label', $request->input('hero_button_label'));
        SiteSetting::set('hero_button_url', $request->input('hero_button_url'));
        SiteSetting::set('blog_rss_url', $newRssUrl);
        SiteSetting::set('sns_section_enabled', $request->boolean('sns_section_enabled') ? '1' : '0');

        return redirect()->back()->with('success', '首頁設定已儲存');
    }

    public function deleteBanner(): RedirectResponse
    {
        $path = SiteSetting::get('hero_banner_path');

        if ($path) {
            Storage::disk('public')->delete($path);
            SiteSetting::set('hero_banner_path', null);
        }

        return redirect()->back()->with('success', '橫幅圖片已刪除');
    }
}

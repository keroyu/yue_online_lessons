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
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HomepageSettingController extends Controller
{
    /** Sidebar widget keys in default display order. */
    public const SIDEBAR_WIDGETS = ['featured_courses', 'social', 'blog'];

    /** Default content categories (label + slug), max 3 slots. */
    public const DEFAULT_CONTENT_CATEGORIES = [
        ['label' => '思維升級', 'slug' => 'mindset'],
        ['label' => '財務覺醒', 'slug' => 'finance'],
        ['label' => '知識變現', 'slug' => 'monetization'],
    ];

    /** Exactly 3 slots (padded with blanks) — used to render the admin editor. */
    public static function contentCategorySlots(): array
    {
        $saved = json_decode(SiteSetting::get('content_categories', ''), true);
        if (! is_array($saved) || empty($saved)) {
            $saved = self::DEFAULT_CONTENT_CATEGORIES;
        }

        $slots = [];
        for ($i = 0; $i < 3; $i++) {
            $slots[] = [
                'label' => (string) ($saved[$i]['label'] ?? ''),
                'slug'  => (string) ($saved[$i]['slug'] ?? ''),
            ];
        }

        return $slots;
    }

    /** Only fully-filled slots — used by the frontend filter and course form. */
    public static function contentCategories(): array
    {
        return array_values(array_filter(
            self::contentCategorySlots(),
            fn ($c) => $c['label'] !== '' && $c['slug'] !== ''
        ));
    }

    /** Whether the homepage content-type filter row is shown. */
    public static function contentFilterEnabled(): bool
    {
        return (bool) (int) SiteSetting::get('content_filter_enabled', '0');
    }

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
            'contentCategorySlots' => self::contentCategorySlots(),
            'contentFilterEnabled' => self::contentFilterEnabled(),
        ]);
    }

    public function updateContentCategories(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled'            => ['boolean'],
            'categories'         => ['array', 'max:3'],
            'categories.*.label' => ['nullable', 'string', 'max:50'],
            'categories.*.slug'  => ['nullable', 'string', 'max:50', 'regex:/^[a-z-]+$/'],
        ], [
            'categories.*.slug.regex' => '英文名只能使用小寫英文字母與「-」',
        ]);

        $incoming = $validated['categories'] ?? [];

        // A slot is either fully empty or fully filled (both label & slug).
        $filled = [];
        foreach ($incoming as $i => $c) {
            $label = trim($c['label'] ?? '');
            $slug  = trim($c['slug'] ?? '');
            if ($label === '' && $slug === '') {
                continue;
            }
            if ($label === '' || $slug === '') {
                throw ValidationException::withMessages([
                    "categories.$i" => '顯示文字與英文名必須同時填寫或同時留空',
                ]);
            }
            $filled[] = ['label' => $label, 'slug' => $slug];
        }

        // Slugs must be unique across slots.
        $slugs = array_column($filled, 'slug');
        if (count($slugs) !== count(array_unique($slugs))) {
            throw ValidationException::withMessages([
                'categories' => '英文名不可重複',
            ]);
        }

        // Cascade slug renames to courses (by slot position vs. previous save).
        $old = self::contentCategorySlots();
        foreach (array_values($incoming) as $i => $c) {
            $oldSlug = trim($old[$i]['slug'] ?? '');
            $newSlug = trim($c['slug'] ?? '');
            if ($oldSlug !== '' && $newSlug !== '' && $oldSlug !== $newSlug) {
                Course::where('content_category', $oldSlug)->update(['content_category' => $newSlug]);
            }
        }

        SiteSetting::set('content_categories', json_encode(array_values($filled), JSON_UNESCAPED_UNICODE));
        SiteSetting::set('content_filter_enabled', $request->boolean('enabled') ? '1' : '0');

        return redirect()->back()->with('success', '內容分類已更新');
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

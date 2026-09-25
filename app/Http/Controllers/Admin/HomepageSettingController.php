<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateHomepageSettingRequest;
use App\Models\Course;
use App\Models\HomepageFeaturedCourse;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Services\SiteIconService;
use App\Services\ThemeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
        ['label' => '商業策略', 'slug' => 'monetization'],
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
            'hero_title', 'hero_subtitle', 'hero_description',
            'hero_banner_path', 'hero_promo_course_id',
            'sns_section_enabled', 'sns_profile_intro',
        ]);

        $bannerPath = $settings->get('hero_banner_path');

        return Inertia::render('Admin/HomepageSettings/Edit', [
            'settings' => [
                'hero_title'          => $settings->get('hero_title'),
                'hero_subtitle'       => $settings->get('hero_subtitle'),
                'hero_description'    => $settings->get('hero_description'),
                'hero_banner_url'     => $bannerPath ? Storage::url($bannerPath) : null,
                'hero_promo_course_id' => $settings->get('hero_promo_course_id') ?: null,
                // Cast to bool: stored as "0"/"1" text — (bool)"0" is true in PHP
                'sns_section_enabled' => (bool) (int) $settings->get('sns_section_enabled', '0'),
                'sns_profile_intro'   => $settings->get('sns_profile_intro'),
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
                    // Hidden rows stay in this list — the admin needs to see
                    // what is off before it can be switched back on (FR-051).
                    'is_visible' => $item->is_visible,
                ])->values(),
            'availableCourses' => Course::orderBy('id', 'desc')->get(['id', 'name'])->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
            ])->values(),
            // Targets for the hero's 📌 line. Any live product qualifies now
            // that the form subscribes to the newsletter instead of claiming a
            // chained course (FR-070) — the line is just a link to a sales page.
            'promoCourses' => Course::where('is_published', true)
                ->where('status', '!=', 'draft')
                ->orderBy('id', 'desc')
                ->get(['id', 'name'])
                ->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->name,
                ])->values(),
            'siteIdentity' => SiteSetting::identity(),
            // 000 US14 — the palette card sits between 站台資訊 and Hero 主視覺,
            // because those two are the only site-wide cards on this page and
            // everything below them only affects the homepage (000 D47).
            'colorSchemes' => app(ThemeService::class)->all(),
            'activeColorScheme' => app(ThemeService::class)->activeKey(),
            'siteIcons' => app(SiteIconService::class)->urls(),
            'sidebarOrder' => self::sidebarWidgetOrder(),
            'contentCategorySlots' => self::contentCategorySlots(),
            'contentFilterEnabled' => self::contentFilterEnabled(),
        ]);
    }

    /**
     * 站台資訊 — the name printed in the navbar, the footer, page titles, system
     * mail and the OG card, plus the operator and address that the legal modal
     * is required to show.
     *
     * Its own endpoint rather than a few more fields on `update()`: that one is
     * a multipart request built around the hero image, and identity has no
     * reason to be re-posted every time somebody swaps the banner.
     */
    public function updateSiteIdentity(Request $request, SiteIconService $icons): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'     => ['required', 'string', 'max:100'],
            'site_operator' => ['nullable', 'string', 'max:255'],
            'site_address'  => ['nullable', 'string', 'max:255'],
            // PNG first in the list because it is the only one that carries
            // transparency, which both the navy navbar and a browser tab need.
            'site_logo'     => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'site_favicon'  => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ], [
            'site_name.required' => '請填寫站名',
            'site_name.max'      => '站名不能超過 100 字',
            'site_operator.max'  => '經營者不能超過 255 字',
            'site_address.max'   => '地址不能超過 255 字',
            'site_logo.image'    => '網站圖示請上傳圖片檔',
            'site_logo.mimes'    => '網站圖示格式限 PNG / JPG / WebP',
            'site_logo.max'      => '網站圖示不能超過 2MB',
            'site_favicon.image' => 'Favicon 請上傳圖片檔',
            'site_favicon.mimes' => 'Favicon 格式限 PNG / JPG / WebP',
            'site_favicon.max'   => 'Favicon 不能超過 2MB',
        ]);

        SiteSetting::set(SiteSetting::SITE_NAME_KEY, trim($validated['site_name']));
        SiteSetting::set(SiteSetting::SITE_OPERATOR_KEY, trim((string) ($validated['site_operator'] ?? '')));
        SiteSetting::set(SiteSetting::SITE_ADDRESS_KEY, trim((string) ($validated['site_address'] ?? '')));

        if ($request->hasFile('site_logo')) {
            $icons->storeLogo($request->file('site_logo'));
        }

        if ($request->hasFile('site_favicon')) {
            $icons->storeFavicon($request->file('site_favicon'));
        }

        return redirect()->back()->with('success', '站台資訊已更新');
    }

    public function deleteSiteLogo(SiteIconService $icons): RedirectResponse
    {
        $icons->deleteLogo();

        return redirect()->back()->with('success', '網站圖示已刪除');
    }

    public function deleteSiteFavicon(SiteIconService $icons): RedirectResponse
    {
        $icons->deleteFavicon();

        return redirect()->back()->with('success', 'Favicon 已刪除');
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

    /**
     * 配色方案 — the seven `--color-brand-*` values the whole site is drawn
     * with (000 US14).
     *
     * Only the key is stored; the schemes themselves live in `config/themes.php`
     * (000 D43). `Rule::in` over that config means a retired scheme cannot be
     * selected, while `ThemeService` separately tolerates one already sitting
     * in the database.
     *
     * Its own endpoint, like every other card on this page, so choosing a
     * palette cannot disturb the site name in the card above it.
     */
    public function updateColorScheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'color_scheme' => ['required', 'string', Rule::in(array_keys(config('themes.schemes')))],
        ]);

        SiteSetting::set(ThemeService::SETTING_KEY, $validated['color_scheme']);

        return redirect()->back()->with('success', '配色方案已更新');
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

        SiteSetting::set('hero_title', $request->input('hero_title'));
        SiteSetting::set('hero_subtitle', $request->input('hero_subtitle'));
        SiteSetting::set('hero_description', $request->input('hero_description'));
        // Empty string, not null: the 📌 line is off when this is blank, and a
        // stored '' reads back the same on every driver.
        SiteSetting::set('hero_promo_course_id', (string) $request->input('hero_promo_course_id', ''));
        SiteSetting::set('sns_section_enabled', $request->boolean('sns_section_enabled') ? '1' : '0');
        SiteSetting::set('sns_profile_intro', $request->input('sns_profile_intro'));

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

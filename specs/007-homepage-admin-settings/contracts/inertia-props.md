# Inertia Props Contracts: Homepage Admin Settings

**Branch**: `007-homepage-admin-settings` | **Date**: 2026-03-25

---

## Routes (add to existing `auth + admin` group in `routes/web.php`)

```php
// Homepage settings
Route::get('/homepage', [HomepageSettingController::class, 'edit'])->name('homepage.edit');
Route::post('/homepage', [HomepageSettingController::class, 'update'])->name('homepage.update');
Route::delete('/homepage/banner', [HomepageSettingController::class, 'deleteBanner'])->name('homepage.banner.destroy');

// Social links CRUD
Route::post('/homepage/social-links', [SocialLinkController::class, 'store'])->name('social-links.store');
Route::put('/homepage/social-links/{socialLink}', [SocialLinkController::class, 'update'])->name('social-links.update');
Route::delete('/homepage/social-links/{socialLink}', [SocialLinkController::class, 'destroy'])->name('social-links.destroy');
```

---

## Admin Page: `GET /admin/homepage`

**Controller**: `Admin\HomepageSettingController@edit`
**Renders**: `Admin/HomepageSettings/Edit`

### Inertia Props (Server → Vue)

```typescript
{
  settings: {
    hero_title:          string | null,
    hero_description:    string | null,
    hero_button_label:   string | null,
    hero_button_url:     string | null,
    hero_banner_url:     string | null,  // resolved Storage::url() or null
    blog_rss_url:        string | null,
    sns_section_enabled: boolean,
  },
  socialLinks: Array<{
    id:       number,
    platform: 'instagram' | 'threads' | 'youtube' | 'facebook' | 'substack' | 'podcast',
    url:      string,
  }>,
}
```

### Form Submissions

**Save hero + RSS settings**: `POST /admin/homepage`
```
Content-Type: multipart/form-data   (required for optional file upload)

hero_title?          string, max:255
hero_description?    string, max:2000
hero_button_label?   string, max:100
hero_button_url?     url, max:500
hero_banner?         file (jpg|jpeg|png|webp, max:5120KB, min_width:1200px)
blog_rss_url?        url, max:500
sns_section_enabled  boolean (1 or 0)
```
→ Redirects back with flash `success` or `error`.
→ If `hero_banner` present and valid: deletes old file, stores to `hero-banner/` public disk, updates `hero_banner_path` in site_settings.
→ If `blog_rss_url` changed: `Cache::forget('blog_articles_' . md5($oldUrl))`.

**Delete banner**: `DELETE /admin/homepage/banner`
→ Deletes file from `Storage::disk('public')`, sets `hero_banner_path = null`.
→ Redirects back with flash `success`.

**Add social link**: `POST /admin/homepage/social-links`
```json
{ "platform": "instagram", "url": "https://..." }
```
→ Creates row with `sort_order = SocialLink::max('sort_order') + 1`.
→ Redirects back with flash `success`.

**Edit social link**: `PUT /admin/homepage/social-links/{id}`
```json
{ "url": "https://..." }
```
→ Updates `url` field.
→ Redirects back with flash `success`.

**Delete social link**: `DELETE /admin/homepage/social-links/{id}`
→ Deletes row.
→ Redirects back with flash `success`.

---

## Homepage: `GET /`

**Controller**: `HomeController@index`
**Renders**: `Home`

### New/Changed Inertia Props

```typescript
// ADDED
hero: {
  title:        string | null,
  description:  string | null,
  button_label: string | null,
  button_url:   string | null,
  banner_url:   string | null,   // Storage::url(path) or null
},
socialLinks: Array<{
  platform: string,
  url:      string,
}>,
blogArticles: Array<{           // renamed from substackArticles
  title:        string,
  url:          string,
  published_at: string,         // ISO 8601
}>,

// UNCHANGED
courses: Array<{ ... }>,
isAdmin: boolean,
```

**Data loading in HomeController:**

```php
// One batch query for all settings
$settings = SiteSetting::getMany([
    'hero_title', 'hero_description', 'hero_button_label',
    'hero_button_url', 'hero_banner_path',
    'blog_rss_url', 'sns_section_enabled',
]);

// Social links: only when SNS section enabled
$socialLinks = $settings['sns_section_enabled']
    ? SocialLink::ordered()->get()->map(fn($l) => ['platform' => $l->platform, 'url' => $l->url])->values()
    : collect();

// RSS
$rssUrl = $settings['blog_rss_url'] ?? '';
$blogArticles = $rssUrl ? $blogRssService->getArticles($rssUrl) : [];
```

---

## Validation: `UpdateHomepageSettingRequest`

```php
rules(): [
    'hero_title'          => ['nullable', 'string', 'max:255'],
    'hero_description'    => ['nullable', 'string', 'max:2000'],
    'hero_button_label'   => ['nullable', 'string', 'max:100'],
    'hero_button_url'     => ['nullable', 'url', 'max:500'],
    'hero_banner'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=1200'],
    'blog_rss_url'        => ['nullable', 'url', 'max:500'],
    'sns_section_enabled' => ['required', 'boolean'],
]

messages(): [
    'hero_banner.image'       => '請上傳圖片檔案',
    'hero_banner.mimes'       => '圖片格式必須是 jpg、jpeg、png 或 webp',
    'hero_banner.max'         => '圖片大小不能超過 5MB',
    'hero_banner.dimensions'  => '圖片寬度至少需要 1200px',
]
```

## Validation: `StoreSocialLinkRequest`

```php
rules(): [
    'platform' => ['required', 'string', 'in:instagram,threads,youtube,facebook,substack,podcast'],
    'url'      => ['required', 'url', 'max:500'],
]

messages(): [
    'platform.in'  => '請選擇有效的社群平台',
    'url.required' => '請填入網址',
    'url.url'      => '請填入有效的網址（包含 https://）',
]
```

# Inertia Props Contracts: Homepage Admin Settings

**Branch**: `007-homepage-admin-settings` | **Date**: 2026-03-25
**Updated**: 2026-07-05 - 新增精選課程 CRUD/排序與側欄 widget 排序路由；edit 頁新增 featuredCourses/availableCourses/sidebarOrder props；Home 頁新增 featuredCourses/sidebarOrder props
**Updated**: 2026-07-06 - 新增內容分類路由 POST /admin/homepage/content-categories；edit 頁新增 contentCategorySlots/contentFilterEnabled props；Home 頁新增 contentCategories prop（僅開關開啟時帶出）

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

// Featured courses (right sidebar) CRUD + reorder
Route::post('/homepage/featured-courses', [HomepageFeaturedCourseController::class, 'store'])->name('featured-courses.store');
Route::put('/homepage/featured-courses/{featuredCourse}', [HomepageFeaturedCourseController::class, 'update'])->name('featured-courses.update');
Route::delete('/homepage/featured-courses/{featuredCourse}', [HomepageFeaturedCourseController::class, 'destroy'])->name('featured-courses.destroy');
Route::post('/homepage/featured-courses/reorder', [HomepageFeaturedCourseController::class, 'reorder'])->name('featured-courses.reorder');

// Sidebar widget ordering
Route::post('/homepage/widget-order', [HomepageSettingController::class, 'updateWidgetOrder'])->name('homepage.widget-order');

// Content categories (homepage type filter) + visibility toggle
Route::post('/homepage/content-categories', [HomepageSettingController::class, 'updateContentCategories'])->name('homepage.content-categories');
```

### Route table

| METHOD | /path | route.name | Controller@action | 說明 |
|--------|-------|------------|-------------------|------|
| POST | /admin/homepage/featured-courses | admin.featured-courses.store | HomepageFeaturedCourseController@store | 加入精選課程（course_id + optional blurb） |
| PUT | /admin/homepage/featured-courses/{featuredCourse} | admin.featured-courses.update | HomepageFeaturedCourseController@update | 更新精選課程的自訂介紹 |
| DELETE | /admin/homepage/featured-courses/{featuredCourse} | admin.featured-courses.destroy | HomepageFeaturedCourseController@destroy | 移除精選課程 |
| POST | /admin/homepage/featured-courses/reorder | admin.featured-courses.reorder | HomepageFeaturedCourseController@reorder | 依 ids 陣列重排 sort_order |
| POST | /admin/homepage/widget-order | admin.homepage.widget-order | HomepageSettingController@updateWidgetOrder | 儲存側欄 widget 順序至 sidebar_widget_order |
| POST | /admin/homepage/content-categories | admin.homepage.content-categories | HomepageSettingController@updateContentCategories | 儲存內容分類（label+slug）與顯示開關；改 slug 連動更新課程 |

### Request bodies

```php
// POST /admin/homepage/featured-courses
[
    'course_id' => 'required|integer|exists:courses,id',
    'blurb'     => 'nullable|string|max:500',  // 自訂介紹，可換行
]

// PUT /admin/homepage/featured-courses/{featuredCourse}
[
    'blurb' => 'nullable|string|max:500',
]

// POST /admin/homepage/featured-courses/reorder
[
    'ids'   => 'required|array',                            // 依畫面順序的 id 陣列
    'ids.*' => 'integer|exists:homepage_featured_courses,id',
]

// POST /admin/homepage/widget-order
[
    'order'   => 'required|array',
    'order.*' => 'string|in:featured_courses,social,blog',  // 側欄區塊鍵
]

// POST /admin/homepage/content-categories
[
    'enabled'            => 'boolean',                        // 全域顯示開關
    'categories'         => 'array|max:3',                    // 最多 3 格
    'categories.*.label' => 'nullable|string|max:50',         // 顯示文字
    'categories.*.slug'  => 'nullable|string|max:50|regex:/^[a-z-]+$/',  // 英文名
    // 額外規則：label/slug 需成對填寫、slug 不可重複；改 slug 連動更新 courses.content_category
]
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
  featuredCourses: Array<{      // ordered by sort_order; course relation eager-loaded
    id:        number,          // homepage_featured_courses.id
    course_id: number,
    name:      string,          // course.name
    thumbnail: string | null,   // course.thumbnail_url
    blurb:     string | null,   // custom intro
  }>,
  availableCourses: Array<{     // all courses, for the "add" dropdown
    id:   number,
    name: string,
  }>,
  sidebarOrder: string[],       // normalised widget keys: featured_courses | social | blog
  contentCategorySlots: Array<{ // exactly 3 slots (blanks included) for the editor
    label: string,              // 顯示文字 ('' if empty)
    slug:  string,              // 英文名 ('' if empty)
  }>,
  contentFilterEnabled: boolean, // global toggle state
}
```

### Home page props (Server → Vue, `HomeController@index` → `Home`)

```typescript
{
  // ...existing courses / hero / socialLinks / blogArticles / isAdmin...
  featuredCourses: Array<{
    id:        number,          // course id (for the /course/{id} link)
    name:      string,
    thumbnail: string | null,
    blurb:     string | null,
    url:       string,          // "/course/{id}"
  }>,
  sidebarOrder: string[],       // widget render order: featured_courses | social | blog
  contentCategories: Array<{    // filled category slots; [] when the filter toggle is off
    label: string,
    slug:  string,              // matched against courses.content_category for filtering
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

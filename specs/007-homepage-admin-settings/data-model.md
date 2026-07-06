# Data Model: Homepage Admin Settings

**Branch**: `007-homepage-admin-settings` | **Date**: 2026-03-25
**Updated**: 2026-07-05 - 新增 `homepage_featured_courses` 表（精選課程）與 `sidebar_widget_order` site_setting 鍵（側欄排序）

---

## New Tables

### `site_settings`

Stores scalar homepage configuration values. One row per key.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint unsigned | PK, auto-increment | |
| `key` | varchar(255) | UNIQUE, NOT NULL | Namespaced key (see keys below) |
| `value` | text | NULLABLE | All values stored as text; cast at model layer |
| `created_at` | timestamp | NULLABLE | |
| `updated_at` | timestamp | NULLABLE | |

**Keys used by this feature:**

| Key | Default | Description |
|-----|---------|-------------|
| `hero_title` | `経營者時間銀行` | Hero heading text |
| `hero_description` | `省去摸索、試錯…` | Hero body text (multi-line) |
| `hero_button_label` | `null` | CTA button label (hidden if null/empty) |
| `hero_button_url` | `null` | CTA button destination URL |
| `hero_banner_path` | `null` | Relative path within public storage (e.g. `hero-banner/abc.jpg`) |
| `blog_rss_url` | `https://getwhealthy.substack.com/feed` | RSS feed URL; empty = hide section |
| `sns_section_enabled` | `1` | `1` = show SNS block, `0` = hide |
| `sidebar_widget_order` | `["featured_courses","social","blog"]` | JSON array of right-sidebar widget keys; normalised on read (unknown keys dropped, missing keys appended) |

---

### `social_links`

Stores admin-managed social media links. Displayed on homepage in creation order (sort_order).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint unsigned | PK, auto-increment | |
| `platform` | varchar(50) | NOT NULL | One of: `instagram`, `threads`, `youtube`, `facebook`, `substack`, `podcast` |
| `url` | varchar(500) | NOT NULL | Full URL including protocol |
| `sort_order` | smallint unsigned | NOT NULL, DEFAULT 0 | Assigned on creation as `max(sort_order) + 1`; determines display order |
| `created_at` | timestamp | NULLABLE | |
| `updated_at` | timestamp | NULLABLE | |

**Indexes**: `INDEX (sort_order)` for efficient ordered reads.

**No `is_enabled` column**: Links are either present or deleted. Use the global `sns_section_enabled` setting to hide the entire section.

---

### `homepage_featured_courses`

Stores admin-curated courses pinned to the homepage right sidebar. Displayed in `sort_order`. **Added 2026-07-05.**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | bigint unsigned | PK, auto-increment | |
| `course_id` | bigint unsigned | FK → `courses.id`, NOT NULL, `cascadeOnDelete` | Featured course; entry auto-removed if course deleted |
| `blurb` | varchar(500) | NULLABLE | Custom one-line intro (line breaks preserved); empty → homepage falls back to course name |
| `sort_order` | smallint unsigned | NOT NULL, DEFAULT 0 | Assigned on creation as `max(sort_order) + 1`; drag-to-reorder rewrites |
| `created_at` | timestamp | NULLABLE | |
| `updated_at` | timestamp | NULLABLE | |

**Indexes**: `INDEX (sort_order)`.
**Migrations**: `..._create_homepage_featured_courses_table` (blurb varchar 255), `..._extend_blurb_on_homepage_featured_courses` (blurb → varchar 500).
**Duplicates allowed**: the same `course_id` may appear multiple times (each with its own blurb).

---

## Models

### `App\Models\SiteSetting`

```
$fillable    : ['key', 'value']
casts()      : (none — all values are text)
Scopes       : (none needed)
Static helpers:
  get(string $key, $default = null) → single key lookup via where('key')
  getMany(array $keys) → Collection  — whereIn('key')->pluck('value', 'key')
  set(string $key, $value)           — updateOrCreate(['key'=>$key],['value'=>$value])
```

### `App\Models\SocialLink`

```
$fillable    : ['platform', 'url', 'sort_order']
casts()      : ['sort_order' => 'integer']
Scopes       :
  scopeOrdered($q) → orderBy('sort_order')   — used on homepage + admin list
```

### `App\Models\HomepageFeaturedCourse` *(added 2026-07-05)*

```
$fillable    : ['course_id', 'blurb', 'sort_order']
casts()      : ['course_id' => 'integer', 'sort_order' => 'integer']
Scopes       :
  scopeOrdered($q) → orderBy('sort_order')
Relations    :
  course() → belongsTo(Course::class)
```

### `App\Http\Controllers\Admin\HomepageSettingController` — static helper *(added 2026-07-05)*

```
const SIDEBAR_WIDGETS = ['featured_courses', 'social', 'blog']  — default order
sidebarWidgetOrder(): array
  → reads site_settings 'sidebar_widget_order' (JSON), intersects with known keys,
    appends any missing known keys → always returns exactly the known widgets
```

---

## Seeder: `HomepageSettingsSeeder`

Seeds default values so the homepage renders correctly before the admin has configured anything.

**`site_settings` rows** (7):
```
hero_title          = 經營者時間銀行
hero_description    = 省去摸索、試錯，高效經營你的人生，朝著健康、快樂、富足前進。
hero_button_label   = (empty string)
hero_button_url     = (empty string)
hero_banner_path    = (null)
blog_rss_url        = https://getwhealthy.substack.com/feed
sns_section_enabled = 1
```

**`social_links` rows** (6) — seeded from current hardcoded values in `SocialLinks.vue`:

| sort_order | platform | url |
|-----------|----------|-----|
| 1 | instagram | https://www.instagram.com/kyontw |
| 2 | threads | https://www.threads.com/@yueyuknows |
| 3 | youtube | https://www.youtube.com/@kyontw828 |
| 4 | facebook | https://www.facebook.com/kyontw828 |
| 5 | substack | https://getwhealthy.substack.com/ |
| 6 | podcast | https://kyontw.firstory.io/ |

---

## Modified Models (No Schema Change)

### `App\Services\BlogRssService` (renamed from `SubstackRssService`)

Not a model, but affects data flow. Signature change:
- Old: `getArticles(): array` (hardcoded URL)
- New: `getArticles(string $rssUrl): array` (URL as parameter)
- Returns `[]` immediately if `$rssUrl` is empty
- Cache key: `'blog_articles_' . md5($rssUrl)`

---

## Entity Relationships

```
site_settings (standalone key-value — no FK relationships)

social_links (standalone — no FK relationships)
  ↑ read by HomeController → passed as Inertia prop → SocialLinks.vue
  ↑ managed by Admin\SocialLinkController

homepage_featured_courses (course_id → courses.id, cascadeOnDelete)
  ↑ read by HomeController → featuredCourses prop → FeaturedCourses.vue
  ↑ managed by Admin\HomepageFeaturedCourseController (store/update/destroy/reorder)

site_settings['sidebar_widget_order']  (JSON widget order)
  ↑ read by HomeController → sidebarOrder prop → Home.vue renders widgets in order
  ↑ managed by Admin\HomepageSettingController@updateWidgetOrder
```

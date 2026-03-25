# Research: Homepage Admin Settings

**Branch**: `007-homepage-admin-settings` | **Date**: 2026-03-25

## Decision 1: Image Dimension Enforcement

**Decision**: Use Laravel's built-in `dimensions:min_width=1200` validation rule in the Form Request.

**Rationale**: Laravel has a native `dimensions` rule that rejects uploads below a minimum pixel size. It is cleaner than calling `getimagesize()` manually in the controller and naturally produces a translatable validation error. The existing codebase uses `getimagesize()` in `CourseImageController` only to *record* dimensions after upload (not for rejection), so the `dimensions` rule is an additive pattern that keeps the controller thin.

**Alternatives considered**:
- Manual `getimagesize()` check in controller → rejected (business rule validation belongs in Form Request per constitution I)
- Client-side dimension check → rejected (bypassable; spec requires server-side rejection)

**Evidence**: Laravel `dimensions` rule docs; `CourseImageController.php:60-62` uses `getimagesize()` post-upload for logging only.

---

## Decision 2: Settings Storage — Key-Value Table

**Decision**: A single `site_settings` table (`id`, `key` unique, `value` text nullable) stores all scalar homepage settings (hero fields + RSS URL + SNS toggle).

**Rationale**: Matches the `config/drip.php` pattern (one config domain per file/table) but promotes it to a DB-backed store for admin editability. Key-value is extensible without schema changes. The six hero/RSS/toggle keys are read together in one `whereIn` query, keeping HomeController to one extra DB call.

**Alternatives considered**:
- Dedicated columns in a single `homepage_settings` table → rejected (schema change required for every new setting)
- JSON column on a settings row → rejected (harder to update individual keys atomically; less readable in MySQL console)

---

## Decision 3: Social Links — Separate Table, No `is_enabled`

**Decision**: A `social_links` table (`id`, `platform`, `url`, `sort_order`, `timestamps`) — no `is_enabled` column. Links are either present or deleted. A global `sns_section_enabled` key in `site_settings` controls section-level visibility.

**Rationale**: The spec (post-clarification) has no per-link disable — admins delete links they don't want. Removing `is_enabled` reduces surface area and prevents a confusing "disabled but present" state. The global toggle is a scalar stored alongside other site settings in `site_settings`.

**Alternatives considered**:
- JSON array in `site_settings` → rejected (cannot edit/delete individual items atomically; no created_at per entry)
- `is_enabled` per row → rejected (spec explicitly removed this; over-complicates the UI)

---

## Decision 4: BlogRssService — URL as Parameter

**Decision**: Rename `SubstackRssService` → `BlogRssService`. The constructor no longer hardcodes the RSS URL; `getArticles(string $rssUrl)` accepts the URL as a parameter. Cache key: `'blog_articles_' . md5($rssUrl)`.

**Rationale**: Follows spec FR-014 (admin-configurable URL). Parameterising the URL allows the same service to fetch any RSS feed. Cache key includes a URL hash so changing the URL automatically uses a fresh cache entry.

**Cache invalidation**: `HomepageSettingController::update()` calls `Cache::forget('blog_articles_' . md5($oldUrl))` before saving the new URL — ensures next homepage load fetches fresh content.

**Alternatives considered**:
- Keep `SubstackRssService` with config-based URL (original spec 004 plan) → rejected (admin UI requires DB-backed URL, not config file)

---

## Decision 5: No Dedicated HomepageService

**Decision**: `HomepageSettingController` performs Eloquent operations directly (no separate Service class).

**Rationale**: Constitution I states "Simple single-model CRUD MAY perform direct Eloquent operations without a Service when no cross-model side-effects are involved." Hero settings update touches only `site_settings` rows. Banner upload is analogous to `Admin\CourseController` thumbnail handling (file store + model field update). Cache::forget for RSS is a one-liner side effect, not a complex state machine.

**Alternatives considered**:
- `HomepageService` → rejected (no multi-model coordination; YAGNI per constitution X)

---

## Decision 6: Banner URL Accessor Pattern

**Decision**: `SiteSetting` has no URL accessor. `HomepageSettingController::edit()` and `HomeController::index()` resolve the banner URL inline: `Storage::url($path)` if path is set, else `null`. This mirrors `Course::thumbnailUrl()` accessor logic but kept in the controller for simplicity.

**Evidence**: `Course.php:245-250` shows the accessor pattern; `filesystems.php:41-48` shows public disk URL = `APP_URL/storage`.

---

## Decision 7: Component Rename — SubstackArticles → BlogArticles

**Decision**: Rename `SubstackArticles.vue` → `BlogArticles.vue` and update the Inertia prop from `substackArticles` → `blogArticles` for naming consistency.

**Rationale**: The component now serves any configurable blog RSS feed, not just Substack. Keeping the old name would be misleading. The rename is cosmetic and has no architectural implications.

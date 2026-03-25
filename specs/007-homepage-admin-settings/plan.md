# Implementation Plan: Homepage Admin Settings

**Branch**: `007-homepage-admin-settings` | **Date**: 2026-03-25 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/007-homepage-admin-settings/spec.md`

---

## Summary

Add a database-backed admin page at `/admin/homepage` that lets the site administrator manage:
1. **Hero Unit** — banner image (≥1200px wide), title, description, CTA button (label + URL)
2. **SNS Links** — dynamic CRUD list (platform dropdown + URL); global show/hide toggle
3. **Blog RSS URL** — configurable feed URL; empty = hide "近期文章" section

Settings are persisted in two new tables (`site_settings` key-value store, `social_links`). The homepage reads from DB on each load via `SiteSetting::getMany()` batch query. `SubstackRssService` is replaced by `BlogRssService` with the URL passed as a parameter. No drag-to-reorder; social links display in creation order.

---

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12
**Primary Dependencies**: Laravel 12, Inertia.js v2, Vue 3 (`<script setup>`), Tailwind CSS v4
**Storage**: MySQL — two new tables (`site_settings`, `social_links`); `Storage::disk('public')` for banner images
**Testing**: `php artisan test` (PHPUnit via Laravel)
**Target Platform**: Web (desktop + mobile, 320–1920px)
**Project Type**: Web application (monolith — Laravel backend + Inertia/Vue frontend)
**Performance Goals**: Homepage loads with DB settings in same response time as current; RSS cached 1 hour
**Constraints**: Banner minimum 1200px wide; image max 5MB; SNS toggle hides entire section when off or empty
**Scale/Scope**: Single admin user; ~7 settings rows; ~6 social link rows

---

## Constitution Check

| Gate | Status | Notes |
|------|--------|-------|
| I. Thin controllers | ✅ Pass | `HomepageSettingController` does direct Eloquent + file ops (no service); no multi-model coordination |
| II. Service for external I/O | ✅ Pass | `BlogRssService` wraps external RSS HTTP call + cache |
| IV. Model conventions | ✅ Pass | `SiteSetting` has `get/getMany/set` static helpers; `SocialLink` has `scopeOrdered` |
| V. Form Request validation | ✅ Pass | `UpdateHomepageSettingRequest` + `StoreSocialLinkRequest` |
| IX. No N+1 | ✅ Pass | `SiteSetting::getMany()` batch-loads all keys in one `whereIn`; `SocialLink::ordered()->get()` is one query |
| X. YAGNI / simplicity | ✅ Pass | No `HomepageService` (single-model CRUD); no drag-to-reorder (spec excludes it); no `is_enabled` on social_links |

---

## Project Structure

### Documentation (this feature)

```text
specs/007-homepage-admin-settings/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 — 7 architectural decisions
├── data-model.md        # Phase 1 — tables, models, seeder
├── quickstart.md        # Phase 1 — setup + acceptance test checklist
├── contracts/
│   └── inertia-props.md # Phase 1 — routes, props, form submissions, validation
└── tasks.md             # Phase 2 output (/speckit.tasks — NOT created here)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── HomeController.php                        # MODIFY — read from DB, rename props
│   │   └── Admin/
│   │       ├── HomepageSettingController.php          # CREATE — edit/update/deleteBanner
│   │       └── SocialLinkController.php               # CREATE — store/update/destroy
│   └── Requests/
│       └── Admin/
│           ├── UpdateHomepageSettingRequest.php        # CREATE
│           └── StoreSocialLinkRequest.php              # CREATE
├── Models/
│   ├── SiteSetting.php                               # CREATE — get/getMany/set statics
│   └── SocialLink.php                                # CREATE — scopeOrdered
└── Services/
    ├── BlogRssService.php                            # CREATE — replaces SubstackRssService
    └── SubstackRssService.php                        # DELETE (after BlogRssService working)

database/
├── migrations/
│   ├── 2026_03_25_000001_create_site_settings_table.php   # CREATE
│   └── 2026_03_25_000002_create_social_links_table.php    # CREATE
└── seeders/
    ├── DatabaseSeeder.php                            # MODIFY — call HomepageSettingsSeeder
    └── HomepageSettingsSeeder.php                    # CREATE

routes/
└── web.php                                           # MODIFY — add 6 admin routes

resources/js/
├── Layouts/
│   └── AdminLayout.vue                              # MODIFY — add "首頁設定" nav item
├── Pages/
│   ├── Home.vue                                     # MODIFY — hero unit + new props
│   └── Admin/
│       └── HomepageSettings/
│           └── Edit.vue                             # CREATE — 3-section admin page
└── Components/
    ├── SocialLinks.vue                              # MODIFY — accept `links` prop
    ├── BlogArticles.vue                             # RENAME from SubstackArticles.vue
    └── SubstackArticles.vue                         # DELETE (after rename)
```

**Structure Decision**: Single Laravel monolith. Backend changes in `app/` and `database/`; frontend in `resources/js/`. No new top-level directories required.

---

## Implementation Order

1. **Migrations** → `php artisan migrate`
2. **Models** — SiteSetting + SocialLink
3. **Seeder** — HomepageSettingsSeeder → `php artisan db:seed --class=HomepageSettingsSeeder`
4. **BlogRssService** — copy from SubstackRssService, parameterise URL
5. **HomeController** — inject BlogRssService, read from DB, rename props
6. **SocialLinks.vue** — accept `links` prop (keep SVG map)
7. **Home.vue** — hero unit + new props + pass `links` to SocialLinks + use BlogArticles
8. ✓ Smoke test: homepage renders correctly from seeded data
9. **Routes** — 6 admin routes in `auth + admin` group
10. **Form Requests** — UpdateHomepageSettingRequest + StoreSocialLinkRequest
11. **HomepageSettingController** — edit + update (inline banner upload) + deleteBanner
12. **SocialLinkController** — store + update + destroy
13. **Admin/HomepageSettings/Edit.vue** — 3-section admin page
14. **AdminLayout.vue** — add "首頁設定" nav entry
15. ✓ End-to-end test: admin page → save → homepage reflects changes
16. **Delete** `SubstackRssService.php`

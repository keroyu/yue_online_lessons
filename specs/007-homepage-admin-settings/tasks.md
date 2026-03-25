# Tasks: Homepage Admin Settings

**Input**: Design documents from `/specs/007-homepage-admin-settings/`
**Branch**: `007-homepage-admin-settings`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/inertia-props.md, research.md, quickstart.md

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies on sibling tasks)
- **[Story]**: Which user story this task belongs to
- Exact file paths included in every task

---

## Phase 1: Setup

**Purpose**: Verify prerequisite environment before code changes

- [ ] T001 Verify storage symlink exists; run `php artisan storage:link` if missing

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: DB schema, core models, seed data, and service layer — MUST be complete before any user story

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T002 Create migration `create_site_settings_table` (id, key UNIQUE, value text nullable, timestamps) in `database/migrations/2026_03_25_000001_create_site_settings_table.php`
- [ ] T003 Create migration `create_social_links_table` (id, platform varchar(50), url varchar(500), sort_order smallint unsigned DEFAULT 0 with INDEX, timestamps) in `database/migrations/2026_03_25_000002_create_social_links_table.php`
- [ ] T004 [P] Create `SiteSetting` model with `$fillable = ['key', 'value']` and three static helpers (`get`, `getMany`, `set`) in `app/Models/SiteSetting.php`
- [ ] T005 [P] Create `SocialLink` model with `$fillable = ['platform', 'url', 'sort_order']`, cast `sort_order` to int, and `scopeOrdered` (`orderBy('sort_order')`) in `app/Models/SocialLink.php`
- [ ] T006 Create `HomepageSettingsSeeder`: insert 7 `site_settings` rows (hero_title, hero_description, hero_button_label, hero_button_url, hero_banner_path, blog_rss_url, sns_section_enabled) and 6 `social_links` rows from current hardcoded values in `SocialLinks.vue` in `database/seeders/HomepageSettingsSeeder.php`
- [ ] T007 Register `HomepageSettingsSeeder` in `database/seeders/DatabaseSeeder.php`
- [ ] T008 Create `BlogRssService` with `getArticles(string $rssUrl): array` — URL as parameter, cache key `'blog_articles_' . md5($rssUrl)` TTL 3600, returns `[]` if `$rssUrl` is empty — in `app/Services/BlogRssService.php`
- [ ] T009 Update `HomeController::index()`: inject `BlogRssService` instead of `SubstackRssService`; batch-load settings via `SiteSetting::getMany([...])` one query; build `$hero` array (title, description, button_label, button_url, banner_url via `Storage::url($path)`); load `$socialLinks` from `SocialLink::ordered()->get()` only when `sns_section_enabled`; pass renamed props (`hero`, `socialLinks`, `blogArticles`) to Inertia in `app/Http/Controllers/HomeController.php`
- [ ] T010 Run `php artisan migrate` then `php artisan db:seed --class=HomepageSettingsSeeder` and smoke-test homepage at `/` renders without errors using seeded data

**Checkpoint**: Migrations run, models available, homepage reads from DB with seeded data — user story implementation can now begin

---

## Phase 3: User Story 1 — Edit Hero Unit via Admin (Priority: P1) 🎯 MVP

**Goal**: Admin can upload a banner image, set title/description/button, and save — homepage reflects changes

**Independent Test**: Visit `/admin/homepage` → upload banner ≥1200px wide → fill title/description/button fields → save → verify hero preview shown and homepage hero updates. Upload <1200px image → verify rejection with validation message.

- [ ] T011 [P] [US1] Create `UpdateHomepageSettingRequest` with rules for all hero fields (nullable string/url/max) and `hero_banner` (image, mimes:jpg,jpeg,png,webp, max:5120, dimensions:min_width=1200) and custom Chinese error messages in `app/Http/Requests/Admin/UpdateHomepageSettingRequest.php`
- [ ] T012 [P] [US1] Create `HomepageSettingController` with three methods: `edit()` batch-loads all settings + social links and renders `Admin/HomepageSettings/Edit`; `update()` validates via `UpdateHomepageSettingRequest`, handles banner upload inline (delete old file → store to `hero-banner/` → update `hero_banner_path`) and calls `SiteSetting::set()` per field, invalidates RSS cache via `Cache::forget('blog_articles_' . md5($oldUrl))` when URL changes, redirects back with flash; `deleteBanner()` deletes file from `Storage::disk('public')`, sets key to null, redirects back with flash in `app/Http/Controllers/Admin/HomepageSettingController.php`
- [ ] T013 [US1] Add 3 hero admin routes to existing `auth + admin` middleware group: `GET /admin/homepage` (homepage.edit), `POST /admin/homepage` (homepage.update), `DELETE /admin/homepage/banner` (homepage.banner.destroy) in `routes/web.php`
- [ ] T014 [US1] Create `Admin/HomepageSettings/Edit.vue` with `defineOptions({ layout: AdminLayout })` and props `settings` + `socialLinks`; implement Section 1 (Hero 設定): banner preview with current image or placeholder, file input for `hero_banner`, "刪除橫幅圖片" button → `router.delete('/admin/homepage/banner')`, inputs for hero_title/hero_description (textarea)/hero_button_label/hero_button_url, blog_rss_url input with helper text "留空則隱藏「近期文章」區塊", sns_section_enabled toggle; save via `router.post('/admin/homepage', formData)` using `FormData` for file upload in `resources/js/Pages/Admin/HomepageSettings/Edit.vue`
- [ ] T015 [US1] Add "首頁設定" entry to `navigation` array in `AdminLayout.vue` linking to `/admin/homepage` in `resources/js/Layouts/AdminLayout.vue`

**Checkpoint**: Admin can manage hero content end-to-end; banner upload/delete works; validation rejects <1200px images

---

## Phase 4: User Story 2 — Manage SNS Links via Admin (Priority: P1)

**Goal**: Admin can add, edit, and delete social links; global toggle controls entire SNS section visibility

**Independent Test**: Visit `/admin/homepage` → click "+" → select platform from dropdown → enter URL → save → link appears in list. Click Edit → change URL → 儲存. Click delete + confirm → link removed. Toggle SNS to "不顯示" → section hidden on homepage.

- [ ] T016 [P] [US2] Create `StoreSocialLinkRequest` with rules (`platform`: required, in:instagram,threads,youtube,facebook,substack,podcast; `url`: required, url, max:500) and Chinese error messages in `app/Http/Requests/Admin/StoreSocialLinkRequest.php`
- [ ] T017 [P] [US2] Create `SocialLinkController` with three methods: `store()` validates via `StoreSocialLinkRequest`, creates with `sort_order = SocialLink::max('sort_order') + 1`, redirects back with flash; `update($socialLink)` validates `url` (required url max:500), updates url, redirects back with flash; `destroy($socialLink)` deletes row, redirects back with flash in `app/Http/Controllers/Admin/SocialLinkController.php`
- [ ] T018 [US2] Add 3 social link routes to existing `auth + admin` group: `POST /admin/homepage/social-links` (social-links.store), `PUT /admin/homepage/social-links/{socialLink}` (social-links.update), `DELETE /admin/homepage/social-links/{socialLink}` (social-links.destroy) in `routes/web.php`
- [ ] T019 [US2] Add SNS section (Section 2) to `Edit.vue`: `localLinks` ref synced with props; list each link with platform badge, URL display, inline Edit (URL input + 儲存/取消 buttons toggled per row, save via `router.put`), delete button with `confirm()` prompt (delete via `router.delete`); "+" button that reveals platform dropdown (6 options) + URL input + 新增 button (POST via `router.post`); SNS toggle is already part of Section 1 form in `resources/js/Pages/Admin/HomepageSettings/Edit.vue`
- [ ] T020 [US2] Update `SocialLinks.vue`: remove hardcoded `const socialLinks = [...]` array; add `defineProps({ links: { type: Array, default: () => [] } })`; keep platform SVG icons as `const platformIcons = { ... }` map; render with `v-for="link in links"`, hide entire component when `links.length === 0` in `resources/js/Components/SocialLinks.vue`

**Checkpoint**: Admin can fully manage social links; SNS global toggle works; homepage sidebar reflects changes

---

## Phase 5: User Story 3 + User Story 4 — Blog RSS Config & Homepage Display (Priority: P2)

**Goal (US3)**: Admin configures blog RSS URL; empty URL hides "近期文章" section on homepage
**Goal (US4)**: Visitors see a redesigned hero section with banner image, white title on black strip, white description with drop shadow, and EXPLORE button

**Independent Test (US3)**: Enter valid RSS URL → save → homepage shows ≤5 recent articles. Clear URL → save → "近期文章" hidden.
**Independent Test (US4)**: Visit homepage with seeded banner → verify image at full width, title on black strip, description with shadow, EXPLORE button at bottom-right. Hover → image darkens, button brightens. At 320px → no layout overflow.

- [ ] T021 [P] [US3/US4] Rename `SubstackArticles.vue` to `BlogArticles.vue` and update internal prop references from `articles` (no change needed if already using `articles` prop) in `resources/js/Components/BlogArticles.vue`
- [ ] T022 [US4] Redesign hero section in `Home.vue`: replace plain text hero with `<div class="relative ... group overflow-hidden">` containing hover overlay (`bg-black/0 group-hover:bg-black/20`), banner image or fallback solid-colour `<div>`, text block at bottom-left (title as `inline-block bg-black px-2 py-1 text-white font-bold`, description as `text-white drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)]`), EXPLORE button at bottom-right (only when `hero.button_url && hero.button_label`); add `hero` and `socialLinks` props; pass `links` to `<SocialLinks>` and `articles` to `<BlogArticles>`; update `substackArticles` prop reference to `blogArticles` in `resources/js/Pages/Home.vue`

**Checkpoint**: Homepage displays new hero design from DB data; RSS articles shown or section hidden based on URL; all sections responsive 320–1920px

---

## Phase 6: Polish & Cleanup

- [ ] T023 Delete `app/Services/SubstackRssService.php` (replaced by `BlogRssService`)
- [ ] T024 Run full acceptance test checklist from `specs/007-homepage-admin-settings/quickstart.md`
- [ ] T025 Run `php artisan test` to verify no regressions

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Phase 1 — **BLOCKS all user stories**
- **US1 (Phase 3)**: Depends on Phase 2 completion
- **US2 (Phase 4)**: Depends on Phase 2 completion — can run in parallel with Phase 3
- **US3+US4 (Phase 5)**: Depends on Phase 2 completion; US4 hero display benefits from Phase 3 admin being complete but can be tested with seeded data
- **Polish (Phase 6)**: Depends on Phases 3–5 completion

### User Story Dependencies

- **US1 (Phase 3)**: No dependency on other stories — start after Phase 2
- **US2 (Phase 4)**: No dependency on US1 — start after Phase 2
- **US3+US4 (Phase 5)**: Depends on Phase 2; BlogRssService (T008) must exist before T021/T022

### Parallel Opportunities Within Phases

**Phase 2** — can run in parallel once migrations done:
```
T004 SiteSetting model   ‖  T005 SocialLink model
T008 BlogRssService      ‖  T009 HomeController update  (after T004+T005)
```

**Phase 3** — T011 and T012 can start together:
```
T011 UpdateHomepageSettingRequest  ‖  T012 HomepageSettingController
```
Then T013 → T014 → T015 sequentially.

**Phase 4** — T016 and T017 can start together:
```
T016 StoreSocialLinkRequest  ‖  T017 SocialLinkController
```
Then T018 → T019 → T020.

---

## Implementation Strategy

### MVP (Phase 2 + Phase 3 only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational → run migrations + seed
3. Complete Phase 3: US1 Hero Unit (admin + homepage hero display added in Phase 5 but seeded data works for visual check)
4. **STOP and VALIDATE**: Admin can save hero settings; validation rejects bad images
5. Deploy/demo with hero admin working

### Incremental Delivery

1. Phase 1 + 2 → DB ready, homepage reads from DB ✓
2. Phase 3 → Hero admin CRUD working ✓
3. Phase 4 → SNS links admin working ✓
4. Phase 5 → Homepage fully redesigned; RSS configurable ✓
5. Phase 6 → Cleanup and final QA ✓

---

## Notes

- `sns_section_enabled` toggle is part of the hero form POST — handled in UpdateHomepageSettingRequest + HomepageSettingController (Phase 3), displayed in Edit.vue Section 1
- RSS URL is saved via the same `POST /admin/homepage` endpoint as hero fields — no separate endpoint needed
- `Storage::disk('public')` requires the symlink from T001; banner files stored at `hero-banner/{filename}`
- Phase 3 T012 HomepageSettingController handles RSS cache invalidation inline — no separate service needed
- Social links display order = `sort_order` column (set to `max + 1` on creation); no drag-to-reorder

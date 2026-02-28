# Tasks: Homepage Enhancement - Blog Articles & Social Links (Config Update)

**Input**: Design documents from `/specs/004-homepage-enhancement/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/, research.md, quickstart.md

**Tests**: Not requested - test tasks omitted.

**Context**: This is an incremental update to an already-implemented feature. Tasks focus on the **changes** needed:
1. Move social media URLs from Vue hardcode → `config/homepage.php`
2. Rename `SubstackRssService` → `BlogRssService` with config-driven RSS URL
3. Update controller and frontend to handle empty URLs (hide button if empty)

**Organization**: Tasks are grouped by user story to enable independent implementation and testing.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions (Laravel Project)

- **Backend**: `app/` (Controllers, Services, Models)
- **Frontend**: `resources/js/` (Pages, Components)
- **Config**: `config/`

---

## Phase 1: Setup

**Purpose**: Project initialization - Skip (existing Laravel project, no new dependencies needed)

No setup tasks required.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Config file that MUST exist before both User Story 1 and User Story 2 can be updated

- [ ] T001 Create `config/homepage.php` with `social_links` array (all 6 platforms with default URLs) and `blog_rss` array (url, cache_ttl, max_items)

**Config structure**:
```php
return [
    'social_links' => [
        'instagram' => env('SOCIAL_INSTAGRAM', 'https://www.instagram.com/kyontw'),
        'threads'   => env('SOCIAL_THREADS',   'https://www.threads.com/@yueyuknows'),
        'youtube'   => env('SOCIAL_YOUTUBE',   'https://www.youtube.com/@kyontw828'),
        'facebook'  => env('SOCIAL_FACEBOOK',  'https://www.facebook.com/kyontw828'),
        'substack'  => env('SOCIAL_SUBSTACK',  'https://getwhealthy.substack.com/'),
        'podcast'   => env('SOCIAL_PODCAST',   'https://kyontw.firstory.io/'),
    ],
    'blog_rss' => [
        'url'       => env('BLOG_RSS_URL',       'https://getwhealthy.substack.com/feed'),
        'cache_ttl' => env('BLOG_RSS_CACHE_TTL', 3600),
        'max_items' => env('BLOG_RSS_MAX_ITEMS', 5),
    ],
];
```

**Checkpoint**: Config file ready - US1 and US2 implementation can begin in parallel

---

## Phase 3: User Story 1 - View Recent Blog Articles (Priority: P1)

**Goal**: RSS service reads URL from config instead of being hardcoded to Substack

**Independent Test**: `php artisan tinker` → `app(App\Services\BlogRssService::class)->getArticles()` returns articles; change `BLOG_RSS_URL` in `.env` → service uses new URL

### Implementation for User Story 1

- [ ] T002 [US1] Create `app/Services/BlogRssService.php` — copy logic from `SubstackRssService.php`, replace hardcoded feed URL with `config('homepage.blog_rss.url')`, replace hardcoded TTL with `config('homepage.blog_rss.cache_ttl')`, replace hardcoded limit with `config('homepage.blog_rss.max_items')`; return empty array if URL is empty
- [ ] T003 [US1] Update `app/Http/Controllers/HomeController.php` — replace `SubstackRssService` injection with `BlogRssService`; rename Inertia prop from `substackArticles` to `blogArticles`
- [ ] T004 [US1] Delete `app/Services/SubstackRssService.php` after confirming `BlogRssService` is wired up and working

**Component note**: `resources/js/Components/SubstackArticles.vue` receives prop name change (`substackArticles` → `blogArticles`). Update prop name in component and in `Home.vue` usage.

- [ ] T005 [US1] Update `resources/js/Components/SubstackArticles.vue` — rename prop from `substackArticles` to `blogArticles`
- [ ] T006 [US1] Update `resources/js/Pages/Home.vue` — pass `:blogArticles="blogArticles"` instead of `:substackArticles`; ensure prop is declared in defineProps

**Checkpoint**: Blog articles display from config-driven RSS URL; articles section hides when RSS URL is empty or fetch fails

---

## Phase 4: User Story 2 - Access Social Media Links (Priority: P1)

**Goal**: Social links read from config; empty URL = button not rendered

**Independent Test**: Visit homepage → only configured (non-empty) social platform buttons visible; set one URL to empty string in config → that button disappears without breaking layout

### Implementation for User Story 2

- [ ] T007 [US2] Update `app/Http/Controllers/HomeController.php` — read `config('homepage.social_links')`, filter out empty values, map to `[platform, label, url]` array, pass as `socialLinks` Inertia prop
- [ ] T008 [US2] Update `resources/js/Components/SocialLinks.vue` — remove hardcoded `socialLinks` array; accept `socialLinks` as a required prop (array of `{platform, label, url}`); component renders buttons from props; section hidden when array is empty

**Checkpoint**: Social links display from config; removing a URL from config hides that button; setting all URLs empty hides the entire section

---

## Phase 5: User Story 3 - Responsive Sidebar Layout (Priority: P2)

**Goal**: Home.vue passes the `socialLinks` prop through to `SocialLinks` component correctly

**Independent Test**: Desktop (≥1024px): sidebar visible with social + articles; Mobile (<1024px): stacked below courses; layout unaffected whether 1 or 6 social links are shown

### Implementation for User Story 3

- [ ] T009 [US3] Update `resources/js/Pages/Home.vue` — add `socialLinks` to `defineProps`; pass `:socialLinks="socialLinks"` to `<SocialLinks>` component; verify sidebar layout remains correct with variable number of social buttons

**Checkpoint**: Full feature works end-to-end with responsive layout

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation per quickstart.md checklist

- [ ] T010 Test that setting `BLOG_RSS_URL=` (empty) in `.env` hides the articles section
- [ ] T011 Test that setting any social URL to empty string in `config/homepage.php` hides that platform button
- [ ] T012 Test RSS feed failure handling (verify page still loads without articles)
- [ ] T013 Test responsive layout: mobile (320px), tablet (768px), desktop (1920px)
- [ ] T014 Verify all links open in new tabs with correct URLs (`target="_blank" rel="noopener"`)
- [ ] T015 Run `php artisan cache:clear` and verify fresh RSS fetch works with new `BlogRssService`

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 2 (Foundation) → T001: config/homepage.php
        ↓
Phase 3 (US1)       → T002-T006: BlogRssService + Controller + Vue ─┐
        ↓                                                           │ Can run in parallel
Phase 4 (US2)       → T007-T008: Controller social links + SocialLinks.vue ──────────────┘
        ↓
Phase 5 (US3)       → T009: Home.vue wiring (needs US1 + US2 props ready)
        ↓
Phase 6 (Polish)    → T010-T015: Validation
```

### User Story Dependencies

| Story | Depends On | Can Parallel With |
|-------|------------|-------------------|
| US1 (Blog Articles) | T001 (config) | US2 |
| US2 (Social Links)  | T001 (config) | US1 |
| US3 (Responsive)    | US1 + US2    | None |

### Parallel Opportunities

**After T001 completes**, US1 and US2 can run in parallel:
```
T002-T006 (US1) ──┬── Can run simultaneously
T007-T008 (US2) ──┘
```

---

## Parallel Example: User Stories 1 & 2

```bash
# After T001 (config/homepage.php) is complete:

# Session 1 (US1 - Blog RSS):
Task: "T002 Create BlogRssService"
Task: "T003 Update HomeController (RSS)"
Task: "T004 Delete SubstackRssService"
Task: "T005 Update SubstackArticles.vue prop name"
Task: "T006 Update Home.vue prop name"

# Session 2 (US2 - Social Links, simultaneously):
Task: "T007 Update HomeController (social links)"
Task: "T008 Update SocialLinks.vue to use props"
```

---

## Implementation Strategy

### MVP First (Recommended)

1. **T001**: Create `config/homepage.php` (foundation)
2. **T002-T006**: Complete User Story 1 (Blog RSS from config)
3. **T007-T008**: Complete User Story 2 (Config-driven social links)
4. **T009**: Complete User Story 3 (Wire Home.vue props)
5. **VALIDATE**: Run quickstart.md checklist
6. **T010-T015**: Polish tasks

### Quick Validation Points

After each user story, validate independently:
- **After US1**: Check homepage shows articles; set `BLOG_RSS_URL=` → articles hidden
- **After US2**: Set one social URL to `''` in config → button disappears
- **After US3**: Full integration test on all screen sizes

---

## Notes

- No database migrations required
- `SubstackRssService.php` is deleted after `BlogRssService.php` is working (T004)
- `SubstackArticles.vue` component name can stay as-is (rename is optional cosmetic change)
- RSS cache key in `BlogRssService` should use a generic key (e.g., `blog_articles`) rather than `substack_articles`
- All external links: `target="_blank" rel="noopener"`
- Config changes take effect immediately (no cache:clear needed for config, only for RSS data cache)

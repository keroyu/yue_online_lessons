# Tasks: Homepage Enhancement - Substack Articles & Social Links

**Input**: Design documents from `/specs/004-homepage-enhancement/`
**Prerequisites**: plan.md, spec.md, data-model.md, contracts/, research.md, quickstart.md

**Tests**: Not requested - test tasks omitted.

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

**Purpose**: Project initialization - Skip (existing Laravel project)

No setup tasks required. Project already has Laravel 12, Inertia.js, Vue 3, and Tailwind CSS configured.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Backend service that MUST be complete before User Story 1 can be implemented

- [x] T001 Create SubstackRssService with RSS fetch and cache logic in `app/Services/SubstackRssService.php`

**Service Requirements**:
- Fetch RSS from `https://getwhealthy.substack.com/feed`
- Parse XML using SimpleXML
- Cache for 1 hour (3600 seconds TTL)
- Return array of 5 most recent articles (title, url, published_at)
- Handle failures gracefully (return empty array)

**Checkpoint**: Backend RSS service ready - User Story 1 implementation can begin

---

## Phase 3: User Story 1 - View Recent Substack Articles (Priority: P1)

**Goal**: Display 5 most recent Substack articles with titles, dates, and clickable links

**Independent Test**: Visit homepage → see 5 articles → click article → opens in new tab

### Implementation for User Story 1

- [x] T002 [US1] Update HomeController to inject SubstackRssService and pass articles in `app/Http/Controllers/HomeController.php`
- [x] T003 [US1] Create SubstackArticles component in `resources/js/Components/SubstackArticles.vue`

**Component Requirements (T003)**:
- Accept `articles` prop (array)
- Display article title and formatted date
- Title links open in new tab (`target="_blank"`)
- Hide section if articles array is empty
- Style with Tailwind CSS

**Checkpoint**: Substack articles display on homepage (can test independently)

---

## Phase 4: User Story 2 - Access Social Media Links (Priority: P1)

**Goal**: Display 6 social media buttons that link to instructor's profiles

**Independent Test**: Visit homepage → see 6 social buttons → click each → opens correct profile in new tab

### Implementation for User Story 2

- [x] T004 [P] [US2] Create SocialLinks component with hardcoded URLs and SVG icons in `resources/js/Components/SocialLinks.vue`

**Component Requirements (T004)**:
- Hardcode social links array:
  ```javascript
  const socialLinks = [
    { platform: 'instagram', label: 'Instagram', url: 'https://www.instagram.com/kyontw' },
    { platform: 'threads', label: 'Threads', url: 'https://www.threads.com/@yueyuknows' },
    { platform: 'youtube', label: 'YouTube', url: 'https://www.youtube.com/@kyontw828' },
    { platform: 'facebook', label: 'Facebook', url: 'https://www.facebook.com/kyontw828' },
    { platform: 'substack', label: 'Substack', url: 'https://getwhealthy.substack.com/' },
    { platform: 'podcast', label: 'Podcast', url: 'https://kyontw.firstory.io/' },
  ]
  ```
- Pill-shaped buttons with inline SVG icons
- Links open in new tab (`target="_blank" rel="noopener"`)
- No follower counts displayed
- Style with Tailwind CSS

**Checkpoint**: Social links display and work (can test independently)

---

## Phase 5: User Story 3 - Responsive Sidebar Layout (Priority: P2)

**Goal**: Integrate both components into a responsive sidebar layout

**Independent Test**:
- Desktop (≥1024px): Courses left, sidebar right with social + articles
- Mobile (<1024px): Courses on top, social + articles stacked below

### Implementation for User Story 3

- [x] T005 [US3] Update Home.vue with CSS Grid sidebar layout in `resources/js/Pages/Home.vue`

**Layout Requirements (T005)**:
- CSS Grid: `grid-cols-1 lg:grid-cols-[1fr_300px]`
- Main area: existing courses grid
- Sidebar: SocialLinks component + SubstackArticles component
- Mobile: sidebar stacks below courses
- Import and use both new components
- Pass `substackArticles` prop to SubstackArticles component

**Checkpoint**: Full feature complete - responsive layout works on all screen sizes

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation and cleanup

- [x] T006 Test RSS feed failure handling (disconnect network, verify page still loads)
- [x] T007 Test responsive layout on mobile (320px), tablet (768px), desktop (1920px)
- [x] T008 Verify all links open in new tabs with correct URLs
- [x] T009 Clear cache and verify fresh RSS fetch (`php artisan cache:clear`)

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup)     → Skip (existing project)
        ↓
Phase 2 (Foundation) → T001: SubstackRssService
        ↓
Phase 3 (US1)       → T002, T003: Substack Articles ─┐
        ↓                                            │ Can run in parallel
Phase 4 (US2)       → T004: Social Links ────────────┘
        ↓
Phase 5 (US3)       → T005: Home.vue Layout (needs US1 + US2)
        ↓
Phase 6 (Polish)    → T006-T009: Testing & Validation
```

### User Story Dependencies

| Story | Depends On | Can Parallel With |
|-------|------------|-------------------|
| US1 (Substack Articles) | T001 (Service) | US2 |
| US2 (Social Links) | None | US1 |
| US3 (Responsive Layout) | US1 + US2 | None |

### Parallel Opportunities

**After T001 completes**, US1 and US2 can run in parallel:
```
T002, T003 (US1) ──┬── Can run simultaneously
T004 (US2) ────────┘
```

---

## Parallel Example: User Stories 1 & 2

```bash
# After T001 (SubstackRssService) is complete, launch in parallel:

# Developer A / Session 1:
Task: "T002 [US1] Update HomeController"
Task: "T003 [US1] Create SubstackArticles.vue"

# Developer B / Session 2 (simultaneously):
Task: "T004 [US2] Create SocialLinks.vue"
```

---

## Implementation Strategy

### MVP First (Recommended)

1. **T001**: Create SubstackRssService (foundation)
2. **T002-T003**: Complete User Story 1 (Substack Articles)
3. **T004**: Complete User Story 2 (Social Links)
4. **T005**: Complete User Story 3 (Sidebar Layout)
5. **VALIDATE**: Test full feature
6. **T006-T009**: Polish tasks

### Quick Validation Points

After each user story, validate independently:
- **After US1**: `php artisan tinker` → test SubstackRssService returns articles
- **After US2**: Temporarily add SocialLinks to Home.vue, verify buttons work
- **After US3**: Full integration test on all screen sizes

---

## Notes

- No database migrations required (uses Laravel Cache)
- Social URLs are hardcoded in Vue (no backend config)
- RSS cache TTL: 3600 seconds (1 hour)
- All external links: `target="_blank" rel="noopener"`
- SVG icons should follow platform brand guidelines

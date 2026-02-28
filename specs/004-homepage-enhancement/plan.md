# Implementation Plan: Homepage Enhancement - Blog Articles & Social Links

**Branch**: `004-homepage-enhancement` | **Date**: 2026-01-26 | **Updated**: 2026-02-27 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-homepage-enhancement/spec.md`

## Summary

Add two new sections to the homepage in a sidebar layout: (1) Blog article feed displaying 5 recent articles fetched from a configurable RSS URL with server-side caching, and (2) social media links as pill-shaped buttons with URLs stored in a Laravel config file (empty URL = button hidden). The sidebar appears alongside courses on desktop/tablet and stacks below on mobile.

**Update 2026-02-27**: RSS service renamed to generic `BlogRssService`; social media URLs moved from Vue hardcode to `config/homepage.php`; frontend receives social links as Inertia props (filtered to non-empty only).

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.x
**Primary Dependencies**: Laravel 12, Inertia.js, Vue 3, Tailwind CSS
**Storage**: Laravel Cache (file-based) for RSS feed caching
**Testing**: PHPUnit via `php artisan test`
**Target Platform**: Web (Laravel Forge deployment)
**Project Type**: Web application (existing Laravel + Inertia.js + Vue 3 stack)
**Performance Goals**: Homepage loads within 2 seconds including RSS data
**Constraints**: RSS cache refreshes hourly (3600 seconds TTL)
**Scale/Scope**: Single page enhancement (Home.vue + HomeController)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel Conventions | ✅ PASS | Using Service class for RSS, Controller for data passing |
| II. Vue & Frontend Standards | ✅ PASS | Using `<script setup>`, Components in `Components/` |
| III. Responsive Design First | ✅ PASS | Mobile-first with Tailwind breakpoints |
| IV. Simplicity Over Complexity | ✅ PASS | Minimal implementation, no over-engineering |
| V. Security & Sensitive Data | ✅ PASS | No sensitive data; URLs in config file |

**Gate Status**: ✅ PASSED - Proceed to Phase 0

### Post-Phase 1 Re-check

| Principle | Status | Design Validation |
|-----------|--------|-------------------|
| I. Laravel Conventions | ✅ PASS | Service class pattern, config file for settings |
| II. Vue & Frontend Standards | ✅ PASS | Components in `Components/`, `<script setup>` syntax |
| III. Responsive Design First | ✅ PASS | CSS Grid with mobile-first breakpoints |
| IV. Simplicity Over Complexity | ✅ PASS | No external packages, minimal code |
| V. Security & Sensitive Data | ✅ PASS | Public URLs only, no secrets |

**Post-Design Gate Status**: ✅ PASSED - Ready for Phase 2 (tasks)

## Project Structure

### Documentation (this feature)

```text
specs/004-homepage-enhancement/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
config/
│   └── homepage.php               # New: Social links URLs + RSS config
app/
├── Http/
│   └── Controllers/
│       └── HomeController.php    # Modified: Pass RSS articles + socialLinks
├── Services/
│   └── BlogRssService.php        # Renamed from SubstackRssService; generic RSS
└── ...

resources/js/
├── Components/
│   ├── SubstackArticles.vue       # Unchanged: Article list component
│   └── SocialLinks.vue            # Modified: URLs from props (not hardcoded)
└── Pages/
    └── Home.vue                   # Modified: Pass socialLinks prop
```

**Structure Decision**: Social URLs and RSS config moved to `config/homepage.php`. Controller reads config, filters out empty social URLs, passes to frontend via Inertia. RSS service renamed to `BlogRssService` for generality. Frontend article component unchanged; `SocialLinks.vue` updated to accept props instead of hardcoded data.

## Complexity Tracking

No violations - implementation follows constitution principles.

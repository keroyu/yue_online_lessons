# Implementation Plan: Homepage Enhancement - Substack Articles & Social Links

**Branch**: `004-homepage-enhancement` | **Date**: 2026-01-26 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-homepage-enhancement/spec.md`

## Summary

Add two new sections to the homepage in a sidebar layout: (1) Substack article feed displaying 5 recent articles fetched from RSS with server-side caching, and (2) social media links as pill-shaped buttons (URLs hardcoded in Vue component) for Instagram, YouTube, Facebook, Substack, and Podcast. The sidebar appears alongside courses on desktop/tablet and stacks below on mobile.

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
app/
├── Http/
│   └── Controllers/
│       └── HomeController.php    # Modified: Pass RSS articles
├── Services/
│   └── SubstackRssService.php    # New: RSS fetching + caching
└── ...

resources/js/
├── Components/
│   ├── SubstackArticles.vue       # New: Article list component
│   └── SocialLinks.vue            # New: Social links component (URLs hardcoded)
└── Pages/
    └── Home.vue                   # Modified: Sidebar layout
```

**Structure Decision**: Follows existing Laravel project structure. New service class for RSS logic, new Vue components for UI. Social media URLs hardcoded in Vue component (no backend config needed).

## Complexity Tracking

No violations - implementation follows constitution principles.

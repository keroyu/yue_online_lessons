# Quickstart: Homepage Enhancement

**Feature**: 004-homepage-enhancement
**Date**: 2026-01-26

## Prerequisites

- PHP 8.2+
- Node.js 18+
- Composer
- MySQL running on port 3306

## Setup

```bash
# 1. Switch to feature branch
git checkout 004-homepage-enhancement

# 2. Install dependencies (if not already)
composer install
npm install

# 3. Start development servers
php artisan serve
npm run dev
```

## Files to Create

| File | Purpose |
|------|---------|
| `app/Services/SubstackRssService.php` | RSS fetching and caching logic |
| `resources/js/Components/SocialLinks.vue` | Social media buttons component (URLs hardcoded) |
| `resources/js/Components/SubstackArticles.vue` | Article list component |

## Files to Modify

| File | Changes |
|------|---------|
| `app/Http/Controllers/HomeController.php` | Add substackArticles to props |
| `resources/js/Pages/Home.vue` | Add sidebar layout with new components |

## Implementation Order

1. **Service** - Create `SubstackRssService` with RSS fetch + cache
2. **Controller** - Update `HomeController` to pass substackArticles
3. **Components** - Create Vue components (SocialLinks with hardcoded URLs, SubstackArticles)
4. **Layout** - Update `Home.vue` with sidebar grid layout
5. **Test** - Verify on mobile and desktop viewports

## Testing

```bash
# Run tests
php artisan test

# Clear cache (if testing RSS refresh)
php artisan cache:clear

# View homepage
open http://localhost:8000
```

## Key URLs

- Homepage: http://localhost:8000
- RSS Feed (external): https://getwhealthy.substack.com/feed

## Verification Checklist

- [ ] 5 Substack articles display with titles and dates
- [ ] Article links open in new tab
- [ ] All 5 social media buttons visible
- [ ] Social links open in new tab
- [ ] Sidebar layout on desktop (≥1024px)
- [ ] Stacked layout on mobile (<1024px)
- [ ] Page loads within 2 seconds
- [ ] RSS failure doesn't break page (hide section)

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

## Files to Create / Rename

| File | Purpose |
|------|---------|
| `config/homepage.php` | Social links URLs + blog RSS settings |
| `app/Services/BlogRssService.php` | Generic RSS fetching and caching (renamed from SubstackRssService) |
| `resources/js/Components/SocialLinks.vue` | Social media buttons (URLs from props) |
| `resources/js/Components/SubstackArticles.vue` | Article list component |

> **Note**: `app/Services/SubstackRssService.php` → renamed to `BlogRssService.php`

## Files to Modify

| File | Changes |
|------|---------|
| `app/Http/Controllers/HomeController.php` | Read config, filter social links, pass `blogArticles` + `socialLinks` props |
| `resources/js/Pages/Home.vue` | Add sidebar layout; pass `socialLinks` prop to SocialLinks |

## Implementation Order

1. **Config** - Create `config/homepage.php` with social links + RSS settings
2. **Service** - Rename/rewrite `BlogRssService` (URL from config)
3. **Controller** - Update `HomeController` to pass `blogArticles` + `socialLinks`
4. **Components** - Create/update Vue components (SocialLinks accepts props, SubstackArticles unchanged)
5. **Layout** - Update `Home.vue` with sidebar grid layout
6. **Test** - Verify on mobile and desktop viewports

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

- [ ] 5 blog articles display with titles and dates
- [ ] Article links open in new tab
- [ ] Only configured (non-empty) social media buttons appear
- [ ] Removing a URL from config hides that platform button
- [ ] Social links open in new tab
- [ ] Sidebar layout on desktop (≥1024px)
- [ ] Stacked layout on mobile (<1024px)
- [ ] Page loads within 2 seconds
- [ ] RSS failure doesn't break page (hide section)
- [ ] Empty RSS URL in config hides articles section entirely

# Quickstart: Homepage Admin Settings

**Branch**: `007-homepage-admin-settings` | **Date**: 2026-03-25

---

## Setup

```bash
# Switch to feature branch
git checkout 007-homepage-admin-settings

# Run migrations (after creating them)
php artisan migrate

# Seed default homepage settings + social links
php artisan db:seed --class=HomepageSettingsSeeder

# Start dev servers
php artisan serve
npm run dev
```

---

## Implementation Order

1. **Migrations** — `site_settings` + `social_links`
2. **Models** — `SiteSetting` (get/getMany/set statics) + `SocialLink` (scopeOrdered)
3. **Seeder** — `HomepageSettingsSeeder` (current hardcoded defaults)
4. **BlogRssService** — rename from SubstackRssService, URL as parameter
5. **HomeController** — read from DB, rename props
6. **SocialLinks.vue** — accept `links` prop
7. **Home.vue** — hero unit redesign + new props
8. ✓ Smoke test: homepage renders from seeded data
9. **Routes** — 6 admin routes in `auth + admin` group
10. **Form Requests** — `UpdateHomepageSettingRequest` + `StoreSocialLinkRequest`
11. **HomepageSettingController** — edit + update (inline banner upload) + deleteBanner
12. **SocialLinkController** — store + update + destroy
13. **Admin/HomepageSettings/Edit.vue** — 3-section admin page
14. **AdminLayout.vue** — add "首頁設定" nav item
15. ✓ End-to-end test: admin page saves and homepage reflects changes
16. **Delete** `SubstackRssService.php`

---

## Manual Acceptance Test Checklist

### Hero Unit

- [ ] Visit `/admin/homepage` — page loads with all fields visible
- [ ] Upload a banner image ≥ 1200px wide → saves, preview shown
- [ ] Upload a banner image < 1200px wide → rejected with error message; existing banner unchanged
- [ ] Upload a non-image file (PDF/GIF) → rejected with error message
- [ ] Enter title + description + button label + URL → save → homepage hero shows all content
- [ ] Clear button label or URL → save → homepage hero has no EXPLORE button rendered
- [ ] Clear title → save → homepage hero has no heading element rendered
- [ ] Click "刪除橫幅圖片" → homepage hero shows solid-colour background; text preserved
- [ ] With no banner set → homepage hero shows solid-colour background, no broken image

### Hero Hover Effect

- [ ] Desktop: hover over hero area → image darkens, EXPLORE button brightens
- [ ] Mobile (< 640px): hero image scales correctly, text readable, button tappable

### SNS Links

- [ ] Click "+" → platform dropdown + URL field appear
- [ ] Select platform + enter URL → save → link appears at bottom of list on homepage
- [ ] Click "Edit" on a link → inline URL field + 儲存/取消 buttons appear
- [ ] Click 儲存 after URL change → homepage sidebar reflects new URL
- [ ] Click 取消 → no change made
- [ ] Click delete on a link + confirm → link removed from homepage
- [ ] SNS toggle OFF → entire social links section hidden on homepage
- [ ] SNS toggle ON with no links → social links section hidden on homepage

### Blog RSS

- [ ] Enter valid RSS URL → save → up to 5 recent articles shown on homepage
- [ ] Clear RSS URL → save → "近期文章" section hidden on homepage
- [ ] RSS URL set but feed unreachable → homepage loads; section hidden or shows cached articles; no error visible

### Responsiveness

- [ ] Homepage renders without layout errors at 320px, 768px, 1280px, 1920px wide
- [ ] Admin page usable on mobile

---

## Key Files

| File | Purpose |
|------|---------|
| `database/migrations/..._create_site_settings_table.php` | Key-value settings store |
| `database/migrations/..._create_social_links_table.php` | Social links store |
| `database/seeders/HomepageSettingsSeeder.php` | Default values from current hardcoded data |
| `app/Models/SiteSetting.php` | `get()`, `getMany()`, `set()` static helpers |
| `app/Models/SocialLink.php` | `scopeOrdered()` scope |
| `app/Services/BlogRssService.php` | RSS feed fetcher, URL as parameter |
| `app/Http/Controllers/HomeController.php` | Homepage — reads from DB |
| `app/Http/Controllers/Admin/HomepageSettingController.php` | Admin hero/RSS settings CRUD |
| `app/Http/Controllers/Admin/SocialLinkController.php` | Admin social links CRUD |
| `app/Http/Requests/Admin/UpdateHomepageSettingRequest.php` | Hero + RSS validation |
| `app/Http/Requests/Admin/StoreSocialLinkRequest.php` | Social link creation validation |
| `resources/js/Pages/Admin/HomepageSettings/Edit.vue` | Admin settings page |
| `resources/js/Pages/Home.vue` | Homepage — hero unit + new props |
| `resources/js/Components/SocialLinks.vue` | Social links display (accepts `links` prop) |
| `resources/js/Components/BlogArticles.vue` | RSS articles display (renamed from SubstackArticles) |
| `resources/js/Layouts/AdminLayout.vue` | Add "首頁設定" nav item |
| `routes/web.php` | Add 6 admin routes |

# Inertia Props Contract: Home Page

**Feature**: 004-homepage-enhancement
**Date**: 2026-01-26
**Updated**: 2026-02-27

## Overview

This feature uses Inertia.js for server-to-client data passing. Social media links are now read from `config/homepage.php` and passed as Inertia props (controller filters out empty URLs before passing).

## Home Page Props

### Current Props (unchanged)

```typescript
interface Course {
  id: number;
  name: string;
  tagline: string;
  price: number;
  thumbnail: string;
  instructor_name: string;
  type: string;
  status: string;
}
```

### Feature Props

```typescript
interface BlogArticle {
  title: string;
  url: string;
  published_at: string; // ISO 8601 format
}

interface SocialLink {
  platform: string; // 'instagram' | 'threads' | 'youtube' | 'facebook' | 'substack' | 'podcast'
  label: string;
  url: string;      // always non-empty (empty ones filtered by controller)
}
```

### Complete Home Page Props

```typescript
interface HomePageProps {
  // Existing
  courses: Course[];

  // 004-homepage-enhancement
  blogArticles: BlogArticle[];    // empty array if RSS unavailable or URL not configured
  socialLinks: SocialLink[];      // only platforms with non-empty URLs in config
}
```

## Controller Response

```php
// HomeController::index()
$socialLinks = collect(config('homepage.social_links'))
    ->filter(fn($url) => !empty($url))
    ->map(fn($url, $platform) => [
        'platform' => $platform,
        'label'    => ucfirst($platform),
        'url'      => $url,
    ])->values();

return Inertia::render('Home', [
    'courses'      => $courses,
    'blogArticles' => $this->blogRssService->getArticles(),
    'socialLinks'  => $socialLinks,
]);
```

## Example Payload

```json
{
  "courses": [...],
  "blogArticles": [
    {
      "title": "起點就是個廢物，沒什麼不好",
      "url": "https://getwhealthy.substack.com/p/no-goal-is-okay",
      "published_at": "2026-01-19T00:00:00Z"
    }
  ],
  "socialLinks": [
    { "platform": "instagram", "label": "Instagram", "url": "https://www.instagram.com/kyontw" },
    { "platform": "youtube",   "label": "Youtube",   "url": "https://www.youtube.com/@kyontw828" }
  ]
}
```

## Error States

| Scenario | Value | UI Behavior |
|----------|-------|-------------|
| RSS fetch success | Array of 1-5 articles | Show article list |
| RSS fetch failure | Empty array `[]` | Hide articles section entirely |
| RSS URL empty in config | Empty array `[]` | Hide articles section entirely |
| All social URLs empty in config | Empty array `[]` | Hide social links section entirely |
| Some social URLs empty | Filtered array | Only non-empty platforms shown |

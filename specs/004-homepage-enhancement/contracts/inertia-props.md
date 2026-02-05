# Inertia Props Contract: Home Page

**Feature**: 004-homepage-enhancement
**Date**: 2026-01-26

## Overview

This feature uses Inertia.js for server-to-client data passing. Social media links are hardcoded in the Vue component (no backend props needed).

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

### New Props (added)

```typescript
interface SubstackArticle {
  title: string;
  url: string;
  published_at: string; // ISO 8601 format
}
```

### Complete Home Page Props

```typescript
interface HomePageProps {
  // Existing
  courses: Course[];

  // New (004-homepage-enhancement)
  substackArticles: SubstackArticle[];
  // Note: socialLinks are hardcoded in SocialLinks.vue, not passed as props
}
```

## Controller Response

```php
// HomeController::index()
return Inertia::render('Home', [
    'courses' => $courses,
    'substackArticles' => $this->substackService->getArticles(),
]);
```

## Example Payload

```json
{
  "courses": [...],
  "substackArticles": [
    {
      "title": "起點就是個廢物，沒什麼不好",
      "url": "https://getwhealthy.substack.com/p/no-goal-is-okay",
      "published_at": "2026-01-19T00:00:00Z"
    },
    {
      "title": "政府應該要「穩定物價」嗎？",
      "url": "https://getwhealthy.substack.com/p/why-government-price-stabilization-fails-economics",
      "published_at": "2025-12-08T00:00:00Z"
    }
  ]
}
```

## Error States

| Scenario | substackArticles Value | UI Behavior |
|----------|------------------------|-------------|
| RSS fetch success | Array of 1-5 articles | Show article list |
| RSS fetch failure | Empty array `[]` | Hide section entirely |
| RSS returns empty | Empty array `[]` | Hide section entirely |

**Note**: Social links are hardcoded in `SocialLinks.vue` and always display (no failure state).

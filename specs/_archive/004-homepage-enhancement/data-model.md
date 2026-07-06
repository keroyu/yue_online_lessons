# Data Model: Homepage Enhancement

**Feature**: 004-homepage-enhancement
**Date**: 2026-01-26
**Updated**: 2026-02-27

## Overview

This feature does not require database changes. Data is either:
- Fetched from external RSS feed (configurable blog articles)
- Configured in `config/homepage.php` (social links + RSS settings)

## Entities

### BlogArticle (Runtime Object)

A transient object representing a blog post fetched from any RSS-compatible feed.

| Field         | Type     | Description                          | Source            |
|---------------|----------|--------------------------------------|-------------------|
| title         | string   | Article headline                     | RSS `<title>`     |
| url           | string   | Full URL to article                  | RSS `<link>`      |
| published_at  | datetime | Publication date                     | RSS `<pubDate>`   |

**Notes**:
- Not persisted to database
- Cached in Laravel Cache for TTL defined in config (default 1 hour)
- Maximum items defined in config (default 5)

### SocialLink (From Config, Passed as Props)

Configured in `config/homepage.php`; controller filters out empty URLs before passing to frontend.

| Field    | Type   | Description                          |
|----------|--------|--------------------------------------|
| platform | string | Platform identifier (instagram, etc) |
| label    | string | Display name for accessibility       |
| url      | string | Full URL to profile (empty = hidden) |
| icon     | SVG    | Inline SVG icon markup (in Vue)      |

**Config Structure** (`config/homepage.php`):
```php
return [
    'social_links' => [
        'instagram' => env('SOCIAL_INSTAGRAM', 'https://www.instagram.com/kyontw'),
        'threads'   => env('SOCIAL_THREADS', 'https://www.threads.com/@yueyuknows'),
        'youtube'   => env('SOCIAL_YOUTUBE', 'https://www.youtube.com/@kyontw828'),
        'facebook'  => env('SOCIAL_FACEBOOK', 'https://www.facebook.com/kyontw828'),
        'substack'  => env('SOCIAL_SUBSTACK', 'https://getwhealthy.substack.com/'),
        'podcast'   => env('SOCIAL_PODCAST', 'https://kyontw.firstory.io/'),
    ],
    'blog_rss' => [
        'url'       => env('BLOG_RSS_URL', 'https://getwhealthy.substack.com/feed'),
        'cache_ttl' => env('BLOG_RSS_CACHE_TTL', 3600),
        'max_items' => env('BLOG_RSS_MAX_ITEMS', 5),
    ],
];
```

## Relationships

```
┌──────────────────┐      ┌──────────────────┐
│  config/homepage │      │   Laravel Cache  │
└────────┬─────────┘      └────────┬─────────┘
         │ rss url                 │
         ▼                         │
┌──────────────────┐               │
│  BlogRssService  │───────────────┘
└────────┬─────────┘
         │ returns BlogArticle[]
         ▼
┌──────────────────────────────────┐
│         HomeController           │
│  - reads config social_links     │
│  - filters out empty URLs        │
│  - passes both to Inertia        │
└────────┬─────────────────────────┘
         │ Inertia props
         ▼
┌──────────────────────────────────┐
│  Home.vue                        │
│  SubstackArticles (blogArticles) │
│  SocialLinks (socialLinks props) │
└──────────────────────────────────┘
```

## Data Flow

1. **Page Request**: User visits homepage
2. **Controller**: HomeController reads `config/homepage.php`
3. **Social Links**: Filter `social_links` to only non-empty URLs → pass as `socialLinks` prop
4. **RSS**: Call `BlogRssService->getArticles()` with config RSS URL
5. **Cache Check**: Service checks Laravel Cache for blog article cache key
6. **Cache Hit**: Return cached articles (fast path)
7. **Cache Miss**: Fetch RSS, parse, cache per configured TTL, return
8. **Render**: Controller passes both `blogArticles` and `socialLinks` to Inertia
9. **Vue**: Home.vue renders `SubstackArticles` (articles prop) + `SocialLinks` (links prop)

## Validation Rules

### BlogArticle
- `title`: Required, non-empty string
- `url`: Required, valid URL format
- `published_at`: Required, valid date format

### SocialLink (config)
- `platform`: One of: instagram, threads, youtube, facebook, substack, podcast
- `url`: Optional; empty string means the platform is disabled and not passed to frontend

## Edge Cases

| Scenario | Handling |
|----------|----------|
| RSS feed unavailable | Return empty array, hide articles section |
| RSS returns 0 articles | Return empty array, hide articles section |
| Malformed RSS XML | Catch exception, return empty array |
| Cache failure | Fetch fresh, don't cache |
| Invalid date in RSS | Use current date as fallback |
| All social URLs empty in config | Pass empty array; SocialLinks section hidden |
| RSS URL empty/missing in config | Skip fetch, return empty array |

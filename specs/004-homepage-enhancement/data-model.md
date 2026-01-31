# Data Model: Homepage Enhancement

**Feature**: 004-homepage-enhancement
**Date**: 2026-01-26

## Overview

This feature does not require database changes. Data is either:
- Fetched from external RSS feed (Substack articles)
- Configured in application config (social links)

## Entities

### SubstackArticle (Runtime Object)

A transient object representing a Substack newsletter article fetched from RSS.

| Field         | Type     | Description                          | Source            |
|---------------|----------|--------------------------------------|-------------------|
| title         | string   | Article headline                     | RSS `<title>`     |
| url           | string   | Full URL to article on Substack      | RSS `<link>`      |
| published_at  | datetime | Publication date                     | RSS `<pubDate>`   |

**Notes**:
- Not persisted to database
- Cached in Laravel Cache for 1 hour
- Maximum 5 articles displayed

### SocialLink (Hardcoded in Vue)

Static data hardcoded directly in `SocialLinks.vue` component.

| Field    | Type   | Description                         |
|----------|--------|-------------------------------------|
| platform | string | Platform identifier (instagram, etc) |
| label    | string | Display name for accessibility       |
| url      | string | Full URL to profile                  |
| icon     | SVG    | Inline SVG icon markup               |

**Hardcoded Values** (in SocialLinks.vue):
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

## Relationships

```
┌─────────────────┐
│   HomeController │
└────────┬────────┘
         │ fetches
         ▼
┌─────────────────────┐      ┌─────────────────┐
│ SubstackRssService  │──────│  Laravel Cache  │
└─────────────────────┘      └─────────────────┘
         │ returns
         ▼
┌─────────────────┐
│ SubstackArticle[] │ (runtime objects, passed via Inertia)
└─────────────────┘

┌─────────────────┐
│ SocialLinks.vue │ ──► socialLinks[] (hardcoded in component)
└─────────────────┘
```

## Data Flow

1. **Page Request**: User visits homepage
2. **Controller**: HomeController calls SubstackRssService
3. **Cache Check**: Service checks Laravel Cache for `substack_articles`
4. **Cache Hit**: Return cached articles (fast path)
5. **Cache Miss**: Fetch RSS, parse, cache for 1 hour, return
6. **Render**: Controller passes articles to Inertia (social links are frontend-only)
7. **Vue**: Home.vue renders SubstackArticles (with props) + SocialLinks (hardcoded)

## Validation Rules

### SubstackArticle
- `title`: Required, non-empty string
- `url`: Required, valid URL format
- `published_at`: Required, valid date format

### SocialLink
- `platform`: Required, one of: instagram, threads, youtube, facebook, substack, podcast
- `url`: Required, valid URL format
- `label`: Required, non-empty string

## Edge Cases

| Scenario | Handling |
|----------|----------|
| RSS feed unavailable | Return empty array, hide section |
| RSS returns 0 articles | Return empty array, hide section |
| Malformed RSS XML | Catch exception, return empty array |
| Cache failure | Fetch fresh, don't cache |
| Invalid date in RSS | Use current date as fallback |

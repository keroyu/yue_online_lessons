# Research: Homepage Enhancement

**Feature**: 004-homepage-enhancement
**Date**: 2026-01-26

## Research Topics

### 1. RSS Feed Parsing in Laravel

**Decision**: Use `simplexml_load_string()` with Laravel HTTP client

**Rationale**:
- PHP's built-in SimpleXML is sufficient for standard RSS 2.0 feeds
- No additional dependencies required
- Laravel's HTTP client handles fetching with timeout/retry support
- Substack RSS feed is standard XML format (verified: https://getwhealthy.substack.com/feed)

**Alternatives Considered**:
- `spatie/laravel-feed` - Overkill for consumption; designed for generating feeds
- `willvincent/feeds` - Adds unnecessary dependency for simple use case
- `laminas/laminas-feed` - Heavy library for simple RSS parsing

**Implementation Pattern**:
```php
$response = Http::timeout(5)->get($feedUrl);
$xml = simplexml_load_string($response->body());
$articles = collect($xml->channel->item)->take(5);
```

### 2. Caching Strategy

**Decision**: Laravel Cache with 1-hour TTL, fallback to empty on failure

**Rationale**:
- Laravel Cache is already configured in the project
- File-based cache is sufficient for single-server deployment
- 1-hour refresh balances freshness vs. external API load
- Graceful degradation: show empty section rather than error

**Alternatives Considered**:
- Database storage - Adds migration complexity for ephemeral data
- Redis - Overkill for this use case; not currently in stack
- No caching - Would slow page loads and risk rate limiting

**Implementation Pattern**:
```php
return Cache::remember('substack_articles', 3600, function () {
    // fetch and parse RSS
});
```

### 3. Social Media Icons

**Decision**: Inline SVG icons with Tailwind styling

**Rationale**:
- No external dependencies or CDN requests
- Full control over colors and sizing
- Better performance than icon fonts
- Consistent with existing project patterns

**Alternatives Considered**:
- Font Awesome - Adds external dependency
- Heroicons - Doesn't have brand icons (Instagram, YouTube, etc.)
- External image files - Requires asset management

**Icon Sources**:
- Use official brand SVG icons from each platform's brand guidelines
- Instagram, YouTube, Facebook have public SVG assets
- Substack uses orange "S" mark
- Podcast (Firstory) uses standard podcast icon

### 4. Sidebar Layout with Tailwind

**Decision**: CSS Grid with responsive breakpoints

**Rationale**:
- CSS Grid provides clean two-column layout
- Tailwind's responsive prefixes handle mobile-first approach
- Grid collapses naturally to single column on mobile

**Implementation Pattern**:
```html
<!-- Mobile: stack, Desktop: sidebar -->
<div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-8">
  <main><!-- courses --></main>
  <aside><!-- social + articles --></aside>
</div>
```

### 5. Social Media URLs

**Decision**: Hardcode URLs directly in SocialLinks.vue component

**Rationale**:
- URLs are static and rarely change
- No backend complexity needed
- Simpler implementation
- Easier to maintain in one place

**Alternatives Considered**:
- Laravel config file - Adds unnecessary backend involvement
- Environment variables - Overkill for static public URLs
- Database settings - Way too complex for static data

## Resolved Clarifications

All technical unknowns resolved:
- ✅ RSS parsing approach: SimpleXML
- ✅ Caching strategy: Laravel Cache, 1 hour
- ✅ Icon approach: Inline SVGs
- ✅ Layout approach: CSS Grid
- ✅ Social URLs: Hardcoded in Vue component

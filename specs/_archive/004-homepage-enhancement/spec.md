# Feature Specification: Homepage Enhancement - Blog Articles & Social Links

**Feature Branch**: `004-homepage-enhancement`
**Created**: 2026-01-26
**Updated**: 2026-02-27
**Status**: Updated
**Input**: User description: "在首頁抓取 Substack 的近期文章標題和連結（類似RSS），做一個專欄 list，有助於講師形象推廣。要抓取的電子報網站在 https://getwhealthy.substack.com/。做一個各社群 SNS 的連結入口：Facebook, Instagram, YouTube, Substack, Podcast"
**Update 2026-02-27**: 社群媒體連結改為可設定（未填寫項目不顯示）；後端 RSS 服務改為通用 Blog RSS（不限 Substack）；以上設定集中在 config 檔案

## User Scenarios & Testing *(mandatory)*

### User Story 1 - View Recent Blog Articles (Priority: P1)

As a homepage visitor, I want to see the instructor's recent blog articles so I can learn more about their expertise and thought leadership before deciding to purchase courses.

**Why this priority**: The blog article feed is the core value proposition - it helps build trust and showcase the instructor's knowledge, directly supporting the business goal of promoting the instructor's image.

**Independent Test**: Can be fully tested by visiting the homepage and verifying that recent blog articles are displayed with clickable links that open in a new tab.

**Acceptance Scenarios**:

1. **Given** a visitor lands on the homepage, **When** the page loads, **Then** they see a section displaying the 5 most recent blog articles with titles and publication dates
2. **Given** the blog articles are displayed, **When** a visitor clicks on an article title, **Then** the full article opens in a new browser tab on the blog platform
3. **Given** the RSS feed is temporarily unavailable, **When** a visitor loads the homepage, **Then** the articles section displays gracefully (either cached content or a hidden section) without breaking the page

---

### User Story 2 - Access Social Media Links (Priority: P1)

As a homepage visitor, I want to quickly find and access the instructor's social media profiles so I can follow them on my preferred platforms.

**Why this priority**: Social media links provide immediate value by connecting visitors to the instructor's broader online presence, supporting multi-channel engagement and brand building.

**Independent Test**: Can be fully tested by visiting the homepage and clicking each social media icon to verify it opens the correct profile in a new tab.

**Acceptance Scenarios**:

1. **Given** a visitor is on the homepage, **When** they look for social media links, **Then** they see icons/buttons only for platforms that have been configured with a URL
2. **Given** the social links section is visible, **When** a visitor clicks any social media icon, **Then** the corresponding profile/page opens in a new browser tab
3. **Given** the visitor is on a mobile device, **When** they view the homepage, **Then** the social links are easily tappable and properly sized for touch interaction
4. **Given** a platform URL is left empty in the configuration, **When** a visitor views the homepage, **Then** that platform's button is not shown at all

---

### User Story 3 - Responsive Design (Priority: P2)

As a mobile user, I want the new homepage sections to display properly on my device so I can easily browse articles and find social links.

**Why this priority**: Mobile users represent a significant portion of visitors; ensuring responsive design maintains accessibility across all devices.

**Independent Test**: Can be fully tested by viewing the homepage on various screen sizes (mobile, tablet, desktop) and verifying proper layout adaptation.

**Acceptance Scenarios**:

1. **Given** a visitor is on a mobile device (< 640px width), **When** viewing the homepage, **Then** the sidebar collapses and social links/articles stack below the course grid
2. **Given** a visitor is on a tablet or desktop, **When** viewing the homepage, **Then** social links and Substack articles appear in a sidebar alongside the main course content
3. **Given** any screen size, **When** viewing social media icons, **Then** icons remain clickable and visually balanced

---

### Edge Cases

- What happens when the RSS feed returns no articles? Display a message or hide the section entirely.
- What happens when the RSS feed takes too long to load? Use cached data or timeout gracefully without blocking page render.
- What happens if a social media URL is invalid or the platform is down? Links should still function; error handling is on the external platform's side.
- What happens if the visitor has JavaScript disabled? Consider server-side rendering for the article list as a fallback.
- What happens if all social media URLs are left empty in config? Hide the social links section entirely.
- What happens if the blog RSS feed URL in config is left empty or invalid? Hide the articles section entirely without breaking the page.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST fetch and display the 5 most recent articles from a configurable blog RSS feed URL (stored in config)
- **FR-002**: System MUST display each article with its title and publication date
- **FR-003**: System MUST make article titles clickable links that open the full article in a new browser tab
- **FR-004**: System MUST display social media link icons only for platforms whose URLs are configured and non-empty
- **FR-005**: Social media icons MUST be visually styled as pill-shaped buttons with platform-appropriate icons
- **FR-006**: System MUST NOT display follower counts on social media links
- **FR-007**: All social media links MUST open in a new browser tab
- **FR-008**: Social media URLs MUST be stored in a config file (not hardcoded in the frontend component); frontend reads URLs passed from backend
- **FR-009**: System MUST cache RSS feed data to avoid fetching on every page load
- **FR-010**: System MUST handle RSS feed failures gracefully without breaking the homepage
- **FR-011**: Both new sections MUST be responsive and work on mobile, tablet, and desktop
- **FR-012**: System MUST display social links and blog articles in a sidebar layout on desktop/tablet, collapsing to stacked layout on mobile
- **FR-013**: If a social media platform URL is empty in config, its button MUST NOT be rendered on the homepage
- **FR-014**: If the blog RSS feed URL is empty or missing in config, the articles section MUST be hidden entirely

### Key Entities

- **Blog Article**: Represents a blog/newsletter post with title, publication date, and URL (fetched from a configurable RSS feed)
- **Social Link**: A platform entry with name, icon, and URL; read from config at runtime; not rendered if URL is empty

### Social Media Config (Stored in Config File)

Supported platforms (each URL is optional; leave empty to hide):

| Platform   | Default URL (current config)                 |
|------------|----------------------------------------------|
| Instagram  | https://www.instagram.com/kyontw             |
| Threads    | https://www.threads.com/@yueyuknows          |
| YouTube    | https://www.youtube.com/@kyontw828           |
| Facebook   | https://www.facebook.com/kyontw828           |
| Substack   | https://getwhealthy.substack.com/            |
| Podcast    | https://kyontw.firstory.io/                  |

### Blog RSS Config (Stored in Config File)

| Key          | Value                                          |
|--------------|------------------------------------------------|
| `rss_url`    | https://getwhealthy.substack.com/feed          |
| `cache_ttl`  | 3600 seconds (1 hour)                          |
| `max_items`  | 5                                              |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Homepage displays 5 recent blog articles within 2 seconds of page load
- **SC-002**: Only social media platforms with configured URLs are visible; platforms with empty URLs are not shown
- **SC-003**: 100% of article and social links open correctly in new browser tabs
- **SC-004**: Homepage renders correctly on screens from 320px to 1920px width
- **SC-005**: RSS feed failures do not cause visible errors or broken layouts for visitors
- **SC-006**: Cached article data refreshes at least once per hour to show recent content

## Clarifications

### Session 2026-01-26

- Q: Section placement on homepage? → A: Sidebar layout (social + articles on the side, courses main)
- Q: Social media URLs? → A: Provided (Instagram, YouTube, Facebook, Substack, Podcast)
- Q: Social URLs configuration? → A: Hardcoded in Vue component (no backend needed)

### Session 2026-02-27

- Q: Social URLs configuration change? → A: Move from hardcoded frontend to config file; empty URL = hide that platform button
- Q: RSS service scope? → A: Backend service renamed and rewritten to be generic (any RSS-compatible blog URL), not Substack-specific
- Q: Config location? → A: Laravel config file (e.g., `config/homepage.php`); both RSS settings and social links stored there

## Assumptions

- Social media URLs are stored in a Laravel config file and passed to the frontend via Inertia props
- The blog RSS feed URL is configurable; current value points to the Substack feed but any valid RSS URL is supported
- A platform with an empty URL string in config is treated as "disabled" and its button is not rendered
- The existing homepage layout (Hero section → Course grid) will be preserved, with social links and blog articles displayed in a sidebar alongside the main course content
- Social media icons will use recognizable platform icons following each platform's brand guidelines
- The "Podcast" link refers to an external podcast platform rather than embedded audio
- Frontend component for articles remains unchanged (SubstackArticles.vue name may be kept or renamed; behaviour unchanged)
- The set of supported social platforms (Instagram, Threads, YouTube, Facebook, Substack, Podcast) remains the same; only their URLs are now configurable

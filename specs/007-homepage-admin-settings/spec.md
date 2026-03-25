# Feature Specification: Homepage Admin Settings - Hero Unit & Social Links Management

**Feature Branch**: `007-homepage-admin-settings`
**Created**: 2026-03-25
**Status**: Draft
**Input**: User description: "對 004-homepage-enhancement 進行增量更新：管理後台新增首頁設定頁，可管理 Hero Unit（橫幅圖片、標題、說明、按鈕）、SNS 連結（新增/修改/排序）、Blog RSS 網址"
**Depends On**: 004-homepage-enhancement (SNS links and RSS URL currently hardcoded; not yet migrated to config)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Edit Hero Unit via Admin (Priority: P1)

As the site administrator, I want to manage the homepage hero section through a settings page so I can update the banner image, headline, and call-to-action without touching code.

**Why this priority**: The hero unit is the first thing visitors see. Being able to update it independently — with a custom image, title, description, and link — is the highest-value content management capability. It directly supports marketing campaigns and brand refresh.

**Independent Test**: Can be fully tested by visiting `/admin/homepage`, filling in hero fields, uploading an image, saving, then visiting the homepage to verify the new content appears correctly.

**Acceptance Scenarios**:

1. **Given** an admin visits the homepage settings page, **When** they upload a banner image (≥ 1200px wide), enter a title and description, fill in a button label and URL, then click Save, **Then** the homepage hero section reflects all the new content on next load.
1a. **Given** an admin uploads an image narrower than 1200px, **When** they attempt to save, **Then** the upload is rejected with a message stating the minimum required width, and the existing banner is unchanged.
2. **Given** a banner image has been uploaded, **When** the admin clicks "刪除橫幅圖片", **Then** the homepage hero falls back to a solid-colour background while preserving the title and description.
3. **Given** no banner image is set, **When** any visitor views the homepage, **Then** the hero section still displays the title and description against a plain background — no broken image or layout error.
4. **Given** the hero button label or URL is left empty, **When** a visitor views the homepage, **Then** the EXPLORE button is not rendered at all.
5. **Given** the admin clears the hero title, **When** a visitor views the homepage, **Then** no empty heading element is rendered — blank fields are simply omitted.

---

### User Story 2 - Manage Social (SNS) Links via Admin (Priority: P1)

As the site administrator, I want to add, edit, and delete social media links through a settings page and control whether the entire SNS section is visible on the homepage, without touching code.

**Why this priority**: Social links appear on every page load and are a key channel-discovery tool for visitors. Being able to manage them without code changes is essential for operational agility.

**Independent Test**: Can be fully tested by opening `/admin/homepage`, clicking "+" to add a new link, selecting a platform and entering a URL, saving, then visiting the homepage to confirm the link appears in the sidebar.

**Acceptance Scenarios**:

1. **Given** an admin clicks the "+" button, **When** they select a platform from the dropdown and enter a URL then save, **Then** the new link appears on the homepage sidebar in the position it was added (after existing links).
2. **Given** an existing link in the list, **When** the admin clicks "Edit", updates the URL, and clicks "儲存", **Then** the homepage sidebar reflects the new URL.
3. **Given** an admin clicks "Edit" on a link, **When** they click "取消", **Then** the original URL is preserved and no change is made.
4. **Given** an existing link, **When** the admin clicks delete and confirms, **Then** the link is permanently removed and no longer shown on the homepage.
5. **Given** the SNS global toggle is set to "不顯示", **When** a visitor views the homepage, **Then** the entire social links section is hidden — no card or empty space is visible.
6. **Given** no links have been added, **When** a visitor views the homepage, **Then** the social links section is hidden entirely.

---

### User Story 3 - Configure Blog RSS URL via Admin (Priority: P2)

As the site administrator, I want to configure the blog RSS feed URL through a settings page so that the "近期文章" section can be pointed at any blog without code changes.

**Why this priority**: The RSS URL changes less frequently than hero content or social links, but still requires operational flexibility. Clearing the URL should cleanly hide the section.

**Independent Test**: Can be fully tested by entering a valid RSS URL and verifying recent articles appear on the homepage, then clearing the URL and verifying the section disappears.

**Acceptance Scenarios**:

1. **Given** an admin enters a valid blog RSS URL and saves, **When** a visitor loads the homepage, **Then** up to 5 recent articles with titles and dates are shown in the sidebar.
2. **Given** the RSS URL field is cleared and saved, **When** a visitor views the homepage, **Then** the "近期文章" section is completely hidden.
3. **Given** a valid RSS URL is set but the feed is temporarily unavailable, **When** a visitor loads the homepage, **Then** cached articles (up to 1 hour old) are shown, or the section is hidden gracefully — the page never shows an error message.

---

### User Story 4 - View Homepage with New Hero Design (Priority: P2)

As a homepage visitor, I want to see a visually engaging hero section with a banner image, headline, description, and a clear call-to-action button so I immediately understand the site's value proposition.

**Why this priority**: Depends on US1 (admin must configure the hero first), but delivers the visitor-facing value. The hover interaction (image darkens, button brightens) enhances visual polish and click-through rate.

**Independent Test**: Can be fully tested by visiting the homepage with a banner configured and verifying layout, text position, button visibility, and hover behaviour at desktop and mobile widths.

**Acceptance Scenarios**:

1. **Given** a banner image and full hero content are configured, **When** a visitor loads the homepage on desktop, **Then** they see the image at full width with title and description left-aligned at the bottom, and the button at the bottom-right. The title is displayed as white text on a solid black background strip; the description is white text with a drop shadow, clearly legible against any banner image.
2. **Given** the hero has a banner image, **When** a visitor moves their cursor over the hero area, **Then** the image visibly darkens and the button becomes more prominent.
3. **Given** a mobile-sized screen (under 640px wide), **When** a visitor views the homepage, **Then** the hero image scales correctly, text remains readable, and the button remains tappable.

---

### Edge Cases

- What happens when an uploaded image is in an unsupported format (e.g., GIF or PDF)? The system must reject it with a clear validation message and leave the current banner unchanged.
- What happens when an uploaded image is under 1200px wide? The system must reject it with a message stating the minimum width requirement (1200px); the existing banner remains in place.
- What happens when an RSS URL is syntactically valid but returns a malformed or empty feed? The section falls back to cached articles or is hidden — no error is exposed to visitors.
- What happens if the SNS global toggle is on but no links have been added? The social links section is hidden — same result as toggling it off.
- What happens if the admin tries to add a second link for the same platform (e.g., two Instagram entries)? This is allowed — the platform dropdown is a type selector for icon display only; duplicate platforms are permitted.
- What happens if a social link URL is entered without a protocol (e.g., `instagram.com` instead of `https://instagram.com`)? The system shows a validation error to the admin before saving.
- What happens when the admin saves a very long hero description? The homepage layout must not overflow — text wraps or the container adapts.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The admin interface MUST provide a dedicated "首頁設定" page accessible only to users with the administrator role.
- **FR-002**: The admin MUST be able to upload a horizontal banner image; accepted formats are JPG, PNG, and WebP; maximum file size is 5 MB; minimum width is 1200px — images narrower than 1200px MUST be rejected with a clear validation message.
- **FR-003**: The settings page MUST display a preview of the current banner image if one has been set.
- **FR-004**: The admin MUST be able to delete the current banner image; doing so reverts the homepage hero to a solid-colour background without removing the text content.
- **FR-005**: The hero settings MUST include fields for: title (max 255 characters), description (multi-line, max 2000 characters), button label (max 100 characters), and button URL.
- **FR-006**: The homepage hero MUST NOT display the call-to-action button if either the button label or button URL is empty.
- **FR-007**: The homepage hero MUST NOT render an empty heading or description element when those fields are blank in settings.
- **FR-008**: The admin MUST be able to add a social link via a "+" button that reveals a form with: a platform dropdown (Instagram, Threads, YouTube, Facebook, Substack, Podcast) and a URL field; submitting adds the link to the bottom of the list.
- **FR-009**: Each existing link MUST display an "Edit" button; clicking it expands that row inline with a URL field and "儲存" / "取消" buttons; other rows remain collapsed.
- **FR-010**: The admin MUST be able to delete any link permanently; a confirmation prompt MUST appear before deletion.
- **FR-011**: The admin MUST be able to toggle a global "顯示 SNS 區塊" switch that controls whether the entire social links section appears on the homepage, independent of which links exist.
- **FR-012**: Social links MUST be displayed on the homepage in the order they were added (creation order); there is no drag-to-reorder.
- **FR-013**: If the global SNS toggle is off, OR if no links have been added, the social links section MUST be hidden entirely from the homepage.
- **FR-014**: The admin MUST be able to configure a blog RSS feed URL in the settings page.
- **FR-015**: If the blog RSS URL is empty or cleared and saved, the "近期文章" section MUST be hidden from the homepage.
- **FR-016**: The homepage hero MUST display: banner image (if set) with title and description left-aligned at the bottom, and a call-to-action button at the bottom-right (if configured). The title MUST appear as white text on a solid black background strip. The description MUST appear as white text with a drop shadow to ensure legibility against any banner image.
- **FR-017**: On hover over the hero area, the banner image MUST visibly darken and the call-to-action button MUST become visually brighter or more prominent.
- **FR-018**: All homepage sections (hero, social links, articles) MUST be responsive across screen widths from 320px to 1920px.
- **FR-019**: All settings (hero content, social links, RSS URL) MUST be persisted; changes MUST survive server restarts.
- **FR-020**: Admin navigation MUST include a "首頁設定" entry that links to the homepage settings page.

### Key Entities

- **Site Setting**: A named configuration value (e.g., hero title, banner image path, blog RSS URL) persisted in a structured store and read by the homepage on every load.
- **Social Link**: An entry created by the admin, consisting of a platform type (chosen from a predefined list) and a URL; displayed as an icon-labelled button in the homepage sidebar in creation order, when the global SNS toggle is on.
- **Hero Unit**: The full-width visual banner at the top of the homepage, composed of a banner image, title, description, and optional call-to-action button.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An administrator can update the homepage hero content (image + text + button) and see the change reflected on the live homepage within 30 seconds of saving.
- **SC-002**: An administrator can add, reorder, disable, or delete a social link and see the homepage sidebar update within 30 seconds of the action.
- **SC-003**: 100% of configured (enabled) social links open in a new browser tab with the correct destination URL; disabled links are never shown.
- **SC-004**: The homepage hero section renders without layout errors on screens from 320px to 1920px wide, both with and without a banner image configured.
- **SC-005**: When the blog RSS feed is unavailable, the homepage finishes loading within 3 seconds and shows either cached articles or a hidden section — never an error state visible to visitors.
- **SC-006**: An administrator who has never used the settings page can locate "首頁設定" in the navigation and successfully update at least one setting without external guidance.

## Clarifications

### Session 2026-03-25

- Q: Should the call-to-action button label be fixed as "EXPLORE" or configurable? → A: Configurable (admin sets the label; "EXPLORE" is merely the suggested default, not hardcoded).
- Q: Can the admin add multiple links for the same social platform (e.g., two Instagram accounts)? → A: Yes — the platform field is a type selector for icon display purposes; multiple entries per platform are allowed.
- Q: When the RSS cache expires, is the feed refreshed in the background or on the next homepage load? → A: On the next homepage load (synchronous fetch with fallback to stale cache on error), consistent with existing behaviour.
- Q: Should the system enforce minimum image dimensions for the banner upload? → A: Yes — enforce minimum width of 1200px; images narrower than 1200px are rejected with a clear validation message; existing banner is unchanged.
- Q: How should social links be edited, and what controls the SNS section visibility? → A: Dynamic list — admin adds links via "+" button (platform dropdown + URL); each row has inline Edit/Delete; display order = creation order (no drag-to-reorder); a global "顯示 SNS 區塊" toggle controls whether the entire section appears on the homepage.
- Q: What determines the display order of social links on the homepage? → A: Creation order (the order links were added by the admin); no drag-to-reorder.

## Assumptions

- The "首頁設定" page is restricted to users with the `admin` role (same access control as all other admin pages).
- Uploaded banner images are stored on the server's file system and served via the existing public storage mechanism; no external CDN is required.
- The set of available platform types in the dropdown is fixed (Instagram, Threads, YouTube, Facebook, Substack, Podcast); adding a new platform type to the dropdown requires a developer change.
- Social links are displayed in creation order; there is no drag-to-reorder.
- The same platform type may be added more than once (e.g., two Instagram accounts); the platform dropdown is for icon selection only.
- The existing "近期文章" sidebar component retains its behaviour; only its data source changes from a hardcoded URL to the admin-configured value.
- The hero hover effect (image darkens, button brightens) is a visual enhancement only and does not affect functionality on touch devices.
- All external links (social links, call-to-action button) open in a new browser tab.
- RSS feed content is cached for up to 1 hour; changing the RSS URL in the admin settings triggers an immediate cache invalidation so the new feed is fetched on the next page load.

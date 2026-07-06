# Research: 客製服務預約系統

**Phase**: 0 — Research
**Date**: 2026-04-08
**Branch**: `008-high-ticket-booking`

---

## 1. Course Type System

**Decision**: Add `'high_ticket'` to the existing `courses.type` MySQL ENUM `['lecture', 'mini', 'full']`.

**Findings**:
- `courses.type` is defined in `2026_01_16_000001_create_courses_table.php:22` as `$table->enum('type', ['lecture', 'mini', 'full'])`.
- Note: there are **two separate type columns**:
  - `courses.type` — product category shown in frontend ("講座 / 迷你課 / 完整課程"). Maps to `form.type` in CourseForm; referenced as `product_type` in admin controller props.
  - `courses.course_type` — delivery mode ('standard' / 'drip'), controls drip email logic.
- `CourseForm.vue:116–119` defines `courseTypes` array; needs `high_ticket` added.
- `Course/Show.vue:67–73` defines `getTypeLabel()` mapping; needs `high_ticket → '客製服務'`.
- `Admin\CourseController:146` passes `product_type: $course->type` to frontend.

**New high_ticket-specific fields needed on `courses` table**:
- `high_ticket_hide_price` (boolean, default false) — when true, replaces price block with info text
- `)

**Rationale**: Extending the existing enum is the minimal change. The two additional columns follow the same pattern as `is_visible` (boolean added via migration).

**MySQL enum migration pattern**: Use `DB::statement("ALTER TABLE courses MODIFY COLUMN type ENUM(...)")` — standard approach for this project.

---

## 2. Sales Page — Price Block & Button

**Decision**: Conditionally branch inside `Course/Show.vue` based on `course.is_high_ticket` (computed from `type === 'high_ticket'`) and `course.high_ticket_hide_price`.

**Findings**:
- `PriceDisplay` is used in **two** places in `Show.vue`:
  1. Top info row (line 439): quick view above the fold
  2. Bottom purchase section (line 603): main purchase CTA area (the red-boxed area in the spec image)
- The bottom section is the primary target for the high_ticket info text replacement.
- The top section should also be hidden or replaced for high_ticket+hide_price mode.
- Main buy button is at line 742: `{{ isFree ? '免費報名' : (payuniSubmitting ? '處理中...' : '立即購買') }}` — needs to become `'立即預約'` for high_ticket mode.
- Floating panel (line 777) also uses `PriceDisplay` — needs to handle high_ticket mode.

**Rationale**: Modifying `Show.vue` directly (no new component) — the logic is localized and adding conditional blocks follows existing pattern (e.g., `v-if="isDrip"`).

---

## 3. Email Template System

**Decision**: Create a dedicated `email_templates` table with Markdown body + variable substitution via `str_replace()`.

**Findings**:
- `BatchEmailMail.php` already uses `League\CommonMark\CommonMarkConverter` for Markdown → HTML.
- `resources/views/emails/batch-email.blade.php` is a minimal wrapper: `{!! $htmlBody !!}`.
- We reuse the same Mailable pattern for `HighTicketBookingMail`.
- Variable syntax: `{{variable_name}}` (double curly braces — safe since this is admin-authored template content, not Vue template).
- Supported variables: `{{user_name}}`, `{{user_email}}`, `{{user_phone}}`, `{{course_name}}`, `{{booking_date}}`, `{{booking_id}}`.
- Variable replacement: `str_replace(['{{user_name}}', ...], [$name, ...], $template->body_md)` before CommonMark conversion.

**Email delivery**: **Sync** (Mail::to()->send()) — user is waiting for confirmation, maps to constitution §VI sync rule.

**Template event types** (initial set): `high_ticket_booking_confirmation`.

**Rationale**: Reusing the BatchEmailMail pattern with added variable substitution is minimal and consistent with existing code.

---

## 4. Admin Template Editor

**Decision**: Markdown textarea + variable insert buttons + reuse existing `ImageGalleryModal` component.

**Findings**:
- `ImageGalleryModal` (`resources/js/Components/Admin/ImageGalleryModal.vue`) is already built and handles multi-select from album.
- Images are stored at `storage/app/public` and served via `/storage/...` paths.
- The editor will use a plain `<textarea>` (same as `description_md` field in `CourseForm.vue:617`) — no new rich text library needed.
- Variable insert: click button → insert `{{variable_name}}` at textarea cursor position via `selectionStart`/`selectionEnd`.
- Image insert: open gallery modal → select image → insert `![alt](url)` at cursor.
- Preview: render Markdown via `marked` (already in package.json, used in `Course/Show.vue:4`).

**Rationale**: Reuses existing patterns (marked for preview, gallery modal for images, textarea for editing).

---

## 5. Workshop Booking Flow

**Decision**: New `HighTicketBookingController` (public) + `HighTicketBookingService` + `HighTicketBooking` model.

**Findings**:
- Booking does NOT go through the `purchases` table (confirmed in spec assumptions).
- Controller handles: display booking form (Inertia render), submit booking (POST → Service).
- Service: creates `HighTicketBooking` record, sends confirmation email sync.
- No Job needed — sync email per constitution §VI (user waiting for result).
- Form fields mirror PayUni form: email, name, phone (all required).
- Booking status transitions: `pending` → `confirmed` / `cancelled` (manual by admin).

**Routing**:
- `POST /course/{course}/book` → `HighTicketBookingController@store` (public, no auth required)
- `
- `
- `GET/POST/PUT/DELETE /admin/email-templates/*` → `Admin\EmailTemplateController`

---

## 6. Constitution Compliance Verification

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Controller Layering | ✅ | Booking has side-effects (email) → Service required |
| II. Service Layer | ✅ | `HighTicketBookingService` handles booking + email |
| III. Frontend Architecture | ✅ | Composition API, local state, Inertia router |
| IV. Model Conventions | ✅ | `$fillable`, `casts()`, scopes follow pattern |
| V. Job Discipline | ✅ | No Job — sync email (user waiting, per §VI) |
| VI. Email Delivery | ✅ | Sync send — user waiting for confirmation |
| VII. Error Handling | ✅ | Service returns `['success' => bool, 'error' => '...']` |
| VIII. Authorization | ✅ | Admin routes: `auth` + `admin` middleware |
| IX. Security | ✅ | No sensitive data in code, standard validation |
| X. YAGNI | ✅ | No premature abstractions — reuse existing patterns |

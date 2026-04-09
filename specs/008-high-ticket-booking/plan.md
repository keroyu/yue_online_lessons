# Implementation Plan: 客製服務預約系統

**Branch**: `008-high-ticket-booking` | **Date**: 2026-04-08 | **Spec**: [spec.md](spec.md)
**Updated**: 2026-04-09 - 實作完成；預約改 JSON API（axios）、說明文案更新、浮動面板文字修正、migration 相容舊版 MySQL
**Input**: Feature specification from `/specs/008-high-ticket-booking/spec.md`

---

## Summary

新增「客製服務」課程類別，支援隱藏價格、顯示說明文字、以「立即預約」取代「立即購買」按鈕；訪客提交預約表單後系統同步發送確認 Email；後台提供 Email 模板 CRUD（Markdown 編輯 + 變數插入）。技術上：擴充 `courses.type` ENUM、新增 `email_templates` 一張表，遵循現有 `BatchEmailMail` + CommonMark 模式。

---

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12
**Primary Dependencies**: Inertia.js v2, Vue 3 (`<script setup>`), Tailwind CSS v4, `league/commonmark` (existing), `marked` (existing)
**Storage**: MySQL — alter `courses.type` enum, two new tables
**Testing**: `php artisan test` (PHPUnit)
**Target Platform**: Web server (Linux via Laravel Forge)
**Project Type**: Web application
**Performance Goals**: Booking confirmation email delivered in < 60 seconds
**Constraints**: Sync email send (user waiting); no new npm/composer packages
**Scale/Scope**: Low volume (high-price, high-ticket services, individual bookings)

---

## Constitution Check

| Principle | Gate | Status |
|-----------|------|--------|
| I. Controller Layering | Booking has email side-effect → Service required | ✅ `HighTicketBookingService` created |
| II. Service Layer | Cross-model + external I/O | ✅ Service handles booking creation + email |
| III. Frontend Architecture | Composition API, local state only | ✅ No Pinia/Vuex introduced |
| IV. Model Conventions | `$fillable`, `casts()`, scopes | ✅ Models follow existing patterns |
| V. Job Discipline | Email is sync (user waiting) → no Job needed | ✅ No Job, per §VI rule |
| VI. Email Delivery | Sync: user waiting for confirmation | ✅ `Mail::to()->send()` |
| VII. Error Handling | Service returns `['success' => bool]` | ✅ Controller handles error display |
| VIII. Authorization | Public booking endpoint; admin under `auth+admin` | ✅ Matches existing auth pattern |
| IX. Security | No secrets in code; standard validation | ✅ Rate limit on public booking route |
| X. YAGNI | No new packages; reuses existing components | ✅ Minimal scope |

**Constitution Check Result**: PASS — no violations.

---

## Project Structure

### Documentation (this feature)

```text
specs/008-high-ticket-booking/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── api.md           # API contracts
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── HighTicketBookingController.php     [NEW] public booking
│   │   └── Admin/
│   │       ├── CourseController.php           [MODIFY] add high_ticket fields
│   │       └── EmailTemplateController.php    [NEW] email template CRUD
│   └── Requests/
│       └── Admin/
│           └── EmailTemplateRequest.php       [NEW] validation
├── Models/
│   ├── Course.php                             [MODIFY] +fillable, cast, accessor
│   └── EmailTemplate.php                     [NEW]
├── Services/
│   └── HighTicketBookingService.php            [NEW]
└── Mail/
    └── HighTicketBookingMail.php               [NEW]

database/migrations/
├── 2026_04_09_000001_add_high_ticket_fields_to_courses_table.php  [NEW]
└── 2026_04_09_000002_create_email_templates_table.php             [NEW]

database/seeders/
└── EmailTemplateSeeder.php                   [NEW] 3 default templates

resources/
├── views/emails/
│   └── high-ticket-booking.blade.php           [NEW]
└── js/
    ├── Components/Admin/
    │   └── CourseForm.vue                    [MODIFY] add high_ticket fields
    └── Pages/
        ├── Course/
        │   └── Show.vue                      [MODIFY] high_ticket UI branches
        └── Admin/
            └── EmailTemplates/
                ├── Index.vue                 [NEW]
                └── Edit.vue                  [NEW] (create + edit combined)

routes/web.php                               [MODIFY] +booking route, +admin routes
```

---

## Phase 0: Research Summary

See [research.md](research.md) for full findings. Key decisions:

1. **`courses.type` enum**: Add `'high_ticket'` via `DB::statement()` migration.
2. **New column**: `high_ticket_hide_price` (boolean) on `courses`. Button is always "立即預約" when hide_price=true.
3. **Email**: Sync send using `Mail::to()->send()` — reuses `BatchEmailMail` / CommonMark pattern.
4. **Template editor**: Plain `<textarea>` + cursor-insert for variables + reuse `ImageGalleryModal`.
5. **No new packages**: `marked` and `league/commonmark` already installed.

---

## Phase 1: Design

### Data Model

See [data-model.md](data-model.md) for full schema.

**Summary**:
- `courses` table: enum altered + 1 new column (`high_ticket_hide_price`)
- `email_templates` table: new (name, event_type, subject, body_md, timestamps)

### API Contracts

See [contracts/api.md](contracts/api.md) for full contracts.

**Key routes**:
- `POST /course/{course}/book` — public, throttled
- `GET|POST|PUT|DELETE /admin/email-templates/...` — admin
- `PUT /admin/courses/{course}` extended with high_ticket fields

### Implementation Details

#### Backend

**HighTicketBookingService**:
```php
// App\Services\HighTicketBookingService
public function book(Course $course, array $data): array
{
    // 1. Validate course is high_ticket type with hide_price=true
    // 2. Find email template for 'high_ticket_booking_confirmation'
    // 3. Render subject + body with vars (user_name, user_email, course_name)
    // 4. Mail::to($data['email'])->send(new HighTicketBookingMail(...))
    // 5. Return ['success' => true] (no DB record created — FR-011)
}
```

**HighTicketBookingMail** (follows BatchEmailMail pattern):
```php
// Constructor receives: emailSubject (string), emailBody (string — Markdown)
// Converts Markdown → HTML in constructor via CommonMarkConverter
// envelope(): subject from $this->emailSubject
// content(): view: 'emails.high-ticket-booking'
// Blade: {!! $htmlBody !!}  (same as batch-email.blade.php)
```

**EmailTemplate::renderBody(array $vars)** on model:
```php
$body = str_replace(array_keys($vars), array_values($vars), $this->body_md);
$converter = new CommonMarkConverter();
return $converter->convert($body)->getContent();
```

**Admin\CourseController** — extend `store()` / `update()` validation:
```php
'high_ticket_hide_price' => 'boolean',
```

#### Frontend

**CourseForm.vue** changes:
1. Add `{ value: 'high_ticket', label: '客製服務' }` to `courseTypes` array (line 116–119).
2. Add `high_ticket_hide_price: props.course?.high_ticket_hide_price ?? false` to `useForm()`.
3. Add high_ticket config section (shown only when `form.type === 'high_ticket'`):
   - Toggle: 隱藏原價/優惠價 (checkbox → `high_ticket_hide_price`)

**Course/Show.vue** changes:
1. Add computed `isHighTicket` = `course.type === 'high_ticket'`.
2. Add computed `highTicketHidePrice` = `course.high_ticket_hide_price`.
3. In top info row (PriceDisplay area, line ~439): wrap `PriceDisplay` with `v-if="!isHighTicket || !highTicketHidePrice"`.
4. In bottom purchase section (line ~601), replace the `<!-- Price Block -->` div:
   ```html
   <!-- Workshop info block (replaces price) -->
   <div v-if="isHighTicket && highTicketHidePrice" class="py-1 max-w-xs text-sm text-gray-600 leading-relaxed">
     此為高價工作坊，請預約 1v1 面談了解，預約後必須立即收取 Email 完成任務，才是正式完成預約。
   </div>
   <div v-else class="py-1">
     <PriceDisplay ... />
   </div>
   ```
5. Workshop booking form: replace the `<!-- Consent & Purchase Button -->` section when `isHighTicket && !hasPurchased`:
   - Show booking form inline (name, email, phone fields + submit button)
   - Submit via `router.post(\`/course/${course.id}/book\`, { name, email, phone })`
   - Show success state from flash `high_ticket_booking_success`
6. Update buy button label: `isHighTicket ? '立即預約' : (isFree ? '免費報名' : '立即購買')`.
7. Floating panel: add `v-if` condition to hide when `isHighTicket && highTicketHidePrice`.

**Admin/EmailTemplates/Edit.vue** — key interactions:
- `<textarea>` bound to `form.body_md` (same as CourseForm description_md pattern)
- Variable insert: `insertAtCursor(variableKey)` — uses `textarea.selectionStart`/`selectionEnd`
- Image insert: open `ImageGalleryModal` → on select, insert `![圖片](imageUrl)` at cursor
- Preview panel: rendered via `computed(() => marked(form.body_md))`

---

## Complexity Tracking

No constitution violations — no complexity justification required.

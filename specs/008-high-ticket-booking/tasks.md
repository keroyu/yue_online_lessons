# Tasks: 客製服務預約系統

**Branch**: `008-high-ticket-booking`
**Input**: `specs/008-high-ticket-booking/` (spec.md, plan.md, data-model.md, contracts/, quickstart.md)
**Tests**: Not requested — no test tasks generated.
**Updated**: 2026-04-09 - 所有任務完成（Phase 7：post-implementation fixes — 非同步預約、UX 優化、MySQL 相容修正）

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: User story label (US1–US4 maps to spec.md stories)

---

## Phase 1: Foundational (Blocking Prerequisites)

**Purpose**: Database schema + core model changes that ALL user stories depend on.

**⚠️ CRITICAL**: All user story phases are blocked until this phase is complete.

- [X] T001 Migration: alter `courses.type` ENUM to add `'high_ticket'` and add `high_ticket_hide_price` boolean column in `database/migrations/2026_04_09_000001_add_high_ticket_fields_to_courses_table.php`
- [X] T002 [P] Migration: create `email_templates` table (name, event_type, subject, body_md, timestamps) in `database/migrations/2026_04_09_000002_create_email_templates_table.php`
- [X] T003 Update `Course` model: add `workshop_hide_price` → `high_ticket_hide_price` to `$fillable`, add `'high_ticket_hide_price' => 'boolean'` to `casts()`, add `isHighTicket` accessor — `app/Models/Course.php`
- [X] T004 [P] Create `EmailTemplate` model: `$fillable` (includes `body_md`), `scopeForEvent()`, `renderSubject(array $vars)` (str_replace into subject), `renderBody(array $vars)` (str_replace into `body_md`, then convert via `CommonMarkConverter` — same pattern as `BatchEmailMail`) — `app/Models/EmailTemplate.php`

**Checkpoint**: Run `php artisan migrate` — both migrations pass. Course model has `is_high_ticket` accessor. EmailTemplate model resolves variables.

---

## Phase 2: User Story 1 — 管理員設定客製服務課程類別 (Priority: P1) 🎯 MVP

**Goal**: Admin can set a course to type `high_ticket` with a hide-price toggle. Saved correctly to DB.

**Independent Test**: Create a new course in admin → select type "客製服務" → enable "隱藏價格" toggle → save → confirm DB has `type='high_ticket'` and `high_ticket_hide_price=1`.

- [X] T005 [P] [US1] Update `StoreCourseRequest`: add `'high_ticket'` to `type` enum validation rule — `app/Http/Requests/Admin/StoreCourseRequest.php`
- [X] T006 [P] [US1] Update `UpdateCourseRequest`: add `'high_ticket'` to `type` enum; add `'high_ticket_hide_price' => 'nullable|boolean'` rule — `app/Http/Requests/Admin/UpdateCourseRequest.php`
- [X] T007 [US1] Update `Admin\CourseController`: accept + store `high_ticket_hide_price` in `store()` and `update()`; pass `high_ticket_hide_price` and `is_high_ticket` as props in `edit()` / `show()` — `app/Http/Controllers/Admin/CourseController.php`
- [X] T008 [US1] Update `CourseForm.vue`: add `{ value: 'high_ticket', label: '客製服務' }` to `courseTypes` array; add `high_ticket_hide_price` to `useForm()`; add conditional toggle section (shown only when `form.type === 'high_ticket'`) — `resources/js/Components/Admin/CourseForm.vue`

**Checkpoint**: Admin can create/edit a course with type "客製服務" and toggle "隱藏價格" — data saved correctly.

---

## Phase 3: User Story 2 — 客製服務銷售頁前台展示 (Priority: P1)

**Goal**: Sales page correctly shows/hides price block and shows "立即預約" button based on `high_ticket_hide_price`.

**Independent Test**: With a `high_ticket` course (hide_price=true) — visit sales page — confirm: bottom price/countdown replaced by info text, top PriceDisplay hidden, button reads "立即預約". With hide_price=false or non-high_ticket course — confirm zero visual change.

> Can be developed in parallel with Phase 2 after Phase 1 completes.

- [X] T009 [US2] Update `CourseController` (public): pass `is_high_ticket` and `high_ticket_hide_price` props to `Course/Show` page — `app/Http/Controllers/CourseController.php`
- [X] T010 [US2] Update `Course/Show.vue` — top section: add `isHighTicket` and `highTicketHidePrice` computed refs; wrap top `PriceDisplay` with `v-if="!isHighTicket || !highTicketHidePrice"` — `resources/js/Pages/Course/Show.vue`
- [X] T011 [US2] Update `Course/Show.vue` — bottom section: replace `<PriceDisplay />` block with conditional: when `isHighTicket && highTicketHidePrice` show info text div, otherwise show `<PriceDisplay />` — `resources/js/Pages/Course/Show.vue`
- [X] T012 [US2] Update `Course/Show.vue` — button label: update buy button text to show `'立即預約'` when `isHighTicket && highTicketHidePrice`; update floating panel to hide `PriceDisplay` with `v-if="!isHighTicket || !highTicketHidePrice"` in same condition — `resources/js/Pages/Course/Show.vue`
- [X] T013 [US2] Update `getTypeLabel()` in `Course/Show.vue`: add `high_ticket: '客製服務'` mapping — `resources/js/Pages/Course/Show.vue`

**Checkpoint**: Sales page renders correctly for all course types. No regression on existing lecture/mini/full courses.

---

## Phase 4: User Story 3 — 訪客預約後收到 Email (Priority: P2)

**Goal**: Visitor fills booking form (name + email) → system sends confirmation email using DB template. No DB record created.

**Independent Test**: Visit a high_ticket+hide_price sales page → click "立即預約" → fill name + email → submit → check inbox for confirmation email with correct variable substitution.

- [X] T014 [P] [US3] Create `HighTicketBookingMail`: follows `BatchEmailMail` pattern — constructor accepts `(string $emailSubject, string $emailBody)`, converts Markdown to HTML via `CommonMarkConverter` in constructor, `envelope()` uses `$this->emailSubject`, `content()` uses `view: 'emails.high-ticket-booking'` — `app/Mail/HighTicketBookingMail.php`
- [X] T015 [P] [US3] Create HTML email blade view: renders `{!! $htmlBody !!}` (same as `batch-email.blade.php`) — `resources/views/emails/high-ticket-booking.blade.php`
- [X] T016 [P] [US3] Create `HighTicketBookingService::book(Course $course, array $data): array`: (1) validate course is `high_ticket` type with `hide_price=true`; (2) find `EmailTemplate::forEvent('high_ticket_booking_confirmation')`; (3) render subject + body with vars; (4) `Mail::to($data['email'])->send(new HighTicketBookingMail(...))`; (5) return `['success' => true]` — no DB record created (FR-011) — `app/Services/HighTicketBookingService.php`
- [X] T017 [US3] Create `HighTicketBookingController@store`: validate (name required, email required/valid), delegate to `HighTicketBookingService::book()`, redirect back with flash `high_ticket_booking_success` on success or `withErrors()` on failure — `app/Http/Controllers/HighTicketBookingController.php`
- [X] T018 [US3] Add route: `POST /course/{course}/book` → `HighTicketBookingController@store` with `throttle:5,1` — `routes/web.php`
- [X] T019 [US3] Update `Course/Show.vue`: add booking form (name + email inputs, submit button), booking submission via `router.post()`, show success message on `high_ticket_booking_success` flash — `resources/js/Pages/Course/Show.vue`

**Checkpoint**: Submit booking form on high_ticket course → email received → correct name and course name in email.

---

## Phase 5: User Story 4 — 後台 Email 模板統一管理 (Priority: P2)

**Goal**: Admin can view and edit all 3 system email templates (high_ticket booking, course gifted, lesson added). Changes take effect on next send. Existing hardcoded mails fall back gracefully when no DB template found.

**Independent Test**: Edit "課程贈禮通知" template in admin → change subject → gift a course → confirm received email uses new subject.

- [X] T020 [P] [US4] Create `EmailTemplateSeeder`: seed 3 default templates (`high_ticket_booking_confirmation`, `course_gifted`, `lesson_added`) with current hardcoded content as initial `body_md`; register seeder in `DatabaseSeeder::run()` — `database/seeders/EmailTemplateSeeder.php`
- [X] T021 [P] [US4] Create `EmailTemplateRequest`: validate `name` (required, max:100), `subject` (required, max:255), `body_md` (required) — `app/Http/Requests/Admin/EmailTemplateRequest.php`
- [X] T022 [P] [US4] Create `Admin\EmailTemplateController`: `index()` returns all templates; `edit()` returns template + availableVariables per event_type; `update(EmailTemplateRequest)` validates and saves — `app/Http/Controllers/Admin/EmailTemplateController.php`
- [X] T023 [US4] Add admin routes: `GET /admin/email-templates` (index), `GET /admin/email-templates/{template}/edit` (edit), `PUT /admin/email-templates/{template}` (update) — `routes/web.php`
- [X] T024 [P] [US4] Create `Admin/EmailTemplates/Index.vue`: list all templates with name, event_type label, edit button — `resources/js/Pages/Admin/EmailTemplates/Index.vue`
- [X] T025 [US4] Create `Admin/EmailTemplates/Edit.vue`: name field, subject field, `body_md` textarea (bound to `form.body_md`), variable insert buttons (per event_type, insert at cursor via `selectionStart`/`selectionEnd`), Markdown preview via `computed(() => marked(form.body_md))`, save via `router.put()` — `resources/js/Pages/Admin/EmailTemplates/Edit.vue`
- [X] T026 [US4] Refactor `CourseGiftedMail`: lookup `EmailTemplate::forEvent('course_gifted')` first; if found render subject+body (Markdown → HTML) and send; else fallback to current hardcoded content — `app/Mail/CourseGiftedMail.php` and `app/Http/Controllers/Admin/MemberController.php`
- [X] T027 [US4] Refactor `LessonAddedNotification`: same fallback pattern as T026 — `app/Mail/LessonAddedNotification.php` and `app/Http/Controllers/Admin/LessonController.php`

**Checkpoint**: All 3 templates editable in admin. Editing course_gifted template → gift a course → updated subject/body received.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T028 Add "Email 模板" nav link in admin sidebar/navigation — `resources/js/Layouts/AdminLayout.vue`
- [X] T029 Run `php artisan db:seed --class=EmailTemplateSeeder` — verify 3 default templates seeded correctly
- [ ] T030 [P] Smoke test complete booking flow end-to-end: admin sets course to high_ticket + hide_price → visitor submits booking form → email arrives with correct content

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Foundational)
  └─▶ Phase 2 (US1) ─┐
  └─▶ Phase 3 (US2) ─┤ ← can run in parallel
  └─▶ Phase 4 (US3) ─┤
        └─▶ Phase 5 (US4) ← US3 must complete first (HighTicketBookingController references EmailTemplate)
              └─▶ Phase 6 (Polish)
```

### Within Each Phase

- Models before controllers
- Controllers before routes
- Routes before frontend pages
- `[P]` tasks within a phase can run simultaneously

### Parallel Opportunities

```bash
# Phase 1 — run T001 and T002 together, then T003 and T004 together:
T001 (migration courses) || T002 (migration email_templates)
T003 (Course model)      || T004 (EmailTemplate model)

# Phase 2 — run T005 and T006 together:
T005 (StoreCourseRequest) || T006 (UpdateCourseRequest)

# Phase 4 — run T014, T015, T016 together:
T014 (HighTicketBookingMail) || T015 (blade template) || T016 (HighTicketBookingService)

# Phase 5 — run T020, T021, T022, T024 together:
T020 (Seeder) || T021 (EmailTemplateRequest) || T022 (Controller) || T024 (Index.vue)
```

---

## Implementation Strategy

### MVP First (US1 + US2 only — P1 stories)

1. Complete Phase 1 (Foundational)
2. Complete Phase 2 (US1) + Phase 3 (US2) in parallel
3. **STOP and VALIDATE**: Admin can set high_ticket type; sales page renders correctly
4. Deploy / demo

### Full Delivery

1. Foundation → US1 + US2 (MVP)
2. Add US3 (booking email) → test booking flow
3. Add US4 (email template management) → seed defaults → test refactored mails
4. Polish → nav link, smoke test

---

## Task Summary

| Phase | Story | Tasks | Parallelizable |
|-------|-------|-------|----------------|
| Phase 1: Foundational | — | T001–T004 | T001‖T002, T003‖T004 |
| Phase 2: US1 (P1) | Admin course config | T005–T008 | T005‖T006 |
| Phase 3: US2 (P1) | Sales page display | T009–T013 | — |
| Phase 4: US3 (P2) | Booking → Email | T014–T019 | T014‖T015‖T016 |
| Phase 5: US4 (P2) | Email template mgmt | T020–T027 | T020‖T021‖T022‖T024 |
| Phase 6: Polish | — | T028–T030 | T028‖T030 |

**Total**: 30 tasks

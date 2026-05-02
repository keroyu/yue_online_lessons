# Tasks: 客製服務預約系統

**Branch**: `008-high-ticket-booking`
**Input**: `specs/008-high-ticket-booking/` (spec.md, plan.md, data-model.md, contracts/, quickstart.md)
**Tests**: Not requested — no test tasks generated.
**Updated**: 2026-04-09 - 所有任務完成（Phase 7：post-implementation fixes — 非同步預約、UX 優化、MySQL 相容修正）
**Updated**: 2026-04-09 - 規格擴充 US5/US6（Phase 8 待規劃：Lead 記錄 + Leads 管理後台 + 加入序列信）
**Updated**: 2026-04-10 - 新增 Phase 8（US5：Lead 記錄）、Phase 9（US6：Leads 管理後台）、Phase 10（Polish）共 13 個任務（T031–T043）
**Updated**: 2026-05-02 - 補充實作：PayUni 分期提示、Email 模板列表標籤、通知新時段確認 modal（Phase 11）
**Updated**: 2026-05-03 - Leads 搜尋/課程篩選、批次發郵件 modal、modal z-index 修復、PayUni drip bug 修復（Phase 12）

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

---

## Phase 8: User Story 5 — 預約時系統儲存 Lead 記錄 (Priority: P2)

**Goal**: 訪客提交預約表單時，系統在發送確認 Email 後，無論 Email 是否成功，均建立一筆 `high_ticket_leads` 記錄（status='pending'）。

**Independent Test**: 訪客送出預約表單 → 後台 DB `high_ticket_leads` 出現新紀錄，name、email、course_id、status='pending'、booked_at 均正確；即使 Email 發送失敗，記錄依然存在。

- [X] T031 [P] [US5] Migration: create `high_ticket_leads` table — 欄位：name VARCHAR(100)、email VARCHAR(255)、course_id BIGINT UNSIGNED、status ENUM('pending','contacted','converted','closed') DEFAULT 'pending'、notified_count TINYINT DEFAULT 0、last_notified_at TIMESTAMP NULL、booked_at TIMESTAMP NOT NULL、timestamps；加 idx_email、idx_status、idx_course_id 索引 — `database/migrations/2026_04_10_000001_create_high_ticket_leads_table.php`
- [X] T032 [P] [US5] Create `HighTicketLead` model: `$fillable` (name, email, course_id, status, notified_count, last_notified_at, booked_at)、`casts()` (booked_at/last_notified_at → datetime, notified_count → integer)、`scopeByStatus(Builder $query, string $status)`、`belongsTo(Course::class)` — `app/Models/HighTicketLead.php`
- [X] T033 [US5] Add `hasMany(HighTicketLead::class)` relation to `Course` model — `app/Models/Course.php`
- [X] T034 [US5] Modify `HighTicketBookingService::book()`: wrap `Mail::to()->send()` in try/catch（失敗 log 但繼續）；try/catch 結束後，**無論成功失敗**，執行 `HighTicketLead::create(['name'=>..., 'email'=>..., 'course_id'=>..., 'status'=>'pending', 'booked_at'=>now()])` — `app/Services/HighTicketBookingService.php`

**Checkpoint**: 訪客送出預約表單 → `high_ticket_leads` 出現新紀錄；模擬 Email 失敗（關閉 mail driver）→ 記錄依然建立。

---

## Phase 9: User Story 6 — 管理員管理 Leads 名單與加入序列信 (Priority: P3)

**Goal**: 管理員可在後台查看、篩選、更新 Leads 狀態；批次「通知新時段」（async Job per lead）；批次「加入序列信」（async Job per lead，含 firstOrCreate user + DripService::subscribe + auto-close）。

**Independent Test**: 管理員篩選 pending leads → 勾選 2 筆 → 點擊「加入序列信」→ 選擇 drip 課程 → 確認 → response 回傳 `{dispatched:2, skipped:0}` → 這 2 人各自建立 user 帳號（若不存在）並訂閱 drip 課程，Lead status 改為 'closed'，第一封序列信由 Queue Worker 發出。

> **⚠️ 前置條件**: Phase 8（T031–T034）必須完成，T037 EmailTemplateSeeder 更新後需 re-seed。

- [X] T035 [P] [US6] Create `NotifyHighTicketSlotJob`: constructor `(int $leadId, int $templateId)`（primitives only）；`handle()`: load lead + template（找不到則 return）→ render vars `{{user_name}}`/`{{course_name}}` → `Mail::to($lead->email)->send(new HighTicketBookingMail(...))` → `$lead->increment('notified_count')` → `$lead->update(['last_notified_at'=>now()])`；`public int $tries = 3`、`public array $backoff = [60, 300, 900]`；失敗時 `Log::error` — `app/Jobs/NotifyHighTicketSlotJob.php`
- [X] T036 [P] [US6] Create `SubscribeDripLeadJob`: constructor `(int $leadId, int $dripCourseId)`（primitives only）；`handle()`: load lead（找不到則 return）→ `$user = User::firstOrCreate(['email'=>$lead->email], ['nickname'=>$lead->name])` → `$result = app(DripService::class)->subscribe($user, Course::find($dripCourseId))` → if success: `$lead->update(['status'=>'closed'])`；`public int $tries = 3`、`public array $backoff = [60, 300, 900]`；失敗時 `Log::error` — `app/Jobs/SubscribeDripLeadJob.php`
- [X] T037 [P] [US6] Update `EmailTemplateSeeder`: 新增第 4 筆模板 `['name'=>'客製服務新時段通知', 'event_type'=>'high_ticket_slot_available', 'subject'=>'【新時段釋出】{{course_name}} 預約面談', 'body_md'=>"Hi {{user_name}}，\n\n感謝您之前預約 {{course_name}}。\n\n我們剛釋出了新的面談時段，歡迎重新預約！\n\n如有任何問題，歡迎回覆此信聯繫。"]`；使用 `updateOrCreate(['event_type'=>...], [...])` 保持冪等 — `database/seeders/EmailTemplateSeeder.php`
- [X] T038 [US6] Create `HighTicketLeadService`: (1) `notifySlot(array $leadIds): array` — 載入 status='pending' 的 leads（id IN $leadIds）→ 找 `EmailTemplate::forEvent('high_ticket_slot_available')`（找不到回傳 `['success'=>false, 'error'=>'...']`）→ per lead: `NotifyHighTicketSlotJob::dispatch($lead->id, $template->id)` → 回傳 `['dispatched'=>N]`；(2) `subscribeDrip(array $leadIds, int $dripCourseId): array` — 載入 status IN ['pending','closed'] 的 leads → 對每筆 lead 先查 `$existingUser = User::where('email', $lead->email)->first()`，若存在且 `DripSubscription::where('user_id', $existingUser->id)->where('status','active')->exists()` 則 skip；否則 `SubscribeDripLeadJob::dispatch($lead->id, $dripCourseId)` → 回傳 `['dispatched'=>N, 'skipped'=>M]` — `app/Services/HighTicketLeadService.php`
- [X] T039 [US6] Create `Admin\HighTicketLeadController`: `index()` — `HighTicketLead::with('course:id,title')->when($status, scopeByStatus)->orderBy('booked_at','desc')->paginate(20)`，pass `leads`、`filters`、`dripCourses`（`Course::where('course_type','drip')->select('id','name')->get()`）→ Inertia render `Admin/HighTicketLeads/Index`；`updateStatus()` — validate status in ENUM，update，return JSON；`notifySlot()` — validate `lead_ids` array，delegate to `HighTicketLeadService::notifySlot()`，return JSON；`subscribeDrip()` — validate `lead_ids` array + `drip_course_id` integer，delegate to `HighTicketLeadService::subscribeDrip()`，return JSON — `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [X] T040 [US6] Add 4 admin routes under `auth + admin` middleware: `GET /admin/high-ticket-leads` → `HighTicketLeadController@index` (name: `admin.high-ticket-leads.index`)；`PATCH /admin/high-ticket-leads/{lead}/status` → `@updateStatus`；`POST /admin/high-ticket-leads/notify-slot` → `@notifySlot`；`POST /admin/high-ticket-leads/subscribe-drip` → `@subscribeDrip` — `routes/web.php`
- [X] T041 [US6] Create `Admin/HighTicketLeads/Index.vue`: (a) status filter tabs（全部/待聯繫/已聯繫/已成交/已關閉）用 `router.get()` 帶 status query；(b) table 欄位：checkbox、姓名、Email、課程名稱、狀態 inline `<select>`（axios.patch updateStatus）、通知次數、預約時間；(c) checkbox 多選 + `selectedIds` ref；(d) 「通知新時段」按鈕（僅 selectedIds 全為 pending 時 enabled）→ `axios.post notify-slot`；(e) 「加入序列信」按鈕（selectedIds 含 pending 或 closed 時 enabled）→ 開 modal 選 drip 課程 → confirm → `axios.post subscribe-drip`；(f) 操作完成後顯示 inline 結果摘要；(g) Inertia 分頁器（同 Members/Index 模式）— `resources/js/Pages/Admin/HighTicketLeads/Index.vue`
- [X] T042 [P] [US6] Add `Leads 名單` nav link to admin sidebar (放在「Email 模板」連結下方) — `resources/js/Layouts/AdminLayout.vue`

**Checkpoint**: 管理員進入 `/admin/high-ticket-leads` → 看到列表 → 篩選/更新狀態成功 → 批次通知時段 → 批次加入序列信（response 含 dispatched/skipped）→ Queue Worker 執行後 DB 出現 drip_subscription + user。

---

## Phase 10: Polish & Verification

- [X] T043 Run `php artisan db:seed --class=EmailTemplateSeeder` on production-like DB — verify 4 templates exist including `high_ticket_slot_available`
- [X] T044 [P] Smoke test US5: submit booking form → confirm `high_ticket_leads` record created with correct fields
- [X] T045 [P] Smoke test US6 end-to-end: admin selects pending lead → 「加入序列信」→ confirm Job dispatched → Queue processes → drip_subscription created → lead status = 'closed' → first drip email sent

---

## Dependencies & Execution Order (Phase 8–10)

```
Phase 8 (US5 — Foundational for US6)
  T031 (migration) ‖ T032 (model)   ← parallel
  T033 (Course relation)             ← after T032
  T034 (modify BookingService)       ← after T032

Phase 9 (US6)
  T035 (NotifySlotJob) ‖ T036 (SubscribeDripJob) ‖ T037 (Seeder)  ← parallel
  T038 (HighTicketLeadService)       ← after T035, T036
  T039 (HighTicketLeadController)    ← after T038
  T040 (routes)                      ← after T039
  T041 (Index.vue) ‖ T042 (nav link) ← after T040, parallel with each other

Phase 10 (Polish)
  T043 (seed DB)                     ← before T044/T045
  T044 ‖ T045 (smoke tests)         ← parallel
```

### Parallel Opportunities

```bash
# Phase 8 — run T031 and T032 together:
T031 (migration) ‖ T032 (HighTicketLead model)

# Phase 9 — run T035, T036, T037 together:
T035 (NotifyHighTicketSlotJob) ‖ T036 (SubscribeDripLeadJob) ‖ T037 (EmailTemplateSeeder)

# Phase 9 end — run T041 and T042 together:
T041 (Index.vue) ‖ T042 (AdminLayout nav link)
```

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
| Phase 8: US5 (P2) | Lead 記錄 | T031–T034 | T031‖T032 |
| Phase 9: US6 (P3) | Leads 管理後台 | T035–T042 | T035‖T036‖T037, T041‖T042 |
| Phase 10: Polish | — | T043–T045 | T044‖T045 |

**Total**: 45 tasks（T001–T030 已完成，T031–T045 待實作）

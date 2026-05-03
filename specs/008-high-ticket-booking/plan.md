# Implementation Plan: 客製服務預約系統

**Branch**: `008-high-ticket-booking` | **Date**: 2026-04-08 | **Spec**: [spec.md](spec.md)
**Updated**: 2026-04-09 - 實作完成；預約改 JSON API（axios）、說明文案更新、浮動面板文字修正、migration 相容舊版 MySQL
**Updated**: 2026-04-10 - 增量計畫：US5（Lead 記錄）、US6（Leads 管理 + 通知新時段 + 加入序列信）
**Updated**: 2026-05-02 - PayUni 分期提示（US2）；Email 模板列表補標籤（US4）；通知新時段確認 modal + 模板預覽（US6）
**Updated**: 2026-05-03 - Leads 名單搜尋/課程篩選、「發送郵件」批次 modal；修復 PayUni 付款後 drip 未 checkAndConvert 的 bug
**Updated**: 2026-05-03 - 新增序列信訂閱紀錄欄（dripByEmail）、「開通」功能（convertLead service + convert endpoint + modal）；修復 notifyTemplate 500 bug
**Input**: Feature specification from `/specs/008-high-ticket-booking/spec.md`

---

## Summary

**US1–US4（已實作完成）**：客製服務課程類別、銷售頁展示、非同步預約表單 + Email 發送、後台 Email 模板 CRUD。

**US5–US6（本次增量）**：訪客預約時同步儲存 `high_ticket_leads` 記錄；後台提供 Leads 管理頁（篩選/狀態更新/批次通知新時段/批次加入序列信）；新增 `high_ticket_slot_available` Email 模板。技術上：新增一張資料表、一個 Model、一個 Service、一個 Controller、一個 Vue 頁面、一個 Job。

---

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12
**Primary Dependencies**: Inertia.js v2, Vue 3 (`<script setup>`), Tailwind CSS v4, `league/commonmark` (existing), `marked` (existing)
**Storage**: MySQL — new table `high_ticket_leads`
**Testing**: `php artisan test` (PHPUnit)
**Target Platform**: Web server (Linux via Laravel Forge)
**Performance Goals**: Batch notify/subscribe < 30 seconds for up to 100 leads
**Constraints**: Batch emails → async Job (constitution §VI); no new npm/composer packages
**Scale/Scope**: Low volume (high-ticket service leads, expected < 500 records)

---

## Constitution Check

| Principle | Gate | Status |
|-----------|------|--------|
| I. Controller Layering | `notifySlot` + `subscribeDrip` span multiple models + external I/O → Service required | ✅ `HighTicketLeadService` created |
| II. Service Layer | Cross-model (User, DripSubscription, HighTicketLead) + email side-effect | ✅ Service handles all batch logic |
| III. Frontend Architecture | Composition API, local state only | ✅ No Pinia/Vuex |
| IV. Model Conventions | `$fillable`, `casts()`, `scopeByStatus` | ✅ Follows existing patterns |
| V. Job Discipline | Batch slot notification emails → async Job | ✅ `NotifyHighTicketSlotJob` dispatched per lead |
| VI. Email Delivery | Bulk admin operation → async via Job dispatch | ✅ `notifySlot` → `NotifyHighTicketSlotJob`; `subscribeDrip` → `SubscribeDripLeadJob` per lead |
| VII. Error Handling | Service returns `['success' => bool]`; Job: per-item try/catch | ✅ |
| VIII. Authorization | Admin routes under `auth + admin` middleware | ✅ |
| IX. Security | No secrets in code; throttle on public booking (existing) | ✅ |
| X. YAGNI | No new packages; reuses existing `DripService::subscribe()` for drip enrollment | ✅ |

**Constitution Check Result**: PASS — no violations.

---

## Project Structure

### Documentation (this feature)

```text
specs/008-high-ticket-booking/
├── plan.md              # This file
├── research.md          # Phase 0 output (US1–US4, reused)
├── data-model.md        # Updated: added high_ticket_leads table
├── quickstart.md        # Phase 1 output (US1–US4, reused)
├── contracts/
│   └── api.md           # Updated: added Leads admin routes
└── tasks.md             # Phase 2 output (US5–US6 tasks pending)
```

### Source Code (incremental additions)

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       └── HighTicketLeadController.php    [NEW] leads CRUD + batch actions
│   └── Requests/
│       └── Admin/
│           └── (inline validation in controller — simple enough)
├── Jobs/
│   ├── NotifyHighTicketSlotJob.php             [NEW] async slot notification email
│   └── SubscribeDripLeadJob.php                [NEW] async drip enrollment per lead
├── Models/
│   └── HighTicketLead.php                      [NEW]
└── Services/
    ├── HighTicketBookingService.php            [MODIFY] also create lead record
    └── HighTicketLeadService.php               [NEW] notifySlot + subscribeDrip

database/
├── migrations/
│   └── 2026_04_10_000001_create_high_ticket_leads_table.php  [NEW]
└── seeders/
    └── EmailTemplateSeeder.php                 [MODIFY] add high_ticket_slot_available

resources/js/
└── Pages/
    └── Admin/
        └── HighTicketLeads/
            └── Index.vue                       [NEW] leads list + filter + batch actions

routes/web.php                                  [MODIFY] +4 admin leads routes
resources/js/Layouts/AdminLayout.vue            [MODIFY] +Leads nav link
```

---

## Phase 0: Research Summary

No new research required. All integration points are known from US1–US4:
- `DripService::subscribe()` — existing, handles find-or-create user + drip subscription + first email dispatch
- `EmailTemplate::forEvent()` — existing, handles DB template lookup
- `SendBatchEmailJob` pattern — reuse constructor/handle pattern for `NotifyHighTicketSlotJob`
- `User::firstOrCreate(['email' => ...], ['nickname' => ...])` — standard Eloquent, no issues

**Key decision**: `subscribeDrip` delegates to existing `DripService::subscribe()` for each lead. This reuses the entire drip subscription flow (create subscription → dispatch `SendDripEmailJob` for first lesson) without duplication.

---

## Phase 1: Design

### Data Model

See [data-model.md](data-model.md) — `high_ticket_leads` table already documented.

### API Contracts

See [contracts/api.md](contracts/api.md) — 4 new admin routes already documented:
- `GET /admin/high-ticket-leads` — paginated list with status filter
- `PATCH /admin/high-ticket-leads/{lead}/status` — update individual status
- `POST /admin/high-ticket-leads/notify-slot` — batch notify pending leads
- `POST /admin/high-ticket-leads/subscribe-drip` — batch enroll to drip course

### Implementation Details

#### Backend

**HighTicketBookingService (modified)**:
```php
// After sending confirmation email, always create lead record:
HighTicketLead::create([
    'name'       => $data['name'],
    'email'      => $data['email'],
    'course_id'  => $course->id,
    'status'     => 'pending',
    'booked_at'  => now(),
]);
// Lead creation is independent of email send result (try/catch email, always create lead)
```

**HighTicketLeadService**:
```php
// notifySlot(array $leadIds): array
// - Load leads where id IN $leadIds AND status='pending'
// - Find EmailTemplate::forEvent('high_ticket_slot_available')
// - If template missing: return ['success' => false, 'error' => '...']
// - Per lead: dispatch NotifyHighTicketSlotJob(leadId, templateId)
// - Return ['dispatched' => N]  ← async; actual send/count update in Job

// subscribeDrip(array $leadIds, int $dripCourseId, User $admin): array
// - Load leads where id IN $leadIds AND status IN ['pending','closed']
// - Pre-check: for each lead, check if email has any status='active' drip_subscription
//   → if yes: mark as skipped, do NOT dispatch Job
// - Per eligible lead: dispatch SubscribeDripLeadJob(leadId, dripCourseId)
//   (constitution §VI: bulk admin op with DB writes + email → async Job)
// - Return ['dispatched' => N, 'skipped' => M]
```

**SubscribeDripLeadJob** *(new — constitution §VI compliance)*:
```php
// Constructor: (int $leadId, int $dripCourseId) — primitives only (constitution §V)
// handle():
//   (a) $lead = HighTicketLead::find($leadId); if (!$lead) return;
//   (b) $user = User::firstOrCreate(['email'=>$lead->email], ['nickname'=>$lead->name])
//   (c) $result = app(DripService::class)->subscribe($user, Course::find($dripCourseId))
//   (d) if $result['success']: $lead->update(['status'=>'closed'])
//   (e) if already subscribed (DripService returns success=false): Log::info, return
// $tries = 3; $backoff = [60, 300, 900]
// Per-item failure: Log::error, return
```

**NotifyHighTicketSlotJob**:
```php
// Constructor: (int $leadId, int $templateId) — primitives only (constitution §V)
// handle(): Load lead + template; render vars; Mail::to($lead->email)->send(...)
//           $lead->increment('notified_count'); $lead->update(['last_notified_at'=>now()])
// $tries = 3; $backoff = [60, 300, 900]
// Per-item failure: Log::error, return (don't stop other items)
```

**Admin\HighTicketLeadController**:
```php
// index(): paginate(20), with('course:id,title'), filter by status, pass dripCourses
// updateStatus(): validate status ENUM, update, return JSON
// notifySlot(): validate lead_ids[], delegate to HighTicketLeadService::notifySlot()
// subscribeDrip(): validate lead_ids[] + drip_course_id, delegate to HighTicketLeadService::subscribeDrip()
```

#### Frontend

**Admin/HighTicketLeads/Index.vue**:
- Status filter tabs (全部 / 待聯繫 / 已聯繫 / 已成交 / 已關閉)
- Table: 姓名、Email、課程、狀態（inline dropdown）、通知次數、預約時間
- Checkbox multi-select with two batch action buttons:
  - 「通知新時段」(only enabled when all selected are `pending`)
  - 「加入序列信」(enabled for `pending` + `closed`)
- 「加入序列信」shows a modal with drip course dropdown before confirming
- After batch action: show inline result summary (已加入 N 人，略過 M 人)
- Pagination: standard Inertia paginator (same as Member Index)

**Status inline update**: `axios.patch()` per lead (JSON, no page reload) — consistent with existing async patterns in 008.

**AdminLayout.vue**: Add `Leads 名單` nav link (under 客製服務 section or after Email 模板).

#### Email Template Seeder (modified)

Add 4th default template:
```php
[
    'name'       => '客製服務新時段通知',
    'event_type' => 'high_ticket_slot_available',
    'subject'    => '【新時段釋出】{{course_name}} 預約面談',
    'body_md'    => "Hi {{user_name}}，\n\n感謝您之前預約 {{course_name}}。\n\n我們剛釋出了新的面談時段，歡迎重新預約！\n\n如有任何問題，歡迎回覆此信聯繫。",
]
```

---

## Complexity Tracking

No constitution violations — no complexity justification required.

**Integration note**: `subscribeDrip` delegates entirely to existing `DripService::subscribe()`. This service already handles:
- Finding/creating users
- Creating drip subscriptions with idempotency
- Dispatching `SendDripEmailJob` for the first lesson

No duplication, no new patterns introduced.

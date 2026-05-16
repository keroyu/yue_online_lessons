# Implementation Plan: 課程作業與批改系統

**Branch**: `010-lesson-homework` | **Date**: 2026-05-10 | **Spec**: [spec.md](./spec.md)  
**Updated**: 2026-05-10 - 教室作業區移至影片正下方（課程文字之前）
**Updated**: 2026-05-10 - 後台批改專區 UX 優化：章節分組顯示、提交列表置頂、每頁 10 筆分頁、partial reload for all admin actions；修復學員提交後頁面空白
**Updated**: 2026-05-10 - AssignmentSection.vue 重設計：題目＋對話紀錄＋輸入框整合為統一卡片；不再使用 CommentThread.vue
**Updated**: 2026-05-10 - fix：HomeworkController allLessonsForCourse 查詢改用雙層排序（chapter.sort_order → lesson.sort_order）
**Updated**: 2026-05-10 - AssignmentSection 樣式微調：題目白底、回覆區 indigo-50、input 直角、留言卡片 pt-[10px] 間距
**Updated**: 2026-05-10 - 作業區細節完善：indigo 色系、展開/收合、Markdown h1/h2 樣式補完、parent_id bug fix、後台 Markdown 渲染
**Updated**: 2026-05-10 - Phase 7-8 完成：通知鈴鐺顯示層（HandleInertiaRequests shared props、useNotifications.js、Navigation.vue、Classroom.vue header）；通知對所有登入者開放（移除 admin 限制）
**Updated**: 2026-05-17 - fix：面板開啟時主容器加 pr-96 + transition 避免內容被遮擋
**Updated**: 2026-05-17 - 後台批改專區 UX 改版：提交列表折疊式（expandedSubmissions ref）、回覆批改改為右側懸浮面板（replyPanel + slide-in Transition）
**Input**: Feature specification from `/specs/010-lesson-homework/spec.md`

---

## Summary

Feature 010 adds a homework assignment and grading system to the classroom. Students submit work via a 2-level comment thread displayed below lesson content; admins grade via a dedicated backend dashboard and can mark submissions complete (triggering +100 points and a bell notification). New entities: `assignments`, `comments`, `assignment_completions`, `homework_notifications`; altered entity: `users` (+`points` column).

---

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12  
**Primary Dependencies**: Laravel 12 + Inertia.js v2 + Vue 3 (`<script setup>`) + Tailwind CSS v4 + `marked` v17 (existing) + `league/commonmark` v2 (existing)  
**Storage**: MySQL — 4 new tables, 1 altered (`users.points` column)  
**Testing**: `php artisan test` (PHPUnit — minimal coverage convention)  
**Target Platform**: Web (Laravel Forge)  
**Project Type**: Laravel full-stack (Inertia.js SPA-like)  
**Performance Goals**: 2s page load with assignment; 2s submission feedback (SC-001, SC-002)  
**Constraints**: N+1 prevention required; all user-facing text in 中文; no external I/O for notifications (sync DB insert only)  
**Scale/Scope**: Single-tenant, small-to-medium user base

---

## Constitution Check

*Evaluated before Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Controller Layering | ✅ Pass | `AssignmentService::markComplete()` handles multi-model flow; simple CRUD stays inline in controllers |
| II. Service Encapsulation | ✅ Pass | Service spans User (points), AssignmentCompletion (create), Notification (create) — 3 models + side-effects. `storeComment` creates Comment + HomeworkNotification inline (2 models) — qualifies for §II simple-path exception: no external I/O, no state machine, no idempotency requirement. |
| III. Frontend Architecture | ✅ Pass | Vue 3 `<script setup>`, no Pinia; `notificationCount` in shared props mirrors `cartCount` pattern; `useNotifications.js` mirrors `useCart.js` |
| IV. Model Conventions | ✅ Pass | All new models use `$fillable`, typed `casts()`, typed relationship methods |
| V. Job & Queue | ✅ Pass | No Job needed — notification is a synchronous DB insert (no email, no external I/O) |
| VI. Email Delivery | ✅ N/A | Spec explicitly says no email notifications for this feature |
| VII. Error Handling | ✅ Pass | Service returns `['success' => bool, 'error' => '中文']`; controller translates to `withErrors()` |
| VIII. Authorization | ✅ Pass | `auth` + ownership check for member comments; `auth + admin` for admin routes; course access check for assignment view |
| IX. Security | ✅ Pass | No new env vars; Markdown rendered via `v-html` (admin-authored trusted content) |
| X. Simplicity | ✅ Pass | No Events/Listeners, no Repository pattern, no API Resources — follows existing codebase patterns throughout |

**No gate violations. No Complexity Tracking section needed.**

*Post-design re-check*: Completed after data-model.md and contracts/routes.md were written. No new violations introduced.

---

## Project Structure

### Documentation (this feature)

```text
specs/010-lesson-homework/
├── plan.md              # This file
├── research.md          # Phase 0 — decisions and rationale
├── data-model.md        # Phase 1 — schema + model definitions
├── quickstart.md        # Phase 1 — onboarding guide
├── contracts/
│   └── routes.md        # Phase 1 — all routes + request/response shapes
├── checklists/
│   └── requirements.md  # Spec quality checklist (all pass)
└── tasks.md             # Phase 2 — /speckit.tasks output (not yet created)
```

### Source Code (repository root)

**New files to create:**

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   └── HomeworkController.php              # Assignment CRUD + grading dashboard + markComplete
│   │   └── Member/
│   │       ├── AssignmentCommentController.php     # store, update, destroy (member-owned)
│   │       └── NotificationController.php          # index (top 5), markRead
│   └── Requests/
│       ├── Admin/
│       │   ├── StoreAssignmentRequest.php          # md_content validation
│       │   └── StoreHomeworkCommentRequest.php     # content, parent_id validation
│       └── Member/
│           └── StoreCommentRequest.php             # content, parent_id validation
├── Models/
│   ├── Assignment.php                               # lesson_id (unique), md_content, is_published
│   ├── Comment.php                                  # assignment_id, user_id, parent_id, content, is_edited
│   ├── AssignmentCompletion.php                     # write-once, unique(assignment_id, user_id)
│   └── HomeworkNotification.php                     # table: homework_notifications; type enum, course_name snapshot, lesson_id, is_read
└── Services/
    └── AssignmentService.php                        # markComplete(User, Assignment): array

database/migrations/
├── 2026_05_10_000001_add_points_to_users_table.php
├── 2026_05_10_000002_create_assignments_table.php
├── 2026_05_10_000003_create_comments_table.php
├── 2026_05_10_000004_create_assignment_completions_table.php
└── 2026_05_10_000005_create_notifications_table.php

resources/js/
├── Components/
│   └── Classroom/
│       ├── AssignmentSection.vue                    # Full block: question + submit form + thread
│       └── CommentThread.vue                        # 2-level comment UI with edit/delete
├── composables/
│   └── useNotifications.js                          # notificationCount state + fetch + markRead
└── Pages/
    └── Admin/
        └── Homework/
            └── Index.vue                            # Grading dashboard (filter + paginated list)
```

**Existing files to edit:**

```text
app/Http/Controllers/Member/ClassroomController.php   # show() 查 assignment+comments 後傳入 formatLessonFull() 作為參數
app/Http/Controllers/Member/SettingsController.php    # Add points + completions to Inertia props
app/Http/Controllers/Admin/MemberController.php       # Add homework_completions + points to show()
app/Http/Middleware/HandleInertiaRequests.php          # Add notificationCount + notifications (top 5) to share()
app/Models/User.php                                   # Add points to $fillable + 2 new relationships
app/Models/Lesson.php                                 # Add assignment() HasOne relationship

resources/js/Components/Layout/Navigation.vue         # Add notification bell (desktop + mobile)
resources/js/Layouts/AdminLayout.vue                  # Add "作業批改專區" to sidebar navigation
resources/js/Pages/Member/Classroom.vue               # Render AssignmentSection when lesson.assignment exists
resources/js/Pages/Member/Settings.vue                # Add points total + completion history section
resources/js/Pages/Admin/Members/Index.vue            # Add homework completions tab in member detail
routes/web.php                                        # Add 8 new routes (member + admin); GET /member/notifications 已移除（改用 shared props）
```

---

## Phase 0: Research Summary

See [research.md](./research.md) for full rationale. Key decisions:

1. **Markdown rendering**: Reuse `marked.js` + `HtmlContent.vue` (frontend rendering). Assignment question block uses scoped Tailwind utility classes for isolation (no custom CSS file).

2. **Notification bell**: Add `notificationCount`（整數）與 `notifications`（最近 5 筆）兩個 lazy closure 到 `HandleInertiaRequests::share()`，與 `cartCount` 相同模式。Bell 點擊直接渲染 shared props，mark-read 用 `router.post()` 觸發 reload。不需獨立 GET 端點，也不需 axios fetch。

3. **Assignment data in classroom**: 在 `show()` 查詢 assignment + student comments + isCompleted，作為參數傳入 `formatLessonFull()`。format 方法維持純整形職責，不執行 DB 查詢。

4. **`AssignmentService` scope**: Single method `markComplete(User $student, Assignment $assignment): array` — only multi-model operation. All other operations stay inline in controllers.

5. **Comment deletion**: Hard delete with DB-level cascade (`onDelete('cascade')` on `parent_id` FK). No soft delete for comments.

6. **Points storage**: `users.points` integer column (atomic `User::increment('points', 100)`). `assignment_completions` table serves as ledger for history display.

7. **`App\Models\HomeworkNotification`**（table: `homework_notifications`）：改名以避免與 `User` model 的 `Notifiable` trait 造成命名混淆。任何同時操作 User 和通知記錄的檔案不需要 `use ... as` alias。

---

## Phase 1: Design Artifacts

- ✅ [data-model.md](./data-model.md) — 5 migrations, 4 new models, model method signatures
- ✅ [contracts/routes.md](./contracts/routes.md) — 8 new routes, full request/response shapes
- ✅ [quickstart.md](./quickstart.md) — setup steps, happy-path test flow, key file index

---

## Implementation Order (for tasks.md)

Recommended order to minimize blockers:

1. **Migrations + Models** (backend foundation — nothing else can proceed without this)
   - 5 migrations in order
   - 4 new model files
   - Edit `User` + `Lesson` models

2. **AssignmentService** (needed by admin controller's markComplete action)
   - `markComplete(User $student, Assignment $assignment): array`

3. **Admin backend** (assignment CRUD + grading dashboard)
   - `Admin\HomeworkController` (all 9 admin actions)
   - Admin Form Requests
   - Routes in `web.php`
   - `AdminLayout.vue` sidebar item
   - `Pages/Admin/Homework/Index.vue`

4. **Classroom integration** (student-facing assignment view + comment submission)
   - Edit `ClassroomController::formatLessonFull()` — include assignment + comments
   - `Member\AssignmentCommentController` + routes
   - `AssignmentSection.vue` + `CommentThread.vue` components
   - Edit `Classroom.vue` to include AssignmentSection

5. **Notification bell** (depends on notification records being created in step 3)
   - Edit `HandleInertiaRequests` (add `notificationCount`)
   - `useNotifications.js` composable
   - `Member\NotificationController` + routes
   - Edit `Navigation.vue`

6. **Points + Settings** (depends on completions being created in step 3)
   - Edit `SettingsController` — add points + completions
   - Edit `Settings.vue` — add points section

7. **Admin member detail** (depends on completions from step 3)
   - Edit `Admin\MemberController::show()` — add homework_completions + points
   - Edit `Pages/Admin/Members/Index.vue` — display completions in member modal

---

## Next Step

Run `/speckit.tasks` to generate `tasks.md` with atomic implementation tasks derived from this plan.

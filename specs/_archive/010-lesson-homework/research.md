# Research: 課程作業與批改系統 (010-lesson-homework)

**Date**: 2026-05-10  
**Branch**: `010-lesson-homework`

---

## R1. Markdown Rendering

**Decision**: Use frontend `marked.js` (already installed at v17.0.3) for both assignment question display and student submission display.

**Rationale**: The codebase already uses `marked` in `HtmlContent.vue` for lesson `md_content`. Reusing this component and library avoids introducing new dependencies and keeps frontend rendering consistent. The assignment question block needs its own CSS scope (per spec FR-001), which means wrapping `HtmlContent`'s output in a scoped container rather than creating a new rendering component.

**Alternatives considered**:
- Backend rendering via `league/commonmark`: rejected because lesson content is already rendered on the frontend; mixing approaches adds confusion.
- `markdown-it`: not installed, would introduce new dependency unnecessarily.

**Pattern to follow**: `HtmlContent.vue` at `resources/js/Components/Classroom/HtmlContent.vue` — accepts raw Markdown string, renders with `marked()`, outputs via `v-html`. The assignment section will wrap this in a container div with isolated Tailwind prose-like styles applied as utility classes (no custom CSS file).

---

## R2. Notification Bell (Nav Badge)

**Decision**: Strategy A — Add `notificationCount`（整數）和 `notifications`（最近 5 筆 array）兩個 lazy closure 到 `HandleInertiaRequests::share()`。Bell 點擊直接渲染 `page.props.notifications`，mark-read 用 `router.post()` 觸發 reload 自動刷新兩個 shared props。不需要獨立 GET 端點，不需要 axios fetch。

**Rationale**: `cartCount` 已驗證 shared props 模式可行。相比 `useCart.js` 中針對購物車操作使用 axios 的做法，通知清單不需要獨立 fetch——直接在 shared props 帶入 top 5 筆，每次 Inertia navigation 自動更新，完全符合 SC-007「下次開啟任意頁面即可看到紅點」。

**Alternatives considered**:
- Polling (e.g., every 30s): rejected — 持續消耗 server 資源，spec 說「下次開啟任意頁面」而非即時通知。
- WebSocket/SSE: rejected — 專案無 queue/broadcast 基礎設施，遠超 spec 需求。
- Bell click → `window.axios.get('/api/notifications')`: rejected — Strategy A 更簡單，不需要額外端點，且 `useCart.js` 的 axios 用法是針對購物車 mutation（add/remove），與此場景不同。

**Implementation**:
1. `HandleInertiaRequests::share()` 加入兩個 lazy closures：`notificationCount`（count query）和 `notifications`（limit 5 query + map）。
2. `useNotifications.js` composable：讀取 `page.props.notificationCount` 和 `page.props.notifications`，監聽 navigation 變化。
3. Bell 開啟：直接渲染 `page.props.notifications`，零額外請求。
4. 點擊通知：`router.post(route('member.notifications.read', id))` → Inertia reload → shared props 自動刷新。

---

## R3. Assignment Data Flow in Classroom

**Decision**: Fold assignment and completion status into `ClassroomController::formatLessonFull()` as additional props. No separate GET endpoint for assignment data.

**Rationale**: The classroom page already loads full lesson data on page load. Adding assignment (md_content, is_published) and completion badge (is_assignment_completed) to the existing lesson props avoids a second HTTP round-trip. This is consistent with how `is_completed`, `promo_html`, etc. are already included in `formatLessonFull`.

**Student comments**: Loaded alongside the assignment data when the lesson is formatted — a small query for the student's own comments (max a few rows per lesson per student).

---

## R4. AssignmentService Scope

**Decision**: Create `AssignmentService` with a single public method `markComplete(User $student, Assignment $assignment): array`.

**Rationale**: The mark-complete operation spans three models (User.points update, AssignmentCompletion.create, Notification.create) plus an idempotency check — this clearly crosses the Constitution §II threshold for requiring a Service. All other operations (comment CRUD, assignment CRUD, notification read) are single-model operations that stay inline in their respective controllers.

**Service return contract**: `['success' => bool]` on success, `['success' => false, 'error' => '中文錯誤訊息']` on failure (already completed, etc.).

**No Job dispatch**: Notification creation is a synchronous DB insert (no email, no external I/O). Following §V, Jobs are only needed for async work. This notification is lightweight enough to run synchronously within the request.

---

## R5. Comment Deletion Strategy

**Decision**: Hard delete with DB-level cascade for child comments. Soft delete NOT used for comments.

**Rationale**: Spec says comments are permanently removed when deleted. DB-level cascade (`onDelete('cascade')` on `parent_id` FK) handles the cascade automatically — no application-level loop needed. This is simpler and matches the constitution's §X preference for simplicity.

**Assignment data on unpublish**: Assignments are never permanently deleted — only toggled `is_published`. Comments on unpublished assignments remain in DB and visible to admin but completely hidden from the frontend.

---

## R6. HomeworkNotification Model & Table Design

**Decision**: Model 命名為 `HomeworkNotification`（table: `homework_notifications`），而非 `Notification`。`type` enum、`course_name`（snapshot）、`lesson_id`（供跳轉）。Standard `$timestamps`（`is_read` 需要更新，保留 `updated_at`）。

**Rationale**: `User` model 已使用 `Notifiable` trait，若 model 命名為 `App\Models\Notification`，任何同時 import User 和 Notification 的 controller/service 都需要 `use App\Models\Notification as HomeworkNotification` alias，長期維護困難。使用 `HomeworkNotification` 直接消除此問題，明確表達這是「作業系統的通知」而非通用通知。Snapshots `course_name` 確保課程改名後通知訊息不受影響。

**Cap at 5**: 顯示上限為最近 5 筆（query limit，非儲存上限）。舊通知自然退出顯示，但保留在 DB。定期清理為 out of scope。

---

## R7. Points Storage

**Decision**: Add `points` integer column (default 0) directly to `users` table. No separate points ledger table.

**Rationale**: The completion history is already captured in `assignment_completions` (which records user_id, assignment_id, created_at). The Settings page queries `assignment_completions` with course/lesson joins to display the history, while `users.points` stores the running total for fast lookup. This avoids a separate `point_transactions` table while still supporting the required display (FR-025).

**Points update**: `User::increment('points', 100)` — atomic increment, race-condition-safe.

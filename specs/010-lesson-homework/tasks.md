# Tasks: 課程作業與批改系統

**Branch**: `010-lesson-homework`  
**Input**: Design documents from `/specs/010-lesson-homework/`  
**Tests**: No test tasks (spec does not request TDD)  
**Spec**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md)

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no shared dependencies)
- **[Story]**: Maps to user story from spec.md (US1–US8)
- File paths are absolute from repository root

---

## Phase 1: Setup

**Purpose**: 確認環境、branch、依賴均就緒，無需新建專案。

- [X] T001 確認已在 `010-lesson-homework` branch (`git checkout 010-lesson-homework`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Migrations、Models、現有 Model 擴充。所有 User Story 均依賴此階段完成。

**⚠️ CRITICAL**: 所有 User Story 實作必須在此階段完成後才能開始。

### Migrations

- [X] T002 建立 `database/migrations/2026_05_10_000001_add_points_to_users_table.php`：`users` 加入 `points` unsignedInteger 欄位，預設 0
- [X] T003 [P] 建立 `database/migrations/2026_05_10_000002_create_assignments_table.php`：`id, lesson_id (unique FK), md_content text, is_published bool default true, timestamps`
- [X] T004 [P] 建立 `database/migrations/2026_05_10_000003_create_comments_table.php`：`id, assignment_id FK cascadeOnDelete, user_id FK cascadeOnDelete, parent_id nullable FK→comments cascadeOnDelete, content text, is_edited bool default false, timestamps`
- [X] T005 [P] 建立 `database/migrations/2026_05_10_000004_create_assignment_completions_table.php`：`id, assignment_id FK, user_id FK, created_at timestamp; UNIQUE(assignment_id, user_id)`（`$timestamps = false`）
- [X] T006 [P] 建立 `database/migrations/2026_05_10_000005_create_homework_notifications_table.php`：`id, user_id FK cascadeOnDelete, type enum('reply','completion'), course_name string, lesson_id FK cascadeOnDelete, is_read bool default false, timestamps`
- [X] T007 執行 `php artisan migrate`，確認 5 個 migration 順利執行

### Models

- [X] T008 建立 `app/Models/Assignment.php`：`$fillable, casts(), lesson(), comments()（whereNull parent_id）, completions(), isCompletedBy(User), scopePublished()`
- [X] T009 [P] 建立 `app/Models/Comment.php`：`$fillable, casts(), user(), assignment(), parent(), replies()（orderBy created_at）, scopeTopLevel(), isOwnedBy(User)`
- [X] T010 [P] 建立 `app/Models/AssignmentCompletion.php`：`$timestamps = false; $fillable; boot()（static::creating → freshTimestamp）; assignment(), user()`（參照 `LessonProgress` 的 `boot()` 寫法）
- [X] T011 [P] 建立 `app/Models/HomeworkNotification.php`：`$table = 'homework_notifications'; $fillable; casts(); user(), lesson(); scopeForUser(), scopeUnread()`

### Existing Model Updates

- [X] T012 修改 `app/Models/User.php`：`$fillable` 加入 `'points'`；加入 `assignmentCompletions(): HasMany` 和 `homeworkNotifications(): HasMany`
- [X] T013 [P] 修改 `app/Models/Lesson.php`：加入 `assignment(): HasOne` 關聯

**Checkpoint**: `php artisan tinker` 確認 `Assignment::first()`, `Comment::first()`, `AssignmentCompletion::first()`, `HomeworkNotification::first()` 均可執行（回傳 null）；`User::first()->points` 為 0

---

## Phase 3: US3 - 管理員建立與管理作業題目 (Priority: P1) 🎯

**Goal**: 管理員可在後台作業批改專區為任意小節建立、編輯、下架（上架）作業題目（Markdown，含即時預覽）。此為所有學員功能的前提。

**Independent Test**: 後台 `/admin/homework` 顯示 sidebar 入口；為某小節建立題目，前往 `/member/classroom/{course}?lesson_id={id}` 確認作業區塊出現；下架後確認作業區塊完全消失。

- [X] T014 建立 `app/Http/Requests/Admin/AssignmentRequest.php`：驗證 `md_content`（required, string, max 50000）；store/update 共用同一 Request class
- [X] T015 建立 `app/Http/Controllers/Admin/HomeworkController.php`（初版）：
  - `index(Request $request)` — 顯示所有課程/小節，預留 submissions 位置（Phase 5 補充）
  - `store(AssignmentRequest $request, Lesson $lesson)` — 建立 assignment（僅 create；若 `$lesson->assignment` 已存在，redirect back with error `'此小節已有作業題目，請使用編輯功能'`，不做 upsert）
  - `update(AssignmentRequest $request, Assignment $assignment)` — 更新題目
  - `publish(Assignment $assignment)` — `is_published = true`
  - `unpublish(Assignment $assignment)` — `is_published = false`
  - 注入 `AssignmentService` constructor：`__construct(protected AssignmentService $assignmentService) {}`
  - **同時建立空的 `app/Services/AssignmentService.php` 存根**（僅含 `public function markComplete(): array { return []; }` 方法簽名），避免 Phase 3–8 期間 Laravel DI 拋 class not found；Phase 9 再填入完整實作
- [X] T016 修改 `routes/web.php`（admin group）：加入以下路由
  ```
  GET    /admin/homework                            admin.homework.index
  POST   /admin/lessons/{lesson}/assignment         admin.homework.store
  PUT    /admin/homework/{assignment}               admin.homework.update
  POST   /admin/homework/{assignment}/publish       admin.homework.publish
  POST   /admin/homework/{assignment}/unpublish     admin.homework.unpublish
  ```
- [X] T017 [P] 修改 `resources/js/Layouts/AdminLayout.vue`：在 navigation array 加入 `{ name: '作業批改專區', href: '/admin/homework', icon: '...' }`（書本或鉛筆 SVG icon）
- [X] T018 建立 `resources/js/Pages/Admin/Homework/Index.vue`（初版）：
  - 顯示課程/小節選擇器（下拉篩選）
  - 對尚無作業的小節顯示「新增題目」按鈕
  - Markdown 編輯器（textarea + 即時預覽 toggle，使用 `marked()`）
  - 已有題目的小節顯示題目內容、「編輯」、「下架/上架」按鈕
  - 提交用 `router.post()` / `router.put()` / `router.post()` Inertia 路由

**Checkpoint**: 後台可建立題目並上/下架，所有操作回傳正確 flash 訊息

---

## Phase 4: US1 - 學員提交作業 (Priority: P1) 🎯

**Goal**: 已購買課程的學員，在教室小節內容下方看到作業題目（Markdown 渲染），並可提交 Markdown 格式的作業回覆。試閱學員不顯示。

**Independent Test**: 以學員帳號進入有作業的小節，確認題目區塊出現且 Markdown 正確渲染；送出作業，確認留言列表即時更新，Markdown 渲染正確；以試閱模式進入，確認作業區不顯示。

- [X] T019 修改 `app/Http/Controllers/Member/ClassroomController.php`：
  - `show()` 方法末尾，在 `formatLessonFull()` 呼叫前，查詢：
    - `$assignment = $currentLesson?->assignment()->published()->first()`
    - `$assignmentComments = ($assignment && !$isFreePreview) ? $assignment->comments()->where('user_id', $user->id)->with('replies.user')->get() : collect()`
    - `$isAssignmentCompleted = $assignment ? $assignment->completions()->where('user_id', $user->id)->exists() : false`
  - 修改 `formatLessonFull()` 簽名：加入 `?Assignment $assignment = null, Collection $assignmentComments = ..., bool $isAssignmentCompleted = false`
  - `formatLessonFull()` 回傳陣列加入 `'assignment'` 和 `'assignment_comments'` 和 `'is_assignment_completed'` key
  - Preview 模式（`isFreePreview`）：`assignment` 永遠為 `null`
- [X] T020 建立 `app/Http/Requests/Member/StoreCommentRequest.php`：驗證 `content`（required, string, max 5000）；`parent_id`（nullable, exists:comments,id，且該 comment 的 assignment_id 必須匹配，且 parent 必須為頂層）
- [X] T021 建立 `app/Http/Controllers/Member/AssignmentCommentController.php`（初版，只含 `store`）：
  - Route model binding: `Course, Assignment`
  - 授權：`$course->hasAccessForUser($user)`，確認 `$assignment->lesson->course_id === $course->id`
  - 確認 `$assignment->is_published`（已下架作業不接受新提交）
  - 建立 `Comment`，`redirect()->back()->with('success', '作業已提交')`
- [X] T022 修改 `routes/web.php`（member group）：加入
  ```
  POST /member/classroom/{course}/assignment/{assignment}/comments   member.comments.store
  ```
- [X] T023 [P] 建立 `resources/js/Components/Classroom/AssignmentSection.vue`：
  - Props: `assignment`（含 `md_content`, `is_completed`）, `comments`（array）, `courseId`, `lessonId`
  - 作業題目區塊：獨立樣式容器（Tailwind prose utility classes），渲染 `###/####` 標題、清單、連結、blockquote、粗體
  - Markdown 題目用 `HtmlContent.vue` 渲染
  - 提交表單：textarea（Markdown 輸入），submit button，用 `router.post()` 提交
  - 顯示 `CommentThread` 元件（props 傳入 comments）
  - Phase 8 補充：is_completed badge（先佔位）
- [X] T024 [P] 建立 `resources/js/Components/Classroom/CommentThread.vue`（初版，只顯示，無 edit/delete）：
  - Props: `comments`（array of top-level + replies）, `assignmentId`, `courseId`
  - 逐筆顯示 top-level comment（Markdown 渲染用 `marked()`），及其 replies
  - 顯示留言者 nickname、時間、`已編輯` badge（if `is_edited`）
  - 在 top-level comment 下方顯示 reply 表單（for 追加補充，parent_id = comment.id）
- [X] T025 修改 `resources/js/Pages/Member/Classroom.vue`：在 lesson content 後加入 `<AssignmentSection>` 元件，僅在 `currentLesson.assignment` 存在時渲染

**Checkpoint**: 學員可看到題目且成功提交作業，試閱模式不顯示作業區

---

## Phase 5: US4 - 管理員批改學員作業 (Priority: P1) 🎯

**Goal**: 管理員在作業批改專區一次性瀏覽所有學員提交，可 inline 回覆（批改）。

**Independent Test**: 以管理員身份進入 `/admin/homework`，確認看到所有學員提交；篩選課程/小節後正確過濾；對一筆提交送出回覆，以學員帳號進入教室確認看到該回覆。

- [X] T026 擴充 `app/Http/Controllers/Admin/HomeworkController.php`：
  - `index()` 加入分頁提交列表邏輯：`Comment::topLevel()->with(['assignment.lesson.course', 'user', 'replies.user', 'assignment.completions'])` 支援 course_id/lesson_id filter，`->paginate(20)`
  - `storeComment(Request $request, Assignment $assignment)` — 建立 admin 回覆（parent_id 必填），接著建立 `HomeworkNotification`（type='reply'）給被回覆的學員；被通知學員的 user_id 取法：`$parentComment = Comment::find($request->parent_id); $studentId = $parentComment->user_id;`；課程快照：`$course = $assignment->lesson->course; 'course_name' => $course->name, 'course_id' => $course->id, 'lesson_id' => $assignment->lesson_id`
- [X] T027 修改 `routes/web.php`（admin group）：加入
  ```
  POST /admin/homework/{assignment}/comments   admin.homework.comments.store
  ```
- [X] T028 擴充 `resources/js/Pages/Admin/Homework/Index.vue`：
  - 新增分頁提交列表（每頁 20 筆）
  - 每筆顯示：學員姓名、提交內容（Markdown 渲染）、時間
  - 每筆下方 inline 回覆表單（textarea + 送出按鈕，`router.post()`）
  - 已有回覆時顯示在提交下方

**Checkpoint**: 管理員可回覆學員作業，學員在教室可看到回覆

---

## Phase 6: US2 - 學員管理自己的提交記錄 (Priority: P2)

**Goal**: 學員可編輯或刪除自己的留言；刪除含回覆的頂層提交時顯示確認提示。

**Independent Test**: 學員編輯一筆提交，確認顯示「已編輯」標記；刪除含老師回覆的提交，確認出現確認對話框，確認後留言連回覆一起消失。

- [X] T029 擴充 `app/Http/Controllers/Member/AssignmentCommentController.php`：加入
  - `update()` — 授權（isOwnedBy），更新 content + `is_edited = true`，redirect back
  - `destroy()` — 授權（isOwnedBy），delete（DB cascade 自動刪 replies），redirect back
- [X] T030 修改 `routes/web.php`（member group）：加入
  ```
  PUT    /member/classroom/{course}/assignment/{assignment}/comments/{comment}   member.comments.update
  DELETE /member/classroom/{course}/assignment/{assignment}/comments/{comment}   member.comments.destroy
  ```
- [X] T031 [P] 擴充 `resources/js/Components/Classroom/CommentThread.vue`：
  - 自己的留言顯示「編輯」、「刪除」按鈕（根據 `comment.user.id === auth.user.id` 判斷）
  - 編輯：inline textarea 取代原內容，儲存用 `router.put()`
  - 刪除：若 comment 有 replies，顯示確認 `window.confirm('刪除後老師批改也將消失，確認刪除？')`，再 `router.delete()`

**Checkpoint**: 學員可完整管理自己的留言，老師回覆在刪除父留言時一併消失

---

## Phase 7: US5 - 管理員管理所有留言 (Priority: P2)

**Goal**: 管理員可編輯或刪除平台上任何人的留言。

**Independent Test**: 以管理員身份在批改專區找到一筆學員留言，編輯後確認顯示「已編輯」；刪除頂層提交後確認連同回覆一起消失。

- [ ] T032 擴充 `app/Http/Controllers/Admin/HomeworkController.php`：加入
  - `updateComment(Request $request, Assignment $assignment, Comment $comment)` — 無 ownership 限制，更新 content + `is_edited = true`
  - `destroyComment(Assignment $assignment, Comment $comment)` — 無 ownership 限制，delete（cascade）
- [ ] T033 修改 `routes/web.php`（admin group）：加入
  ```
  PUT    /admin/homework/{assignment}/comments/{comment}   admin.homework.comments.update
  DELETE /admin/homework/{assignment}/comments/{comment}   admin.homework.comments.destroy
  ```
- [ ] T034 [P] 擴充 `resources/js/Pages/Admin/Homework/Index.vue`：每筆留言（含回覆）顯示管理員專用「編輯」「刪除」按鈕，操作邏輯同學員端但呼叫 admin 路由

**Checkpoint**: 管理員可編輯、刪除任意留言

---

## Phase 8: US6 - 學員收到作業批改通知 (Priority: P2)

**Goal**: 導覽列通知鈴鐺顯示未讀數，展開最多 5 則通知，點擊跳轉至對應小節並標記為已讀。

**Independent Test**: 管理員回覆學員後，以該學員帳號登入任意頁面，確認鈴鐺出現紅點；點擊鈴鐺展開清單，格式正確；點擊通知跳轉至正確小節，紅點消失。

> **Note**: `HomeworkNotification` 記錄已在 Phase 5（admin 回覆時）建立，此階段只需實作顯示層。

- [ ] T035 修改 `app/Http/Middleware/HandleInertiaRequests.php`：在 `share()` 加入兩個 lazy closures（緊接 `cartCount` 後）：
  - `'notificationCount'`：非管理員登入者的未讀通知數
  - `'notifications'`：最近 5 筆，map 成含 `id, type, course_name, course_id, lesson_id, is_read, message, created_at` 的 array（message 由 type 決定文字格式；`course_id` 供點擊跳轉用）
- [ ] T036 建立 `app/Http/Controllers/Member/NotificationController.php`：
  - `markRead(HomeworkNotification $notification)` — 授權 ownership，`$notification->update(['is_read' => true])`，redirect back
- [ ] T037 修改 `routes/web.php`（member group）：加入
  ```
  POST /member/notifications/{notification}/read   member.notifications.read
  ```
- [ ] T038 [P] 建立 `resources/js/composables/useNotifications.js`（參照 `useCart.js` 架構）：
  - module-scoped `ref` for `notificationCount` 和 `notifications`
  - `watch(() => page.props.notificationCount, ...)` 和 `watch(() => page.props.notifications, ...)` 監聽 navigation 更新
  - export `markRead(id)` 函式：`router.post(route('member.notifications.read', id))` 後 Inertia reload 自動刷新 shared props
- [ ] T039 [P] 修改 `resources/js/Components/Layout/Navigation.vue`（Desktop）：
  - `user && !user.isAdmin` 時在購物車圖示旁顯示鈴鐺圖示
  - 未讀數 > 0 顯示紅點 badge（同購物車 badge 樣式）
  - 點擊展開 dropdown：列出最多 5 則通知，每則顯示 message + 時間；空則顯示「目前沒有通知」
  - 點擊通知：呼叫 `markRead(id)` 後用 `router.visit(route('member.classroom.show', { course: notification.course_id }) + '?lesson_id=' + notification.lesson_id)` 跳轉（`course_id` 已包含在 shared props notification 物件中）
- [ ] T040 修改 `resources/js/Components/Layout/Navigation.vue`（Mobile menu）：加入通知未讀數提示（簡化為文字「通知（N）」連結，點擊展開與 desktop 相同的通知 dropdown；或用 `v-show` 切換同一個通知列表元件，不需獨立頁面）

**Checkpoint**: 管理員回覆後學員下次進入任意頁面可看到通知紅點；點擊通知後紅點消失

---

## Phase 9: US7 - 管理員標記「已完成」並發放積分 (Priority: P2)

**Goal**: 管理員在批改專區對特定學員的特定作業點擊「標記已完成」，觸發 +100 積分、完成通知、`✓ 已完成` 標籤。每位學員每道題只能標記一次。

**Independent Test**: 管理員點擊「標記已完成」後：(1) 按鈕消失顯示「✓ 已完成」；(2) 學員積分 +100（`User::find($id)->points` 為 100）；(3) 學員有新的完成通知；(4) 嘗試再次標記 → 系統拒絕（422 / flash error）。

- [ ] T041 填充 `app/Services/AssignmentService.php` 完整實作（存根已在 T015 建立）：
  - `markComplete(User $student, Assignment $assignment): array`
  - 幂等檢查：`AssignmentCompletion::where(...)->exists()` → return `['success' => false, 'error' => '此學員的作業已標記為完成']`
  - 成功流程：`DB::transaction()` 包住：
    1. `AssignmentCompletion::create(['assignment_id' => ..., 'user_id' => ...])`
    2. `$student->increment('points', 100)`
    3. `HomeworkNotification::create(['user_id' => $student->id, 'type' => 'completion', 'course_name' => $course->name, 'course_id' => $course->id, 'lesson_id' => $assignment->lesson_id])`
  - 成功 return `['success' => true]`
  - 注入 constructor：`HomeworkController` 的 `__construct(protected AssignmentService $assignmentService) {}`
- [ ] T042 擴充 `app/Http/Controllers/Admin/HomeworkController.php`：
  - `markComplete(Assignment $assignment, User $user)` — 呼叫 `$this->assignmentService->markComplete($user, $assignment)`，失敗 redirect back with errors，成功 redirect back with flash
- [ ] T043 修改 `routes/web.php`（admin group）：加入
  ```
  POST /admin/homework/{assignment}/completions/{user}   admin.homework.completions.store
  ```
- [ ] T044 [P] 擴充 `resources/js/Pages/Admin/Homework/Index.vue`：
  - 每位學員的提交區塊：若 `completion === null` 顯示「標記已完成」按鈕（`router.post()`）；若已完成顯示「✓ 已完成」標籤（綠色）及完成日期

**Checkpoint**: 積分機制完整：單次+100，重複標記被擋，通知記錄建立

---

## Phase 10: US8 - 學員查看積分與作業完成勳章 (Priority: P2)

**Goal**: 學員在帳號設定頁查看積分總數與完成明細；教室頁面已完成的作業題目右上角顯示綠色打勾勳章；後台會員管理可查看各會員的完成記錄。

**Independent Test**: 完成標記後：(1) `/member/settings` 顯示積分 100 和完成記錄；(2) 進入該小節教室，作業區右上角出現綠色勳章；(3) `/admin/members/{id}` 顯示該會員的完成記錄。

- [ ] T045 修改 `app/Http/Controllers/Member/SettingsController.php`：
  - `index()` 加入：
    - `'user' => [...existing..., 'points' => $user->points]`
    - `'completions'` prop：`$user->assignmentCompletions()->with('assignment.lesson.course')->orderByDesc('created_at')->get()` → map 成 `{course_name, lesson_title, points_awarded: 100, completed_at}`
- [ ] T046 [P] 修改 `resources/js/Pages/Member/Settings.vue`：在訂單記錄區塊後加入「積分與作業完成記錄」區塊，顯示：積分總數（`user.points`）、完成記錄列表（課程名稱、小節名稱、+100、完成日期，新到舊）；無記錄時顯示「尚無完成記錄」
- [ ] T047 修改 `resources/js/Components/Classroom/AssignmentSection.vue`：
  - 題目區塊右上角加入 `v-if="assignment.is_completed"` 的綠色打勾勳章（`✓` SVG 或 emoji），使用 Tailwind 定位（`absolute top-2 right-2`）
- [ ] T048 修改 `app/Http/Controllers/Admin/MemberController.php`：
  - `show()` 方法加入：`'homework_completions'`（完成記錄，含課程名、小節名、完成日期）和 `'points'`（member 的積分）
- [ ] T049 [P] 修改 `resources/js/Pages/Admin/Members/Index.vue`：在會員詳情 modal 中加入「作業完成記錄」區塊，列出完成記錄與積分總計

**Checkpoint**: 學員積分頁、勳章、後台會員完成記錄三處均正確顯示

---

## Phase 11: Polish & Cross-Cutting Concerns

- [ ] T050 [P] 驗證 `AssignmentSection.vue` 的 Markdown 渲染：測試 `###` 標題、有序/無序清單、超連結、blockquote、粗體均正確渲染，且樣式不與課程內文 (`HtmlContent`) 混用
- [ ] T051 [P] 邊界情境測試（手動）：下架作業後前台不顯示、重複 markComplete 被 422 擋回、學員試圖存取他人留言 API 回傳 403、已下架作業的通知點擊跳轉正常不報錯、**學員刪除含老師回覆的頂層提交 → `window.confirm` 對話框出現，確認後該提交與所有回覆均消失**
- [ ] T052 分頁驗證：確認批改專區在提交量大時正確分頁（每頁 20 筆，第 2 頁可點）
- [ ] T053 [P] 執行 `php artisan test` 確認無現有測試 regression
- [ ] T054 [P] 更新 `repo_map.md`：執行 `python3 plugins/spec_index_plugin.py`，確認 010-lesson-homework 的新 code_files 被索引
- [ ] T055 [P] N+1 查詢驗證：確認以下 eager loading 正確設置：(1) `ClassroomController::show()` 的 assignment comments 查詢使用 `with('replies.user')`；(2) `HomeworkController::index()` 使用 `with(['assignment.lesson.course', 'user', 'replies.user', 'assignment.completions'])`；(3) `SettingsController::index()` 的 completions 使用 `with('assignment.lesson.course')`

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Setup)
  └─→ Phase 2 (Foundational: Migrations + Models)
        └─→ Phase 3 (US3: 管理員建立作業)   ← 前台要顯示題目前，後台必須能建立
              └─→ Phase 4 (US1: 學員提交)   ← 有題目才能提交
                    ├─→ Phase 5 (US4: 管理員批改)   ← 有提交才能批改
                    │     ├─→ Phase 6 (US2: 學員管理留言)  ← 可並行
                    │     ├─→ Phase 7 (US5: 管理員管理留言) ← 可並行
                    │     └─→ Phase 8 (US6: 通知 Bell)   ← 回覆已在 Phase 5 建立
                    │           └─→ Phase 9 (US7: 完成標記積分)
                    │                 └─→ Phase 10 (US8: 積分查看+勳章)
                    └─→ Phase 11 (Polish)
```

### User Story Dependencies

| Story | 依賴 | 原因 |
|-------|------|------|
| US3（建立題目）| Phase 2 | 需要 Assignment model 和 migration |
| US1（提交作業）| US3 | 前台需要有題目才能顯示作業區 |
| US4（管理員批改）| US1 | 需要有學員提交才有資料可批改 |
| US2（學員管理留言）| US1 | 需要有提交才能 edit/delete |
| US5（管理員管理留言）| US4 | 共用批改專區 UI |
| US6（通知 Bell）| US4 | 通知記錄在 admin 回覆時建立（Phase 5） |
| US7（完成標記）| US4 | 批改後才有標記需求 |
| US8（積分+勳章）| US7 | 需要有 completion 記錄才能顯示 |

### Parallel Opportunities Within Phases

**Phase 2**:
```
# 可同時進行（不同檔案）：
T003 create_assignments_table
T004 create_comments_table
T005 create_assignment_completions_table
T006 create_homework_notifications_table
---
T008 Assignment model
T009 Comment model
T010 AssignmentCompletion model
T011 HomeworkNotification model
T012 User model update  ← 注意 T012 與 T013 獨立
T013 Lesson model update
```

**Phase 3**:
```
T014 AssignmentRequest (PHP) 可與 T017 AdminLayout.vue (Vue) 並行
```

**Phase 4**:
```
T020 StoreCommentRequest 可與 T023 AssignmentSection.vue 並行
T023 AssignmentSection.vue 可與 T024 CommentThread.vue 並行
```

**Phase 8**:
```
T038 useNotifications.js 可與 T039 Navigation.vue (desktop) 並行
```

---

## Implementation Strategy

### MVP（Phase 1–5，P1 Stories 完整可用）

1. Phase 2: Foundational（T002–T013）
2. Phase 3: US3 管理員建立作業（T014–T018）→ 驗證後台可運作
3. Phase 4: US1 學員提交作業（T019–T025）→ 驗證前台可提交
4. Phase 5: US4 管理員批改（T026–T028）→ **MVP 完整可交付**

### Incremental Delivery（P2 Stories）

5. Phase 6: US2 學員管理留言（T029–T031）
6. Phase 7: US5 管理員管理留言（T032–T034）
7. Phase 8: US6 通知 Bell（T035–T040）
8. Phase 9: US7 完成標記+積分（T041–T044）
9. Phase 10: US8 積分頁面+勳章（T045–T049）
10. Phase 11: Polish（T050–T054）

---

## Notes

- `[P]` tasks 可與同 Phase 內其他 `[P]` tasks 並行執行（不同檔案，無共同依賴）
- 每個 Phase 完成後做一次手動驗證（參照 Checkpoint）
- `HomeworkController::__construct` 在 Phase 3 就注入 `AssignmentService`（但 service 本體在 Phase 9 才建立）；Phase 3–8 期間 service 尚未使用，不影響執行
- 刪除留言的 DB cascade 由 migration 的 `cascadeOnDelete()` 處理，controller 只需 `$comment->delete()`
- 所有 user-facing 錯誤訊息必須為中文（Constitution §VII）

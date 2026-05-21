# API Contracts: 課程作業與批改系統 (010-lesson-homework)

**Branch**: `010-lesson-homework`  
**Date**: 2026-05-10  
**Updated**: 2026-05-10 - 所有後台寫入操作及學員提交操作改為 Inertia partial reload；submissions 分頁改每頁 10 筆；lessons 回傳新增 chapter_id / chapter_title 欄位
**Updated**: 2026-05-21 - GET /member/classroom/{course} 新增管理員專用 preview_user_id query 參數，以指定學員 ID 查詢作業回覆與完成狀態
**Updated**: 2026-05-10 - Phase 8：notificationCount / notifications 加入 HandleInertiaRequests shared props（所有登入者可見，不限角色）；useNotifications composable 新增；bell 同時實作於 Navigation.vue 及 Classroom.vue header

---

## Routes Overview

All routes added to `routes/web.php`.

### Member Routes (under `middleware('auth')->prefix('member')->name('member.')`)

| Method | Path | Controller | Route Name | Description |
|--------|------|------------|------------|-------------|
| POST | `/member/classroom/{course}/assignment/{assignment}/comments` | `AssignmentCommentController@store` | `member.comments.store` | Submit top-level or reply comment |
| PUT | `/member/classroom/{course}/assignment/{assignment}/comments/{comment}` | `AssignmentCommentController@update` | `member.comments.update` | Edit own comment |
| DELETE | `/member/classroom/{course}/assignment/{assignment}/comments/{comment}` | `AssignmentCommentController@destroy` | `member.comments.destroy` | Delete own comment (cascades replies) |
| POST | `/member/notifications/{notification}/read` | `NotificationController@markRead` | `member.notifications.read` | Mark one notification as read (triggers Inertia reload) |

### Admin Routes (under `middleware(['auth','admin'])->prefix('admin')->name('admin.')`)

| Method | Path | Controller | Route Name | Description |
|--------|------|------------|------------|-------------|
| GET | `/admin/homework` | `Admin\HomeworkController@index` | `admin.homework.index` | Grading dashboard (paginated submissions) |
| POST | `/admin/lessons/{lesson}/assignment` | `Admin\HomeworkController@store` | `admin.homework.store` | Create assignment for a lesson |
| PUT | `/admin/homework/{assignment}` | `Admin\HomeworkController@update` | `admin.homework.update` | Edit assignment content |
| POST | `/admin/homework/{assignment}/publish` | `Admin\HomeworkController@publish` | `admin.homework.publish` | 上架 (restore to published) |
| POST | `/admin/homework/{assignment}/unpublish` | `Admin\HomeworkController@unpublish` | `admin.homework.unpublish` | 下架 (hide from frontend) |
| POST | `/admin/homework/{assignment}/comments` | `Admin\HomeworkController@storeComment` | `admin.homework.comments.store` | Admin reply to a submission |
| PUT | `/admin/homework/{assignment}/comments/{comment}` | `Admin\HomeworkController@updateComment` | `admin.homework.comments.update` | Edit any comment |
| DELETE | `/admin/homework/{assignment}/comments/{comment}` | `Admin\HomeworkController@destroyComment` | `admin.homework.comments.destroy` | Delete any comment (cascades) |
| POST | `/admin/homework/{assignment}/completions/{user}` | `Admin\HomeworkController@markComplete` | `admin.homework.completions.store` | Mark student as completed |

---

## Detailed Request/Response Contracts

### Classroom Integration (ClassroomController changes)

**架構說明**：assignment 與 student comments 的查詢在 `show()` 完成後，作為參數傳入 `formatLessonFull()`，而非在 format 方法內部執行 DB 查詢。這維持 format 方法的純整形職責，與現有 `formatLesson()` 的設計一致。

```php
// 在 show() 內查好再傳入
$assignment = $currentLesson?->assignment()->published()->first();
$assignmentComments = ($assignment && !$isFreePreview)
    ? $assignment->comments()->where('user_id', $user->id)->with('replies.user')->get()
    : collect();
$isAssignmentCompleted = $assignment
    ? $assignment->completions()->where('user_id', $user->id)->exists()
    : false;

$this->formatLessonFull($currentLesson, ..., $assignment, $assignmentComments, $isAssignmentCompleted);
```

**Query params**:
- `lesson_id` (optional): select which lesson to display
- `preview_user_id` (optional, **admin only**): when present and the logged-in user is admin, fetch `assignment_comments` and `assignment_completions` for the specified student ID instead of the admin's own. Non-admin users: parameter is ignored.

**Modified response**: `GET /member/classroom/{course}?lesson_id={id}` now includes in `currentLesson`:

```json
{
  "currentLesson": {
    "id": 42,
    "title": "Lesson Title",
    "...existing fields...",
    "assignment": {
      "id": 7,
      "md_content": "## 本週作業\n\n請分析...",
      "is_completed": false
    },
    "assignment_comments": [
      {
        "id": 100,
        "content": "我的作業答案...",
        "is_edited": false,
        "created_at": "2026-05-10T10:00:00Z",
        "user": { "id": 5, "nickname": "學員A", "is_admin": false },
        "replies": [
          {
            "id": 101,
            "content": "講師回覆：很好！",
            "is_edited": false,
            "created_at": "2026-05-10T11:00:00Z",
            "user": { "id": 1, "nickname": "講師", "is_admin": true },
            "replies": []
          }
        ]
      }
    ]
  }
}
```

**When assignment is null or unpublished**: `assignment` key is `null`, `assignment_comments` is `[]`.  
**Preview mode**: Assignment section not shown (assignment always `null`).

---

### Shared Props (HandleInertiaRequests)

Strategy A：`notificationCount`（整數）與 `notifications`（最近 5 筆）都以 lazy closure 加入 shared props，每次 Inertia navigation 自動刷新。Bell 開啟時直接渲染 shared props，不需額外 fetch；mark-read 使用 `router.post()` 觸發頁面 reload，刷新 shared props 的未讀數與清單。這與 `cartCount` 的設計一致，且不需要額外的 axios 呼叫。

Added to every page for authenticated non-admin users:

```json
{
  "notificationCount": 2,
  "notifications": [
    {
      "id": 15,
      "type": "reply",
      "course_name": "Python 入門",
      "course_id": 3,
      "lesson_id": 42,
      "is_read": false,
      "message": "講師已回覆您在【Python 入門】的作業/提問！",
      "created_at": "2026-05-10T11:00:00Z"
    }
  ]
}
```

Admin users: `notificationCount` 為 `0`，`notifications` 為 `[]`（`HomeworkNotification` 只建立給學員）。

---

### `POST /member/classroom/{course}/assignment/{assignment}/comments`

**Authorization**: `auth` middleware + `{course}` route model binding 提供課程 context，controller 使用 `$course->hasAccessForUser($user)` 驗證存取權。路由命名與現有 `/member/classroom/{course}/progress/{lesson}` 保持一致。

**Request body**:
```json
{
  "content": "我的作業回答...",
  "parent_id": null
}
```
Or for a reply:
```json
{
  "content": "追加補充...",
  "parent_id": 100
}
```

**Validation**（`StoreCommentRequest`）:
- `content`: required, string, max 5000 chars
- `parent_id`: nullable, must exist in `comments` table with same `assignment_id`, must be top-level (no nesting beyond 2 levels)

**Response (success)**: Inertia redirect back with flash `['success' => '作業已提交']`。

**Response (error)**: `withErrors(['content' => '...'])` 或 403。

---

### `PUT /member/classroom/{course}/assignment/{assignment}/comments/{comment}`

**Authorization**: `auth` + comment ownership (`$comment->user_id === $user->id`).

**Request body**:
```json
{ "content": "修改後的內容..." }
```

**Response**: Inertia redirect back with flash `['success' => '已更新']`.

**Error**: 403 if not owner.

---

### `DELETE /member/classroom/{course}/assignment/{assignment}/comments/{comment}`

**Authorization**: `auth` + comment ownership.

**Response**: Inertia redirect back. DB cascade deletes replies automatically.

**Error**: 403 if not owner.

---

### `POST /member/notifications/{notification}/read`

**說明**：通知清單不需要獨立 GET 端點——清單已包含在 shared props `notifications` 中，每次 Inertia navigation 自動更新。此 POST 端點只負責標記單筆通知為已讀，觸發 Inertia reload 刷新 shared props。

**Authorization**: `auth` + ownership (`$notification->user_id === $user->id`).

**Request body**: (empty)

**Response**: Inertia redirect back（觸發 shared props reload，bell 自動更新未讀數）。

---

### `GET /admin/homework`

**Authorization**: `auth + admin` middleware.

**Query params**:
- `course_id` (optional): filter by course
- `lesson_id` (optional): filter by lesson
- `page` (optional): pagination page number

**Inertia response props**:
```json
{
  "submissions": {
    "data": [
      {
        "id": 100,
        "content": "學員的作業...",
        "is_edited": false,
        "created_at": "2026-05-10T10:00:00Z",
        "assignment": {
          "id": 7,
          "md_content": "## 作業題目...",
          "is_published": true,
          "lesson": {
            "id": 42,
            "title": "第一節：基礎概念",
            "course": { "id": 3, "name": "Python 入門" }
          }
        },
        "user": { "id": 5, "nickname": "學員A", "email": "a@test.com" },
        "replies": [
          {
            "id": 101,
            "content": "講師回覆...",
            "is_edited": false,
            "created_at": "2026-05-10T11:00:00Z",
            "user": { "id": 1, "nickname": "講師", "is_admin": true }
          }
        ],
        "completion": null
      }
    ],
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 45
  },
  "courses": [{ "id": 3, "name": "Python 入門" }],
  "filters": { "course_id": null, "lesson_id": null },
  "lessons": [
    {
      "id": 42,
      "title": "第一節：基礎概念",
      "chapter_id": 5,
      "chapter_title": "第一章：入門",
      "chapter_sort_order": 1
    }
  ]
}
```

When a student has been marked complete, `"completion"` contains:
```json
{ "id": 8, "created_at": "2026-05-10T12:00:00Z" }
```

---

### `POST /admin/lessons/{lesson}/assignment`

**Form Request**: `AssignmentRequest`（store/update 共用同一個 Request class，因唯一欄位為 `md_content`，無需分開）

**Request body**:
```json
{ "md_content": "## 本週作業\n\n..." }
```

**Response**: `redirect()->back()` with flash `['success' => '題目已建立']`。前端使用 `only: ['assignmentsMap', 'flash']` partial reload，篩選狀態保留。

---

### `PUT /admin/homework/{assignment}`

**Form Request**: `AssignmentRequest`（同上）

**Request body**:
```json
{ "md_content": "## 更新後的題目..." }
```

**Response**: Redirect back with flash `['success' => '題目已更新']`.

---

### `POST /admin/homework/{assignment}/unpublish`

**Response**: Redirect back with flash `['success' => '題目已下架']`.

### `POST /admin/homework/{assignment}/publish`

**Response**: Redirect back with flash `['success' => '題目已上架']`.

---

### `POST /admin/homework/{assignment}/comments`

**Request body**:
```json
{
  "content": "講師批改內容...",
  "parent_id": 100
}
```

**Response**: Redirect back with flash `['success' => '回覆已送出']`. Triggers automatic notification creation for the student.

---

### `POST /admin/homework/{assignment}/completions/{user}`

**Authorization**: `auth + admin`.

**Request body**: (empty)

**Response (success)**: Redirect back with flash `['success' => '已標記完成，積分 +100']`.

**Response (already completed)**:
```json
{ "error": "此學員的作業已標記為完成" }
```
HTTP 422 / redirect back with errors.

**Side effects** (executed in `AssignmentService::markComplete()`):
1. Create `AssignmentCompletion` record (`assignment_id`, `user_id`)
2. `User::increment('points', 100)` on the student
3. Create `HomeworkNotification` record for the student (`type=completion`)

---

### Settings Page (Member\SettingsController changes)

**Modified response**: `GET /member/settings` now includes in Inertia props:

```json
{
  "user": { "...existing fields...", "points": 250 },
  "orders": [...],
  "completions": [
    {
      "id": 8,
      "course_name": "Python 入門",
      "lesson_title": "第一節：基礎概念",
      "points_awarded": 100,
      "completed_at": "2026-05-10T12:00:00Z"
    }
  ]
}
```

Ordered newest-first (`assignment_completions.created_at DESC`).

---

### Admin Member Detail (Admin\MemberController changes)

**Modified response**: `GET /admin/members/{member}` includes additional prop:

```json
{
  "homework_completions": [
    {
      "course_name": "Python 入門",
      "lesson_title": "第一節：基礎概念",
      "completed_at": "2026-05-10T12:00:00Z"
    }
  ],
  "points": 250
}
```

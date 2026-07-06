# Quickstart: 課程作業與批改系統 (010-lesson-homework)

**Branch**: `010-lesson-homework`  
**Date**: 2026-05-10

---

## Prerequisites

```bash
git checkout 010-lesson-homework
```

Confirm you're on the right branch and the database is up to date.

---

## Step 1 — Run Migrations

```bash
php artisan migrate
```

This adds 5 migrations in order:
1. `add_points_to_users_table` — adds `points` integer column (default 0) to `users`
2. `create_assignments_table` — one assignment per lesson, Markdown content, publish flag
3. `create_comments_table` — 2-level nested comments (student submissions + admin replies)
4. `create_assignment_completions_table` — write-once record, unique (assignment, user)
5. `create_homework_notifications_table` — bell notifications (reply + completion types); 命名為 `homework_notifications` 避免與 Laravel Notifiable 命名混淆

---

## Step 2 — Verify New Models

```bash
php artisan tinker
>>> App\Models\Assignment::first()            # should return null (no data yet)
>>> App\Models\Comment::first()               # should return null
>>> App\Models\HomeworkNotification::first()  # should return null
```

---

## Step 3 — Manual Testing Flow (Happy Path)

### Admin side:
1. Log in as admin, go to `/admin/homework`
2. Confirm new sidebar link "作業批改專區" appears in AdminLayout
3. Click "新增題目" for any lesson → enter Markdown → save
4. Confirm the assignment appears in the grading dashboard

### Student side:
5. Log in as a student who owns the course with the lesson you just added an assignment to
6. Go to `/member/classroom/{course}?lesson_id={lesson_id}`
7. Confirm assignment section appears below the video/content, correctly rendering Markdown
8. Submit a text response — confirm it appears in the list with Markdown rendering
9. Edit the submission → confirm "已編輯" badge appears
10. Delete the submission → confirm it disappears

### Admin grading:
11. Back as admin in `/admin/homework`, confirm the student's submission appears
12. Click Reply → enter feedback → save
13. Confirm student sees the reply in their classroom

### Notification bell:
14. As the student, navigate to any page
15. Confirm bell icon appears in nav bar with red badge (unread count ≥ 1)
16. Click bell → confirm notification dropdown shows with correct message format
17. Click the notification → confirm it navigates to the correct classroom lesson and marks as read

### Completion & Points:
18. As admin, click "標記已完成" on the student's submission
19. Confirm the button disappears and "✓ 已完成" label appears
20. As student, go to `/member/settings` → confirm `+100` points and completion record appear
21. Go back to the classroom lesson → confirm green checkmark badge on assignment block
22. As admin, go to `/admin/members/{student-id}` → confirm homework completion shows in member detail

---

## Step 4 — Edge Case Tests

```bash
# Try accessing another student's comments (should 403)
curl -X GET /member/assignment/1/comments -u student_b

# Try marking same completion twice (should 422)
POST /admin/homework/1/completions/5  # first call
POST /admin/homework/1/completions/5  # second call → error

# Unpublish assignment — student should see empty page (no assignment section)
POST /admin/homework/1/unpublish
# Login as student, visit the lesson → confirm no assignment block
```

---

## Key Files to Know

### New Files (create from scratch)

| File | Purpose |
|------|---------|
| `app/Models/Assignment.php` | Assignment model |
| `app/Models/Comment.php` | Comment model (2-level threading) |
| `app/Models/AssignmentCompletion.php` | Completion record model |
| `app/Models/HomeworkNotification.php` | Homework notification model (table: `homework_notifications`) |
| `app/Services/AssignmentService.php` | `markComplete()` — multi-model side-effects |
| `app/Http/Controllers/Admin/HomeworkController.php` | Admin grading dashboard + assignment CRUD |
| `app/Http/Controllers/Member/AssignmentCommentController.php` | Member comment CRUD |
| `app/Http/Controllers/Member/NotificationController.php` | markRead only（清單改用 shared props，無需 GET 端點）|
| `resources/js/Components/Classroom/AssignmentSection.vue` | Assignment block (question + comments) |
| `resources/js/Components/Classroom/CommentThread.vue` | 2-level comment thread UI |
| `resources/js/composables/useNotifications.js` | Notification state + API calls |
| `resources/js/Pages/Admin/Homework/Index.vue` | Admin grading dashboard page |

### Edited Files

| File | Change |
|------|--------|
| `app/Http/Controllers/Member/ClassroomController.php` | `formatLessonFull()` includes assignment + comments |
| `app/Http/Controllers/Member/SettingsController.php` | Add points + completions to props |
| `app/Http/Controllers/Admin/MemberController.php` | Add homework completions to member show |
| `app/Http/Middleware/HandleInertiaRequests.php` | Add `notificationCount` + `notifications` (top 5) to shared props |
| `app/Models/User.php` | Add `points` to fillable + new relationships |
| `app/Models/Lesson.php` | Add `assignment()` HasOne relationship |
| `resources/js/Components/Layout/Navigation.vue` | Add notification bell (desktop + mobile) |
| `resources/js/Layouts/AdminLayout.vue` | Add "作業批改專區" nav item |
| `resources/js/Pages/Member/Classroom.vue` | Render `AssignmentSection` when lesson has assignment |
| `resources/js/Pages/Member/Settings.vue` | Add points total + completion history section |
| `resources/js/Pages/Admin/Members/Index.vue` | Show homework completions in member detail modal |
| `routes/web.php` | Add member comment routes + notification routes + admin homework routes |

---

## Markdown Rendering Notes

Assignment questions and student submissions both use `marked.js` (already installed).

For the assignment question block, wrap the `HtmlContent` component output in a container with isolated styles. Use Tailwind `prose` utilities or explicit utility classes — do NOT use a custom CSS file (per constitution). Minimum required elements per FR-001:

```vue
<div class="assignment-question prose prose-sm max-w-none 
            [&_h3]:text-base [&_h3]:font-semibold [&_h4]:text-sm [&_h4]:font-semibold
            [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5
            [&_a]:text-blue-600 [&_a]:underline [&_blockquote]:border-l-4 
            [&_blockquote]:border-gray-300 [&_blockquote]:pl-4 [&_blockquote]:text-gray-600
            [&_strong]:font-bold">
  <HtmlContent :content="assignment.md_content" />
</div>
```

---

## Notification Pattern (Strategy A)

Bell count 和通知清單都放進 shared props（lazy closures），不需獨立 GET 端點。

`HandleInertiaRequests::share()` — add after existing `cartCount`:

```php
// Bell badge 用
'notificationCount' => fn () => $user && !$user->isAdmin()
    ? \App\Models\HomeworkNotification::where('user_id', $user->id)
        ->where('is_read', false)->count()
    : 0,

// Bell dropdown 直接渲染，零額外 fetch
'notifications' => fn () => $user && !$user->isAdmin()
    ? \App\Models\HomeworkNotification::where('user_id', $user->id)
        ->orderByDesc('created_at')->limit(5)->get()
        ->map(fn ($n) => [
            'id'          => $n->id,
            'type'        => $n->type,
            'course_name' => $n->course_name,
            'course_id'   => $n->course_id,
            'lesson_id'   => $n->lesson_id,
            'is_read'     => $n->is_read,
            'message'     => $n->type === 'reply'
                ? "講師已回覆您在【{$n->course_name}】的作業/提問！"
                : "恭喜！您在【{$n->course_name}】的作業已通過，積分 +100！",
            'created_at'  => $n->created_at->toIso8601String(),
        ])
    : [],
```

`useNotifications.js` — follow the same pattern as `useCart.js`:
- Module-level `ref(page.props.notificationCount)` 和 `ref(page.props.notifications)`
- `watch(() => page.props.notificationCount, ...)` 在 navigation 後同步
- Bell 開啟直接讀取 `notifications` ref，零額外請求
- `markRead(id)` → `router.post(route('member.notifications.read', id))` → Inertia reload 自動刷新兩個 props

---

## DB Naming Checklist

- `assignments` table, `Assignment` model ✓
- `comments` table, `Comment` model ✓
- `assignment_completions` table, `AssignmentCompletion` model ✓
- `homework_notifications` table, `HomeworkNotification` model ✓

# Data Model: 課程作業與批改系統 (010-lesson-homework)

**Branch**: `010-lesson-homework`  
**Date**: 2026-05-10

---

## Schema Changes Overview

| Change | Table | Type |
|--------|-------|------|
| Add `points` column | `users` | ALTER |
| New table | `assignments` | CREATE |
| New table | `comments` | CREATE |
| New table | `assignment_completions` | CREATE |
| New table | `homework_notifications` | CREATE |

---

## Migrations (in order)

### 1. `2026_05_10_000001_add_points_to_users_table`

```php
Schema::table('users', function (Blueprint $table) {
    $table->unsignedInteger('points')->default(0)->after('role');
});
```

### 2. `2026_05_10_000002_create_assignments_table`

```php
Schema::create('assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('lesson_id')->unique()->constrained()->cascadeOnDelete();
    $table->text('md_content');
    $table->boolean('is_published')->default(true);
    $table->timestamps();
});
```

**Notes**:
- `lesson_id` has a `unique()` constraint — one assignment per lesson.
- `cascadeOnDelete()` on `lesson_id`: if the lesson is deleted, the assignment is also deleted. (Lessons are rarely deleted but the constraint keeps DB clean.)
- `is_published = false` → 下架: hidden from frontend, visible in admin.

### 3. `2026_05_10_000003_create_comments_table`

```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
    $table->text('content');
    $table->boolean('is_edited')->default(false);
    $table->timestamps();
});
```

**Notes**:
- `parent_id` null → top-level student submission; non-null → reply (admin batch or student follow-up).
- `cascadeOnDelete` on `parent_id`: deleting a top-level comment automatically deletes its replies at DB level.
- `cascadeOnDelete` on `assignment_id`: unpublishing an assignment does NOT delete comments (assignment record stays in DB). Only if the assignment row itself were deleted (which the spec says doesn't happen) would comments cascade.
- No soft delete — comments are hard-deleted per spec.

### 4. `2026_05_10_000004_create_assignment_completions_table`

```php
Schema::create('assignment_completions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('created_at');
    // Unique constraint: one completion per (student, assignment)
    $table->unique(['assignment_id', 'user_id']);
});
```

**Notes**:
- Only `created_at`, no `updated_at` (record is write-once, like `LessonProgress`).
- `$timestamps = false` on the model with manual `created_at = now()` in `booted()`.
- `unique(['assignment_id', 'user_id'])` enforces the "only once per student per assignment" rule at DB level.

### 5. `2026_05_10_000005_create_homework_notifications_table`

```php
Schema::create('homework_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['reply', 'completion']);
    $table->string('course_name');         // snapshot at creation time
    $table->unsignedBigInteger('course_id'); // snapshot for route binding (no FK — course may be renamed)
    $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
    $table->boolean('is_read')->default(false);
    $table->timestamps();
});
```

**Notes**:
- Table named `homework_notifications`（非 `notifications`）以避免與 Laravel 內建 `Illuminate\Notifications\Notification` 命名空間混淆，`User` model 已使用 `Notifiable` trait，任何同時 import 兩個 Notification 的檔案都需要 alias，長期維護困難。
- `course_name` 為建立當下的快照，不用 FK，確保課程改名後通知訊息不受影響。
- `course_id` 同樣為快照（`unsignedBigInteger`，無 FK），供前台通知點擊跳轉至 `/member/classroom/{course}?lesson_id=...` 時使用。不設 FK 是因為即使課程被刪除，通知記錄本身可保留（`lesson_id` 的 cascade 已處理連動刪除）。
- `lesson_id` FK 供點擊跳轉用。
- 顯示上限為最近 5 筆（query 的 limit，非儲存上限）。
- `is_read` 需要更新 → 保留 `updated_at`。

---

## Model Definitions

### `Assignment` (`app/Models/Assignment.php`)

```php
protected $fillable = ['lesson_id', 'md_content', 'is_published'];

protected function casts(): array {
    return ['is_published' => 'boolean'];
}

public function lesson(): BelongsTo {
    return $this->belongsTo(Lesson::class);
}

public function comments(): HasMany {
    return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at');
}

public function completions(): HasMany {
    return $this->hasMany(AssignmentCompletion::class);
}

public function isCompletedBy(User $user): bool {
    return $this->completions()->where('user_id', $user->id)->exists();
}

public function scopePublished(Builder $query): Builder {
    return $query->where('is_published', true);
}
```

### `Comment` (`app/Models/Comment.php`)

```php
protected $fillable = ['assignment_id', 'user_id', 'parent_id', 'content', 'is_edited'];

protected function casts(): array {
    return ['is_edited' => 'boolean'];
}

public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}

public function assignment(): BelongsTo {
    return $this->belongsTo(Assignment::class);
}

public function parent(): BelongsTo {
    return $this->belongsTo(Comment::class, 'parent_id');
}

public function replies(): HasMany {
    return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
}

public function scopeTopLevel(Builder $query): Builder {
    return $query->whereNull('parent_id');
}

public function isOwnedBy(User $user): bool {
    return $this->user_id === $user->id;
}
```

### `AssignmentCompletion` (`app/Models/AssignmentCompletion.php`)

```php
public $timestamps = false;

protected $fillable = ['assignment_id', 'user_id'];

// 沿用 LessonProgress 的 boot() 寫法，保持一致
protected static function boot(): void {
    parent::boot();

    static::creating(function ($model) {
        $model->created_at = $model->freshTimestamp();
    });
}

public function assignment(): BelongsTo {
    return $this->belongsTo(Assignment::class);
}

public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}
```

### `HomeworkNotification` (`app/Models/HomeworkNotification.php`)

Table: `homework_notifications`

```php
protected $table = 'homework_notifications';

protected $fillable = ['user_id', 'type', 'course_name', 'course_id', 'lesson_id', 'is_read'];

protected function casts(): array {
    return ['is_read' => 'boolean'];
}

public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}

public function lesson(): BelongsTo {
    return $this->belongsTo(Lesson::class);
}

public function scopeForUser(Builder $query, int $userId): Builder {
    return $query->where('user_id', $userId);
}

public function scopeUnread(Builder $query): Builder {
    return $query->where('is_read', false);
}
```

---

## Updated Existing Models

### `User` additions

```php
// Add to $fillable:
'points',

// Add to casts():
// (none needed — points is integer, default handling is fine)

// Add relationships:
public function assignmentCompletions(): HasMany {
    return $this->hasMany(AssignmentCompletion::class);
}

public function homeworkNotifications(): HasMany {
    return $this->hasMany(HomeworkNotification::class);
}
```

### `Lesson` additions

```php
// Add relationship:
public function assignment(): HasOne {
    return $this->hasOne(Assignment::class);
}
```

---

## Entity Relationship Summary

```
Lesson
└── hasOne → Assignment
              ├── hasMany → Comment (parent_id = null = top-level)
              │             └── hasMany → Comment (replies, parent_id = comments.id)
              └── hasMany → AssignmentCompletion

User
├── hasMany → AssignmentCompletion
├── hasMany → HomeworkNotification
└── points (integer column on users table)
```

---

## Indexes

| Table | Index | Type | Reason |
|-------|-------|------|--------|
| `assignments` | `lesson_id` | UNIQUE | One assignment per lesson |
| `comments` | `assignment_id` | INDEX | Filter comments by assignment |
| `comments` | `user_id` | INDEX | Filter comments by student |
| `comments` | `parent_id` | INDEX | Fetch replies efficiently |
| `assignment_completions` | `(assignment_id, user_id)` | UNIQUE | Idempotency + fast lookup |
| `assignment_completions` | `user_id` | INDEX | Student history query |
| `homework_notifications` | `user_id` | INDEX | Fetch user's notifications |
| `homework_notifications` | `(user_id, is_read)` | INDEX | Unread count query |

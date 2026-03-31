# Data Model: Member Management

**Feature**: 003-member-management
**Date**: 2026-01-17
**Updated**: 2026-01-18
**Updated**: 2026-03-31 - 對齊目前實作：會員篩選改以 `User::members()` / `isMember()`，課程進度改用 `getCourseProgressSummary()`，擁有權判斷僅計入 `paidStatus()`

## Entities Overview

This feature primarily uses existing entities with minor extensions.

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│      User       │────<│    Purchase     │>────│     Course      │
│   (Member)      │     │                 │     │                 │
└────────┬────────┘     └─────────────────┘     └────────┬────────┘
         │                                               │
         │              ┌─────────────────┐              │
         └─────────────<│ LessonProgress  │>─────────────┤
                        │                 │              │
                        └─────────────────┘     ┌────────┴────────┐
                                                │     Lesson      │
                                                │                 │
                                                └─────────────────┘
```

## Existing Entities (No Schema Changes)

### User (as Member)

Members are users with `role = 'member'`. In code, member-only queries should prefer the shared `User::members()` scope, and member checks should prefer `User::isMember()`.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, auto | Unique identifier |
| email | varchar(255) | UNIQUE, NOT NULL | Email address (editable) |
| nickname | varchar(100) | NULLABLE | Display name (editable) |
| real_name | varchar(100) | NULLABLE | Legal name (editable) |
| phone | varchar(20) | NULLABLE | Phone number (editable) |
| birth_date | date | NULLABLE | Birthday (editable) |
| role | enum | NOT NULL, default 'member' | User role (member/editor/admin) |
| last_login_at | timestamp | NULLABLE | Last login timestamp |
| last_login_ip | varchar(45) | NULLABLE | Last login IP address |
| created_at | timestamp | NOT NULL | Registration timestamp |
| updated_at | timestamp | NOT NULL | Last update timestamp |

**Validation Rules**:
- `email`: Valid email format, unique across all users
- `nickname`: Max 100 characters
- `real_name`: Max 100 characters
- `phone`: Max 20 characters
- `birth_date`: Valid date, not in future

**Relationships**:
- `hasMany` Purchase
- `hasMany` LessonProgress

---

### Purchase

Links members to courses they own (via purchase or gift).

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, auto | Unique identifier |
| user_id | bigint | FK → users.id | Purchasing member |
| course_id | bigint | FK → courses.id | Purchased course |
| status | varchar | NOT NULL | Purchase status |
| type | varchar | NOT NULL, default 'paid' | How course was obtained: 'paid', 'gift', or 'system_assigned' |
| created_at | timestamp | NOT NULL | Purchase timestamp |

**Relationships**:
- `belongsTo` User
- `belongsTo` Course

**Note**: Course ownership is determined by `status`, not `type`. Only purchases in paid status grant active access; `type` only distinguishes how the course was obtained.

---

### Course

Course entity (read-only for this feature, but description used in gift emails).

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK | Course identifier |
| name | varchar | NOT NULL | Course name |
| description | text | NULLABLE | Course description (used in gift notification email) |

**Relationships**:
- `hasMany` Purchase
- `hasMany` Lesson

**Note**: The `description` field is used in gift notification emails. If empty, display "（無課程簡介）" as placeholder.

---

### LessonProgress

Tracks lesson completion per user.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK, auto | Unique identifier |
| user_id | bigint | FK → users.id | Member who completed |
| lesson_id | bigint | FK → lessons.id | Completed lesson |
| created_at | timestamp | NOT NULL | Completion timestamp |

**Constraints**:
- UNIQUE(user_id, lesson_id) - one completion per member per lesson

**Relationships**:
- `belongsTo` User
- `belongsTo` Lesson

---

### Lesson

Lesson entity (read-only for this feature).

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint | PK | Lesson identifier |
| course_id | bigint | FK → courses.id | Parent course |
| title | varchar | NOT NULL | Lesson title |

**Relationships**:
- `belongsTo` Course
- `hasMany` LessonProgress

---

## Computed/Virtual Attributes

### User Model Extensions

```php
// Shared summary for member/admin progress views
public function getCourseProgressSummary(Course $course, ?array $completedLessonIds = null): array
{
    // returns:
    // [
    //   'completed_lessons' => int,
    //   'total_lessons' => int,
    //   'progress_percent' => int,
    // ]
}

// Backward-compatible convenience wrapper
public function getCourseProgress(Course $course): int
{
    return $this->getCourseProgressSummary($course)['progress_percent'];
}

// Member predicates used by admin/member flows
public function isMember(): bool
{
    return $this->role === 'member';
}

public function scopeMembers(Builder $query): Builder
{
    return $query->where('role', 'member');
}

public function lessonProgress(): HasMany
{
    return $this->hasMany(LessonProgress::class);
}
```

---

## Query Patterns

### Member List with Filters

```php
// Paginated member list with search and course filter
User::query()
    ->members()
    ->when($search, fn ($q) =>
        $q->where('email', 'like', "%{$search}%")
          ->orWhere('real_name', 'like', "%{$search}%")
          ->orWhere('nickname', 'like', "%{$search}%")
    )
    ->when($courseId, fn ($q) =>
        $q->whereHas('purchases', fn ($q) =>
            $q->where('course_id', $courseId)
        )
    )
    ->orderBy($sortField, $sortDirection)
    ->paginate(50);
```

### Member Courses with Progress

```php
// Eager load courses with progress calculation
$member->purchases()
    ->with('course.lessons')
    ->paidStatus()
    ->get()
    ->values();

$progressMap = $member->lessonProgress()
    ->pluck('lesson_id')
    ->flip()
    ->toArray();

$courses->map(function ($purchase) use ($member, $progressMap) {
    $progress = $member->getCourseProgressSummary($purchase->course, $progressMap);

    return [
        'course_id' => $purchase->course_id,
        'course_name' => $purchase->course->name,
        'acquisition_type' => in_array($purchase->type, ['gift', 'system_assigned']) ? 'gift' : 'paid',
        'total_lessons' => $progress['total_lessons'],
        'completed_lessons' => $progress['completed_lessons'],
        'progress_percent' => $progress['progress_percent'],
        'purchased_at' => $purchase->created_at,
    ];
});
```

### Count Members Matching Filter

```php
// For "Select all X matching members" display
$count = User::members()
    ->when($search, /* same as above */)
    ->when($courseId, /* same as above */)
    ->count();
```

---

## Indexes

Existing indexes are sufficient:

- `users.email` - UNIQUE index (email lookup, uniqueness validation)
- `users.role` - INDEX (role filtering)
- `purchases.user_id` - INDEX (user's courses lookup)
- `purchases.course_id` - INDEX (course filter)
- `lesson_progress.user_id` - INDEX (user's progress)
- `lesson_progress.lesson_id` - INDEX (lesson completion check)

---

## Data Integrity

### Validation Constraints

| Entity | Field | Rule |
|--------|-------|------|
| User | email | required, email, unique:users,email,{id} |
| User | nickname | nullable, max:100 |
| User | real_name | nullable, max:100 |
| User | phone | nullable, max:20 |
| User | birth_date | nullable, date, before_or_equal:today |

### Business Rules

1. Only users matching `User::members()` appear in member management
2. Email must remain unique across all users (including admins)
3. Members cannot be deleted if they have purchases (soft delete consideration for future)
4. Progress calculation treats non-existent lesson_progress as 0% complete
5. A member can only have one purchase record per course (prevents duplicate gifts)
6. Purchase types: `type='paid'` (normal purchase), `type='gift'` (admin gifted), `type='system_assigned'` (auto-assigned to admin)
7. Active course ownership is based on `Purchase.status = paid`; `type` only affects UI labels such as gifted vs purchased

---

## Gift Course Query Patterns

### Check Existing Ownership Before Gifting

```php
// Filter out members who already own the course
$membersToGift = User::whereIn('id', $memberIds)
    ->members()
    ->whereDoesntHave('purchases', fn ($q) =>
        $q->where('course_id', $courseId)
          ->paidStatus()
    )
    ->get();
```

### Create Gift Purchase

```php
// Create purchase with gift type
Purchase::create([
    'user_id' => $memberId,
    'course_id' => $courseId,
    'buyer_email' => $member->email ?? '',
    'amount' => 0,
    'currency' => 'TWD',
    'type' => 'gift',
    'status' => 'paid',
]);
```

### Count Already Owned

```php
// Count members who already own the course
$alreadyOwnedCount = User::whereIn('id', $memberIds)
    ->members()
    ->whereHas('purchases', fn ($q) =>
        $q->where('course_id', $courseId)
          ->paidStatus()
    )
    ->count();
```

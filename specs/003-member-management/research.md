# Research: Member Management

**Feature**: 003-member-management
**Date**: 2026-01-17
**Status**: Complete

## Research Topics

### 1. Inline Editing Pattern in Vue 3 + Inertia.js

**Decision**: Use Inertia.js partial reloads with optimistic UI updates

**Rationale**:
- Inertia's `router.patch()` with `preserveScroll: true` enables smooth inline edits
- Optimistic updates show changes immediately while request processes
- On validation error, revert to original value and show error message
- Existing codebase uses similar pattern for course updates

**Alternatives Considered**:
- Full page reload after edit: Rejected - poor UX, loses scroll position
- Separate API with fetch(): Rejected - breaks Inertia's state management
- Real-time WebSocket updates: Rejected - over-engineering for admin panel

**Implementation Pattern**:
```vue
// Inline edit cell component pattern
const editing = ref(false)
const editValue = ref('')

function save() {
  router.patch(route('admin.members.update', member.id), {
    [field]: editValue.value
  }, {
    preserveScroll: true,
    onSuccess: () => editing.value = false,
    onError: (errors) => { /* show validation error */ }
  })
}
```

---

### 2. Batch Email with Laravel Queues

**Decision**: Use Laravel's database queue with chunked job dispatching

**Rationale**:
- Database queue already configured (`QUEUE_CONNECTION=database`)
- Resend.com integration exists via `resend/resend-laravel` package
- Chunk recipients into batches of 50 to avoid memory issues
- Each chunk runs as separate job for better failure isolation

**Alternatives Considered**:
- Sync email sending: Rejected - would timeout for 500+ recipients
- Redis queue: Rejected - not configured, database queue sufficient
- Single job for all emails: Rejected - failure affects all recipients

**Implementation Pattern**:
```php
// Dispatch batch emails in chunks
$memberIds->chunk(50)->each(function ($chunk) use ($subject, $body) {
    SendBatchEmailJob::dispatch($chunk->toArray(), $subject, $body);
});
```

---

### 3. Cross-Page Selection State in Vue

**Decision**: Store selected member IDs in Vue reactive state with Pinia-like pattern

**Rationale**:
- Inertia page navigation loses component state by default
- Need persistent selection across pagination
- Use Inertia's `preserveState` option combined with props
- Store selection IDs, not full objects (memory efficient)

**Alternatives Considered**:
- URL query params: Rejected - unwieldy for many selections
- LocalStorage: Rejected - persists beyond session, causes confusion
- Pinia store: Rejected - overkill for single page feature

**Implementation Pattern**:
```vue
// Pass selection back to server, return in props
const selectedIds = ref(props.selectedIds || [])

function changePage(page) {
  router.get(route('admin.members.index'), {
    page,
    selected: selectedIds.value, // Preserve selection
    ...filters
  }, { preserveState: true })
}
```

---

### 4. Course Progress Calculation

**Decision**: Calculate progress as aggregate query at list time, cache in member detail modal

**Rationale**:
- Progress = (completed lessons count) / (total lessons count) × 100
- Use subquery for efficient calculation without N+1
- Only show detailed breakdown in member modal (lazy load)
- Existing `lesson_progress` table tracks completed lessons

**Alternatives Considered**:
- Store calculated progress in database: Rejected - stale data, sync complexity
- Calculate in frontend: Rejected - requires loading all lessons client-side
- Real-time progress column: Rejected - performance hit on list view

**Implementation Pattern**:
```php
// Efficient progress calculation per course
$coursesWithProgress = $member->purchases()
    ->with(['course.lessons'])
    ->get()
    ->map(function ($purchase) use ($member) {
        $total = $purchase->course->lessons->count();
        $completed = $member->lessonProgress()
            ->whereIn('lesson_id', $purchase->course->lessons->pluck('id'))
            ->count();
        return [
            'course' => $purchase->course,
            'progress' => $total > 0 ? round($completed / $total * 100) : 0
        ];
    });
```

---

### 5. Member List Filtering with Course Ownership

**Decision**: Use query builder with `whereHas` for course filter

**Rationale**:
- Laravel's `whereHas` efficiently filters by relationship
- Combine with search and sort in single query
- Paginate results for performance with 10k+ members
- Index on `purchases.user_id` and `purchases.course_id` ensures fast queries

**Alternatives Considered**:
- Load all members then filter in PHP: Rejected - memory/performance issues
- Separate search API: Rejected - complicates state management
- Full-text search (Scout): Rejected - over-engineering for simple search

**Implementation Pattern**:
```php
$query = User::where('role', 'member')
    ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
        $q->where('email', 'like', "%{$search}%")
          ->orWhere('real_name', 'like', "%{$search}%")
          ->orWhere('nickname', 'like', "%{$search}%");
    }))
    ->when($courseId, fn ($q) => $q->whereHas('purchases',
        fn ($q) => $q->where('course_id', $courseId)
    ))
    ->orderBy($sortField, $sortDirection)
    ->paginate(50);
```

---

### 6. Copy-to-Clipboard Implementation

**Decision**: Use Clipboard API with fallback

**Rationale**:
- Modern Clipboard API (`navigator.clipboard.writeText`) is secure and simple
- Provide visual feedback (toast/tooltip) on success
- Graceful fallback for older browsers

**Implementation Pattern**:
```vue
async function copyEmail(email) {
  try {
    await navigator.clipboard.writeText(email)
    showToast('Email 已複製')
  } catch {
    // Fallback for older browsers
    const textarea = document.createElement('textarea')
    textarea.value = email
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    showToast('Email 已複製')
  }
}
```

---

### 7. Gift Course Implementation Pattern

**Decision**: Reuse batch email pattern with queued job for gift processing

**Rationale**:
- Gift course follows same pattern as batch email (select members → perform action)
- Use chunked job dispatching (50 members per chunk) for consistency
- Create Purchase records with `type='gift'` to distinguish from paid purchases (type='paid') and system-assigned (type='system_assigned')
- Queue notification emails alongside purchase creation for atomicity
- Existing Resend.com integration handles gift notification emails

**Alternatives Considered**:
- Sync gift processing: Rejected - same timeout issues as batch email for 500+ recipients
- Separate Gift model: Rejected - Purchase model already represents course ownership, just add source field
- Custom email per member: Rejected - fixed template is sufficient per spec clarification

**Implementation Pattern**:
```php
// Dispatch gift course in chunks
$memberIds->chunk(50)->each(function ($chunk) use ($courseId) {
    GiftCourseJob::dispatch($chunk->toArray(), $courseId);
});

// In job: create purchases and queue emails
foreach ($memberIds as $memberId) {
    $member = User::find($memberId);

    // Skip if already owns course
    if ($member->purchases()->where('course_id', $courseId)->exists()) {
        $this->alreadyOwnedCount++;
        continue;
    }

    // Create gift purchase
    Purchase::create([
        'user_id' => $memberId,
        'course_id' => $courseId,
        'buyer_email' => $member->email ?? '',
        'amount' => 0,
        'currency' => 'TWD',
        'type' => 'gift',
        'status' => 'paid',
    ]);

    // Queue notification email (if member has email)
    if ($member->email) {
        Mail::to($member)->queue(new CourseGiftedMail($course));
    }
}
```

---

### 8. Course Description for Gift Email

**Decision**: Eager load course with description for email template

**Rationale**:
- Course model already has `description` field (used on course detail page)
- Load course once in job, pass to all email instances
- Handle empty description with placeholder text "（無課程簡介）"
- Email template uses Blade with simple variables (course name, description, site URL)

**Implementation Pattern**:
```php
// In GiftCourseJob
$course = Course::findOrFail($courseId);
$description = $course->description ?: '（無課程簡介）';

// Pass to mailable
new CourseGiftedMail($course->name, $description);
```

---

## Summary

All technical decisions align with:
- Existing codebase patterns (Laravel + Inertia.js)
- Constitution principles (simplicity, Laravel conventions)
- Performance requirements (<2s load, <1min batch email, <1min gift course)

No NEEDS CLARIFICATION items remain. Ready for Phase 1 design.

### Update 2026-01-18

Added research topics 7-8 for Gift Course feature (User Story 7):
- Gift course reuses batch email chunked job pattern
- Purchase model extended with source field for gift tracking
- Course description handling for notification emails

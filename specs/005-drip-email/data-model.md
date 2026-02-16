# Data Model: Email 連鎖加溫系統

**Feature**: 005-drip-email
**Date**: 2026-02-05
**Updated**: 2026-02-05 (新增 Lesson 促銷區塊)

## Entity Overview

```
┌─────────────┐       ┌──────────────────────┐       ┌─────────────┐
│    User     │──────<│   DripSubscription   │>──────│   Course    │
└─────────────┘       └──────────────────────┘       └─────────────┘
                                                            │
                                                            │ (drip courses only)
                                                            ▼
                                                    ┌───────────────────────┐
                                                    │ DripConversionTarget  │
                                                    └───────────────────────┘
                                                            │
                                                            ▼
                                                    ┌─────────────┐
                                                    │   Course    │ (target courses)
                                                    └─────────────┘
```

## Entities

### 1. Course (MODIFY - existing)

**新增欄位**:

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| course_type | ENUM('standard', 'drip') | NOT NULL, DEFAULT 'standard' | 課程類型 |
| drip_interval_days | INT UNSIGNED | NULLABLE | 發信間隔天數（僅 drip 類型使用）|

**Migration**:
```php
Schema::table('courses', function (Blueprint $table) {
    $table->enum('course_type', ['standard', 'drip'])->default('standard')->after('status');
    $table->unsignedInteger('drip_interval_days')->nullable()->after('course_type');
});

// 確保所有現有課程設為 'standard'（雖然 default 已處理，但明確更新更安全）
DB::table('courses')->whereNull('course_type')->update(['course_type' => 'standard']);
```

**Migration Note**: 現有課程會自動設為 `'standard'` 類型，確保向後相容。

**Model 修改（遵循現有 Pattern）**:
```php
// Course.php - 新增到 $fillable
protected $fillable = [
    // ... existing fields ...
    'course_type',
    'drip_interval_days',
];

// Course.php - 新增到 casts()
protected function casts(): array
{
    return [
        // ... existing casts ...
        'drip_interval_days' => 'integer',
    ];
}

// Course.php - 新增 relationships
public function dripConversionTargets(): HasMany
{
    return $this->hasMany(DripConversionTarget::class, 'drip_course_id');
}

public function dripSubscriptions(): HasMany
{
    return $this->hasMany(DripSubscription::class);
}

// Course.php - 新增 accessor（參考 isPromoActive）
protected function isDrip(): Attribute
{
    return Attribute::make(
        get: fn () => $this->course_type === 'drip'
    );
}

// Course.php - 新增 scope
public function scopeDrip(Builder $query): Builder
{
    return $query->where('course_type', 'drip');
}
```

---

### 2. DripSubscription (NEW)

**Description**: 記錄使用者對連鎖課程的訂閱狀態

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | 訂閱者 |
| course_id | BIGINT UNSIGNED | FK → courses.id, NOT NULL | 連鎖課程 |
| subscribed_at | TIMESTAMP | NOT NULL | 訂閱時間（解鎖計算基準）|
| emails_sent | INT UNSIGNED | NOT NULL, DEFAULT 0 | 已寄出幾封信 |
| status | ENUM | NOT NULL, DEFAULT 'active' | 訂閱狀態 |
| status_changed_at | TIMESTAMP | NULLABLE | 狀態變更時間 |
| unsubscribe_token | CHAR(36) | UNIQUE, NOT NULL | 退訂連結 token |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Status Values**:
- `active`: 發信中
- `converted`: 已購買目標課程
- `completed`: 收完全部信但未購買
- `unsubscribed`: 手動退訂

**Indexes**:
- UNIQUE: `(user_id, course_id)` - 每人每課程只能有一筆訂閱
- INDEX: `(course_id, status)` - 查詢課程訂閱者
- UNIQUE: `(unsubscribe_token)` - 退訂連結查詢

**Migration**:
```php
Schema::create('drip_subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('course_id')->constrained()->onDelete('cascade');
    $table->timestamp('subscribed_at');
    $table->unsignedInteger('emails_sent')->default(0);
    $table->enum('status', ['active', 'converted', 'completed', 'unsubscribed'])->default('active');
    $table->timestamp('status_changed_at')->nullable();
    $table->uuid('unsubscribe_token')->unique();
    $table->timestamps();

    $table->unique(['user_id', 'course_id']);
    $table->index(['course_id', 'status']);
});
```

**Model（遵循現有 Pattern）**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class DripSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'subscribed_at',
        'emails_sent',
        'status',
        'status_changed_at',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'emails_sent' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($subscription) {
            $subscription->unsubscribe_token = Str::uuid()->toString();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'active'
        );
    }
}
```

**State Transitions**:
```
           ┌──────────────┐
           │   (start)    │
           └──────┬───────┘
                  │ subscribe
                  ▼
           ┌──────────────┐
           │    active    │
           └──────┬───────┘
                  │
      ┌───────────┼───────────┐
      │           │           │
      ▼           ▼           ▼
┌──────────┐ ┌──────────┐ ┌──────────────┐
│converted │ │completed │ │ unsubscribed │
└──────────┘ └──────────┘ └──────────────┘
(買目標課程) (發完全部信)   (手動退訂)
```

---

### 3. DripConversionTarget (NEW)

**Description**: 記錄連鎖課程與目標課程的關聯（行銷漏斗）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| drip_course_id | BIGINT UNSIGNED | FK → courses.id, NOT NULL | 連鎖課程 |
| target_course_id | BIGINT UNSIGNED | FK → courses.id, NOT NULL | 目標課程 |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Indexes**:
- UNIQUE: `(drip_course_id, target_course_id)` - 避免重複關聯
- INDEX: `(target_course_id)` - 反向查詢

**Migration**:
```php
Schema::create('drip_conversion_targets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('drip_course_id')->constrained('courses')->onDelete('cascade');
    $table->foreignId('target_course_id')->constrained('courses')->onDelete('cascade');
    $table->timestamps();

    $table->unique(['drip_course_id', 'target_course_id']);
    $table->index('target_course_id');
});
```

**Model（遵循現有 Pattern）**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripConversionTarget extends Model
{
    protected $fillable = [
        'drip_course_id',
        'target_course_id',
    ];

    public function dripCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'drip_course_id');
    }

    public function targetCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'target_course_id');
    }
}
```

---

### 4. Lesson (MODIFY - existing)

**Description**: 擴充 Lesson 模型，新增促銷區塊功能（適用所有課程類型）

**新增欄位**:

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| promo_delay_seconds | INT UNSIGNED | NULLABLE | 促銷區塊延遲秒數（null=停用、0=立即、>0=延遲）|
| promo_html | TEXT | NULLABLE | 促銷區塊自訂 HTML 內容 |

**Migration**:
```php
Schema::table('lessons', function (Blueprint $table) {
    $table->unsignedInteger('promo_delay_seconds')->nullable()->after('html_content');
    $table->text('promo_html')->nullable()->after('promo_delay_seconds');
});
```

**Model 修改（遵循現有 Pattern）**:
```php
// Lesson.php - 新增到 $fillable
protected $fillable = [
    // ... existing fields ...
    'promo_delay_seconds',
    'promo_html',
];

// Lesson.php - 新增到 casts()
protected function casts(): array
{
    return [
        // ... existing casts ...
        'promo_delay_seconds' => 'integer',
    ];
}

// Lesson.php - 新增 accessor（參考 hasVideo）
protected function hasPromoBlock(): Attribute
{
    return Attribute::make(
        get: fn () => $this->promo_delay_seconds !== null && !empty($this->promo_html)
    );
}

protected function isPromoImmediate(): Attribute
{
    return Attribute::make(
        get: fn () => $this->promo_delay_seconds === 0
    );
}
```

---

### 5. User (MODIFY - existing)

**新增 Relationships**:
```php
// User.php
public function dripSubscriptions(): HasMany
{
    return $this->hasMany(DripSubscription::class);
}

public function activeDripSubscriptions(): HasMany
{
    return $this->dripSubscriptions()->where('status', 'active');
}
```

---

## Validation Rules

### DripSubscription (StoreDripSubscriptionRequest.php)

```php
// app/Http/Requests/StoreDripSubscriptionRequest.php
[
    'course_id' => ['required', 'exists:courses,id'],
    'email' => ['required_without:user_id', 'email'], // guest flow
    'code' => ['required'], // verify flow
]
```

### Course drip settings (folded into UpdateCourseRequest.php)

```php
// app/Http/Requests/Admin/UpdateCourseRequest.php - added rules
[
    'course_type' => ['required', 'in:standard,drip'],
    'drip_interval_days' => ['required_if:course_type,drip', 'integer', 'min:1', 'max:30'],
    'target_course_ids' => ['nullable', 'array'],
    'target_course_ids.*' => ['exists:courses,id', 'different:course_id'],
]
```

### Lesson (promo settings)

```php
// StoreLessonRequest.php - 新增到 rules()（參考現有結構）
public function rules(): array
{
    return [
        // ... existing rules ...
        'promo_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:7200'],
        'promo_html' => ['nullable', 'string', 'max:10000'],
    ];
}

// StoreLessonRequest.php - 新增到 messages()
public function messages(): array
{
    return [
        // ... existing messages ...
        'promo_delay_seconds.integer' => '延遲時間必須是整數',
        'promo_delay_seconds.min' => '延遲時間不能為負數',
        'promo_delay_seconds.max' => '延遲時間不能超過 7200 秒',
        'promo_html.max' => '促銷內容太長',
    ];
}
```

---

## Computed Properties (not stored)

### Lesson Unlock Status

```php
// DripService
public function getUnlockedLessonCount(DripSubscription $subscription): int
{
    $daysSince = $subscription->subscribed_at->diffInDays(now());
    $interval = $subscription->course->drip_interval_days;

    // sort_order 從 0 開始
    // Day 0: lesson 1 (sort_order 0)
    // Day interval: lesson 2 (sort_order 1)
    return min(
        floor($daysSince / $interval) + 1,
        $subscription->course->lessons()->count()
    );
}

public function isLessonUnlocked(DripSubscription $subscription, Lesson $lesson): bool
{
    return $lesson->sort_order < $this->getUnlockedLessonCount($subscription);
}
```

---

## Query Patterns

### 1. 取得需要發信的訂閱（排程用）

```php
DripSubscription::where('status', 'active')
    ->whereHas('course', fn($q) => $q->where('course_type', 'drip'))
    ->with(['user', 'course.lessons'])
    ->each(function ($subscription) {
        $shouldHaveSent = $this->getUnlockedLessonCount($subscription);
        if ($subscription->emails_sent < $shouldHaveSent) {
            // 發信
        }
    });
```

### 2. 檢查購買是否觸發轉換

```php
DripConversionTarget::where('target_course_id', $purchasedCourseId)
    ->pluck('drip_course_id')
    ->each(function ($dripCourseId) use ($userId) {
        DripSubscription::where('user_id', $userId)
            ->where('course_id', $dripCourseId)
            ->where('status', 'active')
            ->update([
                'status' => 'converted',
                'status_changed_at' => now(),
            ]);
    });
```

### 3. 取得教室頁面資料（含解鎖狀態）

```php
$subscription = DripSubscription::where('user_id', $userId)
    ->where('course_id', $courseId)
    ->firstOrFail();

$lessons = $course->lessons()
    ->orderBy('sort_order')
    ->get()
    ->map(fn($lesson) => [
        ...$lesson->toArray(),
        'is_unlocked' => $this->dripService->isLessonUnlocked($subscription, $lesson),
        'unlock_in_days' => $this->dripService->daysUntilUnlock($subscription, $lesson),
    ]);
```

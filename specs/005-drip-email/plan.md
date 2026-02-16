# Implementation Plan: Email 連鎖加溫系統 (Drip Email System)

**Branch**: `005-drip-email` | **Date**: 2026-02-05 | **Spec**: [spec.md](./spec.md)

## Summary

擴充現有課程系統，新增「連鎖課程」類型（drip course）。當使用者訂閱後，系統依照固定天數間隔自動解鎖 Lesson 並發送 Email 通知。這是一個行銷漏斗，目標是導引客戶購買進階課程。當訂閱者購買任一目標課程時，自動停止發信並獎勵解鎖全部內容。

**新增功能（2026-02-05）**：Lesson 促銷區塊 - 在 Lesson 內可設定延遲顯示的促銷區塊（自訂 HTML），用於建立價值感和過濾精準名單。適用於所有課程類型。

**新增功能（2026-02-16）**：影片免費觀看期限 - Drip 課程 Lesson 解鎖後 48 小時內為免費觀看期，過期後影片仍可觀看但顯示加強版促銷區塊（方案 A：軟性提醒）。設定值存於 config 檔案。

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.x
**Primary Dependencies**: Laravel, Inertia.js, Vue 3, Tailwind CSS, Resend (email)
**Storage**: MySQL (existing database)
**Testing**: PHPUnit (`php artisan test`)
**Target Platform**: Web (Laravel Forge deployment)

## Existing Patterns to Follow

根據現有程式碼分析，本功能必須遵循以下 Pattern：

### Controller Pattern
- 透過 constructor 注入 Service
- 使用 Form Request 處理驗證
- 頁面返回 `Inertia::render()`
- 表單提交返回 `redirect()->route()->with('success/error')`

### Service Pattern
- 複雜業務邏輯放在 Service（參考 `PortalyWebhookService`）
- 返回 `['success' => bool, 'error' => '...']` 格式

### Model Pattern
- 使用 `$fillable` 陣列
- 使用 `casts()` 方法定義型別轉換
- 使用 `Attribute` class 定義 accessor
- 使用 `scope*` 方法定義查詢範圍

### Job Pattern（參考 `SendBatchEmailJob`）
- 實作 `ShouldQueue`
- 設定 `$tries` 和 `$backoff`
- Constructor 接收簡單型別

### Mail Pattern（參考 `CourseGiftedMail`）
- 使用 `Queueable, SerializesModels` traits
- 定義 `envelope()` 和 `content()`

### Console Command Pattern（參考 `UpdateCourseStatus`）
- `$signature` 格式：`category:action`
- `handle()` 返回 `Command::SUCCESS`

### Vue Component Pattern
- 使用 `<script setup>` + Composition API
- Modal 透過 `ref` 控制顯示（參考 `LessonForm`）
- 使用 `router.post/put/delete` from `@inertiajs/vue3`

---

## Project Structure

### 檔案變更清單

```text
app/
├── Console/
│   └── Commands/
│       └── ProcessDripEmails.php           # NEW: 每日發信排程指令
│
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── CourseController.php        # MODIFY: 新增 drip 設定相關 methods
│   │   │   └── ChapterController.php      # MODIFY: index() lesson map 加入 promo 欄位
│   │   ├── Member/
│   │   │   └── ClassroomController.php     # MODIFY: 加入解鎖邏輯 + promo 欄位 + 影片觀看期限
│   │   ├── DripSubscriptionController.php  # NEW: 訂閱/退訂處理
│   │   └── Webhook/
│   │       └── PortalyController.php       # (不變，Service 層處理)
│   │
│   └── Requests/
│       └── Admin/
│           ├── UpdateCourseRequest.php     # MODIFY: 加入 drip 驗證規則
│           └── StoreLessonRequest.php      # MODIFY: 加入 promo 驗證規則
│
├── Jobs/
│   └── SendDripEmailJob.php                # NEW: 發信 Job（參考 SendBatchEmailJob）
│
├── Mail/
│   └── DripLessonMail.php                  # NEW: Lesson 通知信（參考 CourseGiftedMail）
│
├── Models/
│   ├── Course.php                          # MODIFY: 新增 drip 欄位 + relationships
│   ├── Lesson.php                          # MODIFY: 新增 promo 欄位到 $fillable
│   ├── DripConversionTarget.php            # NEW
│   └── DripSubscription.php                # NEW
│
└── Services/
    ├── PortalyWebhookService.php           # MODIFY: handlePaidEvent 加入轉換檢測
    └── DripService.php                     # NEW: 核心業務邏輯 + 影片觀看期限計算

config/
└── drip.php                                # NEW: video_access_hours 設定

database/migrations/
├── YYYY_MM_DD_add_drip_fields_to_courses.php
├── YYYY_MM_DD_create_drip_subscriptions.php
├── YYYY_MM_DD_create_drip_conversion_targets.php
└── YYYY_MM_DD_add_promo_fields_to_lessons.php

resources/
├── js/
│   ├── Components/
│   │   ├── Admin/
│   │   │   └── LessonForm.vue              # MODIFY: 加入 promo_delay_seconds, promo_html 欄位
│   │   ├── Classroom/
│   │   │   ├── LessonPromoBlock.vue        # NEW: 促銷區塊組件（含倒數計時 + localStorage）
│   │   │   └── VideoAccessNotice.vue       # NEW: 影片免費觀看期限組件（倒數 + 過期促銷）
│   │   └── Course/
│   │       └── DripSubscribeForm.vue       # NEW: Email 輸入 + 驗證碼表單
│   │
│   └── Pages/
│       ├── Admin/
│       │   └── Courses/
│       │       ├── Edit.vue                # MODIFY: 加入連鎖課程設定區塊
│       │       └── Subscribers.vue         # NEW: 訂閱者清單頁面
│       ├── Course/
│       │   └── Show.vue                    # MODIFY: 顯示訂閱按鈕/表單
│       ├── Drip/
│       │   └── Unsubscribe.vue             # NEW: 退訂確認頁面
│       └── Member/
│           └── Classroom.vue               # MODIFY: 顯示解鎖狀態 + 促銷區塊 + 影片觀看期限
│
└── views/
    └── emails/
        └── drip-lesson.blade.php           # NEW: Email 模板 + 免費觀看期提示

routes/
├── web.php                                 # MODIFY: 新增路由
└── console.php                             # MODIFY: 註冊排程

tests/
├── Feature/
│   ├── DripSubscriptionTest.php            # NEW
│   └── DripEmailSchedulerTest.php          # NEW
└── Unit/
    └── DripServiceTest.php                 # NEW
```

---

## 實作細節（遵循現有 Pattern）

### 1. DripService.php（參考 VerificationCodeService）

```php
<?php

namespace App\Services;

class DripService
{
    /**
     * 訂閱連鎖課程
     * @return array{success: bool, error?: string, subscription?: DripSubscription}
     */
    public function subscribe(User $user, Course $course): array
    {
        // 檢查是否已退訂過
        // 建立訂閱記錄
        // 發送歡迎信（dispatchSync，立即同步發送）
    }

    /**
     * 計算已解鎖的 Lesson 數量
     */
    public function getUnlockedLessonCount(DripSubscription $subscription): int
    {
        // 公式：floor(daysSince / interval) + 1，比較用 sort_order < count（sort_order 從 0 開始）
    }

    /**
     * 檢查購買是否觸發轉換
     */
    public function checkAndConvert(User $user, Course $purchasedCourse): void
    {
        // 查詢 DripConversionTarget
        // 更新訂閱狀態為 converted
    }

    /**
     * 處理每日排程發信
     */
    public function processDailyEmails(): int
    {
        // 返回發送數量
    }
}
```

### 2. ProcessDripEmails.php（參考 UpdateCourseStatus）

```php
<?php

namespace App\Console\Commands;

class ProcessDripEmails extends Command
{
    protected $signature = 'drip:process-emails';
    protected $description = 'Process and send scheduled drip emails';

    public function __construct(protected DripService $dripService) {}

    public function handle(): int
    {
        $count = $this->dripService->processDailyEmails();
        $this->info("Sent {$count} drip email(s).");
        return Command::SUCCESS;
    }
}
```

### 3. Route 組織（遵循現有結構）

```php
// routes/web.php

// Public drip routes
Route::prefix('drip')->name('drip.')->group(function () {
    Route::post('/subscribe', [DripSubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::post('/verify', [DripSubscriptionController::class, 'verify'])->name('verify');
    Route::get('/unsubscribe/{token}', [DripSubscriptionController::class, 'showUnsubscribe'])->name('unsubscribe.show');
    Route::post('/unsubscribe/{token}', [DripSubscriptionController::class, 'unsubscribe'])->name('unsubscribe');
});

// Member drip routes
Route::middleware('auth')->prefix('member')->name('member.')->group(function () {
    Route::post('/drip/subscribe/{course}', [DripSubscriptionController::class, 'memberSubscribe'])->name('drip.subscribe');
});

// Admin routes（加入現有 admin group）
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // 訂閱者清單
    Route::get('/courses/{course}/subscribers', [CourseController::class, 'subscribers'])->name('courses.subscribers');
});
```

### 4. PortalyWebhookService 修改

```php
// 在 handlePaidEvent() 最後加入：
protected function handlePaidEvent(array $data): array
{
    // ... existing code ...

    if ($purchase) {
        // NEW: 檢查是否觸發轉換
        app(DripService::class)->checkAndConvert($user, $purchase->course);

        return [...];
    }
}
```

### 5. ChapterController 修改（Admin 章節頁）

```php
// index() 中 lesson map 加入 promo 欄位（章節內小節 + 獨立小節皆需加入）
->map(fn ($lesson) => [
    // ... existing fields ...
    'promo_delay_seconds' => $lesson->promo_delay_seconds,
    'promo_html' => $lesson->promo_html,
])
```

**⚠️ 重要**：`ChapterController@index` 提供 lesson 資料給 `LessonForm.vue`（透過 `ChapterList.vue` 的 `editingLesson`），若未包含 promo 欄位，編輯表單開啟時會顯示空白。

### 7. ClassroomController 修改

```php
// formatLessonFull() 加入 promo 欄位
private function formatLessonFull(Lesson $lesson, array $completedLessonIds): array
{
    return [
        // ... existing fields ...
        'promo_delay_seconds' => $lesson->promo_delay_seconds,
        'promo_html' => $lesson->promo_html,
    ];
}
```

### 8. LessonForm.vue 修改

```vue
<!-- 在現有欄位後加入促銷區塊設定 -->
<div class="border-t pt-6 mt-6">
  <h4 class="text-sm font-semibold text-gray-900 mb-4">促銷區塊設定</h4>

  <div class="space-y-4">
    <div>
      <label :class="labelClasses">延遲顯示（秒）</label>
      <input
        v-model="form.promo_delay_seconds"
        type="number"
        min="0"
        :class="inputClasses"
        placeholder="留空則不顯示促銷區塊"
      />
      <p :class="helpTextClasses">0 = 立即顯示，留空 = 不啟用</p>
    </div>

    <div>
      <label :class="labelClasses">促銷內容（HTML）</label>
      <textarea
        v-model="form.promo_html"
        :class="inputClasses"
        rows="5"
        placeholder="<div class='bg-yellow-100 p-4'>...</div>"
      />
    </div>
  </div>
</div>
```

### 9. LessonPromoBlock.vue（新組件）

```vue
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  lessonId: { type: Number, required: true },
  delaySeconds: { type: Number, required: true },
  promoHtml: { type: String, required: true },
})

const UNLOCK_KEY = `promo_unlocked_lesson_${props.lessonId}`
const ELAPSED_KEY = `promo_elapsed_lesson_${props.lessonId}`
const isUnlocked = ref(false)
const elapsedSeconds = ref(0)
let timer = null

onMounted(() => {
  // Check if already unlocked
  if (localStorage.getItem(UNLOCK_KEY) === 'true') {
    isUnlocked.value = true
    return
  }

  if (props.delaySeconds === 0) {
    unlock()
    return
  }

  // Restore elapsed time from previous session
  const savedElapsed = parseInt(localStorage.getItem(ELAPSED_KEY) || '0', 10)
  elapsedSeconds.value = savedElapsed

  if (savedElapsed >= props.delaySeconds) {
    unlock()
    return
  }

  // Start timer, persist elapsed time every 5 seconds
  timer = setInterval(() => {
    elapsedSeconds.value++
    if (elapsedSeconds.value % 5 === 0) {
      localStorage.setItem(ELAPSED_KEY, String(elapsedSeconds.value))
    }
    if (elapsedSeconds.value >= props.delaySeconds) {
      unlock()
    }
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  // Persist elapsed time on unmount (page leave / lesson switch)
  if (!isUnlocked.value) {
    localStorage.setItem(ELAPSED_KEY, String(elapsedSeconds.value))
  }
})

const unlock = () => {
  isUnlocked.value = true
  localStorage.setItem(UNLOCK_KEY, 'true')
  localStorage.removeItem(ELAPSED_KEY)
  if (timer) clearInterval(timer)
}

const remainingSeconds = computed(() =>
  Math.max(0, props.delaySeconds - elapsedSeconds.value)
)

const formattedTime = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60)
  const s = remainingSeconds.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})
</script>

<template>
  <div class="mt-6 border-t pt-6">
    <div v-if="isUnlocked" v-html="promoHtml" />
    <div v-else class="bg-gray-100 rounded-lg p-6 text-center">
      <p class="text-gray-600 mb-2">解鎖進階資訊，請先完成學習</p>
      <p class="text-2xl font-mono text-gray-800">{{ formattedTime }}</p>
    </div>
  </div>
</template>
```

### 10. config/drip.php（新設定檔）

```php
<?php

return [
    'video_access_hours' => env('DRIP_VIDEO_ACCESS_HOURS', 48),
];
```

### 11. DripService 新增影片觀看期限方法

```php
// app/Services/DripService.php — 新增方法

/**
 * 計算 Lesson 的影片免費觀看截止時間
 */
public function getVideoAccessExpiresAt(DripSubscription $subscription, Lesson $lesson): ?Carbon
{
    $hours = config('drip.video_access_hours');
    if ($hours === null) {
        return null;
    }
    $unlockDay = $lesson->sort_order * $subscription->course->drip_interval_days;
    $unlockAt = $subscription->subscribed_at->copy()->addDays($unlockDay);
    return $unlockAt->addHours($hours);
}

/**
 * 檢查影片免費觀看期是否已過期
 */
public function isVideoAccessExpired(DripSubscription $subscription, Lesson $lesson): bool
{
    $expiresAt = $this->getVideoAccessExpiresAt($subscription, $lesson);
    return $expiresAt !== null && now()->greaterThan($expiresAt);
}

/**
 * 取得影片免費觀看剩餘秒數
 */
public function getVideoAccessRemainingSeconds(DripSubscription $subscription, Lesson $lesson): ?int
{
    $expiresAt = $this->getVideoAccessExpiresAt($subscription, $lesson);
    if ($expiresAt === null || now()->greaterThan($expiresAt)) {
        return null;
    }
    return (int) now()->diffInSeconds($expiresAt);
}
```

### 12. ClassroomController 修改（影片觀看期限）

```php
// formatLessonFull() 加入影片觀看期限欄位
private function formatLessonFull(Lesson $lesson, ...): array
{
    $isConverted = $subscription?->status === 'converted';
    $hasVideo = !empty($lesson->video_id);

    return [
        // ... existing fields ...
        'video_access_expired' => (!$isConverted && $hasVideo)
            ? $this->dripService->isVideoAccessExpired($subscription, $lesson)
            : false,
        'video_access_remaining_seconds' => (!$isConverted && $hasVideo)
            ? $this->dripService->getVideoAccessRemainingSeconds($subscription, $lesson)
            : null,
    ];
}

// show() 方法新增 videoAccessTargetCourses prop
$targetCourses = $course->dripConversionTargets()
    ->with('targetCourse:id,name')
    ->get()
    ->map(fn($t) => [
        'id' => $t->targetCourse->id,
        'name' => $t->targetCourse->name,
        'url' => route('course.show', $t->targetCourse),
    ]);

return Inertia::render('Member/Classroom', [
    // ... existing props ...
    'videoAccessTargetCourses' => $course->is_drip ? $targetCourses : [],
]);
```

### 13. VideoAccessNotice.vue（新組件）

```vue
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  expired: { type: Boolean, required: true },
  remainingSeconds: { type: Number, default: null },
  targetCourses: { type: Array, default: () => [] },
})

const countdown = ref(props.remainingSeconds)
let timer = null

onMounted(() => {
  if (!props.expired && countdown.value > 0) {
    timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(timer)
        // Force page reload to get updated server state
        window.location.reload()
      }
    }, 1000)
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const formattedCountdown = computed(() => {
  if (!countdown.value || countdown.value <= 0) return null
  const h = Math.floor(countdown.value / 3600)
  const m = Math.floor((countdown.value % 3600) / 60)
  const s = countdown.value % 60
  return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})
</script>

<template>
  <!-- 免費觀看期內：倒數提示 -->
  <div v-if="!expired && formattedCountdown"
       class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4 text-center">
    <p class="text-sm text-green-700">課程免費公開中，剩餘</p>
    <p class="text-xl font-mono font-bold text-green-800">{{ formattedCountdown }}</p>
  </div>

  <!-- 過期後：加強版促銷區塊 -->
  <div v-else-if="expired"
       class="mt-4 bg-amber-50 border border-amber-300 rounded-lg p-6">
    <p class="text-amber-800 font-semibold mb-2">
      免費觀看期已結束，但我們為你保留了存取權。
    </p>
    <p class="text-amber-700 mb-4">想要完整學習體驗？</p>
    <div v-if="targetCourses.length > 0" class="space-y-2">
      <a v-for="course in targetCourses" :key="course.id"
         :href="course.url"
         class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-lg transition">
        推薦購買：{{ course.name }}
      </a>
    </div>
    <a v-else href="/"
       class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-lg transition">
      探索更多課程
    </a>
  </div>
</template>
```

### 14. Classroom.vue 修改（影片觀看期限）

```vue
<!-- 在影片播放器下方、促銷區塊上方 -->
<VideoAccessNotice
  v-if="course.course_type === 'drip'
    && currentLesson?.video_id
    && subscription?.status !== 'converted'
    && (currentLesson.video_access_expired || currentLesson.video_access_remaining_seconds > 0)"
  :expired="currentLesson.video_access_expired"
  :remaining-seconds="currentLesson.video_access_remaining_seconds"
  :target-courses="videoAccessTargetCourses"
/>
```

**⚠️ 顯示順序**：影片 → VideoAccessNotice（觀看期限）→ LessonPromoBlock（自訂促銷）→ 文字內容

### 15. drip-lesson.blade.php 修改（免費觀看期提示）

```blade
{{-- 在影片提示區塊修改 --}}
@if($lesson->video_id)
  <p style="font-size:16px;font-weight:bold;color:#e00">* 本課程包含教學影片，請至網站觀看</p>
  @if(config('drip.video_access_hours'))
  <p style="font-size:16px;font-weight:bold;color:#e00">* 影片 {{ config('drip.video_access_hours') }} 小時內免費觀看，把握時間！</p>
  @endif
@endif
```

---

## Migration Notes

- `add_drip_fields_to_courses`: 現有課程預設 `course_type = 'standard'`
- `add_promo_fields_to_lessons`: 現有 Lesson 的 promo 欄位預設 `null`

---

## Phase Completion Status

| Phase | Status | Output |
|-------|--------|--------|
| Phase 0: Research | ✅ Complete | [research.md](./research.md) |
| Phase 1: Design | ✅ Complete | [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md) |
| Phase 2: Tasks | ✅ Complete | [tasks.md](./tasks.md) |

---

## Next Steps

1. Implement Phase 11 (US10 — 影片免費觀看期限) tasks T047-T052
2. Complete Phase 12 verification tasks (T045, T053)
3. Run end-to-end quickstart validation

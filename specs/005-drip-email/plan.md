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

## 增量更新：準時到課獎勵區塊（US11）- 2026-02-21

**新增功能（2026-02-21）**：在免費觀看期倒數旁加入獎勵欄（right column）。會員進入頁面後開始 per-session 計時，停留滿 `reward_delay_minutes` 分鐘後顯示管理員自訂 `reward_html`。達標狀態以 localStorage 永久記錄；免費期逾期後保留已達標獎勵，未達標則顯示「下次早點來喔，錯過了獎勵 :(」。

### 檔案變更清單（Phase 13）

```text
database/migrations/
└── YYYY_add_reward_html_to_lessons.php          # NEW

config/
└── drip.php                                     # MODIFY: 新增 reward_delay_minutes

app/
├── Models/
│   └── Lesson.php                               # MODIFY: 新增 reward_html 到 $fillable
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   └── ChapterController.php            # MODIFY: lesson map 加入 reward_html + course_type
│   │   └── Member/
│   │       └── ClassroomController.php          # MODIFY: 傳 reward_html (per-lesson) + reward_delay_minutes (page prop)
│   └── Requests/
│       └── Admin/
│           └── StoreLessonRequest.php           # MODIFY: 加入 reward_html 驗證

resources/js/
├── Components/
│   ├── Admin/
│   │   └── LessonForm.vue                       # MODIFY: 加入 reward_html textarea (v-if drip)
│   └── Classroom/
│       └── VideoAccessNotice.vue                # MODIFY: 擴充為雙欄佈局 + 獎勵欄邏輯
└── Pages/
    └── Member/
        └── Classroom.vue                        # MODIFY: 傳 reward props 給 VideoAccessNotice
```

---

### 16. config/drip.php 修改（新增獎勵延遲設定）

```php
return [
    'video_access_hours'   => env('DRIP_VIDEO_ACCESS_HOURS', 48),
    'reward_delay_minutes' => env('DRIP_REWARD_DELAY_MINUTES', 10),  // NEW
];
```

---

### 17. ClassroomController 修改（US11）

```php
// show() 新增 page-level prop
return Inertia::render('Member/Classroom', [
    // ... existing props ...
    'reward_delay_minutes' => config('drip.reward_delay_minutes'),  // NEW
]);

// formatLessonFull() 新增 reward_html（per-lesson）
private function formatLessonFull(Lesson $lesson, ...): array
{
    $hasVideo = !empty($lesson->video_id);
    $isConverted = $subscription?->status === 'converted';

    return [
        // ... existing fields ...
        // US11: 僅 drip 課程 + 有影片 + 非 converted 才傳 reward_html
        'reward_html' => ($isDrip && $hasVideo && !$isConverted)
            ? $lesson->reward_html
            : null,
    ];
}
```

**⚠️ 設計決策**：`reward_delay_minutes` 是 page-level prop（全局 config 值），不需要 per-lesson 重複傳送。`reward_html` 是 per-lesson prop（管理員自訂內容）。

---

### 18. VideoAccessNotice.vue 修改（US11）— 核心元件擴充

**設計要點：**
- 現有組件負責免費觀看期倒數（US10）。US11 在其右側加入獎勵欄。
- **計時策略差異**（關鍵）：
  - LessonPromoBlock（US8）：跨 session 累積（localStorage 存 elapsed seconds）
  - VideoAccessNotice 獎勵計時（US11）：per-session，每次 mount 從 0 開始，**不累積**
- **localStorage 用途**：僅存 `reward_achieved_lesson_{lessonId}`（布林）記錄已達標，不存計時秒數

```vue
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  // US10 existing props
  expired:          { type: Boolean, required: true },
  remainingSeconds: { type: Number,  default: null },
  targetCourses:    { type: Array,   default: () => [] },
  // US11 new props
  rewardHtml:          { type: String, default: null },
  rewardDelayMinutes:  { type: Number, default: 10 },
  lessonId:            { type: Number, required: true },
})

// US11: reward state
const REWARD_KEY = computed(() => `reward_achieved_lesson_${props.lessonId}`)
const rewardAchieved = ref(localStorage.getItem(REWARD_KEY.value) === 'true')
const rewardElapsed = ref(0)          // per-session, not persisted
let rewardTimer = null

const showRewardColumn = computed(() => !!props.rewardHtml)

onMounted(() => {
  // ... existing US10 countdown timer setup ...

  // US11: start reward timer (per-session, not restored from localStorage)
  if (showRewardColumn.value && !rewardAchieved.value && !props.expired) {
    rewardTimer = setInterval(() => {
      rewardElapsed.value++
      if (rewardElapsed.value >= props.rewardDelayMinutes * 60) {
        rewardAchieved.value = true
        localStorage.setItem(REWARD_KEY.value, 'true')
        clearInterval(rewardTimer)
      }
    }, 1000)
  }
})

onUnmounted(() => {
  // ... existing cleanup ...
  if (rewardTimer) clearInterval(rewardTimer)
  // ⚠️ 不持久化 rewardElapsed（per-session，歸零）
})
</script>

<template>
  <!-- 有 reward_html 時：雙欄佈局（flex-col on mobile, flex-row on md+） -->
  <div v-if="showRewardColumn"
       class="mt-4 flex flex-col md:flex-row gap-4">
    <!-- 左欄：免費觀看期倒數（既有邏輯不變） -->
    <div class="flex-1">
      <!-- 免費觀看期內 -->
      <div v-if="!expired && formattedCountdown"
           class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
        <p class="text-sm text-green-700">課程免費公開中，剩餘</p>
        <p class="text-xl font-mono font-bold text-green-800">{{ formattedCountdown }}</p>
      </div>
      <!-- 過期後 -->
      <div v-else-if="expired" class="bg-amber-50 border border-amber-300 rounded-lg p-6">
        <p class="text-amber-800 font-semibold mb-2">免費觀看期已結束，但我們為你保留了存取權。</p>
        <!-- 未達標時追加提示 -->
        <p v-if="!rewardAchieved" class="text-sm text-amber-600 mt-3">
          下次早點來喔，錯過了獎勵 :(
        </p>
        <!-- 已達標時在過期區塊顯示獎勵內容 -->
        <div v-if="rewardAchieved" v-html="rewardHtml" class="mt-3" />
        <!-- 目標課程連結（不管達標與否都顯示） -->
        <div v-if="targetCourses.length > 0" class="space-y-2 mt-4">
          <a v-for="course in targetCourses" :key="course.id"
             :href="course.url"
             class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-lg transition">
            推薦購買：{{ course.name }}
          </a>
        </div>
        <a v-else href="/"
           class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-lg transition mt-4">
          探索更多課程
        </a>
      </div>
    </div>

    <!-- 右欄：獎勵欄（免費觀看期內才顯示，逾期後整合至左欄） -->
    <div v-if="!expired" class="flex-1 border border-gray-200 rounded-lg p-4 text-center">
      <!-- 未達標：鼓勵文字 -->
      <div v-if="!rewardAchieved">
        <p class="text-sm font-medium text-gray-700">你準時來上課了！真棒</p>
        <p class="text-xs text-gray-400 mt-1">繼續停留即可解鎖獎勵</p>
      </div>
      <!-- 已達標：管理員自訂 reward_html -->
      <div v-else v-html="rewardHtml" />
    </div>
  </div>

  <!-- 無 reward_html 時：原始單欄佈局（既有 US10 邏輯，不受影響） -->
  <template v-else>
    <div v-if="!expired && formattedCountdown"
         class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4 text-center">
      <p class="text-sm text-green-700">課程免費公開中，剩餘</p>
      <p class="text-xl font-mono font-bold text-green-800">{{ formattedCountdown }}</p>
    </div>
    <div v-else-if="expired"
         class="mt-4 bg-amber-50 border border-amber-300 rounded-lg p-6">
      <p class="text-amber-800 font-semibold mb-2">免費觀看期已結束，但我們為你保留了存取權。</p>
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
</template>
```

**⚠️ 注意事項**：
- 已達標（`rewardAchieved = true`）且免費期內：右欄直接顯示 `reward_html`（不需再次計時）
- 已達標且逾期：獎勵整合顯示在左欄的逾期促銷區塊內（右欄在逾期後不顯示）
- 未達標且逾期：左欄追加「下次早點來喔，錯過了獎勵 :(」文字
- 無 `reward_html`：組件行為與 US10 原始版本完全相同（無右欄）

---

### 19. Classroom.vue 修改（US11）

```vue
<!-- 新增 props 解構 -->
const { course, lessons, subscription, videoAccessTargetCourses, reward_delay_minutes } = defineProps(...)

<!-- VideoAccessNotice 傳入 US11 新 props -->
<VideoAccessNotice
  v-if="course.course_type === 'drip'
    && currentLesson?.video_id
    && subscription?.status !== 'converted'
    && (currentLesson.video_access_expired || currentLesson.video_access_remaining_seconds > 0)"
  :expired="currentLesson.video_access_expired"
  :remaining-seconds="currentLesson.video_access_remaining_seconds"
  :target-courses="videoAccessTargetCourses"
  :reward-html="currentLesson.reward_html || null"
  :reward-delay-minutes="reward_delay_minutes"
  :lesson-id="currentLesson.id"
/>
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

## 增量更新：Email 追蹤分析（US12~US14）- 2026-02-28

**新增功能（2026-02-28）**：Email 追蹤分析 - Tracking Pixel 開信追蹤、URL Redirect 點擊追蹤、Lesson 開信率/點擊率報表、訂閱者開信進度指示、促銷商品連結欄位（promo_url）

### 檔案變更清單（Phase 13）

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── DripTrackingController.php          # NEW: open() + click()
│   └── Requests/
│       └── Admin/
│           └── StoreLessonRequest.php           # MODIFY: 加入 promo_url 驗證
│
├── Models/
│   ├── DripEmailEvent.php                       # NEW: opened/clicked 事件 model
│   ├── DripSubscription.php                     # MODIFY: 新增 emailEvents() HasMany
│   └── Lesson.php                               # MODIFY: 新增 promo_url 到 $fillable
│
├── Mail/
│   └── DripLessonMail.php                       # MODIFY: 新增 openPixelUrl, promoTrackUrl props
│
└── Jobs/
    └── SendDripEmailJob.php                     # MODIFY: 生成 signed tracking URLs

database/migrations/
├── YYYY_create_drip_email_events_table.php      # NEW
└── YYYY_add_promo_url_to_lessons.php            # NEW

resources/js/
├── Components/
│   └── Admin/
│       └── LessonForm.vue                       # MODIFY: 加入 promo_url 輸入欄
│
└── Pages/
    └── Admin/
        └── Courses/
            └── Subscribers.vue                  # MODIFY: 加入 Lesson 統計表 + 訂閱者指示器

resources/views/emails/
└── drip-lesson.blade.php                        # MODIFY: 加入 pixel + promo_url 按鈕

routes/
└── web.php                                      # MODIFY: 新增 2 個 tracking 路由
```

---

### 17. DripTrackingController（新增）

```php
<?php

namespace App\Http\Controllers;

use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class DripTrackingController extends Controller
{
    // 1x1 透明 GIF binary
    private const PIXEL = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    public function open(Request $request): Response
    {
        // 驗證 signed URL（失效時仍返回 GIF 避免破圖）
        if ($request->hasValidSignature()) {
            $subId = $request->integer('sub');
            $lesId = $request->integer('les');

            try {
                DripEmailEvent::firstOrCreate(
                    ['subscription_id' => $subId, 'lesson_id' => $lesId, 'event_type' => 'opened'],
                    ['ip' => $request->ip(), 'user_agent' => $request->userAgent()]
                );
            } catch (\Exception $e) {
                Log::warning('Drip tracking open failed', ['sub' => $subId, 'les' => $lesId, 'error' => $e->getMessage()]);
            }
        }

        return response(self::PIXEL, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function click(Request $request): \Illuminate\Http\RedirectResponse
    {
        $targetUrl = $request->query('url', '/');

        if ($request->hasValidSignature()) {
            $subId = $request->integer('sub');
            $lesId = $request->integer('les');

            try {
                DripEmailEvent::firstOrCreate(
                    ['subscription_id' => $subId, 'lesson_id' => $lesId, 'event_type' => 'clicked'],
                    ['target_url' => $targetUrl, 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]
                );
            } catch (\Exception $e) {
                Log::warning('Drip tracking click failed', ['sub' => $subId, 'les' => $lesId, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->away($targetUrl);
    }
}
```

---

### 18. DripEmailEvent Model（新增）

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripEmailEvent extends Model
{
    // 只有 created_at，無 updated_at（事件不可變）
    const UPDATED_AT = null;

    protected $fillable = [
        'subscription_id',
        'lesson_id',
        'event_type',
        'target_url',
        'ip',
        'user_agent',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(DripSubscription::class, 'subscription_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
```

---

### 19. SendDripEmailJob 修改（生成 tracking URLs）

```php
// 在 handle() 中，Mail::to()->send() 之前加入：
$openPixelUrl = URL::signedRoute('drip.track.open', [
    'sub' => $subscription->id,
    'les' => $lesson->id,
], now()->addDays(180));

$promoTrackUrl = null;
if (!empty($lesson->promo_url)) {
    $promoTrackUrl = URL::signedRoute('drip.track.click', [
        'sub' => $subscription->id,
        'les' => $lesson->id,
        'url' => $lesson->promo_url,
    ], now()->addDays(180));
}

Mail::to($user->email)->send(new DripLessonMail(
    // ... existing params ...
    openPixelUrl: $openPixelUrl,
    promoTrackUrl: $promoTrackUrl,
));
```

---

### 20. DripLessonMail 修改

```php
// 新增 constructor params
public function __construct(
    // ... existing params ...
    public string $openPixelUrl,
    public ?string $promoTrackUrl = null,
) {}
```

---

### 21. drip-lesson.blade.php 修改（追蹤 pixel + promo 按鈕）

```blade
{{-- 在 md_content 渲染後、unsubscribe 之前 --}}
@if($promoTrackUrl)
<p style="text-align:center;margin:24px 0">
  <a href="{{ $promoTrackUrl }}"
     style="display:inline-block;background:#F0C14B;color:#373557;padding:12px 40px;border-radius:9999px;border:1px solid rgba(199,163,59,0.5);text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">
    立即瞭解
  </a>
</p>
@endif

{{-- Tracking Pixel（隱藏，放最後） --}}
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
```

**Button Style 對應**（參考課程販售頁「立即購買」按鈕）：
| Tailwind class | Inline CSS |
|---|---|
| `bg-brand-gold` | `background:#F0C14B` |
| `text-brand-navy` | `color:#373557` |
| `rounded-full` | `border-radius:9999px` |
| `px-10 py-3` | `padding:12px 40px` |
| `border border-brand-gold-dark/50` | `border:1px solid rgba(199,163,59,0.5)` |
| `font-semibold` | `font-weight:600` |
| `shadow-sm` | `box-shadow:0 1px 3px rgba(0,0,0,0.1)` |

---

### 22. routes/web.php 修改（新增 tracking 路由）

```php
// 加入現有 drip group 之後（public routes，不需 auth）
Route::get('/drip/track/open', [DripTrackingController::class, 'open'])->name('drip.track.open');
Route::get('/drip/track/click', [DripTrackingController::class, 'click'])->name('drip.track.click');
```

---

### 23. CourseController::subscribers() 修改（Lesson 統計）

```php
public function subscribers(Request $request, Course $course): Response
{
    // ... existing query ...

    // NEW: Lesson stats
    $lessons = $course->lessons()->orderBy('sort_order')->get(['id', 'title', 'sort_order', 'promo_url']);
    $subIds = DripSubscription::where('course_id', $course->id)->pluck('id');

    $eventStats = DripEmailEvent::whereIn('subscription_id', $subIds)
        ->select('lesson_id',
            DB::raw("SUM(event_type = 'opened') as open_count"),
            DB::raw("SUM(event_type = 'clicked') as click_count")
        )
        ->groupBy('lesson_id')
        ->get()->keyBy('lesson_id');

    $lessonStats = $lessons->map(function ($lesson) use ($eventStats, $course) {
        $sentCount = DripSubscription::where('course_id', $course->id)
            ->where('emails_sent', '>', $lesson->sort_order)->count();
        $stats = $eventStats->get($lesson->id);
        return [
            'lesson_id'   => $lesson->id,
            'title'       => $lesson->title,
            'sort_order'  => $lesson->sort_order,
            'sent_count'  => $sentCount,
            'open_count'  => $stats?->open_count ?? 0,
            'open_rate'   => $sentCount > 0 ? round(($stats?->open_count ?? 0) / $sentCount, 4) : null,
            'has_promo_url' => !empty($lesson->promo_url),
            'click_count' => $stats?->click_count ?? 0,
            'click_rate'  => ($sentCount > 0 && !empty($lesson->promo_url))
                ? round(($stats?->click_count ?? 0) / $sentCount, 4) : null,
        ];
    });

    // NEW: conversionRate
    $conversionRate = $stats->total > 0
        ? round($stats->converted_count / $stats->total, 4)
        : null;

    return Inertia::render('Admin/Courses/Subscribers', [
        // ... existing props ...
        'lessonStats'    => $lessonStats,
        'conversionRate' => $conversionRate,
    ]);
}
```

---

### 24. Subscribers.vue 修改（Lesson 統計表 + 訂閱者指示器）

```vue
<!-- Lesson 統計表（在統計卡片下方、訂閱者清單上方） -->
<div v-if="lessonStats.length" class="mb-6 overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-4 py-2 text-left">課程</th>
        <th class="px-4 py-2 text-right">已發送</th>
        <th class="px-4 py-2 text-right">開信</th>
        <th class="px-4 py-2 text-right">開信率</th>
        <th class="px-4 py-2 text-right">點擊</th>
        <th class="px-4 py-2 text-right">點擊率</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="ls in lessonStats" :key="ls.lesson_id" class="border-t">
        <td class="px-4 py-2">{{ ls.title }}</td>
        <td class="px-4 py-2 text-right">{{ ls.sent_count || '—' }}</td>
        <td class="px-4 py-2 text-right">{{ ls.open_count }}</td>
        <td class="px-4 py-2 text-right">
          {{ ls.open_rate != null ? (ls.open_rate * 100).toFixed(1) + '%' : '—' }}
        </td>
        <td class="px-4 py-2 text-right">
          {{ ls.has_promo_url ? ls.click_count : '—' }}
        </td>
        <td class="px-4 py-2 text-right">
          {{ ls.click_rate != null ? (ls.click_rate * 100).toFixed(1) + '%' : '—' }}
        </td>
      </tr>
    </tbody>
  </table>
  <p v-if="conversionRate != null" class="mt-2 text-sm text-gray-600">
    整體轉換率：{{ (conversionRate * 100).toFixed(1) }}%
  </p>
</div>

<!-- 訂閱者表格新增欄位（加在現有 emails_sent 後方） -->
<td class="hidden md:table-cell px-4 py-2 text-sm text-gray-600">
  已開 {{ sub.opened_count }}/{{ sub.emails_sent }} 封
</td>
<td class="hidden md:table-cell px-4 py-2 text-center text-sm">
  <span v-if="sub.has_clicked" class="text-green-600">✓</span>
  <span v-else class="text-gray-300">—</span>
</td>
```

---

### 25. LessonForm.vue 修改（promo_url 欄位 + CTA 快速插入樣式）

**A. 新增 promo_url 輸入欄**（在 promo_html textarea 下方）：

```vue
<div class="mt-4">
  <label :class="labelClasses">商品連結 URL（Email 追蹤）</label>
  <input
    v-model="form.promo_url"
    type="url"
    :class="inputClasses"
    placeholder="https://example.com/product/..."
  />
  <p :class="helpTextClasses">設定後，drip 信件中顯示可追蹤點擊的商品連結按鈕。留空則不顯示。</p>
</div>
```

**B. CTA 快速插入（FR-034b）按鈕樣式更新**：

CTA 快速插入功能產生的 inline HTML 按鈕，樣式統一改用 brand-gold（與 drip 信件 promo_url 按鈕一致）：

```js
// CTA 快速插入生成的 HTML 模板（預設文字改為「立即瞭解」）
const ctaHtml = (url, text = '立即瞭解') =>
  `<div style="text-align:center;margin:24px 0">` +
  `<a href="${url}" ` +
  `style="display:inline-block;background:#F0C14B;color:#373557;` +
  `padding:12px 40px;border-radius:9999px;` +
  `border:1px solid rgba(199,163,59,0.5);` +
  `text-decoration:none;font-weight:600;font-size:15px;` +
  `box-shadow:0 1px 3px rgba(0,0,0,0.1)">${text}</a>` +
  `</div>`
```

> **注意**：若舊有 CTA 快速插入使用 `#ff5a36` 橘色，需更新為上方 brand-gold 樣式。

---

## Next Steps

1. Complete Polish verification tasks (T045, T053, T062)
2. Run `/speckit.tasks` to generate Phase 13 tasks for US12~US14 implementation
3. Run end-to-end quickstart validation

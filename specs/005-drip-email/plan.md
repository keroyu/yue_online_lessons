# Implementation Plan: Email 連鎖加溫系統 (Drip Email System)

**Branch**: `005-drip-email` | **Date**: 2026-02-05 | **Spec**: [spec.md](./spec.md)

## Summary

擴充現有課程系統，新增「連鎖課程」類型（drip course）。當使用者訂閱後，系統依照固定天數間隔自動解鎖 Lesson 並發送 Email 通知。這是一個行銷漏斗，目標是導引客戶購買進階課程。當訂閱者購買任一目標課程時，自動停止發信並獎勵解鎖全部內容。

**新增功能（2026-02-05）**：Lesson 促銷區塊 - 在 Lesson 內可設定延遲顯示的促銷區塊（自訂 HTML），用於建立價值感和過濾精準名單。適用於所有課程類型。

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
│   │   │   └── CourseController.php        # MODIFY: 新增 drip 設定相關 methods
│   │   ├── Member/
│   │   │   └── ClassroomController.php     # MODIFY: 加入解鎖邏輯 + promo 欄位
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
    └── DripService.php                     # NEW: 核心業務邏輯

database/migrations/
├── YYYY_MM_DD_add_drip_fields_to_courses.php
├── YYYY_MM_DD_create_drip_subscriptions.php
├── YYYY_MM_DD_create_drip_conversion_targets.php
└── YYYY_MM_DD_add_promo_fields_to_lessons.php

resources/
├── js/
│   ├── Components/
│   │   ├── Admin/
│   │   │   └── LessonForm.vue              # MODIFY: 加入 promo_delay_minutes, promo_html 欄位
│   │   ├── Classroom/
│   │   │   └── LessonPromoBlock.vue        # NEW: 促銷區塊組件（含倒數計時 + localStorage）
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
│           └── Classroom.vue               # MODIFY: 顯示解鎖狀態 + 促銷區塊
│
└── views/
    └── emails/
        └── drip-lesson.blade.php           # NEW: Email 模板

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
        // 發送歡迎信
    }

    /**
     * 計算已解鎖的 Lesson 數量
     */
    public function getUnlockedLessonCount(DripSubscription $subscription): int
    {
        // 公式：floor(daysSince / interval) + 1
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

### 5. ClassroomController 修改

```php
// formatLessonFull() 加入 promo 欄位
private function formatLessonFull(Lesson $lesson, array $completedLessonIds): array
{
    return [
        // ... existing fields ...
        'promo_delay_minutes' => $lesson->promo_delay_minutes,
        'promo_html' => $lesson->promo_html,
    ];
}
```

### 6. LessonForm.vue 修改

```vue
<!-- 在現有欄位後加入促銷區塊設定 -->
<div class="border-t pt-6 mt-6">
  <h4 class="text-sm font-semibold text-gray-900 mb-4">促銷區塊設定</h4>

  <div class="space-y-4">
    <div>
      <label :class="labelClasses">延遲顯示（分鐘）</label>
      <input
        v-model="form.promo_delay_minutes"
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

### 7. LessonPromoBlock.vue（新組件）

```vue
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  lessonId: { type: Number, required: true },
  delayMinutes: { type: Number, required: true },
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

  if (props.delayMinutes === 0) {
    unlock()
    return
  }

  // Restore elapsed time from previous session
  const savedElapsed = parseInt(localStorage.getItem(ELAPSED_KEY) || '0', 10)
  elapsedSeconds.value = savedElapsed

  if (savedElapsed >= props.delayMinutes * 60) {
    unlock()
    return
  }

  // Start timer, persist elapsed time every 5 seconds
  timer = setInterval(() => {
    elapsedSeconds.value++
    if (elapsedSeconds.value % 5 === 0) {
      localStorage.setItem(ELAPSED_KEY, String(elapsedSeconds.value))
    }
    if (elapsedSeconds.value >= props.delayMinutes * 60) {
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
  Math.max(0, props.delayMinutes * 60 - elapsedSeconds.value)
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
      <p class="text-gray-600 mb-2">請先觀看課程</p>
      <p class="text-2xl font-mono text-gray-800">{{ formattedTime }}</p>
    </div>
  </div>
</template>
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
| Phase 2: Tasks | ⏳ Pending | Run `/speckit.tasks` to generate |

---

## Next Steps

1. Run `/speckit.tasks` to generate implementation tasks
2. Review and prioritize tasks
3. Begin implementation following task order

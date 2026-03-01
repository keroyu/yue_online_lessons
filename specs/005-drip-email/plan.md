# Implementation Plan: Email 連鎖加溫系統 (Drip Email System)

**Branch**: `005-drip-email` | **Date**: 2026-02-05 | **Spec**: [spec.md](./spec.md)

## Summary

擴充現有課程系統，新增「連鎖課程」類型（drip course）。當使用者訂閱後，系統依照固定天數間隔自動解鎖 Lesson 並發送 Email 通知。這是一個行銷漏斗，目標是導引客戶購買進階課程。當訂閱者購買任一目標課程時，自動停止發信並獎勵解鎖全部內容。

**新增功能（2026-02-05）**：Lesson 促銷區塊 - 在 Lesson 內可設定延遲顯示的促銷區塊（自訂 HTML），用於建立價值感和過濾精準名單。適用於所有課程類型。

**新增功能（2026-02-16）**：影片免費觀看期限 - Drip 課程 Lesson 解鎖後一定時數內為免費觀看期，過期後影片仍可觀看但顯示加強版促銷區塊（方案 A：軟性提醒）。~~設定值存於 config 檔案。~~ **（2026-03-01 更新）改為 per-lesson `video_access_hours` 欄位（nullable 整數），null = 無限期觀看。**

**新增功能（2026-02-21）**：準時到課獎勵區塊 - 在免費觀看期倒數旁加入獎勵欄，停留滿設定時間後顯示管理員自訂獎勵 HTML。

**新增功能（2026-02-28）**：Email 追蹤分析 - Tracking Pixel 開信追蹤、Lesson 統計報表（開信率/點擊率/轉換率）、訂閱者開信進度指示。

**設計修正（2026-03-01）**：promo_url 用途修正 - 從「drip Email 按鈕追蹤」改為「教室促銷點擊追蹤」。Email 不包含促銷按鈕；教室頁面以 auth session 識別訂閱者，無需 signed URL。

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.x
**Primary Dependencies**: Laravel, Inertia.js, Vue 3, Tailwind CSS, Resend (email)
**Storage**: MySQL (existing database)
**Testing**: PHPUnit (`php artisan test`)
**Target Platform**: Web (Laravel Forge deployment)

## Constitution Check

*Passes all gates. No security, privacy, or scope violations.*

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
│   │   │   ├── CourseController.php        # MODIFY: 新增 drip 設定 + subscribers()
│   │   │   └── ChapterController.php      # MODIFY: lesson map 加入 promo/reward 欄位
│   │   ├── Member/
│   │   │   └── ClassroomController.php     # MODIFY: 解鎖邏輯 + promo + 影片觀看期限 + promo_url 追蹤按鈕
│   │   ├── DripSubscriptionController.php  # NEW: 訂閱/退訂處理
│   │   ├── DripTrackingController.php      # NEW: open() pixel + click() 教室追蹤（auth session）
│   │   └── Webhook/
│   │       └── PortalyController.php       # (不變，Service 層處理)
│   │
│   └── Requests/
│       └── Admin/
│           ├── UpdateCourseRequest.php     # MODIFY: 加入 drip 驗證規則
│           └── StoreLessonRequest.php      # MODIFY: 加入 promo/reward/promo_url 驗證規則
│
├── Jobs/
│   └── SendDripEmailJob.php                # NEW: 發信 Job（不含 promoTrackUrl）
│
├── Mail/
│   └── DripLessonMail.php                  # NEW: Lesson 通知信（含 openPixelUrl，不含 promoTrackUrl）
│
├── Models/
│   ├── Course.php                          # MODIFY: 新增 drip 欄位 + relationships
│   ├── Lesson.php                          # MODIFY: 新增 promo/reward/promo_url 欄位到 $fillable
│   ├── DripConversionTarget.php            # NEW
│   ├── DripSubscription.php                # NEW
│   └── DripEmailEvent.php                  # NEW: opened / clicked 事件 model
│
└── Services/
    ├── PortalyWebhookService.php           # MODIFY: handlePaidEvent 加入轉換檢測
    └── DripService.php                     # NEW: 核心業務邏輯 + 影片觀看期限計算 + 訂閱者統計

config/
└── drip.php                                # NEW: video_access_hours + reward_delay_minutes

database/migrations/
├── YYYY_MM_DD_add_drip_fields_to_courses.php
├── YYYY_MM_DD_create_drip_subscriptions.php
├── YYYY_MM_DD_create_drip_conversion_targets.php
├── YYYY_MM_DD_add_promo_fields_to_lessons.php
├── YYYY_MM_DD_add_reward_html_to_lessons.php
├── YYYY_MM_DD_create_drip_email_events_table.php
└── YYYY_MM_DD_add_promo_url_to_lessons.php

resources/
├── js/
│   ├── Components/
│   │   ├── Admin/
│   │   │   └── LessonForm.vue              # MODIFY: promo 欄位 + reward_html + promo_url（label：教室追蹤）
│   │   ├── Classroom/
│   │   │   ├── LessonPromoBlock.vue        # NEW: 促銷區塊組件（含倒數計時 + localStorage）
│   │   │   └── VideoAccessNotice.vue       # NEW: 影片免費觀看期限 + 獎勵欄（US10 + US11）
│   │   └── Course/
│   │       └── DripSubscribeForm.vue       # NEW: Email 輸入 + 驗證碼表單
│   │
│   └── Pages/
│       ├── Admin/
│       │   └── Courses/
│       │       ├── Edit.vue                # MODIFY: 加入連鎖課程設定區塊
│       │       └── Subscribers.vue         # NEW/MODIFY: 訂閱者清單 + Lesson 統計表
│       ├── Course/
│       │   └── Show.vue                    # MODIFY: 顯示訂閱按鈕/表單
│       ├── Drip/
│       │   └── Unsubscribe.vue             # NEW: 退訂確認頁面
│       └── Member/
│           └── Classroom.vue               # MODIFY: 解鎖狀態 + 促銷區塊 + 影片觀看期限 + promo_url 按鈕
│
└── views/
    └── emails/
        └── drip-lesson.blade.php           # NEW: Email 模板 + pixel（不含促銷按鈕）

routes/
├── web.php                                 # MODIFY: 新增路由
└── console.php                             # MODIFY: 註冊排程
```

---

## 實作細節

### DripTrackingController（關鍵修正：教室追蹤用 auth session）

```php
<?php

namespace App\Http\Controllers;

use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DripTrackingController extends Controller
{
    private const PIXEL = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    /**
     * Email 開信追蹤（signed URL，drip 信件中的 pixel）
     */
    public function open(Request $request): Response
    {
        if ($request->hasValidSignature()) {
            $subId = $request->integer('sub');
            $lesId = $request->integer('les');

            try {
                DripEmailEvent::firstOrCreate(
                    ['subscription_id' => $subId, 'lesson_id' => $lesId, 'event_type' => 'opened'],
                    ['ip' => $request->ip(), 'user_agent' => $request->userAgent()]
                );
            } catch (\Exception $e) {
                Log::warning('Drip open tracking failed', ['error' => $e->getMessage()]);
            }
        }

        return response(self::PIXEL, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * 教室促銷按鈕點擊追蹤（auth session 識別訂閱者，不需要 signed URL）
     */
    public function click(Request $request): \Illuminate\Http\RedirectResponse
    {
        $targetUrl = $request->query('url', '/');
        $lessonId  = $request->integer('les');

        $user = $request->user(); // auth middleware 保證已登入
        if ($user && $lessonId) {
            $subscription = DripSubscription::where('user_id', $user->id)
                ->whereHas('course.lessons', fn($q) => $q->where('lessons.id', $lessonId))
                ->first();

            if ($subscription) {
                try {
                    DripEmailEvent::firstOrCreate(
                        ['subscription_id' => $subscription->id, 'lesson_id' => $lessonId, 'event_type' => 'clicked'],
                        ['target_url' => $targetUrl, 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]
                    );
                } catch (\Exception $e) {
                    Log::warning('Drip click tracking failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return redirect()->away($targetUrl);
    }
}
```

**⚠️ 關鍵設計差異（vs 原始錯誤設計）**：
- `open()` 端點保留 signed URL（Email pixel 無法用 session 識別）
- `click()` 端點改用 `auth()->user()` 識別訂閱者，**不使用 signed URL**
- `click()` 路由放在 `auth` middleware 群組內
- `SendDripEmailJob` 不再產生 `promoTrackUrl`
- `DripLessonMail` 移除 `promoTrackUrl` 參數
- `drip-lesson.blade.php` 移除促銷按鈕，只保留 pixel

### ClassroomController 修改（promo_url 追蹤按鈕）

```php
// formatLessonFull() 加入 promo_url（供教室渲染追蹤按鈕）
private function formatLessonFull(Lesson $lesson, ...): array
{
    return [
        // ... existing fields ...
        'promo_url' => $lesson->promo_url
            ? route('drip.track.click', ['les' => $lesson->id, 'url' => $lesson->promo_url])
            : null,
    ];
}
```

**⚠️ 注意**：傳給前端的不是原始 `promo_url`，而是已包裝成 `/drip/track/click?les=X&url=...` 的追蹤 URL，前端直接使用。

### routes/web.php（click 移入 auth 群組）

```php
// Public：開信 pixel（無需登入）
Route::get('/drip/track/open', [DripTrackingController::class, 'open'])
    ->name('drip.track.open');

// Auth：教室促銷點擊（需要登入，用 session 識別訂閱者）
Route::middleware('auth')->group(function () {
    Route::get('/drip/track/click', [DripTrackingController::class, 'click'])
        ->name('drip.track.click');
});
```

### LessonForm.vue（label 修正）

```vue
<!-- promo_url 欄位 label 更新 -->
<label :class="labelClasses">促銷連結 URL（教室追蹤）</label>
<input
  v-model="form.promo_url"
  type="url"
  :class="inputClasses"
  placeholder="https://example.com/product/..."
/>
<p :class="helpTextClasses">設定後，教室頁面顯示可追蹤點擊的促銷按鈕。留空則不顯示。</p>
```

### Classroom.vue（渲染教室追蹤按鈕）

```vue
<!-- 在 LessonPromoBlock 後方渲染 promo_url 追蹤按鈕 -->
<a
  v-if="currentLesson?.promo_url"
  :href="currentLesson.promo_url"
  style="display:inline-block;background:#F0C14B;color:#373557;padding:12px 40px;border-radius:9999px;font-weight:600"
>
  立即瞭解
</a>
```

### drip-lesson.blade.php（移除促銷按鈕）

```blade
{{-- 移除：不再有 promoTrackUrl 按鈕 --}}

{{-- 保留：Tracking Pixel --}}
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
```

---

## Phase Completion Status

| Phase | Status | Output |
|-------|--------|--------|
| Phase 0: Research | ✅ Complete | [research.md](./research.md) |
| Phase 1: Design | ✅ Complete | [data-model.md](./data-model.md), [contracts/](./contracts/), [quickstart.md](./quickstart.md) |
| Phase 2: Tasks | ✅ Complete | [tasks.md](./tasks.md) |
| Phase 15: promo_url 設計修正 | ⚠️ Pending tasks update | 本 plan.md 更新完成 |

---

## 增量更新：準時到課獎勵區塊（US11）- 2026-02-21

**新增功能（2026-02-21）**：在免費觀看期倒數旁加入獎勵欄（right column）。會員進入頁面後開始 per-session 計時，停留滿 `reward_delay_minutes` 分鐘後顯示管理員自訂 `reward_html`。達標狀態以 localStorage 永久記錄；免費期逾期後保留已達標獎勵，未達標則顯示「下次早點來喔，錯過了獎勵 :(」。

### 檔案變更（US11）

```text
database/migrations/
└── YYYY_add_reward_html_to_lessons.php          # NEW

config/
└── drip.php                                     # MODIFY: 新增 reward_delay_minutes

app/Models/Lesson.php                            # MODIFY: reward_html 到 $fillable
app/Http/Controllers/Admin/ChapterController.php # MODIFY: lesson map 加入 reward_html + course_type
app/Http/Controllers/Member/ClassroomController.php # MODIFY: reward_html + reward_delay_minutes
app/Http/Requests/Admin/StoreLessonRequest.php   # MODIFY: reward_html 驗證

resources/js/Components/Admin/LessonForm.vue     # MODIFY: reward_html textarea (v-if drip)
resources/js/Components/Classroom/VideoAccessNotice.vue # MODIFY: 雙欄佈局 + 獎勵欄邏輯
resources/js/Pages/Member/Classroom.vue          # MODIFY: 傳 reward props 給 VideoAccessNotice
```

---

## 增量更新：Email 追蹤分析（US12~US14 修正版）- 2026-03-01

**設計修正**：移除 Email 促銷按鈕追蹤，改為教室促銷點擊追蹤。

### 刪除的檔案變更

- `SendDripEmailJob` 中的 `promoTrackUrl` 產生邏輯 → **移除**
- `DripLessonMail` 中的 `promoTrackUrl` 參數 → **移除**
- `drip-lesson.blade.php` 中的促銷按鈕區塊 → **移除**

### 保留並修正的變更

```text
database/migrations/
├── YYYY_create_drip_email_events_table.php      # NEW（結構不變）
└── YYYY_add_promo_url_to_lessons.php            # NEW（欄位不變，用途改為教室）

app/Models/DripEmailEvent.php                    # NEW（結構不變）
app/Models/DripSubscription.php                  # MODIFY: emailEvents() HasMany
app/Models/Lesson.php                            # MODIFY: promo_url 到 $fillable
app/Http/Controllers/DripTrackingController.php  # NEW（click() 改用 auth session）
app/Http/Requests/Admin/StoreLessonRequest.php   # MODIFY: promo_url 驗證
app/Http/Controllers/Admin/CourseController.php  # MODIFY: subscribers() 加入統計
app/Services/DripService.php                     # MODIFY: getSubscriberStats()

resources/js/Components/Admin/LessonForm.vue     # MODIFY: promo_url label 改為「教室追蹤」
resources/js/Pages/Admin/Courses/Subscribers.vue # MODIFY: Lesson 統計表（點擊=教室點擊）
resources/js/Pages/Member/Classroom.vue          # MODIFY: 渲染 promo_url 追蹤按鈕
resources/views/emails/drip-lesson.blade.php     # MODIFY: 加入 pixel，移除促銷按鈕

routes/web.php                                   # MODIFY: click 路由移入 auth 群組
```

---

---

## 增量更新：per-lesson 影片觀看期限 - 2026-03-01

**設計修正**：影片免費觀看時數從全站 config 設定改為 per-lesson 欄位 `video_access_hours`。

### 核心變更

```text
database/migrations/
└── YYYY_add_video_access_hours_to_lessons.php   # NEW

app/Models/Lesson.php                            # MODIFY: video_access_hours 加入 $fillable
app/Services/DripService.php                     # MODIFY: 計算改讀 lesson.video_access_hours（非 config）
app/Http/Controllers/Member/ClassroomController.php  # MODIFY: 傳 video_access_hours 給前端，條件更新
app/Http/Controllers/Admin/ChapterController.php     # MODIFY: lesson map 加入 video_access_hours
app/Http/Requests/Admin/StoreLessonRequest.php       # MODIFY: video_access_hours 驗證規則

resources/js/Components/Admin/LessonForm.vue         # MODIFY: 新增 video_access_hours 數字輸入欄
resources/js/Pages/Member/Classroom.vue              # MODIFY: 渲染條件改為 lesson.video_access_hours !== null
resources/views/emails/drip-lesson.blade.php         # MODIFY: 動態讀取 lesson.video_access_hours

config/drip.php                                      # MODIFY: 移除 video_access_hours（保留 reward_delay_minutes）
```

### 關鍵實作細節

**DripService 計算修改**：

```php
public function getVideoAccessExpiresAt(DripSubscription $subscription, Lesson $lesson): ?Carbon
{
    $hours = $lesson->video_access_hours; // 改讀 lesson 欄位，非 config
    if ($hours === null) {
        return null; // null = 無限期觀看，不啟用計時
    }
    $unlockDay = $lesson->sort_order * $subscription->course->drip_interval_days;
    $unlockAt = $subscription->subscribed_at->copy()->addDays($unlockDay);
    return $unlockAt->addHours($hours);
}
```

**Classroom.vue 渲染條件修改**：
- 原條件：`course is drip + lesson has video + subscription not converted + (expired or remaining > 0)`
- 新條件：`course is drip + lesson has video + lesson.video_access_hours !== null + subscription not converted + (expired or remaining > 0)`

**Email 模板修改**：

```blade
@if($lesson->video_access_hours)
⏰ 影片 {{ $lesson->video_access_hours }} 小時內免費觀看，把握時間！
@endif
```

**LessonForm.vue 新增欄位**：

```vue
<label>影片觀看期限（小時）</label>
<input
  v-model="form.video_access_hours"
  type="number"
  min="1"
  placeholder="留空表示無限期觀看"
/>
<p>drip 課程有影片的 Lesson 專用。設定後啟用倒數計時與準時到課獎勵欄。</p>
```

### 影響範圍

- **US10**（影片免費觀看期）：設定方式從全站 config 改為 per-lesson 欄位，業務邏輯不變
- **US11**（準時到課獎勵）：顯示前提改為「Lesson 設定了 `video_access_hours`」，邏輯不變
- **Email 模板**：動態讀取 lesson 時數，不再固定顯示固定小時數

---

## Next Steps

1. Run `/speckit.tasks` 產生 Phase 15（promo_url 修正）及 Phase 16（per-lesson video_access_hours）的 task 清單

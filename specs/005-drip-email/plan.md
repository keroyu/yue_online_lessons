# Implementation Plan: Email 連鎖加溫系統 (Drip Email System)

**Branch**: `005-drip-email` | **Date**: 2026-02-05 | **Spec**: [spec.md](./spec.md)

## Summary

擴充現有課程系統，新增「連鎖課程」類型（drip course）。當使用者訂閱後，系統依照固定天數間隔自動解鎖 Lesson 並發送 Email 通知。這是一個行銷漏斗，目標是導引客戶購買進階課程。當訂閱者購買任一目標課程時，自動停止發信並獎勵解鎖全部內容。

**新增功能（2026-02-05）**：Lesson 促銷區塊 - 在 Lesson 內可設定延遲顯示的促銷區塊（自訂 HTML），用於建立價值感和過濾精準名單。適用於所有課程類型。

**新增功能（2026-02-16）**：影片免費觀看期限 - Drip 課程 Lesson 解鎖後一定時數內為免費觀看期，過期後影片仍可觀看但顯示加強版促銷區塊（方案 A：軟性提醒）。~~設定值存於 config 檔案。~~ **（2026-03-01 更新）改為 per-lesson `video_access_hours` 欄位（nullable 整數），null = 無限期觀看。**

**新增功能（2026-02-21）**：準時到課獎勵區塊 - 在免費觀看期倒數旁加入獎勵欄，停留滿設定時間後顯示管理員自訂獎勵 HTML。

**新增功能（2026-02-28）**：Email 追蹤分析 - Tracking Pixel 開信追蹤、Lesson 統計報表（開信率/點擊率/轉換率）、訂閱者開信進度指示。

**設計修正（2026-03-01）**：promo_url 用途修正 - 從「drip Email 按鈕追蹤」改為「教室促銷點擊追蹤」。Email 不包含促銷按鈕；教室頁面以 auth session 識別訂閱者，無需 signed URL。

**新增功能（2026-03-01）**：Email 個人化問候語 - drip 信件主旨加入「{名字}，」前綴，信件開頭加入「Hi {名字}，」問候段落（獨立一行，與後續內容間隔一空行）。名字優先取 nickname，fallback 至 real_name；3 個中文字取後 2 字，其餘全名；無名字時略過。

**設計修正（2026-03-01）**：精簡 drip 信件模板 - 移除課程/Lesson 標題行、影片提醒、免費觀看期提示、教室連結、退訂連結等系統自動產生的固定區塊。Email 結構改為「問候語（選填）＋ Lesson HTML 正文＋ tracking pixel」，所有連結由管理員在 Lesson 內容中手動維護。

**新增功能（2026-03-02）**：訂閱時強制填寫暱稱 - 訪客訂閱表單加入必填暱稱欄位（Step 1 + Step 2），確保新訂閱者有 nickname 以供 drip 信件個人化問候語使用；已登入但無暱稱的會員訂閱時顯示暱稱欄位，空白時禁用按鈕。

**設計修正（2026-03-02）**：暱稱欄位行為調整 - 改為永遠顯示（含已有暱稱的已登入會員）並預填現有值，允許訂閱時確認/修改；verify() 改為一律覆蓋舊暱稱；新增 `regex:/\p{L}/u` 驗證防純空格/符號；前端 `memberNickname` 初始值改讀現有 nickname；移除 `needsNickname` computed。

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
│   └── SendDripEmailJob.php                # NEW: 發信 Job（不含 promoTrackUrl）。使用 CommonMarkConverter 將 lesson.html_content（Markdown）渲染為 HTML 後傳入 DripLessonMail
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
│       │   └── Show.vue                    # MODIFY: 顯示訂閱按鈕/表單；onMounted 偵測 flash.drip_subscribed 後 scrollIntoView 至訂閱區塊
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
        'promo_url' => (!$isLocked && $lesson->promo_url)
            ? route('drip.track.click', ['les' => $lesson->id, 'url' => $lesson->promo_url])
            : null,
    ];
}
```

**注意**：傳給前端的不是原始 `promo_url`，而是已包裝成 `/drip/track/click?les=X&url=...` 的追蹤 URL，前端直接使用。不限 drip 訂閱者，所有有存取權用戶均可取得此 URL（非 drip 用戶點擊時 DripTrackingController 找不到訂閱記錄，仍正常 redirect）。

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
<!-- promo_url 按鈕嵌入 LessonPromoBlock 內（與 promo_html 同受延遲計時控制） -->
<LessonPromoBlock
  v-if="selectedLesson.promo_delay_seconds !== null
    && selectedLesson.promo_delay_seconds !== undefined
    && (selectedLesson.promo_html || selectedLesson.promo_url)"
  :key="selectedLesson.id"
  :lesson-id="selectedLesson.id"
  :delay-seconds="selectedLesson.promo_delay_seconds"
  :promo-html="selectedLesson.promo_html"
  :promo-url="selectedLesson.promo_url"
/>
```

**注意**：v-if 條件由原本的 `promo_html` 改為 `(promo_html || promo_url)`，使純 promo_url（無自訂 HTML）也能顯示促銷區塊。

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
| Phase 15: promo_url 設計修正 | ✅ Complete | promo_url 嵌入 LessonPromoBlock；條件修正；$dripSubscription 限制移除 |
| Phase 16: per-lesson video_access_hours | ✅ Complete | video_access_hours 移至 Lesson 欄位；config 移除；Email 動態時數 |
| Phase 17: US15 sidebar filter | ✅ Complete | T101~T103 |

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

---

## 增量更新：Drip 課程教室側邊欄過濾（US15）- 2026-03-01

**設計決策**：Drip 課程的唯一目的是建立信任感並吸引轉單。純文字 Lesson 是 Email 加溫內容，不應出現在教室章節清單。顯示完整序列（含「X 天後解鎖」）會讓訂閱者提前看清漏斗全貌，消除購買動機。

**新規則**：`顯示條件 = has_video AND is_unlocked`（drip 課程側邊欄專屬）

### 檔案變更（US15）

```text
app/Http/Controllers/Member/ClassroomController.php  # MODIFY: sidebar 雙重過濾 + 預設 currentLesson 選擇
resources/js/Pages/Member/Classroom.vue              # MODIFY: 空狀態提示（無任何可顯示的影片課）
```

### 關鍵實作細節

**ClassroomController 修改（chapters + standaloneLessons sidebar 過濾）**：

```php
// 原有過濾：drip 課程移除 locked lessons
// 新增過濾：drip 課程額外移除無影片的 lessons
->filter(fn ($lesson) =>
    (!isset($lessonUnlockMap[$lesson->id]) || $lessonUnlockMap[$lesson->id])
    && (!$isDrip || !empty($lesson->video_id))  // NEW: drip 課程過濾純文字
)
```

此規則在 `chapters` 的 lessons filter 和 `standaloneLessons` filter 均需套用。

**ClassroomController 修改（預設 currentLesson 選擇邏輯）**：

```php
if ($isDrip) {
    $currentLesson = $allLessons->first(fn ($lesson) =>
        (!isset($lessonUnlockMap[$lesson->id]) || $lessonUnlockMap[$lesson->id])
        && !empty($lesson->video_id)                          // NEW: 優先選有影片的
        && !in_array($lesson->id, $completedLessonIds)
    ) ?? $allLessons->first(fn ($lesson) =>
        (!isset($lessonUnlockMap[$lesson->id]) || $lessonUnlockMap[$lesson->id])
        && !empty($lesson->video_id)                          // NEW: fallback 也要有影片
    ) ?? null;                                                // 若無任何有影片的已解鎖 lesson
}
```

**⚠️ 重要邊界**：
- 側邊欄過濾不影響直接訪問。訂閱者透過 Email 連結（`?lesson_id=X`）仍可訪問純文字 Lesson 的內容；存取控制邏輯不變，只有側邊欄清單被過濾
- `currentLesson` 若由 `$requestedLessonId` 指定（Email 連結），純文字 Lesson 也可作為 `currentLesson` 正常顯示
- 此規則**僅適用 drip 課程**。Standard 課程完全不受影響

**Classroom.vue 空狀態提示**（當 drip 課程無任何可顯示的影片課時）：

```vue
<!-- drip 課程，側邊欄為空時 -->
<p v-if="course.is_drip && chapters.length === 0 && standaloneLessons.length === 0"
   class="text-sm text-gray-500 p-4">
  你的課程正在準備中，請留意 Email 通知。
</p>
```

### 影響範圍

- **US6**（在教室中觀看連鎖課程）：側邊欄顯示邏輯改變，不再顯示純文字或未解鎖課程
- **US15**（新增）：完整實作上述過濾規則
- 不影響 Email 發送邏輯、存取控制、promo block、video access notice 等其他功能

---

---

## 增量更新：Email 個人化問候語 - 2026-03-01

**背景**：drip 信件缺乏個人化，對所有訂閱者發送完全相同的信件。加入收件者姓名於主旨與信件開頭，提升親切感與開信率。

**修改檔案**：
- `app/Jobs/SendDripEmailJob.php` - 新增 `resolveGreetingName(User $user): string` helper，計算顯示名稱後傳入 DripLessonMail
- `app/Mail/DripLessonMail.php` - 新增 `greetingName` 參數（`string $greetingName = ''`），`envelope()` 加入名字前綴至主旨
- `resources/views/emails/drip-lesson.blade.php` - 正文最上方加入 `@if($greetingName)<p>Hi {{ $greetingName }}，</p>@endif`，獨立段落與後續內容間隔一空行

**設計決策**：
- **名字來源優先順序**：`nickname` → `real_name` → 空字串；nickname 是用戶自選稱呼，最親切
- **3 字中文截取規則**：`mb_strlen($name) === 3 && preg_match('/^[\x{4e00}-\x{9fff}]+$/u', $name)` → `mb_substr($name, 1)` 取後 2 字（王小明 → 小明）
- **非 3 字一律全名**：2 字（小明）、4 字（歐陽美玲）、英文（Amy）、中英混合一律取全名，避免誤截
- **主旨格式**：`{名字}，{Lesson 標題}`；無名字時維持原格式 `{Lesson 標題}`
- **問候語版型**：`Hi {名字}，`（無「最近好嗎」等附加語），簡潔不累贅；獨立 `<p>` 標籤，與後續課程標題行間隔一空行

---

## 增量更新：精簡 drip 信件模板 - 2026-03-01

**背景**：現有 Email 模板包含大量系統自動產生的固定區塊（課程/Lesson 標題、影片提醒、教室連結、退訂連結），使信件看起來像系統通知而非日常對話式 Email，降低開信率與閱讀意願。

**修改檔案**：
- `resources/views/emails/drip-lesson.blade.php` - 移除課程/Lesson 標題行（`$courseName — $lessonTitle`）、影片提醒區塊（`@if($hasVideo)`）、免費觀看期提示（`$videoAccessHours`）、教室連結（`$classroomUrl`）、退訂連結（`$unsubscribeUrl`）

**設計決策**：
- **移除固定區塊**：系統自動填入的標題行和功能連結讓信件有「機器人感」；改為讓管理員完全掌控信件內文
- **連結手動維護**：教室連結、退訂連結等改由管理員在 Lesson 的 `md_content` 中手動寫入，彈性更高，也更符合真實 Email 寫作習慣
- **最終 Email 結構**：`問候語（Hi {名字}，，若設定）` → `Lesson HTML 正文` → `tracking pixel`（隱藏）

**最終 blade 模板結構**：

```blade
@if($greetingName)
<p>Hi {{ $greetingName }}，</p>
@endif

@if($htmlContent)
{!! $htmlContent !!}
@else
<p>新的課程內容已經解鎖了，請至網站觀看。</p>
@endif

{{-- Tracking pixel (hidden) --}}
@if($openPixelUrl)
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
@endif
```

---

## 增量更新：訂閱 Drip 課程時強制填寫暱稱 - 2026-03-02

**背景**：Email 個人化問候語功能（FR-075/FR-076）需要 `nickname`，但訂閱流程原本不收集暱稱，導致新訂閱者無暱稱可用，發信時略過問候語。透過在訂閱入口強制填寫暱稱，確保每位訂閱者都有名字可供問候。

**修改檔案**：
- `app/Http/Middleware/HandleInertiaRequests.php` - 新增 `drip_nickname` 至 flash shared data，讓 Step 2 能讀取 Step 1 傳入的暱稱
- `app/Http/Controllers/DripSubscriptionController.php` - `subscribe()` 加入 nickname 必填驗證並 flash `drip_nickname`；`verify()` 加入 nickname 驗證並在建立/更新 user 時帶入；`memberSubscribe()` 使用 `Rule::requiredIf` 條件驗證並更新 user
- `resources/js/Components/Course/DripSubscribeForm.vue` - Step 1 加入暱稱輸入欄位與錯誤顯示；nickname ref 初始值從 `flash.drip_nickname` 讀取；Step 1 + Step 2 POST 均帶入 nickname
- `resources/js/Pages/Course/Show.vue` - 新增 `needsNickname` computed（已登入但 nickname 為空）、`memberNickname` ref；無暱稱時顯示暱稱輸入欄並禁用訂閱按鈕；memberSubscribe 帶入 nickname

**設計決策**：
- **Step 2 暱稱傳遞**：透過 flash session（`drip_nickname`）而非 URL 參數，避免暱稱外露於網址列；前端讀取 flash 初始化 ref，使用者進入 Step 2 無需重新輸入
- **條件驗證**（memberSubscribe）：使用 `Rule::requiredIf(empty($user->nickname))` 而非前端 disabled 單獨防護，確保後端也有驗證邏輯，有暱稱的會員傳入的 nickname 欄位為 nullable 不影響原流程
- **不覆蓋現有暱稱**：`elseif (!$user->nickname)` 條件確保已有暱稱的用戶不會被訪客輸入的值覆蓋

---

## 增量更新：暱稱欄位行為調整 - 2026-03-02

**背景**：Phase 20 的初版實作僅對「無暱稱會員」顯示欄位，已有暱稱的會員可直接訂閱。後續決策改為永遠顯示並預填，讓所有訂閱者在訂閱前都有機會確認或修改暱稱，同時加入輸入內容安全性驗證。

**修改檔案**：
- `app/Http/Controllers/DripSubscriptionController.php` - 三個方法統一加入 `regex:/\p{L}/u` 驗證規則和對應中文錯誤訊息；`trim()` 所有儲存動作；`verify()` 移除 `elseif (!$user->nickname)` 條件改為一律更新；`memberSubscribe()` 移除 `Rule::requiredIf` 改為 `required`、移除條件判斷直接呼叫 update；移除 `use Illuminate\Validation\Rule` import
- `resources/js/Pages/Course/Show.vue` - `memberNickname` ref 初始值改為 `page.props.auth?.user?.nickname || ''`（預填現有暱稱）；移除 `needsNickname` computed；暱稱輸入欄改為永遠顯示（移除 `v-if="needsNickname"`）；按鈕 disabled 改為 `!memberNickname.trim()`；`memberSubscribe` 永遠傳送 nickname

**設計決策**：
- **永遠顯示欄位**：讓使用者訂閱前確認暱稱，避免多年前填的暱稱不符現況；UI 預填舊值降低摩擦
- **一律覆蓋暱稱**（verify + memberSubscribe）：訂閱即是一次「身份確認」，輸入值即為最新意願；若需保留舊值只需不改動預填值
- **`regex:/\p{L}/u`**：要求至少一個 Unicode 文字（中/英/日等均可），拒絕純空白、純符號、純數字，不限制語言，對非英語用戶友好
- **`trim()` 儲存**：去除首尾空白，防止資料庫存入無效空白字串；Laravel Eloquent ORM 已透過 PDO prepared statement 防 SQL injection
- **前端 `.trim()` 驗證**：`!memberNickname.trim()` 使純空格輸入無法啟用按鈕（前端早期攔截，後端仍有 regex 雙重保護）

## Next Steps

1. Run `/speckit.tasks` 產生 Phase 17（US15 sidebar filter）的 task 清單

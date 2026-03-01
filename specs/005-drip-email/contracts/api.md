# API Contracts: Email 連鎖加溫系統

**Feature**: 005-drip-email
**Date**: 2026-02-05
**Updated**: 2026-02-16 (新增影片免費觀看期限)

## Overview

本功能使用 Inertia.js，大部分「API」是透過 Inertia 的 page props 和 form submissions 實現。以下定義各端點的 request/response 格式。

---

## Public Routes

### POST /drip/subscribe

**Description**: 訪客訂閱連鎖課程（發送驗證碼）

**Request**:
```json
{
  "course_id": 123,
  "email": "user@example.com"
}
```

**Response (Success - 200)**:
```json
{
  "message": "驗證碼已發送到您的信箱",
  "email": "user@example.com"
}
```

**Response (Error - 422)**:
```json
{
  "message": "此課程已無法訂閱",
  "errors": {
    "course_id": ["您已經退訂過此課程，無法再次訂閱"]
  }
}
```

---

### POST /drip/verify

**Description**: 驗證碼確認並完成訂閱

**Request**:
```json
{
  "course_id": 123,
  "email": "user@example.com",
  "code": "123456"
}
```

**Response (Success - Inertia Back)**:
- 停留在原頁面（back），不跳轉、不彈出 Modal
- Flash `drip_subscribed: true` → 前端在訂閱區塊內 inline 顯示成功訊息（綠色勾勾 + 「訂閱成功」 + 信箱提示）

**Response (Error - 422)**:
```json
{
  "message": "驗證碼錯誤",
  "errors": {
    "code": ["驗證碼不正確或已過期"]
  }
}
```

---

### GET /drip/unsubscribe/{token}

**Description**: 顯示退訂確認頁面

**Inertia Page**: `Drip/Unsubscribe`

**Props**:
```typescript
{
  subscription: {
    id: number;
    course: {
      id: number;
      name: string;
    };
  };
}
```

---

### POST /drip/unsubscribe/{token}

**Description**: 確認退訂

**Request**: Empty body

**Response (Success - Inertia Redirect)**:
- 重導向至首頁 `/`
- Flash message: "您已成功退訂，將不再收到後續通知信。"

---

## Member Routes (requires auth)

### POST /member/drip/subscribe/{course}

**Description**: 已登入會員一鍵訂閱

**Request**: Empty body

**Response (Success - Inertia Back)**:
- 停留在原頁面（back），不跳轉、不彈出 Modal
- Flash `drip_subscribed: true` → 前端在訂閱區塊內 inline 顯示成功訊息（綠色勾勾 + 「訂閱成功」 + 信箱提示）

**Response (Error - 422)**:
```json
{
  "message": "您已經訂閱過此課程"
}
```

---

### GET /member/classroom/{course}

**Description**: 教室頁面（已修改，支援 drip 課程）

**Inertia Page**: `Member/Classroom`

**Props** (when course is drip type):
```typescript
{
  course: {
    id: number;
    name: string;
    course_type: 'standard' | 'drip';
    drip_interval_days: number | null;
  };
  lessons: Array<{
    id: number;
    title: string; // locked lessons: frontend displays as "******"
    sort_order: number; // 0-based
    video_platform: string | null;
    video_id: string | null;
    md_content: string | null;
    // Drip-specific fields
    is_unlocked: boolean;
    unlock_in_days: number | null; // null if already unlocked
    // Promo block fields (all courses)
    promo_delay_seconds: number | null; // null=disabled, 0=immediate, >0=delay
    promo_html: string | null;
    // Video access window (drip courses only, unlocked lessons with video)
    video_access_expired: boolean; // true if free viewing window has passed
    video_access_remaining_seconds: number | null; // null if expired or no video, seconds remaining otherwise
  }>;
  subscription: {
    id: number;
    subscribed_at: string; // ISO 8601
    emails_sent: number;
    status: 'active' | 'converted' | 'completed' | 'unsubscribed';
  } | null; // null if not subscribed (shouldn't happen for drip courses)
  // Video access urgency promo (drip courses only)
  videoAccessTargetCourses: Array<{
    id: number;
    name: string;
    url: string;
  }>; // empty array if no target courses set
}
```

---

## Admin Routes (requires auth + admin role)

### GET /admin/courses/{course}/edit (modified — drip settings integrated)

**Description**: 課程編輯頁面（已修改，包含連鎖課程設定區塊）

**Inertia Page**: `Admin/Courses/Edit`

**Additional Props for drip settings**:
```typescript
{
  // ... existing course edit props ...
  availableCourses: Array<{
    id: number;
    name: string;
  }>; // courses that can be set as targets (excluding self)
  targetCourseIds: number[]; // current target course IDs
}
```

**Frontend Behavior**: When `course.course_type === 'drip'`, the edit page shows an additional drip settings section with interval days input, target course multi-select, and lesson schedule preview.

---

### PUT /admin/courses/{course} (modified — drip fields added)

**Description**: 更新課程（已修改，支援連鎖課程設定欄位）

**Additional Request Fields**:
```json
{
  "course_type": "drip",
  "drip_interval_days": 3,
  "target_course_ids": [456, 789]
}
```

**Validation** (added to `UpdateCourseRequest`):
- `course_type`: required, in:standard,drip
- `drip_interval_days`: required_if:course_type,drip, integer, min:1, max:30
- `target_course_ids`: nullable array, each exists:courses,id

**Response**: Same as existing course update (redirect to edit page with flash)

---

### GET /admin/courses/{course}/subscribers

**Description**: 訂閱者清單

**Inertia Page**: `Admin/Courses/Subscribers`

**Props**:
```typescript
{
  course: {
    id: number;
    name: string;
  };
  subscribers: {
    data: Array<{
      id: number;
      user: {
        id: number;
        email: string;
        nickname: string | null;
      };
      subscribed_at: string;
      emails_sent: number;
      status: 'active' | 'converted' | 'completed' | 'unsubscribed';
      status_changed_at: string | null;
    }>;
    // Pagination
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters: {
    status: string | null;
  };
  stats: {
    total: number;
    active: number;
    converted: number;
    completed: number;
    unsubscribed: number;
  };
}
```

**Query Parameters**:
- `status`: Filter by status (active, converted, completed, unsubscribed)
- `page`: Pagination

---

## Webhook (existing, modified)

### POST /webhook/portaly

**Description**: Portaly 付款通知（已修改，加入轉換檢測）

**Existing Behavior**: 建立 Purchase 記錄

**New Behavior** (after purchase creation):
1. 檢查購買的課程是否為某連鎖課程的目標
2. 若是，將該使用者在對應連鎖課程的訂閱狀態更新為 `converted`

---

## Course Detail Page (modified)

### GET /course/{course}

**Description**: 課程詳情頁（已修改，支援 drip 訂閱按鈕）

**Additional Props for drip courses**:
```typescript
{
  // ... existing props ...

  // New for drip courses
  is_drip: boolean;
  user_subscription: {
    status: 'active' | 'converted' | 'completed' | 'unsubscribed';
  } | null; // null if not subscribed
  can_subscribe: boolean; // false if already subscribed or unsubscribed before
}
```

---

## Classroom Page - Promo Block (all courses)

**Note**: Lesson 內容顯示於 `Member/Classroom` 頁面內，不是獨立頁面。促銷區塊透過 `currentLesson` prop 傳遞。

### GET /member/classroom/{course}

**Description**: 教室頁面（已修改，`currentLesson` 新增促銷區塊欄位）

**Additional fields in `currentLesson` prop**:
```typescript
{
  currentLesson: {
    // ... existing fields ...
    // Promo block (NEW)
    promo_delay_seconds: number | null;
    promo_html: string | null;
    promo_url: string | null;  // 已包裝為 /drip/track/click?les=X&url=... 追蹤 URL
  };
}
```

**Frontend Behavior** (in `LessonPromoBlock.vue` via `Classroom.vue`):
- `promo_delay_seconds = null` 或 `promo_html` 與 `promo_url` 均為空 → 不顯示促銷區塊
- `promo_delay_seconds = 0` → 立即顯示促銷內容（`promo_html` + `promo_url` 按鈕，若有設定）
- `promo_delay_seconds > 0` → 顯示倒數計時，達標後顯示促銷內容
- `promo_url` 按鈕與 `promo_html` 同在 LessonPromoBlock 內，同受延遲計時控制；對所有有存取權用戶顯示
- 達標狀態存於 localStorage（key: `promo_unlocked_lesson_{lesson_id}`），永久有效

---

## Classroom Page - Video Access Window (drip courses only)

**Note**: 影片免費觀看期限資料透過 `lessons` prop 中的 `video_access_expired` 和 `video_access_remaining_seconds` 傳遞。目標課程資訊透過 `videoAccessTargetCourses` prop 傳遞。

### GET /member/classroom/{course}

**Description**: 教室頁面（已修改，新增影片免費觀看期限欄位）

**Additional fields in lesson prop** (drip courses, unlocked lessons with video only):
```typescript
{
  // Per-lesson video access status
  video_access_expired: boolean;        // false if within window or no video
  video_access_remaining_seconds: number | null; // null if expired/no video
}
```

**Additional page-level prop**:
```typescript
{
  videoAccessTargetCourses: Array<{
    id: number;
    name: string;
    url: string;   // e.g. "/course/123"
  }>;
}
```

**Frontend Behavior** (in `Classroom.vue`):
- `video_access_expired = false` 且 `video_access_remaining_seconds > 0` → 影片下方顯示「課程免費公開中，剩餘 XX:XX:XX」倒數
- `video_access_expired = true` → 影片下方顯示加強版促銷區塊（系統生成，非自訂 HTML）
- `subscription.status = 'converted'` → 不顯示任何觀看期限 UI（Controller 已設 `video_access_expired = false`）
- `lesson.video_id = null`（純文字）→ 不顯示（Controller 已設 `video_access_expired = false`）
- 促銷區塊內容根據 `videoAccessTargetCourses` 動態生成：
  - 有目標課程：「免費觀看期已結束，但我們為你保留了存取權。想要完整學習體驗？」+ 目標課程連結
  - 無目標課程：「想要完整學習體驗？探索更多課程」+ 課程列表連結

---

## Admin Chapters Page (existing route, modified)

**Note**: 章節管理頁面載入 lesson 資料供 `LessonForm.vue` 編輯使用。

### GET /admin/courses/{course}/chapters

**Description**: 章節管理頁面（已修改，lesson map 新增促銷區塊欄位）

**Additional fields in lesson map** (章節內小節 + 獨立小節皆需包含):
```typescript
{
  lessons: Array<{
    // ... existing fields (id, title, duration_formatted, etc.) ...
    // Promo block (NEW)
    promo_delay_seconds: number | null;
    promo_html: string | null;
  }>;
}
```

**⚠️ 重要**: 此 lesson map 是 `ChapterList.vue` → `LessonForm.vue` 的資料來源。若缺少 promo 欄位，編輯表單開啟時促銷區塊設定會顯示空白。

---

## Admin Lesson Update (existing route, modified)

**Note**: Lesson 編輯使用 `LessonForm.vue` 組件（Modal 形式），不是獨立頁面。

### PUT /admin/lessons/{lesson}

**Description**: 更新 Lesson（已修改，支援促銷區塊）

**Additional Request Fields**:
```json
{
  "promo_delay_seconds": 5,
  "promo_html": "<div class=\"bg-yellow-100 p-4\"><h3>限時優惠</h3><a href=\"/course/123\" class=\"btn\">立即購買</a></div>"
}
```

---

## Drip Email Template (modified)

### Email: drip-lesson.blade.php

**Description**: Drip Lesson 通知信（已修改，新增免費觀看期提示）

**Additional Template Content** (for lessons with video):
```html
<!-- 在影片提示後方加入 -->
@if($lesson->video_id)
  <p style="font-size:16px;font-weight:bold;color:#e00">* 本課程包含教學影片，請至網站觀看</p>
  <p style="font-size:16px;font-weight:bold;color:#e00">* 影片 {{ config('drip.video_access_hours') }} 小時內免費觀看，把握時間！</p>
@endif
```

**Note**: 此提示僅在 `config('drip.video_access_hours')` 不為 null 時顯示。

---

## 增量更新：Email 追蹤端點（US12~US14）- 2026-02-28

### GET /drip/track/open

**Description**: Tracking Pixel 端點，記錄開信事件並返回 1x1 透明 GIF

**Auth**: None（無需登入，使用 Laravel Signed URL 驗證）

**Query Params** (由 Signed URL 自動包含):
```
sub       = {subscription_id}
les       = {lesson_id}
expires   = {timestamp}
signature = {hash}
```

**Success Response (200)**:
```
Content-Type: image/gif
[1x1 transparent GIF binary]
```

**Behavior**:
1. 驗證 Signed URL 簽名（無效則仍返回 GIF，但不記錄）
2. `DripEmailEvent::firstOrCreate(['subscription_id' => $sub, 'lesson_id' => $les, 'event_type' => 'opened'], ['ip' => $ip, 'user_agent' => $ua])`
3. 返回 1x1 透明 GIF（不做 redirect）

**Error Response**: 簽名無效或過期時仍返回 GIF（避免信件顯示破圖）

**Route**: `GET /drip/track/open` → `DripTrackingController@open`，名稱 `drip.track.open`

---

### GET /drip/track/click

**Description**: 點擊追蹤 redirect 端點，記錄教室促銷點擊事件並 redirect 到目標 URL

**Auth**: auth middleware（用戶必須已登入，教室頁面保證已登入）

**Query Params**:
```
les = {lesson_id}
url = {urlencode(promo_url)}
```

**Success Response (302 redirect)**:
```
Location: {decoded promo_url}
```

**Behavior**:
1. 解碼 `url` 參數（缺失時 redirect 至首頁並記錄 log）
2. 以 `auth()->user()` 查詢對應 Lesson 所在課程的 DripSubscription（無訂閱記錄時仍執行 redirect，不報錯）
3. 若有訂閱記錄：`DripEmailEvent::firstOrCreate(['subscription_id' => $sub->id, 'lesson_id' => $les, 'event_type' => 'clicked'], ['target_url' => $url, 'ip' => $ip, 'user_agent' => $ua])`
4. `return redirect()->away($url)`

**Error Response (302 redirect to home)**: url 參數缺失時，重定向至首頁並記錄 log

**Route**: `GET /drip/track/click` → `DripTrackingController@click`，名稱 `drip.track.click`（在 auth middleware 群組內）

---

### Admin: GET /admin/courses/{course}/subscribers（擴充）

**Description**: 訂閱者清單（擴充：新增 Lesson 統計 + 訂閱者開信/點擊指標）

**新增 Response Props**:
```json
{
  "lessonStats": [
    {
      "lesson_id": 1,
      "lesson_title": "第一課",
      "sort_order": 0,
      "sent_count": 100,
      "open_count": 45,
      "open_rate": 0.45,
      "has_promo_url": true,
      "click_count": 20,
      "click_rate": 0.20
    }
  ],
  "conversionRate": 0.15,
  "subscribers": {
    "data": [
      {
        "id": 1,
        "user": { "email": "...", "nickname": "..." },
        "status": "active",
        "emails_sent": 3,
        "opened_count": 2,
        "has_clicked": false,
        "subscribed_at": "2026-01-01T09:00:00Z",
        "status_changed_at": null
      }
    ]
  }
}
```

**Note**: `has_clicked` 為布林值（cast from count > 0），`conversionRate` 為課程整體轉換率

---

### Admin: PUT /admin/lessons/{lesson}（擴充）

**Description**: 更新 Lesson（擴充：新增 promo_url 欄位）

**新增 Request Fields**:
```json
{
  "promo_url": "https://example.com/product/123"
}
```

**Validation**: `nullable|url|max:500`

---

### Email: drip-lesson.blade.php（擴充）

**新增 Template Content** (當 Lesson 有 promo_url 時):
```html
<!-- 在 md_content 渲染後、unsubscribe URL 之前 -->
@if($promoTrackUrl)
<p style="text-align:center;margin:24px 0">
  <a href="{{ $promoTrackUrl }}"
     style="display:inline-block;background:#ff5a36;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold">
    {{ $promoButtonText ?? '查看詳情' }}
  </a>
</p>
@endif

<!-- Tracking Pixel（最後一行，不影響顯示） -->
<img src="{{ $openPixelUrl }}" width="1" height="1" alt="" style="display:none">
```

**DripLessonMail 新增參數**:
```php
public string $openPixelUrl,
public ?string $promoTrackUrl = null,
public string $promoButtonText = '查看詳情',
```

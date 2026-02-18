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
    html_content: string | null;
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
  };
}
```

**Frontend Behavior** (in `Classroom.vue`):
- `promo_delay_seconds = null` 或 `promo_html` 為空 → 不顯示促銷區塊
- `promo_delay_seconds = 0` → 立即顯示 `promo_html`
- `promo_delay_seconds > 0` → 顯示倒數計時，達標後顯示 `promo_html`
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
```blade
@if($hasVideo)
  <p>▶▶ 本課程包含教學影片，請至網站觀看</p>
  @if(config('drip.video_access_hours'))
  <p>▶ 影片 {{ config('drip.video_access_hours') }} 小時內免費觀看，把握時間！</p>
  @endif
@endif
```

**Link Format**: 連結以純文字 URL 呈現（文字標籤 + 換行 + URL），非 `<a>` 超連結，以降低垃圾信風險。

**Note**: 影片提示僅在 `config('drip.video_access_hours')` 不為 null 時顯示。不使用粗體紅色等 HTML 樣式，改用 Unicode 符號（▶▶/▶）。

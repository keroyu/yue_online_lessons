# API Contracts: Email 連鎖加溫系統

**Feature**: 005-drip-email
**Date**: 2026-02-05
**Updated**: 2026-02-05 (新增 Lesson 促銷區塊)

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

**Response (Success - Inertia Redirect)**:
- 重導向至 `/member/classroom/{course_id}`
- Flash message: "訂閱成功！第一封信已寄出。"

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

**Response (Success - Inertia Redirect)**:
- 重導向至 `/member/classroom/{course_id}`
- Flash message: "訂閱成功！第一封信已寄出。"

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
    title: string;
    sort_order: number;
    video_platform: string | null;
    video_id: string | null;
    html_content: string | null;
    // Drip-specific fields
    is_unlocked: boolean;
    unlock_in_days: number | null; // null if already unlocked
    // Promo block fields (all courses)
    promo_delay_seconds: number | null; // null=disabled, 0=immediate, >0=delay
    promo_html: string | null;
  }>;
  subscription: {
    id: number;
    subscribed_at: string; // ISO 8601
    emails_sent: number;
    status: 'active' | 'converted' | 'completed' | 'unsubscribed';
  } | null; // null if not subscribed (shouldn't happen for drip courses)
}
```

---

## Admin Routes (requires auth + admin role)

### GET /admin/courses/{course}/drip

**Description**: 連鎖課程設定頁面

**Inertia Page**: `Admin/Courses/DripSettings`

**Props**:
```typescript
{
  course: {
    id: number;
    name: string;
    course_type: 'standard' | 'drip';
    drip_interval_days: number | null;
  };
  targetCourses: Array<{
    id: number;
    name: string;
  }>;
  availableCourses: Array<{
    id: number;
    name: string;
  }>; // courses that can be set as targets (excluding self)
  lessonCount: number;
  previewSchedule: Array<{
    lesson_number: number;
    unlock_day: number;
  }>;
}
```

---

### PUT /admin/courses/{course}/drip

**Description**: 更新連鎖課程設定

**Request**:
```json
{
  "course_type": "drip",
  "drip_interval_days": 3,
  "target_course_ids": [456, 789]
}
```

**Response (Success - Inertia Redirect)**:
- 重導向至 `/admin/courses/{course}/edit`
- Flash message: "連鎖課程設定已更新"

**Validation Errors (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "drip_interval_days": ["發信間隔天數必須在 1-30 之間"],
    "target_course_ids.0": ["目標課程不存在"]
  }
}
```

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

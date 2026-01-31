# API Contracts: Member Management

**Feature**: 003-member-management
**Date**: 2026-01-17
**Updated**: 2026-01-18
**Base Path**: `/admin/members`
**Auth**: Requires admin role (middleware: `auth`, `admin`)

---

## Routes Overview

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| GET | /admin/members | index | List members with pagination, search, filters |
| PATCH | /admin/members/{member} | update | Inline edit member fields |
| GET | /admin/members/{member} | show | Get member details with courses |
| POST | /admin/members/batch-email | sendBatchEmail | Send email to selected members |
| POST | /admin/members/gift-course | giftCourse | Gift course to selected members |
| GET | /admin/members/count | count | Count members matching filter (for select all) |

---

## Endpoints

### GET /admin/members

List paginated members with search and filter capabilities.

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | integer | No | Page number (default: 1) |
| per_page | integer | No | Items per page (default: 50, max: 100) |
| search | string | No | Search in email, nickname, real_name |
| course_id | integer | No | Filter by course ownership |
| sort | string | No | Sort field (email, real_name, created_at, last_login_at) |
| direction | string | No | Sort direction (asc, desc, default: desc) |
| selected | array | No | Preserved selection IDs |

**Response** (Inertia Page Props):

```typescript
{
  members: {
    data: Array<{
      id: number
      email: string
      nickname: string | null
      real_name: string | null
      phone: string | null
      birth_date: string | null  // YYYY-MM-DD
      last_login_ip: string | null
      last_login_at: string | null  // ISO 8601
      created_at: string  // ISO 8601
    }>
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  filters: {
    search: string | null
    course_id: number | null
    sort: string
    direction: string
  }
  courses: Array<{
    id: number
    name: string
  }>
  selectedIds: number[]
  matchingCount: number  // Total members matching current filter
}
```

---

### PATCH /admin/members/{member}

Update member fields (inline edit).

**URL Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| member | integer | Member user ID |

**Request Body** (partial update - send only changed fields):

```typescript
{
  email?: string      // Valid email, unique
  real_name?: string  // Max 100 chars
  phone?: string      // Max 20 chars
  nickname?: string   // Max 100 chars (via modal)
  birth_date?: string // YYYY-MM-DD format (via modal)
}
```

**Success Response** (Inertia redirect with flash):

```typescript
// Flash message
{ success: "會員資料更新成功" }
```

**Error Response** (422 Validation Error):

```typescript
{
  errors: {
    email?: ["此 Email 已被使用"]
    birth_date?: ["生日格式不正確"]
    // ... other field errors
  }
}
```

---

### GET /admin/members/{member}

Get member details including courses and progress (for modal).

**URL Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| member | integer | Member user ID |

**Response** (JSON):

```typescript
{
  member: {
    id: number
    email: string
    nickname: string | null
    real_name: string | null
    phone: string | null
    birth_date: string | null
    last_login_ip: string | null
    last_login_at: string | null
    created_at: string
  }
  courses: Array<{
    id: number
    name: string
    purchased_at: string  // ISO 8601
    total_lessons: number
    completed_lessons: number
    progress_percent: number  // 0-100
  }>
}
```

---

### POST /admin/members/batch-email

Send batch email to selected members.

**Request Body**:

```typescript
{
  member_ids: number[]  // Array of member IDs to email
  subject: string       // Email subject (required, max: 200)
  body: string          // Email body (required, max: 10000)
}
```

**Validation Rules**:

| Field | Rules |
|-------|-------|
| member_ids | required, array, min:1 |
| member_ids.* | exists:users,id |
| subject | required, string, max:200 |
| body | required, string, max:10000 |

**Success Response** (JSON):

```typescript
{
  success: true
  message: "已排程發送 {count} 封郵件"
  queued_count: number
  skipped_count: number  // Members without valid email
}
```

**Error Response** (422 Validation Error):

```typescript
{
  errors: {
    member_ids?: ["請選擇至少一位會員"]
    subject?: ["郵件主旨為必填"]
    body?: ["郵件內容為必填"]
  }
}
```

---

### POST /admin/members/gift-course

Gift a course to selected members.

**Request Body**:

```typescript
{
  member_ids: number[]  // Array of member IDs to gift
  course_id: number     // Course to gift
}
```

**Validation Rules**:

| Field | Rules |
|-------|-------|
| member_ids | required, array, min:1 |
| member_ids.* | exists:users,id |
| course_id | required, exists:courses,id |

**Success Response** (JSON):

```typescript
{
  success: true
  message: "已成功贈送課程給 {count} 位會員"
  gifted_count: number      // Members who received the course
  already_owned_count: number  // Members who already owned the course
  email_queued_count: number   // Gift notification emails queued
  skipped_no_email_count: number  // Members without email (gifted but no notification)
}
```

**Error Response** (422 Validation Error):

```typescript
{
  errors: {
    member_ids?: ["請選擇至少一位會員"]
    course_id?: ["請選擇要贈送的課程"]
  }
}
```

**Edge Case Responses**:

```typescript
// When all selected members already own the course
{
  success: false
  message: "所有選取的會員都已擁有此課程"
  gifted_count: 0
  already_owned_count: number
}
```

---

### GET /admin/members/count

Get count of members matching current filter (for "Select all X members" display).

**Query Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search filter |
| course_id | integer | No | Course ownership filter |

**Response** (JSON):

```typescript
{
  count: number
}
```

---

## Error Responses

### 401 Unauthorized

```typescript
// Redirect to login page
```

### 403 Forbidden

```typescript
// Redirect with error flash
{ error: "您沒有權限執行此操作" }
```

### 404 Not Found

```typescript
// Redirect with error flash
{ error: "找不到該會員" }
```

### 500 Server Error

```typescript
{
  error: "伺服器錯誤，請稍後再試"
}
```

---

## Rate Limiting

- Batch email endpoint: 10 requests per minute per admin
- Gift course endpoint: 10 requests per minute per admin
- Other endpoints: Standard Laravel throttle (60 requests/minute)

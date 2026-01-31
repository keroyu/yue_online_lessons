# Data Model: 上課頁面與管理員後臺

**Branch**: `002-classroom-admin` | **Date**: 2026-01-17
**Updated**: 2026-01-26 - 新增 Purchase.type 欄位支援系統指派
**Updated**: 2026-01-30 - 新增 Course.is_visible 欄位支援課程顯示/隱藏設定

## Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│     Course      │       │     Chapter     │
├─────────────────┤       ├─────────────────┤
│ id              │◄──┐   │ id              │
│ name            │   │   │ course_id (FK)  │──┐
│ tagline         │   │   │ title           │  │
│ description     │   │   │ sort_order      │  │
│ description_html│   │   │ created_at      │  │
│ price           │   │   │ updated_at      │  │
│ thumbnail       │   │   └────────┬────────┘  │
│ instructor_name │   │            │           │
│ type            │   │            ▼           │
│ status          │   │   ┌─────────────────┐  │
│ sale_at         │   │   │     Lesson      │  │
│ is_published    │   │   ├─────────────────┤  │
│ sort_order      │   │   │ id              │  │
│ portaly_product_id│ │   │ course_id (FK)  │──┘
│ created_at      │   │   │ chapter_id (FK) │ (nullable)
│ updated_at      │   │   │ title           │
│ deleted_at      │   │   │ video_platform  │
└────────┬────────┘   │   │ video_id        │
         │            │   │ video_url       │
         │            │   │ html_content    │
         │            │   │ duration_seconds│
         │            │   │ sort_order      │
         ▼            │   │ created_at      │
┌─────────────────┐   │   │ updated_at      │
│   CourseImage   │   │   └────────┬────────┘
├─────────────────┤   │            │
│ id              │   │            │
│ course_id (FK)  │───┘            │
│ path            │                │
│ filename        │                ▼
│ created_at      │       ┌─────────────────┐
└─────────────────┘       │ LessonProgress  │
                          ├─────────────────┤
┌─────────────────┐       │ id              │
│      User       │       │ user_id (FK)    │───┐
├─────────────────┤       │ lesson_id (FK)  │───┘
│ id              │◄──────│ created_at      │
│ ...existing...  │       └─────────────────┘
└─────────────────┘
```

---

## Entities

### Course（課程）- 擴充

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| ... | ... | ... | (existing fields from MVP) |
| **status** | enum('draft','preorder','selling') | default: 'draft' | 課程狀態 |
| **sale_at** | timestamp | nullable | 預購開賣時間 |
| **description_html** | longtext | nullable | 課程介紹 HTML |
| **duration_minutes** | int unsigned | nullable | 時間總長（分鐘），前端換算顯示 |
| **portaly_product_id** | varchar(100) | nullable | Portaly 商品 ID，前端組合為完整 URL |
| **original_price** | int unsigned | nullable | 原價（優惠到期後顯示此價格） |
| **promo_ends_at** | timestamp | nullable | 優惠到期時間（預設建立後 30 天） |
| **is_visible** | boolean | default: true | 是否顯示在首頁（隱藏課程仍可透過 URL 存取） |
| deleted_at | timestamp | nullable | 軟刪除時間 |

**定價模式說明（2026-01-17 新增）**:
- `price` = 優惠價（實際售價，必填）
- `original_price` = 原價（可為空）
- `promo_ends_at` = 優惠到期時間（可為空，預設建立後 30 天）
- **顯示邏輯**:
  - 優惠期間（有 original_price 且 promo_ends_at 未到期）：顯示「原價（刪除線）+ 優惠價（醒目）+ 倒數計時（每秒更新，格式 HH:MM:SS）」
  - 優惠到期後：僅顯示原價（無刪除線）
  - 無原價或無到期時間：僅顯示優惠價（無刪除線、無倒數計時）
- **價格區塊位置**: 課程販售頁講師/時間長度右側，使用醒目漸層背景
- **注意**：Portaly 實際售價需管理員手動同步

**Portaly 整合**:
- 只儲存 Product ID（如 `LaHt56zWV8VlHbMnXbvQ`）
- 前端動態產生購買 URL：`https://portaly.cc/kyontw/product/{portaly_product_id}`
- `portaly_url` 欄位已移除（2026-01-17 更新）

**New Indexes**:
- `status`
- `sale_at`
- `promo_ends_at`
- `is_visible`

**Status Transitions**:
```
draft ──發佈──→ preorder (有 sale_at) 或 selling (無 sale_at)
preorder ──時間到──→ selling
preorder/selling ──下架──→ draft
```

**Scopes (new)**:
- `visible()`: where status in ('preorder', 'selling') AND is_published = true AND is_visible = true
- `purchasable()`: where status = 'selling' AND is_published = true
- `hasActivePromo()`: where original_price is not null AND promo_ends_at > now()

**課程顯示/隱藏設定說明（2026-01-30 新增）**:
- `is_visible` = true（預設）：課程顯示在首頁課程列表
- `is_visible` = false：課程不顯示在首頁，但仍可透過直接 URL 存取和購買
- **適用場景**：私人課程、限定會員優惠、測試課程
- **與草稿區別**：草稿課程完全不可存取（404），隱藏課程只是不在列表顯示
- **優先順序**：若課程同時為草稿且隱藏，草稿限制優先（一般用戶無法存取）
- **管理員視角**：管理員在首頁可看到隱藏課程，顯示「隱藏」標籤

---

### Chapter（章）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| course_id | bigint unsigned | FK → courses.id, not null | 所屬課程 |
| title | varchar(255) | not null | 章標題 |
| sort_order | int unsigned | default: 0 | 排序順序 |
| created_at | timestamp | not null | 建立時間 |
| updated_at | timestamp | not null | 更新時間 |

**Indexes**:
- `course_id`
- composite: `course_id, sort_order`

**Relationships**:
- belongsTo: Course
- hasMany: Lesson

---

### Lesson（節/小節）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| course_id | bigint unsigned | FK → courses.id, not null | 所屬課程 |
| chapter_id | bigint unsigned | FK → chapters.id, nullable | 所屬章（可為空） |
| title | varchar(255) | not null | 小節標題 |
| video_platform | enum('vimeo','youtube') | nullable | 影片平台 |
| video_id | varchar(100) | nullable | 影片 ID |
| video_url | varchar(500) | nullable | 原始影片連結 |
| html_content | longtext | nullable | HTML 內容（無影片時使用） |
| duration_seconds | int unsigned | default: 0 | 時長（秒） |
| sort_order | int unsigned | default: 0 | 排序順序 |
| created_at | timestamp | not null | 建立時間 |
| updated_at | timestamp | not null | 更新時間 |

**Indexes**:
- `course_id`
- `chapter_id`
- composite: `course_id, chapter_id, sort_order`

**Relationships**:
- belongsTo: Course
- belongsTo: Chapter (nullable)
- hasMany: LessonProgress

**Computed**:
- `duration_formatted`: 格式化時長 (e.g., "3:50")
- `embed_url`: 根據 platform 生成 embed URL
- `has_video`: video_id is not null

---

### LessonProgress（學習進度）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| user_id | bigint unsigned | FK → users.id, not null | 會員 ID |
| lesson_id | bigint unsigned | FK → lessons.id, not null | 小節 ID |
| created_at | timestamp | not null | 完成時間 |

**Indexes**:
- composite: `user_id, lesson_id` (unique) - 防止重複記錄

**Relationships**:
- belongsTo: User
- belongsTo: Lesson

**Note**: 有記錄即代表已完成，刪除記錄即恢復未完成。

---

### CourseImage（課程圖片）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| course_id | bigint unsigned | FK → courses.id, not null | 所屬課程 |
| path | varchar(500) | not null | 儲存路徑 |
| filename | varchar(255) | not null | 原始檔名 |
| **width** | int unsigned | nullable | 圖片原始寬度（px）|
| **height** | int unsigned | nullable | 圖片原始高度（px）|
| created_at | timestamp | not null | 上傳時間 |

**Indexes**:
- `course_id`

**Relationships**:
- belongsTo: Course

**Storage**:
- Path format: `course-images/{course_id}/{uuid}.{ext}`
- Public URL: `/storage/course-images/{course_id}/{uuid}.{ext}`

**圖片尺寸用途（2026-01-17 新增）**:
- 上傳時自動偵測並儲存原始寬高
- 前端相簿 Modal 選擇圖片時，可根據原始比例計算自適應尺寸
- 用戶僅填寬度時，高度 = 原始高度 × (新寬度 / 原始寬度)
- 用戶僅填高度時，寬度 = 原始寬度 × (新高度 / 原始高度)

---

## Validation Rules

### Course (extended)
- status: required, in ['draft', 'preorder', 'selling']
- sale_at: nullable, date, after:now (when setting to preorder)
- description_html: nullable, string
- duration_minutes: nullable, integer, min 0
- **original_price**: nullable, integer, min 0, should be > price (validation warning if not)
- **promo_ends_at**: nullable, date, after:now (validation error if in past)
- **is_visible**: nullable, boolean (defaults to true)

**Computed (Course)**:
- `duration_formatted`: 換算為「X小時Y分鐘」格式（e.g., 190 → "3小時10分鐘"）
- `is_promo_active`: original_price is not null AND promo_ends_at is not null AND promo_ends_at > now()
- `display_price`: is_promo_active ? price : (original_price ?? price)
- `promo_remaining`: promo_ends_at - now() (for countdown)

### Chapter
- course_id: required, exists in courses
- title: required, max 255
- sort_order: integer, min 0

### Lesson
- course_id: required, exists in courses
- chapter_id: nullable, exists in chapters (must belong to same course)
- title: required, max 255
- video_url: nullable, url, valid Vimeo/YouTube format
- html_content: nullable, string
- duration_seconds: integer, min 0
- sort_order: integer, min 0

### LessonProgress
- user_id: required, exists in users
- lesson_id: required, exists in lessons
- Unique constraint: user_id + lesson_id

### CourseImage
- course_id: required, exists in courses
- image file: required, mimes:jpg,jpeg,png,gif,webp, max:10240 (10MB)

---

## State Transitions

### Course Status
```
┌───────┐     發佈(有sale_at)     ┌──────────┐
│ draft │ ──────────────────────→ │ preorder │
└───────┘                         └──────────┘
    ↑                                   │
    │         下架為草稿                │ 時間到
    │ ←─────────────────────────────────┤
    │                                   ▼
    │         下架為草稿          ┌──────────┐
    └─────────────────────────────│ selling  │
              發佈(無sale_at)     └──────────┘
         ─────────────────────────→
```

---

## Data Seeding (Development)

### Chapters
- 為每個現有課程建立 2-3 個 Chapter
- 包含不同長度的標題

### Lessons
- 每個 Chapter 下建立 2-4 個 Lesson
- 混合 Vimeo 影片和 HTML 內容
- 包含無 Chapter 的獨立 Lesson

### LessonProgress
- 為測試會員建立部分完成記錄
- 驗證完成狀態顯示

### CourseImages
- 為每個課程上傳 2-3 張測試圖片

---

## Purchase（購買紀錄）- 擴充（2026-01-26 新增）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| user_id | bigint unsigned | FK → users.id, not null | 會員 ID |
| course_id | bigint unsigned | FK → courses.id, not null | 課程 ID |
| portaly_order_id | varchar(100) | unique, nullable | Portaly 訂單編號 |
| amount | int unsigned | not null | 金額 |
| currency | varchar(10) | default: 'TWD' | 幣別 |
| discount_code | varchar(50) | nullable | 折扣碼 |
| discount_amount | int unsigned | default: 0 | 折扣金額 |
| status | varchar(20) | default: 'paid' | 狀態 (paid, refunded) |
| **type** | varchar(20) | default: 'paid' | 購買類型 |
| created_at | timestamp | not null | 購買時間 |
| updated_at | timestamp | not null | 更新時間 |

**購買類型說明（2026-01-26 新增）**:
- `paid` = 一般購買（透過 Portaly 付款）
- `system_assigned` = 系統指派（管理員建立課程時自動獲得）
- `gift` = 贈送（管理員後台手動指派）

**系統指派特性**:
- 金額為 $0
- portaly_order_id 格式為 `SYSTEM-{uuid}`
- 不計入銷售統計和報表
- 課程刪除時一併軟刪除

**Indexes**:
- `user_id`
- `course_id`
- composite: `user_id, course_id` (unique)
- `type` (for filtering in reports)

**Relationships**:
- belongsTo: User
- belongsTo: Course

**Scopes (new)**:
- `paid()`: where type = 'paid'
- `systemAssigned()`: where type = 'system_assigned'
- `forSalesReport()`: where type = 'paid' (排除系統指派和贈送)

---

## Course Scopes 擴充（2026-01-26 新增）

**New Scopes for Admin Preview**:
- `visibleToUser($user)`: 根據用戶角色返回可見課程
  - Admin: 所有課程（含草稿、隱藏課程）
  - Member/Guest: 僅預購中和熱賣中，且 is_visible = true

```php
// Course.php (2026-01-30 更新：加入 is_visible 條件)
public function scopeVisibleToUser($query, $user = null)
{
    if ($user && $user->role === 'admin') {
        return $query; // Admin sees all (including hidden)
    }
    return $query->visible(); // Non-admin sees only visible (excludes hidden)
}

// visible() scope 已更新為同時檢查 is_visible = true
```

---

## Validation Rules（擴充）

### Purchase (extended)
- type: required, in ['paid', 'system_assigned', 'gift']
- amount: required when type = 'paid', 0 when type = 'system_assigned'
- portaly_order_id: required when type = 'paid', auto-generated when type = 'system_assigned'

# Data Model: 數位課程販售平台 MVP

**Branch**: `001-course-platform-mvp` | **Date**: 2026-01-16

## Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│      User       │       │     Course      │
├─────────────────┤       ├─────────────────┤
│ id              │       │ id              │
│ email (unique)  │       │ name            │
│ nickname        │       │ tagline         │
│ real_name       │       │ description     │
│ phone           │       │ price           │
│ birth_date      │       │ thumbnail       │
│ role            │       │ instructor_name │
│ last_login_at   │       │ type            │
│ last_login_ip   │       │ is_published    │
│ created_at      │       │ sort_order      │
│ updated_at      │       │ portaly_url     │
└────────┬────────┘       │ portaly_product_id │
         │                │ created_at      │
         │                │ updated_at      │
         │                └────────┬────────┘
         │                         │
         │    ┌────────────────────┘
         │    │
                  ▼    ▼
         ┌─────────────────┐
         │    Purchase     │
         ├─────────────────┤
         │ id              │
         │ user_id (FK)    │
         │ course_id (FK)  │
         │ portaly_order_id│
         │ amount          │
         │ currency        │
         │ coupon_code     │
         │ discount_amount │
         │ status          │
         │ created_at      │
         │ updated_at      │
         └─────────────────┘
         
         ┌─────────────────┐
         │VerificationCode │
         ├─────────────────┤
         │ id              │
         │ email           │
         │ code            │
         │ attempts        │
         │ locked_until    │
         │ expires_at      │
         │ created_at      │
         └─────────────────┘
         ```
         
         ---
         
         ## Entities
         
         ### User（會員）
         
         | Field | Type | Constraints | Description |
         |-------|------|-------------|-------------|
         | id | bigint unsigned | PK, auto-increment | 主鍵 |
         | email | varchar(255) | unique, not null | Email（唯一識別） |
         | password | varchar(255) | nullable | 密碼（OTP 登入不需要） |
         | nickname | varchar(100) | nullable | 暱稱 |
         | real_name | varchar(100) | nullable | 真實姓名 |
         | phone | varchar(20) | nullable | 電話 |
         | birth_date | date | nullable | 出生年月日 |
         | role | enum('admin','editor','member') | default: 'member' | 角色 |
         | last_login_at | timestamp | nullable | 最後登入時間 |
         | last_login_ip | varchar(45) | nullable | 最後登入 IP (支援 IPv6) |
         | email_verified_at | timestamp | nullable | Email 驗證時間 |
         | remember_token | varchar(100) | nullable | Remember token |
         | created_at | timestamp | not null | 建立時間 |
         | updated_at | timestamp | not null | 更新時間 |
         
         **Indexes**:
         - `email` (unique)
         - `role`
         
         **Relationships**:
         - hasMany: Purchase
         
         ---
         
         ### Course（課程）
         
         | Field | Type | Constraints | Description |
         |-------|------|-------------|-------------|
         | id | bigint unsigned | PK, auto-increment | 主鍵 |
         | name | varchar(255) | not null | 課程名稱 |
         | tagline | varchar(255) | not null | 一句話簡介 |
         | description | text | not null | 完整介紹 |
         | price | decimal(10,2) | not null | 價格 |
         | thumbnail | varchar(500) | nullable | 縮圖路徑 |
         | instructor_name | varchar(100) | not null | 教師名稱 |
         | type | enum('lecture','mini','full') | not null | 類型（講座/迷你課/ 大型課程） |
         | is_published | boolean | default: false | 是否上架 |
         | sort_order | int unsigned | default: 0 | 排序順序 |
         | portaly_url | varchar(500) | nullable | Portaly 產品頁連結 |
         | portaly_product_id | varchar(100) | nullable | Portaly productId |
         | created_at | timestamp | not null | 建立時間 |
         | updated_at | timestamp | not null | 更新時間 |
         
         **Indexes**:
         - `is_published`
         - `sort_order`
         - `portaly_product_id`
         
         **Relationships**:
         - hasMany: Purchase
         
         **Scopes**:
         - `published()`: where is_published = true
         - `ordered()`: orderBy sort_order asc

         **Computed (Accessor)**:
         - `thumbnail_url`: 回傳完整 URL（`/storage/{thumbnail}`），前端直接使用
         
         ---
         
         ### Purchase（購買紀錄）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| user_id | bigint unsigned | FK → users.id, not null | 會員 ID |
| course_id | bigint unsigned | FK → courses.id, not null | 課程 ID |
| portaly_order_id | varchar(100) | not null, unique | Portaly 訂單編號 |
| buyer_email | varchar(255) | not null | 購買者 email（webhook 回傳） |
| amount | decimal(10,2) | not null | 金額 |
| currency | varchar(10) | default: 'TWD' | 幣別 |
| coupon_code | varchar(50) | nullable | 折扣碼 |
| discount_amount | decimal(10,2) | default: 0 | 折扣金額 |
| status | enum('paid','refunded') | not null | 付款狀態 |
| webhook_received_at | timestamp | nullable | Webhook 接收時間 |
| created_at | timestamp | not null | 購買時間 |
| updated_at | timestamp | not null | 更新時間 |

**Indexes**:
- `user_id`
- `course_id`
- `portaly_order_id` (unique)
- `buyer_email`
- composite: `user_id, course_id` (unique - 防止重複購買)

**Relationships**:
- belongsTo: User
- belongsTo: Course

**Notes**:
- `portaly_order_id` 改為 not null，因為購買紀錄必須來自 webhook
- `buyer_email` 記錄購買時的 email，方便追蹤
- `webhook_received_at` 記錄 webhook 接收時間，便於除錯

**Webhook Payload Mapping**:
| Webhook Field | Database Field | Notes |
|---------------|----------------|-------|
| `data.id` | `portaly_order_id` | 訂單編號，用於冪等檢查 |
| `data.productId` | 查詢 `Course.portaly_product_id` | 找到對應課程後取 `course_id` |
| `data.amount` | `amount` | 結帳金額 |
| `data.currency` | `currency` | 交易幣別 |
| `data.couponCode` | `coupon_code` | 折扣碼（可能為空字串） |
| `data.discount` | `discount_amount` | 折扣金額 |
| `data.customerData.email` | `buyer_email` + 查詢/建立 User | 用戶 email |
| `data.customerData.name` | User.`real_name` | 建立新用戶時使用 |
| `data.customerData.phone` | User.`phone` | 建立新用戶時使用 |
| `event` | 決定動作 | "paid" 建立紀錄，"refund" 更新狀態 |
         
         ---
         
         ### VerificationCode（驗證碼）

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint unsigned | PK, auto-increment | 主鍵 |
| email | varchar(255) | not null | 目標 email |
| code | varchar(6) | not null | 6 碼驗證碼 |
| attempts | tinyint unsigned | default: 0 | 驗證嘗試次數 |
| locked_until | timestamp | nullable | 鎖定截止時間 |
| expires_at | timestamp | not null | 驗證碼過期時間 |
| created_at | timestamp | not null | 建立時間 |

**Indexes**:
- `email`
- `expires_at`

**Cleanup**: 過期記錄可透過排程清理

---

## Validation Rules

### User
- email: required, email format, max 255, unique
- nickname: max 100
- real_name: max 100
- phone: max 20, numeric (optional)
- birth_date: date, before today

### Course
- name: required, max 255
- tagline: required, max 255
- description: required
- price: required, numeric, min 0
- type: required, in ['lecture', 'mini', 'full']
- sort_order: integer, min 0

### Purchase
- user_id: required, exists in users
- course_id: required, exists in courses
- amount: required, numeric, min 0
- status: required, in ['paid', 'refunded']

---

## State Transitions

### Purchase Status
```
[webhook received] → paid → refunded
                          ↑
                          └─ (manual refund by admin)
```

**Note**: 購買紀錄由 webhook 建立，初始狀態為 'paid'

### User Role
```
member (default) → editor → admin
                         ↑
                         └─ (manual promotion by admin)
```

---

## Data Seeding (MVP)

### Users
- 1 admin user (for testing)
- 3 member users (for testing)

### Courses
- 5 sample courses with different types
- Various prices and sort orders
- All published for homepage display

### Purchases
- Sample purchases linking test users to courses

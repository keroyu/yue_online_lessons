# Research: 數位課程販售平台 MVP

**Branch**: `001-course-platform-mvp` | **Date**: 2026-01-16

## Overview

本文件記錄 Phase 0 研究結果，解決技術決策和最佳實踐問題。

---

## 1. Email OTP Authentication (Laravel)

### Decision
使用 Laravel 內建的 session-based authentication 搭配自訂 OTP 驗證流程。

### Rationale
- Laravel 已有成熟的 session 管理和 authentication guards
- 不需要額外套件，保持依賴簡潔
- 自訂 OTP 邏輯可完全控制驗證碼生成、過期、重試限制

### Implementation Approach
1. 建立 `verification_codes` 資料表儲存 OTP
2. 使用 `VerificationCodeService` 處理：
   - 生成 6 碼數字驗證碼
   - 檢查發送頻率限制（60 秒）
   - 驗證碼過期檢查（10 分鐘）
   - 失敗次數追蹤與鎖定（5 次失敗鎖 15 分鐘）
3. 使用 Laravel Mail 發送驗證碼
4. 驗證成功後使用 `Auth::login()` 建立 session

### Alternatives Considered
| Alternative | Reason Rejected |
|-------------|-----------------|
| Laravel Fortify | 過度複雜，主要針對密碼認證 |
| Third-party OTP packages | 增加依賴，且需求簡單可自訂 |
| JWT tokens | 不符合 session-based 需求，增加複雜度 |

---

## 2. Session Configuration (30 Days)

### Decision
設定 Laravel session lifetime 為 30 天（43200 分鐘）。

### Rationale
- 線上課程平台用戶期望長時間保持登入
- 減少重複登入的摩擦
- 業界標準做法（Netflix, Udemy 等平台）

### Implementation
```php
// config/session.php
'lifetime' => 43200, // 30 days in minutes
'expire_on_close' => false,
```

### Security Considerations
- Session 使用 database driver 以便追蹤和撤銷
- 儲存最後登入時間和 IP 以供審計
- 用戶可從帳號設定登出所有裝置（未來功能）

---

## 3. Inertia.js + Vue 3 Page Structure

### Decision
遵循 Inertia.js 官方推薦的 Pages/Components 分離結構。

### Rationale
- 符合 constitution 規定的目錄結構
- 清楚區分頁面級組件和可重用組件
- Inertia.js 自動處理路由對應

### Best Practices Applied
1. **Pages/**: 每個路由對應一個 Vue 組件
2. **Components/**: 可重用的 UI 組件
3. **Layouts/**: 頁面佈局組件（透過 Inertia persistent layouts）
4. 使用 `<script setup>` 語法
5. Props 透過 Inertia 從 Controller 傳遞

---

## 4. Tailwind CSS Mobile-First RWD

### Decision
使用 Tailwind CSS 預設的 mobile-first breakpoints。

### Rationale
- 符合 constitution 的 responsive design 原則
- Tailwind 預設即為 mobile-first
- 最小寬度支援 320px（spec 要求）

### Breakpoints Reference
```css
/* Default (mobile): < 640px */
/* sm: >= 640px */
/* md: >= 768px */
/* lg: >= 1024px */
/* xl: >= 1280px */
```

### Implementation Pattern
```html
<!-- Mobile first: default styles apply to mobile -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
  <!-- Course cards -->
</div>
```

---

## 5. Course Sorting Strategy

### Decision
在 Course model 加入 `sort_order` 整數欄位，預設 ASC 排序。

### Rationale
- 管理員可完全控制首頁課程順序
- 簡單實作，無需複雜排序邏輯
- 未來可透過後台拖曳調整順序

### Implementation
```php
// Course model scope
public function scopeOrdered($query)
{
    return $query->orderBy('sort_order', 'asc');
}

// Controller
$courses = Course::where('is_published', true)->ordered()->get();
```

---

## 6. Error Handling for Email Failures

### Decision
捕捉 email 發送例外，顯示友善錯誤訊息，不自動重試。

### Rationale
- MVP 簡化處理，符合 YAGNI 原則
- 用戶可手動重試（受頻率限制）
- 避免複雜的 queue/retry 機制

### Implementation
```php
try {
    Mail::to($email)->send(new VerificationCodeMail($code));
} catch (\Exception $e) {
    Log::error('Failed to send verification code', ['email' => $email, 'error' => $e->getMessage()]);
    return back()->withErrors(['email' => '驗證碼發送失敗，請稍後重試']);
}
```

---

## 7. Email Service Provider (Resend.com)

### Decision
使用 Resend.com 作為 email 發送服務。

### Rationale
- 現代化的 email API 服務，開發體驗佳
- Laravel 原生支援（透過 Resend Laravel SDK）
- 免費方案提供每月 3,000 封 email，足夠 MVP 使用
- 提供詳細的發送狀態追蹤和 webhook
- 支援自訂域名（未來需求）

### Implementation
```php
// .env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxx_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@yueyuknows.com
MAIL_FROM_NAME="經營者時間銀行"

// config/services.php
'resend' => [
    'key' => env('RESEND_API_KEY'),
],
```

### Required Configuration
已完成設定：
1. **RESEND_API_KEY**: 已取得（請從團隊取得實際 key 或查看 .env）
2. **發送域名**: `yueyuknows.com`（已驗證）
3. **寄件者信箱**: `noreply@yueyuknows.com`
4. **寄件者名稱**: 「經營者時間銀行」

### Alternatives Considered
| Alternative | Reason Rejected |
|-------------|-----------------|
| SMTP (Mailgun, SendGrid) | 設定較複雜，Resend 更簡潔 |
| AWS SES | 設定繁瑣，需要 IAM 權限管理 |
| 自建 SMTP | 維護成本高，可送達率難保證 |

---

## 8. Database Schema Decisions

### Verification Codes Table
需要獨立表格追蹤 OTP 狀態：

| Column | Type | Purpose |
|--------|------|---------|
| id | bigint | Primary key |
| email | string | 目標 email |
| code | string(6) | 6 碼驗證碼 |
| attempts | int | 嘗試次數 |
| locked_until | timestamp | 鎖定截止時間 |
| expires_at | timestamp | 過期時間 |
| created_at | timestamp | 建立時間 |

### User Table Extensions
基於 Laravel 預設 users 表擴展：

| Added Column | Type | Purpose |
|--------------|------|---------|
| nickname | string(nullable) | 暱稱 |
| real_name | string(nullable) | 真實姓名 |
| phone | string(nullable) | 電話 |
| birth_date | date(nullable) | 出生年月日 |
| role | enum | admin/editor/member |
| last_login_at | timestamp(nullable) | 最後登入時間 |
| last_login_ip | string(nullable) | 最後登入 IP |

**Note**: Laravel users 表已有 `email`, `password`(nullable for OTP), `created_at`, `updated_at`

---

## 9. Thumbnail URL Handling (2026-01-17 新增)

### Decision
課程縮圖資料庫儲存相對路徑，透過 Model Accessor 輸出完整 URL 給前端使用。

### Rationale
- 資料庫儲存相對路徑保持彈性（未來可遷移至 S3）
- 後端統一處理 URL 轉換，前端不需知道 storage 實作細節
- 符合 constitution 的 Simplicity Over Complexity 原則

### Implementation
```php
// Course.php - Model Accessor
protected function thumbnailUrl(): Attribute
{
    return Attribute::make(
        get: fn () => $this->thumbnail ? "/storage/{$this->thumbnail}" : null
    );
}
```

Controller 在組裝資料時輸出完整 URL：
```php
// Controller
'thumbnail' => $course->thumbnail_url,
// 或
'thumbnail' => $course->thumbnail ? "/storage/{$course->thumbnail}" : null,
```

### Alternatives Considered
| Alternative | Reason Rejected |
|-------------|-----------------|
| 前端自行加 `/storage/` 前綴 | 前端需知道 storage 細節，未來遷移需改多處 |
| 資料庫儲存完整 URL | 失去彈性，遷移時需更新所有資料 |
| 使用 `$appends` 自動附加 | 影響所有序列化，包含不需要 URL 的場景 |

---

## Summary

所有技術決策已完成研究，無未解決的 NEEDS CLARIFICATION 項目。可進入 Phase 1 設計階段。

**2026-01-17 更新**：新增縮圖 URL 處理規範（第 9 項研究）。

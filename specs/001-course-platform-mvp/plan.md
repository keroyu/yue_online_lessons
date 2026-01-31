# Implementation Plan: 數位課程販售平台 MVP

**Branch**: `001-course-platform-mvp` | **Date**: 2026-01-16 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-course-platform-mvp/spec.md`
**Updated**: 2026-01-30 - 全站配色優化、倒數計時器簡化設計

## Summary

建立數位課程販售平台 MVP，提供課程展示、email 驗證碼會員系統、我的課程頁面、帳號設定功能。採用 Laravel 12 + Inertia.js + Vue 3 + Tailwind CSS 技術棧，購買流程外連至 Portaly 處理。

## Technical Context

**Language/Version**: PHP 8.2+ / Laravel 12.x
**Primary Dependencies**: Laravel 12, Inertia.js, Vue 3, Tailwind CSS
**Storage**: MySQL (Latest stable)
**Testing**: PHPUnit via `php artisan test`
**Target Platform**: Web (Laravel Forge deployment)
**Project Type**: Web application (Laravel monolith with Inertia.js SPA)
**Performance Goals**: 首頁載入 < 3 秒, Email 發送 < 30 秒
**Constraints**: RWD 支援 320px 以上寬度, Session 30 天有效
**Scale/Scope**: MVP 階段，預期 < 1000 用戶，< 50 課程

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Laravel Conventions | ✅ PASS | 使用 Laravel 12, RESTful controllers, Form Request, Policy |
| II. Vue & Frontend Standards | ✅ PASS | Vue 3 + Composition API + `<script setup>`, Inertia.js routing |
| III. Responsive Design First | ✅ PASS | Tailwind CSS mobile-first, 所有頁面 RWD |
| IV. Simplicity Over Complexity | ✅ PASS | MVP 範圍明確，延後功能已識別 |
| V. Security & Sensitive Data | ✅ PASS | .env 管理敏感資料，Vimeo embed 視頻 |
| Technology Stack | ✅ PASS | 完全符合 constitution 定義的技術棧 |

**Gate Result**: PASS - 可進入 Phase 0

## Project Structure

### Documentation (this feature)

```text
specs/001-course-platform-mvp/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── CourseController.php
│   │   ├── Auth/
│   │   │   └── LoginController.php
│   │   ├── Member/
│   │   │   ├── LearningController.php
│   │   │   └── SettingsController.php
│   │   └── Webhook/
│   │       └── PortalyController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── SendVerificationCodeRequest.php
│   │   │   └── VerifyCodeRequest.php
│   │   └── Member/
│   │       └── UpdateProfileRequest.php
│   └── Middleware/
│       └── EnsureAuthenticated.php
├── Models/
│   ├── User.php
│   ├── Course.php
│   ├── Purchase.php
│   └── CourseProgress.php
├── Policies/
│   ├── CoursePolicy.php
│   └── PurchasePolicy.php
├── Services/
│   ├── VerificationCodeService.php
│   └── PortalyWebhookService.php
└── Mail/
    └── VerificationCodeMail.php

database/
├── migrations/
│   ├── create_users_table.php
│   ├── create_courses_table.php
│   ├── create_purchases_table.php
│   └── create_course_progress_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── UserSeeder.php
    └── CourseSeeder.php

resources/
├── js/
│   ├── Pages/
│   │   ├── Home.vue
│   │   ├── Course/
│   │   │   └── Show.vue
│   │   ├── Auth/
│   │   │   └── Login.vue
│   │   └── Member/
│   │       ├── Learning.vue
│   │       └── Settings.vue
│   ├── Components/
│   │   ├── CourseCard.vue
│   │   ├── VerificationCodeInput.vue
│   │   └── Layout/
│   │       ├── AppLayout.vue
│   │       ├── Navigation.vue
│   │       └── Footer.vue
│   └── app.js
└── views/
    └── app.blade.php

routes/
└── web.php

tests/
├── Feature/
│   ├── HomeTest.php
│   ├── CourseTest.php
│   ├── AuthTest.php
│   └── MemberTest.php
└── Unit/
    └── VerificationCodeServiceTest.php
```

**Structure Decision**: Laravel monolith with Inertia.js SPA pattern. 前後端整合在同一專案中，符合 constitution 定義的技術棧。

## Complexity Tracking

> No violations - all complexity within constitution bounds.

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| Auth | Email OTP without password | 符合 spec 需求，簡化用戶體驗 |
| Payment | External link to Portaly | MVP 不處理金流，降低複雜度 |
| Progress | Table created, logic deferred | 預留未來擴展，MVP 顯示預設值 |
| **Thumbnail URL** | Model Accessor 輸出完整 URL | 前端不需知道 storage 細節，未來遷移 S3 只改一處 |
| **Webhook** | 簽章驗證 + 冪等處理 | 確保安全性和可靠性 |
| **Guest Purchase** | Email 輸入 + 自動註冊 | 降低購買門檻，購買即註冊 |
| **Countdown Timer** | 簡化設計，無深色背景 | 減少視覺層級複雜度，融入頁面風格 |

---

## Incremental Update Summary

### 2026-01-17: Webhook 購買處理

**背景**：原本購買紀錄由管理員手動建立，現改為透過 Portaly webhook 自動建立。未登入用戶可直接購買（輸入 email），系統自動建立會員帳號。

**新增功能**：
1. 課程販售頁 email 輸入（未登入用戶）
2. Webhook 端點接收 Portaly 付款通知（paid 和 refund 事件）
3. 自動建立會員帳號（若 email 未註冊，含姓名和電話）
4. 自動建立購買紀錄
5. 處理退款更新購買狀態

**新增路由**：
- `POST /api/webhook/portaly` - 接收 Portaly webhook

**新增/修改檔案**：
- `app/Http/Controllers/Webhook/PortalyController.php` - Webhook 處理
- `app/Services/PortalyWebhookService.php` - Webhook 驗證與處理邏輯
- `resources/js/Pages/Course/Show.vue` - 新增 email 輸入欄位
- `database/migrations/xxxx_add_webhook_fields_to_purchases_table.php` - 新增欄位

**設計決策**：
- Webhook 端點不需 CSRF 保護（使用 HMAC-SHA256 簽章驗證）
- 使用 portaly_order_id 確保冪等性
- 自動建立的會員無密碼，使用 OTP 登入

**HMAC-SHA256 簽章驗證實作**：

```php
// PortalyWebhookService.php
public function verifySignature(Request $request): bool
{
    $signature = $request->header('X-Portaly-Signature');
    $secret = config('services.portaly.webhook_secret');
    $data = json_encode($request->input('data'));

    $expectedSignature = hash_hmac('sha256', $data, $secret);

    return hash_equals($expectedSignature, $signature);
}
```

**環境變數配置**：
```env
# .env
PORTALY_WEBHOOK_SECRET=your-secret-key-from-portaly
```

```php
// config/services.php
'portaly' => [
    'webhook_secret' => env('PORTALY_WEBHOOK_SECRET'),
],
```

**Webhook URL 設定**：
- 需在 Portaly 後台為每個商品設定 webhook URL
- 格式：`https://your-domain.com/api/webhook/portaly`
- 加密金鑰也從 Portaly 後台取得

---

### 2026-01-17: 縮圖 URL 處理規範

**背景**：課程縮圖資料庫儲存相對路徑（如 `thumbnails/abc.jpg`），但前端需要完整 URL（如 `/storage/thumbnails/abc.jpg`）才能正確載入圖片。

**決策**：
- 資料庫儲存相對路徑（保持彈性）
- Model 新增 `thumbnail_url` Accessor，回傳完整 URL
- Controller 統一輸出 `thumbnail_url`（或在組裝資料時轉換）
- 前端直接使用，不需自行加前綴

**實作方式**：

```php
// Course.php - Model Accessor
protected function thumbnailUrl(): Attribute
{
    return Attribute::make(
        get: fn () => $this->thumbnail ? "/storage/{$this->thumbnail}" : null
    );
}
```

**受影響的檔案**：
- `app/Models/Course.php` - 新增 Accessor
- `app/Http/Controllers/HomeController.php` - 輸出 thumbnail_url
- `app/Http/Controllers/CourseController.php` - 輸出 thumbnail_url
- `resources/js/Components/CourseCard.vue` - 直接使用 thumbnail（已是完整 URL）
- `resources/js/Pages/Course/Show.vue` - 直接使用 thumbnail

**設計原則**：
- 前端不需知道 storage 實作細節
- 後端負責提供可直接使用的 URL
- 未來如遷移至 S3 只需修改 Model Accessor 一處

---

### 2026-01-30: 全站配色優化

**背景**：統一全站配色，提升視覺一致性和品牌識別度。

**色彩規範**：
- `#F6F1E9` - 米白色（頁面背景）
- `#FAA45E` - 橘色（倒數計時數字）
- `#FF4438` - 紅色（促銷價格）
- `#373557` - 深紫藍色（導航/頁尾背景、標題）
- `#3F83A3` - 藍綠色（連結、一般按鈕）
- `#F0C14B` - 土黃金色（購買按鈕，Amazon 風格）
- `#C7A33B` - 深金色（購買按鈕 hover 狀態）

**修改檔案**：
- `tailwind.config.js` - 新增 brand 色彩變數
- `resources/css/app.css` - 頁面背景、課程內容樣式
- `resources/js/Components/Layout/Navigation.vue` - 深藍背景
- `resources/js/Components/Layout/Footer.vue` - 深藍背景
- `resources/js/Components/CourseCard.vue` - 品牌配色
- `resources/js/Components/MyCourseCard.vue` - 品牌配色
- `resources/js/Components/Course/PriceDisplay.vue` - 倒數計時器配色
- `resources/js/Pages/*.vue` - 各頁面標題和按鈕配色

**倒數計時器設計決策**：
- 移除深色背景容器，改用簡化設計
- 移除個別數字卡片背景，降低視覺層級複雜度
- 數字使用橘色(#FAA45E)，標籤使用深藍色透明度
- 動畫改為輕微的 pulse 效果

**課程頁面價格顯示**：
- 價格與倒數計時器在兩處顯示：
  1. 頁面上方（課程資訊區）：讓用戶一進頁面就看到價格
  2. 購買區段（頁面底部）：購買按鈕旁邊再次顯示，方便決策
- 購買區兩欄式佈局：左欄價格/計時器，右欄同意條款與購買按鈕
- 響應式設計：手機版垂直堆疊，桌面版水平並排

**購買按鈕設計（Amazon 風格）**：
- 圓角膠囊形狀（rounded-full）
- 土黃金色背景 (#F0C14B)，深金色邊框
- 深藍色文字 (#373557)
- Hover 時加深顏色並增加陰影
- 點擊時有輕微縮放回饋

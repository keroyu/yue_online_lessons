---
id: 000-platform-core
status: done
owner_files:
  - app/Http/Controllers/Controller.php
  - app/Http/Controllers/SitemapController.php
  - app/Http/Controllers/Admin/SettingsController.php
  - app/Http/Middleware/AdminMiddleware.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - app/Providers/AppServiceProvider.php
  - app/Models/SiteSetting.php
  - app/Console/Commands/ConvertHtmlToMarkdown.php
  - routes/web.php
  - routes/api.php
  - routes/console.php
  - bootstrap/app.php
  - resources/css/app.css
  - database/migrations/0001_01_01_000001_create_cache_table.php
  - database/migrations/0001_01_01_000002_create_jobs_table.php
  - database/migrations/2026_03_25_000001_create_site_settings_table.php
  - database/seeders/DemoDataSeeder.php
  - resources/js/app.js
  - resources/js/bootstrap.js
  - resources/js/Components/Layout/AppLayout.vue
  - resources/js/Components/Layout/Navigation.vue
  - resources/js/Components/Layout/Footer.vue
  - resources/js/Layouts/AdminLayout.vue
  - resources/js/Components/Legal/LegalPolicyModal.vue
  - resources/js/Components/Legal/PrivacyContent.vue
  - resources/js/Components/Legal/TermsContent.vue
  - resources/js/Components/Legal/PurchaseContent.vue
  - resources/js/Pages/Error.vue
  - resources/views/app.blade.php
  - resources/views/sitemap.blade.php
  - resources/views/welcome.blade.php
touchpoints:
  - file: app/Services/CartService.php
    owner: 005-checkout
    why: HandleInertiaRequests 全域共享 cartCount 時呼叫 CartService::count()
  - file: resources/js/composables/useCart.js
    owner: 005-checkout
    why: Navigation 購物車角標讀取共享的 cartCount
  - file: resources/js/composables/useNotifications.js
    owner: 003-classroom
    why: Navigation 通知鈴讀取共享的 notifications / notificationCount
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: 課程頁以 view()->share('og', ...) 提供 app.blade.php 的 OG meta 資料
---

# Platform Core（全站基礎設施）

## 目標

提供所有功能模組共用的地基：Inertia SPA 進入點與全域共享資料、前台/後台版面框架、
路由總表、admin 權限攔截、SEO（meta/OG/sitemap）、Meta Pixel 追蹤，
以及 `site_settings` key-value 全站設定機制（金流憑證、積分參數等模組讀寫的共用儲存層）。

## User Stories

### User Story 1 - 全站導航與版面 (Priority: P1)

訪客與會員在任何前台頁面都看到一致的導航列（Logo、購物車角標、通知鈴、登入狀態選單）
與 Footer，頁面切換維持 SPA 體驗。

**驗收**：
- [x] `app.js` 對所有 `Pages/**` 自動套用 `AppLayout` 為預設 layout（頁面可設 `layout = false` 退出，如登入頁/教室）
- [x] Navigation 未登入顯示「登入」按鈕；登入後顯示我的課程／我的積分／帳號設定／登出
- [x] 購物車圖示顯示角標數字（來自全域共享 `cartCount`），大於 9 顯示「9+」，訪客恆為 0
- [x] 登入者顯示通知鈴（未讀數角標 + 最近 5 筆下拉），點擊通知標記已讀並跳轉教室對應單元
- [x] 行動版收合為漢堡選單，含購物車與通知清單（RWD mobile-first）
- [x] flash `success` / `error` 以右上角浮動訊息顯示，5 秒後自動消失（AppLayout 與 AdminLayout 皆同）
- [x] `HandleInertiaRequests` 全域共享：`auth.user`（id/email/nickname/real_name/phone/role）、`flash`（含 drip_* 鍵）、`cartCount`、`notificationCount`、`notifications`

### User Story 2 - 管理後台版面與權限 (Priority: P1)

管理員進入 `/admin/*` 看到側欄版面；非管理員一律被擋下，不暴露後台存在。

**驗收**：
- [x] `admin` middleware alias（`AdminMiddleware`）：未登入或 `!isAdmin()` 重導首頁並 flash「您沒有權限存取此頁面」
- [x] 所有 `/admin/*` 路由套 `['auth', 'admin']` middleware 群組（見 `routes/web.php`）
- [x] `AdminLayout` 固定側欄（桌機）/ 抽屜側欄（行動版），選單涵蓋 Dashboard、首頁設定、課程、會員、交易、折扣碼、推薦成效、Email 模板、Leads、金流設定、積分設定、作業批改
- [x] 側欄 active 判斷用路徑前綴；`/admin/coupons` 同時涵蓋 `/admin/coupon-chains`
- [x] 側欄底部顯示管理員暱稱首字頭像與「返回前台」連結
- [x] `AppServiceProvider` 註冊 `CoursePolicy`、`PurchasePolicy`（授權基礎）

### User Story 3 - 法務條款彈窗 (Priority: P2)

訪客在 Footer 點「服務條款／購買須知／隱私政策」即可閱讀完整條款，不需離開當前頁。

**驗收**：
- [x] Footer 三個按鈕開啟 `LegalPolicyModal`，依 `type`（terms/purchase/privacy）切換靜態內容組件
- [x] 彈窗支援 ESC 關閉、點背景關閉、開啟時鎖定 body scroll（關閉/卸載時還原）
- [x] 條款內容為前端靜態 Vue 組件（TermsContent / PurchaseContent / PrivacyContent），無後端資料

### User Story 4 - SEO、Sitemap 與 Meta Pixel (Priority: P2)

搜尋引擎與社群分享能正確抓到頁面標題、描述、OG 圖；行銷可透過 Meta Pixel 追蹤全站瀏覽。

**驗收**：
- [x] `app.blade.php` 輸出 meta description、canonical、OG、Twitter Card；有 `$og` view 變數時用頁面專屬值（課程頁由 CourseController `view()->share('og', ...)` 提供），否則用全站預設文案
- [x] `GET /sitemap.xml` 輸出已發佈課程清單（`is_published=true`），URL 優先用 `slug`、無 slug 退回 id，含 `lastmod`
- [x] Meta Pixel ID 取自 `SiteSetting::get('meta_pixel_id')`（fallback env `META_PIXel_ID`）；有值才注入 Pixel script 並送 PageView
- [x] SPA 導航時 `app.js` 監聽 `router.on('navigate')` 補送 `fbq('track', 'PageView')`
- [x] 頁面標題格式：`{title} - Your Time Bank`，無標題時 `Your Time Bank`

### User Story 5 - site_settings 全站設定機制 (Priority: P1)

管理員可在後台調整全站參數（金流憑證、Pixel ID、積分規則），不需改 code 或重新部署；
其他模組以 key-value API 讀寫。

**驗收**：
- [x] `SiteSetting` 提供靜態 API：`get(key, default)`、`getMany(keys)`、`set(key, value)`（upsert）
- [x] `Admin/SettingsController::showPayment/updatePayment` 管理金流憑證（PayUni / NewebPay / Portaly webhook key / meta_pixel_id）
- [x] 機密欄位（hash_key/hash_iv/webhook_key）表單只回傳遮罩預覽（前 5 碼 + `*`），送出留空 = 不覆蓋原值
- [x] 非機密欄位（merchant_id、newebpay_env、meta_pixel_id）送出即覆蓋；`newebpay_env` 限 sandbox/production
- [x] `showPoints/updatePoints` 管理積分參數 4 鍵（referral_threshold_amount / referral_reward_rate / homework_reward_points / referral_maturity_days），改值僅影響之後產生的積分（既有 ledger 已快照）
- [x] 讀取端 fallback 順序：`site_settings` → `config/services.php`（env）→ 硬編碼預設值

## Requirements

- **FR-001**: `routes/web.php` 是全站路由總表；購物車/結帳 API 必須放 web.php 的 `api` prefix 群組而非 `routes/api.php`（api 群組無 StartSession，結帳需讀 session 的 `traffic_source`）
- **FR-002**: `routes/api.php` 僅放無 session 需求的端點：金流 NotifyURL webhooks、Portaly webhook、免費課程報名、付款結果輪詢
- **FR-003**: 金流 ReturnURL（瀏覽器 redirect）放 web.php 並豁免 CSRF（`withoutMiddleware(ValidateCsrfToken)`）
- **FR-004**: `bootstrap.js` 為 axios 設定 `X-Requested-With` 與 `X-CSRF-TOKEN`（讀 `<meta name="csrf-token">`），所有前端 axios 呼叫依賴此設定
- **FR-005**: `cartCount`、`notifications` 等共享 props 用 closure 延遲求值，僅在頁面實際回傳時查詢
- **FR-006**: `php artisan content:html-to-markdown` 為一次性維運指令：將 `courses.description_md`、`lessons.md_content` 內殘留 HTML 轉為 Markdown（`saveQuietly` 不觸發事件）
- **FR-007**: `welcome.blade.php` 為 Laravel 預設樣板，未被任何路由使用（`/` 由 HomeController 接管），保留不動

## 設計決策

- **D1**: 條款內容做成靜態 Vue 組件而非 DB/CMS — 條款極少變動，改版走 git；否決後台編輯（過度設計）
- **D2**: `site_settings` 採單表 key-value（`key` unique、`value` text）而非每功能一張設定表 — 新增設定鍵零 migration；型別轉換由讀取端負責（如積分參數 cast int）
- **D3**: 金流機密存 DB 明文但 UI 僅顯示遮罩、留空不覆蓋 — 讓非工程師可自助換憑證；否決純 env 管理（每次換 key 要重新部署）
- **D4**: 預設 layout 在 `app.js` resolve 時注入（`layout === undefined` 才套 AppLayout）— 頁面可用 `layout = false` 明確退出（登入頁、教室全螢幕），避免每頁手動包 layout
- **D5**: Meta Pixel 初始化在 blade 注入（首載）+ Inertia navigate 事件補送 PageView — 純 SPA 導航不會重載 blade，兩者缺一都會漏追蹤

## Schema

- `site_settings` — 全站 key-value 設定；`key` unique，`value` nullable text（一律存字串，讀取端轉型）。
  目前使用的鍵：`payuni_*`、`newebpay_*`（009 金流）、`portaly_webhook_key`、`meta_pixel_id`（本模組）、
  `referral_*` / `homework_reward_points`（012 積分）、首頁設定鍵（007）。
  不變量：`set()` 為 upsert，同 key 永遠只有一列；機密值無加密（依賴 DB 存取控管）。

## Tasks

- [ ] T001 將 `resources/js/Pages/Error.vue` 掛上 exception handler（`bootstrap/app.php` 的 `withExceptions` 目前為空，403/404/500 仍走 Laravel 預設 HTML 錯誤頁，Error.vue 尚未被任何程式渲染）

## 進度日誌

- 2026-07-11: 後台側欄選單重排（內容類在上、營運類在下）＋「作業批改專區」改「作業批改」＋「推薦成效」併入「積分與推薦」單一入口（AdminLayout.vue）。新增 `DemoDataSeeder`（跨模組本機 demo 資料，可重跑、以標記自清）。
- 2026-07-06: 領域重組 — 全站基礎設施自各模組抽出，依實際 codebase 撰寫

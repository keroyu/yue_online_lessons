---
id: 000-platform-core
status: done
owner_files:
  - app/Http/Controllers/Controller.php
  - app/Http/Controllers/SitemapController.php
  - app/Http/Controllers/Admin/SettingsController.php
  - app/Http/Middleware/AdminMiddleware.php
  - app/Http/Middleware/StaffMiddleware.php
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
  - database/migrations/2026_07_11_000003_add_is_sales_consultant_to_users.php
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
  - file: app/Models/User.php
    owner: 001-auth-account
    why: 新增 is_sales_consultant cast 與 isSalesConsultant()/canAccessSalesPanel() 權限判斷方法（StaffMiddleware 依賴）
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

### User Story 6 - 銷售顧問受限後台存取 (Priority: P2)

被指派為銷售顧問（`users.is_sales_consultant = true`）的會員可進入後台，但只看得到、也只進得去
「Leads 名單」與「折扣碼」兩區，其餘後台一律擋下；管理員維持完整存取。
（指派身份的 UI 與 endpoint 見 008-members-admin US 9。）

**驗收**：
- [x] 新增 `staff` middleware alias（`StaffMiddleware`）：未登入或非 `canAccessSalesPanel()`（= `isAdmin()` OR `isSalesConsultant()`）→ 重導首頁並 flash「您沒有權限存取此頁面」
- [x] `routes/web.php` 的 `/admin` 群組改兩層：外層 `auth` + `prefix('admin')` + `name('admin.')`；內層 `staff` 群組含 coupons / coupon-chains / high-ticket-leads 全部路由；內層 `admin` 群組含其餘（dashboard、members、transactions、courses、posts、broadcasts、homework、email-templates、homepage、settings…）
- [x] 所有 route name 不變（仍 `admin.coupons.*`、`admin.coupon-chains.*`、`admin.high-ticket-leads.*`）；既有 coupon / lead controller 不需改（原本就只靠 route middleware 守門）
- [x] AdminLayout 側欄依角色過濾：admin 顯示全部；純銷售顧問只顯示「Leads 名單」「折扣碼」兩項
- [x] `HandleInertiaRequests` 全域共享的 `auth.user` 增加 `is_sales_consultant` 欄位，前端據以判斷
- [x] 前台 Navigation 帳號選單：`user.role === 'admin' || user.is_sales_consultant` 顯示「管理後台」連結（admin → `/admin`，純銷售顧問 → `/admin/high-ticket-leads`）
- [x] 銷售顧問直接輸入其他 `/admin/*` 網址（`/admin`、`/admin/members` 等）→ 被內層 `admin` middleware 擋下重導首頁

## Requirements

- **FR-001**: `routes/web.php` 是全站路由總表；購物車/結帳 API 必須放 web.php 的 `api` prefix 群組而非 `routes/api.php`（api 群組無 StartSession，結帳需讀 session 的 `traffic_source`）
- **FR-002**: `routes/api.php` 僅放無 session 需求的端點：金流 NotifyURL webhooks、Portaly webhook、免費課程報名、付款結果輪詢
- **FR-003**: 金流 ReturnURL（瀏覽器 redirect）放 web.php 並豁免 CSRF（`withoutMiddleware(ValidateCsrfToken)`）
- **FR-004**: `bootstrap.js` 為 axios 設定 `X-Requested-With` 與 `X-CSRF-TOKEN`（讀 `<meta name="csrf-token">`），所有前端 axios 呼叫依賴此設定
- **FR-005**: `cartCount`、`notifications` 等共享 props 用 closure 延遲求值，僅在頁面實際回傳時查詢
- **FR-006**: `php artisan content:html-to-markdown` 為一次性維運指令：將 `courses.description_md`、`lessons.md_content` 內殘留 HTML 轉為 Markdown（`saveQuietly` 不觸發事件）
- **FR-007**: `welcome.blade.php` 為 Laravel 預設樣板，未被任何路由使用（`/` 由 HomeController 接管），保留不動
- **FR-008**: 後台存取分兩級 — `admin`（完整）與 `staff`（= admin ∪ sales_consultant，僅 coupons / coupon-chains / high-ticket-leads）。sales_consultant 一律不得進入 dashboard、members、transactions、settings、email-templates、courses 等 admin-only 路由
- **FR-009**: 指派 / 移除銷售顧問身份僅 admin 可為（在 admin-only 的 members 路由下，見 008 US 9），銷售顧問無法自我或互相授權
- **FR-010**: 銷售顧問維持一般會員身份（`role` 恆 `member` 不變），前台購課、教室、積分等行為完全不受影響；`is_sales_consultant` 與 `role` 正交

## 設計決策

- **D1**: 條款內容做成靜態 Vue 組件而非 DB/CMS — 條款極少變動，改版走 git；否決後台編輯（過度設計）
- **D2**: `site_settings` 採單表 key-value（`key` unique、`value` text）而非每功能一張設定表 — 新增設定鍵零 migration；型別轉換由讀取端負責（如積分參數 cast int）
- **D3**: 金流機密存 DB 明文但 UI 僅顯示遮罩、留空不覆蓋 — 讓非工程師可自助換憑證；否決純 env 管理（每次換 key 要重新部署）
- **D4**: 預設 layout 在 `app.js` resolve 時注入（`layout === undefined` 才套 AppLayout）— 頁面可用 `layout = false` 明確退出（登入頁、教室全螢幕），避免每頁手動包 layout
- **D5**: Meta Pixel 初始化在 blade 注入（首載）+ Inertia navigate 事件補送 PageView — 純 SPA 導航不會重載 blade，兩者缺一都會漏追蹤
- **D6**: 銷售顧問用 `is_sales_consultant` 布林旗標，而非在 `role` enum 加值 — 顧問通常本身也是會員，旗標與 `role` 正交、可在會員列表一鍵開關、不動既有 `members()` scope 與 `isManageableMember()`（否決改 role：會使帳號離開 member 範圍、難兼具會員身份）
- **D7**: 新增 `staff` middleware 並把 coupons / leads 路由移進內層群組，而非在既有 `admin` group 逐路由加判斷 — 集中一處控管、route name 與 controller 皆不動（既有 coupon / lead controller 無 `isAdmin()` 內檢，純靠 route middleware）
- **D8**: 銷售顧問後台入口導向 `/admin/high-ticket-leads` 而非 dashboard — dashboard 含營收儀表板屬敏感、維持 admin-only

## Schema

- `site_settings` — 全站 key-value 設定；`key` unique，`value` nullable text（一律存字串，讀取端轉型）。
  目前使用的鍵：`payuni_*`、`newebpay_*`（009 金流）、`portaly_webhook_key`、`meta_pixel_id`（本模組）、
  `referral_*` / `homework_reward_points`（012 積分）、首頁設定鍵（007）。
  不變量：`set()` 為 upsert，同 key 永遠只有一列；機密值無加密（依賴 DB 存取控管）。
- `users.is_sales_consultant` — boolean 預設 false；標記該會員兼任銷售顧問（後台受限存取用）。與 `role` 正交，不影響 `members()` / `isManageableMember()` 的會員範圍判斷。（users 表基礎欄位歸 001）

## Tasks

- [ ] T001 將 `resources/js/Pages/Error.vue` 掛上 exception handler（`bootstrap/app.php` 的 `withExceptions` 目前為空，403/404/500 仍走 Laravel 預設 HTML 錯誤頁，Error.vue 尚未被任何程式渲染）

US 6（銷售顧問受限後台存取）：

- [x] T002 migration 加 `users.is_sales_consultant` boolean default false in `database/migrations/2026_07_11_000003_add_is_sales_consultant_to_users.php`
- [x] T003 User 加 `is_sales_consultant` boolean cast + `isSalesConsultant()` / `canAccessSalesPanel()`（= isAdmin() OR isSalesConsultant()）in `app/Models/User.php`（001 touchpoint）
- [x] T004 新增 `StaffMiddleware` 並在 `bootstrap/app.php` 註冊 `staff` alias in `app/Http/Middleware/StaffMiddleware.php`, `bootstrap/app.php`
- [x] T005 `routes/web.php` 的 `/admin` 群組拆為外層 `auth` + 內層 `staff`（coupons / coupon-chains / high-ticket-leads）與 `admin`（其餘）兩子群組 in `routes/web.php`
- [x] T006 [P] AdminLayout 側欄依 `user.role` / `user.is_sales_consultant` 過濾可見項目 in `resources/js/Layouts/AdminLayout.vue`
- [x] T007 [P] HandleInertiaRequests 共享的 `auth.user` 增加 `is_sales_consultant` in `app/Http/Middleware/HandleInertiaRequests.php`
- [x] T008 [P] 前台 Navigation 帳號選單依角色顯示「管理後台」連結 in `resources/js/Components/Layout/Navigation.vue`

## 進度日誌

- 2026-07-12: 全域修正按鈕游標 — Tailwind v4 preflight 預設 button cursor:default，app.css @layer base 對非 disabled button 恢復 pointer；規則寫入 CLAUDE.md 與 constitution（可點元素必有 pointer + hover 樣式）

- 2026-07-12: /dev 完成 US6 銷售顧問受限後台存取 — StaffMiddleware + staff alias、/admin 拆外層 auth + 內層 staff/admin 兩子群組（route name 不變）、側欄與前台入口依角色過濾、auth.user 共享 is_sales_consultant；SalesConsultantTest 8 tests、全套 108 passed。T001（Error.vue exception handler）為既有 backlog 未動

- 2026-07-11: [draft] 規劃 US 6 銷售顧問受限後台存取（`is_sales_consultant` 旗標 + `staff` middleware + 路由分層 + 側欄/導航過濾）。指派 UI 見 008 US 9。
- 2026-07-11: 後台「金流設定」改名「API 設定」（側欄 nav + `SettingsController@updatePayment` 成功訊息；路由 `/admin/settings/payment` 不變）。頁面本身（Payment.vue）歸 005；此頁憑證取值為「site_settings（後台）優先、config/.env fallback」，PayUni `sandbox` 目前僅讀 .env。
- 2026-07-11: 後台側欄選單重排（內容類在上、營運類在下）＋「作業批改專區」改「作業批改」＋「推薦成效」併入「積分與推薦」單一入口（AdminLayout.vue）。新增 `DemoDataSeeder`（跨模組本機 demo 資料，可重跑、以標記自清）。
- 2026-07-06: 領域重組 — 全站基礎設施自各模組抽出，依實際 codebase 撰寫

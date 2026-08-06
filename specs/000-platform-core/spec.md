---
id: 000-platform-core
status: building
owner_files:
  - app/Http/Controllers/Controller.php
  - app/Models/EmailSuppression.php
  - app/Services/EmailSuppressionService.php
  - app/Listeners/RecordEmailSuppression.php
  - app/Listeners/BlockSuppressedRecipients.php
  - database/migrations/2026_08_09_000001_create_email_suppressions_table.php
  - tests/Feature/Platform/EmailSuppressionTest.php
  - app/Http/Controllers/SitemapController.php
  - app/Http/Controllers/Admin/SettingsController.php
  - app/Http/Controllers/Admin/ShortLinkController.php
  - app/Http/Controllers/ShortLinkRedirectController.php
  - app/Http/Requests/Admin/StoreShortLinkRequest.php
  - app/Http/Requests/Admin/UpdateShortLinkRequest.php
  - app/Models/ShortLink.php
  - database/migrations/2026_07_31_000001_create_short_links_table.php
  - database/seeders/ShortLinkSeeder.php
  - resources/js/Pages/Admin/ShortLinks/Index.vue
  - tests/Feature/Platform/ShortLinkTest.php
  - app/Http/Middleware/AdminMiddleware.php
  - app/Http/Middleware/StaffMiddleware.php
  - app/Http/Middleware/HandleInertiaRequests.php
  - app/Providers/AppServiceProvider.php
  - app/Models/SiteSetting.php
  - app/Services/MetaConversionsService.php
  - app/Jobs/SendMetaConversionJob.php
  - database/migrations/2026_07_12_000001_add_meta_click_ids_to_orders_table.php
  - app/Console/Commands/ConvertHtmlToMarkdown.php
  - routes/web.php
  - routes/api.php
  - routes/console.php
  - bootstrap/app.php
  - config/services.php
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
  - file: app/Services/DripService.php
    owner: 010-drip-email
    why: US9 序列信派工前跳過已封鎖 email，並在訂閱者名單帶出封鎖狀態
  - file: app/Mail/DripLessonMail.php
    owner: 010-drip-email
    why: US9 行銷信需掛 X-Mail-Class: marketing header
  - file: resources/js/Components/Admin/Leads/SubscriberListTab.vue
    owner: 010-drip-email
    why: US9 訂閱者名單顯示「已退信 / 已投訴」標記
  - file: app/Mail/NewsletterBroadcastMail.php
    owner: 012-newsletter
    why: US9 行銷信需掛 X-Mail-Class: marketing header
  - file: app/Mail/NewsletterWelcomeMail.php
    owner: 012-newsletter
    why: US9 行銷信需掛 X-Mail-Class: marketing header
  - file: app/Mail/BatchEmailMail.php
    owner: 008-members-admin
    why: US9 行銷信需掛 X-Mail-Class: marketing header
  - file: app/Jobs/NotifyHighTicketSlotJob.php
    owner: 011-high-ticket
    why: US9 新時段通知信屬行銷信，需掛 header（同用 TemplatedMail 的預約確認信不掛）
  - file: app/Http/Controllers/Admin/HighTicketLeadController.php
    owner: 011-high-ticket
    why: US9 leads 名單帶出封鎖狀態
  - file: resources/js/Components/Admin/Leads/BookingListTab.vue
    owner: 011-high-ticket
    why: US9 leads 名單顯示「已退信 / 已投訴」標記
  - file: resources/js/Pages/Admin/HighTicketLeads/Index.vue
    owner: 011-high-ticket
    why: US9 把 suppressionsByEmail 從頁面 props 轉傳給 BookingListTab
  - file: resources/js/Pages/Admin/Settings/Payment.vue
    owner: 005-checkout
    why: US9 在既有 API 設定頁新增 resend_webhook_secret 遮罩欄位
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: 課程頁以 view()->share('og', ...) 提供 app.blade.php 的 OG meta 資料
  - file: app/Models/User.php
    owner: 001-auth-account
    why: 新增 is_sales_consultant cast 與 isSalesConsultant()/canAccessSalesPanel() 權限判斷方法（StaffMiddleware 依賴）
  - file: app/Services/CheckoutService.php
    owner: 005-checkout
    why: US7 — fulfillOrder 入帳後 dispatch CAPI Purchase 事件；initiate 時快照 _fbp/_fbc cookie 到 orders
  - file: app/Http/Controllers/CheckoutController.php
    owner: 005-checkout
    why: US7 — 結帳 initiate 讀取 _fbp/_fbc cookie 傳給 CheckoutService
  - file: app/Services/PortalyWebhookService.php
    owner: 005-checkout
    why: US7 — Portaly 訂單入帳後送 CAPI Purchase（無瀏覽器端對應）
  - file: app/Http/Controllers/Purchase/FreePurchaseController.php
    owner: 005-checkout
    why: US7 — 免費領取成功後送 CAPI FreeEnroll 自訂事件
  - file: resources/js/Pages/Admin/Settings/Payment.vue
    owner: 005-checkout
    why: US7 — API 設定頁加 CAPI access token（遮罩）與 test_event_code 欄位
  - file: app/Services/HighTicketBookingService.php
    owner: 011-high-ticket
    why: US7 — 高價課預約成功後送 CAPI Lead 事件
  - file: app/Http/Controllers/Auth/LoginController.php
    owner: 001-auth-account
    why: US7 — OTP 首次註冊建立 User 後送 CAPI CompleteRegistration
  - file: app/Services/NewsletterService.php
    owner: 012-newsletter
    why: US7 — 電子報訂閱建立新 User 後送 CAPI CompleteRegistration
  - file: app/Models/Order.php
    owner: 005-checkout
    why: US7 — fillable 增加 meta_fbp / meta_fbc（結帳時的 pixel cookie 快照欄位）
  - file: resources/images/og-logo.png
    owner: 012-newsletter
    why: 前台 Navigation 左上角品牌 logo（Vite import，與 OG 卡片共用同一支品牌圖）
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
- [x] `bootstrap/app.php` 對 `drip/unsubscribe/*` 與 `newsletter/unsubscribe/*` 豁免 CSRF —— RFC 8058 一鍵退訂由郵件用戶端直接 POST、無 session，不豁免會回 419（授權來源是網址中的 per-recipient token）。**注意**：`withMiddleware` 的 closure 只在 HTTP Kernel 被解析時執行，console/tinker 讀 `getExcludedPaths()` 永遠是空的

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
- [x] Meta Pixel ID 取自 `SiteSetting::get('meta_pixel_id')`（fallback `config('services.meta.pixel_id')` ← env `META_PIXEL_ID`；不可在 blade 直接呼叫 `env()`，config:cache 後會失效）；有值才注入 Pixel script 並送 PageView
- [x] SPA 導航時 `app.js` 監聽 `router.on('navigate')` 補送 `fbq('track', 'PageView')`；初始整頁載入的第一次 navigate 事件跳過（blade 注入的 snippet 已送過，避免重複計數）
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
- [x] 2026-08-04 起「Leads 名單」頁多了「訂閱者名單」tab（drip 訂閱者的開信/進度），顧問一併看得到 —— 路由層邊界不變（仍是 staff 群組的同一個 endpoint），只是該頁內容變多；理由見 011 D27

### User Story 7 - Meta CAPI 轉換追蹤強化 (Priority: P1)

行銷需要準確的轉換數據餵給 Meta 投放演算法：iOS ATT 與廣告攔截器讓純瀏覽器 Pixel 漏掉 20–40% 轉換。
由 server 在業務事實發生點（金流入帳、表單送出、帳號建立）直送 Conversions API，
與瀏覽器事件以 eventID 去重，並以 Advanced Matching（hashed email/phone）提升配對率。

**驗收**：
- [x] `MetaConversionsService::send(string $eventName, array $userData, array $customData = [], ?string $eventId = null, ?string $sourceUrl = null): void` — 組 payload 後 dispatch queued `SendMetaConversionJob`；`meta_pixel_id` 或 `meta_capi_access_token` 未設定時靜默 no-op
- [x] `SendMetaConversionJob` POST `graph.facebook.com/v21.0/{pixel_id}/events`，失敗重試 3 次（backoff），最終失敗僅 log；`meta_capi_test_event_code` 有值時附 `test_event_code`（Events Manager 測試模式）
- [x] PII 一律 SHA-256 後出站：email（lowercase + trim）、phone（去非數字、補國碼 886）；CAPI `user_data` 盡量附 `em/ph/client_ip_address/client_user_agent/fbp/fbc/external_id`（user id）
- [x] 結帳 initiate 時快照 `_fbp`/`_fbc` cookie 存 `orders.meta_fbp/meta_fbc`（webhook 時刻無瀏覽器 cookie 可讀）
- [x] `CheckoutService::fulfillOrder` 入帳後送 CAPI `Purchase`（eventID `purchase_{merchant_order_no}`，與 Success.vue 瀏覽器事件去重；value/currency/content_ids 同瀏覽器端）；Portaly webhook 入帳亦送（僅 CAPI，無瀏覽器對應）
- [x] 高價課預約表單成功（`HighTicketBookingService::book`）送 CAPI `Lead`（content_name 課程名）
- [x] 首次建立 User 送 CAPI `CompleteRegistration`：OTP 註冊（LoginController）`content_name: otp_register`、電子報訂閱（NewsletterService）`content_name: newsletter`；既有 user 重複登入/訂閱不送
- [x] 免費領取課程成功（FreePurchaseController）送 CAPI 自訂事件 `FreeEnroll`（content_ids 課程 id）
- [x] Advanced Matching（瀏覽器端）：登入用戶的 blade Pixel init 附 `{em: sha256(email)}`（server 端算好再輸出，原文不進 HTML）
- [x] API 設定頁（Payment.vue）新增 CAPI access token（機密遮罩、留空不覆蓋）與 test_event_code（非機密）欄位
- [x] 測試：hash 正規化、payload 結構、fulfillOrder / book / 註冊點以 `Queue::fake()` 驗證 job dispatch 與 no-op 條件

### User Story 8 - 短網址轉址管理 (Priority: P2)

管理員在後台自建短網址（如 `/1v1`）指向任意外部連結（Google Calendar 預約頁、Zoom、表單…），訪客造訪即轉走。
換人接手或換連結時只改後台一個欄位，不動 code、不重新部署。

**驗收**：
- [ ] 後台 `/admin/short-links` 單頁管理：列表 + inline 新增/編輯/刪除，欄位為 slug、目標網址、備註名稱、啟用開關
- [ ] 前台 `GET /{slug}` 命中啟用中的短網址 → 302 轉外部網址（`redirect()->away()`），回應帶 `Cache-Control: no-store`
- [ ] 停用中或不存在的 slug → 404（不洩漏「曾經存在」）
- [ ] slug 大小寫不敏感：一律小寫正規化後儲存與比對，`/1V1` 與 `/1v1` 同一筆
- [ ] slug 驗證：1–64 字、僅小寫英數與 `-` `_`、unique；撞到現有路由第一段（admin/blog/course/cart/member/login…）時擋下並回中文提示
- [ ] 每次成功轉址原子累加 `clicks` 並寫入 `last_clicked_at`；後台列表顯示點擊數與最後點擊時間（**存 UTC、顯示轉 `Asia/Taipei`**，比照 PostController 的慣例；app timezone 為 UTC，直接 format 會少 8 小時）
- [ ] 後台列表可一鍵複製完整網址（`https://<host>/<slug>`）
- [ ] 首筆資料 `/1v1` → `https://calendar.app.google/4oQaEE1JbDgSmhhD9`，之後由後台自行修改

### User Story 9 - 退信與投訴自動標記 (Priority: P2)

Resend 在硬退信 / 垃圾信投訴發生時通知本站，系統把該 email 記進封鎖名單，後台名單上看得出誰收不到信，並停止對他寄出無意義的信件。
目的**不是**保護寄件信譽 — Resend 自己的 suppression list 已經自動處理硬退信與投訴、跨全網域跳過寄送。本故事要解決的是「本站資料庫完全不知道誰退信了」：死地址繼續佔著 active 名單、汙染開信率與轉換率分母，顧問也看不出哪個聯絡方式是死的。

**驗收**：
- [x] 收到 `email.bounced` 且 `data.bounce.type === 'Permanent'` → 該 email 進封鎖名單，reason=bounce
- [x] 收到 `email.complained` → 該 email 進封鎖名單，reason=complaint
- [x] `Transient` / `Undetermined` 退信只寫 log，不封鎖
- [x] 同一 email 重複事件不重複建列（Svix 會重送）；已是 complaint 的收到 bounce 升級為 bounce，反向不降級
- [x] 被封鎖的 email：行銷信（序列信 / 電子報 / 批次信 / 新時段通知）一律不寄；交易信（驗證碼 / 預約確認 / 贈課 / 新小節通知）僅在 reason=bounce 時不寄，complaint 照寄
- [x] 每一次攔截都寫 log（email + reason + mailable class），不得靜默失敗
- [x] drip 序列在派工前就跳過已封鎖的訂閱：`emails_sent` 不推進、不派 job
- [x] 後台 leads 名單與 drip 訂閱者名單，對已封鎖的 email 顯示「已退信」/「已投訴」標記
- [x] webhook secret 在後台 API 設定頁以遮罩欄位設定（留空不覆蓋，比照金流／CAPI／Zoom）
- [x] secret 未設定時套件不掛驗簽 middleware、端點形同無認證 — 後台該欄位需標示此風險，且驗簽失敗回 403

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
- **FR-011**: CAPI 呼叫 MUST 走 queue（`SendMetaConversionJob`），不得同步阻塞金流 webhook 或表單回應；Meta API 失敗不得影響任何業務流程（訂單照常入帳、表單照常成功）
- **FR-012**: PII（email/phone）MUST 經 SHA-256 正規化雜湊後才送 Meta，原文不出站；瀏覽器端 Advanced Matching 的 hash 由 server 計算後輸出，不在前端做
- **FR-013**: 瀏覽器與 CAPI 同時存在的事件（目前僅 Purchase）MUST 帶相同 eventID 供 Meta 去重；單邊事件（Lead/CompleteRegistration/FreeEnroll）不需 eventID
- **FR-014**: 短網址 catch-all（單段 `/{slug}`）MUST 註冊在 `routes/web.php` 最末行；日後新增的任何具名路由都必須加在它之前，否則會被吃掉。路由以 `->where('slug', '[A-Za-z0-9_-]+')` 限制字元集，含 `.` `/` 的路徑不進入此路由
- **FR-015**: slug 保留字不寫死清單，改由 `Route::getRoutes()` 動態推導所有已註冊路由的第一段靜態片段 — 新功能加路由後保護自動生效
- **FR-016**: 轉址一律 302 且 `Cache-Control: no-store` — 目標網址本來就會換（換人接手），不得讓瀏覽器或 CDN 快取
- **FR-017**: `target_url` 僅接受 `http`/`https` 絕對網址，防止 `javascript:` 等 scheme 進入 redirect
- **FR-018**: webhook 端點沿用 `resend/resend-laravel` 內建的 `POST /resend/webhook`（route name `resend.webhook`，由套件 ServiceProvider 自動註冊，不在 web 群組故無 session/CSRF）。secret 存 `site_settings.resend_webhook_secret`，並 MUST 在請求進入該路由前餵進 `config('resend.webhook.secret')` — 套件 controller 建構子的邏輯是「config 有值才掛 `VerifyWebhookSignature`」，沒餵值等於端點**完全無認證**，任何人都能偽造事件封鎖任意 email
- **FR-019**: 只有 `data.bounce.type === 'Permanent'` 的 `email.bounced` 建立封鎖；`Transient` / `Undetermined` 僅寫 log — 信箱暫時滿了不該永久封鎖
- **FR-020**: 封鎖以 email 為唯一鍵而非 user_id — 收件人不一定是會員（high ticket leads、確認信的 CC 收件者、已刪帳號）
- **FR-021**: 事件處理 MUST 冪等（Svix 會重送同一則）；reason 只能由 complaint 升級為 bounce，不可反向降級
- **FR-022**: 攔截統一發生在 `Illuminate\Mail\Events\MessageSending` 的 listener（回傳 false 取消寄送），不得在各 `Mail::to()` 呼叫點逐一判斷 — 全站 14 處寄信點，逐一判斷必漏且未來新增的寄信路徑不會自動受保護
- **FR-023**: 行銷信 MUST 由寄信端掛 `X-Mail-Class: marketing` header；listener 對沒掛 header 的一律視為交易信（安全側：寧可多寄一封確認信，不可漏寄）。該 header 在判斷後 MUST 從實際寄出的信件移除
- **FR-024**: 每次攔截 MUST 寫 log（email、reason、mailable class）— 沒有 log 的攔截等於查不出原因的靜默失敗
- **FR-025**: drip 序列 MUST 在 `processSubscription` 就跳過已封鎖的訂閱，不推進 `emails_sent`、不派 job — 只靠 MessageSending 攔會讓游標空轉、開信率分母持續被汙染

## 設計決策

- **D1**: 條款內容做成靜態 Vue 組件而非 DB/CMS — 條款極少變動，改版走 git；否決後台編輯（過度設計）
- **D2**: `site_settings` 採單表 key-value（`key` unique、`value` text）而非每功能一張設定表 — 新增設定鍵零 migration；型別轉換由讀取端負責（如積分參數 cast int）
- **D3**: 金流機密存 DB 明文但 UI 僅顯示遮罩、留空不覆蓋 — 讓非工程師可自助換憑證；否決純 env 管理（每次換 key 要重新部署）
- **D4**: 預設 layout 在 `app.js` resolve 時注入（`layout === undefined` 才套 AppLayout）— 頁面可用 `layout = false` 明確退出（登入頁、教室全螢幕），避免每頁手動包 layout
- **D5**: Meta Pixel 初始化在 blade 注入（首載）+ Inertia navigate 事件補送 PageView — 純 SPA 導航不會重載 blade，兩者缺一都會漏追蹤
- **D6**: 銷售顧問用 `is_sales_consultant` 布林旗標，而非在 `role` enum 加值 — 顧問通常本身也是會員，旗標與 `role` 正交、可在會員列表一鍵開關、不動既有 `members()` scope 與 `isManageableMember()`（否決改 role：會使帳號離開 member 範圍、難兼具會員身份）
- **D7**: 新增 `staff` middleware 並把 coupons / leads 路由移進內層群組，而非在既有 `admin` group 逐路由加判斷 — 集中一處控管、route name 與 controller 皆不動（既有 coupon / lead controller 無 `isAdmin()` 內檢，純靠 route middleware）
- **D8**: 銷售顧問後台入口導向 `/admin/high-ticket-leads` 而非 dashboard — dashboard 含營收儀表板屬敏感、維持 admin-only
- **D9**: CAPI Purchase 掛在 `CheckoutService::fulfillOrder`（PayUni/藍新共用的單一咽喉點）而非各 gateway controller — 一處整合、天然覆蓋未來新金流；Portaly 因走獨立 webhook service 另掛一處
- **D10**: 免費領取送自訂事件 `FreeEnroll` 而非 `Purchase` value=0 或 `Lead` — 不汙染 Purchase 的出價優化訊號、與高價課 Lead 區隔；投免費課廣告時在 Events Manager 以 FreeEnroll 建自訂轉換即可優化
- **D11**: Lead / CompleteRegistration / FreeEnroll 僅送 CAPI、不加瀏覽器端對應 — 這些動作的事實發生點就在 server（表單 POST、建帳號），單邊發送零去重複雜度；否決雙邊發送（要生成共享 eventID、收益趨近零）
- **D12**: `_fbp`/`_fbc` 在結帳 initiate 快照進 orders 欄位 — Purchase 是 webhook 時刻發送，屆時無瀏覽器 cookie 可讀，不快照則 CAPI 事件無法歸因回廣告點擊
- **D13**: CAPI access token 沿用 D3 機密欄位 pattern（DB 明文 + UI 遮罩 + 留空不覆蓋），與金流憑證同頁管理
- **D14**: 短網址走乾淨的 catch-all `/{slug}`，不用 `/go/{slug}` 或 `/l/{slug}` 前綴 — 短網址的用途就是口播/名片/IG bio，多一段前綴等於失去意義；吃掉未來路由的風險用「註冊在最末 + 建立時保留字檢查」兩道防線控制（否決前綴方案：安全但沒解決使用者的需求）
- **D15**: 保留字檢查動態掃 route collection 第一段（`ShortLink::isReservedSlug()`），不維護黑名單常數 — 半年後有人加 `/webinar` 路由時，保護自動跟上；只擋靜態片段，`{param}` 開頭的路由不納入
- **D16**: 302 而非 301 — 這個功能的前提就是「以後會換目標」，301 被瀏覽器永久快取後使用者換了後台也叫不回來（同理加 `no-store`）
- **D17**: 點擊統計用同表計數器（`clicks` + `last_clicked_at`，單句原子 UPDATE），不建事件表 — 短網址量級極小，與 002 D13「彙總不存 raw event」同一思路；要看流量趨勢請走既有的 UTM/GA 路線
- **D18**: 不做軟刪除、不留歷史版本、不做 QR code — 刪掉就沒了，YAGNI；未來真的要，加欄位即可
- **D19**: 後台單頁 inline CRUD（比照 `SocialLinkController` + 首頁設定頁的做法），不開 Create/Edit 獨立頁 — 欄位只有四個，跳頁反而慢
- **D20**: `redirect()->away()` 而非 `redirect()->to()` — 目標是站外網址，`to()` 會被當成內部路徑處理
- **D21**: 直接用 `resend/resend-laravel` 已安裝的 `WebhookController` + `VerifyWebhookSignature` + `EmailBounced`/`EmailComplained` 事件，本站只寫 listener — 套件已處理 Svix HMAC 驗簽、時間容差（預設 300 秒）與路由註冊；否決自刻 controller 與 HMAC（等於自己維護一份會過期的安全性程式碼）
- **D22**: 封鎖名單獨立成 `email_suppressions` 表、以 email 為鍵，不在 `users` 加欄位 — email 是所有寄信管道唯一的共同鍵，users 覆蓋不到 leads 與 CC 收件者
- **D23**: 攔截點選 `MessageSending` listener — 一處覆蓋現有 14 個寄信點與所有未來新增的寄信路徑，新功能不需要記得加判斷
- **D24**: 行銷 / 交易靠寄信端主動掛 header 區分，而非用 Mailable class 名稱判斷 — `TemplatedMail` 同一個 class 同時用於預約確認（交易）與新時段通知（行銷），class 名稱天生分不出來
- **D25**: 不新增 `drip_subscriptions` / `users` 的狀態值，封鎖狀態只存在 `email_suppressions` — 維持最小範圍，不動 010/012 的 enum、統計與篩選程式碼；「退信者不計入開信率分母」列為未來工作
- **D26**: 不做後台管理頁、不做手動解除封鎖 — 誤判時在 Resend 後台移除 + 刪本站一列即可（一次性維運），現在的量體不值得一個 CRUD 畫面
- **D27**: `resend_webhook_secret` 存 `site_settings` 並在既有 API 設定頁以遮罩欄位管理（沿用 D3/D13 pattern），不走 env — **本系統要量販給客戶**，每個站台各自一組 Resend 憑證，設定必須能由非工程師自助完成，這正是 D3 當初立的理由。代價是明文落 DB（同 D3 的既有取捨）；此 secret 外洩的最大危害僅止於「偽造退信事件、把任意 email 加進封鎖名單」，不能寄信也讀不到任何資料，敏感度低於同表已存的金流金鑰與 CAPI token。
  **實作限制**：`SiteSetting::get()` 無快取，覆寫 `config('resend.webhook.secret')` MUST 只發生在 webhook 那條路徑上，不得放進每個 request 都跑的 boot 流程（否則全站每次請求多一次 DB query）。**掛載點是 `RouteMatched` 事件、不是 `AppServiceProvider::boot()` 本體**：套件的 `WebhookController` 建構子讀 `config('resend.webhook.secret')` 決定要不要掛驗簽 middleware，而建構子是路由比對後、middleware 組裝前才執行；`boot()` 本身每個 process 只跑一次（測試環境甚至整個 test case 只跑一次），無從得知「這次 request 是不是打中 webhook」。改監聽 `RouteMatched` 判斷 `$route->getName() === 'resend.webhook'` 才查 DB，同時滿足「只在 webhook 路徑查」與「per-request 都能正確生效」兩個條件，也讓這段邏輯可以直接用真實 HTTP 測試驗證（不必 mock）。
  **未來工作**：`RESEND_API_KEY` 仍在 env，量販時客戶無法自助設定自己的 Resend 帳號。搬它會動到 mail transport 的憑證來源、風險與本故事不同級（設錯＝整站寄不出信），另開 US 處理。
- **D28**: 交易信只被 bounce 擋、不被 complaint 擋 — 地址不存在時連驗證碼都送不到（寄了純浪費），但「嫌行銷信煩而按檢舉」的人仍然需要收到自己主動觸發的驗證碼與預約確認信

## Schema

- `site_settings` — 全站 key-value 設定；`key` unique，`value` nullable text（一律存字串，讀取端轉型）。
  目前使用的鍵：`payuni_*`、`newebpay_*`（009 金流）、`portaly_webhook_key`、`meta_pixel_id`（本模組）、
  `referral_*` / `homework_reward_points`（012 積分）、首頁設定鍵（007）。
  不變量：`set()` 為 upsert，同 key 永遠只有一列；機密值無加密（依賴 DB 存取控管）。
- `users.is_sales_consultant` — boolean 預設 false；標記該會員兼任銷售顧問（後台受限存取用）。與 `role` 正交，不影響 `members()` / `isManageableMember()` 的會員範圍判斷。（users 表基礎欄位歸 001）
- `orders.meta_fbp` varchar(100) nullable / `orders.meta_fbc` varchar(255) nullable — 結帳 initiate 時的 `_fbp`/`_fbc` cookie 快照，供 webhook 時刻的 CAPI Purchase 歸因（orders 表主體歸 005，本欄位 migration 歸本模組）。
- `site_settings` 新鍵：`meta_capi_access_token`（機密、遮罩）、`meta_capi_test_event_code`（非機密，空 = 正式發送）、`resend_webhook_secret`（US9，機密、遮罩；空 = webhook 端點不驗簽）。
- `short_links`（US8 新表）— 後台可管理的站內短網址轉址；`slug` varchar(64) unique（**恆為小寫**，寫入時正規化）、`target_url` varchar(2048)、`name` varchar(100) nullable（人看的備註）、`is_active` boolean default true、`clicks` unsigned int default 0、`last_clicked_at` timestamp nullable、timestamps。
  不變量：`clicks` 只增不減（原子 `increment`，不重算）；`slug` 唯一且不得與任何已註冊路由的第一段相同；停用不刪資料（`is_active=false` 即 404，點擊數保留）。
- `email_suppressions`（US9 新表）— Resend 判定為硬退信或垃圾信投訴的 email 封鎖名單；`email` varchar(255) unique（**恆為小寫**，寫入時正規化）、`reason` enum('bounce','complaint')、`detail` varchar(500) nullable（Resend 的 bounce `subType` / `message` 原文，供日後判讀真實原因）、`suppressed_at` timestamp、timestamps。
  不變量：一個 email 只有一列；`reason` 只能由 complaint 升級為 bounce，不可降級；本表只記「發生了什麼事實」，不記「要不要擋」（政策在 listener，見 D28）。

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

US 7（Meta CAPI 轉換追蹤強化）：

Phase 1 — 基建：
- [x] T009 `MetaConversionsService`（send + hashEmail/hashPhone 正規化 helpers、no-op 條件）in `app/Services/MetaConversionsService.php`
- [x] T010 `SendMetaConversionJob`（Graph API POST、tries=3 backoff、test_event_code、最終失敗僅 log）in `app/Jobs/SendMetaConversionJob.php`
- [x] T011 [P] migration `orders.meta_fbp` / `orders.meta_fbc` in `database/migrations/2026_07_12_000001_add_meta_click_ids_to_orders_table.php`
- [x] T012 [P] API 設定頁加 `meta_capi_access_token`（遮罩、留空不覆蓋）與 `meta_capi_test_event_code` in `app/Http/Controllers/Admin/SettingsController.php`, `resources/js/Pages/Admin/Settings/Payment.vue`

Phase 2 — 事件接點：
- [x] T013 結帳 initiate 快照 `_fbp`/`_fbc` 到 orders in `app/Http/Controllers/CheckoutController.php`, `app/Services/CheckoutService.php`
- [x] T014 `fulfillOrder` 與 Portaly webhook 入帳後送 CAPI Purchase（eventID `purchase_{merchant_order_no}`）in `app/Services/CheckoutService.php`, `app/Services/PortalyWebhookService.php`
- [x] T015 [P] 高價課預約送 Lead in `app/Services/HighTicketBookingService.php`
- [x] T016 [P] OTP 首次註冊 / 電子報訂閱建新 User 送 CompleteRegistration in `app/Http/Controllers/Auth/LoginController.php`, `app/Services/NewsletterService.php`
- [x] T017 [P] 免費領取送 FreeEnroll in `app/Http/Controllers/Purchase/FreePurchaseController.php`
- [x] T018 [P] blade Pixel init 附 server 端算好的 `{em: sha256(email)}` Advanced Matching in `resources/views/app.blade.php`

Phase 3 — 驗證：
- [x] T019 Feature/Unit 測試：hash 正規化、payload 結構、各接點 `Queue::fake()` 驗 dispatch、未設 token 時 no-op in `tests/Feature/MetaConversionsTest.php`

US 8（短網址轉址管理）：

Phase 1 — 資料層：
- [x] T020 migration 建 `short_links`（slug unique / target_url / name / is_active / clicks / last_clicked_at）in `database/migrations/2026_07_31_000001_create_short_links_table.php`
- [x] T021 `ShortLink` model：fillable、`is_active` bool + `last_clicked_at` datetime cast、slug 小寫正規化 mutator、`scopeActive()`、`recordClick()`（`increment('clicks', 1, ['last_clicked_at' => now()])`）、static `isReservedSlug()`（掃 `Route::getRoutes()` 第一段靜態片段）in `app/Models/ShortLink.php`

Phase 2 — 後台：
- [x] T022 [P] `StoreShortLinkRequest` / `UpdateShortLinkRequest`：slug regex `^[a-z0-9_-]{1,64}$`、unique(ignore self)、保留字 rule、`target_url` url + `active_url` 不驗（外部連結可能擋機器人）、僅 http/https、中文錯誤訊息 in `app/Http/Requests/Admin/StoreShortLinkRequest.php`, `app/Http/Requests/Admin/UpdateShortLinkRequest.php`
- [x] T023 `Admin\ShortLinkController`：index（列表 + `app.url` 供前端組完整網址）/ store / update / destroy，redirect back with flash in `app/Http/Controllers/Admin/ShortLinkController.php`
- [x] T024 admin 路由（`admin` 內層群組，非 staff）`/admin/short-links` resource 四條 in `routes/web.php`
- [x] T025 [P] `Admin/ShortLinks/Index.vue`：表格（短網址/目標/備註/點擊/最後點擊/啟用 toggle/編輯/刪除）+ 頂部新增列 + 複製按鈕（`navigator.clipboard`，含 fallback）+ RWD（窄螢幕改卡片）in `resources/js/Pages/Admin/ShortLinks/Index.vue`
- [x] T026 [P] AdminLayout 側欄加「短網址」入口（放在「API 設定」上方）in `resources/js/Layouts/AdminLayout.vue`

Phase 3 — 前台轉址與驗證：
- [x] T027 `ShortLinkRedirectController`（`__invoke`）：查 active slug → 找不到 `abort(404)` → `recordClick()` → `redirect()->away($url, 302)->header('Cache-Control', 'no-store')` in `app/Http/Controllers/ShortLinkRedirectController.php`
- [x] T028 catch-all 路由 `Route::get('/{slug}', ShortLinkRedirectController::class)->where('slug', '[A-Za-z0-9_-]+')` **加在 web.php 最末行**，並在檔尾留註解警告新路由要加在上面 in `routes/web.php`
- [x] T029 [P] `ShortLinkSeeder`（`firstOrCreate` 冪等，首筆 `/1v1`）in `database/seeders/ShortLinkSeeder.php`
- [x] T030 Feature 測試：命中轉址 302 + 計數 +1、大小寫不敏感、停用 404、不存在 404、既有路由（/blog、/admin）不被 catch-all 吃掉、保留字擋下、非 admin 進不了後台 in `tests/Feature/Platform/ShortLinkTest.php`

US 9（退信與投訴自動標記）：

Phase 1 — 資料層：
- [x] T031 migration 建 `email_suppressions`（email unique / reason enum / detail / suppressed_at）in `database/migrations/2026_08_09_000001_create_email_suppressions_table.php`
- [x] T032 `EmailSuppression` model：fillable、email 小寫正規化 mutator、`suppressed_at` datetime cast、static `reasonFor(string $email): ?string` in `app/Models/EmailSuppression.php`
- [x] T033 `EmailSuppressionService`：`record(string $email, string $reason, ?string $detail): void`（冪等 upsert，bounce 可升級、complaint 不降級）、`blocks(string $email, bool $isMarketing): bool`（bounce 全擋、complaint 只擋行銷）in `app/Services/EmailSuppressionService.php`

Phase 2 — 接收 webhook：
- [x] T034 `RecordEmailSuppression` listener 監聽 `Resend\Laravel\Events\EmailBounced` / `EmailComplained`：解析 payload 的 `data.to[]`、`data.bounce.type` / `subType` / `message`，只在 `Permanent` 時封鎖，其餘寫 log in `app/Listeners/RecordEmailSuppression.php`
- [x] T035 於 `AppServiceProvider::boot()` 註冊 listener in `app/Providers/AppServiceProvider.php`
- [x] T036 API 設定頁新增 `resend_webhook_secret`：加入 SettingsController 既有的 `$secretFields`（留空不覆蓋）、`maskSecret()` preview、validation，前端欄位旁標註「未設定＝端點無認證」in `app/Http/Controllers/Admin/SettingsController.php`, `resources/js/Pages/Admin/Settings/Payment.vue`（005）
- [x] T037 把 site_settings 的 secret 餵進 `config('resend.webhook.secret')`，**只在 webhook 路徑上執行**（`SiteSetting::get()` 無快取，放進每 request 的 boot 會讓全站多一次 DB query）in `app/Providers/AppServiceProvider.php`

Phase 3 — 攔截寄送：
- [x] T038 `BlockSuppressedRecipients` listener 監聽 `Illuminate\Mail\Events\MessageSending`：讀 `X-Mail-Class` 判定行銷/交易 → 查封鎖 → 命中則寫 log 並 `return false` 取消 → 未命中則移除該 header in `app/Listeners/BlockSuppressedRecipients.php`
- [x] T039 於 `AppServiceProvider::boot()` 註冊 listener in `app/Providers/AppServiceProvider.php`
- [x] T040 [P] 四支行銷 Mailable 於 `headers()` 掛 `X-Mail-Class: marketing` in `app/Mail/DripLessonMail.php`（010）, `app/Mail/NewsletterBroadcastMail.php`（012）, `app/Mail/NewsletterWelcomeMail.php`（012）, `app/Mail/BatchEmailMail.php`（008）
- [x] T041 [P] `NotifyHighTicketSlotJob` 寄出的 `TemplatedMail` 掛 marketing header（同 class 的預約確認 / 改期 / 取消信**不掛**）in `app/Jobs/NotifyHighTicketSlotJob.php`（011）
- [x] T042 `DripService::processSubscription` 開頭跳過已封鎖 email 的訂閱（不推進 `emails_sent`、不派 job）in `app/Services/DripService.php`（010）

Phase 4 — 後台可見性：
- [x] T043 [P] leads 名單查詢帶出每列封鎖狀態並於前端顯示「已退信 / 已投訴」標記 in `app/Http/Controllers/Admin/HighTicketLeadController.php`（011）, `resources/js/Components/Admin/Leads/BookingListTab.vue`（011）
- [x] T044 [P] drip 訂閱者名單同上 in `app/Services/DripService.php`（010）, `resources/js/Components/Admin/Leads/SubscriberListTab.vue`（010）

Phase 5 — 驗證：
- [x] T045 Feature 測試：Permanent bounce 建列 / Transient 不建列 / complaint 建列 / 重複事件冪等 / complaint→bounce 升級且不反向 / 行銷信被擋 / 交易信在 complaint 下照寄、在 bounce 下被擋 / drip 跳過已封鎖訂閱 / secret 已設時驗簽失敗回 403 / secret 未設時不掛驗簽（回歸提醒）in `tests/Feature/Platform/EmailSuppressionTest.php`

## 進度日誌

- 2026-08-06: /dev 完成 US9 退信與投訴自動標記 — `email_suppressions` 表 + `EmailSuppression`（小寫正規化、`reasonFor`/`reasonsFor` 單筆與批次查詢）、`EmailSuppressionService`（`record` 冪等升級、`blocks` 依 marketing 判斷）、`RecordEmailSuppression` 監聽 Resend `EmailBounced`/`EmailComplained`（只認 `Permanent`）、`BlockSuppressedRecipients` 監聽 `MessageSending` 單一攔截點（讀 `X-Mail-Class` 後移除該 header）、四支行銷 Mailable + `NotifyHighTicketSlotJob`（`withSymfonyMessage` 動態加 header，因 `TemplatedMail` 同時服務交易信）掛 marketing 標記、`DripService::processSubscription` 跳過已封鎖訂閱、leads/訂閱者名單顯示封鎖標記、API 設定頁加 `resend_webhook_secret` 遮罩欄位。**實作時修正 D27 的掛載點**：原規劃在 `AppServiceProvider::boot()` 依 `request()->is()` 判斷路徑，測試時發現 boot() 每個 process（測試裡是每個 test case）只跑一次、抓到的是啟動當下的 request 而非之後每次模擬的 HTTP 呼叫，導致 secret 永遠餵不進去；改監聽 `RouteMatched` 事件判斷路由名稱，行為正確且可用真實 HTTP 測試驗證，細節記在 D27。EmailSuppressionTest 16 tests，全套 519 passed、vite build 綠。T001（Error.vue exception handler）仍是既有 backlog，本次未動。
- 2026-08-06: [draft] 規劃 US 9 退信與投訴自動標記 — 接 Resend webhook（用套件內建 controller + 驗簽，只寫 listener）、`email_suppressions` 表、`MessageSending` 單一攔截點、行銷信掛 `X-Mail-Class` header 區分交易/行銷（D21~D28）。**定位修正**：Resend 自己的 suppression list 已自動處理硬退信與投訴、跨全網域跳過寄送，故本故事的價值是「本站的可見性與名單品質」，不是保護寄件信譽或省額度。審核時 D27 依「系統將量販給客戶」改版：webhook secret 從 env 改存 site_settings 後台遮罩欄位（設定要能由非工程師自助完成），並記下 `RESEND_API_KEY` 同樣該搬但風險等級不同、另開 US。
- 2026-08-04: Leads 名單頁併入 drip 訂閱者名單 tab（011 US8），銷售顧問的可視範圍隨之含訂閱者行為資料；路由與 middleware 未動，`/admin/courses/{course}/subscribers` 路由移除。
- 2026-08-02: 短網址「最後點擊」改以 `Asia/Taipei` 顯示（原本直接 format 吐 UTC，少 8 小時；DB 仍存 UTC）；`bootstrap/app.php` 新增兩條退訂路徑的 CSRF 豁免供 RFC 8058 一鍵退訂使用。ShortLinkTest 加時區斷言。
- 2026-07-31: /dev 完成 US8 短網址轉址管理 — `short_links` 表、`ShortLink` model（slug 小寫正規化、`recordClick()` 原子計數、`isReservedSlug()` 掃 route collection）、後台 `/admin/short-links` 單頁 inline CRUD（複製網址、啟停 toggle、點擊數）、side nav 入口、catch-all `/{slug}` 302 + no-store 轉址、`ShortLinkSeeder` 種 `/1v1`。TDD：ShortLinkTest 15 tests（含既有路由不被吃掉的回歸），全套 184 passed、vite build 綠。**部署後正式站要跑 `php artisan db:seed --class=ShortLinkSeeder` 或直接在後台新增 /1v1**。
- 2026-07-31: [draft] 規劃 US 8 短網址轉址管理 — `short_links` 表 + 後台單頁 CRUD + catch-all `/{slug}` 302 轉址（D14~D20：乾淨短路徑、動態保留字檢查、302 no-store、同表點擊計數）。首用途：`/1v1` → 1對1 諮詢的 Google Calendar 預約頁，日後交接員工只改後台。
- 2026-07-20: 前台 Navigation 左上角（站名左側）加上品牌 logo — Vite import `resources/images/og-logo.png`（touchpoint 012，與 OG 卡片共用同一支圖），`h-9` 顯示於 brand-navy 導覽列。純前端。
- 2026-07-12: /dev 完成 US7 Meta CAPI 轉換追蹤強化 — MetaConversionsService + SendMetaConversionJob（queue、3 retries、test_event_code）、Purchase 掛 fulfillOrder/Portaly（eventID 去重）、Lead/CompleteRegistration/FreeEnroll、orders.meta_fbp/meta_fbc 快照（encryptCookies 排除 _fbp/_fbc）、blade Advanced Matching、API 設定頁 CAPI 欄位；順手修正 SettingsController 機密欄位「留空不覆蓋」被 ConvertEmptyStringsToNull 破功的既有 bug；MetaConversionsTest 10 tests。

- 2026-07-12: [draft] 規劃 US 7 Meta CAPI 轉換追蹤強化 — Conversions API（queued job、Purchase 於 fulfillOrder/Portaly webhook 直送 + eventID 去重）、Advanced Matching（sha256 em/ph）、補 Lead/CompleteRegistration/FreeEnroll 事件、orders 快照 fbp/fbc、後台 CAPI token 欄位。
- 2026-07-12: Meta Pixel 追蹤修正 — (1) blade 的 Pixel ID fallback 改走 `config('services.meta.pixel_id')`（原直接 `env()`，production config:cache 後恆為 null）；(2) app.js 跳過初始載入的 navigate 事件，消除首次進站 PageView 重複送兩次。AddToCart 時機修正記在 005。
- 2026-07-12: 全域修正按鈕游標 — Tailwind v4 preflight 預設 button cursor:default，app.css @layer base 對非 disabled button 恢復 pointer；規則寫入 CLAUDE.md 與 constitution（可點元素必有 pointer + hover 樣式）

- 2026-07-12: /dev 完成 US6 銷售顧問受限後台存取 — StaffMiddleware + staff alias、/admin 拆外層 auth + 內層 staff/admin 兩子群組（route name 不變）、側欄與前台入口依角色過濾、auth.user 共享 is_sales_consultant；SalesConsultantTest 8 tests、全套 108 passed。T001（Error.vue exception handler）為既有 backlog 未動

- 2026-07-11: [draft] 規劃 US 6 銷售顧問受限後台存取（`is_sales_consultant` 旗標 + `staff` middleware + 路由分層 + 側欄/導航過濾）。指派 UI 見 008 US 9。
- 2026-07-11: 後台「金流設定」改名「API 設定」（側欄 nav + `SettingsController@updatePayment` 成功訊息；路由 `/admin/settings/payment` 不變）。頁面本身（Payment.vue）歸 005；此頁憑證取值為「site_settings（後台）優先、config/.env fallback」，PayUni `sandbox` 目前僅讀 .env。
- 2026-07-11: 後台側欄選單重排（內容類在上、營運類在下）＋「作業批改專區」改「作業批改」＋「推薦成效」併入「積分與推薦」單一入口（AdminLayout.vue）。新增 `DemoDataSeeder`（跨模組本機 demo 資料，可重跑、以標記自清）。
- 2026-07-06: 領域重組 — 全站基礎設施自各模組抽出，依實際 codebase 撰寫

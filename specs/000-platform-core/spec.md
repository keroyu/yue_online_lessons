---
id: 000-platform-core
status: building
owner_files:
  - app/Models/AiPrompt.php
  - app/Services/OpenAiService.php
  - config/ai.php
  - resources/js/Pages/Admin/Settings/Ai.vue
  - database/migrations/2026_08_17_000002_create_ai_prompts_table.php
  - tests/Feature/Platform/AiSettingsTest.php
  - app/Http/Controllers/Controller.php
  - resources/js/Components/Admin/HintBox.vue
  - resources/js/Components/Pagination.vue
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
  - resources/js/Components/Admin/Analytics/ShortLinkTab.vue
  - tests/Feature/Platform/ShortLinkTest.php
  - app/Http/Requests/Concerns/NormalizesTaipeiInput.php
  - config/database.php
  - tests/Feature/Admin/AdminDateInputTimezoneTest.php
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
  - database/migrations/2026_09_25_000001_install_site_identity_settings.php
  - app/Services/SiteIconService.php
  - config/themes.php
  - app/Services/ThemeService.php
  - resources/js/Components/Admin/ColorSchemePicker.vue
  - tests/Feature/Platform/ColorSchemeTest.php
  - app/Services/FirstAdminService.php
  - config/auth.php
  - .env.example
  - tests/Feature/Platform/FirstAdminTest.php
  - app/Http/Controllers/FaviconController.php
  - tests/Feature/Platform/SiteIconTest.php
  - database/migrations/2026_07_11_000003_add_is_sales_consultant_to_users.php
  - database/seeders/DemoDataSeeder.php
  - resources/js/app.js
  - resources/js/bootstrap.js
  - resources/js/Components/Layout/AppLayout.vue
  - resources/js/Components/Layout/Navigation.vue
  - resources/js/Components/Layout/Footer.vue
  - resources/js/Layouts/AdminLayout.vue
  - tests/Feature/Platform/AdminLayoutChromeTest.php
  - resources/js/Components/Legal/LegalPolicyModal.vue
  - resources/js/Components/Legal/PrivacyContent.vue
  - resources/js/Components/Legal/TermsContent.vue
  - resources/js/Components/Legal/PurchaseContent.vue
  - resources/js/Pages/Error.vue
  - resources/views/app.blade.php
  - resources/views/sitemap.blade.php
  - resources/views/welcome.blade.php
touchpoints:
  - file: resources/js/Pages/Admin/Posts/Index.vue
    owner: 012-newsletter
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Admin/Broadcasts/Index.vue
    owner: 012-newsletter
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Blog/Index.vue
    owner: 012-newsletter
    why: 前台文章列表的上一頁下一頁改用共用 Pagination 元件的 href 模式（FR-030 / FR-031）
  - file: resources/js/Pages/Blog/Tag.vue
    owner: 012-newsletter
    why: tag 頁與 /blog 是各自的檔案，同樣改 href 模式（FR-030 / FR-031）
  - file: resources/js/Pages/Admin/Homework/Index.vue
    owner: 003-classroom
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Admin/Members/Index.vue
    owner: 008-members-admin
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Admin/Transactions/Index.vue
    owner: 009-transactions-admin
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Admin/Coupons/Index.vue
    owner: 006-coupons
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Admin/CouponChains/Index.vue
    owner: 006-coupons
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Components/Admin/Leads/BookingListTab.vue
    owner: 011-high-ticket
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Components/Admin/Leads/SubscriberListTab.vue
    owner: 010-drip-email
    why: 分頁列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Member/Points.vue
    owner: 007-points-referral
    why: 積分明細分頁由 Laravel links 陣列改用共用 Pagination 元件（FR-030）
  - file: resources/js/Pages/Admin/HomepageSettings/Edit.vue
    owner: 002-storefront
    why: US14 配色選擇器掛進「首頁設定」頁成為一個區塊（D47）
  - file: app/Http/Controllers/Admin/HomepageSettingController.php
    owner: 002-storefront
    why: US14 edit() 多傳配色清單、新增 updateColorScheme()（D47）
  - file: tests/Feature/Newsletter/OgImageTest.php
    owner: 012-newsletter
    why: US14 配色 key 進 OG 卡片快取 key，該測試自行推導檔名故需同步（FR-124）
  - file: resources/js/Components/Classroom/RoadmapBoard.vue
    owner: 003-classroom
    why: keyframe 裡以 rgba() 寫死的 teal/gold 改 color-mix（US14 FR-122）
  - file: resources/js/Components/FeaturedCourses.vue
    owner: 002-storefront
    why: keyframe 裡以 rgba() 寫死的 gold 改 color-mix（US14 FR-122）
  - file: resources/js/Components/Classroom/AssignmentSection.vue
    owner: 003-classroom
    why: 寫死的 brand hex 改讀 CSS 變數，#336d8a 改 hover:bg-brand-teal/85（US14 FR-122）
  - file: resources/js/Components/Classroom/LessonPromoBlock.vue
    owner: 003-classroom
    why: <style> 內寫死的 gold / navy 改讀 CSS 變數（US14 FR-122）
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: bg-[#373557] 與漸層的 from-[#F6F1E9] 改回 brand utility（US14 FR-122）
  - file: resources/js/Components/Admin/LessonForm.vue
    owner: 004-course-admin
    why: CTA 產生器改由 theme prop 取當下配色的字面 hex（US14 FR-122 的唯一豁免）
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
  - file: app/Http/Controllers/Admin/AnalyticsController.php
    owner: 002-storefront
    why: US8 短網址列表資料改由該 controller 依 ?tab=short-links 提供（畫面併入行銷分析頁，見 002 US15）
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
  - file: app/Http/Controllers/Admin/HomepageSettingController.php
    owner: 002-storefront
    why: US12 站台資訊的後台編輯端點放在「首頁設定」頁（見 002 US22）
  - file: resources/js/Pages/Admin/HomepageSettings/Edit.vue
    owner: 002-storefront
    why: US12 站台資訊的編輯欄位（見 002 US22）
  - file: app/Models/EmailTemplate.php
    owner: 011-high-ticket
    why: US12 新增全域變數 {{site_name}}（比照既有的 {{support_email}} / {{app_url}}）
  - file: app/Http/Controllers/Admin/EmailTemplateController.php
    owner: 011-high-ticket
    why: US12 編輯頁的變數清單列出 {{site_name}}
  - file: database/seeders/EmailTemplateSeeder.php
    owner: 011-high-ticket
    why: US12 模板署名改用 {{site_name}}，新安裝不再帶入本站品牌字串
  - file: database/migrations/2026_08_06_000003_insert_booking_change_email_templates.php
    owner: 011-high-ticket
    why: US12 同上（該 migration 自帶模板本文，乾淨 DB 會吃到）
  - file: resources/views/emails/booking-verify.blade.php
    owner: 011-high-ticket
    why: US12 署名改讀 SiteSetting::siteName()
  - file: resources/views/emails/booking-verify-text.blade.php
    owner: 011-high-ticket
    why: US12 署名改讀 SiteSetting::siteName()
  - file: app/Services/OgImageService.php
    owner: 012-newsletter
    why: US12 OG 卡片的品牌 lockup 改讀站名（見 012 FR-013）；US14 卡片的 navy/teal 改讀作用中配色，配色 key 一併進快取 key（FR-124）
  - file: app/Http/Controllers/BlogFeedController.php
    owner: 012-newsletter
    why: US12 RSS 標題改讀站名
  - file: resources/views/emails/newsletter-broadcast.blade.php
    owner: 012-newsletter
    why: US12 頁尾站名改讀 SiteSetting::siteName()
  - file: resources/views/emails/newsletter-broadcast-text.blade.php
    owner: 012-newsletter
    why: US12 頁尾站名改讀 SiteSetting::siteName()
  - file: resources/views/emails/newsletter-welcome.blade.php
    owner: 012-newsletter
    why: US12 頁尾站名改讀 SiteSetting::siteName()
  - file: tests/Feature/Newsletter/OgImageTest.php
    owner: 012-newsletter
    why: US12 OG 卡片快取 key 含站名，斷言改為先設定再推算檔名
  - file: tests/Feature/Newsletter/EmailBrandNameTest.php
    owner: 012-newsletter
    why: US12 站名來源由 hero_title 改為 site_name（hero_title 降為 fallback）
  - file: resources/js/Components/Course/DripSubscribeForm.vue
    owner: 010-drip-email
    why: US12 「來信者為…」文案改讀共享的 site.name
  - file: resources/js/Pages/Auth/Login.vue
    owner: 001-auth-account
    why: US12 「來信者為…」文案改讀共享的 site.name
  - file: resources/views/emails/verification-code.blade.php
    owner: 001-auth-account
    why: US12 信頭與頁尾署名改讀 SiteSetting::siteName()
  - file: resources/views/emails/course-gifted.blade.php
    owner: 008-members-admin
    why: US12 署名改讀 SiteSetting::siteName()
  - file: resources/views/emails/lesson-added.blade.php
    owner: 004-course-admin
    why: US12 署名改讀 SiteSetting::siteName()
  - file: app/Http/Controllers/Auth/LoginController.php
    owner: 001-auth-account
    why: US13 OTP 首次註冊後呼叫 FirstAdminService::bootstrap()（唯一的呼叫點）
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
- [x] `HandleInertiaRequests` 全域共享：`auth.user`（id/email/nickname/real_name/phone/role）、`flash`（含 drip_* 鍵）、`cartCount`、`notificationCount`、`notifications`、`supportEmail`、`site`（站名/經營者/地址，見 US12）
- [x] Navigation 左上角站名與 logo alt、Footer copyright 的站名一律讀共享的 `site.name`，不得寫死品牌字串（US12）
- [x] `bootstrap/app.php` 對 `drip/unsubscribe/*` 與 `newsletter/unsubscribe/*` 豁免 CSRF —— RFC 8058 一鍵退訂由郵件用戶端直接 POST、無 session，不豁免會回 419（授權來源是網址中的 per-recipient token）。**注意**：`withMiddleware` 的 closure 只在 HTTP Kernel 被解析時執行，console/tinker 讀 `getExcludedPaths()` 永遠是空的

### User Story 2 - 管理後台版面與權限 (Priority: P1)

管理員進入 `/admin/*` 看到側欄版面；非管理員一律被擋下，不暴露後台存在。

**驗收**：
- [x] `admin` middleware alias（`AdminMiddleware`）：未登入或 `!isAdmin()` 重導首頁並 flash「您沒有權限存取此頁面」
- [x] 所有 `/admin/*` 路由套 `['auth', 'admin']` middleware 群組（見 `routes/web.php`）
- [x] `AdminLayout` 固定側欄（桌機）/ 抽屜側欄（行動版），選單涵蓋 Dashboard、首頁設定、課程、會員、交易、折扣碼、推薦成效、Email 模板、Leads、金流設定、積分設定、作業批改
- [x] **後台桌機版 MUST NOT 有置頂橫條**。原本的白色 `sticky top-0` top bar 裡只有一顆登出與手機漢堡鈕，桌機上是一條空白帶，而且每一頁的內容都會從它下面穿過去。唯一允許置頂的是手機版的漢堡列（`lg:hidden`，抽屜側欄沒有別的開啟方式）
- [x] 登出 MUST 放在側欄底部（與「返回前台」同一排），桌機與手機抽屜各一份；側欄只讓**連結清單**捲動（`overflow-y-auto` 在 `<nav>` 上），底部那排在矮視窗也不會被推出畫面
- [x] 以 `AdminLayoutChromeTest` 掃原始碼把關：任何 `sticky top-0` 的元素都必須帶 `lg:hidden`，且 `/logout` 連結恰好兩份 —— 這條橫條已經回來過一次
- [x] 側欄 active 判斷用路徑前綴；`/admin/coupons` 同時涵蓋 `/admin/coupon-chains`
- [x] 側欄底部顯示管理員暱稱首字頭像與「返回前台」連結
- [x] `AppServiceProvider` 註冊 `CoursePolicy`、`PurchasePolicy`（授權基礎）

### User Story 3 - 法務條款彈窗 (Priority: P2)

訪客在 Footer 點「服務條款／購買須知／隱私政策」即可閱讀完整條款，不需離開當前頁。

**驗收**：
- [x] Footer 三個按鈕開啟 `LegalPolicyModal`，依 `type`（terms/purchase/privacy）切換靜態內容組件
- [x] 彈窗支援 ESC 關閉、點背景關閉、開啟時鎖定 body scroll（關閉/卸載時還原）
- [x] 條款內容為前端靜態 Vue 組件（TermsContent / PurchaseContent / PrivacyContent）；條文本文寫死，但**站名、經營者、地址、客服信箱這四個識別資訊 MUST 讀共享 prop**（`site.name` / `site.operator` / `site.address` / `supportEmail`），不得寫死（US12 / 011 FR-057）
- [x] 經營者與地址留空時，服務條款與購買須知末尾 MUST 整行不渲染（不留「經營者：」這種空標籤）

### User Story 4 - SEO、Sitemap 與 Meta Pixel (Priority: P2)

搜尋引擎與社群分享能正確抓到頁面標題、描述、OG 圖；行銷可透過 Meta Pixel 追蹤全站瀏覽。

**驗收**：
- [x] `app.blade.php` 輸出 meta description、canonical、OG、Twitter Card；有 `$og` view 變數時用頁面專屬值（課程頁由 CourseController `view()->share('og', ...)` 提供），否則用以站名組出的預設文案。站名以 `@php $siteName = …; @endphp` 區塊取得——**不可用 `@php(...)` 行內形式**：帶命名空間靜態呼叫時 Blade 會編譯壞掉，症狀是同檔後面的變數（`$pixelId`）變成 undefined
- [x] `GET /sitemap.xml` 輸出已發佈課程清單（`is_published=true`），URL 優先用 `slug`、無 slug 退回 id，含 `lastmod`
- [x] Meta Pixel ID 取自 `SiteSetting::get('meta_pixel_id')`（fallback `config('services.meta.pixel_id')` ← env `META_PIXEL_ID`；不可在 blade 直接呼叫 `env()`，config:cache 後會失效）；有值才注入 Pixel script 並送 PageView
- [x] SPA 導航時 `app.js` 監聽 `router.on('navigate')` 補送 `fbq('track', 'PageView')`；初始整頁載入的第一次 navigate 事件跳過（blade 注入的 snippet 已送過，避免重複計數）
- [x] 頁面標題格式：`{title} - {站名}`，無標題時只有站名（站名來自 `site.name`，見 US12）。`app.js` 在 `setup()` 取一次 `initialPage.props.site.name` 存進模組變數——`title` callback 只有 `<Head>` 會呼叫，而那發生在 setup 之後，所以一次賦值涵蓋首屏與後續所有 SPA 導航

### User Story 5 - site_settings 全站設定機制 (Priority: P1)

管理員可在後台調整全站參數（金流憑證、Pixel ID、積分規則），不需改 code 或重新部署；
其他模組以 key-value API 讀寫。

**驗收**：
- [x] `SiteSetting` 提供靜態 API：`get(key, default)`、`getMany(keys)`、`set(key, value)`（upsert）、`supportEmail()`、`identity()` 與 `siteName()` / `siteOperator()` / `siteAddress()`（見 US12）
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
- [ ] 後台管理畫面為 `/admin/analytics?tab=short-links`（**2026-08-06 起併入行銷分析頁成為分頁**，見 002 US15；舊路徑 `GET /admin/short-links` 保留為轉址）：列表 + inline 新增/編輯/刪除，欄位為 slug、目標網址、備註名稱、啟用開關。寫入端點 `POST/PUT/DELETE /admin/short-links` 位置不變
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

### User Story 10 - AI 設定與 Prompt 管理 (Priority: P2)

011 US23 的面談摘要是本站第一個 AI 功能，但它不會是最後一個。這個故事把「用哪家、
用哪個模型、每個用途的 instructions 是什麼」抽成一塊全站基礎設施，讓第二、第三個 AI
功能上線時只需要插一列資料 + 呼叫一支 service，不必各自處理憑證、錯誤與模型選擇。

後台獨立分頁 `/admin/settings/ai`：上半是服務憑證（API key + 全站預設模型），
下半依**功能**分組列出所有 prompt，每個 prompt 各自可調 instructions 與**指定要用哪個模型**
（便宜的校訂用 luna、需要判斷力的摘要單獨升級，互不影響）。目前只有「面談整理」一組。

**驗收**：
- [x] `/admin/settings/ai` 為 admin-only（不開給 staff：AI 憑證是全站設定，且會產生費用）
- [x] 服務憑證區：`openai_api_key` 遮罩顯示、留空不覆蓋（沿用 D3/D13/D27 的既有 pattern）、`openai_default_model` 下拉
- [x] Prompt 區以 `v-for` 依 `feature` 分組渲染 `ai_prompts` 全表，MUST NOT 寫死任何單一 prompt —— 新增 AI 功能後這頁自動多一組，不改 Vue
- [x] 每列可編輯 `instructions`、`model`（含「使用全站預設」選項）、`max_output_tokens`，並唯讀顯示 label / description / `updated_at`
- [x] 模型選項來自 `config/ai.php`，MUST NOT 硬寫在 Vue —— OpenAI 出新型號時改一行
- [x] 後台 MUST NOT 能新增列、刪除列或修改 `key` / `feature` / `label`：一列對應一個程式裡寫死的呼叫點
- [x] `OpenAiService::respond(string $promptKey, string $input): ?string` 為全站唯一的 OpenAI 呼叫點；憑證或 prompt 列不存在時回 null，不丟例外
- [x] 側欄設定區加入口，與既有的積分／金流設定並列
- [x] 測試：admin 可讀寫、staff 被擋、送進來的 `key` / `feature` / `label` 被忽略、`model` 覆寫生效

### User Story 11 - 全站時區約定 (Priority: P1)

DB 一律存 UTC，讀者永遠在台北。轉換只發生在兩個邊界：表單送進來的那一刻、
資料送出去給人看的那一刻。中間層一律不碰時區。

**驗收**：
- [x] `config/app.php` 的 `timezone` 維持 `UTC`；`config/database.php` 兩個連線的 `timezone` 釘死 `+00:00`（可用 `DB_TIMEZONE` 覆寫），MUST NOT 留給主機的 `SYSTEM` 決定
- [x] `datetime-local` 送來的裸牆鐘字串 MUST 在 FormRequest 的 `prepareForValidation()` 用 `NormalizesTaipeiInput::readAsTaipei()` 轉一次，MUST NOT 在 controller 轉
- [x] `readAsTaipei()` 產出的是 **UTC instance**（`->utc()->toIso8601String()`），不是帶 `+08:00` offset 的字串
- [x] 送給人看的值（Inertia prop、CSV、信件內文、flash 訊息）在 `format()` / `toDateString()` 前 MUST 有 `->timezone('Asia/Taipei')`；序列化成 ISO 的不必轉（offset 已在字串裡）
- [x] `course_daily_stats` 與 `post_cta_clicks` 的日期桶**刻意維持 UTC 日**，為既有歷史資料的連續性；此例外 MUST 寫在 CLAUDE.md，避免被當 bug 修掉
- [x] 對外 API（Zoom、ICS）與 `date` 型欄位（`birth_date` 等）維持原樣，不套用上述轉換
- [x] 測試涵蓋「台北已過去但 UTC 讀起來像未來」的邊界（`AdminDateInputTimezoneTest`）

### User Story 12 - 站台資訊（站名 / 經營者 / 地址）(Priority: P1)

這份程式碼要能開第二個站。擋路的不是架構，是散落各處的字面字串：「經營者時間銀行」
寫死在導航列、頁尾、頁面標題、OG 卡片、八支信件模板裡，公司名與登記地址寫死在服務條款
與購買須知的結尾。換一個客戶就要在十幾個檔案裡改文字，而且改完兩份程式碼就開始各自漂移。

這條故事把三個識別資訊搬進 `site_settings`（沿用 US5 的機制，不新增資料表），
由後台「首頁設定」頁維護（編輯器本身屬 002，見 002 US22），全站顯示端一律讀設定。

**驗收**：
- [x] 三個鍵：`site_name`（站名）、`site_operator`（經營者）、`site_address`（地址）
- [x] `SiteSetting::identity()` 一次讀出三個值並回 `['name','operator','address']`；`siteName()` / `siteOperator()` / `siteAddress()` 為其薄包裝。MUST NOT 為此加 request 級快取 —— queue worker 的靜態狀態會跨 job 存活，後台改了值要等重啟才生效（`supportEmail()` 至今每次重查，同一理由）
- [x] 站名的 fallback 順序：`site_name` → `hero_title` → `config('app.name')`。`hero_title` 在這個鍵存在之前就是業主對外維護的品牌名（見 012 FR-013），升級的站 MUST 繼續顯示它本來顯示的東西
- [x] `HandleInertiaRequests` 以 `site` 這個 prop 全域共享三個值（一次 query）；MUST NOT 逐頁由 controller 傳 —— 條款彈窗掛在 footer，每一頁都可能印，逐頁傳一定會漏（沿用 011 FR-057 對客服信箱的同一判斷）
- [x] 前台顯示端全部改讀設定：Navigation（站名 + logo alt）、Footer（copyright）、法務條款三組件、登入頁與 drip 訂閱表單的「來信者為…」、`app.js` 的頁面標題、`app.blade.php` 的 title / OG / JSON-LD publisher / 預設 description
- [x] 後端顯示端全部改讀設定：`CourseController` 的 OG title、`BlogFeedController` 的 RSS 標題、`OgImageService` 的品牌 lockup（站名 MUST 進快取 key，改名才會重生舊卡片）、八支信件 blade 的署名
- [x] 信件模板新增全域變數 `{{site_name}}`（注入點同 `{{support_email}}` / `{{app_url}}`，見 011 FR-057）；`EmailTemplateSeeder` 與自帶模板本文的 migration 的署名改用該變數，乾淨 DB 不再帶入本站品牌字串
- [x] 資料遷移 `2026_09_25_000001_install_site_identity_settings`：**只有既有安裝**（判斷依據是 `site_settings` 已有 `hero_title` 列）才把現行的站名／公司／地址寫進去；乾淨 DB MUST 什麼都不插 —— 條款頁的法定資訊不能在升級時無聲消失，但也不能把本站的公司帶到別人的站上。既有列一律不覆寫（後台從此是唯一權威）
- [x] 站名必填（`max:100`）；經營者與地址可留空，留空時條款頁整行不渲染
- [x] **網站圖示（導覽列左上角）與 favicon 皆為後台上傳**，存在 public disk 的 `site-icons/`；限 `png,jpg,jpeg,webp`、2MB。導覽列的 `resources/images/og-logo.png` 降為 fallback —— 第二個安裝不可能替換打包進 JS bundle 的檔案
- [x] favicon **只上傳一張 PNG**，由 `SiteIconService` 自動派生 32×32、180×180（apple-touch-icon）與 `.ico`。縮圖前 MUST 先 `imagealphablending(false)` + `imagesavealpha(true)` + 填透明底，否則 GD 會把 alpha 壓在黑底上，淺色分頁上就是一塊黑
- [x] `.ico` 由 PHP 自行組出（GD 不會寫 ICO、本機也沒有 Imagick）：ICO 容器允許整包 PNG，所以檔案就是 6 bytes 檔頭 + 16 bytes 目錄項 + PNG 本體，Vista 之後的所有瀏覽器都讀得懂
- [x] `GET /favicon.ico` 由 `FaviconController` 提供，`public/favicon.ico`（Laravel 預設的 0 bytes 空檔）MUST 刪除，否則 nginx 會先命中它。沒上傳時回 404（瀏覽器自行退回預設圖示）
- [x] 產生的檔名帶內容 hash，換圖即換網址；舊檔在替換與刪除時 MUST 一併從 disk 移除（比照 002 FR-008 hero banner）
- [x] OG 卡片的 logo 改用上傳的圖（沒有才退回內建檔），且 logo 檔名 MUST 進快取 key，換 logo 才會重生舊卡片
- [x] 測試：`SiteIdentityTest`（儲存三值、站名必填、經營者/地址可空、訪客不得改、`site` prop 出現在每一頁、站名 fallback 到 hero_title）
- [x] 測試：`SiteIconTest`（logo 上傳後出現在 `site.logoUrl`、一張 PNG 派生三個檔且尺寸正確、`.ico` 的檔頭/目錄項/PNG 本體逐欄位驗證、`/favicon.ico` 有無圖各自回 200/404、layout 未上傳時不輸出 icon link、替換與刪除都清掉舊檔、訪客不得上傳、非圖片被擋）
- [x] 原始碼掃描把關：`app/` 與 `resources/` 底下 MUST NOT 出現舊的品牌字串（站名／公司名／地址），比照 011 FR-057 對客服信箱的作法 —— 只被一半的程式碼尊重的設定，比沒有設定更糟


### User Story 13 - 首次安裝的第一位管理員 (Priority: P1)

一份乾淨的資料庫沒有任何管理員，而後台是唯一能設定站名、金流、Email 模板的地方——
等於新裝好的站沒有入口。目前唯一的辦法是連進伺服器開 tinker 改 `users.role`，
那對「客戶自己裝一份」這個情境來說不成立（US12 的同一個問題，換一個面向）。

這條故事讓第一位以 OTP 註冊的人自動成為管理員，並把他的 email 寫進客服信箱設定
——後者同時修掉另一個跨站問題：`SiteSetting::DEFAULT_SUPPORT_EMAIL` 是本站的地址，
在別人的站上是錯的，只要這裡寫進真實值，那個 fallback 就不會再有機會出場。

**只認 `/login` 的 OTP 註冊**。訪客結帳、電子報訂閱、免費領取、Portaly webhook 也會
建 user，但那些路徑沒有任何人證明過自己收得到那個信箱的信；在新站首頁輸入一個 email
訂閱電子報就拿到整個後台，是不能接受的。

**驗收**：
- [x] `FirstAdminService::bootstrap(User $user): bool` 為唯一判斷點，呼叫點只有 `LoginController::verify()` 建立新帳號之後
- [x] 前提條件（兩者皆須成立才升權）：`users` 表**目前沒有任何 `role = admin`**，且下列其一成立
  - `config('auth.first_admin_email')`（env `FIRST_ADMIN_EMAIL`）有值 → 新帳號的 email 與它相符（不分大小寫、前後空白 trim）
  - 該設定留空 → 這個新帳號是 `users` 表裡**唯一**的一列（`count() === 1`）
- [x] 升權時 MUST 同時：`role = 'admin'`、把該 email 寫進 `site_settings.support_email`（**僅在該設定目前為空時**，已設定的值不覆蓋）、寫一行 `info` log（`first_admin.bootstrapped`，帶 user id 與 email）——權限升級不可以是無聲的
- [x] 前提不成立時 MUST 什麼都不做並回 `false`，且 MUST NOT 影響註冊流程本身（登入照常完成）
- [x] `FIRST_ADMIN_EMAIL` 有值但不相符時，即使那是全站第一個帳號也 MUST NOT 升權 —— 設了這個變數就表示「只有這個人」
- [x] 判斷與寫入 MUST 在同一個 transaction 內完成（與建立帳號同一筆），避免兩個同時進來的註冊各自讀到「還沒有 admin」
- [x] `.env.example` 補上 `FIRST_ADMIN_EMAIL=`，並在註解說明留空的行為
- [x] 測試：第一位 OTP 註冊者成為 admin 且 support_email 被填上；第二位不會；已存在 admin 時不會；`FIRST_ADMIN_EMAIL` 有值時只有相符者升權、不相符者即使是第一個帳號也維持 member；電子報訂閱/結帳建立的帳號不會升權（即使是第一列）；`support_email` 已有值時不被覆蓋


### User Story 14 - 全站配色方案切換 (Priority: P2)

US12 把站名、經營者、logo 搬進設定，讓這份程式碼開得了第二個站。剩下的最後一件識別
資訊是**顏色**：全站 90 個檔案、1,099 處 utility 都引用那七個 `--color-brand-*` token，
但 token 的值寫死在 `app.css` 的 `@theme` 裡，另有 37 處連 token 都不走、直接寫 hex。
換一個客戶的品牌色 = 改原始碼 + 重新 build + 重新部署，非工程師做不到。

這條故事把配色抽成**八組具名方案**（含現行這組），在既有的後台「首頁設定」頁一鍵切換，
前台與後台同時換色、不必 rebuild。不開放自訂色票 —— 八組通過對比驗算的方案，
比一個讓業主調出「白底淺黃字」的色票選擇器有用，而且那種畫面沒有人看得出是自己弄壞的。

**八組配色**（色值全部通過 FR-121 的九條對比門檻，逐組數字見 FR-121 下方）：

| key | 名稱 | 產業定位 | cream | navy | teal | gold | gold-dark | orange | red |
|---|---|---|---|---|---|---|---|---|---|
| `cream-indigo` | 奶油靛藍 | 知識型／生活風格（現行配色，預設） | `#F6F1E9` | `#373557` | `#33697F` | `#F0C14B` | `#C7A33B` | `#DF6807` | `#D92B1F` |
| `ink-bamboo` | 墨竹 | 東方人文 — 書法、茶道、國學、古典樂 | `#F5F2EA` | `#262521` | `#3F5E56` | `#C9A96B` | `#B0935A` | `#BE7C39` | `#B33A2B` |
| `deep-harbor` | 深港 | 商務財經 — 顧問、B2B、投資理財 | `#F2F5F7` | `#1B2A38` | `#2E5F8A` | `#C9A227` | `#B08F2A` | `#CA7827` | `#C93B3B` |
| `moss-field` | 苔原 | 健康永續 — 瑜珈、營養、園藝、戶外 | `#F3F2E8` | `#262E21` | `#4A7A3E` | `#D9A441` | `#BE8F38` | `#C27A26` | `#BF4332` |
| `terracotta` | 赤陶 | 手作職人 — 烘焙、料理、陶藝、木工 | `#F7F0E8` | `#3A2A22` | `#A34A32` | `#D4A24C` | `#BB8C3F` | `#D17031` | `#9B1B3F` |
| `electric-slate` | 電光石板 | 科技數位 — 程式、AI、SaaS、UI 設計 | `#FAFBFC` | `#14161C` | `#2563EB` | `#D9E84B` | `#A9B82C` | `#CD7F09` | `#E11D48` |
| `rose-quartz` | 玫瑰石英 | 美感生活 — 美妝、香氛、婚顧、花藝 | `#FBF3F1` | `#3A2430` | `#9B4A68` | `#E8B07A` | `#BE8652` | `#D96E48` | `#C02B4E` |
| `midnight-violet` | 夜航紫 | 身心靈創作 — 占星、冥想、音樂、寫作 | `#F4F1F8` | `#26203B` | `#6B4FA8` | `#D9C08A` | `#AE9662` | `#CD6C8D` | `#D13D5C` |

**驗收**：
- [ ] 八組配色（key / 中文名 / 產業定位 / 七個色票）全部定義在 `config/themes.php`；DB 只存選了哪一組（`site_settings.color_scheme`，沿用 US5 機制，零 migration）
- [ ] 切換 MUST NOT 需要 rebuild：主題就是那七個 CSS 變數的重新定義。Tailwind v4 把 `@theme` 的值放進 `:root` 並把 utility 編成 `var(--color-brand-*)`（實測 `.border-brand-teal{border-color:var(--color-brand-teal)}`），所以覆寫變數就等於全站換色
- [ ] 注入點是 `app.blade.php` 的 `<head>`、**`@vite(...)` 之後**的一段 `<style>`。順序不可調動：我們的 `:root` 與 Tailwind 的 `:root,:host` 特異性相同（0,1,0），靠原始碼順序取勝
- [ ] MUST NOT 改走 Inertia prop + JS 套用。那會在第一個 paint 先閃一次預設配色，而且 SSR／爬蟲／OG 預覽拿到的是預設色
- [ ] 七個 token 是**角色別名**、不是色相承諾（`teal` 在赤陶裡是燒赭、在玫瑰石英裡是深莓）：`cream` 頁面畫布、`navy` 墨色與後台側欄、`teal` 主要行動、`gold` 強調 CTA 填色、`gold-dark` gold 的 hover 與邊框、`orange` 次要強調數字、`red` 促銷與急迫。名稱維持舊名不重構（改名要動 90 個檔案 1,099 處，每一處都是無聲的錯誤風險）
- [ ] 每一組 MUST 通過九條對比門檻（FR-121 的表），由 `ColorSchemeTest` 逐組以 WCAG 相對亮度公式**算出來**驗證，不是人工目測。未通過的配色不得收錄
- [ ] 現行配色「奶油靛藍」原本有四條未達標（白字/teal 4.21、teal/cream 3.75、白字/red 3.42、orange/cream 1.77），依使用者決策一併修到過關：`teal #3F83A3 → #33697F`、`red #FF4438 → #D92B1F`、`orange #FAA45E → #DF6807`。**這是本次唯一一處會改變既有外觀的地方**，前台的主按鈕、連結、售價與分期價顏色會肉眼可辨地變深
- [ ] 八組 MUST 全為淺底（cream 亮、navy 暗）。深色模式**不在本故事範圍**：`cream` 是 `body` 底色、`navy` 是後台側欄底色配白字，這兩個角色反向鎖死，做深色等於把兩者對調，後台側欄會變成白底白字
- [ ] `app.css` 的 23 處與四支 Vue 的 14 處寫死 brand hex MUST 全部改讀 `var(--color-brand-*)`；`.section-dots` 的 `rgba(55,53,87,.22)` 改 `color-mix(in oklab, var(--color-brand-navy) 22%, transparent)`；`AssignmentSection.vue` 的兩處 `#336d8a` 改 `hover:bg-brand-teal/85`（全站既有慣例是用透明度做 hover，共 91 處，不另開深色 token）
- [ ] **唯一豁免 `LessonForm.vue` 的 CTA 產生器**：那段 HTML 帶 inline style 寫進課程內文存進 DB，會被 drip 信件原樣寄出，而 email client 不解析 CSS 變數。該處 MUST 插入「插入當下」的字面 hex（從共享的 `theme` prop 取），且已插入的內容日後換主題**不會**跟著變 —— 那是定稿的內容，不是樣式
- [ ] 切換介面 MUST 是既有「首頁設定」頁（`/admin/homepage`）的一個新區塊，**MUST NOT 另開後台分頁、MUST NOT 加側欄項目**。那頁已經是全站識別的維護處（站名、logo、favicon、Hero 主視覺都在那裡），配色是同一件事的最後一塊
- [ ] 區塊沿用該頁既有慣例：自成一張 `<section>` 卡片、自己的儲存端點（`POST /admin/homepage/color-scheme`），與其他五個區塊互不牽動。權限由 `/admin/homepage` 所在的 `admin` middleware 群組自然涵蓋，不必另外判斷
- [ ] 位置 MUST 是**第二張卡片**：緊接在「站台資訊」之後、「Hero 主視覺」**之前**。那頁有一條隱含的分界線 —— 站台資訊與配色是**全站**識別（每一頁都吃得到），Hero 以下五個區塊全是**首頁專屬**內容；兩張全站卡片並排壓在首頁內容之上，分界線才讀得出來。次要理由：即時預覽會把整個後台框架換色，放在不必捲動就看得到的位置才用得上
- [ ] 選擇器本身 MUST 抽成 `Components/Admin/ColorSchemePicker.vue`，不寫進 `Edit.vue` 的模板。那個檔案已經 877 行六個區塊，八張配色卡片攤進去會超過一千行；抽成元件後 `Edit.vue` 只多掛一個標籤，而元件歸 000（配色是本模組的資產，頁面才是 002 的）
- [ ] MUST 有即時預覽：點一張卡片就把該組七個變數寫上 `document.documentElement.style`，後台自己的側欄、按鈕、連結當場換色（後台本來就吃同一組 token）；「儲存」才寫 DB，「取消」移除 inline 變數還原。八張卡片各自顯示名稱、產業定位、七個色票與一塊縮小的版面示意（navy 導覽列 + teal 按鈕 + gold CTA + cream 底上的內文）
- [ ] `color_scheme` 驗證 MUST 用 `Rule::in(array_keys(config('themes.schemes')))`；`ThemeService::active()` 讀到不存在的 key 時 MUST 退回預設而非丟例外 —— 配色可能在日後改版被移除，DB 裡留下的舊值不該讓全站 500
- [ ] `OgImageService` 寫死的 navy/teal MUST 改讀作用中配色，且**配色 key MUST 進快取 key**，否則換了配色、舊的分享卡片會帶著上一組顏色留在 disk 上（比照 US12 對 logo 檔名的同一判斷）
- [ ] MUST NOT 為 `ThemeService` 加靜態快取（同 FR-113 對 `identity()` 的理由：queue worker 的靜態狀態跨 job 存活，後台改了要等重啟才生效）。每次請求兩筆索引讀（blade 一次、Inertia share 一次）是可接受的代價
- [ ] 原始碼掃描把關：`resources/css/` 與 `resources/js/` 底下 MUST NOT 再出現任何 brand 色的字面 hex（唯一豁免 `LessonForm.vue`）—— 比照 FR-117 對品牌字串的作法，只被一半程式碼尊重的設定比沒有設定更糟
- [ ] 測試：`ColorSchemeTest`（八組結構完整且皆為合法 6 位 hex、九條對比門檻逐組驗算、預設與未設定時皆為 `cream-indigo`、切換後首頁 HTML 含新 hex 且不含舊 teal、`<style>` 出現在 vite 的 CSS link 之後、非法 key 被擋、DB 存未知 key 時退回預設而非 500、訪客與銷售顧問不得打 `POST /admin/homepage/color-scheme`、儲存配色不會動到同頁其他五個區塊的設定值、原始碼掃描無漏網 hex）


## Requirements

- **FR-001**: `routes/web.php` 是全站路由總表；購物車/結帳 API 必須放 web.php 的 `api` prefix 群組而非 `routes/api.php`（api 群組無 StartSession，結帳需讀 session 的 `traffic_source`）
- **FR-002**: `routes/api.php` 僅放無 session 需求的端點：金流 NotifyURL webhooks、Portaly webhook、免費課程報名、付款結果輪詢
- **FR-003**: 金流 ReturnURL（瀏覽器 redirect）放 web.php 並豁免 CSRF（`withoutMiddleware(ValidateCsrfToken)`）
- **FR-004**: `bootstrap.js` 為 axios 設定 `X-Requested-With` 與 `X-CSRF-TOKEN`（讀 `<meta name="csrf-token">`），所有前端 axios 呼叫依賴此設定
- **FR-005**: `cartCount`、`notifications` 等共享 props 用 closure 延遲求值，僅在頁面實際回傳時查詢
- **FR-006**: `php artisan content:html-to-markdown` 為一次性維運指令：將 `courses.description_md`、`lessons.content_md` 內殘留 HTML 轉為 Markdown（`saveQuietly` 不觸發事件）
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
- **FR-026**: US9 收得到事件的**前提**是寄件網域在 Resend 完成驗證、且 DNS 有那筆 `MX` 記錄 — 該 MX 正是收件方回報退信與投訴的通道，少了它 webhook 永遠不會被觸發，封鎖名單恆為空而且不會有任何錯誤訊息。此前提不在程式碼裡，只能靠後台 API 設定頁的說明傳達（見 US9 的設定說明），部署新站台時 MUST 一併檢查

- **FR-027**: `ai_prompts` 的 `key` / `feature` / `label` 由**程式**擁有，後台只得編輯 `instructions` / `model` / `max_output_tokens`。`updateAi` MUST 忽略請求裡的其餘欄位，且 MUST NOT 提供新增／刪除列的路徑 —— 一列對應一個程式裡寫死的呼叫點，讓使用者自由增列只會產生沒有東西會讀的孤兒資料。新功能上線時由 migration 補列
- **FR-028**: 安裝預設 prompt 的 migration MUST 採「逐筆查 `key`、缺才 insert、**永不 update**」（沿用 `2026_08_09_000003` 對 email template 的既有作法）。正式站的 instructions 可能已被業主調整過，`updateOrCreate` 會把它默默改回原廠值
- **FR-029**: `OpenAiService::respond()` 為全站唯一的 OpenAI 呼叫點。憑證未設定或指定的 `ai_prompts` 列不存在時 MUST 回 `null` 而非丟例外 —— 呼叫端據此靜默跳過該功能，本機與 CI 永遠不需要真的 API key（沿用 011 D40 對 Zoom 的既有立場）。模型解析順序為 `ai_prompts.model` → `site_settings.openai_default_model` → `config('ai.default_model')`。
  取文字時 MUST **依 `type` 走訪 `output` 陣列找 `message`**，MUST NOT 假設 `output.0` —— 推理模型會先放一個空的 `reasoning` 項、`message` 在其後，讀索引 0 會得到空字串並誤判成「模型沒回話」。另外，200 卻取不到文字 MUST 寫 warning：那是解析錯誤或回應格式變動，不是「未設定」，兩者用同一種靜默的 null 表達就沒有人會發現

- **FR-030**: 全站**每一個**分頁導覽 MUST 使用 `Components/Pagination.vue`，MUST NOT 自行 `v-for="p in last_page"`、自行拼上一頁／下一頁，或直接渲染 Laravel 的 `links` 陣列。元件最多渲染 **10 個頁碼位置**：當前頁置中的滑動視窗，加上恆常可見的第 1 頁與最後一頁，被截掉的區段以不可點的 `…` 表示；當前頁撞到頭尾時視窗往另一側補滿，位置數不因此變少。`last_page <= 10` 時 MUST 完整列出且 MUST NOT 出現 `…`；`last_page <= 1` 時 MUST 不渲染任何東西。

- **FR-031**: 元件有兩種導覽模式，呼叫端二選一：未給 `href` 時渲染 `<button>` 並 `emit('change', page)`（後台各列表用，換頁邏輯留在呼叫端）；給了 `href`（`(page) => string`）時渲染 Inertia `<Link>`。前台 `/blog` 與 tag 頁 MUST 用 href 模式 —— 那裡的分頁是**爬蟲要跟得到的真連結**，換成只有 click handler 的按鈕等於把第 2 頁以後的文章從索引裡拿掉。

- **FR-110**: Eloquent 寫入 DB 時**用 Carbon 自帶的時區直接 format，不做任何轉換**，讀取時才用 `app.timezone` 解析；query binding（`where('x', '>=', $carbon)`）同樣不轉換。因此送進持久層或查詢條件的 Carbon MUST 已經是 UTC —— 一個帶 `+08:00` 的實例會被原樣寫成台北牆鐘，再被當成 UTC 讀回來，差的還是那 8 小時，只是移到了下一步才發作
- **FR-111**: 時區轉換 MUST 發生在 `prepareForValidation()` 而非 controller。`after:now` / `before:` 這類規則在驗證階段就會比對時刻，晚一步轉換等於讓驗證拿錯誤的時刻去比 —— 具體後果是放行一個其實已經過去 8 小時的時間（台北 07:00 是 UTC 前一天的 23:00，「未來」的判斷會反過來）
- **FR-115**: 自動升權的判斷 MUST 同時看「目前沒有 admin」與「是第一個帳號或符合 `FIRST_ADMIN_EMAIL`」。只看「沒有 admin」是不夠的：一個已上線的站如果管理員被停用或刪掉，下一個註冊的陌生人就會接手整個後台。只看「users 是空的」也不夠，因為 webhook 與電子報訂閱會先建出 member。
- **FR-116**: 自動升權 MUST 只由 `/login` 的 OTP 註冊觸發。其餘建帳路徑（`CheckoutService::findOrCreateUser`、`NewsletterService`、`DripSubscriptionController`、`PortalyWebhookService`、後台匯入）MUST NOT 觸發 —— 只有 OTP 這條路徑上的人證明過自己收得到那個信箱的信。
- **FR-117**: 升權 MUST 寫 log。這是全站唯一一處會在沒有人操作後台的情況下產生管理員的程式碼，出事時要查得到是誰、什麼時候拿到的。

- **FR-113**: 對外顯示的站台識別（站名、經營者、地址）MUST 讀 `site_settings`，MUST NOT 以字面字串寫在任何 Vue／Blade／PHP 檔。這條規則的存在理由不是「可設定比較好」，而是這份程式碼要同時跑在多個客戶的站上：一個寫死的品牌字串會讓第二個安裝從第一天就是錯的，而且錯得很安靜（沒有人會收到「頁尾印著別人的公司名」的錯誤）。
- **FR-114**: Blade 取站名 MUST 用 `@php … @endphp` 區塊形式。`@php(\App\Models\SiteSetting::siteName())` 這種行內形式在 `app.blade.php` 會編譯壞掉，症狀出現在**同檔後面**的變數（`$pixelId` undefined），與站名本身無關，除錯時幾乎不會往這裡看。

- **FR-112**: 有 `useCurrent()` 的欄位由 MySQL 自己填（`cart_items` / `order_items` / `lesson_progress` / `course_images` / `post_images`，皆為 `$timestamps = false` 的 model），走的是 **MySQL 的 session 時區**而非 PHP 的。連線時區若留 `SYSTEM`，同一張表裡 PHP 寫的列與 MySQL 寫的列會差 8 小時，而且開發機（macOS 預設 Asia/Taipei）與正式站（UTC）的行為不一致 —— 這是 FR-109 之外另一個「不會有任何錯誤訊息」的靜默分歧

- **FR-118**: 配色切換 MUST 以「重新定義 `--color-brand-*` 七個變數」實現，MUST NOT 以切換 class、切換 stylesheet 或重新 build 實現。已知限制：帶透明度的 utility（`bg-brand-teal/20` 這類，全站 91 處）被 Tailwind 編成 `color-mix(in oklab, var(--color-brand-teal) 20%, transparent)` 並在 `@supports` 外留了一份寫死的 hex 備援，所以不支援 `color-mix()` 的瀏覽器（Safari < 16.2 / Chrome < 111）在**那些半透明的邊框與底色**上會停在預設配色。不透明的 utility（1,008 處）是純 `var()`，所有瀏覽器都跟著換
- **FR-119**: 配色定義 MUST 放 `config/themes.php`，MUST NOT 進 DB。八組色票是**程式碼資產**不是使用者資料：要跟著 git 走、要能在測試裡直接斷言、要能被 `config:cache` 快取；而「新增第九組」= 改一個 array，不必 migration 也不必資料搬遷。DB 只存作用中的 key 一個字串
- **FR-120**: 所有配色 MUST 為淺底。`cream` 是 `body` 底色、`navy` 是後台側欄底色配白字，這兩個角色反向鎖死；深色主題必須同時把兩者對調，那是另一條故事而不是多一組色票
- **FR-121**: 收錄門檻（WCAG 2.1 相對亮度，全部由測試算出，非目測）。價格與分期價是 `text-xl`～`text-3xl` 的粗體，適用大型文字的 3:1；其餘一律 4.5:1，內文對底色取 7:1：

  | 組合 | 用途 | 門檻 |
  |---|---|---|
  | navy / cream | 內文對頁面底色 | 7.0 |
  | white / navy | 導覽列與後台側欄 | 4.5 |
  | white / teal | 主要按鈕 | 4.5 |
  | teal / cream | 連結 | 4.5 |
  | navy / gold | 強調 CTA | 4.5 |
  | navy / gold-dark | 強調 CTA 的 hover 狀態 | 4.5 |
  | white / red | 促銷徽章 | 4.5 |
  | red / cream | 售價（大型粗體） | 3.0 |
  | orange / cream | 分期價（大型粗體） | 3.0 |

  八組實測值（`ColorSchemeTest` 的斷言即這張表）：

  | 組合 | 門檻 | 奶油靛藍 | 墨竹 | 深港 | 苔原 | 赤陶 | 電光石板 | 玫瑰石英 | 夜航紫 |
  |---|---|---|---|---|---|---|---|---|---|
  | navy/cream | 7.0 | 10.32 | 13.71 | 13.37 | 12.49 | 12.11 | 17.45 | 13.01 | 13.89 |
  | white/navy | 4.5 | 11.61 | 15.34 | 14.64 | 14.05 | 13.69 | 18.08 | 14.24 | 15.53 |
  | white/teal | 4.5 | 6.06 | 7.13 | 6.73 | 5.07 | 5.86 | 5.17 | 5.88 | 6.35 |
  | teal/cream | 4.5 | 5.39 | 6.37 | 6.15 | 4.51 | 5.19 | 4.99 | 5.37 | 5.68 |
  | navy/gold | 4.5 | 6.87 | 6.85 | 6.05 | 6.24 | 5.91 | 13.43 | 7.41 | 8.77 |
  | navy/goldD | 4.5 | 4.83 | 5.24 | 4.74 | 4.80 | 4.52 | 8.25 | 4.56 | 5.43 |
  | white/red | 4.5 | 4.87 | 5.90 | 5.03 | 5.17 | 8.01 | 4.70 | 5.68 | 4.63 |
  | red/cream | 3.0 | 4.33 | 5.28 | 4.60 | 4.60 | 7.09 | 4.53 | 5.19 | 4.14 |
  | orng/cream | 3.0 | 3.05 | 3.06 | 3.08 | 3.06 | 3.06 | 3.05 | 3.06 | 3.07 |

- **FR-122**: brand 色 MUST NOT 以字面值出現在 `resources/css/` 或 `resources/js/`，**`#RRGGBB` 與 `rgb()`／`rgba()` 十進位形式都算**（實作時 hex-only 的掃描漏掉五處寫成 `rgba(63,131,163,…)` 與 `rgba(240,193,75,…)` 的 teal 與 gold，正是這條規則要防的「安靜的半套設定」）。唯一豁免是 `LessonForm.vue` 產生的 CTA HTML —— 那段 inline style 存進 DB 的課程內文、被 drip 信件原樣寄出，email client 不解析 CSS 變數，用 `var()` 會得到一顆沒有底色的按鈕
- **FR-123**: 注入的 `<style>` MUST 在 `@vite(...)` **之後**。我們的 `:root` 與 Tailwind preflight 的 `:root,:host` 特異性相同，勝負只由原始碼順序決定；放前面的症狀是「換了配色但畫面完全沒變」，而那看起來會像是儲存沒成功
- **FR-124**: 任何以品牌色繪製並快取到磁碟的產物（目前只有 `OgImageService` 的 OG 卡片）MUST 把配色 key 放進快取 key。否則換配色之後，舊卡片帶著上一組顏色永遠留在 disk 上，而且只有在別人分享連結時才看得出來（與 US12 對 logo 檔名的判斷同源）


## 設計決策

- **D36**: 基準時區維持 **UTC**，不改成 `Asia/Taipei`（使用者發現正式站主機是 UTC 時提出，經評估後否決切換）。切換要位移 121 個 datetime 欄位、44 張表，單向不可逆且需停機停 worker，換來的只有「4 個表單順手修好 + DB 直接看比較順眼」；而對外整合（Zoom、ICS、金流 webhook）全講 UTC，改成本地時區只是把轉換從入口搬到出口。真正的病是「轉換寫得不一致」不是「基準選錯」，所以補齊邊界轉換、把約定寫進 CLAUDE.md，零 migration 零停機。另兩個理由：正式站的 nginx / PHP-FPM log 是 UTC，app log 改台北會在查線上問題時差 8 小時；`consultation_notes.consultant_id` 已預留多顧問，境外顧問只有 UTC 撐得住
- **D37**: 入口轉換做成 **trait**（`NormalizesTaipeiInput`）而非各 FormRequest 自己寫。這次一口氣有 5 個 FormRequest 要同樣的處理，而漏掉的代價是靜默的 8 小時偏差 —— 沒有例外噴出來，只有使用者事後發現課程在半夜開賣。共用一個入口也讓「UTC instance 而非 offset 字串」這個容易寫錯的細節只需正確一次

- **D29**: AI prompt 存**獨立的 `ai_prompts` 表**，不散成 `site_settings` 的一堆 key。使用者要求這頁「方便以後擴展到其他 AI 功能」，而用表的話新增一個功能 = 插一列資料、設定頁自動長出區塊；用 site_settings 的話每加一個功能都要動 controller 與 Vue。這與 D2「新增設定鍵零 migration」不衝突：D2 的前提是設定鍵彼此獨立、UI 各自寫死，而 prompt 是**同構的一組**，同構的東西該用列而不是鍵。
- **D30**: 每個 prompt 各自可指定 `model`，而不是全站一個模型設定（使用者決策）。同一個功能裡的不同步驟對智慧的需求差很多 —— 逐字稿校訂是機械活、摘要要判斷力，兩者用同一個模型不是浪費就是將就。`null` 表示「跟隨全站預設」，讓大多數 prompt 不必操心這件事。
- **D31**: 獨立分頁 `/admin/settings/ai`，不塞進既有的「API 設定」（金流）頁。金流頁已經是四張憑證卡片，再加 AI 憑證 + N 組 prompt textarea 會變成一頁滾不完的東西；而且 AI 設定的擴展方向明確（會一直長），值得從一開始就有自己的地方。定為 admin-only 而非 staff：這裡的每一次儲存都會影響全站 AI 行為與費用。

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

- **D32**: 頁碼收斂成單一共用元件而不是各頁 inline —— 這段程式碼在專案裡有**三種寫法、十個副本**（三份無上限頁碼、六份上一頁／下一頁、一份直接吐 Laravel `links`），也就是說下一個列表頁會貼上第十一份。元件只吃 `current-page` / `last-page` 兩個數字，不碰 router：呼叫端換頁的方式各不相同（帶篩選的 `router.get`、`preserveState`、`preserveScroll` 各有各的），把導覽塞進元件會逼出一個誰都不合身的 props 介面。
- **D33**: 元件放 `Components/Pagination.vue` 而非 `Components/Admin/` —— 會員積分頁與前台 `/blog` 也在使用者要求的「全部」範圍內，放進 `Admin/` 會讓每個非後台呼叫端都在說謊。樣式因此不能寫死後台色票：當前頁用 `bg-brand-navy text-white`（現有後台頁碼列的樣式），其餘為中性灰邊框，前後台共用同一組。
- **D34**: `Member/Points.vue` 原本直接渲染 Laravel 的 `links` 陣列並用 `v-html` 輸出 `label`（`&laquo; Previous` 這類 HTML entity）。改用元件後 `v-html` 一併消失 —— 那是為了顯示 `«` 而開的一個 `v-html`，資料雖然來自 Laravel 而非使用者，但一個純顯示的分頁列不該有這種東西。呼叫端改傳 `current_page` / `last_page` 兩個數字。
- **D35**: 前台 `/blog` 原本只有上一頁／下一頁兩顆 `<Link>`，這次一併長出頁碼。它是三種寫法裡唯一**沒有**可點頁碼的畫面，而讀者要回到半年前那篇文章時，「一路按下一頁」和「點第 7 頁」的差距就是會不會放棄。href 模式保住原本的爬蟲可跟性（FR-031）。

- **D41**: 主題做成「token 重新定義」而不是「多套 class」或「多份 stylesheet」。全站已經有 1,099 處 utility 引用那七個 token，而 Tailwind v4 把它們全部編成 `var(--color-brand-*)` —— 也就是說**換色的能力早就在編譯結果裡了**，我們只是沒有去用。覆寫七行 CSS 變數，比起產生八份 stylesheet（八倍的 CSS、快取失效、還要決定怎麼選檔）或在 90 個檔案上掛 `theme-x:` 變體（每一處都要改、每一處都可能漏）便宜兩個數量級。
- **D42**: 注入在 blade 的 `<head>`，不走 Inertia prop 讓前端套用。配色必須在**第一個 paint 之前**就位：走 prop 的話首載會先閃一次預設配色（尤其是滿版的 cream 底色與 navy 導覽列，閃動非常明顯），而且爬蟲與 OG 預覽器根本不執行 JS，拿到的永遠是預設色。代價是 blade 要多讀一筆 `site_settings`，與 `siteName()`／`supportEmail()` 已經在做的事情一樣。
- **D43**: 配色存 `config/themes.php` 而非 DB（承 D2 的分界線）。D2 說的是「設定鍵進 site_settings 以免 migration」，但那指的是**使用者的選擇**；八組色票本身是設計成果、是程式碼，進了 DB 就變成「改個色票要下 SQL」「測試要先 seed」「兩個安裝的色票會各自漂移」。分界線是：**選了哪一組**進 DB，**有哪些組**進 git。
- **D44**: 維持 `cream`/`navy`/`teal`/`gold`/`gold-dark`/`orange`/`red` 這組舊名，不重構成 `canvas`/`ink`/`primary`/`accent` 之類的語意名。語意名確實比較誠實（赤陶的 `teal` 是燒赭），但改名要動 90 個檔案、1,099 處字串，而漏掉一處的症狀是那一個元素留在舊配色——不會報錯、不會壞版、只會在某個角落一直錯下去。把「token 名是角色別名」寫進文件的成本是零，改名的成本是一次高風險全域取代，換來的只有可讀性。
- **D45**: 八組全部淺底，深色模式明確排除在外。`navy` 同時是「內文顏色」與「後台側欄底色（配白字）」，`cream` 同時是「頁面底色」與「導覽列上的淺色文字」——要做深色主題就得讓這兩個角色互換，那不是換七個值，那是把每一處 `text-brand-navy` 與 `bg-brand-navy` 重新判斷一次。夾帶進這條故事會讓一個低風險的變更變成高風險的。
- **D47**: 配色選擇器塞進既有的「首頁設定」頁，不另開 `/admin/settings/appearance`（使用者決策）。理由站得住腳：那一頁已經是**全站識別的維護處** —— 站名、經營者、logo、favicon（US12）、Hero 主視覺都在那裡，配色是同一件事剩下的最後一塊，拆成兩頁等於要業主記住「文字在這裡、顏色在那裡」。側欄也因此不必再長一項（目前 16 項，已經是滿的）。代價是 `Edit.vue` 會變大，所以選擇器抽成 `Components/Admin/ColorSchemePicker.vue`：元件歸 000（配色是本模組的資產），頁面留在 002，`Edit.vue` 只多掛一個標籤。儲存走自己的端點 `POST /admin/homepage/color-scheme`，與該頁既有的五個區塊一致（每區塊各自送出、互不牽動）。位置定在**第二張**（站台資訊之後、Hero 主視覺之前）：那頁其實分成「全站識別」與「首頁內容」兩段，只是從來沒有寫下來 —— 站台資訊與配色每一頁都吃得到，Hero 以下只影響首頁。把兩張全站卡片並排放在最上面，這條分界線才對維護的人可見。
- **D46**: 對比是**收錄條件**而不是建議，且由測試算出來而不是設計時目測。理由來自抽取現行配色時的實測結果：這組用了一年多的配色有四項未達 AA，其中「分期價的橘字在米色底上」只有 1.77:1 —— 那是實際上讀不了的東西，而它一直在線上。人工目測抓不到這種事（橘色在白底上「看起來很清楚」是因為飽和度高，不是因為亮度差夠），所以門檻必須是九行可執行的斷言。使用者決定連現行配色一起修到過關（`teal`／`red`／`orange` 三個值改動），這是本次唯一會改變既有外觀的地方。


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
- `ai_prompts`（US10 新表）— 全站 AI 功能的 prompt 與模型設定；`key` varchar(50) unique（程式對接鍵，比照 `email_templates.event_type` 的角色）、`feature` varchar(50)（功能分組，後台依此分區）、`label` varchar(100)、`description` varchar(255) nullable、`instructions` text、`model` varchar(50) nullable（null = 用 `openai_default_model`）、`max_output_tokens` unsigned int nullable、`sort_order` int default 0、timestamps。
  不變量：列由 migration 建立，**後台不得新增、刪除或改 `key` / `feature` / `label`**（FR-027）；一個 `key` 對應程式裡恰好一個呼叫點，沒有呼叫點的列即為孤兒；安裝 migration 永不 update 既有列（FR-028）。
- `site_settings` 新鍵（US10）：`openai_api_key`（機密、遮罩、留空不覆蓋）、`openai_default_model`（非機密，空 = 用 `config('ai.default_model')`）。
- `site_settings` 新鍵（US12）：`site_name`（站名，必填、`max:100`；空值時讀取端 fallback `hero_title` → `config('app.name')`）、`site_operator`（經營者，可空）、`site_address`（地址，可空）。三者皆非機密、送出即覆蓋。安裝 migration 只對既有安裝（已有 `hero_title` 列）補值，乾淨 DB 留空。
- US13 **沒有 migration**：沿用既有的 `users.role` enum 與 `site_settings.support_email`，只是第一次有程式會自動寫它們。不變量：全站只有 `FirstAdminService` 會在無人操作後台的情況下寫入 `role = 'admin'`。
- `site_settings` 新鍵（US12 圖示）：`site_logo_path`（導覽列圖示）、`site_favicon_path`（32×32 PNG）、`site_favicon_apple_path`（180×180 PNG）、`site_favicon_ico_path`（`.ico`）。四者皆存 public disk 的相對路徑，後三個由上傳的單張 PNG 派生、不可手動設定；刪除時值設為空字串並同步刪檔。

US 10（AI 設定與 Prompt 管理）：

- [x] T0A1 migration 建 `ai_prompts` 表，並以「逐筆查 key、缺才 insert、永不 update」寫入 `consultation_transcript_proofread` 與 `consultation_summary` 兩列（FR-028）in `database/migrations/2026_08_17_000002_create_ai_prompts_table.php`
- [x] T0A2 `config/ai.php`：`features`（feature → 中文顯示名）與 `models`（可選型號清單）+ `default_model` in `config/ai.php`
- [x] T0A3 `AiPrompt` model：`fillable` 三欄 + `AiPrompt::for(string $key): ?self` in `app/Models/AiPrompt.php`
- [x] T0A4 `OpenAiService::respond(string $promptKey, string $input): ?string` —— 讀憑證與 prompt、解析模型（FR-029）、`Http::withToken()->timeout(120)->retry(2, 2000, throw: false)` 打 `POST https://api.openai.com/v1/responses`、取 `output_text`（退回依 `type` 走訪 `output` 找 `message`，見 FR-029）、失敗帶 body 丟 RuntimeException in `app/Services/OpenAiService.php`
- [x] T0A5 `SettingsController::ai()` / `updateAi()` —— 憑證沿用 `maskSecret()` 與留白不覆蓋；prompt 只更新三個可編欄位（FR-027）in `app/Http/Controllers/Admin/SettingsController.php`
- [x] T0A6 路由 `GET|POST /admin/settings/ai` 加在 `admin` 群組（動詞比照同頁層級的 payment / points，皆為 post） in `routes/web.php`
- [x] T0A7 `Ai.vue`：憑證區 + 依 feature 分組的 prompt 區（`v-for`，不寫死任何 prompt）in `resources/js/Pages/Admin/Settings/Ai.vue`
- [x] T0A8 [P] 側欄設定區加「AI 設定」入口 in `resources/js/Layouts/AdminLayout.vue`
- [x] T0A9 [P] `AiSettingsTest`：admin 可讀寫、staff 被擋、`key`/`feature`/`label` 被忽略、`model` 覆寫生效、憑證未設定時 `respond()` 回 null in `tests/Feature/Platform/AiSettingsTest.php`

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
- [x] T025 [P] `Admin/ShortLinks/Index.vue`：表格（短網址/目標/備註/點擊/最後點擊/啟用 toggle/編輯/刪除）+ 頂部新增列 + 複製按鈕（`navigator.clipboard`，含 fallback）+ RWD（窄螢幕改卡片）in `resources/js/Pages/Admin/ShortLinks/Index.vue`（2026-08-06 已搬為 `resources/js/Components/Admin/Analytics/ShortLinkTab.vue`，見 002 US15）
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

全站分頁元件（FR-030 / FR-031 / D32–D35）：

- [x] T046 新增 `Pagination.vue`：props `current-page` / `last-page` / `max-visible`（預設 10）/ `href`（可選函式）；未給 href 時渲染 `<button>` 並 emit `change`，給了則渲染 `<Link :href="href(page)">`。視窗演算法為當前頁置中、撞到頭尾往另一側補滿；第 1 頁與最後一頁恆常渲染，斷開處插入不可點的 `…`；`last-page <= 1` 回傳空。當前頁 `bg-brand-navy text-white`，其餘中性灰邊框 + `hover:bg-gray-50` + `cursor-pointer` in `resources/js/Components/Pagination.vue`
- [x] T047 [P] 文章列表 in `resources/js/Pages/Admin/Posts/Index.vue`（012）
- [x] T048 [P] 電子報列表 in `resources/js/Pages/Admin/Broadcasts/Index.vue`（012）
- [x] T049 [P] 作業列表（保留左側「第 N / M 頁，共 X 筆」文字，換掉頁碼與上下頁鈕）in `resources/js/Pages/Admin/Homework/Index.vue`（003）
- [x] T050 [P] 會員列表（保留左側「顯示第 X - Y 筆，共 Z 筆」，換掉右側 nav）in `resources/js/Pages/Admin/Members/Index.vue`（008）
- [x] T051 [P] 交易列表（同 T050 的保留方式）in `resources/js/Pages/Admin/Transactions/Index.vue`（009）
- [x] T052 [P] 折扣碼列表 in `resources/js/Pages/Admin/Coupons/Index.vue`（006）
- [x] T053 [P] 折扣碼鏈列表 in `resources/js/Pages/Admin/CouponChains/Index.vue`（006）
- [x] T054 [P] 預約名單 in `resources/js/Components/Admin/Leads/BookingListTab.vue`（011）
- [x] T055 [P] 訂閱者名單 in `resources/js/Components/Admin/Leads/SubscriberListTab.vue`（010）
- [x] T056 [P] 會員積分明細：改傳兩個數字，移除 `links` 迴圈與 `v-html`（D34）in `resources/js/Pages/Member/Points.vue`（007）
- [x] T057 前台 `/blog` 與 tag 頁改 href 模式（`pageHref(page)` 產 `?page=N`，第 1 頁不帶參數）；後端未動 —— 兩支查詢都沒有其他查詢字串要保留（FR-031 / D35）in `resources/js/Pages/Blog/Index.vue`（012）, `resources/js/Pages/Blog/Tag.vue`（012）
- [x] T058 `npm run build` exit 0；`php artisan test` 全綠
- [ ] T059 使用者實測：後台任一 > 10 頁的列表頁碼恰 10 個、首尾頁可直接點、停在第 1 頁與最後一頁時視窗仍是滿的；`/blog` 第 2 頁的頁碼是真連結（右鍵可在新分頁開啟）且篩選條件不掉
- `site_settings` 新鍵：`color_scheme`（US14）— 作用中的配色 key，例如 `cream-indigo`。未設定或指向 `config('themes.schemes')` 不存在的 key 時一律退回 `config('themes.default')`。可選值只存在於程式碼（`config/themes.php`），DB 端無約束也無 migration（見 D43）。


## Tasks（首次安裝的第一位管理員 / US13）

- [x] T0C1 `config/auth.php` 加 `first_admin_email`（`env('FIRST_ADMIN_EMAIL')`），`.env.example` 補上該鍵與留空行為的註解 in `config/auth.php` + `.env.example`
- [x] T0C2 `FirstAdminService::bootstrap(User $user): bool` — 前提判斷（無 admin + 相符 email 或唯一帳號）、升權、寫 support_email（僅在空值時）、`Log::info('first_admin.bootstrapped', …)` in `app/Services/FirstAdminService.php`
- [x] T0C3 `LoginController::verify()` 在建立新帳號後呼叫 bootstrap，並與建帳包在同一個 transaction in `app/Http/Controllers/Auth/LoginController.php`
- [x] T0C4 [P] `FirstAdminTest`：第一位升權且 support_email 被填；第二位不升；已有 admin 不升；`FIRST_ADMIN_EMAIL` 相符/不相符；電子報與結帳建的帳號不升；support_email 已有值不被覆蓋 in `tests/Feature/Platform/FirstAdminTest.php`
- [ ] T0C5 使用者實測：乾淨 DB 跑 `migrate --force`，用自己的 email 走一次 `/login`，確認進得去 `/admin` 且「Email 模板」頁的客服信箱已是該 email

## Tasks（全站配色方案切換 / US14）

**Phase 1 — 配色資產與服務**

- [x] T0D1 `config/themes.php`：`default` = `cream-indigo`，`schemes` 為八組 `key => ['name','industry','colors' => [cream, navy, teal, gold, gold_dark, orange, red]]`（色值見 US14 的配色表）in `config/themes.php`
- [x] T0D2 `ThemeService::all(): array` / `active(): array` — 讀 `site_settings.color_scheme`，未知或空值退回 `config('themes.default')`；回 `['key','name','industry','colors']`；**MUST NOT 加靜態快取**（FR-113 同一理由）in `app/Services/ThemeService.php`
- [x] T0D3 [P] `ColorSchemeTest` 先寫兩組斷言並確認會紅：八組結構完整且皆為合法 6 位 hex、九條對比門檻以 WCAG 相對亮度逐組算出（FR-121 的表）in `tests/Feature/Platform/ColorSchemeTest.php`

**Phase 2 — 注入與收編寫死的 hex**（相依 Phase 1）

- [x] T0D4 `app.blade.php` 在 `@vite(...)` **之後**輸出 `<style>:root{--color-brand-…}</style>`（順序即正確性，FR-123）in `resources/views/app.blade.php`
- [x] T0D5 `app.css`：`@theme` 的七個值換成修正後的 `cream-indigo`（teal `#33697F`、red `#D92B1F`、orange `#DF6807`）；`body` 底色改 `var(--color-brand-cream)`；`.section-dots` 改 `color-mix(in oklab, var(--color-brand-navy) 22%, transparent)`；`.course-content` / `.assignment-content` 的 23 處 hex 全改 `var()` in `resources/css/app.css`
- [x] T0D6 [P] 三支 Vue 的寫死 hex 改回 brand utility；`AssignmentSection.vue` 兩處 `#336d8a` 改 `hover:bg-brand-teal/85`（FR-122）in `resources/js/Components/Classroom/AssignmentSection.vue`, `resources/js/Components/Classroom/LessonPromoBlock.vue`, `resources/js/Pages/Course/Show.vue`
- [x] T0D7 `HandleInertiaRequests` 共享 `theme`（`key` + `colors`）；`LessonForm.vue` 的 CTA 產生器改讀 `page.props.theme.colors` 產出字面 hex（FR-122 的唯一豁免）in `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/Components/Admin/LessonForm.vue`
- [x] T0D8 `OgImageService` 的 navy/teal 改讀作用中配色，並把配色 key 放進快取 key（FR-124）in `app/Services/OgImageService.php`

**Phase 3 — 「首頁設定」頁的配色區塊**（相依 Phase 1）

- [x] T0D9 `HomepageSettingController::edit()` 多傳 `colorSchemes`（`ThemeService::all()`）與 `activeColorScheme`；新增 `updateColorScheme()`，`color_scheme` 以 `Rule::in(array_keys(config('themes.schemes')))` 驗證；路由 `POST /admin/homepage/color-scheme` 加在既有 homepage 群組內（權限由所在的 `admin` middleware 群組涵蓋，不另外判斷）in `app/Http/Controllers/Admin/HomepageSettingController.php`, `routes/web.php`
- [x] T0D10 `ColorSchemePicker.vue`：八張卡片（名稱、產業定位、七個色票、縮小版面示意）；點卡片即把七個變數寫上 `document.documentElement.style` 做即時預覽，「儲存」送 `POST /admin/homepage/color-scheme`、「取消」移除 inline 變數還原。卡片是非 button 的可點元素時 MUST 自行加 `cursor-pointer` in `resources/js/Components/Admin/ColorSchemePicker.vue`
- [x] T0D11 「首頁設定」頁掛上該元件，自成一張 `<section>` 卡片，沿用該頁既有的卡片樣式；插在**「站台資訊」與「Hero 主視覺」之間**（全站識別在上、首頁內容在下，D47）in `resources/js/Pages/Admin/HomepageSettings/Edit.vue`

**Phase 4 — 把關與驗收**

- [x] T0D12 [P] `ColorSchemeTest` 其餘斷言：預設與未設定皆為 `cream-indigo`、切換後首頁 HTML 含新 hex 且不含舊 `#3F83A3`、`<style>` 出現在 vite CSS link 之後、非法 key 被擋、DB 存未知 key 時退回預設而非 500、訪客與銷售顧問不得打 `POST /admin/homepage/color-scheme`、儲存配色不動到同頁其他五個區塊的設定值、原始碼掃描 `resources/css` 與 `resources/js` 無漏網 brand hex（豁免 `LessonForm.vue`）in `tests/Feature/Platform/ColorSchemeTest.php`
- [x] T0D13 `npm run build` exit 0；`php artisan test` 全綠
- [ ] T0D14 使用者實測：在「首頁設定」頁逐組切過一輪，每組都看首頁、課程銷售頁、教室內文與後台側欄；確認主按鈕、gold CTA、售價、分期價、課程內文的標題橫幅與引用區塊都跟著換；最後換一次配色並重新分享一則文章連結，確認 OG 卡片是新配色（驗 FR-124）


## 進度日誌

- 2026-09-25: US14 全站配色方案切換完成（T0D1–T0D13，僅剩 T0D14 使用者實測）— 八組配色進 `config/themes.php`、`ThemeService` 解析作用中配色、`app.blade.php` 在 `@vite` **之後**注入 `:root` 覆寫，前後台同時換色且不必 rebuild。切換介面照審核時的決定做成「首頁設定」頁的第二張卡片（站台資訊之後、Hero 主視覺之前），選擇器抽成 `ColorSchemePicker.vue` 歸 000、頁面留在 002。
  TDD：先寫 `ColorSchemeTest` 15 條跑出 **11 紅 4 綠** —— 綠的四條是純資料（九條對比門檻、淺底、gold-dark 暗於 gold、預設存在），確認色票本身站得住；紅的十一條全部紅在「功能不存在」。
  **三個實作中才浮出來的東西**：(1) 用正規表示式把 brand hex 換成 `var()` 時連 `@theme` 區塊自己也換掉了，產出 `--color-brand-cream: var(--color-brand-cream)` 這種循環定義 —— 定義處必須留字面值，測試的掃描因此要先剝掉 `@theme` 區塊再驗。(2) 我的測試原本斷言顧問存取回 403，實際上 `AdminMiddleware` 是導回 `/` 帶 flash（`SalesConsultantTest` 早就釘住這個慣例），改的是測試不是程式。(3) **hex-only 的掃描不夠**：`AssignmentSection` 與 `RoadmapBoard` 的 teal、`FeaturedCourses` 與 `RoadmapBoard` 的 gold 共五處寫成 `rgba(63,131,163,…)` / `rgba(240,193,75,…)`，十進位形式完全躲過第一版把關。把掃描擴充到 `rgb()/rgba()` 後當場又抓到兩處，FR-122 也跟著改寫 —— 這正是「只被一半程式碼尊重的設定比沒有設定更糟」的實例。
  現行配色依使用者決策修到通過 AA：`teal #3F83A3 → #33697F`、`red #FF4438 → #D92B1F`、`orange #FAA45E → #DF6807`（第三項是使用者回答之後才量到的第四個未達標，1.77:1）。連帶更新 `OgImageTest` 的前提（配色 key 進了 OG 卡片快取 key，該測試自行推導檔名）。
  全套 **1026 passed（4490 assertions）**、`npm run build` exit 0。註記：`php artisan test` 會以預設 128M 記憶體上限中途 fatal —— 測試套件本身峰值 128.50 MB，本次新增 16 條把它推過線，改用 `php -d memory_limit=2G vendor/bin/phpunit` 才跑得完，屬環境餘裕問題非測試失敗。

- 2026-09-25: [draft] 規劃 US14 全站配色方案切換 — 起點是把現行配色抽出來當成「一組方案」，再設計七組給業主選。抽取時量了對比才發現現行這組有四項未達 WCAG AA，最糟的是分期價的橘字在米色底上只有 **1.77:1**（`#FAA45E` on `#F6F1E9`）——那是實際上讀不了的東西，而它已經在線上一年多。使用者決定連現行配色一併修到過關（`teal #3F83A3 → #33697F`、`red #FF4438 → #D92B1F`、`orange #FAA45E → #DF6807`），所以這次不只是「加能力」，預設外觀也會變。
  實作路徑比預期便宜很多：全站 90 個檔案、1,099 處 utility 全部引用那七個 `--color-brand-*` token，而 Tailwind v4 已經把它們編成 `var(--color-brand-*)`（查了 `public/build/assets/*.css` 確認，不是照文件推論）——**換色的能力早就在編譯結果裡**，只要在執行期覆寫七行變數就行，不必 rebuild、不必動那 90 個檔案（D41）。注入點必須是 blade 的 `<head>` 且在 `@vite` 之後：走 Inertia prop 會先閃一次預設配色、爬蟲也拿不到（D42）；放在 `@vite` 前面則因為 `:root` 與 `:root,:host` 特異性相同而完全不生效，症狀看起來會像「儲存沒成功」（FR-123）。
  三個劃界的決定：八組色票進 `config/themes.php` 而 DB 只存選了哪一組（D43——「有哪些組」是設計成果屬程式碼，「選了哪一組」才是使用者資料）；token 維持 `teal`/`navy` 這種色相名不重構成 `primary`/`ink`（D44——改名要動 1,099 處，漏一處的症狀是那個元素無聲地留在舊配色）；八組全為淺底、深色模式明確排除（D45——`navy` 同時是內文色與後台側欄底色配白字，做深色等於把兩個角色對調，那是另一條故事）。
  **審核時使用者決定不另開後台分頁**，配色選擇器改塞進既有的「首頁設定」頁（D47）—— 那一頁已經是全站識別的維護處（站名、經營者、logo、favicon、Hero 主視覺都在那裡），配色是同一件事剩下的最後一塊，拆兩頁等於要業主記住「文字在這裡、顏色在那裡」；側欄也不必再長第 17 項。選擇器抽成 `Components/Admin/ColorSchemePicker.vue` 歸 000，頁面留在 002（`Edit.vue` 已經 877 行六個區塊，八張卡片攤進去會破千行）。
  對比定為**收錄條件**而非建議，由 `ColorSchemeTest` 以 WCAG 相對亮度算出九條門檻（D46/FR-121）；八組全部通過，數字列在 US14 裡。另外把 `app.css` 的 23 處與四支 Vue 的 14 處寫死 hex 一併收編（FR-122），唯一豁免是 `LessonForm.vue` 產生的 CTA——那段 inline style 會被 drip 信件原樣寄出，email client 不解析 CSS 變數。status: draft 待審核。

- 2026-09-25: US13 首次安裝的第一位管理員完成（T0C1–T0C4，僅剩 T0C5 使用者實測）— `FirstAdminService::bootstrap()` 是唯一判斷點，由 `LoginController::verify()` 在建帳後於**同一個 transaction** 內呼叫。判斷式照 FR-115 的兩半：先確認沒有任何 `role = admin`，再看 `FIRST_ADMIN_EMAIL` 是否指名（相符才升，比對前 trim + 轉小寫）或這是 `users` 表唯一的一列。升權時一併把 email 寫進 `support_email`（**僅在空值時**）並寫 `first_admin.bootstrapped` info log。`config/auth.php` 新增 `first_admin_email`、`.env.example` 補上該鍵與留空行為說明。
  TDD：先寫 `FirstAdminTest` 7 條，跑出 **5 紅**（紅在「期望 admin、實際 member」，確認是功能不存在而不是測試寫錯），實作後全綠。其中兩條是反向守門——電子報訂閱建的第一個帳號不得升權（`NewsletterService` 路徑）、已設定的 `support_email` 不得被覆蓋。全套 **1010 passed（4341 assertions）**。

- 2026-09-25: US12 追加網站圖示與 favicon 上傳 — 導覽列的 logo 原本是 `import` 進 JS bundle 的檔案、favicon 則根本不存在（`public/favicon.ico` 是 Laravel 預設的 0 bytes 空檔），兩者都不是第二個安裝換得掉的東西。改成後台上傳，存 public disk。
  **favicon 只要上傳一張 PNG**：`SiteIconService` 用 GD 縮出 32×32 與 180×180，再把 32×32 那張包成 `.ico`。這台機器沒有 Imagick、GD 也不會寫 ICO，但 ICO 容器本來就允許整包一張 PNG（Vista 之後通用），所以檔案就是 6 bytes 檔頭 + 16 bytes 目錄項 + PNG 本體，二十行搞定、不必加套件；測試逐欄位驗那 22 bytes，不是只看副檔名。兩個實作中的判斷：(1) 縮圖前一定要先關 alphablending、開 savealpha 並填透明底，否則 GD 把 alpha 壓在黑底上，淺色分頁看到的是一塊黑；(2) `public/favicon.ico` 必須從 repo 刪掉，nginx 的 `try_files` 會先命中那個空檔，留著等於路由永遠不會被呼叫到。
  另外把 OG 卡片的 logo 也接上上傳的圖，並把 logo 檔名放進快取 key —— 否則換了 logo，舊卡片會帶著上一個品牌的 mark 留在 disk 上。本機實測：上傳後導覽列換圖、`<head>` 三個 icon link 正確、`/favicon.ico` 回 200 `image/x-icon`，刪除後回 404。新增 `SiteIconTest` 9 tests，全套 **1003 passed（4313 assertions）**、`npm run build` exit 0。

- 2026-09-25: `html, body { overscroll-behavior: none }`（`resources/css/app.css`）— 業主回報前後台頂端出現一條米色橫條。查證後確認不是版面問題：正式站伺服器送出的 HTML 裡 `<body>` 底下只有 `#app`（沒有多餘元素、沒有文字節點、`<style>` 數為 0），瀏覽器實際渲染時 `nav` 與 `#app` 的 `top` 都是 0。那個顏色是 `body` 的底色 `#F6F1E9`，只有在捲動回彈把頁面推離視窗上緣時，瀏覽器才會用它畫外露的畫布。關掉回彈就沒有露出的機會。**症狀在部署完成後自行消失，無法證明是這條規則修好的**（也可能只是業主瀏覽器還拿著部署前的快取）；規則本身無害且合理，所以留著。副作用：手機瀏覽器的下拉重新整理會失效。

- 2026-09-25: 拿掉後台桌機版的置頂橫條（000 US2）— 業主回報「後台的 header 又多出來了」，實際看是 `AdminLayout` 自己的 `sticky top-0` white bar：桌機上它只裝了一顆登出，內容從它下面穿過去，每一頁都一樣。先排除了舊的懷疑方向（教室那次是頁面沒宣告 layout、吃到預設 `AppLayout` 的導覽列疊成兩層）—— 所有後台頁都有 `defineOptions({ layout: AdminLayout })`，沒有第二層，問題出在 layout 自己。桌機版整條移除，手機保留一條 `lg:hidden` 的漢堡列（抽屜沒有別的開法），登出移進側欄底部並在手機抽屜補一份；側欄改成只有連結清單捲動，底部那排在矮視窗不會被推出畫面。
  **這次補了防回歸的把關**，因為這條橫條已經回來過一次：`AdminLayoutChromeTest` 掃 `AdminLayout.vue` 的原始碼，任何 `sticky top-0` 元素沒帶 `lg:hidden` 就紅，`/logout` 少於兩份也紅（少一份代表某個斷點根本登不出去）。故意把 `lg:hidden` 拿掉跑過一次確認它會失敗。同一支保險也補給 US12 的品牌字串（`SiteIdentityTest::test_no_source_file_hardcodes_the_brand`）。本機瀏覽器實測：桌機 `/admin/homepage` 捲動無白帶、側欄底部登出恆在；DOM 檢查確認手機漢堡列在桌機為 `display:none`。

- 2026-09-25: 新增 US12 站台資訊（站名 / 經營者 / 地址）— 起因是要把同一份程式碼開在 Forge 同機的第二個站給客戶用，盤點後真正擋路的不是架構而是散在十幾個檔案裡的字面字串：「經營者時間銀行」寫死在導航列、頁尾、頁面標題、OG 卡片與八支信件模板，公司名與登記地址寫死在服務條款與購買須知的結尾。三個鍵進 `site_settings`（沿用 US5 機制，不開新表），`SiteSetting::identity()` 一次讀出、`HandleInertiaRequests` 以 `site` prop 全域共享（比照 `supportEmail` 的理由：條款彈窗掛在 footer，逐頁傳 prop 一定會漏）。信件端新增全域變數 `{{site_name}}`，seeder 與自帶模板本文的 migration 的署名一併改掉，乾淨 DB 不再帶入本站品牌。
  **兩個實作中才浮出來的判斷**：(1) 安裝 migration 的難處是它要同時滿足兩個相反的需求 —— 既有站升級後條款頁不能無聲少掉法定資訊，但乾淨 DB 又絕不能被寫進本站的公司名。折衷是以「`site_settings` 已有 `hero_title` 列」當既有安裝的判準，只有既有站補值。(2) `@php($siteName = \App\Models\SiteSetting::siteName())` 這種行內形式讓 `app.blade.php` 編譯壞掉，而症狀是**同檔後面**的 `$pixelId` undefined，五個 admin 測試同時轉紅，看起來完全不像站名造成的；改 `@php … @endphp` 區塊形式即正常，已寫成 FR-114。
  另修正兩處既有測試的前提：`OgImageTest` 把品牌字串寫死在快取 key 的斷言裡（站名進了 key，改名才會重生舊卡片），`EmailBrandNameTest` 釘的是 012 FR-013 的舊規則（站名來源由 `hero_title` 改為 `site_name`，`hero_title` 降為 fallback）。新增 `SiteIdentityTest` 6 tests，全套 **991 passed（4257 assertions）**、`npm run build` exit 0、本機 MySQL `migrate` DONE。

- 2026-09-25: 新增 US11 全站時區約定 — 釘死 DB 連線時區 `+00:00`（原為 `SYSTEM`，開發機 Asia/Taipei、正式站 UTC，`useCurrent()` 欄位在兩邊差 8 小時）、新增 `NormalizesTaipeiInput` trait 供 004/006/012 的 FormRequest 做入口轉換、`AdminDateInputTimezoneTest` 覆蓋「台北已過去但 UTC 看似未來」邊界。經評估否決「主機改 Asia/Taipei」方案（見 D36）。CLAUDE.md 新增 `## Timezone` 段。985 passed

- 2026-09-08: 全站分頁收斂成單一元件（T046–T058 完成，僅剩 T059 使用者實測，FR-030 / FR-031）— `Components/Pagination.vue` 上線，**12 個畫面**全數改用它（原規劃 11 個，實作時發現 `/blog` 與 tag 頁是兩個各自的檔案）。視窗演算法先用一支獨立腳本把 11 組 `(current, last)` 跑過一遍才接呼叫端：`cur=1 last=32`、`cur=32 last=32`、`cur=6 last=11` 這幾組正是「置中」寫法最容易只吐半排頁碼的地方，全部確認恰好 10 個頁碼位置、首尾恆在、`last<=10` 不出現 `…`。
  三個實作上的意外：作業列表把換頁寫在 template 的 `@click` 裡（三處各一份 `router.get`），抽成 `goToSubmissionsPage()` 才有得傳給元件；`Blog/Tag.vue` 的 `defineProps` 原本沒接成 `props`，href 函式要讀 `tag.slug` 才補上；`Blog/Index.vue` 的 `Link` 在換掉分頁後成了沒人用的 import，一併移除。積分頁的 `v-html` 如期消失（D34）。後端一行沒動 —— 兩支 blog 查詢都沒有其他查詢字串要保留，`pageHref` 直接產 `?page=N` 即可。`npm run build` exit 0、全站 `php artisan test` **873 passed（3656 assertions）**。

- 2026-09-08: [draft] 規劃全站分頁元件（FR-030 / FR-031 / D32–D35）— 起點是 `/admin/posts`：每頁 50 筆的分頁一直都在，缺的是頁碼列 `v-for="p in posts.last_page"` **沒有上限**。盤點後發現分頁在這個專案裡有**三種寫法、十個副本**：三份無上限頁碼（文章／電子報／作業）、六份上一頁下一頁（會員／交易／折扣碼／折扣碼鏈／預約名單／訂閱者名單）、一份直接渲染 Laravel `links`（會員積分），前台 `/blog` 則連頁碼都沒有。使用者決定全部收斂成同一個元件。
  元件只吃兩個數字、不碰 router（D32）：呼叫端換頁方式各不相同（帶篩選的 `router.get`、`preserveState`、`preserveScroll`），把導覽塞進元件會逼出一個誰都不合身的介面。視窗要「置中 + 撞到頭尾往另一側補滿」而不是單純 `slice`，否則停在第 1 頁時頁碼只剩半排。放 `Components/Pagination.vue` 而非 `Components/Admin/`（D33），因為積分頁與 `/blog` 都在範圍內。
  一個不能省的細節寫成 FR-031：前台 `/blog` MUST 用 href 模式渲染真的 `<Link>`。那裡的分頁是爬蟲跟得到的連結，換成只有 click handler 的按鈕，等於把第 2 頁以後的文章從索引裡拿掉 —— 而這件事在畫面上完全看不出來。積分頁順手拿掉為了顯示 `«` 而開的 `v-html`（D34）。無 schema 變更，後端最多動 `BlogController` 的 `withQueryString()`。status: draft 待審核。

- 2026-08-16: 修 `extractText()` 讀錯位置（FR-029）— 正式站第一次實跑面談摘要，逐字稿存進去了但摘要是空的，且沒有任何錯誤。直接打 API 看回應才發現：請求 `status=completed`、`output_tokens=732`、`message` 裡有 752 字的摘要 —— **東西一直都在，是我們讀錯地方**。`output_text` 在這個回應裡是空的，而備援路徑寫死 `output.0.content.0.text`，但推理模型的 `output[0]` 是空的 `reasoning` 項、`message` 在 `output[1]`。改成依 `type` 走訪找 `message` 並串接其所有文字段。
  連帶的災情比摘要更大：校訂那步用同一支 `respond()`，每個分段都拿到 null 後**靜靜退回機械式原文**，所以那份逐字稿從頭到尾沒被校訂過，未能機械對應的講者標籤也沒被語境判斷補上（實測留下的是「投好壯壯The Must Big」與「Zoom使用者」—— 都不是真實人名，FR-109 未被違反，但也不是該有的「顧問／客戶」）。根因是「未設定」與「解析失敗」共用同一個靜默的 null；現在後者會寫 warning 並記下 `output` 各項的 type。704 passed。

- 2026-08-16: US10 AI 設定與 Prompt 管理完成（T0A1–T0A9 全數）— `ai_prompts` 表 + `AiPrompt` model + `OpenAiService::respond()` 全站唯一呼叫點 + `/admin/settings/ai` 分頁（依 feature 分組、`v-for` 渲染，新增 AI 功能不必改 Vue）。模型解析鏈 `ai_prompts.model` → `site_settings.openai_default_model` → `config('ai.default_model')`，兩個 prompt 各自指定模型的行為由測試釘住（校訂用 luna、摘要可單獨升級）。憑證沿用 D3 的遮罩＋留白不覆蓋。AiSettingsTest 16 passed；模組 status 轉回 `done`（US10 已完結，既有的 T001 Error.vue 仍未做，與本次無關）。
- 2026-08-16: [draft] 規劃 US10 AI 設定與 Prompt 管理 — 011 US23 的面談摘要是本站第一個 AI 功能，趁這次把「用哪家、用哪個模型、每個用途的 instructions」抽成全站基礎設施，第二個 AI 功能上線時只要插一列 + 呼叫一支 service。三個關鍵決策：prompt 存獨立的 `ai_prompts` 表而非散成 site_settings 的鍵（D29 —— prompt 是同構的一組，同構的東西該用列；這也是使用者「方便以後擴展」要求的直接後果），每個 prompt 各自可指定模型（D30，使用者決策 —— 校訂是機械活、摘要要判斷力，同一個模型不是浪費就是將就），獨立分頁而非塞進金流的 API 設定頁（D31，admin-only，因為每次儲存都影響全站行為與費用）。`OpenAiService::respond()` 為全站唯一呼叫點，憑證未設定回 null 不丟例外（FR-029，沿用 011 D40 對 Zoom 的立場）。模組 status 由 `done` 轉 `draft` 待審。
- 2026-08-06: 短網址管理畫面併入行銷分析頁成為分頁（正典在 002 US15），本模組這側的變動：`ShortLinks/Index.vue` 搬成 `Components/Admin/Analytics/ShortLinkTab.vue`、列表組裝上移為 `ShortLink::adminListing()`（畫面歸 002 的頁面管，資料形狀留在擁有這張表的模組，兩邊才不會各自漂移）、`ShortLinkController::index` 改為轉址而非刪除（這個路徑進過側欄也進過書籤，404 讀起來像「功能被拿掉了」）。側欄同時重排：移除短網址、Leads 名單與諮詢時段移到行銷分析之前、Email 模板移到 API 設定之前 —— 依接站時的實際使用頻率排，而非依功能加入的先後。US8 驗收條款已就地更新為新路徑。
- 2026-08-06: API 設定頁的 Resend 區塊補完整設定說明，並把說明框改成可收合元件。查 Resend 官方文件時發現一條沒人會自己想到的依賴：驗證網域要加的那筆 `MX` 記錄，正是收件方回報退信與投訴的通道 —— 少了它 US9 的 webhook 永遠不會被觸發，而且封鎖名單只會安靜地保持空的，不會有任何錯誤。補成 FR-026。說明同時涵蓋三項仍在 `.env` 的設定（`RESEND_API_KEY` 只顯示一次、`MAIL_FROM_ADDRESS` 必須落在已驗證網域否則全站寄不出信、`MAIL_FROM_NAME`），因為量販給客戶時這些是對方自己要設的。四個說明框（Resend ×3、Zoom ×1）抽成 `HintBox.vue`：字級 `text-xs` → `text-sm`、預設收合 —— 這些是接站當天讀一次就不再看的內容，攤開只會把真正要填的欄位擠到摺線以下。觸發用 `<button type="button">`，框在 `<form>` 裡，不寫死 type 每點一次展開就送出整張表單。`npm run build` 綠、EmailSuppression/ShortLink 32 tests passed；未在瀏覽器實測（後台需 Email 驗證碼登入）。
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

---
id: 012-newsletter
status: building
owner_files:
  # Models
  - app/Models/Post.php
  - app/Models/Tag.php
  - app/Models/PostImage.php
  - app/Models/Broadcast.php
  - app/Models/NewsletterEmailEvent.php
  # Controllers
  - app/Http/Controllers/BlogController.php
  - app/Http/Controllers/BlogFeedController.php
  - app/Http/Controllers/NewsletterSubscriptionController.php
  - app/Http/Controllers/NewsletterTrackingController.php
  - app/Http/Controllers/Admin/PostController.php
  - app/Http/Controllers/Admin/PostImageController.php
  - app/Http/Controllers/Admin/BroadcastController.php
  # Requests
  - app/Http/Requests/Admin/StorePostRequest.php
  - app/Http/Requests/Admin/UpdatePostRequest.php
  - app/Http/Requests/Admin/SendBroadcastRequest.php
  - app/Http/Requests/StoreNewsletterSubscriptionRequest.php
  # Services
  - app/Services/PostService.php
  - app/Services/NewsletterService.php
  - app/Services/BroadcastService.php
  # Jobs / Mail / Console
  - app/Jobs/SendBroadcastEmailJob.php
  - app/Mail/NewsletterBroadcastMail.php
  - app/Mail/NewsletterWelcomeMail.php
  - app/Console/Commands/PublishScheduledPosts.php
  - app/Console/Commands/CleanDormantSubscribers.php
  # Email views
  - resources/views/emails/newsletter-broadcast.blade.php
  - resources/views/emails/newsletter-welcome.blade.php
  # Frontend pages
  - resources/js/Pages/Blog/Index.vue
  - resources/js/Pages/Blog/Show.vue
  - resources/js/Pages/Blog/Tag.vue
  - resources/js/Pages/Newsletter/Unsubscribe.vue
  - resources/js/Pages/Admin/Posts/Index.vue
  - resources/js/Pages/Admin/Posts/Create.vue
  - resources/js/Pages/Admin/Posts/Edit.vue
  - resources/js/Pages/Admin/Broadcasts/Index.vue
  - resources/js/Pages/Admin/Broadcasts/Show.vue
  # Frontend components
  - resources/js/Components/Admin/PostForm.vue
  - resources/js/Components/Newsletter/PostCard.vue
  - resources/js/Components/Newsletter/ShareButtons.vue
  - resources/js/Components/Newsletter/SubscribeForm.vue
  # Migrations
  - database/migrations/2026_07_10_000001_create_posts_table.php
  - database/migrations/2026_07_10_000002_create_tags_table.php
  - database/migrations/2026_07_10_000003_create_post_tag_table.php
  - database/migrations/2026_07_10_000004_create_post_images_table.php
  - database/migrations/2026_07_10_000005_create_broadcasts_table.php
  - database/migrations/2026_07_10_000006_create_newsletter_email_events_table.php
  - database/migrations/2026_07_10_000007_add_newsletter_fields_to_users_table.php
touchpoints:
  - file: app/Models/User.php
    owner: 001-auth-account
    why: newsletter_status / newsletter_subscribed_at / newsletter_unsubscribe_token / newsletter_last_opened_at 欄位 casts、broadcasts 開信關聯、scopeNewsletterSubscribed
  - file: app/Services/VerificationCodeService.php
    owner: 001-auth-account
    why: 訂閱走 OTP 兩步驗證，Step1 generate 驗證碼（沿用既有 rate limit）、Step2 validate
  - file: app/Mail/VerificationCodeMail.php
    owner: 001-auth-account
    why: 訂閱 Step1 寄送驗證碼信（沿用既有 Mailable）
  - file: resources/js/Components/VerificationCodeInput.vue
    owner: 001-auth-account
    why: 訂閱表單 Step2 沿用 6 碼輸入元件
  - file: app/Http/Controllers/HomeController.php
    owner: 002-storefront
    why: 首頁 'blog' widget 資料來源由 BlogRssService 改為原生 Post（精選優先、最新次之）；Substack 改留在 SNS 區
  - file: resources/js/Pages/Home.vue
    owner: 002-storefront
    why: 'blog' widget 改渲染原生 PostCard + 訂閱框；ShareButtons 用於精選文章
  - file: resources/js/Components/BlogArticles.vue
    owner: 002-storefront
    why: 由 RSS 文章形狀改為原生 Post 形狀（title/excerpt/url/cover/published_at）
  - file: app/Http/Controllers/SitemapController.php
    owner: 000-platform-core
    why: sitemap 納入 published posts 與 tag 封存頁
  - file: resources/views/sitemap.blade.php
    owner: 000-platform-core
    why: 迴圈輸出 posts 與 tags 的 <url>
  - file: resources/views/app.blade.php
    owner: 000-platform-core
    why: og 擴充 article:published_time 與 BlogPosting JSON-LD slot（沿用既有 $og 機制）
  - file: resources/js/Layouts/AdminLayout.vue
    owner: 000-platform-core
    why: 側欄新增「文章」「電子報」選單項目
  - file: routes/web.php
    owner: 000-platform-core
    why: 註冊 /blog、/blog/{slug}、/blog/tag/{slug}、/blog/feed、訂閱/退訂/開信追蹤、admin posts/broadcasts 路由
  - file: routes/console.php
    owner: 000-platform-core
    why: 排程 posts:publish-scheduled（每分鐘）與 newsletter:clean-dormant（每月 1 號）
---

# Newsletter（極簡電子報 / mini-blog）

## 目標

在現有課程網站上長出一個「極簡 Ghost」：管理員用 Markdown 發佈輕量教學文章（Prompt 介紹、免費短片），前台以 SEO 最佳化的部落格頁呈現作為引流與索引資產，喜歡的人一鍵 email 訂閱即成為會員，管理員可把任一文章「寄成電子報」給訂閱者並追蹤開信率，月排程清理逾兩月未開信者以省發送額度但保留會員身分。

## User Stories

### User Story 1 - 後台文章 CRUD 與 Markdown 編輯 (Priority: P1)

管理員在 `/admin/posts` 新增/編輯/軟刪除文章：Markdown 內文（左寫右預覽，沿用課程描述那套）、封面圖、tags、SEO slug / seo_title / meta_description / og image、精選開關、可選綁定引流課程、draft/scheduled/published 狀態。

**驗收**：
- [ ] 文章列表分頁 + 狀態篩選（draft/scheduled/published）+ 關鍵字搜尋（title/slug）
- [ ] PostForm：Markdown textarea + 現有圖片庫 Modal 多選插入、貼上 YouTube 連結存原文（前台才 render embed）
- [ ] slug 必填、手動輸入英文 SEO 網址（`^[a-z0-9\-]+$`）、unique；與 course slug 不同命名空間（前台前綴 `/blog/`）故不互撞
- [ ] tags 以逗號/多選輸入，firstOrCreate Tag 並同步 pivot
- [ ] status=scheduled 需填未來 published_at；published 立即上線；draft 不出現在任何前台
- [ ] 有付費/已寄送 broadcast 的文章仍可編輯內容；刪除為軟刪除，前台立即 404
- [ ] 內文渲染由 `PostService::toHtml(body_md)` 於前台 request 時處理（CommonMark，strip `<script>/<style>`；YouTube/Vimeo 獨立行連結經 VideoEmbedService 轉 responsive iframe），不存 body_html 快取欄（比照 EmailTemplate accessor）

### User Story 2 - 文章前台頁與 SEO (Priority: P1)

訪客在 `/blog` 看文章列表、`/blog/{slug}` 看單篇、`/blog/tag/{slug}` 看標籤封存；單篇頁輸出完整 SEO（canonical、OG、Twitter card、BlogPosting JSON-LD），文章加入 sitemap，並提供原生 RSS feed。

**驗收**：
- [ ] `/blog` 列出 published 文章（分頁、封面+標題+摘要+日期），依 published_at desc
- [ ] `/blog/{slug}` render `PostService::toHtml`（v-html 吃 server-render HTML）、封面、tags、published_at、YouTube embed；底部顯示同 tag 相關文章（≤4，內部連結）
- [ ] `view()->share('og', …)` 輸出 type=article、og image（og_image ?: cover ?: 站台預設）、meta_description、canonical=`/blog/{slug}`
- [ ] app.blade.php 追加 `article:published_time` 與 BlogPosting JSON-LD（headline/datePublished/image/author）
- [ ] `/blog/tag/{slug}` 列出該 tag 的 published 文章；tag 不存在或無文章顯示空狀態（非錯誤頁）
- [ ] `/blog/feed` 輸出 RSS 2.0（最新 20 篇，title/link/description=excerpt/pubDate），`Content-Type: application/rss+xml`
- [ ] SitemapController 納入 published posts（lastmod=updated_at）與有文章的 tag 頁
- [ ] 綁定引流課程時，文章底部顯示課程 CTA 卡片，連結帶 `utm_source=blog&utm_medium=post&utm_campaign={slug}`（沿用 002.us-8 來源追蹤）

### User Story 3 - 首頁精選文章與原生分享 (Priority: P1)

首頁 'blog' widget 由外部 Substack RSS 改吃原生文章（精選優先、其餘最新），Substack 保留在 SNS 區；每篇文章頁與精選卡片提供原生分享按鈕。

**驗收**：
- [ ] HomeController 'blog' widget 改由 Post 提供（is_featured 優先、補最新，共 ≤5 篇），移除該格對 BlogRssService 的依賴
- [ ] BlogArticles.vue 改吃原生 post 形狀（title/excerpt/url=`/blog/{slug}`/cover/published_at）
- [ ] ShareButtons：X / Threads / Facebook / LINE / 複製連結，行動裝置優先用 Web Share API；純 share-intent URL，不載入第三方 JS
- [ ] Substack 仍可經 SNS 區 BLOG 連結到達（沿用 002.us-4，不移除）

### User Story 4 - Email 訂閱即成為會員 (Priority: P1)

訪客在首頁/文章頁訂閱框輸入 email → 收 6 碼驗證碼 → 驗證通過才建立會員（沿用全站 OTP 流程，杜絕 subscribe-bombing）、設 newsletter_status=subscribed、寄歡迎信；退訂連結保留會員身分。

**驗收**：
- [ ] SubscribeForm 兩步：Step1 輸入 email → `VerificationCodeService::generate` + 寄 `VerificationCodeMail`；Step2 沿用 `VerificationCodeInput` 輸入碼 → `validate`
- [ ] 驗證通過：新 email 建 member（email_verified_at=now()、無密碼，比照 `DripSubscriptionController::verify`）並 `Auth::login`；既有帳號直接沿用
- [ ] 驗證通過後設 newsletter_status=subscribed、寫 newsletter_subscribed_at 與 unsubscribe_token(UUID)；既有已 subscribed 顯示「你已在訂閱清單中」
- [ ] 既有 user 若 status ∈ {none, unsubscribed, dormant} → 驗證後改為 subscribed 並補 token
- [ ] 既有會員預設 newsletter_status=none（不自動視為訂閱者，需明確訂閱才收信）
- [ ] 寄 NewsletterWelcomeMail（極簡模板 + 退訂連結 + List-Unsubscribe header），失敗僅 log 不影響訂閱結果
- [ ] `/newsletter/unsubscribe/{token}` 確認頁 → status=unsubscribed；帳號與會員身分、已購課程完全保留
- [ ] Step1 送碼沿用 VerificationCodeService 既有 IP/email rate limit（防濫用）

### User Story 5 - 把文章寄成電子報（Broadcast） (Priority: P1)

管理員在文章或 `/admin/broadcasts` 按「寄成電子報」→ 建立 Broadcast（快照 subject + post_id）、對所有 subscribed 會員逐封佇列發送極簡防垃圾信；發佈上網與寄信完全解耦。

**驗收**：
- [ ] 只有 published 文章可寄；每次寄信建立一筆 Broadcast（status: draft→sending→sent）
- [ ] 收件對象 = User::newsletterSubscribed()（status=subscribed，排除 unsubscribed/dormant）；recipients_count 建立時快照
- [ ] BroadcastService 為每位收件者 dispatch 一個 SendBroadcastEmailJob（可帶每人 tracking pixel 與一鍵退訂 token）
- [ ] 極簡信模板：標題 + 摘要/前段 + 「在網站上閱讀全文」按鈕（連 `/blog/{slug}`）；YouTube 以縮圖圖片連回文章（信內不 iframe）
- [ ] 信件含 text/plain 備援、`List-Unsubscribe` + `List-Unsubscribe-Post`（RFC 8058 一鍵退訂）header、tracking pixel
- [ ] Job 發送前檢查收件者仍為 subscribed（退訂者不寄）；失敗重試 3 次（backoff 60/300/900）
- [ ] 全部寄完更新 sent_count 與 status=sent、sent_at；重寄同一文章允許（建立新 Broadcast，不覆蓋歷史）

### User Story 6 - 開信追蹤與後台成效 (Priority: P2)

每封 broadcast 信嵌 signed tracking pixel 記錄開信；後台 broadcast 列表與詳情顯示寄送數、開信數/率。

**驗收**：
- [ ] pixel 為 signed URL（180 天效期），驗簽失敗仍回 1×1 GIF 不報錯
- [ ] 開信寫 newsletter_email_events（broadcast_id, user_id, event_type=opened），(broadcast_id, user_id, event_type) DB unique 去重；firstOrCreate 失敗僅 log
- [ ] 開信事件同時更新 user.newsletter_last_opened_at（供 dormant 判定與自動復活）
- [ ] Broadcasts/Index：每筆顯示文章標題、寄送時間、recipients / opened / 開信率
- [ ] Broadcasts/Show：開信率、（已知的 Apple MPP 灌水以註記說明）、收件者開信明細分頁
- [ ] 開信率分母為 recipients_count；為 0 顯示「—」

### User Story 7 - 月排程清理沉睡訂閱者 (Priority: P2)

每月排程把「近 60 天曾被寄出 ≥1 封 broadcast 且全部未開信」的訂閱者轉 dormant（停止收信、省額度），但保留會員身分；日後任何開信自動復活為 subscribed。

**驗收**：
- [ ] `newsletter:clean-dormant` 每月 1 號排程（routes/console.php）
- [ ] 判定：newsletter_status=subscribed 且 近 60 天內 recipients 涵蓋過此人的 broadcast ≥1、且此人於近 60 天無任何 opened 事件 → 轉 dormant、寫 status_changed_at
- [ ] 從未被寄過 broadcast 的新訂閱者一律豁免（不因「沒開信」被誤殺）
- [ ] dormant 使用者：不進 broadcast 收件清單、role 與會員身分/已購課程/積分完全不動
- [ ] 自動復活：US6 開信事件寫入時若 user.newsletter_status=dormant → 改回 subscribed
- [ ] `posts:publish-scheduled` 每分鐘排程：status=scheduled 且 published_at 到期者轉 published（沿用 courses:update-status 模式）

### User Story 8 - 文章瀏覽次數 (Priority: P3)

前台文章頁被瀏覽時累計該篇 view_count；後台文章列表顯示各篇瀏覽數，供內容成效參考與（未來）熱門排序。

**驗收**：
- [ ] `/blog/{slug}` 對 published 文章瀏覽時原子 `increment('view_count')`
- [ ] 同一 session 對同一篇只計一次（session key `viewed_post_{id}`，防重整/來回灌數）
- [ ] admin 預覽、draft/scheduled、bot（可選 user-agent 略過）不計數
- [ ] 後台 `Admin/Posts/Index` 每列顯示 view_count（可作為排序欄）
- [ ] 計數失敗不影響文章頁正常顯示（try/catch 或 fire-and-forget）

## Requirements

- **FR-001**: Post slug 必填、手動輸入英文 SEO 網址（`^[a-z0-9\-]+$`）、全站唯一；不自動由標題生成（中文標題 `Str::slug` 會產空字串）。前台一律 `/blog/{slug}`，與 `/course/{slug}` 不同命名空間。slug 變更不做自動 301（MVP）。
- **FR-002**: 內文不存 HTML 快取欄；`PostService::toHtml(body_md)` 於前台 request 時渲染（比照 `EmailTemplate` 用 CommonMarkConverter 的慣例）= CommonMark → strip `<script>/<style>/on*` → VideoEmbedService 把獨立成行的 YouTube/Vimeo 連結換成 responsive embed。前台以 v-html 吃 server-render HTML（正文進初始 payload 利 SEO，優於課程頁 client-side `marked`），永不直接 render 使用者原始 HTML。
- **FR-003**: 訂閱者身分即 users 會員（不另建名單表）；newsletter_status ∈ {none, subscribed, unsubscribed, dormant}。none=從未訂閱、unsubscribed=主動退訂、dormant=系統休眠。
- **FR-004**: 既有會員預設 none — 不因已是 member 就收信；必須明確訂閱（尊重同意權、保護 domain 到達率）。
- **FR-005**: 發佈 ≠ 寄信。Post published 只上網做 SEO；寄信是獨立動作並產生 Broadcast。同一 Post 可零次或多次 broadcast，每次獨立記錄，不覆蓋歷史。
- **FR-006**: 極簡信規則 — 單欄、主要為文字、YouTube 以縮圖圖片連回文章（不 iframe）、必附 text/plain 備援與 `List-Unsubscribe`(+`-Post`) header；退訂連結恆在。降低進垃圾桶機率與維持額度效率。
- **FR-007**: 開信事件 immutable（無 updated_at），(broadcast_id, user_id, event_type) unique，firstOrCreate 失敗僅 log 不中斷回應。
- **FR-008**: dormant 判定保守（Apple MPP 會預抓像素灌水開信 → 傾向少殺不誤刪付費會員）：僅對「有被寄過且全未開」者休眠；任何一次開信立即復活。
- **FR-009**: 訂閱採 OTP 兩步驗證（沿用 VerificationCodeService，驗證通過才建帳號）杜絕 subscribe-bombing；開信像素端點免登入走 signed URL。退訂 token 為 UUID、訂閱成立時產生。
- **FR-010**: Broadcast 發送以每收件者一個 queued Job 進行，逐封夾帶個人化 pixel 與退訂連結；不在單封信合併多人（追蹤與一鍵退訂需要個別 token）。
- **FR-011**: view_count 為近似計數（每 session 每篇去重、admin/draft/bot 不計），只作內容成效與排序參考，非精確分析；以 `increment()` 原子更新避免併發競態，計數失敗不得影響文章頁回應。

## 設計決策

- **D1**: 訂閱者 = User，狀態掛 users 欄位（比照 010 drip 的 D1）— 後台會員/批次發信/贈課無縫共用，退訂/休眠只改狀態不刪帳號。否決獨立 subscribers 表（會與既有會員名單雙寫）。
- **D2**: 新建 `newsletter_email_events`（broadcast_id + user_id），不共用也不重構 `drip_email_events` — drip 事件硬綁 subscription+lesson，語意不同；沿用相同「immutable + unique 去重 + signed pixel」pattern 但獨立表，010 零改動、零回歸風險。（否決 polymorphic 大重構。）
- **D3**: Post 與 Broadcast 分離 — Post 是永久 SEO 網頁（單一真相），Broadcast 是一次寄送事件並快照 subject/recipients_count/發送統計。開信率算在 Broadcast 上，重寄產生新 Broadcast 不污染歷史。
- **D4**: 內文後端渲染但不快取 — `PostService::toHtml` 於 request 時轉（比照 `EmailTemplate::body_html` accessor，全站既有慣例），server-render HTML 進初始 payload 比課程頁的 client-side `marked` 更利 SEO；千級文章即時轉成本可忽略，省掉快取欄與回填問題。（否決自創 body_html 快取欄。）
- **D5**: 極簡防垃圾信與逐封發送 — 為了個人化 pixel + RFC 8058 一鍵退訂，採「每收件者一 Job」；規模千級可接受，佇列自然節流。否決「一封信 BCC 多人」（無法個別追蹤/退訂、更易進垃圾桶）。
- **D6**: 首頁 blog widget 換原生、Substack 移 SNS — 原生文章才進 sitemap/JSON-LD 產生 SEO 資產；Substack 定位為「另一條長文管道」不與輕量內容打架（沿用近期 substack→BLOG 更名成果）。
- **D7**: 引流做實 — Post 可綁 related_course_id，文章底 CTA 帶 UTM，讓電子報流量可歸因到課程銷售（接 002.us-8）。否決純文字連結（無法歸因）。
- **D8**: 分享按鈕用原生 share-intent / Web Share API，不掛第三方 JS — 保 SEO 與載入速度（比照全站不引入外部追蹤腳本的取向）。
- **D9**: SEO 沿用既有 `view()->share('og', …)` + app.blade.php `$og` 機制擴充，不另造系統 — 文章頁只需補 article 專屬欄位與 JSON-LD slot。
- **D10**: 訂閱採 OTP 兩步（email→驗證碼→建會員），沿用 VerificationCodeService/VerificationCodeMail/VerificationCodeInput — 與全站「驗證後才建帳號」一致，杜絕幫他人亂訂（subscribe-bombing），且比自建 double opt-in 確認連結更省事。（否決 single opt-in 直接建帳號。）
- **D11**: 瀏覽數用 posts.view_count 單一計數欄 + session 去重，不建 post_views 事件表 — 比照 drip 的 emails_sent 單欄取向與「不過度設計」原則；代價是無時間序列/UV 分析，未來要趨勢再升級成事件表。（否決事件表 MVP。）

## Schema

本模組擁有的資料表（欄位語意，細節見 migration）：

- `posts` — 文章主體。`slug` unique（必填、手動英文）；`body_md`（原稿；前台以 `PostService::toHtml` 即時渲染，不存 HTML 欄）；`excerpt`（摘要，供列表/RSS/信件前段）；`cover_image_path`、`og_image_path`（nullable）；`seo_title`、`meta_description`（nullable，空則 fallback title/excerpt）；`status`(draft/scheduled/published)、`published_at`(nullable)；`is_featured`(bool)；`view_count`(unsigned int, default 0，近似瀏覽數)；`related_course_id`(nullable FK courses，引流 CTA)；`author_id`(nullable FK users)。softDeletes。index：slug、(status, published_at)、is_featured。
- `tags` — `name`、`slug` unique。
- `post_tag` — pivot，(post_id, tag_id) unique。
- `post_images` — 每篇文章圖片庫（比照 course_images），`post_id`、`path`、`sort_order`。
- `broadcasts` — 一次寄送事件。`post_id` FK、`subject`（快照）、`status`(draft/sending/sent)、`recipients_count`、`sent_count`、`sent_at`(nullable)。index：(status)、post_id。
- `newsletter_email_events` — 開信事件（未來可擴 clicked）。`broadcast_id` FK、`user_id` FK、`event_type`(opened)、`ip`、`user_agent`、`created_at`。unique(broadcast_id, user_id, event_type)；只有 created_at。
- `users` 增欄（本模組 migration，User model 屬 001 為 touchpoint）— `newsletter_status`(enum none/subscribed/unsubscribed/dormant, default none)、`newsletter_subscribed_at`(nullable)、`newsletter_unsubscribe_token`(uuid, nullable, unique)、`newsletter_last_opened_at`(nullable)、`newsletter_status_changed_at`(nullable)。index：newsletter_status。

## Tasks

### Phase 1 — 資料層與模型
- [ ] T001 [P] 建 posts migration（含 view_count unsigned int default 0）in `database/migrations/2026_07_10_000001_create_posts_table.php`
- [ ] T002 [P] 建 tags migration in `database/migrations/2026_07_10_000002_create_tags_table.php`
- [ ] T003 [P] 建 post_tag pivot migration in `database/migrations/2026_07_10_000003_create_post_tag_table.php`
- [ ] T004 [P] 建 post_images migration in `database/migrations/2026_07_10_000004_create_post_images_table.php`
- [ ] T005 [P] 建 broadcasts migration in `database/migrations/2026_07_10_000005_create_broadcasts_table.php`
- [ ] T006 [P] 建 newsletter_email_events migration in `database/migrations/2026_07_10_000006_create_newsletter_email_events_table.php`
- [ ] T007 [P] users 加 newsletter 欄位 migration in `database/migrations/2026_07_10_000007_add_newsletter_fields_to_users_table.php`
- [ ] T008 Post/Tag/PostImage/Broadcast/NewsletterEmailEvent models（關聯、casts、scopes）in `app/Models/*.php`
- [ ] T009 User model 加 newsletter casts / relations / scopeNewsletterSubscribed in `app/Models/User.php`（touchpoint 001）

### Phase 2 — 內容渲染與後台文章 CRUD
- [ ] T010 PostService：markdown→html + VideoEmbedService + sanitize + og payload in `app/Services/PostService.php`
- [ ] T011 Admin PostController + Store/UpdatePostRequest in `app/Http/Controllers/Admin/PostController.php`
- [ ] T012 [P] Admin PostImageController（圖片庫上傳/刪除）in `app/Http/Controllers/Admin/PostImageController.php`
- [ ] T013 [P] Admin Posts Index/Create/Edit + PostForm.vue in `resources/js/Pages/Admin/Posts/*.vue`
- [ ] T014 PublishScheduledPosts command in `app/Console/Commands/PublishScheduledPosts.php`

### Phase 3 — 前台部落格與 SEO
- [ ] T015 BlogController（index/show/tag）in `app/Http/Controllers/BlogController.php`
- [ ] T016 [P] Blog Index/Show/Tag.vue + PostCard/ShareButtons in `resources/js/Pages/Blog/*.vue`
- [ ] T017 BlogFeedController（RSS 2.0）in `app/Http/Controllers/BlogFeedController.php`
- [ ] T018 sitemap 納入 posts/tags in `app/Http/Controllers/SitemapController.php` + `resources/views/sitemap.blade.php`（touchpoint 000）
- [ ] T019 app.blade.php og 擴充 article + JSON-LD in `resources/views/app.blade.php`（touchpoint 000）
- [ ] T020 首頁 blog widget 改原生 Post in `app/Http/Controllers/HomeController.php` + `resources/js/Pages/Home.vue` + `resources/js/Components/BlogArticles.vue`（touchpoint 002）

### Phase 4 — 訂閱與會員
- [ ] T021 NewsletterService（subscribe/unsubscribe/reactivate/markDormant）in `app/Services/NewsletterService.php`
- [ ] T022 NewsletterSubscriptionController（OTP 兩步：subscribe 送碼 / verify 建會員+訂閱）+ StoreNewsletterSubscriptionRequest，沿用 VerificationCodeService/VerificationCodeMail（touchpoint 001）in `app/Http/Controllers/NewsletterSubscriptionController.php`
- [ ] T023 [P] SubscribeForm.vue（兩步，Step2 沿用 VerificationCodeInput）+ Newsletter/Unsubscribe.vue in `resources/js/Components/Newsletter/SubscribeForm.vue`、`resources/js/Pages/Newsletter/Unsubscribe.vue`
- [ ] T024 NewsletterWelcomeMail + 極簡模板 in `app/Mail/NewsletterWelcomeMail.php`、`resources/views/emails/newsletter-welcome.blade.php`

### Phase 5 — 寄送、追蹤與清理
- [ ] T025 BroadcastService（建 Broadcast、組收件人、dispatch、統計）in `app/Services/BroadcastService.php`
- [ ] T026 SendBroadcastEmailJob（逐封、狀態檢查、重試）in `app/Jobs/SendBroadcastEmailJob.php`
- [ ] T027 NewsletterBroadcastMail + 極簡信模板（pixel / List-Unsubscribe / text 備援）in `app/Mail/NewsletterBroadcastMail.php`、`resources/views/emails/newsletter-broadcast.blade.php`
- [ ] T028 Admin BroadcastController + SendBroadcastRequest + Broadcasts Index/Show.vue in `app/Http/Controllers/Admin/BroadcastController.php`、`resources/js/Pages/Admin/Broadcasts/*.vue`
- [ ] T029 NewsletterTrackingController（signed pixel、寫 event、更新 last_opened_at、dormant 自動復活）in `app/Http/Controllers/NewsletterTrackingController.php`
- [ ] T030 CleanDormantSubscribers command in `app/Console/Commands/CleanDormantSubscribers.php`
- [ ] T031 路由與排程註冊 in `routes/web.php`、`routes/console.php`（touchpoint 000）；AdminLayout 側欄選單 in `resources/js/Layouts/AdminLayout.vue`（touchpoint 000）

### Phase 6 — 文章瀏覽次數（US8）
- [ ] T032 BlogController::show 對 published 文章 session 去重後原子 `increment('view_count')`（admin/draft/bot 不計）in `app/Http/Controllers/BlogController.php`
- [ ] T033 [P] 後台文章列表顯示/可排序 view_count in `app/Http/Controllers/Admin/PostController.php`、`resources/js/Pages/Admin/Posts/Index.vue`

## 進度日誌

- 2026-07-10: 建立 spec（draft）。已確認 6 項澄清：既有會員預設不訂閱、新建獨立事件表、dormant 保守判定＋開信自動復活、發佈≠寄信、Markdown+圖片庫編輯、Broadcast 對象=全部 subscribed。
- 2026-07-10: 一致性稽核後修訂 3 項 — (1) 內文改後端 `PostService::toHtml` request 時渲染、棄 body_html 快取欄（比照 EmailTemplate accessor，且更利 SEO）；(2) slug 改必填手動英文網址；(3) 訂閱改回 OTP 兩步驗證（沿用 VerificationCodeService，防 subscribe-bombing）。
- 2026-07-10: 使用者審核通過，status → building，可 /dev 實作。
- 2026-07-10: 加 US8 文章瀏覽次數（posts.view_count 單欄 + session 去重，不建事件表）。

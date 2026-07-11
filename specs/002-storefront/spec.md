---
id: 002-storefront
status: done
owner_files:
  - app/Http/Controllers/HomeController.php
  - app/Http/Controllers/CourseController.php
  - app/Http/Controllers/Admin/HomepageSettingController.php
  - app/Http/Controllers/Admin/HomepageFeaturedCourseController.php
  - app/Http/Controllers/Admin/SocialLinkController.php
  - app/Http/Requests/Admin/StoreFeaturedCourseRequest.php
  - app/Http/Requests/Admin/UpdateFeaturedCourseRequest.php
  - app/Http/Requests/Admin/UpdateHomepageSettingRequest.php
  - app/Http/Requests/Admin/StoreSocialLinkRequest.php
  - app/Http/Requests/Admin/UpdateSocialLinkRequest.php
  - app/Models/SocialLink.php
  - app/Models/HomepageFeaturedCourse.php
  - app/Services/BlogRssService.php
  - database/migrations/2026_03_25_000002_create_social_links_table.php
  - database/migrations/2026_07_05_000001_create_homepage_featured_courses_table.php
  - database/migrations/2026_07_05_000002_extend_blurb_on_homepage_featured_courses.php
  - database/migrations/2026_07_11_000001_rename_monetization_label_to_business_strategy.php
  - resources/js/Components/BlogArticles.vue
  - resources/js/Components/SubstackArticles.vue
  - resources/js/Components/FeaturedCourses.vue
  - resources/js/Components/SocialLinks.vue
  - resources/js/Components/CourseCard.vue
  - resources/js/Components/SectionHeader.vue
  - resources/js/Components/Course/PriceDisplay.vue
  - resources/js/Pages/Home.vue
  - resources/js/Pages/Course/Show.vue
  - resources/js/Pages/Admin/HomepageSettings/Edit.vue
  - resources/js/Pages/Admin/Courses/Traffic.vue
touchpoints:
  - file: app/Models/Course.php
    owner: 004-course-admin
    why: 首頁列表/銷售頁讀取課程資料（visibleToUser/ordered scope、slug 路由綁定）；分類 slug 改名時 cascade 更新 courses.content_category
  - file: app/Models/SiteSetting.php
    owner: 000-platform-core
    why: hero/RSS/SNS/側欄排序/內容分類等設定的 key-value 存取（get/getMany/set）
  - file: database/migrations/2026_03_25_000001_create_site_settings_table.php
    owner: 000-platform-core
    why: site_settings 為全站共用 key-value 表，本模組只使用其中的鍵，不擁有資料表
  - file: database/migrations/2026_05_08_000001_add_utm_to_orders_table.php
    owner: 005-checkout
    why: UTM/click id/referrer 欄位屬 orders 表；本模組只寫 session('traffic_source')，落庫由結帳流程完成
  - file: app/Http/Controllers/CheckoutController.php
    owner: 005-checkout
    why: 結帳建立訂單時讀取 session('traffic_source') 寫入 orders 的 utm 欄位
  - file: app/Http/Controllers/Admin/CourseController.php
    owner: 004-course-admin
    why: traffic()/trafficExport() 兩個方法提供來源統計查詢與 CSV 匯出（頁面 Traffic.vue 屬本模組）
  - file: database/migrations/2026_07_06_000001_add_content_category_to_courses_table.php
    owner: 004-course-admin
    why: content_category 欄位屬 courses 表；首頁分類篩選只讀取此欄位
---

# Storefront（門市前台）

## 目標

給訪客的門面：首頁（hero、課程列表、分類篩選、右側欄 widget）與課程銷售頁；
給管理員的首頁後台設定（hero / SNS / RSS / 精選課程 / 側欄排序 / 內容分類）；
以及行銷追蹤（銷售頁 UTM 捕捉 → 訂單來源統計頁）。

## User Stories

### User Story 1 - 首頁課程列表與分類篩選 (Priority: P1)

訪客進入首頁看到所有販售中課程的卡片（縮圖、名稱、tagline、價格/優惠價），可用「內容分類」按鈕（後台設定，最多 3 顆）與「產品類型」badge（講座/迷你課/完整課程/客製服務）前端即時篩選；點卡片進入銷售頁。

**驗收**：
- [x] 首頁顯示 `Course::visibleToUser($user)->ordered()` 的課程卡片；隱藏/草稿課程對一般訪客不出現，管理員可見並帶「草稿/隱藏」badge
- [x] 內容分類按鈕僅在後台開關開啟且至少一格分類設定時顯示；點選中的按鈕再點一次清除篩選回到全部
- [x] 產品類型 badge 只列出目前有課程的類型；分類與類型兩種篩選為 AND 疊加
- [x] 篩選後無課程時顯示「此分類目前沒有課程。」；全站無課程顯示空狀態引導
- [x] 卡片連結使用 `/course/{slug}`（無 slug 時 fallback 為 id）；優惠期間卡片顯示劃線原價＋紅色優惠價
- [x] RWD：課程 grid 手機 1 欄、sm 以上 2 欄；側欄 lg 以上 365px 右欄、以下堆疊在下方
- [x] 管理員瀏覽首頁時卡片顯示狀態 badge（草稿/預售/熱賣）與「隱藏」標記，一般訪客不顯示

### User Story 2 - 課程銷售頁 (Priority: P1)

訪客進入 `/course/{course}`（slug 或 id 皆可解析）看到完整銷售內容：Markdown 介紹（`marked` 前端渲染，允許 iframe 嵌入）、價格與優惠倒數、按產品型態變化的 CTA。

**驗收**：
- [x] 草稿課程對非管理員回 404；管理員進入為預覽模式（顯示預覽提示）
- [x] CTA 依型態切換：免費課「免費報名」、一般課「立即購買/加入購物車」、高價課隱藏價格時「立即預約」（預約表單）、drip 課「免費訂閱」
- [x] 已購買者顯示前往上課入口而非購買按鈕；可積分兌換課程顯示兌換按鈕（兩段式確認，顯示兌換後餘額）
- [x] 優惠價設定且未過期時 `PriceDisplay` 顯示劃線原價＋優惠價＋天/時/分/秒倒數
- [x] `?lp=1` 進入 Landing Page 模式（隱藏導覽等 UI）；隱藏課程（is_visible=false）銷售頁功能完整、僅不出現在首頁
- [x] 後端 share OG meta（title/description/image/url）供社群分享預覽
- [x] 已在購物車的課程顯示對應狀態（isInCart）；drip 課依 `canSubscribe` / 現有訂閱狀態切換表單與提示
- [x] `?coupon=CODE` 進入時把正規化後的折扣碼（大寫英數、6 碼）存入 session `checkout_coupon` 供結帳沿用

### User Story 3 - Hero Unit 首頁橫幅設定與呈現 (Priority: P1)

管理員在 `/admin/homepage` 管理首頁 hero（橫幅圖、標題、說明、CTA 按鈕），不需改 code；訪客在首頁看到對應呈現。

**驗收**：
- [x] 可上傳橫幅圖（JPG/PNG/WebP、≤5MB、寬 ≥1200px）；不符者拒絕並保留原圖；上傳成功時舊圖自動從 storage 刪除
- [x] 超過 5MB 前端選檔即時擋下提示；PHP `upload_max_filesize` 造成的靜默丟檔後端以 `UPLOAD_ERR_INI_SIZE` 偵測回驗證錯誤（雙層防護）
- [x] 可刪除橫幅圖 → 首頁 fallback 為 `bg-brand-navy` 純色底，文字保留
- [x] 標題/說明留空則不渲染空元素；按鈕 label 或 URL 任一為空則整顆按鈕不渲染
- [x] 前台呈現：標題為 navy 半透明色塊＋gold 左側 accent bar、說明白字帶陰影、CTA 右下角白框按鈕；hover 時整區加深、按鈕反白
- [x] 設定頁顯示目前橫幅預覽；CTA 按鈕連結一律新分頁開啟

### User Story 4 - 社群連結管理與顯示 (Priority: P1)

管理員在後台以「+」新增 SNS 連結（平台下拉：Instagram/Threads/YouTube/Facebook/BLOG/Podcast ＋ URL），inline 編輯/刪除，並有全域「顯示 SNS 區塊」開關；訪客在首頁側欄看到 icon 按鈕。

**驗收**：
- [x] 新增後依建立順序（sort_order = max+1）附加在列表尾；無拖曳排序
- [x] inline Edit 展開該列編輯 URL，儲存/取消；刪除前有確認提示
- [x] 全域開關關閉或無任何連結時，首頁 SNS 區塊整個隱藏（不留空卡片）
- [x] 同平台可重複新增（platform 僅決定 icon）；URL 需含 protocol，否則驗證錯誤
- [x] 所有社群連結新分頁開啟
- [x] `HomepageSettingsSeeder` 播種六筆預設連結與 hero/RSS 預設值，未設定前首頁即可正常渲染

### User Story 5 - 部落格 RSS 近期文章 (Priority: P2)

管理員在後台設定部落格 RSS URL；首頁側欄「近期文章」顯示最新 5 篇（標題＋日期，新分頁開啟）。

**驗收**：
- [x] `BlogRssService::getArticles($url)` 取最新 5 篇，快取 1 小時（key: `blog_articles_` + md5(url)），HTTP timeout 5 秒
- [x] RSS 抓取失敗/格式錯誤時回空陣列並記 log warning，首頁隱藏該區塊，不會出錯或阻塞頁面
- [x] RSS URL 清空儲存後區塊完全隱藏；URL 變更時舊 cache key 立即清除，下次載入抓新 feed

### User Story 6 - 精選課程與側欄排序 (Priority: P2)

管理員可把課程釘選到首頁右側欄「精選推薦」（縮圖＋自訂介紹 blurb ＋「立即了解」按鈕），拖曳排序精選項目，也可拖曳排序整個側欄三個 widget（精選推薦/追蹤站長/近期文章）。

**驗收**：
- [x] 從全部課程下拉挑選加入，可選填 blurb（≤500 字、保留換行、即時字數統計）；blurb 空時 fallback 顯示課程名
- [x] 同一課程可重複加入（各自帶不同 blurb）；inline 編輯 blurb、刪除（有確認）即時反映不需重整
- [x] 拖曳排序精選項目，drop 時持久化；首頁按 sort_order 呈現
- [x] 側欄 widget 順序存 `site_settings.sidebar_widget_order`（JSON）；讀取時正規化：未知 key 剔除、缺漏 key 依預設順序補齊
- [x] 課程被刪除時精選項目 cascade delete；首頁另以 `filter(course !== null)` 防呆；無精選時 widget 整個隱藏
- [x] 三個 widget 標題統一使用 `SectionHeader`（teal 色塊＋navy 底線視覺）
- [x] 後台加入用的課程下拉列出全部課程（id 由新到舊），含草稿與隱藏課程

### User Story 7 - 內容分類後台管理 (Priority: P2)

管理員在首頁設定頁編輯「內容分類」：最多 3 格（顯示文字＋英文名 slug），加上「在首頁顯示分類過濾按鈕」全域開關。

**驗收**：
- [x] slug 僅接受 `^[a-z-]+$`、各格不可重複；一格必須「全空」或「兩欄皆填」，否則驗證錯誤
- [x] 設定存 `site_settings.content_categories`（JSON，只存填滿的格）與 `content_filter_enabled`（預設關）
- [x] 依格位比對改名 slug 時，cascade 更新持有舊 slug 的 `courses.content_category`，課程不會變孤兒
- [x] 清空格位不會刪除或改派引用它的課程（該課程回到只在「全部」出現）
- [x] 未存過設定時 fallback 預設三分類（思維升級 mindset / 財務覺醒 finance / 知識變現 monetization）
- [x] `contentCategories()`（只含填滿格位）同時供後台課程表單的分類下拉使用；`contentCategorySlots()`（固定 3 格補空）供設定編輯器渲染

### User Story 8 - 課程連結來源追蹤 (Priority: P2)

訪客進入銷售頁時系統捕捉來源（UTM 五參數、gclid/fbclid/ttclid、referrer 網域）存 session；結帳付款後隨訂單落庫。管理員在課程列表點「來源」進 `/admin/courses/{course}/traffic` 看各行銷管道的訂單數與金額。

**驗收**：
- [x] 捕捉 `utm_source/medium/campaign/term/content`（各截 100 字）與 `gclid/fbclid/ttclid`（截 255 字）；無 UTM 時記 referrer 網域（去 www、排除自站與 payuni/newebpay 金流域名）
- [x] 統計頁依來源分組顯示訂單數＋營收，只計 `orders.status = paid`；可切 7/30/90 天/全部區間
- [x] 「管道」檢視前端把來源歸類：有 click id → 付費廣告；utm_source 比對 → 社群/搜尋引擎/電子報/影音/其他；皆無 → (直接造訪)
- [x] 顯示追蹤覆蓋率（有任一來源欄位的訂單 / 全部付款訂單）
- [x] CSV 匯出（BOM ＋中文表頭）逐筆列出訂單編號、時間、Email、金額與全部來源欄位；沿用目前天數篩選、chunk(200) 串流輸出
- [x] 管道彙總檢視依訂單數由多至少排序；來源/管道兩種檢視可切換
- [x] 無資料時顯示空狀態；統計表可橫向捲動適配手機
- [x] 天數參數後端白名單驗證（7/30/90，其餘視為全部），匯出連結沿用當前參數

## Requirements

- **FR-001**: `sns_section_enabled`、`content_filter_enabled` 等布林設定以 `"0"/"1"` 文字存於 site_settings，讀取時 MUST `(bool)(int)` 轉型（PHP `(bool)"0"` 為 true）。
- **FR-002**: 首頁與銷售頁對訪客 MUST 無錯誤降級：RSS 失敗、精選課程失聯、hero 未設定，皆隱藏區塊而非出錯。
- **FR-003**: 來源捕捉 MUST 在銷售頁（進站點）發生、以 session 傳遞；只有付款完成的訂單才進入統計（歸因於「帶來購買」而非「帶來流量」）。
- **FR-004**: referrer 黑名單 MUST 含自站網域與金流回跳網域（payuni.com.tw、newebpay.com），避免付款回跳覆蓋真實來源。
- **FR-005**: 課程路由綁定 MUST 先以 slug 再 fallback id 解析（`Course::resolveRouteBinding`），舊 id 連結不失效。
- **FR-006**: 分類 slug 改名 cascade 以「格位」比對新舊值，僅在同格位 slug 變更時執行，避免誤改。
- **FR-007**: 銷售頁 Markdown 渲染（marked v17）放行原生 HTML/iframe——內容僅管理員可寫，屬信任輸入。
- **FR-008**: 橫幅圖存於 `Storage::disk('public')` 的 `hero-banner/` 目錄；替換或刪除時 MUST 一併刪除舊檔，避免孤兒檔案累積。
- **FR-009**: 精選課程 blurb 上限 500 字 MUST 前後端雙重驗證（textarea maxlength＋即時計數器、server 端 max:500）。
- **FR-010**: UTM 參數捕捉 MUST trim 後截斷長度（UTM 100 字、click id 255 字）再寫入 session，防止超長 query 汙染資料。
- **FR-011**: 首頁課程查詢 MUST 用 select 白名單欄位＋map 輸出，不得整包 model 傳給前端（避免洩漏後台欄位）。

## 設計決策

- **D1**: 首頁所有設定集中在 site_settings key-value 表而非專用資料表 — 欄位增減免 migration；結構化資料（分類、側欄順序）以 JSON 字串存放並在讀取端正規化。
- **D2**: 分類/類型篩選在前端做（Vue computed），不打 API — 課程總量小，一次載入後即時切換體驗最好；被否決：後端 query string 篩選。
- **D3**: UTM 先存 session、結帳時落庫到 orders — 訪客瀏覽不產生資料列，只有轉換才記錄；被否決：page view 級追蹤表（量大且本平台只關心購買歸因）。
- **D4**: 社群連結不做拖曳排序（建立順序即顯示順序），精選課程與側欄 widget 才有拖曳 — 依 007 澄清決議，SNS 順序調整頻率極低。
- **D5**: 精選課程允許同課程重複加入 — 每筆可帶不同活動文案（blurb），資料表無 unique 限制。
- **D6**: `SubstackArticles.vue` 為 004 時期舊元件，已無任何引用（現用 `BlogArticles.vue`）— 保留於 owner_files 等待清理。
- **D7**: 管道歸類（社群/搜尋/電子報…）在 Traffic.vue 前端以 regex 做，後端只回原始分組 — 歸類規則常調整，免部署後端。
- **D8**: 來源統計以 `order_items` join `orders` 計算（訂單數 count distinct、營收 sum unit_price）— 一張訂單可含多課程，統計以「該課程」維度切分；被否決：直接查 purchases（缺 UTM 欄位）。
- **D9**: hero 標題/說明等文字設定不設預設值 fallback 於前端 — 由 `HomepageSettingsSeeder` 播種初始值，前端只負責「空值不渲染」。

## Schema

- `social_links` — 首頁 SNS 連結；`platform` 限六平台枚舉字串（僅決定 icon，可重複）、`sort_order` 建立時 max+1，無啟用欄位（存在即顯示，整區顯隱由 `sns_section_enabled` 控制）。
- `homepage_featured_courses` — 首頁精選課程；`course_id` FK cascadeOnDelete、`blurb` varchar(500) nullable（空則前台 fallback 課程名）、`sort_order` 拖曳重排時整批改寫。
- site_settings 使用鍵（表本身屬 000-platform-core）：`hero_title` / `hero_description` / `hero_button_label` / `hero_button_url` / `hero_banner_path`、`blog_rss_url`、`sns_section_enabled`、`sidebar_widget_order`（JSON array）、`content_categories`（JSON，≤3 組 label+slug）、`content_filter_enabled`。
- 來源欄位（`orders` 表，屬 005-checkout）：`utm_source/medium/campaign/term/content`、`gclid/fbclid/ttclid`、`referrer_domain` — 本模組寫 session、讀統計。

## 進度日誌

- 2026-07-11: 課程內容分類「知識變現」改「商業策略」（label；slug `monetization` 不變）。新增資料 migration 一併同步既有 `site_settings.content_categories` 與文章 `tags`（觸及 012 的 tags 資料，屬跨模組 demote 一致性修正）。
- 2026-07-10: SNS 平台 `substack` 更名為 `blog`（顯示 BLOG + 通用 RSS 圖示），含 DB migration 更名既有資料列
- 2026-07-06: 領域重組 — 合併 001(US1)+004+007+002(US12) 重寫，依實際 codebase 校正

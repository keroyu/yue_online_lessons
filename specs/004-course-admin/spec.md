---
id: 004-course-admin
status: done
owner_files:
  - app/Http/Controllers/Admin/CourseController.php
  - app/Http/Controllers/Admin/ChapterController.php
  - app/Http/Controllers/Admin/LessonController.php
  - app/Http/Controllers/Admin/CourseImageController.php
  - app/Http/Requests/Admin/StoreCourseRequest.php
  - app/Http/Requests/Admin/UpdateCourseRequest.php
  - app/Http/Requests/Admin/StoreChapterRequest.php
  - app/Http/Requests/Admin/StoreLessonRequest.php
  - app/Models/Course.php
  - app/Models/Chapter.php
  - app/Models/Lesson.php
  - app/Models/CourseImage.php
  - app/Policies/CoursePolicy.php
  - app/Console/Commands/UpdateCourseStatus.php
  - app/Mail/LessonAddedNotification.php
  - resources/views/emails/lesson-added.blade.php
  - resources/js/Components/Admin/CourseForm.vue
  - resources/js/Components/Admin/ChapterList.vue
  - resources/js/Components/Admin/LessonForm.vue
  - resources/js/Components/Admin/ImageGalleryModal.vue
  - resources/js/Pages/Admin/Courses/Index.vue
  - resources/js/Pages/Admin/Courses/Create.vue
  - resources/js/Pages/Admin/Courses/Edit.vue
  - resources/js/Pages/Admin/Courses/Chapters.vue
  - resources/js/Pages/Admin/Courses/Gallery.vue
  - database/migrations/2026_01_16_000001_create_courses_table.php
  - database/migrations/2026_01_17_000001_add_status_to_courses_table.php
  - database/migrations/2026_01_17_000002_create_chapters_table.php
  - database/migrations/2026_01_17_000003_create_lessons_table.php
  - database/migrations/2026_01_17_000005_create_course_images_table.php
  - database/migrations/2026_01_17_103320_remove_portaly_url_from_courses_table.php
  - database/migrations/2026_01_17_120809_add_pricing_fields_to_courses_table.php
  - database/migrations/2026_01_17_120822_add_dimensions_to_course_images_table.php
  - database/migrations/2026_01_30_072516_add_is_visible_to_courses_table.php
  - database/migrations/2026_02_28_rename_html_content_to_markdown_columns.php
  - database/migrations/2026_03_01_104839_rename_md_content_to_html_content_in_lessons_table.php
  - database/migrations/2026_03_01_132951_fix_rename_html_content_to_md_content_in_lessons.php
  - database/migrations/2026_03_07_160852_backfill_course_duration_minutes_from_lessons.php
  - database/migrations/2026_03_08_180036_add_seo_fields_to_courses_table.php
  - database/migrations/2026_03_09_000001_add_is_preview_to_lessons_table.php
  - database/migrations/2026_04_09_000001_add_high_ticket_fields_to_courses_table.php
  - database/migrations/2026_07_06_000001_add_content_category_to_courses_table.php
  - database/migrations/2026_07_06_000002_change_content_category_to_string_on_courses.php
touchpoints:
  - file: database/migrations/2026_02_16_000001_add_drip_fields_to_courses_table.php
    owner: 010-drip-email
    why: 課程表單雖可切換 course_type / drip_interval_days / 轉換目標，欄位語意與發信排程歸 drip 模組
  - file: app/Services/DripService.php
    owner: 010-drip-email
    why: 新增小節時 reactivate 已完成訂閱者；CourseController@subscribers 的統計亦呼叫此 service
  - file: resources/js/Pages/Admin/Courses/Subscribers.vue
    owner: 010-drip-email
    why: drip 訂閱者頁由本模組 CourseController@subscribers 渲染，頁面本身歸 drip 模組
  - file: app/Services/RedemptionService.php
    owner: 007-points-referral
    why: redeem_points 兌換邏輯歸 007；本模組僅擁有表單欄位與 courses.redeem_points 欄位定義
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: 前台銷售頁展示（定價/優惠倒數/slug 網址/免費試閱入口）讀取本模組維護的課程欄位
  - file: resources/js/Pages/Admin/Courses/Traffic.vue
    owner: 002-storefront
    why: 課程來源追蹤頁歸 002；本模組 Index.vue 僅提供「來源」入口按鈕（Portaly 課程不顯示）
  - file: app/Http/Controllers/Admin/HomepageSettingController.php
    owner: 002-storefront
    why: contentCategories() 提供課程表單「內容分類」下拉選項與驗證用 slug 白名單
  - file: app/Models/EmailTemplate.php
    owner: 011-high-ticket
    why: LessonAddedNotification 優先使用 lesson_added 事件模板渲染主旨與內文
  - file: app/Services/VideoEmbedService.php
    owner: 003-classroom
    why: 小節儲存時解析/驗證 Vimeo、YouTube 連結為 video_platform + video_id
  - file: app/Models/Purchase.php
    owner: 005-checkout
    why: 建課自動指派 system_assigned 購買紀錄、刪課防呆與通知信收件人名單皆查詢此 model
  - file: app/Models/HighTicketLead.php
    owner: 011-high-ticket
    why: type=high_ticket 的預約行為（leads/隱藏價格前台呈現）歸 011，本模組僅擁有表單欄位
---

# Course Admin（後台課程管理）

## 目標

讓管理員在後台完成課程的完整生命週期管理：課程 CRUD 與上下架、章節/小節結構編輯、
課程圖片庫與同頁插圖、預購自動開賣排程、新增小節通知學員。
本模組是全站課程資料（courses / chapters / lessons / course_images）的 schema owner。

## User Stories

### User Story 1 - 課程 CRUD 與上下架管理 (Priority: P1)

管理員在 `/admin/courses` 管理所有課程：新增、編輯、軟刪除、發佈與下架。
狀態機為 draft → preorder / selling，發佈時由 `sale_at` 自動判斷。

**驗收**：
- [x] 課程列表（含 `withTrashed`）依 sort_order 顯示名稱、講師、狀態、優惠價/原價、縮圖、開賣時間
- [x] 新增課程預設 `status=draft`、`is_published=false`、`sort_order=max+1`；縮圖上傳至 `storage/app/public/thumbnails`
- [x] 建立成功時在同一 transaction 內為建立者建立 `type=system_assigned`、$0 的購買紀錄（供前台以會員視角預覽）
- [x] 發佈：`sale_at` 為未來 → preorder；否則 → selling 並清空過期 `sale_at`；下架則回 draft、`is_published=false`
- [x] 有付費/贈送購買紀錄（status=paid 且 type≠system_assigned）的課程不可刪除，顯示「此課程已有學員購買，無法刪除」
- [x] 可刪除時，同 transaction 先刪 system_assigned 購買紀錄、再軟刪除課程
- [x] 列表每列提供章節、相簿、來源（僅非 Portaly 課程）、預覽等入口，按鈕語意配色統一

### User Story 2 - 課程表單欄位與定價設定 (Priority: P1)

課程表單（`CourseForm.vue`，Create/Edit 共用）涵蓋產品類別、內容分類、雙價定價、
SEO、點數兌換、金流與顯示設定。

**驗收**：
- [x] 產品類別 `type` 四選一：lecture / mini / full / high_ticket；選 high_ticket 時顯示「隱藏價格（改為預約模式）」開關（`high_ticket_hide_price`）
- [x] 內容分類 `content_category` 下拉由「首頁設定 → 內容分類」動態帶入（`contentCategories` prop）；後端以 `Rule::in(已設定 slugs)` 驗證，無設定時退回 `regex:^[a-z-]+$`
- [x] 定價：`price`（優惠價=實際售價）、`original_price`（原價，選填）、`promo_ends_at`（優惠到期）；建立時填了原價但未填到期日 → 自動預設 30 天後
- [x] SEO：`slug`（全域唯一、小寫英數連字號、≤200 字）與 `meta_description`（≤160 字）；前台 `/course/{slug}` 與 `/course/{id}` 皆可存取（`resolveRouteBinding` slug 優先）
- [x] `redeem_points`（nullable 正整數）僅為表單欄位；>0 即前台可兌換（`isRedeemable`），兌換流程歸 007
- [x] `is_visible` 顯示開關（隱藏課程仍可直接 URL 購買）；`payment_gateway`（payuni/newebpay）選項依 `site_settings` 憑證是否齊全（`gatewayConfigured`）啟用
- [x] `duration_minutes` 為唯讀自動計算值，表單無手動輸入欄位
- [x] 編輯表單完整回填所有欄位（含 2026-07-05 修正的 `redeem_points` 漏傳）

### User Story 3 - 章節與小節編輯 (Priority: P1)

管理員在 `/admin/courses/{course}/chapters` 建立課程結構：「章」為容器、「節」為實際內容，
節可獨立存在（無章）。支援影片、Markdown 圖文與拖曳排序。

**驗收**：
- [x] 章 CRUD（title + sort_order）；刪章時連動刪除其下所有小節
- [x] 節可屬章或獨立（`chapter_id` nullable）；`sort_order` 取所在容器（該章或無章區）max+1
- [x] `video_url` 經 `VideoEmbedService` 驗證並解析為 `video_platform` + `video_id`；更新時清空連結則三欄一併設 null
- [x] 無影片小節以 `md_content`（Markdown）呈現圖文/電子書內容
- [x] 時長欄接受 `M:SS` 與 `H:MM:SS` 兩種輸入，轉存 `duration_seconds`；小節增刪改後課程 `duration_minutes` 自動重算（僅加總有 `video_id` 的小節）
- [x] 拖曳排序（vuedraggable）：章 reorder；小節 reorder 可跨章拖曳（同時更新 `chapter_id` 與 `sort_order`）
- [x] `is_preview` 免費試閱勾選框（drip 課程不顯示）；重新編輯時勾選狀態正確回填（2026-05-09 修正）
- [x] 課中促銷/獎勵欄位：`promo_delay_seconds`、`promo_html`、`promo_url`、`reward_html`、`video_access_hours`（觀看期限）
- [x] drip 課程新增小節時自動 reactivate 已完成的訂閱者（`DripService::reactivateCompletedSubscriptions`）

### User Story 4 - 課程圖片庫與同頁插圖 (Priority: P2)

每個課程有獨立相簿（`/admin/courses/{course}/images` + 課程編輯頁的 `ImageGalleryModal`），
支援批次上傳/刪除，並可在編輯課程介紹時同頁選圖插入 Markdown。

**驗收**：
- [x] 單張與批次上傳（一次 ≤20 張、單張 ≤10MB、限 jpg/jpeg/png/gif/webp），違規回中文錯誤訊息且整批不上傳
- [x] 批次上傳採後端反序插入（`array_reverse`）+ `orderByDesc('id')` 查詢，顯示順序與選取順序一致
- [x] Modal 多選插入：依點擊順序顯示數字 badge、可取消重排；尺寸統一套用、多張以空行分隔一次插入編輯器游標處
- [x] 批次刪除：左上角 checkbox 專責勾選，工具列（已選 N / 全選 / 刪除已選）僅在有勾選時顯示
- [x] 刪圖（單張/批次）後自動以 regex 精確比對 URL，清除 `description_md` 中對應的 `<img>` 與 `![alt](url)` 引用；清理失敗不影響刪圖結果
- [x] 上傳時以 `getimagesize` 記錄原始寬高，供插入時等比縮放計算

### User Story 5 - 預購課程上架排程 (Priority: P2)

設定為「預購中」且指定 `sale_at` 的課程，到時自動切換為「熱賣中」，無需人工操作。

**驗收**：
- [x] `courses:update-status` 指令批次將 `status=preorder` 且 `sale_at <= now` 的課程 update 為 `selling`
- [x] `routes/console.php` 排程每分鐘執行，開賣時間到達後 1 分鐘內完成切換
- [x] 無符合條件課程時靜默結束（不輸出、exit 0）

### User Story 6 - 新增小節 Email 通知學員 (Priority: P2)

管理員在已發布課程新增小節時可勾選「發送 Email 通知學員」，提醒舊學員回訪。
通知以小節（非章）為觸發單位。

**驗收**：
- [x] 新增小節 Modal 的 `notify_members` 勾選框僅在「已發布（非 draft）且非 drip」課程顯示；後端亦二次判斷，drip/草稿課即使傳入也不發信
- [x] 收件人為該課程 `status≠refunded` 且 `type≠system_assigned` 的購買者（排除退款學員與管理員自身）
- [x] 信件優先使用 `EmailTemplate::forEvent('lesson_added')` 模板（變數：course_name / lesson_title / classroom_url，CommonMark 轉 HTML）；無模板時 fallback 至純文字 blade（`emails/lesson-added.blade.php`），主旨依課程 type 顯示 課程/迷你課/講座
- [x] 逐封同步發送；單封失敗僅記 log，不中斷後續發送、不影響小節儲存成功

## Requirements

- **FR-001**: 後台路由統一掛 `auth` + `admin` middleware（只認 `role=admin`）。`CoursePolicy` 雖有 editor 條款（create/update 允許 editor），但 editor 目前被 middleware 擋在後台外，Policy 的 editor 條款僅在前台 `view`（草稿可見性）生效——修改權限模型時須同時考慮兩層
- **FR-002**: 課程可見性為三維獨立欄位：`status`（draft/preorder/selling）×`is_published`×`is_visible`；前台 visible scope 需三者同時滿足；「草稿限制」優先於「隱藏設定」
- **FR-003**: 優惠有效判定 = `original_price` 有值 且 `promo_ends_at` 為未來（`is_promo_active`）；只設 `price` 時前台僅顯示單一價格
- **FR-004**: `system_assigned` 購買紀錄不計入銷售統計；課程刪除時連動移除
- **FR-005**: `StoreCourseRequest` 對 `sale_at`/`promo_ends_at` 驗 `after:now`；`UpdateCourseRequest` 刻意不驗（允許保留既有的過去時間值，避免編輯其他欄位時被卡）
- **FR-006**: `duration_seconds` 有 DB NOT NULL 約束，controller 以 `?? 0` 補值；課程總時長只計 `video_id` 非空的小節
- **FR-007**: 更新課程未上傳新縮圖時不得覆蓋 `thumbnail`（unset）；有新檔時先刪舊檔
- **FR-008**: `course_type` 從 drip 切回 standard 時，後端自動清空 `drip_interval_days` 並刪除全部轉換目標
- **FR-009**: 小節/章 reorder 皆驗證 id 存在且以 `course_id` 限定 update 範圍，防止跨課程改寫
- **FR-010**: 刪除小節時先刪其 `lesson_progress` 紀錄再刪小節；刪章時先刪其下小節
- **FR-011**: 圖片刪除的 `description_md` 清理採 `preg_quote` 精確比對 URL，同 URL 多處引用全部移除，不影響其他圖片
- **FR-012**: slug 於 Store 驗 `unique:courses,slug`、Update 排除自身；留空時前台網址退回 id

## 設計決策

- **D1**: `status` + `is_published` 雙欄位而非單一狀態欄 — 下架回草稿後仍可由 `sale_at` 重新推斷發佈狀態；發佈邏輯（未來 sale_at → preorder）collapse 在 `publish()` 一處
- **D2**: 前台課程網址用 `resolveRouteBinding`（slug 優先、id fallback）— 舊 id 連結不失效，SEO slug 可隨時補設（否決：強制 slug redirect）
- **D3**: 通知信同步逐封 `Mail::send` — 學員量小（<百人），與驗證碼發信方式一致；未來需要時只需改 `queue()`（否決：Queue Job）
- **D4**: 課程總時長全自動計算、表單移除手動輸入 — 消除影片增刪後忘記更新的資料不一致（2026-03-10 起）
- **D5**: 批次上傳「反序插入 + orderByDesc(id)」保持選取順序 — 避免為排序加欄位或改前端邏輯
- **D6**: `content_category` 用 varchar 對照首頁設定 slug，而非 DB enum — 分類清單由後台維護可自由增減（2026-07-06 由 enum 改 string）
- **D7**: 建課自動指派 `system_assigned` 購買 — 讓建立者免手動造單即可在前台完整走學員流程
- **D8**: `type`（產品類別）與 `course_type`（交付模式 standard/drip）為兩個獨立維度，不可合併 — high_ticket 是「賣法」、drip 是「交付節奏」

## Schema

本模組擁有的資料表（細節見 migrations）：

- `courses` — 課程主檔。狀態機 draft/preorder/selling + `is_published` + `is_visible` 三維可見性；`price`=實際售價（優惠價）、`original_price`=原價、優惠有效需 `promo_ends_at` 未來；`type`=產品類別（lecture/mini/full/high_ticket，high_ticket 搭配 `high_ticket_hide_price`）；`course_type`=交付模式（standard/drip，drip 欄位語意歸 010）；`content_category`=首頁分類 slug（varchar）；`redeem_points`>0 即可點數兌換；`slug` unique nullable；`duration_minutes` 為衍生值（由小節自動加總）；軟刪除
- `chapters` — 純容器（title + sort_order），無內容欄位；刪除連動刪其下 lessons
- `lessons` — 實際內容單元。`video_platform`/`video_id` 由 URL 解析而來（不可信任手填）；`duration_seconds` NOT NULL 預設 0；`is_preview` 免費試閱；`chapter_id` nullable（獨立小節）；`promo_*`/`reward_html`/`video_access_hours` 為課中促銷與觀看期限欄位
- `course_images` — 課程相簿。`path`/`filename`/`width`/`height`（原始尺寸供等比計算）；刪除紀錄時須同步清 `courses.description_md` 引用與實體檔案

## 進度日誌

- 2026-07-06: 領域重組 — 自 002(後台)+008(課程類別) 重寫，依實際 codebase 校正

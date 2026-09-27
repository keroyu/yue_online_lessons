---
id: 004-course-admin
status: building
owner_files:
  - app/Http/Controllers/Admin/CourseController.php
  - app/Http/Controllers/Admin/ChapterController.php
  - app/Http/Controllers/Admin/LessonController.php
  - app/Http/Controllers/Admin/CourseImageController.php
  - database/seeders/CourseSeeder.php
  - database/seeders/LessonSeeder.php
  - app/Http/Controllers/Admin/CourseRoadmapController.php
  - tests/Feature/Admin/CourseImageBatchUploadTest.php
  - app/Http/Requests/Admin/StoreCourseRequest.php
  - app/Http/Requests/Admin/UpdateCourseRequest.php
  - app/Http/Requests/Admin/StoreChapterRequest.php
  - app/Http/Requests/Admin/CourseRoadmapRequest.php
  - app/Http/Requests/Admin/StoreLessonRequest.php
  - app/Models/Course.php
  - app/Models/Chapter.php
  - app/Models/Lesson.php
  - app/Models/CourseImage.php
  - app/Models/CourseRoadmapStage.php
  - app/Models/CourseRoadmapCheckpoint.php
  - app/Services/CourseRoadmapService.php
  - app/Services/LessonNotificationService.php
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
  - resources/js/Pages/Admin/Courses/Roadmap.vue
  - resources/js/Components/Admin/RoadmapStageCard.vue
  - database/migrations/2026_09_23_000001_create_course_roadmap_stages_table.php
  - database/migrations/2026_09_23_000002_create_course_roadmap_checkpoints_table.php
  - database/migrations/2026_09_23_000003_add_roadmap_title_to_courses_table.php
  - tests/Feature/Admin/CourseRoadmapTest.php
  - database/migrations/2026_08_01_000001_add_ebook_to_courses_type.php
  - tests/Feature/Admin/CourseTypeTest.php
  - tests/Feature/Admin/CourseCreateFieldsTest.php
  - tests/Feature/Admin/AdminCourseListLinksTest.php
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
  - database/migrations/2026_09_27_000001_add_notified_at_to_lessons_table.php
  - tests/Feature/Admin/LessonNotificationTest.php
touchpoints:
  - file: database/migrations/2026_02_16_000001_add_drip_fields_to_courses_table.php
    owner: 010-drip-email
    why: 課程表單雖可切換 course_type / drip_interval_days / 轉換目標，欄位語意與發信排程歸 drip 模組
  - file: app/Services/DripService.php
    owner: 010-drip-email
    why: 新增小節時 reactivate 已完成訂閱者（`CourseController@subscribers` 已於 2026-08-04 刪除，訂閱者統計改由 011 US8 的 Leads 名單頁呼叫 `DripService::subscriberPageData()`）
  - file: resources/js/Components/Admin/Leads/SubscriberListTab.vue
    owner: 010-drip-email
    why: 歷史接點 —— 2026-08-04 前 drip 訂閱者頁由本模組 `CourseController@subscribers` 渲染；現已移交 011 US8 的 Leads 名單頁，本模組不再涉入（課程編輯頁的「訂閱者」按鈕亦一併移除）
  - file: app/Services/RedemptionService.php
    owner: 007-points-referral
    why: redeem_points 兌換邏輯歸 007；本模組僅擁有表單欄位與 courses.redeem_points 欄位定義
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: 前台銷售頁展示（定價/優惠倒數/slug 網址/免費試閱入口）讀取本模組維護的課程欄位
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 產品類別中文標籤對照（getTypeLabel）與 Meta CAPI 的 content_category — 新增 type 值須同步
  - file: resources/js/Components/CourseCard.vue
    owner: 002-storefront
    why: 首頁課程卡片的類別 badge 標籤對照 — 新增 type 值須同步
  - file: resources/js/Pages/Home.vue
    owner: 002-storefront
    why: 首頁產品類型篩選 badge 的標籤與排序（typeLabels / typeOrder）— 新增 type 值須同步
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
    why: 建課自動指派 system_assigned 購買紀錄、刪課防呆與通知信收件人名單皆查詢此 model；小節通知的方案過濾呼叫 `Purchase::accessibleLessonIds()`（語意歸 011 US21）
  - file: app/Models/CoursePlan.php
    owner: 011-high-ticket
    why: 小節通知的收件人過濾與「預計通知人數」預覽需讀 course_plan_lesson 歸屬與各方案的合格購買數（US6 追加，FR-024/FR-027）
  - file: app/Http/Requests/Concerns/NormalizesTaipeiInput.php
    owner: 000-platform-core
    why: sale_at / promo_ends_at 的 FormRequest 用它在 prepareForValidation() 把 datetime-local 的台北牆鐘轉成 UTC（000 US11）
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
- [x] 列表第一欄的課程名稱本身即銷售頁連結（新分頁開啟），滑鼠移入有 hover 變色
- [x] 操作欄原本的「銷售頁」連結改名為「教室預覽」，指向 `/member/classroom/{id}`（新分頁開啟）

### User Story 2 - 課程表單欄位與定價設定 (Priority: P1)

課程表單（`CourseForm.vue`，Create/Edit 共用）涵蓋產品類別、內容分類、雙價定價、
SEO、點數兌換、金流與顯示設定。

**驗收**：
- [x] 產品類別 `type` 四選一：lecture / mini / full / high_ticket；選 high_ticket 時顯示「隱藏價格（改為預約模式）」開關（`high_ticket_hide_price`）
- [x] 內容分類 `content_category` 下拉由「首頁設定 → 內容分類」動態帶入（`contentCategories` prop）；後端以 `Rule::in(已設定 slugs)` 驗證，無設定時退回 `regex:^[a-z-]+$`
- [x] 定價：`price`（優惠價=實際售價）、`original_price`（原價，選填）、`promo_ends_at`（優惠到期）；建立時填了原價但未填到期日 → 自動預設 30 天後
- [x] `sale_at` 與 `promo_ends_at` 的輸入與顯示皆為**台北時間**：表單送出的裸牆鐘字串在 `{Store,Update}CourseRequest::prepareForValidation()` 轉成 UTC 存，列表與編輯頁回填時轉回台北（000 US11）。編輯頁回填值必須與輸入值逐字相同，否則每存一次就走 8 小時
- [x] SEO：`slug`（全域唯一、小寫英數連字號、≤200 字）與 `meta_description`（≤160 字）；前台 `/course/{slug}` 與 `/course/{id}` 皆可存取（`resolveRouteBinding` slug 優先）
- [x] `redeem_points`（nullable 正整數）僅為表單欄位；>0 即前台可兌換（`isRedeemable`），兌換流程歸 007
- [x] `is_visible` 顯示開關（隱藏課程仍可直接 URL 購買）；`payment_gateway`（payuni/newebpay）選項依 `site_settings` 憑證是否齊全（`gatewayConfigured`）啟用
- [x] `duration_minutes` 為唯讀自動計算值，表單無手動輸入欄位
- [x] 編輯表單完整回填所有欄位（含 2026-07-05 修正的 `redeem_points` 漏傳）
- [x] 表單為單頁分區卡片佈局（無 tabs）：① 課程類型（course_type + type + content_category + high_ticket 設定）② 基本資訊（name / tagline / instructor_name / description / thumbnail）③ 課程介紹（description_md + 插入圖片）④ 販售設定（standard：定價/積分/預購/Portaly/金流；drip：發信間隔/目標商品/排程預覽，卡片標題隨模式切換）⑤ SEO 與顯示（slug / meta_description / is_visible）
- [x] 儲存列唯一且 sticky 固定於視窗底部（取消 + 儲存），表單內容底部預留 padding 不被遮擋
- [x] 送出驗證失敗時：自動捲動至第一個錯誤欄位並 focus，sticky 列顯示「有 N 個欄位需要修正」紅字提示（單頁佈局下所有錯誤皆可見，不再有 tab 藏錯誤問題）
- [x] 產品類別下拉含五個選項：講座課程 / 迷你課 / 完整課程 / 客製服務 / 電子書（值 lecture/mini/full/high_ticket/ebook），前後台標籤一致（FR-013）
- [x] 新增頁與編輯頁的欄位能力等價：新增頁同樣帶入可選目標商品清單（`availableCourses`），`course_type` / `drip_interval_days` / `target_course_ids` / `high_ticket_hide_price` 在建立時同樣被驗證並落庫（FR-015）
- [x] drip 課程的 `price` 非必填：表單無定價欄位，後端建立時預設 0（FR-016）
- [x] 任何驗證錯誤都必須在畫面上看得到：sticky 儲存列上方浮出紅色錯誤面板，標題為「有 N 個欄位需要修正」，其下逐條列出「欄位中文名：訊息」且每條可點擊捲至該欄位（儲存列文案同步改為「請修正上方列出的欄位」）；欄位因條件隱藏而不在 DOM 時，清單仍完整呈現（FR-017）

### User Story 3 - 章節與小節編輯 (Priority: P1)

管理員在 `/admin/courses/{course}/chapters` 建立課程結構：「章」為容器、「節」為實際內容，
節可獨立存在（無章）。支援影片、Markdown 圖文與拖曳排序。

**驗收**：
- [x] 章 CRUD（title + sort_order）；刪章時連動刪除其下所有小節
- [x] 節可屬章或獨立（`chapter_id` nullable）；`sort_order` 取所在容器（該章或無章區）max+1
- [x] `video_url` 經 `VideoEmbedService` 驗證並解析為 `video_platform` + `video_id`；更新時清空連結則三欄一併設 null
- [x] 無影片小節以 `content_md`（Markdown）呈現圖文/電子書內容
- [x] 時長欄接受 `M:SS` 與 `H:MM:SS` 兩種輸入，轉存 `duration_seconds`；小節增刪改後課程 `duration_minutes` 自動重算（僅加總有 `video_id` 的小節）
- [x] 拖曳排序（vuedraggable）：章 reorder；小節 reorder 可跨章拖曳（同時更新 `chapter_id` 與 `sort_order`）
- [x] `is_preview` 免費試閱勾選框（drip 課程不顯示）；重新編輯時勾選狀態正確回填（2026-05-09 修正）
- [x] 課中促銷/獎勵欄位：`promo_delay_seconds`、`promo_html`、`promo_url`、`reward_html`、`video_access_hours`（觀看期限）
- [x] drip 課程新增小節時自動 reactivate 已完成的訂閱者（`DripService::reactivateCompletedSubscriptions`）

### User Story 4 - 課程圖片庫與同頁插圖 (Priority: P2)

每個課程有獨立相簿（`/admin/courses/{course}/images` + 課程編輯頁的 `ImageGalleryModal`），
支援批次上傳/刪除，並可在編輯課程介紹時同頁選圖插入 Markdown。

**驗收**：
- [x] 多選/拖曳上傳（單張 ≤2MB、限 jpg/jpeg/png/gif/webp）：前端採「一檔一請求」序列上傳（`images.batch-store` 每次帶 1 張），每個請求僅 1 張 ≤2MB，遠低於 PHP `post_max_size(8M)`，不再因多張合計超限而整包被丟棄；上傳中顯示 N/總數 進度，某張失敗只回報該張、其餘照常上傳（無需調整伺服器 PHP 上限）
- [x] 顯示順序 `orderByDesc('id')`（最新在前）；後端 `batchStore` 仍支援一次多張（`array_reverse` 保序），供其他呼叫端使用
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

**補寄與回報（2026-09-27 追加，FR-024~FR-028）**：多方案課（011 US21）在建立小節的當下，`course_plan_lesson`
必然還是空的，綁方案的學員會被方案過濾整批濾掉 —— 信一封都不會寄，畫面卻只顯示「小節建立成功」。
補上「編輯時可補寄」與「寄送結果誠實回報」兩條路。

- [x] 小節**編輯** Modal 亦顯示「發送 Email 通知學員」勾選框（同樣只在已發布非 drip 課），`update()` 與 `store()` 共用同一支寄信 Service
- [x] 勾選框旁常駐「預計通知 N 位學員」；N=0 時改為警示樣式並 disable 勾選框，明寫原因（此小節尚未歸屬任何方案／此課程尚無學員）
- [x] 已寄過的小節顯示上次寄送時間（台北時區），勾選框文案改為「再次發送 Email 通知學員（收過的人會再收到）」的二次確認語意
- [x] 儲存後的 flash 帶實際成功寄出封數（`小節建立成功，已通知 12 位學員`）；0 封時附上原因，不得只說成功
- [x] 單方案／無方案課程的行為完全不變（`LessonNotificationTest` 回歸防線 + 手動回歸）

### User Story 7 - 課程 Roadmap 編輯 (Priority: P2)

管理員在 `/admin/courses/{course}/roadmap` 為單一課程自訂一份 Roadmap：一串**縱向排列的階段里程碑**，
每個階段有標題、一段 Markdown 說明，以及一組供學員自我檢核的項目。Roadmap 是**選配**的 —
沒有建立任何階段的課程，教室頁完全不出現 Roadmap 入口。

**驗收**：
- [x] 課程列表每列新增「Roadmap」入口（比照章節／相簿），進入本頁
- [x] 頁面為單一「整份文件」表單：頂部可設定 Roadmap 標題（留空則顯示「Roadmap」），下方為階段卡片清單
- [x] 階段卡片可新增／刪除／拖曳排序（vuedraggable），每張卡含標題、Markdown 說明、檢核項目清單
- [x] 檢核項目可在卡片內新增／刪除／拖曳排序；Enter 直接新增下一項
- [x] 提供「從 Markdown 匯入」貼上框：解析 `## 標題` 為階段、其下 `- [ ]` 行為檢核項目，其餘文字為該階段說明；匯入為**覆蓋草稿**（按儲存才生效），並顯示「將取代目前 N 個階段」警告
- [x] 儲存為單次 PUT 整份文件；既有階段／檢核項目**以 id 保留**，只有真的被刪掉的項目才連同學員完成紀錄一起消失
- [x] 刪除階段或檢核項目時，若已有學員勾選，MUST 明確提示「N 位學員的 M 筆完成紀錄會一併刪除」再確認
- [x] 驗證錯誤以中文顯示於對應欄位；RWD 手機可用

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

- **FR-013**: `courses.type`（產品類別）值域為 `lecture` / `mini` / `full` / `high_ticket` / `ebook`，中文對照固定為 講座 / 迷你課 / 完整課程 / 客製服務 / 電子書。新增值 MUST 同步這 8 個位置，缺一就會出現「顯示成原始英文值」或「存檔被驗證擋下」：DB enum、Store/UpdateCourseRequest 的 `in:` 規則、CourseForm 選單、Admin Courses Index 篩選、Course/Show 的 getTypeLabel、CourseCard、Home 的 typeLabels + typeOrder、LessonAddedNotification 的信件標籤
- **FR-014**: 產品類別**只影響顯示與篩選**，不改變任何交付或金流行為（`high_ticket` 是唯一例外，見 D8）。`ebook` 與其他一般類別走完全相同的購買與教室流程

- **FR-015**: 建立與更新走同一份欄位契約 — 凡是 `CourseForm` 送得出來的欄位，`StoreCourseRequest` 與 `UpdateCourseRequest` MUST 都有對應規則，`store()` 與 `update()` MUST 都有對應的落庫處理，`create()` 與 `edit()` MUST 都提供該欄位所需的選項資料。**缺規則不會報錯，只會讓值被 `validated()` 靜默丟棄** — `course_type`、`drip_interval_days`、`target_course_ids`、`high_ticket_hide_price` 四欄即為此類（新增頁選了連鎖課程/勾了隱藏價格，存完全部復原成 standard 且無轉換目標）
- **FR-016**: drip 課程沒有定價概念（免費領取，靠 `target_course_ids` 導購），`price` 對 drip MUST 非必填並以 0 落庫；standard 維持必填。此規則同時適用 Store 與 Update
- **FR-017**: 驗證錯誤 MUST 永遠可見。錯誤欄位若被條件渲染隱藏（drip 隱藏定價卡、standard 隱藏 drip 卡），`[data-field]` 錨點不存在，捲動與 focus 都會失效——此時 MUST 退回文字清單呈現（欄位中文名 + 訊息），不得只留一個數字讓使用者猜。**任何新增的條件渲染區塊都受此規則約束**

- **FR-018**: 後台課程列表提供兩個前台入口，語意不重複：**課程名稱**（第一欄）連到銷售頁 `/course/{id}`，**操作欄「教室預覽」**連到教室 `/member/classroom/{id}`。兩者皆 `target="_blank" rel="noopener noreferrer"`，避免管理員離開列表後遺失搜尋/篩選狀態。admin 進入 `/member/classroom/{id}` 靠 `Course::hasAccessForUser()` 的 admin bypass（FR 不依賴 D7 的 `system_assigned` 購買紀錄），不需任何後端改動

- **FR-019**: Roadmap 為**選配**且**逐課程獨立**：`courses.roadmap_title`（nullable）只決定顯示名稱，「這門課有沒有 Roadmap」的唯一真相是 `course_roadmap_stages` 底下有沒有列。沒有階段 = 沒有 Roadmap，教室端不得出現任何入口或空殼區塊
- **FR-020**: 儲存是**整份文件覆寫**（單次 PUT），但 **id 必須守住**。payload 裡帶 `id` 的階段／檢核項目走 update、不帶 `id` 的走 insert、payload 裡沒出現的既有列才刪除。**絕對不可以「全刪再全建」** — 學員的完成紀錄掛在 `course_roadmap_checkpoints.id` 上，重建一次等於全站學員的 Roadmap 進度歸零
- **FR-021**: payload 帶進來的既有 `id` MUST 驗證歸屬（階段屬於本 course、檢核項目屬於該階段），不符一律 422。否則可用別門課的 id 拼出跨課程改寫
- **FR-022**: `sort_order` 由**陣列位置**決定，後端於儲存時整批重寫（0..n-1），不信任前端送來的 sort_order 值 — 前例 FR-009 的 reorder 防線
- **FR-023**: Markdown 匯入只是**前端的草稿產生器**，不是 import API：解析後填進表單狀態，使用者仍要按儲存。因此匯入產生的階段一律無 `id`（= 全新），使用者若在既有 Roadmap 上匯入，等同整份取代、既有完成紀錄消失 —— 這就是驗收要求必須先跳警告的原因

- **FR-024**: 通知信的收件人規則是**單一真相**，只實作在 `LessonNotificationService`：`purchases.status≠refunded` ∧ `type≠system_assigned` ∧（`course_plan_id` 為 null ∨ 該小節在其方案的 `course_plan_lesson` 內，即 `Purchase::accessibleLessonIds()` 判定）。`store()`、`update()`、預覽人數三處 MUST 都走它，**不得各自寫一份 Purchase query** —— 規則漂移的代價是「有人收到兩封、有人一封都沒有」，且不會有任何錯誤浮出來
- **FR-025**: 建立小節時 `course_plan_lesson` **必然為空**（方案歸屬走另一條 `PUT /lessons/{lesson}/plans`，`StoreLessonRequest` 完全不收 plan 欄位），所以多方案課在建立當下勾通知，綁方案的學員一律被 FR-024 濾掉、實際寄出 0 封。這不是過濾寫錯而是**時序**問題：011 FR-095 讓方案外小節在教室直接隱藏，寄了學員也點不到，故過濾本身 MUST 保留。因此「編輯時可補寄」MUST 存在 —— 否則綁方案的學員永遠收不到新小節通知，而現行設計連補救入口都沒有（勾選框只在 `!isEditing` 顯示，`update()` 也不讀 `notify_members`）
- **FR-026**: 寄送結果 MUST 誠實回報。flash 帶實際成功封數；0 封時 MUST 附原因（尚未歸屬任何方案／此課程尚無合格學員）。**不得只顯示「小節建立成功」讓管理員以為信寄出去了** —— 這正是本次問題被埋了這麼久的原因。**已知限制**：`BlockSuppressedRecipients`（000 FR-022）是靠 `MessageSending` 回 `false` 取消寄送、不丟例外，所以退信名單上的地址會被算進「已通知 N 位」。這是全站 14 個寄信點共有的行為，不在本次範圍 —— 封數的語意是「已交給 mailer 且未被擋下」，不是「進到收件匣」；要精確就得改成監聽 `MessageSent` 計數（未做）
- **FR-027**: 「預計通知人數」是**前端加總**，不是後端逐小節查詢：payload 帶一份 `notifiableCounts`（`no_plan` + 各 `course_plan_id` 的合格購買數，單一 `groupBy` 查詢即得），前端以 `no_plan + Σ counts[lesson.plan_ids]` 算出每個小節的預計人數。預覽**僅供參考**，實際寄送名單一律由後端依 FR-024 重算（比照 D23：提示是前端的事，不為一句提示多一個往返）
- **FR-028**: `lessons.notified_at` 只記**最後一次**寄送時間，不記「誰收過」。已寄過時勾選框 MUST 轉為明確的二次確認語意（顯示上次時間 + 「收過的人會再收到」），既不得靜默重寄、也不得永久封鎖 —— 永久封鎖會讓後來才被加進方案 B 的學員永遠收不到（FR-025 的同一個坑換個位置重演）

## 設計決策

- **D1**: `status` + `is_published` 雙欄位而非單一狀態欄 — 下架回草稿後仍可由 `sale_at` 重新推斷發佈狀態；發佈邏輯（未來 sale_at → preorder）collapse 在 `publish()` 一處
- **D2**: 前台課程網址用 `resolveRouteBinding`（slug 優先、id fallback）— 舊 id 連結不失效，SEO slug 可隨時補設（否決：強制 slug redirect）
- **D3**: 通知信同步逐封 `Mail::send` — 學員量小（<百人），與驗證碼發信方式一致；未來需要時只需改 `queue()`（否決：Queue Job）
- **D4**: 課程總時長全自動計算、表單移除手動輸入 — 消除影片增刪後忘記更新的資料不一致（2026-03-10 起）
- **D5**: 批次上傳「反序插入 + orderByDesc(id)」保持選取順序 — 避免為排序加欄位或改前端邏輯
- **D6**: `content_category` 用 varchar 對照首頁設定 slug，而非 DB enum — 分類清單由後台維護可自由增減（2026-07-06 由 enum 改 string）
- **D7**: 建課自動指派 `system_assigned` 購買 — 讓建立者免手動造單即可在前台完整走學員流程
- **D8**: `type`（產品類別）與 `course_type`（交付模式 standard/drip）為兩個獨立維度，不可合併 — high_ticket 是「賣法」、drip 是「交付節奏」
- **D9**: 課程表單採單頁分區卡片佈局取代 tabs（2026-07-11 UX 重構）— tabs 會把驗證錯誤藏在非當前分頁造成「按儲存沒反應」假象，且欄位歸類混亂（SEO 夾在基本資訊中、開賣時間/金流塞在價格頁）、儲存按鈕重複三份。改為五張分區卡片 + 單一 sticky 儲存列 + 錯誤自動捲動。表單 script 邏輯（useForm 欄位、drip 切換、圖片插入、submit transform）不變，只重排 template（否決：保留 tabs 加錯誤紅點 — 治標不治本；否決：雙欄主從佈局 — 手機仍折回單欄，複雜度不值得）

- **D10**: 擴充 type enum 的 migration 一律用 `Schema::change()` 帶完整值列表，不用 `DB::statement` 分 MySQL/sqlite 兩套 — `2026_04_09_000001` 當初只在 MySQL 分支加 `high_ticket`，sqlite 分支漏掉，導致測試 DB 的 CHECK 至今仍是三值、任何 high_ticket 課程都無法在測試中落庫（011 D12 記錄的限制）。本次 migration 一併把 sqlite 補齊，該限制隨之解除
- **D11**: 電子書仍是一門「課程」（沿用 courses/lessons），不另建資料表 — 交付形式（PDF 連結、影片、文字）本來就由小節內容決定，type 只負責前台分類與篩選；否決新增 `ebooks` 表（會讓購買、教室、積分兌換、drip 目標全部要多一條分支）

- **D12**: 錯誤呈現改為「捲動 + 常駐清單」雙軌，而不是只修 `price` 的必填規則 — 只改規則能解掉今天這個 case，但條件渲染的卡片還會再長（drip 卡、high_ticket 卡、未來的其他模式），任何一個隱藏欄位出錯就會重演「有 1 個欄位需要修正、但畫面上找不到紅字」。清單以 `fieldLabels` 對照表把 key 轉中文，`scrollToFirstError` 找不到 `[data-field]` 時不再靜默 `return`（否決：只把 price 改成 `required_unless` — 治標；否決：把隱藏卡片改成 `v-show` 讓 DOM 恆存 — 錯誤會捲到一張視覺上不存在的卡片，更難懂）
- **D13**: drip 的 `price` 用 `required_unless:course_type,drip` + `prepareForValidation` 補 0，而不是在前端塞 hidden input — 驗證契約留在後端單一來源，前端少一個「為了過驗證而存在」的隱形欄位；且 API/測試直接 POST 時行為一致
- **D14**: `create()` 的 `availableCourses` 查詢與 `edit()` 相同但不排除自身（新課還沒有 id）— 兩處共用一個 private helper `availableTargetCourses(?Course $exclude = null)`，避免日後條件（如「排除 drip」「僅已發布」）在兩處漂移

- **D15**: `Course` 覆寫 `getRouteKey()` 回傳 `slug ?: id`，而不是覆寫 `getRouteKeyName()` — 後者會讓尚未設定 slug 的課程產生 `/course/`（正式站確實有 slug 空白的課），前者只影響**產生**網址、解析仍走既有的 `resolveRouteBinding`（slug 或 id 皆可）。修正前所有由模型產生的連結（OG url、後台追蹤連結、領取後導向、教室 sales_url）都是 id 版；改在模型單點修，勝過在五個呼叫點各自傳 slug

- **D16**: 「教室預覽」指向 `/member/classroom/{id}`（會員教室本體，注意路由掛在 `prefix('member')` 群組下）而非 `/course/{id}/preview`（免費試閱頁）— 管理員要檢查的是學員買完後看到的完整教室（章節樹、影片、進度、作業），試閱頁只露出 `is_preview` 的小節，看不出交付內容是否正確。admin 的存取由 `hasAccessForUser()` 既有 bypass 提供（否決：新開一條 admin-only 預覽路由 — 教室頁已對 admin 全開，多一條路由只會多一份會漂移的渲染邏輯）
- **D17**: 網址沿用 payload 既有的 `course.id` 硬串，不為了 slug 改 index payload — `resolveRouteBinding` 同時吃 slug 與 id，後台連結不吃 SEO，加 slug 只是多一個要同步的欄位（D15 修的是**對外**產生的網址，後台內部入口不在其列）
- **D18**: 軟刪除課程的兩個連結維持與現狀相同（照常渲染）— 不為這個 case 加條件分支；點進去得到 404 與今天「銷售頁」連結的行為一致，屬既有行為不在本次範圍

- **D19**: Roadmap 拆成 `course_roadmap_stages` + `course_roadmap_checkpoints` 兩張正規化表，而不是 `courses.roadmap_json` 單一 JSON 欄位 — 學員的完成紀錄必須指向一個**穩定的識別子**。JSON 只能用陣列索引或自行維護的 uuid：前者在管理員插入／刪除／拖曳任一項時全部錯位（學員勾的「發布 30 篇內容」會變成別項），後者等於在 JSON 裡手刻一套 id 機制，還失去 FK cascade。代價是編輯要做 diff 儲存（FR-020），但那是一次性的 Service 邏輯（否決：JSON 欄位 — 編輯簡單、進度不可靠；否決：三張表全展平成單表 — 階段與檢核項目的欄位與排序語意不同）
- **D20**: 階段與檢核項目的所有權在 **004**（課程內容的一部分，比照 chapters/lessons），學員的完成紀錄 `roadmap_checkpoint_completions` 歸 **003**（學習行為，比照 `lesson_progress` / `assignment_completions`）。這條線與既有的模組分工完全一致：004 定義「課程長什麼樣」，003 記錄「學員做了什麼」
- **D21**: 編輯介面是**結構化表單**（卡片 + 兩層 vuedraggable），不是「一個大 Markdown 框存成文字」 — 存純文字就回到 D19 否決掉的位置（沒有穩定 id）。但業主的原始素材就是一份 Markdown 草稿，所以另外提供**一次性的匯入貼上框**（`## 標題` → 階段、`- [ ]` → 檢核項目、其餘行 → 說明），讓第一次建置不用手打 70 幾個項目。匯入產物一律當成全新資料（FR-023），解析器只是前端的字串處理，不落任何後端端點
- **D22**: `CourseRoadmapService::sync(Course $course, array $data): void` 封裝整段 diff 儲存（單一 transaction：驗歸屬 → upsert 階段 → upsert 各階段檢核項目 → 刪除缺席列 → 重寫 sort_order），controller 只做 `$this->service->sync($course, $request->validated())` + redirect。刪除交給 FK `cascadeOnDelete` 連動清完成紀錄，不在 Service 手動刪第三張表
- **D23**: `CourseRoadmapRequest` 另提供 `affectedCompletions(Course $course): int` 供刪除警告使用？**否決** — 警告是**前端**的事：頁面載入時每個檢核項目已帶著 `completed_count`，刪除時直接加總即可，不必為了一句提示多一個往返

- **D24**: 補寄做成**編輯 Modal 裡的同一顆勾選框**，不是小節列上一顆獨立「通知學員」按鈕 —— 介面與建立時完全一致（同一個 Modal、同一個位置、同一句文案），管理員不必學兩套心智模型。代價是「只想寄信」也得按一次儲存，但那是無害的 no-op update（否決：小節列加通知按鈕 — 多一條路由與一個只做一件事的 endpoint，語意還與既有勾選框重複；否決：設定方案時自動補寄 — 方案面板一次 sync 一整批小節，會毫無預警噴出數十封信，且管理員調方案歸屬的頻率遠高於「想通知」的頻率）
- **D25**: 抽 `LessonNotificationService`，把收件人規則從 controller 搬出去 —— `LessonController::store()`、`update()`、`ChapterController::index()` 的預覽人數三處共用同一份規則（FR-024）。D3 的「同步逐封 `Mail::send`、單封失敗只記 log」不變，只是搬家。方法簽名：`recipients(Lesson $lesson): Collection`（合格 purchases，已 eager load `user` 與 `plan.lessons:id`）、`notify(Lesson $lesson): array`（逐封寄送，回 `['sent' => int, 'failed' => int, 'eligible' => int]` 並寫 `notified_at`；constitution §II 要求有副作用的寫入回結構化結果，故非裸 int）、`notifiableCounts(Course $course): array`（回 `['no_plan' => int, 'plans' => [planId => int]]`，單一 `groupBy('course_plan_id')`）、`emptyReason(Lesson $lesson): ?string`（0 封時的原因，flash 與表單提示共用）（否決：在 LessonController 開 private helper — ChapterController 也要用，跨 controller 共用就該是 Service）
- **D26**: 防重複用**單一 `notified_at` datetime 欄位 + 明確二次確認**，而不是 `lesson_notifications(lesson_id, user_id, sent_at)` 明細表。明細表能做到零重複（方案 B 補寄時自動跳過方案 A 學員），但那是一張**只為了防重複而存在**的表，而補寄是低頻操作（設方案 → 補寄，一門課一生發生幾次）。改成把判斷交給看得到名單的管理員：「已於 09/27 14:30 通知，仍要再寄?」（否決：`lesson_notifications` 明細表 — 精準但成本不成比例；否決：`notified_user_ids` JSON 欄位 — 一樣要維護、又沒有表的查詢與去重能力）。**升級條件寫在這裡**：若日後補寄變成常態，或出現學員抱怨收到重複信，就該把這裡換成明細表，`notified_at` 退化為衍生值
- **D27**: `notified_at` **不進 `$fillable`** —— 它是寄送的副作用，不是表單欄位，只由 Service 以 `forceFill`/明確 `save()` 寫入。同理 `update()` MUST 比照 `store()` 明確寫出 `$request->safe()->except(['notify_members'])`：今天 `update()` 直接把含 `notify_members` 的 `validated()` 餵給 `$lesson->update()` 而沒出錯，是因為該 key 剛好不在 `$fillable` 被靜默丟棄 —— 那是運氣，不是設計（前例 FR-015 的「缺規則靜默丟棄」）

## Schema

- 本次新增 migration `2026_08_01_000001_add_ebook_to_courses_type.php` — `courses.type` enum 由 4 值擴為 5 值（加 `ebook`）；以 `Schema::change()` 同時作用於 MySQL 與 sqlite（D10）。down() 還原為 4 值前須確保無 ebook 資料列

**Roadmap（US7 新增，2026-09-23）**：

- `2026_09_23_000001_create_course_roadmap_stages_table.php` — `course_id`（FK cascade）、`title` string(200)、`description_md` text nullable、`sort_order` unsignedInteger default 0、timestamps；index `(course_id, sort_order)`
- `2026_09_23_000002_create_course_roadmap_checkpoints_table.php` — `course_roadmap_stage_id`（FK cascade）、`label` string(500)、`sort_order` unsignedInteger default 0、timestamps；index `(course_roadmap_stage_id, sort_order)`
- `2026_09_23_000003_add_roadmap_title_to_courses_table.php` — `courses.roadmap_title` string(100) nullable

關鍵不變量：**checkpoint 的 id 是學員完成紀錄的唯一錨點**（見 FR-020／D19）。刪除階段 → cascade 刪其檢核項目 → cascade 刪學員完成紀錄，這條鏈是刻意的，但也因此任何「重建式儲存」都會靜默清空全站進度。完成紀錄表本身（`roadmap_checkpoint_completions`）歸 003 擁有，見 003 US11 Schema 段。

**小節通知補寄（US6 追加，2026-09-27）**：

- `2026_09_27_000001_add_notified_at_to_lessons_table.php` — `lessons.notified_at` datetime nullable，無 index（只做單列讀寫，不做範圍查詢）

關鍵不變量：`notified_at` 是**最後一次寄送時間**，不是「誰收過」的名單（D26）。既有小節一律留 null，不回填 —— 過去確實寄過的小節會顯示成「未寄送」，這是刻意的：回填只能瞎猜時間，而 null 的語意「沒有寄送紀錄」本來就涵蓋它。此欄位不進 `$fillable`（D27）。

本模組擁有的資料表（細節見 migrations）：

- `courses` — 課程主檔。狀態機 draft/preorder/selling + `is_published` + `is_visible` 三維可見性；`price`=實際售價（優惠價）、`original_price`=原價、優惠有效需 `promo_ends_at` 未來；`type`=產品類別（lecture/mini/full/high_ticket，high_ticket 搭配 `high_ticket_hide_price`）；`course_type`=交付模式（standard/drip，drip 欄位語意歸 010）；`content_category`=首頁分類 slug（varchar）；`redeem_points`>0 即可點數兌換；`slug` unique nullable；`duration_minutes` 為衍生值（由小節自動加總）；軟刪除
- `chapters` — 純容器（title + sort_order），無內容欄位；刪除連動刪其下 lessons
- `lessons` — 實際內容單元。`video_platform`/`video_id` 由 URL 解析而來（不可信任手填）；`duration_seconds` NOT NULL 預設 0；`is_preview` 免費試閱；`chapter_id` nullable（獨立小節）；`promo_*`/`reward_html`/`video_access_hours` 為課中促銷與觀看期限欄位
- `course_images` — 課程相簿。`path`/`filename`/`width`/`height`（原始尺寸供等比計算）；刪除紀錄時須同步清 `courses.description_md` 引用與實體檔案

## Tasks

課程表單 UX 重構（D9，US-2 新驗收）：

- [x] T001 重構表單為五張分區卡片（課程類型/基本資訊/課程介紹/販售設定/SEO 與顯示），移除 tabs 與重複儲存列，販售設定卡依 course_type 切換 standard/drip 內容 in `resources/js/Components/Admin/CourseForm.vue`
- [x] T002 單一 sticky 底部儲存列（取消+儲存）、submit onError 捲動至第一個錯誤欄位並顯示錯誤數提示 in `resources/js/Components/Admin/CourseForm.vue`
- [x] T003 Create/Edit 頁面適配：內容底部 padding 避開 sticky 列、文案校對；手動驗證 standard / drip / high_ticket 三種課程的新增與編輯流程 + 手機 RWD in `resources/js/Pages/Admin/Courses/Create.vue`, `resources/js/Pages/Admin/Courses/Edit.vue`

### 新增產品類別「電子書」（US2 追加）

- [x] T00E1 migration：`courses.type` enum 加 `ebook`，用 `Schema::change()` 帶完整 5 值（MySQL + sqlite 同時生效）in database/migrations/2026_08_01_000001_add_ebook_to_courses_type.php
- [x] T00E2 [P] `in:lecture,mini,full,high_ticket,ebook` in app/Http/Requests/Admin/StoreCourseRequest.php, app/Http/Requests/Admin/UpdateCourseRequest.php
- [x] T00E3 [P] 後台選單與篩選加「電子書」；課程類別說明文字補上 in resources/js/Components/Admin/CourseForm.vue, resources/js/Pages/Admin/Courses/Index.vue
- [x] T00E4 [P] 前台標籤對照補 `ebook: '電子書'` in resources/js/Pages/Course/Show.vue, resources/js/Components/CourseCard.vue, resources/js/Pages/Home.vue（含 typeOrder 尾端）〔touchpoint 002〕
- [x] T00E5 [P] 新增小節通知信的類別標籤補上 in app/Mail/LessonAddedNotification.php
- [x] T00E6 驗證：`php artisan test` 全綠、`npm run build` exit 0；並補一個測試確認 ebook 課程可落庫（順帶驗證 D10 的 sqlite 修正）in tests/Feature/Admin/

### 修復新增課程流程與隱形驗證錯誤（US2 追加，FR-015~017）

Phase 1 — 後端欄位契約補齊（先做，前端的可見錯誤才有正確語意）
- [x] T00F1 `StoreCourseRequest` 補 `course_type`(required,in:standard,drip)、`drip_interval_days`(nullable,required_if,integer,1..30)、`target_course_ids`(nullable,array)+`.*`(exists)、`high_ticket_hide_price`(nullable,boolean) 四組規則與中文 messages，與 `UpdateCourseRequest` 對齊 in app/Http/Requests/Admin/StoreCourseRequest.php
- [x] T00F2 `price` 改 `required_unless:course_type,drip`，並在兩個 Request 的 `prepareForValidation()` 對 drip 補 `price = 0`；messages 補 `price.required_unless` in app/Http/Requests/Admin/StoreCourseRequest.php, app/Http/Requests/Admin/UpdateCourseRequest.php
- [x] T00F3 `store()` 比照 `update()`：抽出 `target_course_ids` 不進 `Course::create`，drip 時於同一 transaction 寫入 `drip_conversion_targets`；standard 時清空 `drip_interval_days` in app/Http/Controllers/Admin/CourseController.php
- [x] T00F4 抽 private `availableTargetCourses(?Course $exclude = null)`（`course_type != drip` + `published()` + orderBy name），`create()` 與 `edit()` 共用；`create()` 傳 `availableCourses`（D14）in app/Http/Controllers/Admin/CourseController.php

Phase 2 — 前端
- [x] T00F5 `Create.vue` 宣告並下傳 `availableCourses` prop in resources/js/Pages/Admin/Courses/Create.vue
- [x] T00F6 [P] sticky 列錯誤清單：加 `fieldLabels` key→中文對照，錯誤逐條顯示「欄位名：訊息」且可點擊捲至該欄位；`scrollToFirstError` 找不到 `[data-field]` 時改為不捲動但清單照常呈現（不得靜默 return）in resources/js/Components/Admin/CourseForm.vue

Phase 3 — 驗證
- [x] T00F7 測試：(a) POST 建立 drip 課程不帶 price → 201/redirect 且 `course_type=drip`、`price=0`、`drip_interval_days` 與 `drip_conversion_targets` 正確落庫；(b) POST 建立 high_ticket 且 `high_ticket_hide_price=true` → 該欄位為 true；(c) standard 不帶 price 仍擋下並回 `price` 錯誤 in tests/Feature/Admin/CourseCreateFieldsTest.php
- [x] T00F8 `php artisan test` 全綠 + `npm run build` exit 0；手動確認新增頁選「連鎖課程」時目標商品清單有選項

### 後台課程列表前台入口調整（US1 追加，FR-018）

- [x] T00G1 課程名稱欄（`<div class="font-medium text-gray-900">{{ course.name }}</div>`）改為 `<a :href="`/course/${course.id}`" target="_blank" rel="noopener noreferrer" class="font-medium text-gray-900 hover:text-brand-teal">`，保留其下 `開賣:` 副標不變 in resources/js/Pages/Admin/Courses/Index.vue
- [x] T00G2 操作欄第一個連結：文字「銷售頁」→「教室預覽」，`:href` 由 `/course/${course.id}` 改為 `/member/classroom/${course.id}`，其餘 class 與 `target`/`rel` 不動 in resources/js/Pages/Admin/Courses/Index.vue
- [x] T00G3 驗證：`npm run build` exit 0；手動確認 admin 點課程名開銷售頁、點「教室預覽」直接進教室不被導到 ClassroomUnauthorized

### 課程 Roadmap 編輯（US7，FR-019~FR-023）

**Phase A — 後端**

- [x] T00H1 三支 migration：`course_roadmap_stages`、`course_roadmap_checkpoints`（皆 FK cascade + (parent, sort_order) index）、`courses.roadmap_title` in database/migrations/2026_09_23_00000{1,2,3}_*.php
- [x] T00H2 [P] `CourseRoadmapStage`（belongsTo Course、hasMany checkpoints、`$fillable`、預設 orderBy sort_order 的 relation）與 `CourseRoadmapCheckpoint`（belongsTo stage、hasMany completions〔003 model〕）in app/Models/CourseRoadmapStage.php, app/Models/CourseRoadmapCheckpoint.php
- [x] T00H3 [P] `Course::roadmapStages()` hasMany + orderBy sort_order；`Course::hasRoadmap()` = `roadmapStages()->exists()` in app/Models/Course.php
- [x] T00H4 `CourseRoadmapRequest`：`roadmap_title` nullable|string|max:100；`stages` array；`stages.*.id` nullable|integer；`stages.*.title` required|string|max:200；`stages.*.description_md` nullable|string|max:5000；`stages.*.checkpoints` array；`stages.*.checkpoints.*.id` nullable|integer；`stages.*.checkpoints.*.label` required|string|max:500；全部附中文 messages（FR-023 比照 003 FR-023 的要求：錯誤必須看得見）in app/Http/Requests/Admin/CourseRoadmapRequest.php
- [x] T00H5 `CourseRoadmapService::sync(Course $course, array $data): void` — 單一 transaction：(1) 驗所有帶 id 的階段屬於本 course、檢核項目屬於該階段，不符丟 ValidationException（FR-021）；(2) 依陣列位置 upsert 階段與檢核項目並重寫 sort_order（FR-022）；(3) 刪除 payload 中缺席的既有列（cascade 帶走完成紀錄）；(4) 更新 `courses.roadmap_title` in app/Services/CourseRoadmapService.php
- [x] T00H6 `Admin\CourseRoadmapController`：`edit(Course)` 回 Inertia `Admin/Courses/Roadmap`（帶 course 基本資料 + 階段樹，每個 checkpoint 附 `completed_count` 供 D23 的刪除警告）、`update(CourseRoadmapRequest, Course)` 呼叫 Service 後 redirect back with flash；兩條路由掛既有 admin 群組 in app/Http/Controllers/Admin/CourseRoadmapController.php, routes/web.php〔touchpoint 000〕

**Phase B — 前端**（T00H6 完成後）

- [x] T00H7 `Admin/Courses/Roadmap.vue`：Roadmap 標題輸入、階段卡片清單（vuedraggable）、sticky 儲存列、錯誤清單（比照 CourseForm 的 D12 作法）、刪除前的完成紀錄警告 in resources/js/Pages/Admin/Courses/Roadmap.vue
- [x] T00H8 [P] `RoadmapStageCard.vue`：單張階段卡（標題、Markdown 說明 textarea、檢核項目 vuedraggable 清單、Enter 新增下一項、逐項刪除）in resources/js/Components/Admin/RoadmapStageCard.vue
- [x] T00H9 [P] Markdown 匯入貼上框與解析器（`## ` → 階段標題、`- [ ]`／`- [x]` → 檢核項目、其餘非空行 → 該階段 description_md；忽略 `↓` 之類的分隔符），匯入前跳「將取代目前 N 個階段、M 筆學員完成紀錄」確認 in resources/js/Pages/Admin/Courses/Roadmap.vue
- [x] T00H10 [P] 課程列表操作欄加「Roadmap」連結（比照「章節」配色）in resources/js/Pages/Admin/Courses/Index.vue

**Phase C — 驗證**

- [x] T00H11 測試（TDD，先紅）：(a) 儲存既有階段時 id 保留、學員完成紀錄存活；(b) payload 移除某檢核項目 → 該列與其完成紀錄一起消失；(c) 帶別門課的 stage id → 422；(d) sort_order 由陣列位置重寫、不信前端值 in tests/Feature/Admin/CourseRoadmapTest.php
- [x] T00H12 `php artisan test` 全綠 + `npm run build` exit 0

### 小節通知補寄與寄送回報（US6 追加，FR-024~FR-028）

**Phase A — 後端**

- [x] T00I1 migration：`lessons.notified_at` datetime nullable（不回填既有列）in database/migrations/2026_09_27_000001_add_notified_at_to_lessons_table.php
- [x] T00I2 `LessonNotificationService`（D25）三個方法：`recipients(Lesson): Collection`（把現行 `store()` 內那段 Purchase query + `accessibleLessonIds()` 過濾整段搬進來，eager load `user`、`plan.lessons:id`）、`notify(Lesson): int`（逐封 `Mail::send(new LessonAddedNotification(...))`，單封失敗只記 log 續跑，結束後寫 `notified_at = now()` 並回成功封數）、`notifiableCounts(Course): array`（單一 `groupBy('course_plan_id')` 查合格購買數，回 `['no_plan' => n, 'plans' => [id => n]]`）in app/Services/LessonNotificationService.php
- [x] T00I3 `LessonController::store()` 改呼叫 Service：保留「已發布 ∧ 非 drip」的二次判斷，`$sent = $service->notify($lesson)`，flash 依 FR-026 組字串（勾了通知才附人數；0 封時依 `notifiableCounts` 判斷原因是「尚未歸屬任何方案」還是「尚無合格學員」）in app/Http/Controllers/Admin/LessonController.php
- [x] T00I4 `LessonController::update()` 加上同一段通知處理，並把 `$request->validated()` 改為 `$request->safe()->except(['notify_members'])`（D27）in app/Http/Controllers/Admin/LessonController.php
- [x] T00I5 `ChapterController::index()` payload 追加：每個 lesson 的 `notified_at`（`->timezone('Asia/Taipei')` 後輸出，或直接 ISO 交前端）與頁層級 `notifiableCounts`（FR-027，單一查詢、不逐小節算）in app/Http/Controllers/Admin/ChapterController.php

**Phase B — 前端**（T00I5 完成後）

- [x] T00I6 `LessonForm.vue`：(a) 通知區塊的 `v-if` 移除 `!isEditing`，只留 `courseStatus !== 'draft' && courseType !== 'drip'`；(b) 以 props 傳入的 `notifiableCounts` + 目前 `plan_ids` 算出預計人數並常駐顯示，0 人時警示樣式 + disable 勾選框 + 明寫原因；(c) 編輯且 `notified_at` 有值時顯示台北時間並改用「再次發送（收過的人會再收到）」文案（FR-028）in resources/js/Components/Admin/LessonForm.vue
- [x] T00I7 `Chapters.vue`：把 `notifiableCounts` 與小節的 `notified_at` 往下傳給 LessonForm；`plan_ids` 已在 payload 內（011 US21），沿用不動 in resources/js/Pages/Admin/Courses/Chapters.vue, resources/js/Components/Admin/ChapterList.vue

**Phase C — 驗證**

- [x] T00I8 測試（TDD，先紅）：(a) 多方案課建立小節勾通知 → 綁方案學員 0 封、flash 帶 0 與原因（鎖住今天的行為並讓它可見）；(b) 把小節加進方案 A 後編輯勾通知 → 方案 A 學員收到、方案 B 學員沒收到、`notified_at` 落值；(c) 無方案課程建立時通知照舊全寄（回歸防線）；(d) refunded / system_assigned 一律不收；(e) 單封 Mail 失敗不中斷後續且不影響儲存 in tests/Feature/Admin/LessonNotificationTest.php
- [x] T00I9 `php artisan test` 全綠 + `npm run build` exit 0
- [ ] T00I10 **部署後在正式站驗**（本機 DB 無多方案課資料，2026-09-27 決定跳過本機實走）：拿一門**沒有學員**的多方案課走「建立小節（確認勾不下去且寫出 0 位原因）→ 設方案歸屬 → 編輯勾通知 → 重開確認顯示台北時間與二次確認文案」。**注意：正式站勾下去是真的寄信給真學員**，不要拿有學員的課試

## 進度日誌

- 2026-09-27: 實作 US6 追加 T00I1~T00I8 — `lessons.notified_at`（datetime、不進 `$fillable`、有 datetime cast）、`LessonNotificationService`（`recipients` / `notify` / `notifiableCounts` / `emptyReason`，收件人規則從 LessonController 搬出成單一真相）、`store()` 與 `update()` 共用 `notifyAndDescribe()` 把實際寄出封數與 0 封原因寫進 flash、`ChapterController@index` payload 加 `notifiableCounts` 與各小節台北時區的 `notified_at`、LessonForm 通知區塊改為建立/編輯都顯示並帶預計人數（0 人時 disable + 警示原因）與已寄過的二次確認文案。`LessonNotificationTest` 9 例綠（含故意弄壞 no_plan 計數、方案過濾、時區轉換各一次確認會紅），`php artisan test` 1108 passed、`npm run build` exit 0。手動實走改為部署後在正式站進行（T00I10，本機 DB 沒有多方案課資料）。

- 2026-09-27: /spec 規劃「小節通知補寄與寄送回報」（US6 追加，FR-024~FR-028、D24~D27、T00I1~T00I9）— 根因是多方案課建立小節時 `course_plan_lesson` 必為空，`store()` 的方案過濾把所有綁方案的學員濾掉，導致靜默 0 寄送且無補救路徑。解法：抽 `LessonNotificationService` 統一收件人規則、編輯 Modal 也能勾通知（`lessons.notified_at` + 二次確認防手滑）、flash 回報實際封數、勾選框旁前端加總預覽人數。status: draft 待審。

- 2026-09-25: `lesson-added.blade.php` 的署名改讀 `SiteSetting::siteName()`，不再寫死品牌字串（000 US12）。

- 2026-09-25: 修 `sale_at` / `promo_ends_at` 的 8 小時偏差 — 原本 datetime-local 的裸字串被當 UTC 直接存，admin 設 09:00 開賣實際是台北 17:00；改走 000 的 `NormalizesTaipeiInput`，列表/編輯頁輸出補 `->timezone('Asia/Taipei')`。另修 `CourseSeeder`（portaly_url 已 drop）與 `LessonSeeder`（html_content → content_md，範例內容改寫為 Markdown），`migrate:fresh --seed` 自今年一月起即無法執行。移除沒人讀的 `portalyUrl` accessor 與 prop（前端自行以 portaly_product_id 組網址）

- 2026-09-23: 實作 US7 T00H1~T00H12 — 三張 migration（stages / checkpoints / courses.roadmap_title）、CourseRoadmapStage/Checkpoint model、Course::roadmapStages + hasRoadmap、CourseRoadmapRequest、CourseRoadmapService::sync（id 保留式 diff 儲存，sort_order 由陣列位置重寫）、Admin\CourseRoadmapController、Roadmap.vue 編輯頁（拖曳排序 + Markdown 匯入 + 刪除前完成紀錄警告）、課程列表 Roadmap 入口。CourseRoadmapTest 4 例綠（含「編輯不得清空學員進度」的防線），php artisan test 966 passed、npm run build exit 0。
- 2026-09-23: /spec 規劃「課程 Roadmap 編輯」US7（FR-019~023、D19~D23、T00H1~T00H12）— 後台逐課程自訂縱向階段里程碑與自我檢核清單，正規化兩張表 + id 保留式整份儲存，附 Markdown 匯入。教室端顯示與學員勾選見 003 US11。status: draft 待審。
- 2026-09-23: 修正「教室預覽」連結 404 — 教室路由掛在 `Route::prefix('member')` 群組下，實際路徑是 `/member/classroom/{id}`，原本寫成 `/classroom/{id}`。與 slug 無關（`resolveRouteBinding` 同時吃 slug 與 id）。新增 AdminCourseListLinksTest 3 例鎖住兩個入口網址可達。
- 2026-09-23: 實作 T00G1~T00G3 — 後台課程列表的課程名稱改為銷售頁連結（新分頁 + hover 變色），操作欄「銷售頁」改名「教室預覽」並改指 `/classroom/{id}`（admin 走 hasAccessForUser bypass，無後端改動）。npm run build exit 0、php artisan test 953 passed。
- 2026-09-23: /spec 規劃「後台課程列表前台入口調整」（FR-018、D16~D18、T00G1~T00G3）— 課程名稱加銷售頁連結、原「銷售頁」改為「教室預覽」指向 `/classroom/{id}`。純前端單檔變更。2026-09-23 使用者確認「教室預覽」走 /classroom/{id}（D16 A 案），status: building。
- 2026-08-04: 移除 `CourseController@subscribers` 與課程編輯頁的「訂閱者」按鈕（011 US8 touchpoint）— drip 訂閱者名單改由 Leads 名單頁的 tab 承載，本模組不再持有 drip 的資料組裝邏輯。
- 2026-08-02: `Course::getRouteKey()` 改回傳 slug（無 slug 才用 id），修正全站由模型產生的課程網址；Gallery 頁麵包屑課程名補連結（D15）。
- 2026-08-02: 課程表單「銷售頁促銷區塊」新增輪換折扣碼插入器、`Admin\CourseController`／`ChapterController` 改注入 `CouponChainService` 取清單、Create/Edit 下傳 `couponChains`；插入 UI 抽成 006 owner 的 `CouponChainInserter.vue`，`LessonForm`／`CourseForm` 共用 — 由 006-coupons 以 touchpoint 身分修改，規則見 006 US5／D9。
- 2026-08-01: LessonForm 的 Markdown 內容欄新增 drip 專屬說明區塊（信件固定格式：開頭問候、結尾退訂）— 由 010-drip-email 以 touchpoint 身分修改，規則與驗收記在 010 US8。
- 2026-08-01: 後台「課程描述」說明文字改為「顯示於銷售頁影片下方的前言區塊，支援 Markdown；完整介紹請寫在下方「課程介紹」」（原文誤稱顯示於課程卡片，卡片實際顯示 tagline）；渲染端變更見 002-storefront。
- 2026-08-01: /sync 對帳 — 本批 6 個 code 檔全屬 004，spec 已於 /dev 同步；補記實作與規劃的一處差異：錯誤計數改置於紅色錯誤面板標題，sticky 列文案改為「請修正上方列出的欄位」。
- 2026-08-01: 實作 T00F1~T00F8 — Store/Update 欄位契約對齊（補 course_type/drip_interval_days/target_course_ids/high_ticket_hide_price 規則）、price 改 required_unless:course_type,drip + prepareForValidation 補 0、store() 寫入 drip_conversion_targets、抽 availableTargetCourses() 供 create()/edit() 共用、Create.vue 下傳 availableCourses、sticky 列改常駐錯誤清單（可點擊捲動、隱藏欄位也看得到）；新增 CourseCreateFieldsTest 4 例，php artisan test 207 passed、npm run build exit 0。

- 2026-08-01: /spec 規劃「修復新增課程流程與隱形驗證錯誤」（FR-015~017、D12~D14、T00F1~T00F8），status: draft 待審。根因查證：`Create.vue` 未宣告/下傳 `availableCourses` → 目標商品永遠空；`StoreCourseRequest` 缺 `course_type`/`drip_interval_days`/`target_course_ids`/`high_ticket_hide_price` 四條規則 → 新增頁選連鎖或勾隱藏價格被 `validated()` 靜默丟棄、`store()` 也沒寫 `drip_conversion_targets`；`price` required 但 drip 定價卡被 `v-if` 移除 → `data-field="price"` 不在 DOM，`scrollToFirstError` 靜默 return，形成「有 1 個欄位需要修正但找不到紅字」。
- 2026-08-01: 課程表單「是否顯示於首頁」加註「（Landing Page 模式）」，說明文字補上關閉後銷售頁會隱藏導覽列與麵包屑（純文案，行為未變；對應行為的正典描述見 002 US2）。
- 2026-08-01: 產品類別新增「電子書」ebook（FR-013 的 8 個同步點全數更新）。migration 改用 Schema::change() 帶完整值列表，順帶補上 sqlite 測試 DB 缺失的 high_ticket（011 D12 的限制解除，高價課終於能在測試落庫）；另修 CourseCard 標籤表原本連 high_ticket 都沒有、卡片會顯示原始英文值。新增 CourseTypeTest（4 tests），203 passed、npm build 綠。
- 2026-07-20: 修相簿批次上傳 bug（一次多張常失敗）— 根因是前端把整包檔案塞進單一 POST，多張合計超過 PHP `post_max_size(8M)` → PHP 丟棄整個 body → 驗證回「請選擇至少一張圖片」。改為 Gallery.vue「一檔一請求」序列上傳（含 N/總數 進度、逐張失敗回報），每請求僅 1 張，遠低於 8M 上限，故不需調整伺服器 PHP 設定。同步把單張上限從 10MB 校正為實際的 2MB（UI 文案 + `store`/`batchStore` 驗證 `max:2048`），與 `upload_max_filesize=2M` 一致。新增 CourseImageBatchUploadTest（單張/多張/非圖片）3 綠。
- 2026-07-11: CourseForm 金流未設定警告的連結標籤「金流設定」→「API 設定」，對齊後台頁面改名（純文案）。
- 2026-07-11: /spec 規劃課程表單 UX 重構（tabs → 單頁分區卡片 + sticky 儲存列），status: draft 待審
- 2026-07-11: 課程縮圖上傳區標註為「課程縮圖／主視覺 Banner」並加建議尺寸說明（1920×1080 16:9、主體置中，因銷售頁滿版 object-cover 會裁上下）。
- 2026-07-11: 課程管理列表新增搜尋（名稱／講師）＋內容分類／課程類型（講座/迷你/客製）篩選按鈕（前端即時過濾；index payload 補 content_category、product_type、contentCategories）。
- 2026-07-06: 領域重組 — 自 002(後台)+008(課程類別) 重寫，依實際 codebase 校正

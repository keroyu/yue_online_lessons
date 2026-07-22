---
id: 003-classroom
status: done
owner_files:
  - app/Http/Controllers/Member/ClassroomController.php
  - app/Http/Controllers/Member/LearningController.php
  - app/Http/Controllers/Member/AssignmentCommentController.php
  - app/Http/Controllers/Member/NotificationController.php
  - app/Http/Controllers/Admin/HomeworkController.php
  - app/Http/Requests/Member/StoreCommentRequest.php
  - app/Http/Requests/Admin/AssignmentRequest.php
  - app/Models/LessonProgress.php
  - app/Models/Assignment.php
  - app/Models/AssignmentCompletion.php
  - app/Models/Comment.php
  - app/Models/HomeworkNotification.php
  - app/Services/AssignmentService.php
  - app/Services/VideoEmbedService.php
  - app/Services/CloudflareStreamService.php
  - database/migrations/2026_01_17_000004_create_lesson_progress_table.php
  - database/migrations/2026_07_15_000002_change_video_platform_to_string_on_lessons.php
  - tests/Feature/Classroom/CloudflareStreamTest.php
  - database/migrations/2026_05_10_000002_create_assignments_table.php
  - database/migrations/2026_05_10_000003_create_comments_table.php
  - database/migrations/2026_05_10_000004_create_assignment_completions_table.php
  - database/migrations/2026_05_10_000005_create_homework_notifications_table.php
  - resources/js/composables/useNotifications.js
  - resources/js/Components/Classroom/AssignmentSection.vue
  - resources/js/Components/Classroom/ChapterSidebar.vue
  - resources/js/Components/Classroom/CommentThread.vue
  - resources/js/Components/Classroom/HtmlContent.vue
  - resources/js/Components/Classroom/LessonItem.vue
  - resources/js/Components/Classroom/LessonPromoBlock.vue
  - resources/js/Components/Classroom/VideoAccessNotice.vue
  - resources/js/Components/Classroom/VideoPlayer.vue
  - resources/js/Components/MyCourseCard.vue
  - resources/js/Pages/Member/Classroom.vue
  - resources/js/Pages/Member/ClassroomUnauthorized.vue
  - resources/js/Pages/Member/Learning.vue
  - resources/js/Pages/Admin/Homework/Index.vue
touchpoints:
  - file: app/Services/PointService.php
    owner: 007-points-referral
    why: AssignmentService 標記作業完成時經積分帳本發放（type=earn_homework），PointService 為 users.points 唯一寫入點
  - file: app/Models/Course.php
    owner: 004-course-admin
    why: hasAccessForUser() 為上課權限唯一判斷入口（admin / 付費購買 / drip 訂閱）；教室讀取章節結構
  - file: app/Models/Chapter.php
    owner: 004-course-admin
    why: 教室側欄以 Chapter sort_order 分組顯示小節
  - file: app/Models/Lesson.php
    owner: 004-course-admin
    why: 教室讀取小節影片欄位（video_platform/video_id/embed_url）、md_content、is_preview、duration_seconds
  - file: app/Models/Purchase.php
    owner: 005-checkout
    why: paidStatus scope 決定「我的課程」列表與付費上課權限
  - file: app/Services/CouponChainService.php
    owner: 006-coupons
    why: 教室 promo_html 中折扣碼佔位符替換（substitutePlaceholders）
  - file: app/Services/DripService.php
    owner: 010-drip-email
    why: drip 課程在教室內的小節解鎖判斷與影片限時觀看（isLessonUnlocked / isVideoAccessExpired）
  - file: app/Http/Middleware/HandleInertiaRequests.php
    owner: 000-platform-core
    why: 全站 Inertia shared props 提供通知鈴鐺資料（notificationCount、notifications 最新 5 則與文案）
  - file: app/Http/Requests/Admin/StoreLessonRequest.php
    owner: 004-course-admin
    why: video_url 驗證需接受 Cloudflare Stream 連結/UID（url 規則放寬為 string + VideoEmbedService::isValid）
  - file: resources/js/Components/Admin/LessonForm.vue
    owner: 004-course-admin
    why: 前端影片連結偵測提示需辨識 Cloudflare Stream 格式
  - file: config/services.php
    owner: 000-platform-core
    why: 新增 cloudflare_stream 設定區塊（customer_code、簽名 key、token TTL）
  - file: app/Services/PostService.php
    owner: 012-newsletter
    why: 行為接點（不改碼）— PostService 呼叫 VideoEmbedService::parse 嵌入文章影片，parse 新認得 Cloudflare URL；開 requireSignedURLs 的影片在部落格嵌入不可播（已知限制）
---

# Classroom（會員教室）

## 目標

讓已購買（或 drip 訂閱）的會員完成「找到課程 → 看影片/讀內文 → 記錄進度 → 交作業 → 收到批改與積分」的完整學習閉環；同時提供管理員批改後台與未購買者的免費試閱入口。

## User Stories

### User Story 1 - 我的課程頁面 (Priority: P1)

已登入會員進入 `/member/learning` 查看所有已購課程卡片（縮圖、課程名、教師名、完成百分比進度條），點擊卡片進入教室。

**驗收**：
- [x] 已登入且有付費購買紀錄（`Purchase::paidStatus()`，依購買時間新→舊）時，顯示課程卡片列表（桌機最多 2 欄，每張約 500px 寬）
- [x] 每張卡片顯示課程進度百分比與進度條（`User::getCourseProgressSummary()`：已完成小節數 / 全部小節數；無小節課程顯示 0%）
- [x] 無任何課程時顯示「尚無課程」提示並引導至首頁
- [x] 未登入時顯示 client-side「請先登入」防護提示

### User Story 2 - 教室上課與影片播放 (Priority: P1)

已購會員進入 `/member/classroom/{course}`，左欄章節側欄、右欄影片播放器或 Markdown 內文（Teachable 風格），切換小節不整頁刷新。

**驗收**：
- [x] 無上課權限（`Course::hasAccessForUser()` 為 false）時渲染 `ClassroomUnauthorized`，訊息依課程型態顯示「您尚未購買此課程」/「您尚未訂閱此課程」（drip）
- [x] 進入頁面預設小節：URL `?lesson_id` 指定者優先，否則第一個未完成小節，再否則第一個小節
- [x] 側欄初始只展開「目前進度小節」所屬章節，其餘章節折疊；小節不屬於任何章節時展開第一章；初始折疊狀態僅於載入時計算一次，之後手動展開/收合不受干擾（`ChapterSidebar.buildInitialCollapsed()`）
- [x] 點擊小節以 Inertia partial reload（`only: ['currentLesson']`）切換右側內容，preserveState/preserveScroll，手機自動關閉側欄
- [x] 支援 Vimeo / YouTube 嵌入自動播放；Vimeo 加 `texttrack=zh-TW` 自動顯示繁中字幕；YouTube 切換小節用 `loadVideoById()`（不重建 player，避免卡住）
- [x] 影片自然播放完畢：目前小節立即標記完成（樂觀更新 + 即時 POST，跳過節流計時器）並自動切至扁平順序（章節內小節 → 獨立小節）的下一小節；最後一小節不跳轉
- [x] 無影片小節顯示 Markdown 內文（`HtmlContent`，含響應式 iframe 與表格樣式）
- [x] 側欄可收合：桌機漢堡鈕 + 右緣細長 toggle tab（width slide 動畫）、手機 translate slide + 遮罩
- [x] drip 課程：未解鎖小節不出現在側欄、直接帶 `?lesson_id` 也會被擋；限時觀看到期顯示 `VideoAccessNotice`、promo 區塊 `LessonPromoBlock`（細節歸 010-drip-email）
- [x] 課程無任何小節時顯示「課程內容準備中」；頁面回應帶 `Cache-Control: no-store`

### User Story 3 - 學習進度紀錄與節流 (Priority: P1)

會員停留在小節達門檻時間後，系統才將完成紀錄寫入伺服器；前端先樂觀顯示綠勾。防止快速點擊灌進度，也保留手動標記的自由。

**驗收**：
- [x] 點擊小節後左欄立即顯示綠勾（樂觀更新，`localCompletedLessons` Set）
- [x] 完成門檻 = 影片時長的 75%（`duration_seconds * 0.75`）；小節無時長時 fallback 2 分鐘（`getCompletionThresholdMs()`）
- [x] 門檻時間內切換到其他小節：取消原小節計時器並移除樂觀綠勾，不寫入伺服器；刷新頁面後回復伺服器真實狀態
- [x] 達門檻後前端以 fetch `POST /member/classroom/{course}/progress/{lesson}` 寫入（JSON API，非 Inertia），`LessonProgress::firstOrCreate` 冪等
- [x] 手動點擊灰色圖示標記完成：立即 POST，不等計時器
- [x] 點擊綠勾取消完成：立即 `DELETE`，同時取消 pending 計時器
- [x] 影片播完自動完成：立即 POST（見 US2）
- [x] 伺服器端驗證：lesson 必須屬於該 course（404）、使用者必須有上課權限（403）
- [x] 元件 unmount 時清除所有計時器（關頁不誤寫）

### User Story 4 - 免費試閱教室 (Priority: P2)

訪客不需登入即可從販售頁進入 `/course/{course}/preview`，只能觀看標記 `is_preview` 的小節，其餘顯示鎖頭，頁內有購買 CTA。

**驗收**：
- [x] 公開路由，不需登入；drip 課程一律 404
- [x] 課程無任何 `is_preview` 小節時渲染 `ClassroomUnauthorized`，訊息「此課程目前沒有免費試閱內容」（非 404）
- [x] 側欄顯示全部章節小節，但僅 `is_preview = true` 可點擊播放，其餘鎖頭不可點；`?lesson_id` 指向非試閱小節時忽略、退回第一個試閱小節
- [x] 試閱模式不記錄任何進度（無綠勾、無計時器、影片播完不自動完成），作業區不顯示
- [x] 影片播完的自動跳轉在試閱模式下由 `handleSelectLesson` 的鎖定守衛自然擋下（下一小節非試閱則不切換）
- [x] 頁面顯示試閱提示與「立即購買完整課程」CTA（course.tagline + sales_url 連回販售頁）

### User Story 5 - 學員提交與管理作業留言 (Priority: P1)

有作業題目的小節，影片正下方（課程內文之前）顯示作業卡片；學員以 Markdown 提交作業，可編輯/刪除自己的留言，只看得到自己的提交。

**驗收**：
- [x] 小節有已上架題目（`assignment()->published()`）時顯示作業卡片：題目 Markdown 渲染，討論串與輸入框預設收合（「展開回答」按鈕）
- [x] 提交走 `POST /member/classroom/{course}/assignment/{assignment}/comments`，成功後 Inertia partial reload 即時顯示，輸入框清空
- [x] 學員前端提交一律為頂層留言（`parent_id: null` 白色氣泡）；講師回覆為第二層（teal 氣泡 +「講師」標籤，左縮排）
- [x] 兩層巢狀上限由 `StoreCommentRequest` 後端驗證：parent 必須是頂層留言且屬於同一 assignment
- [x] 只回傳目前使用者本人的留言（時間正序，舊→新）；學員 A 看不到學員 B 的提交
- [x] 學員可編輯（顯示「已編輯」標記）/刪除自己的留言；刪除頂層連同子回覆 cascade 刪除，僅本人可操作（`Comment::isOwnedBy` 403 防護）
- [x] 提交防護：無課程權限 403、assignment 不屬於該課程 404、題目已下架 403「不接受新提交」
- [x] 試閱模式與未購買者看不到作業區；輸入框 placeholder 提示 Markdown 寫法（### 起頭、# 後加空格）

### User Story 6 - 管理員作業題目管理與批改 (Priority: P1)

管理員在後台 `/admin/homework` 為任意小節建立/編輯題目（Markdown，一節最多一題），瀏覽全部學員提交並逐筆回覆批改。

**驗收**：
- [x] 題目 CRUD：`POST /admin/lessons/{lesson}/assignment` 建立、`PUT /admin/homework/{assignment}` 更新（`md_content` 上限 50000 字）；題目管理表格依 Chapter → Lesson sort_order 章節分組
- [x] 題目只能「下架/上架」（`publish`/`unpublish` 切換 `is_published`），不提供永久刪除；下架後前台完全不顯示、資料保留、可重新上架
- [x] 提交列表：預設全部課程最新提交倒序，`paginate(10)`；可依課程、小節篩選，另支援 `search`（email/nickname LIKE，300ms debounce），條件 AND 疊加、互不清空
- [x] 作業題目管理的課程選單獨立於提交列表篩選（各自 URL 參數：`course_id` / `manage_course_id`）：列出全部課程（含尚無題目者，供補第一題）、依最新題目建立時間降序、無題目課程置底；未指定時預設選定「最新新增題目」的課程
- [x] 折疊式列表（點標題列展開詳情）+ 右側滑入回覆面板（Escape/overlay 關閉）；已有回覆的提交顯示「已回覆」淺藍標記
- [x] 回覆批改 `POST /admin/homework/{assignment}/comments`（parent_id 必填 = 學員頂層留言），成功後自動建立 reply 通知給該學員本人
- [x] 管理員可編輯/刪除任何人的留言（updateComment / destroyComment）
- [x] 「預覽」按鈕開新分頁 `/member/classroom/{course}?lesson_id=X&preview_user_id={student}`：僅 admin 帶此參數時，教室以該學員 ID 查詢作業留言與完成狀態（學員視角）；非 admin 帶參數直接忽略
- [x] 所有後台寫入操作走 Inertia partial reload，篩選狀態與滾動位置保留

### User Story 7 - 作業完成標記與積分發放 (Priority: P2)

管理員對某學員某道題按「標記已完成」，系統發放積分（後台可設定，預設 100）、建立完成通知，教室作業卡顯示綠色勳章。

**驗收**：
- [x] `POST /admin/homework/{assignment}/completions/{user}`：同一 (assignment, user) 只能標記一次，重複標記後端拒絕（先查 exists，DB 亦有 unique 約束），積分不重複累計
- [x] 積分值取 `SiteSetting('homework_reward_points', 100)`，經 `PointService::award(user, points, 'earn_homework', 'assignment', id)` 走帳本發放（本模組不直接寫 users.points）
- [x] 建立 completion + 發積分 + 建立完成通知包在同一 DB transaction
- [x] 已標記的學員區塊顯示「✓ 已完成」，標記按鈕消失
- [x] 學員教室進入該小節時，作業卡片顯示綠色打勾勳章（`assignment.is_completed`）
- [x] 題目事後下架不影響已發放積分與完成紀錄（資料保留於後台）

### User Story 8 - 作業通知鈴鐺 (Priority: P2)

登入者在導覽列與教室 header 看到通知鈴鐺；講師批改或標記完成時產生站內通知，點擊跳轉至對應小節並標為已讀。

**驗收**：
- [x] 講師回覆 → type=reply 通知「老師已批改《課程名》的作業」；標記完成 → type=completion 通知「《課程名》作業已完成，積分 +100」；只發給被批改的學員本人
- [x] 通知資料由 `HandleInertiaRequests` 全站 shared props 提供：`notificationCount`（未讀數）與 `notifications`（最新 5 則）；`useNotifications` composable 以模組級 singleton 狀態同步各頁鈴鐺
- [x] 有未讀時鈴鐺顯示紅點與數字；點擊展開最近 5 則；無通知顯示「目前沒有通知」
- [x] 點擊通知：未讀者先 `POST /member/notifications/{id}/read`（僅本人可標記，403 防護）再跳轉 `/member/classroom/{course_id}?lesson_id={lesson_id}`；已讀者直接跳轉
- [x] 通知跳轉不檢查題目上架狀態：一律跳至小節，作業區是否顯示由頁面自身邏輯決定
- [x] 教室頁自有 header 內建鈴鐺（與主導覽列行為一致）；試閱模式不顯示；所有登入角色（含 admin）皆可見鈴鐺

### User Story 9 - Cloudflare Stream 影音來源 (Priority: P2)

管理員上架小節影片時，除 Vimeo / YouTube 外可貼 Cloudflare Stream 連結或影片 UID（影片先在 Cloudflare Dashboard 上傳，用量計費）；會員教室以 Signed URL 限時 token 播放，防止 embed 連結外流白嫖流量。三種來源並行，隨時可換。

**驗收**：
- [x] `VideoEmbedService::parse()` 認得 Cloudflare Stream 格式：`customer-{code}.cloudflarestream.com/{uid}/watch|iframe`、`watch.cloudflarestream.com/{uid}`、`videodelivery.net/{uid}`、裸 32 位 hex UID，回傳 `platform: cloudflare` + `video_id`（UID）
- [x] 後台小節表單貼上任一 Cloudflare 格式即時顯示「已偵測到 Cloudflare Stream 影片」提示；Vimeo/YouTube 行為不變（input type url→text，原生驗證不擋裸 UID）
- [x] `StoreLessonRequest` 接受裸 UID（`url` 規則放寬為 `string`），無效格式錯誤訊息更新為含 Cloudflare Stream
- [x] 教室播放：`platform = cloudflare` 的小節，後端於 render 時以 signing key 產生限時 JWT token，`embed_url` 為 `https://customer-{code}.cloudflarestream.com/{token}/iframe`；token 不落地 DB
- [x] `VideoPlayer.vue` 支援 cloudflare 分支：iframe 嵌入 + Stream Player SDK（`embed.cloudflarestream.com/embed/sdk.latest.js`，全域載一次）監聽 `ended` 自動跳下一小節，autoplay 行為與 Vimeo/YouTube 一致（瀏覽器實測待使用者環境有 Stream 影片後進行）
- [x] 未設定 signing key（本機開發）時 fallback 為未簽名 UID embed URL，不噴錯
- [x] drip 限時觀看、試閱、進度紀錄等既有邏輯對 cloudflare 小節一體適用（`embedUrlFor()` 統一入口，僅 embed_url 產生方式不同）
- [x] Feature 測試：parse 各格式、簽名 URL 產生（含 exp claim）、StoreLessonRequest 驗證

## Requirements

- **FR-001**: 上課權限唯一判斷入口為 `Course::hasAccessForUser()`：admin 恆通過、付費購買（paidStatus）通過、drip 訂閱通過；退款（refunded）即失去權限
- **FR-002**: 進度完成門檻 = 影片時長 75%，無時長 fallback 2 分鐘；手動標記、取消、影片播完三者不受門檻限制（立即寫入）
- **FR-003**: 進度 API 為 JSON fetch（非 Inertia），避免打斷影片播放；寫入冪等（firstOrCreate / delete）
- **FR-004**: 留言巢狀最多 2 層；學員端一律建立頂層留言，第二層僅由後台批改產生；後端 `StoreCommentRequest` 強制驗證
- **FR-005**: 學員只能讀取/編輯/刪除本人的留言；跨學員存取一律 403，完全隔離
- **FR-006**: 作業題目每小節最多一題（`assignments.lesson_id` unique）；只有下架、沒有刪除
- **FR-007**: 完成標記每 (assignment, user) 唯一；積分只增不減，本模組無撤銷機制
- **FR-008**: 積分發放一律經 PointService 帳本，金額由 `homework_reward_points` 站台設定控制（預設 100）
- **FR-009**: `preview_user_id` 參數僅 admin 生效（學員視角預覽）；非 admin 靜默忽略，不構成資料洩漏
- **FR-010**: 試閱教室不寫任何 `lesson_progress`，不顯示作業區；drip 課程無試閱（404）
- **FR-011**: 影片連結解析由 `VideoEmbedService` 統一處理（Vimeo / YouTube / Cloudflare Stream URL → platform + video_id + embed_url），格式錯誤回 null
- **FR-012**: Cloudflare Stream 播放 token 一律由後端 `CloudflareStreamService` 於教室 render 時產生（RS256 JWT，TTL 預設 12 小時、config 可調）；token 不寫入 DB、不出現在後台表單，僅存 UID
- **FR-013**: 簽名憑證（key id + private key PEM）存 `.env`，經 `config/services.php` 讀取；未設定時 degrade 為未簽名 embed URL（開發模式），不阻擋頁面

## 設計決策

- **D1**: 完成門檻從固定 2 分鐘改為「影片時長 75%」— 固定門檻對長影片太寬鬆、對短影片太嚴格；無時長資料時保留 2 分鐘 fallback
- **D2**: 進度寫入用原生 fetch 而非 Inertia visit — 計時器到期時不能打斷正在播放的影片或觸發頁面狀態變化
- **D3**: 切換小節用 Inertia partial reload（`only: ['currentLesson']`）— 側欄與播放器狀態保留，只換右側內容；`selectedLesson` 以 `watch(props.currentLesson)` 同步
- **D4**: 題目「下架」而非刪除 — 學員提交與批改紀錄是資產，永久保留；前台以 `published()` scope 過濾
- **D5**: 通知用自建 `homework_notifications` 輕量表而非 Laravel Notifications — 只需鈴鐺 5 則展示 + 已讀標記，冗餘存 `course_name` 免 join；通知文案在 HandleInertiaRequests 組裝
- **D6**: 側欄初始折疊狀態只在 setup 計算一次 — 之後 lesson 切換不重算，避免自動跳下一小節時側欄突然折疊、干擾使用者手動展開的狀態
- **D7**: 積分發放從 hardcode +100 改為 SiteSetting + PointService 帳本（012 積分系統重構）— 保證 users.points 單一寫入點與交易紀錄可稽核
- **D8**: Cloudflare Stream 採「Dashboard 上傳 + 貼 URL/UID」而非站內直傳 — 與 Vimeo/YouTube 既有上架流程一致、零 API 整合成本；tus 直傳（需 API token、進度 UI、轉檔輪詢）留待未來獨立 US
- **D9**: 播放保護採本地簽 JWT（RS256, openssl 手寫 ~20 行）而非每次呼叫 Stream `/token` API — 播放不產生外部 API 呼叫與延遲；不新增 composer 依賴（firebase/php-jwt 被否決：單一用途不值得引入套件）。簽名 key 用戶以 `POST /accounts/{id}/stream/keys` 建立一次，回傳的 `id`/`pem` 存 .env
- **D10**: `lessons.video_platform` 由 enum('vimeo','youtube') 改 string(20) — 前例 `change_content_category_to_string_on_courses`；未來加來源不再動 schema。migration 由本模組擁有（前例：010 擁有 courses 表的 drip 欄位 migration）
- **D11**: `Lesson::embed_url` attribute 對 cloudflare 維持回 null，簽名 URL 在 `ClassroomController` 兩處 lesson formatter 注入（`CloudflareStreamService::signedEmbedUrl()`）— token 有 TTL 屬 request-time 資料，不塞進 Model 靜態 attribute；Model 不 resolve service
- **D12**: `CloudflareStreamService` 介面：`signedEmbedUrl(string $uid): ?string`（內含 JWT 組裝：header `{alg: RS256, kid}` + payload `{sub: uid, kid, exp: now+TTL}`，openssl_sign 後 base64url 拼接）；`VideoEmbedService::parse` 的 cloudflare `embed_url` 回未簽名 iframe URL（維持回傳 shape 一致，教室端不使用它）

## Schema

- `lesson_progress` — (user_id, lesson_id) 存在即代表該小節已完成；unique 複合鍵，無其他欄位（完成時間即 created_at）
- `assignments` — 作業題目；`lesson_id` unique（一節最多一題）、`md_content` Markdown 題目、`is_published` 上下架（下架 = 前台隱藏、資料保留）
- `comments` — 作業留言；`parent_id` null = 學員頂層提交、非 null = 第二層回覆（批改/追問），最多兩層；`is_edited` 編輯標記；cascade 刪除（刪頂層連同子回覆）
- `assignment_completions` — 完成標記；(assignment_id, user_id) unique，只有 created_at，建立即觸發積分與通知，不可撤銷
- `homework_notifications` — 站內通知；`type` enum(reply, completion)、冗餘 `course_name`（免 join）、`lesson_id` 供跳轉、`is_read`；展示端只取最新 5 則
- `lessons.video_platform`（表歸 004，本 migration 歸 003）— enum → string(20)；合法值 `vimeo` / `youtube` / `cloudflare`，來源真相是 `VideoEmbedService::parse` 的輸出，DB 不再約束

**Config（非資料表）**：`services.cloudflare_stream` = `customer_code`（iframe 子網域 customer-{code}.cloudflarestream.com）、`key_id` + `private_key`（base64 PEM，簽 JWT 用）、`token_ttl`（秒，預設 43200）；對應 env `CLOUDFLARE_STREAM_CUSTOMER_CODE` / `CLOUDFLARE_STREAM_KEY_ID` / `CLOUDFLARE_STREAM_PRIVATE_KEY` / `CLOUDFLARE_STREAM_TOKEN_TTL`

## Tasks

**Phase A — 後端基礎**（T001–T004 完成後前端才能接）

- [x] T001 migration：`video_platform` enum → string(20) in `database/migrations/2026_07_15_000002_change_video_platform_to_string_on_lessons.php`
- [x] T002 [P] config 區塊 `cloudflare_stream` + `.env.example` 四個變數 in `config/services.php`
- [x] T003 [P] `CloudflareStreamService`：RS256 JWT 簽名 + `signedEmbedUrl()`（未設 key fallback 未簽名 URL）in `app/Services/CloudflareStreamService.php`
- [x] T004 [P] `parse()` 加 cloudflare 四種格式（customer 子網域 / watch / videodelivery / 裸 UID）in `app/Services/VideoEmbedService.php`

**Phase B — 接點串接**

- [x] T005 [P] `video_url` 規則 `url` → `string`、錯誤訊息含 Cloudflare Stream in `app/Http/Requests/Admin/StoreLessonRequest.php`
- [x] T006 兩處 lesson formatter：platform=cloudflare 時 embed_url 改由 `CloudflareStreamService::signedEmbedUrl()` 產生 in `app/Http/Controllers/Member/ClassroomController.php`

**Phase C — 前端**

- [x] T007 [P] `videoPlatform` computed 加 cloudflare 偵測與提示文案 in `resources/js/Components/Admin/LessonForm.vue`
- [x] T008 [P] cloudflare 播放分支：iframe + Stream SDK（全域載一次，比照 YT API pattern）監聽 ended、autoplay in `resources/js/Components/Classroom/VideoPlayer.vue`

**Phase D — 驗證**

- [x] T009 Feature 測試：parse 各格式、JWT exp/kid claim、StoreLessonRequest 驗證 in `tests/Feature/Classroom/CloudflareStreamTest.php`
- [x] T010 跑 `python3 tools/build_spec_index.py` 對帳索引

## 進度日誌

- 2026-07-22: `VideoEmbedService::parse()` YouTube regex 加 `shorts/`、`live/` 路徑格式（012-newsletter 文章 shorts 網址未轉 embed 的修正；小節影片上架同步受益）。PostServiceTest 補案例，全 repo 168 passed。
- 2026-07-15: 實作 US9 Cloudflare Stream 影音來源完成（T001–T010）— migration enum→string、CloudflareStreamService（本地 RS256 JWT 簽名）、parse 四格式、表單/驗證/播放器三端接通；全套測試 156 passed、npm build 過。附帶修正：VideoPlayer 的 Vimeo message listener 改為無條件註冊（跨平台切換小節後 Vimeo ended 事件原本會失效）
- 2026-07-15: 規劃 US9 Cloudflare Stream 影音來源（貼 URL/UID 上架 + Signed URL 播放保護），status: draft 待審
- 2026-07-12: 修正作業題目管理課程選單 — 改列「全部課程」（不再過濾掉無題目課程，供補第一題），無題目課程置底；並將其課程選單自提交列表篩選解耦（獨立 `manage_course_id` 參數 + `manageLessons`），預設選定「最新新增題目」的課程。`$courses` join→leftJoin；`HomeworkController::lessonsForCourse()` 抽出共用。更新 `HomeworkCoursesTest`。
- 2026-07-11: 作業批改頁課程下拉選單（學員提交列表＋作業題目管理共用）只列「有作業」的課程，並依該課程最新一筆作業建立時間降序排列（HomeworkController@index join+groupBy）。頁面／選單標題「作業批改專區」改「作業批改」。補測試 `tests/Feature/Classroom/HomeworkCoursesTest.php`。
- 2026-07-06: 領域重組 — 合併 002(前台)+010+001(US3) 重寫，依實際 codebase 校正

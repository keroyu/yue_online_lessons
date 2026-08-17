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
  - app/Services/HomeworkGradingService.php
  - database/migrations/2026_01_17_000004_create_lesson_progress_table.php
  - database/migrations/2026_07_15_000002_change_video_platform_to_string_on_lessons.php
  - database/migrations/2026_08_18_000005_rename_md_content_to_suffix_columns.php
  - database/migrations/2026_08_18_000006_add_handout_md_to_assignments_table.php
  - database/migrations/2026_08_18_000007_install_homework_grading_prompt.php
  - database/migrations/2026_08_18_000008_widen_assignment_markdown_columns.php
  - tests/Feature/Classroom/CloudflareStreamTest.php
  - tests/Feature/Classroom/AiGradingTest.php
  - tests/Feature/Classroom/HomeworkCoursesTest.php
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
    why: 教室讀取小節影片欄位（video_platform/video_id/embed_url）、content_md、is_preview、duration_seconds；US10 欄位改名 md_content → content_md（fillable）
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
    why: video_url 驗證需接受 Cloudflare Stream 連結/UID（url 規則放寬為 string + VideoEmbedService::isValid）；US10 欄位改名 md_content → content_md
  - file: resources/js/Components/Admin/LessonForm.vue
    owner: 004-course-admin
    why: 前端影片連結偵測提示需辨識 Cloudflare Stream 格式；US10 欄位改名 md_content → content_md（含插入佔位符的兩處字串操作）
  - file: app/Http/Controllers/Admin/ChapterController.php
    owner: 004-course-admin
    why: US10 欄位改名 — 小節建立/更新的 md_content → content_md
  - file: app/Http/Controllers/Admin/DripLessonPreviewController.php
    owner: 010-drip-email
    why: US10 欄位改名 — 預覽信取小節內文的欄位名
  - file: app/Mail/DripLessonMail.php
    owner: 010-drip-email
    why: US10 欄位改名 — 註解與內文取值的欄位名
  - file: resources/views/emails/drip-lesson.blade.php
    owner: 010-drip-email
    why: US10 欄位改名 — 註解提及的欄位名
  - file: app/Console/Commands/ConvertHtmlToMarkdown.php
    owner: 000-platform-core
    why: US10 欄位改名 — 一次性維運指令掃描 lessons.md_content（000 FR-006 文字同步更新）
  - file: config/ai.php
    owner: 000-platform-core
    why: features 陣列新增 `homework` 分組，AI 設定頁才會把作業批改 prompt 歸到「作業批改」標題下
  - file: app/Services/OpenAiService.php
    owner: 000-platform-core
    why: 行為接點（不改碼）— HomeworkGradingService 經 respond() 呼叫，維持全站唯一 OpenAI 呼叫點
  - file: routes/web.php
    owner: 000-platform-core
    why: admin 群組新增 AI 批改草稿端點（homework.comments.ai-draft）
  - file: tests/Feature/Drip/GuestClaimTest.php
    owner: 010-drip-email
    why: US10 欄位改名 — 測試 factory 欄位名
  - file: tests/Feature/Drip/MemberSubscribeTest.php
    owner: 010-drip-email
    why: US10 欄位改名 — 測試 factory 欄位名（該檔未登記於 code_index）
  - file: tests/Feature/Drip/LessonEmailPreviewTest.php
    owner: 010-drip-email
    why: US10 欄位改名 — 測試 factory 欄位名
  - file: tests/Feature/Storefront/EmailLinkTaggerTest.php
    owner: 002-storefront
    why: US10 欄位改名 — 測試 factory 欄位名
  - file: tests/Feature/Member/UserSocialLinkTest.php
    owner: 001-auth-account
    why: US10 欄位改名 — 測試建立作業題目時的欄位名
  - file: tests/Feature/Points/SettingsEffectTest.php
    owner: 007-points-referral
    why: US10 欄位改名 — 測試建立作業題目時的欄位名（該檔未登記於 code_index）
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
- [x] 內文中單獨一次換行即在畫面上換行（不需空行），空行仍為新段落；與同一份 `content_md` 寄出的連鎖信呈現一致
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
- [x] 題目 CRUD：`POST /admin/lessons/{lesson}/assignment` 建立、`PUT /admin/homework/{assignment}` 更新（`question_md` 上限 50000 字）；題目管理表格依 Chapter → Lesson sort_order 章節分組
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

### User Story 10 - AI 快速批改 (Priority: P2)

管理員在「回覆批改」面板按一次「AI 輔助批改」，系統把**講義 + 作業題目 + 學員提交 + 這串既有的批改往返**送給模型，回傳 500 字以內的批改草稿並**追加**到批改輸入框，管理員改完再自己送出。講義是每個小節各自輸入的 Markdown，放在作業題目旁邊；prompt 走既有的 `/admin/settings/ai` 註冊表，業主可自行改寫語氣與結構。

同一支 US 順手清掉一個命名舊債：`md_content` 是 2026-02 那批 `html_content` 改名留下的名字，之後新增的 Markdown 欄位（`body_md` / `description_md` / `free_success_md`）全走 `*_md` 後綴。新增 `handout_md` 前先把兩個舊欄位改名，避免同一張表裡兩套命名法並存。

**驗收**：
- [x] `lessons.md_content` → `content_md`、`assignments.md_content` → `question_md`；全 repo（PHP 35 處 / Vue 25 處 / 測試）同步替換，教室、小節後台、drip 信、作業區行為完全不變
- [x] `assignments.handout_md` 選填 Markdown 講義，在 `/admin/homework` 的「新增/編輯題目」表單題目欄位下方，共用同一組「編輯 / 預覽」切換；欄位說明點出它只餵給 AI、學員看不到
- [x] 回覆批改面板的 textarea 上方有「AI 輔助批改」按鈕；點擊後按鈕進入 disabled 的「AI 產生中…」狀態，同一次生成不可重複點
- [x] 生成結果**追加**到 textarea 末端：原本有字時以空行分隔接在後面，原本空白時直接填入；永不覆蓋、永不自動送出
- [x] 送進模型的脈絡固定四段（講義 / 作業題目 / 學員提交 / 先前的批改往返），角色標籤只有「老師」與「學員」；講義未填時該段寫「（未提供）」，按鈕照常可用
- [x] 失敗時面板內顯示紅字訊息（未設 API Key、模型無回傳、逾時），textarea 內容不受影響，可直接重試
- [x] `/admin/settings/ai` 自動多出「作業批改」分組（`config/ai.php` features + `ai_prompts` 一列），instructions / 模型 / max_output_tokens 皆可後台調整，預設模型 `gpt-5.6-terra`
- [x] Feature 測試：`Http::fake` 驗證送出的 input 四段組裝與截斷、未設 key 回 422、跨 assignment 的 comment 被擋、草稿不寫入 comments

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
- **FR-014**: 教室小節內文以 `marked(content, { breaks: true })` 渲染 — 單一 `\n` 產生 `<br>`、空行為新段落；raw HTML（iframe 嵌入）維持 marked v17 預設原樣通過，不 sanitize
- **FR-015**: 站內 Markdown 欄位命名一律 `*_md` 後綴（`content_md` / `question_md` / `handout_md` / `body_md` / `description_md` / `free_success_md`）；`md_content` 為 2026-02 改名遺留，US10 一併清除，日後不得再新增此形式的欄位
- **FR-016**: 講義存 `assignments.handout_md`（選填，上限 50000 字，與題目同一驗證規則）；純屬 AI 脈絡資料，前台教室與學員端任何位置都不得輸出
- **FR-022**: `assignments` 的兩個 Markdown 欄位 MUST 為 `longText`（同 `posts.body_md` / `email_templates.body_md`）。MySQL `TEXT` 是 65,535 **bytes**，驗證的 `max:50000` 是**字元** — 中文一字 3 bytes，約 21,845 字起就是「通過驗證、INSERT 時 1406」的死角，而講義正是會被整篇貼進來的欄位
- **FR-023**: 作業題目表單 MUST 顯示後端回傳的驗證錯誤。這頁原本完全沒有錯誤 UI，任何被擋下的儲存都是靜默無反應；加入第二個欄位後這個缺陷才變得容易踩到。錯誤訊息 MUST 為中文（`AssignmentRequest::messages()`），因為它直接呈現給使用者
- **FR-017**: AI 批改只產生**草稿**：一律追加到批改輸入框末端，永不覆蓋既有文字、永不自動建立 comment — 送出批改始終是管理員的明確動作
- **FR-018**: 草稿不落地 — 沒有 `ai_draft` 欄位、沒有生成紀錄表；未送出即丟棄。已送出的批改就是一般 comment，與手寫的無從也不需區分
- **FR-019**: 生成為**同步** JSON 端點（非 queue）：一次呼叫、輸出短，管理員必須當場看到結果才能編輯。OpenAI 未設定、prompt 列不存在、模型無回傳一律回 422 + 可讀中文訊息，不寫入任何資料
- **FR-020**: 送進模型的 input 由 `HomeworkGradingService` 組成固定四段 Markdown（`## 講義` / `## 作業題目` / `## 學員提交` / `## 先前的批改往返`）；每段上限 8000 字（超過從尾端截斷並標註「（內容過長，已截斷）」），往返只取最近 6 則。學員身分只以「學員」出現，不帶暱稱或 email
- **FR-021**: 端點必須驗證 `comment.assignment_id === assignment.id` 且 `parent_id` 為 null（只能對頂層提交生成）；不符一律 404，防止用別題的 assignment id 拼出跨課程脈絡

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
- **D13**: `HtmlContent` 補 `breaks: true`，讓教室與 Email 對同一份 `content_md` 給出相同換行語意。原本教室走 CommonMark 預設（單行併段），Email 端已於 011 FR-021 改成 hard break，兩邊分歧：作者在小節編輯器打一次 Enter，信裡有換行、教室裡沒有。站內其餘 Markdown 呈現（`AssignmentSection`、`CommentThread`、`Admin/Homework`、`EmailTemplates/Edit`）本來就帶 `breaks: true`，`HtmlContent` 是唯一漏網的。
  - 影響範圍：所有課程的小節內文。既有內容多半以連按兩次 Enter（`\n\n`）寫成，那是段落、不受影響；真正改變的只有目前被併行的單行，而那些正是作者本來就想換行的地方。
  - 當時不改 `Pages/Course/Show.vue` 的 `renderedDescription`（判斷銷售頁長文案的段落語意是刻意的），但 2026-08-08 業主回報「必須按兩次 Enter 才換行」是老問題，證明該假設不成立——`renderedDescription` 已於 002 FR-032 補上 `breaks: true`，理由與這裡相同。`PostForm`/部落格（`PostService`）維持不動，段落語意未收到相同回饋。

- **D14**: 講義存 `assignments.handout_md` 而非 `lessons.handout_md` — 作業題目本來就是一節一題（`lesson_id` unique），存在 assignment 上等同 per-lesson，且讓 AI 批改要用的三件事（講義 / 題目 / 提交）全在同一筆、同一頁維護，老師批改時不必跨到課程後台。代價是沒有作業題目的小節無處放講義，但那種小節本來也用不到 AI 批改。被否決的 `lessons.handout_md` 會多動到 004 的 Lesson / LessonForm / StoreLessonRequest 三個檔，換來的只是「未來其他 AI 功能可能共用」這個尚未存在的需求
- **D15**: 新增 `handout_md` 前先把 `lessons.md_content` → `content_md`、`assignments.md_content` → `question_md`（FR-015）— 這 repo 的 Markdown 欄位命名其實有規則（`body_md` / `description_md` / `free_success_md` 都是 `*_md`），`md_content` 只是 2026-02 那批 `html_content` rename 留下的例外。不先改，assignments 表會變成 `md_content` + `handout_md` 兩套命名法並排，之後每次新增欄位都要重新選一次。改名是純機械替換（PHP 35 處 / Vue 25 處），Laravel 12 原生 `renameColumn` 不需 doctrine/dbal。風險：Forge 部署 pull 完才跑 migrate，中間數秒舊 code 對到新 schema 會 500 — 單人網站可接受，且刻意排在同一次部署裡一起上
- **D16**: 同步 JSON 端點而非 queue（FR-019）— 前例是 011 的 `regenerateSummary`：單次呼叫、輸出短，管理員按下去就是要當場看到草稿再編輯。丟 queue 會變成「按了、等三分鐘、重新整理」，對一個要接著手動改字的動作沒有意義。逾時就讓它失敗重試，不做輪詢狀態機
- **D17**: 追加（append）而非覆蓋 textarea（FR-017）— 老師常見用法是先自己寫兩句重點、再讓 AI 補完整段；覆蓋會吃掉那兩句。也因此不做「採用 / 捨棄」的預覽區：草稿直接落在可編輯的 textarea 裡，本來就是要改的
- **D18**: prompt 走既有 `ai_prompts` 註冊表（000 US10）而非寫死在 Service — 加一列 + `config/ai.php` 的 features 加一行，`/admin/settings/ai` 就自動多出「作業批改」分組，Vue 完全不動。業主要改批改語氣、換模型、調長度都不必發版。預設模型 `gpt-5.6-terra`（要判斷學員有沒有真的理解講義，比校訂類工作需要更多判斷力），`max_output_tokens` 2000（500 字中文約 1000 tokens，其餘留給 reasoning）
- **D19**: 草稿不落地 DB（FR-018）— 不加 `ai_draft` 欄位、不記生成歷史。沒送出的草稿沒有價值，送出後它就是一則 comment；要區分「AI 寫的 / 人寫的」得先有這個需求，目前沒有
- **D20**: 脈絡含既有批改往返（FR-020）— 學員交二稿、或老師想補一段評語時，模型能看到前面已經講過什麼，不會把同樣的建議再講一次。取最近 6 則、角色只標「老師 / 學員」（不帶暱稱與 email，PII 不進 prompt）

## Schema

- `lesson_progress` — (user_id, lesson_id) 存在即代表該小節已完成；unique 複合鍵，無其他欄位（完成時間即 created_at）
- `assignments` — 作業題目；`lesson_id` unique（一節最多一題）、`question_md` Markdown 題目、`handout_md` 選填 Markdown 講義（**只餵 AI，前台永不輸出**）、`is_published` 上下架（下架 = 前台隱藏、資料保留）
- `comments` — 作業留言；`parent_id` null = 學員頂層提交、非 null = 第二層回覆（批改/追問），最多兩層；`is_edited` 編輯標記；cascade 刪除（刪頂層連同子回覆）
- `assignment_completions` — 完成標記；(assignment_id, user_id) unique，只有 created_at，建立即觸發積分與通知，不可撤銷
- `homework_notifications` — 站內通知；`type` enum(reply, completion)、冗餘 `course_name`（免 join）、`lesson_id` 供跳轉、`is_read`；展示端只取最新 5 則
- `lessons.video_platform`（表歸 004，本 migration 歸 003）— enum → string(20)；合法值 `vimeo` / `youtube` / `cloudflare`，來源真相是 `VideoEmbedService::parse` 的輸出，DB 不再約束

**改名（US10 / FR-015）**：`lessons.md_content` → `content_md`、`assignments.md_content` → `question_md`；純改名，型別、nullable、內容一律不動

**AI prompt 列（非本模組資料表，寫入 000 擁有的 `ai_prompts`）**：`key = homework_grading_draft`、`feature = homework`、`label = 作業批改草稿`、`model = gpt-5.6-terra`、`max_output_tokens = 2000`；migration 以「不存在才插入」寫法（比照 `create_ai_prompts_table`，避免覆蓋業主改過的 instructions）

**Config（非資料表）**：`ai.features` 加 `'homework' => '作業批改'`；`services.cloudflare_stream` = `customer_code`（iframe 子網域 customer-{code}.cloudflarestream.com）、`key_id` + `private_key`（base64 PEM，簽 JWT 用）、`token_ttl`（秒，預設 43200）；對應 env `CLOUDFLARE_STREAM_CUSTOMER_CODE` / `CLOUDFLARE_STREAM_KEY_ID` / `CLOUDFLARE_STREAM_PRIVATE_KEY` / `CLOUDFLARE_STREAM_TOKEN_TTL`

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

**Phase E — 教室內文換行與 Email 對齊（FR-014 / D13）**

- [x] T011 `rendered` computed 改 `marked(props.content || '', { breaks: true })` in `resources/js/Components/Classroom/HtmlContent.vue`
- [x] T012 以 lesson 80 內文比對 marked 前後輸出（改前無 `<br>`、改後有，iframe 仍原樣通過），`npm run build` exit 0
- [x] T013 跑 `python3 tools/build_spec_index.py` 對帳索引

**Phase F — Markdown 欄位改名（US10 / FR-015 / D15，獨立於 AI 功能，先做完再往下）**

- [x] T014 migration：`lessons.md_content` → `content_md`、`assignments.md_content` → `question_md`（down 反向）in `database/migrations/2026_08_18_000005_rename_md_content_to_suffix_columns.php`
- [x] T015 PHP 端替換 35 處：`Lesson`(fillable)、`Assignment`(fillable)、`AssignmentRequest`、`StoreLessonRequest`、`ChapterController`、`ClassroomController`、`HomeworkController`、`DripService`、`ConvertHtmlToMarkdown`、`DripLessonPreviewController` / `DripLessonMail` / `drip-lesson.blade.php` 的註解
- [x] T016 [P] Vue 端替換 25 處：`LessonForm.vue`（含兩處插入佔位符的字串操作）、`Pages/Member/Classroom.vue`、`Pages/Admin/Homework/Index.vue`、`AssignmentSection.vue`、`HtmlContent.vue` 註解
- [x] T017 測試端替換：`HomeworkCoursesTest`、`UserSocialLinkTest`、`SettingsEffectTest`、`Drip/GuestClaimTest`、`Drip/MemberSubscribeTest`、`Drip/LessonEmailPreviewTest`、`Storefront/EmailLinkTaggerTest`；`php artisan test` 全綠 + `npm run build` exit 0
- [x] T018 000 FR-006 的指令說明文字同步改名 in `specs/000-platform-core/spec.md`

**Phase G — 講義欄位（US10 / FR-016 / D14）**

- [x] T019 migration：`assignments.handout_md` nullable text（after `question_md`）in `database/migrations/2026_08_18_000006_add_handout_md_to_assignments_table.php`
- [x] T020 [P] `handout_md` 加入 fillable in `app/Models/Assignment.php`；驗證 `['nullable','string','max:50000']` in `app/Http/Requests/Admin/AssignmentRequest.php`
- [x] T021 `getAssignmentsMap()` 回傳 `handout_md` 供編輯表單帶入 in `app/Http/Controllers/Admin/HomeworkController.php`
- [x] T022 新增/編輯題目表單加「講義（選填）」textarea + 說明「只提供給 AI 參考，學員看不到」，共用既有編輯/預覽切換 in `resources/js/Pages/Admin/Homework/Index.vue`

**Phase H — AI 批改（US10 / FR-017~021 / D16~D20）**

- [x] T023 [P] `features` 加 `'homework' => '作業批改'` in `config/ai.php`
- [x] T024 [P] migration：插入 `homework_grading_draft` prompt 列（不存在才插入；instructions 含「繁中、500 字以內、先具體肯定→再指 1–3 個可改進處並給做法→收尾一句下一步」「依講義用詞與觀念判斷，不臆測學員沒寫的內容」「不重複先前批改講過的建議」「直接輸出可貼上的內容，無開場白」）in `database/migrations/2026_08_18_000007_install_homework_grading_prompt.php`
- [x] T025 `HomeworkGradingService`：`draft(Assignment, Comment): ?string` + private `buildInput()`（四段組裝、每段 8000 字截斷、往返取最近 6 則、角色標「老師/學員」）in `app/Services/HomeworkGradingService.php`
- [x] T026 `aiDraft(Assignment, Comment, HomeworkGradingService): JsonResponse`（驗 comment 歸屬 + parent_id null 否則 404；null → 422 中文訊息；成功回 `{draft}`）in `app/Http/Controllers/Admin/HomeworkController.php`
- [x] T027 route `POST /admin/homework/{assignment}/comments/{comment}/ai-draft` name `homework.comments.ai-draft` in `routes/web.php`
- [x] T028 回覆面板加「AI 輔助批改」按鈕：axios POST、loading 態「AI 產生中…」且 disabled、成功追加到 textarea 末端（非空則空行分隔）並 focus、失敗顯示面板內紅字訊息 in `resources/js/Pages/Admin/Homework/Index.vue`

**Phase I — 驗證**

- [x] T029 Feature 測試（`Http::fake`）：input 四段組裝與截斷、講義未填仍可生成、未設 API key 回 422、跨 assignment 的 comment 回 404、生成不建立 comment in `tests/Feature/Classroom/AiGradingTest.php`
- [x] T030 `php artisan test` 全綠、`npm run build` exit 0、跑 `python3 tools/build_spec_index.py` 對帳索引

## 進度日誌

- 2026-08-18: US10 上線後修正（FR-022/FR-023）— 業主回報「講義輸入後全部存檔失敗」。查證：正式站 migration 全 Ran、程式碼為最新 commit、laravel.log 無任何例外、DB 全部 assignments 的 updated_at 停在 6/6，代表請求根本沒寫進 DB 也沒丟錯 → 指向「被擋下但畫面不說」。根因是這頁自始就沒有錯誤顯示 UI（`errors` 出現 0 次），加了第二個欄位後才容易踩到。同時修掉 `TEXT` 只裝得下約 21,845 中文字的未爆彈（本機 MySQL 實測 25000 字即 1406）。業主端待確認是否為 session 過期的 419。760 passed
- 2026-08-18: 實作 US10 完成（T014–T030）— Phase F 欄位改名（md_content → content_md / question_md，PHP+Vue+測試共 60 處，行為零變更）、Phase G 講義欄位、Phase H AI 批改（HomeworkGradingService + 同步 JSON 端點 + 回覆面板按鈕）。新增 AiGradingTest 11 案，全 repo 758 passed（改名前基準 747）、npm build exit 0。過程修正兩處測試預期：admin 防護是 302 導回首頁非 403、截斷計數誤含標題字元
- 2026-08-18: 規劃 US10 AI 快速批改（講義欄位 + 回覆面板一鍵生成草稿），同時清掉 `md_content` 命名舊債（→ `content_md` / `question_md`），status: draft 待審
- 2026-08-07: 教室小節內文補 `breaks: true`（FR-014 / D13）— 同一份 `md_content` 在信裡會換行、在教室卻被併成一行，原因是 `HtmlContent` 是站內唯一沒帶 breaks 的 marked 呼叫。查證時確認 Email 端（`EmailMarkdownService` soft_break）自 8/4 起就是對的，正式站實跑 pipeline 有 `<br />`，問題只在教室。522 passed、npm build exit 0。
- 2026-07-22: `VideoEmbedService::parse()` YouTube regex 加 `shorts/`、`live/` 路徑格式（012-newsletter 文章 shorts 網址未轉 embed 的修正；小節影片上架同步受益）。PostServiceTest 補案例，全 repo 168 passed。
- 2026-07-15: 實作 US9 Cloudflare Stream 影音來源完成（T001–T010）— migration enum→string、CloudflareStreamService（本地 RS256 JWT 簽名）、parse 四格式、表單/驗證/播放器三端接通；全套測試 156 passed、npm build 過。附帶修正：VideoPlayer 的 Vimeo message listener 改為無條件註冊（跨平台切換小節後 Vimeo ended 事件原本會失效）
- 2026-07-15: 規劃 US9 Cloudflare Stream 影音來源（貼 URL/UID 上架 + Signed URL 播放保護），status: draft 待審
- 2026-07-12: 修正作業題目管理課程選單 — 改列「全部課程」（不再過濾掉無題目課程，供補第一題），無題目課程置底；並將其課程選單自提交列表篩選解耦（獨立 `manage_course_id` 參數 + `manageLessons`），預設選定「最新新增題目」的課程。`$courses` join→leftJoin；`HomeworkController::lessonsForCourse()` 抽出共用。更新 `HomeworkCoursesTest`。
- 2026-07-11: 作業批改頁課程下拉選單（學員提交列表＋作業題目管理共用）只列「有作業」的課程，並依該課程最新一筆作業建立時間降序排列（HomeworkController@index join+groupBy）。頁面／選單標題「作業批改專區」改「作業批改」。補測試 `tests/Feature/Classroom/HomeworkCoursesTest.php`。
- 2026-07-06: 領域重組 — 合併 002(前台)+010+001(US3) 重寫，依實際 codebase 校正

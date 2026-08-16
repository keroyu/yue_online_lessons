---
id: 010-drip-email
status: done
owner_files:
  - app/Http/Controllers/DripSubscriptionController.php
  - app/Http/Controllers/DripTrackingController.php
  - app/Http/Controllers/Admin/DripLessonPreviewController.php
  - app/Http/Requests/StoreDripSubscriptionRequest.php
  - app/Http/Requests/StoreDripClaimRequest.php
  - app/Models/DripSubscription.php
  - app/Models/DripEmailEvent.php
  - app/Models/DripConversionTarget.php
  - app/Services/DripService.php
  - app/Jobs/SendDripEmailJob.php
  - app/Console/Commands/ProcessDripEmails.php
  - app/Mail/DripLessonMail.php
  - config/drip.php
  - resources/views/emails/drip-lesson.blade.php
  - resources/js/Components/Course/DripSubscribeForm.vue
  - resources/js/Components/Course/ClaimConsentNotice.vue
  - resources/js/Pages/Drip/Unsubscribe.vue
  - resources/js/Components/Admin/Leads/SubscriberListTab.vue
  - resources/js/Components/Admin/Leads/LessonEmailPreviewModal.vue
  - database/migrations/2026_02_16_000001_add_drip_fields_to_courses_table.php
  - database/migrations/2026_02_16_000002_create_drip_subscriptions_table.php
  - database/migrations/2026_02_16_000003_create_drip_conversion_targets_table.php
  - database/migrations/2026_02_16_000004_add_promo_fields_to_lessons_table.php
  - database/migrations/2026_02_21_000001_add_reward_html_to_lessons_table.php
  - database/migrations/2026_02_28_000001_create_drip_email_events_table.php
  - database/migrations/2026_02_28_000002_add_promo_url_to_lessons_table.php
  - database/migrations/2026_03_01_084230_add_video_access_hours_to_lessons_table.php
  - database/migrations/2026_07_20_000001_add_sent_to_drip_email_events_event_type.php
  - database/migrations/2026_07_31_000002_add_booked_to_drip_subscriptions_status.php
  - database/migrations/2026_07_31_000003_add_unlock_all_to_drip_subscriptions_table.php
  - database/migrations/2026_08_16_000003_add_drip_day_to_lessons_table.php
  - tests/Feature/Drip/VariableScheduleTest.php
  - tests/Feature/Drip/VideoAccessAnchorTest.php
  - tests/Feature/Drip/FunnelStopTest.php
  - tests/Feature/Drip/ClaimWordingTest.php
  - tests/Feature/Drip/DripMailDeliverabilityTest.php
  - tests/Feature/Drip/GuestClaimTest.php
  - tests/Feature/Drip/LessonEmailPreviewTest.php
touchpoints:
  - file: resources/js/composables/useDelayedConfirm.js
    owner: 011-high-ticket
    why: 10 秒 Email 覆核的狀態機，正典為 011 FR-059；本模組領取表單共用（US15、D18）。US16 由 useEmailReview.js 更名為此（同一個狀態機，第三個用途不是 Email 覆核而是停止接收的二次確認，D22），並以 5 秒供停止接收確認頁使用
  - file: resources/js/Components/EmailReviewNotice.vue
    owner: 011-high-ticket
    why: US15 — 覆核區塊 UI，後果說明以 slot 傳入；本模組領取表單共用（D18）
  - file: resources/js/Components/Course/HighTicketBookingWizard.vue
    owner: 011-high-ticket
    why: US15 — 內嵌的 10 秒覆核邏輯抽出為共用 composable/元件後改為引用，行為不變
  - file: routes/web.php
    owner: 000-platform-core
    why: US15 — /drip/subscribe 加 throttle:10,1；/drip/verify 路由刪除。US17 — staff 群組內新增 `GET /admin/drip/lessons/{lesson}/email-preview`（唯讀預覽端點，與宿主的 leads 頁同權限）
  - file: app/Jobs/SendDripEmailJob.php
    owner: 002-storefront
    why: 2026-08-05 起寄信前以 EmailLinkTagger 戳 UTM（002 US14）；本模組另提供 DripService::lessonNumber() 供 utm_content 取「第幾封信」
  - file: resources/js/Components/Admin/CourseForm.vue
    owner: 004-course-admin
    why: 「連鎖 Email 設定」分頁 — course_type、drip_interval_days、目標課程選擇、發信排程預覽。US18 起排程預覽表格改為可編輯（每列一個 Day 輸入框），送出 `drip_days` map
  - file: app/Http/Controllers/Admin/CourseController.php
    owner: 004-course-admin
    why: US18 — `edit()` 的 courseLessons 加 `drip_day`；`update()` 在 transaction 內批次寫回 lessons.drip_day，切回 standard 時清空
  - file: app/Http/Requests/Admin/UpdateCourseRequest.php
    owner: 004-course-admin
    why: US18 — 新增 `drip_days` 陣列驗證（key 為 lesson id、首封必為 0、依 sort_order 嚴格遞增，FR-037）
  - file: app/Http/Requests/Admin/StoreCourseRequest.php
    owner: 004-course-admin
    why: US18 — `drip_interval_days` 的錯誤訊息語意由「發信間隔」改為「預設間隔」（規則本身不動）
  - file: app/Models/Lesson.php
    owner: 004-course-admin
    why: US18 — `drip_day` 加入 fillable 與 casts（integer）
  - file: resources/js/Components/Admin/LessonForm.vue
    owner: 004-course-admin
    why: Lesson 的 promo_delay_seconds / promo_html / promo_url / reward_html / video_access_hours 欄位、CTA 快速插入、{{classroom_url}} 插入按鈕與影片警示、drip 信固定格式（開頭問候 + 結尾退訂）的說明區塊
  - file: app/Http/Controllers/Admin/HighTicketLeadController.php
    owner: 011-high-ticket
    why: 2026-08-04 起訂閱者頁併入 Leads 名單頁的「訂閱者名單」tab（011 US8），由該 controller 的 index() 呼叫 `DripService::subscriberPageData()`；原 `CourseController@subscribers` 已刪除
  - file: app/Http/Controllers/Admin/LessonController.php
    owner: 004-course-admin
    why: drip 課程新增 Lesson 時呼叫 DripService::reactivateCompletedSubscriptions()。US18 — `store()` 為 drip 課程的新 Lesson 帶入預設 `drip_day`（既有最大值 + drip_interval_days，FR-038）
  - file: app/Services/PortalyWebhookService.php
    owner: 005-checkout
    why: Portaly webhook 付款成功 — 購買 drip 課程自動 subscribe()、購買任何課程觸發 checkAndConvert()
  - file: app/Services/CheckoutService.php
    owner: 005-checkout
    why: 金流結帳完成 — 與 Portaly webhook 相同的 subscribe() / checkAndConvert() 觸發
  - file: app/Http/Controllers/Purchase/FreePurchaseController.php
    owner: 005-checkout
    why: 免費課程報名後觸發 checkAndConvert()
  - file: app/Jobs/SubscribeDripLeadJob.php
    owner: 011-high-ticket
    why: 高價課名單（high_ticket_leads）批次訂閱 drip 課程 — 呼叫 DripService::subscribe()
  - file: app/Services/HighTicketBookingService.php
    owner: 011-high-ticket
    why: 高價課預約成功建立 lead 後呼叫 DripService::checkAndBook() — 預約即達標，停止序列信（US13）
  - file: app/Services/HighTicketLeadService.php
    owner: 011-high-ticket
    why: convertLead() 後台開通商品建立 Purchase 後呼叫 checkAndConvert()（US13）
  - file: app/Http/Controllers/Admin/MemberController.php
    owner: 008-members-admin
    why: 會員後台與 drip 訂閱者共用同一份 users 名單（User::dripSubscriptions 關聯）；giftCourse() 贈課成功後呼叫 checkAndConvert()（US13）
  - file: app/Http/Controllers/Member/ClassroomController.php
    owner: 003-classroom
    why: drip 教室的 lessonUnlockMap（呼叫 isLessonUnlocked）、觀看期錨點批次查詢、達標訂閱者豁免觀看期/獎勵 props
  - file: resources/js/Pages/Member/Classroom.vue
    owner: 003-classroom
    why: VideoAccessNotice 的顯示條件需排除已達標（booked/converted）訂閱者
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 課程詳情頁嵌入 DripSubscribeForm（訪客）與會員一鍵訂閱區塊（暱稱欄 + 訂閱按鈕）；頁首與右側懸浮面板的「免費領取」CTA 導向此訂閱區。US16 起會員一鍵領取區塊也掛 ClaimConsentNotice
  - file: bootstrap/app.php
    owner: 000-platform-core
    why: `drip/unsubscribe/*` 的 CSRF 豁免（RFC 8058 一鍵退訂的 POST 無 session）
  - file: app/Http/Middleware/HandleInertiaRequests.php
    owner: 000-platform-core
    why: flash 白名單新增 `drip_already_claimed`（值為已領取過的 Email，供領取表單顯示「內容已寄到這個信箱」提示；白名單制，不註冊就永遠 undefined）
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: 課程詳情頁下發 isDrip / 已訂閱狀態 props（drip 課程隱藏試看與購買入口）
---

# Drip Email（連鎖加溫信系統）

## 目標

把課程系統擴充為行銷漏斗：訪客/會員免費訂閱「連鎖課程」後，系統依 Lesson 排序 × 間隔天數自動解鎖內容並逐封發信加溫，追蹤開信與教室促銷點擊，最終導引購買目標課程（轉換）。訂閱者統一納入 users 會員名單管理。

## User Stories

### User Story 1 - 訪客免費訂閱連鎖課程 (Priority: P1)

訪客在課程詳情頁輸入 Email + 必填暱稱（Step 1），收驗證碼後確認（Step 2）；系統自動建立會員（或登入既有帳號並覆蓋暱稱）、建立訂閱、立即發送第一封歡迎信。

**驗收**：
- [x] ~~Step 1 驗證 email + nickname（required, max:50, regex `/\p{L}/u` 防純空格/符號），發送驗證碼並 flash 帶暱稱至 Step 2~~ **（US15 取代：驗證碼整條移除，欄位驗證規則不變）**
- [x] ~~Step 2 驗證碼正確 → 新 Email 建立 member 帳號（email_verified_at 即時）、既有帳號一律以輸入值覆蓋 nickname，登入並建立訂閱~~ **（US15 取代：不再登入、不再覆寫既有暱稱、`email_verified_at` 不再即時給值）**
- [x] 已停止接收者再次領取 → 「您已停止接收此商品的信件，無法再次領取」；已領取過 → 「此 Email 已經領取過了，內容已寄到這個信箱」，並以 flash `drip_already_claimed`（值為該 Email）讓表單顯示醒目提示框：內容寄到哪個信箱、找不到請查促銷/廣告分頁與垃圾郵件、想再領一份就換別的 Email。**不得**引導去登入或宣稱可在網站上觀看 —— 交付物在信箱裡，登入看不到
- [x] ~~驗證碼畫面顯示寄件者提示「來信者為『經營者時間銀行』，找不到時請檢查垃圾郵件」；送出鈕文案為「確認驗證碼」（非「確認訂閱」— 這一步只是驗證信箱）~~ **（US15 取代：該畫面不存在了；寄件者提示移到覆核區塊）**
- [x] 訂閱成功通知顯示於頁面頂部主圖下方（flash `drip_subscribed`）
- [x] 銷售頁的訂閱徽章對讀者說「已領取」（active 狀態）且不附「前往教室」連結 — 銷售頁語境是免費贈品的交付，教室動線留給會員中心與留客區塊；後台的訂閱者/名單頁仍用「訂閱中」（營運語彙，見 002 US11 進度日誌）

### User Story 2 - 已登入會員一鍵訂閱 (Priority: P1)

已登入會員在 drip 課程詳情頁看到暱稱欄（預填現有值）+ 訂閱按鈕，確認暱稱後一鍵訂閱，無需驗證碼。

**驗收**：
- [x] POST `/member/drip/subscribe/{course}` 驗證 nickname（規則同 US1）並更新帳號暱稱後建立訂閱
- [x] 已訂閱者在詳情頁顯示「已訂閱」狀態而非按鈕
- [x] 暱稱空白時前端按鈕 disabled
- [x] `DripSubscriptionController::memberSubscribe()` 的 `Request` 型別 MUST 對應 `Illuminate\Http\Request`（FR-029）；正式站 2026-08-07 已有多筆此錯誤（`userId:93`）

### User Story 3 - 自動序列信排程發送 (Priority: P1)

訂閱當下立即收到第一封信（Lesson 0）；之後每天 09:00 排程比較「應解鎖數」與 emails_sent，補發差額信件；全部寄完標記 completed。

**驗收**：
- [x] 訂閱成功即 dispatchAfterResponse 第一封信並將 emails_sent 設為 1
- [x] `drip:process-emails` 每日 09:00 排程（routes/console.php），應解鎖數 = floor(訂閱天數/間隔)+1（上限為 Lesson 總數）
- [x] SendDripEmailJob 發信前跳過停信狀態（unsubscribed/converted/**booked**，US13 修訂）；completed 仍寄出最後一封（狀態與 dispatch 同時發生）
- [x] 失敗重試 3 次（backoff 60/300/900 秒）
- [x] 信件內容 = 問候語（有名字才顯示）+ md_content 轉 HTML（strip style/class）+ 退訂連結 + tracking pixel；`{{classroom_url}}` 佔位符替換為教室 URL（帶 lesson_id）
- [x] 主旨/問候名字：nickname 優先、fallback real_name；3 個中文字取後 2 字；無名字則省略
- [x] 管理員新增 Lesson 時，completed 訂閱自動 reactivate 為 active（後續排程補發新信）

### User Story 4 - 購買與名單管道自動建立訂閱 (Priority: P2)

除了詳情頁訂閱，付費購買 drip 課程（Portaly webhook / 站內結帳）或高價課名單批次匯入也會建立訂閱並啟動序列信。

**驗收**：
- [x] Portaly webhook 付款成功且課程為 drip → 自動 subscribe()
- [x] 站內結帳（CheckoutService）付款成功的 drip 課程項目 → 自動 subscribe()
- [x] 高價課名單（SubscribeDripLeadJob）→ subscribe()；重複訂閱時跳過不報錯

### User Story 5 - 購買目標課程後自動轉換 (Priority: P2)

drip 課程可設定多個目標課程；訂閱者購買任一目標課程後狀態轉為 converted、停止發信、獎勵解鎖全部 Lesson。

**驗收**：
- [x] checkAndConvert() 由 Portaly webhook、結帳、免費報名、積分兌換四個購買管道觸發（US13 再補後台 Lead 開通與贈課兩條）
- [x] active 與 booked 訂閱都會被轉換（booked→converted 為升級，US13 修訂）；轉換寫入 status_changed_at
- [x] converted 訂閱者解鎖凍結在 emails_sent，**不再**看到全部 Lesson（US14 修訂；改版前既有 converted 由 unlock_all 旗標維持全開）
- [x] 已排入佇列的信件在 handle() 時檢查狀態，converted 後不寄出

### User Story 6 - 使用者停止接收連鎖信件 (Priority: P3)

信件末尾「按此停止接收」連結（UUID token）→ 確認頁警告「限期商品，停止接收後無法再次領取」→ 確認後停止發信，已解鎖內容保留觀看權。

**驗收**：
- [x] `/drip/unsubscribe/{token}` 顯示確認頁（Drip/Unsubscribe.vue），token 建立訂閱時自動產生（model booted）
- [x] 序列信帶 `List-Unsubscribe` 與 `List-Unsubscribe-Post: One-Click`（比照電子報），並在 `bootstrap/app.php` 豁免該路徑的 CSRF，否則郵件用戶端的一鍵 POST 會 419
- [x] 信件內的停止接收行位於正文之後空兩行＋分隔線，以 12px 淺灰呈現並附英文 `Unsubscribe`：既是垃圾郵件過濾器認得的訊號，也不會讀起來像正文的結尾句
- [x] 確認後 status=unsubscribed（欄位值不變，只有文案改）；重複進入顯示「您已停止接收此商品的信件」
- [x] 停止接收者解鎖狀態凍結在 emails_sent（已收信的 Lesson 仍可看，不再解鎖新內容）
- [x] 停止接收者無法再次領取同商品（US1 驗收）

### User Story 7 - 教室觀看與側邊欄過濾 (Priority: P1)

訂閱者進入教室只看到「有影片且已解鎖」的 Lesson；純文字 Lesson 只活在 Email、未解鎖 Lesson 完全不露出（無倒數無鎖頭），維持漏斗黑盒子效果。

**驗收**：
- [x] 解鎖判定一律以 emails_sent 為準（位次 < emails_sent，位次見 FR-022）；只有 completed 與 unlock_all=true 的舊 converted 全解鎖（US14 修訂）
- [x] drip 課程側邊欄過濾：無 video_id 或未解鎖的 Lesson 不出現；admin 預覽豁免（可見全部）
- [x] 直接以 URL 存取未解鎖 Lesson 被擋（改抓第一個未完成的已解鎖影片 Lesson）
- [x] 無任何可顯示 Lesson 時顯示空白歡迎狀態（currentLesson=null，非錯誤頁）
- [x] drip 課程不支援訪客試看（preview）模式

### User Story 8 - 管理員設定連鎖課程與信件內容 (Priority: P2)

管理員在課程表單切換 course_type=drip、設定間隔天數與目標課程（含發信排程預覽）；Lesson 編輯器提供 `{{classroom_url}}` 快速插入與影片警示。

**驗收**：
- [x] CourseForm「連鎖 Email 設定」分頁：drip_interval_days、目標課程多選、依現有 Lesson 排序預覽 Day 0/N/2N 發信日
- [x] 解鎖日全自動：位次 × drip_interval_days（位次見 FR-022），管理員只調排序與間隔
- [x] LessonForm（drip 課程）「+ 插入教室連結」在游標處插入 `{{classroom_url}}`；偵測到影片 URL 時顯示琥珀色提醒
- [x] 信件不含系統固定區塊（課程標題行/影片提醒/教室連結），內容連結全由管理員在 md_content 維護（退訂連結除外）
- [x] 系統會在 md_content **之前**自動加一行「Hi {暱稱}，」、主旨自動組成「{暱稱}，{小節標題}」（無暱稱時兩者都省略），**之後**自動附退訂連結；LessonForm 的 Markdown 內容欄上方 MUST 在 drip 課程時說明這件事，避免管理員在正文重複寫稱呼

### User Story 9 - Lesson 促銷區塊與教室點擊追蹤 (Priority: P2)

Lesson 可設定延遲顯示的促銷區塊（promo_delay_seconds + promo_html，適用所有課程類型），另可設定 promo_url 產生教室內可追蹤按鈕，點擊記錄事件後導向目標。

**驗收**：
- [x] 促銷區塊：null=停用、0=立即、>0 顯示倒數；達標以 localStorage 永久記錄，重整不再等待
- [x] promo_url 按鈕嵌在 LessonPromoBlock 內、與 promo_html 同受延遲控制；後端輸出時已包成 `/drip/track/click?les=&url=` 追蹤連結
- [x] 點擊追蹤走 auth session 找 DripSubscription；查無訂閱仍 redirect 不報錯；去重（同訂閱同 Lesson 只記一次）
- [x] promo_html 支援 CTA 快速插入（品牌金色按鈕 HTML）與優惠碼佔位符替換（CouponChainService）
- [x] drip 信件不含任何促銷按鈕（Email 不追蹤點擊）

### User Story 10 - 影片免費觀看期與準時到課獎勵 (Priority: P2)

Lesson 可設定 video_access_hours（null=無限期）；期限內顯示倒數，過期後影片仍可看但顯示加強促銷區塊（軟性提醒）。獎勵欄：停留滿 config 設定分鐘數後解鎖管理員自訂 reward_html。

**驗收**：
- [x] 過期時間 = subscribed_at + (位次 × 間隔天數) 天 + video_access_hours 小時；null 不顯示任何相關 UI
- [x] 過期後影片不鎖定，顯示「免費觀看期已結束…」促銷區塊，附目標課程連結（無目標課程則通用文案）
- [x] 已達標訂閱者（converted **與 booked**，US13 修訂）豁免全部觀看期/獎勵 UI（後端直接不下發相關 props）
- [x] 獎勵欄前提：有影片 + 有 video_access_hours + 有 reward_html；達標前顯示「你準時來上課了！真棒」，per-session 計時（離開歸零），達標寫 localStorage 永久保留
- [x] 逾期後曾達標者保留獎勵；未達標者顯示「下次早點來喔，錯過了獎勵 :(」
- [x] 等待時間由 `config/drip.php` reward_delay_minutes 全站統一（env 可調，null 停用）

### User Story 11 - 開信追蹤與訂閱者後台分析 (Priority: P2)

每封信嵌 tracking pixel 記錄開信；後台訂閱者頁顯示狀態統計、整體轉換率、per-Lesson 開信率/點擊率表，以及每位訂閱者的開信進度與點擊狀態。

**驗收**：
- [x] pixel 為 signed URL（180 天效期），驗簽失敗仍回 1x1 GIF 不報錯；事件以 (subscription, lesson, event_type) DB unique 去重
- [x] 訂閱者清單：分頁 20 筆、狀態篩選（active/converted/completed/unsubscribed）、狀態統計卡（2026-08-04 起頁面位置改為 Leads 名單頁的「訂閱者名單」tab，課程以頁內下拉選擇；資料組裝為 `DripService::subscriberPageData()`，見 011 US8）
- [x] Lesson 統計表：已發送數（emails_sent > 位次 的訂閱數，位次見 FR-022）、開信數/率、點擊數/率；無 promo_url 或分母 0 顯示「—」
- [x] 整體轉換率 = converted / 總訂閱數（分母 0 顯示「—」）
- [x] 每位訂閱者行顯示「已開 N/M 封」與是否曾點擊促銷按鈕（✓/—）

### User Story 12 - 觀看期改以實際發信時間起算 (Priority: P2)

原本影片免費觀看期到期時間用 `subscribed_at + 理論排程日` 純推算，若排程延遲補寄，信一寄出觀看期就已被吃掉甚至過期。改為以「該 Lesson 對該訂閱者**實際寄出**的時間」為計時起點：發信後才開始跑 video_access_hours。

**驗收**：
- [x] 影片寄出成功時，寫入一筆 `drip_email_events` 的 `sent` 事件（created_at = 實際寄出時刻），為該訂閱該 Lesson 的計時錨點
- [x] 過期時間 = `sent 事件 created_at + video_access_hours 小時`（優先）；查無 sent 事件時 fallback 舊公式（`subscribed_at + 位次 × 間隔天數 天 + video_access_hours 小時`）
- [x] fallback 涵蓋兩種情境：改版前既有訂閱（無回填 sent 事件）、已 dispatch 但 Job 尚未實際寄出的空窗期；兩者行為與改版前一致，無資料遷移
- [x] sent 事件寫入採 firstOrCreate 冪等，Job 重試（$tries=3）不重複；寫入失敗僅 log 不中斷（與開信/點擊事件同策略）
- [x] sent 事件在 `Mail::send` 成功**之後**才寫，寄信拋錯（觸發重試）不會留下錨點
- [x] 已達標訂閱者維持豁免（後端仍不下發觀看期 props）；US13 起 booked 一併適用
- [x] 後台訂閱者頁 per-Lesson 統計表新增「最近發信」欄，顯示該 Lesson 最後一次 sent 事件時間（無則「—」）

### User Story 13 - 預約與後台成交也停止序列信 (Priority: P1)

drip 不直接賣高價課，它的目標常常是「完成預約」。因此達標路徑不只有購買：高價課預約完成即標 `booked`、後台 Lead 開通商品與贈課標 `converted`，三者都立即停止序列信。booked 與 converted 分開統計，才看得出「預約了幾個 / 真的成交幾個」。

**驗收**：
- [x] `HighTicketBookingService::book()` 成功建立 lead 後呼叫 `DripService::checkAndBook(string $email, Course $bookedCourse)`；book 回傳失敗（非高價課/無模板）不觸發
- [x] `checkAndBook()` 以 email 反查既有 user，查無 user 直接 return（不建帳號）；沿用 `drip_conversion_targets` 找出以該課為 target 的 drip 課程，將該 user 的 **active** 訂閱標 `booked` + `status_changed_at`
- [x] 停信整段包 try/catch，失敗僅 Log::error，不影響預約本身的成功回應
- [x] `booked` 與 `converted`、`unsubscribed` 同屬停信狀態：`processDailyEmails` 只撈 active（原本即是）、`SendDripEmailJob::handle()` 跳過清單改吃 `DripSubscription::STOPS_SENDING` 常數
- [x] `HighTicketLeadService::convertLead()` 建立 Purchase 後呼叫 `checkAndConvert($user, $course)`
- [x] `MemberController::giftCourse()` 每位贈課成功的會員呼叫 `checkAndConvert()`，包在既有 try/catch 內，失敗只 log 不中斷批次
- [x] `checkAndConvert()` 受理 active **與 booked**：先預約後成交的人升級為 converted（不會卡在 booked）
- [x] 後台訂閱者頁：狀態統計卡加「已預約」、狀態篩選白名單加 `booked`、清單徽章顯示「已預約」（琥珀色）
- [x] 後台指標拆兩條：預約率 = booked / 總訂閱數、轉換率 = converted / 總訂閱數（分母 0 皆顯示「—」）
- [x] 前台 `Course/Show.vue` 訂閱狀態標籤與徽章色加 `booked` → 「已預約」

### User Story 14 - 達標後不再解鎖全部小節 (Priority: P1)

drip 的定位是促銷漏斗、不是公益教育：達成目標（預約或購買）就結束漏斗 — 停信、已解鎖內容保留、後續小節不再公開（語意同退訂）。改版前既有的 converted 訂閱者維持全開，不回收已給出去的內容。

**驗收**：
- [x] `isLessonUnlocked()` 判定順序：`unlock_all=true` 或 `completed` → 全開；其餘（含 active/booked/converted/unsubscribed）一律 `位次 < emails_sent`
- [x] migration 新增 `unlock_all` boolean（default false），並將現有 `status='converted'` 的列回填為 true；新產生的轉換一律 false
- [x] `daysUntilUnlock()` 對停信狀態（booked/converted/unsubscribed）一律回 -1（不會再解鎖）
- [x] 教室側邊欄過濾、URL 直闖擋下皆沿用 isLessonUnlocked，無需個別改動；達標者進教室只看得到已寄達的 Lesson
- [x] `ClassroomController::formatLessonFull()` 的 `$isConverted` 擴為「已達標」判定（booked/converted 皆豁免觀看期倒數與 reward props）
- [x] `Classroom.vue` 的 VideoAccessNotice `v-if` 條件同步改為排除全部已達標狀態

### User Story 15 - 免登入領取與 Email 覆核防呆 (Priority: P1)

領取免費電子書要先收驗證碼、回站貼六位數才拿得到。8/2 開跑後的實測是
**520 人完成領取、另有 65 個 Email 索取了驗證碼卻從沒建立過帳號**（其中 16 人重複索取，
是想拿卻拿不到、不是改變心意），驗證這一步流失約 11%；
同期正好是序列信偶發被 Gmail 分到促銷分頁的日子，驗證碼信與序列信同網域同信譽。

真正的代價不在那幾秒，而在**這道關卡把「送達問題」放大成「整條漏斗歸零」**：
信沒被看見時，有驗證碼 = 帳號沒建、名單沒留、那個人徹底消失；沒有驗證碼 = 至少留下名單，之後還找得到人。

而那道驗證碼**其實是登入**（`Auth::login($user, true)`，與 `LoginController` 同一行）。
本故事把「領取」與「登入」拆開：領取不再建立 session，驗證碼因此沒有保護對象，整條移除；
打錯字改由高價課那套 10 秒 Email 覆核（FR-059）承接。

**驗收**：
- [x] `/drip/subscribe` 改為**單次提交**即完成：建立/取得 user → 建立訂閱 → 寄出第一封信；`/drip/verify` 路由與 controller method 整組移除
- [x] 領取 MUST NOT 呼叫 `Auth::login()`，MUST NOT 寫 `last_login_at` / `last_login_ip`；訪客領完仍是訪客（FR-023）
- [x] 新 Email 建立的帳號 `email_verified_at` 為 **null**（沒驗證就不能宣稱已驗證）；既有帳號的 `nickname` **不覆寫**，僅在原本為空時填入（FR-025）
- [x] 送出鈕兩段式：第一次按顯示 Email 覆核區塊（大字體印出所輸入的 Email）並倒數 10 秒，倒數期間停用並顯示剩餘秒數；倒數結束後文案改為「Email 正確，確認領取」，第二次按才送出。區塊附「這個 Email 不對，回去修改」可直接改回欄位；修改 Email 或關閉表單 MUST 重置整個流程（FR-024）
- [x] 覆核區塊的後果說明寫這門課的實情：電子書寄到這個地址，打錯不會有任何通知；並保留寄件者提示「來信者為『經營者時間銀行』，找不到請檢查垃圾郵件與促銷分頁」
- [x] 10 秒覆核抽成共用元件與 composable，`HighTicketBookingWizard` 改用之，兩處行為與秒數由同一份程式碼決定（D18）
- [x] `/drip/subscribe` 加 `throttle:10,1`，Form Request 加 `website` 蜜罐欄位（`nullable|prohibited`，錯誤訊息「領取失敗」），前端補視覺隱藏的蜜罐 input —— 比照電子報既有作法（FR-026）
- [x] 既有的「已領取過 / 已停止接收」兩條訊息與 `drip_already_claimed` flash 行為完全不變
- [x] 領取成功後仍以 flash `drip_subscribed` 顯示成功區塊；**回訪的訪客會再看到領取表單**（沒有 session 可判斷），再次送出即落入既有的「已領取過」提示，該提示已指名信箱與垃圾郵件（D19）
- [x] US2 的會員一鍵領取（已登入）不加覆核區塊 —— 那個 Email 是帳號本身，沒有可打錯的餘地
- [x] 電子報訂閱（012）的驗證碼流程**不在本故事範圍**：它沒有交付物可當作驗證，且同樣會 `Auth::login()`，要拆是另一個決定
- [x] 測試：領取後 `Auth::check()` 為 false、`email_verified_at` 為 null、既有會員暱稱不被覆寫、蜜罐命中即擋、throttle 生效、第一封信仍寄出、既有 ClaimWordingTest 全數續過

### User Story 16 - 領取同意告知與停止接收前的挽留 (Priority: P2)

US15 拆掉驗證碼之後，領取變成「填了就送出」，中間再也沒有一個畫面說明「你正在同意什麼」。
補上一段同意告知，把交換條件講清楚：這份免費資源不是無條件的贈品，
它換的是「願意持續收信」這件事。

另一頭是停止接收。現行確認頁只講**功能後果**（不再收到信、無法再次領取），
沒講**權益後果**（限定免費資源、活動、諮詢申請可能就此關門）。
很多人按下去只是想少收幾封信，並不知道自己退掉的是整個資格 —— 
所以確認頁改成先把後果講完，再要求二次確認，中間強制停 5 秒。

停 5 秒不是為了讓人放棄，是為了讓「讀完那段話」真的發生：
一段沒有人讀的說明，跟沒寫是一樣的。想走的人 5 秒後照樣走得掉。

**驗收**：
- [x] 兩個領取入口（訪客 `DripSubscribeForm`、已登入會員一鍵領取區塊）送出鈕**下方**顯示同一段同意告知，文字由共用元件 `ClaimConsentNotice.vue` 提供 —— 法律文字兩份會漂移（FR-027）
- [x] 同意告知文案（依 FR-017 改寫，不出現「訂閱／退訂」）：
      「領取即代表同意接收免費資源及後續相關內容。您可以隨時停止接收；停止後我們將不再寄送信件，同時失去限定免費資源與服務的申請資格。」
- [x] 告知為**被動揭露**，不加 checkbox：送出即同意，不新增必勾欄位（D24）
- [x] `/drip/unsubscribe/{token}` 確認頁載入時即顯示挽留說明，取代現行的紅色警告框，語氣由警告改為說明（琥珀色）：
      「本免費商品與後續免費資源，是提供給願意持續接收我們內容的人。若您選擇停止接收，我們會立即停止寄送信件；同時，您的領取資格也會終止，未來部分限定免費資源、活動及諮詢申請可能不再開放。付費產品與既有客戶權益不受影響。」
- [x] 確認鈕為兩段式：第一次按進入 **5 秒**倒數（停用、顯示剩餘秒數），倒數結束後文案改為確認語氣，第二次按才真的送出 POST（FR-028）
- [x] 「取消／返回首頁」在全程可用，兩段式期間不得移除 —— 挽留不等於卡住出口
- [x] 已是 `unsubscribed` 的重複進入維持原樣（顯示「已停止接收」），不顯示挽留、不顯示確認鈕
- [x] 5 秒與二次確認**只活在網頁確認頁**：`POST /drip/unsubscribe/{token}` 端點行為完全不變，郵件用戶端的 RFC 8058 一鍵退訂 MUST 維持單次 POST 即生效（FR-028、D21）
- [x] 兩段式狀態機沿用 US15 抽出的共用 composable（改名 `useDelayedConfirm`，秒數為參數），不另寫一份倒數（D22）
- [x] 「失去申請資格」本次**只做文案宣告，不實作跨商品封鎖**；措辭用「可能不再開放」保留營運彈性（D23）
- [x] 測試：`POST /drip/unsubscribe/{token}` 單次請求即 status=unsubscribed（守住一鍵退訂不被日後加上伺服器端二段式）

### User Story 17 - 後台預覽 Lesson 信件實際樣貌 (Priority: P3)

後台「訂閱者名單 → Lesson 發信統計」看得到每封信寄了幾封、開了幾成，
卻看不到**那封信長什麼樣子**。要確認一封信的排版、連結、問候語有沒有錯，
現行唯一辦法是自己去訂閱一次課程、等信寄到、翻收件匣 —— 而序列信一天只跑一次，
發現錯字的成本是一天。

在統計表點該列的標題，直接開 modal 看那封信寄出去的樣子。

關鍵是**預覽必須等於寄出**：信件最終的 HTML 不是 `md_content` 本身，
中間還隔著 Markdown 轉換、strip style/class、UTM 戳章、問候語組裝、blade 版面與頁尾。
前端拿 `marked` 重繪一份只會給出「像那封信的東西」，而預覽的用途正是抓出那些差異，
所以一律由後端渲染真正的 `DripLessonMail`（D25）。

**驗收**：
- [x] 「Lesson 發信統計」表格的「課程」欄標題可點：游標 pointer、hover 有可見回饋（依 Development Rules）
- [x] 點擊開 modal，內容為該封信的**主旨**與**信件內文**；內文以 `sandbox` iframe（`srcdoc`）呈現，不受後台頁面 CSS 影響、也不執行內文裡的任何 script（比照 011 US7 模板預覽作法）
- [x] modal 內容由後端渲染真正的 `DripLessonMail`（`->render()`）取得，**不得**在前端用 `marked` 重繪（FR-030）
- [x] 寄信與預覽共用同一段組裝邏輯 `DripService::buildLessonMail()`；`SendDripEmailJob` 改為呼叫它，實際寄出的信**行為零變更**（FR-031）
- [x] 預覽中的個人化欄位一律為佔位假資料（D26）：問候語用固定範例名（`小明`）、退訂連結為 `#`、**不放追蹤像素**
- [x] 預覽為唯讀：MUST NOT 寫入任何 `drip_email_events`、MUST NOT 動到任何真實訂閱者的 token 或統計（FR-032）
- [x] 內文連結仍帶真實的 UTM 戳章（`utm_source=drip`、`utm_content=lesson-N`）—— 那正是最需要被眼睛檢查的部分
- [x] `{{classroom_url}}` 佔位符已代換為真實教室連結，與寄出時相同
- [x] 無 `md_content` 的 Lesson 預覽顯示 blade 的 fallback 文案（「新的內容已經解鎖了，請至網站觀看。」），不報錯
- [x] 端點權限跟隨宿主頁面（`/admin/high-ticket-leads` 為 **staff**，業務諮詢師看得到訂閱者 tab）；非 drip 課程的 Lesson 回 404（FR-033）
- [x] 本次**不做**「寄測試信到我的信箱」，只做畫面預覽（D28）
- [x] 測試：admin 取得 200 且 HTML 含內文與頁尾、不含追蹤像素 `<img`；非 admin 不得存取；非 drip Lesson 404；既有 `DripMailDeliverabilityTest` 零修改續過

### User Story 18 - 每封信各自設定發送日（可變間隔） (Priority: P2)

目前一門 drip 課程只有一個 `drip_interval_days`，整串信被綁成等距：Day 0、3、6、9、12。
但加溫序列的節奏本來就不是等距的 —— 前段要密（人還記得你），後段要疏（給決策時間），
實務上想要的是 **Day 0 → 3 → 7 → 14 → 30**，而現行結構排不出來。

改為每一封信各自設定「訂閱後第幾天寄」，編輯介面就放在課程的「連鎖 Email 設定」分頁：
現有的排程預覽表格改成可編輯，每列一個天數輸入框，一次看完整個節奏並看到與前一封的間隔。

`drip_interval_days` **不移除**，語意改為「預設間隔」：既有課程沒填天數時仍用舊公式算（零遷移），
新增 Lesson 時用它算出預設天數。

**驗收**：
- [x] `lessons.drip_day`（nullable）存「訂閱後第幾天寄這封信」；null 時 fallback 舊公式 `位次 × drip_interval_days`（FR-035）
- [x] CourseForm「連鎖 Email 設定」分頁的排程表格每列可編輯：第 N 封、Day 輸入框、與前一封的間隔（`+X 天`）、Lesson 標題
- [x] 首封（位次 0）的 Day 固定為 0 且輸入框 disabled —— 訂閱當下就寄，這不是可調的參數（FR-005 不變）
- [x] 表格開啟時，`drip_day` 為 null 的列**預帶**舊公式算出來的數值（位次 × 間隔），管理員看到的一定是「現在實際的行為」，存檔後即落地為明確值
- [x] 天數 MUST 嚴格遞增，不遞增時存檔擋下並指出是哪一列（FR-037）；前端同步顯示紅字提示，但正典是後端驗證
- [x] 存檔後 `getUnlockedLessonCount()` / `daysUntilUnlock()` / 觀看期 fallback 錨點三處一致改吃新天數（FR-036）
- [x] `drip_interval_days` 欄位保留，UI 文案改為「預設間隔天數」，說明它只影響「未設定天數的課程」與「新增 Lesson 的預設值」
- [x] drip 課程新增 Lesson 時，若該課程已有任何明確 `drip_day`，新 Lesson 自動帶 `max(drip_day) + drip_interval_days`；若整門課都還是 null 則維持 null（FR-038）
- [x] 課程由 drip 切回 standard 時，該課程所有 `lessons.drip_day` 一併清為 null（比照既有清 `drip_interval_days` 的作法）
- [x] 進行中的訂閱不需遷移：改天數後下一次排程即依新天數計算應寄數，已寄出的信不重寄、不回收（FR-039）
- [x] 測試：Day 0/3/7/14/30 的課程，訂閱後第 0/3/6/7/13/14/29/30 天各自算出正確應寄數；`drip_day` 全 null 的課程行為與改版前逐字相同；非遞增天數存檔回 422

## Requirements

- **FR-001**: 解鎖日由 `DripService::unlockDay(Lesson)` 單一入口決定 —— `lessons.drip_day` 有值即為該值，null 則 fallback 舊公式 `位次 × drip_interval_days`（位次見 FR-022，US18 修訂）；但個別 Lesson 的解鎖判定以 **emails_sent** 為準（信寄到哪、解鎖到哪），時間公式只用於排程計算應寄數與觀看期起算
- **FR-002**: 課程算不出有效排程時視為全部解鎖（防呆）—— 即 `drip_day` 為 null 且 `drip_interval_days` ≤ 0/null，此時 `unlockDay()` 一律回 0，每一課的解鎖日都是 Day 0，等價於改版前「interval ≤ 0 全開」的行為
- **FR-003**: 訂閱唯一性：(user_id, course_id) DB unique；unsubscribed 是終態 — 永不能再訂閱同課程
- **FR-004**: 狀態機：active → booked（預約目標高價課）/ converted（購買目標課程）/ completed（寄完全部）/ unsubscribed（退訂）；booked → converted 可升級（預約後真的成交），其餘轉移不可逆；completed 可因新增 Lesson 回到 active
- **FR-005**: 第一封信 dispatchAfterResponse（回應後即發），emails_sent 同步 +1；發送計數在 dispatch 時記錄，實際寄出與否由 Job 內狀態檢查決定
- **FR-006**: 信件為極簡模板：問候語（可省）＋內文＋退訂連結＋pixel；內文 Markdown 以 CommonMark 轉 HTML 後 strip style/class/`<style>`；無內文時顯示「新的課程內容已經解鎖了，請至網站觀看」
- **FR-007**: 開信/點擊事件 immutable（無 updated_at），(subscription_id, lesson_id, event_type) unique，firstOrCreate 寫入失敗僅 log 不中斷
- **FR-008**: `/drip/track/open` 免登入 + signed URL；`/drip/track/click` 需 auth，以 session user 反查訂閱（lesson 必須屬於已訂閱課程）
- **FR-009**: 促銷/獎勵達標狀態存 localStorage（不進 DB），跨裝置不共用為已接受的取捨
- **FR-010**: 課程下架（unpublished）後排程不再對其訂閱者發信（processDailyEmails 僅取 published drip 課程）
- **FR-011**: 觀看期計時錨點為「實際寄出時間」— `SendDripEmailJob` 於 `Mail::send` 成功後 firstOrCreate 一筆 `(subscription_id, lesson_id, 'sent')` 事件；錨點取該事件 `created_at`。錨點缺席時（既有訂閱／queued 空窗期）fallback 舊理論公式
- **FR-012**: 觀看期到期／剩餘秒數一律由 DripService 後端計算並吃錨點；教室每次載入以單一查詢批次取回該訂閱所有 sent 事件（lesson_id ⇒ created_at），避免逐 Lesson N+1
- **FR-013**: 達標管道總表 — 標 `converted`：站內結帳、Portaly webhook、免費領取、積分兌換、後台 Lead 開通商品、後台贈課（六條，皆為實際成交）；標 `booked`：高價課預約表單（一條）。所有管道共用 `drip_conversion_targets` 判定「這門課是哪些 drip 課的目標」
- **FR-014**: 達標即出漏斗 — 停信且解鎖凍結在 `emails_sent`。全開只剩兩種情況：`completed`（已寄完，本來就等於全部）與 `unlock_all=true` 的改版前既有 converted
- **FR-015**: 停信狀態集合與豁免集合定義為 `DripSubscription` 常數（`STOPS_SENDING = [booked, converted, unsubscribed]`、`FUNNEL_DONE = [booked, converted]`），Job/Service/Controller 一律引用常數，禁止各處硬編字串陣列
- **FR-016**: 達標停信為「盡力而為」的副作用 — 預約/贈課/開通的主流程不因 drip 停信失敗而中斷，一律 try/catch + log

- **FR-022**: 「第幾封信」一律以 Lesson 在該課程 `orderBy(sort_order)` 序列中的 **0 起算位次**（position）為準，**MUST NOT 拿 `sort_order` 的值當索引**（2026-08-05 修正）。`sort_order` 只是排序鍵：`LessonController` 以 `max + 1` 編號，所以後台建出來的課程是 1、2、3，拖曳重排還可能留下跳號。`processSubscription()` 本來就是按位次取 `$lessons[$i]` 寄信，因此位次才是與「實際寄了哪一封」對得上的定義。適用於解鎖判定、解鎖日、觀看期 fallback 錨點、後台已發送數四處。
  **這個落差在正式站是 off-by-one**：`sort_order` 從 1 起算的課程，收到第 1 封信的人（`emails_sent=1`）連第 1 課都打不開，第 2 課的已發送數恆為 0（開信率因此顯示「—」）。既有測試全部以 0 起算建 Lesson，所以測不出來 —— 那個慣例後台從來產不出來。位次由 `DripService::lessonPosition()` 單一入口計算並按課程 memoise（教室一頁會逐 Lesson 呼叫）

- **FR-023**: 免費領取 MUST NOT 建立登入 session（US15）。領取表單是**取得交付物**的入口，不是登入入口；`Auth::login()` 留在 `/login`。理由是安全而非體驗：領取只驗證了「有人打了這個 Email」，不是「這個 Email 是他的」，據此登入等於任何人打你的信箱就能進你的帳號（訂單、積分、已購課程、個人設定）。同理 MUST NOT 更新 `last_login_at` / `last_login_ip` —— 沒有人登入。

- **FR-024**: 送出 MUST 為兩段式，比照 011 FR-059：第一次按顯示 Email 覆核區塊（大字體印出所輸入地址）並強制倒數 **10 秒**，第二次按才真的送出；修改 Email MUST 重置。停頓的目的不是等待，是讓「再看一眼」真的發生 —— 打錯 Email 是**靜默失敗**，系統每一層都回報成功，只有收件匣是空的。秒數與行為由共用元件決定，兩處不得各自調整（D18）。

- **FR-025**: 領取建立的帳號 `email_verified_at` MUST 為 null —— 沒有驗證就不得宣稱已驗證；該欄位全站沒有任何 gate 依賴它（無 `verified` middleware），寫成 `now()` 只會讓資料說謊。既有帳號的 `nickname` MUST NOT 被領取表單覆寫，僅在原值為空時填入 —— 未經身分驗證的表單改得動既有會員的顯示名稱，是 US15 拆掉登入後才浮現的破口。

- **FR-026**: 沒有驗證碼之後，`/drip/subscribe` 等於「輸入任意信箱就寄一封信出去」，MUST 補上 `throttle:10,1` 與 `website` 蜜罐欄位（比照 012 電子報既有作法）。這不只防機器人，也防有人拿它對第三方信箱灌信 —— 灌出去的每一封都掛在你的寄件信譽上。

- **FR-027**: 領取入口 MUST 在送出鈕下方揭露同意告知（US16），且文字 MUST 來自單一共用元件 `ClaimConsentNotice.vue`，兩個入口（訪客表單、會員一鍵領取）不得各自維護。理由不是省行數：這是**權益條款**，兩份文字漂移之後「哪一份才是使用者當初同意的」無法回答。告知為被動揭露（送出即同意），MUST NOT 新增必勾 checkbox。

- **FR-028**: 停止接收確認頁 MUST 為兩段式（第一次按 → 強制 **5 秒** → 第二次按才送出），但這道關卡 MUST 只存在於 Vue 確認頁。`POST /drip/unsubscribe/{token}` 端點 MUST 維持單次請求即生效 —— 該路徑同時是 RFC 8058 `List-Unsubscribe-Post: One-Click` 的目標，郵件用戶端直接 POST、沒有頁面可以按第二次；在端點加關卡等於一鍵退訂失效，而 `List-Unsubscribe` 正是 2026-08-02 那次「Gmail 偶發分到促銷分頁」的解方（US6）。**寄件信譽優先於挽留。**
- **FR-029**: `DripSubscriptionController` 內任何裸 `Request` 型別參數 MUST 對應 `use Illuminate\Http\Request;`。該檔案 namespace 為 `App\Http\Controllers`（非子命名空間），少了限定 import 時裸 `Request` 會被解析成不存在的 `App\Http\Controllers\Request`，路由參數綁定丟 `ReflectionException` → 500。`memberSubscribe()` 曾漏掉此 import，導致登入會員一鍵領取全數失敗（正式站 2026-08-07 事件）。

- **FR-030**: 後台 Lesson 信件預覽 MUST 由後端渲染真正的 `DripLessonMail`（`Mailable::render()`），MUST NOT 在前端用 `marked` 重繪。信件最終 HTML 經過 Markdown 轉換 → strip style/class → UTM 戳章 → 問候語與主旨組裝 → blade 版面與頁尾，這條鏈只存在後端；前端重繪出來的是「像那封信的東西」，而預覽存在的理由正是抓出兩者的差異
- **FR-031**: 寄信與預覽 MUST 共用單一組裝函式 `DripService::buildLessonMail(Lesson $lesson, ?DripSubscription $subscription = null, ?User $user = null): DripLessonMail`。`SendDripEmailJob::handle()` 內的內文組裝（含 `resolveGreetingName`、`stripStylesForEmail`）整段移入該函式，Job 只留狀態判斷、寄送、記錄 sent 事件。兩份組裝邏輯是預覽失真的唯一來路，不得存在
- **FR-032**: 預覽 MUST 為唯讀且無副作用：`$subscription`/`$user` 為 null 時 `openPixelUrl` 為空字串（blade 不輸出像素）、`unsubscribeUrl` 為 `#`、問候語為 `DripService::PREVIEW_GREETING_NAME`。MUST NOT 寫入 `drip_email_events`、MUST NOT 使用真實訂閱者的 `unsubscribe_token`（誤點即真的把人退掉）
- **FR-033**: 預覽端點 `GET /admin/drip/lessons/{lesson}/email-preview` MUST 與宿主頁面同權限層級 —— 掛 **staff** middleware 群組（不是 admin）。訂閱者名單本身就在 `/admin/high-ticket-leads` 的 staff 群組內，端點若收緊成 admin，業務諮詢師看得到那張統計表卻點不開預覽。且 MUST 對非 drip 課程的 Lesson 回 404 —— 一般課程根本不寄這封信，能預覽只是誤導
- **FR-034**: 預覽 HTML MUST 以 `sandbox` iframe 的 `srcdoc` 呈現（比照 011 FR 的模板預覽）。兩個理由：後台頁面的 Tailwind 樣式會讓預覽比實際的信好看，以及 `md_content` 允許原生 HTML，未 sandbox 等於讓內容在 admin session 下執行 script

- **FR-035**: 解鎖日 MUST 只由 `DripService::unlockDay(Lesson $lesson): int` 產出，回傳「訂閱後第幾天」的絕對天數。實作為 `$lesson->drip_day ?? 位次 × ($course->drip_interval_days ?? 0)`。原本散在三處的 `位次 × drip_interval_days` 算式（`getUnlockedLessonCount`、`daysUntilUnlock`、`getVideoAccessExpiresAt` 的 fallback 錨點）MUST 全部改呼叫它 —— 這正是 FR-022 那次 off-by-one 要改四個地方的原因，同一個算式不得再有第二份

- **FR-036**: 應寄數 MUST 為「`unlockDay(lesson) ≤ 已訂閱天數` 的 Lesson 數」（依 sort_order 由前往後計數），取代原本的 `floor(訂閱天數 / 間隔) + 1`。天數保證嚴格遞增（FR-037），因此「符合條件的數量」與「前綴長度」等價，不需另設前綴邏輯。上限仍為 Lesson 總數，`processSubscription()` 的差額補發邏輯一行不改

- **FR-037**: `drip_days` MUST 在 `UpdateCourseRequest` 驗證：格式為 `{lesson_id: day}`、key MUST 全屬該課程、值為 0–365 整數、依 `sort_order` 排序後 MUST **嚴格遞增**、首封 MUST 為 0。非遞增一律擋下，不做「用前綴規則吃掉」的寬容處理 —— 發信游標本來就是循序推進的，`Day 0 → 7 → 3` 在系統裡執行起來等於 `0 → 7 → 7`，那不是管理員填那個數字時想要的東西，而畫面上看不出來。**存檔時擋下比事後解釋便宜。** 錯誤訊息 MUST 指出是第幾封

- **FR-038**: drip 課程新增 Lesson 時，`LessonController::store()` MUST 決定新 Lesson 的 `drip_day`：該課程已有任何非 null 的 `drip_day` → 帶 `max(drip_day) + (drip_interval_days ?: 7)`；全部為 null → 維持 null。理由是不得產生**混合狀態** —— 明確天數的課程混進一個 null 列，fallback 算出來的 `位次 × 間隔` 幾乎必定小於前一課的明確天數，排程順序當場錯亂，而 FR-037 的遞增驗證只在課程表單存檔時跑，攔不到這條路徑

- **FR-039**: 改天數 MUST 立即對進行中的訂閱生效，且 MUST NOT 回補或重寄。下一次 `drip:process-emails` 依新天數算出應寄數，與 `emails_sent` 比差額 —— 天數往前調（Day 14 → 7）可能一次補寄多封，天數往後調則單純變慢，`emails_sent` 只增不減，寄出去的信收不回來。這是 D2「進度用單欄位游標」的既有結果，不另做遷移或凍結

- **FR-040**: `drip_days` MUST 只在 `course_type=drip` 時被送出與驗證。`CourseForm` 用同一份 `useForm` 服務兩種課程類型，**不管當下是哪一種都會把所有欄位送出**，所以「表單沒顯示這個欄位」不等於「後端不會收到它」。修法兩端都做：前端 `transform` 在非 drip 時把 `drip_days` 拿掉，後端 `prepareForValidation()` 一律清成 null（正典）。
  **這條是踩過才寫的**：一般課程只要有章節就存不了檔 —— `LessonController` 的 `sort_order` 是**每章各自從 1 起算**，整門課因此有重複值，前端按位次預帶的 0,1,2,3… 與後端 `orderBy('sort_order')` 讀到的順序對不上，遞增檢查判定失敗，畫面回「發信排程：第 4 封信的天數必須大於前一封」—— 一門跟連鎖信毫無關係的課程，被一個它根本沒有的東西擋住（2026-08-17 正式站事件）

- **FR-017**: 前台對免費商品 MUST 用「領取／商品」語彙，不得出現「訂閱」；「退訂」對外一律說「停止接收信件」（徽章「已停止接收」）。電子報是全站例外（維持訂閱語彙）；後台（訂閱者頁、名單、廣播）維持「訂閱」等營運語彙，因為它對應資料表 `drip_subscriptions` 與 `status` 欄位值，文字跟著欄位走才查得動問題。資料庫欄位、路由 `/drip/unsubscribe/{token}`、狀態值 `unsubscribed` 皆不改。

## 設計決策

- **D1**: 訂閱者統一為 users 會員 — 不另建 Email 名單表；訪客訂閱即建帳號（~~並登入~~，US15 起不再登入），後台會員/批次發信/贈課功能無縫共用
- **D2**: 進度用 emails_sent 單欄位，不建發信記錄表 — 排程比較差額補發；解鎖與發信天然同步，代價是無 per-封發送履歷
- **D3**: Email 不追蹤點擊，點擊追蹤移到教室 promo_url 按鈕 — 教室必登入可用 auth session 識別，免 signed URL；開信率＋課程進度已足夠評估信件本身
- **D4**: promo_html 與 promo_url 職責分離 — 自訂 HTML 無法安全解析連結故不追蹤；promo_url 由系統產生單一可追蹤按鈕，兩者同在 LessonPromoBlock、同受延遲計時
- **D5**: 側邊欄過濾（漏斗黑盒子）— drip 教室只顯示有影片且已解鎖的 Lesson，訂閱者無法預見序列全貌，維持每封信的期待感；純文字 Lesson 僅經 Email 傳遞
- **D6**: 觀看期採軟性提醒 — 過期不鎖影片只顯示促銷（「我們為你保留了存取權」），per-lesson 設定 video_access_hours，避免懲罰感
- **D7**: 統計即時計算不快取 — 訂閱規模千級內可接受；分母 0 一律顯示「—」
- **D8**: `StoreDripSubscriptionRequest` 目前未被引用（controller 內採 inline validate，因暱稱規則後來直接加在 controller）— 保留檔案，未來收斂驗證時再啟用或刪除
- **D9**: 觀看期倒數/獎勵計算放後端（DripService），前端組件只吃 props 倒數 — 避免時區/竄改問題
- **D10**: 發信時戳復用 `drip_email_events`（新增 `sent` event_type），不另建發送記錄表 — 該表本已 immutable、有 created_at、且 `(subscription, lesson, event_type)` unique 天然去重，完美當每封信的錨點；代價僅是 enum 加值。刻意不推翻 D2（emails_sent 仍是解鎖游標與 dispatch 計數），sent 事件只服務「觀看期起算」與「實際發信時間顯示」，兩者職責分離
- **D11**: 錨點缺席一律 fallback 舊公式（非回傳 null）— 既有訂閱免資料遷移即向後相容；queued 空窗期（dispatch 已 +1 但 Job 未跑）短暫且同日，理論值≈實際值。取捨：捨棄一次性 backfill 的帳面一致，換零遷移風險，符合觀看期本為軟性提醒（D6）的定位

- **D12**: 預約用新狀態 `booked` 而非共用 `converted` — 兩者行為完全相同（停信 + 出漏斗），差別只在後台統計要分得出「預約 vs 實際成交」。否決共用 converted：轉換率會被預約灌水，看不出真實成交
- **D13**: 既有 converted 的全開權以 `unlock_all` 布林旗標保留，migration 一次性回填 — 不回收已經給出去的內容。否決「比對 status_changed_at 與改版日期」：魔術常數 + 依賴伺服器時間，且無法解釋。此欄長期只有舊資料為 true，未來確認無人在意時可整欄移除
- **D14**: `checkAndBook()` 以 email 反查既有 user，查無不建帳號 — 訂閱者必為 users 會員（D1），查無 user 就必然沒有訂閱，建帳號是 011 高價課的職責，不在 drip 這邊擴張
- **D15**: booked → converted 允許升級（`checkAndConvert` 受理 active + booked）— 先預約後成交是最正常的漏斗路徑，卡在 booked 會讓成交數統計失真。反向（converted → booked）不允許
- **D16**: 停信/豁免的狀態集合收斂成 model 常數 — 這次改動要同時碰 Job、Service、Controller 三處的狀態判斷，硬編字串陣列是下次漏改的溫床

- **D17**: 驗證碼可以整條拿掉，是因為它保護的東西被移走了，不是因為它沒用（US15）。原本那道 OTP 同時做三件事：證明信箱歸屬、擋打錯字、以及**登入**。登入拆掉之後，第一件事沒有保護對象（沒有 session 可以被冒領），第二件事由 10 秒覆核接手，剩下的只有成本。**先拆登入、再拆驗證，順序不能倒過來** —— 只拿掉驗證碼而保留 `Auth::login()`，等於任何人打你的 Email 就能進你的帳號。
  代價誠實記錄：（1）名單品質從 confirmed opt-in 降為 single opt-in，投訴率與蜜罐風險上升，而寄件信譽本來就已經是這個模組的痛點（2026-08-02 偶發被 Gmail 分到促銷分頁）；（2）任何人都能拿別人的信箱去領取，對方會收到一封沒要求的信 —— 救濟是信中既有的一鍵退訂，這是電子報產業的通行取捨（見 D20）。**若日後投訴率惡化，正確的回頭路是恢復 double opt-in（寄確認連結），不是恢復「驗證即登入」。**

- **D18**: 10 秒覆核抽成共用元件，且**歸 011 所有**（`owner_files`），010 以 touchpoint 使用。理由是那條規則的正典在 011 FR-059，秒數與文案語氣屬於同一個決定；若各留一份，日後把 10 秒調成 15 秒只會改到一邊，而「兩個表單的防呆強度不一樣」是沒有人會發現的漂移。切法：`useEmailReview()` composable 管狀態機（confirming / countdown / start / reset），`EmailReviewNotice.vue` 管那塊琥珀色區塊，**後果說明以 slot 傳入** —— 高價課要講「時段 1 小時後釋出」，領取要講「電子書寄不到」，共用的是機制不是文案。

- **D19**: 回訪的訪客會再看到領取表單，接受（US15）。沒有 session 就無從得知這台瀏覽器領過沒有，而既有的「已領取過」提示已經是好的落點：它指名信箱、提醒查垃圾郵件與促銷分頁、並告訴他換個 Email 可以再領。刻意**不**用 cookie 記住領取狀態 —— 為了一個提示而長期存訪客識別，代價高於收益，何況跨裝置本來就記不住。

- **D20**: 既有會員被陌生人代領時，不擋、不驗證，只確保**不造成破壞**（US15）。可以做的傷害僅止於「收到一封沒要求的免費電子書」，信裡有一鍵退訂；為此加一道驗證等於讓所有正常使用者付出成本去防一個低傷害情境。但**寫入面必須守住**：`nickname` 不覆寫（FR-025）、不建立 session（FR-023）、不動任何既有欄位 —— 未經驗證的表單可以「新增一筆訂閱」，不可以「改既有帳號」。

- **D21**: 挽留與 5 秒二次確認只加在**網頁確認頁**，端點一律不動（US16）。這是 FR-028 的取捨紀錄：能被挽留的人是點了信裡連結、走回站上的人；用郵件用戶端「一鍵退訂」的人本來就不會經過任何頁面，硬要攔他只有一個做法 —— 拿掉 `List-Unsubscribe-Post`，而那正是 8/2 把序列信從 Gmail 促銷分頁拉回主收件匣的東西。**退訂摩擦換來的名單，遠不如送達率值錢**：退不掉的人下一步是按「檢舉垃圾郵件」，那一筆記在網域信譽上，全部收件人一起付 —— 而目前的問題還只是偶發被分到促銷分頁，真被檢舉才是不可逆的那種掉法。

- **D22**: 二次確認沿用 US15 的共用狀態機，並把 `useEmailReview` 改名為 `useDelayedConfirm`（US16）。那 40 行做的事從來就不是「檢查 Email」，是「兩段式確認＋倒數」；第三個用途（停止接收）連 Email 都沒有，名字再不改就開始說謊。改名成本是 3 個 import（`DripSubscribeForm`、`HighTicketBookingWizard`、`Drip/Unsubscribe`），API 與行為零變更；`EmailReviewNotice.vue` **不改名**，它確實只服務 Email 覆核那塊 UI。秒數本來就是參數（覆核 10 秒、挽留 5 秒），D18 的「兩處強度不得漂移」限縮為「Email 覆核那兩處」。ownership 維持 011（D18 原因不變）；日後若出現與 011 完全無關的第四個用途，再考慮移交 000。

- **D23**: 「失去限定免費資源與服務的申請資格」只寫進文案，**不實作跨商品封鎖**（US16）。目前程式唯一硬性執行的是 FR-003（同一門課停止接收後不能再領），其餘一律人工判斷。否決同步實作的理由有三：（1）要先定義封鎖是全站性還是分商品，這是營運政策不是工程決定；（2）誤按停止接收的人需要解鎖後門，等於再開一套後台功能；（3）在**讀取面**設關卡與 D20「寫入面守住、讀取面不設關卡」的既有取向相反。因此措辭用「**可能**不再開放」而非「將不再開放」—— 說得保留一點，才不會有一天被自己的文案綁住。

- **D24**: 同意告知不加必勾 checkbox（US16）。這是免費領取不是契約簽署，多一個必勾等於在剛拆掉驗證碼、專程降低摩擦的表單上，親手加回一道摩擦。被動揭露（送出即同意，文字就在按鈕下方）在電子報產業是通行作法，也符合本模組 single opt-in 的既定定位（D17）。

- **D25**: 預覽走後端渲染真信，不走前端重繪（US17）。前端方案便宜（統計表已經有 `md_content` 就能 `marked` 一下），但它預覽的是**輸入**不是**輸出** —— 看不到 UTM 有沒有戳上、strip style 有沒有把排版吃掉、問候語與主旨怎麼組、頁尾長怎樣。這些正是會出錯的地方，也是唯一值得為它開一個 modal 的東西。代價是多一個端點與一次網路往返，換「所見即所寄」。

- **D26**: 個人化欄位用佔位假資料，不抓真實訂閱者（US17）。抓「該課程最新一位訂閱者」渲染看似更真實，但那份預覽帶的是**可點的真實退訂 token** —— 管理員在後台檢查排版時誤點一下，就把一位真實訂閱者退掉了，而且沒有任何提示。次要理由：預覽畫面會露出該訂閱者的暱稱（後台其他人也看得到），以及像素若一併渲染會污染開信統計。假名 `小明` 定義為 `DripService::PREVIEW_GREETING_NAME` 常數，避免散落在 controller 與測試裡。

- **D27**: 組裝函式放 `DripService`，不另開 `DripLessonMailBuilder` 類別（US17）。它做的事是「這門 drip 課的這封信長什麼樣」，與 `lessonNumber()`、`isLessonUnlocked()` 同屬一件事的不同切面；為了一個函式新增一層抽象，只是把 drip 的知識拆到兩個檔案。真正的重點在 FR-031（只能有一份），放哪裡是次要的。若日後 Mailable 組裝長到需要獨立測試套件，再抽不遲。

- **D28**: 只做畫面預覽，不做「寄測試信到我的信箱」（US17）。兩者解決的問題不同：畫面預覽答「內容與連結對不對」，測試信答「Gmail 會不會剪字、暗色模式會不會爆」。後者是真問題，但它需要另一條寄送路徑（不寫事件、不推進 `emails_sent`、收件者從 admin 帳號取），而每多一條**繞過正常流程的寄送路徑**，就多一個「以為在測試卻寄給了真人」的破口。先做覆蓋 90% 情境的那個，需要時再加 —— 屆時應該掛在同一個 modal 內、共用同一個 `buildLessonMail()`。

- **D29**: 天數存在 **`lessons.drip_day`（絕對天數）**，不是課程層的間隔陣列、也不是「與前一封的間隔」（US18）。三種存法都能表達 Day 0/3/7/14/30，差別在改動時會壞掉的地方：
  課程層 JSON 陣列（`[0,3,7,14,30]`）以位次對應，Lesson 一新增或一拖曳，陣列長度與內容就得跟著校正，而那是兩個不同 controller 的事；存「相對間隔」則是把每一封的絕對日期變成前面所有列的累加 —— 改中間一列，後面全部連動位移，管理員改的是 A 卻動到 B。
  絕對天數存在 Lesson 自己身上，改哪列就只有那列變，也讓 `unlockDay()` 能單筆回答而不必先讀全課程。代價是拖曳重排後天數不會自動跟著換位置，可能變成非遞增 —— 由 FR-037 在存檔時擋下，且那正是管理員應該自己決定的事（換順序時，是內容跟著日期走還是日期跟著內容走，只有他知道）

- **D30**: 編輯介面放 **CourseForm 的排程表格**，LessonForm 不加這個欄位（US18）。節奏是一個關於**序列**的決定 —— 「Day 7 到 Day 14 之間隔太久嗎」這個問題，在只看得到一課的畫面上問不出來。既有的排程預覽表格本來就把整串列在那裡，把唯讀數字換成輸入框，是離「管理員腦中想的東西」最近的一次改動。代價是新增 Lesson 之後要回課程頁補天數，由 FR-038 的自動預設吸收掉大部分情況

- **D31**: `drip_interval_days` **保留不刪**，語意從「發信間隔」降級為「預設間隔」（US18）。刪掉它需要一次性回填正式站所有 drip 課程的 lessons，而那些課程正有進行中的訂閱 —— 回填算錯一課，受影響的是已經在收信的人，且沒有回頭路。保留 fallback 則是零遷移：既有課程一行資料都不動、行為逐字相同，管理員哪天想改成不規則，打開課程頁把預帶的數字改掉就落地了。**這是 D11（觀看期錨點缺席就 fallback 舊公式）同一套取捨**：捨棄「只有一條路徑」的帳面乾淨，換零遷移風險。
  這個欄位留著還有第二個用途 —— FR-038 新增 Lesson 的預設遞增值，所以它不是純粹的歷史包袱

- **D32**: 表格開啟時把 null 的列**預帶**成舊公式的數值，而不是留空白（US18）。空白會讓管理員以為「還沒設定 = 不會寄」，但實際上舊公式正在跑；預帶則讓畫面上的數字永遠等於系統實際的行為，而且他只要按一次儲存，整門課就從隱性的 fallback 變成明確的天數。**預帶是 UI 行為，不是自動寫入** —— 沒有按儲存就不會有任何 migration 之外的資料變動

- **D33**: 遞增驗證放後端 Form Request，前端只做即時提示（US18）。前端紅字是為了讓管理員在打字當下就看到問題，但它擋不住直接打 API 的路徑，也擋不住 FR-038 那條 Lesson 新增路徑；把正典放後端，前端提示壞掉的時候壞的是體驗而不是資料

## Schema

- **US18** 新增 migration `2026_08_16_000003_add_drip_day_to_lessons_table.php` — `lessons` 加 `drip_day`（`unsignedSmallInteger`、nullable、`after('video_access_hours')`），語意為「訂閱後第幾天寄這封信」，null = 沿用 `位次 × drip_interval_days` 舊公式。**不回填、不動 `courses.drip_interval_days`**（D31）。不變量：同一門課程內非 null 的 `drip_day` 依 `sort_order` 嚴格遞增（由 FR-037 在寫入面守住，DB 不加 constraint —— 它是跨列條件，MySQL 表達不了）；位次 0 的值恆為 0

- **US17 無 migration、無 schema 變更** —— 只新增一個唯讀端點與一支前端 modal；`SendDripEmailJob` 的改動為純重構（邏輯搬進 `DripService::buildLessonMail()`），寄出的信 byte-for-byte 不變

- `drip_subscriptions` — 訂閱記錄；(user_id, course_id) unique；emails_sent 恆等於已 dispatch 的信數（也是解鎖游標）；unsubscribe_token 為 UUID、建立時自動產生
- 本模組新增 migration `2026_07_31_000002_add_booked_to_drip_subscriptions_status.php` — status enum 由 4 值擴為 5 值（加 `booked`）。**須以 `Schema::change()` 且重新指定 `->default('active')`**，否則 MySQL MODIFY 會掉預設值；sqlite 測試 DB 也才會更新 CHECK constraint（比照 `2026_07_20_000001` 的寫法）
- 本模組新增 migration `2026_07_31_000003_add_unlock_all_to_drip_subscriptions_table.php` — 加 `unlock_all` boolean default false（放在 status 後），up() 內回填 `UPDATE drip_subscriptions SET unlock_all = 1 WHERE status = 'converted'`；語意為「此訂閱不受解鎖游標限制」，只服務改版前既有轉換者的向後相容
- `drip_conversion_targets` — drip 課程 ↔ 目標課程多對多；購買任一 target 即轉換
- `drip_email_events` — 開信（opened）/教室促銷點擊（clicked）/**實際發信（sent）** 事件；(subscription_id, lesson_id, event_type) unique 保證去重；只有 created_at。`sent` 事件的 created_at 即該封信對該訂閱者的實際寄出時刻，作為影片觀看期計時錨點；target_url/ip/user_agent 為 null
- 本模組新增 migration `2026_07_20_000001_add_sent_to_drip_email_events_event_type.php` — 將 event_type enum 由 `['opened','clicked']` 擴為 `['opened','clicked','sent']`（僅擴值，不動既有資料；down 還原為兩值前需先確保無 sent 列）
- `courses` 增欄（本模組 migration）— `course_type`（standard/drip，預設 standard）、`drip_interval_days`（nullable）
- `lessons` 增欄（本模組 migration，promo 欄位適用所有課程類型）— `promo_delay_seconds`（null=停用/0=立即）、`promo_html`、`promo_url`（varchar 500，教室追蹤按鈕）、`reward_html`（drip 限定）、`video_access_hours`（null=無限期）

- **US15 無 migration、無 schema 變更** —— 只改寫入行為：`users.email_verified_at` 由領取當下的 `now()` 改為 null（新列才適用，**既有列不回填** —— 那些人當初確實通過了驗證碼，改掉等於竄改歷史）；`users.nickname` 由「一律覆寫」改為「僅在空值時填入」。`verification_codes` 表**保留不動**，登入與電子報訂閱仍在用。

- **US16 無 migration、無 schema 變更、無後端變更** —— 全部是前台文案與互動：新增一個共用文案元件、改寫停止接收確認頁、重新命名一支 composable。`DripSubscriptionController::unsubscribe()` 與路由**一行都不動**（FR-028）。

## Tasks（修正一般課程被發信排程擋住）

- [x] T017 `UpdateCourseRequest::prepareForValidation()`：非 drip 一律 `drip_days => null`；`withValidator` 的遞增檢查加 `course_type === 'drip'` 前提（FR-040）in app/Http/Requests/Admin/UpdateCourseRequest.php
- [x] T018 `CourseForm`：`submit()` 以 `transform` 在非 drip 時剔除 `drip_days`，create/update 兩條路徑都套用 in resources/js/Components/Admin/CourseForm.vue
- [x] T019 回歸測試：有章節（sort_order 每章從 1 起算）的一般課程送出 `drip_days` 仍存檔成功、且 `lessons.drip_day` 維持 null in tests/Feature/Drip/VariableScheduleTest.php

## Tasks（US18 — 每封信各自設定發送日）

**Phase 1：資料層**
- [x] T001 migration `2026_08_16_000003_add_drip_day_to_lessons_table.php`：`lessons` 加 `drip_day` unsignedSmallInteger nullable after `video_access_hours` in database/migrations/2026_08_16_000003_add_drip_day_to_lessons_table.php
- [x] T002 `Lesson`：`drip_day` 加入 `$fillable` 與 `casts`（integer）in app/Models/Lesson.php

**Phase 2：Service 單一入口（相依 T002）**
- [x] T003 `DripService`：新增 `public function unlockDay(Lesson $lesson): int` — `$lesson->drip_day ?? $this->lessonPosition($lesson) * (int) ($lesson->course->drip_interval_days ?? 0)`；沿用 `lessonPositions` 的 per-course memoise，避免教室逐 Lesson 觸發查詢 in app/Services/DripService.php
- [x] T004 `DripService::getUnlockedLessonCount()`：改為載入該課程 lessons（orderBy sort_order）後計數 `unlockDay ≤ daysSince`，上限 Lesson 總數；移除 `floor($daysSince / $interval) + 1` 與 `$interval <= 0` 分支（防呆改由 `unlockDay()` 回 0 天然覆蓋，FR-002）in app/Services/DripService.php
- [x] T005 [P] `DripService::daysUntilUnlock()`：解鎖日改用 `unlockDay($lesson)`，其餘（已解鎖回 0、停信回 -1）不動 in app/Services/DripService.php
- [x] T006 [P] `DripService::getVideoAccessExpiresAt()`：fallback 錨點改為 `subscribed_at->addDays($this->unlockDay($lesson))` in app/Services/DripService.php

**Phase 3：後台寫入面（相依 T003）**
- [x] T007 `UpdateCourseRequest`：加 `drip_days` 陣列規則（`nullable|array`、`drip_days.*` integer 0–365），並在 `withValidator` 檢查 key 全屬該課程、依 sort_order 嚴格遞增、首封為 0；錯誤訊息指出第幾封（FR-037）in app/Http/Requests/Admin/UpdateCourseRequest.php
- [x] T008 `CourseController::update()`：transaction 內批次寫回 `lessons.drip_day`；`course_type=standard` 時一併把該課程所有 `drip_day` 清為 null in app/Http/Controllers/Admin/CourseController.php
- [x] T009 `CourseController::edit()`：`courseLessons` 的 select 加 `drip_day` in app/Http/Controllers/Admin/CourseController.php
- [x] T010 `LessonController::store()`：drip 課程且該課程已有非 null `drip_day` 時，新 Lesson 帶 `max(drip_day) + (drip_interval_days ?: 7)`（FR-038）in app/Http/Controllers/Admin/LessonController.php
- [x] T011 [P] `StoreCourseRequest`/`UpdateCourseRequest`：`drip_interval_days` 的 messages 文案改為「預設間隔天數」語意（規則不動）in app/Http/Requests/Admin/StoreCourseRequest.php

**Phase 4：前端（相依 T009）**
- [x] T012 `CourseForm`：`schedulePreview` 改為可編輯表格 —— form 加 `drip_days`（lesson_id ⇒ day），初值取 `lesson.drip_day ?? index × interval`（D32 預帶）；每列顯示「第 N 封／Day 輸入框／`+X 天`／Lesson 標題」；位次 0 固定 0 且 disabled in resources/js/Components/Admin/CourseForm.vue
- [x] T013 `CourseForm`：非遞增即時紅字提示（computed 檢查），並把 `drip_interval_days` 的 label/help 文案改為「預設間隔天數」＋說明其兩個用途 in resources/js/Components/Admin/CourseForm.vue

**Phase 5：測試（相依 T004、T007）**
- [x] T014 `VariableScheduleTest`：Day 0/3/7/14/30 課程，subscribed_at 回推 0/3/6/7/13/14/29/30 天各自斷言應寄數；`drip_day` 全 null 的課程與改版前逐字相同（等距）；`drip_day` 為 null 且 interval 為 0/null 時全開（FR-002）in tests/Feature/Drip/VariableScheduleTest.php
- [x] T015 `VariableScheduleTest`：後台 PUT 課程送非遞增 `drip_days` 回 422、送遞增值寫入成功；drip → standard 後 `drip_day` 全清為 null in tests/Feature/Drip/VariableScheduleTest.php
- [x] T016 既有 drip 測試全數續過（`LessonPositionTest`、`VideoAccessAnchorTest`、`FunnelStopTest`）—— 它們全部用 `drip_interval_days` 且不設 `drip_day`，是 fallback 路徑的回歸網 in tests/Feature/Drip/

## Tasks（US17 — 後台預覽 Lesson 信件實際樣貌）

Phase 1 — 組裝邏輯抽出（純重構，後面全部相依）
- [x] T157 `DripService`：新增常數 `PREVIEW_GREETING_NAME = '小明'` 與 `buildLessonMail(Lesson $lesson, ?DripSubscription $subscription = null, ?User $user = null): DripLessonMail`；把 `SendDripEmailJob` 的 classroomUrl / unsubscribeUrl / UTM 陣列 / `{{classroom_url}}` 代換 / `EmailMarkdownService::toHtml` / `EmailLinkTagger` 戳章 / openPixelUrl signed route 整段移入，並把 `resolveGreetingName()`、`stripStylesForEmail()` 一併移為 private 方法；`$subscription === null` → `unsubscribeUrl = '#'` 且 `openPixelUrl = ''`，`$user === null` → 問候語用常數（FR-031、FR-032）in app/Services/DripService.php
- [x] T158 `SendDripEmailJob::handle()` 改為 `Mail::to($user->email)->send($this->dripService->buildLessonMail($lesson, $subscription, $user))`，刪除已搬走的私有方法；狀態判斷、log、sent 事件記錄一行不動 —— 本任務**不得**改變任何寄出行為 in app/Jobs/SendDripEmailJob.php
- [x] T159 跑 `php artisan test --filter=Drip` 確認既有 drip 測試零修改續過（重構的驗收標準就是它們不用改）

Phase 2 — 預覽端點
- [x] T160 新增 `Admin\DripLessonPreviewController`（單一 `__invoke(Lesson $lesson)`）：`abort_unless($lesson->course?->course_type === 'drip', 404)` → `$mail = buildLessonMail($lesson)` → 回 `response()->json(['subject' => $mail->envelope()->subject, 'html' => $mail->render()])`（FR-030、FR-033）in app/Http/Controllers/Admin/DripLessonPreviewController.php
- [x] T161 staff 群組內註冊 `GET /admin/drip/lessons/{lesson}/email-preview` → `admin.drip.lesson-email-preview`（緊接 high-ticket-leads 路由，與宿主頁面同權限）in routes/web.php〔touchpoint 000〕

Phase 3 — 前端 modal
- [x] T162 [P] 新增 `LessonEmailPreviewModal.vue`：props `lesson`（含 id/title）與 `open`，開啟時 `fetch` 端點取 JSON；三種狀態（載入中／錯誤／內容），內容區為主旨列 + `<iframe sandbox :srcdoc="html">`（FR-034）；Esc 與背景點擊關閉，關閉後清空內容避免下次開啟閃到上一封 in resources/js/Components/Admin/Leads/LessonEmailPreviewModal.vue
- [x] T163 `SubscriberListTab.vue`：「Lesson 發信統計」表格「課程」欄的標題改為可點（`cursor-pointer` + `hover:text-brand-teal hover:underline`，依 Development Rules），點擊設定 `previewLesson` 並開 modal；表格其餘欄位與統計顯示不變 in resources/js/Components/Admin/Leads/SubscriberListTab.vue

Phase 4 — 驗證
- [x] T164 新增測試 `LessonEmailPreviewTest`：admin 取得 200 且 `html` 含 Lesson 內文、含頁尾「停止接收」、**不含** `openPixelUrl` 的 `<img`、連結含 `utm_source=drip`；`subject` 含問候語 `小明`；非 admin 不得存取；非 drip 課程的 Lesson 回 404；預覽後 `drip_email_events` 筆數不變（FR-032）in tests/Feature/Drip/LessonEmailPreviewTest.php
- [x] T165 `php artisan test` 全綠 ＋ `npm run build` exit 0
- [ ] T166 使用者實測：後台訂閱者名單點統計表標題開得起 modal、內容與實際收到的信一致、換一封信不會殘留上一封、手機寬度下 modal 可讀可捲

## Tasks（US16 — 領取同意告知與停止接收前的挽留）

Phase 1 — 共用元件改名（先做，後面兩 phase 都相依）
- [x] T135 `useEmailReview.js` 更名為 `useDelayedConfirm.js`，函式改名 `useDelayedConfirm(seconds = 10)`，API（`confirming`/`countdown`/`start()`/`reset()`/`stop()`）與行為零變更；檔頭註解改寫為「兩段式確認 + 倒數」的通用描述，並保留「Email 覆核正典為 011 FR-059」的指路 in resources/js/composables/useDelayedConfirm.js〔touchpoint 011〕
- [x] T136 [P] `HighTicketBookingWizard` 更新 import 與呼叫名稱，秒數維持 10，行為不得改變（BookingWizardTest 須零修改續過）in resources/js/Components/Course/HighTicketBookingWizard.vue〔touchpoint 011〕
- [x] T137 [P] `DripSubscribeForm` 更新 import 與呼叫名稱，秒數維持 10 in resources/js/Components/Course/DripSubscribeForm.vue

Phase 2 — 同意告知（可與 Phase 3 並行）
- [x] T138 新增 `ClaimConsentNotice.vue`：單一段落、`text-xs text-gray-500 leading-relaxed`、無 props、文案硬編於元件內（FR-027 的單一來源）in resources/js/Components/Course/ClaimConsentNotice.vue
- [x] T139 `DripSubscribeForm` 於送出鈕**下方**掛 `ClaimConsentNotice` in resources/js/Components/Course/DripSubscribeForm.vue
- [x] T140 `Course/Show.vue` 會員一鍵領取區塊（`canSubscribe && auth.user` 那張卡）於領取鈕下方掛同一個元件 in resources/js/Pages/Course/Show.vue〔touchpoint 002〕

Phase 3 — 停止接收挽留與二次確認
- [x] T141 `Drip/Unsubscribe.vue` 以挽留說明取代紅色警告框：改為琥珀色（`bg-amber-50 border-amber-200 text-amber-900`）、內容為 US16 驗收所列文案；頁首圖示與標題語氣同步由「警告」調為「說明」in resources/js/Pages/Drip/Unsubscribe.vue
- [x] T142 `Drip/Unsubscribe.vue` 接上 `useDelayedConfirm(5)`：`confirmUnsubscribe` 改為先 `start()`，回傳 false 即 return；按鈕四段文案（確定停止接收／請再想一下（N）／我確定，停止接收／處理中…），倒數期間 `:disabled`；「取消」連結全程保留 in resources/js/Pages/Drip/Unsubscribe.vue
- [x] T143 `subscription.status === 'unsubscribed'` 分支不受影響（不顯示挽留與確認鈕），視覺維持現狀 in resources/js/Pages/Drip/Unsubscribe.vue

Phase 4 — 驗證
- [x] T144 新增測試：`POST /drip/unsubscribe/{token}` 單次請求即 status=unsubscribed、重複 POST 不報錯（守住 FR-028 的端點不變性）in tests/Feature/Drip/DripMailDeliverabilityTest.php
- [x] T145 `php artisan test` 全綠 ＋ `npm run build` exit 0
- [ ] T146 使用者實測：領取頁看得到同意告知（訪客與登入兩種）、信中停止接收連結進站看得到挽留、按第一次會倒數 5 秒、第二次才真的停止；郵件用戶端的一鍵退訂（若 Gmail 顯示）仍一次生效

## Tasks（US15 — 免登入領取與 Email 覆核防呆）

Phase A — 共用元件抽取（011 owner，先做，兩邊都相依）
- [x] T125 抽出 `useEmailReview()` composable：`confirming` / `countdown` / `start()` / `reset()` / `stop()`，秒數為參數（預設 10），`onUnmounted` 自動清 timer in resources/js/composables/useEmailReview.js〔touchpoint 011〕
- [x] T126 抽出 `EmailReviewNotice.vue`：琥珀色區塊 + 大字體 Email + 「這個 Email 不對，回去修改」按鈕（emit `edit`），後果說明以 default slot 傳入 in resources/js/Components/EmailReviewNotice.vue〔touchpoint 011〕
- [x] T127 `HighTicketBookingWizard` 改用上述兩者，刪掉內嵌的 `emailConfirming` / `emailCountdown` / `emailTimer` / `resetEmailConfirm`；行為與文案不得改變（既有 BookingWizardTest 須續過）in resources/js/Components/Course/HighTicketBookingWizard.vue〔touchpoint 011〕

Phase B — 後端（可與 Phase A 並行）
- [x] T128 **測試先行**：領取後 `Auth::check()` 為 false、新帳號 `email_verified_at` 為 null、既有會員 nickname 不被覆寫（原有值保留、原為空才填）、蜜罐命中回錯誤且不建訂閱、第一封信仍寄出 in tests/Feature/Drip/GuestClaimTest.php
- [x] T129 新增 `StoreDripClaimRequest`：course_id / email / nickname（規則沿用 US1）＋ `website` 蜜罐（`nullable|prohibited`，訊息「領取失敗」）in app/Http/Requests/StoreDripClaimRequest.php
- [x] T130 `subscribe()` 改為單次完成：查/建 user（`email_verified_at` 不給值、nickname 僅空值時填）→ `DripService::subscribe()` → flash `drip_subscribed`；移除 `Auth::login()` 與 `last_login_*` 寫入；`verify()` method 整組刪除；`VerificationCodeService` / `VerificationCodeMail` 的 import 一併移除（FR-023 / FR-025）in app/Http/Controllers/DripSubscriptionController.php
- [x] T131 `/drip/subscribe` 加 `throttle:10,1`；刪除 `/drip/verify` 路由（FR-026）in routes/web.php〔touchpoint 000〕

Phase C — 前台（相依 A + B）
- [x] T132 `DripSubscribeForm` 改為單一表單：移除 step/code 狀態機與 `drip_email`/`drip_course_id`/`drip_nickname` flash 流程；接上 `useEmailReview` + `EmailReviewNotice`（後果文案：電子書寄到這個地址、打錯不會有任何通知、寄件者為「經營者時間銀行」、找不到請查垃圾郵件與促銷分頁）；補視覺隱藏的 `website` 蜜罐 input；送出鈕三段文案（領取／請再看一眼 Email（N）／Email 正確，確認領取）in resources/js/Components/Course/DripSubscribeForm.vue

Phase D — 驗證
- [x] T133 `php artisan test` 全綠（基準 486 passed，既有 ClaimWordingTest 須零修改續過）＋ `npm run build` exit 0
- [ ] T134 使用者實測：以未登入瀏覽器領取一次 —— 確認倒數 10 秒、確認送出後仍是未登入狀態、第一封信有收到、回訪再送出會看到「已領取過」提示

## Tasks（US12 — 觀看期改以實際發信時間起算）

Phase 1 — Schema
- [x] T001 新增 migration `add_sent_to_drip_email_events_event_type`：ALTER `drip_email_events.event_type` enum 加 `'sent'` in database/migrations/2026_07_20_000001_add_sent_to_drip_email_events_event_type.php

Phase 2 — 寫入錨點
- [x] T002 [P] `SendDripEmailJob::handle()` 於 `Mail::send` 成功後，firstOrCreate 一筆 `(subscription_id, lesson_id, 'sent')` DripEmailEvent；包 try/catch，失敗僅 Log::warning 不 rethrow in app/Jobs/SendDripEmailJob.php

Phase 3 — 讀錨點算觀看期（相依 T001/T002）
- [x] T003 `DripService`：新增 `getSentAtMap(DripSubscription): Collection`（單一查詢 pluck created_at,lesson_id，event_type='sent'，值轉 Carbon）in app/Services/DripService.php
- [x] T004 `DripService`：`getVideoAccessExpiresAt` / `isVideoAccessExpired` / `getVideoAccessRemainingSeconds` 加 `?Carbon $sentAt` 參數；$sentAt 有值 → `$sentAt + hours`，null → fallback 現有 `subscribed_at + sort_order×interval 天` 公式 in app/Services/DripService.php
- [x] T005 `ClassroomController`：currentLesson 前用 `getSentAtMap` 取一次 map，傳入 `->get($lesson->id)` 給 formatLessonFull（消 N+1）in app/Http/Controllers/Member/ClassroomController.php

Phase 4 — 後台發信時間顯示（相依 T002）
- [x] T006 [P] `DripService::getSubscriberStats`：per-lesson 加 `last_sent_at`（sent 事件 MAX(created_at)），併入 eventStats 聚合與 lessonStats 輸出 in app/Services/DripService.php
- [x] T007 [P] `Subscribers.vue`：per-Lesson 統計表加「最近發信」欄，格式化 last_sent_at，無值顯示「—」 in resources/js/Pages/Admin/Courses/Subscribers.vue

Phase 5 — 驗證
- [x] T008 自動化驗證（feature test 取代手動）：VideoAccessAnchorTest 覆蓋 fallback 舊公式、sent 錨點起算、null 無倒數、Job 冪等寫 sent 事件 in tests/Feature/Drip/VideoAccessAnchorTest.php

## Tasks（US13 + US14 — 達標即停信、不再全開）

Phase 1 — Schema（US14 的 T002 為後續判定前提）
- [x] T101 新增 migration：`drip_subscriptions.status` enum 加 `'booked'`，用 `Schema::change()` 並保留 `->default('active')` in database/migrations/2026_07_31_000002_add_booked_to_drip_subscriptions_status.php
- [x] T102 新增 migration：加 `unlock_all` boolean default false（after status），up() 回填既有 `status='converted'` 為 true；down() dropColumn in database/migrations/2026_07_31_000003_add_unlock_all_to_drip_subscriptions_table.php

Phase 2 — Model 與 Service 核心（相依 Phase 1）
- [x] T103 `DripSubscription`：加常數 `STOPS_SENDING = ['booked','converted','unsubscribed']`、`FUNNEL_DONE = ['booked','converted']`；`unlock_all` 進 fillable + cast boolean in app/Models/DripSubscription.php
- [x] T104 `DripService::isLessonUnlocked()` 改寫：`unlock_all || status==='completed'` → true，其餘一律 `sort_order < emails_sent` in app/Services/DripService.php
- [x] T105 `DripService::daysUntilUnlock()`：停信狀態（STOPS_SENDING）一律回 -1 in app/Services/DripService.php
- [x] T106 `DripService::checkAndConvert()`：狀態條件由 `where('status','active')` 改 `whereIn('status', ['active','booked'])`（booked 升級 converted）in app/Services/DripService.php
- [x] T107 `DripService::checkAndBook(string $email, Course $bookedCourse): void`：email 查 user（查無 return）→ 查 drip_conversion_targets → 該 user 的 active 訂閱標 booked + status_changed_at + Log::info in app/Services/DripService.php

Phase 3 — 觸發點接線（相依 Phase 2）
- [x] T108 [P] `SendDripEmailJob::handle()` 跳過清單改吃 `DripSubscription::STOPS_SENDING` in app/Jobs/SendDripEmailJob.php
- [x] T109 [P] `HighTicketBookingService::book()`：lead 建立後 try/catch 呼叫 `checkAndBook($data['email'], $course)`，失敗僅 Log::error in app/Services/HighTicketBookingService.php
- [x] T110 [P] `HighTicketLeadService::convertLead()`：Purchase 建立後呼叫 `checkAndConvert($user, Course::find($courseId))`，try/catch + log in app/Services/HighTicketLeadService.php
- [x] T111 [P] `MemberController::giftCourse()`：贈課迴圈內 Purchase 成功後呼叫 `checkAndConvert($member, $course)`（沿用既有 try/catch）in app/Http/Controllers/Admin/MemberController.php

Phase 4 — 教室豁免（相依 Phase 2）
- [x] T112 `ClassroomController::formatLessonFull()`：`$isConverted` 改為 `$isFunnelDone = in_array($status, DripSubscription::FUNNEL_DONE)`，三處 props 條件同步 in app/Http/Controllers/Member/ClassroomController.php
- [x] T113 [P] `Classroom.vue`：VideoAccessNotice 的 `dripSubscription?.status !== 'converted'` 改為排除 booked + converted in resources/js/Pages/Member/Classroom.vue

Phase 5 — 後台與前台顯示（相依 Phase 1）
- [x] T114 `CourseController::subscribers()`：狀態篩選白名單加 `booked`、statusStats 加 `booked_count`、stats props 加 `booked` in app/Http/Controllers/Admin/CourseController.php
- [x] T115 `DripService::getSubscriberStats()`：回傳加 `booking_rate`（booked / 總訂閱數，分母 0 為 null）in app/Services/DripService.php
- [x] T116 [P] `Subscribers.vue`：狀態卡加「已預約」、篩選 option 加 booked、徽章 label/色（琥珀）加 booked、指標區顯示預約率與轉換率 in resources/js/Pages/Admin/Courses/Subscribers.vue
- [x] T117 [P] `Course/Show.vue`：`subscriptionStatusLabel` 加 `booked: '已預約'`、徽章 class 加 booked 配色 in resources/js/Pages/Course/Show.vue

Phase 6 — 驗證
- [x] T118 新增 FunnelStopTest：預約高價課→active 訂閱轉 booked 且排程不再發信、查無 user 不報錯、booked 後購買升級 converted、新 converted 只看得到已寄達 Lesson、`unlock_all=true` 舊訂閱仍全開、贈課/Lead 開通觸發轉換 in tests/Feature/Drip/FunnelStopTest.php
- [x] T119 `php artisan test` 全綠 + `npm run build` 通過

## Tasks（修正 sort_order 被當索引）

- [x] T120 `DripService::lessonPosition()`：以課程 `orderBy(sort_order)` 的 0 起算位次取代 `sort_order` 值，按課程 memoise；`isLessonUnlocked` / `daysUntilUnlock` / `getVideoAccessExpiresAt` 三處改用（FR-022）in app/Services/DripService.php
- [x] T121 `getSubscriberStats()` 已發送數改以位次比較，並把每課一次 COUNT 併成單一 `GROUP BY emails_sent` 分佈 in app/Services/DripService.php
- [x] T122 新增 LessonPositionTest：Lesson 刻意以 1 起算與跳號建立，釘住解鎖、解鎖日與後台已發送數/開信率 in tests/Feature/Drip/LessonPositionTest.php
- [x] T123 `VideoAccessAnchorTest` 的 fallback 案例補齊前兩課（原本只建一課卻假設它在位次 2）in tests/Feature/Drip/VideoAccessAnchorTest.php
- [ ] T124 部署後確認正式站訂閱者名單第二封信的已發送/開信率有數字，且只收到第 1 封的訂閱者打得開第 1 課

## Tasks（修正會員一鍵領取 500 error）

- [x] T147 `memberSubscribe()` 補 `use Illuminate\Http\Request;`（FR-029）in app/Http/Controllers/DripSubscriptionController.php
- [x] T148 新增回歸測試：已登入會員 POST `/member/drip/subscribe/{course}` 回 302（非 500），訂閱成功建立 in tests/Feature/Drip/MemberSubscribeTest.php

## 進度日誌

- 2026-08-17: 修正一般課程存不了檔 —— `CourseForm` 不分課程類型都送出 `drip_days`，有章節的一般課程（sort_order 每章從 1 起算）因此被遞增檢查擋下；改為前端 transform 剔除 + 後端 `prepareForValidation()` 清空（FR-040），補有章節的回歸測試；`php artisan test` 705 passed
- 2026-08-16: US18 可變發信頻率 — 新增 `lessons.drip_day`（null 走舊等距公式，既有課程零遷移），解鎖日收斂為 `DripService::unlockDay()` 單一入口（應寄數／daysUntilUnlock／觀看期 fallback 三處改吃它），CourseForm 排程預覽改為可編輯表格並預帶現行天數，遞增驗證擋在 `UpdateCourseRequest`，新增 Lesson 自動接續天數；`php artisan test` 702 passed / 2933 assertions、`npm run build` exit 0
- 2026-08-08: US17 後台預覽 Lesson 信件 — 寄信組裝抽為 `DripService::buildLessonMail()`（Job 改呼叫，既有 45 個 drip 測試零修改續過），新增 staff 權限的唯讀預覽端點與 `LessonEmailPreviewModal`，統計表標題可點開 sandbox iframe 預覽；預覽用假資料佔位、不寫任何事件

- 2026-08-07: 修正會員一鍵領取 500 error（T147/T148 / FR-029）— `DripSubscriptionController::memberSubscribe()` 的 `Request` 型別缺 `use Illuminate\Http\Request;`，在 `App\Http\Controllers` 命名空間下被解析成不存在的 `App\Http\Controllers\Request`，路由參數綁定丟 `ReflectionException` → 500（正式站 2026-08-07 事件，`userId:93`）。TDD：先寫 `MemberSubscribeTest` 重現同一個錯誤訊息確認紅，補 import 後綠。全套 524 passed，純後端改動不涉及前端。
- 2026-08-07: US16 — 領取表單（訪客＋會員一鍵）補同意告知（共用 ClaimConsentNotice）；停止接收確認頁改為挽留說明＋5 秒二段式確認，端點維持一鍵退訂單次生效（新增回歸測試）；useEmailReview 更名 useDelayedConfirm。順帶更正 8/2 事件記錄：是 Gmail 促銷分頁（偶發），非垃圾郵件匣。522 passed、npm build 綠。

- 2026-08-05: US15 完成 — 免費領取移除驗證碼與自動登入。拆的順序是關鍵：先拿掉 `Auth::login()`，驗證碼才失去保護對象。實作時浮現一個規劃時就預期、但只有真的拆了才會出事的破口 —— 原本 `$user->update(['nickname' => ...])` 一律覆寫，沒了驗證碼等於陌生人能改既有會員的顯示名稱，現在只在原值為空時填入，並有測試釘住。新帳號 `email_verified_at` 留 null（沒驗證就不宣稱已驗證，全站無 `verified` gate 依賴它），既有列不回填。10 秒覆核抽成 `useEmailReview()` + `EmailReviewNotice.vue`，後果說明走 slot（高價課講時段釋出、領取講電子書寄不到），`HighTicketBookingWizard` 改為引用，既有 BookingWizardTest 38 passed 行為不變。順手清掉 `HandleInertiaRequests` 裡的 `drip_email` / `drip_course_id` / `drip_nickname` 三個 flash key —— 兩步驟流程沒了，它們是孤兒。新增 GuestClaimTest（7 tests），既有 ClaimWordingTest **零修改**續過，全套 493 passed、npm build 綠。
- 2026-08-05: 修正 `sort_order` 被當成索引（T120–T123 / FR-022）— 業主回報訂閱者名單第二封信有人開信卻顯示 0 已發送、開信率「—」。查下來不是統計壞掉，是整個模組對「第幾封信」的定義錯了：`emails_sent` 是**信的封數**，程式卻拿它跟 `sort_order` 的**值**比。`LessonController` 以 `max + 1` 編號，所以後台建出來的課程是 1、2、3，而 `processSubscription()` 一直是按位次 `$lessons[$i]` 寄信 —— 兩邊差一格。正式站（`猴子也能懂的 AI Road Map`，480 位訂閱者）的實際後果比回報的更嚴重：**收到第 1 封信的 85 人連第 1 課都打不開，收到第 2 封的 395 人打不開剛寄給他們的第 2 課**（57 人開了那封信卻進不去），統計只是同一個 off-by-one 比較顯眼的那一面。修法是不再把排序鍵當索引：位次由 `lessonPosition()` 從 `orderBy(sort_order)` 的序列算出（按課程 memoise，教室一頁會逐 Lesson 呼叫），解鎖、解鎖日、觀看期 fallback 錨點、後台已發送數四處共用。**這個 bug 活下來是因為既有測試全部以 `sort_order` 0、1、2 建 Lesson** —— 一個後台從來產不出來的慣例，於是測試與程式一起錯得很一致；新的 LessonPositionTest 刻意用 1 起算與跳號（10、20、30）建課。順手把每課一次 COUNT 的 N+1 併成單一 `GROUP BY emails_sent` 分佈。全套 467 passed。
- 2026-08-04: 訂閱者後台頁搬家（011 US8 touchpoint）— `Pages/Admin/Courses/Subscribers.vue` 改為 `Components/Admin/Leads/SubscriberListTab.vue`，掛在 Leads 名單頁的第二個 tab，課程改頁內下拉（只列 drip 課）。資料組裝從 `CourseController@subscribers` 下沉為 `DripService::subscriberPageData()`（該 action 與 `/admin/courses/{course}/subscribers` 路由已刪）。顯示內容與統計邏輯不變。
- 2026-08-03: 序列信的 `md_content` 渲染改用 `EmailMarkdownService::toHtml()`（011 FR-021 touchpoint）— 原本裸 `new CommonMarkConverter()` 會吃掉單次換行，小節內容手動斷行在信裡會黏成一段。現在單次換行即 `<br>`，空一行仍是新段落；`stripStylesForEmail()` 與寄送流程不動。查驗現有 16 篇有 md_content 的小節，3 篇（id 20、46 各多 7 / 2 個換行；id 14 無變化）會多出換行，皆為作者原本就手動斷行的位置。
- 2026-08-02: 序列信偶發被 Gmail 分到「促銷」分頁（非垃圾郵件匣，2026-08-06 更正記錄）— 查證 DKIM/SPF/DMARC 皆正常，缺的是 `List-Unsubscribe` 標頭（電子報有、連鎖信從來沒有），加上當天把內文唯一的「退訂」字樣改掉，等於兩個訊號都沒了。補標頭 + `List-Unsubscribe-Post: One-Click` + CSRF 豁免（順帶修好電子報宣告卻會 419 的一鍵退訂），內文停止接收行改為空兩行＋分隔線＋12px 淺灰並附英文 Unsubscribe。新增 DripMailDeliverabilityTest（3 tests）。
- 2026-08-02: 修正已領取者的提示 — 原本引導「登入後即可繼續觀看」是錯的（交付走 Email，站上看不到），改為醒目提示框：指名信箱、垃圾郵件提醒、換別的 Email 再領；flash `drip_already_claimed` 由布林改為帶該 Email。215→218 全綠。
- 2026-08-02: 前台免費商品語彙統一 — 訂閱→領取、課程→商品、退訂→停止接收信件（涵蓋領取表單、銷售頁成功卡與徽章、教室空狀態、停止接收頁、序列信內文、DripService／ClassroomController 訊息）；「此 Email 已訂閱此課程」改為指向信箱的提示並新增 flash `drip_already_claimed`（HandleInertiaRequests 白名單同步）。新增 ClaimWordingTest（3 tests），全套 215 passed、npm build 綠。
- 2026-08-02: 訪客訂閱表單 Step 2 按鈕文案改「確認驗證碼」；銷售頁訂閱徽章 active 改「已領取」並移除「前往教室」連結（檔案為 002 owner，本模組僅記語彙決定）。
- 2026-08-01: LessonForm 的 Markdown 內容欄在 drip 課程時新增固定格式說明（信件開頭自動加「Hi {暱稱}，」、主旨為「{暱稱}，{小節標題}」、結尾自動附退訂連結，正文不必再寫稱呼），對應 US8 新增一條驗收；純 UI 文案，無行為變更。npm build 綠。
- 2026-07-31: US13+US14 完成（/sync 對帳：touchpoint 說明與 code_files 已補齊，無額外行為差異） — 達標即出漏斗。高價課預約（HighTicketBookingService）新增 booked 狀態並停止序列信；後台 Lead 開通與贈課補上 checkAndConvert（booked→converted 可升級）；停信/豁免狀態集合收斂為 DripSubscription::STOPS_SENDING / FUNNEL_DONE；converted 不再全開小節（解鎖凍結在 emails_sent），改版前既有 converted 由 unlock_all 旗標回填保留全開；後台訂閱者頁加「已預約」統計卡/篩選/徽章與預約率。順手補 ClassroomController 缺失的 DripSubscription import（型別提示原本解析到不存在的類別）。新增 FunnelStopTest（8 tests），全套 192 passed、npm build 綠。
- 2026-07-31: drip 銷售頁右側懸浮面板同步「免費領取」CTA（touchpoint: Course/Show.vue，owner 002；原本面板對 drip 整個關閉、頁首 CTA 點擊無反應），面板僅在 `canSubscribe` 且無現有訂閱時出現、點擊捲至訂閱區。
- 2026-07-21: US12 完成 — 影片觀看期改以實際發信時間起算。SendDripEmailJob 於寄信成功後 firstOrCreate 一筆 sent 事件當錨點（冪等）；DripService 加 getSentAtMap + 三方法吃 `?Carbon $sentAt`，缺席 fallback 舊公式；ClassroomController 傳入錨點；後台訂閱者頁加「最近發信」欄。新增 VideoAccessAnchorTest（4 tests）。全測試 167 passed、npm build 綠。
- 2026-07-11: 銷售頁 drip「免費領取」CTA 標題改「立刻免費領取【課程名】！」，並統一登入／未登入視覺——訪客表單（DripSubscribeForm）由 indigo 改品牌配色（brand-gold 按鈕、brand-teal 聚焦），登入狀態區塊（touchpoint: Course/Show.vue，owner 002）改為同款白底卡片＋同標題＋滿版金按鈕。
- 2026-07-06: 領域重組 — 自 005-drip-email 重寫，依實際 codebase 校正；整併 partial/planned 故事

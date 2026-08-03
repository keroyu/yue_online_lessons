---
id: 010-drip-email
status: done
owner_files:
  - app/Http/Controllers/DripSubscriptionController.php
  - app/Http/Controllers/DripTrackingController.php
  - app/Http/Requests/StoreDripSubscriptionRequest.php
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
  - resources/js/Pages/Drip/Unsubscribe.vue
  - resources/js/Pages/Admin/Courses/Subscribers.vue
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
  - tests/Feature/Drip/VideoAccessAnchorTest.php
  - tests/Feature/Drip/FunnelStopTest.php
  - tests/Feature/Drip/ClaimWordingTest.php
  - tests/Feature/Drip/DripMailDeliverabilityTest.php
touchpoints:
  - file: resources/js/Components/Admin/CourseForm.vue
    owner: 004-course-admin
    why: 「連鎖 Email 設定」分頁 — course_type、drip_interval_days、目標課程選擇、發信排程預覽
  - file: resources/js/Components/Admin/LessonForm.vue
    owner: 004-course-admin
    why: Lesson 的 promo_delay_seconds / promo_html / promo_url / reward_html / video_access_hours 欄位、CTA 快速插入、{{classroom_url}} 插入按鈕與影片警示、drip 信固定格式（開頭問候 + 結尾退訂）的說明區塊
  - file: app/Http/Controllers/Admin/CourseController.php
    owner: 004-course-admin
    why: subscribers() action — 組裝訂閱者清單、狀態統計（US13 起含 booked_count）、預約率/轉換率與 Lesson 開信/點擊分析（呼叫 DripService）
  - file: app/Http/Controllers/Admin/LessonController.php
    owner: 004-course-admin
    why: drip 課程新增 Lesson 時呼叫 DripService::reactivateCompletedSubscriptions()
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
    why: 課程詳情頁嵌入 DripSubscribeForm（訪客）與會員一鍵訂閱區塊（暱稱欄 + 訂閱按鈕）；頁首與右側懸浮面板的「免費領取」CTA 導向此訂閱區
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
- [x] Step 1 驗證 email + nickname（required, max:50, regex `/\p{L}/u` 防純空格/符號），發送驗證碼並 flash 帶暱稱至 Step 2
- [x] Step 2 驗證碼正確 → 新 Email 建立 member 帳號（email_verified_at 即時）、既有帳號一律以輸入值覆蓋 nickname，登入並建立訂閱
- [x] 已停止接收者再次領取 → 「您已停止接收此商品的信件，無法再次領取」；已領取過 → 「此 Email 已經領取過了，內容已寄到這個信箱」，並以 flash `drip_already_claimed`（值為該 Email）讓表單顯示醒目提示框：內容寄到哪個信箱、找不到請查促銷/廣告分頁與垃圾郵件、想再領一份就換別的 Email。**不得**引導去登入或宣稱可在網站上觀看 —— 交付物在信箱裡，登入看不到
- [x] 驗證碼畫面顯示寄件者提示「來信者為『經營者時間銀行』，找不到時請檢查垃圾郵件」；送出鈕文案為「確認驗證碼」（非「確認訂閱」— 這一步只是驗證信箱）
- [x] 訂閱成功通知顯示於頁面頂部主圖下方（flash `drip_subscribed`）
- [x] 銷售頁的訂閱徽章對讀者說「已領取」（active 狀態）且不附「前往教室」連結 — 銷售頁語境是免費贈品的交付，教室動線留給會員中心與留客區塊；後台的訂閱者/名單頁仍用「訂閱中」（營運語彙，見 002 US11 進度日誌）

### User Story 2 - 已登入會員一鍵訂閱 (Priority: P1)

已登入會員在 drip 課程詳情頁看到暱稱欄（預填現有值）+ 訂閱按鈕，確認暱稱後一鍵訂閱，無需驗證碼。

**驗收**：
- [x] POST `/member/drip/subscribe/{course}` 驗證 nickname（規則同 US1）並更新帳號暱稱後建立訂閱
- [x] 已訂閱者在詳情頁顯示「已訂閱」狀態而非按鈕
- [x] 暱稱空白時前端按鈕 disabled

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
- [x] 解鎖判定一律以 emails_sent 為準（sort_order < emails_sent）；只有 completed 與 unlock_all=true 的舊 converted 全解鎖（US14 修訂）
- [x] drip 課程側邊欄過濾：無 video_id 或未解鎖的 Lesson 不出現；admin 預覽豁免（可見全部）
- [x] 直接以 URL 存取未解鎖 Lesson 被擋（改抓第一個未完成的已解鎖影片 Lesson）
- [x] 無任何可顯示 Lesson 時顯示空白歡迎狀態（currentLesson=null，非錯誤頁）
- [x] drip 課程不支援訪客試看（preview）模式

### User Story 8 - 管理員設定連鎖課程與信件內容 (Priority: P2)

管理員在課程表單切換 course_type=drip、設定間隔天數與目標課程（含發信排程預覽）；Lesson 編輯器提供 `{{classroom_url}}` 快速插入與影片警示。

**驗收**：
- [x] CourseForm「連鎖 Email 設定」分頁：drip_interval_days、目標課程多選、依現有 Lesson 排序預覽 Day 0/N/2N 發信日
- [x] 解鎖日全自動：sort_order × drip_interval_days，管理員只調排序與間隔
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
- [x] 過期時間 = subscribed_at + (sort_order × 間隔天數) 天 + video_access_hours 小時；null 不顯示任何相關 UI
- [x] 過期後影片不鎖定，顯示「免費觀看期已結束…」促銷區塊，附目標課程連結（無目標課程則通用文案）
- [x] 已達標訂閱者（converted **與 booked**，US13 修訂）豁免全部觀看期/獎勵 UI（後端直接不下發相關 props）
- [x] 獎勵欄前提：有影片 + 有 video_access_hours + 有 reward_html；達標前顯示「你準時來上課了！真棒」，per-session 計時（離開歸零），達標寫 localStorage 永久保留
- [x] 逾期後曾達標者保留獎勵；未達標者顯示「下次早點來喔，錯過了獎勵 :(」
- [x] 等待時間由 `config/drip.php` reward_delay_minutes 全站統一（env 可調，null 停用）

### User Story 11 - 開信追蹤與訂閱者後台分析 (Priority: P2)

每封信嵌 tracking pixel 記錄開信；後台訂閱者頁顯示狀態統計、整體轉換率、per-Lesson 開信率/點擊率表，以及每位訂閱者的開信進度與點擊狀態。

**驗收**：
- [x] pixel 為 signed URL（180 天效期），驗簽失敗仍回 1x1 GIF 不報錯；事件以 (subscription, lesson, event_type) DB unique 去重
- [x] 訂閱者清單：分頁 20 筆、狀態篩選（active/converted/completed/unsubscribed）、狀態統計卡
- [x] Lesson 統計表：已發送數（emails_sent > sort_order 的訂閱數）、開信數/率、點擊數/率；無 promo_url 或分母 0 顯示「—」
- [x] 整體轉換率 = converted / 總訂閱數（分母 0 顯示「—」）
- [x] 每位訂閱者行顯示「已開 N/M 封」與是否曾點擊促銷按鈕（✓/—）

### User Story 12 - 觀看期改以實際發信時間起算 (Priority: P2)

原本影片免費觀看期到期時間用 `subscribed_at + 理論排程日` 純推算，若排程延遲補寄，信一寄出觀看期就已被吃掉甚至過期。改為以「該 Lesson 對該訂閱者**實際寄出**的時間」為計時起點：發信後才開始跑 video_access_hours。

**驗收**：
- [x] 影片寄出成功時，寫入一筆 `drip_email_events` 的 `sent` 事件（created_at = 實際寄出時刻），為該訂閱該 Lesson 的計時錨點
- [x] 過期時間 = `sent 事件 created_at + video_access_hours 小時`（優先）；查無 sent 事件時 fallback 舊公式（`subscribed_at + sort_order × 間隔天數 天 + video_access_hours 小時`）
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
- [x] `isLessonUnlocked()` 判定順序：`unlock_all=true` 或 `completed` → 全開；其餘（含 active/booked/converted/unsubscribed）一律 `sort_order < emails_sent`
- [x] migration 新增 `unlock_all` boolean（default false），並將現有 `status='converted'` 的列回填為 true；新產生的轉換一律 false
- [x] `daysUntilUnlock()` 對停信狀態（booked/converted/unsubscribed）一律回 -1（不會再解鎖）
- [x] 教室側邊欄過濾、URL 直闖擋下皆沿用 isLessonUnlocked，無需個別改動；達標者進教室只看得到已寄達的 Lesson
- [x] `ClassroomController::formatLessonFull()` 的 `$isConverted` 擴為「已達標」判定（booked/converted 皆豁免觀看期倒數與 reward props）
- [x] `Classroom.vue` 的 VideoAccessNotice `v-if` 條件同步改為排除全部已達標狀態

## Requirements

- **FR-001**: 解鎖日公式 `sort_order × drip_interval_days`（sort_order 從 0 起）；但個別 Lesson 的解鎖判定以 **emails_sent** 為準（信寄到哪、解鎖到哪），時間公式只用於排程計算應寄數與觀看期起算
- **FR-002**: drip_interval_days ≤ 0 時視為全部解鎖（防呆）
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

- **FR-017**: 前台對免費商品 MUST 用「領取／商品」語彙，不得出現「訂閱」；「退訂」對外一律說「停止接收信件」（徽章「已停止接收」）。電子報是全站例外（維持訂閱語彙）；後台（訂閱者頁、名單、廣播）維持「訂閱」等營運語彙，因為它對應資料表 `drip_subscriptions` 與 `status` 欄位值，文字跟著欄位走才查得動問題。資料庫欄位、路由 `/drip/unsubscribe/{token}`、狀態值 `unsubscribed` 皆不改。

## 設計決策

- **D1**: 訂閱者統一為 users 會員 — 不另建 Email 名單表；訪客訂閱即建帳號並登入，後台會員/批次發信/贈課功能無縫共用
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

## Schema

- `drip_subscriptions` — 訂閱記錄；(user_id, course_id) unique；emails_sent 恆等於已 dispatch 的信數（也是解鎖游標）；unsubscribe_token 為 UUID、建立時自動產生
- 本模組新增 migration `2026_07_31_000002_add_booked_to_drip_subscriptions_status.php` — status enum 由 4 值擴為 5 值（加 `booked`）。**須以 `Schema::change()` 且重新指定 `->default('active')`**，否則 MySQL MODIFY 會掉預設值；sqlite 測試 DB 也才會更新 CHECK constraint（比照 `2026_07_20_000001` 的寫法）
- 本模組新增 migration `2026_07_31_000003_add_unlock_all_to_drip_subscriptions_table.php` — 加 `unlock_all` boolean default false（放在 status 後），up() 內回填 `UPDATE drip_subscriptions SET unlock_all = 1 WHERE status = 'converted'`；語意為「此訂閱不受解鎖游標限制」，只服務改版前既有轉換者的向後相容
- `drip_conversion_targets` — drip 課程 ↔ 目標課程多對多；購買任一 target 即轉換
- `drip_email_events` — 開信（opened）/教室促銷點擊（clicked）/**實際發信（sent）** 事件；(subscription_id, lesson_id, event_type) unique 保證去重；只有 created_at。`sent` 事件的 created_at 即該封信對該訂閱者的實際寄出時刻，作為影片觀看期計時錨點；target_url/ip/user_agent 為 null
- 本模組新增 migration `2026_07_20_000001_add_sent_to_drip_email_events_event_type.php` — 將 event_type enum 由 `['opened','clicked']` 擴為 `['opened','clicked','sent']`（僅擴值，不動既有資料；down 還原為兩值前需先確保無 sent 列）
- `courses` 增欄（本模組 migration）— `course_type`（standard/drip，預設 standard）、`drip_interval_days`（nullable）
- `lessons` 增欄（本模組 migration，promo 欄位適用所有課程類型）— `promo_delay_seconds`（null=停用/0=立即）、`promo_html`、`promo_url`（varchar 500，教室追蹤按鈕）、`reward_html`（drip 限定）、`video_access_hours`（null=無限期）

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

## 進度日誌

- 2026-08-03: 序列信的 `md_content` 渲染改用 `EmailMarkdownService::toHtml()`（011 FR-021 touchpoint）— 原本裸 `new CommonMarkConverter()` 會吃掉單次換行，小節內容手動斷行在信裡會黏成一段。現在單次換行即 `<br>`，空一行仍是新段落；`stripStylesForEmail()` 與寄送流程不動。查驗現有 16 篇有 md_content 的小節，3 篇（id 20、46 各多 7 / 2 個換行；id 14 無變化）會多出換行，皆為作者原本就手動斷行的位置。
- 2026-08-02: 序列信被 Gmail 丟進垃圾郵件 — 查證 DKIM/SPF/DMARC 皆正常，缺的是 `List-Unsubscribe` 標頭（電子報有、連鎖信從來沒有），加上當天把內文唯一的「退訂」字樣改掉，等於兩個訊號都沒了。補標頭 + `List-Unsubscribe-Post: One-Click` + CSRF 豁免（順帶修好電子報宣告卻會 419 的一鍵退訂），內文停止接收行改為空兩行＋分隔線＋12px 淺灰並附英文 Unsubscribe。新增 DripMailDeliverabilityTest（3 tests）。
- 2026-08-02: 修正已領取者的提示 — 原本引導「登入後即可繼續觀看」是錯的（交付走 Email，站上看不到），改為醒目提示框：指名信箱、垃圾郵件提醒、換別的 Email 再領；flash `drip_already_claimed` 由布林改為帶該 Email。215→218 全綠。
- 2026-08-02: 前台免費商品語彙統一 — 訂閱→領取、課程→商品、退訂→停止接收信件（涵蓋領取表單、銷售頁成功卡與徽章、教室空狀態、停止接收頁、序列信內文、DripService／ClassroomController 訊息）；「此 Email 已訂閱此課程」改為指向信箱的提示並新增 flash `drip_already_claimed`（HandleInertiaRequests 白名單同步）。新增 ClaimWordingTest（3 tests），全套 215 passed、npm build 綠。
- 2026-08-02: 訪客訂閱表單 Step 2 按鈕文案改「確認驗證碼」；銷售頁訂閱徽章 active 改「已領取」並移除「前往教室」連結（檔案為 002 owner，本模組僅記語彙決定）。
- 2026-08-01: LessonForm 的 Markdown 內容欄在 drip 課程時新增固定格式說明（信件開頭自動加「Hi {暱稱}，」、主旨為「{暱稱}，{小節標題}」、結尾自動附退訂連結，正文不必再寫稱呼），對應 US8 新增一條驗收；純 UI 文案，無行為變更。npm build 綠。
- 2026-07-31: US13+US14 完成（/sync 對帳：touchpoint 說明與 code_files 已補齊，無額外行為差異） — 達標即出漏斗。高價課預約（HighTicketBookingService）新增 booked 狀態並停止序列信；後台 Lead 開通與贈課補上 checkAndConvert（booked→converted 可升級）；停信/豁免狀態集合收斂為 DripSubscription::STOPS_SENDING / FUNNEL_DONE；converted 不再全開小節（解鎖凍結在 emails_sent），改版前既有 converted 由 unlock_all 旗標回填保留全開；後台訂閱者頁加「已預約」統計卡/篩選/徽章與預約率。順手補 ClassroomController 缺失的 DripSubscription import（型別提示原本解析到不存在的類別）。新增 FunnelStopTest（8 tests），全套 192 passed、npm build 綠。
- 2026-07-31: drip 銷售頁右側懸浮面板同步「免費領取」CTA（touchpoint: Course/Show.vue，owner 002；原本面板對 drip 整個關閉、頁首 CTA 點擊無反應），面板僅在 `canSubscribe` 且無現有訂閱時出現、點擊捲至訂閱區。
- 2026-07-21: US12 完成 — 影片觀看期改以實際發信時間起算。SendDripEmailJob 於寄信成功後 firstOrCreate 一筆 sent 事件當錨點（冪等）；DripService 加 getSentAtMap + 三方法吃 `?Carbon $sentAt`，缺席 fallback 舊公式；ClassroomController 傳入錨點；後台訂閱者頁加「最近發信」欄。新增 VideoAccessAnchorTest（4 tests）。全測試 167 passed、npm build 綠。
- 2026-07-11: 銷售頁 drip「免費領取」CTA 標題改「立刻免費領取【課程名】！」，並統一登入／未登入視覺——訪客表單（DripSubscribeForm）由 indigo 改品牌配色（brand-gold 按鈕、brand-teal 聚焦），登入狀態區塊（touchpoint: Course/Show.vue，owner 002）改為同款白底卡片＋同標題＋滿版金按鈕。
- 2026-07-06: 領域重組 — 自 005-drip-email 重寫，依實際 codebase 校正；整併 partial/planned 故事

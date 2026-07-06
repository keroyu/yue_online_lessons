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
touchpoints:
  - file: resources/js/Components/Admin/CourseForm.vue
    owner: 004-course-admin
    why: 「連鎖 Email 設定」分頁 — course_type、drip_interval_days、目標課程選擇、發信排程預覽
  - file: resources/js/Components/Admin/LessonForm.vue
    owner: 004-course-admin
    why: Lesson 的 promo_delay_seconds / promo_html / promo_url / reward_html / video_access_hours 欄位、CTA 快速插入、{{classroom_url}} 插入按鈕與影片警示
  - file: app/Http/Controllers/Admin/CourseController.php
    owner: 004-course-admin
    why: subscribers() action — 組裝訂閱者清單、狀態統計、Lesson 開信/點擊分析（呼叫 DripService）
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
  - file: app/Http/Controllers/Admin/MemberController.php
    owner: 008-members-admin
    why: 會員後台與 drip 訂閱者共用同一份 users 名單（User::dripSubscriptions 關聯）
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 課程詳情頁嵌入 DripSubscribeForm（訪客）與會員一鍵訂閱區塊（暱稱欄 + 訂閱按鈕）
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
- [x] 已退訂者再訂閱同課程 → 「此課程已無法再次訂閱」；已訂閱 → 「此 Email 已訂閱此課程」
- [x] 驗證碼畫面顯示寄件者提示「來信者為『經營者時間銀行』，找不到時請檢查垃圾郵件」
- [x] 訂閱成功通知顯示於頁面頂部主圖下方（flash `drip_subscribed`）

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
- [x] SendDripEmailJob 發信前跳過 unsubscribed/converted；completed 仍寄出最後一封（狀態與 dispatch 同時發生）
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
- [x] checkAndConvert() 由 Portaly webhook、結帳、免費報名、積分兌換四個購買管道觸發
- [x] 僅 active 訂閱會被轉換；轉換寫入 status_changed_at
- [x] converted 訂閱者在教室可看全部 Lesson（isLessonUnlocked 直接放行）
- [x] 已排入佇列的信件在 handle() 時檢查狀態，converted 後不寄出

### User Story 6 - 使用者退訂連鎖課程 (Priority: P3)

信件末尾退訂連結（UUID token）→ 確認頁警告「限期商品，退訂後無法再次訂閱」→ 確認後停止發信，已解鎖內容保留觀看權。

**驗收**：
- [x] `/drip/unsubscribe/{token}` 顯示確認頁（Drip/Unsubscribe.vue），token 建立訂閱時自動產生（model booted）
- [x] 確認後 status=unsubscribed；重複退訂顯示「您已退訂此課程」
- [x] 退訂者解鎖狀態凍結在 emails_sent（已收信的 Lesson 仍可看，不再解鎖新內容）
- [x] 退訂者無法再次訂閱同課程（US1 驗收）

### User Story 7 - 教室觀看與側邊欄過濾 (Priority: P1)

訂閱者進入教室只看到「有影片且已解鎖」的 Lesson；純文字 Lesson 只活在 Email、未解鎖 Lesson 完全不露出（無倒數無鎖頭），維持漏斗黑盒子效果。

**驗收**：
- [x] 解鎖判定以 emails_sent 為準（sort_order < emails_sent）；converted/completed 全解鎖
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
- [x] converted 訂閱者豁免全部觀看期/獎勵 UI（後端直接不下發相關 props）
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

## Requirements

- **FR-001**: 解鎖日公式 `sort_order × drip_interval_days`（sort_order 從 0 起）；但個別 Lesson 的解鎖判定以 **emails_sent** 為準（信寄到哪、解鎖到哪），時間公式只用於排程計算應寄數與觀看期起算
- **FR-002**: drip_interval_days ≤ 0 時視為全部解鎖（防呆）
- **FR-003**: 訂閱唯一性：(user_id, course_id) DB unique；unsubscribed 是終態 — 永不能再訂閱同課程
- **FR-004**: 狀態機：active → converted（購買目標課程）/ completed（寄完全部）/ unsubscribed（退訂）；completed 可因新增 Lesson 回到 active
- **FR-005**: 第一封信 dispatchAfterResponse（回應後即發），emails_sent 同步 +1；發送計數在 dispatch 時記錄，實際寄出與否由 Job 內狀態檢查決定
- **FR-006**: 信件為極簡模板：問候語（可省）＋內文＋退訂連結＋pixel；內文 Markdown 以 CommonMark 轉 HTML 後 strip style/class/`<style>`；無內文時顯示「新的課程內容已經解鎖了，請至網站觀看」
- **FR-007**: 開信/點擊事件 immutable（無 updated_at），(subscription_id, lesson_id, event_type) unique，firstOrCreate 寫入失敗僅 log 不中斷
- **FR-008**: `/drip/track/open` 免登入 + signed URL；`/drip/track/click` 需 auth，以 session user 反查訂閱（lesson 必須屬於已訂閱課程）
- **FR-009**: 促銷/獎勵達標狀態存 localStorage（不進 DB），跨裝置不共用為已接受的取捨
- **FR-010**: 課程下架（unpublished）後排程不再對其訂閱者發信（processDailyEmails 僅取 published drip 課程）

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

## Schema

- `drip_subscriptions` — 訂閱記錄；(user_id, course_id) unique；emails_sent 恆等於已 dispatch 的信數（也是解鎖游標）；unsubscribe_token 為 UUID、建立時自動產生
- `drip_conversion_targets` — drip 課程 ↔ 目標課程多對多；購買任一 target 即轉換
- `drip_email_events` — 開信（opened）/教室促銷點擊（clicked）事件；(subscription_id, lesson_id, event_type) unique 保證去重；只有 created_at
- `courses` 增欄（本模組 migration）— `course_type`（standard/drip，預設 standard）、`drip_interval_days`（nullable）
- `lessons` 增欄（本模組 migration，promo 欄位適用所有課程類型）— `promo_delay_seconds`（null=停用/0=立即）、`promo_html`、`promo_url`（varchar 500，教室追蹤按鈕）、`reward_html`（drip 限定）、`video_access_hours`（null=無限期）

## 進度日誌

- 2026-07-06: 領域重組 — 自 005-drip-email 重寫，依實際 codebase 校正；整併 partial/planned 故事

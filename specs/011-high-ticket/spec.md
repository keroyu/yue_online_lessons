---
id: 011-high-ticket
status: building
owner_files:
  - app/Http/Controllers/HighTicketBookingController.php
  - app/Http/Controllers/Admin/HighTicketLeadController.php
  - app/Http/Controllers/Admin/EmailTemplateController.php
  - app/Http/Requests/Admin/EmailTemplateRequest.php
  - app/Models/HighTicketLead.php
  - app/Models/EmailTemplate.php
  - app/Services/HighTicketBookingService.php
  - app/Services/HighTicketLeadService.php
  - app/Services/EmailMarkdownService.php
  - app/Jobs/NotifyHighTicketSlotJob.php
  - app/Jobs/SubscribeDripLeadJob.php
  - app/Mail/TemplatedMail.php
  - resources/views/emails/high-ticket-booking.blade.php
  - resources/js/Pages/Admin/HighTicketLeads/Index.vue
  - resources/js/Components/Admin/Leads/BookingListTab.vue
  - resources/js/Pages/Admin/EmailTemplates/Index.vue
  - resources/js/Pages/Admin/EmailTemplates/Edit.vue
  - resources/views/emails/template-text.blade.php
  - database/migrations/2026_04_09_000002_create_email_templates_table.php
  - database/migrations/2026_04_10_000001_create_high_ticket_leads_table.php
  - database/migrations/2026_08_03_000001_add_body_type_to_email_templates_table.php
  - database/migrations/2026_08_04_000002_add_no_response_to_high_ticket_leads_status.php
  - database/seeders/EmailTemplateSeeder.php
  - tests/Feature/HighTicket/LeadConvertTest.php
  - tests/Feature/HighTicket/BookingMailFailureTest.php
  - tests/Feature/HighTicket/BookingLeadRecordTest.php
  - tests/Feature/HighTicket/EmailTemplateHtmlModeTest.php
  - tests/Feature/HighTicket/LeadsTabsTest.php
touchpoints:
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 隱藏價格模式的銷售頁展示（價格區塊替換為預約須知、按鈕改「立即預約」）與右欄預約表單（axios POST + inline 成功提示）實作於此；`isFunnelLanding` 的 landing page 隱藏規則（hero 時長行、第 3 區整塊、免費試閱）與預約成功文案依 mail_sent 分岔亦在此
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: show() 傳遞 is_high_ticket / high_ticket_hide_price props 給銷售頁
  - file: database/migrations/2026_04_09_000001_add_high_ticket_fields_to_courses_table.php
    owner: 004-course-admin
    why: courses.type enum 擴充 high_ticket + high_ticket_hide_price 欄位；課程表單的類別/開關 UI 歸課程管理模組
  - file: app/Services/DripService.php
    owner: 010-drip-email
    why: SubscribeDripLeadJob 呼叫 DripService::subscribe() 建立訂閱並立即發送第一封序列信；US8 起新增 `subscriberPageData()`，把原 `CourseController@subscribers` 的資料組裝下沉至此，供本模組的名單管理頁「訂閱者名單」tab 呼叫
  - file: resources/js/Components/Admin/Leads/SubscriberListTab.vue
    owner: 010-drip-email
    why: 訂閱者名單 tab 的 UI（US8 起取代 `Pages/Admin/Courses/Subscribers.vue`）；內容全屬 drip 領域（狀態語彙、Lesson 開信統計、觀看進度），本模組只是承載頁
  - file: app/Http/Controllers/Admin/CourseController.php
    owner: 004-course-admin
    why: US8 移除 `subscribers()` action —— 訂閱者頁併入本模組的名單管理頁，資料組裝改由 `DripService::subscriberPageData()` 提供
  - file: resources/js/Pages/Admin/Courses/Edit.vue
    owner: 004-course-admin
    why: US8 移除課程編輯頁工具列的「訂閱者」按鈕（舊入口取消，唯一入口改為側欄 Leads 名單）
  - file: app/Mail/BatchEmailMail.php
    owner: 008-members-admin
    why: Leads「發送郵件」批次功能沿用會員後台的 BatchEmailMail（Markdown 渲染）；本模組把裸 `new CommonMarkConverter()` 換成 `EmailMarkdownService::toHtml()`，讓單次換行行為與模板信一致（FR-021），寄出內容其餘不變
  - file: app/Jobs/SendDripEmailJob.php
    owner: 010-drip-email
    why: 序列信的 `md_content` 渲染同樣改用 `EmailMarkdownService::toHtml()`（FR-021）；僅換 converter 設定，後續的 `stripStylesForEmail()` 與寄送流程不動
  - file: app/Mail/CourseGiftedMail.php
    owner: 008-members-admin
    why: 讀取本模組 email_templates（event_type=course_gifted）；模板存在時改用 emails/high-ticket-booking.blade.php 版型寄送
  - file: app/Mail/LessonAddedNotification.php
    owner: 004-course-admin
    why: 讀取本模組 email_templates（event_type=lesson_added）
  - file: routes/web.php
    owner: 000-platform-core
    why: 預約 API（`POST /course/{course}/book`，throttle:5,1）、Leads 後台與 Email 模板路由（含 `PUT /admin/email-templates/notify-cc`，須宣告在 `{template}` 之前）；US8 移除 admin 群組內的 `GET /admin/courses/{course}/subscribers`
  - file: app/Models/SiteSetting.php
    owner: 000-platform-core
    why: 唯讀取用 —— 預約通知 CC 清單存於 `site_settings.high_ticket_lead_notify_cc`（FR-014），沿用 000 的全站設定機制，未新增欄位
---

# High Ticket（高價課預約：隱藏價格銷售頁 + Leads 後台 + Email 模板系統）

## 目標

讓高價客製服務課程以「隱藏價格 + 預約 1v1 面談」模式銷售：訪客在銷售頁提交預約表單後
收到 DB 模板驅動的確認信並留下 Lead 記錄；管理員在後台追蹤 Leads 銷售漏斗
（通知新時段、批次郵件、轉序列信、開通商品），並可自行編輯所有系統 Email 模板文案。

## User Stories

### User Story 1 - 隱藏價格銷售頁與預約表單 (Priority: P1)

訪客瀏覽 `type=high_ticket` 且開啟隱藏價格的課程銷售頁時，看不到任何價格資訊，
改以「立即預約」引導填寫姓名 + Email 預約 1v1 面談。

**驗收**：
- [x] 隱藏價格模式下，銷售頁底部「優惠價 + 倒數計時」區塊替換為「預約須知」說明文字，頂部快速購買區的 PriceDisplay 同步隱藏，100% 不洩漏價格
- [x] 行動按鈕文字為「立即預約」；底部右欄的同意條款 + 購買按鈕替換為預約表單（姓名、Email 必填；已登入者自動帶入 real_name / email）
- [x] 提交為非同步 `POST /course/{course}/book`（axios），成功後 inline 顯示「預約申請已送出！」，不整頁跳轉、停留在銷售頁
- [x] 422 驗證錯誤 inline 顯示於對應欄位下方；其他錯誤顯示「預約失敗，請稍後再試。」
- [x] 隱藏價格關閉（客製服務顯示價格）或非 high_ticket 課程時，銷售頁與購買流程和一般課程完全相同
- [x] 隱藏價格的高價課套用銷售頁的**漏斗落地頁版型**（規則定義見 002 FR-021 / D27–D29，drip 課同版型）：隱藏堂數時長、免費試閱，以及影片下方第 3 區整塊（含頂部「立即預約／前往學習」按鈕）；成交動線為下方預約表單與懸浮面板 CTA

### User Story 2 - 預約確認信與 Lead 記錄 (Priority: P1)

訪客成功提交預約後，系統寄出 DB 模板驅動的確認信，並建立一筆 Lead 記錄供後台追蹤；
不建立任何訂單或購買記錄。

**驗收**：
- [x] 確認信使用 `high_ticket_booking_confirmation` 模板：subject / body 以 str_replace 替換 `{{user_name}}` / `{{user_email}}` / `{{course_name}}`，body Markdown 經 CommonMark 轉 HTML，以 `emails/high-ticket-booking.blade.php` 版型寄出，並 CC 後台設定的收件者清單（未設定時 fallback 至 `DEFAULT_NOTIFY_CC`，即管理員 themustbig+leads@gmail.com）
- [x] 模板不存在時回傳 422「預約確認信模板不存在，請聯絡管理員」，不寄信也不建立 Lead
- [x] Lead 記錄 MUST 在寄信之前寫入 DB；Email 寄送失敗僅記 error log，不影響已落地的 Lead
- [x] 寄送失敗時回應帶 `mail_sent: false`，前台成功區塊改顯示「預約已收到！但確認信寄送失敗，我們會盡快主動與你聯絡。」— 不得叫使用者去收一封沒寄出的信
- [x] 首次提交建立 `high_ticket_leads` 記錄（status=pending、booked_at=now、notified_count=0）
- [x] 同一 email 重複預約**同一課程**不產生第二筆 lead：更新既有記錄的 name 與 booked_at，status 為 `closed` / `no_response` 者回復 `pending`（重新有意願），`contacted` / `converted` 維持管理員設定的狀態；不同課程仍為各自獨立的 lead
- [x] 非 high_ticket 或未開啟隱藏價格的課程呼叫預約 API 時回 422「此課程不接受預約」

### User Story 3 - 後台 Leads 名單管理 (Priority: P2)

管理員在 `/admin/high-ticket-leads` 檢視所有預約者，依狀態 / 課程 / 關鍵字篩選，
追蹤每位潛在客戶的銷售漏斗階段與序列信紀錄。

**驗收**：
- [x] 列表顯示姓名、Email、課程、狀態、通知次數、序列信紀錄、預約時間；依 booked_at 降冪、每頁 20 筆分頁
- [x] 狀態篩選（待聯繫 / 已聯繫 / 未回應 / 已成交 / 已關閉）、課程下拉篩選（僅列 `type=high_ticket` 課程）、姓名或 Email 關鍵字搜尋（LIKE 模糊比對、300ms debounce）三者可組合，分頁保留查詢參數
- [x] 可直接更新單筆 lead 狀態（`PATCH /admin/high-ticket-leads/{lead}/status`），列表即時反映；狀態欄為五顆色塊按鈕（P 待聯繫黃 / C 已聯繫藍 / N 未回應橘 / D 已成交綠 / X 已關閉灰），一鍵切換免展開下拉，目前狀態以實心底色 + ring 標示、其餘為淺色底，欄頭附 `P待 / C聯 / N未回 / D成 / X關` 圖例，點擊當前狀態不發請求
- [x] `no_response`（未回應）代表「已聯繫但對方沒下文」，排在 `contacted` 之後：可被批次「加入序列信」（同 pending / closed），且對方重新預約同課程時狀態自動回 `pending`（重新預約本身就是回應）
- [x] 「序列信紀錄」欄以 email 關聯 `users` → `drip_subscriptions` 顯示曾加入的 drip 課程與訂閱狀態；無紀錄顯示 `—`（不需額外欄位）
- [x] 狀態篩選按鈕 active / 非 active 均為 cursor-pointer，active 提供 hover 深化效果；四個狀態 tab 與列內色塊按鈕共用同一組配色（黃/藍/綠/灰），active 為實心、非 active 為同色系淺底，「全部」維持 brand-teal 中性色 — 全頁同一顏色恆等於同一狀態
- [x] 批次動作列有「複製 Email」按鈕：把已勾選 leads 的 email 以 `, ` 串接寫入剪貼簿（去重，同人重複預約只出現一次），可直接貼進郵件收件人欄；未勾選時停用，複製成功後 2 秒顯示綠勾與「已複製 N 個 Email」，複製不會清空勾選

### User Story 4 - 通知新時段與批次郵件 (Priority: P2)

新面談時段釋出時，管理員批次通知 pending leads；也可對任意勾選的 leads
發送一次性客製郵件。

**驗收**：
- [x] 勾選 pending leads 點「通知新時段」先開確認 modal：顯示 `high_ticket_slot_available` 模板主旨、body Markdown 渲染預覽、收件人列表、前往編輯模板的連結
- [x] 模板不存在時 modal 顯示警告並停用「確認發送」；後端亦回 422 引導先建立模板
- [x] 確認後 per-lead 派送 `NotifyHighTicketSlotJob`（後端只接受 status=pending 的 leads，前端勾選限制外再過濾一層），立即回應 dispatched 數
- [x] Job 成功寄出後該 lead `notified_count` +1、`last_notified_at` 更新為當下；寄送失敗 throw 觸發重試（3 次，backoff 60/300/900 秒）
- [x] 「發送郵件」可勾選任意狀態 leads：modal 填主旨（上限 200 字）與內容（上限 10000 字，含字元計數），以 `BatchEmailMail` 逐一同步寄出（以 lead.email 為收件地址，不依賴 User 帳號）；單筆失敗僅記 log 不中斷，回應「已發送 N 封郵件」

### User Story 5 - Lead 轉序列信與開通商品 (Priority: P2)

冷掉或未成交的 leads 交給 drip 自動化培養；面談成交者由管理員直接開通商品。

**驗收**：
- [x] 勾選 `pending`（冷掉）、`no_response`（未回應）或 `closed` 的 leads 點「加入序列信」，下拉選單列出所有 `course_type=drip` 課程供選擇
- [x] lead 的 email 已有「任一」active drip_subscription 時跳過（不限同課程），回應摘要 `{dispatched, skipped}`
- [x] 每筆派送 `SubscribeDripLeadJob`：以 email `firstOrCreate` user（nickname=lead.name、無密碼，沿用驗證碼登入）→ `DripService::subscribe()` 建立訂閱並立即發第一封序列信 → 成功後 lead status 自動改 `closed`
- [x] 非「已成交」的 lead 課程欄有「開通」按鈕：確認 modal 顯示 lead 姓名 / Email、三條操作說明、商品下拉（所有課程）
- [x] 開通 modal 有「成交價格」欄位（整數、必填、≥ 0）：選擇商品時自動帶入該課程當前顯示價（`display_price`，含促銷邏輯），管理員可改為實際成交金額（私下匯款成交價可能與網站定價不同）
- [x] 確認開通後：`firstOrCreate` user（password 隨機 16 碼）→ `Purchase::updateOrCreate`（type=`lead_conversion`、status=paid、amount=成交價格）→ lead status 改 `converted`；列表 inline 更新該列並於頁頂顯示結果摘要；金額自動計入後台交易列表與營收圖表（`sum(Purchase.amount)`）
- [x] 上述三步寫入包在單一 `DB::transaction()`：任一步失敗全部回滾，不留下「有帳號但沒課程」的孤兒 user；drip 停信與通知信留在 transaction 外（最佳努力，不隨回滾、也不擋成交）
- [x] 開通成功後寄出 `lead_converted` 模板通知信給 lead.email：告知課程已開通、成交金額，以及**用這個 email 到網站收驗證碼登入**（新建帳號無密碼，這是對方唯一的入口資訊）
- [x] 缺模板或寄送失敗時開通照常完成，回應帶 `mail_sent: false`，前端結果摘要改顯示「已開通，但通知信寄送失敗，請自行聯絡對方」— 不得宣稱已寄出（比照 FR-013）
- [x] 既有 `Purchase(user_id, course_id)` 的 `type` 非 `lead_conversion`（線上刷卡 / 贈課 / 系統指派）且 `status` 非 `refunded` 時，開通預設回 409 拒絕並附既有記錄的類型與金額；帶 `force=true` 才放行覆寫
- [x] 開通 modal 依所選商品即時比對該 lead 是否已持有：命中時顯示警告框（原始類型 + 金額），非 `lead_conversion` 者須勾選「我了解這會覆寫原有購買記錄」才啟用「確認開通」

### User Story 6 - Email 模板系統後台管理 (Priority: P2)

管理員在 `/admin/email-templates` 統一編輯所有可模板化的系統信件，
不依賴工程師即可修改主旨與內容。

**驗收**：
- [ ] 列表顯示 5 個模板，event_type 以中文標籤呈現：客製服務預約確認、課程贈禮通知、課程新增小節通知、客製服務新時段通知、顧問成交開通通知
- [x] 編輯頁顯示模板名稱、觸發事件（唯讀）、主旨、body_md 內容編輯區；「插入變數」按鈕列依 event_type 顯示可用變數，點擊插入 textarea 游標位置
- [x] 編輯 / 預覽模式切換：預覽以 `marked` + `breaks: true` 渲染，單行換行與寄出效果一致（**此條在 FR-021 之前為假** — 預覽會換行、寄出不會，管理員得按兩次 Enter 才有效果）
- [x] 儲存驗證 name ≤ 100、subject ≤ 255、body_md 必填（中文錯誤訊息），成功後導回列表並 flash「模板已更新」
- [ ] `EmailTemplateSeeder` 以 event_type 為 key `updateOrCreate` seed 5 個預設模板，可重複執行不覆蓋主鍵
- [x] `course_gifted` / `lesson_added` 事件由對應 Mailable 建構時讀取模板，模板不存在時 fallback 至寫死內容；high_ticket 預約相關兩事件無 fallback（缺模板直接擋下操作）；`lead_converted` 缺模板不擋操作但也不 fallback — 不寄信並回報 `mail_sent: false`（見 D15）
- [x] 列表頁上方有「預約通知收件者（CC）」設定卡：單一文字框（逗號分隔多筆）+ 儲存按鈕，`PUT /admin/email-templates/notify-cc` 寫入 `site_settings`；每筆須為合法 Email 格式否則 inline 顯示「「xxx」不是有效的 Email 格式」；留空即 fallback 至預設值（placeholder 與說明文字均顯示該預設）

### User Story 7 - HTML 模板模式與純文字備援 (Priority: P2)

管理員手上已有設計好的 HTML 版信件（彩色 Step 標籤、多段強調），
Markdown 寫不出來也貼不進去（CommonMark 會吃掉縮排與空行）。
要能在編輯頁把模板切成 HTML 模式、直接貼原始碼、看到與收件匣一致的預覽並原樣寄出。

**驗收**：
- [x] 編輯頁內容區有 Markdown / HTML 兩顆分段切換鈕，切換只改變「怎麼解讀 body_md」，MUST NOT 自動轉換既有內容；切換狀態隨表單一起儲存
- [x] HTML 模式：貼進去的原始 HTML 原樣寄出（inline style / 屬性 / 巢狀標籤完整保留，不經 CommonMark、不 sanitize）
- [x] `{{var}}` 替換在兩種模式行為一致（含放在 `href` 等屬性值內）
- [x] 預覽改用 sandbox `<iframe srcdoc>`：兩種模式共用同一個預覽框，看到的樣式等同真實信件（不再套後台的 `prose` class），且貼進來的 `<script>` 不會在後台頁面執行
- [x] 「插入變數」按鈕在 HTML 模式照常運作（插入 textarea 游標位置）
- [x] 四種模板皆可各自切換模式；未設定者維持 `markdown`，既有模板行為零變化
- [x] 儲存驗證 `body_type` 僅接受 `markdown` / `html`，非法值 inline 顯示中文錯誤
- [x] 四封模板信 MUST 以 `multipart/alternative` 寄出（HTML + 純文字兩段），純文字版由模板自動產生，管理員不需額外維護
- [x] Markdown 模式按一次 Enter 即為真正的換行（寄出結果與編輯時所見一致），不需按兩次；空一行仍是新段落

### User Story 8 - 名單管理頁合併預約名單與訂閱者名單 (Priority: P2)

「預約來的人」和「在序列信裡被培養的人」是同一條銷售漏斗的前後段，
現在卻分在兩個地方：前者在側欄 `/admin/high-ticket-leads`，後者藏在每門課程編輯頁的「訂閱者」按鈕裡。
管理員要對照一個人的狀態得先記住他訂了哪門課、再繞進課程後台。
把兩份名單併成同一頁的兩個 tab，訂閱者名單改由頁內下拉切換課程。

**驗收**：
- [x] `/admin/high-ticket-leads` 頂部有兩顆 tab：**預約名單**（預設）、**訂閱者名單**，樣式比照「積分與推薦」頁（`border-b-2` 底線式、active 為 brand-teal、非 active hover 變深、皆 `cursor-pointer`）
- [x] tab 狀態由網址 `?tab=` 決定（`booking` / `subscribers`，非法值視為 booking）；切 tab 是一次 Inertia 請求，只載入該 tab 的資料，重整或分享網址回到同一個 tab
- [x] 預約名單 tab 的所有既有功能（狀態色塊按鈕、課程/關鍵字/狀態篩選、批次通知、發送郵件、加入序列信、開通、複製 Email、分頁）行為與外觀零變化，既有網址參數（`status` / `search` / `course_id`）維持可用
- [x] 訂閱者名單 tab 頂部有課程下拉，**只列出 `course_type=drip` 的課程**（一般課程、高價課、電子書皆不出現）；未指定時預設選第一門 drip 課程，切換課程即重載該課資料
- [x] 訂閱者名單 tab 的內容等同原訂閱者頁：狀態統計卡（全部/發信中/已預約/已轉換/已完成/已退訂）、Lesson 發信統計表（含預約率、轉換率、最近發信時間）、訂閱者列表（Email、暱稱、狀態、進度、開信數、點擊、訂閱時間、狀態變更）、狀態篩選與分頁 —— 原有的麵包屑與「課程 X - 共 N 個小節」標題改由課程下拉承擔
- [x] 系統中沒有任何 drip 課程時，訂閱者名單 tab 顯示空狀態說明，不報錯
- [x] 銷售顧問（`is_sales_consultant`）兩個 tab 都看得到、都能操作（見 D27）
- [x] 課程編輯頁工具列的「訂閱者」按鈕移除，`GET /admin/courses/{course}/subscribers` 路由與 `CourseController@subscribers` 一併刪除；名單的唯一入口是側欄「Leads 名單」（選單名稱不變）

## Requirements

- **FR-001**: 預約 API 只接受 `is_high_ticket && high_ticket_hide_price` 的課程，否則 422；路由掛 `throttle:5,1` 防濫用
- **FR-002**: 預約流程 MUST NOT 建立訂單 / 購買記錄；唯一產出為確認信 + 一筆 Lead
- **FR-003**: 確認信模板缺失時整個預約失敗（422），Lead 不建立；Email「寄送失敗」（模板存在但寄不出去）則 Lead 照常保留 — 兩種失敗語意不同。`book()` MUST 依「模板檢查 → 寫 Lead → 寄信 → drip 停信 → CAPI」的順序執行：Lead 先落地，其後每一步失敗都以 `mail_sent: false` 或 log 回報，不得讓名單掉單
- **FR-004**: 各 event_type 可用變數（以實際 code 為準，與舊 spec 不同 — course_gifted / lesson_added 無 `{{user_name}}`）：

  | event_type | 可用變數 |
  |------------|---------|
  | `high_ticket_booking_confirmation` | `{{user_name}}`、`{{user_email}}`、`{{course_name}}` |
  | `course_gifted` | `{{course_name}}`、`{{course_description}}`、`{{app_url}}` |
  | `lesson_added` | `{{course_name}}`、`{{lesson_title}}`、`{{classroom_url}}` |
  | `high_ticket_slot_available` | Job 實際替換 `{{user_name}}`、`{{course_name}}`；編輯頁變數清單未登錄此 event_type，不顯示插入按鈕 |

- **FR-005**: 模板變數以 `str_replace` 全量替換（無 escape / 白名單機制）；event_type 建立後不可修改（update 僅驗證 name / subject / body_md / body_type）
- **FR-006**: 「通知新時段」後端 MUST 以 `status=pending` 過濾傳入的 lead_ids；notified_count / last_notified_at 由 Job 於寄送成功後更新，非派送當下
- **FR-007**: 「加入序列信」後端 MUST 以 `status IN (pending, closed)` 過濾；去重條件為該 email 對「任何課程」存在 active 訂閱即跳過，最後防線是 `DripService::subscribe()` 內的重複訂閱檢查（Job 內失敗僅記 log，lead 狀態不變）
- **FR-008**: 開通使用 `Purchase::updateOrCreate([user_id, course_id])`，同人同課重複開通不會產生第二筆購買記錄（重複開通以最新成交價覆寫 amount）；購買類型固定 `lead_conversion`（「顧問轉換」，後台與會員頁以 teal 樣式與贈送 / 購買區分）。**覆寫僅限既有記錄本身就是 `lead_conversion`，或 `status=refunded` 的作廢記錄**；其餘情形受 FR-015 守門
- **FR-011**: 開通時 `amount` 由管理員輸入（`required|integer|min:0`），寫入 `Purchase.amount`；0 元開通合法（免費體驗 / 補開通，不計營收）。前端預設值為所選課程 `display_price`，`grantableCourses` 需帶 `price / original_price / promo_ends_at` 以計算之
- **FR-009**: 兩個 Job 均為 `tries=3`、`backoff=[60, 300, 900]`；lead 或 template 已被刪除時記 warning 後靜默結束，不 retry
- **FR-010**: `email_templates` 每個 event_type 僅應存在一筆（seeder updateOrCreate 保證；DB 無 unique 約束，程式一律取 `forEvent()->first()`）
- **FR-012**: 隱藏價格的高價課 MUST 以銷售頁的漏斗落地頁版型呈現 — 規則、判定條件與隱藏清單一律以 **002 FR-021** 為準（本模組不另行定義）；有標價的高價課不適用
- **FR-013**: 前台 MUST NOT 對未實際寄出的信件宣稱已寄出；預約成功文案依 `mail_sent` 分岔

- **FR-015**: 開通 MUST NOT 靜默覆寫非顧問來源的購買記錄。`convertLead()` 寫入前查既有 `Purchase(user_id, course_id)`，若存在且 `type !== 'lead_conversion'` 且 `status !== 'refunded'`，回 409 並附既有 `type` / `amount`，除非明確傳入 `force=true`。**守門在 service 層**，前端警告只是提示，不可作為唯一防線。理由：線上刷卡記錄被改成 `lead_conversion` 會同時汙染交易類型與營收金額，且原始金額無處可還原

- **FR-016**: 開通的三步寫入（`User::firstOrCreate` → `Purchase::updateOrCreate` → `lead->update`）MUST 包在單一 `DB::transaction()`。`DripService::checkAndConvert()` 與通知信 MUST 留在 transaction 外並各自 try/catch — 外部副作用不隨回滾，也不得讓已成立的成交失敗

- **FR-017**: 開通通知信以 `lead_converted` 模板寄給 `lead.email`，變數 `{{user_name}}`、`{{course_name}}`、`{{amount}}`、`{{classroom_url}}`、`{{app_url}}`；渲染與版型比照 `CourseGiftedMail`（CommonMark → `emails/high-ticket-booking.blade.php`）。不 CC 任何內部信箱（開通是管理員主動操作，本人已知情，與 FR-014 的 lead 通知情境不同）。缺模板或寄送失敗只記 log，開通結果以 `mail_sent` 布林誠實回報

- **FR-014**: 新 lead 的內部通知收件者存於 `site_settings.high_ticket_lead_notify_cc`（逗號分隔），由後台 Email 模板管理頁編輯；未設定或格式無效時 fallback 至 `HighTicketBookingService::DEFAULT_NOTIFY_CC` 常數。解析一律走 `HighTicketBookingService::parseRecipients()`（逗號 / 分號 / 空白皆可分隔、去空去重），前後端共用同一份規則。**這份清單與付款/法律頁的「客服信箱」是不同角色**，不得混用：客服是對外聯絡管道，此清單是誰該接手這條 lead。會員的 `is_sales_consultant` 旗標**目前不參與寄信**（只管後台權限），要改成依身分自動通知需另立設計 —— CC 會把顧問信箱曝露給預約者，屆時應改用 bcc 或另寄內部通知信。

- **FR-018**: 預約 MUST 以 `(email, course_id)` 為唯一鍵去重（見 D17）；`recordLead()` 命中既有記錄時只更新 name / booked_at 與（僅限 closed → pending 的）status，不新增列、不動 notified_count / last_notified_at

- **FR-019**: 模板內容的渲染唯一入口為 `EmailTemplate::renderBody(array $vars)`（原已存在但無人呼叫）與新增的 `renderText(array $vars)`。`body_type` 僅兩值 `markdown` / `html`：markdown 走 CommonMark，html **原樣輸出、不 sanitize、不做任何後處理**。所有呼叫端（`HighTicketBookingService`、`NotifyHighTicketSlotJob`、`CourseGiftedMail`、`LessonAddedNotification`、成交通知信共用的 `TemplatedMail`）MUST NOT 自行 `new CommonMarkConverter()` — 目前這段 `str_replace + CommonMark` 在四處各寫一次，HTML 模式必須四條路徑一致，否則同一個模板在不同事件下會渲染成不同結果
- **FR-020**: 四封模板信 MUST 以 `multipart/alternative` 寄出（`Content(view:, text:)`），純文字段由 `renderText()` 自動產生：markdown 模式回傳替換變數後的原始 md（本來就是可讀純文字）；html 模式把 `<a href>` 還原成「文字 (網址)」（文字本身即網址時不重複）、區塊結束標籤轉換行、`strip_tags` + `html_entity_decode`、收斂連續空行。**不另開 `body_text` 欄位讓管理員手寫** — 雙欄位必然漂移，而純文字段的唯一目的是通過 spam filter 的 MIME_HTML_ONLY 檢查與純文字客戶端可讀，不需要逐字打磨

- **FR-021**: **後台手打的 Markdown 信件內容** MUST 以 hard break 語意渲染：converter 設 `['renderer' => ['soft_break' => "<br />\n"]]`，單次換行即產出 `<br>`，與編輯頁預覽的 `marked(..., { breaks: true })` 對齊；空一行仍是新段落（`<p>`），行為不變。此設定收在 `EmailMarkdownService::toHtml()`，適用**三個呼叫點**：`EmailTemplate::renderBody()`（011 模板信）、`BatchEmailMail`（008 批次寄信）、`SendDripEmailJob`（010 序列信）—— 這三處都是管理員在 textarea 手打的信，行為必須一致。**部落格文章（012 `PostService`）不適用**：長文散文語意、且前台 `HtmlContent.vue` 的 `marked()` 未開 `breaks`，前後端本來就一致，改了會讓既有文章版面跑掉。**這是修正而非破壞**：既有 4 筆 seeder 模板皆以 `\n\n` 分段、清單前的單換行由 list 語法接手，渲染結果不受影響；受影響的只有「管理員按一次 Enter 卻被吃掉」的內容，那本來就是錯的

- **FR-022**: `/admin/high-ticket-leads` 為兩份名單的唯一入口。`tab` 只接受 `booking`（預設）/ `subscribers`，其餘值一律當 `booking`。controller MUST **只組裝 active tab 的資料**，另一份傳 `null` —— 訂閱者資料含 per-lesson 開信聚合與事件統計，每次進 Leads 頁都算一次是白花的查詢（D24）

- **FR-023**: 訂閱者 tab 的課程來源 MUST 為 `Course::drip()` scope（`course_type = 'drip'`），與「加入序列信」下拉同一份清單。`sub_course` 傳入的 id 若不存在或非 drip 課程，fallback 至第一門 drip 課程（`ordered()` 首筆）；MUST NOT 直接以傳入 id 查訂閱者，避免非 drip 課程的資料經由網址參數外洩

- **FR-024**: 兩個 tab 的篩選參數各用各的命名：預約名單沿用 `status` / `search` / `course_id`（既有網址不得失效），訂閱者名單用 `sub_course` / `sub_status`。**MUST NOT 共用 `status`** —— 兩邊的狀態 enum 完全不同（pending/contacted/no_response/converted/closed vs active/booked/converted/completed/unsubscribed），共用會讓切 tab 後帶著一個對方不認得的值。`page` 可共用（一次只渲染一個列表）

- **FR-025**: 舊入口 MUST 完整移除，不留轉址：`GET /admin/courses/{course}/subscribers` 路由、`CourseController@subscribers` action、`Pages/Admin/Courses/Subscribers.vue`、課程編輯頁的「訂閱者」按鈕四者一起刪。保留半套等於兩份 UI 要各自維護（使用者決策）

## 設計決策

- **D23**: 合併後的殼**沿用 `Admin/HighTicketLeads/Index.vue` 這個 component 路徑**（Inertia render 字串與路由名不變），內容拆成兩個 tab 元件：`Components/Admin/Leads/BookingListTab.vue`（現有 987 行整段搬入）與 `Components/Admin/Leads/SubscriberListTab.vue`。不把訂閱者那 300 行直接塞進去 —— 987 + 300 行的單檔沒人改得動，而且兩個 tab 的 state（勾選、modal、篩選）互不相干，天然就該分檔。Page 檔只留 h1 + tab nav + 兩個 `v-if`

- **D24**: tab 走 **server-side query param**（`?tab=`）而非 Points.vue 的純前端 `v-show` 兩份都載。理由是兩頁的成本結構不同：Points.vue 的兩份資料都輕（一組設定 + 一張推薦統計表）；訂閱者 tab 要跑 per-lesson 開信/點擊聚合、per-subscriber 事件統計與分頁，把它掛在每次開 Leads 頁的路徑上，等於為了「可能會切過去看」而固定付一次重查詢。代價是切 tab 有一次 round-trip，但換來網址可分享、可重整、可加書籤，對後台是划算的

- **D25**: 訂閱者 tab 的參數加 `sub_` 前綴而非共用 `status`（FR-024）—— 兩邊狀態 enum 沒有交集，共用的話「篩已聯繫的 lead → 切到訂閱者」會變成篩一個不存在的訂閱狀態，得在切 tab 時清掉對方參數，那是更繞的做法。分開命名的附帶好處是切回來時原本的篩選還在

- **D26**: 訂閱者 tab **不做「全部課程」聚合**，一定選定一門 drip 課程。因為這頁的每個數字都以單一課程為分母 —— 進度是「第 N / 共 M 個小節」、Lesson 發信統計逐課列出、轉換率是該課的目標課程達成率，跨課程加總沒有語意。預設選第一門而非空白，是為了讓頁面一進來就有東西看（目前 drip 課程數量是個位數）

- **D27**: 訂閱者資料**開放給銷售顧問**（`is_sales_consultant`）—— 整頁留在 staff 路由群組，兩個 tab 都不做角色過濾。理由是顧問接手一條 lead 時要知道對方在序列信裡走到第幾封、開了沒、是否已預約，這正是判斷「現在打不打這通電話」的依據；資料本身是自家名單的行為紀錄，不含金流或個資敏感欄位。路由層的權限邊界（000 US6：顧問只進得去 Leads 與折扣碼兩區）不變，只是 Leads 頁的內容變多 —— 這是使用者明確選定的方案

- **D28**: 訂閱者資料組裝下沉為 `DripService::subscriberPageData(Course $course, ?string $status): array`（回傳 `subscribers` / `stats` / `lessonStats` / `conversionRate` / `bookingRate`），而不是把 `CourseController@subscribers` 那 60 行搬進 `HighTicketLeadController`。後者的 `index()` 已經有 60 行、要再接 tab 分派；而那段本來就在呼叫 `getSubscriberStats()` + `getSubscriberEventCounts()`，屬於 drip 領域自己的事。下沉後 controller 只剩一行呼叫，也讓 004 徹底不再持有 drip 的邏輯（owner 掛 010）

- **D1**: 預約表單採非同步 axios + inline 成功提示，不走 Inertia 表單跳轉 — 訪客停留在銷售頁，避免打斷高價品的說服動線
- **D2**: Email 模板存 DB（Markdown body + `{{var}}` 佔位符）而非 Blade 檔 — 業主可自行改文案；渲染沿用 BatchEmailMail 的 CommonMark 模式，前後台預覽一致
- **D3**: 預約後續流程（面談排程、外部平台對接）完全由信件內容引導，系統不介入 — 目前規模下人工銷售比自動化排程划算
- **D4**: 批次通知 / 轉序列信採 Job per lead — 操作立即回應 dispatched 數，單封寄送失敗獨立重試不影響其他人
- **D5**: 轉序列信成功後 lead 自動 `closed` — 人工銷售線結束、交給自動化；不另設 nurturing 狀態，保持狀態機簡單
- **D6**: 開通購買類型用獨立的 `lead_conversion`（原為 gift，2026-05-09 改）— 報表上「顧問轉換」與「贈送」語意不同，需可區分
- **D7**: Lead 允許同 email 跨課程各留一筆、course_id 無外鍵約束 — 不同課程是不同銷售機會，且課程軟刪除不受牽連（同課程重複預約的去重規則見 D17）
- **D8**: 開通成交價由管理員手動輸入而非取商品定價（2026-07-15 起，原寫死 amount=0）— 顧問面談後的實際成交價常與網站定價不同（私下匯款），必須以真實金額入帳才能讓交易紀錄與營收統計反映顧問銷售線的營收；預設帶入 `display_price` 減少輸入成本，允許 0 保留免費開通彈性。無 schema 變更（沿用 `purchases.amount`），營收圖表 `sum(Purchase.amount)` 自動涵蓋

- **D9**: 落地頁版型的實作約束（第 3 區整塊移除，以及隨之必要的 `topInfoVisible` observer 補償）已隨規則正典移至 **002 D28** — 本模組不重複定義，改動銷售頁前請先讀該條
- **D10**: 判定收斂成單一 computed（原 `isFunnelLanding`，擴及 drip 後更名 `isFunnelLanding`，見 002 D27），四個隱藏點共用 — 這個條件已散落在銷售頁多處（PriceDisplay、CTA 文案、預約表單），再加四處裸寫必然漂移（比照 002 D18 的 `primaryCtaLabel` 收斂）
- **D11**: 寄信結果以 `mail_sent` 布林回報，而非把寄信失敗升級為 422 — 名單比信重要：預約者的聯絡方式已經拿到，讓整個預約失敗會逼他重填一次，反而更可能流失。改為誠實告知並承諾主動聯絡

- **D13**: 收款方式確定為**場外匯款 + 後台手動開通**，不做「跳過預約頁直接刷卡」的專屬結帳連結（2026-08-03 評估後否決）。查證結果：結帳鏈路其實不擋高價課（`AddToCartRequest` 與 `CheckoutService` 只驗 `price > 0` / `selling` / `published` / 非 Portaly），訪客結帳也已支援，技術上可行；否決理由是流程選擇而非技術限制。連帶結論 —— 開通從「補充手段」升格為**唯一成交入口**，其完成度（通知、防呆、一致性）必須比照正式金流路徑，這是 D14–D16 的共同前提

- **D14**: 覆寫守門採「白名單放行」而非「黑名單阻擋」：只有 `lead_conversion` 與 `refunded` 兩種既有記錄可直接覆寫，其餘一律擋。`refunded` 視同作廢額度（對方已退款，重新成交合理，比照 008 贈課對 refunded 的處理），不需 `force`。選白名單是因為 `purchases.type` 日後還會長（現有 paid / gift / system_assigned / lead_conversion），黑名單每加一種類型就得記得補一次，漏掉就是靜默資料損毀

- **D15**: 開通通知信缺模板時**不擋開通、也不 fallback 寫死內容**，只回報 `mail_sent: false` — 與 US-6 對預約兩事件「缺模板直接擋下操作」的取捨刻意不同。預約失敗使用者可以重填，成交是已經收到錢的事實，不能因為一封信卡住；但也不該塞一封語氣不明的預設信給剛匯完款的客戶，寧可讓管理員看到「寄送失敗」自己補聯絡。此決策沿用 D11 對 `mail_sent` 的同一套誠實回報邏輯

- **D16**: 前端的既有購買警告以 `index()` 一次撈出的 `purchasesByEmail` 比對，不另開 API endpoint — 比照同一頁既有的 `dripByEmail`（`HighTicketLeadController.php`）作法，單頁 20 筆、單一查詢，選課程時純前端比對，免 roundtrip

- **D17**: 同 email + 同課程的重複預約改為「更新既有 lead」而非新增一列（2026-08-03，推翻原 D7 的保留完整歷史）— 後台是一份**待辦名單**不是事件流水帳：同一個人送三次表單就變三列待聯繫，管理員得逐列判斷是不是同一人，複製 Email 也得靠去重補救。狀態處理採「closed 回復 pending、其餘保留」：closed 多半是冷掉後丟進序列信，本人再次主動預約等於重新有意願，該回到待辦；contacted / converted 是管理員基於真實接觸下的判斷，程式不該覆寫。代價是失去重複預約的次數與時間軸 —— 目前沒有任何功能讀這份歷史，等真的要做「預約熱度」再另開事件表，不為假想需求先犧牲日常可用性

- **D18**: CC 清單放 `site_settings` 並掛在 **Email 模板管理頁**，不放付款/API 設定頁 — 那頁是金流與第三方憑證，這是信件收件人，語意不同；Email 模板管理頁本來就是「信件相關設定」的入口，管理員找得到。保留 `DEFAULT_NOTIFY_CC` 常數當 fallback 而非改成必填設定：新環境（含測試 DB）沒有這筆設定時，lead 通知信仍必須寄得出去，絕不能因為沒設定就靜默不通知任何人

- **D19**: HTML 模式採「每列一個 `body_type` 旗標」而非自動偵測內容格式 —— `str_contains($body, '<')` 之類的猜測會在「Markdown 裡夾一個 `<br>`」時誤判，而誤判的後果是整封信變成裸露的原始碼寄給客戶。連帶四個取捨：（1）**切換模式不轉換既有內容**，只改變解讀方式 —— 自動 md→html 之後切回來就回不去了；（2）**html 不 sanitize**，比照 D2 與 `HtmlContent.vue` 對後台內容的既有立場，sanitizer 會吃掉 inline style 與 `<table>`，那正是 email HTML 唯一能用的排版手段；（3）**欄位仍叫 `body_md`**，改名要動 model / request / seeder / 四個 Mailable / 前端，收益只有命名好看；（4）**預覽改 sandbox iframe**，現行預覽套後台的 `prose` class 但真實信件的 blade 完全沒有 CSS，預覽比實際好看，iframe 讓兩種模式看到的都等於收件匣，順便擋掉貼進來的 `<script>` 在後台執行

- **D20**: 純文字備援（FR-020）與 HTML 模式同批交付，不另案處理 —— 兩者共用 `renderBody` / `renderText` 這條收斂後的渲染管線，分開做等於把同一段程式改兩次。動機是投遞率：模板信目前是 HTML-only，這是 SpamAssassin 明確扣分的 MIME_HTML_ONLY，而 010 的 drip 信已經因為同類問題被 Gmail 丟過垃圾桶（見 `DripMailDeliverabilityTest`）。要寄的那份 HTML 文案本身帶有收入數字、限量名額、時限與黑名單警告等高風險詞組，內容分數已經吃緊，能補的技術分不該省。寫法沿用 `NewsletterBroadcastMail` 既有的 `view: + text:`，不引入新機制

- **D21**: 換行不一致選擇「改後端去配合預覽」而不是「改預覽去配合後端」或「教管理員按兩次 Enter」—— 模板是寫給非工程師用的信件編輯器，「按一次 Enter 不會換行」是 Markdown 的規格，不是任何人的心智模型；而 US6 驗收條款從一開始就宣告「單行換行與寄出效果一致」，所以這是把實作對齊到早已聲明的規格，不是改需求。改預覽（拿掉 `breaks: true`）雖然也能達成一致，但那是把兩邊一起對齊到錯的那一邊。實作只有一行 converter 設定（`renderer.soft_break`），比在 UI 上加說明文字或做編輯器層的自動 `\n\n` 轉換都便宜

- **D22**: hard break 設定抽成 `EmailMarkdownService::toHtml()` 而非在三處各寫一次設定陣列 —— 一模一樣的 duplication 正是 FR-019 這次要消滅的東西，再手動複製三份等於當場製造下一個漂移點。owner 掛 **011** 而不是 000：這條規則是從 `EmailTemplate::renderBody()` 抽出來的，模板系統與 D2「信件內容走 CommonMark」的決策都在本模組，008 / 010 是消費者（已列 touchpoints）。日後若第四、第五個信件路徑出現、或需要更多 email 專用的渲染規則（例如自動加 `max-width` 容器），再評估是否升格到 000

- **D23**: 成交通知信**重用 `TemplatedMail`**（即原 `HighTicketBookingMail`，本次一併更名），推翻 T010 原本「新增 `LeadConvertedMail`」的規劃。原規劃寫於 US7 之前 —— 當時該 Mailable 還會自己 `new CommonMarkConverter()`、自己查預約模板，硬塞第三個事件進去確實會髒；US7（FR-019）把它掏空成只收 `subject / htmlBody / textBody` 的搬運工之後，它已同時服務預約確認與新時段通知兩個事件，再開一個 `content()` 一字不差的類別，正是 FR-019 這次要消滅的重複。更名而非沿用舊名，是因為「HighTicketBooking」已經名不副實，日後第四、第五個模板事件接上來時不會有人敢用它。代價：模板查詢與「缺模板不擋開通」（D15）的判斷落在 service 而非 Mailable 建構子 —— 這反而更對，那是成交流程的決策，不是一封信的決策。連帶取捨：`CourseGiftedMail` / `LessonAddedNotification` **不併入** `TemplatedMail`，它們有「缺模板時 fallback 到寫死 blade」的分支（系統自動流程不能因為模板被刪就中斷），與本類別「呼叫端先查好才進來」的前提不同，強行合併會把 fallback 邏輯塞回 Mailable

- **D12**: 高價課測試已可持久化 `type=high_ticket`（2026-08-01 起）— 原本 `2026_04_09_000001` 只在 MySQL 分支擴 enum，sqlite 測試 DB 停在三值、任何高價課都無法落庫；`2026_08_01_000001`（004 D10）改用 `Schema::change()` 帶完整值列表後兩邊對齊，`CourseTypeTest` 已實測通過。既有測試（LeadConvertTest、FunnelStopTest、BookingMailFailureTest）仍走 service 層＋記憶體指定 type，改寫成 HTTP 層非必要，日後新增測試可直接建課

## Schema

- **本次 schema 變更**：`2026_08_03_000001_add_body_type_to_email_templates_table.php` — `string('body_type', 10)->default('markdown')->after('subject')`。用 string 不用 enum（比照 004 `change_content_category_to_string_on_courses` 的既有作法）；有 default，既有 4 筆自動落在 markdown，正式站不需資料處理
- **US8（名單管理頁合併）無 schema 變更** —— 純後台資訊架構調整，讀的是既有 `high_ticket_leads` 與 `drip_subscriptions` / `drip_email_events`
- US5（開通補強）無 schema 變更（新增的是 `email_templates` 的一筆資料，不是欄位；覆寫守門與 transaction 皆為既有欄位上的邏輯）
- US1 亦無 schema 變更（landing page 隱藏與 mail_sent 回報皆為既有欄位與前端呈現）
- `high_ticket_leads` — 預約產生的潛在客戶；status 銷售漏斗 enum(pending 待聯繫 / contacted 已聯繫 / no_response 未回應 / converted 已成交 / closed 已關閉) 預設 pending；notified_count（unsigned tinyint）與 last_notified_at 只由 NotifyHighTicketSlotJob 寄送成功後更新；booked_at 為最近一次提交時間（非 created_at 語意）；email / status / course_id 皆有索引。**DB 無 (email, course_id) unique 約束**，去重由 `recordLead()` 在應用層負責（D17）— 歷史資料可能已有重複列，加 unique 需先清理，現階段不值得
- `site_settings.high_ticket_lead_notify_cc` — 預約通知 CC 收件者，逗號分隔字串；不存在或為空即 fallback 至 `DEFAULT_NOTIFY_CC`（FR-014）
- `email_templates` — 系統信件模板；event_type 為程式對接鍵（index，非 unique，程式取 first）；subject 與 body_md 均支援 `{{var}}` 佔位符；由 EmailTemplateSeeder 以 event_type updateOrCreate 初始化 4 筆
- `email_templates.body_md` — **模板原始內容，格式由 `body_type` 決定**（歷史命名，非僅 Markdown，見 D19）
- `email_templates.body_type` — `markdown`（預設，經 CommonMark 轉 HTML）或 `html`（原樣輸出）；無 DB 層 enum 約束，合法值由 `EmailTemplateRequest` 把關，未知值一律當 markdown 處理

## Tasks

- [x] T001 `convert()` 驗證新增 `amount` (`required|integer|min:0`) 並傳入 service；`index()` 的 `grantableCourses` select 加 `price / original_price / promo_ends_at` 並 map 出 `display_price` in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T002 `convertLead(HighTicketLead $lead, int $courseId, int $amount)` — `Purchase::updateOrCreate` 的 amount 改寫入參數值 in `app/Services/HighTicketLeadService.php`
- [x] T003 開通 modal 加「成交價格」number input：選擇商品時自動帶入該課程 `display_price`、可修改、必填 ≥ 0、submit 帶 `amount`；操作說明第三條改為「開通所選商品（以成交價格入帳）」 in `resources/js/Pages/Admin/HighTicketLeads/Index.vue`

### 預約型 landing page 化 + 寄信失敗誠實回報（US1/US2 追加）

Phase A — 後端 mail_sent 回報
- [x] T004 `book()` 加 `$mailSent` 旗標（catch 內設 false），回傳 `['success' => true, 'mail_sent' => $mailSent]`；lead 建立、Meta CAPI、`checkAndBook` 順序與行為不變 in `app/Services/HighTicketBookingService.php`
- [x] T005 [P] `store()` 成功回應改帶 `mail_sent` in `app/Http/Controllers/HighTicketBookingController.php`

Phase B — 銷售頁前端（相依 T005）
- [x] T006 加 computed `isFunnelLanding`；四處套用隱藏：hero 的 `durationLabel` 行、第 3 區左欄「課程資訊」h3 + 規格 grid、第 3 區右欄「免費試閱」、懸浮面板「免費試閱」。**外層 `div[ref=topInfoRef]` 與左欄 `div.flex-1` 保留**（D9）in `resources/js/Pages/Course/Show.vue`〔touchpoint 002〕
- [x] T007 加 `bookingMailSent` ref，`submitBooking` 由回應寫入；預約成功區塊文案依此分岔 in `resources/js/Pages/Course/Show.vue`〔touchpoint 002〕

Phase C — 驗證
- [x] T008 新增 feature test：mock Mailer 拋例外 → 回應 `mail_sent=false`、`high_ticket_leads` 仍有該筆、drip 訂閱仍轉 `booked`；另覆蓋正常路徑 `mail_sent=true` in `tests/Feature/HighTicket/BookingMailFailureTest.php`
- [x] T009 `php artisan test` 全綠（含既有 LeadConvertTest、FunnelStopTest）＋ `npm run build` exit 0；隱藏效果與懸浮面板由使用者以瀏覽器確認

### 開通功能補強：通知信 + 覆寫守門 + 交易一致性（US5/US6 追加）

Phase A — 通知信管線（可平行）
- [x] T010 ~~新增 `LeadConvertedMail`~~ **改為：`HighTicketBookingMail` 更名為 `TemplatedMail`，成交通知信重用之**（D23 推翻原規劃）；模板查詢與缺模板判斷留在 service in `app/Mail/TemplatedMail.php`
- [x] T011 [P] seeder 加第 5 筆 `lead_converted`（name「顧問成交開通通知」）；文案必須包含「用此 email 到網站收驗證碼登入」 in `database/seeders/EmailTemplateSeeder.php`
- [x] T012 [P] `$availableVariables` 加 `lead_converted` 五個變數條目 in `app/Http/Controllers/Admin/EmailTemplateController.php`
- [x] T013 [P] `eventTypeLabels` 加 `lead_converted: '顧問成交開通通知'` in `resources/js/Pages/Admin/EmailTemplates/Index.vue`

Phase B — Service 層（相依 T010）
- [x] T014 `convertLead()` 加 `bool $force = false`；寫入前查既有 Purchase，依 FR-015 白名單判定，擋下時回 `['success' => false, 'conflict' => [...]]`（controller 轉 409）in `app/Services/HighTicketLeadService.php`
- [x] T015 三步寫入包 `DB::transaction()`；`checkAndConvert()` 與寄信移到 transaction 外各自 try/catch，回傳值加 `mail_sent` in `app/Services/HighTicketLeadService.php`

Phase C — Controller 與前端（相依 T014/T015）
- [x] T016 `convert()` 驗證加 `force`（`sometimes|boolean`）並傳入；service 回 conflict 時以 409 + 中文訊息回應（`PURCHASE_TYPE_LABELS` 常數提供類型中文）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T017 `index()` 比照 `dripByEmail` 多傳 `purchasesByEmail`（course_id / type / amount / status）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T018 開通 modal：依 `convertCourseId` 比對 `purchasesByEmail` 顯示既有購買警告 + 覆寫確認勾選（非 lead_conversion 才要求）、送出帶 `force`、409 訊息 inline 顯示；結果摘要依 `mail_sent` 分岔 in `resources/js/Pages/Admin/HighTicketLeads/Index.vue`

Phase D — 驗證
- [x] T019 補測試：既有 `type=paid` 被擋（Purchase 原封不動）、帶 force 放行、refunded 直接放行、模板存在寄信且 `mail_sent=true`、缺模板與 Mailer 拋例外時開通仍成功且 `mail_sent=false`、Purchase 寫入失敗不留孤兒 user in `tests/Feature/HighTicket/LeadConvertTest.php`
- [x] T020 `php artisan test` 全綠（既有 5 個 LeadConvertTest 必須維持通過，其中 repeat convert 走 lead_conversion 覆寫路徑不應被新守門擋下）＋ `npx vite build` exit 0＋ `php artisan db:seed --class=EmailTemplateSeeder` 後後台可見第 5 個模板

### 預約流程三項修正：lead 去重 + 寫入順序 + CC 可設定（US2/US6 追加）

- [x] T021 `book()` 把 `HighTicketLead` 寫入移到寄信之前；新增 private `recordLead()` 實作 (email, course_id) 去重與 closed → pending 回復 in `app/Services/HighTicketBookingService.php`
- [x] T022 `NOTIFY_CC` 常數改名 `DEFAULT_NOTIFY_CC`（public，供 controller 與測試引用）＋ `NOTIFY_CC_SETTING_KEY` 常數；新增 private `notifyCc()` 讀 SiteSetting、public static `parseRecipients()` 解析清單 in `app/Services/HighTicketBookingService.php`
- [x] T023 `index()` 多傳 `notifyCc` / `notifyCcDefault`；新增 `updateNotifyCc()`（closure 驗證每筆 Email 格式，正規化成 `, ` 分隔後寫 SiteSetting）in `app/Http/Controllers/Admin/EmailTemplateController.php`
- [x] T024 加 `PUT /admin/email-templates/notify-cc` 路由，**必須宣告在 `/email-templates/{template}` 之前**（literal 不是 model key）in `routes/web.php`
- [x] T025 列表頁上方加 CC 設定卡（useForm + inline 錯誤 + 儲存中狀態）in `resources/js/Pages/Admin/EmailTemplates/Index.vue`
- [x] T026 新增測試：重複預約不重複建 lead、跨課程各自獨立、closed 回 pending 而 converted 不變、寄信拋例外仍留 lead、設定 CC 取代預設、留空 fallback 預設、後台儲存與格式驗證 in `tests/Feature/HighTicket/BookingLeadRecordTest.php`
- [x] T027 `php artisan test` 全綠（238 passed，既有 BookingMailFailureTest 對預設 CC 的斷言維持通過）＋ `npm run build` exit 0

### HTML 模板模式與純文字備援（US7）

Phase A — Schema 與渲染收斂（先做，後面全部依賴）
- [x] T028a 新增 `EmailMarkdownService::toHtml(string $md): string` — CommonMark + `renderer.soft_break => "<br />\n"`（FR-021 / D22）in `app/Services/EmailMarkdownService.php`
- [x] T028 新增 migration：`email_templates` 加 `body_type`（string 10、default `markdown`、after `subject`）in `database/migrations/2026_08_03_000001_add_body_type_to_email_templates_table.php`
- [x] T029 `$fillable` 加 `body_type`；`renderBody()` 改為唯一渲染入口（替換變數 → html 原樣 / markdown 走 CommonMark **並設 `renderer.soft_break => "<br />\n"`**，FR-021）；新增 `renderText()`（markdown 回原始 md、html 走 a 標籤還原 + 區塊轉換行 + strip_tags + entity decode + 收斂空行）in `app/Models/EmailTemplate.php`
- [x] T030 建構子不再自行轉 Markdown，改收已渲染的 `$htmlBody` 與 `$textBody`；`content()` 加 `text: 'emails.template-text'` in `app/Mail/HighTicketBookingMail.php`
- [x] T031 新增純文字版 blade（單行 `{!! $textBody !!}`）in `resources/views/emails/template-text.blade.php`

Phase B — 四個呼叫端改吃 renderBody / renderText（可平行，皆依賴 Phase A）
- [x] T032 [P] `book()` 改用 `$template->renderBody($vars)` / `renderText($vars)` 並傳給 Mailable，刪除本地 `str_replace` in `app/Services/HighTicketBookingService.php`
- [x] T033 [P] 同上改法 in `app/Jobs/NotifyHighTicketSlotJob.php`
- [x] T034 [P] 刪除建構子內的 `new CommonMarkConverter()`，改用 `renderBody()` / `renderText()`；模板分支的 `content()` 加 `text:`（無模板 fallback 分支維持原樣）in `app/Mail/CourseGiftedMail.php`
- [x] T035 [P] 同上改法 in `app/Mail/LessonAddedNotification.php`

Phase B2 — 換行修正擴及其他兩條信件路徑（跨模組，依賴 T028a）
- [x] T035a [P] 裸 `new CommonMarkConverter()` 改用 `EmailMarkdownService::toHtml()`（touchpoint，owner 008-members-admin）in `app/Mail/BatchEmailMail.php`
- [x] T035b [P] 同上改法，`stripStylesForEmail()` 與寄送流程不動（touchpoint，owner 010-drip-email）in `app/Jobs/SendDripEmailJob.php`

Phase C — 後台編輯
- [x] T036 驗證加 `body_type` in `['markdown','html']` + 中文錯誤訊息 in `app/Http/Requests/Admin/EmailTemplateRequest.php`
- [x] T037 `form` 加 `body_type`；內容區加 Markdown / HTML 分段切換鈕（`cursor-pointer` + 選中態 + 一行說明「切換只改變解讀方式，不會轉換既有內容」）；預覽改 sandbox `<iframe :srcdoc>`（HTML 模式回原字串、Markdown 模式維持 `marked(..., { breaks: true })`）；placeholder 依模式切換 in `resources/js/Pages/Admin/EmailTemplates/Edit.vue`

Phase D — 驗證
- [x] T038 新增測試：html 模式原樣輸出（不被包 `<p>`、inline style 保留）、markdown 模式仍轉 HTML（回歸）、**markdown 單次換行渲染成 `<br>` 且空行仍分段（FR-021）**、`{{var}}` 在 html 屬性內照樣替換、預約信 `htmlBody` 為原始 HTML、`renderText()` 兩種模式輸出、後台 PUT 接受 html 並拒絕非法 `body_type` in `tests/Feature/HighTicket/EmailTemplateHtmlModeTest.php`
- [x] T039 `php artisan test` 全綠（基準 238 passed；`BookingMailFailureTest` / `BookingLeadRecordTest` / `LeadConvertTest` 在 Mailable 簽章變更後必須維持通過；010 的 `DripMailDeliverabilityTest` / `ClaimWordingTest` 在 converter 換掉後必須維持通過）＋ `npx vite build` exit 0
- [x] T039a `/sync` 對帳：008 與 010 的 spec 進度日誌各補一行換行修正（touchpoint 變更需回寫 owner 模組）
- [ ] T040 實寄驗證：後台切 HTML 模式貼入模板 → tinker 觸發 `book()` 寄到自己信箱 → 「顯示原始郵件」確認 `Content-Type: multipart/alternative` 且兩段內容皆正確

### 名單管理頁合併：預約名單 + 訂閱者名單 tab（US8）

Phase A — 後端資料層（T041 先做，T042 依賴它）
- [x] T041 新增 `subscriberPageData(Course $course, ?string $status): array` — 把 `CourseController@subscribers` 的查詢、狀態統計、`getSubscriberStats()` / `getSubscriberEventCounts()` 合併與 `total_lessons` 整段搬入，回傳 `['course', 'subscribers', 'stats', 'lessonStats', 'conversionRate', 'bookingRate']`（touchpoint，owner 010-drip-email）in `app/Services/DripService.php`
- [x] T042 `index()` 加 tab 分派：讀 `tab`（白名單 booking/subscribers，預設 booking）；booking 分支維持現狀；subscribers 分支查 `Course::drip()->ordered()` 當下拉來源、依 `sub_course` 解析目標課程（不合法則取首筆，FR-023）、呼叫 `subscriberPageData()`。render props 加 `tab` / `dripCourseOptions` / `subscriberData`（另一分支傳 null）/ `filters.sub_course` / `filters.sub_status`。注入 `DripService` in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T043 [P] 刪除 `subscribers()` action 與其 `DripSubscription` import（若無其他使用）（touchpoint，owner 004-course-admin）in `app/Http/Controllers/Admin/CourseController.php`
- [x] T044 [P] 刪除 `GET /admin/courses/{course}/subscribers` 路由（touchpoint，owner 000-platform-core）in `routes/web.php`

Phase B — 前端拆檔（依賴 T042）
- [x] T045 新增 `BookingListTab.vue`：現有 `HighTicketLeads/Index.vue` 的 script + template 整段搬入，`defineOptions({ layout })` 與最外層容器留給 shell；props 沿用原本的 `leads` / `filters` / `highTicketCourses` / `dripCourses` / `notifyTemplate` / `dripByEmail` / `grantableCourses`（**行為與樣式零變化**）in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T046 新增 `SubscriberListTab.vue`：`Pages/Admin/Courses/Subscribers.vue` 內容搬入，移除麵包屑、AdminLayout 與頁面標題；頂部改為 drip 課程下拉（`dripCourseOptions`）+ 既有狀態篩選；所有 `router.get` 目標改為 `/admin/high-ticket-leads`，參數帶 `tab=subscribers` / `sub_course` / `sub_status` / `page`；下拉為空時顯示「尚未建立任何連鎖 Email 課程」空狀態（touchpoint，owner 010-drip-email）in `resources/js/Components/Admin/Leads/SubscriberListTab.vue`
- [x] T047 `Index.vue` 改為 shell：h1 + 兩顆 tab 按鈕（比照 `Settings/Points.vue` 的 `border-b-2` 樣式與 `cursor-pointer`）+ 兩個 `v-if` 掛對應元件；點 tab 以 `router.get('/admin/high-ticket-leads', { tab }, { preserveScroll: true })` 切換 in `resources/js/Pages/Admin/HighTicketLeads/Index.vue`
- [x] T048 [P] 刪除舊訂閱者頁（touchpoint，owner 010-drip-email）in `resources/js/Pages/Admin/Courses/Subscribers.vue`
- [x] T049 [P] 移除工具列「訂閱者」Link（含 `delivery_mode === 'drip'` 條件）（touchpoint，owner 004-course-admin）in `resources/js/Pages/Admin/Courses/Edit.vue`

Phase C — 驗證
- [x] T050 新增測試：預設 tab 為 booking 且不含 subscriberData、`tab=subscribers` 回傳訂閱者資料、課程下拉只含 drip 課程、`sub_course` 指到非 drip 課程時 fallback 首筆 drip 課、`sub_status` 篩選生效、銷售顧問可存取兩個 tab、舊路由 `/admin/courses/{id}/subscribers` 回 404 in `tests/Feature/HighTicket/LeadsTabsTest.php`
- [x] T051 `php artisan test` 全綠（基準 249 passed；010 既有 drip 測試不得退化）＋ `npx vite build` exit 0；兩個 tab 的實際操作由使用者以瀏覽器確認
- [x] T052 `/sync` 對帳：010 的 owner_files 以 `Components/Admin/Leads/SubscriberListTab.vue` 取代 `Pages/Admin/Courses/Subscribers.vue`、US11 條款改指新位置並補 `subscriberPageData()`；004 移除 `subscribers()` 相關 touchpoint 敘述；000 US6 補一句顧問可見訂閱者資料（D27）

## 進度日誌

- 2026-08-04: US8 完成（T041–T052）— `/admin/high-ticket-leads` 合併為兩個 tab：預約名單（原樣搬入 `Components/Admin/Leads/BookingListTab.vue`，行為零變化）與訂閱者名單（新 `SubscriberListTab.vue`，課程改頁內下拉、只列 drip 課）。tab 走 `?tab=` server-side 分派、只組裝該 tab 資料（D24）；訂閱者資料組裝下沉為 `DripService::subscriberPageData()`，`CourseController@subscribers`、`GET /admin/courses/{course}/subscribers`、`Pages/Admin/Courses/Subscribers.vue` 與課程編輯頁「訂閱者」按鈕四者一併移除（FR-025）。訂閱者資料改對銷售顧問開放（D27）。新增 LeadsTabsTest（10 tests），全套 269 passed（1422 assertions）、`npx vite build` 綠。實際 tab 操作待業主以瀏覽器確認。
- 2026-08-04: 開通功能補強完成（T010–T020）— 後台開通自 D13 起是唯一成交入口，補齊三項比照金流路徑的保證：（1）**覆寫守門**（FR-015/D14）：既有 Purchase 非 `lead_conversion` 且非 `refunded` 時回 409 並附原類型與金額，帶 `force=true` 才放行；守門為 transaction 外的唯讀查詢，被擋下時連 user 都不建。（2）**交易一致性**（FR-016）：user / purchase / lead 三步寫入包 `DB::transaction()`，drip 停信與通知信移到外面各自 try/catch。（3）**成交通知信**（FR-017）：新增第 5 個模板 `lead_converted`（引導以 Email 驗證碼登入），不 CC 任何內部信箱，缺模板或寄送失敗只回 `mail_sent: false` 不擋成交（D15）。Mailable 決策推翻 T010：`HighTicketBookingMail` 更名 `TemplatedMail` 並重用，不另開類別（D23）。前端開通 modal 加既有購買警告卡 + 覆寫確認勾選（D16，比對 `index()` 新傳的 `purchasesByEmail`）。LeadConvertTest 5 → 14 tests，全套 259 passed、`npx vite build` 綠、seeder 後後台可見 5 個模板。
- 2026-08-03: US7 完成（T028a–T039a）— Email 模板加 `body_type`（markdown / html），HTML 模式原樣寄出、編輯頁加格式切換鈕與 sandbox iframe 預覽；渲染收斂到 `EmailTemplate::renderBody()` / `renderText()`，四個呼叫端不再各自 `new CommonMarkConverter()`，`HighTicketBookingMail` 改收已渲染的 HTML + 純文字；四封模板信改 `multipart/alternative` 補純文字段（FR-020，解 MIME_HTML_ONLY）。另修長期存在的換行 bug：新增 `EmailMarkdownService::toHtml()` 設 `soft_break`，單次 Enter 即真換行，同步套用 008 `BatchEmailMail` 與 010 `SendDripEmailJob`（FR-021 / D21 / D22）。查驗本機既有內容：4 個模板中 3 個、16 篇 drip 小節中 3 篇會多出換行，全部是作者手動斷行卻被吃掉的位置，屬修正。新增 EmailTemplateHtmlModeTest（12 tests），全套 249 passed（1249 assertions）、`npx vite build` 綠。T040 實寄驗證待業主操作。
- 2026-08-03: 預約流程三項修正 —（1）lead 改以 (email, course_id) 去重，重複預約更新既有列、closed 回復 pending（D17）；（2）lead 寫入移到寄信之前，SMTP 卡住不再有掉單風險；（3）通知 CC 從寫死常數改為 `site_settings` + 後台 Email 模板管理頁可編輯，常數降級為 fallback（D18）。新增 BookingLeadRecordTest（7 tests），全套 238 passed、build 綠。
- 2026-08-03: 狀態篩選 tab 改用與列內色塊相同的配色（tabActive / tabIdle 併入 statusButtons，tabs 由其 map 產生，「全部」另外定義為中性色）— 配色只有一份定義，日後改色不會兩處漂移。
- 2026-08-03: 狀態欄下拉改為四顆 25×25 字母色塊按鈕（P/C/D/X），一鍵切換不用展開；課程欄寬 w-52 → w-[173px] 讓出空間給狀態欄，狀態與通知次數兩個 th 加 `whitespace-nowrap`（只讓寬度不夠，圖例仍會在「X關」斷行、通知次數被壓成直排）。原 statusLabels / statusClasses / statusOptions 三張表併為單一 statusButtons 設定（class 字串寫全，Tailwind scanner 才掃得到）。
- 2026-08-03: 批次動作列新增「複製 Email」按鈕 — 勾選的 leads email 以 `, ` 串接複製（去重），供貼到外部郵件工具的收件人欄；純前端，沿用既有單筆複製的 clipboard 模式。
- 2026-08-02: 預約確認信的 CC 收斂為 `NOTIFY_CC` 常數；移除硬寫的客服信箱 themustbig+learn@gmail.com（目前沒有銷售顧問，該信箱是對外客服角色、不是 lead 收件者），改為只 CC 管理員 themustbig+leads@gmail.com。測試明確斷言「不再 CC 客服信箱」。
- 2026-08-01: 落地頁隱藏範圍擴大 — 第 3 區整塊不再渲染（含頂部「立即預約」按鈕），規則與實作約束見 002 FR-021 / D28；本模組僅同步條款文字，無程式變更。
- 2026-08-01: 落地頁版型規則正典移交 002（FR-012/D9/US1 條款改為引用）；computed 隨之更名 isFunnelLanding，行為對高價課不變。
- 2026-08-01: 預約型高價課銷售頁 landing page 化（隱藏 hero 堂數/時長、課程資訊區、頂部與懸浮面板的免費試閱，保留「立即預約」；判定收斂為 isBookingLanding，刻意保留 topInfoRef 外層以免懸浮面板失效）＋ 預約確認信寄送失敗改以 mail_sent 誠實回報（前台不再叫使用者去收沒寄出的信，lead/CAPI/drip 停信全部照跑）。新增 BookingMailFailureTest（3 tests），全套 199 passed、npm build 綠。/sync 對帳：無孤兒檔案，發現並記錄 D12（sqlite 測試 DB 的 type CHECK 未含 high_ticket）。
- 2026-07-24: 預約確認信新增 CC 客服信箱 themustbig+learn@gmail.com（管理員同步收到預約通知）。
- 2026-07-15: 開通「成交價格」欄位完成（T001–T003，TDD 5 測試、全套 148 passed、build 過）。
- 2026-07-15: 規劃開通「成交價格」欄位（S 級小改，draft 待審）。
- 2026-07-11: Leads 名單 Email 旁新增快速複製按鈕（clipboard，複製後綠勾回饋）。
- 2026-07-06: 領域重組 — 自 008-high-ticket-booking 重寫，依實際 codebase 校正

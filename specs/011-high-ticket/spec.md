---
id: 011-high-ticket
status: draft
owner_files:
  - app/Mail/LeadConvertedMail.php
  - app/Http/Controllers/HighTicketBookingController.php
  - app/Http/Controllers/Admin/HighTicketLeadController.php
  - app/Http/Controllers/Admin/EmailTemplateController.php
  - app/Http/Requests/Admin/EmailTemplateRequest.php
  - app/Models/HighTicketLead.php
  - app/Models/EmailTemplate.php
  - app/Services/HighTicketBookingService.php
  - app/Services/HighTicketLeadService.php
  - app/Jobs/NotifyHighTicketSlotJob.php
  - app/Jobs/SubscribeDripLeadJob.php
  - app/Mail/HighTicketBookingMail.php
  - resources/views/emails/high-ticket-booking.blade.php
  - resources/js/Pages/Admin/HighTicketLeads/Index.vue
  - resources/js/Pages/Admin/EmailTemplates/Index.vue
  - resources/js/Pages/Admin/EmailTemplates/Edit.vue
  - database/migrations/2026_04_09_000002_create_email_templates_table.php
  - database/migrations/2026_04_10_000001_create_high_ticket_leads_table.php
  - database/seeders/EmailTemplateSeeder.php
  - tests/Feature/HighTicket/LeadConvertTest.php
  - tests/Feature/HighTicket/BookingMailFailureTest.php
  - tests/Feature/HighTicket/BookingLeadRecordTest.php
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
    why: SubscribeDripLeadJob 呼叫 DripService::subscribe() 建立訂閱並立即發送第一封序列信
  - file: app/Mail/BatchEmailMail.php
    owner: 008-members-admin
    why: Leads「發送郵件」批次功能沿用會員後台的 BatchEmailMail（Markdown 渲染）
  - file: app/Mail/CourseGiftedMail.php
    owner: 008-members-admin
    why: 讀取本模組 email_templates（event_type=course_gifted）；模板存在時改用 emails/high-ticket-booking.blade.php 版型寄送
  - file: app/Mail/LessonAddedNotification.php
    owner: 004-course-admin
    why: 讀取本模組 email_templates（event_type=lesson_added）
  - file: routes/web.php
    owner: 000-platform-core
    why: 預約 API（`POST /course/{course}/book`，throttle:5,1）、Leads 後台與 Email 模板路由（含 `PUT /admin/email-templates/notify-cc`，須宣告在 `{template}` 之前）
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
- [x] 同一 email 重複預約**同一課程**不產生第二筆 lead：更新既有記錄的 name 與 booked_at，status 為 `closed` 者回復 `pending`（重新有意願），`contacted` / `converted` 維持管理員設定的狀態；不同課程仍為各自獨立的 lead
- [x] 非 high_ticket 或未開啟隱藏價格的課程呼叫預約 API 時回 422「此課程不接受預約」

### User Story 3 - 後台 Leads 名單管理 (Priority: P2)

管理員在 `/admin/high-ticket-leads` 檢視所有預約者，依狀態 / 課程 / 關鍵字篩選，
追蹤每位潛在客戶的銷售漏斗階段與序列信紀錄。

**驗收**：
- [x] 列表顯示姓名、Email、課程、狀態、通知次數、序列信紀錄、預約時間；依 booked_at 降冪、每頁 20 筆分頁
- [x] 狀態篩選（待聯繫 / 已聯繫 / 已成交 / 已關閉）、課程下拉篩選（僅列 `type=high_ticket` 課程）、姓名或 Email 關鍵字搜尋（LIKE 模糊比對、300ms debounce）三者可組合，分頁保留查詢參數
- [x] 可直接更新單筆 lead 狀態（`PATCH /admin/high-ticket-leads/{lead}/status`），列表即時反映；狀態欄為四顆色塊按鈕（P 待聯繫黃 / C 已聯繫藍 / D 已成交綠 / X 已關閉灰），一鍵切換免展開下拉，目前狀態以實心底色 + ring 標示、其餘為淺色底，欄頭附 `P待 / C聯 / D成 / X關` 圖例，點擊當前狀態不發請求
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
- [x] 勾選 `pending`（冷掉）或 `closed` 的 leads 點「加入序列信」，下拉選單列出所有 `course_type=drip` 課程供選擇
- [x] lead 的 email 已有「任一」active drip_subscription 時跳過（不限同課程），回應摘要 `{dispatched, skipped}`
- [x] 每筆派送 `SubscribeDripLeadJob`：以 email `firstOrCreate` user（nickname=lead.name、無密碼，沿用驗證碼登入）→ `DripService::subscribe()` 建立訂閱並立即發第一封序列信 → 成功後 lead status 自動改 `closed`
- [x] 非「已成交」的 lead 課程欄有「開通」按鈕：確認 modal 顯示 lead 姓名 / Email、三條操作說明、商品下拉（所有課程）
- [x] 開通 modal 有「成交價格」欄位（整數、必填、≥ 0）：選擇商品時自動帶入該課程當前顯示價（`display_price`，含促銷邏輯），管理員可改為實際成交金額（私下匯款成交價可能與網站定價不同）
- [x] 確認開通後：`firstOrCreate` user（password 隨機 16 碼）→ `Purchase::updateOrCreate`（type=`lead_conversion`、status=paid、amount=成交價格）→ lead status 改 `converted`；列表 inline 更新該列並於頁頂顯示結果摘要；金額自動計入後台交易列表與營收圖表（`sum(Purchase.amount)`）
- [ ] 上述三步寫入包在單一 `DB::transaction()`：任一步失敗全部回滾，不留下「有帳號但沒課程」的孤兒 user；drip 停信與通知信留在 transaction 外（最佳努力，不隨回滾、也不擋成交）
- [ ] 開通成功後寄出 `lead_converted` 模板通知信給 lead.email：告知課程已開通、成交金額，以及**用這個 email 到網站收驗證碼登入**（新建帳號無密碼，這是對方唯一的入口資訊）
- [ ] 缺模板或寄送失敗時開通照常完成，回應帶 `mail_sent: false`，前端結果摘要改顯示「已開通，但通知信寄送失敗，請自行聯絡對方」— 不得宣稱已寄出（比照 FR-013）
- [ ] 既有 `Purchase(user_id, course_id)` 的 `type` 非 `lead_conversion`（線上刷卡 / 贈課 / 系統指派）且 `status` 非 `refunded` 時，開通預設回 409 拒絕並附既有記錄的類型與金額；帶 `force=true` 才放行覆寫
- [ ] 開通 modal 依所選商品即時比對該 lead 是否已持有：命中時顯示警告框（原始類型 + 金額），非 `lead_conversion` 者須勾選「我了解這會覆寫原有購買記錄」才啟用「確認開通」

### User Story 6 - Email 模板系統後台管理 (Priority: P2)

管理員在 `/admin/email-templates` 統一編輯所有可模板化的系統信件，
不依賴工程師即可修改主旨與內容。

**驗收**：
- [ ] 列表顯示 5 個模板，event_type 以中文標籤呈現：客製服務預約確認、課程贈禮通知、課程新增小節通知、客製服務新時段通知、顧問成交開通通知
- [x] 編輯頁顯示模板名稱、觸發事件（唯讀）、主旨、body_md 內容編輯區；「插入變數」按鈕列依 event_type 顯示可用變數，點擊插入 textarea 游標位置
- [x] 編輯 / 預覽模式切換：預覽以 `marked` + `breaks: true` 渲染，單行換行與寄出效果一致
- [x] 儲存驗證 name ≤ 100、subject ≤ 255、body_md 必填（中文錯誤訊息），成功後導回列表並 flash「模板已更新」
- [ ] `EmailTemplateSeeder` 以 event_type 為 key `updateOrCreate` seed 5 個預設模板，可重複執行不覆蓋主鍵
- [x] `course_gifted` / `lesson_added` 事件由對應 Mailable 建構時讀取模板，模板不存在時 fallback 至寫死內容；high_ticket 預約相關兩事件無 fallback（缺模板直接擋下操作）；`lead_converted` 缺模板不擋操作但也不 fallback — 不寄信並回報 `mail_sent: false`（見 D15）
- [x] 列表頁上方有「預約通知收件者（CC）」設定卡：單一文字框（逗號分隔多筆）+ 儲存按鈕，`PUT /admin/email-templates/notify-cc` 寫入 `site_settings`；每筆須為合法 Email 格式否則 inline 顯示「「xxx」不是有效的 Email 格式」；留空即 fallback 至預設值（placeholder 與說明文字均顯示該預設）

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

- **FR-005**: 模板變數以 `str_replace` 全量替換（無 escape / 白名單機制）；event_type 建立後不可修改（update 僅驗證 name / subject / body_md）
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

## 設計決策

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

- **D12**: 高價課測試已可持久化 `type=high_ticket`（2026-08-01 起）— 原本 `2026_04_09_000001` 只在 MySQL 分支擴 enum，sqlite 測試 DB 停在三值、任何高價課都無法落庫；`2026_08_01_000001`（004 D10）改用 `Schema::change()` 帶完整值列表後兩邊對齊，`CourseTypeTest` 已實測通過。既有測試（LeadConvertTest、FunnelStopTest、BookingMailFailureTest）仍走 service 層＋記憶體指定 type，改寫成 HTTP 層非必要，日後新增測試可直接建課

## Schema

- **本次無 schema 變更**（新增的是 `email_templates` 的一筆資料，不是欄位；覆寫守門與 transaction 皆為既有欄位上的邏輯）
- 前次亦無 schema 變更（US1 landing page 隱藏與 mail_sent 回報皆為既有欄位與前端呈現）
- `high_ticket_leads` — 預約產生的潛在客戶；status 銷售漏斗 enum(pending 待聯繫 / contacted 已聯繫 / converted 已成交 / closed 已關閉) 預設 pending；notified_count（unsigned tinyint）與 last_notified_at 只由 NotifyHighTicketSlotJob 寄送成功後更新；booked_at 為最近一次提交時間（非 created_at 語意）；email / status / course_id 皆有索引。**DB 無 (email, course_id) unique 約束**，去重由 `recordLead()` 在應用層負責（D17）— 歷史資料可能已有重複列，加 unique 需先清理，現階段不值得
- `site_settings.high_ticket_lead_notify_cc` — 預約通知 CC 收件者，逗號分隔字串；不存在或為空即 fallback 至 `DEFAULT_NOTIFY_CC`（FR-014）
- `email_templates` — 系統信件模板；event_type 為程式對接鍵（index，非 unique，程式取 first）；subject 與 body_md 均支援 `{{var}}` 佔位符；body_md 為 Markdown，寄出時經 CommonMark 轉 HTML；由 EmailTemplateSeeder 以 event_type updateOrCreate 初始化 4 筆

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
- [ ] T010 [P] 新增 `LeadConvertedMail`：建構子讀 `EmailTemplate::forEvent('lead_converted')`，`str_replace` 五個變數 → CommonMark → 套 `emails/high-ticket-booking.blade.php`；**無模板時不 fallback**，以 `hasTemplate()` 供呼叫端判斷 in `app/Mail/LeadConvertedMail.php`
- [ ] T011 [P] seeder 加第 5 筆 `lead_converted`（name「顧問成交開通通知」）；文案必須包含「用此 email 到網站收驗證碼登入」 in `database/seeders/EmailTemplateSeeder.php`
- [ ] T012 [P] `$availableVariables` 加 `lead_converted` 五個變數條目 in `app/Http/Controllers/Admin/EmailTemplateController.php`
- [ ] T013 [P] `eventTypeLabels` 加 `lead_converted: '顧問成交開通通知'` in `resources/js/Pages/Admin/EmailTemplates/Index.vue`

Phase B — Service 層（相依 T010）
- [ ] T014 `convertLead()` 加 `bool $force = false`；寫入前查既有 Purchase，依 FR-015 白名單判定，擋下時回 `['success' => false, 'conflict' => [...]]`（controller 轉 409）in `app/Services/HighTicketLeadService.php`
- [ ] T015 三步寫入包 `DB::transaction()`；`checkAndConvert()` 與寄信移到 transaction 外各自 try/catch，回傳值加 `mail_sent` in `app/Services/HighTicketLeadService.php`

Phase C — Controller 與前端（相依 T014/T015）
- [ ] T016 `convert()` 驗證加 `force`（`sometimes|boolean`）並傳入；service 回 conflict 時以 409 + 中文訊息回應 in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [ ] T017 `index()` 比照 `dripByEmail` 多傳 `purchasesByEmail`（course_id / type / amount）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [ ] T018 開通 modal：依 `convertCourseId` 比對 `purchasesByEmail` 顯示既有購買警告 + 覆寫確認勾選（非 lead_conversion 才要求）、送出帶 `force`、409 訊息 inline 顯示；結果摘要依 `mail_sent` 分岔 in `resources/js/Pages/Admin/HighTicketLeads/Index.vue`

Phase D — 驗證
- [ ] T019 補測試：既有 `type=paid` 被擋（Purchase 原封不動）、帶 force 放行、refunded 直接放行、模板存在寄信且 `mail_sent=true`、缺模板與 Mailer 拋例外時開通仍成功且 `mail_sent=false`、Purchase 寫入失敗不留孤兒 user in `tests/Feature/HighTicket/LeadConvertTest.php`
- [ ] T020 `php artisan test` 全綠（既有 5 個 LeadConvertTest 必須維持通過，其中 repeat convert 走 lead_conversion 覆寫路徑不應被新守門擋下）＋ `npm run build` exit 0＋ `php artisan db:seed --class=EmailTemplateSeeder` 後後台可見第 5 個模板

### 預約流程三項修正：lead 去重 + 寫入順序 + CC 可設定（US2/US6 追加）

- [x] T021 `book()` 把 `HighTicketLead` 寫入移到寄信之前；新增 private `recordLead()` 實作 (email, course_id) 去重與 closed → pending 回復 in `app/Services/HighTicketBookingService.php`
- [x] T022 `NOTIFY_CC` 常數改名 `DEFAULT_NOTIFY_CC`（public，供 controller 與測試引用）＋ `NOTIFY_CC_SETTING_KEY` 常數；新增 private `notifyCc()` 讀 SiteSetting、public static `parseRecipients()` 解析清單 in `app/Services/HighTicketBookingService.php`
- [x] T023 `index()` 多傳 `notifyCc` / `notifyCcDefault`；新增 `updateNotifyCc()`（closure 驗證每筆 Email 格式，正規化成 `, ` 分隔後寫 SiteSetting）in `app/Http/Controllers/Admin/EmailTemplateController.php`
- [x] T024 加 `PUT /admin/email-templates/notify-cc` 路由，**必須宣告在 `/email-templates/{template}` 之前**（literal 不是 model key）in `routes/web.php`
- [x] T025 列表頁上方加 CC 設定卡（useForm + inline 錯誤 + 儲存中狀態）in `resources/js/Pages/Admin/EmailTemplates/Index.vue`
- [x] T026 新增測試：重複預約不重複建 lead、跨課程各自獨立、closed 回 pending 而 converted 不變、寄信拋例外仍留 lead、設定 CC 取代預設、留空 fallback 預設、後台儲存與格式驗證 in `tests/Feature/HighTicket/BookingLeadRecordTest.php`
- [x] T027 `php artisan test` 全綠（238 passed，既有 BookingMailFailureTest 對預設 CC 的斷言維持通過）＋ `npm run build` exit 0

## 進度日誌

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

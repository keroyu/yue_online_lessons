---
id: 011-high-ticket
status: done
owner_files:
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
touchpoints:
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 隱藏價格模式的銷售頁展示（價格區塊替換為預約須知、按鈕改「立即預約」）與右欄預約表單（axios POST + inline 成功提示）實作於此
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

### User Story 2 - 預約確認信與 Lead 記錄 (Priority: P1)

訪客成功提交預約後，系統寄出 DB 模板驅動的確認信，並建立一筆 Lead 記錄供後台追蹤；
不建立任何訂單或購買記錄。

**驗收**：
- [x] 確認信使用 `high_ticket_booking_confirmation` 模板：subject / body 以 str_replace 替換 `{{user_name}}` / `{{user_email}}` / `{{course_name}}`，body Markdown 經 CommonMark 轉 HTML，以 `emails/high-ticket-booking.blade.php` 版型寄出
- [x] 模板不存在時回傳 422「預約確認信模板不存在，請聯絡管理員」，不寄信也不建立 Lead
- [x] Email 寄送失敗僅記 error log，Lead 記錄照常建立（寄送與記錄解耦）
- [x] 成功提交建立 `high_ticket_leads` 記錄（status=pending、booked_at=now、notified_count=0）
- [x] 同一 email 重複預約同一課程允許建立新記錄（保留完整預約歷史，不做 upsert）
- [x] 非 high_ticket 或未開啟隱藏價格的課程呼叫預約 API 時回 422「此課程不接受預約」

### User Story 3 - 後台 Leads 名單管理 (Priority: P2)

管理員在 `/admin/high-ticket-leads` 檢視所有預約者，依狀態 / 課程 / 關鍵字篩選，
追蹤每位潛在客戶的銷售漏斗階段與序列信紀錄。

**驗收**：
- [x] 列表顯示姓名、Email、課程、狀態、通知次數、序列信紀錄、預約時間；依 booked_at 降冪、每頁 20 筆分頁
- [x] 狀態篩選（待聯繫 / 已聯繫 / 已成交 / 已關閉）、課程下拉篩選（僅列 `type=high_ticket` 課程）、姓名或 Email 關鍵字搜尋（LIKE 模糊比對、300ms debounce）三者可組合，分頁保留查詢參數
- [x] 可直接更新單筆 lead 狀態（`PATCH /admin/high-ticket-leads/{lead}/status`），列表即時反映
- [x] 「序列信紀錄」欄以 email 關聯 `users` → `drip_subscriptions` 顯示曾加入的 drip 課程與訂閱狀態；無紀錄顯示 `—`（不需額外欄位）
- [x] 狀態篩選按鈕 active / 非 active 均為 cursor-pointer，active 提供 hover 深化效果

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
- [x] 確認開通後：`firstOrCreate` user（password 隨機 16 碼）→ `Purchase::updateOrCreate`（type=`lead_conversion`、status=paid、amount=0）→ lead status 改 `converted`；列表 inline 更新該列並於頁頂顯示結果摘要

### User Story 6 - Email 模板系統後台管理 (Priority: P2)

管理員在 `/admin/email-templates` 統一編輯所有可模板化的系統信件，
不依賴工程師即可修改主旨與內容。

**驗收**：
- [x] 列表顯示 4 個模板，event_type 以中文標籤呈現：客製服務預約確認、課程贈禮通知、課程新增小節通知、客製服務新時段通知
- [x] 編輯頁顯示模板名稱、觸發事件（唯讀）、主旨、body_md 內容編輯區；「插入變數」按鈕列依 event_type 顯示可用變數，點擊插入 textarea 游標位置
- [x] 編輯 / 預覽模式切換：預覽以 `marked` + `breaks: true` 渲染，單行換行與寄出效果一致
- [x] 儲存驗證 name ≤ 100、subject ≤ 255、body_md 必填（中文錯誤訊息），成功後導回列表並 flash「模板已更新」
- [x] `EmailTemplateSeeder` 以 event_type 為 key `updateOrCreate` seed 4 個預設模板，可重複執行不覆蓋主鍵
- [x] `course_gifted` / `lesson_added` 事件由對應 Mailable 建構時讀取模板，模板不存在時 fallback 至寫死內容；high_ticket 兩事件無 fallback（缺模板直接擋下操作）

## Requirements

- **FR-001**: 預約 API 只接受 `is_high_ticket && high_ticket_hide_price` 的課程，否則 422；路由掛 `throttle:5,1` 防濫用
- **FR-002**: 預約流程 MUST NOT 建立訂單 / 購買記錄；唯一產出為確認信 + 一筆 Lead
- **FR-003**: 確認信模板缺失時整個預約失敗（422），Lead 不建立；Email「寄送失敗」（模板存在但寄不出去）則 Lead 照常建立 — 兩種失敗語意不同
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
- **FR-008**: 開通使用 `Purchase::updateOrCreate([user_id, course_id])`，同人同課重複開通不會產生第二筆購買記錄；購買類型固定 `lead_conversion`（「顧問轉換」，後台與會員頁以 teal 樣式與贈送 / 購買區分）
- **FR-009**: 兩個 Job 均為 `tries=3`、`backoff=[60, 300, 900]`；lead 或 template 已被刪除時記 warning 後靜默結束，不 retry
- **FR-010**: `email_templates` 每個 event_type 僅應存在一筆（seeder updateOrCreate 保證；DB 無 unique 約束，程式一律取 `forEvent()->first()`）

## 設計決策

- **D1**: 預約表單採非同步 axios + inline 成功提示，不走 Inertia 表單跳轉 — 訪客停留在銷售頁，避免打斷高價品的說服動線
- **D2**: Email 模板存 DB（Markdown body + `{{var}}` 佔位符）而非 Blade 檔 — 業主可自行改文案；渲染沿用 BatchEmailMail 的 CommonMark 模式，前後台預覽一致
- **D3**: 預約後續流程（面談排程、外部平台對接）完全由信件內容引導，系統不介入 — 目前規模下人工銷售比自動化排程划算
- **D4**: 批次通知 / 轉序列信採 Job per lead — 操作立即回應 dispatched 數，單封寄送失敗獨立重試不影響其他人
- **D5**: 轉序列信成功後 lead 自動 `closed` — 人工銷售線結束、交給自動化；不另設 nurturing 狀態，保持狀態機簡單
- **D6**: 開通購買類型用獨立的 `lead_conversion`（原為 gift，2026-05-09 改）— 報表上「顧問轉換」與「贈送」語意不同，需可區分
- **D7**: Lead 允許同 email 重複記錄、course_id 無外鍵約束 — 保留完整預約歷史，且課程軟刪除不受牽連

## Schema

- `high_ticket_leads` — 預約產生的潛在客戶；status 銷售漏斗 enum(pending 待聯繫 / contacted 已聯繫 / converted 已成交 / closed 已關閉) 預設 pending；notified_count（unsigned tinyint）與 last_notified_at 只由 NotifyHighTicketSlotJob 寄送成功後更新；booked_at 為提交時間（非 created_at 語意）；email / status / course_id 皆有索引；允許同 email + course 多筆
- `email_templates` — 系統信件模板；event_type 為程式對接鍵（index，非 unique，程式取 first）；subject 與 body_md 均支援 `{{var}}` 佔位符；body_md 為 Markdown，寄出時經 CommonMark 轉 HTML；由 EmailTemplateSeeder 以 event_type updateOrCreate 初始化 4 筆

## 進度日誌

- 2026-07-06: 領域重組 — 自 008-high-ticket-booking 重寫，依實際 codebase 校正

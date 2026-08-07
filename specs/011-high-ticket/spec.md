---
id: 011-high-ticket
status: building
owner_files:
  - app/Http/Controllers/HighTicketBookingController.php
  - app/Http/Controllers/BookingConfirmController.php
  - app/Exceptions/SlotUnavailableException.php
  - app/Services/ZoomMeetingService.php
  - app/Services/CalendarInviteService.php
  - app/Http/Requests/Admin/RescheduleBookingRequest.php
  - database/migrations/2026_08_06_000001_add_calendar_fields_to_high_ticket_leads_table.php
  - database/migrations/2026_08_06_000002_add_cancelled_to_high_ticket_leads_status.php
  - database/migrations/2026_08_06_000003_insert_booking_change_email_templates.php
  - tests/Feature/HighTicket/CalendarInviteTest.php
  - tests/Feature/HighTicket/BookingChangeTest.php
  - app/Http/Controllers/Admin/ConsultationSlotController.php
  - app/Http/Requests/HighTicketBookingRequest.php
  - app/Http/Requests/Admin/StoreConsultationSlotsRequest.php
  - app/Models/ConsultationSlot.php
  - app/Services/ConsultationSlotService.php
  - app/Console/Commands/ReleaseExpiredBookingHolds.php
  - resources/js/Components/Course/HighTicketBookingWizard.vue
  - resources/js/composables/useDelayedConfirm.js
  - resources/js/Components/EmailReviewNotice.vue
  - resources/js/Pages/Booking/Confirm.vue
  - resources/js/Pages/Admin/ConsultationSlots/Index.vue
  - resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue
  - app/Http/Requests/Admin/DestroyConsultationSlotsRequest.php
  - app/Http/Requests/Admin/UpdateConsultationSettingsRequest.php
  - tests/Feature/HighTicket/ConsultationSlotAdminTest.php
  - database/migrations/2026_08_05_000001_add_application_fields_to_high_ticket_leads_table.php
  - database/migrations/2026_08_05_000002_create_consultation_slots_table.php
  - database/migrations/2026_08_05_000003_widen_phone_columns_to_30.php
  - database/migrations/2026_08_05_000004_add_resume_token_to_high_ticket_leads_table.php
  - database/migrations/2026_08_05_000005_add_booking_url_to_slot_available_template.php
  - tests/Feature/HighTicket/BookingWizardTest.php
  - tests/Feature/HighTicket/SlotHoldTest.php
  - tests/Feature/HighTicket/ZoomMeetingTest.php
  - tests/Support/BooksHighTicket.php
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
  - app/Mail/BookingVerifyMail.php
  - resources/views/emails/booking-verify.blade.php
  - resources/views/emails/booking-verify-text.blade.php
  - database/migrations/2026_08_06_000004_drop_booking_verify_email_template.php
  - tests/Feature/HighTicket/SupportEmailTest.php
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
  - tests/Feature/HighTicket/LeadSubscribeDripTest.php
  - database/migrations/2026_08_07_000001_add_consultant_to_consultation_slots_table.php
  - database/migrations/2026_08_07_000002_add_consultant_to_high_ticket_leads_table.php
  - tests/Feature/HighTicket/ConsultantAssignmentTest.php
  - app/Support/PhoneNumber.php
  - database/migrations/2026_08_08_000001_index_and_normalise_phones.php
  - tests/Feature/HighTicket/DuplicateBookingTest.php
  - database/migrations/2026_08_08_000002_realign_lead_statuses_to_consultation_vocabulary.php
  - tests/Feature/HighTicket/LeadStatusRealignTest.php
  - database/migrations/2026_08_08_000003_install_missing_email_templates.php
  - tests/Feature/HighTicket/EmailTemplateInstallTest.php
  - tests/Feature/HighTicket/BookingMailFailureTest.php
  - tests/Feature/HighTicket/BookingLeadRecordTest.php
  - tests/Feature/HighTicket/EmailTemplateHtmlModeTest.php
  - tests/Feature/HighTicket/LeadsTabsTest.php
  - tests/Feature/HighTicket/ExpiredApplicationPurgeTest.php
touchpoints:
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 隱藏價格模式的銷售頁展示（價格區塊替換為預約須知、按鈕改「立即預約」）與右欄預約表單（axios POST + inline 成功提示）實作於此；`isFunnelLanding` 的 landing page 隱藏規則（hero 時長行、第 3 區整塊、免費試閱）與預約成功文案依 mail_sent 分岔亦在此
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: show() 傳遞 is_high_ticket / high_ticket_hide_price props 給銷售頁；US9 起另傳 `bookingDraft`（已登入且為該 lead 本人時的既有問卷答覆），FR-042 起另接受 `?resume=` token 免登入回傳完整 draft
  - file: resources/js/Layouts/AdminLayout.vue
    owner: 000-platform-core
    why: US10 側欄新增「諮詢時段」項目（staff 可見，位置在 Leads 名單與折扣碼之間）
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
    why: 預約 API（`POST /course/{course}/book`，throttle:5,1）、Leads 後台與 Email 模板路由（含 `PUT /admin/email-templates/notify-cc`，須宣告在 `{template}` 之前）；US8 移除 admin 群組內的 `GET /admin/courses/{course}/subscribers`；US10/US11 新增 `GET /course/{course}/booking-slots`（throttle:30,1）、`GET /booking/confirm/{token}`（公開、無 auth）與 staff 群組內的 `/admin/consultation-slots` 三條；US14 新增 staff 群組內的 `PUT /admin/high-ticket-leads/{lead}/booking`（改期）與 `DELETE /admin/high-ticket-leads/{lead}/booking`（取消）；FR-057 新增 `PUT /admin/email-templates/support-email`（須宣告在 `{template}` 之前）
  - file: app/Http/Middleware/HandleInertiaRequests.php
    owner: 000-platform-core
    why: FR-057 新增 shared prop `supportEmail`（`SiteSetting::supportEmail()`）—— 法律條款 modal 掛在 footer，每一頁都可能要印客服信箱，沒有單一 controller 可傳
  - file: app/Http/Controllers/Payment/NewebpayController.php
    owner: 005-checkout
    why: FR-057 付款失敗訊息裡硬寫的客服信箱改讀 `SiteSetting::supportEmail()`（3 處），訊息其餘文字不變
  - file: app/Http/Controllers/Payment/PayuniController.php
    owner: 005-checkout
    why: 同上，1 處
  - file: resources/js/Pages/Payment/Success.vue
    owner: 005-checkout
    why: FR-057 客服 mailto 連結改讀 shared prop `supportEmail`
  - file: resources/js/Components/Legal/PrivacyContent.vue
    owner: 002-storefront
    why: FR-057「客服信箱」欄改讀 shared prop `supportEmail`
  - file: resources/js/Components/Legal/PurchaseContent.vue
    owner: 002-storefront
    why: 同上
  - file: routes/console.php
    owner: 000-platform-core
    why: US11 的 `booking:release-holds` 每 10 分鐘排程（逾時暫留的清理，非正確性來源，見 D33）
  - file: app/Models/SiteSetting.php
    owner: 000-platform-core
    why: 預約通知 CC 清單存於 `site_settings.high_ticket_lead_notify_cc`（FR-014），US10/US12 另加 `high_ticket_booking_bonus_codes` 與三組 `zoom_*` 憑證，沿用 000 的全站設定機制，未新增欄位；2026-08-05 起另在此類別上新增 `SUPPORT_EMAIL_KEY` 常數與 `supportEmail()` helper（FR-057）—— 客服信箱是跨模組都要印的值，放在 SiteSetting 比放在任一功能 service 合理
  - file: app/Http/Controllers/Admin/SettingsController.php
    owner: 000-platform-core
    why: US12 在「API 設定」頁（`showPayment` / `updatePayment`）加一組 Zoom 憑證欄位，沿用該頁既有的 `maskSecret()` 與「留白即維持原值」的 secret 處理（D41），不新增頁面與路由
  - file: resources/js/Pages/Admin/Settings/Payment.vue
    owner: 000-platform-core
    why: US12 新增「Zoom 會議」設定卡（account_id / client_id 明文、client_secret 遮罩），版面比照同頁既有的金流與 Meta CAPI 卡片
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
- [x] 狀態篩選（待面談 / 已面談 / 未出席 / 已成交 / 已關閉 / 已取消，顯示名稱見 FR-055）、課程下拉篩選（僅列 `type=high_ticket` 課程）、姓名或 Email 關鍵字搜尋（LIKE 模糊比對、300ms debounce）三者可組合，分頁保留查詢參數
- [x] 可直接更新單筆 lead 狀態（`PATCH /admin/high-ticket-leads/{lead}/status`），列表即時反映；狀態欄為五顆色塊按鈕（P 待面談黃 / C 已面談藍 / N 未出席橘 / D 已成交綠 / X 已關閉灰）＋ 已取消（V 玫瑰）唯讀徽章，一鍵切換免展開下拉，目前狀態以實心底色 + ring 標示、其餘為淺色底，欄頭附 `P待談 / C已談 / N未出席 / D成 / X關` 圖例，點擊當前狀態不發請求
- [x] `no_response`（顯示為**未出席**，US14 後語意改為 no-show，見 FR-055）排在 `contacted` 之後：可被批次「加入序列信」（同 pending / closed），且對方重新預約同課程時狀態自動回 `pending`（重新預約本身就是回應）
- [x] 「序列信紀錄」欄以 email 關聯 `users` → `drip_subscriptions` 顯示曾加入的 drip 課程與訂閱狀態；無紀錄顯示 `—`（不需額外欄位）
- [x] 狀態篩選按鈕 active / 非 active 均為 cursor-pointer，active 提供 hover 深化效果；四個狀態 tab 與列內色塊按鈕共用同一組配色（黃/藍/綠/灰），active 為實心、非 active 為同色系淺底，「全部」維持 brand-teal 中性色 — 全頁同一顏色恆等於同一狀態
- [x] 每顆狀態 tab 標籤後顯示該狀態的漏斗佔比（`待面談 70%`），「全部」則顯示總筆數（`全部 40 筆`）；分母為套用搜尋 / 課程篩選但不含狀態篩選的全體 leads —— 點進某個狀態後百分比不變（FR-067）；hover 顯示實際筆數
- [x] 批次動作列有「複製 Email」按鈕：把已勾選 leads 的 email 以 `, ` 串接寫入剪貼簿（去重，同人重複預約只出現一次），可直接貼進郵件收件人欄；未勾選時停用，複製成功後 2 秒顯示綠勾與「已複製 N 個 Email」，複製不會清空勾選

### User Story 4 - 通知新時段與批次郵件 (Priority: P2)

新面談時段釋出時，管理員批次通知已勾選的 leads；也可對任意勾選的 leads
發送一次性客製郵件。

**驗收**：
- [x] 勾選任意狀態的 leads 點「通知新時段」先開確認 modal：顯示 `high_ticket_slot_available` 模板主旨、body Markdown 渲染預覽、收件人列表、前往編輯模板的連結
- [x] 模板不存在時 modal 顯示警告並停用「確認發送」；後端亦回 422 引導先建立模板
- [x] 確認後 per-lead 派送 `NotifyHighTicketSlotJob`（不依狀態過濾 — 新時段對已面談 / 未出席 / 已關閉的 lead 同樣值得一提，由勾選的管理員判斷），立即回應 dispatched 數
- [x] Job 成功寄出後該 lead `notified_count` +1、`last_notified_at` 更新為當下；寄送失敗 throw 觸發重試（3 次，backoff 60/300/900 秒）
- [x] 「發送郵件」可勾選任意狀態 leads：modal 填主旨（上限 200 字）與內容（上限 10000 字，含字元計數），以 `BatchEmailMail` 逐一同步寄出（以 lead.email 為收件地址，不依賴 User 帳號）；單筆失敗僅記 log 不中斷，回應「已發送 N 封郵件」

### User Story 5 - Lead 轉序列信與開通商品 (Priority: P2)

冷掉或未成交的 leads 交給 drip 自動化培養；面談成交者由管理員直接開通商品。

**驗收**：
- [x] 勾選任意狀態的 leads 點「加入序列信」（2026-08-05 起不依狀態過濾，FR-007），下拉選單列出所有 `course_type=drip` 課程供選擇；選取中含「已成交」時 modal 顯示提示但不擋下
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
- [x] 列表顯示 6 個模板，event_type 以中文標籤呈現：客製服務預約待確認、客製服務預約確認、課程贈禮通知、課程新增小節通知、客製服務新時段通知、顧問成交開通通知（US11 起新增第一項）
- [x] 編輯頁顯示模板名稱、觸發事件（唯讀）、主旨、body_md 內容編輯區；「插入變數」按鈕列依 event_type 顯示可用變數，點擊插入 textarea 游標位置
- [x] 編輯 / 預覽模式切換：預覽以 `marked` + `breaks: true` 渲染，單行換行與寄出效果一致（**此條在 FR-021 之前為假** — 預覽會換行、寄出不會，管理員得按兩次 Enter 才有效果）
- [x] 儲存驗證 name ≤ 100、subject ≤ 255、body_md 必填（中文錯誤訊息），成功後導回列表並 flash「模板已更新」
- [x] `EmailTemplateSeeder` 以 event_type 為 key `updateOrCreate` seed 6 個預設模板，可重複執行不覆蓋主鍵
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

### User Story 9 - 預約申請多步驟表單 (Priority: P1)

現在的預約門檻是「填姓名 + Email 按送出」，30 秒就能完成，導致名單裡塞滿隨手留資料、
面談當天不出現、或根本沒有經營意圖的人 —— 顧問的時間被稀釋在篩選上，而不是成交上。
把預約改成**四個步驟的申請流程**，用問卷與承諾清單把「隨手看看」的人自然擋在門外：
願意花五分鐘寫完瓶頸與專長、逐條勾選承諾的人，才是值得排進行事曆的人。

**驗收**：
- [x] Step 1：填 Email + 暱稱後按「開始申請」，**同頁下方即時展開**簡易問卷（不換頁、不打 API、不捲走）；問卷欄位為手機電話\*、職業和從事時長\*、事業瓶頸\*、知識或能力的專長\*、經營社群網址（選填），`*` 欄位以紅色星號標示且未填不得前進
- [x] Step 2：問卷按「下一步」後顯示承諾條件清單（三條，文案見 FR-026），三個 checkbox **全部勾選**才啟用「下一步」；未全勾時按鈕為 disabled 樣式並附說明「請確認全部項目後繼續」
- [x] Step 4：選完時段後顯示**申請資料覆核區**，逐欄列出 Step 1–3 的所有輸入值（含所選時段與諮詢長度），每欄可點「修改」跳回該步驟且保留已填內容
- [x] 覆核區下方 MUST 顯示不出席警語：「若確定預約卻無故不出席，我們將永久黑名單。」以警示樣式（amber/red 底）呈現，不可摺疊、不可略過
- [x] 「送出申請」按鈕正上方 MUST 顯示一行說明小字：「1v1 諮詢將依名額安排，由創辦人或團隊專業顧問提供服務。」灰階小字（`text-xs text-gray-500`），不影響送出邏輯，不出現在第 3 步候補送出（「送出申請並等候通知」）
- [x] 「送出申請」為單一 `POST /course/{course}/book`（axios，沿用 D1 非同步）；送出後 inline 顯示待確認提示（US11），全程不換頁
- [x] 四個步驟共用一組進度指示（1 資料 → 2 承諾 → 3 時段 → 4 確認），已完成步驟可點回、未達步驟不可點；所有可點元素 `cursor-pointer` + hover 樣式（專案規則）
- [x] 整段流程抽成獨立元件 `Components/Course/HighTicketBookingWizard.vue`，`Course/Show.vue` 只保留一行掛載（見 D29）；既有的一步式表單整組移除，不留開關（使用者決策）
- [x] 已登入者自動帶入 real_name / email（行為與現況相同）；重新申請時若該 email 已有 lead，問卷欄位預填既有值省得重打
- [x] 後端驗證由 `HighTicketBookingRequest` 承擔（非 controller inline）：必填、長度上限、`social_url` 須為合法 URL、`commitments` 須為三條全 true，任一不符回 422 並 inline 顯示於對應欄位
- [x] 後台 Leads 名單的每列可展開檢視該筆申請的問卷答覆（手機、職業、瓶頸、專長、社群連結、預約時段、Email 確認時間、Zoom 連結）；舊資料無問卷欄位時顯示說明而非一排「—」
- [x] 展開列不再顯示「預約優惠碼」（原值仍存在 `booking_code`，只是後台不需要看它）；改顯示該筆已預約的時段，格式如「2026/8/8 14:00-15:45」，起訖時間由該 lead 名下 `consultation_slots`（依 `starts_at` 排序）取首尾單位換算 —— 起始單位的 `starts_at`，結束時間為末位單位 `starts_at + 15 分鐘`；lead 尚未選定時段（候補中）則不顯示此列
- [x] 展開列「Email 確認時間」之後新增「序列信起始時間」，格式如「2026/8/3 17:03（經過 3 天）」，用於追蹤該 email 被序列信加溫多久：取該 email 名下**所有**序列信訂閱（`dripByEmail`，不限課程、不限狀態）中 `subscribed_at` **最早**的一筆；天數為**日曆天差**（比較年月日，不比時分，避免數字隨查看時刻跳動）；該 email 完全沒有任何序列信訂閱時不顯示此列。**天數的比較終點**：該筆預約已確認（有 `confirmed_at`）時，比較終點固定為 `confirmed_at`——「序列信起始 → 確認預約」是已發生的歷史事實，天數算出來後不應再變動；尚未確認時終點才是「今天」，隨查看當下累加（2026-08-07 修正，原本一律比較到今天，導致已轉換的 lead 天數還在持續增加，見下方進度日誌）
- [x] 問卷填的手機號碼在 lead 轉為會員帳號時一併寫入 `users.phone`：兩個轉換點（`HighTicketLeadService::convertLead()` 開通、`SubscribeDripLeadJob` 加序列信）皆以 `firstOrCreate` 的建立屬性帶入 —— **既有會員維持原值不覆寫**（見 D43）
- [x] RWD：手機上每個步驟單欄排列、承諾清單與覆核區不橫向溢出

### User Story 10 - 顧問時段管理與預約選位 (Priority: P1)

預約流程走到最後只丟一封信說「我們會跟你聯絡」，實際排程仍要來回喬時間。
讓管理員在後台先把自己的空閒時段放上來（15 分鐘為 1 單位），申請人在流程第 3 步直接選定，
預設一場 30 分鐘；持有預約專屬優惠碼者可延長為 45 分鐘。

**驗收**：
- [x] 後台 `/admin/consultation-slots`（staff 群組，顧問可用）：依日期分組列出所有時段單位，每格顯示時間、狀態（可預約 / 暫留中 / 已預約）與佔用者姓名 Email（連往 Leads 名單）
- [x] 「新增時段」表單：選日期 + 起訖時間 → 一次產生該區間所有 15 分鐘單位；已存在的單位跳過不重複建立，回報「已新增 N 個、略過 M 個」
- [x] 刪除只允許**未被佔用**的單位（`lead_id` 為 null 或暫留已逾時）；已確認佔用的單位刪除回 422，要求先在 Leads 處理該筆預約
- [x] 前台 Step 3 呼叫 `GET /course/{course}/booking-slots?code=`，只回傳「**該起始單位起連續 N 個單位皆可用**」的起始時間（N = 諮詢長度 ÷ 15）；跨日與跨不連續區間的組合 MUST NOT 出現
- [x] 預設諮詢長度 30 分鐘（2 單位）。時段區塊上方有「預約優惠碼（選填）」輸入框，填入命中 `site_settings.high_ticket_booking_bonus_codes` 的碼後即時重查時段，長度變 45 分鐘（3 單位）並顯示「已套用，諮詢延長為 45 分鐘」
- [x] 優惠碼比對忽略大小寫與前後空白；無效碼**不擋流程**，顯示「此優惠碼無效，將以 30 分鐘進行」後照常可選時段（見 D31）
- [x] 45 分鐘（優惠碼延長）時段的可選起始時間 MUST 只落在整點或半點（`:00`/`:30`，以台北時間判定），`:15`/`:45` 不得出現於清單；30 分鐘預設時段不受此限制，起始時間維持每 15 分鐘一個選項（FR-069）
- [x] 時段以「日期分組 + 時間按鈕」呈現，只列出今天之後、尚有可用組合的日期；完全沒有可預約時段時顯示空狀態「目前沒有開放的時段」並提供「通知我有新時段」的既有預約行為（仍建 lead、狀態 pending，等管理員用 US4 通知）
- [x] 候補的 lead 產生永久 `resume_token`；US4 的「新時段通知」信帶 `{{booking_url}}` 深連結，點入即以**已填資料**開在第 3 步（選時段），承諾自動視為已接受，不必重填問卷（見 FR-042 / D44）
- [x] 送出申請時後端 MUST 重新驗證所選時段仍可用（前端清單可能已過期）；被搶走回 409「該時段剛被預約，請重新選擇」，前端自動重查時段清單
- [x] 顯示時間一律以**台北時間**呈現與比對；伺服器為 UTC，轉換規則見 D32
- [x] 後台時段頁與前台時段選擇皆為 RWD；所有時段按鈕 `cursor-pointer` + 選中狀態明確（實心底色 + ring）

### User Story 11 - Email 二次確認與時段暫留 (Priority: P1)

送出申請不等於預約成立 —— 沒驗證過的 Email 佔著顧問的行事曆，等於把爽約成本前置到系統裡。
送出後時段先**暫留 1 小時**並寄出待確認信，申請人點信中連結回站完成確認，時段才正式保留；
逾時未確認自動釋出，讓下一個人選得到。

**驗收**：
- [x] 送出申請成功後：lead 落地（含問卷欄位與 `commitments_accepted_at`）、產生 `confirm_token`、`confirm_expires_at = now + 1 小時`，所選的 N 個時段單位寫入 `lead_id` 與 `held_until`（= `confirm_expires_at`），三者在**同一個 `DB::transaction()`** 內完成
- [x] 同步寄出 `high_ticket_booking_verify`（新模板「客製服務預約待確認」）；此信含確認連結、所選時段、到期時間
- [x] 前台送出成功後顯示：「預約時段已暫時保留。請於 **1 小時內**收取 Email 並點擊確認連結完成預約。」附倒數與所選時段；MUST NOT 宣稱預約已完成
- [x] `high_ticket_booking_verify` 模板不存在時整個申請 422 擋下、不建 lead、不佔時段（比照 FR-003 對預約確認信的既有取捨）
- [x] 待確認信寄送失敗（模板存在但寄不出去）：lead **保留**、時段**立即釋出**、回應 `mail_sent: false`，前台顯示「申請已收到，但確認信寄送失敗，我們會主動與你聯絡安排時段」（見 D34）
- [x] `GET /booking/confirm/{token}` 為公開路由（無需登入）；確認成功後：`confirmed_at = now`、該 lead 的時段單位 `held_until` 清為 null（正式佔用），並顯示提醒頁「確認已完成預約，相關資料已寄出，建議在諮詢時間以前看完」
- [x] **確認成功才寄出**既有的 `high_ticket_booking_confirmation`「客製服務預約確認」信（送出當下不寄）；同時才觸發 drip `checkAndBook()` 與 Meta CAPI `Lead` 事件（見 D35）
- [x] token 已確認過再點一次為**冪等**：不重複寄信、不報錯，顯示同一張「已完成確認」頁
- [x] token 逾時（`confirm_expires_at < now`）且該筆申請尚未被清掃時：顯示「確認連結已逾時，保留的時段已釋出」+ 回課程頁重新預約的連結。**清掃後該 lead 已不存在**（FR-068），同一個連結改落到 `invalid` 分支，文案因此改為「連結已失效 —— 可能已逾時或網址不完整」並同樣導回重新申請
- [x] token 不存在或格式不符：顯示「連結無效」頁，MUST NOT 洩漏任何 lead 資訊
- [x] 逾時的暫留單位對**其他人的查詢**立即視同可用（lazy 判定，不等排程；見 D33）；`booking:release-holds` 排程每 10 分鐘把逾時單位清乾淨 —— 就時段而言僅為資料整齊，但**刪除逾時申請只發生在這裡**（FR-068），排程沒跑名單就會一直錯
- [x] 同一 lead 重新送出申請時，先釋放它先前持有的所有單位再佔新的，不會一人卡住兩組時段
- [x] 逾時未確認的申請 MUST 從 Leads 名單刪除（FR-068）：時段先釋放再刪 lead，不留孤兒；`confirmed_at` 非空或 `status` 已被管理員改動者一律保留
- [x] 測試：並發搶同一時段只有一人成功、逾時後單位可被他人選走、確認冪等、確認前不寄確認信 / 不送 CAPI、逾時申請被清掃且已確認 / 已跟進 / 候補者不受影響

### User Story 12 - 確認後自動建立 Zoom 會議 (Priority: P2)

時段確定保留之後，會議連結仍要人工開、人工貼進信裡 —— 這是整條流程最後一段還沒自動化的環節，
而且是最容易漏掉的一段（漏了就等於對方到了時間沒地方去）。
確認完成後直接呼叫 Zoom API 建立該時段的會議，把 `join_url` 寫進「客製服務預約確認」信一起寄出。

**驗收**：
- [x] Zoom 憑證走 **Server-to-Server OAuth**，三個值（`account_id` / `client_id` / `client_secret`）由管理員在後台「API 設定」頁（`/admin/settings/payment`）填寫，存 `site_settings`，沿用該頁既有的遮罩式密鑰處理：`client_secret` 為 secret 欄位（顯示 `maskSecret()` 預覽、留白即維持原值、填了才覆寫），`account_id` / `client_id` 為一般欄位可直接顯示（見 D41）
- [x] `ZoomMeetingService::createMeeting()`：以 `account_credentials` 換 access token（快取 55 分鐘，Zoom token 效期 1 小時）→ `POST /v2/users/me/meetings` 帶 `start_time`（該時段 UTC）、`duration`（30 或 45）、`timezone: Asia/Taipei`、`topic`（`{申請人姓名} 諮詢`，2026-08-07 簡化，原為「課程名 + 1v1 諮詢 + 申請人姓名」，過長）；回傳 `meeting_id` 與 `join_url`
- [x] 確認流程改為：`confirmed_at` 落地 → **同步建好會議、寫回 lead、才寄出**「客製服務預約確認」信（原為派送 `CreateZoomMeetingJob`，2026-08-05 改為同步，見 D55）
- [x] 確認頁文案不變（「確認已完成預約，相關資料已寄出」），信件抵達有數秒延遲屬正常
- [x] `high_ticket_booking_confirmation` 模板新增變數 `{{zoom_join_url}}`；模板可自由決定要不要放
- [x] Job `tries=3`、`backoff=[30, 120, 300]`；三次皆失敗時 MUST 仍寄出確認信，`{{zoom_join_url}}` 替換為「（會議連結將另行寄出）」，同時 log error 並 CC 通知 `high_ticket_lead_notify_cc` 收件者 —— 對方一定要收到「預約成立」，只是連結晚到（見 D39）
- [x] **未設定 Zoom 憑證時整條路徑跳過**：不派 Job、確認當下直接寄信、`{{zoom_join_url}}` 替換為空字串，行為完全等同 US11。本機與測試環境不需要任何 Zoom 設定即可跑完整流程（見 D40）
- [x] 45 分鐘場（優惠碼延長）可正常建立 —— Zoom 帳號為付費方案，無免費方案的 40 分鐘上限；此為**營運前提**，程式不檢查方案、不因長度分支
- [x] `zoom_meeting_id` 與 `zoom_join_url` 落庫於 lead；後台 Leads 名單展開該筆時顯示會議連結（可點開）
- [x] 測試：憑證未設定時走同步寄信路徑、Job 成功時信中含連結、Job 三次失敗後仍寄出且帶 fallback 文案（皆以 fake HTTP client，不打真實 API）

### User Story 13 - 諮詢時段週曆化操作 (Priority: P2)

現行 `/admin/consultation-slots` 要開時段得填「日期 + 開始時間 + 結束時間」三個欄位再送出，
一週開五天就是填五次表；開完之後看到的是依日期分組的方塊清單，看不出哪一天整天是空的、
哪幾段連得起來、下週還有沒有排。時段管理本質上是**空間問題**，用表單解等於把行事曆退化成打字。

改為一張週曆格線（1 格 = 15 分鐘），直接在格線上拖曳釋出或收回時段；四種狀態以顏色區分，
已成立的預約在格子上直接顯示預約人與 Zoom 連結，不必再跳去 Leads 名單對照。

**驗收**：
- [x] 週曆檢視：7 天（週一起）× 每天 08:00–22:00，`?week=YYYY-MM-DD` 決定顯示哪一週（預設本週）；提供上一週 / 下一週 / 回到本週三個切換，切換走 Inertia partial reload 不整頁重載
- [x] 顯示範圍固定 08:00–22:00，但該週若有落在範圍外的既有時段，格線 MUST **自動往外撐開**至涵蓋它 —— 顯示範圍是預設值，不是資料的過濾器（見 D47）
- [x] 在空白格按下並拖曳到另一格放開 → 建立該區間所有 15 分鐘單位，一次請求；已存在的單位略過，flash「已釋出 N 個、略過 M 個」
- [x] 在**已釋出且未被佔用**的格上起手拖曳 → 收回該區間的單位（起點決定意圖，見 D45）；區間內已被佔用的單位一律跳過，flash 回報略過數
- [x] 已被佔用（暫留中 / 已預約）的格 MUST NOT 作為拖曳起點；拖曳經過時不受影響、不被覆寫
- [x] 今天之前的格不可新增（後端 `date` 不得早於今天的既有規則保留），前端以灰底 + `cursor-not-allowed` 呈現並擋下拖曳起手
- [x] 四種狀態顏色可區分且頁面上有圖例：未釋出（白）、可預約（teal）、暫留中（amber）、已預約（indigo）
- [x] 同一筆預約的連續單位在畫面上 MUST **合併為一個區塊**（30 分鐘 = 1 塊而非 2 格），顯示起訖時間、預約人暱稱、狀態；暱稱連往 `/admin/high-ticket-leads?search={email}`
- [x] 已確認的預約若已建立 Zoom 會議，區塊內顯示「Zoom」連結（`target="_blank" rel="noopener"`）；未建立時該連結不出現（不顯示空連結或「—」）
- [x] 暫留中的區塊額外顯示保留到期時間（`held_until`），讓管理員看得出這格隨時可能被釋出
- [x] 手機（`< sm`）改為**單日檢視**：`< 日期 >` 切換 + 單欄格線，拖曳（觸控）行為與桌機一致
- [x] 原有的「日期 + 起訖時間」表單與依日期分組的方塊清單移除；所有可點元素 `cursor-pointer` + hover 回饋
- [x] 圖例旁顯示**全站累計**（不分週）的三個真實狀態數量：可預約、暫留中、已預約；「未釋出」不接數字（見 D65）
- [x] 測試：週資料組裝（跨週邊界、台北時區）、範圍外既有時段會撐開格線、批次收回只刪未佔用者、佔用中的單位收不掉、同 lead 連續單位合併為一個區塊

### User Story 14 - 行事曆邀請與預約異動 (Priority: P1)

確認信目前只給一段時間文字加一條 Zoom 連結，對方得自己把時間抄進行事曆 —— 這是 1v1 諮詢
最大的 no-show 來源：人不是故意不出現，是那個時間根本沒進他的行程表。

同時，系統至今**沒有任何取消或改期的路徑**（FR-040 把它列為明確的已知限制）。對方臨時有事只能寫信，
你只能到 Zoom 後台手動刪會議，而那個時段仍然卡在週曆上放不出去，別人也選不到。
US13 的頁面註腳「已被預約的時段要先到 Leads 名單處理該筆預約才能收回」指向一個當時還不存在的動作。

這個故事補上兩件事：確認信附一份真正的行事曆邀請（`.ics`），以及後台可以改期／取消已成立的預約，
而異動會同步反映到對方的行事曆與 Zoom 會議上。

**驗收**：
- [x] 「客製服務預約確認」信 MUST 附一份 `.ics`（`text/calendar; charset=UTF-8; method=REQUEST`，檔名 `consultation.ics`）；Zoom 已建會議時 `LOCATION` 與 `DESCRIPTION` 帶 `join_url`，未啟用或建立失敗時兩者留白但**附件照樣附** —— 時間本身就是要進行事曆的東西，有沒有連結是另一回事
- [x] `.ics` 的 `UID` MUST 由 lead id 推導且終生不變（`high-ticket-lead-{id}@{host}`），`SEQUENCE` 每次異動遞增並落庫；改期與取消能對上對方日曆裡的同一筆行程，靠的就是這兩個值（見 D49）
- [x] `DTSTART` / `DTEND` MUST 為 UTC（結尾 `Z`）；內容 MUST 以 **CRLF** 換行、超過 75 octets 的行做 folding 且 **MUST NOT 切在 UTF-8 多位元組字元中間**；`DESCRIPTION` / `SUMMARY` 內的 `,` `;` `\` 與換行 MUST escape（見 D51）
- [x] 後台可將已確認的預約**改期**到週曆上的另一個時段：舊單位釋出、新單位直接落為已確認佔用，包在單一 transaction；**不限改期次數**（使用者決策）
- [x] 改期 MUST 沿用 FR-032 的併發保護（`lockForUpdate` + 連續 N 單位檢查），撞車回 **409**；新舊區間重疊時屬於本 lead 自己的單位 MUST 視為可用（10:00 改到 10:15 不該被自己擋下）
- [x] 改期 MUST 呼叫 Zoom `PATCH /meetings/{id}` 更新 `start_time` / `duration`，**MUST NOT 刪除重建** —— `join_url` 不變，對方行事曆裡的連結與已經存下來的連結繼續有效（見 D52）
- [x] 改期 MUST 寄「客製服務預約已改期」信，附 `SEQUENCE+1`、同 `UID`、`METHOD:REQUEST` 的 `.ics`，對方日曆自動更新為新時間（不會多出第二筆行程）
- [x] 後台可**取消**已確認的預約：單位釋出回可預約、`cancelled_at` 落地、`status` 轉 `cancelled`
- [x] 取消 MUST 呼叫 Zoom `DELETE /meetings/{id}` 並清空 lead 的 `zoom_meeting_id` / `zoom_join_url`；Zoom 回 404（會議已被人工刪掉）MUST 視同成功
- [x] 取消 MUST 寄「客製服務預約已取消」信，附 `METHOD:CANCEL` + `STATUS:CANCELLED` 的 `.ics`，對方日曆自動移除該行程
- [x] 兩封異動信 MUST CC `high_ticket_lead_notify_cc`（沿用 FR-014）
- [x] Zoom 的更新／刪除 MUST 走 Job（`tries=3`），失敗只 log 並 CC 內部收件者，**MUST NOT 讓已成立的異動失敗** —— 時段與信件是事實，Zoom 是副作用（沿用 FR-016 原則）
- [x] 改期與取消的入口在**週曆的預約區塊**（US13）：區塊上兩顆按鈕。「改期」進入改期模式 —— 格線只讓能容納該長度的起始格可點，點選後跳確認（顯示舊時間 → 新時間）；「取消」跳確認 modal。改期模式可用 Esc 或再點一次按鈕退出（見 D53）
- [x] 該面板 MUST **懸浮在被選取的區塊正上方**並隨頁面捲動跟著移動，不再固定於行程表右上角（原本捲到下方時面板已離開可視範圍，見 D66）；水平置中並貼齊視窗邊界內、上方空間不足時自動翻到區塊下方
- [x] `cancelled` 為 status enum 第六值，Leads 名單篩選多一顆 tab；取消後的 lead **重新申請 MUST 自動轉回 `pending`**（沿用 `recordLead()` 對 closed / no_response 的既有處理）
- [x] 申請人端 MUST NOT 有任何自助改期／取消入口（使用者決策，見 D50）：信中不放管理連結，異動一律經由聯絡管理員
- [x] 所有新增的可點元素 `cursor-pointer` + hover 回饋；改期／取消為破壞性操作，MUST 二次確認
- [x] 測試：ics 的 UID / SEQUENCE / UTC 格式 / CRLF / 中文 folding 不斷字 / escape、Zoom 未啟用時仍附件、改期的併發與自我重疊、改期後 `join_url` 不變、取消釋出單位並轉狀態、Zoom 404 視同成功、兩封信的 CC 與附件 method（皆以 fake HTTP client）

### User Story 15 - 諮詢時段指派銷售顧問 (Priority: P2)

顧問要能順手開自己的時段、並且**在自己的信箱裡收到成立的預約**，才可能自己安排行程。
目前所有預約通知都寄到同一組客服／管理員信箱，顧問要嘛看不到、要嘛得靠人轉寄；
而時段本身也不知道是誰開的，週曆上只有一片沒有歸屬的空格。

同時把系統信的 CC 收斂到只剩一封：現在待確認、改期、取消都會副本給內部，
但改期與取消本來就是人與人直接寫信談出來的結果，系統再補一封只是噪音。

**驗收**：
- [ ] `consultation_slots` 新增 `consultant_id`（nullable）。staff（管理員或銷售顧問）在週曆上拖曳建立時段時，MUST 自動指派為「目前選定的歸屬對象」，預設是自己
- [ ] 週曆頁 MUST 有「時段歸屬」選擇器：管理員可選任一 staff（管理員 + 銷售顧問），銷售顧問 MUST 只能選自己（欄位鎖定顯示自己的名字）
- [ ] 管理員 MUST 能變更**既有預約**的顧問：選取預約區塊後在面板上切換，一次請求
- [ ] 已釋出但未被預約的格子，其歸屬 MUST 至少以 `title` tooltip 可查（v1 不做視覺分軌，見 D57）
- [ ] 確認預約當下 MUST 把該時段的 `consultant_id` **快照**到 `high_ticket_leads.consultant_id`；此後時段被改派或釋放都不影響已成立的歸屬（見 D58）
- [ ] Leads 名單的預約 tab MUST 顯示該筆的負責顧問（無指派時顯示「—」）
- [x] **CC 規則簡化**：只有「客製服務預約確認」信 CC，收件為**該筆的顧問**（未指派時退回客服清單）；「預約待確認」「已改期」「已取消」三封 MUST NOT CC 任何人（見 D59）
- [x] 確認信 MUST **只** CC 該筆的顧問；顧問為 null 時才退回客服清單 —— 沒有指派不等於沒有人要知道
- [ ] `ZoomMeetingService::createMeeting()` MUST 可指定主持人 Email（`POST /v2/users/{email}/meetings`）；未指定、或該 Email 在 Zoom 帳號下不存在（404）時 MUST fallback 回 `me` 並記 log，預約流程不受影響（見 D60）
- [ ] 顧問沒有 Zoom 席次時系統行為 MUST 完全等同現況 —— 會議建在擁有者帳號下，功能不因此中斷
- [ ] 所有新增的可點元素 `cursor-pointer` + hover 回饋
- [ ] 測試：拖曳建立時自動帶入歸屬、顧問只能指派給自己、管理員可改派既有預約、確認時快照到 lead、確認信 CC 客服 + 顧問、無顧問時只 CC 客服、其餘三封信完全無 CC、Zoom 指定主持人成功、主持人 404 時 fallback 回 me

### User Story 16 - 阻擋重複預約與電話正規化 (Priority: P1)

同一個人可以無限次重複預約同一門課的諮詢，而且**第二次申請會靜默摧毀第一次已確認的預約**：
`recordLead()` 命中既有列時把 `confirmed_at` 重設為 null、`reserve()` 把原本的時段釋放掉，
於是一筆已成立的預約變回「暫留中」。對方若沒點新的確認信，一小時後連新時段也釋出 ——
他從「有一個確認好的預約」變成什麼都沒有，而**雙方都不會收到任何通知**：
週曆上那格自己消失，`.ics` 也不會發取消，對方行事曆裡還留著已經不存在的行程。

`recordLead()` 的去重（D17）當初是為了「同一個人送三次表單就變三列待處理」而設計的，
那時還沒有時段、沒有 Email 確認、沒有 `.ics`、沒有 Zoom。這些加上去之後，
「更新既有列」的語意就從「刷新一筆待辦」變成了「拆掉一個已成立的預約」。

同時電話完全沒有參與比對，換一個 Email 就是新的一筆 lead、佔第二組時段。

**驗收**：
- [ ] 電話 MUST 在寫入 DB 前正規化：去除所有非數字字元，`+886` / `886` 開頭轉為 `0`（`0912-345-678`、`+886912345678`、`0912 345 678` 一律成為 `0912345678`）。正規化收在 `PhoneNumber::normalise()` 單一入口
- [ ] `high_ticket_leads.phone` 與 `users.phone` 的既有資料 MUST 由 migration 一併轉換；`orders.buyer_phone` **MUST NOT 動**（交易紀錄是當下的快照，改寫已成立的訂單風險大於效益）
- [ ] `high_ticket_leads.phone` MUST 建 index —— 它成為去重的查詢鍵
- [ ] 送出申請與候補申請 MUST 以 **Email 或正規化電話任一命中**、且**同一課程**尋找既有 lead
- [ ] 命中且處於下列狀態時 MUST 擋下（422），MUST NOT 建立或更新任何資料、MUST NOT 動到既有時段：
  | 狀態 | 擋 | 理由 |
  |------|----|------|
  | `pending` 且已確認（等待面談） | ✅ | 已經有一個成立的預約 |
  | `contacted`（已面談） | ✅ | 第二次諮詢走顧問 |
  | `converted`（已成交） | ✅ | 已超過「已面談」 |
  | `no_response`（未出席） | ✅ | 爽約者要再約須經顧問（使用者決策） |
  | `pending` 未確認（申請中） | ❌ 放行 | 還在流程中，改選時段是正常操作 |
  | `closed`（已關閉） | ❌ 放行 | 冷掉後重新申請正是要的再接觸（沿用 D17） |
  | `cancelled`（已取消） | ❌ 放行 | 取消後重約正是取消的意義（沿用 FR-049） |
- [ ] 擋下的訊息 MUST 指名該筆的**負責顧問 Email**，無指派時退回客服信箱（FR-057）；文案 MUST 說明「若需要改期或安排第二次面談，請直接聯絡」
- [ ] 前台 MUST 把這個訊息完整顯示給申請人（不是泛用的「送出失敗」）
- [ ] 測試：三種電話寫法正規化為同一值、換 Email 同電話被擋、換電話同 Email 被擋、四種擋下狀態各一、三種放行狀態各一、被擋時既有預約與時段**完全未被更動**、訊息含顧問 Email、無顧問時含客服信箱、舊資料 migration 轉換正確

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
  | `high_ticket_slot_available` | Job 實際替換 `{{user_name}}`、`{{course_name}}`、`{{booking_url}}`（FR-042） |
  | `high_ticket_booking_rescheduled` / `high_ticket_booking_cancelled` | 見 FR-052（US14 新增） |

- **FR-005**: 模板變數以 `str_replace` 全量替換（無 escape / 白名單機制）；event_type 建立後不可修改（update 僅驗證 name / subject / body_md / body_type）
- **FR-006**: 「通知新時段」MUST NOT 依 lead 狀態過濾傳入的 lead_ids（2026-08-04 放寬，原為只收 `status=pending`）— 收件人由管理員勾選決定；notified_count / last_notified_at 由 Job 於寄送成功後更新，非派送當下
- **FR-007**: 「加入序列信」MUST NOT 依 lead 狀態過濾（2026-08-05 放寬，原為 `status IN (pending, no_response, closed)`）—— 收件人由管理員勾選決定，與「通知新時段」（FR-006）採同一原則。**放寬的理由是舊規則會靜靜吃掉勾選**：`已面談` / `已成交` / `已取消` 被無聲丟棄，管理員勾了 6 位卻只看到「已加入 3 位」，而且前端按鈕會在選到這些人時變灰、不說原因 —— 其中「已取消」往往正是最該進序列的一群。
  真正的守門是**去重**：該 email 對「任何課程」存在 active 訂閱即跳過（計入 `skipped`），最後防線是 `DripService::subscribe()` 內的重複訂閱檢查（Job 內失敗僅記 log，lead 狀態不變）。
  唯一需要提醒的情形是 `converted`（已成交）—— 對已付費的人再寄一次這門課的招生序列信是尷尬的。處理方式為**在確認 modal 顯示提示**（「其中 N 位已成交…」）而非擋下：把既有客戶推向**另一門**課是合理操作，誤勾才不是，而這兩者只有管理員分得出來
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

- **FR-026**: 承諾條件清單的三條文案 MUST 逐字如下（前導說明「預約前，請先確認：」）：
  1. 我有明確想改善的問題，有意投入時間學習並持續執行。
  2. 我願意接受務實建議，也願意調整原本的想法與做法。
  3. 如果確認方向適合，我願意認真評估並採取下一步行動。

  三條 MUST 全數勾選才能前進，前後端各驗一次（前端控制按鈕 disabled，後端 `HighTicketBookingRequest` 驗 `commitments` 為長度 3 且全為 true 的陣列）。勾選事實以 `commitments_accepted_at` 時間戳落庫 —— 不逐條存布林，三條全真才寫入，存了也只會是三個 true（見 D30）。三個選項本身以獨立容器（`space-y-2`）分組間距，不跟隨 Step 2 外層 `space-y-4` 的段落級間距（2026-08-07 修正，原本外層 `space-y-4` 把選項間距撐得跟「標題到清單」一樣大）

- **FR-027**: 申請表單的必填欄位為 `name`、`email`、`phone`、`occupation`、`bottleneck`、`expertise`；`social_url` 選填但有值時 MUST 為合法 URL **且 scheme 限 http / https**（`url:http,https`）—— 光用 `url` 會放行 `ftp:`、`javascript:`、`data:`，而這個字串會成為後台 Leads 名單裡的 `href`。長度上限：name 100 / email 255 / phone 30 / occupation 255 / social_url 500；`bottleneck` 與 `expertise` 為 text，上限 2000 字。驗證 MUST 收在 `HighTicketBookingRequest`，controller 不得 inline `validate()`（專案慣例：Form Request 處理驗證）。
  **前端 MUST 同步擋下**（2026-08-05 追加）：`social_url` 格式錯誤時 MUST NOT 讓使用者離開第 1 步，且第 4 步覆核區 MUST 把該列標紅、附修正提示並停用送出鈕 —— 只靠後端 422 的話，使用者要按完「送出申請」才被丟回第 1 步，而覆核畫面在那之前完全看不出哪裡有問題。判定用 `new URL()` 而非 regex（瀏覽器同一套 parser，一次擋掉沒有 scheme 的 `instagram.com/me` 與危險的 `javascript:`）；空字串一律略過（選填）。
  **後端仍是權威**：前端規則與 Laravel `url` 規則在極端輸入上可能有落差（例如單標籤 host），因此 `submit()` 與 `submitWaitlist()` 兩條路徑 MUST 共用同一套 422 欄位錯誤處理，把使用者帶回擁有該欄位的步驟。`submitWaitlist()` 原本把欄位錯誤壓成「請稍後再試」—— 一句永遠不會成功的建議，且沒說是哪一欄

- **FR-028**: 時段的最小單位為 **15 分鐘**，一列 `consultation_slots` = 一個單位，`starts_at` 為 unique。一次預約佔用 **N 個時間上連續**的單位（N = 諮詢分鐘 ÷ 15）；連續的定義是 `starts_at` 每隔恰好 15 分鐘皆存在對應列且皆可用，缺一不可 —— 中間少一格就不是可選的起始時間

- **FR-029**: 單位的可用性 MUST 由 `lead_id` 與 `held_until` 推導，不另設 status 欄位（見 D33）：
  | lead_id | held_until | 意義 |
  |---------|-----------|------|
  | null | — | 可預約 |
  | 有值 | `> now()` | 暫留中（他人不可選） |
  | 有值 | null | 已確認佔用 |
  | 有值 | `<= now()` | 暫留逾時，**視同可預約** |

  所有查詢可用時段的地方 MUST 帶上第四列的條件，不得只判斷 `lead_id IS NULL`

- **FR-030**: 諮詢長度預設 **30 分鐘**（2 單位）；命中預約優惠碼者為 **45 分鐘**（3 單位）。兩個數字為 service 常數（`DEFAULT_MINUTES = 30`、`BONUS_MINUTES = 15`），不做成後台設定 —— 目前只有一種諮詢型態，設定項會比它服務的需求更貴

- **FR-031**: 預約優惠碼存於 `site_settings.high_ticket_booking_bonus_codes`（逗號 / 分號 / 空白分隔多組），解析沿用 `HighTicketBookingService::parseRecipients()` 的同一套切分規則。比對 MUST 忽略大小寫與前後空白。無效碼 MUST NOT 擋下流程（見 D31）。命中的碼原值寫入 `high_ticket_leads.booking_code` 供後台辨識來源。此設定 MUST 可由「諮詢時段」頁的欄位維護（`PUT /admin/consultation-slots/settings`，staff 群組），儲存時正規化為逗號分隔字串；留空即所有碼皆無效

- **FR-032**: 送出申請的寫入（lead upsert → 釋放該 lead 舊單位 → 佔用新單位）MUST 包在單一 `DB::transaction()`，且撈取目標單位時 MUST `lockForUpdate()`。並發搶同一時段時只有一方成立，另一方回 **409**「該時段剛被預約，請重新選擇」。寄信、drip、CAPI 一律留在 transaction 外（沿用 FR-016 的既有原則）

- **FR-033**: 預約成立分兩段，**確認前不算數**：
  - 送出申請 → 建 lead + 暫留時段 + 寄 `high_ticket_booking_verify`
  - 點確認連結 → `confirmed_at` 落地 + 時段轉正式 + 寄 `high_ticket_booking_confirmation` + drip `checkAndBook()` + Meta CAPI `Lead`

  `high_ticket_booking_confirmation`、drip 停信與 CAPI 事件 MUST NOT 在送出當下觸發（行為變更，見 D35）

- **FR-034**: `confirm_token` MUST 為 `Str::random(64)` 等級的不可預測字串並建 unique index；查詢一律以 token 為唯一入口，路由公開（無 auth）。token 三種失效情境的回應 MUST 可區分：**已確認**（冪等成功頁）、**逾時**（引導重新預約）、**不存在**（連結無效，不洩漏任何 lead 資訊）

- **FR-036**: Zoom 憑證存於 `site_settings`：`zoom_account_id` / `zoom_client_id` / `zoom_client_secret`，由後台「API 設定」頁維護。`zoom_client_secret` MUST 比照該頁既有 secret 欄位處理 —— 讀取時只回 `maskSecret()` 預覽、提交空字串代表「維持原值」，MUST NOT 把明文密鑰回傳給前端

- **FR-037**: `ZoomMeetingService` MUST 在三個憑證**任一為空**時回報「未啟用」，呼叫端據此跳過整條 Zoom 路徑（FR-039）。access token 以 `account_credentials` grant 取得並快取 **55 分鐘**（Zoom 效期 1 小時，留 5 分鐘餘裕）；快取鍵須包含 `client_id`，換憑證後不得沿用舊 token

- **FR-038**: 確認信的寄送時機依 Zoom 是否啟用分岔，但**兩條路徑都必須寄到**：
  | 情境 | 行為 |
  |------|------|
  | Zoom 未啟用 | 確認當下同步寄信，`{{zoom_join_url}}` → 空字串 |
  | Zoom 啟用、建會議成功 | 同步建好會議、寫回 lead、才寄信，`{{zoom_join_url}}` → `join_url` |
  | Zoom 啟用、建會議失敗 | 仍寄信，`{{zoom_join_url}}` → 「（會議連結將另行寄出）」，log error |

  MUST NOT 出現「確認成功但對方收不到任何信」的組合

- **FR-039**: Zoom 為**選配**：未設定憑證時系統行為 MUST 完全等同 US11（含測試）。測試 MUST 以 fake HTTP client 驗證，不得打真實 Zoom API

- **FR-046**: `.ics` 的產生 MUST 收斂於單一入口 `CalendarInviteService`，對外只有兩個方法：`invite()`（`METHOD:REQUEST`）與 `cancellation()`（`METHOD:CANCEL`）。**不引入 iCalendar 套件**（見 D51）。格式硬性要求：
  - 換行一律 **CRLF**（`\r\n`）；用 `\n` 有客戶端直接不解析
  - 行長超過 **75 octets** 需 folding（下一行以單一空白起始），且切點 MUST 落在 UTF-8 字元邊界 —— 中文一字 3 bytes，切在中間會讓整段變亂碼
  - `DTSTART` / `DTEND` / `DTSTAMP` 為 UTC 且以 `Z` 結尾；時段本來就存 UTC（D32），MUST NOT 繞經台北時區再轉回來
  - `SUMMARY` / `DESCRIPTION` / `LOCATION` 的 `\` `;` `,` 與換行 MUST escape（`\\` `\;` `\,` `\n`）
  - MUST 含 `ATTENDEE`（申請人 Email）與 `ORGANIZER` —— 少了 `ATTENDEE`，`METHOD:CANCEL` 在 Outlook 上不會生效
  - `SUMMARY` MUST 為 `"{申請人姓名} 諮詢"`，與 Zoom 會議 `topic`（見 US12 驗收）同一格式（2026-08-08 修正，原本是 `"{課程名} 1v1 諮詢 - {申請人姓名}"`，Zoom 名稱簡化時漏改這裡，導致加進 Google Calendar 等日曆 app 的行程標題仍是長版）

- **FR-047**: 行事曆行程的身分 MUST 由 `UID` + `SEQUENCE` 構成。`UID` = `high-ticket-lead-{lead_id}@{app_url 的 host}`，**推導而非落庫**（沿用 D33 的「狀態不存欄位」原則，lead id 不變則 UID 不變）；`SEQUENCE` 存於 `high_ticket_leads.calendar_sequence`，每次寄出異動邀請前 +1。同一 `UID` 配遞增 `SEQUENCE`，對方日曆才會**更新既有行程**而不是新增第二筆

- **FR-048**: 改期 = 「釋放舊單位 + 佔用新單位」的原子操作，MUST 包在單一 `DB::transaction()` 並 `lockForUpdate()`，撞車回 **409**（與 FR-032 同構）。既有 `ConsultationSlotService::reserve()` 已在檢查可用性前先 `release($lead)`，**自我重疊因此不需要特例**；本次只把 `$holdUntil` 放寬為 nullable，null 即新單位直接落為已確認佔用（`held_until = null`），不必再走一次 `confirm()`。改期 MUST 只對 `confirmed_at` 非 null 且未取消的 lead 生效；未確認的申請沒有「行程」可改，只有暫留會自然逾時

- **FR-049**: 取消 MUST 釋放該 lead 的全部單位、寫入 `cancelled_at`、`status` 轉 `cancelled`，並清空 `zoom_meeting_id` / `zoom_join_url`。`confirm_token` / `resume_token` **不清除** —— 對方若改變主意重新申請，`recordLead()` 會把 `cancelled` 一併視為可復活的狀態轉回 `pending`（比照既有的 closed / no_response 處理），舊 token 沿用不失效

- **FR-050**: Zoom 的改期／取消同步 MUST **同步執行**（2026-08-05 改，見 D55），且 MUST NOT 阻擋或回滾已成立的異動 —— 失敗只記 log 並回傳 `zoom_synced: false`，由 controller 在 flash 訊息附警告，讓管理員當場知道要去 Zoom 後台補。`updateMeeting()` 走 `PATCH /v2/meetings/{id}`（回 204），`deleteMeeting()` 走 `DELETE /v2/meetings/{id}`（回 204；**404 視同成功** —— 會議已經不在了，正是我們要的結果）。Zoom 未啟用或 lead 沒有 `zoom_meeting_id` 時整條跳過（沿用 FR-039）。**此條取代 FR-040**

- **FR-051**: `high_ticket_leads.status` 新增第六值 `cancelled`（「已取消」）。取消與 `closed` 是不同的事實 —— closed 是「聊過但冷掉／轉序列信」，cancelled 是「約好了但沒發生」，混在一起就看不出哪些人其實根本沒談過（使用者決策）。enum 變更 MUST 以 `Schema::change()` 帶完整值列表（比照 `2026_08_04_000002`，讓 sqlite 測試 DB 的 CHECK 一併更新），Leads 名單的篩選 tab 與色塊沿用既有的 `statusButtons` 單一設定表

- **FR-055**: Leads 名單的狀態**顯示名稱**與 DB 值刻意脫鉤（2026-08-05，使用者決策）。US14 之後每一筆 lead 都是「約了 1v1 面談」，漏斗講的是面談而不是聯繫，因此對照表如下：

  | DB 值 | 顯示名稱 | 意義 |
  |-------|---------|------|
  | `pending` | 待面談 | 已預約，面談還沒發生 |
  | `contacted` | 已面談 | 面談完成 |
  | `no_response` | **未出席** | 約了但沒出現（no-show）。與預約表單第 4 步警語「若確定預約卻無故**不出席**，我們將永久黑名單」（FR-025 區塊，`HighTicketBookingWizard.vue`）同一個詞 —— 申請人送出前讀到的就是它 |
  | `converted` | 已成交 | |
  | `closed` | 已關閉 | 談過但冷掉 |
  | `cancelled` | 已取消 | 預約被取消（FR-051），唯讀（FR-054） |

  **既有資料 MUST 一次性重新歸類**（2026-08-05 追加，業主指出）：顯示名稱換了意思、儲存值沒換，所以**部署前寫入的每一列都帶著舊語意**。放著不管等於把「我傳過訊息給他」悄悄升格成「面談發生了」，並把「單純沒回我」的人標記成爽約者 —— 而 US16 正是用那個狀態擋住他再次預約。
  | 舊值（舊意思） | 改為 | 理由 |
  |---|---|---|
  | `contacted`（已聯繫） | `pending`（待面談） | 聯絡過但面談還沒發生 |
  | `no_response`（未回應） | `cancelled`（已取消） | 從來沒談成，而且該讓他們能重新預約 |

  `pending` / `converted` / `closed` 值與意思都不變。`cancelled_at` 刻意留 null —— 不知道他們是什麼時候冷掉的，捏造一個時間戳比缺一個更糟；而且所有歷史 lead 都早於 `confirmed_at` 與 `consultation_slots`，沒有任何一筆握著預約，不會出現「說已取消卻還佔著時段」的矛盾。migration 不可逆（轉換後的 `pending` 與原生的 `pending` 無從分辨），但可重複執行。
  **MUST NOT 改動 DB 值**（指的是欄位語彙本身）：`contacted` / `no_response` 的字面意思雖然已與顯示名稱不符，但改名要動 enum migration + 既有資料 UPDATE，而既有的 `no_response` 列原意是「沒回我的訊息」，改寫成 no-show 等於把那段歷史重新貼標。代價是讀 code 時需要這張表；顯示字串收在 `BookingListTab.vue` 的 `statusButtons` 單一設定表，且每個值旁有註解說明。行為完全不變 —— `no_response` 仍可加入序列信、仍在重新預約時回到 `pending`（FR-007 / D17），這些規則對 no-show 同樣成立

- **FR-057**: 對外客服信箱 MUST 收在 `site_settings.support_email`，由「Email 模板管理」頁維護；未設定或留空時 fallback 至 `SiteSetting::DEFAULT_SUPPORT_EMAIL`。取值一律走 `SiteSetting::supportEmail()`。
  **`{{support_email}}` 與 `{{app_url}}` MUST 對所有模板自動可用**：注入點在 `EmailTemplate::renderSubject()` / `substitute()`，不是各呼叫端 —— 呼叫端有六個，漏一個的症狀是收件人信箱裡出現字面的 `{{support_email}}`。呼叫端明確傳入的值 MUST 覆蓋全域值。編輯頁的變數清單以 `GLOBAL_VARIABLES` 附加於每個 event_type 之後。
  **與 `high_ticket_lead_notify_cc` 是不同角色**（沿用 FR-014 的區分）：那是「誰接手這條 lead」，這是「訪客有問題寫給誰」；兩者共用同一頁但文案 MUST 說明差別。
  **全站唯一真相**：`app/` 與 `resources/` MUST NOT 出現硬寫的客服地址，唯一例外是 `SiteSetting::DEFAULT_SUPPORT_EMAIL` 常數本身；前台以 Inertia shared prop `supportEmail` 取用（法律條款 modal 掛在 footer，沒有單一 controller 可傳），後端一律 `SiteSetting::supportEmail()`。此條 MUST 由測試掃描原始碼把關 —— 一個只有一半應用程式遵守的設定，比沒有設定更糟

- **FR-060**: `consultation_slots.consultant_id`（nullable、index、無外鍵，比照 `lead_id` 的既有作法）記錄時段歸屬。staff 建立時段時 MUST 帶入請求中的歸屬對象；**銷售顧問 MUST 只能指派給自己**（後端驗證，前端鎖定只是提示）；管理員可指定任一 staff。
  **明確限制**：`starts_at` 維持**全域 unique**，所以這是**一本共用行事曆**上的歸屬標記，不是每位顧問各一本 —— 同一個 15 分鐘單位全系統只會有一列，兩位顧問無法同時開放同一時刻（見 D57）

- **FR-061**: 確認預約當下 MUST 把時段的 `consultant_id` 快照到 `high_ticket_leads.consultant_id`。快照而非即時查詢的理由：時段可被改派，取消後更會被釋放並可能重新指派給別人，屆時「這筆預約是誰負責的」就再也查不出來（見 D58）

- **FR-062**: 系統信的 CC MUST 收斂為**只有一封**：
  | 信件 | CC |
  |------|-----|
  | 客製服務預約確認（`high_ticket_booking_confirmation`） | **該筆的顧問**；未指派顧問時才退回客服清單（FR-014） |
  | 預約待確認（`BookingVerifyMail`） | **無** |
  | 預約已改期 / 已取消 | **無** |

  **顧問優先且互斥**（2026-08-05 收斂，使用者決策）：有指派顧問時**只**副本給顧問，客服清單不再同時收到 —— 顧問就是負責人，客服清單只是同一則消息的第二份。客服清單僅在**未指派顧問**時作為後備，因為沒有它的話一筆落在無主時段上的預約會**沒有任何人收到通知**，而那正是本模組反覆在防的失敗模式。
  **這是行為變更**：管理員不再收到每一筆「有人送出申請」的副本，只會收到已確認成立、且由自己負責的預約 —— 未確認的申請仍完整存在於 Leads 名單的 `pending`，沒有資料遺失（見 D59）

- **FR-064**: 電話 MUST 經 `PhoneNumber::normalise()` 正規化後才寫入 DB。規則：去除所有非數字字元；`886` 開頭（含 `+886`）轉為 `0`；空字串回 null。**唯一入口**，`HighTicketBookingRequest::prepareForValidation()` 在驗證前就套用，使驗證、儲存、比對三者看到的都是同一個值。
  適用 `high_ticket_leads.phone` 與 `users.phone`（兩者本來就相連 —— FR-041 會把 lead 電話寫進會員）。**`orders.buyer_phone` 明確不動**（使用者決策）：那是交易當下的快照，用途是聯絡與收據而非去重鍵，改寫已成立的訂單風險大於效益

- **FR-065**: 送出申請（`apply()`）與候補申請（`waitlist()`）MUST 先檢查重複預約，命中即回 **422 並終止** —— MUST NOT 建立 lead、MUST NOT 更新既有 lead、MUST NOT 動到任何時段。比對條件為**同一課程**下 `email` 或**正規化後的** `phone` 任一命中。
  擋下的狀態集合：`contacted` / `converted` / `no_response`，加上「`pending` 且 `confirmed_at` 非 null 且 `cancelled_at` 為 null」（＝等待面談）。放行：`closed`、`cancelled`、以及 `pending` 但尚未確認者。
  **狀態優先於 confirmed_at**：`closed` 的 lead 多半也有 `confirmed_at`（談過才冷掉），但它要放行 —— 判斷順序寫錯會把所有再接觸的人一起擋掉

- **FR-066**: 擋下的訊息 MUST 指名該筆 lead 的**負責顧問 Email**（`consultant_id`，US15），無指派時退回 `SiteSetting::supportEmail()`。前台 MUST 原樣顯示這段訊息 —— 這是唯一告訴申請人「該找誰」的地方，代換成泛用錯誤等於把人擋在門外又不給路

- **FR-067**: 預約名單的狀態篩選 tab MUST 在標籤後顯示該狀態佔漏斗的百分比（`[全部][待面談 70%][已面談 5%]…`）。分母 MUST 為**套用了搜尋 / 課程篩選、但不套用狀態篩選**的全體 leads —— 百分比是拿來看漏斗形狀的，若隨著點進某狀態而重算，那個狀態就會變成 100%，數字當場失去意義。計數 MUST 由後端一次 `GROUP BY status` 聚合（涵蓋整個結果集，不是當頁 20 筆），與列表共用同一組篩選條件；四捨五入後為 0 但實際有資料者顯示 `<1%`，總數為 0 時整排不顯示任何數字，`title` 提供實際筆數。「全部」MUST 顯示**總筆數**而非百分比 —— 它就是其餘百分比的分母，自己沒有佔比可言，而百分比若沒有一個絕對數字對照，「5%」是 2 筆還是 200 筆分不出來

- **FR-063**: `ZoomMeetingService::createMeeting()` MUST 接受選填的主持人 Email，指定時打 `POST /v2/users/{email}/meetings`。該 Email 在 Zoom 帳號下不存在時 Zoom 回 **404**，此時 MUST fallback 至 `/users/me/meetings` 並記 warning —— 顧問還沒有 Zoom 席次是**預期中的過渡狀態**，不是錯誤，預約流程 MUST NOT 因此中斷（見 D60）

- **FR-059**: 第 4 步的送出 MUST 為**兩段式**（2026-08-05，實測踩到）。第一次按「送出申請」MUST NOT 直接送出，而是顯示醒目的 Email 覆核區塊（大字體印出 `form.email`）並**倒數 10 秒**，倒數期間按鈕停用、顯示剩餘秒數；倒數結束後按鈕改為「Email 正確，確認送出」，第二次按下才真的送出。區塊 MUST 提供「這個 Email 不對，回去修改」直接跳回第 1 步。離開第 4 步或修改 Email MUST 重置整個流程。
  **送出後的畫面 MUST 逐字印出收件地址**（含候補路徑），並說明地址錯誤的後果。
  理由：打錯 Email 是**靜默失敗** —— 申請成功、時段被佔住、確認信寄到不存在的地址，申請人不會收到任何東西，也不會知道哪裡出錯，時段就這樣被鎖到逾時。系統這端完全正常（`Mail::send()` 不會丟例外、log 乾淨），所以事後也查不出異狀。唯一能攔下它的時機就是送出前那一眼，而 10 秒的強制停頓正是為了讓那一眼真的發生

- **FR-058**: 「預約待確認」信 MUST NOT 由 `email_templates` 驅動，改為寫死的 `BookingVerifyMail` + Blade（比照 `VerificationCodeMail`）。理由有二：（1）這封信是**機制**不是訊息 —— 它唯一的任務是送出確認連結，沒有業主會想改的措辭；（2）原本模板不存在時 `apply()` 直接回 422「預約待確認信模板不存在，請聯絡管理員」，**而正式站的資料庫正是這個狀態**（seeder 不隨部署執行），等於整條申請路徑在上線後是壞的。
  `apply()` MUST NOT 再檢查任何模板。信件 MUST 為 `multipart/alternative`（比照 FR-020 —— 確認連結進垃圾桶等於預約斷掉）。到期時間 MUST 帶日期（`n/j H:i`）：23:45 送出的申請隔天 00:45 到期，「今天 00:45」是錯的。
  `high_ticket_booking_verify` 這筆模板 MUST 從 seeder、編輯頁變數清單、後台清單標籤一併移除，並以資料 migration 刪除既有列 —— 留著會在後台顯示成一個「可編輯但改了沒用」的模板，比沒有更糟

- **FR-056**: 預約流程 MUST NOT 依賴 queue worker（2026-08-05，見 D55）。建立 Zoom 會議、寄確認信、改期／取消的 Zoom 同步一律**同步執行**；`ZoomMeetingService` 的每個 HTTP 呼叫 MUST 設 timeout（8 秒）與短重試（2 次、間隔 300ms），避免 Zoom 掛掉時把訪客的請求拖住。測試 MUST 以 `Queue::fake()` + `Queue::assertNothingPushed()` 釘住這件事 —— 沒有 worker 也要能寄出確認信

- **FR-054**: `updateStatus` 端點接受 `cancelled`，但**僅限該 lead 沒有生效中的預約**（`isActiveBooking()` 為 false）。有生效預約時 MUST 回 422 並指向「諮詢時段」頁 —— 從名單一鍵改成「已取消」會貼上標籤卻不釋出時段、不刪 Zoom 會議、不通知對方，那個狀態會是假的；唯一正確入口是週曆區塊的取消動作（FR-049）。
  **2026-08-05 收窄**：原條款是整個拒絕 `cancelled`，但那個理由只對「握著預約」的 lead 成立。沒有預約的 lead（尤其 FR-055 從舊 `no_response` 轉過來的那批 —— 它們沒有時段也沒有 `confirmed_at`）本來就沒有東西要釋出，全面拒絕反而製造死路：一旦被誤改成其他狀態就再也回不去。名單上該顆按鈕永遠顯示，有生效預約時停用並以 tooltip 說明要去哪裡取消

- **FR-052**: 新增兩個 `email_templates`：

  | event_type | 名稱 | 可用變數 |
  |------------|------|---------|
  | `high_ticket_booking_rescheduled` | 客製服務預約已改期 | `{{user_name}}`、`{{user_email}}`、`{{course_name}}`、`{{old_slot_time}}`、`{{slot_time}}`、`{{consult_minutes}}`、`{{zoom_join_url}}` |
  | `high_ticket_booking_cancelled` | 客製服務預約已取消 | `{{user_name}}`、`{{user_email}}`、`{{course_name}}`、`{{slot_time}}`、`{{course_url}}` |

  **所有** canonical 模板 MUST 由 `2026_08_08_000003` 保證存在於正式站：逐一檢查 `event_type`，缺的才 insert，**永遠不 update**（業主編輯過的文案是他的，「修復」時蓋掉會比原本的 bug 更糟）。模板清單的唯一來源是 `EmailTemplateSeeder::templates()`（public static），migration 直接讀它 —— 不可有第二份 body 定義。
  兩者 MUST 同時登記於 `EmailTemplateSeeder` 與**一支資料 migration**（見 D54）。migration MUST 以「先查 `event_type` 是否存在再 insert」實作，**不可用 `insertOrIgnore`** —— `event_type` 只有一般索引、沒有 unique 約束（見 Schema 段），IGNORE 沒有約束可觸發，重跑會直接多一列，而 `forEvent()->first()` 會沉默地取到其中一筆。變數清單 MUST 補進 `EmailTemplateController::$availableVariables`，否則編輯頁沒有插入按鈕、管理員不知道有哪些變數可用（`high_ticket_slot_available` 就漏過一次，見 FR-004 表格）

- **FR-053**: `TemplatedMail` MUST 支援附件，但 MUST 維持它「對信件內容一無所知」的定位：建構子多收一個 `array $attachments`（Laravel `Illuminate\Mail\Mailables\Attachment` 實例陣列），`attachments()` 原樣回傳。MIME type 由**呼叫端**決定 —— `method=REQUEST` 與 `method=CANCEL` 是兩個不同的 content type，Mailable 不該知道差別

- **FR-045**: 批次收回 MUST 只刪除**未被佔用**的單位（`lead_id` 為 null 或暫留已逾時）。區間內已被佔用者跳過並計入 flash 的略過數 —— 沿用既有 `destroy()` 的守門理由：把已確認預約的時段抽掉，對方手上會留著一張指向系統已不認得的時段的行事曆邀請

- **FR-044**: 新增批次收回端點 `DELETE /admin/consultation-slots`（帶 `date` / `start_time` / `end_time`，staff 群組）。既有的單筆 `DELETE /admin/consultation-slots/{slot}` 保留 —— 兩者共用同一段守門邏輯，收在 `ConsultationSlotService` 內

- **FR-043**: 後台諮詢時段 MUST 以週曆格線操作（1 格 = 15 分鐘），拖曳選取範圍後一次送出；`GET /admin/consultation-slots?week=YYYY-MM-DD` 回傳該週資料，`week` 缺省或不合法時取本週。回傳的預約 MUST 由後端**聚合為區塊**（同一 lead 的連續單位合併，附起訖時間、暱稱、Email、狀態、`zoom_join_url`、`held_until`），前端不得自行拼接（見 D46）

- **FR-042**: 候補（無時段可選）的 lead MUST 取得一組永久有效的 `resume_token`；寄送「新時段通知」時若該 lead 尚無 token（FR-042 之前的舊資料，或走完整流程而非候補的 lead）MUST 當場補發，使**任何被通知的 lead 都有可用的深連結**。信 MUST 提供 `{{booking_url}}` = `/course/{slug}?resume={token}`。持該 token 造訪銷售頁 MUST 回傳完整 draft（姓名 / Email / 問卷五欄 / 已命中的 `booking_code`）且**不要求登入**；`booking_code` 只在當初驗證通過時才存在，帶回不會讓失效碼復活，漏帶則會把對方已取得的 45 分鐘悄悄縮回 30；問卷四個必填欄位齊全時 `resume: true`，精靈直接開在第 3 步並視承諾清單為已接受，否則 `resume: false` 由第 1 步開始（否則會把人丟在後面的步驟、前面卻是空的）。token MUST 與課程綁定（跨課程無效）；重複送出候補申請 MUST 沿用同一 token，使既發出的信不失效

- **FR-041**: 問卷的手機號碼 MUST 在 lead 轉為會員帳號時寫入 `users.phone`，且 MUST NOT 覆寫既有會員的值（以 `firstOrCreate` 的建立屬性帶入，非 `update`）。所有電話欄位 MUST 為 `varchar(30)`：`users.phone`、`orders.buyer_phone`、`high_ticket_leads.phone` 三者與 Form Request 的 `max:30` 一致

- **FR-040**: ~~本次 MUST NOT 實作改期 / 取消同步~~ **已由 FR-050 取代（2026-08-05, US14）**。原條款記錄 US12 當時的取捨：改期／取消不同步，Zoom 會議需人工到後台刪除（D42）。US14 補上後台改期與取消，兩者皆同步呼叫 Zoom 並寄出更新／取消的 `.ics`（D55）。**仍未涵蓋的殘留情形**：管理員直接在 Leads 名單把狀態改成 `closed`，或用批次收回硬刪未被佔用的單位 —— 這兩條路徑不觸發任何同步，取消預約請一律走預約區塊的「取消」按鈕

- **FR-035**: 逾時釋出的正確性來源 MUST 是查詢時的 lazy 判定（FR-029 第四列），排程 `booking:release-holds` 只做資料整理。排程停擺時系統行為 MUST 完全正確 —— 使用者選得到逾時釋出的時段，只是後台會看到殘留的 `lead_id`

- **FR-025**: 舊入口 MUST 完整移除，不留轉址：`GET /admin/courses/{course}/subscribers` 路由、`CourseController@subscribers` action、`Pages/Admin/Courses/Subscribers.vue`、課程編輯頁的「訂閱者」按鈕四者一起刪。保留半套等於兩份 UI 要各自維護（使用者決策）

- **FR-068**: 逾時未確認的申請 MUST 從 `high_ticket_leads` **刪除**，不只是釋放時段。判定為 `confirmed_at IS NULL AND confirm_expires_at IS NOT NULL AND confirm_expires_at <= now() AND status = 'pending'`，執行點為 `HighTicketBookingService::purgeExpiredApplications()`（由 `booking:release-holds` 每 10 分鐘呼叫）。三道保留條件缺一不可：（1）`confirmed_at` 非空代表曾經成立過預約，含事後取消者，保留完整歷史；（2）`status` 已被管理員改離 `pending` 代表有人正在手動跟進，程式不得覆蓋人的判斷；（3）候補名單（US10）的 `confirm_expires_at` 為 null，本來就沒有東西可逾時，不在範圍內。**MUST 先釋放時段再刪 lead** —— `consultation_slots.lead_id` 無外鍵約束，反過來做會讓時段指向不存在的 row，後台週曆顯示幽靈擁有者

- **FR-069**: `ConsultationSlotService::availableStarts(int $minutes)` 在 `$minutes >= 45`（優惠碼延長）時，MUST 只保留台北時間分鐘數為 `0` 或 `30` 的起始時刻；`$minutes` 為預設 30 分鐘時不過濾，維持每 15 分鐘一個起始選項。過濾發生在既有的「N 個連續單位皆可用」判定**之後**——先照 FR-028 找出所有合法起始，再依長度篩選分鐘數，兩條規則不互相依賴。業主回報 45 分鐘場若允許 `:15`/`:45` 起訖，跟顧問既有的整點／半點行事曆習慣對不上。

## 設計決策

- **D57**: 顧問歸屬做在**共用行事曆**上，不改成每位顧問各一本（使用者決策）。
  `consultation_slots.starts_at` 目前是全域 unique，一個 15 分鐘單位全系統只有一列。要讓兩位顧問同時開放同一時刻，得改成 `unique(starts_at, consultant_id)`，而那會連鎖到：公開選時段頁要決定「訪客自己挑顧問還是系統分配」（**商業決定，不是技術決定**）、`availableStarts()` 要跨顧問聚合去重、FR-032 的併發搶位變成 per-consultant、週曆要分軌或加篩選。那是一個完整的 US，動到公開預約流程。
  目前系統裡有 **0 位銷售顧問**，這整件事是為將來鋪路。先做歸屬標記可以立刻解決「顧問收不到信」與「不知道誰開的時段」，而且完全不擋日後升級 —— 換掉 unique 再處理前台即可。
  **代價要說清楚**：週曆是一本共用的，兩位顧問靠協調分時段而不是靠系統隔離；A 顧問看得到也收得回 B 顧問釋出的空格。這在只有一位顧問時完全無感，在第二位加入時就會變成問題 —— 那也正是該升級成每人一本行事曆的訊號。
  同理，**未被預約的空格不做視覺分軌**（只給 tooltip）：一位顧問時那是純噪音，兩位顧問時光靠顏色也不夠用，屆時要的是分軌或篩選，不是把 v1 的配色改複雜

- **D58**: 顧問在 lead 上**存快照**而不是每次從時段查（使用者決策）。時段的歸屬是「這段時間屬於誰」，會被改派；取消預約還會把時段釋放回可預約池，之後可能指派給完全不同的人。若靠即時查詢，一筆三個月前成交的 lead 會顯示現在佔用那個時刻的顧問，甚至查無資料。
  快照的欄位加在現在是一欄，事後要補只能用猜的 —— 這是少數「先加比較便宜」的欄位

- **D59**: CC 從三封收斂成一封（使用者決策）。改期與取消**本來就是人與人直接寫信談出來的結果**，管理員是那場對話的當事人，系統再補一封只是噪音；待確認信則是「這個 Email 是真的嗎」的一次性驗證，未確認的申請照樣完整躺在 Leads 名單的 `pending`。
  **失去的東西**：管理員不再即時知道「有人送出申請但還沒確認」。這是可接受的 —— 那個狀態本來就要進名單才處理，而且 US14 之後 `pending` 就是名單的預設篩選。若日後想要即時感知，正確做法是後台的通知中心而不是把 CC 加回來

- **D64**: 「模板只存在 seeder」是一個**反覆出現的 bug 形狀**，因此改為一次性關掉整類（2026-08-05，第三次踩到之後）。
  seeder 只在全新安裝時跑，部署只跑 `migrate`。於是每次有人在 seeder 加一個模板，它在開發環境一切正常、在正式站永遠不存在，而症狀每次都不同且都很安靜：
  | 模板 | 正式站的症狀 |
  |---|---|
  | `high_ticket_booking_verify` | 送出申請直接 422 |
  | `booking_rescheduled` / `_cancelled` | 改期／取消信只在 log 留 warning，什麼都沒寄 |
  | `high_ticket_slot_available` | 「通知新時段」拒絕派送 |

  前兩次都是補一支專屬 migration；第三次代表這不是巧合而是結構問題。`2026_08_08_000003` 改為檢查**全部** canonical event_type、只補缺的、絕不覆寫，並把模板清單抽成 `EmailTemplateSeeder::templates()` 讓 seeder 與 migration 共用同一份定義。
  **副作用值得記錄**：這支 migration 在測試 DB 也會跑，於是原本 `EmailTemplate::create()` 的測試會產生重複列（`event_type` 沒有 unique 約束，`forEvent()->first()` 會沉默地取錯），10 個測試因此變紅。全部改為 `updateOrCreate`；而斷言「模板不存在時的行為」的測試改為**明確刪除**該列 —— 那個前提以前靠「測試 DB 本來就是空的」成立，現在必須自己安排

- **D65**: 圖例旁的數量統計是**全站累計**，不是「這週格線裡有幾個」（使用者決策，2026-08-07）。三個真實狀態的口徑各不相同，刻意分開處理：
  可預約／暫留中只算 `starts_at` 未過去的列（沿用既有 `scopeUpcoming`）—— 昨天沒被訂走的空格已經不可能被訂走，算進「可預約」是誤導；已過期的暫留同理，邏輯上早該被 `booking:release-holds` 釋出。
  已預約則**不分過去未來，全部算** —— 這格代表「總共成交幾場諮詢」，過去已完成的諮詢也是成交，排除掉反而失真。
  「未釋出」明確**不接數字**：它代表「還沒開放的時段」，時間軸往未來是無限的，沒有天然分母。硬要給一個數字勢必得先框一個時間窗口（例如未來 4 週），而那個窗口長度本身沒有業務意義、純粹是為了湊一個分母，之後只會有人問「為什麼是 4 週不是 8 週」。圖例保留這一格純視覺用途（灰格 = 還沒開放），不接統計。

- **D66**: 預約區塊的操作面板改用 **`position: fixed` + `getBoundingClientRect()`** 追蹤區塊位置，取代原本待在行程表右上角固定格的作法（使用者回報，2026-08-07）。
  面板內容（姓名、時間、狀態、名單／Zoom 連結、改期／取消按鈕）遠比一個 15 分鐘格（28px 高、104px 寬）寬，勢必要疊到相鄰欄位甚至相鄰日期的格子上方 —— 這代表面板不能是區塊的一般文件流子元素，得用絕對定位「浮」出來。
  兩個候選：(a) 面板當區塊的絕對定位子元素，靠 CSS Grid 版位讓它跟著區塊走；(b) 面板用 `position: fixed`，點擊當下量出區塊的 `getBoundingClientRect()`，之後用 `scroll`/`resize` 監聽器即時重算。選 (b)：整個行事曆包在 `overflow-x-auto` 的橫向捲動容器裡，而 CSS 規則是只要一軸設了非 `visible` 的 overflow，另一軸會一併變成有效的裁切邊界（`overflow-x: auto` 會讓 `overflow-y` 的算出值也變 `auto`）—— 面板往上浮出區塊頂端時，垂直方向也會被同一個容器裁掉，(a) 的方式在區塊排在較前面幾列時會整個消失不見。`position: fixed` 直接跳出所有裁切邊界，沒有這個問題。
  代價：需要監聽器（`window.addEventListener('scroll', ..., true)` 用 capture 才抓得到內層容器自己的捲動、加 `resize`），面板打開期間掛上、`clearSelection()` 與 `onUnmounted` 一併卸載，否則會是外洩的監聽器。手機（`< sm`）切換單日檢視時直接 `clearSelection()`（呼叫既有的 `syncNarrow`）—— 版面切換當下錨點元素可能已經被 Vue 換掉，與其追蹤哪個 DOM 節點還存在，不如把面板收起來，使用者重新點一次的成本很低
  水平置中並用面板實際量到的寬度（`offsetWidth`）夾在 `[8px, innerWidth - 8px]` 之間，避免貼齊視窗邊緣時被切掉；垂直預設在區塊上方，量到區塊距視窗頂端不足一個面板高度時（約 80px 判斷）自動翻到區塊下方 —— 這與原生 tooltip 的碰撞處理是同一套邏輯

- **D63**: 重複預約的檢查**只發生在送出當下**，不做第 1 步的即時 precheck（2026-08-05，使用者決策）。
  體驗上這是有代價的：申請人會填完整份問卷、選完時段、等完 10 秒的 Email 覆核倒數，才被告知白填了。把檢查提前到第 1 步（Email 與手機都已填妥）能省下那一切。
  不做的理由是**隱私**：那需要一個「輸入 Email／手機 → 回答有沒有預約過」的端點，正是 US9 當初刻意避開的探測器。US9 避開的那個更敏感（會回傳對方的完整問卷答案），這個只回布林值，而且同樣的資訊透過 `POST /course/{course}/book` 的 422 本來就問得到 —— 但業主判斷「知道某人跟你約過諮詢」這件事本身就不該變得更容易問，寧可承擔體驗成本。
  這個取捨若日後要翻案，正確做法是加端點時同步收緊 `/book` 的 throttle，讓兩條路徑的可探測性一致 —— 只加 precheck 而不動 `/book`，是把門鎖上卻留著窗。

- **D61**: 重複預約改為**擋下**而不是「當成改期處理」（使用者決策）。
  技術上可以把第二次申請當成改期（保留 confirmed、不要求重新確認、發 `.ics` 更新、同步 Zoom），但那與 D50 的決定衝突 —— 既然改期／取消一律人工聯絡，那「重新申請」就是繞過那個決定的後門，做得越順手繞得越多。擋下並指名顧問 Email，是把人導回既有的那條路。
  第二次諮詢的需求真實存在，但**在系統之外手動安排**（使用者決策）：顧問可以直接在週曆上開時段並手動指派，不需要申請人再走一次公開流程。
  `no_response`（未出席）也擋（使用者決策）：爽約者要再約須經顧問，這讓業主保有「要不要再給一次機會」的決定權。`closed` 與 `cancelled` 放行，因為那兩種狀態下重新申請正是我們想要的再接觸

- **D62**: 電話正規化採**去數字 + 台灣國碼轉換**，不引 libphonenumber。
  受眾是台灣，需要處理的只有 `0912345678` / `0912-345-678` / `0912 345 678` / `+886912345678` / `886912345678` 這幾種寫法，全部化為 `0912345678`。非台灣號碼會退化成「純數字字串」，仍然是穩定的比對鍵 —— 對去重來說夠用，而 libphonenumber 會帶進一個要跟著更新的號碼規則資料庫。
  正規化放在 **Form Request 的 `prepareForValidation()`** 而非 service：那是請求進入系統的第一個關卡，之後驗證規則、儲存、去重查詢看到的都是同一個值，不會出現「驗證用原值、比對用正規值」的錯位。
  舊資料以 migration 一次轉換。**`orders.buyer_phone` 不動**：它不是去重鍵，而改寫已成立的交易紀錄是不對稱的風險 —— 出錯時沒有還原依據

- **D60**: Zoom 主持人做成**選填 + 404 fallback**，而不是等買了席次再實作。
  `POST /v2/users/{email}/meetings` 需要該 Email 在同一個 Zoom 帳號下有席次（Zoom 按席次計費）。業主打算之後才買，所以現在指定顧問 Email 一定會 404。
  兩個選擇：現在不做、日後回頭改；或現在做好 fallback、買了席次當天自動生效。選後者，因為成本只有一個選填參數加一段 404 分支，而前者要等到最需要它的時候才回來動 Zoom 這塊最難測的程式碼。
  **404 視為預期狀態而非錯誤**（記 warning 不是 error）：顧問還沒有席次是過渡期的正常情況，用 error 會讓 log 充滿假警報，真正的 Zoom 故障反而被淹沒。
  代價：在買席次之前，「指派顧問」只完成一半 —— 信寄對人了，會議還是建在擁有者帳號下，顧問不是主持人（不能錄影、結束會議、管理等候室）。這點 MUST 寫在後台 API 設定頁的說明裡，否則業主會以為指派完就結束了

- **D56**: 「預約待確認」信改為寫死，客服信箱改為設定值（FR-057 / FR-058，使用者決策）。
  兩件事同一個根：**可設定性要放在會被改的東西上**。待確認信的措辭沒人會想改，卻因為模板缺列而讓整條申請 422；客服信箱是真的會換的東西，卻硬寫在六個地方。原本的設計把這兩者搞反了。
  判斷準則：**這封信是機制還是訊息？** 機制（驗證碼、確認連結）寫死；訊息（預約確認、改期通知、成交開通）留模板。前者改文案的需求是零、缺失的代價是流程中斷；後者相反。
  掃掉全站 6 處硬寫（藍新 3、統一 1、付款成功頁 1、隱私政策與購買須知各 1）。前台走 Inertia shared prop 而非逐頁傳 prop —— 法律條款 modal 在 footer，等於每一頁都可能要印，逐頁傳會漏。代價是每次請求多一次索引查詢，與這張表既有的讀取模式一致；沒有加 per-request memo，因為靜態快取跨測試殘留的風險大於省下的那次查詢。掃描結果由測試把關（`test_no_source_file_hardcodes_the_address`），否則下一個人複製貼上就又長回來。
  客服信箱注入在 `EmailTemplate` 的渲染入口而非各呼叫端 —— 六個呼叫端漏掉一個，症狀是收件人看到字面的 `{{support_email}}`，而那是寄出去之後才會發現的。放在 Email 模板頁而非另開設定頁：它服務的就是這一頁的模板，且旁邊已經有 `notify_cc`，兩者都是「信要去哪」。代價是要在文案裡明講兩者角色不同，否則很容易把客服信箱填成 lead 收件人。

- **D55**: Zoom 與確認信改為**同步執行**，移除 `CreateZoomMeetingJob` 與 `SyncZoomMeetingJob`（2026-08-05，使用者決策）。
  觸發點是 `QUEUE_CONNECTION=database` —— jobs 需要有 worker 常駐。而 D38 當初把確認信搬進了 job（為了讓對方只收一封含連結的完整信），於是**確認信成為這個模組裡唯一一條「訪客被告知成功、但結果掛在 worker 上」的路徑**，而且失敗是無聲的：頁面說「相關資料已寄出」，對方什麼都收不到。其餘的 job（通知新時段、加序列信）都是管理員主動觸發、看得到回報，性質不同。
  同步之後最差的情況是「頁面多等幾秒」或「信到了但沒連結 + log 有紀錄」，兩個都是吵的、可發現的。代價是**失去 `tries=3` + `backoff=[30,120,300]`**：Zoom 若有 30 秒等級的抽風，原本第二次重試會成功，現在對方就是收到沒有連結的信。以本模組的量級（一週數場 1v1）這個交換划算 —— 用「一定會寄到」換「連結偶爾要人工補」。
  補償措施：HTTP 層設 8 秒 timeout + 2 次短重試（間隔 300ms），吸收掉斷線等級的抖動而不影響 happy path；Laravel 預設 30 秒 timeout 在同步情境下會讓一次 Zoom 中斷把頁面卡住將近一分鐘。
  改期／取消的 Zoom 同步一併改為同步，理由相同但更強：那兩條路的信本來就是同步寄的，只有 Zoom 掛在 worker 上，等於「對方拿到新時間、Zoom 卻停在舊時間」而沒人會發現。同步之後 `syncZoom()` 回傳三態（null = 沒東西要同步 / true / false），controller 只在 false 時於 flash 附警告 —— 「本來就沒有會議」不是失敗，為它跳警告只會訓練管理員忽略警告。原本 job `failed()` 寄給內部的通知信隨之移除：管理員當下就在畫面前面，沒有理由改用 email 告知。

- **D49**: `.ics` 用 **`METHOD:REQUEST`** 而不是 `METHOD:PUBLISH`（FR-046）。兩者的差別很實際：Gmail 只對 REQUEST 顯示可互動的「加入日曆」卡片，PUBLISH 多半退化成一個附件圖示 —— 而這張卡片就是整個功能存在的理由，退化了等於沒做。
  REQUEST 的代價是它語意上是一份**正式邀請**，發出去就欠對方一套生命週期：改期要送同 `UID` 的更新、取消要送 `METHOD:CANCEL`，否則對方日曆會留下指向已不存在的會議的幽靈行程。這正是這個故事把 `.ics` 與「後台改期／取消」綁在同一個 US 的原因 —— 先前 US12 之所以沒做 `.ics`，缺的不是產生器，是這套生命週期（FR-040/D42）。單獨上 `.ics` 而不做取消，等於把 US12 的已知限制從「Zoom 後台有殘留」升級成「對方手機會在你早就取消的時間跳提醒」。
  另外**不再額外提供「加入 Google 日曆」按鈕連結**：它只服務 Google 用戶、且是一次性複製（之後的改期完全同步不到），與 REQUEST 的更新語意互相矛盾 —— 兩套並存會讓同一個人的日曆裡出現一筆會更新、一筆不會更新的重複行程

- **D50**: 申請人端**完全不做自助改期／取消**（使用者決策）。原規劃是拿既有的 `resume_token`（D44）開一個 `/booking/manage/{token}` 自助頁，被否決；理由是高價諮詢的異動本身就是一次接觸機會，讓對方按個鈕就消失，等於把唯一一次挽回的對話丟掉。政策面因此也不需要「開始前 N 小時截止」的門檻與改期次數上限 —— 沒有自助入口，就沒有需要限制的行為，`rescheduled_count` 欄位隨之取消。
  代價要說清楚：對方臨時有事**只能寫信或私訊**，如果他懶得聯絡就會直接是 no-show，而你到當下才知道。緩解的不是系統而是文案 —— 確認信與 `.ics` 的 `DESCRIPTION` 都要明確寫「需異動請直接回信」，並附上聯絡方式；否則對方連該找誰都不知道，只好放生。
  這也是為什麼改期不限次數：把它交給人之後，判斷「這個人改第三次了」是你的工作，不是系統的

- **D51**: `.ics` **自己產生，不引 iCalendar 套件**（FR-046）。需要的只有一個 `VEVENT`、七八個欄位，套件帶來的是 RRULE、時區資料庫、VALARM 那些我們用不到的東西，以及一個要跟著升級的相依。真正有難度的只有兩件事，而它們套件也不見得做對：**CRLF** 與 **UTF-8 安全的 75-octet folding**。中文一個字 3 bytes，天真的 `wordwrap()` / `str_split()` 會切在字元中間，讓整段 `DESCRIPTION` 變亂碼 —— 這是中文 `.ics` 最常見的失敗模式，所以 folding 要按 byte 累計、按字元邊界斷，並且**測試要直接斷言中文長行 folding 後仍可正確解回原字串**。
  同理，`UID` 選擇**推導而非落庫**（`high-ticket-lead-{id}@{host}`）：lead id 不變則 UID 不變，多一個欄位只是多一個會跟事實不同步的地方（沿用 D33 的推導優先原則）。`SEQUENCE` 則**必須落庫** —— 它是計數，推不出來

- **D52**: 改期走 Zoom `PATCH /meetings/{id}` 而**不是刪掉重建**（FR-050）。PATCH 保留同一個 `join_url`，於是三件事同時成立：對方日曆裡的 `LOCATION` 不用換、他先前自己存下來的連結還能用、`.ics` 的更新只需要改時間。刪除重建則會讓所有已發出的連結同時失效，而其中至少一份在對方的日曆裡 —— 那正是我們剛剛才更新過的那筆。
  刪除時把 Zoom 的 **404 視同成功**：這個 API 是宣告式的（「這個會議不該存在」），會議已被人工刪掉時 404 恰恰代表目標達成，當成錯誤重試三次只會產生三筆假的 error log

- **D53**: 後台改期／取消的入口放在**週曆的預約區塊**，不放 Leads 名單。理由是時段是空間問題（US13 的整個論點）：改期要看的是「哪裡還有空」，而那張圖只有週曆有；Leads 名單連時段欄位都沒有，在那裡改期等於盲選。
  改期的手勢選**兩段式**（點「改期」進模式 → 點新起始格 → 確認），不做「拖曳預約區塊到新位置」。拖放看起來更直覺，但 D45 已經把「拖曳起點」這個手勢分配給釋出／收回了，再疊一種語意上去，使用者每次按下滑鼠都得先想自己在做哪件事。兩段式的另一個好處是進入模式後可以**把不可用的起始格全部變灰**（不足 N 個連續單位的位置直接不給點），把「選了才被拒絕」變成「根本選不到」。
  取消不做 undo，改為二次確認 modal —— 取消會寄信給對方並刪掉 Zoom 會議，這兩件事收不回來，undo 只會給人錯誤的安全感

- **D54**: 兩個新模板同時寫進 **seeder 與一支資料 migration**（FR-052）。正式站的部署只跑 `migrate`，不跑 seeder，所以只加 seeder 的話功能上線即形同虛設（寄信時找不到模板，只會在 log 留一行 warning）。這與 D44 那支 `2026_08_05_000005` 是同一類問題但**方向相反**：那次是既有模板不能被 seeder 蓋掉，所以用 migration 做「附加」；這次是全新的 event_type，正式站根本沒有這兩列，migration 用 `insertOrIgnore`（已存在就跳過）即可，不會有覆寫業主文案的風險

- **D23**: 合併後的殼**沿用 `Admin/HighTicketLeads/Index.vue` 這個 component 路徑**（Inertia render 字串與路由名不變），內容拆成兩個 tab 元件：`Components/Admin/Leads/BookingListTab.vue`（現有 987 行整段搬入）與 `Components/Admin/Leads/SubscriberListTab.vue`。不把訂閱者那 300 行直接塞進去 —— 987 + 300 行的單檔沒人改得動，而且兩個 tab 的 state（勾選、modal、篩選）互不相干，天然就該分檔。Page 檔只留 h1 + tab nav + 兩個 `v-if`

- **D24**: tab 走 **server-side query param**（`?tab=`）而非 Points.vue 的純前端 `v-show` 兩份都載。理由是兩頁的成本結構不同：Points.vue 的兩份資料都輕（一組設定 + 一張推薦統計表）；訂閱者 tab 要跑 per-lesson 開信/點擊聚合、per-subscriber 事件統計與分頁，把它掛在每次開 Leads 頁的路徑上，等於為了「可能會切過去看」而固定付一次重查詢。代價是切 tab 有一次 round-trip，但換來網址可分享、可重整、可加書籤，對後台是划算的

- **D25**: 訂閱者 tab 的參數加 `sub_` 前綴而非共用 `status`（FR-024）—— 兩邊狀態 enum 沒有交集，共用的話「篩已面談的 lead → 切到訂閱者」會變成篩一個不存在的訂閱狀態，得在切 tab 時清掉對方參數，那是更繞的做法。分開命名的附帶好處是切回來時原本的篩選還在

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

- **D17**: 同 email + 同課程的重複預約改為「更新既有 lead」而非新增一列（2026-08-03，推翻原 D7 的保留完整歷史）— 後台是一份**待辦名單**不是事件流水帳：同一個人送三次表單就變三列待處理，管理員得逐列判斷是不是同一人，複製 Email 也得靠去重補救。狀態處理採「closed 回復 pending、其餘保留」：closed 多半是冷掉後丟進序列信，本人再次主動預約等於重新有意願，該回到待辦；contacted / converted 是管理員基於真實接觸下的判斷，程式不該覆寫。代價是失去重複預約的次數與時間軸 —— 目前沒有任何功能讀這份歷史，等真的要做「預約熱度」再另開事件表，不為假想需求先犧牲日常可用性

- **D18**: CC 清單放 `site_settings` 並掛在 **Email 模板管理頁**，不放付款/API 設定頁 — 那頁是金流與第三方憑證，這是信件收件人，語意不同；Email 模板管理頁本來就是「信件相關設定」的入口，管理員找得到。保留 `DEFAULT_NOTIFY_CC` 常數當 fallback 而非改成必填設定：新環境（含測試 DB）沒有這筆設定時，lead 通知信仍必須寄得出去，絕不能因為沒設定就靜默不通知任何人

- **D19**: HTML 模式採「每列一個 `body_type` 旗標」而非自動偵測內容格式 —— `str_contains($body, '<')` 之類的猜測會在「Markdown 裡夾一個 `<br>`」時誤判，而誤判的後果是整封信變成裸露的原始碼寄給客戶。連帶四個取捨：（1）**切換模式不轉換既有內容**，只改變解讀方式 —— 自動 md→html 之後切回來就回不去了；（2）**html 不 sanitize**，比照 D2 與 `HtmlContent.vue` 對後台內容的既有立場，sanitizer 會吃掉 inline style 與 `<table>`，那正是 email HTML 唯一能用的排版手段；（3）**欄位仍叫 `body_md`**，改名要動 model / request / seeder / 四個 Mailable / 前端，收益只有命名好看；（4）**預覽改 sandbox iframe**，現行預覽套後台的 `prose` class 但真實信件的 blade 完全沒有 CSS，預覽比實際好看，iframe 讓兩種模式看到的都等於收件匣，順便擋掉貼進來的 `<script>` 在後台執行

- **D20**: 純文字備援（FR-020）與 HTML 模式同批交付，不另案處理 —— 兩者共用 `renderBody` / `renderText` 這條收斂後的渲染管線，分開做等於把同一段程式改兩次。動機是投遞率：模板信目前是 HTML-only，這是 SpamAssassin 明確扣分的 MIME_HTML_ONLY，而 010 的 drip 信已經因為同類問題被 Gmail 丟過垃圾桶（見 `DripMailDeliverabilityTest`）。要寄的那份 HTML 文案本身帶有收入數字、限量名額、時限與黑名單警告等高風險詞組，內容分數已經吃緊，能補的技術分不該省。寫法沿用 `NewsletterBroadcastMail` 既有的 `view: + text:`，不引入新機制

- **D21**: 換行不一致選擇「改後端去配合預覽」而不是「改預覽去配合後端」或「教管理員按兩次 Enter」—— 模板是寫給非工程師用的信件編輯器，「按一次 Enter 不會換行」是 Markdown 的規格，不是任何人的心智模型；而 US6 驗收條款從一開始就宣告「單行換行與寄出效果一致」，所以這是把實作對齊到早已聲明的規格，不是改需求。改預覽（拿掉 `breaks: true`）雖然也能達成一致，但那是把兩邊一起對齊到錯的那一邊。實作只有一行 converter 設定（`renderer.soft_break`），比在 UI 上加說明文字或做編輯器層的自動 `\n\n` 轉換都便宜

- **D22**: hard break 設定抽成 `EmailMarkdownService::toHtml()` 而非在三處各寫一次設定陣列 —— 一模一樣的 duplication 正是 FR-019 這次要消滅的東西，再手動複製三份等於當場製造下一個漂移點。owner 掛 **011** 而不是 000：這條規則是從 `EmailTemplate::renderBody()` 抽出來的，模板系統與 D2「信件內容走 CommonMark」的決策都在本模組，008 / 010 是消費者（已列 touchpoints）。日後若第四、第五個信件路徑出現、或需要更多 email 專用的渲染規則（例如自動加 `max-width` 容器），再評估是否升格到 000

- **D23**: 成交通知信**重用 `TemplatedMail`**（即原 `HighTicketBookingMail`，本次一併更名），推翻 T010 原本「新增 `LeadConvertedMail`」的規劃。原規劃寫於 US7 之前 —— 當時該 Mailable 還會自己 `new CommonMarkConverter()`、自己查預約模板，硬塞第三個事件進去確實會髒；US7（FR-019）把它掏空成只收 `subject / htmlBody / textBody` 的搬運工之後，它已同時服務預約確認與新時段通知兩個事件，再開一個 `content()` 一字不差的類別，正是 FR-019 這次要消滅的重複。更名而非沿用舊名，是因為「HighTicketBooking」已經名不副實，日後第四、第五個模板事件接上來時不會有人敢用它。代價：模板查詢與「缺模板不擋開通」（D15）的判斷落在 service 而非 Mailable 建構子 —— 這反而更對，那是成交流程的決策，不是一封信的決策。連帶取捨：`CourseGiftedMail` / `LessonAddedNotification` **不併入** `TemplatedMail`，它們有「缺模板時 fallback 到寫死 blade」的分支（系統自動流程不能因為模板被刪就中斷），與本類別「呼叫端先查好才進來」的前提不同，強行合併會把 fallback 邏輯塞回 Mailable

- **D29**: 四步驟流程抽成 `Components/Course/HighTicketBookingWizard.vue`，不塞進 `Course/Show.vue`。Show.vue 已超過 1000 行且 owner 是 002；這段流程有四個步驟的 state、問卷、承諾勾選、時段查詢與覆核區，直接內嵌會再加 300+ 行到一個別的模組擁有的檔案裡。抽成元件後 owner 乾淨落在 011，Show.vue 的 touchpoint 縮小到「掛一行元件」，日後改預約流程不必動到 002 的檔案

- **D30**: 承諾勾選只存一個 `commitments_accepted_at` 時間戳，不存逐條布林或 JSON。因為 FR-026 規定全勾才能前進 —— 存下來的必然全是 true，逐條存等於用多個欄位記錄一個常數。時間戳保留了唯一有資訊量的部分（何時同意），日後條款改版要追溯「同意的是哪一版」再加 `commitments_version` 即可，不為假想需求先攤開結構

- **D31**: 無效的預約優惠碼**不擋流程**，只降級為 30 分鐘並提示。這個碼是加值不是門票 —— 打錯字就被擋在流程外，等於用一個選填欄位製造流失，而流失的是已經填完問卷與承諾清單的高意願申請人。相對地「碼無效卻靜默當成有效」會讓顧問行事曆被多佔 15 分鐘，所以必須明說「將以 30 分鐘進行」，不能沉默

- **D32**: 時段的 `starts_at` 以 **UTC 落庫**（Laravel 慣例），顯示與選擇一律轉 **Asia/Taipei**。伺服器是 UTC（見 000 reference），若讓管理員輸入的「下午 2:00」直接當 UTC 存，後台看到的與信件寫的會差 8 小時 —— 這類錯誤在跨日時段上最致命（台北的隔天上午在 UTC 是當天下午）。轉換收在 `ConsultationSlotService`，controller 與前端只處理已轉好的值；前端顯示格式固定 `M/D（週X）HH:mm`

- **D33**: 時段狀態用 `lead_id` + `held_until` 兩欄推導，不加 `status` enum（FR-029）。理由是單一真相：有了 status 欄就會出現「status=booked 但 lead_id 為 null」這種自相矛盾的列，而它只能靠人工修。連帶決定逾時釋出走 **lazy 判定為主、排程為輔**（FR-035）—— 若正確性依賴排程，排程掛掉的那段時間所有逾時時段都選不到，而使用者完全看不出原因；lazy 判定讓系統在排程停擺時依然正確，排程退化成純粹的資料整理

- **D34**: 待確認信寄送失敗時「**留 lead、放時段**」。這是 D11「名單比信重要」與「時段是稀缺資源」兩個原則的交集：聯絡方式已經拿到，沒有理由丟掉；但那個人**永遠無法完成確認**（信根本沒到），時段留著就是白鎖一小時，還會讓其他真的收得到信的人選不到。前台文案改為「我們會主動與你聯絡安排時段」，把排程責任誠實地轉回人工 —— 比照 FR-013，不對未寄出的信宣稱已寄出

- **D35**: drip 停信與 Meta CAPI `Lead` 事件從「送出當下」移到「確認之後」（行為變更，推翻 US2 的既有順序）。理由是這兩件事都以「這是一條真實線索」為前提：CAPI 的 Lead 事件會餵給 Meta 做廣告優化，用未驗證的 Email 餵它等於教演算法去找「會隨手填表但不會確認」的人；drip 停信同理 —— 序列信是培養機制，人還沒確認就停掉，等於在最需要推力的時候撤掉推力。代價是若對方不確認，CAPI 就少一個事件，但那個事件本來就是雜訊

- **D36**: 舊的一步式表單**整組移除、不留課程層開關**（使用者決策）。留開關要維護兩套前端與兩條後端路徑，而目前隱藏價格的高價課只有少數幾門、成交入口本來就是人工（D13），沒有「某幾門課要低門檻」的實際需求。若日後真的需要，加開關比維護一套沒人走的舊路徑便宜

- **D37**: 「永久黑名單」只做**警示文案**，不做系統實作（使用者決策）。爽約與否是線下事實，系統無從自動判定；真要擋人，現有的 `closed` 狀態加上管理員記憶已足夠應付目前的量。文案本身才是它的作用 —— 它要嚇阻的是「隨便按送出」的人，而那個效果在覆核頁顯示的當下就已經達成，不需要後端配合

- **D38**（部分被 D55 取代：「一封完整的信」保留，但改為同步而非 job）: 確認信改由 `CreateZoomMeetingJob` 在建好會議後才寄，而不是確認當下先寄、連結後補。因為這封信的用途就是「告訴對方什麼時間、去哪裡」—— 少了連結就得再補寄一封，而補寄信的開信率遠低於第一封，且兩封信講同一件事最容易讓人以為預約重複了。代價是信件抵達比確認頁晚幾秒，這對使用者不可見（確認頁本來就寫「相關資料已寄出」，沒有承諾秒到）

- **D39**: Zoom 建立失敗三次後**照常寄出確認信**（帶 fallback 文案），而不是靜默重試到天荒地老或讓確認流程失敗。原則與 D11 / D34 同一條：已經成立的事實不能因為周邊服務失敗而被推翻 —— 對方已完成 Email 驗證、時段已正式保留，這是既成事實。同時 CC 內部收件者，是因為這種情況一定要有人去手動開會議並補連結，只寫 log 沒人會看

- **D40**: Zoom 設為**選配而非必要**（FR-039）。理由有二：本機開發與 CI 不該需要真實的第三方憑證才能跑完預約流程（否則測試會變成打真實 API 或整段被跳過）；以及萬一憑證過期或 Zoom 出事，系統要能退回 US11 的人工排程模式繼續收預約，而不是整條預約線停擺。判斷條件放在 service（`isEnabled()`），呼叫端只問一句，不各自檢查三個設定

- **D41**: Zoom 憑證放**後台「API 設定」頁**而非 `.env`（使用者決策）。該頁已經是 PayUni / 藍新 / Portaly / Meta CAPI token 的所在，語意一致（都是第三方服務憑證），業主換 Zoom app 時不必找工程師。沿用該頁既有的 `maskSecret()` + 「留白即維持原值」模式，不另發明一套 —— 那套模式已經在四組憑證上運作，再寫一份就是 FR-019 那類重複的起點。`client_secret` 走 secret 欄位，`account_id` / `client_id` 不遮罩（它們不是密鑰，遮了反而不好核對）

- **D42**: 本次**不做改期 / 取消同步**（FR-040）。改期是一條完整的支線 —— 要有對外的改期連結、時段釋放與重佔、Zoom `PATCH`/`DELETE`、以及改期後重寄哪封信的決策，跟本次「把門檻拉高」的目標無關。目前量體下，改期是顧問私訊喬時間的事，人工到 Zoom 後台改一分鐘的事情不值得先寫進系統。明確記為已知限制，比做半套（例如只刪時段不動 Zoom）好 —— 半套會讓管理員以為系統處理過了

- **D48**: **不做**「複製上週」、週期性時段、預設班表（US13 範圍外）。這三個都是「我每週二四都開下午」的變形，聽起來很省事，但它們各自要回答「改了母版之後既有的預約怎麼辦」—— 而那正是行事曆類功能最容易做爛的地方。目前顧問一週開一次時段，拖三下就完事；等到真的每週重複排到覺得煩，再回頭做，那時也才知道該做成哪一種。先把「拖曳一次開一整段」做對就是這次的全部

- **D47**: 顯示範圍固定 **08:00–22:00**，但**不當成資料的過濾器** —— 該週若有落在範圍外的既有時段，格線自動撐開到涵蓋它（FR-043 / US13 驗收第 2 條）。做成可設定（存 `site_settings`）被否決：多一組設定要維護、要驗證、要在 UI 上找地方放，而它要解的問題（半夜或清晨排諮詢）用「自動撐開」就已經解掉，而且不需要顧問先知道自己等一下要排幾點。固定值寫在前端常數即可，不進資料庫

- **D46**: 週檢視的預約區塊在**後端聚合**，前端只負責畫（FR-043）。一場 30 分鐘的諮詢在 DB 是 2 列、45 分鐘是 3 列，畫面上必須是一塊；把「同 lead 且 `starts_at` 相隔恰 15 分鐘就併」這條規則放到前端，等於讓 Vue 重新實作一次 FR-028 的連續性判斷 —— 那條規則已經在 `ConsultationSlotService::availableStarts()` 裡了，兩處各寫一份遲早會不一致。後端多回一個 `bookings` 陣列，前端 `v-for` 就好

- **D45**: 拖曳的**起點決定意圖**：起手在空白格 = 釋出，起手在已釋出格 = 收回，起手在已佔用格 = 不啟動（FR-043）。替代方案是「拖曳只能新增，收回改用區塊上的 ✕」，被否決的理由是收回一整個上午會變成點十六次 ✕ —— 新增可以一次拖，收回不行，這種不對稱在用的時候特別煩。
  起點決定意圖是 drag-to-paint 的通用作法（試算表選取、小畫家橡皮擦都是這個模型），使用者不需要先切換模式，手勢本身就宣告了意圖。代價是拖曳中途經過的格會混雜狀態，處理方式明確：**經過的格只套用起點決定的那一種動作**，已佔用的一律跳過（不是中止整段）。
  實作走 **Pointer Events 單一路徑**（滑鼠與觸控共用），而且拖曳中的命中判定用 `document.elementFromPoint()` 而非在每格掛 `pointerenter` —— 觸控指標會被起始元素捕獲，經過的格根本不會觸發 enter，手機拖曳會靜默地只選到一格。`pointermove` / `pointerup` / `pointercancel` 都綁在 `window` 上，拖到格線外放開也要正確收尾，否則會留下一個永遠選取中的狀態。
  代價：格子設 `touch-action: none` 才能吃到觸控拖曳，於是手機上格線區域無法用手指捲頁 —— 左側時間欄不設此屬性，作為捲動用的握把

- **D44**: 候補回訪走**獨立的 `resume_token`**，不重用 `confirm_token`（FR-042）。兩者的生命週期相反：`confirm_token` 一小時到期、用過即廢，是「這個 Email 是真的」的一次性證明；`resume_token` 可能要撐好幾週（顧問什麼時候開時段沒人知道），是「這份申請是你的」的持續憑證。把兩種語意壓在同一欄，早晚會有人為了讓其中一邊過期而弄壞另一邊。
  身分驗證只靠持有 token，**不要求登入** —— 候補者絕大多數不是會員，要求登入等於把這條路關掉；64 字元隨機值的猜中機率與確認連結同級，信任模型一致。token 與 `course_id` 綁定比對，避免一組 token 打開任何課程的既有申請。
  承諾清單**自動視為已接受**（使用者決策）：`commitments_accepted_at` 已經落在 lead 上，等待期間再要求重勾，讀起來像是在懷疑已經收到的答覆；畫面仍停留在可回頭修改的狀態，不是把步驟藏起來。
  另外補一支資料 migration（`2026_08_05_000005`）把 `{{booking_url}}` **附加**到正式站既有的「新時段通知」模板 —— seeder 用 `updateOrCreate`，在正式站重跑會把業主改過的文案整段蓋掉；不補這一支，功能上線即形同虛設（信照寄，但沒有連結）

- **D43**: 手機號碼在**轉換時**帶進會員資料，而不是在 Email 確認時回填（使用者決策）。預約流程不建 User，lead 要成為會員只有「開通商品」與「加入序列信」兩個時機，在那裡帶過去等於順手，不需要額外的寫入路徑。選 `firstOrCreate` 的建立屬性而非 `update`，是因為「預約一次就改掉既有會員的電話」是使用者沒要求的副作用 —— 會員自己在帳號設定填的值，權威性高於他在預約表單隨手填的。代價是既有會員的空 `phone` 不會被補上，這是可接受的：那筆資料仍在 lead 上看得到。連帶把三個電話欄位一律加寬到 `varchar(30)`，順手修掉一個既有的截斷風險 —— `FreePurchaseController` 早就收 `max:30`，但 `users.phone` 只有 20

- **D12**: 高價課測試已可持久化 `type=high_ticket`（2026-08-01 起）— 原本 `2026_04_09_000001` 只在 MySQL 分支擴 enum，sqlite 測試 DB 停在三值、任何高價課都無法落庫；`2026_08_01_000001`（004 D10）改用 `Schema::change()` 帶完整值列表後兩邊對齊，`CourseTypeTest` 已實測通過。既有測試（LeadConvertTest、FunnelStopTest、BookingMailFailureTest）仍走 service 層＋記憶體指定 type，改寫成 HTTP 層非必要，日後新增測試可直接建課

- **D63**: 逾時申請採**硬刪除**而非標記狀態（2026-08-06，業主指定）—— 名單只該留「真的成立過的預約」與「有人在跟進的對象」，填完問卷卻沒點確認信的人兩者都不是。代價講清楚：問卷答案（`phone` / `occupation` / `bottleneck` / `expertise`）會一併永久消失，而「email 已在訂閱者名單」救不回這些 —— 訂閱者名單只有 email、暱稱與訂閱狀態，且 `apply()` 本來就不建立 drip 訂閱（D35：未驗證的 email 還不算 lead），所以申請人是否在訂閱者名單其實不保證。保留 `status = 'pending'` 這道閘是整條規則的安全帶：管理員一旦動過狀態，掃除就繞開。連帶副作用：清掃後同一個確認連結會從 `expired` 落到 `invalid`，因為 lead 已不存在、無從分辨「逾時」與「網址亂打」，故 `invalid` 文案改為同時涵蓋兩種成因並導回重新申請 —— 原文案「可能是網址不完整，請直接使用信件中的連結」對一個正在使用信件連結的人是錯誤指引

## Schema

- **US16 schema 變更（一支 migration）**：

  `2026_08_08_000001_index_and_normalise_phones.php` — `high_ticket_leads.phone` 加 index（它成為去重查詢鍵），並把 `high_ticket_leads.phone` 與 `users.phone` 的既有值就地正規化（FR-064）。`orders.buyer_phone` 不動（D62）

  **只有一支** —— US16 的擋門完全建立在既有欄位（`status` / `confirmed_at` / `cancelled_at` / `phone`）之上，不需要第二支

  **不變量**：電話進 DB 前一律經 `PhoneNumber::normalise()`；DB 裡不應再出現含 `-`、空白或 `+886` 的號碼

- **US15 schema 變更（兩支 migration）**：

  `2026_08_07_000001_add_consultant_to_consultation_slots_table.php` — `consultation_slots` 增一欄：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `consultant_id` | unsignedBigInteger nullable, index | 這段時間屬於哪位 staff；無外鍵（比照 `lead_id` 的既有作法）。null = 未指派，通知一律回退客服清單（FR-062） |

  `2026_08_07_000002_add_consultant_to_high_ticket_leads_table.php` — `high_ticket_leads` 增一欄：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `consultant_id` | unsignedBigInteger nullable, index | 確認預約當下自時段**快照**（FR-061）；此後時段改派或釋放都不影響 |

  **不變量**：`starts_at` 維持全域 unique —— 這是共用行事曆上的歸屬標記，不是每位顧問各一本（D57）。要支援兩位顧問同時開放同一時刻，需改為 `unique(starts_at, consultant_id)` 並連帶重做公開選時段流程，那是另一個 US

- **US14 schema 變更（三支 migration）**：

  `2026_08_06_000001_add_calendar_fields_to_high_ticket_leads_table.php` — `high_ticket_leads` 增兩欄：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `calendar_sequence` | unsignedTinyInteger, default 0 | `.ics` 的 `SEQUENCE`；每次寄出異動邀請前 +1（FR-047） |
  | `cancelled_at` | timestamp nullable | 取消時間；null = 未取消。與 `status = cancelled` 並存是刻意的 —— status 可被管理員手改，時間戳是事實 |

  `2026_08_06_000002_add_cancelled_to_high_ticket_leads_status.php` — status enum 擴為六值
  `['pending', 'contacted', 'no_response', 'converted', 'closed', 'cancelled']`，`Schema::change()` 帶完整列表 + 重述 `default('pending')`（比照 `2026_08_04_000002`，否則 sqlite 的 CHECK 不會更新、default 會被丟掉）

  `2026_08_06_000003_insert_booking_change_email_templates.php` — 寫入 `high_ticket_booking_rescheduled` 與 `high_ticket_booking_cancelled` 兩筆模板（D54）。已存在即跳過（**逐筆查 `event_type`，不是 `insertOrIgnore`** —— 該欄無 unique 約束，見 FR-052），不覆寫任何既有文案

  **不變量**：`UID` 不落庫（由 lead id 推導，FR-047）；改期不新增列、不留歷史 —— `consultation_slots` 只表達「現在誰佔著哪一格」，改期軌跡要查請看信件與 log（若日後需要異動歷史，那是一張新表，不是往這裡加欄位）

- **US9–US11 schema 變更（兩支 migration）**：

  `2026_08_05_000001_add_application_fields_to_high_ticket_leads_table.php` — `high_ticket_leads` 增欄，**全部 nullable**（既有列沒有這些值，不得設 NOT NULL）：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `phone` | varchar(30) | 手機電話（US9 必填，欄位仍 nullable 供舊資料） |
  | `occupation` | varchar(255) | 職業和從事時長 |
  | `bottleneck` | text | 事業瓶頸 |
  | `expertise` | text | 知識或能力的專長 |
  | `social_url` | varchar(500) | 經營社群網址（選填） |
  | `commitments_accepted_at` | timestamp | 承諾清單全數勾選的時間（D30） |
  | `booking_code` | varchar(50) | 命中的預約優惠碼原值（FR-031） |
  | `confirm_token` | char(64) unique | Email 確認 token（FR-034） |
  | `confirm_expires_at` | timestamp | 確認期限 = 送出 + 1 小時，同時是時段暫留期限 |
  | `confirmed_at` | timestamp | 完成確認的時間；null = 尚未確認 |
  | `zoom_meeting_id` | varchar(50) | US12：Zoom 會議 id，未啟用或失敗時為 null |
  | `zoom_join_url` | varchar(500) | US12：會議連結，寫進確認信並顯示於後台 |

  `2026_08_05_000002_create_consultation_slots_table.php` — 新表 `consultation_slots`，**一列 = 一個 15 分鐘單位**：

  | 欄位 | 型別 | 說明 |
  |------|------|------|
  | `id` | bigint | |
  | `starts_at` | datetime **unique** | 單位起始時刻（UTC，D32）；unique 保證同一時刻不會有兩列 |
  | `lead_id` | unsignedBigInteger nullable, index | 佔用者；無外鍵約束（比照 D7 對 course_id 的既有作法） |
  | `held_until` | timestamp nullable | 暫留到期；null 且 lead_id 有值 = 已確認佔用 |
  | timestamps | | |

  **不變量**：狀態不存欄位，一律由 `lead_id` + `held_until` 推導（FR-029 / D33）；一次預約佔用 N 個 `starts_at` 相隔恰 15 分鐘的連續列（FR-028）

- `site_settings.high_ticket_booking_bonus_codes` — 預約專屬優惠碼，逗號分隔字串；命中者諮詢延長 15 分鐘（FR-031）。未設定即所有碼皆無效，流程照走 30 分鐘
- `email_templates` 新增第 6 筆 `high_ticket_booking_verify`（「客製服務預約待確認」），變數 `{{user_name}}`、`{{course_name}}`、`{{confirm_url}}`、`{{slot_time}}`、`{{expires_at}}`；既有 `high_ticket_booking_confirmation` 的可用變數擴充 `{{slot_time}}`、`{{consult_minutes}}`、`{{zoom_join_url}}`
- **US13（諮詢時段週曆化）無 schema 變更** —— `consultation_slots` 的三個欄位（`starts_at` / `lead_id` / `held_until`）已經足以推導週曆要畫的一切：格子有沒有被釋出看列存不存在，顏色看 `lead_id` + `held_until`（FR-029 的既有推導），區塊合併看 `starts_at` 是否相隔 15 分鐘。這次新增的是一個批次收回端點與一種呈現方式，不是新的事實
- `high_ticket_leads.resume_token` — char(64) unique nullable（`2026_08_05_000004`）；候補回訪的永久憑證，與一小時到期的 `confirm_token` 是兩種東西（D44）。只有走候補路徑的 lead 會有值
- `email_templates` 的 `high_ticket_slot_available` — 2026-08-05 由 `2026_08_05_000005` **附加**（非覆寫）`{{booking_url}}` 段落；已含該變數者跳過。正式站的模板內容可能已被業主編輯過，seeder 的 `updateOrCreate` 不能用來做這件事
- `users.phone` / `orders.buyer_phone` — 2026-08-05 由 varchar(20) 加寬為 **varchar(30)**（`2026_08_05_000003`），與 `high_ticket_leads.phone` 及所有 Form Request 的 `max:30` 對齊；加寬無損，舊值必然放得下
- `site_settings.zoom_account_id` / `zoom_client_id` / `zoom_client_secret` — Zoom Server-to-Server OAuth 憑證（FR-036），由後台「API 設定」頁維護；任一為空即 Zoom 未啟用（FR-037/FR-039）

- **本次 schema 變更**：`2026_08_03_000001_add_body_type_to_email_templates_table.php` — `string('body_type', 10)->default('markdown')->after('subject')`。用 string 不用 enum（比照 004 `change_content_category_to_string_on_courses` 的既有作法）；有 default，既有 4 筆自動落在 markdown，正式站不需資料處理
- **US8（名單管理頁合併）無 schema 變更** —— 純後台資訊架構調整，讀的是既有 `high_ticket_leads` 與 `drip_subscriptions` / `drip_email_events`
- US5（開通補強）無 schema 變更（新增的是 `email_templates` 的一筆資料，不是欄位；覆寫守門與 transaction 皆為既有欄位上的邏輯）
- US1 亦無 schema 變更（landing page 隱藏與 mail_sent 回報皆為既有欄位與前端呈現）
- `high_ticket_leads` — 預約產生的潛在客戶；status 銷售漏斗 enum(pending / contacted / no_response / converted / closed / cancelled) 預設 pending；**DB 值與顯示名稱刻意脫鉤，對照表見 FR-055**；notified_count（unsigned tinyint）與 last_notified_at 只由 NotifyHighTicketSlotJob 寄送成功後更新；booked_at 為最近一次提交時間（非 created_at 語意）；email / status / course_id 皆有索引。**DB 無 (email, course_id) unique 約束**，去重由 `recordLead()` 在應用層負責（D17）— 歷史資料可能已有重複列，加 unique 需先清理，現階段不值得
- `site_settings.support_email` — 對外客服信箱（FR-057）；空值或未設定時 fallback 至 `SiteSetting::DEFAULT_SUPPORT_EMAIL`。與下面的 notify_cc 是不同角色，不可混用
- `email_templates` 的 `high_ticket_booking_verify` — 2026-08-05 由 `2026_08_06_000004` **刪除**（FR-058，改為寫死的 `BookingVerifyMail`）；`down()` 為 no-op，沒有東西會再讀這個 event_type
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
- [ ] T040 實寄驗證：後台切 HTML 模式貼入模板 → tinker 觸發 `apply()` 寄到自己信箱 → 「顯示原始郵件」確認 `Content-Type: multipart/alternative` 且兩段內容皆正確

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

### 預約門檻提升：申請問卷 + 時段預約 + Email 二次確認 + Zoom（US9–US12）

Phase A — Schema 與模型（先落地，其餘全部相依）
- [x] T053 migration：`high_ticket_leads` 加 10 欄（phone / occupation / bottleneck / expertise / social_url / commitments_accepted_at / booking_code / confirm_token unique / confirm_expires_at / confirmed_at）+ US12 的 zoom_meeting_id / zoom_join_url，全部 nullable in `database/migrations/2026_08_05_000001_add_application_fields_to_high_ticket_leads_table.php`
- [x] T054 [P] migration：建 `consultation_slots`（starts_at datetime unique、lead_id nullable index、held_until nullable、timestamps）in `database/migrations/2026_08_05_000002_create_consultation_slots_table.php`
- [x] T055 `$fillable` 與 `casts` 補新欄位（四個 timestamp 轉 datetime）；加 `slots()` hasMany 關聯 in `app/Models/HighTicketLead.php`
- [x] T056 [P] 新模型：`lead()` belongsTo、`scopeAvailable()`（`lead_id IS NULL OR held_until <= now()`，FR-029 的唯一實作點）、`scopeUpcoming()` in `app/Models/ConsultationSlot.php`

Phase B — 時段服務與後台（相依 Phase A）
- [x] T057 新服務：`DEFAULT_MINUTES=30` / `BONUS_MINUTES=15` 常數；`generate(CarbonInterface $from, CarbonInterface $to): array{created,skipped}`（15 分鐘切分、已存在跳過）、`minutesFor(?string $code): int`（比對 `high_ticket_booking_bonus_codes`，忽略大小寫與空白，切分沿用 `HighTicketBookingService::parseRecipients()`）、`availableStarts(int $minutes): array`（只回連續 N 單位皆可用的起始時刻，依 FR-028）、`reserve(HighTicketLead $lead, CarbonInterface $startsAt, int $minutes, CarbonInterface $holdUntil)`（`lockForUpdate` + 先釋放該 lead 舊單位，衝突拋 `SlotUnavailableException`）、`release(HighTicketLead $lead)`、`confirm(HighTicketLead $lead)`（`held_until` 清 null）。台北時區轉換收在此（D32）in `app/Services/ConsultationSlotService.php`
- [x] T058 [P] 後台 controller：`index()` 依日期分組列出單位與佔用者（eager load lead 避免 N+1）、`store()` 呼叫 `generate()` 並 flash「已新增 N 個、略過 M 個」、`destroy()` 僅允許刪未佔用單位（否則 422）in `app/Http/Controllers/Admin/ConsultationSlotController.php`
- [x] T059 [P] Form Request：`date` 必填且不得早於今天、`start_time` / `end_time` 為 `H:i` 且須落在 15 分鐘刻度、`end_time` 須晚於 `start_time`，中文錯誤訊息 in `app/Http/Requests/Admin/StoreConsultationSlotsRequest.php`
- [x] T060 後台頁：依日期分組的時段格狀檢視（可預約 / 暫留中 / 已預約三色）、新增表單、刪除確認；佔用者姓名連往 `/admin/high-ticket-leads?search={email}`；RWD + 所有可點元素 `cursor-pointer` in `resources/js/Pages/Admin/ConsultationSlots/Index.vue`
- [x] T061 [P] 路由：staff 群組內加 `/admin/consultation-slots` 的 index / store / destroy（touchpoint，owner 000-platform-core）in `routes/web.php`

Phase C — 申請送出與確認流程（相依 Phase B）
- [x] T062 Form Request：FR-027 的全部欄位規則 + `commitments` 須為長度 5 且全 true 的陣列 + `slot_starts_at` 必填且為合法時刻 + `code` 選填，中文錯誤訊息 in `app/Http/Requests/HighTicketBookingRequest.php`
- [x] T063 `book()` 改寫為兩段式的第一段：改名 `apply()` —— 檢查 `high_ticket_booking_verify` 模板（缺則 422）→ `DB::transaction`（`recordLead()` 寫入問卷欄位 + `commitments_accepted_at` + `confirm_token` + `confirm_expires_at` → `ConsultationSlotService::reserve()`）→ transaction 外寄待確認信 → 寄失敗則 `release()` 並回 `mail_sent: false`（D34）。**移除**此處的 `checkAndBook()` 與 Meta CAPI 呼叫（改到確認階段，D35）in `app/Services/HighTicketBookingService.php`
- [x] T064 新增 `confirm(string $token): array` —— 依 token 查 lead，分四種結果（有效 / 已確認冪等 / 逾時 / 不存在）；有效時 `DB::transaction`（`confirmed_at` + `ConsultationSlotService::confirm()`），transaction 外依 Zoom 是否啟用分岔寄信（FR-038）、`checkAndBook()`、Meta CAPI in `app/Services/HighTicketBookingService.php`
- [x] T065 [P] `store()` 改用 `HighTicketBookingRequest`，回應加 `hold_expires_at`；新增 `slots()` 回 `{minutes, code_applied, slots[]}` in `app/Http/Controllers/HighTicketBookingController.php`
- [x] T066 [P] 新 controller：`show(string $token)` 回 Inertia `Booking/Confirm` 頁，props 帶 `state`（confirmed / already / expired / invalid）與課程名、時段 in `app/Http/Controllers/BookingConfirmController.php`
- [x] T067 [P] 確認結果頁：四種 state 各一段文案，confirmed 顯示「確認已完成預約，相關資料已寄出，建議在諮詢時間以前看完」，expired 附回課程頁連結 in `resources/js/Pages/Booking/Confirm.vue`
- [x] T068 [P] 路由：`GET /course/{course}/booking-slots`（throttle:30,1）、`GET /booking/confirm/{token}`（公開）（touchpoint，owner 000-platform-core）in `routes/web.php`

Phase D — 前台四步驟精靈（相依 Phase C）
- [x] T069 新元件：四步驟 state 機（資料 → 承諾 → 時段 → 覆核）、進度指示、問卷欄位、FR-026 的五條承諾、優惠碼輸入與時段查詢、覆核區與不出席警語、送出與待確認提示（含 1 小時倒數）；422 / 409 分別處理（409 自動重查時段）in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T070 移除既有的一步式預約表單與其 script 區塊（`bookingName` / `bookingEmail` / `submitBooking` 等），改掛 `<HighTicketBookingWizard :course="course" />`；`bookingSuccess` 相關分支一併清掉（touchpoint，owner 002-storefront）in `resources/js/Pages/Course/Show.vue`
- [x] T071 [P] Leads 名單每列可展開顯示問卷答覆與 Zoom 連結；舊資料欄位為 null 時顯示「—」in `resources/js/Components/Admin/Leads/BookingListTab.vue`

Phase E — Email 模板與排程（可與 Phase D 平行）
- [x] T072 [P] seeder 加第 6 筆 `high_ticket_booking_verify`（name「客製服務預約待確認」），文案須含確認連結、所選時段與「1 小時內」期限 in `database/seeders/EmailTemplateSeeder.php`
- [x] T073 [P] `$availableVariables` 加 `high_ticket_booking_verify` 五個變數，`high_ticket_booking_confirmation` 補 `{{slot_time}}` / `{{consult_minutes}}` / `{{zoom_join_url}}` in `app/Http/Controllers/Admin/EmailTemplateController.php`
- [x] T074 [P] `eventTypeLabels` 加 `high_ticket_booking_verify: '客製服務預約待確認'` in `resources/js/Pages/Admin/EmailTemplates/Index.vue`
- [x] T075 [P] 新 command `booking:release-holds`：把 `held_until <= now()` 的單位 `lead_id` / `held_until` 清空；註解須寫明這只是資料整理，正確性來自 lazy 判定（FR-035）in `app/Console/Commands/ReleaseExpiredBookingHolds.php`
- [x] T076 [P] 排程 `booking:release-holds` 每 10 分鐘（touchpoint，owner 000-platform-core）in `routes/console.php`

Phase F — Zoom 串接（US12，相依 Phase C）
- [x] T077 新服務：`isEnabled()`（三個設定皆非空）、`token()`（`account_credentials` grant + Basic auth，`Cache::remember` 55 分鐘、鍵含 client_id）、`createMeeting(CarbonInterface $startsAt, int $minutes, string $topic): array{meeting_id, join_url}`；全程 `Http::` facade 以利 fake in `app/Services/ZoomMeetingService.php`
- [x] ~~T078 [P] 新 Job `CreateZoomMeetingJob`~~ **已由 T130 移除（D55，改為同步）**；行為（建會議 → 寫回 lead → 寄信、失敗仍寄出 fallback 文案）保留於 `HighTicketBookingService::createMeetingAndConfirm()`
- [x] T079 [P] 「API 設定」頁後端加三個 Zoom 欄位：`zoom_account_id` / `zoom_client_id` 為一般欄位，`zoom_client_secret` 併入既有 `$secretFields` 陣列（留白即維持原值）（touchpoint，owner 000-platform-core）in `app/Http/Controllers/Admin/SettingsController.php`
- [x] T080 [P] 「API 設定」頁加「Zoom 會議」設定卡，版面比照既有金流 / Meta CAPI 卡（touchpoint，owner 000-platform-core）in `resources/js/Pages/Admin/Settings/Payment.vue`

Phase G — 驗證
- [x] T081 新增測試：四步驟送出建立 lead 與暫留時段、缺 verify 模板回 422 且不建 lead、承諾未全勾回 422、必填欄位驗證、待確認信寄失敗時 lead 留存但時段釋出 in `tests/Feature/HighTicket/BookingWizardTest.php`
- [x] T082 [P] 新增測試：並發搶同一時段只有一人成功（409）、逾時後單位可被他人選走、確認冪等不重複寄信、確認前不寄 confirmation 信 / 不送 CAPI、優惠碼命中變 3 單位、連續單位不足的起始時刻不出現在清單、Zoom 未設定時同步寄信、Zoom 成功時信中含連結、Zoom 三次失敗仍寄出 fallback 文案（`Http::fake`）in `tests/Feature/HighTicket/SlotHoldTest.php`
- [x] T083a 補 US10 驗收「沒有可預約時段」的候補路徑：`waitlist()` + `POST /course/{course}/waitlist`（伺服器端擋「其實有時段」的情況），精靈空狀態改為可送出 in `app/Services/HighTicketBookingService.php`
- [x] T083b 補 US9 驗收「重新申請預填既有問卷」：改由 `CourseController@show` 傳 `bookingDraft` prop，**只給已登入且為該 lead 本人**（避免以 email 探測是否申請過）in `app/Http/Controllers/CourseController.php`
- [x] T083c 新增 Zoom 測試檔（US12 全部驗收，`Http::fake`）in `tests/Feature/HighTicket/ZoomMeetingTest.php`
- [x] T083d 手機號碼帶進會員資料（FR-041 / D43）：`users.phone` 與 `orders.buyer_phone` 加寬為 varchar(30)、兩個 lead→member 轉換點帶入 `phone`、`UpdateProfileRequest` / `UpdateMemberRequest` / `CheckoutRequest` 的 `max:20` 一併改 30
- [x] T083 `php artisan test` 全綠（基準 271 passed；既有 `BookingMailFailureTest` / `BookingLeadRecordTest` 在 `book()` → `apply()` 改名與流程兩段化後必須同步改寫並通過）＋ `npm run build` exit 0
- [ ] T084 使用者以瀏覽器實測：四步驟流程、優惠碼延長、時段搶位、Email 確認連結、逾時釋出、Zoom 會議實際建立且連結可用

Phase H — 候補回訪連結（US10 追加，FR-042 / D44）
- [x] T085 migration：`high_ticket_leads` 加 `resume_token` char(64) unique nullable in `database/migrations/2026_08_05_000004_add_resume_token_to_high_ticket_leads_table.php`
- [x] T086 `waitlist()` 產生 `resume_token`（既有值沿用，使已寄出的連結不失效）；`$fillable` 補欄位 in `app/Services/HighTicketBookingService.php`
- [x] T087 [P] 通知信加 `{{booking_url}}` = `/course/{slug}?resume={token}`；無 token 的 lead 在寄送當下補發（lazy，不做全表 backfill）in `app/Jobs/NotifyHighTicketSlotJob.php`
- [x] T088 [P] `$availableVariables` 補 `high_ticket_slot_available` 三個變數 in `app/Http/Controllers/Admin/EmailTemplateController.php`；seeder 內文改寫 in `database/seeders/EmailTemplateSeeder.php`；資料 migration 附加至正式站既有模板 in `database/migrations/2026_08_05_000005_add_booking_url_to_slot_available_template.php`
- [x] T089 `bookingDraft()` 改吃 `?resume=`：token 命中且課程相符即回完整 draft（含姓名 / Email / `resume: true`），不要求登入；既有的「登入者本人」路徑保留 in `app/Http/Controllers/CourseController.php`
- [x] T090 [P] 精靈依 `draft.resume` 開在第 3 步、承諾預設全勾、掛載即查時段，並顯示「歡迎回來」提示條 in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T091 新增 10 個測試：候補產 token、重複候補沿用同一 token、通知信含深連結、無 token 者寄送時補發、未登入持 token 可預填、問卷不全者退回第 1 步、優惠碼帶回、無效碼不帶回、錯誤 token 不預填、跨課程 token 無效 in `tests/Feature/HighTicket/BookingWizardTest.php`

### 諮詢時段週曆化操作（US13）

Phase A — 後端週資料與批次收回（前端全部相依於此）
- [x] T092 `weekView(?string $week = null): array` —— 以台北時間求該週一 00:00 ~ 週日 24:00，轉 UTC 撈該區間全部單位（eager load lead 避免 N+1）；回傳 `week`（start / end / label / prev / next / is_current）、`range`（依實際資料撐開的起訖，預設 08:00–22:00）、`days[]`（每天含 `free[]` 可預約時刻與 `bookings[]` 聚合區塊）。同 lead 且 `starts_at` 相隔恰 15 分鐘者併為一塊（D46）in `app/Services/ConsultationSlotService.php`
- [x] T093 `releaseRange(CarbonInterface $from, CarbonInterface $to): array{released, skipped}` —— 只刪未佔用單位，佔用者計入 skipped；與既有 `destroy()` 共用 `ConsultationSlot::isAvailable()` 判定（FR-045）in `app/Services/ConsultationSlotService.php`
- [x] T094 [P] Form Request：`date` / `start_time` / `end_time` 規則比照 `StoreConsultationSlotsRequest`（15 分鐘刻度、end > start），但**不擋過去日期**（收回舊時段是合理操作），中文錯誤訊息 in `app/Http/Requests/Admin/DestroyConsultationSlotsRequest.php`
- [x] T095 `index(Request)` 改回傳 `weekView()` 的結果（`?week=` 缺省或不合法取本週）；新增 `destroyRange(DestroyConsultationSlotsRequest)` 呼叫 `releaseRange()` 並 flash「已收回 N 個、略過 M 個（已被預約）」；既有 `store()` / `destroy()` 不動 in `app/Http/Controllers/Admin/ConsultationSlotController.php`
- [x] T096 [P] 路由：staff 群組加 `DELETE /admin/consultation-slots`（批次，置於單筆 `{slot}` 路由**之前**避免被吃掉）（touchpoint，owner 000-platform-core）in `routes/web.php`

Phase B — 週曆格線元件（相依 Phase A）
- [x] T097 新元件：格線渲染（列 = 15 分鐘、欄 = 天）、四狀態配色與圖例、預約區塊以 `row-span` 跨格顯示暱稱 / 起訖 / Zoom 連結 / 暫留到期；以 Pointer Events（`pointerdown` + `window` 上的 `pointermove`/`pointerup`/`pointercancel`）實作拖曳選取，滑鼠與觸控共用一條路徑，起點決定意圖（D45），已佔用與過去的格不可起手；`sm` 以下切為單日單欄 in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T098 頁面改寫：移除「日期 + 起訖時間」表單與日期分組方塊清單，改掛 `<WeekGrid>`；上一週 / 本週 / 下一週切換走 `router.get` + `preserveScroll`；拖曳完成後 `router.post` / `router.delete` 送出並保留捲動位置 in `resources/js/Pages/Admin/ConsultationSlots/Index.vue`

Phase C — 驗證
- [x] T099 新增測試：`weekView()` 的週邊界與台北時區換算、範圍外既有時段撐開 `range`、同 lead 連續單位併為一個 booking、不連續者不併、`?week=` 不合法時退回本週 in `tests/Feature/HighTicket/ConsultationSlotAdminTest.php`
- [x] T100 [P] 新增測試：批次收回只刪未佔用者並正確回報 released / skipped、區間內已確認的單位收不掉、批次端點需 staff 權限 in `tests/Feature/HighTicket/ConsultationSlotAdminTest.php`
- [x] T101 `php artisan test` 全綠（基準 323 passed）＋ `npm run build` exit 0
- [x] T103 補 FR-031 的後台介面缺口：優惠碼原本只存在 `site_settings`，沒有任何填寫畫面（只能 tinker）。「諮詢時段」頁加設定欄位 + `updateSettings()` + `PUT /admin/consultation-slots/settings` + Form Request，儲存時以 `parseRecipients()` 正規化；新增 5 個測試 in `app/Http/Requests/Admin/UpdateConsultationSettingsRequest.php`
- [ ] T102 使用者以瀏覽器實測：桌機拖曳釋出 / 拖曳收回 / 拖過已預約格、手機單日檢視的觸控拖曳、週切換、Zoom 連結可點

### 行事曆邀請與預約異動（US14）

Phase A — Schema（其餘全部相依於此）
- [x] T104 [P] `calendar_sequence`（unsignedTinyInteger default 0）+ `cancelled_at`（timestamp nullable）in `database/migrations/2026_08_06_000001_add_calendar_fields_to_high_ticket_leads_table.php`
- [x] T105 [P] status enum 擴為六值（加 `cancelled`），`Schema::change()` 帶完整列表並重述 `default('pending')`（FR-051）in `database/migrations/2026_08_06_000002_add_cancelled_to_high_ticket_leads_status.php`
- [x] T106 [P] `insertOrIgnore` 寫入兩筆新模板；同步登記於 seeder（D54）in `database/migrations/2026_08_06_000003_insert_booking_change_email_templates.php` + `database/seeders/EmailTemplateSeeder.php`

Phase B — `.ics` 產生器（可與 Phase C 平行）
- [x] T107 **測試先行**：`fold()` 對中文長行折行後可正確解回原字串、CRLF、`escapeText()` 處理 `\` `;` `,` 與換行、`DTSTART` 為 UTC `Z` 格式、`UID` 由 lead id 推導且穩定、`METHOD:CANCEL` 版本含 `STATUS:CANCELLED` 與 `ATTENDEE` in `tests/Feature/HighTicket/CalendarInviteTest.php`
- [x] T108 新 service：`invite(HighTicketLead, Course, CarbonInterface $startsAt, int $minutes, ?string $zoomUrl): string` 與 `cancellation(...): string`；private `uid()` / `fold()`（按 byte 累計、按 UTF-8 字元邊界斷）/ `escapeText()` / `utcStamp()`（FR-046 / FR-047 / D51）in `app/Services/CalendarInviteService.php`
- [x] T109 建構子加 `array $attachments = []`（Laravel `Attachment` 實例），`attachments()` 原樣回傳；MIME 由呼叫端決定（FR-053）in `app/Mail/TemplatedMail.php`
- [x] T110 `sendConfirmationMail()` 掛上 `.ics` 附件（`consultation.ics`，`text/calendar; charset=UTF-8; method=REQUEST`）—— Zoom 成功與失敗兩條路徑都經過這裡，附件邏輯只寫一次 in `app/Services/HighTicketBookingService.php`

Phase C — 改期／取消後端（相依 Phase A）
- [x] T111 `reserve()` 的 `$holdUntil` 放寬為 `?CarbonInterface`，null 即新單位直接落為已確認佔用（`held_until = null`）；既有呼叫端不受影響（FR-048）in `app/Services/ConsultationSlotService.php`
- [x] T112 `updateMeeting(string $meetingId, CarbonInterface $startsAt, int $minutes): void`（`PATCH /v2/meetings/{id}`）與 `deleteMeeting(string $meetingId): void`（`DELETE`，404 視同成功）（FR-050 / D52）in `app/Services/ZoomMeetingService.php`
- [x] ~~T113 新 Job `SyncZoomMeetingJob`~~ **已由 T130 移除（D55，改為同步）**；行為保留於 `HighTicketBookingService::syncZoom()`，失敗改為 flash 警告而非內部通知信
- [x] T114 **測試先行**：`reschedule(HighTicketLead, CarbonInterface): array` —— 只對已確認且未取消的 lead 生效、transaction + `lockForUpdate`、撞車回 409、自我重疊可通過、`calendar_sequence` +1、寄改期信附新 ics、派送 Zoom PATCH job；`cancel(HighTicketLead): array` —— 釋出單位、`cancelled_at` / status 落地、清空 zoom 欄位、寄取消信附 `METHOD:CANCEL` ics、派送 delete job in `app/Services/HighTicketBookingService.php` + `tests/Feature/HighTicket/BookingChangeTest.php`
- [x] T115 `reschedule(RescheduleBookingRequest, HighTicketLead)` 與 `cancelBooking(HighTicketLead)` 兩個 action（thin，只轉呼叫 service 並 flash）；`recordLead()` 的復活狀態清單加 `cancelled`（FR-049）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T116 [P] Form Request：`slot_starts_at` required + `date` + 15 分鐘刻度 + 不得早於現在，中文錯誤訊息 in `app/Http/Requests/Admin/RescheduleBookingRequest.php`
- [x] T117 [P] 路由：staff 群組加 `PUT /admin/high-ticket-leads/{lead}/booking`（改期）與 `DELETE /admin/high-ticket-leads/{lead}/booking`（取消）（touchpoint，owner 000-platform-core）in `routes/web.php`

Phase D — 週曆 UI（相依 Phase C）
- [x] T118 預約區塊加「改期」／「取消」兩顆按鈕（`cursor-pointer` + hover）；改期模式：該區塊 highlight、格線只讓能容納 N 個連續可用單位的起始格可點（其餘變灰不可點）、點選後跳確認顯示「舊時間 → 新時間」、Esc 或再點一次退出；取消跳二次確認 modal（D53）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T119 承接 `@reschedule` / `@cancel` 事件送出請求（`preserveScroll`）；頁尾註腳「已被預約的時段要先到 Leads 名單處理」改寫為指向區塊上的按鈕 in `resources/js/Pages/Admin/ConsultationSlots/Index.vue`
- [x] T120 [P] Leads 名單 `statusButtons` 加 `cancelled`（「取」，灰／紅色系，與 closed 可區分）；篩選 tab 隨之多一顆（沿用既有由 statusButtons 產生 tabs 的作法）in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T121 [P] `$availableVariables` 補兩個新 event_type 的變數清單（FR-052）in `resources/js/Pages/Admin/EmailTemplates/Edit.vue`

Phase E — 驗證
- [x] T122 `php artisan test` 全綠（基準 349 passed）＋ `npm run build` exit 0
- [x] T124 `updateStatus` 拿掉 `cancelled`，名單改為唯讀徽章（FR-054）in `app/Http/Controllers/Admin/HighTicketLeadController.php` + `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T134 第 4 步送出改為兩段式 + 10 秒倒數的 Email 覆核（FR-059）；送出後畫面（含候補）逐字印出收件地址 in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T131 「預約待確認」信改為寫死的 `BookingVerifyMail` + Blade（含純文字版），`apply()` 拿掉模板檢查；`high_ticket_booking_verify` 自 seeder／變數清單／後台標籤移除並以 migration 刪除既有列（FR-058）in `app/Mail/BookingVerifyMail.php` + `resources/views/emails/booking-verify*.blade.php` + `database/migrations/2026_08_06_000004_drop_booking_verify_email_template.php`
- [x] T133 全站掃除硬寫客服地址（FR-057）：藍新／統一金流的付款失敗訊息、付款成功頁、隱私政策、購買須知共 6 處；前台以 Inertia shared prop `supportEmail` 取用（touchpoint，owner 000-platform-core / 002-storefront / 005-checkout）；新增原始碼掃描測試把關 in `app/Http/Middleware/HandleInertiaRequests.php` + `app/Http/Controllers/Payment/*.php` + `resources/js/Components/Legal/*.vue` + `resources/js/Pages/Payment/Success.vue`
- [x] T132 客服信箱設定：`SiteSetting::SUPPORT_EMAIL_KEY` + `supportEmail()`、Email 模板頁新增欄位與 `PUT /admin/email-templates/support-email`、`{{support_email}}` 與 `{{app_url}}` 注入 `EmailTemplate` 渲染入口並附加於編輯頁變數清單（FR-057）；新增 SupportEmailTest（10 tests）in `app/Models/SiteSetting.php` + `app/Models/EmailTemplate.php` + `app/Http/Controllers/Admin/EmailTemplateController.php` + `resources/js/Pages/Admin/EmailTemplates/Index.vue`
- [x] T130 Zoom 與確認信改為同步執行（D55 / FR-050 / FR-056）：移除 `CreateZoomMeetingJob` 與 `SyncZoomMeetingJob`，邏輯內收至 `HighTicketBookingService::createMeetingAndConfirm()` 與 `syncZoom()`；`ZoomMeetingService` 每個呼叫加 8 秒 timeout + 2 次短重試；改期／取消失敗改由 flash 警告管理員（原本寄內部通知信）；ZoomMeetingTest 與 BookingChangeTest 改寫，新增「沒有 worker 也要寄得出確認信」的斷言 in `app/Services/HighTicketBookingService.php` + `app/Services/ZoomMeetingService.php` + `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T129 第 4 步覆核區改為分區呈現：「申請資料」7 列共用一顆修改（皆屬第 1 步）、「諮詢時段」自成一區另一顆（屬第 3 步）；原本每列一顆共 8 顆，其中 7 顆去同一個地方 in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T128 `social_url` 前端即時驗證（FR-027）：`new URL()` 判定、第 1 步擋下並顯示提示、第 4 步覆核列標紅 + 停用送出、候補按鈕同步；`submitWaitlist()` 的 422 欄位錯誤改與 `submit()` 共用 `routeFieldErrors()` in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T126 `.ics` 的 `DESCRIPTION` 移除「請直接回覆此信」—— 聯絡管道是業主在模板裡自訂的政策（線上模板寫的是「請勿直接回覆此信」），`.ics` 不該複製一份會漂移的版本 in `app/Services/CalendarInviteService.php`
- [x] T127 「加入序列信」拿掉狀態白名單（FR-007）；前端按鈕 gate 一併移除，確認 modal 加「已成交」提示；補 LeadSubscribeDripTest（5 tests，此路徑原本零覆蓋）in `app/Services/HighTicketLeadService.php` + `resources/js/Components/Admin/Leads/BookingListTab.vue` + `tests/Feature/HighTicket/LeadSubscribeDripTest.php`
- [x] T125 狀態顯示名稱改為面談語彙：待面談 / 已面談 / 未出席（FR-055，DB 值不動）；欄頭圖例同步 in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [ ] T123 使用者以瀏覽器實測：確認信收到後 `.ics` 在 Gmail / Apple Mail 顯示為可加入的邀請、改期後日曆自動更新為新時間（不是多一筆）、取消後日曆行程消失、Zoom 會議實際被改時間 / 刪除、中文標題在日曆中無亂碼

### 諮詢時段指派銷售顧問（US15）

Phase A — Schema（其餘相依於此）
- [x] T135 [P] `consultation_slots.consultant_id`（nullable, index）in `database/migrations/2026_08_07_000001_add_consultant_to_consultation_slots_table.php`
- [x] T136 [P] `high_ticket_leads.consultant_id`（nullable, index）+ model fillable / 關聯 `consultant()` in `database/migrations/2026_08_07_000002_add_consultant_to_high_ticket_leads_table.php` + `app/Models/HighTicketLead.php` + `app/Models/ConsultationSlot.php`

Phase B — 歸屬寫入與快照（相依 A）
- [x] T137 **測試先行**：`generate()` 帶入 `consultant_id`、顧問只能指派給自己（後端擋）、管理員可指定任一 staff、`confirm()` 把時段的 consultant 快照到 lead in `tests/Feature/HighTicket/ConsultantAssignmentTest.php`
- [x] T138 `generate()` 簽名加 `?int $consultantId`（`reserve()` 不需要 —— 它佔用既有的列，歸屬本來就在列上）；`confirm(HighTicketLead)` 快照 consultant 至 lead（FR-060 / FR-061）in `app/Services/ConsultationSlotService.php`
- [x] T139 `store()` 接受 `consultant_id`，Form Request 驗證「非管理員只能填自己」；新增 `PUT /admin/consultation-slots/bookings/{lead}/consultant` 改派既有預約 in `app/Http/Controllers/Admin/ConsultationSlotController.php` + `app/Http/Requests/Admin/StoreConsultationSlotsRequest.php`
- [x] T140 [P] 路由：staff 群組加改派端點（置於 `{consultationSlot}` 之前）（touchpoint，owner 000-platform-core）in `routes/web.php`

Phase C — CC 規則（可與 B 平行）
- [x] T141 **測試先行**：確認信 CC 客服 + 顧問、無顧問時只 CC 客服、待確認／改期／取消三封完全無 CC（FR-062）in `tests/Feature/HighTicket/ConsultantAssignmentTest.php`
- [x] T142 `sendConfirmationMail()` 的 CC 加入 lead 的顧問 Email；`sendVerifyMail()` 與 `sendChangeMail()` 移除 `->cc()` in `app/Services/HighTicketBookingService.php`

Phase D — Zoom 主持人（可與 B/C 平行）
- [x] T143 `createMeeting()` 加選填 `?string $hostEmail`，指定時打 `/users/{email}/meetings`，404 時 fallback `me` 並記 warning（FR-063 / D60）in `app/Services/ZoomMeetingService.php`
- [x] T144 建會議時帶入 lead 的顧問 Email；API 設定頁補一段說明「顧問需有 Zoom 席次，否則會議仍建在擁有者帳號下」in `app/Services/HighTicketBookingService.php` + `resources/js/Pages/Admin/Settings/Payment.vue`

Phase E — 後台 UI（相依 B）
- [x] T145 週曆頁加「時段歸屬」選擇器（管理員可選任一 staff、顧問鎖定自己），拖曳建立時帶入；空格 `title` 顯示歸屬 in `resources/js/Pages/Admin/ConsultationSlots/Index.vue` + `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T146 預約區塊的面板顯示顧問，管理員可切換改派 in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T147 [P] Leads 名單預約 tab 顯示負責顧問欄（無指派顯示「—」）in `resources/js/Components/Admin/Leads/BookingListTab.vue` + `app/Http/Controllers/Admin/HighTicketLeadController.php`

Phase F — 驗證
- [x] T148 `php artisan test` 全綠（基準 411 passed）＋ `npm run build` exit 0
- [ ] T149 使用者以瀏覽器實測：以顧問身分登入建時段、管理員改派、預約後確認信的 CC 收件者、Leads 名單的顧問欄

### 阻擋重複預約與電話正規化（US16）

Phase A — 正規化（其餘相依於此）
- [x] T150 **測試先行**：`0912-345-678` / `0912 345 678` / `+886912345678` / `886912345678` 皆正規化為 `0912345678`；空值回 null；非台灣號碼退化為純數字 in `tests/Feature/HighTicket/DuplicateBookingTest.php`
- [x] T151 新 helper：`normalise(?string): ?string`（FR-064 / D62）in `app/Support/PhoneNumber.php`
- [x] T152 `prepareForValidation()` 於驗證前正規化 `phone`，使驗證／儲存／比對看到同一值 in `app/Http/Requests/HighTicketBookingRequest.php`
- [x] T153 [P] migration：`high_ticket_leads.phone` 加 index；以 `chunkById` 就地正規化（只寫真的有變的列） `high_ticket_leads.phone` 與 `users.phone`（`orders.buyer_phone` 不動）in `database/migrations/2026_08_08_000001_index_and_normalise_phones.php`

Phase B — 擋門（相依 A）
- [x] T154 **測試先行**：四種擋下狀態各一、三種放行狀態各一、換 Email 同電話被擋、換電話同 Email 被擋、**被擋時既有 lead 與時段完全未被更動**、訊息含顧問 Email、無顧問時含客服信箱 in `tests/Feature/HighTicket/DuplicateBookingTest.php`
- [x] T155 `blockingLead(Course, string $email, ?string $phone): ?HighTicketLead` + `apply()` / `waitlist()` 於最前面呼叫並回 422（FR-065 / FR-066）；狀態判斷必須先於 `confirmed_at`，否則 `closed` 會被一起擋掉 in `app/Services/HighTicketBookingService.php`

Phase C — 前台
- [x] T156 送出失敗時原樣顯示後端訊息（目前 422 會被吃成泛用文案），並在 Email 覆核區塊之後、第 4 步的錯誤位置呈現 in `resources/js/Components/Course/HighTicketBookingWizard.vue`

Phase D — 驗證
- [x] T159 既有 lead 狀態一次性重新歸類：`contacted`→`pending`、`no_response`→`cancelled`（FR-055）；不可逆但可重跑，附 5 個測試 in `database/migrations/2026_08_08_000002_realign_lead_statuses_to_consultation_vocabulary.php` + `tests/Feature/HighTicket/LeadStatusRealignTest.php`
- [x] T157 `php artisan test` 全綠（基準 427 passed）＋ `npm run build` exit 0
- [ ] T158 使用者以瀏覽器實測：已確認預約者重複申請被擋且訊息指名顧問、換 Email 同電話被擋、取消後可重新預約

US3 補充
- [x] T160 狀態 tab 顯示漏斗佔比（FR-067）：後端 `GROUP BY status` 聚合（與列表共用同一組搜尋 / 課程篩選、不含狀態篩選），前端算百分比並以 `title` 附實際筆數 in `app/Http/Controllers/Admin/HighTicketLeadController.php` + `resources/js/Pages/Admin/HighTicketLeads/Index.vue` + `resources/js/Components/Admin/Leads/BookingListTab.vue` + `tests/Feature/HighTicket/LeadsTabsTest.php`

US9 補充
- [x] T161 Leads 名單展開列拿掉「預約優惠碼」，改顯示已預約時段（取該 lead `slots` 首尾單位換算的起訖時間，格式「2026/8/8 14:00-15:45」；候補中無時段則不顯示此列）in `app/Http/Controllers/Admin/HighTicketLeadController.php`（`index()` 的 booking leads 查詢補 eager load `slots:id,lead_id,starts_at`）+ `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T162 展開列「Email 確認時間」後新增「序列信起始時間」：`dripByEmail` 的 map 補回 `subscribed_at`（目前只吐 `course_name` / `status`）；前端取該 email 名下所有訂閱中最早的 `subscribed_at`，以日曆天差算「經過 N 天」並與時間一併顯示；無任何序列信訂閱則不顯示此列 in `app/Http/Controllers/Admin/HighTicketLeadController.php` + `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T164 修正 T162 的天數終點：改比較到 `lead.confirmed_at`（已確認時），未確認才 fallback 比較到今天 —— 原本恆比較到今天，已確認的 lead 天數會隨查看日期持續增加，不是固定值 in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T163 補測試：booking leads 帶出 `slots`（eager load 生效）、`dripByEmail` 帶出 `subscribed_at` in `tests/Feature/HighTicket/LeadsTabsTest.php`

US13 補充
- [x] T165 `ConsultationSlotService::statusCounts(): array` 回傳 `['available' => int, 'held' => int, 'booked' => int]`（D65）：available 用 `scopeAvailable()->upcoming()`；held 用 `whereNotNull('held_until')->where('held_until', '>', now())->upcoming()`；booked 用 `whereNotNull('lead_id')->whereNull('held_until')`（不加 upcoming）in `app/Services/ConsultationSlotService.php`
- [x] T166 [P] `index()` 把 `statusCounts()` 併入 Inertia props in `app/Http/Controllers/Admin/ConsultationSlotController.php`
- [x] T167 [P] `LEGEND` 三個真實狀態旁顯示對應數量，「未釋出」維持純視覺不接數字 in `resources/js/Pages/Admin/ConsultationSlots/Index.vue`
- [x] T168 測試：可預約排除過去未被訂走的列、暫留排除過去的過期保留、已預約含過去已完成的列 in `tests/Feature/HighTicket/ConsultationSlotAdminTest.php`

US14 補充
- [x] T169 `selectBooking()` 收 `$event`，記錄 `event.currentTarget` 為 `anchorEl`；新增 `panelPos`（`{ left, top, flip }`）與 `updatePanelPos()`：以 `anchorEl.getBoundingClientRect()` + 面板自身 `offsetWidth` 算水平置中並夾在 `[8, innerWidth - 8]`；上方空間 < 80px 時 `flip = true`（改貼區塊下方）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T170 選取／改期面板改為 `<Teleport to="body">` + `position: fixed`（`left`/`top` 綁 `panelPos`，`transform: translate(-50%, ...)` 依 `flip` 切換方向）；面板打開期間（`onMounted`）掛 `scroll`（`capture: true`）與 `resize` 監聽器即時重算，`onUnmounted` 一併移除；`updatePanelPos()` 內部以 `selected`/`rescheduling` 皆為空時提早返回，不需另外在 `clearSelection()` 卸載監聽器（監聽器本身常駐、只是空轉）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`（實作時追加 `Teleport` 到 `<body>`：面板疊在整個行事曆之上，`z-40` 若留在元件內仍可能被頁面上其他 stacking context 蓋住，teleport 到 body 讓它必定在最上層，且與 `position: fixed` 的視窗定位互不衝突）
- [x] T171 `syncNarrow()`（單日／週檢視切換）額外呼叫 `clearSelection()`（D66：版面切換後錨點元素可能已被置換，收起面板比追蹤舊節點簡單）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T172 使用者以瀏覽器實測：選取遠離頁面頂端的預約區塊、捲動頁面時面板跟著移動、視窗邊緣與最上排區塊時的夾擠／翻轉是否符合預期、手機單日檢視切換時面板正確收起
- [x] T173 面板改期／取消預約／關閉三顆按鈕包成同一個 `flex items-center gap-2` 子容器（不再各自獨立參與外層 `flex-wrap`），外層空間不足時整組一起換行，不會斷在「改期」與「取消預約」中間（使用者回報）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T174 面板換行後右側留白（使用者回報）：兩個面板外層容器由 `flex` 改 `inline-flex`——`position: fixed` 的外層 `panelRef` 本身是 auto 寬，內層若是 `flex`（block-level）會反過來撐滿外層算出的寬度，兩個 auto 寬互相依賴時瀏覽器的 shrink-to-fit 計算會用「未換行前」的最大內容寬，換行後兩行實際內容都比容器窄，右側留白。`inline-flex` 讓內層自己以 shrink-to-fit（依照換行後的實際版面）量寬，外層再照內層量到的寬度收邊 in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T175 Zoom 會議 `topic` 由 `"{$course->name} 1v1 諮詢 - {$lead->name}"` 簡化為 `"{$lead->name} 諮詢"`（業主回報名稱太長）in `app/Services/HighTicketBookingService.php`
- [x] T176 第 4 步「送出申請」按鈕正上方加一行小字「1v1 諮詢將依名額安排，由創辦人或團隊專業顧問提供服務。」（`text-xs text-gray-500`）in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T177 承諾清單三條文案改寫（FR-026）；三個 `<label>` 包進獨立 `space-y-2` 容器，脫離外層 `space-y-4` 縮小選項間距（業主回報）in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T178 `availableStarts()` 依 FR-069 篩選：`$minutes >= 45` 時只保留台北時間分鐘數為 0 或 30 的起始 in `app/Services/ConsultationSlotService.php`
- [x] T179 新增測試：45 分鐘起始清單不含 `:15`/`:45`、30 分鐘起始清單不受影響、篩選發生在連續性判定之後 in `tests/Feature/HighTicket/SlotHoldTest.php`
- [x] T180 `CalendarInviteService::build()` 的 `SUMMARY` 由 `"{$course->name} 1v1 諮詢 - {$lead->name}"` 改為 `"{$lead->name} 諮詢"`，與 T175 的 Zoom `topic` 同步（FR-046）in `app/Services/CalendarInviteService.php`

## 進度日誌

- 2026-08-08: 修正 .ics SUMMARY 遺漏的名稱簡化（T180 / FR-046）— 8/7 簡化 Zoom 會議 topic（T175）時漏改 `CalendarInviteService::build()` 的 `SUMMARY`，導致確認信附件的 `.ics` 加進 Google Calendar 等日曆 app 後，行程標題仍是長版「{課程名} 1v1 諮詢 - {姓名}」。業主回報後查證：SSH 到正式站直接打 Zoom API 核對最近 8 筆會議的實際 topic，確認 Zoom 端本身在 8/7 15:40 部署後即完全正確；問題單獨出在 `.ics` 的 `SUMMARY` 欄位從未跟著改。TDD：先寫斷言 `SUMMARY:王小明 諮詢` 確認紅（實際是長版），改一行後綠；連帶修正一個因此變得過時的既有測試 `test_folded_chinese_survives_a_round_trip`——它原本斷言 `$course->name` 會出現在 `.ics` 內容中，但這正是本次改動要拿掉的東西，改為只驗證長姓名 folding 後仍完整往返。全套 528 passed，純後端改動。
- 2026-08-07: 45 分鐘場起始時間限制整點／半點（T178/T179 / FR-069）— 業主回報 45 分鐘（優惠碼延長）場次不該出現 `20:15`/`20:45` 這種起始時間，跟顧問既有整點／半點的行事曆習慣對不上。`availableStarts()` 在既有的「N 個連續單位皆可用」判定之後，多一層依台北時間分鐘數（0/30）過濾，只在 `$minutes >= 45` 時生效；30 分鐘預設場次不受影響。TDD：先寫兩個測試（45 分鐘濾掉 `:15`、30 分鐘不受限）確認前者紅（未過濾時回傳 `10:00/10:15/10:30`），加篩選後綠。全套 526 passed，純後端改動。
- 2026-08-07: 承諾清單文案改寫＋間距調整（T177 / FR-026）— 三條文案改為「我有明確想改善的問題…」「我願意接受務實建議…」「如果確認方向適合…」；業主回報三個選項之間縫隙太大（因為外層 `space-y-4` 把選項間距、標題到清單的段落間距混在一起），把三個 `<label>` 包進獨立 `space-y-2` 容器脫離外層間距。純文案／樣式，未寫測試。
- 2026-08-07: 第 4 步送出前加名額說明小字（T176）— 覆核區送出按鈕上方加一行「1v1 諮詢將依名額安排，由創辦人或團隊專業顧問提供服務。」純文案樣式改動，未寫測試（純樣式／文案可略過 TDD）。`npm run build` 綠、全套 523 passed。
- 2026-08-07: Zoom 會議 `topic` 簡化（T175）— 業主回報原本「課程名 + 1v1 諮詢 + 申請人姓名」太長，改為「申請人姓名 諮詢」。TDD：先在 `ZoomMeetingTest::test_meeting_request_carries_the_slot_time_and_length` 加 `topic` 斷言確認紅，再改 `HighTicketBookingService::createMeetingAndConfirm()` 一行後綠。全套 523 passed，不涉及前端。
- 2026-08-07: 修正懸浮面板換行後的留白（T174）— 業主回報按鈕換到第二行後，面板右側留下一大塊詭異的空白。根因是 `position: fixed` 的外層容器是 auto 寬，內層 `flex` 容器是 block-level 元素會撐滿外層，兩個「auto 寬互相依賴」的框在瀏覽器算 shrink-to-fit 時是用換行前（單行）的最大內容寬去定外層寬度，換行後兩行實際內容都比這個寬度窄，就留白在右邊。內層改 `inline-flex` 後自己先以換行後的實際版面量寬，外層才不會抓錯數字。純樣式調整，`npm run build` 綠、全套 523 passed。
- 2026-08-07: 修正懸浮面板的按鈕換行（T173）— 業主回報「改期／取消預約」剛好斷行在中間，很怪異。外層是 `flex-wrap`，三顆按鈕各自是獨立的 flex item，換行時是逐顆換而非整組換。包成一個不換行的子容器後，三顆按鈕永遠一起出現在同一行，空間不足時整組一起掉到下一行。純樣式調整，`npm run build` 綠、全套 523 passed（不涉及邏輯，未新增測試）。
- 2026-08-07: 諮詢時段頁兩項體驗修正（T165–T171 / D65 / D66）。(1) 圖例旁補上三個真實狀態的**全站累計**數量：`ConsultationSlotService::statusCounts()` 用三條獨立 count 查詢，可預約／暫留中只算未來（`scopeUpcoming`），已預約不分過去未來全部算（=總共成交幾場）；「未釋出」代表未來無限時間、沒有天然分母，刻意不接數字。(2) 選取預約區塊跳出的改期／取消面板，原本固定在行程表右上角，頁面捲到下方就看不到——改為 `position: fixed` + `getBoundingClientRect()` 追蹤區塊、`<Teleport to="body">` 確保疊在最上層，開啟期間掛 `scroll`（capture）/`resize` 監聽器即時重算；水平置中並夾在視窗邊界內，上方空間不足自動翻到區塊下方。選 `position: fixed` 而非把面板做成區塊的絕對定位子元素，是因為行事曆包在 `overflow-x-auto` 容器裡，CSS 規則會讓垂直方向也一併被裁切，面板往上浮在較前面幾列會直接消失。手機單日／週檢視切換時面板直接收起（版面切換後錨點 DOM 節點可能已被置換）。全套 523 passed、`npm run build` 綠。T172 業主瀏覽器實測通過。
- 2026-08-07: 承諾清單由五條縮為三條（FR-026）— 刪掉「已有想經營的主題/方向」與「認真評估發展收入」，第三條改寫為「我已有初步想法，願意投入時間學習並持續執行，而不是隨意了解。」把兩者的意思併進來。`HighTicketBookingRequest` 的 `commitments` 由 `size:5` 改 `size:3`（前端 disabled 只是禮貌，控制在後端），測試 fixture 同步。全套 522 passed、`npm run build` 綠。

- 2026-08-07: 修正「序列信起始時間」的天數計算（T164）——業主回報這個天數理應是「序列信起始到確認預約」的固定間隔，但原本（T162）一律拿「今天」當比較終點，導致已確認/已轉換的 lead 天數還在隨查看日期持續增加。改成：有 `confirmed_at` 就固定比較到那個時間點，尚未確認才 fallback 比較到今天。純前端計算修正，無需碰後端或資料庫。
- 2026-08-07: 本模組擁有的 `useEmailReview.js` 更名為 `useDelayedConfirm.js`（010 US16 / D22）—— 那支 composable 做的一直是「兩段式確認＋倒數」，010 的第三個用途（停止接收確認，5 秒）連 Email 都沒有。函式同步改名，API 與 `HighTicketBookingWizard` 的行為、秒數（10）完全不變；`EmailReviewNotice.vue` 維持原名，它確實只服務 Email 覆核那塊 UI。FR-059 的規則不受影響。

- 2026-08-06: Leads 展開列改版（T161–T163）— 「預約優惠碼」對後台沒有辨識價值（顧問只需要知道約在什麼時候），換成已預約時段（`slots` 首尾單位換算起訖）；另加「序列信起始時間」讓顧問一眼看出這個人被加溫多久，取該 email 名下所有序列信訂閱中最早的 `subscribed_at`、算日曆天差（不比時分，避免數字隨查看時刻抖動）。兩個值都跟著既有「有值才顯示」慣例，候補中無時段、從未加過序列信的 lead 不顯示對應列。新增 2 個測試（先紅後綠：拿掉 eager load / `subscribed_at` 欄位重跑確認會壞），全套 503 passed、`npm run build` 綠。
- 2026-08-05: /sync 對帳補登 —— `useEmailReview.js` 與 `EmailReviewNotice.vue`（US15 的 10 秒 Email 覆核，正典 FR-059）此前無任何模組宣告擁有，010 卻已在 touchpoints 指名 owner 為本模組。依該聲明補進 owner_files，全 repo 的 .php/.vue/.js 至此皆有唯一 owner。
- 2026-08-06: 逾時未確認的預約申請改為連同 lead 一起刪除（FR-068 / D63）— 原本只釋放時段、lead 留在名單，導致「填完問卷但沒確認」的人堆積在待面談。改由 `purgeExpiredApplications()` 在釋放時段後刪除，`booking:release-holds` 每 10 分鐘執行；已確認（含事後取消）、管理員動過狀態、候補名單三類一律保留。連帶把確認頁 `invalid` 文案改寫 —— 清掃後同一連結會落到該分支，舊文案要人「直接使用信件中的連結」，但對方正是這麼做的。新增 ExpiredApplicationPurgeTest（8 tests），全套 501 passed。查證後同步修正一項既有 spec 敘述：US11 原寫「lead 保留於名單」，已隨本次改動更新。
- 2026-08-05: 狀態 tab 加上漏斗佔比（T160 / FR-067）— 名單頁原本要看漏斗形狀得逐個狀態點進去、記下分頁總數再自己心算。百分比直接印在 tab 上，一眼看得出卡在哪一段。兩個決定值得記：（1）分母**不含狀態篩選** —— 若跟著篩選走，點進「待面談」會讓它變成 100%，數字反而騙人；（2）計數走後端 `GROUP BY`，不是拿當頁 20 筆算 —— 前端只看得到一頁，算出來的比例會隨翻頁跳動。搜尋 / 課程篩選則**要**吃進分母（「這門課的漏斗」是合理的問題），因此與列表共用同一個 query builder，避免兩邊條件日後漂移。四捨五入到 0 但實際有資料的顯示 `<1%`，免得剛起步的狀態看起來像沒有。
- 2026-08-05: 規劃 US15 諮詢時段指派銷售顧問（已審核，開始實作） — 顧問要能順手開自己的時段並在自己信箱收到成立的預約，才可能自己安排行程。`consultation_slots` 與 `high_ticket_leads` 各加一個 `consultant_id`，前者是「這段時間屬於誰」、後者是確認當下的**快照**（時段會被改派、取消還會釋放回池子，靠即時查詢會查到錯的人，D58）。**關鍵限制寫進 D57**：`starts_at` 仍是全域 unique，所以這是「一本共用行事曆上的歸屬標記」，不是每位顧問各一本 —— 兩位顧問無法同時開放同一時刻。改成每人一本要連帶決定「訪客自己挑顧問還是系統分配」（商業決定）並重做公開選時段流程，是另一個 US；目前系統有 0 位顧問，先做標記不擋日後升級。CC 規則同時從三封收斂成一封（D59）：改期與取消本來就是人與人寫信談出來的結果，系統再補一封只是噪音；代價是管理員不再即時收到「有人送出申請」，但那筆申請照樣在名單的 pending。Zoom 主持人做成選填 + 404 fallback（D60），業主之後才會買席次，現在指定一定 404 —— 做好 fallback 等於買了席次當天自動生效，且 404 記 warning 不記 error，免得過渡期的假警報淹掉真故障。顧問權限隔離（只看自己的時段與 leads）明確不做。
- 2026-08-05: 第 4 步加 Email 覆核防呆（T134 / FR-059）— 業主實測時把自己的信箱打成 `gosihnra@`（h/i 顛倒），等了一分鐘才發現收不到信。查下來系統一切正常：lead 建了、時段佔了、`Mail::send()` 沒丟例外、log 乾淨 —— 因為信**確實寄出去了**，只是寄到一個不存在的地址。這是最難查的一類失敗：每一層都回報成功，只有收件匣是空的，而申請人不會知道自己打錯，時段就被鎖到 1 小時後逾時。唯一能攔下它的時機是送出前，所以第一次按送出改為顯示大字體的 Email 覆核區塊並強制倒數 10 秒 —— 停頓的目的不是等待，是讓「再看一眼」真的發生。附「這個 Email 不對，回去修改」直接跳回第 1 步；離開步驟或改動 Email 都會重置。送出後的畫面（含候補路徑）也逐字印出收件地址並說明打錯的後果，讓人在還記得自己填了什麼的時候就能發現。
- 2026-08-05: 「預約待確認」信改為寫死 + 客服信箱改為設定值（T131 / T132 / FR-057 / FR-058 / D56）— 業主實測送出申請時撞到 422「預約待確認信模板不存在，請聯絡管理員」：seeder 不隨部署執行，所以**正式站的資料庫本來就沒有這一列**，等於整條申請路徑上線即是壞的。根本問題是可設定性放錯了東西 —— 這封信是機制不是訊息（唯一任務是送出確認連結，沒人會想改措辭），卻做成可編輯模板且缺列即中斷；反過來客服信箱是真的會換的東西，卻硬寫在六個地方。判斷準則寫進 D56：機制寫死、訊息留模板。改為 `BookingVerifyMail` + Blade（比照既有的 `VerificationCodeMail`），含純文字版（確認連結進垃圾桶等於預約斷掉）；到期時間帶日期，因為 23:45 送出的申請隔天 00:45 到期、「今天」是錯的。`high_ticket_booking_verify` 自 seeder／變數清單／後台標籤移除並以 migration 刪列 —— 留著會顯示成「可編輯但改了沒用」的模板，比沒有更糟。客服信箱新增 `site_settings.support_email` + Email 模板頁欄位，`{{support_email}}` 與 `{{app_url}}` 注入 `EmailTemplate` 的渲染入口而非各呼叫端（六個呼叫端漏一個的症狀是收件人看到字面的 `{{support_email}}`，寄出去才會發現）。全站另掃掉 6 處硬寫地址（金流失敗訊息、付款成功頁、隱私政策、購買須知），前台走 Inertia shared prop —— 法律條款 modal 掛在 footer，每一頁都可能要印，逐頁傳 prop 一定會漏。加了一個掃描原始碼的測試把關，否則下一個人複製貼上就又長回來。新增 SupportEmailTest（13 tests），全套 410 passed（2001 assertions）。
- 2026-08-05: Zoom 與確認信改為同步、移除兩支 job（T130 / D55 / FR-056）— 起因是覆核 Zoom 串接時發現 `QUEUE_CONNECTION=database`，而 D38 把確認信搬進了 `CreateZoomMeetingJob`：**正式站沒有 queue worker 的話，Zoom 啟用後確認信永遠不會寄出**，而畫面還是顯示「相關資料已寄出」。這是本模組唯一一條「訪客被告知成功、結果卻掛在 worker 上」的路徑，其餘 job 都是管理員觸發、看得到回報。改為同步後最差是頁面多等幾秒或信沒連結（log 有紀錄），兩者都是可發現的。代價是失去 `tries=3` 的重試，改以 HTTP 層 8 秒 timeout + 2 次短重試補一部分 —— Laravel 預設 30 秒 timeout 在同步情境會讓一次 Zoom 中斷把頁面卡住將近一分鐘。改期／取消的 Zoom 同步一併改同步（那兩條的信本來就同步寄，只有 Zoom 掛在 worker 上，等於對方拿到新時間、Zoom 停在舊時間而沒人發現）；`syncZoom()` 回傳三態，controller 只在真的失敗時於 flash 附警告，「本來就沒有會議」不跳警告以免訓練管理員忽略它。原 job 的內部通知信移除 —— 管理員當下就在畫面前。ZoomMeetingTest 改寫為同步版本並新增 `Queue::assertNothingPushed()` 斷言，全套 397 passed。
- 2026-08-05: 第 4 步覆核區的「修改」由每列一顆改為每區一顆（T129）— 原本 8 顆按鈕裡有 7 顆做同一件事（回第 1 步），視覺上像是每一欄都能單獨編輯，實際上全部通往同一個畫面。改為兩張卡片：「申請資料」（7 列，標題列一顆修改 → 第 1 步）與「諮詢時段」（自成一區，另一顆 → 第 3 步）。沒有併成單一顆，是因為這兩顆去的是不同步驟 —— 真的只留一顆的話，改時段會變成「修改 → 第 1 步 → 下一步 → 下一步」。社群網址格式錯誤時，「申請資料」那顆會轉紅粗體，指向唯一需要處理的地方。
- 2026-08-05: 社群網址改為前端即時驗證（T128 / FR-027）— 原本只有後端擋，使用者要一路填到第 4 步、按下「送出申請」才被丟回第 1 步，而「請再確認一次你的申請資料」那個畫面在那之前完全看不出哪一欄有問題（該列就只是顯示那串壞掉的網址）。改為：第 1 步即時提示且不放行、第 4 步覆核列標紅 + 附修正說明 + 停用送出鈕、候補路徑的按鈕同步處理。判定用 `new URL()` 不用 regex —— 瀏覽器同一套 parser，一次擋掉沒有 scheme 的 `instagram.com/me` 與 `javascript:` / `data:` / `ftp:`，逐一比對過後端那 4 個測試的案例。順手修掉一個既有 bug：`submitWaitlist()` 把 422 的欄位錯誤全壓成「送出失敗，請稍後再試」，那是一句永遠不會成功的建議、也沒說是哪一欄；兩條送出路徑改為共用 `routeFieldErrors()`。後端仍是權威（前端規則在單標籤 host 這類極端輸入上可能與 Laravel `url` 有落差），落差由共用的欄位錯誤處理接住。
- 2026-08-05: 「加入序列信」不再依狀態過濾（T127 / FR-007）+ `.ics` 移除聯絡管道指示（T126）— 序列信的狀態白名單會**靜靜吃掉勾選**：`已面談` / `已成交` / `已取消` 被無聲丟棄，勾 6 位只加了 3 位，前端按鈕還會在選到他們時變灰不說原因。改為完全由管理員勾選決定（比照 FR-006 的既有原則），真正的守門留給去重（active 訂閱即 skip）；唯一會尷尬的 `converted` 改為在確認 modal 提示而非擋下 —— 把既有客戶推向另一門課是合理操作，只有管理員分得出誤勾。此路徑原本**零測試覆蓋**，補 LeadSubscribeDripTest（5 tests）。另修一個 US14 帶進來的矛盾：`.ics` 的 `DESCRIPTION` 寫「需要改期或取消，請直接回覆預約確認信」，但線上那封確認信（業主 2026-08-04 編輯過）結尾寫的是「**請勿直接回覆此信**，請寄信到客服信箱」。D50 說「不做自助入口、靠文案告訴對方怎麼聯絡」，對著實際模板是失效的。`.ics` 改為中性的「如需改期或取消，請與我們聯繫」—— 聯絡管道是業主在可編輯模板裡設定的政策，`.ics` 不該複製一份會漂移的版本。
- 2026-08-05: 狀態顯示名稱改為面談語彙（T125 / FR-055）— US14 之後每一筆 lead 都是「約了 1v1 面談」，「待聯繫 / 已聯繫」描述的是錯的事件。改為 **待面談 / 已面談**，`no_response` 由「未回應」改為 **未出席**（no-show），取其中性標準（「爽約」帶指責、「缺席」偏機構用語），且與預約表單第 4 步警語「無故**不出席**」同一個詞 —— 申請人送出前讀到的就是它。**DB 值刻意不動**（使用者決策）：改名要動 enum migration + 既有資料 UPDATE，而既有的 `no_response` 列原意是「沒回我的訊息」，改寫成 no_show 等於把那段歷史重新貼標。代價是 `contacted` / `no_response` 的字面意思與顯示不符，以 FR-055 的對照表 + `statusButtons` 每個值旁的註解承擔。行為零變化（重新預約回 pending 對 no-show 同樣成立）。
- 2026-08-05: US14 完成（T104–T122、T124）— 確認信改為附 `.ics`（`METHOD:REQUEST`），並補上後台改期／取消。`CalendarInviteService` 自己產生 iCalendar，folding 按 byte 累計、按 UTF-8 字元邊界斷 —— 測試直接斷言中文長行 fold 後 `mb_check_encoding` 仍為合法 UTF-8 且可解回原字串（天真的 `wordwrap()` 在這裡會切出亂碼）。`UID` 由 lead id 推導、`SEQUENCE` 落庫遞增，改期送同 UID 的更新、取消送 `METHOD:CANCEL`。改期只需把 `reserve()` 的 `$holdUntil` 放寬為 nullable —— 它本來就先 `release($lead)` 再檢查可用性，**自我重疊因此不必特例**（10:00 改 10:15 直接可行）。Zoom 走 `PATCH` 保住 `join_url`（D52），刪除的 404 視同成功。週曆的改期採兩段式：點區塊 → 上方動作列出現 → 點「改期」後只有能容納 N 格連續空檔的起始格可點，其餘變灰；改期模式下所有區塊設 `pointer-events-none`，否則區塊會蓋住自己底下那幾格，而 15 分鐘的微調正好要點在那裡。**實作中補了 FR-054**：`updateStatus` 原本會接受 `cancelled`，那條路只貼標籤、不釋時段不刪會議不寄信，狀態會是假的 —— 改為拒絕，名單上的「已取消」降為唯讀徽章。另修一個測試層面的真實風險：`event_type` 沒有 unique 索引，migration 若用 `insertOrIgnore` 重跑會多一列而 `forEvent()->first()` 沉默取錯（測試 seeder 也一併改 `updateOrCreate`）。新增 CalendarInviteTest（13）與 BookingChangeTest（29），全套 391 passed（1945 assertions）、`npm run build` exit 0。T123 瀏覽器／真實信箱實測待業主確認。
- 2026-08-05: 規劃 US14 行事曆邀請與預約異動（已審核，開始實作）— 確認信附 `.ics`（`METHOD:REQUEST`，D49），並補上後台改期／取消。兩件事綁在同一個 US 是因為它們是同一件事的兩半：REQUEST 型的邀請一旦發出就欠對方一套生命週期，只做 `.ics` 而不做取消，會把 US12 的已知限制（FR-040/D42：Zoom 後台有殘留）升級成「對方手機在你早就取消的時間跳提醒」。`UID` 由 lead id 推導不落庫（沿用 D33），`SEQUENCE` 落庫遞增，改期送同 UID 的更新、取消送 `METHOD:CANCEL`（FR-047）。ics 自己產生不引套件，難點只有 CRLF 與 **UTF-8 安全的 75-octet folding** —— 中文一字 3 bytes，天真折行會把 `DESCRIPTION` 切成亂碼（D51）。改期走 Zoom `PATCH` 而非刪除重建，`join_url` 因此不變、已發出的連結全部繼續有效（D52）；刪除時 404 視同成功。**申請人端不做任何自助入口**（使用者決策，D50）：改期／取消一律經由聯絡管理員，於是門檻與次數上限都不需要，`rescheduled_count` 欄位取消 —— 代價是懶得聯絡的人會直接 no-show，緩解靠信件與 ics 內文明確寫「需異動請直接回信」。後台入口放週曆的預約區塊而非 Leads 名單（改期要看的是哪裡還有空，只有週曆有那張圖），手勢用兩段式而非拖放，避免與 D45 的拖曳語意打架（D53）。`status` 加第六值 `cancelled`（與 closed 分開，否則看不出誰其實根本沒談過）。三支 migration，其中新模板同時進 seeder 與 `insertOrIgnore` 的資料 migration —— 正式站只跑 migrate 不跑 seeder（D54）。status: draft 待審核。
- 2026-08-05: 社群網址改為 scheme 限定（FR-027）— 原本的 `url` 規則擋得掉沒有 scheme 的 `instagram.com/someone`，但 `ftp://`、`javascript:alert(1)`、`data:text/html,...` 全部放行，而這個值會直接成為後台 Leads 名單裡的 `href`。改為 `url:http,https`，錯誤訊息改寫成「須為完整網址，並以 http:// 或 https:// 開頭」。新增 4 個測試（無 scheme 擋下、三種非 http scheme 擋下、http/https 皆放行、留空仍可）。
- 2026-08-05: 補 FR-031 的後台介面缺口（T103）— 預約優惠碼從 US10 起就只存在 `site_settings.high_ticket_booking_bonus_codes`，程式讀得到、但**後台從來沒有填它的畫面**，只能 tinker 或直接改 DB。US10 的驗收條款只寫「填入命中 `site_settings...` 的碼」，沒有要求介面，所以測試全綠也照樣漏掉 —— 驗收寫的是資料在哪，不是人怎麼設定它。「諮詢時段」頁加設定欄位（諮詢長度是它改變的東西，放這裡比放「API 設定」語意連貫，比照 `high_ticket_lead_notify_cc` 放在 Email 模板頁的既有作法），儲存時以 `parseRecipients()` 正規化為逗號分隔字串。新增 5 個測試，全套 345 passed。
- 2026-08-05: 前台第 3 步版面調整 — 優惠碼由時段清單上方的整寬欄位改為左右兩欄（`sm:grid-cols-[minmax(0,14rem)_1fr]`，手機堆疊），金色虛線卡片 + 「填入可將諮詢延長為 45 分鐘」說明。原本只標「（選填）」，看不出填了有什麼好處，那才是它被略過的原因。後台週曆同步修三處版面 bug：頁面缺水平留白（AdminLayout 只給 `py-6`）、時間標籤用 `-mt-2` 導致整欄被負 margin 壓縮（56 格差約 450px，看起來像格子超出 22:00）、格線畫在 `border-b` 使整點線落在自己標籤的下一格。拖曳讀數由格內文字框改為格線上方浮條 —— 格內版本在單格選取時只有 20px 高卻要放兩行字，文字擠爛且蓋住游標。
- 2026-08-05: US13 完成（T092–T101）— 後台諮詢時段從「日期 + 起訖時間」表單改為週曆格線。`weekView()` 在後端把該週資料組成前端能直接畫的形狀：以**台北日期**分桶（23:30 台北是同日 15:30 UTC，用原始 UTC 分桶會落到錯的欄、跨午夜甚至錯的週）、同 lead 連續單位併為一個 booking 區塊（D46）、顯示範圍 08:00–22:00 會被範圍外的既有時段自動撐開（D47）。新增 `releaseRange()` 與 `DELETE /admin/consultation-slots` 批次收回，只刪未佔用者、佔用者計入 skipped 而非中止整段（FR-045）；路由置於單筆 `{slot}` 之前避免被 model binding 吃掉。前端 `WeekGrid.vue` 以 Pointer Events 單一路徑處理滑鼠與觸控，**拖曳命中改用 `elementFromPoint()`** —— 原本掛 `pointerenter` 在手機上會靜默失效（觸控指標被起始元素捕獲，經過的格不觸發 enter），只選得到一格。舊表單與日期分組清單整組移除，controller 的 `state()` 私有方法一併清掉。新增 ConsultationSlotAdminTest（17 tests），全套 340 passed（1761 assertions）、`npm run build` exit 0。T102 瀏覽器實測待業主確認，尤其手機觸控拖曳與捲動的取捨（見 D45 末段）。
- 2026-08-05: 規劃 US13 諮詢時段週曆化（已審核，status: building） — 現行「日期 + 起訖時間」表單改為週曆格線（1 格 = 15 分鐘）拖曳操作。**拖曳起點決定意圖**（空白格起手 = 釋出、已釋出格起手 = 收回、已佔用格不可起手，D45），避免「新增能一次拖、收回要點十六次 ✕」的不對稱。預約區塊在**後端聚合**（同 lead 連續單位併為一塊），前端不重寫一次連續性判斷（D46）。顯示範圍固定 08:00–22:00 但會被範圍外的既有時段自動撐開，不做成可設定（D47）。四狀態配色 + 圖例，已確認的區塊直接顯示暱稱與 Zoom 連結；手機改單日檢視。無 schema 變更 —— 新增的只有一個批次收回端點 `DELETE /admin/consultation-slots` 與一種呈現方式。明確不做複製上週 / 週期性班表（D48）。status: draft 待審核。
- 2026-08-05: 候補回訪連結（FR-042 / D44）— 沒有時段時仍收申請，但「通知新時段」信只帶 `{{user_name}}`／`{{course_name}}`，**連個入口都沒有**（`$availableVariables` 甚至沒有這個 event_type 的條目）；候補者自己回課程頁也是從第 1 步重來，因為問卷預填只給「已登入且 email 相符」的人，而候補者多半不是會員。新增永久 `resume_token`（與一小時到期的 `confirm_token` 分開，D44），信中 `{{booking_url}}` 深連結直接把精靈開在第 3 步、承諾自動視為已接受。身分只憑持有 token，不要求登入；token 與 course 綁定，重複候補沿用同一組使舊信不失效。token 改為**寄送當下補發**（不做全表 backfill），所以 FR-042 之前就在名單上的舊 lead 一樣拿得到深連結；問卷不齊全者仍由第 1 步開始，避免把人丟在後面的步驟而前面是空的。另補一支資料 migration 把變數**附加**到正式站既有模板（seeder 的 `updateOrCreate` 會蓋掉業主改過的文案）。draft 一併帶回當初命中的 `booking_code` —— 少帶這一欄，等於把對方已取得的 45 分鐘悄悄縮回 30。新增 10 個測試，全套 323 passed（1702 assertions）、`npm run build` 綠。
- 2026-08-05: 問卷手機號碼接上會員資料（FR-041 / D43）— 原本號碼只留在 `high_ticket_leads.phone`，lead 被開通或加序列信而變成會員時不會帶過去，會員的 `phone` 永遠是空的。改為在兩個轉換點以 `firstOrCreate` 的建立屬性帶入（既有會員不覆寫，帳號設定填的值權威性較高）。連帶把 `users.phone` 與 `orders.buyer_phone` 從 varchar(20) 加寬到 30，與 lead 表及 Form Request 對齊 —— 順手修掉既有的截斷風險：`FreePurchaseController` 早就收 `max:30`。新增 4 個測試，全套 313 passed。
- 2026-08-05: US9–US12 完成（T053–T084）— 預約由「姓名 + Email 一步送出」改為四步驟申請 + Email 二次確認 + Zoom。新增 `consultation_slots`（一列 = 一個 15 分鐘單位，可用性由 `lead_id` + `held_until` 推導、不設 status 欄，D33）與 lead 的 12 個申請/確認欄位。送出申請在單一 transaction 內寫 lead 並以 `lockForUpdate` 佔用連續 N 個單位（撞車回 409），寄「預約待確認」信；點信中 token 才 `confirmed_at` 落地、時段轉正、寄原本的確認信、停 drip、送 CAPI（D35 行為變更，既有 5 支測試同步改寫）。逾時釋出走查詢時 lazy 判定，`booking:release-holds` 每 10 分鐘只做資料整理。Zoom 走 Server-to-Server OAuth、憑證放後台「API 設定」頁，確認信改由 `CreateZoomMeetingJob` 帶著 join_url 寄出（D38）；未設定憑證整條跳過（D40），三次失敗仍寄出 fallback 文案並 CC 內部（D39）。前台流程抽成 `HighTicketBookingWizard.vue`，舊表單整組移除（D29/D36）。**規劃外補了兩項驗收缺口**：沒有可預約時段時的候補申請路徑（`POST /course/{course}/waitlist`，伺服器端擋「其實有時段」），以及重新申請的問卷預填 —— 後者改由 `CourseController` 傳 prop 而非開查詢端點，因為以 email 查既有 lead 會變成「這個信箱申請過嗎」的探測器。新增 BookingWizardTest（20）、SlotHoldTest（12）、ZoomMeetingTest（6），全套 309 passed（1607 assertions）、`npm run build` 綠。T084 瀏覽器實測與真實 Zoom 建會議待業主確認。

- 2026-08-05: [draft] 規劃 US9–US12 預約門檻提升 — 一步式表單（姓名 + Email）改為四步驟申請：問卷（手機／職業與從事時長／事業瓶頸／專長／社群連結）→ 五條承諾清單全勾 → 選定顧問時段（15 分鐘為單位，預設 30 分鐘，預約優惠碼 +15 分鐘）→ 資料覆核與不出席警語。新增 `consultation_slots`（一列 = 一個 15 分鐘單位，狀態由 `lead_id` + `held_until` 推導，D33）與 lead 的問卷／確認欄位。送出後時段暫留 1 小時並寄「預約待確認」信，點信中連結完成確認才正式保留、才寄原本的「客製服務預約確認」信、才觸發 drip 停信與 Meta CAPI（D35）；逾時釋出以查詢時 lazy 判定為正確性來源、排程只做清理（D33/FR-035）。確認後串 Zoom Server-to-Server OAuth 自動建會議，`{{zoom_join_url}}` 寫進確認信，憑證放後台「API 設定」頁（D41），未設定即整條跳過、行為等同無 Zoom（D40）。舊表單整組移除不留開關（D36）；黑名單只做警示文案不做系統實作（D37）；改期／取消同步明確不做（D42/FR-040）。status: draft 待審核。
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

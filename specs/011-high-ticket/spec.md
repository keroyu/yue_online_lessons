---
id: 011-high-ticket
status: building
owner_files:
  - database/migrations/2026_08_20_000001_install_booking_declined_template.php
  - tests/Feature/HighTicket/BookingDeclineTest.php
  - app/Support/BookingScreening.php
  - app/Console/Commands/SendApplicationResumeReminders.php
  - database/migrations/2026_08_18_000003_add_resume_reminder_sent_at_to_high_ticket_leads_table.php
  - database/migrations/2026_08_18_000004_install_application_resume_template.php
  - tests/Feature/HighTicket/ApplicationResumeReminderTest.php
  - app/Http/Requests/BookingScreeningRequest.php
  - database/migrations/2026_08_18_000001_add_screening_to_high_ticket_leads_table.php
  - database/migrations/2026_08_18_000002_add_declined_to_high_ticket_leads_status.php
  - resources/js/Components/Course/BookingScreeningStep.vue
  - tests/Feature/HighTicket/BookingScreeningTest.php
  - app/Models/CoursePlan.php
  - app/Http/Controllers/Admin/CoursePlanController.php
  - app/Http/Requests/Admin/StoreCoursePlanRequest.php
  - app/Http/Requests/Admin/SyncLessonPlansRequest.php
  - app/Http/Requests/Admin/SyncPlanLessonsRequest.php
  - app/Http/Requests/Admin/UpdatePurchasePlanRequest.php
  - database/migrations/2026_08_13_000001_create_course_plans_table.php
  - database/migrations/2026_08_13_000002_create_course_plan_lesson_table.php
  - database/migrations/2026_08_13_000003_add_course_plan_id_to_purchases_table.php
  - resources/js/Components/Admin/CoursePlanPanel.vue
  - tests/Feature/HighTicket/ConversionStatsTest.php
  - tests/Feature/HighTicket/CoursePlanTest.php
  - tests/Feature/HighTicket/PlanAccessTest.php
  - tests/Feature/HighTicket/PlanSwitchTest.php
  - app/Console/Commands/SendConsultationReminders.php
  - database/migrations/2026_08_09_000002_add_reminder_sent_at_to_high_ticket_leads_table.php
  - database/migrations/2026_08_09_000003_install_consultation_reminder_template.php
  - tests/Feature/HighTicket/ConsultationReminderTest.php
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
  - app/Models/ConsultationNote.php
  - app/Http/Controllers/Admin/ConsultationNoteController.php
  - app/Http/Controllers/Webhook/ZoomController.php
  - app/Services/ZoomWebhookService.php
  - app/Services/ZoomTranscriptService.php
  - app/Services/ConsultationTranscriptService.php
  - app/Jobs/ProcessZoomTranscriptJob.php
  - app/Console/Commands/FetchConsultationTranscript.php
  - app/Console/Commands/BackfillConsultationNotes.php
  - database/migrations/2026_08_17_000001_create_consultation_notes_table.php
  - database/migrations/2026_08_17_000003_drop_transcript_edited_at_from_consultation_notes_table.php
  - tests/Feature/HighTicket/ConsultationSummaryTest.php
  - tests/Feature/HighTicket/ConsultationNoteTest.php
  - resources/js/Components/Admin/Leads/ConsultationNotesPanel.vue
  - resources/js/Components/Admin/Leads/ConsultationSummaryModal.vue
touchpoints:
  - file: resources/js/Pages/Course/Show.vue
    owner: 002-storefront
    why: 隱藏價格模式的銷售頁展示（價格區塊替換為預約須知、按鈕改「立即預約」）與右欄預約表單（axios POST + inline 成功提示）實作於此；`isFunnelLanding` 的 landing page 隱藏規則（hero 時長行、第 3 區整塊、免費試閱）與預約成功文案依 mail_sent 分岔亦在此；US24 起多接一個 `screeningQuestions` prop 並透傳給精靈
  - file: app/Http/Controllers/CourseController.php
    owner: 002-storefront
    why: show() 傳遞 is_high_ticket / high_ticket_hide_price props 給銷售頁；US9 起另傳 `bookingDraft`（已登入且為該 lead 本人時的既有問卷答覆），FR-042 起另接受 `?resume=` token 免登入回傳完整 draft；US24 起另傳 `screeningQuestions`（`BookingScreening::questionsForFront()`，已剝除分數）且 `draftAnswers()` 多帶五題資格審核答案
  - file: resources/js/Layouts/AdminLayout.vue
    owner: 000-platform-core
    why: US10 側欄新增「諮詢時段」項目（staff 可見，位置在 Leads 名單與折扣碼之間）
  - file: database/migrations/2026_04_09_000001_add_high_ticket_fields_to_courses_table.php
    owner: 004-course-admin
    why: courses.type enum 擴充 high_ticket + high_ticket_hide_price 欄位；課程表單的類別/開關 UI 歸課程管理模組
  - file: app/Models/Purchase.php
    owner: 005-checkout
    why: US21 新增 `course_plan_id` 欄位與 `plan()` / `accessibleLessonIds()` —— 方案是「這筆授權涵蓋哪些小節」，語意屬於購買記錄本身，不能另開一張 user_course_plan 表（會與既有的 unique(user_id, course_id) 產生兩份真相）
  - file: app/Models/Course.php
    owner: 004-course-admin
    why: US21 新增 `plans()` 關聯與 `planLessonIdsForUser()` —— 與既有的 `hasAccessForUser()` 放在一起，教室的授權判斷才只有一個地方要找
  - file: app/Models/Lesson.php
    owner: 004-course-admin
    why: US21 新增 `plans()` BelongsToMany —— 方案歸屬是多對多（小節可屬於多個方案），關聯必須雙向可查
  - file: app/Http/Controllers/Member/ClassroomController.php
    owner: 003-classroom
    why: US21 教室依方案過濾小節（章節側欄、獨立小節、currentLesson 解析、完成紀錄範圍四處），`markComplete` / `markIncomplete` 各加一道 403
  - file: app/Models/User.php
    owner: 001-auth-account
    why: US21 `getCourseProgressSummary()` 加選填的 `$scopeLessonIds` 參數，讓進度分母能縮到該會員的方案範圍（不傳則行為完全不變）
  - file: app/Http/Controllers/Member/LearningController.php
    owner: 003-classroom
    why: US21「我的課程」卡片的進度改依方案計算（傳入該筆 purchase 的 `accessibleLessonIds()`）
  - file: app/Http/Controllers/Admin/ChapterController.php
    owner: 004-course-admin
    why: US21 章節編輯頁需要方案清單與每個小節的 `plan_ids`（course payload 加 `type`、兩處 lesson map 加 `plan_ids`）
  - file: resources/js/Pages/Admin/Courses/Chapters.vue
    owner: 004-course-admin
    why: US21 掛載 `CoursePlanPanel.vue` 並把 `plans` 透傳給 ChapterList；另把 `chapters` / `standaloneLessons` 傳進方案面板供章節捷徑計算三態（FR-096）
  - file: resources/js/Components/Admin/ChapterList.vue
    owner: 004-course-admin
    why: US21 每個小節列加一排方案 chip（章節內與獨立小節兩處模板），點擊即 sync
  - file: app/Http/Controllers/Admin/LessonController.php
    owner: 004-course-admin
    why: US21 新增小節通知信的收件人加一道方案過濾 —— 方案 A 的會員不該收到「新增了一節」卻進教室找不到
  - file: app/Http/Controllers/Admin/MemberController.php
    owner: 008-members-admin
    why: US21 會員詳情的擁有課程加方案欄位，並新增 `updatePurchasePlan()` 供匯款升級時切換方案 + 記補價
  - file: resources/js/Components/MemberDetailModal.vue
    owner: 008-members-admin
    why: US21 擁有課程卡片加方案下拉 + 補價金額欄
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
    why: 預約 API（`POST /course/{course}/book`，throttle:5,1）、Leads 後台與 Email 模板路由（含 `PUT /admin/email-templates/notify-cc`，須宣告在 `{template}` 之前）；US8 移除 admin 群組內的 `GET /admin/courses/{course}/subscribers`；US10/US11 新增 `GET /course/{course}/booking-slots`（throttle:30,1）、`GET /booking/confirm/{token}`（公開、無 auth）與 staff 群組內的 `/admin/consultation-slots` 三條；US14 新增 staff 群組內的 `PUT /admin/high-ticket-leads/{lead}/booking`（改期）與 `DELETE /admin/high-ticket-leads/{lead}/booking`（取消）；FR-057 新增 `PUT /admin/email-templates/support-email`（須宣告在 `{template}` 之前）；US20 新增 staff 群組內的 `GET /admin/consultation-slots/reschedule-options/{lead}`（須宣告在 `{consultationSlot}` 之前）；US24 新增公開的 `POST /course/{course}/screen`（throttle:10,1）；US21 新增 admin 群組內的方案 CRUD 五條（`POST /admin/courses/{course}/plans`、`PUT|DELETE /admin/plans/{plan}`、`PUT /admin/lessons/{lesson}/plans`、`PUT /admin/plans/{plan}/lessons`）與 `PATCH /admin/members/{member}/purchases/{purchase}/plan`；US27 新增 staff 群組內的 `POST /admin/high-ticket-leads/{lead}/decline`（婉拒並取消）
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
    why: US11 的 `booking:release-holds` 每 10 分鐘排程（逾時暫留的清理，非正確性來源，見 D33）；US19 新增 `booking:send-reminders`，以 `->timezone('Asia/Taipei')->dailyAt('17:00')` 註冊（本檔其餘排程皆為裸 UTC 時刻，這是第一個顯式帶時區的，理由見 D71）
  - file: app/Models/SiteSetting.php
    owner: 000-platform-core
    why: 預約通知 CC 清單存於 `site_settings.high_ticket_lead_notify_cc`（FR-014），US10/US12 另加 `high_ticket_booking_bonus_codes` 與三組 `zoom_*` 憑證，沿用 000 的全站設定機制，未新增欄位；2026-08-05 起另在此類別上新增 `SUPPORT_EMAIL_KEY` 常數與 `supportEmail()` helper（FR-057）—— 客服信箱是跨模組都要印的值，放在 SiteSetting 比放在任一功能 service 合理
  - file: app/Http/Controllers/Admin/SettingsController.php
    owner: 000-platform-core
    why: US12 在「API 設定」頁（`showPayment` / `updatePayment`）加一組 Zoom 憑證欄位，沿用該頁既有的 `maskSecret()` 與「留白即維持原值」的 secret 處理（D41），不新增頁面與路由
  - file: resources/js/Pages/Admin/Settings/Payment.vue
    owner: 000-platform-core
    why: US12 新增「Zoom 會議」設定卡（account_id / client_id 明文、client_secret 遮罩），版面比照同頁既有的金流與 Meta CAPI 卡片；US23 於同卡再加 `zoom_webhook_secret_token` 遮罩欄位與唯讀的 webhook URL 供複製
  - file: routes/api.php
    owner: 000-platform-core
    why: US23 新增 `POST /api/webhooks/zoom`；該群組無 session / CSRF，與既有三個金流／Portaly webhook 同性質（000 FR-002）
  - file: app/Models/AiPrompt.php
    owner: 000-platform-core
    why: US23 以 `AiPrompt::for('consultation_transcript_proofread' / 'consultation_summary')` 取用 instructions 與模型；prompt 本身是全站 AI 設定的一部分，由 000 US10 擁有
  - file: app/Services/OpenAiService.php
    owner: 000-platform-core
    why: US23 的校訂與摘要都經由這支通用呼叫發出；本模組不自行組裝 OpenAI 請求，否則第二個 AI 功能上線時會有兩份憑證與錯誤處理
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
> **2026-08-17 起步驟編號由 US24 改寫**（1 資格 → 2 資料 → 3 承諾 → 4 時段 → 5 確認）：以下條款所稱 Step 1 的問卷欄位現在位於 **Step 2**，Step 2/3/4 各順延一格。條款內容本身未變，只有編號與所在畫面移動（FR-130）。

- [x] Step 1：填 Email + 暱稱後按「開始申請」，**同頁下方即時展開**簡易問卷（不換頁、不打 API、不捲走）；問卷欄位為手機電話\*、職業和從事時長\*、事業瓶頸\*、知識或能力的專長\*、經營社群網址（選填），`*` 欄位以紅色星號標示且未填不得前進
- [x] Step 2：問卷按「下一步」後顯示承諾條件清單（三條，文案見 FR-026），三個 checkbox **全部勾選**才啟用「下一步」；未全勾時按鈕為 disabled 樣式並附說明「請確認全部項目後繼續」
- [x] Step 4：選完時段後顯示**申請資料覆核區**，逐欄列出 Step 1–3 的所有輸入值（含所選時段與諮詢長度），每欄可點「修改」跳回該步驟且保留已填內容
- [x] 覆核區下方 MUST 顯示不出席警語：「若確定預約卻無故不出席，將不可再申請本站免費諮詢名額。」（2026-08-08 改寫，原文案「我們將永久黑名單」）以警示樣式（amber/red 底）呈現，不可摺疊、不可略過
- [x] 「送出申請」按鈕正上方 MUST 顯示一行說明小字：「1v1 諮詢將依名額安排，由創辦人或團隊專業顧問提供服務。」灰階小字（`text-xs text-gray-500`），不影響送出邏輯，不出現在第 3 步候補送出（「送出申請並等候通知」）
- [x] 「送出申請」為單一 `POST /course/{course}/book`（axios，沿用 D1 非同步）；送出後 inline 顯示待確認提示（US11），全程不換頁
- [x] 四個步驟共用一組進度指示（1 資料 → 2 承諾 → 3 時段 → 4 確認），已完成步驟可點回、未達步驟不可點；所有可點元素 `cursor-pointer` + hover 樣式（專案規則）
- [x] 整段流程抽成獨立元件 `Components/Course/HighTicketBookingWizard.vue`，`Course/Show.vue` 只保留一行掛載（見 D29）；既有的一步式表單整組移除，不留開關（使用者決策）
- [x] 已登入者自動帶入 real_name / email（行為與現況相同）；重新申請時若該 email 已有 lead，問卷欄位預填既有值省得重打
- [x] 後端驗證由 `HighTicketBookingRequest` 承擔（非 controller inline）：必填、長度上限、`social_url` 須為合法 URL、`commitments` 須為三條全 true（FR-026），任一不符回 422 並 inline 顯示於對應欄位
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
- [x] 諮詢時段的可選起始時間 MUST 一律只落在整點或半點（`:00`/`:30`，以台北時間判定），`:15`/`:45` 不得出現於清單——不分 30 分鐘預設或 45 分鐘（優惠碼延長）場次（FR-069，2026-08-08 擴大範圍：原本只限 45 分鐘場，業主回報 30 分鐘預設場也要一致）
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
- [x] 顯示範圍固定 **10:00–23:00**（2026-08-11 由 08:00–22:00 調整，業主的實際諮詢時間往後移），但該週若有落在範圍外的既有時段，格線 MUST **自動往外撐開**至涵蓋它 —— 顯示範圍是預設值，不是資料的過濾器（見 D47）
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
- [x] `consultation_slots` 新增 `consultant_id`（nullable）。staff（管理員或銷售顧問）在週曆上拖曳建立時段時，MUST 自動指派為「目前選定的歸屬對象」，預設是自己
- [x] 週曆頁 MUST 有「時段歸屬」選擇器：管理員可選任一 staff（管理員 + 銷售顧問），銷售顧問 MUST 只能選自己（欄位鎖定顯示自己的名字）
- [x] 管理員 MUST 能變更**既有預約**的顧問：選取預約區塊後在面板上切換，一次請求
- [x] 已釋出但未被預約的格子，其歸屬 MUST 至少以 `title` tooltip 可查（v1 不做視覺分軌，見 D57）
- [x] 確認預約當下 MUST 把該時段的 `consultant_id` **快照**到 `high_ticket_leads.consultant_id`；此後時段被改派或釋放都不影響已成立的歸屬（見 D58）
- [x] Leads 名單的預約 tab MUST 顯示該筆的負責顧問（無指派時顯示「—」）
- [x] **CC 規則簡化**：只有「客製服務預約確認」信 CC，收件為**該筆的顧問**（未指派時退回客服清單）；「預約待確認」「已改期」「已取消」三封 MUST NOT CC 任何人（見 D59）
- [x] 確認信 MUST **只** CC 該筆的顧問；顧問為 null 時才退回客服清單 —— 沒有指派不等於沒有人要知道
- [x] `ZoomMeetingService::createMeeting()` MUST 可指定主持人 Email（`POST /v2/users/{email}/meetings`）；未指定、或該 Email 在 Zoom 帳號下不存在（404）時 MUST fallback 回 `me` 並記 log，預約流程不受影響（見 D60）
- [x] 顧問沒有 Zoom 席次時系統行為 MUST 完全等同現況 —— 會議建在擁有者帳號下，功能不因此中斷
- [x] 所有新增的可點元素 `cursor-pointer` + hover 回饋
- [x] 測試：拖曳建立時自動帶入歸屬、顧問只能指派給自己、管理員可改派既有預約、確認時快照到 lead、確認信 CC 客服 + 顧問、無顧問時只 CC 客服、其餘三封信完全無 CC、Zoom 指定主持人成功、主持人 404 時 fallback 回 me

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

### User Story 17 - 週曆勾選預約並複製 Email (Priority: P3)

要對「這週要面談的人」一起寄封提醒信，現在得離開週曆、去 Leads 名單、
用日期把人一個一個找出來、逐筆複製 Email —— 而週曆上明明就看得到是哪幾個人。

在預約區塊上加勾選框，選完按上方的「複製 Email」，
得到一串以半形逗號相隔的地址，直接貼進收件欄位。

這個功能刻意**不寄信**：寄信有既有的批次郵件（US4）與模板系統（US6），
再開一條寄送路徑只會多一個繞過模板的破口。這裡只解決「名單怎麼拿出來」。

後端不動 —— 區塊 payload 早就帶了 `email`（`ConsultationSlotService::finishBlock()`）。

**驗收**：
- [ ] 已預約（indigo）與暫留中（amber）的區塊 MUST 在暱稱左側顯示常駐 checkbox；未釋出與可預約的空格沒有（沒有申請人可選）
- [ ] 點 checkbox **只**切換勾選，MUST NOT 開啟改期／取消面板；點區塊其他地方維持現行行為（開面板）
- [ ] 格線上方 MUST 有一列工具列：顯示「已選 N 筆」與「複製 Email」按鈕；N = 0 時按鈕 disabled；N > 0 時另有「清除」
- [ ] 按下複製後寫入剪貼簿的格式為 `a@x.com, b@y.com` —— 半形逗號 + 一個空格，可直接貼進 Gmail／Outlook 收件欄位
- [ ] 重複的 Email MUST 去重（同一人同週有兩筆預約時只出現一次），順序為畫面上的日期→時間順序
- [ ] 複製成功顯示提示（沿用頁面既有的 toast 或按鈕即時回饋，文案含實際筆數）；**勾選狀態不清空**，可再複製一次
- [ ] 剪貼簿 API 不可用或被拒（非 https、瀏覽器擋權限）時 MUST 有可見退路：把整串地址顯示在唯讀輸入框並自動全選，讓使用者手動 Ctrl/Cmd+C，不得只是靜默失敗（FR-072）
- [ ] 切換週次時勾選清空（走整頁 Inertia visit，`preserveState: false`，狀態自然重置）—— 不做跨週累積（D68）
- [ ] 手機單日檢視行為一致：checkbox 一樣可點、工具列一樣在格線上方
- [ ] 改期模式（`rescheduling`）進行中時，區塊維持 `pointer-events-none`，checkbox 一併不可點 —— 該模式下唯一該被點的是目標格
- [x] 所有新增可點元素 `cursor-pointer` + hover 回饋
- [ ] 測試：`weekView()` 的 booked 與 held 區塊皆帶非空 `email`（前端唯一依賴的後端契約，見 D67）

### User Story 18 - 預約名單依顧問篩選 (Priority: P3)

US15 之後每筆預約都記得住是誰負責的，但名單頁沒有任何方式只看某一位顧問的人。
「我手上這批走到哪了」現在只能靠肉眼掃「負責顧問」欄，
而狀態 tab 上那排漏斗百分比是**全站**的，看不出個別顧問的成交狀況。

在搜尋列旁加一個顧問下拉選單，選了之後列表與**狀態 tab 上的百分比一起收斂**到那位顧問的名單。
關鍵在後者：篩選只改列表而百分比停在全站，等於畫面上並排兩組互相矛盾的數字。

顧問權限不變（D27 / D70）：銷售顧問一樣看得到全部，這是**檢視工具不是權限閘門**，
下拉預設「所有顧問」，要看自己的自己選。

**驗收**：
- [ ] 搜尋列所在的那一排 MUST 有顧問下拉選單，選項為「所有顧問」（預設，不篩選）+ 每位 staff（管理員 + `is_sales_consultant`，依 `nickname` 排序，顯示 `nickname`，無暱稱則顯示 email）+ 「未指派」
- [ ] 選「未指派」MUST 列出 `consultant_id IS NULL` 的 leads —— US15 之前的舊資料與落在無主時段上的預約全在這一堆，沒有這個選項就永遠撈不出來
- [ ] 已選顧問時 MUST 有「清除篩選」的 ✕（比照課程篩選既有作法）
- [ ] 套用顧問篩選後，狀態 tab 的「全部 N 筆」與各狀態百分比 MUST 依同一組篩選重算 —— 分母是該顧問名下的 leads 總數，各狀態百分比相加仍為 100%（FR-074）
- [ ] 顧問篩選 MUST 與既有的搜尋、課程篩選、狀態 tab **可疊加**，且三者互不覆蓋；點狀態 tab、改搜尋、換課程、翻頁 MUST 保留當前的顧問篩選
- [ ] 篩選值 MUST 在網址上（`?consultant=`），重新整理與分享連結後篩選還在
- [ ] 銷售顧問身分 MUST NOT 被自動帶入或鎖定 —— 顧問看得到整份名單的規則不變（D27）
- [ ] 篩到空集合時，空狀態文案 MUST 為「沒有符合條件的 Leads」而非「尚無預約記錄」（現行判斷只看 `status`，加了顧問篩選後會謊報「尚無預約記錄」）
- [ ] 下拉選單有 hover 回饋、清除鈕 `cursor-pointer`
- [ ] 測試：依顧問 id 篩選只回該顧問的 leads 且 `statusCounts` 同步收斂、`consultant=none` 回未指派的 leads、顧問 + 狀態 + 搜尋三者疊加、`consultantOptions` 含管理員與銷售顧問但不含一般會員、無篩選時的 `statusCounts` 與現況一致

### User Story 19 - 面談前一日提醒信 (Priority: P2)

確認信是在對方**選定時段的當下**寄出的，那可能是兩週前。中間隔著十幾天，
`.ics`（US14）解決了「時間有沒有進行事曆」，但沒解決「那天早上有沒有想起來」——
行事曆提醒預設多半只有 10 分鐘，而 1v1 諮詢的 no-show 幾乎都不是故意的。

每天台灣時間下午 5 點，把**隔天**所有已確認的面談各寄一封提醒信給申請人：
時間、長度、Zoom 連結、以及請準時出席。

範圍刻意只有一封信、只針對申請人：不做多段提醒（前一週 / 前一小時）、
不改後台 UI、不動時段與 Zoom。這是一條純粹的排程輸出。

**驗收**：
- [ ] 排程 MUST 為**台灣時間**每日 17:00（`->timezone('Asia/Taipei')->dailyAt('17:00')`），伺服器時區改變時行為不變；命令為 `booking:send-reminders`
- [ ] 收件範圍為**台北時間翌日整日**（`明天 00:00` ~ `23:59:59`，半開區間 `[明日 00:00, 後日 00:00)` 轉 UTC 後查詢）—— 跨 UTC 換日的兩端（台北 00:15 與 23:45 的面談）都 MUST 命中
- [ ] 判定的錨點為該 lead 的**最早一個時段**（`slots()` 第一列）：一場 23:45 起跳、單位延伸到隔日 00:15 的面談只算在起始那天，不會被連續兩天各提醒一次
- [ ] MUST 只寄給**生效中的預約**：`confirmed_at` 非 null、`cancelled_at` 為 null、且時段為已確認佔用（`lead_id` 非 null 且 `held_until` 為 null）；暫留中（送了申請沒點確認信）MUST NOT 收到 —— 那不是一場成立的面談
- [ ] 漏斗狀態 MUST NOT 參與過濾：`contacted` / `converted` 的 lead 若手上握著明天的預約照樣提醒 —— status 講的是銷售進度，不是「明天要不要出現」
- [ ] 同一筆預約 MUST 只提醒一次：寄出成功後 `high_ticket_leads.reminder_sent_at` 落地，查詢以 `whereNull` 守門，命令重跑不會二次寄出
- [ ] 改期（FR-048）MUST 清空 `reminder_sent_at` —— 移到別天的預約要能重新拿到那天的提醒（見 D72）
- [ ] 使用新模板 `high_ticket_consultation_reminder`（「客製服務面談提醒」），變數 `{{user_name}}`、`{{user_email}}`、`{{course_name}}`、`{{slot_time}}`、`{{consult_minutes}}`、`{{zoom_join_url}}`，並沿用全域變數 `{{support_email}}` / `{{app_url}}`
- [ ] `{{slot_time}}` MUST 沿用 `ConsultationSlotService::label()`（`8/10（週一）14:00`，台北時間）—— 與確認信、改期信同一種寫法，不另造格式
- [ ] 模板 MUST 同時登記於 `EmailTemplateSeeder::templates()` 與一支**資料 migration**（沿用 FR-052 的「缺才 insert、永不 update」迴圈），否則正式站永遠不會有這筆
- [ ] 變數清單 MUST 補進 `EmailTemplateController::$availableVariables`，後台清單 MUST 有中文標籤，否則編輯頁沒有插入按鈕、列表顯示成裸 event_type
- [x] 提醒信 MUST CC **該筆的顧問**，未指派顧問時退回通知清單（2026-08-17 使用者決策，推翻原本的「不 CC 任何人」）—— 明天要出現的是兩個人，而顧問手上唯一提過這件事的信是確認當下寄的那封，可能已經是幾週前
- [ ] 提醒信 MUST NOT 附 `.ics` —— 同 `UID` 同 `SEQUENCE` 的邀請對日曆是 no-op，而為了寄提醒去遞增 `SEQUENCE` 會讓真正的異動（US14）失去意義（見 D73）
- [ ] 模板不存在時 MUST 記 warning 後正常結束（不寄、不炸、排程不變紅）；單筆寄送失敗 MUST 只記 error 並繼續處理其餘 leads，該筆 `reminder_sent_at` 不落地
- [ ] 「今天才確認、面談就在明天」MUST NOT 補寄（使用者決策，見 D74）—— 他幾小時前才收過含 `.ics` 的確認信
- [ ] 命令 MUST 輸出實際寄出筆數（`已寄出 N 封面談提醒`），供 Forge 的排程日誌與人工重跑檢視
- [x] 測試：排程註冊為 `0 17 * * *` + `Asia/Taipei`、翌日 00:15 與 23:45 兩端都寄、今天與後天不寄、暫留中與已取消不寄、跨日面談只算起始日、重跑不重寄、改期清空 `reminder_sent_at`、寄出內容含 slot_time 與 zoom 連結、CC 指派顧問、未指派時退回通知清單、缺模板時不寄也不丟例外

### User Story 20 - 改期面板直接選時段 (Priority: P2)

US14 的改期只有一條路徑：進入改期模式，然後**在格線上點目標格**。
格線一次只畫一週，而換週是一次整頁 Inertia visit（`preserveState: false`，D68）——
換過去的瞬間改期模式連同選取的預約一起被重置。

於是「改到下週或更後面」這件事在畫面上做不出來：
唯一能點到的目標格永遠在當前這一週。對方寫信說「這週不行，下下週三可以嗎」，
管理員能做的只有取消預約再請對方重新申請，
而那會多寄一封取消信、把時段丟回池子、也丟掉那筆預約的歷史。

面板上加一組日期／時間下拉，清單直接來自後端算好的可用時段（跨週、跨月都在裡面），
選完確認就送出。格線點選保留 —— 同一週小幅挪動，用點的還是最快。

**驗收**：
- [x] 進入改期模式時，面板 MUST 立即抓取「這筆預約可以改到哪些時段」的清單並顯示載入中狀態
- [x] 清單來源 MUST 為 `ConsultationSlotService::availableStarts()`——**與訪客預約精靈同一份名單**，依日期分組、時間顯示為台北時間 `H:i`，涵蓋**所有未來日期**而不限當前週（跨週正是本故事要解的問題）
- [x] 該 lead **目前佔用的單位** MUST 視為可用（沿用 FR-048 的自我重疊處理），使 45 分鐘場次能挪到與自己部分重疊的起始；但**目前所在的那個起始 MUST 被排除** —— 移到原地不是改期，留著它還會讓空狀態失去意義（見 FR-086）
- [x] 面板 MUST 提供日期、時間兩個下拉；選定後按「確認改期」跳二次確認（顯示 `舊時間 → 新時間`），確認才送出既有的 `PUT /admin/high-ticket-leads/{lead}/booking`（沿用 `date` + `start_time` 參數，端點不變）
- [x] 沒有任何可用時段時 MUST 顯示明確空狀態（「未來沒有可用時段，請先在週曆上釋出時段」），MUST NOT 只留一個空下拉——空下拉看起來就是壞掉
- [x] 既有的「格線點選目標格」路徑 MUST 保留，兩條並存：同週微調用點的、跨週用下拉
- [x] 清單 MUST 在每次進入改期模式時重新抓取、MUST NOT 快取——撞車的成因就是拿舊名單做決定（沿用 FR-032）
- [x] 送出後若仍撞車 MUST 沿用既有 409 處理（提示重新整理），不得吞掉
- [x] 面板高度會因下拉而改變，MUST 於內容變動後重算懸浮位置（沿用 `updatePanelPos()` / D66）；下拉展開 MUST NOT 被格線的 `overflow-x-auto` 容器裁切
- [x] 改期成功後 MUST 跳轉到**新時段所在的那一週**，而非留在原本那週——跨週改期後那筆預約會離開當前畫面，停在原地等於讓管理員無法確認結果（見 D77）
- [x] 新端點 MUST 掛 staff 群組並宣告在 `{consultationSlot}` 之前；lead 沒有生效中的預約時回 422（`isActiveBooking()` 為 false）
- [x] 所有新增可點元素 `cursor-pointer` + hover 回饋，下拉有 focus 樣式；手機單日檢視下面板一樣可用
- [x] 測試：端點分組結構正確、該 lead 自身單位被視為可用（同日挪動的起始出現在清單）、跨週的時段確實在清單裡、無可用時段回空陣列、非 staff 擋下、非生效預約回 422、改期成功後導向新時段所在週

### User Story 21 - 高價課多方案與分級授權 (Priority: P1)

高價課目前是「買了就看全部」，但實際銷售需要分級：方案 A 只給前 4 集、方案 B 給全部，
客戶先買 A、之後匯款升級到 B。

現況擋在三個地方：`purchases` 只有 `unique(user_id, course_id)`，一人一課一筆記錄，
沒有欄位表達「買到哪個層級」；教室只有「有沒有這門課」的二元判斷（`hasAccessForUser()`），
沒有小節層級的授權；而自 D13 起唯一成交入口的「開通」只選課程、輸入成交價，無從表達方案。

管理員在章節編輯頁定義方案與小節歸屬（**可重疊** —— A: 1,2,3；B: 2,4,5 是合法的），
開通時選方案，客戶匯款升級後在會員詳情切換並記下補價。

**驗收**：
- [x] 只有 `type = high_ticket` 的課程能設定方案：章節編輯頁的方案面板 `v-if` 該條件，後端另擋一次（403）
- [x] 章節編輯頁可新增／改名／刪除方案並設定「建議價格」（選填）；未建立任何方案 = 單一方案，行為與現況完全相同
- [x] 每個小節列可勾選歸屬哪些方案，**同一小節可屬於多個方案**；點擊即存（`preserveScroll`），不需要另按儲存
- [x] 方案卡片提供「選取章節」捷徑：以章節為單位整批加入／移除，另有獨立小節組與「全選／全部清除」；按鈕上顯示該方案目前涵蓋節數（`N/總數`）
- [x] 捷徑的章節勾選為三態（全選／部分／未選），**部分狀態視為「尚未選滿」**——再點一次是補齊而非清空，不得清掉管理員已手動勾好的小節
- [x] 沒有被歸類到任何方案的小節，持方案的會員**一律看不到**（授權單向明確：看得到 = 該方案有勾）
- [x] 方案外的小節是**完全隱藏**而非鎖頭：整個從章節側欄濾掉，連標題都不出現（與 drip 的 `is_locked` 刻意不同，見 D80）
- [x] 教室四個判定點都吃到方案範圍：章節側欄、獨立小節、`currentLesson` 解析（含 `?lesson_id=` 直連）、完成紀錄的統計範圍
- [x] `markComplete` / `markIncomplete` 對方案外的小節回 403 —— 前端點不到不等於擋住，那是公開端點
- [x] 學習進度 % 依方案計算：方案 A 有 4 節、看完 4 節即 100%；影響「我的課程」卡片與會員詳情兩處
- [x] 管理員一律看得到全部小節（沿用 `hasAccessForUser($user, includeAdmin: true)` 的立場）
- [x] 開通 modal 在所選課程有方案時出現方案下拉（必選），成交價預設帶 `plan.price ?? course.display_price`
- [x] 課程有方案卻沒選方案時，開通 MUST 在 service 層擋下（422），不是只靠前端必填
- [x] 開通成功的通知信 `{{course_name}}` 帶上方案名（`課程名（方案B）`）—— 客戶要知道自己買到什麼
- [x] 會員詳情的擁有課程可切換方案，並可填**選填**的「補價金額」累加到該筆 purchase 的 `amount`，營收統計自動反映
- [x] 切換方案 MUST 驗證 purchase 屬於該會員、plan 屬於該課程；補價 MUST NOT 收負數（退款走交易管理的既有路徑）
- [x] 仍有會員持有的方案 MUST NOT 被刪除（422 並說明有幾位持有）；DB 層以 `restrict` 兜底
- [x] 新增小節時的通知信收件人排除「方案看不到這節」的會員
- [x] 既有購買記錄（`course_plan_id` 為 null）與所有非開通路徑（前台結帳／贈課／匯入／積分兌換）行為零變化，一律全開
- [x] 所有新增可點元素 `cursor-pointer` + hover 回饋；方案 chip 與會員詳情下拉在手機寬度不破版
- [x] 測試：方案 CRUD 與刪除守門、教室過濾與 403、進度分母、開通選方案、切換方案 + 補價累加

### User Story 22 - 預約名單成交業績摘要 (Priority: P3)

自 D13 起後台開通是高價課唯一的成交入口，成交金額也都落在 `purchases.amount` 上，
但這條銷售線的業績目前只能到「交易管理」翻列表或看營收圖表 —— 而那兩處混著
線上刷卡、贈課與積分兌換，看不出「顧問這個月談成多少」。

狀態色塊列的右側加一塊摘要：本月與年度各自的**成交人數**與**總金額**。
它與既有的狀態百分比共用同一份篩選範圍，所以選了某位顧問就是那個人的業績，
選了某門課就是那門課的數字。

**驗收**：
- [x] 狀態色塊列右側以**單行**顯示本月與年度的「成交人數」與「總金額」
- [x] 摘要 MUST NOT 增加色塊列的高度：純文字 `text-xs`、無邊框無底色無垂直 padding，整體矮於色塊本身（`text-sm` + `py-1.5` + border），列高仍由色塊決定
- [x] 數字 MUST 與狀態色塊共用 `bookingLeadsQuery()` 的篩選範圍（課程／顧問／關鍵字），MUST NOT 各自寫一份查詢
- [x] 狀態 tab 本身（`?status=`）MUST NOT 影響摘要 —— 比照 FR-067，點進「已成交」不該讓分母跟著變
- [x] 成交人數以**人**為單位去重：同一個 Email 買兩門課算 1 人
- [x] 金額只計 `type = lead_conversion` 且 `status = paid` 的購買紀錄；已退款的不計入
- [x] 期間以**台北時間**的當月／當年判定，轉 UTC 後查詢（伺服器跑 UTC，沿用 FR-077 的作法）
- [x] 沒有任何成交時顯示 `0 人 · NT$ 0`，MUST NOT 隱藏整塊 —— 空的數字本身就是資訊
- [x] 手機寬度下摘要換行到色塊列下方，不擠壓色塊
- [x] 測試：篩選連動（顧問／課程）、狀態 tab 不影響、同人多課去重、退款不計、跨月邊界以台北時間切分

### User Story 23 - 面談逐字稿自動摘要 (Priority: P2)

這條銷售線在「面談之後」是斷的。`high_ticket_leads` 記了誰、什麼時候、哪位顧問、Zoom 連結，
但**面談裡談了什麼完全沒有留下** —— 這張表連一個備註欄位都沒有，唯一的自由文字是
前台問卷填的 `bottleneck` / `expertise`，而那是面談**之前**的答案。談完一輪，客戶的痛點、
預算、異議、承諾事項全在顧問腦袋裡，換人接手或隔週回訪就要重問一次。

面談結束、Zoom 產出雲端錄影的 VTT 逐字稿後，系統自動抓回來、校訂成乾淨的匿名對話稿存檔，
再萃取成固定格式的摘要。後台每場次收斂成兩個動作：**摘要**開 modal 檢視與編輯，
**逐字稿**只提供 `.txt` 下載、不在頁面上呈現也不可編輯（FR-120）。

紀錄落在新的 `consultation_notes` 表，**一場面談一列、以 email 對應客戶**（D92）：
買了多次一對一顧問的人會累積出完整的面談史，而即將開發的「客戶自行登記時段的顧問服務」
也寫進同一張表。後台展開一位 lead 時看到的是**這個 email 的所有場次**，不只這次預約 ——
這才是 CRM 該有的樣子。

面談列在**預約確認當下**就建立（此時 Zoom 會議剛開好、時段已定），逐字稿與摘要之後補上。
webhook 的對照鍵是 `consultation_notes.zoom_meeting_id`。

**驗收**：
- [x] Zoom 的 `endpoint.url_validation` 挑戰在驗簽之前處理，回 `plainToken` + HMAC-SHA256 的 `encryptedToken`，3 秒內完成
- [x] 其餘事件一律驗 `x-zm-signature`（`v0=` + HMAC-SHA256 of `v0:{timestamp}:{raw body}`，`hash_equals` 比對）；不符或時間戳超過 5 分鐘 → 401
- [x] 只認 `recording.transcript_completed` 與 `recording.completed`，其餘事件回 200 忽略
- [x] webhook 端點只做「驗簽 → 找 lead → 派 job → 回 200」，所有處理在佇列；處理中的例外一律吞掉並回 200
- [x] 派工走專屬的長租約佇列連線，job `$timeout` 大於實際工作時間、連線 `retry_after` 又大於 `$timeout`（FR-117）
- [x] 預約確認成功時建立一列 `consultation_notes`（email、met_at、zoom_meeting_id、consultant_id、course_id、lead_id、source=`high_ticket_booking`）
- [x] 改期 → 更新該列 `met_at`；取消 → 該列**無逐字稿也無摘要時才刪除**，已有內容則保留
- [x] `zoom_meeting_id` 對不到任何 note → 寫 log 回 200，MUST NOT 視為錯誤（可能是非面談用途的會議）
- [x] VTT 解析剝除 `WEBVTT` 檔頭、cue 序號、時間軸與空行，同一講者連續行合併
- [x] 講者以 `lead.name` / `lead.consultant.name` **機械式**對應為「客戶」／「顧問」，MUST NOT 交給模型猜；未能對應者才由校訂階段依語境判定
- [x] 校訂分段送出（約 4000 字／段，切在講者邊界），要求逐句保留、只修錯字與贅詞
- [x] 防縮水：任一段輸出長度 < 輸入的 60% 即視為模型擅自摘要，保留該段機械式原文並寫 warning log
- [x] 落庫的逐字稿只有校訂後的匿名版；**原始 VTT、Zoom 顯示名稱與真實人名 MUST NOT 進資料庫、MUST NOT 進 log**
- [x] 摘要以校訂後的逐字稿為輸入，依 `ai_prompts` 的 instructions 產出固定 Markdown 結構
- [x] 後台展開 lead 時列出**該 email 的所有面談場次**（依 `met_at` 倒序），不只本次預約那一場
- [x] 逐字稿與摘要在每一列場次上皆可編輯儲存，各自蓋上 `*_edited_at`
- [x] 逐字稿已取回 → webhook 再送一次不重跑校訂（不浪費 token）
- [x] payload 沒有 TRANSCRIPT 檔（`recording.completed` 的常態）→ 寫 info log 靜默結束，不丟例外、不進 failed_jobs（FR-132）
- [x] `consultation_summary_edited_at` 有值 → webhook 不覆寫摘要；後台「重新產生摘要」按鈕可明確解鎖重跑
- [x] 「重新產生摘要」用資料庫既存的逐字稿重跑，**MUST NOT 再打 Zoom**（雲端錄影有保存期限）；逐字稿為空時回 422 而非 500
- [x] `openai_api_key` 未設定時 AI 步驟靜默跳過、不丟例外，逐字稿仍以機械式解析結果落庫
- [ ] `php artisan booking:fetch-transcript {note}` 可在沒有 webhook 的情況下手動跑完整條流程（指令已實作，但需真實錄影才驗得了 —— 見 T287）
- [x] 測試：URL 驗證、驗簽失敗、講者匿名化、防縮水、兩個覆寫守門、重跑不打 Zoom、憑證未設定

### User Story 24 - 前置資格審核與自動婉拒 (Priority: P1)

申請 1v1 的人幾乎都來自電子書的序列信名單 —— 他們**本來就在信箱裡**，這一步要做的不是獲取名單，
是**分流**：把「真的想買」的人挑出來排進行事曆（確認預約後 `checkAndBook()` 標記 `booked`、停止序列信），
其餘的人留在序列信裡繼續加溫。

因此閘門必須放在**最前面**。讓人寫完五分鐘的問卷、選好時段、再告訴他不安排，反彈不會隨著他離開 ——
他還在你的名單裡，接下來每一封信都在提醒他那件事。這是被拒絕者仍留在資產內部的漏斗特有的成本
（D96，推翻本 spec 前一版「填完再婉拒」的設計）。

流程改為五步：**1 資格 → 2 資料 → 3 承諾 → 4 時段 → 5 確認**。
第一步只要 Email + 暱稱 + 五題單選（時程、預算、決策權、痛點成本、下一步），
送出後**一律等 15 秒**跑自動審核：滿 5 分進第二步，未達 5 分或勾選「目前沒有預算」則顯示婉拒文案。
第二步之後的流程與 US9–US12 完全不變。

**驗收**：
- [x] 第一步只有三塊：Email、暱稱、五題單選（題目與選項文案、計分、一票否決見 FR-123）；MUST NOT 出現手機／職業／瓶頸／專長／社群等第二步欄位
- [ ] 五題**全部必答**才可送出審核（FR-124）—— 未作答在計分制下只能算 0 分，留成選填等於做一題使用者看不懂自己被什麼擋住的扣分題
- [ ] 每題以單選卡片呈現（比照承諾清單的 `<label>` 卡片），MUST NOT 用 `<select>`；選項文字在手機上單欄不溢出，每張卡 `cursor-pointer` + 選中樣式
- [ ] 題目與選項文字由後端以 prop 下發（`CourseController@show`），**分數與否決旗標 MUST NOT 下發**（FR-124 / D101）
- [ ] 送出 → `POST /course/{course}/screen`（throttle:10,1），回 `{passed: true|false}`，**不回分數**
- [ ] 收到回應後**不論通過與否一律顯示「自動審核中」畫面**（進度條 + 倒數秒數，長度 15 秒）；倒數結束後通過者進入第二步、未通過者顯示婉拒文案（FR-128）
- [ ] 倒數由前端跑，伺服器 MUST NOT sleep 或延遲回應（FR-128）
- [ ] 婉拒文案定版見 FR-128，版面為中性灰而非錯誤紅 —— 這不是他填錯了什麼；畫面上 MUST NOT 出現分數、也 MUST NOT 說明是哪一題造成的
- [ ] 審核當下即建立／更新該 email 的 lead：五題答案、`screening_score`、`screened_at` 落庫（FR-125）—— 這是「他是哪一種」的紀錄，中途離開也留得住
- [ ] 未通過者：`status = declined`、`declined_at = now()`、**不寄任何信**（不寄婉拒信、不寄內部 CC）、不佔時段、不產 `confirm_token`（FR-126）
- [ ] 未通過者 MUST NOT 被停掉序列信 —— 他們正是要留在加溫名單裡的那群（FR-126）
- [ ] 審核 MUST NOT 覆寫已有進行中預約的 lead 狀態：`confirmed_at` 非 null 或 `status` 已被管理員改動者，只更新答案與分數，`status` 不動（FR-125）
- [ ] 送出申請（`book` / `waitlist`）時伺服器**以送上來的答案重新計分**：帶了答案就必須通過，沒帶答案（`?resume=` 回訪、本功能上線前的舊 lead）則放行（FR-129 / D97）
- [ ] `?resume=` 回訪者直接開在時段步驟，MUST NOT 被要求重做資格審核；已存的答案由 `draftAnswers()` 帶回（FR-129）
- [ ] 婉拒非永久：同一 email 重做審核、答案改變即照常通過，`status` 回 `pending`、`declined_at` 清空（D97）
- [ ] 進度指示改為五格（1 資格 / 2 資料 / 3 承諾 / 4 時段 / 5 確認）；已完成可點回、未達不可點；**回到第一步 MUST NOT 重跑審核倒數**（同一組答案未變更時直接放行）
- [ ] 後台 Leads 狀態新增第 7 個「已婉拒」（字母 `R`、rose 色系），狀態方塊與篩選 pill 同步；管理員仍可手動改成其他狀態
- [ ] 後台展開列新增「資格審核」區：分數（`N/10`）、分級標籤（8–10 高意願 / 5–7 值得談 / 0–4 培育名單）與五題答案的中文標籤；無審核紀錄的舊 lead 顯示說明而非一排「—」
- [ ] 逾時清掃 MUST NOT 刪到只完成審核、尚未送出申請的 lead（`confirm_expires_at` 為 null 即已排除，FR-126）
- [ ] 測試：計分表逐題、5 分邊界、「沒有預算」一票否決、未通過不寄信／不停 drip／不佔時段、通過者照常走完既有流程、重新審核可翻案、送出時重新計分、舊 lead 無答案仍可送出

### User Story 25 - 手動抓取逐字稿 (Priority: P2)

逐字稿的抵達時間是**不可預期**的。2026-08-17 量到的兩場：一場的 `recording.transcript_completed`
在會議結束後隨即到達，另一場等了 **3 小時 27 分**。而整條自動路徑（Zoom → webhook → 佇列 → worker）
有四個環節，任何一環出事的症狀完全一樣 —— 後台那一列永遠停在「尚無逐字稿」，
而管理員無從分辨「Zoom 還沒生出來」與「我們這邊壞了」。這次的實際事故（`long` 佇列沒有 worker，
兩場面談的工作單躺了四小時）就是後者，卻只能靠人工上伺服器查 `jobs` 表才看得出來。

因此在後台每一個**還沒有逐字稿**的場次上放一顆「抓取逐字稿」按鈕：直接去問 Zoom 現在有什麼，
不依賴 webhook 是否到達、不依賴佇列裡那張工作單。管理員的使用方式很單純 ——
會議結束一小時後還看不到逐字稿，就按一下。

按下去的**第一件事是回答問題，不是排隊**：向 Zoom 查詢錄影清單只需一秒，
而那一秒就足以分辨三種情形（沒有錄影／逐字稿還沒好／已經好了）。
只有第三種才需要進佇列做慢的部分（下載、校訂、摘要，25k 字約 2–4 分鐘）。
把整段都丟進佇列會讓最常見的兩種情形失去回饋 —— 按了、等三分鐘、回來看，畫面還是一樣。

**驗收**：
- [x] 後台面談紀錄列在 `transcript_bytes` 為 0 時，「尚無逐字稿」文字改為「抓取逐字稿」按鈕；已有逐字稿的場次 MUST NOT 顯示此按鈕（既有的「重新產生摘要」已涵蓋該情境，且不必重付校訂費用）
- [x] `POST /admin/consultation-notes/{note}/fetch-transcript`（staff 群組、`throttle:10,1`）：同步查 Zoom，**慢的部分才派工**
- [x] Zoom 憑證未設定 → 422，訊息指向後台 API 設定頁
- [x] 該場次沒有 `zoom_meeting_id` → 422（例如手動補建但沒有會議的場次）
- [x] Zoom 回錯（含 `3301 此錄製不存在`）→ 422，文案說明可能未開錄影或雲端錄影已過保存期限，MUST NOT 派工
- [x] 錄影存在但清單裡沒有 TRANSCRIPT/VTT → 422，文案說明逐字稿尚未產出、稍後再試，MUST NOT 派工（沿用 FR-132 的立場：這不是故障）
- [x] 清單裡有 VTT → 派 `ProcessZoomTranscriptJob` 並回 202，前端提示「已排入處理，約 1–3 分鐘後重新整理」
- [x] 查詢 Zoom 錄影清單的邏輯 MUST 只有一份，`booking:fetch-transcript` 指令與本端點共用（沿用 FR-019 對重複渲染的既有立場）
- [x] 後端 MUST NOT 另設「已有逐字稿就拒絕」的守門 —— job 的 `transcriptIsSettled()` 已經擋住重抓（FR-110），再擋一次只是把同一條規則寫在兩個地方
- [x] 測試：有 VTT 則派工、Zoom 404 不派工、無 VTT 不派工、憑證未設定、無 meeting id、訪客被擋

**前提**：慢的那一半仍跑在 `long` 佇列上，因此本功能與自動路徑共用 T289 的 worker。
worker 未建時按鈕仍會誠實回報 Zoom 端的狀態，但抓回來的動作不會發生。

### User Story 26 - 未完成申請的續填提醒 (Priority: P1)

US24 把 lead 的落地時機提前到資格審核（FR-125），於是名單裡出現一種新的列：
**通過了審核，然後就沒有下文**。第一步只要 30 秒，第二步要寫出自己的事業瓶頸 ——
斷點會固定落在這裡，而且斷掉的往往不是不想來的人，是被打斷的人。
上線第一天實際遇到的那一筆是 7/10、想在一個月內開始、能自行決定、想盡快確認合作方式。

這種列有三個問題：逾時清掃碰不到它們（沒有 `confirm_expires_at`）所以會無限累積、
它們混在 `pending` 裡跟真的預約長得一樣、而最貴的是**沒有人去把他們找回來**。
本故事只解決第三個，因為那是唯一會賺錢的那個：排程每小時挑出這些人，
寄**一封**帶回站連結的信，連結直接把他們放回第二步。

**驗收**：
- [x] 命中條件（全部同時成立）：`screened_at` 非 null 且距今 **≥ 3 小時**、`screened_at` 距今 **≤ 7 天**、`resume_reminder_sent_at` 為 null、`phone` 為 null、`confirmed_at` 為 null、`status = pending`（FR-135）
- [x] `phone` 是「沒走到第二步」的判準 —— 它是第二步的第一個必填欄位，有值就表示對方走得比這封信要處理的情形更遠
- [x] **7 天上限**：功能上線當下 MUST NOT 對名單裡所有歷史未完成申請發一輪信
- [x] 每筆 lead **一生只寄一次**：寄送成功後才蓋 `resume_reminder_sent_at`（寄失敗不蓋，比照 FR-078）；欄位獨立，MUST NOT 沿用 `last_notified_at`（那是 US4 的「通知新時段」，共用會讓兩封信互相取消）
- [x] 信件走既有模板機制：新 `event_type` = `high_ticket_application_resume`（「申請未完成提醒」），變數 `{{user_name}}` / `{{user_email}}` / `{{course_name}}` / `{{booking_url}}`；MUST 同時登記於 `EmailTemplateSeeder::templates()`、一支資料 migration（沿用既有的「缺才 insert、永不 update」迴圈）與 `EmailTemplateController::$availableVariables`
- [x] 提醒信 **MUST NOT CC 任何人** —— 這是一封關於未填完表單的催填信，沒有任何人需要據此行動（FR-062）
- [x] `{{booking_url}}` 為 `?resume=` 深連結，token 於寄信時 lazy 產生；URL 組法 MUST 只有一份定義（`HighTicketBookingService::resumeUrl()`，`NotifyHighTicketSlotJob` 改為委派）
- [x] 點連結回站 MUST 直接落在**第二步**且不重跑資格審核（FR-137）
- [x] 排程 `booking:send-resume-reminders` MUST 為**每小時**執行但限台北時間 09:00–21:00 —— 每日一次會讓提醒晚到十幾小時，而不限時段會在凌晨三點寄一封關於表單的信
- [x] 模板不存在或單筆寄送失敗 MUST 記 log 後繼續，排程不變紅（比照 FR-081）
- [x] 命令輸出實際寄出筆數（`已寄出 N 封續填提醒`）
- [x] 測試：命中一次即寄且帶正確 resume 連結、第二次執行不重寄、3 小時內不寄、7 天前不寄、`declined` 不寄、已填手機不寄、管理員改過狀態不寄、缺模板不蓋章、回站 `screening_cleared` 為 true、**答案不及格者 `screening_cleared` MUST 為 false**

### User Story 27 - 預約名單直接婉拒並取消 (Priority: P2)

US24 的閘門擋掉的是「答案不對的人」。但**答案對、人不對**這件事，只有在複查過對方寫下來的
瓶頸與專長之後才看得出來 —— 而那一刻預約已經成立、Zoom 會議已經建好、時段已經被佔住。

現在要處理這種列得走兩段路：先到「諮詢時段」週曆上找到那個區塊按取消（那裡才會釋出時段、
刪 Zoom、寄信），再回到預約名單把狀態點成「已婉拒」。中間任一段忘記做，名單就開始說謊 ——
只取消不標記，這筆看起來像對方臨時有事；只標記不取消，時段永遠卡在週曆上放不出去。
而**複查本來就是在預約名單上做的**（申請內容、資格審核答案、面談紀錄都在那一列的展開區），
要求管理員為此換頁，正是這條路徑最容易斷的地方。

因此在預約名單有生效預約的列上放一顆「婉拒」按鈕：一次做完取消該做的三件事，
狀態直接落成「已婉拒」，並附上一封說明理由的信。

這與週曆上的「取消預約」是**兩個不同的動作**，不是同一個動作的兩個入口：取消是中性的
（時間不合、對方有事，歡迎重約），婉拒是我方的決定，而且不歡迎重約。兩者的信件內容與最終狀態
都不同，唯一共用的是「把預約拆掉」那段機制（見 D103）。

**驗收**：
- [x] 「婉拒」按鈕 MUST 只出現在**有生效預約**的列（`confirmed_at` 非 null 且 `cancelled_at` 為 null）；其餘列維持既有的「已婉拒」狀態方塊（只改標記、不寄信）
- [x] 按下 MUST 二次確認：確認框 MUST 顯示對方暱稱、原訂時段，以及**將寄出的婉拒理由全文** —— 這封信寄出去收不回來，按之前要看得到會寄出什麼
- [x] 一次動作 MUST 完成四件事，缺一即為狀態說謊：釋出時段、刪除 Zoom 會議、寄出婉拒信（附 `METHOD:CANCEL` 的 `.ics`）、`status` 落 `declined`（FR-138）
- [x] `declined_at` 與 `cancelled_at` MUST **同時**落地（FR-139）：前者記錄這是我方婉拒，後者是既有的「這筆預約已不生效」判準（`isActiveBooking()`）；只落其一會讓這列在名單與週曆上繼續被當成生效中的預約
- [x] MUST NOT 同時寄出 US14 的「客製服務預約已取消」信 —— 同一件事寄兩封說法不同的信是最糟的結果
- [x] 婉拒信走既有模板機制：新 `event_type` = `high_ticket_booking_declined`（「預約婉拒通知」），MUST 同時登記於 `EmailTemplateSeeder::templates()`、一支資料 migration（沿用「缺才 insert、永不 update」迴圈）與 `EmailTemplateController::$availableVariables`（FR-140）
- [x] 婉拒理由為**模板內文的一部分**，MUST NOT 在按下按鈕當下逐筆輸入（使用者決策，D105）；要改文字就到後台 Email 模板頁改一次
- [x] 婉拒信 MUST NOT CC 任何人（沿用 FR-062）
- [x] 序列信訂閱狀態 MUST NOT 因婉拒而改動（使用者決策，D107）：確認預約時已被標記 `booked` 停止加溫，婉拒後就停在那裡
- [x] Zoom 刪除失敗 MUST NOT 讓婉拒失敗（沿用 FR-050）：時段與信件是事實，Zoom 是副作用；失敗時於成功訊息後附一句提醒手動處理（沿用既有 `zoomNote()`）
- [x] 端點 MUST 自行守門：非生效預約打進來不做任何事，以紅色 flash 回報而非靜默成功（FR-139）—— 按鈕不顯示只是第一道
- [x] 婉拒非永久（沿用 D97）：同一 email 重新申請時 `recordLead()` MUST 轉回 `pending` **並清空 `declined_at`**（現況只在帶著審核答案時才清，`?resume=` 回站的路徑會留下一枚假的「已婉拒」標記，FR-139）
- [x] 按鈕 `cursor-pointer` + hover 回饋；送出中 disabled 並顯示處理中；完成後該列 MUST 就地更新（Inertia 局部重載 + `preserveScroll`，D108）
- [x] 測試：釋出時段、刪 Zoom、只寄一封婉拒信、附件 method 為 CANCEL、兩個時間戳與狀態、無 CC、drip 訂閱不動、非生效預約被擋、重新申請清空 `declined_at`、訪客被擋


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

  原第 3 條「我有預算／決策權，希望在未來 3-6 個月內實踐計劃。」於 2026-08-17 移除（使用者決策）：US24 的資格審核已在第一步用兩道各七／五選項的題目問過預算與決策權，而且問到了級距。承諾清單再勾一次同一件事，只是把一個已經有答案的問題降級成一個沒有資訊量的核取方塊。
  **條數 MUST 與 `HighTicketBookingRequest::COMMITMENT_COUNT` 同步**，漏改則每次送出都被 422，而訊息「請確認全部的預約前提條件」完全不提條數（`BookingWizardTest` 有一條測試直接讀 Vue 的陣列長度去打端點，就是為了讓這種漂移出聲）。

  四條 MUST 全數勾選才能前進，前後端各驗一次（前端控制按鈕 disabled，後端 `HighTicketBookingRequest` 驗 `commitments` 為長度 4 且全為 true 的陣列）。**條數是跨語言的耦合**：清單定義在 Vue、長度驗證在 PHP，改一邊忘了另一邊會讓每一次送出都被 422 擋下，而使用者看到的錯誤訊息完全不提「條數」。因此後端的長度寫成具名常數 `HighTicketBookingRequest::COMMITMENT_COUNT`，並由測試讀取 Vue 的 `COMMITMENTS` 陣列實際比對兩者，不靠註解自律。勾選事實以 `commitments_accepted_at` 時間戳落庫 —— 不逐條存布林，全真才寫入，存了也只會是一排 true（見 D30）。選項本身以獨立容器（`space-y-2`）分組間距，不跟隨 Step 2 外層 `space-y-4` 的段落級間距（2026-08-07 修正，原本外層 `space-y-4` 把選項間距撐得跟「標題到清單」一樣大）

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
  | `no_response` | **未出席** | 約了但沒出現（no-show）。與預約表單第 4 步警語「若確定預約卻無故**不出席**，將不可再申請本站免費諮詢名額」（US9 驗收，`HighTicketBookingWizard.vue`）同一個詞 —— 申請人送出前讀到的就是它 |
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

- **FR-062**: 系統信的 CC MUST 收斂為**只有兩封**（原為一封，2026-08-17 加入提醒信）：
  | 信件 | CC |
  |------|-----|
  | 客製服務預約確認（`high_ticket_booking_confirmation`） | **該筆的顧問**；未指派顧問時才退回客服清單（FR-014） |
  | 客製服務面談提醒（`high_ticket_consultation_reminder`） | 同上（2026-08-17 加入，見 FR-078） |
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

- **FR-074**: 預約名單 MUST 支援 `?consultant=` 篩選，且該條件 MUST 加在 `HighTicketLeadController::bookingLeadsQuery()` 內 —— 列表分頁與 `statusCounts` 的 `GROUP BY status` 共用這同一個 builder，因此百分比會自動跟著收斂，**不得**另外複製一份條件到其中一邊。理由與 FR-067 同源：分母的定義決定百分比在講什麼，兩邊各寫一次遲早漂移，而漂移的症狀是「列表 3 筆、tab 卻寫 400 筆」這種沒人看得懂的畫面。狀態篩選仍 MUST NOT 進分母（FR-067 不變）
- **FR-075**: 參數取值：`consultant` 為空 = 不篩選；`consultant=none` = `whereNull('consultant_id')`；其餘一律 `(int)` 後 `where('consultant_id', ...)`。不存在的 id 回空集合即可（不 422）—— 這是一個檢視參數，網址被亂改的代價是看到空列表，不值得為它加一層驗證。選項來源 MUST 與週曆歸屬選擇器同一條件（`role = 'admin' OR is_sales_consultant = true`，依 `nickname` 排序），避免兩處的「誰是顧問」定義分岔；但 MUST NOT 沿用那裡的「顧問只能選自己」限制（見 D70）
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

- **FR-070**: 週曆的 Email 複製 MUST 為**純前端**功能：勾選狀態存在 `WeekGrid.vue` 的本地 state，地址來源為既有 props（`day.bookings[].email`），MUST NOT 新增端點、MUST NOT 新增請求。後端唯一的責任是繼續在 booked／held 區塊吐出 `email`（FR-071）
- **FR-071**: 可勾選的區塊限 `state ∈ {booked, held}` —— 這兩種才有申請人。暫留中一併納入是刻意的：那是「送了申請但還沒點確認信」的人，正是最需要被催的一批（使用者決策）。空格（未釋出／可預約）沒有 checkbox，不是 disabled 而是根本不渲染
- **FR-072**: 複製 MUST 走 `navigator.clipboard.writeText()`，且失敗時 MUST 有可見退路（唯讀輸入框 + 自動全選 + 手動複製提示）。理由不是保守：`navigator.clipboard` 只在 secure context 可用，本機 `http://localhost` 算 secure、區網 IP（`http://192.168.x.x`）不算 —— 用手機連內網測試時它會直接丟例外，而沒有退路的失敗長得跟「按了沒反應」一模一樣
- **FR-073**: 複製格式為 `implode(', ', $emails)`（半形逗號 + 一個空格），MUST 去重且 MUST 依畫面順序（日期 → 起始時間）。分隔符不可改為分號或換行 —— 目的就是貼進 Gmail／Outlook 的收件欄位

- **FR-069**: `ConsultationSlotService::availableStarts(int $minutes)` **不分諮詢長度**，一律 MUST 只保留台北時間分鐘數為 `0` 或 `30` 的起始時刻（2026-08-08 擴大範圍，取代原本僅 `$minutes >= 45` 才過濾的版本——業主原意就是所有場次都要對齊整點／半點的顧問行事曆習慣，8/7 的實作把範圍縮小到只有 45 分鐘場是誤判）。過濾發生在既有的「N 個連續單位皆可用」判定**之後**——先照 FR-028 找出所有合法起始，再依分鐘數篩選，兩條規則不互相依賴。

- **FR-076**: 面談提醒排程 MUST 以 `->timezone('Asia/Taipei')->dailyAt('17:00')` 註冊。伺服器跑 UTC，因此裸寫 `dailyAt('17:00')` 會是台北凌晨 1 點、`dailyAt('09:00')` 才是台北 17:00 —— 後者雖然能動，但檔案裡看到的數字與需求裡的數字對不上，任何人（含未來的你）想調整時間都得先在腦中做一次時區換算，而那正是這類設定最常出錯的地方（見 D71）
- **FR-077**: 收件範圍 MUST 由**台北時間**定義：`$tomorrow = now('Asia/Taipei')->addDay()`，區間為 `[$tomorrow->startOfDay(), $tomorrow->copy()->addDay()->startOfDay())` 轉 UTC 後查詢（半開，避免 `23:59:59` 這種邊界寫法漏掉 `23:59:30` 的可能）。判定錨點為 lead 的最早時段，非「任一時段落在區間內」—— 後者會讓跨午夜的面談在兩天各被撈出一次
- **FR-078**: 候選 lead MUST 同時滿足：`confirmed_at IS NOT NULL`、`cancelled_at IS NULL`、`reminder_sent_at IS NULL`，且其最早時段 `held_until IS NULL`（已確認佔用，沿用 FR-029 的推導）。`status` MUST NOT 參與過濾。`reminder_sent_at` 只在**寄送成功後**寫入 —— 先寫後寄會在寄信失敗時把人靜靜吃掉。
  **收件人**（2026-08-17 改，使用者決策）：`to` 為申請人，`cc` 為 `confirmationCc()` 的結果 —— 有指派顧問就只 CC 顧問，未指派才退回通知清單。與確認信共用同一支方法而非另寫一份收件邏輯：兩封信要回答的是同一個問題（這場面談歸誰），分成兩份定義只會在指派規則改動時漏掉一邊。附件規則不變，提醒信仍 MUST NOT 附 `.ics`（D73）
- **FR-079**: 新增 `email_templates` 一筆：

  | event_type | 名稱 | 可用變數 |
  |------------|------|---------|
  | `high_ticket_consultation_reminder` | 客製服務面談提醒 | `{{user_name}}`、`{{user_email}}`、`{{course_name}}`、`{{slot_time}}`、`{{consult_minutes}}`、`{{zoom_join_url}}` |

  MUST 同時登記於 `EmailTemplateSeeder::templates()`、`EmailTemplateController::$availableVariables`、後台列表的中文標籤映射，並由一支資料 migration 沿用 FR-052 的迴圈（讀 seeder、逐筆查 `event_type`、缺才 insert、**永不 update**）安裝到正式站。三處少一處的症狀各不相同且都很安靜：少 seeder 則新環境沒有、少 migration 則正式站沒有、少變數清單則管理員不知道有哪些變數可用
- **FR-080**: 提醒信 MUST NOT CC、MUST NOT 夾帶 `.ics`（使用者決策 + D73）。它是一封純提醒，不是一次異動通知
- **FR-081**: 排程 MUST 為「最佳努力」：模板缺失記 warning 後正常結束，單筆寄送失敗記 error 後繼續下一筆，命令 MUST 一律回 `SUCCESS`。理由是這條排程沒有任何下游依賴它 —— 預約、時段、Zoom、`.ics` 都已經是既成事實，讓排程變紅只會在 Forge 上製造一個沒有動作可做的告警。同理，排程整個停擺時 MUST NOT 影響任何其他流程（沿用 FR-035 的立場）

- **FR-082**: 新增 `GET /admin/consultation-slots/reschedule-options/{lead}`（staff 群組，宣告於 `{consultationSlot}` 之前）。回傳 `{minutes, slots: [{date, times: [{value, label}]}]}`，**與公開的 `course.booking-slots` 同一種形狀**（分組邏輯抽成共用的一段，不複製第二份）。lead 的 `isActiveBooking()` 為 false 時回 422 —— 沒有生效中的預約就沒有東西可以改期
- **FR-083**: `ConsultationSlotService::availableStarts(int $minutes, ?HighTicketLead $ignoring = null)` 新增第二個選填參數：`$ignoring` 名下的單位在可用性判定中視為**可用**。既有呼叫端（訪客精靈、候補判定）不傳第二參數，行為完全不變。理由與 FR-048 同源 —— 改期時把自己的格子當成佔用，會讓「往後挪 15 分鐘」這種最常見的操作看起來不可能
- **FR-084**: 改期成功後的導向 MUST 為新時段所在的那一週（`redirect()->route('admin.consultation-slots.index', ['week' => 新時段的台北日期])`），取代原本的 `back()`。`back()` 在同週改期時是對的，跨週時會把管理員留在一個看不到結果的畫面上
- **FR-085**: 面板的時段清單 MUST 每次進入改期模式重新抓取，MUST NOT 快取或隨頁面預先載入（見 D75）
- **FR-086**: 清單 MUST 排除該預約**目前所在的起始**（在 controller 過濾，`availableStarts()` 維持通用）。兩個理由：移到原地不是改期；而留著它會讓「沒有可用時段」的空狀態永遠不觸發 —— 下週還沒開時段時，管理員會看到一個只有自己現在時間的下拉，那比空清單更難懂。`groupStarts()` 的每組另帶 `value`（台北 `Y-m-d`）供前端送出，**MUST NOT** 用 ISO 字串前 10 碼推日期：那是 UTC 日期，台北 08:00 之前的時段會差一天

- **FR-087**: `purchases.course_plan_id` 為 **null 時代表「全部內容」**，這是整個 US21 的地基。它讓所有既有購買記錄、以及所有沒有選方案 UI 的路徑（前台結帳、贈課、匯入名單、積分兌換）零改動仍然正確 —— 它們本來就是全開。**只有兩個地方能寫入非 null**：Leads 開通與會員詳情的方案切換。MUST NOT 為了「明確」而給既有資料回填一個預設方案：那會把「這人買的時候還沒有方案這回事」竄改成「這人買的是方案 A」
- **FR-088**: 授權判定 MUST 收斂在兩個方法上，不得在 controller 各自展開：`Purchase::accessibleLessonIds(): ?array`（這筆授權涵蓋哪些小節，null = 全部）與 `Course::planLessonIdsForUser(?User): ?array`（教室用；無 user／admin／課程無方案／全開購買／drip 訂閱者一律回 null）。方案被刪等異常情況回**空陣列**而非 null —— 授權不明時寧可看不到，也不要外洩
- **FR-089**: 教室過濾 MUST 同時套用在**四個**判定點：章節側欄的 lessons filter、獨立小節的同一個 filter、決定 `currentLesson` 的 `$allLessons`、以及 `$completedLessonIds` 的查詢範圍。漏掉第三個會讓「第一個未完成」落在方案外（畫面空白）；漏掉第四個會讓方案外的完成紀錄灌水進度百分比。`markComplete` / `markIncomplete` MUST 各自再擋一次（403）—— 它們是獨立的公開端點，不受頁面渲染保護
- **FR-090**: `is_preview` 免費試閱（`ClassroomController::preview()`）MUST NOT 受方案影響。那是行銷用的公開試看，與購買授權是兩套東西
- **FR-091**: 進度分母 MUST 縮到該會員的方案範圍。實作為 `User::getCourseProgressSummary()` 新增選填的第三參數 `?array $scopeLessonIds`，不傳則行為完全不變（既有呼叫端與測試不受影響）。兩個呼叫端（`LearningController`、`MemberController::show()`）本來就在 iterate purchases，直接用同一筆 purchase 的 `accessibleLessonIds()`，不新增查詢（eager load `plan.lessons:id`）
- **FR-092**: 開通 MUST 在 **service 層**驗證方案：課程有方案而 `course_plan_id` 為 null → 422；plan 不屬於該 course → 422。前端的必填只是提示，不可作為唯一防線（沿用 FR-015 的同一立場）。既有的覆寫守門（`isOverwritable()` / 409 / `force`）**完全不動** —— 同為 `lead_conversion` 但方案不同就是升級，本來就在白名單內，直接覆寫是正確行為
- **FR-093**: 刪除方案 MUST 有兩層保護：service 層查 `$plan->purchases()->exists()`，有人持有就回 422 並說明人數；DB 外鍵用 `restrict` 兜底。**MUST NOT 用 `nullOnDelete`** —— 那會把持有該方案的會員靜默升級成全開，是這個功能最糟的失敗模式（安靜、看不出來、而且是往外洩的方向）
- **FR-094**: 方案切換端點 MUST 驗證 `$purchase->user_id === $member->id`（route model binding 不會幫你驗兩個參數的關係）與 plan 屬於 `$purchase->course_id`（null 合法 = 改回全部內容）。補價 `additional_amount` 為 `nullable|integer|min:0`，> 0 時**累加**到 `amount`（不是覆寫）；金額與方案兩個寫入包同一個 `DB::transaction()`。負數不收 —— 退款有既有的交易管理路徑，從這裡開一個第二入口只會讓帳更難對
- **FR-095**: 新增小節的通知信（`LessonController::store()`）收件人 MUST 排除「方案看不到這節」的會員。否則方案 A 的人收到「新增了一節」，進教室卻找不到，而他甚至不知道有方案 B 的存在（FR-089 的隱藏是徹底的）。
  **已知限制（實作時發現，2026-08-13）**：小節是先建立、之後才在章節列表勾選方案，因此**建立當下**那一節還不屬於任何方案 —— 在有方案的課程上勾「通知學員」，實際只會寄給 `course_plan_id` 為 null 的全開會員，所有持方案者都會被這條規則濾掉。這是 FR-095 的正確推論（那一刻他們確實看不到），不是 bug，但也代表「先歸類方案、再通知」目前沒有路徑。已由測試釘住實際行為；要補的話需另開一個「對指定方案補寄通知」的入口，不在本故事範圍

- **FR-096**: 方案與小節的歸屬有**兩個方向的端點**，各自驗證歸屬課程：`PUT /admin/lessons/{lesson}/plans`（一節對多方案，chip 用）與 `PUT /admin/plans/{plan}/lessons`（一方案對多節，章節捷徑用）。兩者皆送**完整結果集合**而非增減差異 —— 瀏覽器已知現況，送完整集合表示連點兩下不會讓兩邊漂掉。捷徑的三態判定（全選／部分／未選）MUST 由 lesson payload 的 `plan_ids` 現算，MUST NOT 另存一份狀態，否則捷徑與下方的 chip 會各說各話

- **FR-097**: 業績摘要的範圍 MUST 由 `bookingLeadsQuery($search, $courseId, $consultant)` 決定 —— 取該範圍內 leads 的 email 集合，再以 `users.email` 連到 `purchases`。**不加 `status` 條件**（FR-067 的同一理由：點進某個狀態不該改變摘要）。理由：`purchases` 沒有 `consultant_id` 也沒有「這筆是哪個 lead 轉來的」外鍵，唯一的連結是 email；而讓摘要與狀態色塊共用同一個 builder，是唯一能保證兩者不會各說各話的作法（沿用 FR-074）
- **FR-098**: 期間邊界 MUST 以**台北時間**建構再轉 UTC：本月為 `now('Asia/Taipei')->startOfMonth()`、年度為 `->startOfYear()`，區間取半開 `[start, next)`。伺服器跑 UTC，直接用 `whereMonth()` / `whereYear()` 會把台北 08:00 之前的成交算到前一天／前一個月（沿用 FR-077）
- **FR-099**: 計入條件為 `type = 'lead_conversion'` **且** `status = 'paid'`。退款作廢的成交 MUST NOT 計入營收 —— 這與 FR-008 把 `refunded` 視為可覆寫的作廢紀錄是同一個立場。成交人數 MUST 以 `count(distinct users.email)` 計算，金額以 `sum(purchases.amount)`；同一 email 在範圍內有多筆 lead（不同課程）時，購買紀錄 MUST 只算一次
- **FR-100**: 方案升級的補價（FR-094）**計入原成交月**（使用者決策）。補價是累加到既有 `purchases.amount` 上，`created_at` 不動，所以 8 月成交、9 月補的 5 萬會出現在 8 月的數字裡。**這是已知的失真，不是 bug**：要讓月營收反映實際進帳，需要一張逐筆進帳的記錄表與改寫升級流程，那是獨立的一個故事；在只有「後台手動開通」這一條成交路徑、補價又不常見的現況下，不值得為它先付這個成本

- **FR-101**: `endpoint.url_validation` MUST 在驗簽**之前**回應。Zoom 的 CRC 挑戰本身就是用來建立信任的握手，先驗簽等於要求對方先證明一件還沒建立的事；回應內容為 `{plainToken, encryptedToken}`，`encryptedToken = hash_hmac('sha256', $plainToken, $secret)`，且 MUST 在 3 秒內完成（Zoom 的硬性上限）
- **FR-102**: 其餘所有事件 MUST 驗簽：訊息為 `"v0:{x-zm-request-timestamp}:{raw request body}"`，期望值 `'v0=' . hash_hmac('sha256', $message, $secret)`，以 `hash_equals` 比對 `x-zm-signature`。不符 → 401。時間戳與現在相差超過 5 分鐘 → 401（擋重放）。**raw body MUST 取自 `$request->getContent()`**，不得用重新序列化的陣列 —— 任何欄位順序或空白差異都會讓 HMAC 對不上
- **FR-103**: webhook 端點 MUST 只做「驗簽 → 找 lead → 派 job → 回 200」。所有下載、解析、AI 呼叫都在佇列，因為 Zoom 要求 3 秒內回應，而校訂一小時的逐字稿是分鐘級的工作
- **FR-104**: 業務處理中的例外 MUST 吞掉並回 200（比照 000 對 Portaly / 藍新 webhook 的既有立場）。回非 2xx 只會讓 Zoom 進入重送迴圈，而重送不會讓壞掉的解析變好；驗簽失敗是唯一回非 2xx（401）的情況
- **FR-105**: `zoom_meeting_id` 對照 `payload.object.id` 時兩邊 MUST 轉 string 後 trim（Zoom 送數字型別，本站存 varchar），查的是 `consultation_notes`。對不到任何列時寫 `Log::info` 回 200 —— 同一個 Zoom 帳號可能有非面談用途的會議，那不是錯誤
- **FR-106**: 講者對應 MUST 先做機械式判定：以 `lead.name` 與 `lead.consultant?->name` 比對 VTT 的講者標籤（去空白、全形轉半形後比對），命中即映射為「客戶」／「顧問」。只有未能對應的標籤才交給校訂階段依語境判定。**誰是誰是已知事實，不該讓模型猜** —— 猜錯會讓整份紀錄的歸屬顛倒，而這是最貴的一種錯
- **FR-107**: 校訂 MUST 分段送出（約 4000 字／段，切點落在講者邊界，不切斷單一發言）。單次送完整份逐字稿會撞上輸出上限，而模型在接近上限時的行為是「開始摘要」而不是「截斷」，那正是最難察覺的失敗
- **FR-108**: 校訂 MUST 有**防縮水檢查**：任一段輸出長度 < 輸入的 60% 即判定為模型擅自摘要，**保留該段的機械式原文**並寫 warning log。一份被悄悄砍半的「逐字稿」比一份有錯字的逐字稿危險得多 —— 前者看起來完全正常
- **FR-113**: `consultation_notes` 的列由**預約確認**建立（`HighTicketBookingService::confirm()`，Zoom 會議剛開好、時段已定的那一刻），不是等 webhook 才 lazily 建立 —— 這樣後台在面談發生**之前**就看得到「已排定」，而不是只有事後才有東西。webhook 找不到對應列時仍只寫 log 回 200（FR-105），不補建：沒有預約紀錄的 Zoom 會議不屬於這張表
- **FR-114**: 改期 MUST 更新該列 `met_at`（沿用 US14 的既有立場：`consultation_slots` 只表達「現在誰佔著哪一格」，不留異動歷史）。取消 MUST **只在該列無 `transcript` 也無 `summary` 時刪除**；已有內容者保留 —— 談過了才取消後續安排的情況存在，那份紀錄不該跟著消失
- **FR-115**: `email` 是 `consultation_notes` 的唯一客戶識別鍵，寫入時 MUST 正規化為小寫（沿用 000 `email_suppressions` 的既有作法）。`lead_id` / `user_id` / `course_id` 僅為來源標記，**MUST NOT** 用於「找出這位客戶的所有面談」—— 那一律以 email 查詢，否則買了第二次顧問服務的人會被切成兩個人
- **FR-116**: 後台 lead detail row 顯示的是**以 email 查出的所有場次**（`met_at` 倒序），不限於當前 lead 的那一場。多場次是這張表存在的理由，只顯示一場等於回到 D92 被推翻的設計
- **FR-109**: 落庫的 `transcript` MUST 只有校訂後的匿名版。**原始 VTT、Zoom 顯示名稱與真實人名 MUST NOT 寫入任何資料表，也 MUST NOT 出現在任何 log**；`Log::` 只得記 lead id、字元數、模型、耗時。原始檔在 job 記憶體中處理完即釋放
- **FR-110**: 兩個步驟各有獨立守門，**MUST 都在跑 AI 之前檢查**，跳過的同時要省下該步驟的 token。逐字稿的守門是 `transcript_fetched_at` + 已有內容（`transcriptIsSettled()`）—— 我們同時訂閱 `recording.completed` 與 `recording.transcript_completed`（D88），所以同一場次收到第二次事件是常態而非例外，沒有這道守門就是同一份逐字稿付兩次校訂費。摘要的守門是 `summary_edited_at`：人寫的摘要不該被 Zoom 重送打回原形。兩者都以 job 的 `$force` 旗標（`booking:fetch-transcript --force`）刻意繞過
- **FR-111**: 「重新產生摘要」MUST 以該場次既存的 `transcript` 為輸入，MUST NOT 再向 Zoom 請求。Zoom 雲端錄影有保存期限，過期即永久取不回；把校訂稿留在自己的資料庫，正是為了讓摘要格式日後能隨時調整重跑。逐字稿為空時回 422（提示尚未取得逐字稿），不是 500
- **FR-117**: `ProcessZoomTranscriptJob` MUST 跑在專屬的 `database_long` 佇列連線上，並自帶 `$timeout`（1500 秒）。**這不是調校而是正確性問題**：worker 預設 timeout 為 60 秒，而校訂一小時的逐字稿是數次連續 LLM 呼叫、分鐘級的工作，預設值會在中途砍掉它；更糟的是 `database` 連線的 `retry_after` 為 90 秒，job 只要跑超過 90 秒佇列就判定它已死、把同一份 payload 交給第二個 worker —— 重複處理、重複付 API 費用。`retry_after` MUST 恆大於 job 的 `$timeout`（本連線設 1800）。獨立連線而非直接調高 `database` 的 `retry_after`，是為了讓卡住的**信件** job 仍在 90 秒後重試，而不是等半小時
- **FR-142**: `long` 佇列 MUST 由排程消費（`queue:work database_long --queue=long --stop-when-empty`，每分鐘、背景執行、`withoutOverlapping`），MUST NOT 依賴正式站上人工啟動或人工維護的 worker。這條是正確性而非部署偏好：沒有消費者時整條自動路徑**無聲失敗** —— webhook 回 200、工作單進表、`reserved_at` 恆為 NULL、log 一行錯誤都沒有，後台只是永遠停在「尚無逐字稿」。同樣的故障已發生兩次（2026-08-17、2026-08-22，見 D109）。`--stop-when-empty` MUST 保留：少了它排程會養出一個常駐行程，而後續每一次 tick 都會被重疊鎖跳過。測試 MUST 釘住這條排程存在（T335）
- **FR-133**: 手動抓取端點 MUST 為**兩段式**：同步向 Zoom 查 `GET /meetings/{id}/recordings`（一秒內），只有清單裡確實有 TRANSCRIPT/VTT 才派 `ProcessZoomTranscriptJob` 並回 202。理由是這顆按鈕要回答的問題是「到底好了沒」，而三種答案裡有兩種（Zoom 上沒有錄影、逐字稿還沒產出）在那一秒內就確定了；把整段丟進佇列會讓這兩種情形變成「按了、等三分鐘、畫面還是一樣」。反過來也不能整段同步：25k 字的校訂是七次連續 LLM 呼叫、2–4 分鐘，nginx / PHP-FPM 的 60 秒讀取逾時會先斷線，而工作其實還在跑（見 FR-117 的租約論證）。
  查詢錄影清單的實作 MUST 收斂為 `ZoomTranscriptService::recordingPayload(string $meetingId): ?array`，回傳與 webhook 同形的 `['object' => ..., 'download_token' => '']`，`booking:fetch-transcript` 指令改為呼叫同一個方法 —— 兩處各寫一次 HTTP 呼叫，就會有兩套錯誤處理與兩種逾時
- **FR-134**: 手動抓取 MUST NOT 在後端另設「已有逐字稿就拒絕」的守門。`ProcessZoomTranscriptJob` 的 `transcriptIsSettled()`（FR-110）已經擋住重複校訂，而按鈕本身在有逐字稿時就不顯示（UI 層）。同一條規則寫三次的代價不是多餘的程式碼，是三處會各自漂移
- **FR-132**: payload 的 `recording_files` 裡沒有 TRANSCRIPT 檔時，job MUST 寫一行 info log 後靜默結束，**MUST NOT 丟例外交給 backoff**。`recording.completed` 依定義不帶 VTT（逐字稿由 `recording.transcript_completed` 另行送達，帶自己的檔案清單），而 job 拿到的是**派工當下凍結的 payload 快照** —— 重試只會重讀同一份清單，永遠不可能讀出逐字稿。原設計因此讓每場面談固定產生一個註定失敗的 job（三次重試 + 一筆 failed_jobs + 兩行 ERROR log），把「這一則不是逐字稿事件」記成故障（D88 修訂）。逐字稿為空時 `summarise()` 已回 null，故靜默結束不會產生空摘要
- **FR-112**: `openai_api_key` 或對應的 `ai_prompts` 列不存在時，AI 步驟 MUST 靜默跳過而非丟例外，逐字稿仍以機械式解析結果落庫（沿用 D40 對 Zoom 的既有立場：未設定憑證即該路徑不存在，本機與 CI 永遠不需要真的 key）
- **FR-122**: 前台時段選擇器 MUST 為**日期成欄、時間直向堆疊**的版面（原本是每日一列、時間橫向 wrap，兩天各四格就把第三天推到摺線下，「我什麼時候能來」變成要捲動才答得出來）。翻頁 MUST 用左右箭頭按鈕，MUST NOT 用橫向捲動 —— 觸控板難控、手機更差，且看不出可預約期間有多長。
  分頁單位 MUST 是**連續 N 個有時段的日期**，MUST NOT 依日曆週切分：可預約日稀疏且不平均，一個 Mon–Sun 頁常只有兩三欄，右側整片空白 —— 那片留白不帶任何資訊（空日期本來就不顯示）。欄位 MUST 平分整列且不設 `max-width`，使每頁填滿卡片、任何螢幕都不需橫向捲動；每頁欄數依螢幕寬度切換（< 640px 四欄、≥ 640px 六欄，`matchMedia`）。
  **重新分頁（換頁、轉螢幕、套用優惠碼後重抓）MUST 停留在目前所選時段的那一頁**，不得一律回到第一頁 —— 選了三週後的時段卻被彈回開頭，讀起來像選擇被清掉了。日期分行顯示（`8/6` / `週三`）於 Vue 端拆字串，MUST NOT 改 `ConsultationSlotService::dateLabel()`（後台週曆與確認信共用該字串）。
- **FR-121**: 主表格姓名旁的徽章 MUST 只在**該客戶至少有一份非空摘要**時出現，數字為有摘要的場次數（非總場次數），點擊直接開啟**最近一份**摘要的 modal。徽章是「有東西可讀」的承諾 —— 今早剛成立的預約有紀錄但裡面是空的，為它顯示徽章等於邀請一次白點；空場次仍列在展開列裡。讀摘要是打開這一列的唯一理由，把它做成「先展開、再找按鈕」是對常見路徑課稅。徽章 MUST 是展開鈕的**同層兄弟**而非其子元素 —— button 套 button 是無效標記，而在 `<span>` 上掛 `@click.stop` 會讓它鍵盤永遠到不了。動作列與徽章的位置以「時間 → 動作 → 次要資訊」排序，MUST NOT 用 `ml-auto` 把動作推到列尾
- **FR-120**: 後台每場次 MUST 收斂成兩個連結：「摘要」開 modal 檢視／編輯，「下載逐字稿」輸出 `.txt`。逐字稿 **MUST NOT 在頁面上直接呈現** —— 一大片對話塞在已展開的表格列裡，會讓上下場次無法掃讀，而摘要才是真正被讀的東西。既然不再顯示，leads payload MUST 改送 `LENGTH(transcript)` 而非本文（用 `LENGTH` 不用 MySQL 的 `CHAR_LENGTH`，因為測試跑在 sqlite 上）：一頁 20 筆 lead、每筆帶著該客戶所有場次，逐字稿本文可達 200k 字元，不送才是把「不直接呈現」落實到傳輸層而非只是視覺上藏起來。**連帶清除**：逐字稿既不可編輯，`updateTranscript()` 端點、其路由與 `transcript_edited_at` 欄位 MUST 一併移除（migration `..._000003` 卸掉該欄）—— 一個永遠為 null 的時間戳是在邀請後人寫程式去檢查它。原本掛在該欄上的覆寫守門改以 `transcript_fetched_at` 表達，見 FR-110
- **FR-119**: 面談紀錄只在**預約確認當下**建立，因此本功能上線前已確認的預約一筆紀錄都沒有 —— 而沒有紀錄，webhook 就沒有 `zoom_meeting_id` 可對，逐字稿會直接被丟掉。MUST 提供 `booking:backfill-consultation-notes` 補建，且 MUST 與確認路徑共用 `recordConsultationNote()`（該方法以 `zoom_meeting_id` 比對既有列，這正是補建可重複執行而不產生重複列的原因）。已取消的預約 MUST 略過（沿用 FR-114 的立場：那場會議不會發生了）
- **FR-118**: 後台 MUST 能刪除單一場次（誤約、測試約、客人走錯房間）。硬刪除而非軟刪除 —— 沒有還原介面可以正當化留著那列，而隱藏的列仍得在每一處以 email 查歷史的地方被過濾掉。**MUST NOT 以「有內容就不准刪」設限**：促成這個需求的正是一場短暫誤入而確實產生了錄影的會議，設限等於擋掉需求本身；升級版的確認對話留在 UI，可追溯性靠 `Log::warning`（只記 note id、lead id、`met_at`、字元數與操作者 id，內容仍受 FR-109 約束）。刪除 MUST 是終局的 —— Zoom 會連續數日重送 `recording.completed`，webhook 找不到對應列即回 200 且不重建
- **FR-123**: 資格審核共五題，題目、選項、儲存值與計分定版如下。滿分 10 分，**5 分（含）以上通過**（`BookingScreening::PASS_SCORE`）。標「否決」者無論總分多少一律不通過（使用者決策）。

  **Q1 `screen_timeline` — 你希望在多久內開始改善目前的問題？**

  | 儲存值 | 選項文案 | 分數 |
  |--------|---------|------|
  | `immediate` | 立即，希望 1 個月內開始 | 2 |
  | `1_3m` | 1–3 個月內 | 2 |
  | `3_6m` | 3–6 個月內 | 1 |
  | `6m_plus` | 6 個月以上 | 0 |
  | `exploring` | 目前只是先了解，沒有明確時間表 | 0 |

  **Q2 `screen_budget` — 如果確認這項服務適合你，你目前可考慮投入的預算大約是多少？**

  | 儲存值 | 選項文案 | 分數 |
  |--------|---------|------|
  | `over_100k` | NT$100,000 以上 | 2 |
  | `50k_100k` | NT$50,000–100,000 | 2 |
  | `10k_50k` | NT$10,000–49,999 | 2 |
  | `6k_10k` | NT$6,000–9,999 | 1 |
  | `under_6k` | NT$5,999 以下 | 0 |
  | `none` | 目前沒有預算 | 0 **· 否決** |
  | `unsure` | 不確定，希望先了解方案內容與價格 | 1 |

  級距對應實際方案定價（入門 8,888 / 一般 36,000 / 旗艦 58,000）：**買得起主力方案的門檻在 1 萬以上，故 `10k_50k` 給滿分**；`6k_10k` 只買得起入門級，是客戶但不是最該佔用 1v1 的那種，給 1 分。`unsure` 給 1 分而非 0 —— 銷售頁本來就隱藏價格，「想先知道多少錢」是誠實回答，不是低意圖。

  **Q3 `screen_authority` — 關於這筆投入，你目前的決策狀態最接近哪一種？**

  | 儲存值 | 選項文案 | 分數 |
  |--------|---------|------|
  | `self` | 我可以自行決定，只要確認適合就能開始 | 2 |
  | `discuss` | 我是主要決策者，但需要和伴侶／夥伴討論 | 2 |
  | `approval` | 需要其他人共同核准 | 1 |
  | `none` | 我目前沒有決策權 | 0 |
  | `not_considered` | 還沒想過是否要投入 | 0 |

  **Q4 `screen_pain` — 如果接下來 3–6 個月都沒有改善這個問題，對你的影響有多大？**

  | 儲存值 | 選項文案 | 分數 |
  |--------|---------|------|
  | `severe` | 影響非常大，已經造成明顯損失或壓力 | 2 |
  | `high` | 影響很大，希望盡快解決 | 2 |
  | `moderate` | 有影響，但目前仍可接受 | 1 |
  | `low` | 影響不大 | 0 |
  | `curious` | 只是想提前了解 | 0 |

  **Q5 `screen_next_step` — 如果這次討論後，你認為方向適合，你最可能採取哪個下一步？**

  | 儲存值 | 選項文案 | 分數 |
  |--------|---------|------|
  | `start_now` | 希望盡快確認合作方式並開始 | 2 |
  | `evaluate` | 願意認真評估方案與費用後決定 | 2 |
  | `compare` | 需要再比較其他選項 | 1 |
  | `diy` | 想先自己嘗試一段時間 | 0 |
  | `advice_only` | 目前主要是想獲得一些建議，暫時沒有合作打算 | 0 |

  分級（僅供後台顯示，不影響通過與否）：8–10 高購買意願、5–7 值得談但需 qualification、0–4 培育名單。
- **FR-124**: 五題**全部必答**。未作答在計分制下只能算 0 分，把它留成選填等於做一題「答不答都算你錯」的扣分題，而使用者永遠不會知道自己被什麼擋住。
  題目與選項文字由後端下發（單一來源見 D101），**分數與否決旗標 MUST NOT 出現在任何前端 payload**：把評分表放進網頁原始碼，等於在閘門旁邊貼上答案。審核回應同理只回 `{passed}`，**不回分數** —— 分數是給顧問看的，不是給申請人看的。
- **FR-125**: `POST /course/{course}/screen`（throttle:10,1）：驗證 Email／暱稱／五題 → 伺服器端計分 → 建立或更新該 `email + course_id` 的 lead，寫入五題答案、`screening_score`、`screened_at`，回 `{passed: bool}`。
  **審核即落地**是刻意的：這一步的產出就是「這個人是哪一種」，而中途離開的人正是最需要留住的資料。通過者以 `status = pending` 存在（尚無 `confirm_token`、無時段），未通過者見 FR-126。
  **MUST NOT 覆寫進行中的預約**：`confirmed_at` 非 null 或 `status` 已非 `pending` / `declined` 者，只更新答案與分數，`status` 與 `declined_at` 一律不動 —— 一個已確認的客人回來把玩表單，不該把自己的預約洗成婉拒。
- **FR-126**: 未通過者的落庫形狀：`status = declined`、`declined_at = now()`、`confirm_token` 與 `confirm_expires_at` 皆 null、名下無 `consultation_slots`。並且 MUST NOT：寄出任何信件（婉拒信與內部 CC 通知都不寄，使用者決策）、觸發 `DripService::checkAndBook()`、送出 Meta CAPI 事件。
  **不停序列信是重點而非疏漏**：這些人來自電子書名單，婉拒的意思是「現在不排 1v1」，不是「不要再聯絡」—— 停掉序列信等於把唯一還在運作的加溫管道也關掉。
- **FR-127**: `declined` 是 `high_ticket_leads.status` 的第 7 個值，新增時 MUST 同步四處，漏一處就是一個安靜的錯誤：enum migration、`HighTicketLeadController::updateStatus()` 的 `in:` 驗證清單（漏了則管理員改不回其他狀態）、`BookingListTab.vue` 的 `statusButtons`（漏了則該列狀態方塊全部呈現未選中）、`HighTicketBookingService::recordLead()` 的可復活清單（漏了則婉拒過的人永遠停在 declined，見 D97）。
- **FR-128**: 送出審核後 MUST **一律**顯示「自動審核中」畫面（進度條 + 倒數秒數），通過與否都一樣 —— 只有被擋的人要等，會讓兩條路徑的速度差自己說出答案。倒數長度為 **15 秒**（`REVIEW_SECONDS`，2026-08-17 由 60 秒縮短，使用者決策：60 秒足以讓通過的人以為當掉了）。倒數 MUST 由前端執行，伺服器 MUST NOT sleep、MUST NOT 延遲回應：掛住一個 request 會佔用 PHP-FPM worker，而中途斷線會把已經完成的判定顯示成失敗。
  婉拒文案定版：「感謝您的申請。根據您的現況，我們判斷現階段可能不是最適合安排一對一諮詢和推進下一步計劃的時機，因此此次先不安排預約。謝謝您的理解，祝您接下來的規劃順利。」
  畫面上 MUST NOT 出現分數，也 MUST NOT 指出是哪一題造成的 —— 那等於附上一份修改指南。
- **FR-129**: `book` / `waitlist` 送出時，伺服器 MUST 以**該次請求帶上來的答案**重新計分並擋下未通過者（422），MUST NOT 只信任 lead 上已存的 `screened_at` —— 否則用 A 信箱通過審核、用 B 信箱送出申請就繞過整道閘。
  **答案完全未帶時放行**：`?resume=` 從「新時段通知」信回站的人不會重做審核，本功能上線前的舊 lead 也沒有答案，擋下他們是拿一道軟性過濾去砸真實預約。這確實留下一個「不送欄位就過」的縫，而 D97 已經接受了這道閘門本來就繞得過去。
- **FR-130**: 預約精靈的步驟結構改為五步：**1 資格 → 2 資料 → 3 承諾 → 4 時段 → 5 確認**。US9 驗收條款中提到的 Step 編號一律以此為準（原 Step 1 的問卷欄位移至新 Step 2，原 Step 2/3/4 各順延一格）。回到第一步 MUST NOT 重跑審核倒數 —— 答案未變更時直接放行，變更後才需重審。
- **FR-135**: 續填提醒的命中條件 MUST 為六者同時成立：`screened_at` 非 null 且在 **3 小時前～7 天內**、`resume_reminder_sent_at` 為 null、`phone` 為 null、`confirmed_at` 為 null、`status = pending`。
  三個守門各有各的理由：**3 小時**是不要在對方還開著分頁時就寄信；**7 天**是功能上線那一刻不能對整份歷史名單發信；**`status = pending`** 是管理員一旦動過這筆就代表有人在手動處理，機器不該同時也在寫信。
  `phone` 而不是別的欄位當「沒走到第二步」的判準：它是第二步的第一個必填欄位，有值即代表對方走得比這封信要處理的情形更遠。
- **FR-136**: 提醒 MUST 一生只寄一次，且 MUST 在**寄送成功後**才蓋 `resume_reminder_sent_at`（先蓋後寄會在寄信失敗時把人永久吃掉）。欄位 MUST 獨立於 `last_notified_at`（US4 的「通知新時段」）—— 共用一個時間戳等於寄出其中一封就默默取消另一封。
  排程 MUST 為每小時、限台北 09:00–21:00：這封信要救的動能以小時計，每日一次會讓它晚到十幾小時；而不限時段就會在凌晨三點寄一封關於「你的表單還沒填完」的信。
- **FR-137**: `?resume=` 回站時，若該 lead 的既存答案**仍然通過**，`bookingDraft.screening_cleared` MUST 為 true，精靈據此**直接開在第二步**且不重跑 15 秒審核。
  這個判斷 MUST 在伺服器端做，MUST NOT 由前端看著自己被預填的答案自行認定通過 —— 一個被婉拒的 lead 重新載入頁面時，草稿裡同樣帶著那五個答案，讓前端自行判斷等於把閘門交給被擋下的人保管。送出時仍會重新計分（FR-129），這是第二道。
- **FR-131**: 後台 Leads 展開列 MUST 顯示「資格審核」區：分數 `N/10`、分級標籤（8–10 高購買意願 / 5–7 值得談 / 0–4 培育名單）與五題答案的中文標籤。**分數與分級只在後台出現**（FR-124）。無審核紀錄的舊 lead 顯示一句說明，MUST NOT 印五排「—」。

- **FR-138**: 後台婉拒 MUST 是**單一原子動作**，一次做完四件事：釋出該 lead 佔用的時段單位、刪除 Zoom 會議、寄出婉拒信（附 `METHOD:CANCEL` 的 `.ics`）、`status` 落 `declined`。
  MUST NOT 拆成「先取消、再改狀態」兩步交給管理員接力 —— 那正是現況，而現況的兩種漏做各自產生一種說謊的名單：只取消不標記讓這筆看起來像對方臨時有事，只標記不取消讓時段永遠卡在週曆上。
  拆預約的那段機制（transaction 內釋出單位 + 清空 `zoom_meeting_id` / `zoom_join_url`、`calendar_sequence` 遞增、面談紀錄依 FR-114 保留或刪除、Zoom 刪除走 `syncZoom()`）MUST 只有一份定義，`cancel()` 與 `decline()` 皆委派（D104）。
- **FR-139**: 婉拒的狀態不變量：
  `cancelled_at` 與 `declined_at` MUST 同時寫入。`isActiveBooking()`（以及前端的 `hasLiveBooking`）判斷的是 `cancelled_at`，只寫 `declined_at` 會讓一筆已經沒有時段、沒有 Zoom 會議的 lead 繼續被系統當成生效中的預約。
  端點 MUST 自行檢查 `isActiveBooking()`，非生效者不做任何事並回紅色 flash；按鈕的顯示條件是體驗，不是授權。
  `recordLead()` 在把 `declined` / `cancelled` 轉回 `pending` 時 MUST 一併清空 `declined_at`（比照既有的 `cancelled_at`）。現況只有帶著審核答案的路徑會清，`?resume=` 回站的舊 lead 因此會頂著一枚已經不成立的「已婉拒」標記出現在名單上。
- **FR-140**: 婉拒信 MUST 走既有 email_templates 機制，`event_type` = `high_ticket_booking_declined`（顯示名「預約婉拒通知」），變數 `{{user_name}}` / `{{user_email}}` / `{{course_name}}` / `{{slot_time}}`。
  **婉拒理由寫在模板內文裡**（使用者決策，D105），MUST NOT 做成逐筆輸入的欄位。
  MUST NOT CC 任何人（沿用 FR-062）：這是一封我方做出決定後的告知信，沒有第三方需要據此行動。
  同一次婉拒 MUST NOT 另外觸發 `high_ticket_booking_cancelled` —— 兩封信對同一件事給出兩種說法。
  MUST NOT 附「歡迎重新預約」的課程連結：那與這封信要說的事互相矛盾。
- **FR-141**: 入口為預約名單（`BookingListTab`）每一列的「婉拒」按鈕，MUST 二次確認，且確認框 MUST 印出將寄出的理由全文（由後端隨頁面下發模板內文，MUST NOT 在前端另寫一份）。
  完成後 MUST 以 Inertia 局部重載該頁並 `preserveScroll`，MUST NOT 只在前端改寫該列的 `status` —— 這個動作同時改了狀態、兩個時間戳與時段欄，逐一手動同步等於在前端維護第二份真相（D108）。


## 設計決策

- **D96**: 閘門放在**第一步**，五題答完當場給結果（使用者決策，2026-08-17 推翻本 spec 初版的「填完整份申請才婉拒」）。
  推翻的理由不是體驗，是成本歸屬：申請人幾乎全部來自電子書的序列信名單，**被婉拒之後仍留在名單裡繼續收信**。一個花五分鐘寫完瓶頸、選好時段、然後被系統擋下來的人，接下來每一封序列信都在提醒他那件事 —— 反彈不會隨著他離開漏斗而消散，它留在資產內部發酵。初版把這筆成本算成「對方多花的力氣」，那是把一次性流量的直覺套在一個名單型漏斗上。
  代價是初版換到的兩件事都要放棄：一是被婉拒者不再留下瓶頸／專長等自由文字（現在只留五題答案），二是閘門的繞過成本從「重寫五分鐘問卷」降到「退回去改一個選項」。後者由 D97 正面接受。
- **D97**: 婉拒**不黏**，而且這道閘門**本來就繞得過去**。不記黑名單、不設冷卻期，同一 email 重做審核、答案改了即照常通過（`declined` 併入 `recordLead()` 既有的可復活清單，與 `closed` / `no_response` / `cancelled` 同級）。
  兩個理由。其一，這五題問的是**現況**不是身份：預算與急迫度三個月後就變了，把一次「現在沒錢」變成永久標記，擋掉的正是最該被擋回來的那種轉變。其二，一個願意退回去把答案改成「我有 5 萬、我能自己決定」的人，跟隨手點完就走的人已經不是同一群 —— 這道閘門要分的就是這個，而那句改過的答案會存在資料庫裡，面談時攤開來就是最好的開場。
  真正的守門仍在面談裡。這也是 FR-129「答案沒帶就放行」可以接受的原因。
- **D98**: 新增第 7 個狀態 `declined`（已婉拒），而不是沿用 `closed` 加一個旗標（使用者決策）。自動婉拒與人工關閉是兩種不同的事實 —— 混在同一桶裡，漏斗 pill 上的「已關閉 N 筆」就再也答不出「有多少人是在資格審核就被擋掉的」，而那個數字正是這個功能上線後唯一要看的指標。
  代價是狀態列從 6 顆變 7 顆（窄螢幕要能換行），以及 FR-127 那份四處同步清單。
- **D99**: 審核倒數**所有人都等**，通過與否一視同仁（使用者決策）。只讓被擋的人等，等於用速度差把答案講出來 —— 通過的人瞬間進下一步、被擋的人轉一分鐘，任何試第二次的人都會發現。
  這在新的步驟結構下才成立：等待點從「填完五分鐘問卷之後」移到「點完五題之後」，通過者付出的是十幾秒而不是五分鐘加一個落空的時段，而那段等待同時把「有一道審核存在」這件事變成可信的。
- **D100**: 計分用規則表，**不用 AI 模型**（使用者提問，本 spec 給出的建議）。五題全是單選，答案是 key 不是自然語言，計分是一張對照表的加總 —— 這件工作沒有任何一項是模型比 `array_sum()` 做得好的。用模型要付三種代價：同一組答案可能在不同時間得到不同結果（被申訴時無法還原當初為什麼擋人）、多一次 API 延遲與費用、以及一個沒有 API key 就跑不動的本機與 CI 環境。那段倒數是體感設計，不是算力需求。
  **AI 該放的位置在後台**：把五題答案加上第二步的自由文字（瓶頸、專長）餵給既有的 `OpenAiService`，產出「這位申請人的購買意願摘要與建議切入點」給顧問在面談前看 —— 那裡有自由文字（模型的強項）、判斷錯了也不傷人，且沿用 000 US10 的 `ai_prompts` 基礎設施。列為日後獨立 US，本次不做。
- **D101**: 題目、選項文案與計分表只有一份定義，放在 `app/Support/BookingScreening.php`（PHP 端），前端的題目與選項由 `CourseController@show` 以 prop 下發。
  放 PHP 而不是放 `resources/js/lib/`：計分**必須**在伺服器端執行（FR-129），所以後端一定要有完整的表；如果文案另放前端，就會出現「兩份清單各自演化」的經典失敗 —— 前端加了一個選項、後端的 `Rule::in` 沒加，該選項的每一次送出都是 422，而錯誤訊息不會提到任何一題。
  下發時 MUST 過濾掉 `score` 與 `veto`（FR-124）。沿用 `app/Support/PhoneNumber.php` 的既有位置慣例。
- **D102**: 第一步保留**暱稱**欄位，只有 Email 是不夠的。一是 `high_ticket_leads.name` 是 `NOT NULL`，而審核當下就要建 lead（FR-125），不留欄位就得改 schema 或塞空字串；二是婉拒畫面能叫得出名字，那句話讀起來的差別比一個欄位的成本大得多。
- **D87**: 走 Zoom webhook- **D87**: 走 Zoom webhook 而不是排程輪詢 `GET /users/me/recordings`。輪詢要嘛延遲要嘛浪費 —— 面談是低頻事件，為了 15 分鐘的延遲每天空掃幾百次沒有意義；webhook 的代價是要多維護一個公開端點與驗簽，但那份程式碼本站已經寫過三次（Portaly、PayUni、藍新），是熟路。
  代價是 Zoom Marketplace 的事件訂閱設定不在 git 裡，換站台要人工重設一次 —— 已寫進 US23 的上線前提。
- **D88**: 同時訂閱 `recording.transcript_completed` 與 `recording.completed`。逐字稿比錄影晚幾分鐘產出，只收後者會拿不到檔；但前者的事件名稱**必須在實作時於 Zoom Marketplace 的事件清單確認確實可訂閱**（公開文件查不到這一層）。兩個都收 + job 端自行判斷 TRANSCRIPT 檔在不在，是唯一不依賴那個確認結果的作法。
  **已證實可訂閱（2026-08-17）**：正式站觀察到同一場會議的兩則事件各自帶著自己的 `recording_files` —— 先到的 `recording.completed` 只有 `[MP4, M4A, TIMELINE, CHAT]`，**3 小時 27 分後**才到的 `recording.transcript_completed` 只有 `[TRANSCRIPT/VTT]`。D88 標記的「事件名稱待確認」到此結案，同時也量到了逐字稿的實際延遲遠大於「幾分鐘」。
  **修訂（2026-08-17，正式資料推翻原本的「不在就靠 backoff 重試」）**：重試對這件事完全無效。job 收到的是**該次事件的 payload 快照**，`recording_files` 清單在派工當下就凍結了，重跑只會重讀同一份清單 —— 逐字稿是由**另一個事件**帶著自己的清單進來的。原設計等於每場面談固定產生一個註定失敗的 job：兩行 ERROR log、三次重試、最後一筆 failed_jobs，而那個「失敗」的實際意義只是「這一則不是逐字稿事件」。改為靜默 return（見 FR-118）。`backoff` 保留給重試真的有用的情形：下載失敗、Zoom / OpenAI 暫時性錯誤。
- **D89**: 逐字稿**保留**而非只存摘要（使用者決策，推翻初版）。留著才能在改摘要格式後直接重跑、才能回頭查原話；一小時面談約 30–60KB 文字，量體完全不構成問題。真正的風險是隱私，而那由 D90 處理。
- **D90**: 講者一律正規化為「顧問」／「客戶」，原始姓名不落庫。這讓「保留全文」的隱私成本大幅下降 —— 資料庫裡是一份去識別化的對話稿，而客戶身份本來就在同一列的 `name` / `email` 欄位上，重複存一次在逐字稿裡只增加外洩面、不增加任何資訊。
  機械式對應優先、模型只處理殘留（FR-106）：誰是誰是已知事實，不是推理題。
- **D91**: 校訂與摘要拆成兩次 API 呼叫，不用一次呼叫同時產出兩者。要求模型「原樣輸出整份逐字稿並額外附上摘要」會把輸出長度推到上限附近，而模型在那個區域的失敗模式是悄悄開始摘要（FR-108 就是在防這件事）。兩次呼叫多花的錢在 `gpt-5.6-luna` 的價位是零頭。
- **D92**: 面談紀錄獨立成 `consultation_notes` 表，**以 email 為客戶識別鍵**，一場面談一列（使用者決策，2026-08-16 推翻初版的「存在 lead 欄位上」）。
  初版把六個欄位掛在 `high_ticket_leads` 上，前提是「一個 lead 對一場面談」—— 但那個前提只在**售前**成立。買了多次一對一顧問的客戶會有第二、第三場，而那些場次不屬於任何 lead；即將開發的「客戶自行登記時段的顧問服務」也要寫進同一個地方。掛在 lead 上等於把售前的一次性接觸當成客戶關係的全部，第二場一來就要搬遷。
  **email 而非 `user_id` 或 `lead_id` 當識別鍵**：沿用 000 D22 對 `email_suppressions` 的既有立場 —— email 是所有管道唯一的共同鍵，受訪者不一定是會員（lead 階段還沒有帳號），而 `lead_id` 只存在於售前那條路徑。`lead_id` / `user_id` 都留欄位但可為 null，是**來源標記**不是識別鍵。
  代價是後台要多一次以 email 的查詢，而不是跟著 lead 一起載出來 —— 換來的是同一個人的所有面談自動聚在一起，這正是 CRM 的最小要求。
- **D93**: 兩個 `*_edited_at` 各自獨立守門，而不是用一個「已人工介入」旗標。逐字稿與摘要是兩種不同的東西 —— 顧問可能修了逐字稿卻想讓 AI 重出摘要（這正是最有價值的路徑：修正辨識錯誤 → 重跑 → 得到更準的摘要），一個旗標會把這條路堵死。
- **D94**: 用 `Http::` facade 打 OpenAI，不引入 SDK。OpenAI 沒有官方 PHP SDK，而本站全部對外 API 呼叫（Meta CAPI、Zoom、Blog RSS）都是 `Http::`；為一支 POST 引入社群套件，換來的是一個要跟著升級的相依。
- **D95**: 摘要用固定 Markdown 結構而非 JSON structured output。這份東西的讀者是顧問，不是程式 —— 沒有任何程式會去 parse 它的欄位，存成 Markdown 就能直接在 textarea 裡編輯與閱讀。改用 JSON 只會多一層渲染，且讓人工編輯變成要顧格式的苦工。
- **D85**: 業績摘要放在 Leads 頁而不是擴充「交易管理」或營收圖表。那兩處是全站口徑，混著線上刷卡、贈課與積分兌換；顧問要問的是「我這個月談成幾個、多少錢」，而那個問題的上下文就是他正在看的這份名單。把數字放在他已經在看的畫面上，比要他切頁再自己心算篩選條件便宜得多。
  代價是同一份營收在兩個地方各有一種口徑（全站 vs 顧問線），這是刻意的 —— 兩個問題本來就不同，硬合成一個會兩邊都答不好。

- **D86**: 成交人數以 **email 去重**而非算購買筆數（使用者決策）。「人數」字面上就是這個意思，也是顧問看自己成交了幾個客戶的直覺。代價是人數與金額的加總基礎不一致（一個人可能貢獻兩筆金額），因此 UI **不得**呈現「平均客單價」之類由兩者相除得到的數字 —— 那會是個沒有意義的商。

- **D84**: 章節捷徑做成**卡片內的 popover**而非展開式面板（2026-08-13，業主先要求壓低卡片高度、隨後要求加捷徑，兩者直接衝突）。連帶必須拿掉方案面板外層的 `overflow-hidden`（否則下拉被裁切），圓角改由 header 自己的 `rounded-t-lg` 承擔。關閉用透明 backdrop 而非 document listener —— backdrop 不可能活得比 popover 久，不會留下殘留監聽。
  「部分」狀態刻意**不**做成第三種點擊行為（點了變全選、再點變清空），而是併入「尚未選滿」：管理員手動勾過幾節之後點章節捷徑，預期是補齊而不是被清掉，而誤清的代價遠高於多點一次。

- **D83**: 方案掛在 `purchases.course_plan_id`，而不是另開一張 `user_course_plan` 表。`purchases` 已經有 `unique(user_id, course_id)`，一人一課本來就只有一筆記錄，而「買到哪個層級」正是這筆記錄的屬性 —— 另開一張表會產生兩份真相，且立刻要回答「有 purchase 沒有 plan 列」與「有 plan 列沒有 purchase」這兩種不該存在的狀態該怎麼辦。
  連帶好處是升級補價可以直接累加在同一筆的 `amount` 上（FR-094），營收圖表的 `sum(Purchase.amount)` 一行都不用改。
- **D82**: **只有 high_ticket 課程能開方案**（使用者決策）。技術上沒有任何東西擋著一般課程用，否決的理由是流程不完整：一般課程走前台結帳，而結帳頁沒有選方案的 UI，開了會變成「後台設得出來、前台買不到」的半套功能。高價課不同 —— 它自 D13 起唯一成交入口就是後台開通，管理員手上本來就握著這個選擇。
  日後若真要讓一般課程分級，缺的是銷售頁與購物車的方案選擇，那是另一個故事，不是把這裡的 `if` 拿掉就好。
- **D81**: **未歸類到任何方案的小節 = 持方案者看不到**（使用者決策），而非「未歸類即公共內容」。差別只在新增小節時的預設：前者預設是關的、要主動勾才放出去；後者預設是開的。選前者是因為兩種寫錯的代價不對稱 —— 少勾一個方案，客戶會來問（吵但可修）；多開一節不該開的，沒有人會告訴你。
- **D80**: 方案外的小節**完全隱藏**，不做鎖頭（使用者決策）。這與同一個教室裡 drip 課現有的做法刻意不同：drip 會渲染出鎖頭、把內容欄位 null 掉，因為那是「時間到就給你」的等待感；方案是「你沒買」，把買不到的東西列出來只是在強調對方付得不夠多。
  兩個必須說清楚的連帶結果：（1）**教室內不會有升級誘因** —— 方案 A 的人看到的就是一門 4 集的課，看不出還有別的。日後要做 upsell 得另放升級區塊（參考 `Components/Classroom/LessonPromoBlock.vue`），不能靠鎖頭。（2）**管理員沒有「以方案 A 視角預覽」的模式**，一律看全部。驗證方案設定靠章節編輯頁的 chip 勾選狀態 —— 比照作業批改的 `preview_user_id` 另做一套視角切換，成本不划算。
- **D79**: 方案的 `price` 只是**開通 modal 的預設成交價**，不上銷售頁。高價課本來就 `high_ticket_hide_price`，價格是面談時談的；這個欄位存在的唯一理由是讓管理員少打一次字（沿用 FR-011 帶 `display_price` 的既有模式），所以它 nullable、也不做任何驗證以外的約束。
- **D78**: MVP **不做方案排序拖曳**。方案通常 2–3 個，`sort_order` 依建立順序給值就夠；為了它把 vuedraggable 再接一層，收益遠低於同頁已經有的章節／小節拖曳。真的長到需要排序時再說。

- **D77**: 改期成功後導向新時段的那一週（FR-084），而不是留在原處或另外做一個「跨週預覽」。跨週改期的結果必然離開當前畫面，此時停在原地會給出一個非常糟的訊號：那筆預約從格線上消失了，而 flash 訊息說「已改期至 8/26（週三）14:00」—— 兩者合起來像是「東西被移走了，但我看不到它去哪」。直接把畫面帶到它現在的位置，確認成本是零。
  同週改期時這個導向等於原地重載，與原本的 `back()` 沒有差別。
- **D76**: 格線點選**不被取代**。下拉解決的是「看不到的那些週」，而同一週內把 14:00 挪到 14:30 用點的只要一下，改成「展開日期下拉 → 找到今天 → 展開時間下拉 → 選 14:30 → 確認」反而更慢。兩條路徑共用同一個確認對話框與同一個端點，差別只在怎麼指定目標，並存的維護成本很低。
  因此下拉是**補上**去的：面板在改期模式下同時說得出兩件事 —— 上面選時間，或直接在格線上點。
- **D75**: 時段清單**按下「改期」才抓**，不隨週曆頁一起送 props。兩個理由：（1）諮詢長度是每筆預約各自的（30 或 45 分鐘，看當初有沒有用優惠碼），而「N 個連續單位皆可用」的判定必須留在後端（D46）—— 要預先送就得為兩種長度各算一份塞進每一頁；（2）撞車的成因就是拿舊名單做決定，頁面可能已經開了二十分鐘，那份 props 早就過期。按下的當下才問，是這個清單唯一有意義的時點。
  代價是多一次請求與一個載入狀態，而那次請求發生在管理員已經表明意圖之後 —— 沒有人在等首屏。

- **D71**: 排程寫 `->timezone('Asia/Taipei')->dailyAt('17:00')` 而不是沿用本檔既有的裸 UTC 慣例（`drip:process-emails` 的 `dailyAt('09:00')` 實際就是台北 17:00）。兩種寫法產生完全相同的執行時刻，差別只在讀的人：需求是「台灣下午 5 點」，程式碼就該長成那個樣子。既有那行沒有一併改，是因為它不屬於這個模組，而且改動一條正在跑的排程時間需要它的 owner 決定 —— 但新的這條沒有這個包袱。
  另一個好處是伺服器時區若哪天變了（搬機、Forge 預設調整），這條排程的行為不會跟著漂走，而裸 UTC 的那些會。
- **D72**: 一天一封的守門用 `reminder_sent_at` 欄位，而不是「排程每天只跑一次，所以自然不會重複」。後者在正常狀況下成立，但排程重跑（手動 `php artisan booking:send-reminders` 除錯、部署後補跑、Forge 排程重試）是實際會發生的事，而重複的提醒信會直接寄到客戶信箱 —— 這是少數「多寄一次」比「少寄一次」更難看的信。
  欄位順帶提供一個免費的事實：某筆預約到底有沒有被提醒過，可以直接查，不必翻 log。
  改期時清空它而不是另外記「提醒的是哪一天」：改期本來就會寄一封帶新時間與新 `.ics` 的信，把提醒狀態重置回原點，語意上就是「這是一場新的約」。代價是**當天 17:00 之後才改期、且新時間仍在明天**的情形不會再收到提醒（下一次排程看的是後天），這個縫隙留著 —— 改期信本身剛送出，時間資訊是最新的。
- **D73**: 提醒信不附 `.ics`。附一份同 `UID`、同 `SEQUENCE` 的邀請，對方的日曆客戶端會判定為重複而忽略（好一點的）或跳出「這個邀請已存在」（差一點的）；要讓它真的更新就得遞增 `SEQUENCE`，而 `SEQUENCE` 是 US14 用來表達「這場約異動過」的唯一訊號，為了一封提醒去動它，會讓真正的改期在對方日曆上排在一堆假異動後面。
  行事曆邀請在確認的當下就已經送出去了（FR-046），提醒信的任務是把注意力拉回來，不是再送一次同樣的東西。
- **D74**: 「今天才確認、面談就在明天」不補寄（使用者決策）。這批人幾小時前才收過確認信，信裡有時間、有 Zoom 連結、有 `.ics`，資訊完全一樣而且更新。為了覆蓋他們去加第二班排程（或把排程改成每小時掃描），換來的是一組新的邊界條件（哪些算補寄、補寄的信要不要跟提醒信長得一樣）與一個不再固定的發信時刻，而需求本來就寫得很清楚：下午 5 點、寄明天的。
- **D70**: 顧問篩選是**檢視工具，不是權限閘門**（使用者決策）。銷售顧問進頁面預設仍看到全部 leads，不自動帶入自己、也不鎖住選單 —— 這維持 D27 的立場（顧問接手一條 lead 時要看得到對方的完整行為紀錄）。
  「顧問預設只看自己」被否決的理由不只是省事：那會讓同一個畫面對兩種身分講不同的話 —— 管理員看到的百分比是全站漏斗，顧問看到的是自己的漏斗，兩人對著同一個「已成交 3%」討論卻是不同的東西，而畫面上沒有任何地方說明這件事。要做身分預設，該一併做的是把當前分母寫在畫面上，那是另一個範圍。
  「未指派」用 `consultant=none` 這個字串哨兵而不是另開一個 `unassigned=1` 參數：兩個參數表達同一個維度，就會有「同時給了會怎樣」的無意義組合要處理；一個參數一個維度，網址與程式碼都只有一種讀法。

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

- **D67**: 勾選複製全部做在前端，後端一行不改（US17）。區塊 payload 從 US13 就帶著 `email`（`finishBlock()`），要的資料已經在畫面上，再開一個「給我這些 lead 的 email」端點只是把手邊的東西繞一圈回來，還多一次往返與一組權限判斷。
  代價是這個功能靠一條**沒有型別、沒人保證**的前後端契約活著：哪天有人為了瘦身 payload 把 `email` 拿掉，畫面不會壞、按鈕照按，複製出來的是一串空字串 —— 靜默失敗，而且要等到有人把空白貼進收件欄位才發現。因此 US17 唯一的自動化測試不測 UI，測那條契約：`weekView()` 的 booked 與 held 區塊必須帶非空 `email`。這是本專案目前沒有 JS 測試環境（只有 PHPUnit）之下，唯一擋得住回歸的位置。

- **D68**: 勾選不跨週保留（US17，使用者決策）。切週是整頁 Inertia visit（`preserveState: false`），狀態自然歸零，不保留等於不寫程式。
  保留的版本要多付兩樣東西：一份獨立於畫面的選取清單，以及「你選了 3 筆，其中 2 筆不在這一週」的呈現方式 —— 後者才是真正的成本，因為看不見的勾選是會出事的那種：使用者以為在複製這一週的名單，實際上夾帶了兩週前的人。一次複製一週的名單是最常見也最好預測的用法，先做這個。
  **實作時延伸到手機單日檢視**（2026-08-09）：切日不是整頁 visit，勾選狀態不會自然歸零，因此計數與複製結果一律以 `visibleDays` 為範圍 —— 在週一勾了人、切到週二再按複製，複製到的是週二看得到的那些。同一條原則（畫面上看不到的人不會被複製到）在兩種版面下維持一致，代價是手機上無法跨日累積名單。

- **D69**: 不在這裡加「寄信給選取的人」（US17）。批次寄信已經有 US4 的既有路徑（模板 + 確認 modal + per-lead Job），在週曆上再開一顆寄信按鈕，等於多一條繞過模板系統的寄送路徑 —— 而寄出去的東西沒有回收鍵。
  「複製 Email 貼到自己的信箱」聽起來土，但它把「寄什麼、寄給誰、什麼時候寄」整段留在人手上，不需要系統為此承擔任何責任。真的需要系統寄，正確的做法是讓週曆選取後跳到既有的批次郵件流程，而不是自己長一套。

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

- **D37**: 不出席警語只做**警示文案**，不做系統實作（使用者決策）。爽約與否是線下事實，系統無從自動判定；真要擋人，現有的 `closed` 狀態加上管理員記憶已足夠應付目前的量。文案本身才是它的作用 —— 它要嚇阻的是「隨便按送出」的人，而那個效果在覆核頁顯示的當下就已經達成，不需要後端配合。文案 2026-08-08 由「將永久黑名單」改為「將不可再申請本站免費諮詢名額」——後者具體點出失去的是什麼（這個站的免費諮詢資格），比籠統的「黑名單」更可信、也更符合實際能落地的後果（D37 的決策本身不變：仍是純警示文案）

- **D38**（部分被 D55 取代：「一封完整的信」保留，但改為同步而非 job）: 確認信改由 `CreateZoomMeetingJob` 在建好會議後才寄，而不是確認當下先寄、連結後補。因為這封信的用途就是「告訴對方什麼時間、去哪裡」—— 少了連結就得再補寄一封，而補寄信的開信率遠低於第一封，且兩封信講同一件事最容易讓人以為預約重複了。代價是信件抵達比確認頁晚幾秒，這對使用者不可見（確認頁本來就寫「相關資料已寄出」，沒有承諾秒到）

- **D39**: Zoom 建立失敗三次後**照常寄出確認信**（帶 fallback 文案），而不是靜默重試到天荒地老或讓確認流程失敗。原則與 D11 / D34 同一條：已經成立的事實不能因為周邊服務失敗而被推翻 —— 對方已完成 Email 驗證、時段已正式保留，這是既成事實。同時 CC 內部收件者，是因為這種情況一定要有人去手動開會議並補連結，只寫 log 沒人會看

- **D40**: Zoom 設為**選配而非必要**（FR-039）。理由有二：本機開發與 CI 不該需要真實的第三方憑證才能跑完預約流程（否則測試會變成打真實 API 或整段被跳過）；以及萬一憑證過期或 Zoom 出事，系統要能退回 US11 的人工排程模式繼續收預約，而不是整條預約線停擺。判斷條件放在 service（`isEnabled()`），呼叫端只問一句，不各自檢查三個設定

- **D41**: Zoom 憑證放**後台「API 設定」頁**而非 `.env`（使用者決策）。該頁已經是 PayUni / 藍新 / Portaly / Meta CAPI token 的所在，語意一致（都是第三方服務憑證），業主換 Zoom app 時不必找工程師。沿用該頁既有的 `maskSecret()` + 「留白即維持原值」模式，不另發明一套 —— 那套模式已經在四組憑證上運作，再寫一份就是 FR-019 那類重複的起點。`client_secret` 走 secret 欄位，`account_id` / `client_id` 不遮罩（它們不是密鑰，遮了反而不好核對）

- **D42**: 本次**不做改期 / 取消同步**（FR-040）。改期是一條完整的支線 —— 要有對外的改期連結、時段釋放與重佔、Zoom `PATCH`/`DELETE`、以及改期後重寄哪封信的決策，跟本次「把門檻拉高」的目標無關。目前量體下，改期是顧問私訊喬時間的事，人工到 Zoom 後台改一分鐘的事情不值得先寫進系統。明確記為已知限制，比做半套（例如只刪時段不動 Zoom）好 —— 半套會讓管理員以為系統處理過了

- **D48**: **不做**「複製上週」、週期性時段、預設班表（US13 範圍外）。這三個都是「我每週二四都開下午」的變形，聽起來很省事，但它們各自要回答「改了母版之後既有的預約怎麼辦」—— 而那正是行事曆類功能最容易做爛的地方。目前顧問一週開一次時段，拖三下就完事；等到真的每週重複排到覺得煩，再回頭做，那時也才知道該做成哪一種。先把「拖曳一次開一整段」做對就是這次的全部

- **D47**: 顯示範圍固定 **10:00–23:00**（2026-08-11 由 08:00–22:00 調整），但**不當成資料的過濾器** —— 該週若有落在範圍外的既有時段，格線自動撐開到涵蓋它（FR-043 / US13 驗收第 2 條）。做成可設定（存 `site_settings`）被否決：多一組設定要維護、要驗證、要在 UI 上找地方放，而它要解的問題（半夜或清晨排諮詢）用「自動撐開」就已經解掉，而且不需要顧問先知道自己等一下要排幾點。固定值以**後端常數**表達（`ConsultationSlotService::GRID_START_MINUTE` / `GRID_END_MINUTE`）不進資料庫 —— 格線的列陣列本來就由 `weekView()` 產出（D46 的同一個理由：前端只負責畫），把預設值放前端會讓「撐開」的計算分在兩處。
  **2026-08-11 改為 10:00–23:00**（業主的實際諮詢時間往後移）。這個調整只動預設值，兩個既有性質不變：早於 10:00 的**既有**時段仍會把格線撐開、不會憑空消失；而新的時段只能在看得見的格線上拖出來，所以實質效果是「不再開得出早上 8–10 點的時段」—— 那正是這次要的。這也再次證明可設定化沒有必要：改一個數字重新部署，比維護一組沒人會再動第二次的設定便宜。
  **結束邊界同時也是諮詢的結束上限**（業主確認：最後一場不得晚於 23:00），而這不需要另一條規則來保證：`availableStarts()` 只提供「N 個連續單位皆已釋出」的起始（FR-028），而 23:00 那一格拖不出來，所以 45 分鐘的場次最晚只能從 22:15 開始（22:15+22:30+22:45）、30 分鐘最晚 22:30 —— 兩者都在 23:00 整收工。要讓某一場超過 23:00，得先有人用格線以外的方式建出 23:00 的單位

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
- **D103**: 婉拒是**第三個動作**，不是「取消」的別名，也不是「取消」加上一個狀態參數的呼叫端選擇。
  兩者在對外語意上是相反的：取消對申請人來說是一次行程異動（時間不合、臨時有事，那封信結尾放的是「歡迎再次預約」的連結），婉拒是我方單方面終止這條線。同一段機制服務兩種語意沒問題 —— 拆預約就是拆預約 —— 但它們的信、最終狀態、以及對方接下來會不會再出現在名單上都不同，共用一顆按鈕只會逼管理員在按下去之前先在腦子裡分辨這次算哪一種。
- **D104**: `cancel()` 與 `decline()` 共用私有的 `releaseBooking(HighTicketLead $lead, string $status, string $eventType)`。
  那段程式碼有五個副作用（釋出單位、清 Zoom 欄位、`calendar_sequence` 遞增、面談紀錄依 FR-114 分流、`syncZoom()` 刪會議），其中三個是後來一次一次補上去的。複製一份出來給婉拒用，等於保證下一次改動只會落在其中一邊 —— 而兩邊的差異會表現成「有些取消的時段沒放出來」這種要等到有人抱怨才會發現的症狀。
- **D105**: 婉拒理由**寫死在 Email 模板內文**，不做成逐筆可編輯的欄位（使用者決策）。
  這句話是一項政策，不是一次評論：同一個理由對每一個被婉拒的人都應該長得一樣，而讓它變成輸入框，等於每次按下按鈕前都要重新措辭一次一段本來就已經想清楚的話。要改就到後台 Email 模板頁改一次，全站跟著變。
  **措辭**：使用者指定的原句「經過複查，對方的計劃和專業度都未成熟……」是**意思**，不是定稿（使用者 2026-08-20 確認）。原句以第三人稱寫成（「對方」指的就是收信人本人），寄出去會讀起來像不小心轉寄的內部備註，因此模板預設值改寫為第二人稱，並把判斷的對象從「這個人」移到「這個時機」：說的是現在談幫不上什麼忙，不是說他不夠格。要傳達的訊息沒有變軟 —— 面談不會發生 —— 變的是對方讀完之後會不會想回信吵。
  結尾保留一句「日後若您的規劃更具體，歡迎再與我們聯繫」。這與 FR-140 禁止的「歡迎重新預約」課程連結是兩回事：一句話要對方寫信給人，一個連結讓他當場再佔一個時段，後者才會把剛剛婉拒掉的事情原地做回來。
- **D106**: 婉拒同時寫 `cancelled_at` 與 `declined_at`，而不是只寫後者。
  `cancelled_at` 在本模組不是「誰取消的」，它是**這筆預約還算不算數**的唯一判準（`isActiveBooking()`、前端 `hasLiveBooking`、`recordLead()` 的復活條件都讀它）。婉拒之後這筆預約確實不算數了，不寫就是讓資料與事實不一致；而「這是婉拒不是取消」由 `declined_at` 與 `status` 各自記得住。
- **D107**: 婉拒**不改動序列信訂閱狀態**（使用者決策）。
  對方在確認預約時已被 `checkAndBook()` 標記 `booked`、停止加溫，婉拒後就停在那裡。US24 對自動婉拒者採取的是相反立場（留在名單裡繼續加溫），兩者不矛盾：那些人從頭到尾不知道自己被評分過，而這裡的人剛收到一封明講婉拒的信 —— 在那之後接著寄行銷信是這條路徑上最不該發生的事。要把他放回加溫名單是之後的人工決定，不是這個按鈕的副作用。
- **D108**: 前端用 Inertia `router.post` + `preserveScroll`，不沿用狀態方塊那條 axios 路徑。
  狀態方塊改的是一個欄位，回一個 lead JSON 就補得回來；婉拒改的是 `status` + 兩個時間戳 + 該列的時段欄，還可能影響上方的狀態計數與分頁篩選。用 axios 就得在前端逐一同步這些衍生值，那是在前端養第二份真相 —— 而這個動作一整天按不到幾次，一次頁面重載換掉整類同步錯誤，很划算。

- **D109**: `long` 佇列的消費者放在**排程**裡（`queue:work database_long --queue=long --stop-when-empty`，每分鐘、`runInBackground`、`withoutOverlapping(30)`），不再依賴正式站上手動維護的 worker。
  這條是被同一個故障教兩次才寫下來的：T289 當時寫成「Forge 已新增第二個 daemon」，但正式站實際上跑的是 Forge 的**一次性指令**（`provision-210173762.sh`），指令視窗結束、部署跑 `queue:restart`、或機器重開，那個 worker 就沒了 —— 而它消失的症狀是**完全無聲的**：webhook 照收、工作單照進 `jobs` 表、`reserved_at` 永遠是 NULL，後台那一列只是停在「尚無逐字稿」。2026-08-22 第二次發生時，表裡躺了 6 筆、`/etc/supervisor/conf.d/` 只有兩個舊 conf、`consultation_notes` 從 8/20 06:55 之後再無任何 `transcript_fetched_at`。
  排程贏在它是**程式碼**：跟著 deploy 走、被刪掉會出現在 diff 裡、有測試釘住（T335），而 cron 每分鐘重新起一個行程，本質上不存在「跑著跑著就沒了」這個狀態。代價是最多一分鐘的延遲 —— 對一個要等 Zoom 數十分鐘到數小時才生出逐字稿的流程，這個代價量不出來。`--stop-when-empty` 是關鍵：沒有它就變成一個沒人監管的常駐行程，而且第一次啟動後的每一次 tick 都會被重疊鎖跳過。沿用 FR-056 的既有立場（預約流程不依賴 worker），只是這裡的慢工作不能同步做，所以改由 cron 承擔常駐的那一半。


## Schema

- **US24 schema 變更（兩支 migration，皆動 `high_ticket_leads`）**：

  `2026_08_18_000001_add_screening_to_high_ticket_leads_table.php`

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `screen_timeline` | varchar(20) nullable，`after('social_url')` | Q1 時程答案（存 key，FR-123） |
  | `screen_budget` | varchar(20) nullable | Q2 預算答案 |
  | `screen_authority` | varchar(20) nullable | Q3 決策權答案 |
  | `screen_pain` | varchar(20) nullable | Q4 痛點成本答案 |
  | `screen_next_step` | varchar(20) nullable | Q5 下一步答案 |
  | `screening_score` | tinyint unsigned nullable | 0–10 分。**存下來而不是每次重算**：計分表會隨定價調整，重算會讓上個月的判定跟著變，而「當初為什麼擋他」必須是可還原的事實 |
  | `screened_at` | timestamp nullable | 審核完成時間；null 代表這筆 lead 早於本功能或走 `?resume=` 回訪 |
  | `declined_at` | timestamp nullable，`after('confirmed_at')` | 自動婉拒時間 |

  五個答案欄各自獨立成欄而非一個 JSON —— 這五題最終要拿來回答「勾了 X 的人成交率多少」，那是 `GROUP BY` 的工作；且測試跑在 sqlite 上，JSON 查詢的兩套方言不值得為五個固定欄位付出。**DB 層一律不設 enum 約束**（比照 004 `content_category` 的既有作法）：選項會隨定價調整，合法值由 `BookingScreening` 與 Form Request 把關。

  `2026_08_18_000002_add_declined_to_high_ticket_leads_status.php` — `status` enum 加入 `declined`（第 7 個值），寫法比照既有的 `2026_08_06_000002_add_cancelled_...`。`down()` MUST 先把 `declined` 的列改回 `closed` 再收窄 enum，否則回滾會在既有資料上炸掉。

  **不變量**：
  - `screened_at` 非 null ⇒ 五個答案欄與 `screening_score` 皆非 null（一起寫，一起讀）
  - `declined_at` 非 null ⇒ `status = declined`、`confirm_token` 為 null、名下無 `consultation_slots`（FR-126）；重新審核通過時兩者一起清
  - `screening_score` 為 null ⇒ 舊 lead 或 resume 回訪，送出申請時放行（FR-129）
  - 所有欄位對舊資料一律 null，後台顯示一句說明而非五排「—」（FR-131）

- **US23 schema 變更（一支 migration）**：

  `2026_08_17_000001_create_consultation_notes_table.php` — 新表 `consultation_notes`，**一列 = 一場面談**。`high_ticket_leads` **不動**（D92）：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `id` | bigint PK | |
  | `email` | varchar(255), **index** | **客戶識別鍵**，恆為小寫（寫入時正規化）。同一 email 的所有場次即該客戶的面談史 |
  | `source` | varchar(30) | 這場從哪來：`high_ticket_booking`（本模組）；未來顧問服務寫入自己的值。無 DB enum 約束（比照 004 對 `content_category` 的既有作法） |
  | `lead_id` | unsignedBigInteger nullable, index | 來源標記，非識別鍵；無外鍵約束（比照 `consultation_slots.lead_id` 的既有作法，D7） |
  | `user_id` | unsignedBigInteger nullable, index | 面談當下若已是會員則記下；null 不代表非會員（lead 階段可能還沒註冊） |
  | `consultant_id` | unsignedBigInteger nullable, index | 主談顧問，自 lead 快照（比照 FR-061） |
  | `course_id` | unsignedBigInteger nullable | 這場面談談的是哪門課；顧問服務場次可為 null |
  | `met_at` | datetime, **index** | **場次時間**（UTC）。列表以此倒序 |
  | `zoom_meeting_id` | varchar(50) nullable, **unique** | webhook 的對照鍵。unique 保證一場 Zoom 會議只對應一列 |
  | `transcript` | longText nullable | **校訂後的匿名對話稿**；原始 VTT 與 Zoom 顯示名稱不進這裡（FR-109） |
  | `transcript_fetched_at` | timestamp nullable | 取回並校訂完成的時間；**非 null 且有內容即不再重跑**（FR-110） |
  | ~~`transcript_edited_at`~~ | — | 已於 `..._000003` 移除：逐字稿改為只可下載，沒有任何程式會寫它（FR-120） |
  | `summary` | text nullable | AI 產出的摘要，管理員可覆寫 |
  | `summary_generated_at` | timestamp nullable | 最近一次自動產生摘要的時間 |
  | `summary_edited_at` | timestamp nullable | 人工編輯時間；**非 null 即鎖定**自動覆寫（FR-110） |
  | timestamps | | |

  **不變量**：
  - `email` 是唯一的客戶識別鍵，恆小寫；`lead_id` / `user_id` / `course_id` 皆為來源標記，全 null 也是合法的一列（未來顧問服務的場次可能三者皆無）
  - `zoom_meeting_id` unique 且 nullable —— 沒有 Zoom 的面談（實體、電話）將來也放得進這張表
  - 原始 VTT、Zoom 顯示名稱、真實人名**不存在於任何資料表與任何 log**；`transcript` 裡的講者標籤只有「顧問」與「客戶」兩種值
  - 兩個 `*_edited_at` 各自獨立、互不影響（D93）；`*_generated_at` / `*_fetched_at` 只由自動流程寫入，`*_edited_at` 只由後台編輯端點寫入 —— 四個時間戳各有唯一寫入點
  - **本表為跨模組的客戶面談紀錄**：目前唯一寫入者是本模組，未來的顧問服務模組以 `touchpoints` 聲明使用，**MUST NOT 移轉 owner**（沿用「一檔一 owner」原則）
  - **佇列**：本 job 走 `database_long` 連線（同一張 `jobs` 表、`long` 佇列、`retry_after` 1800），與寄信類 job 分離；`retry_after` > job `$timeout` 是不可違反的關係，否則會重複執行（FR-117）
  - `site_settings.zoom_webhook_secret_token` — Zoom Event Subscription 的 Secret Token；空值即 webhook 無從驗簽，端點 MUST 直接拒絕所有非 url_validation 事件（不得退化為無認證）

- **US22 無 migration、無 schema 變更** —— 成交金額自 D8 起就寫在 `purchases.amount`，成交類型是既有的 `type = 'lead_conversion'`，本故事只是把它們依台北時間的月／年加總後顯示。
  **不變量**：摘要是純讀取，MUST NOT 寫入任何資料表；`purchases` 的 `(user_id, course_id)` unique 意味著同人同課只有一列，因此「同一人重複開通」不會讓人數或金額重複計算（FR-099 的去重是為了處理**同人多課**與**同 email 多 lead**）

- **US21 schema 變更（三支 migration）**：

  `2026_08_13_000001_create_course_plans_table.php` — 新表 `course_plans`：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `course_id` | FK → courses，cascade | 所屬課程（實務上只會是 `type=high_ticket`，但不在 DB 層擋，見 D82） |
  | `name` | string(50) | 方案名稱（「方案 A」「完整版」…），管理員自由命名 |
  | `price` | unsigned int nullable | 建議成交價，只用於開通 modal 的預設值（D79） |
  | `sort_order` | int default 0 | 建立順序，無排序 UI（D78） |

  index：`course_id`

  `2026_08_13_000002_create_course_plan_lesson_table.php` — 樞紐表 `course_plan_lesson`（`course_plan_id` / `lesson_id`，皆 cascade，`unique(course_plan_id, lesson_id)`）。**刻意是多對多而非在 lessons 上加欄位** —— 需求明確要求方案可重疊（A: 1,2,3；B: 2,4,5）

  `2026_08_13_000003_add_course_plan_id_to_purchases_table.php` — `purchases` 增一欄：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `course_plan_id` | unsignedBigInteger nullable，FK → course_plans，**onDelete('restrict')** | 這筆授權買到哪個方案；**null = 全部內容**（FR-087） |

  **不變量**：
  - `course_plan_id` 只有兩個寫入點 —— `HighTicketLeadService::convertLead()` 與 `MemberController::updatePurchasePlan()`。其餘所有建立 Purchase 的路徑（`CheckoutService`、`giftCourse()`、`grantCourse()`、`RedemptionService`）一律不碰此欄，落 null 即全開
  - `restrict` 是刻意選的：有人持有的方案在 DB 層就刪不掉，配合 service 層的友善 422（FR-093）。`nullOnDelete` 會靜默把會員升級成全開
  - 一個 lesson 可屬於 0..N 個方案；屬於 0 個時，持方案的會員看不到（D81）
  - 課程的 `course_plans` 為空 = 未啟用多方案，全站行為與 US21 之前完全相同 —— 沒有「啟用開關」欄位，方案數量本身就是開關

- **US20 無 migration、無 schema 變更** —— 改期一直都是「把 `consultation_slots.lead_id` 從一組單位搬到另一組」，本故事只是多一種指定目標的方式。新增的是一個唯讀端點與 `availableStarts()` 的一個選填參數。
  **不變量**：可用性的唯一判定仍是 `available()` scope（`lead_id` null 或暫留已逾時）＋「N 個連續單位」（FR-028）；`$ignoring` 只是在這個判定上開一個當事人自己的例外，不改寫任何資料

- **US19 schema 變更（兩支 migration）**：

  `2026_08_09_000002_add_reminder_sent_at_to_high_ticket_leads_table.php` — `high_ticket_leads` 增一欄：

  | 欄位 | 型別 | 用途 |
  |------|------|------|
  | `reminder_sent_at` | timestamp nullable | 面談前一日提醒的寄出時間；null = 尚未提醒。改期時清回 null（D72） |

  `2026_08_09_000003_install_consultation_reminder_template.php` — 安裝 `high_ticket_consultation_reminder` 模板。實作**直接沿用 `2026_08_08_000003` 的迴圈**（讀 `EmailTemplateSeeder::templates()`、逐筆查 `event_type`、缺才 insert、永不 update），因此它同時也是後續任何新模板的補漏網；`down()` 為 no-op（無法區分哪些列是它建的）

  **不變量**：`reminder_sent_at` 只由 `booking:send-reminders` 於**寄送成功後**寫入，以及由改期清空 —— 沒有第三個寫入點；欄位不進 Leads 名單 UI（US19 不動任何後台畫面）

- **US16 schema 變更（一支 migration）**：

  `2026_08_08_000001_index_and_normalise_phones.php` — `high_ticket_leads.phone` 加 index（它成為去重查詢鍵），並把 `high_ticket_leads.phone` 與 `users.phone` 的既有值就地正規化（FR-064）。`orders.buyer_phone` 不動（D62）

  **只有一支** —— US16 的擋門完全建立在既有欄位（`status` / `confirmed_at` / `cancelled_at` / `phone`）之上，不需要第二支

  **不變量**：電話進 DB 前一律經 `PhoneNumber::normalise()`；DB 裡不應再出現含 `-`、空白或 `+886` 的號碼

- **US18 無 migration、無 schema 變更** —— 完全建立在 US15 已存在的 `high_ticket_leads.consultant_id`（已有 index）之上，只多一個 `where` 與一個下拉選單

- **US17 無 migration、無 schema 變更、無後端邏輯變更** —— 全部在 `WeekGrid.vue` 內；唯一動到 PHP 的是新增一個守住 payload 契約的測試（D67）

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
- **US27 schema 變更：無欄位變更，一支資料 migration**。

  `2026_08_20_000001_install_booking_declined_template.php` — 沿用 `2026_08_18_000004` 的通用迴圈（走過 `EmailTemplateSeeder::templates()` 全集、**缺才 insert、永不 update**、`down()` 為 no-op）。seeder 只在全新安裝時跑，新模板要抵達正式站的資料庫只能靠 migration；而永不 update 是因為那些內文的所有權在後台編輯它的人手上。

  婉拒用到的四個欄位（`status` / `declined_at` / `cancelled_at` / `calendar_sequence`）在 US14 與 US24 都已存在，`status` enum 的 `declined` 也已是第 7 個值 —— 本故事只是第一次讓**人**寫入它。


## Tasks

### US27 預約名單直接婉拒並取消

- [x] T326 [P] 模板：`EmailTemplateSeeder` 加 `high_ticket_booking_declined` 一筆（主旨／內文定版見本節末）+ 資料 migration（既有「缺才 insert」迴圈）+ `EmailTemplateController::$availableVariables` 四個變數 in `database/seeders/EmailTemplateSeeder.php`, `database/migrations/2026_08_20_000001_install_booking_declined_template.php`, `app/Http/Controllers/Admin/EmailTemplateController.php`
- [x] T327 `HighTicketBookingService`：把 `cancel()` 的內容抽成 `private releaseBooking(HighTicketLead $lead, string $status, string $eventType): array`（FR-138 / D104），`cancel()` 委派為 `('cancelled', 'high_ticket_booking_cancelled')`；`declined` 時另寫 `declined_at = now()`（FR-139 / D106）in `app/Services/HighTicketBookingService.php`
- [x] T328 `HighTicketBookingService::decline(HighTicketLead $lead): array` — 委派 `releaseBooking($lead, 'declined', 'high_ticket_booking_declined')`；drip 訂閱不碰（D107）in `app/Services/HighTicketBookingService.php`
- [x] T329 [P] `recordLead()` 復活路徑一併清 `declined_at`（FR-139）—— 與既有的 `'cancelled_at' => null` 同一個 update array in `app/Services/HighTicketBookingService.php`
- [x] T330 端點：`HighTicketLeadController::decline()`，守 `isActiveBooking()`（失敗 `back()->with('error', ...)`，FR-139）、成功 `back()->with('success', ...)` 並沿用 `zoomNote()`；路由 `POST /admin/high-ticket-leads/{lead}/decline`（staff 群組內，緊鄰既有的 reschedule / cancel-booking 兩條）in `app/Http/Controllers/Admin/HighTicketLeadController.php`, `routes/web.php`〔touchpoint 000〕
- [x] T331 婉拒理由下發：`HighTicketLeadController::index()` 加 prop `declineReason`（`EmailTemplate::forEvent('high_ticket_booking_declined')->value('body_md')`，模板缺漏時為 null）—— 確認框要印的是真的會寄出去的那段文字，不是前端另抄一份（FR-141）in `app/Http/Controllers/Admin/HighTicketLeadController.php`, `resources/js/Pages/Admin/HighTicketLeads/Index.vue`
- [x] T332 前端：`hasLiveBooking(lead)` 的列加「婉拒」按鈕（rose 色系、`cursor-pointer` + hover、送出中 disabled）+ 確認 modal（暱稱、原訂時段、理由全文）；送出走 `router.post(..., { preserveScroll: true })`（D108）；模板缺漏時按鈕 disabled 並提示先到 Email 模板頁 in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T333 `BookingDeclineTest`：見 US27 驗收最後一條 in `tests/Feature/HighTicket/BookingDeclineTest.php`

**模板預設值（T326）** — `subject` = `【關於您的 1v1 諮詢申請】{{course_name}}`，`body_md`：

```
您好 {{user_name}}，

關於您原訂於 {{slot_time}} 的「{{course_name}}」1v1 諮詢，我們在複查申請內容後決定不安排這次面談，該場次的會議連結同時失效。

一對一諮詢很吃時機。從您目前的規劃與準備來看，現在談能幫上的忙有限，我們認為還不是最好的時候，因此這次容我們婉拒。

本信附有行事曆取消檔案，開啟後即可從您的日曆移除這筆行程。

日後若您的規劃更具體，歡迎再與我們聯繫。

經營者時間銀行
```

（措辭的取捨見 D105：使用者指定的是意思，不是原文。）

### US26 未完成申請的續填提醒

- [x] T319 [P] migration：`high_ticket_leads` 加 `resume_reminder_sent_at`（FR-136）in `database/migrations/2026_08_18_000003_add_resume_reminder_sent_at_to_high_ticket_leads_table.php`
- [x] T320 模板：`EmailTemplateSeeder` 加 `high_ticket_application_resume` 一筆 + 資料 migration（既有的「缺才 insert」迴圈）+ `EmailTemplateController::$availableVariables` 四個變數 in `database/seeders/EmailTemplateSeeder.php`, `database/migrations/2026_08_18_000004_install_application_resume_template.php`, `app/Http/Controllers/Admin/EmailTemplateController.php`
- [x] T321 `HighTicketBookingService`：`resumeUrl()` 收為單一定義（`NotifyHighTicketSlotJob` 改委派）、`sendApplicationResumeReminders()` 依 FR-135 挑人並蓋章、`sendResumeReminderMail()` 無 CC in `app/Services/HighTicketBookingService.php`, `app/Jobs/NotifyHighTicketSlotJob.php`
- [x] T322 命令與排程：`booking:send-resume-reminders`、`->timezone('Asia/Taipei')->hourly()->between('9:00','21:00')` in `app/Console/Commands/SendApplicationResumeReminders.php`, `routes/console.php`
- [x] T323 回站落在第二步（FR-137）：`draftAnswers()` 加 `screening_cleared`（伺服器端判定）、精靈依此把起始步驟設為 2 並預設 `screeningPassed` in `app/Http/Controllers/CourseController.php`, `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T324 [P] 修回歸：`hasApplication()` 移除 `screened_at`（US24 加入後，只完成審核的 lead 會渲染出一排「—」，正是 US9 明文禁止的），未完成者改顯示專屬說明 in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T325 `ApplicationResumeReminderTest`：見 US26 驗收最後一條 in `tests/Feature/HighTicket/ApplicationResumeReminderTest.php`

### US24 前置資格審核與自動婉拒

Phase 1 — 資料層與計分核心

- [x] T295 [P] migration：`high_ticket_leads` 加五個 `screen_*` 答案欄、`screening_score`、`screened_at`、`declined_at`（見 Schema）in `database/migrations/2026_08_18_000001_add_screening_to_high_ticket_leads_table.php`
- [x] T296 [P] migration：`status` enum 加入 `declined`；`down()` 先把 `declined` 改回 `closed` 再收窄 in `database/migrations/2026_08_18_000002_add_declined_to_high_ticket_leads_status.php`
- [x] T297 `BookingScreening` support 類別：`PASS_SCORE = 5`、`QUESTIONS` 常數（五題 × 選項 × `label` / `score` / `veto`，FR-123）、`score(array $answers): int`、`vetoed()`、`passes()`、`tier(int $score): string`（hot / warm / cold）、`questionsForFront(): array`（**剝除 score 與 veto**，FR-124 / D101）、`rules(): array`（供 Form Request 組 `Rule::in`）in `app/Support/BookingScreening.php`
- [x] T298 `HighTicketLead`：`$fillable` 加五個 `screen_*`、`screening_score`、`screened_at`、`declined_at`；`casts()` 加兩個 datetime 與 `screening_score => integer` in `app/Models/HighTicketLead.php`

Phase 2 — 審核端點（依賴 Phase 1）

- [x] T299 `BookingScreeningRequest`：`name` / `email` 必填、五題以 `BookingScreening::rules()` 驗證（全部 required + `Rule::in`）、中文 `messages()` in `app/Http/Requests/BookingScreeningRequest.php`
- [x] T300 `HighTicketBookingService::screen(Course $course, array $data): array`：課程資格檢查 → 計分 → `firstOrNew` 該 `email + course_id` 的 lead → 寫五題答案 / `screening_score` / `screened_at`；未通過且 lead 處於可覆寫狀態（新建、`pending` 且未確認、或 `declined`）時寫 `status = declined` + `declined_at`，通過時清 `declined_at` 並回 `pending`；**MUST NOT 寄信、MUST NOT 呼叫 `checkAndBook()`、MUST NOT 送 CAPI**（FR-125 / FR-126）in `app/Services/HighTicketBookingService.php`
- [x] T301 `HighTicketBookingController::screen()`：回 `{passed: bool}`，**不回分數**（FR-124）in `app/Http/Controllers/HighTicketBookingController.php`
- [x] T302 [P] 路由 `POST /course/{course}/screen`（`throttle:10,1`，公開）in `routes/web.php`（touchpoint 000-platform-core）
- [x] T303 送出時重新計分：`HighTicketBookingRequest` 五題改為 `nullable` + `Rule::in`；`apply()` / `waitlist()` 在重複預約檢查之後、時段查詢之前，對**有帶答案**的請求呼叫 `BookingScreening::passes()`，未通過回 422「此申請未通過資格審核」；答案未帶則放行（FR-129）in `app/Http/Requests/HighTicketBookingRequest.php`, `app/Services/HighTicketBookingService.php`
- [x] T304 `recordLead()`：帶入五題答案與分數（有帶才覆寫），可復活狀態清單加入 `declined` 並清 `declined_at`（FR-127 / D97）in `app/Services/HighTicketBookingService.php`
- [x] T305 [P] `updateStatus()` 的 `in:` 驗證清單加 `declined`（FR-127）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T306 [P] `show()` 下發 `screeningQuestions`（`BookingScreening::questionsForFront()`）；`draftAnswers()` 多帶五題答案供 `?resume=` 與登入者回填（FR-129，touchpoint 002-storefront）in `app/Http/Controllers/CourseController.php`

Phase 3 — 前台第一步與審核畫面（依賴 Phase 2）

- [x] T307 `BookingScreeningStep.vue` 新組件：Email / 暱稱 / 五題單選卡片（題目由 prop 來、全部必答才可送出）、`POST .../screen`、「自動審核中」畫面（進度條 + 15 秒倒數，`onUnmounted` 清 timer）、婉拒文案畫面（中性灰、不顯示分數與原因）；通過則 `emit('passed', answers)`（FR-124 / FR-128）in `resources/js/Components/Course/BookingScreeningStep.vue`
- [x] T308 Wizard 改為五步：`STEPS` 換成 1 資格 / 2 資料 / 3 承諾 / 4 時段 / 5 確認，第一步掛 `BookingScreeningStep`，原問卷欄位整組移到第二步，`canLeave()` / `maxReachable` / `routeFieldErrors()` 的步驟編號全數順延；答案存進 `form` 並隨 `book` / `waitlist` 送出；**回到第一步且答案未變更時 MUST NOT 重跑倒數**；`resuming` 直接落在時段步驟且跳過審核（FR-130 / FR-129）in `resources/js/Components/Course/HighTicketBookingWizard.vue`
- [x] T309 [P] 第五步覆核區的「申請資料」不列五題答案（那是審核用的，不是要覆核的），但 `reviewRows` 其餘欄位與既有的不出席警語、Email 二次確認一律不動 —— 確認此步驟在改編號後行為未變 in `resources/js/Components/Course/HighTicketBookingWizard.vue`

Phase 4 — 後台名單（可與 Phase 3 平行）

- [x] T310 [P] `BookingListTab`：`statusButtons` 加第 7 顆「已婉拒」（value `declined`、letter `R`、rose 色系，四組 class 字串寫全供 Tailwind 掃描）；展開列新增「資格審核」區（分數 `N/10` + 分級標籤 + 五題中文答案，無紀錄時顯示一句說明）；`hasApplication()` 納入 `screened_at`（FR-127 / FR-131）in `resources/js/Components/Admin/Leads/BookingListTab.vue`

Phase 5 — 測試

- [x] T311 `BookingScreeningTest`：五題計分逐題驗證、5 分邊界（4 分擋 / 5 分過）、`screen_budget = none` 一票否決（其餘滿分仍擋）、未通過不寄信（`Mail::assertNothingSent()`）／不呼叫 `checkAndBook()`／不佔時段／`confirm_token` 為 null、通過者照常走完 `book` 全流程、重新審核可翻案（`declined` → `pending` 且 `declined_at` 清空）、已確認的 lead 重跑審核不被改狀態、送出時重新計分擋下換信箱繞過、舊 lead 無答案仍可送出、`questionsForFront()` 不含 score / veto in `tests/Feature/HighTicket/BookingScreeningTest.php`

### US23 面談逐字稿自動摘要

> 依賴 000 US10 的 `ai_prompts` 表、`AiPrompt` model 與 `OpenAiService`（T267–T272）先落地。

Phase 1 — 資料層與 Zoom 接收

- [x] T267 migration：建 `consultation_notes` 表（見 Schema；`email` / `met_at` / `lead_id` / `user_id` / `consultant_id` index，`zoom_meeting_id` unique）in `database/migrations/2026_08_17_000001_create_consultation_notes_table.php`
- [x] T268 `ConsultationNote` model：`$fillable`、四個時間欄 + `met_at` 進 `casts()`、`lead()` / `consultant()` / `course()` 關聯、`scopeForEmail(string $email)`（小寫正規化後比對，FR-115）in `app/Models/ConsultationNote.php`
- [x] T268b `HighTicketLead::consultationNotes()` —— 以 email 而非 `lead_id` 關聯（`hasMany(..., 'email', 'email')`），讓同一個人的所有場次跟著出來（FR-116）in `app/Models/HighTicketLead.php`
- [x] T268c 確認預約時建立 note（FR-113）；`reschedule()` 更新 `met_at`、`cancel()` 依 FR-114 條件刪除 in `app/Services/HighTicketBookingService.php`
- [x] T269 `ZoomWebhookService`：`secret()` / `challengeResponse(string $plainToken)` / `verify(Request $request): bool`（HMAC + 5 分鐘時間戳容差，FR-101/FR-102）in `app/Services/ZoomWebhookService.php`
- [x] T270 `Webhook\ZoomController::handle()`：url_validation 先回、驗簽、事件過濾、以 `zoom_meeting_id` 找 note、派 job、例外一律 200（FR-103/FR-104/FR-105）in `app/Http/Controllers/Webhook/ZoomController.php`
- [x] T271 路由 `POST /api/webhooks/zoom` in `routes/api.php`（000 touchpoint）
- [x] T272 [P] API 設定頁加 `zoom_webhook_secret_token` 遮罩欄位與唯讀 webhook URL in `app/Http/Controllers/Admin/SettingsController.php`, `resources/js/Pages/Admin/Settings/Payment.vue`（000 touchpoint）

Phase 2 — 抓取、解析與 AI

- [x] T273 `ZoomTranscriptService::download(array $recordingFile, ?string $downloadToken): string` —— 優先用 payload 的 download_token，退回 `ZoomMeetingService::token()`；timeout 30 秒 in `app/Services/ZoomTranscriptService.php`
- [x] T274 `ConsultationTranscriptService::vttToDialogue(string $vtt): string` —— 剝除檔頭／cue 序號／時間軸，同講者連續行合併 in `app/Services/ConsultationTranscriptService.php`
- [x] T275 `ConsultationTranscriptService::normaliseSpeakers(string $dialogue, ConsultationNote $note): string` —— 機械式對應顧問／客戶（客戶名取自 note 的 lead 或 user，顧問名取自 `consultant_id`；FR-106）in `app/Services/ConsultationTranscriptService.php`
- [x] T276 `ConsultationTranscriptService::proofread(string $dialogue): string` —— 分段（4000 字、切在講者邊界）逐段呼叫 `OpenAiService`，含防縮水檢查與 warning log（FR-107/FR-108）in `app/Services/ConsultationTranscriptService.php`
- [x] T277 `ConsultationTranscriptService::summarise(string $transcript): ?string` in `app/Services/ConsultationTranscriptService.php`
- [x] T278 `ProcessZoomTranscriptJob(int $noteId, array $payload, bool $force = false)`（`$tries = 3`、`$backoff = [60, 300, 900]`）：找 TRANSCRIPT 檔（缺則寫 info log 靜默結束，T312 起不再丟例外重試，FR-132）→ 下載 → 解析 → 正規化 → 校訂 → 摘要，兩個守門各自檢查（FR-110/FR-112；逐字稿守門於 T293 改為 `transcriptIsSettled()`）in `app/Jobs/ProcessZoomTranscriptJob.php`
- [x] T279 [P] `booking:fetch-transcript {note}` 手動指令（不進排程）in `app/Console/Commands/FetchConsultationTranscript.php`

Phase 3 — 後台 UI

- [x] T280 `Admin\ConsultationNoteController`：`updateSummary()` / `updateTranscript()` / `regenerateSummary()`（inline validate、回 JSON、比照既有 `updateStatus()`；重跑不打 Zoom、逐字稿為空回 422，FR-111）in `app/Http/Controllers/Admin/ConsultationNoteController.php`
- [x] T281 `/admin/consultation-notes/{note}` 四條路由加在 `staff` 群組末尾 in `routes/web.php`（000 touchpoint）：`PATCH .../summary`、`POST .../regenerate-summary`、`GET .../transcript.txt`（T292）、`DELETE`（T290）。原本的 `PATCH .../transcript` 已於 T293 移除
- [x] T282 `index()` 的 leads payload 掛上 `consultationNotes`（以 email 關聯、`met_at` 倒序，FR-116）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T283 detail row 加「面談紀錄」區塊：`v-for` 列出該 email 的所有場次（`met_at` 標題 + 課程／顧問），每場一組可編輯摘要 + 兩個時間戳。**抽成 `ConsultationNotesPanel.vue` 獨立組件**（BookingListTab 已 1438 行）in `resources/js/Components/Admin/Leads/ConsultationNotesPanel.vue`
- [x] T284 每場次內加「逐字稿」收合區塊（可編輯 textarea、等寬字體、複製按鈕）in `resources/js/Components/Admin/Leads/ConsultationNotesPanel.vue`
- [x] T285 每場次加「重新產生摘要」按鈕 + 二次確認 in `ConsultationNotesPanel.vue`；姓名旁加場次數量標記 in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T290 刪除場次（FR-118）：`destroy()` 硬刪除 + 稽核 log in `app/Http/Controllers/Admin/ConsultationNoteController.php`；`DELETE /admin/consultation-notes/{note}` in `routes/web.php`；場次抬頭右側「刪除場次」按鈕，確認文案依有無內容分級 in `resources/js/Components/Admin/Leads/ConsultationNotesPanel.vue`

Phase 4 — 測試與驗證

- [x] T286 `ConsultationSummaryTest`：URL 驗證、驗簽失敗、逾時時間戳、講者匿名化（原始姓名不得出現）、防縮水、兩個覆寫守門、重跑不打 Zoom、憑證未設定、meeting_id 對不到 in `tests/Feature/HighTicket/ConsultationSummaryTest.php`
- [x] T286b `ConsultationNoteTest`：確認預約建列、改期更新 `met_at`、取消依內容有無決定刪或留（FR-114）、**同一 email 跨兩筆不同 lead 的場次會一起列出**（FR-116）、email 大小寫不影響歸戶（FR-115）in `tests/Feature/HighTicket/ConsultationNoteTest.php`
- [x] T288 佇列連線與 timeout：新增 `database_long` 連線（`retry_after` 1800）、job 設 `$timeout = 1500` 並於非 sync 環境掛上該連線（FR-117）in `config/queue.php`, `app/Jobs/ProcessZoomTranscriptJob.php`
- [x] T292 面談紀錄 UX 收斂為兩個連結（FR-120）：`ConsultationSummaryModal.vue` 新組件（摘要檢視／編輯／重新產生）、`ConsultationNotesPanel.vue` 改為單行場次列、`downloadTranscript()` 輸出含 BOM 的 `.txt`、leads payload 改送 `transcript_bytes` in `resources/js/Components/Admin/Leads/ConsultationSummaryModal.vue`, `resources/js/Components/Admin/Leads/ConsultationNotesPanel.vue`, `app/Http/Controllers/Admin/ConsultationNoteController.php`, `app/Http/Controllers/Admin/HighTicketLeadController.php`, `routes/web.php`
- [x] T294 動作列位置與徽章捷徑（FR-121）：動作移到面談時間旁、純文字改為有邊框小按鈕、刪除留在最右；姓名旁徽章改為 button 兄弟元素並直開最近一場摘要 in `resources/js/Components/Admin/Leads/ConsultationNotesPanel.vue`, `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T293 清除逐字稿編輯路徑（FR-120）：移除 `updateTranscript()` 與其路由、`transcriptIsLocked()`，新增 migration `..._000003` 卸掉 `transcript_edited_at`；job 的守門改為 `transcriptIsSettled()`（`transcript_fetched_at` + 有內容），順帶擋掉 Zoom 兩個錄影事件造成的重複校訂 in `app/Http/Controllers/Admin/ConsultationNoteController.php`, `routes/web.php`, `app/Models/ConsultationNote.php`, `app/Jobs/ProcessZoomTranscriptJob.php`, `database/migrations/2026_08_17_000003_drop_transcript_edited_at_from_consultation_notes_table.php`
- [x] T292b 下載與 payload 測試：`.txt` 標頭與 BOM 正確、無逐字稿回 404、訪客被擋、**leads payload 不含逐字稿本文但有 `transcript_bytes`**（FR-120）in `tests/Feature/HighTicket/ConsultationSummaryTest.php`
- [x] T291 補建指令 `booking:backfill-consultation-notes {--dry-run}`（FR-119）：`recordConsultationNote()` 改 public 供共用，略過已有紀錄／無時段／已取消 in `app/Console/Commands/BackfillConsultationNotes.php`, `app/Services/HighTicketBookingService.php`
- [x] T291b 補建測試：補出缺漏的紀錄、可重複執行不產生重複列、`--dry-run` 不寫入、已取消的預約略過（FR-119）in `tests/Feature/HighTicket/ConsultationNoteTest.php`
- [x] T291c 正式站補建完成：`--dry-run` 確認後實跑，補出 **39 筆**（涵蓋所有曾確認且未取消的預約，不只上線前那批）
- [x] T291d 正式站補抓已結束場次的逐字稿：13 場成功（逐字稿 11k–27k 字、摘要 747–1054 字），4 場 Zoom 回 `3301 此錄製不存在`（沒開錄影／客人未出席／顧問改用個人會議室）
- [x] T290b 刪除場次測試：空場次可刪、**有內容的場次也可刪**、訪客不可刪、**已刪除的場次不因後續 webhook 復活**（FR-118）in `tests/Feature/HighTicket/ConsultationSummaryTest.php`
- [x] T312 payload 無 TRANSCRIPT 檔改為靜默結束（FR-132 / D88 修訂）：`fetchTranscript()` 不再丟 `RuntimeException`，改寫 info log 後 return；測試以 `dispatchSync` 直接驗 job 不再拋例外（webhook controller 會吞例外，走 HTTP 驗不到）in `app/Jobs/ProcessZoomTranscriptJob.php`, `tests/Feature/HighTicket/ConsultationSummaryTest.php`
- [x] T289 ~~正式站 Forge 新增第二個 daemon 監聽 `long` 佇列~~ —— **記載有誤**：2026-08-18 跑的是 Forge 的一次性指令而非常駐 daemon（`/home/forge/.forge/provision-210173762.sh`），因此 8/20 之後自動路徑再度停擺。改由 T334 的排程承擔，見 D109
- [x] T334 排程消費 `long` 佇列（D109 / FR-142）：`Schedule::command('queue:work', ['database_long', '--queue=long', '--stop-when-empty', '--max-time=1500', '--tries=3', '--timeout=1500'])->everyMinute()->runInBackground()->withoutOverlapping(30)` in `routes/console.php`
- [x] T335 排程守門測試：schedule 裡 MUST 恰有一條 `queue:work`，且含 `database_long` / `--queue=long` / `--stop-when-empty` —— 這條是「第三次無聲停擺」的唯一防線 in `tests/Feature/HighTicket/LongQueueDrainScheduleTest.php`
- [x] T287a 匿名化實測：掃過正式站全部 13 份逐字稿的每一行，講者標籤異常 **0 筆**（全為「顧問」／「客戶」，無 Zoom 顯示名稱或真實姓名殘留，FR-109）
**US25 — 手動抓取逐字稿**

- [x] T313 `ZoomTranscriptService::recordingPayload(string $meetingId): ?array` — 打 `GET /meetings/{id}/recordings`，成功回 `['object' => $json, 'download_token' => '']`（與 webhook 同形），Zoom 非 2xx 回 null 並寫 info log（含狀態碼，不含內容）；`FetchConsultationTranscript` 改為呼叫此方法，指令的錯誤文案不變（FR-133）in `app/Services/ZoomTranscriptService.php`, `app/Console/Commands/FetchConsultationTranscript.php`
- [x] T314 `ConsultationNoteController::fetchTranscript()` — 依序守門：Zoom 憑證未設定 / 無 `zoom_meeting_id` / `recordingPayload()` 回 null / 清單無 VTT，四種各回 422 與各自文案；有 VTT 則 `ProcessZoomTranscriptJob::dispatch()` 回 202 `{queued: true}`（FR-133 / FR-134）in `app/Http/Controllers/Admin/ConsultationNoteController.php`
- [x] T315 [P] 路由 `POST /admin/consultation-notes/{note}/fetch-transcript`（staff 群組內、`throttle:10,1`，比照既有 regenerate-summary 一行）in `routes/web.php`
- [x] T316 前端按鈕：`transcript_bytes` 為 0 時把「尚無逐字稿」換成「抓取逐字稿」按鈕（`action` class 沿用、`cursor-pointer`、處理中顯示「查詢中…」並 disabled）；成功顯示「已排入處理，約 1–3 分鐘後重新整理」，失敗顯示後端訊息（沿用既有 `error` 區塊）in `resources/js/Components/Admin/Leads/ConsultationNotesPanel.vue`
- [x] T317 測試（`Queue::fake()` 驗派工與否）：有 VTT → 202 且派工、Zoom 404 → 422 不派工、清單無 VTT → 422 不派工、憑證未設定 → 422、無 meeting id → 422、訪客 → 302/403 in `tests/Feature/HighTicket/ConsultationSummaryTest.php`
- [x] T318 使用者實測：2026-08-18 對 note 24 按下按鈕，端點查到 VTT 後派工回 202；worker 補上後該場逐字稿 69,308 字、摘要 2,508 字落庫

- [ ] T287 使用者實測（自動路徑）：Zoom 後台按 Validate 通過；跑一場實際會議確認逐字稿與摘要**由 webhook 自動**出現（手動 `booking:fetch-transcript` 路徑已驗證 13 次）；`tail` log 確認無內容外洩。**T289 已於 2026-08-18 完成，此項現在驗得了 —— 待下一場實際會議**

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
- [x] T149 使用者以瀏覽器實測：以顧問身分登入建時段、管理員改派、預約後確認信的 CC 收件者、Leads 名單的顧問欄

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
- [x] T181 `availableStarts()` 拿掉 `$minutes >= 45` 條件，分鐘數過濾對所有長度一律生效（FR-069 擴大範圍）in `app/Services/ConsultationSlotService.php`
- [x] T182 更新 `test_thirty_minute_starts_are_not_restricted_to_the_hour_or_half_hour`（更名為 `test_thirty_minute_starts_are_also_limited_to_the_hour_or_half_hour`，反轉斷言）；`test_forty_five_minutes_needs_three_consecutive_units`、`test_available_starts_requires_consecutive_units` 更新為新預期值；`test_rebooking_releases_the_previous_hold`／`test_release_expired_command_clears_stale_holds_only` 的 4 單位 fixture 擴為 7 單位以容納 `$starts[2]` 索引（過濾後只剩 2 個候選會 out-of-range）in `tests/Feature/HighTicket/SlotHoldTest.php`；連帶修正 `BookingWizardTest.php` 兩處撞到同一過濾規則的既有斷言（`test_slots_endpoint_reflects_the_bonus_code`、`test_expired_token_reports_expired_and_the_slot_is_free`）
- [x] T183 覆核區不出席警語文案改為「若確定預約卻無故不出席，將不可再申請本站免費諮詢名額。」in `resources/js/Components/Course/HighTicketBookingWizard.vue`

US17 — 週曆勾選預約並複製 Email

Phase 1 — 勾選
- [x] T184 `WeekGrid.vue` 新增 `selectedLeadIds`（`ref(new Set())`）與 `toggleSelect(booking)`；`selectableEmails` computed 依畫面順序（`visibleDays` → `day.bookings`）攤平出 `{ lead_id, email }`，供工具列取用 in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T185 預約區塊在暱稱左側加 `<input type="checkbox" class="h-3 w-3 shrink-0 cursor-pointer accent-teal-600">`，`@click.stop` 只切換勾選不開面板；僅 `state ∈ {booked, held}` 渲染（FR-071）。區塊既有 `title` 與名字 `truncate` 維持不變 in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`

Phase 2 — 工具列與複製
- [x] T186 格線上方加工具列：「已選 N 筆」+「複製 Email」（N=0 時 disabled）+「清除」（N>0 才出現）；沿用頁面既有按鈕樣式與 `cursor-pointer` in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T187 `copyEmails()`：取勾選中的 email → 去重（`Set`）→ `join(', ')` → `await navigator.clipboard.writeText()`；成功時按鈕文案暫時改為「已複製 N 筆」（2 秒後還原），勾選**不清空**（FR-073）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T188 複製失敗（非 secure context／權限被拒）的退路：工具列下方展開唯讀 `<input>` 帶完整字串並 `select()`，附一行「請按 Ctrl/Cmd+C 複製」；再次成功複製或清除選取時收起（FR-072）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`

Phase 3 — 驗證
- [x] T189 新增測試：`weekView()` 的 booked 與 held 區塊皆帶非空 `email`（D67 的契約守門）in `tests/Feature/HighTicket/ConsultationSlotAdminTest.php`
- [x] T190 `php artisan test` 全綠 ＋ `npm run build` exit 0
- [ ] T191 使用者實測：勾選框點得到且不會誤開面板、複製出來的字串貼進 Gmail 收件欄可正確拆成多個收件者、切週後勾選歸零、手機單日檢視可用

US18 — 預約名單依顧問篩選

Phase 1 — 後端篩選與選項
- [x] T192 `bookingLeadsQuery()` 簽名加第三參數 `?string $consultant`：`none` → `whereNull('consultant_id')`，其他非空值 → `where('consultant_id', (int) $consultant)`，空值不加條件（FR-075）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T193 `index()` 讀 `$request->query('consultant')`、併入 `$filters['consultant']`，並把它傳進**兩處** `bookingLeadsQuery()` 呼叫（列表分頁與 `statusCounts` 聚合）—— 漏掉任一處就是 FR-074 要防的那個症狀 in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T194 `index()` 的 booking 分支新增 `consultantOptions` prop：`User::where(fn ($q) => $q->where('role', 'admin')->orWhere('is_sales_consultant', true))->orderBy('nickname')->get(['id', 'nickname', 'email'])`（與 `ConsultationSlotController::index()` 同一條件，但不做 admin/顧問分歧，D70）in `app/Http/Controllers/Admin/HighTicketLeadController.php`

Phase 2 — 前端
- [x] T195 `Index.vue` 加 `consultantOptions` prop（`Array`，預設 `[]`）並下傳給 `BookingListTab` in `resources/js/Pages/Admin/HighTicketLeads/Index.vue`
- [x] T196 `BookingListTab.vue` 收 `consultantOptions` prop、加 `consultantFilter = ref(props.filters.consultant || '')`；課程篩選右側加同款 `<select>`：「所有顧問」(`''`) + 每位 staff（顯示 `nickname || email`，value 為 id）+ 「未指派」(`'none'`)，`@change="applyFilters()"`，非空時顯示清除 ✕（沿用課程篩選的 ✕ 樣式與 `cursor-pointer`）in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T197 三處 `router.get` —— `applyFilters()`、`applyFilter(status)`、`goToPage(page)` —— 一律補上 `consultant: consultantFilter.value || undefined`，確保換狀態 / 搜尋 / 翻頁都不掉篩選 in `resources/js/Components/Admin/Leads/BookingListTab.vue`
- [x] T198 空狀態文案判斷由 `filters.status ?` 改為「任一篩選有值」（status / search / course_id / consultant）才顯示「沒有符合條件的 Leads」in `resources/js/Components/Admin/Leads/BookingListTab.vue`

Phase 3 — 驗證
- [x] T199 新增測試：依顧問 id 篩選時列表與 `statusCounts` 同步收斂（同時斷言兩者，這是 FR-074 的守門）、`consultant=none` 只回未指派、顧問 + 狀態 + 搜尋疊加、`consultantOptions` 含管理員與銷售顧問不含一般會員 in `tests/Feature/HighTicket/LeadsTabsTest.php`
- [x] T200 `php artisan test` 全綠 ＋ `npm run build` exit 0
- [x] T201 使用者以瀏覽器實測：選顧問後 tab 百分比跟著變、選「未指派」撈得到舊資料、疊加狀態 tab 後篩選不掉、重新整理後篩選還在、清除 ✕ 可用

### 面談前一日提醒信（US19）

Phase 1 — Schema 與模板

- [x] T202 [P] 新增 migration：`high_ticket_leads` 加 `reminder_sent_at`（timestamp nullable，`after('cancelled_at')`）in `database/migrations/2026_08_09_000002_add_reminder_sent_at_to_high_ticket_leads_table.php`
- [x] T203 [P] `EmailTemplateSeeder::templates()` 追加 `high_ticket_consultation_reminder`（名稱「客製服務面談提醒」；subject 帶 `{{slot_time}}`；body 含時間 / 長度 / Zoom 連結 / 準時出席提醒 / 需改期請聯絡 `{{support_email}}`）in `database/seeders/EmailTemplateSeeder.php`
- [x] T204 新增 migration：沿用 `2026_08_08_000003` 的「讀 seeder、缺才 insert、永不 update」迴圈安裝新模板 in `database/migrations/2026_08_09_000003_install_consultation_reminder_template.php`（依賴 T203）
- [x] T205 [P] `$availableVariables` 追加 `high_ticket_consultation_reminder` 的六個變數與中文 label in `app/Http/Controllers/Admin/EmailTemplateController.php`
- [x] T206 [P] event_type 中文標籤映射追加「客製服務面談提醒」in `resources/js/Pages/Admin/EmailTemplates/Index.vue`（順手補上同樣缺席的「已改期」「已取消」兩個標籤 —— 它們原本在列表上顯示成裸 event_type）

Phase 2 — 寄送邏輯

- [x] T207 `reminder_sent_at` 加進 `$fillable` 與 `casts()`（`datetime`）in `app/Models/HighTicketLead.php`
- [x] T208 新增 `sendDayBeforeReminders(): int`：以台北時間算出翌日區間（FR-077）→ 撈該區間內 `lead_id` 非 null、`held_until` 為 null 的 `consultation_slots` → 取 lead 且 `confirmed_at` 非 null / `cancelled_at` 為 null / `reminder_sent_at` 為 null → **以 lead 的最早時段複驗落在區間內**（FR-077）→ 逐筆寄信、成功才 `reminder_sent_at = now()`；回傳實際寄出數 in `app/Services/HighTicketBookingService.php`
- [x] T209 新增 `sendReminderMail(HighTicketLead $lead, Course $course, CarbonInterface $startsAt): bool`：取 `high_ticket_consultation_reminder` 模板（缺則 log warning 回 false）、組六個變數（`slot_time` 用 `$this->slots->label()`、`consult_minutes` 用 `minutesFor($lead->booking_code)`）、`Mail::to($lead->email)->send(new TemplatedMail(...))` **無 cc、無 attachments**、失敗記 error 回 false in `app/Services/HighTicketBookingService.php`
- [x] T210 `reschedule()` 在 `increment('calendar_sequence')` 附近清空 `reminder_sent_at`（D72）in `app/Services/HighTicketBookingService.php`
- [x] T211 新增 `booking:send-reminders` 命令：呼叫 `sendDayBeforeReminders()`、輸出「已寄出 N 封面談提醒」、一律回 `SUCCESS`（FR-081）in `app/Console/Commands/SendConsultationReminders.php`
- [x] T212 註冊排程 `Schedule::command('booking:send-reminders')->timezone('Asia/Taipei')->dailyAt('17:00')`，並在註解寫明「台灣時間，伺服器為 UTC」in `routes/console.php`

Phase 3 — 驗證

- [x] T213 新增測試：排程註冊為 `0 17 * * *` + `Asia/Taipei`（從 `app(Schedule::class)->events()` 找該命令斷言 expression 與 timezone）、翌日台北 00:15 與 23:45 兩端都寄、今天與後天不寄、暫留中（`held_until` 非 null）與已取消（`cancelled_at`）不寄、`contacted` 狀態照樣寄、跨午夜面談只在起始日寄一次、重跑不重寄、改期後 `reminder_sent_at` 歸 null、信件內容含 `slot_time` 與 zoom 連結、`assertSent` 斷言 cc 為空、缺模板時不寄也不丟例外 in `tests/Feature/HighTicket/ConsultationReminderTest.php`
- [x] T214 `php artisan test` 全綠（559 passed / 2513 assertions）＋ `npm run build` exit 0
- [ ] T215 使用者實測：後台 Email 模板頁看得到「客製服務面談提醒」並可編輯 / 預覽；本機以 `php artisan booking:send-reminders` 對一筆翌日的測試預約實跑，確認收件內容與時間文字正確

### 諮詢時段格線顯示範圍改為 10:00–23:00（US13 微調）

- [x] T216 `GRID_START_MINUTE` 改 `10 * 60`、`GRID_END_MINUTE` 改 `23 * 60` in `app/Services/ConsultationSlotService.php`
- [x] T217 更新既有測試 `test_the_default_range_is_office_hours`：起訖改斷言 `10:00` / `23:00`、列數由 56 改 **52**（13 小時 × 4）；同時確認 `test_...outside_the_default_range...` 仍綠（06:30 的時段照樣把格線往前撐開，這是 D47 的守門）in `tests/Feature/HighTicket/ConsultationSlotAdminTest.php`
- [x] T218 `php artisan test --filter=ConsultationSlotAdminTest` 全綠 ＋ `npm run build` exit 0（前端無改動，僅確認未破版）

### 改期面板直接選時段（US20）

Phase 1 — 後端

- [x] T219 `availableStarts()` 加第二參數 `?HighTicketLead $ignoring = null`：可用性查詢改為 `where(fn ($q) => $q->available()->orWhere('lead_id', $ignoring->id))`，未傳時維持原查詢（FR-083）in `app/Services/ConsultationSlotService.php`
- [x] T220 新增 `rescheduleOptions(HighTicketLead $lead)`：`isActiveBooking()` 為 false 回 422；以 `minutesFor($lead->booking_code)` 取長度、`availableStarts($minutes, $lead)` 取清單，依 `dateLabel()` 分組回 JSON（形狀比照 `HighTicketBookingController::slots()`）in `app/Http/Controllers/Admin/ConsultationSlotController.php`
- [x] T221 註冊 `GET /admin/consultation-slots/reschedule-options/{lead}`（staff 群組，**宣告在 `{consultationSlot}` 之前**）in `routes/web.php`
- [x] T222 `reschedule()` 成功後改 `redirect()->route('admin.consultation-slots.index', ['week' => $result 新時段的台北日期])`，flash 訊息不變（FR-084）；service 的回傳補上可供導向的日期 in `app/Http/Controllers/Admin/HighTicketLeadController.php`、`app/Services/HighTicketBookingService.php`

Phase 2 — 面板 UI

- [x] T223 `startReschedule()` 改為 async：`axios.get` 抓選項、期間顯示載入中；成功後填入 `options`，失敗顯示錯誤並留在改期模式；每次進入都重抓不快取（FR-085）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T224 改期橫幅內加日期 `<select>` + 時間 `<select>` + 「確認改期」按鈕：時間選項依所選日期連動、空清單顯示空狀態文案、確認沿用既有 `window.confirm` 文案（舊 → 新）後 emit `reschedule`；下拉與按鈕 `cursor-pointer` + focus 樣式；內容變動後 `nextTick(updatePanelPos)` in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`
- [x] T225 面板容器確認不被 `overflow-x-auto` 裁切（既有 `position: fixed` 已成立，僅需驗證下拉展開後的高度與翻轉行為）in `resources/js/Components/Admin/ConsultationSlots/WeekGrid.vue`

Phase 3 — 驗證

- [x] T226 新增測試：端點回傳分組結構、下週的時段出現在清單、該 lead 目前所在的起始**不**出現、無可用時段回空 `slots`、guest 導向登入、非生效預約 422 in `tests/Feature/HighTicket/ConsultationSlotAdminTest.php`（放這裡而非 BookingChangeTest：時段建置的 helper 都在這支）；改期成功導向 `week=` 新時段所在週的測試 in `tests/Feature/HighTicket/BookingChangeTest.php`
- [x] T227 `php artisan test` 全綠（572 passed / 2544 assertions）＋ `npm run build` exit 0
- [x] T228 使用者實測：選一筆本週的預約 → 面板下拉選到下週或下下週的時段 → 確認後畫面跳到該週且預約出現在新位置；同週用格線點選的舊路徑仍可用

### US21 高價課多方案與分級授權

Phase 1 — Schema 與 Model（授權的單一真相，其餘全部相依於此）

- [x] T229 [P] 建 `course_plans` 表（course_id / name / price / sort_order，index course_id）in `database/migrations/2026_08_13_000001_create_course_plans_table.php`
- [x] T230 [P] 建 `course_plan_lesson` 樞紐表（unique(course_plan_id, lesson_id)，兩邊 cascade）in `database/migrations/2026_08_13_000002_create_course_plan_lesson_table.php`
- [x] T231 `purchases` 加 `course_plan_id`（nullable，FK **restrict**）in `database/migrations/2026_08_13_000003_add_course_plan_id_to_purchases_table.php`
- [x] T232 [P] 新 Model：`course()` / `lessons()` BelongsToMany(`course_plan_lesson`) / `purchases()` in `app/Models/CoursePlan.php`
- [x] T233 `course_plan_id` 進 `$fillable`、`plan()` BelongsTo、`accessibleLessonIds(): ?array`（null = 全部；方案不存在回空陣列，FR-088）in `app/Models/Purchase.php`〔touchpoint 005〕
- [x] T234 `plans()` HasMany(orderBy sort_order) + `planLessonIdsForUser(?User): ?array`（無 user／admin／無方案／全開購買／drip 訂閱者皆回 null）in `app/Models/Course.php`〔touchpoint 004〕

Phase 2 — 後台方案設定（相依 T232）

- [x] T235 `store` / `update` / `destroy` / `syncLessons`；`store` 擋非 high_ticket（403）；`destroy` 查 `purchases()->exists()` 回 422 帶人數（FR-093）in `app/Http/Controllers/Admin/CoursePlanController.php`
- [x] T236 [P] `name` required|string|max:50、`price` nullable|integer|min:0 in `app/Http/Requests/Admin/StoreCoursePlanRequest.php`
- [x] T237 [P] `plan_ids` array、每個 plan 須屬於該 lesson 的 course（自訂 rule 或 controller 驗證）in `app/Http/Requests/Admin/SyncLessonPlansRequest.php`
- [x] T238 admin 群組加四條路由：`POST /admin/courses/{course}/plans`、`PUT|DELETE /admin/plans/{plan}`、`PUT /admin/lessons/{lesson}/plans` in `routes/web.php`〔touchpoint 000〕
- [x] T239 `index()` 的 course payload 加 `type`、加 `plans`（id/name/price）；兩處 lesson map 各加 `plan_ids`（eager load `lessons.plans:id`）in `app/Http/Controllers/Admin/ChapterController.php`〔touchpoint 004〕
- [x] T240 新元件：方案清單（inline 編輯名稱 + 建議價格 + 刪除）、「新增方案」、空清單與有方案兩種 `HintBox` 說明 in `resources/js/Components/Admin/CoursePlanPanel.vue`
- [x] T241 掛載 `CoursePlanPanel`（`v-if="course.type === 'high_ticket'"`）於 `<ChapterList>` 上方並透傳 `plans` in `resources/js/Pages/Admin/Courses/Chapters.vue`〔touchpoint 004〕
- [x] T242 加 `plans` prop；`plans.length > 0` 時在**章節內與獨立小節兩處**模板的小節列渲染方案 chip，點擊 `router.put('/admin/lessons/{id}/plans')`（`preserveScroll`）in `resources/js/Components/Admin/ChapterList.vue`〔touchpoint 004〕

Phase 3 — 教室分級授權（相依 T233/T234，本故事的核心）

- [x] T243 `show()` 取 `$planLessonIds`，套用於**四處**：章節 lessons filter、獨立小節 filter、`$allLessons`、`$completedLessonIds` 查詢範圍（FR-089）in `app/Http/Controllers/Member/ClassroomController.php`〔touchpoint 003〕
- [x] T244 `markComplete()` / `markIncomplete()` 各加方案外 403；`preview()` 明確不動（FR-090）in `app/Http/Controllers/Member/ClassroomController.php`〔touchpoint 003〕
- [x] T245 `getCourseProgressSummary()` 加選填 `?array $scopeLessonIds`（取交集縮分母，不傳則行為不變）in `app/Models/User.php`〔touchpoint 001〕
- [x] T246 [P] 我的課程卡片傳入該筆 purchase 的 `accessibleLessonIds()`（eager load `plan.lessons:id`）in `app/Http/Controllers/Member/LearningController.php`〔touchpoint 003〕

Phase 4 — 開通選方案（相依 T233）

- [x] T247 `convertLead()` 加 `?int $coursePlanId = null`：課程有方案卻沒選 → 422、plan 不屬該 course → 422、寫入 `Purchase::updateOrCreate`；`sendConversionMail()` 的 `{{course_name}}` 附方案名。既有 `isOverwritable()` / force 守門不動（FR-092）in `app/Services/HighTicketLeadService.php`
- [x] T248 `convert()` 驗證加 `course_plan_id`（nullable|integer）並傳入；`index()` 的 `grantableCourses` 加 `plans`（eager load）in `app/Http/Controllers/Admin/HighTicketLeadController.php`
- [x] T249 開通 modal 加方案下拉（該課有方案時必選）；`watch(convertCourseId)` 的預設價擴充為 `plan.price ?? course.display_price`；submit 帶 `course_plan_id` in `resources/js/Components/Admin/Leads/BookingListTab.vue`

Phase 5 — 會員詳情切換方案 + 補價（相依 T233）

- [x] T250 [P] `course_plan_id` nullable|integer、`additional_amount` nullable|integer|min:0 in `app/Http/Requests/Admin/UpdatePurchasePlanRequest.php`
- [x] T251 `show()` 的 courses map 加 `purchase_id` / `plan_id` / `plan_name` / `available_plans`；新增 `updatePurchasePlan()`（驗 purchase 屬該會員、plan 屬該課程，補價累加，單一 transaction，FR-094）in `app/Http/Controllers/Admin/MemberController.php`〔touchpoint 008〕
- [x] T252 加 `PATCH /admin/members/{member}/purchases/{purchase}/plan` in `routes/web.php`〔touchpoint 000〕
- [x] T253 擁有課程卡片加方案下拉 + 補價金額選填欄 + 儲存，成功後就地更新該卡（進度 % 一起變）in `resources/js/Components/MemberDetailModal.vue`〔touchpoint 008〕

Phase 6 — 通知信收斂（相依 T233）

- [x] T254 `store()` 寄 `LessonAddedNotification` 的收件人加方案過濾（FR-095）in `app/Http/Controllers/Admin/LessonController.php`〔touchpoint 004〕

Phase 7 — 驗證

- [x] T255 [P] 方案 CRUD：非 high_ticket 建方案 403、小節 sync、跨課程 plan_id 422、有人持有不能刪 422、無人持有可刪 in `tests/Feature/HighTicket/CoursePlanTest.php`
- [x] T256 [P] 分級授權（最關鍵）：持方案 A 只看到 A 的小節、`?lesson_id=` 指向 B 不渲染內容、未歸類小節看不到、`course_plan_id=null` 仍看全部（回歸保護）、`markComplete` 方案外 403、進度 4/4=100%、admin 看得到全部 in `tests/Feature/HighTicket/PlanAccessTest.php`
- [x] T257 [P] 切換方案：補價累加 `amount`、plan 不屬該課 422、purchase 不屬該會員 403 in `tests/Feature/HighTicket/PlanSwitchTest.php`
- [x] T258 擴充：有方案沒選 → 422、選了 → `course_plan_id` 落地、A→B 重開通覆寫方案不被守門擋 in `tests/Feature/HighTicket/LeadConvertTest.php`
- [x] T259 `php artisan test` 全綠 ＋ `npm run build` exit 0
- [x] T260 使用者實測：建 high_ticket 課 8 節 → 建方案 A(1–4) / B(全部，含 2 節重疊) → 開通方案 A → 該會員教室只見 4 節、進度分母 4、改網址指向第 6 節看不到 → 會員詳情切 B + 補價 5000 → 交易金額 = 原價+5000、教室見全部 8 節 → 手機寬度檢查

### US22 預約名單成交業績摘要

Phase 1 — Service（單一真相，controller 只負責傳）

- [x] T261 `conversionStats(Builder $leadsQuery): array` — 取範圍內 leads 的 email，連 `users` → `purchases`（`type=lead_conversion` + `status=paid`），回 `['month' => ['people' => int, 'amount' => int], 'year' => [...]]`；期間以台北時間建構再轉 UTC（FR-097/098/099）in `app/Services/HighTicketLeadService.php`

Phase 2 — Controller（相依 T261）

- [x] T262 `index()` 的 booking 分支呼叫 `conversionStats($this->bookingLeadsQuery($search, $courseId, $consultant))`，以 prop `conversionStats` 傳出；**不得**帶入 `$status`（FR-097）in `app/Http/Controllers/Admin/HighTicketLeadController.php`

Phase 3 — 前端（相依 T262）

- [x] T263 狀態色塊列右側加摘要區塊（`ml-auto`，本月／年度各一行、人數 + 金額，`toLocaleString()` 千分位），手機寬度換行到色塊下方；新增 `conversionStats` prop in `resources/js/Components/Admin/Leads/BookingListTab.vue`

Phase 4 — 驗證

- [x] T264 新增測試：顧問／課程篩選連動、`?status=` 不影響摘要、同一 email 多門課只算 1 人、退款不計、台北時間跨月邊界（月初 07:59 台北 = 前一月 UTC，必須算在本月）in `tests/Feature/HighTicket/ConversionStatsTest.php`
- [x] T265 `php artisan test` 全綠 ＋ `npm run build` exit 0
- [x] T266 使用者實測：切換顧問／課程篩選確認數字跟著變、點狀態 tab 確認數字不變、手機寬度版面

## 進度日誌

- 2026-08-22: `long` 佇列的消費者改由排程承擔（T334 / T335、FR-142、D109）— 使用者回報「面談後自動抓逐字稿又停了」。查正式站：`jobs` 表 6 筆 `ProcessZoomTranscriptJob`、`queue=long`、`attempts=0`、`reserved_at=NULL`（當天 04:40–07:01 陸續進來），`consultation_notes` 自 8/20 06:55 之後再無任何 `transcript_fetched_at`，log 裡連一行錯誤都沒有 —— **與 8/17 那次同一個故障**。`/etc/supervisor/conf.d/` 只有兩個 12 月／1 月建立的 conf（都是 `queue:work database`，只吃 `default`），`long` 佇列沒有任何消費者。追下去才發現 T289 的記載是錯的：8/18 跑的不是 Forge daemon，是一次性指令（`~/.forge/provision-210173762.sh` 內容正是 `queue:work database_long --queue=long …`），它把當時的積壓清掉之後就隨著指令視窗結束了，所以「修好」只是那一次的錯覺。修法是把消費者放進**程式碼**：`routes/console.php` 每分鐘背景跑 `queue:work database_long --queue=long --stop-when-empty --max-time=1500`、`withoutOverlapping(30)`。跟著 deploy 走、被刪會出現在 diff 裡、cron 每分鐘重起一次行程，本質上沒有「跑著跑著就沒了」這個狀態（D109）。`--stop-when-empty` 是關鍵，少了它會變成沒人監管的常駐行程、且之後每次 tick 都被重疊鎖跳過 —— 因此 T335 直接把這個字串釘進測試。正式站手動 drain 補回積壓（note 21 逐字稿與摘要已落庫，其餘陸續處理，`failed_jobs` 無新增）。全站 `php artisan test` **780 passed（3224 assertions）**。

- 2026-08-20: US27 預約名單直接婉拒並取消（T326–T333）— 後台複查完申請內容後，可直接在該列按「婉拒」：一個動作釋出時段、刪 Zoom 會議、寄出婉拒通知（附 `METHOD:CANCEL` 的 `.ics`）並把狀態落成 `declined`。先前這件事得走兩段路（週曆按取消 → 回名單改狀態），漏做任一段名單就開始說謊。實作上把 `cancel()` 的五個副作用抽成 `releaseBooking($lead, $status, $eventType)`，`cancel()` 與新的 `decline()` 都委派 —— 複製一份是保證下次只改到一邊（D104）。`cancelled_at` 與 `declined_at` 一起寫：前者才是 `isActiveBooking()` 讀的那一欄，只寫後者會讓一筆沒有時段也沒有 Zoom 的 lead 繼續被當成生效中的預約（D106）。婉拒理由寫在新模板 `high_ticket_booking_declined` 的內文裡（D105，措辭改為第二人稱、把判斷放在時機而非人），確認框直接渲染該模板內文，不在前端另抄一份。順手修掉一個既有瑕疵：`recordLead()` 復活 lead 時只清 `cancelled_at`，`?resume=` 回站的人會頂著一枚已經不成立的「已婉拒」紅標（FR-139）。新增 `BookingDeclineTest` 15 條；全站 `php artisan test` **774 passed（3203 assertions）**（新測試前）、加上婉拒的 Zoom 失敗案例後該檔 15 passed（41 assertions）；`npm run build` exit 0。

- 2026-08-18: T289 正式站 `long` 佇列 worker 補上，US23 自動路徑的最後一塊基礎設施到位 —— 使用者回報「面談完 7 小時、手動按抓取逐字稿也沒有結果」。查正式站：`jobs` 表 6 筆 `queue=long`、`reserved_at=NULL` 全數躺著（最舊的從 8/17 起），其中一筆正是那次按鈕派的工作單；`ps aux` 只看得到一個 `queue:work database` 的 daemon，`long` 佇列從頭到尾沒人領。**按鈕與端點都是好的** —— 它同步查到 Zoom 上確實有 VTT 才派工回 202，壞的是慢的那一半沒有 worker，正是 T289。Forge 加第二個 daemon（`queue:work database_long --queue=long --timeout=1500`）後佇列排空，該場（note 24）逐字稿 69,308 字、摘要 2,508 字落庫，`failed_jobs` 無新增。T318 因此一併驗完（三種文案的另外兩種在先前測試已釘住）。T287（webhook 自動路徑）仍待下一場實際會議 —— 這次的逐字稿來自手動派的工作單，不是 webhook 觸發的。

- 2026-08-17: US25 實作完成（T313–T317，僅剩 T318 使用者實測）— 後台面談紀錄列的「尚無逐字稿」改成「抓取逐字稿」按鈕，直接問 Zoom 現況、不依賴 webhook 是否到達。端點兩段式（FR-133）：同步查 `GET /meetings/{id}/recordings`（一秒），四種擋下的情形各給各自文案（憑證未設定／無 meeting id／Zoom 沒有錄影／逐字稿還沒產出），只有清單裡真有 VTT 才派工並回 202。查 Zoom 錄影清單的實作抽成 `ZoomTranscriptService::recordingPayload()`，`booking:fetch-transcript` 指令改呼叫同一個方法 —— 那段 HTTP 呼叫原本只存在指令裡，複製到 controller 就會養出第二套逾時與錯誤處理。後端刻意不加「已有逐字稿就拒絕」的守門（FR-134），job 的 `transcriptIsSettled()` 已經擋住，按鈕本身在有逐字稿時也不顯示。測試 6 條（有 VTT 派工、無 VTT 不派工、Zoom 404 不派工、憑證未設定不打 Zoom、無 meeting id、訪客被擋）以 `Queue::fake()` 釘住「不該派工就真的沒派」。全站 746 passed（3126 assertions）、`npm run build` exit 0。

- 2026-08-17: US26 未完成申請的續填提醒（T319–T325）— 起因是使用者在正式站看到一筆「通過審核 7/10 但沒往下填」的 lead。排程每小時（限台北 09:00–21:00）挑出 3 小時前～7 天內、未填手機、仍為 pending 的審核通過者，寄一封帶 `?resume=` 的信，一生一次；點回站直接落在第二步（`screening_cleared` 由伺服器判定，不讓前端看著自己的預填答案自認通過）。順帶把 `resumeUrl()` 收成單一定義（原本只存在於 `NotifyHighTicketSlotJob`），並修掉 US24 造成的回歸：`hasApplication()` 誤含 `screened_at`，讓只完成審核的 lead 渲染出一排「—」。`php artisan test` 740 passed。

- 2026-08-17: 面談提醒信改為 CC 顧問（使用者決策，推翻 US19 原本的「不 CC 任何人」，FR-062 / FR-078 已改）—— 沿用確認信的 `confirmationCc()`：有指派就只 CC 顧問，未指派才退回通知清單，兩封信的收件規則因此只有一份定義。附件規則不變（仍不附 `.ics`，D73）。測試改寫原本斷言「完全無 CC」的那條，另補兩條（指派／未指派）。`php artisan test` 729 passed。

- 2026-08-17: 承諾清單移除「我有預算／決策權，希望在未來 3-6 個月內實踐計劃。」，回到三條（使用者決策，FR-026 已改）—— US24 的資格審核已經在第一步問過預算級距與決策狀態，承諾清單再勾一次是重複。同步改 `COMMITMENT_COUNT` 4 → 3 與三份測試的 payload。`php artisan test` 727 passed、`npm run build` exit 0。

- 2026-08-17: 審核倒數由 60 秒縮短為 15 秒（使用者決策，FR-128 已改）；婉拒畫面的 icon 換掉 —— 原本那條 path 是拼錯的，畫出半截弧線沒有圓，改用本檔既有的 information-circle。僅動 `BookingScreeningStep.vue`。

- 2026-08-17: US24 第一步改為漸進揭露（使用者回饋：一開頁就攤開五題太長）—— Email + 暱稱先行，按「下一步」才出問卷，之後一次只揭露一題並顯示 `N / 5`；草稿已帶答案者整份展開。僅動 `BookingScreeningStep.vue`，計分與端點不變，`BookingScreeningTest` + `BookingWizardTest` 59 passed、`npm run build` exit 0。

- 2026-08-17: 無逐字稿檔的 payload 改為靜默結束（T312，FR-132，D88 修訂）— 起因是使用者回報「面談完幾小時了摘要還沒出來」。查正式站：note 存在、`zoom_meeting_id` 對得上、webhook 也確實派了工，但 `jobs` 表裡兩筆 `ProcessZoomTranscriptJob` 的 `queue=long`、`attempts=0`、`reserved_at=NULL` 躺了四小時 —— **沒有 worker 在吃 `long` 佇列**，正是還沒做的 T289。手動 drain 後該場逐字稿 6,629 字、摘要 763 字正常落庫。
  drain 過程順帶暴露第二件事：四次執行裡有兩次 FAIL，訊息都是 `Zoom recording has no transcript file yet`，而且屬於**另一場**的 `recording.completed`。原設計要它丟例外靠 backoff 重試，但 job 拿到的是派工當下凍結的 payload 快照，`recording_files` 清單不會因為重試而長出 VTT —— 逐字稿是由另一個事件帶著自己的清單進來的。等於每場面談固定產生一個註定失敗的 job（三次重試、兩行 ERROR log、一筆 failed_jobs），把「這一則不是逐字稿事件」記成故障。改為寫 info log 後 return；`backoff` 留給重試真的有用的情形（下載失敗、API 暫時性錯誤）。逐字稿為空時 `summarise()` 本來就回 null，所以不會產生空摘要。
  測試用 `dispatchSync` 直接打 job —— 走 webhook 驗不到這件事，controller 的 try/catch 會把例外吞掉，新舊行為在 HTTP 層完全同形。全站 727 passed。**T289 仍未做**，正式站 worker 沒補上之前，自動路徑（webhook → 佇列）依然不會動。
  同一天第二場（`yiting…@gmail.com`，note 29）把兩件事一起釘死了：其一，`recording.transcript_completed` **確實有訂到也確實會來**（06:34 UTC 先來 `recording.completed` 只帶 `[MP4, M4A, TIMELINE, CHAT]`，10:01 UTC 才來 `[TRANSCRIPT/VTT]`），D88 的待確認項結案；其二，逐字稿的實際延遲是 **3 小時 27 分**，不是 spec 原本寫的「幾分鐘」——「面談完幾小時還沒摘要」在 worker 補上之後仍可能是正常現象，後台文案與心理預期都該以小時計。該場的逐字稿 25,788 字、摘要 1,152 字（以手動指令補上）。另補抓 note 4 / 11 / 13（8/11–8/14）皆回 `3301 此錄製不存在` —— 雲端錄影已過期，永久取不回。

- 2026-08-17: US24 實作完成（T295–T311）— 五題資格審核前置於預約精靈第一步：`BookingScreening` support 類別持有題目／選項／計分表（分數不下發前端）、`POST /course/{course}/screen` 審核即建 lead、未通過落 `declined` 且不寄信不佔時段不停序列信、送出時以請求帶的答案重新計分（沒帶答案放行 resume 與舊 lead）；精靈改五步（資格→資料→承諾→時段→確認）並新增 `BookingScreeningStep.vue`（60 秒審核畫面 + 婉拒文案）；後台加第 7 狀態「已婉拒」與展開列的資格審核區（分數／分級／五題中文答案由 model accessor 解析）。測試 `php artisan test` 全站 726 passed、`npm run build` exit 0。

- 2026-08-17: 前台時段選擇器改為日期成欄＋箭頭翻頁（FR-122）— 三輪修正才收斂，每一輪都是我少想一步。第一版把日期做成欄、時間直排，但用橫向捲動翻閱：使用者指出捲動操作不順，改為箭頭翻頁。第二版按日曆週切頁，並且欄寬固定 `4.75rem` —— 使用者兩個回饋都對：**沒考慮 RWD**（固定欄寬在 375px 手機上永遠塞不下，加斷點治標，根因是欄寬不該固定），以及**照週切完一頁只剩兩三欄、右邊一片留白**（留白不是因為畫了空日期，是分頁單位選錯）。最終版分頁單位改為「連續 N 個有時段的日期」，欄位平分整列、不設 max-width，每頁依螢幕寬度取 4 或 6 欄，完全不需橫向捲動。另兩個不明顯的點：週界若用 `new Date(ymd).getDay()` 會以**訪客時區**重解台北日期（已隨日曆週方案一併移除，但同類陷阱記著）；重新分頁時停在所選時段那一頁，否則套用優惠碼重抓後被彈回第一頁，看起來像選擇被清掉。706 passed。
- 2026-08-17: 承諾清單加第 4 條（FR-026）— 「我有預算／決策權，希望在未來 3-6 個月內實踐計劃。」插在原第 3 條之前（使用者決策）：預算與決策權是資格條件，該在對方承諾採取行動之前先問。同步改後端 `size:3` → 4 —— 這條若漏改，每一次送出都會被 422 擋下，而錯誤訊息「請確認全部的預約前提條件」完全不提條數，前端看起來像是全勾了卻送不出去。條數改寫成具名常數 `COMMITMENT_COUNT`，並補一支測試直接讀 Vue 的 `COMMITMENTS` 陣列與伺服器實際接受的長度比對 —— 跨語言的耦合靠註解自律遲早會斷。706 passed。
- 2026-08-17: [sync] 正式站補建與補抓完成（T291c / T291d / T287a）— 補建 39 筆紀錄（不只上線前那 7 筆，是所有曾確認且未取消的預約），再對已結束的場次補抓逐字稿：**13 場成功**（逐字稿 11k–27k 字、摘要 747–1054 字），4 場 Zoom 回 `3301 此錄製不存在`。掃過全部 13 份逐字稿的每一行，講者標籤異常 0 筆 —— FR-109 的匿名化在真實資料上成立，不只在測試裡。另補上索引缺口：`BackfillConsultationNotes.php`、`ConsultationSummaryModal.vue`、`..._000003` migration 三個新檔未列入 `owner_files`；T281 的「三條路由」描述已過時（現為四條，`PATCH .../transcript` 於 T293 移除）。**US23 仍為 partial**：自動路徑（webhook → 佇列）尚未驗證過，卡在 T289 —— 正式站的 `database_long` worker 還沒加，13 場全是手動指令跑的。
- 2026-08-16: 動作列位置與徽章捷徑（T294，FR-121）— 使用者實際用起來才發現的兩件事。其一：`ml-auto` 把兩個動作推到列尾，寬螢幕下與面談時間隔了半個畫面，「右邊根本看不到」。改成緊接時間，課程與顧問這類辨識資訊往後讓位；純文字連結也改成有邊框的小按鈕，原本跟旁邊的灰字長得一樣、看不出可點。其二：姓名旁的場次徽章加上點擊直開最近一場摘要 —— 讀摘要是打開那一列的唯一理由，做成「先展開、再找按鈕」是對常見路徑課稅。徽章改成展開鈕的兄弟元素而非子元素（button 套 button 是無效標記，而 `<span>` 掛 `@click.stop` 會讓它鍵盤到不了）。徽章的判準與數字改為「有摘要的場次」而非「總場次」—— 補建之後每筆確認過的預約都有紀錄，若按總場次算，全部 40 筆都會長出徽章，點下去卻是空白 modal。704 passed。
- 2026-08-16: 面談紀錄 UX 收斂（T292 / T292b，FR-120）— 原本每場次在展開列裡塞了一個摘要 textarea 加一個可收合的逐字稿 textarea，一位有三場面談的客戶等於三面牆，上下場次完全無法掃讀。改成單行場次列 + 兩個連結：摘要開 modal（沿用 `ReferrerDetailModal` 的 Teleport／ESC／背景鎖捲版型），逐字稿只給 `.txt` 下載。順手把 leads payload 裡的逐字稿本文拿掉改送 `LENGTH(transcript)` —— 「不直接呈現」若只是視覺上藏起來、本文仍躺在頁面原始碼裡，那叫裝飾不叫設計；一頁 20 筆 lead 各帶該客戶所有場次，本文可達 200k 字元。下載檔加 UTF-8 BOM，否則 Windows 記事本會把中文顯示成亂碼。**取捨已記入 FR-120**：逐字稿因此不可在後台編輯。702 passed。
- 2026-08-16: 清除逐字稿編輯路徑（T293，FR-120）— 使用者確認逐字稿不需要編輯後，把上一步暫時留著的死路徑清掉：`updateTranscript()`、其路由、`transcriptIsLocked()`、以及 `transcript_edited_at` 欄位（migration `..._000003`）。留一個永遠為 null 的時間戳，等於邀請後人寫程式去檢查它。清的過程發現原本那道守門其實擋錯了東西：它防的是「人改過的稿被 webhook 蓋掉」，但真正會發生的是**同一場次收到兩次事件**（我們同時訂閱 `recording.completed` 與 `recording.transcript_completed`，D88），而舊守門對此毫無作用 —— 等於同一份逐字稿付兩次校訂費，這個洞從一開始就在。改成 `transcriptIsSettled()`（`transcript_fetched_at` 有值且有內容）後兩者都擋住了，`--force` 仍可刻意重跑。701 passed。
- 2026-08-16: 補建指令（T291 / T291b，FR-119）— 使用者回報「7 個人預約只有 1 個有面談紀錄」，並推測是「有錄影才建立」。查正式站資料後是部署時間：lead 64–71 於 06:08–11:27 UTC 確認，lead 72 於 13:17 UTC 確認，而功能是 12:09 UTC 推上去的 —— 分界線正好落在那裡。這暴露了一個規劃時沒想到的缺口：**紀錄只在確認當下建立，所以上線前的預約永遠不會有**，而沒有紀錄 webhook 就沒有 `zoom_meeting_id` 可對，逐字稿會被靜靜丟掉（只留一行 `no matching record`）。那 7 場面談都還在 8/22–9/5，補得回來。新增 `booking:backfill-consultation-notes`，與確認路徑共用 `recordConsultationNote()`（因此改為 public）—— 該方法本來就以 `zoom_meeting_id` 比對既有列，補建的冪等性是白撿的。687 passed。
- 2026-08-16: 加刪除場次（T290 / T290b，FR-118）— 使用者回報有人誤入會議室、留下用不到的空紀錄，而面談紀錄一旦建立就沒有任何移除途徑（US14 的取消只在「無內容」時順帶刪列，且要先取消預約）。做成硬刪除：沒有還原介面可以正當化軟刪除，而隱藏的列在每一處以 email 查歷史的地方都還得再過濾一次。**刻意不設「有內容就不准刪」**—— 促成需求的正是一場短暫誤入而確實產生了錄影的會議，設限等於擋掉需求本身；改由 UI 依有無內容分級確認文案（有逐字稿時明說 Zoom 錄影過期後再也抓不回來），並在 server 端寫稽核 log（只記 note id、字元數與操作者 id，內容仍受 FR-109 約束）。另補一支測試釘住「已刪除的場次不因 Zoom 後續重送 `recording.completed` 而復活」—— Zoom 會連送數日，會復活的刪除鍵等於沒有。683 passed。
- 2026-08-16: 修正佇列租約（T288，FR-117）— 使用者問「時間差多久」時才發現：這支 job 是全站第一個分鐘級的工作，而 worker 預設 timeout 60 秒、`database` 連線 `retry_after` 90 秒。前者會把校訂砍在半途，後者更糟 —— job 跑超過 90 秒佇列就判定它死了、把同一份 payload 交給第二個 worker，變成重複處理與重複付 OpenAI 費用。既有 5 支 job 都是幾秒內的寄信，所以從來沒人踩到。新增 `database_long` 連線（`retry_after` 1800）並讓 job 自帶 `$timeout = 1500`；獨立連線而非直接調高 `database`，是為了讓卡住的信件仍在 90 秒後重試。連線只在非 sync 環境掛上，否則測試與手動指令的 inline 執行會被打斷。679 passed 不變。**正式站需為此連線加第二個 worker（T289）**
- 2026-08-16: US23 實作完成（T267–T286b，僅剩 T287 使用者實測）— `consultation_notes` 新表 + `ConsultationNote` model，`HighTicketLead::consultationNotes()` 刻意以 **email** 而非 `lead_id` 關聯（FR-116），所以買第二次的人所有場次會聚在一起。預約確認建列、改期搬 `met_at`、取消依內容有無決定刪或留，三個掛載點都在 `HighTicketBookingService`。Webhook 走 `/api/webhooks/zoom`：url_validation 先回、`v0:{ts}:{raw body}` HMAC 驗簽、5 分鐘時間戳容差，其餘例外一律吞掉回 200。
  **實作中抓到一個會毀掉所有中文逐字稿的 bug**：`preg_split('/\R/', ...)` 沒加 `/u` 時，`\R` 會匹配到 CJK 字元內部的 `0x85` 位元組，把中文字從中間切斷 —— 產出無效 UTF-8，接著每一次 `json_encode` 都失敗，而 job 的例外被 controller 吞掉，症狀是「逐字稿永遠是 null 且完全沒有錯誤訊息」。三處 split 全部補上 `/u`，並在 docblock 註明這個修飾符是功能性的不是裝飾。是測試的中文 fixture 逼出來的，用英文樣本永遠不會發現。
  防縮水檢查（FR-108）與兩個獨立的 `*_edited_at` 守門（FR-110）都由測試釘住。後台面談紀錄抽成 `ConsultationNotesPanel.vue` 獨立組件（BookingListTab 已 1438 行）。ConsultationSummaryTest 17 + ConsultationNoteTest 9，全套 679 passed（2861 assertions）、`npm run build` exit 0。
- 2026-08-16: [draft] 規劃 US23 面談逐字稿自動摘要 — 這條銷售線在「面談之後」是斷的：`high_ticket_leads` 連一個備註欄位都沒有，唯一的自由文字是面談**之前**的問卷答案。Zoom webhook 推 VTT → 機械式解析與講者正規化 → 分段校訂 → 摘要。關鍵前提是 US12 已經把 `zoom_meeting_id` 寫在每筆確認的預約上，對照關係現成、不必新建。
  使用者決策五項：webhook 而非輪詢（D87）、模型 `gpt-5.6-luna`、**面談紀錄獨立成 `consultation_notes` 表並以 email 為客戶識別鍵**（D92 —— 審核時推翻初版的「掛在 lead 欄位上」：那個設計的前提「一個 lead 對一場面談」只在售前成立，買了多次一對一顧問的客戶、以及即將開發的「客戶自行登記時段的顧問服務」都放不進去，第二場一來就要搬遷）、**逐字稿保留而非只存摘要**（D89，也是推翻初版；留著才能改格式後重跑、才能回頭查原話）、**逐字稿也可後台編輯**（連帶 D93：兩個 `*_edited_at` 各自獨立守門，因為「修逐字稿 → 重跑摘要」正是最有價值的路徑，一個共用旗標會把它堵死）。
  隱私上的取捨：保留全文的成本由 D90 抵銷 —— 講者一律正規化為「顧問」／「客戶」，原始姓名不落庫不進 log（FR-109），機械式對應優先、模型只處理殘留（FR-106，誰是誰是已知事實不是推理題）。技術上最需要小心的是 FR-108 的防縮水檢查：模型接近輸出上限時的失敗模式是悄悄開始摘要而不是截斷，一份被砍半的「逐字稿」看起來完全正常，所以分段送出 + 長度比對兩道一起上。
  依賴 000 US10 的 `ai_prompts` / `OpenAiService` 先落地。一支 migration 建一張新表；`high_ticket_leads` 完全不動。
- 2026-08-13: 業主實測 T228 / T260 / T266 全數通過 — US20（改期面板跨週選時段）、US21（多方案端到端：開通方案A → 教室只見該方案 → 切方案B + 補價 → 教室見全部）、US22（業績摘要隨篩選連動、狀態 tab 不影響、手機版型）三者的瀏覽器實測結束，索引狀態一併由 `partial` 轉 `implemented`。模組 status 維持 `building`：更早的實測任務（T040 實寄驗證、T084、T102、T123、T158、T191、T215）與 US16–US19 的驗收條款仍未勾，那些不在本次確認範圍內
- 2026-08-13: 業績摘要改單行、不增加列高（業主回饋，純前端樣式）— 初版做成有邊框底色的兩行卡片，把狀態色塊列整條撐高了。改為單行純文字 `text-xs`、拿掉邊框／底色／垂直 padding，整體矮於色塊（`text-sm` + `py-1.5` + border），列高回到由色塊決定；靠右仍用 `lg:ml-auto`，加 `whitespace-nowrap` 避免數字中間斷行。驗收條款一併就地改為「單行」並新增「不得增加列高」一條。無後端變更，11 支測試維持全綠、`npm run build` exit 0
- 2026-08-13: US22 成交業績摘要完成（T261–T265，僅剩 T266 使用者實測）— `conversionStats(Builder $leadsQuery)` 收在 service，收的是**已經篩好的 builder** 而不是一堆篩選參數：這樣它與列表、狀態色塊三者共用同一份範圍定義，不可能各自漂掉（FR-097）。`purchases` 沒有 `consultant_id` 也沒有回指 lead 的外鍵，email 是唯一的連結，所以先取範圍內 leads 的 email 再 join `users`。期間邊界用 `now('Asia/Taipei')` 建構再轉 UTC 的半開區間，不用 `whereMonth()` —— 台北 9/1 早上 07:00 在 UTC 還是 8/31，naive 寫法會把它歸到上個月（FR-098，測試釘住這條邊界）。人數 `count(distinct users.email)`、金額 `sum(amount)`，兩者基礎不同故 UI 不做客單價（D86）。新增 ConversionStatsTest（11 tests，含顧問／課程連動、狀態 tab 不影響、同人多課去重、退款與 gift 不計、跨月與跨年邊界），全套 628 passed（2719 assertions）、`npm run build` exit 0
- 2026-08-13: [draft] 規劃 US22 預約名單成交業績摘要 — 狀態色塊列右側加「本月／年度的成交人數與總金額」。範圍共用 `bookingLeadsQuery()`（FR-097），所以顧問／課程篩選會連動而狀態 tab 不會，與既有的漏斗百分比同一套邏輯；`purchases` 沒有 `consultant_id`，唯一的連結是 email，這也是為什麼必須共用 builder 而不是另寫一份查詢。三個使用者決策：跟著篩選連動、成交人數以 email 去重（D86，連帶禁止在 UI 上做人數與金額相除的「客單價」）、補價計入原成交月（FR-100，已知失真但不值得為它先建進帳記錄表）。期間以台北時間切分再轉 UTC（FR-098）。無 schema 變更。**2026-08-13 使用者確認六項關鍵決策，方案通過，進入 `/dev`。**
- 2026-08-13: 方案卡片加「選取章節」捷徑（FR-096 / D84）— 一節一節點 chip 對「方案B 涵蓋全部」是苦工，卡片內加 popover 以章節為單位整批加入／移除（獨立小節自成一組、另有全選／全部清除），按鈕上顯示 `已選/總數`。後端加第二個方向的端點 `PUT /admin/plans/{plan}/lessons`（送完整結果集合，不送差異），與既有的 lesson→plans 端點各自驗證歸屬課程。三態的「部分」刻意併入「尚未選滿」——再點是補齊不是清空，誤清的代價高於多點一次。做成 popover 而非展開面板是因為前一輪才剛壓低卡片高度，連帶拿掉外層 `overflow-hidden`（會裁切下拉）並把圓角移到 header。CoursePlanTest 11 → 14，全套 617 passed（2677 assertions）、`npm run build` exit 0
- 2026-08-13: 課程方案面板版面調整（業主實測回饋，純前端樣式）— 新增方案的輸入欄位改為常駐在標題列的按鈕前面（移除 `showAdd` 展開狀態，名稱空白時按鈕 disabled）；方案清單由「一個方案一整條撐滿版」改為 `grid-cols-1 / sm:2 / lg:4` 卡片，編輯／刪除移到卡片右側與文字同列（卡片從三行縮成兩行）。順手補了「未設建議價」的顯示文字 —— 原本該行留空會讓卡片高度不齊。無後端變更，US21 相關 55 支測試維持全綠、`npm run build` exit 0
- 2026-08-13: US21 高價課多方案與分級授權完成（T229–T259，僅剩 T260 使用者實測）— 三支 migration（`course_plans` / `course_plan_lesson` / `purchases.course_plan_id`），授權收斂在 `Purchase::accessibleLessonIds()` 與 `Course::planLessonIdsForUser()` 兩個方法。教室的四個判定點全部接上（章節側欄、獨立小節、`$allLessons` 的 currentLesson 解析、`$completedLessonIds` 範圍），兩個 progress 端點各補 403。進度分母走 `getCourseProgressSummary()` 新的第三參數，`LearningController` 與 `MemberController::show()` 各傳入該筆 purchase 的方案範圍。開通加方案下拉（預設價 `plan.price ?? display_price`），service 層擋「有方案卻沒選」與「方案不屬於此課程」；通知信的 `{{course_name}}` 附上方案名而非新增第六個變數 —— 剛匯完款的人得看得懂自己買到什麼，而那不該先要求管理員改模板。會員詳情加方案下拉 + 選填補價（累加不覆寫），切換後重抓詳情因為分母跟著換了。刪方案兩層擋（service 422 + FK `restrict`）。
  **實作中發現並記錄一個限制**（見 FR-095 補述）：小節是先建立、之後才勾方案，所以建立當下勾「通知學員」在有方案的課程上只會寄給全開會員 —— 這是 FR-095 的正確推論而非 bug，已用測試釘住，但「先歸類再通知」目前沒有路徑。
  新增 CoursePlanTest（11）、PlanAccessTest（14）、PlanSwitchTest（9），LeadConvertTest 15 → 21。全套 612 passed（2657 assertions）、`npm run build` exit 0。
- 2026-08-13: [draft] 規劃 US21 高價課多方案與分級授權 — 把「買了就看全部」的二元授權改為小節層級。地基是 `purchases.course_plan_id` 且 **null = 全部內容**（FR-087/D83）：方案掛在既有的一人一課記錄上而非另開表，於是所有既有資料與所有沒有選方案 UI 的路徑（結帳／贈課／匯入／兌換）零改動仍正確，升級補價也能直接累加在同一筆的 `amount`。授權判定收斂成兩個方法（`Purchase::accessibleLessonIds()` / `Course::planLessonIdsForUser()`），教室要套在**四個**判定點上（側欄、獨立小節、currentLesson 解析、完成紀錄範圍）—— 漏第三個畫面會空白、漏第四個進度會灌水，兩個 API 端點另擋 403。四個使用者決策：只限 high_ticket（D82，一般課程走前台結帳沒有選方案的 UI，開了是半套）、未歸類小節視為未授權（D81，兩種寫錯的代價不對稱）、方案外完全隱藏不做鎖頭（D80，連帶放棄教室內的升級誘因與管理員的方案視角預覽）、切換方案可填補價累加（FR-094）。刪方案用 `restrict` + service 層 422 兩層擋（FR-093）—— `nullOnDelete` 會把持有者靜默升級成全開，是最糟的失敗模式。status 維持 building（US20 的 T228 仍未結）。**2026-08-13 使用者確認六項關鍵決策，方案通過，進入 `/dev`。**
- 2026-08-11: US20 改期面板直接選時段（T219–T227，僅剩 T228 使用者實測）— 根因不是「選不到」而是**改期模式活不過換週**：換週是整頁 Inertia visit（`preserveState: false`，US17 為了勾選狀態刻意這樣做），`rescheduling` 與 `selected` 一起被重置，所以能點到的目標格永遠在當前週。新增 `GET /admin/consultation-slots/reschedule-options/{lead}`，用 `availableStarts($minutes, $lead)` 現算（新的選填參數把該 lead 自己的單位視為可用，45 分鐘場次才挪得到與自己部分重疊的起始），分組邏輯抽成 `ConsultationSlotService::groupStarts()` 與訪客精靈共用一份。實作中修掉兩個自己踩出來的坑：(1) 清單原本會包含「目前所在的起始」—— 移到原地不是改期，而且它會讓空狀態永遠不觸發，改在 controller 濾掉；(2) 前端原本想用 ISO 字串前 10 碼當日期送出，那是 UTC 日期，台北 08:00 前的時段會差一天，改由後端在每組附上台北 `Y-m-d`。改期成功後導向新時段所在的那一週（FR-084），否則跨週改期的結果會直接離開畫面。格線點選路徑保留（D76）。572 passed、`npm run build` exit 0
- 2026-08-11: 諮詢時段格線顯示範圍 08:00–22:00 改為 10:00–23:00（T216–T218 / D47）— 只動 `ConsultationSlotService` 的兩個常數，`WeekGrid.vue` 一行未改：格線高度、拖曳命中、時間標籤全部吃後端回傳的 `range.rows`（D46 的同一個決定在這裡付了利息）。列數 56 → 52。業主追加確認「最後一場結束不得晚於 23:00」，這不需要新規則：23:00 沒有對應的列所以拖不出來，而 `availableStarts()` 只提供整段皆已釋出的起始（FR-028），因此 30 分鐘場最晚 22:30 開始、45 分鐘場最晚 22:15 開始，兩者都在 23:00 收工 —— 加了斷言釘住「23:00 不在 rows 裡」。早於 10:00 的既有時段仍會把格線撐開（D47 的既有守門測試續綠），實質效果只是開不出新的早上 8–10 點時段。順手修正 D47 裡「固定值寫在前端常數」這句失真的描述（實作一直在後端）。566 passed、`npm run build` exit 0
- 2026-08-10: US19 面談前一日提醒信（T202–T214，僅剩 T215 使用者實測）— 排程 `booking:send-reminders` 以 `->timezone('Asia/Taipei')->dailyAt('17:00')` 註冊（`schedule:list` 顯示 `0 17 * * *`）。查詢窗以台北時間的翌日整日建構再轉 UTC，判定錨點是 lead 的最早一格，跨午夜的面談只算在起始日。`reminder_sent_at` 寄成功才寫、改期時清空。踩到一個純測試面的時區陷阱值得記下來：`Carbon::setTestNow()` 會把**測試時刻的時區**交給之後所有 Carbon 實例，包含 Eloquent 從 datetime 欄位建出來的那些 —— 用 Taipei 時區的 mock 會讓 DB 裡的 UTC 值被標成 +08:00，邊界比較整整差 8 小時而 production 完全正常。mock 改成 `->utc()` 後 12 條測試全綠。順手補上後台 Email 模板列表缺席的「已改期」「已取消」兩個中文標籤（原本顯示裸 event_type）。559 passed、`npm run build` exit 0
- 2026-08-09: US18 預約名單依顧問篩選（T192–T200）— 顧問條件加進 `bookingLeadsQuery()` 本身而不是各自加在列表與 `statusCounts` 上，是這次唯一有份量的決定：兩個查詢共用一個 builder，狀態 tab 的漏斗百分比就必然跟著篩選走，不會出現「列表 3 筆、tab 卻寫 400 筆」。守門測試也照這個形狀寫 —— 同一條測試同時斷言 `leads.data` 與 `statusCounts`，兩者漂移就紅。`consultant=none` 用字串哨兵而非另開參數（D70）。順手修掉一個既有小 bug：空狀態文案原本只看 `filters.status`，用課程或顧問篩到空集合會謊報「尚無預約記錄」，改為任一篩選有值即顯示「沒有符合條件的 Leads」。權限維持 D27 / D70：顧問不被自動帶入自己，看得到整份名單。547 passed、`npm run build` exit 0
- 2026-08-09: US17 週曆勾選預約並複製 Email（T184–T190）— `WeekGrid.vue` 加本地勾選 state（`pickedLeadIds` Set，重新賦值而非就地 mutate 才保得住 computed 反應性）、booked/held 區塊內常駐 12px checkbox（`@click.stop` 不觸發改期面板）、格線上方工具列（已選 N 筆／複製 Email／清除），複製走 `navigator.clipboard` 並在失敗時展開唯讀輸入框自動全選當退路。後端零改動，僅在 `ConsultationSlotAdminTest` 補一條守 payload 契約的測試（booked 與 held 區塊必帶 email）。全套 541 passed、`npm run build` 綠

- 2026-08-09: US15（諮詢時段指派銷售顧問）業主確認全部完成 —— 10 條驗收與 T149 實測勾選；覆核程式碼確認顧問欄位與自動指派、歸屬選擇器（顧問鎖定自己）、管理員改派既有預約、空格歸屬 tooltip、確認時快照到 lead、Leads 名單顧問欄、Zoom 指定主持人與 404 fallback 皆已在位，`ConsultantAssignmentTest` + `ZoomMeetingTest` 23 passed。索引 status 由 partial 轉 implemented

- 2026-08-08: 整點/半點限制擴大到 30 分鐘預設場次 + 不出席警語文案改寫（T181–T183 / FR-069）— 業主回報「上次說過的整點/半點限制怎麼還是沒改」，查證後發現 8/7 的實作把範圍誤縮小成只有 45 分鐘（優惠碼延長）場次才過濾，30 分鐘預設場次仍可選到 `:15`/`:45`；業主確認要擴大到所有長度。`availableStarts()` 拿掉 `$minutes >= 45` 條件，過濾對所有長度一律生效。TDD：先改 `SlotHoldTest` 既有測試的預期值反映新規則，確認因舊 code 未改而紅，拿掉條件後轉綠；過程中發現兩個測試（`test_rebooking_releases_the_previous_hold`、`test_release_expired_command_clears_stale_holds_only`）用 `$starts[2]` 索引 4 單位 fixture，過濾後只剩 2 個候選會 index-out-of-range，改用 7 單位 fixture 讓 3 個整點/半點候選都存在。跑全套時另外抓到 `BookingWizardTest.php` 兩個斷言撞到同一規則變更，一併修正。不出席警語同時由「我們將永久黑名單」改為「將不可再申請本站免費諮詢名額」（業主要求文案更具體），同步修正 D37 與後台狀態說明表格裡的舊引用文字。全套 540 passed、`npm run build` 綠。
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

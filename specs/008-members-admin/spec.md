---
id: 008-members-admin
status: draft
owner_files:
  - app/Http/Controllers/Admin/MemberController.php
  - app/Http/Requests/Admin/UpdateMemberRequest.php
  - app/Http/Requests/Admin/ToggleSalesConsultantRequest.php
  - app/Http/Requests/Admin/GiftCourseRequest.php
  - app/Http/Requests/Admin/SendBatchEmailRequest.php
  - app/Mail/BatchEmailMail.php
  - app/Mail/CourseGiftedMail.php
  - resources/views/emails/batch-email.blade.php
  - resources/views/emails/course-gifted.blade.php
  - resources/js/Components/ImportMembersModal.vue
  - resources/js/Components/MemberDetailModal.vue
  - resources/js/Components/BatchEmailModal.vue
  - resources/js/Components/GiftCourseModal.vue
  - resources/js/Pages/Admin/Members/Index.vue
touchpoints:
  - file: app/Models/User.php
    owner: 001-auth-account
    why: members() scope 與 isManageableMember() 定義可管理帳號範圍；getCourseProgressSummary() 計算課程進度；匯入時建立 User；讀寫 is_sales_consultant 旗標（欄位與 cast 歸 000）
  - file: routes/web.php
    owner: 000-platform-core
    why: 註冊 admin-only 路由 PATCH /admin/members/{member}/sales-consultant（切換銷售顧問身份）
  - file: app/Models/Purchase.php
    owner: 005-checkout
    why: 贈課（type=gift）與匯入指派授權（type=lead_conversion）以 updateOrCreate 寫入 purchases；持有判斷用 paidStatus scope
  - file: app/Models/LessonProgress.php
    owner: 003-classroom
    why: 會員詳情的課程完成進度以 lesson_progress 記錄計算
  - file: app/Models/AssignmentCompletion.php
    owner: 003-classroom
    why: 會員詳情顯示作業完成記錄（唯讀，含關聯已刪除 null-guard）
  - file: app/Models/Course.php
    owner: 004-course-admin
    why: 課程篩選下拉、贈課 modal 與贈課通知信讀取課程 name / description
  - file: app/Services/PointService.php
    owner: 007-points-referral
    why: 詳情 modal「派發積分」入口呼叫 PointService::award('admin_grant')，帳本邏輯歸 007
---

# Members Admin（後台會員管理）

## 目標

讓管理者在後台集中管理會員：查詢與編輯基本資料、檢視課程持有與學習進度、
批次寄信與贈課、CSV 匯入匯出，並提供派發積分的操作入口（積分邏輯歸 007-points-referral）。

## User Stories

### User Story 1 - 會員列表與搜尋 (Priority: P1)

管理者在 `/admin/members` 檢視分頁會員列表，可關鍵字搜尋、欄位排序、依課程持有篩選。

**驗收**：
- [x] 列表顯示 email、暱稱、真實姓名、電話、生日、last_login_ip、註冊時間、最後登入時間；分頁預設 50 筆、上限 100
- [x] 關鍵字同時模糊比對 email / real_name / nickname
- [x] 排序欄位白名單：email、real_name、created_at、last_login_at（預設 created_at desc，非法值 fallback 預設）
- [x] 課程持有篩選只計 purchases paidStatus（refunded 不算持有）

### User Story 2 - 編輯會員資料 (Priority: P1)

表格內 inline 編輯 email / 姓名 / 電話；詳情 modal 編輯暱稱 / 生日；email 一鍵複製。

**驗收**：
- [x] inline 編輯儲存後立即反映（Inertia redirect + flash）；modal 編輯走 AJAX（wantsJson 回 JSON）
- [x] email 唯一性驗證（unique ignore 自己）、格式驗證；生日不可為未來日（before_or_equal:today）
- [x] 所有欄位 `sometimes` 規則 — 可單欄位部分更新
- [x] email 欄旁複製按鈕，複製成功有視覺回饋

### User Story 3 - 會員課程持有與學習進度 (Priority: P2)

詳情 modal 顯示課程列表（含取得方式標籤）、每課程完成進度、作業完成記錄，
以及積分餘額 + 帳本 + 派發積分入口。

**驗收**：
- [x] `GET /admin/members/{member}` 回 JSON：member、courses、homework_completions、point_transactions（最近 50 筆，含 is_matured）
- [x] 進度 = 已完成 lessons / 課程全部 lessons（百分比）
- [x] 取得方式標籤：type=lead_conversion → 顧問轉換；gift / system_assigned → 贈送；其他 → 購買
- [x] 作業完成記錄 null-guard：關聯 assignment / lesson / course 已刪除者靜默排除，不噴錯
- [x] 派發積分表單（只增加、無扣點入口）→ `POST /admin/members/{member}/grant-points` → PointService::award('admin_grant')，即時生效（詳見 007-points-referral）

### User Story 4 - 批次選取會員 (Priority: P2)

Checkbox 個別選取、當頁全選、跨頁「選取所有符合條件的 N 位」。

**驗收**：
- [x] 當頁全選後出現橫幅，可升級為跨頁全選（matchingCount 由 index 回傳；`GET /admin/members/count` 供動態查詢）
- [x] 跨頁全選啟用時清除個別選取，計數顯示 matchingCount
- [x] 換頁保留選取狀態

### User Story 5 - 批次寄送 Email (Priority: P3)

選取會員後在 modal 編寫主旨與內文（支援 Markdown），同步寄送給所有選取者。

**驗收**：
- [x] 主旨必填 ≤200 字、內文必填 ≤10000 字
- [x] 內文 Markdown 經 CommonMarkConverter 轉 HTML（BatchEmailMail 建構時轉換）
- [x] 同步 Mail::send 逐一寄送；單封失敗 Log::error 記錄後繼續，不中斷整批
- [x] 無 email 的會員排除，回報 skipped_count；全部無效回 422
- [x] route `throttle:10,1` 防濫用

### User Story 6 - 贈送課程 (Priority: P3)

選取會員後贈課：建立 type=gift 的 purchase 並寄通知信。

**驗收**：
- [x] 已持有（paidStatus）者略過並回報 already_owned_count；全部已持有回「所有選取的會員都已擁有此課程」
- [x] updateOrCreate（user_id + course_id unique）可救回 refunded 舊紀錄；amount=0、currency=TWD、status=paid、type=gift
- [x] CourseGiftedMail 含課程名稱與簡介；空簡介顯示「（無課程簡介）」
- [x] 無 email 者仍贈課但跳過寄信，回報 skipped_no_email_count
- [x] route `throttle:10,1`

### User Story 7 - 匯出會員 CSV (Priority: P3)

右上常駐「匯出」下拉：匯出全部（尊重目前搜尋 / 課程篩選）或匯出選定。

**驗收**：
- [x] streamDownload + UTF-8 BOM（Excel 相容）+ chunk(200)；檔名 `members-YYYY-MM-DD.csv`
- [x] 欄位依序：暱稱、真實姓名、Email、加入日期、最後登入時間（空值輸出空字串）
- [x] 「匯出選定」支援跨頁全選的 id 集合；無選取時選項 disabled；scope=selected 且 ids 空 → 422
- [x] 下拉即時顯示匯出範圍 hint（全部會員 / 符合篩選的 N 位 / 已選取的 N 位）

### User Story 8 - 匯入會員名單 (Priority: P3)

匯入 modal 兩種模式：貼上 Email 名單、上傳 CSV（前端解析 + 預覽）；可選同時指派課程授權。

**驗收**：
- [x] 貼上模式：換行 / 逗號分隔混用皆可、自動去重、格式驗證；輸入上限 50000 字元
- [x] CSV 模式：前端解析（首列為標題列不驗證名稱；位置對應第 1~3 欄 = Email / 姓名 / 電話，多餘欄忽略；<3 欄或無資料列擋下）→ 解析後以 rows JSON 送同一匯入 API
- [x] 新帳號：role=member、nickname=email @ 前段、email_verified_at=now、無密碼（驗證碼登入即可用）；email 一律轉小寫
- [x] 已存在 email 整列略過，絕不覆寫既有會員資料
- [x] 台灣手機驗證：09 開頭須恰 10 碼，否則帳號照建但電話留空，email 列入 phone_format_errors 清單
- [x] 可選課程指派：新舊帳號皆授權（已持有 paidStatus 者跳過）；purchases type=lead_conversion、amount=0、updateOrCreate
- [x] 結果摘要留在 modal 內：新增 / 略過 / 無效格式（含完整清單）/ 電話格式錯誤（含清單）/ 指派授權數；關閉 modal 後刷新列表

### User Story 9 - 指派銷售顧問身份 (Priority: P2)

管理員在會員詳情 modal 一鍵把會員設為 / 取消「銷售顧問」；列表以標籤標示已指派者。
被指派者可協助管理 Leads 名單與折扣碼（後台存取控管見 000-platform-core US 6）。

**驗收**：
- [ ] 詳情 modal「基本資料」區顯示銷售顧問開關（僅 `role=member` 的帳號顯示；admin 帳號不顯示，避免無意義切換）
- [ ] `PATCH /admin/members/{member}/sales-consultant`（admin-only、`isManageableMember()` 守門）切換 `is_sales_consultant`，`wantsJson` 回 JSON，即時反映；非可管理帳號回 403 / 404
- [ ] `GET /admin/members/{member}` 詳情 JSON 增回 `is_sales_consultant`
- [ ] 列表對已指派者顯示「銷售顧問」標籤（badge）；切換成功後 `router.reload({ only: ['members'] })` 更新列表
- [ ] 切換只寫 `is_sales_consultant` 單一欄位，不動會員其他資料

## Requirements

- **FR-001**: 會員管理範圍 = `role in (member, admin)`（User::members() scope）；editor 排除。show / update / grant-points 以 isManageableMember() 守門，違反回 404 / 403。
- **FR-002**: 課程「持有」一律以 purchases.status 判斷（paidStatus）；「取得方式」標籤一律以 type 判斷。兩者不可混用。
- **FR-003**: 批次信與贈課通知同步寄送（Mail::send），不依賴 queue；Mailable 保留 Queueable trait 以便未來切 queue() 不改類別。
- **FR-004**: 批次寄信與贈課 route 皆 `throttle:10,1`。
- **FR-005**: 匯入絕不修改既有會員任何欄位（僅可能為其新增課程授權）。
- **FR-006**: 列表 index、count、匯出三處的搜尋 + 課程篩選 where 邏輯必須一致，跨頁全選人數才會與匯出結果吻合。
- **FR-007**: 派發積分僅有「增加」入口，無扣點 UI；帳本寫入與不變量由 PointService 負責（007 模組）。
- **FR-008**: 指派銷售顧問限 `role=member` 的可管理帳號；切換為冪等的布林寫入，不觸及會員其他欄位。身份實際授予的存取權限由 000-platform-core 的 `staff` middleware 落實。

## 設計決策

- **D1**: 同步寄信而非 queue — 會員規模小、與登入驗證碼寄送方式一致，省 queue worker 運維（改 queue 只需 send→queue）。
- **D2**: CSV 解析放前端（預覽零往返），確認後以 rows JSON 與貼上模式共用同一匯入 endpoint（`has('rows')` 分流）。
- **D3**: 贈課 / 授權用 updateOrCreate 而非 create — purchases 有 (user_id, course_id) unique，可覆蓋 refunded 舊列而不撞鍵。
- **D4**: 會員詳情用 JSON endpoint + modal 而非獨立頁 — 保持列表選取狀態與操作上下文。
- **D5**: 匯入帳號免密碼、直接 email_verified_at=now — 平台為 email 驗證碼登入，無密碼欄位。
- **D6**: 台灣手機格式錯誤仍建帳號、電話留空 — 匯入不因次要欄位整列失敗，錯誤清單交管理員事後修正；非 09 開頭視為國際號碼原樣存。
- **D7**: Admin 頁面用 `defineOptions({ layout: AdminLayout })` 而非 template 包裹 — 避免 AppLayout + AdminLayout 同時渲染造成重複 flash toast；ImportMembersModal 以 Teleport to body 解 z-index。
- **D8**: 銷售顧問身份用獨立切換 endpoint（`.../sales-consultant`）而非併入 inline / modal 的 PATCH update — 語意獨立、便於單獨授權與日後記錄，前端亦為獨立按鈕。

## Schema

本模組不擁有任何資料表。讀寫他模組的表：

- `users` — 讀取 + 更新基本欄位；匯入時建立（role 恆 'member'）；讀寫 `is_sales_consultant` 旗標（欄位定義歸 000）
- `purchases` — 贈課寫 type='gift'、匯入授權寫 type='lead_conversion'；皆 amount=0、status='paid'
- `lesson_progress` / `assignment_completions` / `point_transactions` — 唯讀展示（進度、作業記錄、積分帳本）

## API（admin.members.*，middleware: auth + admin）

- `GET /admin/members` — 列表（search、course_id、sort、direction、per_page）
- `GET /admin/members/count` — 跨頁全選人數（與列表同一組篩選邏輯）
- `GET /admin/members/{member}` — JSON 詳情
- `PATCH /admin/members/{member}` — 更新基本資料
- `POST /admin/members/{member}/grant-points` — 派發積分入口（邏輯歸 007）
- `POST /admin/members/batch-email` — 批次寄信（throttle:10,1）
- `POST /admin/members/gift-course` — 贈課（throttle:10,1）
- `GET /admin/members/export` — CSV 匯出（scope=all|selected）
- `POST /admin/members/import` — 匯入（emails 字串或 rows 陣列，+ 可選 course_id）
- `PATCH /admin/members/{member}/sales-consultant` — 切換銷售顧問身份（admin-only；route 定義在 000 的 web.php）

## Tasks

US 9（指派銷售顧問身份）：

- [ ] T001 新增 `ToggleSalesConsultantRequest`（`is_sales_consultant` required boolean）in `app/Http/Requests/Admin/ToggleSalesConsultantRequest.php`
- [ ] T002 MemberController：`show()` 詳情 JSON 回傳 `is_sales_consultant`；新增 `updateSalesConsultant()`（isManageableMember 守門、單欄位寫入、wantsJson 回 JSON）in `app/Http/Controllers/Admin/MemberController.php`
- [ ] T003 註冊 route `PATCH /admin/members/{member}/sales-consultant`（admin 內層群組）in `routes/web.php`（000 touchpoint）
- [ ] T004 [P] 詳情 modal 加銷售顧問開關（僅 role=member 顯示）in `resources/js/Components/MemberDetailModal.vue`
- [ ] T005 [P] 列表對已指派者顯示「銷售顧問」標籤 in `resources/js/Pages/Admin/Members/Index.vue`

## 進度日誌

- 2026-07-11: [draft] 規劃 US 9 指派銷售顧問身份（詳情 modal 切換 + `.../sales-consultant` endpoint + 列表標籤）。存取控管見 000 US 6。
- 2026-07-06: 領域重組 — 自 003-member-management 重寫，依實際 codebase 校正

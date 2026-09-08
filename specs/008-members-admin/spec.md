---
id: 008-members-admin
status: done
owner_files:
  - app/Http/Controllers/Admin/MemberController.php
  - app/Http/Requests/Admin/UpdateMemberRequest.php
  - app/Http/Requests/Admin/ToggleSalesConsultantRequest.php
  - resources/js/Components/SalesConsultantModal.vue
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
    why: 贈課（type=gift）與匯入指派授權（type=lead_conversion）以 updateOrCreate 寫入 purchases；持有判斷用 paidStatus scope；授權多方案課程時寫入 course_plan_id
  - file: app/Models/CoursePlan.php
    owner: 011-high-ticket
    why: 贈課與匯入的方案下拉讀取課程方案清單（id/name/sort_order），並驗證所選方案確實屬於該課程（唯讀）
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
- [x] 選到有方案的課程時，方案下拉為**必填**（無「完整課程」選項）；未選則送出鈕停用且後端回 422（FR-010）
- [x] 贈出的 purchase 寫入所選 `course_plan_id`；無方案的課程維持 null（FR-011）
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
- [x] 指派的課程有方案時，方案下拉為**必填**（無「完整課程」選項）；未選則送出鈕停用且後端回 422（FR-010）
- [x] 授權的 purchase 寫入所選 `course_plan_id`；無方案的課程維持 null（FR-011）
- [x] 兩種匯入模式（貼上 Email / CSV rows）走同一份方案守門
- [x] 結果摘要留在 modal 內：新增 / 略過 / 無效格式（含完整清單）/ 電話格式錯誤（含清單）/ 指派授權數；關閉 modal 後刷新列表

### User Story 9 - 指派銷售顧問身份 (Priority: P2)

管理員從會員管理頁工具列的「銷售顧問」按鈕開啟專屬管理視窗，集中檢視、指派與移除顧問
（全站僅 1-2 人的規模，不在每個會員詳情放開關）；列表以標籤標示已指派者。
被指派者可協助管理 Leads 名單與折扣碼（後台存取控管見 000-platform-core US 6）。

**驗收**：
- [x] 會員管理頁工具列「銷售顧問」按鈕（僅列表既有 admin 介面）開啟管理視窗：上半部列出目前顧問（email＋姓名＋移除鈕），下半部以 email/姓名搜尋並一鍵指派
- [x] `GET /admin/members/sales-consultants?search=`（admin-only）回傳 `{consultants, results}`；results 僅含 `role=member` 且尚未指派者、上限 8 筆；會員詳情 modal 不再有任何指派 UI
- [x] `PATCH /admin/members/{member}/sales-consultant`（admin-only、`isManageableMember()` 守門）切換 `is_sales_consultant`，`wantsJson` 回 JSON，即時反映；非可管理帳號回 403 / 404
- [x] `GET /admin/members/{member}` 詳情 JSON 增回 `is_sales_consultant`
- [x] 列表對已指派者顯示「銷售顧問」標籤（badge）；指派/移除成功後 `router.reload({ only: ['members'] })` 更新列表
- [x] 切換只寫 `is_sales_consultant` 單一欄位，不動會員其他資料

## Requirements

- **FR-001**: 會員管理範圍 = `role in (member, admin)`（User::members() scope）；editor 排除。show / update / grant-points 以 isManageableMember() 守門，違反回 404 / 403。
- **FR-002**: 課程「持有」一律以 purchases.status 判斷（paidStatus）；「取得方式」標籤一律以 type 判斷。兩者不可混用。
- **FR-003**: 批次信與贈課通知同步寄送（Mail::send），不依賴 queue；Mailable 保留 Queueable trait 以便未來切 queue() 不改類別。
- **FR-004**: 批次寄信與贈課 route 皆 `throttle:10,1`。
- **FR-005**: 匯入絕不修改既有會員任何欄位（僅可能為其新增課程授權）。
- **FR-006**: 列表 index、count、匯出三處的搜尋 + 課程篩選 where 邏輯必須一致，跨頁全選人數才會與匯出結果吻合。
- **FR-007**: 派發積分僅有「增加」入口，無扣點 UI；帳本寫入與不變量由 PointService 負責（007 模組）。
- **FR-009**: 批次寄信 modal 的背景關閉 MUST 要求 **mousedown 與 click 都落在背景上**，且在主旨或內文非空時 MUST 先確認再關；ESC MUST 忽略輸入法組字中的按鍵（`isComposing` / keyCode 229）。沿用 011 FR-143 —— 那條是同一個地雷在客戶摘要 modal 上被實際踩到後訂下的：在 textarea 裡拖曳選字、滑出面板才放開時，`click` 會派送到 mousedown 與 mouseup 的共同祖先（modal 根節點），只看 `e.target === e.currentTarget` 會把選字判成點背景，整封寫好的信無聲消失
- **FR-010**: 凡後台新增課程授權的路徑（贈課、匯入指派），若目標課程 `plans()` 非空，`course_plan_id` MUST 為必填且 MUST 屬於該課程，否則回 422；課程無方案時 MUST 不接受 `course_plan_id`（傳了即 422，不靜默忽略）。前端下拉不提供「完整課程」選項。
- **FR-011**: `purchases.course_plan_id = null` 在多方案課程上的語意是**全課程開放**（`Purchase::accessibleLessonIds()` 回 null）。因此任何新增授權的路徑 MUST NOT 把「管理員沒選方案」寫成 null —— 要全開只能事後在會員詳情的改方案 UI 明示選擇。

- **FR-008**: 指派銷售顧問限 `role=member` 的可管理帳號；切換為冪等的布林寫入，不觸及會員其他欄位。身份實際授予的存取權限由 000-platform-core 的 `staff` middleware 落實。

## 設計決策

- **D1**: 同步寄信而非 queue — 會員規模小、與登入驗證碼寄送方式一致，省 queue worker 運維（改 queue 只需 send→queue）。
- **D2**: CSV 解析放前端（預覽零往返），確認後以 rows JSON 與貼上模式共用同一匯入 endpoint（`has('rows')` 分流）。
- **D3**: 贈課 / 授權用 updateOrCreate 而非 create — purchases 有 (user_id, course_id) unique，可覆蓋 refunded 舊列而不撞鍵。
- **D4**: 會員詳情用 JSON endpoint + modal 而非獨立頁 — 保持列表選取狀態與操作上下文。
- **D5**: 匯入帳號免密碼、直接 email_verified_at=now — 平台為 email 驗證碼登入，無密碼欄位。
- **D6**: 台灣手機格式錯誤仍建帳號、電話留空 — 匯入不因次要欄位整列失敗，錯誤清單交管理員事後修正；非 09 開頭視為國際號碼原樣存。
- **D7**: Admin 頁面用 `defineOptions({ layout: AdminLayout })` 而非 template 包裹 — 避免 AppLayout + AdminLayout 同時渲染造成重複 flash toast；ImportMembersModal 以 Teleport to body 解 z-index。
- **D9**: 方案下拉刻意**不給「完整課程」選項** —— 一旦課程分了方案，「全開」就不是任何一個賣得出去的東西，把它擺在贈課／匯入的預設位置，等於讓每一次漏選都免費送出最高方案（本次 bug 的原形）。要全開仍做得到，但必須走會員詳情的改方案 UI 明示指定，那裡的 null 是一個有名字的選擇而不是預設值。
- **D10**: 方案清單併進 `index()` 既有的 `courses` payload（`plans:id,course_id,name,sort_order`）而非另開 endpoint —— 課程與方案都是十位數量級，一次帶下來讓贈課與匯入兩個 modal 共用同一份資料；也免去選完課程還要 await 一次網路才看得到方案，那段空窗正是管理員會直接按送出的時候。
- **D11**: 方案守門寫成 controller 的單一 private helper 供贈課與匯入共用，而不是各自在 Form Request 裡寫規則 —— 匯入沒有 Form Request（`importEmails()` 用 inline validate 且分流兩種 payload），規則放兩處遲早只改一處。

- **D8**: 銷售顧問身份用獨立切換 endpoint（`.../sales-consultant`）而非併入 inline / modal 的 PATCH update — 語意獨立、便於單獨授權與日後記錄，前端亦為獨立按鈕。

## Schema

本模組不擁有任何資料表。讀寫他模組的表：

- `users` — 讀取 + 更新基本欄位；匯入時建立（role 恆 'member'）；讀寫 `is_sales_consultant` 旗標（欄位定義歸 000）
- `purchases` — 贈課寫 type='gift'、匯入授權寫 type='lead_conversion'；皆 amount=0、status='paid'；多方案課程另寫 `course_plan_id`（欄位定義歸 011）
- `course_plans` — 唯讀：下發方案下拉選項、驗證方案歸屬（欄位定義歸 011）
- `lesson_progress` / `assignment_completions` / `point_transactions` — 唯讀展示（進度、作業記錄、積分帳本）

## API（admin.members.*，middleware: auth + admin）

- `GET /admin/members` — 列表（search、course_id、sort、direction、per_page）
- `GET /admin/members/count` — 跨頁全選人數（與列表同一組篩選邏輯）
- `GET /admin/members/{member}` — JSON 詳情
- `PATCH /admin/members/{member}` — 更新基本資料
- `POST /admin/members/{member}/grant-points` — 派發積分入口（邏輯歸 007）
- `POST /admin/members/batch-email` — 批次寄信（throttle:10,1）
- `POST /admin/members/gift-course` — 贈課（throttle:10,1；多方案課程另帶 course_plan_id）
- `GET /admin/members/export` — CSV 匯出（scope=all|selected）
- `POST /admin/members/import` — 匯入（emails 字串或 rows 陣列，+ 可選 course_id；course_id 指向多方案課程時 course_plan_id 必填）
- `PATCH /admin/members/{member}/sales-consultant` — 切換銷售顧問身份（admin-only；route 定義在 000 的 web.php）

## Tasks

US 9（指派銷售顧問身份）：

- [x] T001 新增 `ToggleSalesConsultantRequest`（`is_sales_consultant` required boolean）in `app/Http/Requests/Admin/ToggleSalesConsultantRequest.php`
- [x] T002 MemberController：`show()` 詳情 JSON 回傳 `is_sales_consultant`；新增 `updateSalesConsultant()`（isManageableMember 守門、單欄位寫入、wantsJson 回 JSON）in `app/Http/Controllers/Admin/MemberController.php`
- [x] T003 註冊 route `PATCH /admin/members/{member}/sales-consultant`（admin 內層群組）in `routes/web.php`（000 touchpoint）
- [x] T004 [P] 詳情 modal 加銷售顧問開關（僅 role=member 顯示）in `resources/js/Components/MemberDetailModal.vue`
- [x] T005 [P] 列表對已指派者顯示「銷售顧問」標籤 in `resources/js/Pages/Admin/Members/Index.vue`

UI 改版（2026-07-12 回饋：詳情 modal 開關小題大作）：

- [x] T006 `salesConsultants()` 列表/搜尋 endpoint + route（置於 /members/{member} 之前防路由吞噬）in `app/Http/Controllers/Admin/MemberController.php`, `routes/web.php`
- [x] T007 新增 `SalesConsultantModal.vue`（現任列表＋移除、搜尋＋指派）、工具列按鈕；移除 MemberDetailModal 的開關 in `resources/js/Components/SalesConsultantModal.vue`, `resources/js/Pages/Admin/Members/Index.vue`, `resources/js/Components/MemberDetailModal.vue`
- [x] T008 endpoint 測試＋全套測試/`npm run build` 驗證 in `tests/Feature/Platform/SalesConsultantTest.php`

批次寄信 modal 關閉守門（2026-08-22，FR-009）：

- [x] T009 背景關閉改為 mousedown + click 都在背景才成立（並修好反向瑕疵 —— 背景層是獨立的 `fixed` 節點，先前點它其實關不掉）；ESC 略過 `isComposing` / keyCode 229；三個出口統一走 `requestClose()`，主旨或內文非空時先 `confirm`；寄送成功後仍直接 `emit('close')`（信已送出，再問要不要放棄沒有意義）in `resources/js/Components/BatchEmailModal.vue`
- [ ] T010 使用者實測：在內文裡拖曳選字並滑出面板放開，modal MUST 不關；中文輸入法按 Esc 取消候選字，modal MUST 不關；寫了內容後按關閉會先問

贈課與匯入的方案指派（2026-09-07，FR-010 / FR-011）：

- [x] T011 `index()` 的 `$courses` 改為 `Course::with(['plans' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')])->select('id','name','description')`，方案僅帶 `id/course_id/name/sort_order` in `app/Http/Controllers/Admin/MemberController.php`
- [x] T012 新增 private `resolveCoursePlanId(?int $courseId, mixed $planId): ?int` —— 課程有方案且 `$planId` 為空 → ValidationException（`course_plan_id` => '此課程有多個方案，請選擇要授權的方案'）；`$planId` 不屬於該課程 → 422；課程無方案卻傳了 `$planId` → 422；其餘回傳正規化後的 int 或 null in `app/Http/Controllers/Admin/MemberController.php`
- [x] T013 `grantCourse(User $user, int $courseId, ?int $planId = null)` 於 `updateOrCreate` 的 values 加入 `'course_plan_id' => $planId`；`importEmails()` 讀取 `course_plan_id`、經 T012 守門後傳入，並一併傳給 `importFromRows()`（貼上模式與 rows 模式共用同一份守門）in `app/Http/Controllers/Admin/MemberController.php`
- [x] T014 `giftCourse()` 於 `Purchase::updateOrCreate` 的 values 加入 `'course_plan_id'`，值取自 T012 守門結果；守門置於「取有效會員」之前，讓參數錯誤在寫任何一列之前就回 422 in `app/Http/Controllers/Admin/MemberController.php`
- [x] T015 `GiftCourseRequest` 加 `'course_plan_id' => ['nullable','integer','exists:course_plans,id']`（歸屬與必填由 T012 判斷，Form Request 只驗型別存在性）in `app/Http/Requests/Admin/GiftCourseRequest.php`
- [x] T016 [P] 匯入 modal：課程下拉下方，當所選課程 `plans.length > 0` 時顯示必填方案下拉（無「完整課程」選項、預設空值）；未選時送出鈕 disabled 並顯示欄位錯誤；`course_id` 改選時重置 `course_plan_id`；兩種匯入模式的送出 payload 皆帶 `course_plan_id` in `resources/js/Components/ImportMembersModal.vue`
- [x] T017 [P] 贈課 modal：同 T016 的方案下拉與送出守門；`selectedCourse` 摘要區一併顯示已選方案名稱 in `resources/js/Components/GiftCourseModal.vue`
- [x] T018 Feature 測試：多方案課程未帶方案的贈課／匯入回 422 且**不建立任何 purchase**；帶跨課程方案回 422；帶正確方案時 purchase 的 `course_plan_id` 正確；無方案課程維持 null；無方案課程傳方案回 422 in `tests/Feature/Admin/MemberCoursePlanAssignmentTest.php`
- [ ] T019 使用者實測：匯入一位新會員並指派多方案課程 → 該會員在教室只看得到所選方案的小節

## 進度日誌

- 2026-09-07: 贈課與匯入補上方案指派（T011–T018 完成，僅剩 T019 使用者實測，FR-010 / FR-011）— 守門收斂成 `resolveCoursePlanId()` 一個 private helper，贈課與兩種匯入 payload 共用，且都在**寫任何一列之前**執行：測試斷言的不只是 422，還有「purchases 為 0、users 也沒有多出一列」，因為匯入原本會邊建帳號邊授權，中途丟例外會留下一批沒有授權的孤兒帳號。錯誤訊息分兩種寫法 —— 課程有方案但選了別課的方案是「所選方案不屬於此課程」，課程根本沒方案卻傳了值是「此課程未設定方案，不可指定方案」；後者刻意不靜默忽略，靜默忽略正是這條 bug 的出廠設定。
  前端兩個 modal 各自加必填方案下拉（`plans.length > 0` 才出現、換課程即重置、未選則送出鈕 disabled），並把後端的 `course_plan_id` 錯誤排在既有 `emails` / `rows` 錯誤之前顯示，否則被擋下時畫面只會冒出泛用的「匯入失敗」。方案清單走 `index()` 既有的 `courses` payload 加掛 `with('plans:id,course_id,name,sort_order')`（`plans()` 本身已依 sort_order 排序）。`MemberCoursePlanAssignmentTest` 12 passed、`npm run build` exit 0、全站 `php artisan test` **871 passed（3630 assertions）**。

- 2026-09-07: [draft] 規劃贈課與匯入的方案指派（FR-010 / FR-011）— 匯入 modal 的「指派課程授權」從來只有課程下拉，`grantCourse()` 也沒寫過 `course_plan_id`，而 `Purchase::accessibleLessonIds()` 把 null 讀成「全課程開放」，所以匯入一批人並指派高價課，等於整批免費送出最高方案，而畫面上沒有任何跡象。同一份缺陷在贈課（US6）逐字存在 —— 兩處共用同一個 `updateOrCreate` 寫法 —— 依使用者決策一起修。
  關鍵決策是**方案必填、不給「完整課程」選項**（D9）：讓「沒選」有一個合法的落點，就是把今天這個 bug 保留成一個功能。全開仍做得到，但要走會員詳情的改方案 UI，在那裡 null 是被明示選出來的。守門收斂成 controller 的一個 private helper（D11），因為匯入沒有 Form Request 且要分流貼上／CSV 兩種 payload，規則擺兩處遲早只改一處；同理，課程無方案卻傳了 `course_plan_id` 也回 422 而不是靜默忽略 —— 靜默忽略正是這條 bug 的出廠設定。無 schema 變更、無新路由。status: draft 待審核。

- 2026-08-22: 批次寄信 modal 補上與 011 摘要 modal 同一組關閉守門（T009 / FR-009）— 使用者在客戶摘要那邊踩到之後，同一個寫法在這裡也在：`handleBackdropClick` 只看 `e.target === e.currentTarget`，於是在內文 textarea 裡拖曳選字、滑出面板才放開時，`click` 被派送到共同祖先（modal 根節點），一次選字就把整封信關掉。改成 mousedown 與 click 都要落在背景；同時修好反方向 —— 背景層是獨立的 `fixed` 節點且疊在上面，點它時 `e.target` 從來不是根節點，**背景關閉其實一直沒作用**。另加 ESC 略過輸入法組字，以及主旨／內文非空時關閉前先確認；寄送成功那條路徑維持直接關閉。`npm run build` exit 0、`php artisan test` 780 passed。

- 2026-08-03: 批次寄信的 Markdown 渲染改用 `EmailMarkdownService::toHtml()`（011 FR-021 touchpoint）— 原本裸 `new CommonMarkConverter()` 會吃掉單次換行，管理員在 modal 裡按一次 Enter 沒有效果、得按兩次。現在單次換行即 `<br>`，空一行仍是新段落；`BatchEmailMail` 其餘行為與版型不變。
- 2026-07-12: US9 指派 UI 依回饋改版 — 移除詳情 modal 開關，改為工具列「銷售顧問」按鈕開專屬管理視窗（現任列表＋移除、搜尋＋指派，salesConsultants endpoint admin-only、結果排除已指派者上限 8 筆）；全套 109 passed

- 2026-07-12: /dev 完成 US9 指派銷售顧問 — ToggleSalesConsultantRequest + updateSalesConsultant（isManageableMember 守門、單欄位寫入）、詳情 modal 開關（僅 role=member）、列表「銷售顧問」badge；測試併入 SalesConsultantTest

- 2026-07-11: [draft] 規劃 US 9 指派銷售顧問身份（詳情 modal 切換 + `.../sales-consultant` endpoint + 列表標籤）。存取控管見 000 US 6。
- 2026-07-06: 領域重組 — 自 003-member-management 重寫，依實際 codebase 校正

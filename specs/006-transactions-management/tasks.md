# Tasks: 交易紀錄管理

**Input**: Design documents from `/specs/006-transactions-management/`
**Prerequisites**: plan.md ✅ spec.md ✅ research.md ✅ data-model.md ✅ contracts/ ✅ quickstart.md ✅
**Updated**: 2026-03-11 - 新增課程進度顯示 (Phase 9)；列表「標記退款」快捷按鈕 (Phase 10)；修正退款按鈕 cursor-pointer (T026 補丁)；金額格式化 (Phase 11)

**Tests**: Not requested — no test tasks included.

**Organization**: Tasks grouped by user story. US1(P1) → US2(P2) + US5(P2) → US3(P3) → US4(P4).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to

---

## Phase 1: Setup

**Purpose**: Bug fix + routes + navigation — 所有 User Story 的前提

- [x] T001 Fix `Purchase.$fillable` — 補上 `'source'` 欄位（現有 bug）in `app/Models/Purchase.php`
- [x] T002 Add 5 transaction routes inside admin middleware group in `routes/web.php`
- [x] T003 [P] Add 「交易紀錄」nav link after 「會員管理」in admin navigation component

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Service 和 Controller 骨架 — MUST 在所有 User Story 之前完成

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T004 Create `TransactionService` with `createManual()` and `refund()` methods in `app/Services/TransactionService.php`
- [x] T005 [P] Create `StoreTransactionRequest` with validation rules in `app/Http/Requests/Admin/StoreTransactionRequest.php`
- [x] T006 Create `TransactionController` class skeleton with route model binding in `app/Http/Controllers/Admin/TransactionController.php`

**Checkpoint**: Foundation ready — User Story phases can now begin

---

## Phase 3: User Story 1 — 交易列表（搜尋/篩選/分頁）Priority: P1 🎯 MVP

**Goal**: 管理員可進入交易列表，看到分頁紀錄，並以狀態、類型、課程、關鍵字篩選

**Independent Test**: 瀏覽 `/admin/transactions`，看到分頁列表；輸入搜尋關鍵字後列表更新；下拉篩選 status/type/course 後結果正確

- [x] T007 [US1] Implement `TransactionController::index()` with filtered paginated query (search, status, type, course_id), eager load user+course, pass matchingCount prop in `app/Http/Controllers/Admin/TransactionController.php`
- [x] T008 [US1] Create `Index.vue` with data table (columns: 購買者、課程、金額、狀態、類型、購買時間) and pagination component in `resources/js/Pages/Admin/Transactions/Index.vue`
- [x] T009 [US1] Add search input and filter dropdowns (status, type, course) with Inertia `router.get` on change and `preserveState: true` in `resources/js/Pages/Admin/Transactions/Index.vue`

**Checkpoint**: US1 fully functional — list, search, filter, pagination all work independently

---

## Phase 4: User Story 2 — 單筆交易詳情 Priority: P2

**Goal**: 點擊列表中任一筆交易，進入詳情頁顯示所有欄位，並可跳至對應會員頁和課程頁

**Independent Test**: 點擊列表中一筆交易，詳情頁顯示 portaly_order_id、buyer_email、金額、折扣、狀態、type_label、webhook_received_at 等所有欄位；member/course 連結跳轉正確

- [x] T010 [US2] Implement `TransactionController::show()` with eager load `user` and `course`, shape all fields as props in `app/Http/Controllers/Admin/TransactionController.php`
- [x] T011 [US2] Create `Show.vue` displaying all transaction fields in a detail layout; member link → `admin.members.index?highlight={user.id}`（勿用 admin.members.show，該路由回傳 JsonResponse）; course link → `admin.courses.edit` in `resources/js/Pages/Admin/Transactions/Show.vue`

**Checkpoint**: US1 + US2 both independently functional

---

## Phase 5: User Story 5 — 批次勾選並匯出 CSV Priority: P2

**Goal**: 管理員可勾選交易（含跨頁全選）並下載 CSV

**Independent Test**: 勾選 3 筆後點「匯出 CSV」，下載檔名含日期，開啟後有且僅有這 3 筆的 13 個欄位；未勾選時按鈕不可用

- [x] T012 [P] [US5] Add checkbox column to data table with individual selection state and select-all-page checkbox in header in `resources/js/Pages/Admin/Transactions/Index.vue`
- [x] T013 [US5] Add selected count banner and「選取全部 N 筆符合條件的交易」option (cross-page select) following Members/Index.vue pattern in `resources/js/Pages/Admin/Transactions/Index.vue`
- [x] T014 [US5] Add「匯出 CSV」button (disabled when selectedIds empty), build export URL with `?ids[]=...` or `?select_all=true&...filters`, trigger via `window.location.href` in `resources/js/Pages/Admin/Transactions/Index.vue`
- [x] T015 [US5] Implement `TransactionController::export()` with StreamedResponse, UTF-8 BOM, `chunk(200)`, 13-column CSV output, filename `transactions-YYYYMMDD.csv` in `app/Http/Controllers/Admin/TransactionController.php`

**Checkpoint**: US1 + US2 + US5 all independently functional; CSV download verified

---

## Phase 6: User Story 3 — 手動新增交易 Priority: P3

**Goal**: 管理員可手動新增 system_assigned/gift 交易，會員立即取得課程存取權

**Independent Test**: 點「手動新增」，填入 Email、課程、類型後提交，列表出現新紀錄（amount=0, status=paid），該會員可進入教室；重複指派同課程時顯示錯誤

- [x] T016 [US3] Implement `TransactionController::store()` delegating to `TransactionService::createManual()`, handle success redirect and error redirect with flash in `app/Http/Controllers/Admin/TransactionController.php`
- [x] T017 [US3] Add manual create modal/form with user search (by email, using Inertia `router.get` suggestions or simple input), course dropdown, type radio (system_assigned/gift) to `Index.vue`; wire submit to `router.post` in `resources/js/Pages/Admin/Transactions/Index.vue`

**Checkpoint**: US1–US3 all functional; manual create persists correctly and duplicate is blocked

---

## Phase 7: User Story 4 — 退款標記 Priority: P4

**Goal**: 在詳情頁對 paid 交易執行退款標記，撤銷課程存取

**Independent Test**: 在一筆 paid 交易詳情頁點「標記退款」，確認後 status 變 refunded；該會員進入教室被拒；refunded 交易詳情頁不顯示退款按鈕

- [x] T018 [US4] Implement `TransactionController::refund()` delegating to `TransactionService::refund()`, redirect back with flash in `app/Http/Controllers/Admin/TransactionController.php`
- [x] T019 [P] [US4] Create `TransactionRefundModal.vue` confirm dialog with warning text and confirm/cancel buttons in `resources/js/Components/Admin/TransactionRefundModal.vue`
- [x] T020 [US4] Wire refund button (visible only when `status === 'paid'`) and `TransactionRefundModal` to `Show.vue`; on confirm `router.patch` to refund route in `resources/js/Pages/Admin/Transactions/Show.vue`

**Checkpoint**: All 5 User Stories functional end-to-end

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: UX 完整性與 RWD

- [x] T021 [P] Add flash success/error banner handling (using existing flash pattern) in `Index.vue` and `Show.vue`
- [x] T022 Add empty state display (「目前沒有符合條件的交易紀錄」) when filtered results are empty in `resources/js/Pages/Admin/Transactions/Index.vue`
- [x] T023 RWD review — add horizontal scroll wrapper on data table for mobile viewports in `resources/js/Pages/Admin/Transactions/Index.vue`

---

## Phase 9: 課程進度顯示 (2026-03-11 新增)

**Purpose**: 在交易列表「課程」欄位下方顯示該會員的課程學習進度

**背景**：管理員需在列表頁即可快速掌握會員學習狀況，無需點入詳情頁。

- [x] T024 [US1] 更新 `TransactionController::index()` — 新增 `course.lessons:id,course_id` eager load，批次查詢 `LessonProgress`，透過 `through()` 注入 `progress_completed` / `progress_total` 到各 paginator item in `app/Http/Controllers/Admin/TransactionController.php`
- [x] T025 [P] [US1] 更新 `Index.vue` 課程欄位 — 新增進度條（indigo `h-1.5`）與「X/Y 課」文字；`progress_total === 0` 時顯示「（無課程內容）」 in `resources/js/Pages/Admin/Transactions/Index.vue`

**Checkpoint**: 交易列表課程欄位顯示進度條與課數，批次查詢無 N+1 ✅

---

## Phase 10: 列表「標記退款」快捷按鈕 (2026-03-11 增量更新)

**Purpose**: 讓管理員直接從列表操作退款，無需進入詳情頁

**背景**：後端 `POST /admin/transactions/{transaction}/refund` 路由與 `TransactionService::refund()` 均已實作。本次只需在列表操作欄新增前端按鈕與確認邏輯。

- [x] T026 [US4] 在 `Index.vue` 操作欄（td.relative.whitespace-nowrap，line ~434）的「查看」Link 左側新增「標記退款」按鈕：僅在 `transaction.status === 'paid'` 時顯示（`v-if`）；點擊後 `window.confirm('確認將此交易標記為退款？退款後該會員的課程存取將被撤銷。')` → 確認後 `router.post(\`/admin/transactions/\${transaction.id}/refund\`, {}, { preserveScroll: true })`；按鈕樣式 `text-red-600 hover:text-red-900 mr-3 cursor-pointer` in `resources/js/Pages/Admin/Transactions/Index.vue`
    - 補加 `cursor-pointer` 確保 hover 游標與「查看」Link 一致

**Checkpoint**: 列表中 paid 交易的操作欄顯示「標記退款 | 查看」；refunded 交易只顯示「查看」；點擊確認後列表即時更新狀態

---

## Phase 11: 金額格式化 (2026-03-11 新增)

**Purpose**: 確保交易金額固定顯示兩位小數

**背景**：模板直接插值無格式化，DB 存整數時顯示 `TWD 1200` 而非 `TWD 1200.00`；前端統一用 `Number().toFixed(2)` 格式化。

- [x] T027 [P] [US1] 在 `Index.vue` 新增 `formatAmount(currency, amount)` helper，替換金額欄插值為 `{{ formatAmount(transaction.currency, transaction.amount) }}` in `resources/js/Pages/Admin/Transactions/Index.vue`
- [x] T028 [P] [US2] 在 `Show.vue` 新增 `formatAmount(currency, amount)` helper，替換 `amount` 與 `discount_amount` 插值 in `resources/js/Pages/Admin/Transactions/Show.vue`

**Checkpoint**: 列表與詳情頁金額均顯示兩位小數（如 `TWD 1200.00`、`TWD 0.00`）✅

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies — start immediately
- **Phase 2 (Foundational)**: Depends on Phase 1 — BLOCKS all user stories
- **Phase 3 (US1)**: Depends on Phase 2 — no other story dependencies
- **Phase 4 (US2)**: Depends on Phase 3 (needs list page to navigate from)
- **Phase 5 (US5)**: Depends on Phase 3 (checkboxes added to existing list)
- **Phase 6 (US3)**: Depends on Phase 3 (modal on list page)
- **Phase 7 (US4)**: Depends on Phase 4 (refund button on detail page)
- **Phase 8 (Polish)**: Depends on all desired user stories being complete

### Parallel Opportunities

- T003 (nav link) can run in parallel with T004/T005/T006
- T005 (StoreTransactionRequest) can run in parallel with T004 (TransactionService)
- Phase 4 (US2) and Phase 5 (US5) can run in parallel after Phase 3
- T012 (checkbox column) can start alongside T013/T014 on different state refs
- T019 (RefundModal component) can be built in parallel with T018 (controller action)
- T021 (flash banners) can run in parallel with T022/T023

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (T001–T003)
2. Complete Phase 2: Foundational (T004–T006)
3. Complete Phase 3: US1 (T007–T009)
4. **STOP and VALIDATE**: Visit `/admin/transactions`, verify list/search/filter
5. Already usable for viewing transaction data

### Incremental Delivery

1. Phase 1 + 2 → Foundation ready (T001–T006)
2. Phase 3 → List + search/filter functional (T007–T009) 🎯 MVP
3. Phase 4 → Detail page (T010–T011)
4. Phase 5 → CSV export (T012–T015)
5. Phase 6 → Manual create (T016–T017)
6. Phase 7 → Refund (T018–T020)
7. Phase 8 → Polish (T021–T023)

---

## Summary

| Phase | Stories | Tasks | Notes |
|-------|---------|-------|-------|
| Phase 1: Setup | — | T001–T003 | Bug fix + routes + nav |
| Phase 2: Foundational | — | T004–T006 | Service + Request + Controller skeleton |
| Phase 3: US1 P1 | 交易列表 | T007–T009 | 🎯 MVP |
| Phase 4: US2 P2 | 單筆詳情 | T010–T011 | |
| Phase 5: US5 P2 | CSV 匯出 | T012–T015 | Parallel with Phase 4 |
| Phase 6: US3 P3 | 手動新增 | T016–T017 | |
| Phase 7: US4 P4 | 退款標記 | T018–T020 | |
| Phase 8: Polish | — | T021–T023 | |
| Phase 9: 課程進度顯示 | US1 | T024–T025 | 2026-03-11 新增 |
| Phase 10: 列表退款按鈕 | US4 | T026 | 2026-03-11 增量更新 |
| Phase 11: 金額格式化 | US1, US2 | T027–T028 | 2026-03-11 新增 |
| **Total** | **5 stories** | **28 tasks** | |

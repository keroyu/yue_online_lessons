---

description: "Task list for 012-points-system implementation"
---

**Updated**: 2026-07-05 - T023–T054 全數實作完成（US2–US6 + Phase 9）；validate-referral 實作於 `/api/checkout/validate-referral`。（Phase 10）
**Updated**: 2026-07-05 - US1 兌換改兩段式確認（銷售頁確認面板顯示兌換後餘額）；修後台編輯課程 `redeem_points` 未帶回 bug。（Phase 11）
**Updated**: 2026-07-05 - 兌換成功導向改「我的課程」（`member.learning`）；確認按鈕文字方位中性化。（Phase 12）

# Tasks: 積分系統擴充（積分帳本 + 兌換課程 + 推薦回饋）

**Input**: Design documents from `/specs/012-points-system/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/api.md, quickstart.md

**Tests**: 本功能涉及積分（類貨幣）正確性，spec 的 Success Criteria 與 quickstart 明確要求數個完整性測試（並發、零負餘額、回饋計算、成熟、退款、對帳）。故針對這些不變量納入 Feature 測試任務；其餘 UI/CRUD 不強制測試。

**Organization**: 任務依 User Story 分組，每個故事可獨立實作與測試。

## Format: `[ID] [P?] [Story] Description`

- **[P]**: 可平行（不同檔案、無未完成相依）
- **[Story]**: 對應 spec User Story（US1–US6）
- 所有路徑為 repo 根目錄相對路徑

## Path Conventions

Web app（Laravel backend + Inertia/Vue，單一 repo）：`app/`、`database/`、`resources/js/`、`routes/`、`tests/`。

---

## Phase 1: Setup（Schema 基礎）

**Purpose**: 建立資料庫 schema substrate。

- [X] T001 [P] Migration 建立 `point_transactions` 表（欄位/索引依 data-model.md §1）in `database/migrations/2026_06_30_000001_create_point_transactions_table.php`
- [X] T002 [P] Migration 為 `users` 新增 `referral_code`(unique,nullable)、`referral_activated_at` in `database/migrations/2026_06_30_000002_add_referral_fields_to_users_table.php`
- [X] T003 [P] Migration 為 `courses` 新增 `redeem_points`(unsignedInteger,nullable) in `database/migrations/2026_06_30_000003_add_redeem_points_to_courses_table.php`
- [X] T004 [P] Migration 為 `orders` 新增 `referrer_user_id`(FK,nullable)、`referral_rate`、`referral_reward_points` + index in `database/migrations/2026_06_30_000004_add_referral_fields_to_orders_table.php`
- [X] T005 [P] SiteSetting seeder 寫入 4 組預設鍵（`referral_threshold_amount=3000`、`referral_reward_rate=10`、`homework_reward_points=100`、`referral_maturity_days=14`）in `database/seeders/SiteSettingSeeder.php`

---

## Phase 2: Foundational（阻斷性核心 — 所有 User Story 的前置）

**Purpose**: 帳本 model、各 model 欄位、`PointService` 核心、既有作業發點改走帳本。

**⚠️ CRITICAL**: 本階段完成前，任何 User Story 不可開始。

- [X] T006 [P] 建立 `PointTransaction` model（`$timestamps=false` + `boot()` 設 created_at、`casts()`、`scopeMatured`/`scopePending`、`user()`）in `app/Models/PointTransaction.php`
- [X] T007 [P] 擴充 `User` model：`referral_code`/`referral_activated_at` 加入 `$fillable`+`casts()`、`booted()` 自動產生 `generateReferralCode()`（8 碼排除易混字元、碰撞重試）、`pointTransactions()`、`isReferralActive()`、`pendingPoints()` in `app/Models/User.php`
- [X] T008 [P] 擴充 `Course` model：`redeem_points` 加入 `$fillable`+`casts()`、`isRedeemable` accessor in `app/Models/Course.php`
- [X] T009 [P] 擴充 `Order` model：3 個推薦欄位加入 `$fillable`+`casts()`、`referrer()` 關聯 in `app/Models/Order.php`
- [X] T010 建立 `PointService` 核心：`award()`（即時成熟動快取/延遲成熟不動快取，皆於 `DB::transaction` 寫帳本）、`syncMatured()`（單會員即時成熟到期回饋、冪等）、`redeemDeduct()`（先 `syncMatured` 再條件式 `UPDATE ... WHERE points>=cost`，0 筆 throw）、`availableBalance()`（先 `syncMatured` 再回快取，保證成熟即可用）；確立「`users.points` 唯一寫入點」原則 in `app/Services/PointService.php`（依 data-model.md Service Contracts、research R2/R3）
- [X] T011 改寫 `AssignmentService` 第 27 行 `increment('points',100)` → `PointService::award($student,$pts,'earn_homework','assignment',$assignment->id)`，`$pts` 讀 `homework_reward_points` 設定 in `app/Services/AssignmentService.php`（research R10）
- [X] T012 修正 `Member/SettingsController` 作業歷程 `points_awarded` 由寫死 100 改為讀帳本該筆 `earn_homework` 實際 `amount` in `app/Http/Controllers/Member/SettingsController.php`（research R10）
- [X] T013 Backfill migration：為既有會員產生唯一 `referral_code`，並對 `SUM(purchases.amount WHERE type='paid') >= 門檻` 者寫入 `referral_activated_at=now()` in `database/migrations/2026_06_30_000005_backfill_referral_codes.php`（research R5/R6；依賴 T007 的 generate 邏輯，於 migration 內以等價程式碼實作）

**Checkpoint**: 帳本與 `PointService` 就緒；既有作業發點已走帳本，`users.points` 語意改為「已成熟可用餘額」。

---

## Phase 3: User Story 1 - 用積分兌換課程（Priority: P1）🎯 MVP

**Goal**: 學員以已成熟積分整筆兌換可兌換課程，取得永久擁有權，不經金流。

**Independent Test**: 餘額足夠帳號兌換 → 取得擁有權、`users.points` 正確扣除、帳本新增 `redeem_course` 負筆；餘額不足/已擁有被擋；並發/重複點擊至多一次成功且不為負。

### Tests for User Story 1

- [X] T014 [P] [US1] Feature 測試：並發/重複兌換至多一次成功、餘額永不為負（SC-001）in `tests/Feature/Points/RedeemConcurrencyTest.php`
- [X] T015 [P] [US1] Feature 測試：成功兌換扣點 + 建 `source='points'` Purchase + 寫 `redeem_course` 帳本；餘額不足/已擁有/不可兌換皆被擋 in `tests/Feature/Points/RedeemCourseTest.php`

### Implementation for User Story 1

- [X] T016 [US1] `RedemptionService::redeem(User,Course)`：`DB::transaction` 內檢查可兌換+未擁有 → `PointService::redeemDeduct` → 建 Purchase（`source='points'`（string 欄，無需 migration）/`type='paid'`/`amount=0`/`status='paid'`）；**catch redeemDeduct 的餘額不足 throw、rollback 後回 `['success'=>false,'error'=>'可用積分不足']`，throw 不外洩至 controller（憲章 VII）** in `app/Services/RedemptionService.php`（data-model；research R3/R4）
- [X] T017 [P] [US1] 建立 `RedeemCourseRequest` in `app/Http/Requests/RedeemCourseRequest.php`
- [X] T018 [US1] `RedemptionController::store` + 路由 `POST /courses/{course}/redeem`(auth)，成功 redirect 教室、失敗 `withErrors` in `app/Http/Controllers/RedemptionController.php` 與 `routes/web.php`（contracts §3）
- [X] T019 [US1] 公開 `CourseController::show` 新增 props `redeemPoints`、`userAvailablePoints` in `app/Http/Controllers/CourseController.php`（contracts §3）
- [X] T020 [P] [US1] 建立 `RedeemButton.vue`（可兌換+足夠→可點；不足→disabled+「還差 N 點」；已擁有→不顯示）in `resources/js/Components/Course/RedeemButton.vue`
- [X] T021 [US1] `Course/Show.vue` 嵌入 `RedeemButton` in `resources/js/Pages/Course/Show.vue`
- [X] T022 [US1] 後台課程表單可設定 `redeem_points`：`CourseForm.vue` + `StoreCourseRequest`/`UpdateCourseRequest` 新增欄位 in `resources/js/Components/Admin/CourseForm.vue`、`app/Http/Requests/Admin/StoreCourseRequest.php`、`app/Http/Requests/Admin/UpdateCourseRequest.php`

**Checkpoint**: US1 可獨立運作——可設定兌換點數、學員可兌換、防超扣成立。

---

## Phase 4: User Story 2 - 推薦碼回饋（Priority: P1）

**Goal**: 結帳填推薦碼、付款確認後回饋實付比例積分（14 天成熟）；推薦碼驗證在建單前擋下錯誤；累計跨門檻啟用資格。

**Independent Test**: 不存在/自薦/未啟用碼結帳前被擋並顯示對應訊息；已啟用推薦人+買家付款 → 推薦人帳本新增 `earn_referral`（折後實付×比例四捨五入到十位、`available_at`=14 天後）；未付款不發；14 天內不可用；買家跨門檻啟用。

### Tests for User Story 2

- [X] T023 [P] [US2] Feature 測試：`validate-referral` 對不存在/自薦/未啟用回對應中文 `message`(422) + 連續失敗 429 節流 in `tests/Feature/Points/ReferralValidationTest.php`（contracts §1）
- [X] T024 [P] [US2] Feature 測試：付款確認才發回饋、金額四捨五入到十位、`available_at`=+14d、未付款不發、買家累計跨門檻 `referral_activated_at` 點亮（SC-004）in `tests/Feature/Points/ReferralRewardTest.php`

### Implementation for User Story 2

- [X] T025 [P] [US2] 建立 `ReferralService`：`validateAtCheckout()`（正規化/查無/自薦/未啟用）、`reward(Order)`（`round(total*rate/100/10)*10`，呼叫 `PointService::award(...,'earn_referral',available_at=now+days)`）、`evaluateActivation(User)` in `app/Services/ReferralService.php`（contracts §1/§2、research R6/R7）
- [X] T026 [US2] `PointService` 擴充：`matureDue()`（後備批次成熟結算 + `matured_synced`）in `app/Services/PointService.php`（`voidReferral` 於 Phase 9 T048 實作）
- [X] T027 [P] [US2] 建立 `ValidateReferralRequest` in `app/Http/Requests/ValidateReferralRequest.php`
- [X] T028 [US2] `ReferralController::validate` + 路由 `POST /checkout/validate-referral`，JSON 用 `message`、加 `RateLimiter` 失敗節流(429)（比照 `CouponController`）in `app/Http/Controllers/ReferralController.php`、`routes/web.php`（contracts §1）
- [X] T029 [US2] `CheckoutRequest` 新增 `referral_code`(nullable,string,max:12) rule in `app/Http/Requests/CheckoutRequest.php`
- [X] T030 [US2] `CheckoutService::createOrder` 驗證推薦碼（失敗 throw 擋建單）並於 `orders` 快照 `referrer_user_id`/`referral_rate`/`referral_reward_points` in `app/Services/CheckoutService.php`（contracts §2、FR-018/019）
- [X] T031 [US2] `CheckoutController::initiate` 接 `referral_code`、驗證失敗回 422、通過傳入 `createOrder` in `app/Http/Controllers/CheckoutController.php`
- [X] T032 [US2] `CheckoutService::fulfillOrder` 付款後呼叫 `ReferralService::reward($order)` + `evaluateActivation($buyerUser)` in `app/Services/CheckoutService.php`（FR-016/020）
- [X] T033 [P] [US2] 建立 `ReferralInput.vue`（比照 `CouponInput.vue`，獨立欄位、即時驗證提示）in `resources/js/Components/Cart/ReferralInput.vue`
- [X] T034 [US2] `Checkout/Index.vue` 嵌入 `ReferralInput` in `resources/js/Pages/Checkout/Index.vue`
- [X] T035 [US2] `points:mature` 後備批次指令 + 排程：`MaturePoints` 呼叫 `PointService::matureDue()` 批次結算，於 `routes/console.php` 加每日排程（即時正確性已由 T010 的 `syncMatured` 保證，本指令為後備）in `app/Console/Commands/MaturePoints.php`、`routes/console.php`（contracts §8）

**Checkpoint**: US2 可獨立運作——驗證、回饋發放、成熟、啟用判定齊備。

---

## Phase 5: User Story 3 - 會員積分中心（Priority: P2）

**Goal**: 會員查看可用/未成熟餘額、帳本明細、推薦碼與回饋紀錄。

**Independent Test**: 多筆帳本帳號開啟 → 可用=已成熟、未成熟另計正確；明細逐筆顯示時間/類型/增減；未啟用顯示門檻提示。

- [X] T036 [US3] `Member/PointController::index` + 路由 `GET /member/points`（available/pending/transactions 分頁/referralCode/active/thresholdAmount）in `app/Http/Controllers/Member/PointController.php`、`routes/web.php`（contracts §4）
- [X] T037 [P] [US3] 建立 `Member/Points.vue`（餘額拆分、明細分頁、推薦碼區塊與未啟用提示）in `resources/js/Pages/Member/Points.vue`

**Checkpoint**: US3 可獨立運作。

---

## Phase 6: User Story 4 - 後台積分參數設定（Priority: P2）

**Goal**: 管理員調整門檻/比例/作業獎勵/成熟天數；僅影響之後產生的積分。

**Independent Test**: 改設定後下一筆作業/回饋採新值，既有帳本不變。

- [X] T038 [US4] `Admin\SettingsController` 新增積分設定讀取/更新（4 鍵，經 `SiteSetting::set`）+ 路由 in `app/Http/Controllers/Admin/SettingsController.php`、`routes/web.php`
- [X] T039 [P] [US4] 後台積分設定表單頁/區塊（比照 `Admin/Settings/Payment.vue`）in `resources/js/Pages/Admin/Settings/Points.vue`
- [X] T040 [P] [US4] Feature 測試：改設定影響後續發放/計算、不影響歷史（SC-007）in `tests/Feature/Points/SettingsEffectTest.php`

**Checkpoint**: US4 可獨立運作。

---

## Phase 7: User Story 5 - 後台推薦成效統計（Priority: P3）

**Goal**: 管理員查看各推薦人訂單數/營收/回饋積分，可依時間區間。

**Independent Test**: 有推薦訂單時統計正確；時間區間篩選正確。

- [X] T041 [US5] `Admin\ReferralStatsController::index`：聚合 `orders WHERE referrer_user_id IS NOT NULL AND status='paid'`，依 referrer 分組 + 時間區間（7/30/60/90/all）+ 路由 `GET /admin/referrals` in `app/Http/Controllers/Admin/ReferralStatsController.php`、`routes/web.php`（contracts §6）
- [X] T042 [P] [US5] 建立 `Admin/Referrals/Index.vue`（比照折扣碼統計頁風格）in `resources/js/Pages/Admin/Referrals/Index.vue`

**Checkpoint**: US5 可獨立運作。

---

## Phase 8: User Story 6 - 後台派發積分並檢視會員帳本（Priority: P3）

**Goal**: 會員詳情積分區塊可派發積分（只增不減）並檢視該會員帳本明細。

**Independent Test**: 派發只有增加、無扣除入口；非正整數被擋；派發即時可用並出現在明細；明細逐筆呈現各類型。

### Tests for User Story 6

- [X] T043 [P] [US6] Feature 測試：派發僅接受正整數（拒 0/負/空白）、寫 `admin_grant` 帳本、即時計入可用 in `tests/Feature/Points/GrantPointsTest.php`

### Implementation for User Story 6

- [X] T044 [US6] 建立 `Admin\GrantPointsRequest`（`amount` required|integer|min:1、`note` nullable|max:255）in `app/Http/Requests/Admin/GrantPointsRequest.php`（contracts §5）
- [X] T045 [US6] `MemberController::grantPoints` → `PointService::award($member,$amount,'admin_grant','admin',null,$note)` + 路由 `POST /admin/members/{member}/grant-points` in `app/Http/Controllers/Admin/MemberController.php`、`routes/web.php`
- [X] T046 [US6] `MemberController::show` JSON 新增 `points` + `pointTransactions` in `app/Http/Controllers/Admin/MemberController.php`（contracts §5、FR-031）
- [X] T047 [US6] `MemberDetailModal.vue` 積分區塊：派發表單（僅增加）+ 帳本明細列表 in `resources/js/Components/MemberDetailModal.vue`

**Checkpoint**: US6 可獨立運作。

---

## Phase 9: Polish & Cross-Cutting（含退款互動 + 對帳）

**Purpose**: 退款連動（FR-023~025，搭配 006）、對帳守門、驗證收尾。

- [X] T048 `PointService::voidReferral(Order)`（對銷未成熟回饋、order 級冪等）in `app/Services/PointService.php`
- [X] T049 `TransactionService::refund` 擴充：含回饋訂單先檢查 14 天退款窗口（逾期回結構化錯誤）、期限內標記退款後呼叫 `voidReferral($order)`（order 級冪等）in `app/Services/TransactionService.php`（contracts §7、FR-023/024/025）
- [X] T050 [P] Feature 測試：14 天內退款作廢未成熟回饋且無負餘額、逾 14 天退款被拒（SC-006）in `tests/Feature/Points/ReferralRefundTest.php`
- [X] T051 `PointService::reconcile()` + `points:reconcile` 指令 + 每日排程 in `app/Services/PointService.php`、`app/Console/Commands/ReconcilePoints.php`、`routes/console.php`（research R2、contracts §8）
- [X] T052 [P] Feature 測試：對帳斷言每位會員 `users.points == SUM(已成熟帳本)`（SC-002）in `tests/Feature/Points/ReconcileTest.php`
- [X] T053 依 `quickstart.md` 逐切片手動驗證
- [X] T054 執行 `/updatespec` 同步 `repo_map.md`、`spec_index.json`、`code_index.json`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: 無相依，可立即開始；T001–T005 皆 [P]。
- **Foundational (Phase 2)**: 依賴 Setup 完成 — **阻斷所有 User Story**。T006–T009 [P]；T010 依賴 T006–T009；T011/T012 依賴 T010；T013 依賴 T007。
- **User Stories (Phase 3–8)**: 全部依賴 Foundational。之後可平行或依優先序進行。
- **Polish (Phase 9)**: T049 依賴 T026/T048（voidReferral）與 US2；T051/T052 依賴 T010。

### User Story Dependencies

- **US1（P1）**: 僅依賴 Foundational。MVP。
- **US2（P1）**: 僅依賴 Foundational；T026 擴充 PointService（與 US1 不衝突）。
- **US3（P2）**: 依賴 Foundational；讀取帳本（US1/US2 有資料時更完整，但可獨立測）。
- **US4（P2）**: 依賴 Foundational（SiteSetting seeds）。
- **US5（P3）**: 依賴 Foundational；資料來自 US2 的 order 快照（可獨立測，造資料即可）。
- **US6（P3）**: 依賴 Foundational。
- **退款（Phase 9）**: 依賴 US2（回饋資料）與 006 既有 refund。

### Within Each User Story

- 測試先寫且應 FAIL → Model → Service → Controller/Route → 前端 → 整合。
- 同檔案任務（如多次擴充 `PointService.php`、`routes/web.php`、`CheckoutService.php`）不可標 [P]，須依序。

### Parallel Opportunities

- Setup：T001–T005 全平行。
- Foundational：T006–T009（不同 model 檔）平行。
- US1 測試 T014/T015 平行；US2 測試 T023/T024 平行。
- 跨故事：Foundational 完成後，US1/US2/US3/US4/US5/US6 可由不同人平行進行（注意共用檔 `routes/web.php`、`CheckoutService.php`、`PointService.php`、`MemberController.php` 的合併順序）。

---

## Parallel Example: Foundational models

```bash
# Foundational 完成 Setup 後，平行建立各 model：
Task: "建立 PointTransaction model in app/Models/PointTransaction.php"        # T006
Task: "擴充 User model 推薦欄位與關聯 in app/Models/User.php"                  # T007
Task: "擴充 Course model redeem_points in app/Models/Course.php"             # T008
Task: "擴充 Order model 推薦欄位 in app/Models/Order.php"                      # T009
```

## Parallel Example: User Story 1 tests

```bash
Task: "並發兌換不超扣測試 in tests/Feature/Points/RedeemConcurrencyTest.php"   # T014
Task: "兌換成功/被擋測試 in tests/Feature/Points/RedeemCourseTest.php"         # T015
```

---

## Implementation Strategy

### MVP First（僅 US1）

1. Phase 1 Setup → 2. Phase 2 Foundational（**阻斷，必先完成**）→ 3. Phase 3 US1 → 4. **停下驗證** US1（兌換 + 防超扣）→ 5. 可上線/展示。

### Incremental Delivery

1. Setup + Foundational → 帳本就緒（既有作業發點已走帳本，零行為退化）
2. + US1（兌換課程，MVP）→ 獨立驗證 → 上線
3. + US2（推薦回饋）→ 獨立驗證 → 上線
4. + US3 / US4 → 會員積分中心 + 後台設定
5. + US5 / US6 → 統計 + 後台派發/帳本檢視
6. + Phase 9（退款連動 + 對帳）→ 完整性收尾

### Parallel Team Strategy

Foundational 完成後：開發者 A 接 US1、B 接 US2（注意 `PointService.php`/`CheckoutService.php` 合併）、C 接 US3/US4。US5/US6 與退款收尾隨後。

---

## Notes

- [P] = 不同檔案、無未完成相依；同檔案任務一律依序。
- 測試聚焦於積分完整性不變量（SC-001/002/004/005/006/007），非全面覆蓋。
- 共用檔案熱點：`PointService.php`（T010→T026→T048/T051）、`CheckoutService.php`（T030→T032）、`routes/web.php`、`routes/console.php`、`MemberController.php`——合併時注意順序。
- 每完成一個 Checkpoint 可停下獨立驗證該故事。

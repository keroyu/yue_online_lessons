# Implementation Plan: [FEATURE]

**Branch**: `[###-feature-name]` | **Date**: [DATE] | **Spec**: [link]
**Input**: Feature specification from `/specs/[###-feature-name]/spec.md`
**Updated**: 2026-03-09 - 修正贈課 Email 模板檔名；批次 Email 加入 Markdown 支援（league/commonmark）
**Updated**: 2026-03-11 - 會員詳情課程列表新增取得方式標籤
**Updated**: 2026-05-03 - 新增 US8 匯出 CSV、US9 匯入 Email 名單規格（FR-030～041）
**Updated**: 2026-05-03 - US9 補強：無效 Email 清單列出（FR-040/041）；modal 保持開啟至使用者關閉後才 reload
**Updated**: 2026-05-03 - 新增 US10 CSV 上傳匯入（FR-042～049a）；papaparse；importEmails() 擴充 rows[] 路徑

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

[Extract from feature spec: primary requirement + technical approach from research]

## Technical Context

<!--
  ACTION REQUIRED: Replace the content in this section with the technical details
  for the project. The structure here is presented in advisory capacity to guide
  the iteration process.
-->

**Language/Version**: [e.g., Python 3.11, Swift 5.9, Rust 1.75 or NEEDS CLARIFICATION]  
**Primary Dependencies**: [e.g., FastAPI, UIKit, LLVM or NEEDS CLARIFICATION]  
**Storage**: [if applicable, e.g., PostgreSQL, CoreData, files or N/A]  
**Testing**: [e.g., pytest, XCTest, cargo test or NEEDS CLARIFICATION]  
**Target Platform**: [e.g., Linux server, iOS 15+, WASM or NEEDS CLARIFICATION]
**Project Type**: [single/web/mobile - determines source structure]  
**Performance Goals**: [domain-specific, e.g., 1000 req/s, 10k lines/sec, 60 fps or NEEDS CLARIFICATION]  
**Constraints**: [domain-specific, e.g., <200ms p95, <100MB memory, offline-capable or NEEDS CLARIFICATION]  
**Scale/Scope**: [domain-specific, e.g., 10k users, 1M LOC, 50 screens or NEEDS CLARIFICATION]

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

[Gates determined based on constitution file]

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)
<!--
  ACTION REQUIRED: Replace the placeholder tree below with the concrete layout
  for this feature. Delete unused options and expand the chosen structure with
  real paths (e.g., apps/admin, packages/something). The delivered plan must
  not include Option labels.
-->

```text
# [REMOVE IF UNUSED] Option 1: Single project (DEFAULT)
src/
├── models/
├── services/
├── cli/
└── lib/

tests/
├── contract/
├── integration/
└── unit/

# [REMOVE IF UNUSED] Option 2: Web application (when "frontend" + "backend" detected)
backend/
├── src/
│   ├── models/
│   ├── services/
│   └── api/
└── tests/

frontend/
├── src/
│   ├── components/
│   ├── pages/
│   └── services/
└── tests/

# [REMOVE IF UNUSED] Option 3: Mobile + API (when "iOS/Android" detected)
api/
└── [same as backend above]

ios/ or android/
└── [platform-specific structure: feature modules, UI flows, platform tests]
```

**Structure Decision**: [Document the selected structure and reference the real
directories captured above]

## Incremental Update Summary

---

### 2026-05-03: US8 匯出 CSV + US9 匯入 Email 名單

**背景**：管理員需要批次匯出/匯入會員資料，以便與外部工具（Excel、CRM）串接或大量建立帳號。

**Constitution Check**：

| Principle | Gate | Status |
|-----------|------|--------|
| I. Controller Layering | Export = 單一 Model 查詢 + 檔案回應；Import = 單一 Model (User) 建立，無跨 Model 副作用、無外部 I/O → simple path 無需 Service | ✅ 直接在 Controller 處理 |
| III. Frontend Architecture | 新增永久按鈕 + ImportMembersModal，local state only | ✅ |
| IV. Model Conventions | 匯入時使用現有 User::create，nickname 預設為 email @ 前段 | ✅ |
| VI. Email Delivery | Import 不發送 Email（OTP 登入，不需通知）| ✅ |
| IX. Security | Export 以 admin middleware 保護；Import 僅建立 member 角色帳號 | ✅ |
| X. YAGNI | 無新套件；CSV 以 PHP 原生 fputcsv 生成 | ✅ |

**Constitution Check Result**: PASS — no violations.

**新增檔案**：
- `resources/js/Components/ImportMembersModal.vue` — 匯入 Email 名單 modal

**修改檔案**：
- `app/Http/Controllers/Admin/MemberController.php` — 新增 `exportCsv()` + `importEmails()`
- `routes/web.php` — 新增 2 條 admin 路由（export + import）
- `resources/js/Pages/Admin/Members/Index.vue` — 新增右上角永久「匯入」按鈕 + 「匯出」下拉選單

**設計決策**：

1. **Export 觸發方式**：`window.location.href` + query string（非 Inertia router），瀏覽器直接下載檔案，頁面不跳轉。
2. **Export scope=selected 的跨頁選取**：  
   - 個別勾選：傳遞 `ids[]` 陣列  
   - 全選符合條件（FR-012a）：傳 `scope=all` + 當前 filters（不傳 ids），後端重新查詢匯出，與 「匯出全部」邏輯一致  
   → 前端判斷：`selectAllMatching === true` 時，「匯出選定」等同 「匯出全部（帶 filter）」
3. **CSV 編碼**：回應前加 UTF-8 BOM（`"\xEF\xBB\xBF"`）確保 Excel 正確顯示中文。
4. **Import 無密碼**：`User::create(['email' => ..., 'nickname' => ..., 'role' => 'member', 'email_verified_at' => now()])` — 與 OTP 登入流程一致（LoginController 同模式）。
5. **Import 結果後重新整理**：`router.reload()` 讓新會員出現在列表中。

**API 端點**：

| Method | Route | Action |
|--------|-------|--------|
| GET | /admin/members/export | exportCsv — scope, ids[], search, course_id |
| POST | /admin/members/import | importEmails — emails (raw text) |

路由須加在 `{member}` wildcard 之前。

**Import 解析邏輯（Controller inline）**：
```
$raw = $request->input('emails', '')
$lines = preg_split('/[\r\n,]+/', $raw)
→ trim each line → filter empty → array_unique
→ foreach: filter_var(FILTER_VALIDATE_EMAIL) → collect into $invalidEmails[] / User exists → skipped / create → created
→ return { created_count, skipped_count, invalid_count, invalid_emails: [], message }
```

**Modal UX（補強）**：
- 匯入完成後 modal 保持開啟，顯示結果（綠色摘要 + 若有無效 Email 則顯示黃色清單）
- 使用者點「關閉」後才執行 `router.reload()`，避免列表在結果還在閱讀時閃動
- `router.reload()` 僅在 `created_count > 0` 時執行（無新增則無需 reload）

---

### 2026-05-03: US10 匯入 modal 新增 CSV 上傳模式

**背景**：管理員有時擁有含姓名、電話的完整聯絡人名單（Excel/Google Sheets），純貼上 Email 模式無法帶入其他欄位。新增 CSV 上傳模式，在同一個匯入 modal 內以 tab 切換，客戶端解析、預覽後再送後端，與既有的貼上模式共用同一 API 端點（擴充 `rows[]` 參數路徑）。

**Constitution Check**：

| Principle | Gate | Status |
|-----------|------|--------|
| I. Controller Layering | CSV 路徑同為單一 Model（User）操作，無跨 Model 副作用、無外部 I/O → simple path，直接在 Controller 處理 | ✅ |
| III. Frontend Architecture | ImportMembersModal 新增 tab UI + CSV 解析 + 預覽，全為 local state；無新 Page 或跨元件共享狀態 | ✅ |
| IV. Model Conventions | User.$fillable 已含 real_name、phone；User::create 直接使用，無需異動 Model | ✅ |
| IX. Security | CSV 由前端解析後以 JSON rows 傳送，無檔案上傳至伺服器；同一 admin middleware 保護 | ✅ |
| X. Simplicity & YAGNI | 新增 `papaparse` npm 套件：理由為 RFC 4180 解析（BOM、quoted fields、CRLF）手工實作易出錯；papaparse 為業界標準輕量套件（gzip 約 15KB），Constitution §X「implement what is needed now」 | ✅ 理由充分 |

**Constitution Check Result**: PASS — no violations.

**新增套件**：
- `papaparse` — 客戶端 CSV 解析（npm install papaparse）

**修改檔案**：
- `app/Http/Controllers/Admin/MemberController.php` — `importEmails()` 擴充 `rows[]` 輸入路徑 + 電話驗證邏輯
- `resources/js/Components/ImportMembersModal.vue` — 新增 tab UI、CSV 上傳區、Papa Parse 解析、預覽、phone_format_errors 結果顯示

**設計決策**：

1. **同一端點，雙輸入路徑**：`POST /admin/members/import` 擴充為：若請求包含 `rows[]`（JSON 陣列）→ CSV 路徑；否則沿用既有 `emails` 字串路徑。Controller 以 `$request->has('rows')` 分支處理。
2. **CSV 解析 library**：前端使用 `Papa.parse(file, { header: false, skipEmptyLines: true })` 解析 File 物件，取前 3 欄，忽略其餘欄及 header 列。
3. **電話驗證（台灣手機）**：Controller 中對每列的 phone 欄位：trim → 若以 `09` 開頭且恰好 10 碼 → 合法存入；若以 `09` 開頭但非 10 碼 → 存空字串，加入 `phone_format_errors[]`；其餘（國際或空白）→ 照原值存入（不驗證）。
4. **回應擴充**：CSV 路徑新增 `phone_format_errors: string[]`（內含電話有誤的 email 清單），貼上路徑仍回傳既有欄位（此欄不出現或為空陣列）。
5. **預覽顯示**：前端解析後顯示 3 個固定欄位標籤（Email、姓名、電話）+ 資料列數，不顯示實際資料內容（FR-045）。確認後才送後端。
6. **Modal 結果顯示**：原有的綠色摘要 + 黃色無效 email 清單保持不變；CSV 路徑額外顯示橘色電話格式有誤清單（若 phone_format_errors 非空）。

**CSV 路徑 Controller 邏輯（inline）**：
```
$rows = $request->input('rows', [])  // array of {email, real_name, phone}
→ foreach $rows:
    email = trim(row.email) → filter_var(FILTER_VALIDATE_EMAIL) → invalid / exists → skip / create
    phone = trim(row.phone):
        starts with '09' && strlen == 10 → store as-is
        starts with '09' && strlen != 10 → store '' + add email to $phoneFormatErrors[]
        else → store as-is (international)
→ User::create([email, nickname=before('@'), real_name, phone, role='member', email_verified_at=now()])
→ return { created_count, skipped_count, invalid_count, invalid_emails[], phone_format_errors[], message }
```

**API 端點異動**（同一路由，擴充 request body）：

| Method | Route | 新增參數 |
|--------|-------|---------|
| POST | /admin/members/import | 新增 `rows[]`：`[{email, real_name, phone}]`（與 `emails` 互斥） |

---

### 2026-03-11: 會員詳情課程列表新增取得方式標籤

**背景**：管理員在查看會員詳情時，需要一眼辨識每門課程的取得方式（贈送或購買），以便客服判斷處理方式。原設計只顯示課程名稱與進度，無法區分。

**修改檔案**：
- `app/Http/Controllers/Admin/MemberController.php` - `show()` 回傳的課程陣列加入 `acquisition_type` 欄位（`'gift'` | `'paid'`）
- `resources/js/Components/MemberDetailModal.vue` - 課程名稱旁新增小標籤，日期前綴文字同步調整

**設計決策**：
- 只區分兩種：`gift`（贈送，含 system_assigned）vs `paid`（購買）：管理員只需知道「是否為贈送」，不需要細分 system_assigned vs gift
- 標籤使用色彩區分：贈送 → 紫色（`bg-purple-100 text-purple-700`），購買 → 藍色（`bg-blue-100 text-blue-700`）

**影響元素**：
1. 課程卡片標題列 — 課程名稱右側加小標籤
2. 日期前綴文字 — 贈送顯示「取得於」，購買顯示「購買於」

---

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |

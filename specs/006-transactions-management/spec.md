# Feature Specification: 交易紀錄管理

**Feature Branch**: `006-transactions-management`
**Created**: 2026-03-10
**Updated**: 2026-03-11 - 交易列表「課程」欄位新增課程進度顯示（進度條 + X/Y 課）
**Status**: Draft
**Input**: User description: "在管理後台加上交易紀錄的檢視和管理功能"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - 檢視所有交易紀錄列表 (Priority: P1)

管理員進入後台的「交易紀錄」頁面，可以看到所有購買紀錄的總覽列表，包含購買者、課程、金額、狀態、來源、類型、購買時間等欄位，並能依條件篩選與搜尋。

**Why this priority**: 最核心的需求，讓管理員掌握整體交易全貌，是所有其他管理操作的入口。

**Independent Test**: 在後台導覽中點擊「交易紀錄」即可看到分頁列表，包含實際購買資料，獨立即可驗證。

**Acceptance Scenarios**:

1. **Given** 管理員已登入後台，**When** 進入交易紀錄頁面，**Then** 看到依購買時間倒序排列的交易列表，每筆顯示：購買者姓名/Email、課程名稱、實付金額、幣別、狀態（已付款/已退款）、類型（paid/system_assigned/gift）、購買時間。
2. **Given** 交易紀錄列表頁面，**When** 在搜尋欄輸入 Email 或訂單編號，**Then** 列表即時過濾只顯示符合條件的紀錄。
3. **Given** 交易紀錄列表頁面，**When** 選擇篩選條件（狀態、類型、課程），**Then** 列表更新只顯示符合條件的紀錄。
4. **Given** 有大量交易紀錄，**When** 瀏覽列表，**Then** 每頁顯示固定筆數並提供分頁導覽。
5. **Given** 交易列表中某筆交易對應的會員有課程學習進度，**When** 瀏覽列表，**Then** 該列的課程欄位下方顯示進度條與「已完成課數/總課數」（如「3/12 課」），讓管理員無需進入詳情即可掌握學習狀況。

---

### User Story 2 - 檢視單筆交易詳情 (Priority: P2)

管理員在列表中點擊某筆交易，可查看該筆交易的完整資訊，包含所有欄位詳情以及相關聯的會員資訊與課程資訊。

**Why this priority**: 列表只能顯示摘要，詳情頁提供完整資訊供客服與對帳使用。

**Independent Test**: 點擊任一交易列表中的項目，能進入詳情頁看到所有欄位，並有連結可跳至對應會員或課程頁。

**Acceptance Scenarios**:

1. **Given** 交易列表頁面，**When** 點擊某筆交易，**Then** 進入詳情頁顯示：訂單 ID、Portaly 訂單編號、購買者姓名與 Email、課程名稱、金額、折扣金額、優惠碼、幣別、狀態、來源、類型、Webhook 接收時間、建立時間。
2. **Given** 交易詳情頁，**When** 點擊購買者連結，**Then** 跳至會員管理列表頁並展開該會員詳情。
3. **Given** 交易詳情頁，**When** 點擊課程連結，**Then** 跳至該課程的管理頁面。

---

### User Story 3 - 手動新增交易（系統指派 / 贈送） (Priority: P3)

管理員可手動為某位會員指派某課程的存取權，類型為 `system_assigned` 或 `gift`，無需付款流程。

**Why this priority**: 處理客服補單、贈課等例外情況，不影響正常查閱流程可獨立實作。

**Independent Test**: 透過「新增交易」表單，填入會員 Email、課程、類型後提交，在列表中即可看到新增的紀錄且該會員取得課程存取權。

**Acceptance Scenarios**:

1. **Given** 管理員在交易列表頁，**When** 點擊「手動新增交易」，**Then** 顯示表單，包含：會員選擇（搜尋 Email）、課程選擇、類型（system_assigned / gift）。
2. **Given** 填妥手動新增表單，**When** 提交，**Then** 成功建立一筆新交易紀錄，金額為 0，狀態為 paid，該會員可存取該課程。
3. **Given** 手動新增表單，**When** 選擇的會員已擁有該課程，**Then** 顯示錯誤提示「該會員已擁有此課程」，不建立重複紀錄。

---

### User Story 4 - 將交易標記為退款 (Priority: P4)

管理員可將一筆狀態為「已付款」的交易改為「已退款」，並同步撤銷該會員對該課程的存取權。

**Why this priority**: 退款處理是重要的運營操作，但發生頻率較低，優先級低於查閱功能。

**Independent Test**: 在某筆 paid 狀態的交易詳情頁點擊「標記退款」，確認後該筆狀態變為 refunded，且對應會員無法再存取該課程。

**Acceptance Scenarios**:

1. **Given** 交易詳情頁，狀態為 paid，**When** 點擊「標記退款」並確認，**Then** 該筆交易狀態更新為 refunded，且該會員的課程存取權被撤銷。
2. **Given** 交易狀態已為 refunded，**When** 進入詳情頁，**Then** 不顯示「標記退款」按鈕（已退款無法重複操作）。
3. **Given** 管理員誤點退款按鈕，**When** 出現確認對話框，**Then** 取消後不執行任何操作。

---

### User Story 5 - 勾選交易紀錄並批次匯出 CSV (Priority: P2)

管理員在交易列表頁可勾選一筆或多筆交易，或全選當前頁面/全部符合篩選條件的紀錄，再點擊「匯出 CSV」，下載一份包含所有欄位的結構化資料檔，供對帳或分析使用。

**Why this priority**: 對帳、報表、客服處理時常需要將交易資料帶入試算表，是高頻的運營需求；但查閱功能優先，匯出為輔助工具。

**Independent Test**: 勾選 3 筆不同狀態的交易後點擊「匯出 CSV」，下載的檔案應包含且僅包含這 3 筆的所有欄位資料。

**Acceptance Scenarios**:

1. **Given** 管理員在交易列表頁，**When** 勾選個別交易的 checkbox，**Then** 被選取的列高亮顯示，並在列表頂端顯示「已選取 N 筆」。
2. **Given** 管理員在交易列表頁，**When** 點擊表頭的「全選」checkbox，**Then** 目前頁面所有交易被選取。
3. **Given** 管理員已套用篩選條件，篩選結果跨多頁，**When** 點擊「選取全部 N 筆符合條件的交易」，**Then** 所有符合篩選條件的交易（不限當前頁）皆被選取。
4. **Given** 已選取一筆或多筆交易，**When** 點擊「匯出 CSV」，**Then** 瀏覽器下載一份 CSV 檔，檔名含日期（如 `transactions-20260310.csv`），包含欄位：訂單 ID、Portaly 訂單編號、購買者姓名、購買者 Email、課程名稱、金額、折扣金額、優惠碼、幣別、狀態、來源、類型、購買時間。
5. **Given** 未勾選任何交易，**When** 試圖匯出，**Then** 「匯出 CSV」按鈕為不可點擊狀態或顯示提示「請先選取交易紀錄」。

---

### Edge Cases

- 若某會員帳號已被刪除，交易紀錄仍應保留並顯示原始 buyer_email。
- Portaly 訂單編號為空（手動建立的交易）時，詳情頁該欄位顯示「—」；CSV 中該欄位為空字串。
- 同一會員同一課程只允許一筆有效（paid）交易，嘗試重複手動新增應被攔截。
- 退款後若管理員重新手動指派，視為新的 system_assigned 紀錄（不恢復舊紀錄）。
- 選取大量交易（例如超過 5,000 筆）匯出時，系統應能正常處理，不發生逾時或資料截斷。

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: 管理員 MUST 能在後台導覽列中找到「交易紀錄」入口。
- **FR-002**: 系統 MUST 以分頁列表顯示所有交易，每頁預設 20 筆，依建立時間倒序排列。
- **FR-003**: 管理員 MUST 能依以下條件篩選交易：狀態（paid/refunded）、類型（paid/system_assigned/gift）、課程。
- **FR-004**: 管理員 MUST 能以 Email 或 Portaly 訂單編號進行關鍵字搜尋。
- **FR-005**: 系統 MUST 提供每筆交易的詳情頁，顯示所有欄位。
- **FR-006**: 管理員 MUST 能手動新增 system_assigned 或 gift 類型的交易。
- **FR-007**: 手動新增交易時，系統 MUST 驗證該會員與課程組合尚無有效 paid 紀錄。
- **FR-008**: 管理員 MUST 能將 paid 狀態的交易標記為 refunded。
- **FR-009**: 退款操作 MUST 同步撤銷該會員對對應課程的存取權。
- **FR-010**: 退款操作 MUST 提供確認步驟，防止誤操作。
- **FR-011**: 系統 MUST 僅允許已驗證的管理員存取交易管理功能，一般會員無法存取。
- **FR-012**: 交易列表 MUST 在每列提供 checkbox，允許管理員勾選個別交易。
- **FR-013**: 列表表頭 MUST 提供「全選」checkbox，一次勾選當前頁所有交易。
- **FR-014**: 套用篩選後，系統 MUST 提供「選取全部 N 筆符合條件的交易」選項，支援跨頁全選。
- **FR-015**: 列表頂端 MUST 即時顯示目前已選取的筆數。
- **FR-016**: 有選取交易時，系統 MUST 顯示可點擊的「匯出 CSV」按鈕；無選取時按鈕不可用。
- **FR-017**: 匯出的 CSV MUST 包含欄位：訂單 ID、Portaly 訂單編號、購買者姓名、購買者 Email、課程名稱、金額、折扣金額、優惠碼、幣別、狀態、來源、類型、購買時間。
- **FR-018**: 匯出檔名 MUST 包含匯出日期，格式為 `transactions-YYYYMMDD.csv`。
- **FR-019**: 交易列表 MUST 在課程欄位下方顯示該會員對應課程的學習進度（已完成課數 / 總課數），以進度條與文字呈現。

### Key Entities

- **Purchase（交易）**: 記錄一筆課程購買或指派。核心欄位：購買者、課程、金額、折扣、優惠碼、幣別、狀態（paid/refunded）、來源（source）、類型（paid/system_assigned/gift）、Portaly 訂單編號、Webhook 接收時間。
- **User（會員）**: 交易的購買者，透過 user_id 關聯；buyer_email 作為備援顯示。
- **Course（課程）**: 交易所對應的課程，透過 course_id 關聯。

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 管理員可在 3 次點擊內從後台首頁進入交易紀錄列表。
- **SC-002**: 交易列表頁在有 10,000 筆以上紀錄時，仍能在 2 秒內完成載入。
- **SC-003**: 搜尋與篩選結果在輸入完成後 1 秒內更新顯示。
- **SC-004**: 手動新增交易流程可在 2 分鐘內完成，無需技術人員協助。
- **SC-005**: 退款標記操作從開始到完成不超過 3 個步驟（點擊 → 確認 → 完成）。
- **SC-006**: 所有交易管理功能在行動裝置上可正常操作（RWD）。
- **SC-007**: 管理員可在 3 次操作內（勾選 → 點擊匯出 → 下載）完成 CSV 匯出。
- **SC-008**: 匯出 1,000 筆交易的 CSV 可在 5 秒內完成下載。

## Assumptions

- 現有 `purchases` 表已包含所有所需欄位（user_id, course_id, portaly_order_id, buyer_email, amount, currency, coupon_code, discount_amount, status, source, type, webhook_received_at）。
- 退款僅為後台標記操作，不觸發金流退款；實際金流退款由外部（Portaly）處理。
- 管理員身份驗證沿用現有後台 auth 機制，不另外設計。
- 手動新增交易不需要審核流程，管理員提交即生效。

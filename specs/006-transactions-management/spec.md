# Feature Specification: 交易紀錄管理

**Feature Branch**: `006-transactions-management`
**Created**: 2026-03-10
**Updated**: 2026-03-11 - 交易列表「課程」欄位新增課程進度顯示（進度條 + X/Y 課）；交易列表每列新增「標記退款」快捷按鈕（置於「查看」按鈕左側）；修正「標記退款」按鈕 hover cursor 與「查看」連結一致；金額欄位強制顯示兩位小數（如 TWD 1200.00）；交易列表上方新增營收圖表（柱狀 + 折線雙軸），支援時間區間篩選（過去 7 / 30 / 90 天、自訂）
**Updated**: 2026-03-11 - 營收圖表實作完成；圖表高度限制 360px；右 Y 軸整數刻度修正（`beginAtZero` + `stepSize: 1`，防止資料稀少時出現 0.1 小數刻度）
**Updated**: 2026-03-23 - 修正交易列表「標記退款」按鈕 HTTP method（POST → PATCH），解決 405 Method Not Allowed 錯誤
**Updated**: 2026-03-30 - 釐清 Purchase 的 `status` 與 `type` 語意；文件中的「有效交易」明確指 `status = paid`
**Updated**: 2026-05-07 - 增量更新：因應 009 購物車結帳流程上線，交易詳情頁新增 merchant_order_no / gateway_trade_no / payment_gateway 顯示；搜尋功能擴展支援 merchant_order_no；CSV 匯出新增 merchant_order_no 欄位；交易列表訂單 ID 欄位改為金流來源標籤（[Portaly] / [PayUni] / [NewebPay]）搭配點擊複製訂單編號
**Status**: Draft
**Input**: User description: "在管理後台加上交易紀錄的檢視和管理功能"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - 檢視所有交易紀錄列表 (Priority: P1)

管理員進入後台的「交易紀錄」頁面，可以看到所有購買紀錄的總覽列表，包含購買者、課程、金額、狀態、來源、類型、購買時間等欄位，並能依條件篩選與搜尋。

**Why this priority**: 最核心的需求，讓管理員掌握整體交易全貌，是所有其他管理操作的入口。

**Independent Test**: 在後台導覽中點擊「交易紀錄」即可看到分頁列表，包含實際購買資料，獨立即可驗證。

**Acceptance Scenarios**:

1. **Given** 管理員已登入後台，**When** 進入交易紀錄頁面，**Then** 看到依購買時間倒序排列的交易列表，每筆顯示：金流來源標籤與訂單編號（見 Scenario 6）、購買者姓名/Email、課程名稱、實付金額（格式 `幣別 金額.00`，如 `TWD 1200.00`）、狀態（已付款/已退款）、類型（paid/system_assigned/gift）、購買時間。
6. **Given** 交易列表中任一列，**When** 瀏覽該列，**Then** 訂單欄位顯示一個圓角小標籤（badge）標示金流來源（如 `[Portaly]`、`[PayUni]`、`[NewebPay]`），標籤下方顯示對應訂單編號的前段縮略（完整編號以 tooltip 顯示）；**When** 管理員點擊該標籤或編號，**Then** 完整訂單編號被複製到剪貼簿，並短暫顯示「已複製」提示。
7. **Given** 交易列表，**When** 某筆交易來自 Portaly，**Then** 顯示 `[Portaly]` 標籤，複製 portaly_order_id；**When** 來自 PayUni 購物車，**Then** 顯示 `[PayUni]` 標籤，複製 merchant_order_no；**When** 來自 NewebPay，**Then** 顯示 `[NewebPay]` 標籤，複製 merchant_order_no；**When** 為手動指派（無訂單編號），**Then** 訂單欄位顯示「—」，無標籤與複製功能。
2. **Given** 交易紀錄列表頁面，**When** 在搜尋欄輸入 Email、Portaly 訂單編號，或購物車結帳產生的商店訂單編號（merchant_order_no），**Then** 列表即時過濾只顯示符合條件的紀錄。
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
4. **Given** 某筆交易是透過購物車結帳產生（source = payuni 或 newebpay），**When** 進入詳情頁，**Then** 顯示「購物車訂單資訊」區塊，包含商店訂單編號、金流交易序號、金流渠道；Portaly 訂單編號欄位顯示「—」。
5. **Given** 某筆交易是透過 Portaly Webhook 或手動指派產生（source = portaly 或 manual），**When** 進入詳情頁，**Then** 正常顯示 Portaly 訂單編號（manual 顯示「—」）；不顯示「購物車訂單資訊」區塊。

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

**Independent Test**: 在交易列表中找到一筆 paid 狀態的交易，直接點擊列表操作欄的「標記退款」按鈕（位於「查看」左側），確認後該筆狀態變為 refunded，且對應會員無法再存取該課程，無需進入詳情頁。

**Acceptance Scenarios**:

1. **Given** 交易列表頁，某列交易狀態為 paid，**When** 瀏覽該列，**Then** 操作欄顯示「標記退款」按鈕位於「查看」按鈕左側。
2. **Given** 交易列表頁，**When** 點擊某列的「標記退款」並在確認對話框確認，**Then** 該筆交易狀態即時更新為 refunded，該列的「標記退款」按鈕消失，且對應會員的課程存取權被撤銷。
3. **Given** 交易狀態已為 refunded（或非 paid），**When** 瀏覽該列，**Then** 不顯示「標記退款」按鈕（已退款無法重複操作）。
4. **Given** 管理員誤點退款按鈕，**When** 出現確認對話框，**Then** 取消後不執行任何操作，列表保持不變。
5. **Given** 交易詳情頁，狀態為 paid，**When** 點擊「標記退款」並確認，**Then** 該筆交易狀態更新為 refunded，且該會員的課程存取權被撤銷（詳情頁保留此操作入口）。

---

### User Story 5 - 勾選交易紀錄並批次匯出 CSV (Priority: P2)

管理員在交易列表頁可勾選一筆或多筆交易，或全選當前頁面/全部符合篩選條件的紀錄，再點擊「匯出 CSV」，下載一份包含所有欄位的結構化資料檔，供對帳或分析使用。

**Why this priority**: 對帳、報表、客服處理時常需要將交易資料帶入試算表，是高頻的運營需求；但查閱功能優先，匯出為輔助工具。

**Independent Test**: 勾選 3 筆不同狀態的交易後點擊「匯出 CSV」，下載的檔案應包含且僅包含這 3 筆的所有欄位資料。

**Acceptance Scenarios**:

1. **Given** 管理員在交易列表頁，**When** 勾選個別交易的 checkbox，**Then** 被選取的列高亮顯示，並在列表頂端顯示「已選取 N 筆」。
2. **Given** 管理員在交易列表頁，**When** 點擊表頭的「全選」checkbox，**Then** 目前頁面所有交易被選取。
3. **Given** 管理員已套用篩選條件，篩選結果跨多頁，**When** 點擊「選取全部 N 筆符合條件的交易」，**Then** 所有符合篩選條件的交易（不限當前頁）皆被選取。
4. **Given** 已選取一筆或多筆交易，**When** 點擊「匯出 CSV」，**Then** 瀏覽器下載一份 CSV 檔，檔名含日期（如 `transactions-20260310.csv`），包含欄位：訂單 ID、商店訂單編號（merchant_order_no）、Portaly 訂單編號、購買者姓名、購買者 Email、課程名稱、金額、折扣金額、優惠碼、幣別、狀態、來源、類型、購買時間。來自 Portaly 的交易，商店訂單編號欄位為空字串；來自購物車結帳的交易，Portaly 訂單編號欄位為空字串。
5. **Given** 未勾選任何交易，**When** 試圖匯出，**Then** 「匯出 CSV」按鈕為不可點擊狀態或顯示提示「請先選取交易紀錄」。

---

### User Story 6 - 檢視營收圖表與區間統計 (Priority: P2)

管理員在交易列表頁上方看到一個「營收圖表」區塊，可選擇預設時間區間（過去 7 / 30 / 90 天）或自訂起訖日，圖表以柱狀圖呈現每日銷售額、折線圖呈現每日銷售量（雙 Y 軸），並在圖表上方顯示區間總銷售量與總銷售額統計卡片。

**Why this priority**: 讓管理員快速掌握收入趨勢，無需匯出 CSV 或手動統計，提升日常運營決策效率。

**Independent Test**: 進入交易列表頁，切換時間區間，確認圖表與統計卡片數字隨之更新，且與手動加總該時段 paid 交易金額一致。

**Acceptance Scenarios**:

1. **Given** 管理員進入交易列表頁，**When** 頁面載入，**Then** 在交易列表上方看到「營收圖表」區塊，預設顯示「過去 30 天」的資料。
2. **Given** 營收圖表區塊，**When** 點擊右上角篩選器選擇「過去 7 天」、「過去 30 天」或「過去 90 天」，**Then** 圖表與統計卡片立即更新為對應時間區間的資料。
3. **Given** 篩選器下拉選單，**When** 選擇「自訂」，**Then** 顯示起訖日期選擇器，管理員可輸入任意日期區間，提交後圖表更新。
4. **Given** 已選定時間區間，**When** 檢視圖表上方的統計卡片，**Then** 顯示「區間總銷售量」（筆數）與「區間總銷售額」（含幣別符號與千分位格式，如 $20,330），數字僅計算狀態為 paid 的交易。
5. **Given** 已選定時間區間，**When** 檢視圖表，**Then** 以 X 軸為日期、左側 Y 軸為銷售額（綠色柱狀）、右側 Y 軸為銷售量（折線）呈現每日數據，圖表底部顯示圖例。
6. **Given** 某天無任何 paid 交易，**When** 該日期出現在圖表，**Then** 柱狀高度為 0、折線該點為 0，不中斷或省略該日期。

---

### Edge Cases

- 若某會員帳號已被刪除，交易紀錄仍應保留並顯示原始 buyer_email。
- Portaly 訂單編號為空（手動建立的交易或購物車結帳交易）時，詳情頁該欄位顯示「—」；CSV 中該欄位為空字串。
- 購物車結帳產生的交易（source = payuni / newebpay），merchant_order_no 與 gateway_trade_no 儲存於關聯的訂單（Order）記錄；若關聯 Order 不存在，詳情頁顯示「—」，CSV 欄位為空字串。
- 手動指派交易（type = system_assigned / gift）沒有訂單編號，列表訂單欄位顯示「—」，不顯示金流來源標籤，不提供複製功能。
- 在不支援 Clipboard API 的瀏覽器環境中，複製功能靜默失敗，不顯示錯誤提示（降級處理）。
- 同一會員同一課程只允許一筆有效交易（定義：`status = paid`），嘗試重複手動新增應被攔截。
- 退款後若管理員重新手動指派，視為新的 system_assigned 紀錄（不恢復舊紀錄）。
- 選取大量交易（例如超過 5,000 筆）匯出時，系統應能正常處理，不發生逾時或資料截斷。
- 當某時間區間內所有日期均無 paid 交易（count 全為 0）時，右側 Y 軸刻度 MUST 顯示整數（0, 1, 2...），不得出現 0.1、0.2 等小數。

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: 管理員 MUST 能在後台導覽列中找到「交易紀錄」入口。
- **FR-002**: 系統 MUST 以分頁列表顯示所有交易，每頁預設 20 筆，依建立時間倒序排列。
- **FR-003**: 管理員 MUST 能依以下條件篩選交易：狀態（paid/refunded）、類型（paid/system_assigned/gift）、課程。
- **FR-004**: 管理員 MUST 能以 Email、Portaly 訂單編號、或商店訂單編號（merchant_order_no）進行關鍵字搜尋。
- **FR-005**: 系統 MUST 提供每筆交易的詳情頁，顯示所有欄位。
- **FR-006**: 管理員 MUST 能手動新增 system_assigned 或 gift 類型的交易。
- **FR-007**: 手動新增交易時，系統 MUST 驗證該會員與課程組合尚無有效 paid 紀錄。
- **FR-008**: 管理員 MUST 能將 paid 狀態的交易標記為 refunded，且操作入口 MUST 同時出現在：(a) 交易列表每列操作欄（「查看」按鈕左側），(b) 交易詳情頁。
- **FR-009**: 退款操作 MUST 同步撤銷該會員對對應課程的存取權。
- **FR-010**: 退款操作 MUST 提供確認步驟，防止誤操作。
- **FR-011**: 系統 MUST 僅允許已驗證的管理員存取交易管理功能，一般會員無法存取。
- **FR-012**: 交易列表 MUST 在每列提供 checkbox，允許管理員勾選個別交易。
- **FR-013**: 列表表頭 MUST 提供「全選」checkbox，一次勾選當前頁所有交易。
- **FR-014**: 套用篩選後，系統 MUST 提供「選取全部 N 筆符合條件的交易」選項，支援跨頁全選。
- **FR-015**: 列表頂端 MUST 即時顯示目前已選取的筆數。
- **FR-016**: 有選取交易時，系統 MUST 顯示可點擊的「匯出 CSV」按鈕；無選取時按鈕不可用。
- **FR-017**: 匯出的 CSV MUST 包含欄位：訂單 ID、商店訂單編號（merchant_order_no）、Portaly 訂單編號、購買者姓名、購買者 Email、課程名稱、金額、折扣金額、優惠碼、幣別、狀態、來源、類型、購買時間。
- **FR-018**: 匯出檔名 MUST 包含匯出日期，格式為 `transactions-YYYYMMDD.csv`。
- **FR-019**: 交易列表 MUST 在課程欄位下方顯示該會員對應課程的學習進度（已完成課數 / 總課數），以進度條與文字呈現。
- **FR-020**: 交易列表頁 MUST 在列表上方顯示「營收圖表」區塊，包含統計卡片與雙軸圖表。
- **FR-021**: 圖表 MUST 顯示「區間總銷售量」（paid 交易筆數）與「區間總銷售額」（paid 交易金額加總）兩個統計卡片。
- **FR-022**: 圖表 MUST 以每日為單位，用柱狀圖呈現當日 paid 銷售額（左側 Y 軸），用折線圖呈現當日 paid 銷售量（右側 Y 軸）。
- **FR-023**: 右上角 MUST 提供時間區間篩選器，選項包含：過去 7 天、過去 30 天（預設）、過去 90 天、自訂。
- **FR-024**: 選擇「自訂」區間時，MUST 顯示起訖日期輸入欄位（格式 MM/DD/YYYY），管理員可手動輸入或使用日期選擇器。
- **FR-025**: 切換時間區間後，統計卡片與圖表 MUST 透過非同步請求更新，不需要手動重新整理整頁。
- **FR-026**: 圖表 MUST 在底部顯示圖例，標示柱狀（當日銷售額）與折線（當日銷售量）各自代表的意義。
- **FR-027**: 銷售額統計卡片數字 MUST 以千分位格式呈現，前加貨幣符號（如 $20,330）；銷售量以純數字呈現。
- **FR-028**: 營收圖表高度 MUST 固定為 360px，不隨視窗高度縮放。右 Y 軸（銷售量）刻度 MUST 為整數（`precision: 0`、`stepSize: 1`、`beginAtZero: true`），在資料量極少時亦不顯示小數刻度。
- **FR-029**: 文件與實作 MUST 將 Purchase 的 `status`（paid/refunded）與 `type`（paid/system_assigned/gift）視為兩個獨立維度；權限與營收統計依 `status` 判斷，取得方式與 UI 標籤依 `type` 判斷。
- **FR-030**: 交易詳情頁 MUST 支援來自購物車結帳的交易（source = payuni 或 newebpay）：有關聯 Order 時顯示「購物車訂單資訊」區塊，包含商店訂單編號（merchant_order_no）、金流交易序號（gateway_trade_no）、金流渠道（payment_gateway）；無關聯 Order（或非購物車來源）時不顯示此區塊。
- **FR-031**: 系統 MUST 在關鍵字搜尋時，同時比對 buyer_email、portaly_order_id、以及關聯 Order 的 merchant_order_no，三者任一符合即納入結果。
- **FR-032**: 匯出 CSV 時，MUST 為每筆交易填入 merchant_order_no（來自關聯 Order 記錄），無關聯 Order 時該欄位為空字串。
- **FR-033**: 交易列表的訂單欄位 MUST 以圓角標籤（badge）顯示金流來源（Portaly / PayUni / NewebPay），替代原本的純文字訂單 ID 顯示。手動指派交易（無訂單編號）顯示「—」，不顯示標籤。
- **FR-034**: 管理員 MUST 能點擊列表中的訂單標籤或編號，將完整訂單編號複製到剪貼簿；複製成功後 MUST 顯示短暫的「已複製」視覺回饋（不需要頁面跳轉或彈窗）。

### Key Entities

- **Purchase（交易）**: 記錄一筆課程購買或指派。核心欄位：購買者、課程、金額、折扣、優惠碼、幣別、狀態（status：paid/refunded）、來源（source：portaly/payuni/newebpay/manual）、類型（type：paid/system_assigned/gift）、Portaly 訂單編號（Portaly 來源才有值）、關聯 Order ID（購物車結帳才有值）、Webhook 接收時間。`status` 表示交易是否仍有效，`type` 表示取得方式。
- **Order（訂單）**: 購物車結帳流程產生的訂單，與 Purchase 為一對一關係。關鍵欄位：商店訂單編號（merchant_order_no）、金流交易序號（gateway_trade_no）、金流渠道（payment_gateway：payuni/newebpay）。Portaly 購買或手動指派的交易不關聯 Order。
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
- **SC-009**: 切換時間區間後，圖表與統計數字在 1 秒內完成更新（從用戶點擊至畫面渲染完成，以本機開發環境為基準）。
- **SC-010**: 管理員可在 2 次操作內（開啟頁面 → 選擇區間）看到目標時段的營收趨勢圖。

## Assumptions

- 現有 `purchases` 表包含核心欄位（user_id, course_id, portaly_order_id, buyer_email, amount, currency, coupon_code, discount_amount, status, source, type, webhook_received_at, order_id）。
- 009 購物車結帳流程新增 `orders` 表，透過 `purchases.order_id` 關聯；merchant_order_no、gateway_trade_no、payment_gateway 儲存於 `orders` 表。
- Portaly Webhook 產生的 Purchase：portaly_order_id 有值，order_id 為 null。
- 購物車結帳產生的 Purchase：order_id 有值，portaly_order_id 為 null，source 為 payuni 或 newebpay。
- 退款僅為後台標記操作，不觸發金流退款；實際金流退款由外部（Portaly 或金流廠商）處理。
- 管理員身份驗證沿用現有後台 auth 機制，不另外設計。
- 手動新增交易不需要審核流程，管理員提交即生效。
- 營收圖表僅計算狀態為 paid 的交易，refunded 交易不計入銷售額與銷售量。
- 圖表日期以台灣時區（UTC+8）為準。
- 自訂日期區間無上限限制，但超長區間（如超過 1 年）資料點可能較密集，不做額外處理。
- Purchase 查詢遵循明確語意：`status=paid` 代表有效交易；`type=paid` 僅代表一般購買，不等同於所有可存取交易。

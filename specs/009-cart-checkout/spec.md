# Feature Specification: 購物車結帳系統

**Feature Branch**: `009-cart-checkout`
**Created**: 2026-05-05
**Status**: Draft

## Clarifications

### Session 2026-05-05

- Q: 多門課程結帳的 PayUni 訂單模型為何？→ A: 合併成一筆 PayUni 訂單（Option B）；結帳介面加總金額後送出單一 PayUni 請求，webhook 回傳時依預存的訂單快照建立各課程的 `purchases` 記錄。
- Q: CartItem 是否儲存加入時的定價快照？→ A: 否（Option B）；結帳時永遠使用課程當前定價，購物車不承諾保留特價。
- Q: PayUni 付款失敗／取消後的 redirect 行為？→ A: 返回購物車頁，顯示「付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com」。
- Q: 購物車授權邊界？→ A: server 端以 session 登入用戶 ID 強制驗證所有權，不接受 client 傳入 user_id 參數。
- Q: 未登入時是否可加入購物車／直接購買？付款成功後 redirect 至何處？→ A: 未登入可加入購物車或點「直接購買」（guest cart 暫存於 client-side）；登入後顯示 server-side 購物車並合併 guest cart。付款成功後導回「我的課程」頁；「我的課程」需登入，若未登入則提示登入。
- Q: 後台填寫 portaly_product_id 時，金流選擇器行為？→ A: 填寫 portaly_product_id 時金流選擇器自動隱藏並清空值；清空 portaly_product_id 時金流選擇器重新出現，預設恢復 PayUni。

### Session 2026-05-05（增量更新）

- 決定：藍新金流（NewebPay）升級為本版本實作，不再暫緩。包含完整 AES-256-CBC 加密、MPG 表單送出、NotifyURL webhook（回應 `SUCCESS`）、ReturnURL 跳轉、付款成功建立 Purchase records、付款失敗導回購物車。

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — 加入購物車 / 直接購買（PayUni 課程）(Priority: P1)

訪客或登入用戶在 PayUni 付費課程銷售頁可點擊「加入購物車」（保留在當頁）或「直接購買」（立即跳轉結帳頁），兩個動作均可在未登入狀態下執行。

**Why this priority**: 這是整個新流程的入口。guest cart 降低了加入購物車的摩擦，讓用戶可以先蒐集課程再一次登入結帳。同時在此步驟觸發 Meta Pixel `AddToCart` 事件。

**Independent Test**: 未登入狀態下在 PayUni 課程頁點擊「加入購物車」，確認 badge 數量增加（client-side）、`AddToCart` 觸發；登入後確認課程仍在購物車中。

**Acceptance Scenarios**:

1. **Given** 已登入用戶在 PayUni 付費課程頁，**When** 點擊「加入購物車」，**Then** 課程加入 server-side 購物車，頁面顯示「已加入購物車」提示，badge +1，Meta Pixel 觸發 `AddToCart`。
2. **Given** 未登入用戶點擊「加入購物車」，**When** 操作，**Then** 課程暫存至 client-side guest cart，badge +1，Meta Pixel 觸發 `AddToCart`；不強制跳轉登入頁。
3. **Given** 未登入用戶點擊「直接購買」，**When** 操作，**Then** 課程加入 guest cart 並立即跳轉結帳頁；結帳頁要求登入後繼續。
4. **Given** 未登入用戶登入後，**When** 登入成功，**Then** guest cart 中的課程自動合併至 server-side 購物車（已在購物車或已購買者略過）。
5. **Given** 已登入用戶點擊「直接購買」，**When** 操作，**Then** 課程加入 server-side 購物車並立即跳轉結帳頁。
6. **Given** 已登入用戶已擁有該課程（已購買），**When** 在課程頁，**Then** 顯示「進入課程」，不顯示「加入購物車」或「直接購買」。
7. **Given** 課程已在購物車中（已登入），**When** 再次點擊「加入購物車」，**Then** 不重複加入，按鈕改為「前往購物車」。

---

### User Story 2 — Portaly 課程保留直購流程 (Priority: P1)

Portaly 課程（有 `portaly_product_id` 的課程）不支援購物車，維持現有「立即購買」直連 Portaly 頁面的行為。

**Why this priority**: Portaly 的金流由外部 webhook 控制，無法在平台端攔截結帳流程，必須保留原有直購模式，避免破壞現有訂單流程。

**Independent Test**: 在 Portaly 課程頁確認按鈕顯示「立即購買」（而非「加入購物車」），點擊後直連外部 Portaly 頁面。

**Acceptance Scenarios**:

1. **Given** 任何用戶在 Portaly 課程銷售頁，**When** 查看購買按鈕，**Then** 顯示「立即購買」（外部連結），不顯示「加入購物車」。
2. **Given** 用戶點擊 Portaly 課程的「立即購買」，**When** 點擊，**Then** 開啟外部 Portaly 結帳頁，行為與現在相同。

---

### User Story 3 — 查看購物車並結帳（InitiateCheckout）(Priority: P1)

已登入用戶進入購物車頁面，可檢視所有待購課程、移除課程，並點擊「前往結帳」觸發 PayUni 金流。

**Why this priority**: 購物車頁面是 Meta Pixel `InitiateCheckout` 的觸發點，也是用戶從意圖到付款的最後一哩。

**Independent Test**: 購物車中有至少一門 PayUni 課程，進入購物車頁確認顯示課程資訊與金額，點擊「前往結帳」後跳轉 PayUni 付款頁，`InitiateCheckout` 觸發即可驗證。

**Acceptance Scenarios**:

1. **Given** 購物車中有一或多門課程，**When** 用戶進入購物車頁，**Then** 顯示每門課程名稱、價格、封面圖，以及訂單總金額，Meta Pixel 觸發 `InitiateCheckout`。
2. **Given** 購物車頁面，**When** 用戶點擊某課程的「移除」，**Then** 該課程從購物車中移除，總金額即時更新。
3. **Given** 購物車中有課程，**When** 用戶點擊「前往結帳」，**Then** 系統建立 PayUni 訂單並跳轉至 PayUni 付款頁面。
4. **Given** 購物車為空，**When** 用戶進入購物車頁，**Then** 顯示「購物車目前是空的」，並提供課程列表連結。
5. **Given** 購物車頁面，**When** 用戶未登入直接訪問，**Then** 跳轉登入頁，登入後返回購物車。

---

### User Story 4 — 購買成功觸發 Purchase 事件 (Priority: P2)

PayUni 付款成功回傳後，Meta Pixel 觸發 `Purchase` 事件（含金額與 course_id），購物車自動清空已購買課程。

**Why this priority**: `Purchase` 是轉換追蹤最重要的事件，但依賴 US3 結帳流程完成後才觸發，屬 P2。

**Independent Test**: 完成一筆 PayUni 測試付款後，確認 PayUni 回傳頁有觸發 `Purchase` 事件，購物車不再顯示已購課程。

**Acceptance Scenarios**:

1. **Given** PayUni 付款成功，**When** 跳轉回平台，**Then** Meta Pixel 觸發 `Purchase`（含 `value` 與 `currency: TWD`），已購課程從購物車移除，並重導向至「我的課程」頁面。
2. **Given** 付款失敗或取消，**When** 返回平台，**Then** 重導向至購物車頁，顯示「付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com」，購物車內容保持原樣，不觸發 `Purchase`。

---

### User Story 5 — 購物車狀態持久化（跨 session）(Priority: P2)

用戶關閉瀏覽器後重新登入，購物車內容仍保留（server-side 儲存）。

**Why this priority**: 提升轉換率，讓用戶在考慮期間重返時不需重新加入課程。

**Independent Test**: 加入課程至購物車後登出再登入，確認課程仍在購物車中。

**Acceptance Scenarios**:

1. **Given** 用戶將課程加入購物車後登出，**When** 重新登入，**Then** 購物車中仍顯示該課程。
2. **Given** 課程被下架或刪除，**When** 用戶進入購物車，**Then** 該課程顯示「課程已下架」標記並自動移除。

---

### User Story 6 — 後台設定課程金流方式 (Priority: P2)

管理員在後台建立或編輯課程時，可為非 Portaly 課程指定金流提供商（PayUni 或藍新金流，兩者本版本均已實作）。

**Why this priority**: 管理員選擇金流後，用戶的結帳流程才能正確路由至對應的付款頁面。

**Independent Test**: 在後台編輯一門非 Portaly 課程，選擇藍新金流後儲存，前台結帳確認跳轉至藍新付款頁而非 PayUni。

**Acceptance Scenarios**:

1. **Given** 管理員編輯非 Portaly 付費課程，**When** 查看金流設定欄位，**Then** 顯示金流選擇器，選項為 PayUni（預設）和藍新金流，兩者皆可選。
2. **Given** 課程未設定 `portaly_product_id`，**When** 系統讀取金流方式，**Then** 使用課程的金流設定；若未設定則預設為 PayUni。
3. **Given** 管理員儲存課程並選擇藍新金流，**When** 用戶加入此課程至購物車並結帳，**Then** 結帳頁跳轉至藍新金流付款頁面。
4. **Given** 管理員填寫 `portaly_product_id`，**When** 查看金流選擇器，**Then** 選擇器自動隱藏（Portaly 課程不適用）。

---

### User Story 7 — 藍新金流結帳流程 (Priority: P2)

用戶購物車中包含指定藍新金流的課程，點擊「前往付款」後跳轉至藍新金流 MPG 付款頁，付款成功後開通課程；失敗則返回購物車。

**Why this priority**: 與 US3 PayUni 結帳地位相同，是完整支援藍新金流課程購買的核心流程。

**Independent Test**: 購物車中放入一門藍新課程，點「前往付款」，確認跳轉至藍新 MPG 頁面，完成測試付款後課程開通，`Purchase` 記錄建立。

**Acceptance Scenarios**:

1. **Given** 購物車中有藍新金流課程，**When** 用戶點擊「前往付款」，**Then** 系統建立 Order 快照，跳轉至藍新 MPG 付款頁面。
2. **Given** 藍新付款成功，**When** 藍新背景通知送達（NotifyURL），**Then** 系統驗證簽章，依 Order 快照建立 Purchase records，清空購物車已購課程，Order 狀態改為 paid。
3. **Given** 藍新付款成功，**When** 用戶瀏覽器跳轉回平台（ReturnURL），**Then** Meta Pixel 觸發 `Purchase`，重導向至「我的課程」頁面。
4. **Given** 藍新付款失敗或取消，**When** 返回平台，**Then** 重導向至購物車頁，顯示「付款未完成，請再試一次；若仍遇到問題請聯絡客服 themustbig+learn@gmail.com」，購物車內容保持原樣。

---

### Edge Cases

- Portaly 課程與 PayUni 課程可同時存在，銷售頁需正確識別並顯示對應按鈕。
- 若 PayUni 課程在結帳過程中被下架，結帳前驗證課程狀態，下架課程從購物車移除並提示用戶。
- PayUni 及藍新金流付款失敗或取消後，均重導向購物車頁，顯示失敗提示與客服 email（themustbig+learn@gmail.com）。
- 購物車中若混有 PayUni 課程與藍新課程，結帳時需所有課程使用同一金流提供商；若有混用，系統提示用戶分開結帳。
- 免費課程維持「免費領取」直接流程，不需購物車，不顯示「加入購物車」。
- High-ticket 課程維持「預約諮詢」表單流程，不涉及購物車。
- 用戶同時在多個分頁操作購物車：以 server-side 狀態為準，不會產生重複項目。
- 未登入時 guest cart 的課程與登入後 server-side 購物車合併時，若同一課程已在 server cart 或已購買，略過不報錯。
- 結帳頁需登入；未登入用戶進入結帳頁，顯示登入提示，登入後繼續結帳流程（不清空購物車）。

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: 系統 MUST 以 `portaly_product_id` 是否為空作為第一層判斷：有值者為 Portaly 課程（直購）；無值的付費課程走平台金流購物車流程，使用該課程設定的金流提供商（目前預設並唯一支援 PayUni）。
- **FR-002**: PayUni 付費課程銷售頁 MUST 同時顯示「加入購物車」與「直接購買」兩個按鈕。「直接購買」等同於加入購物車後立即跳轉結帳頁。
- **FR-003**: Portaly 課程銷售頁 MUST 維持「立即購買」外部連結，行為不變。
- **FR-004**: 免費課程銷售頁 MUST 維持「免費領取」直接領取流程，行為不變。
- **FR-005**: High-ticket 課程銷售頁 MUST 維持「預約諮詢」表單流程，行為不變。
- **FR-006**: 系統 MUST 支援 guest cart（未登入時 client-side 暫存）與 server-side 購物車（登入後帳號綁定）兩種模式；用戶登入時自動合併 guest cart 至 server-side 購物車，已存在或已購買的課程略過。
- **FR-006a**: Server-side 購物車 MUST 以 session 中的登入用戶 ID 作為唯一識別，server 端強制驗證所有權，不接受 client 傳入的 user_id 參數。
- **FR-007**: 同一課程 MUST NOT 重複加入購物車（server-side 或 guest cart）；已登入用戶已購買的課程 MUST NOT 顯示「加入購物車」或「直接購買」。
- **FR-008**: 購物車頁面 MUST 顯示每門課程的名稱、封面圖、定價，以及訂單總金額。
- **FR-009**: 購物車頁面 MUST 提供「移除課程」功能，移除後總金額即時更新。
- **FR-010**: 購物車「前往結帳」MUST 將購物車所有課程合併成一筆訂單（加總金額），依課程的 `payment_gateway` 欄位決定使用 PayUni 或藍新金流，建立訂單快照後跳轉至對應付款頁面。
- **FR-010a**: 系統 MUST 在送出金流請求前，於資料庫儲存「訂單快照」（Order + OrderItems），記錄本次付款涵蓋的課程清單、各課程金額及使用的金流提供商；webhook 回傳成功時，依此快照建立對應的 `purchases` 記錄。
- **FR-010b**: 若購物車中課程的 `payment_gateway` 不一致（混用 PayUni 與藍新），系統 MUST 在結帳頁提示用戶，並阻止送出，要求用戶調整購物車至單一金流。
- **FR-011**: 任一金流付款成功後，MUST 依訂單快照建立各課程的購買記錄，清空購物車中已完成付款的課程，並重導向至「我的課程」頁面。
- **FR-012**: Meta Pixel MUST 在以下時機觸發對應事件：
  - 「加入購物車」成功 → `AddToCart`（含 `content_ids`, `value`, `currency: TWD`）
  - 進入購物車頁 → `InitiateCheckout`（含 `num_items`, `value`, `currency: TWD`）
  - 任一金流付款成功返回頁 → `Purchase`（含 `value`, `currency: TWD`）
- **FR-013**: 導覽列 MUST 顯示購物車 icon，並以 badge 標示目前購物車課程數量。
- **FR-014**: 每門課程 MUST 儲存金流提供商欄位，可選值為 `payuni`（預設）或 `newebpay`；Portaly 課程此欄位不適用。
- **FR-015**: 後台課程表單 MUST 顯示金流選擇器（限非 Portaly 課程），PayUni 與藍新金流皆可選。當管理員填寫 `portaly_product_id` 時，金流選擇器 MUST 自動隱藏並清空 `payment_gateway` 值；清空 `portaly_product_id` 時，金流選擇器 MUST 重新顯示且預設值恢復為 PayUni。
- **FR-016**: 系統 MUST 以課程的 `payment_gateway` 欄位決定結帳時呼叫哪個金流 API：`payuni` → PayUni UPP；`newebpay` → 藍新 MPG。
- **FR-017**: 藍新金流結帳 MUST 提供兩個獨立端點：NotifyURL（背景 POST，接收加密回傳，驗證簽章後回應字串 `SUCCESS`）與 ReturnURL（前台跳轉，success 導向「我的課程」，failure 導向購物車）。
- **FR-018**: 藍新金流 NotifyURL MUST 驗證 TradeSha 簽章後才處理付款，驗證失敗需記錄 log 並回應 `SUCCESS` 防止重試。

### Key Entities

- **CartItem**：用戶購物車中的一筆項目，關聯用戶與課程，記錄加入時間；同一用戶不得有重複課程。不儲存價格，結帳時以課程當前定價計算。
- **Cart**（邏輯概念）：用戶的 CartItem 集合，計算總金額，驗證各課程可購買狀態。
- **Order**（新增）：結帳時由購物車快照產生的中間實體，記錄本次付款的課程清單、各課程金額、總金額、使用的金流提供商（`payment_gateway`）、金流平台交易號（PayUni trade no 或 NewebPay TradeNo）、付款狀態；供 webhook / callback 回傳時查找並建立 `purchases` 記錄。
- **Course**（既有，擴充）：透過 `portaly_product_id` 是否為空決定 Portaly vs 平台金流；新增 `payment_gateway` 欄位（`payuni` | `newebpay`，預設 `payuni`）供非 Portaly 課程使用；`price` 為 0 時為免費課程；`type = high_ticket` 走預約流程。

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 用戶可在 30 秒內完成「加入購物車 → 進入購物車 → 前往結帳」完整流程。
- **SC-002**: Meta Pixel 的 `AddToCart`、`InitiateCheckout`、`Purchase` 三個事件，可在 Facebook Events Manager 中驗證到對應操作觸發。
- **SC-003**: Portaly 課程的購買流程行為與重構前 100% 相同（無破壞性變更）。
- **SC-004**: 免費課程與 high-ticket 課程的購買流程行為與重構前 100% 相同。
- **SC-005**: 購物車跨 session 持久化：登出後重新登入，課程項目仍保留。
- **SC-006**: 購物車 badge 數量在加入或移除課程後，無需重新整理頁面即即時更新。
- **SC-007**: 後台課程表單中金流選擇器可正常儲存與讀取，PayUni 與藍新金流皆可選；現有課程金流欄位未設定時預設顯示 PayUni。
- **SC-008**: 藍新金流課程可完成完整購買流程：從購物車結帳 → 跳轉藍新付款頁 → 付款成功 → 課程開通，可在測試環境驗證。
- **SC-009**: PayUni 與藍新金流的 webhook 均實作冪等性保護，同一筆付款重複通知不會產生重複 Purchase 記錄。

---

## Assumptions

- PayUni 結帳採合併單筆訂單模式：購物車所有課程加總金額送出一次 PayUni 請求；系統預存 Order 快照，webhook 回傳成功後依快照建立各課程 `purchases` 記錄。
- 購物車分兩層：未登入使用 client-side guest cart（localStorage）；登入後使用 server-side 資料庫購物車（跨裝置一致）。登入時自動合併。
- 結帳（付款）需登入；加入購物車與直接購買按鈕不強制登入。
- 購物車項目不設過期時間，課程下架時系統自動清除該項目。
- 本版本同時實作 PayUni 與藍新金流（NewebPay）；兩者皆走相同的購物車 → Order 快照 → 外部付款頁 → webhook → Purchase 架構。
- 藍新金流技術規格：加密方式為 AES-256-CBC（OPENSSL_ZERO_PADDING，輸出 hex）+ SHA256 TradeSha；測試端點 `ccore.newebpay.com`，正式 `core.newebpay.com`；環境變數 `NEWEBPAY_MERCHANT_ID / NEWEBPAY_HASH_KEY / NEWEBPAY_HASH_IV`；NotifyURL 必須回應字串 `"SUCCESS"`；訂單編號限英數字+底線，最長 30 字元，不可重複。
- 混合金流購物車（同時有 PayUni 和藍新課程）：本版本要求用戶調整為單一金流再結帳，不自動拆單。
- 現有課程若未填 `payment_gateway`，自動 fallback 為 PayUni，不需任何資料遷移。

# Feature Specification: 客製服務預約系統

**Feature Branch**: `008-high-ticket-booking`
**Created**: 2026-04-08
**Updated**: 2026-04-09
**Updated**: 2026-04-09 - 新增 US5（預約儲存 Lead 記錄）、US6（後台 Leads 管理 + 加入序列信）；FR-011 修訂、FR-019～FR-032 新增
**Updated**: 2026-04-10 - Clarifications：新增行程通知流程（notified_count / last_notified_at）、high_ticket_slot_available 模板、pending 冷掉後加入 drip 邏輯
**Updated**: 2026-05-02 - US2 銷售頁 PayUni 分期付款 hint；US4 Email 模板列表補 high_ticket_slot_available 中文標籤；US6 「通知新時段」改為先開確認 modal 並顯示模板預覽
**Updated**: 2026-05-03 - US6 新增搜尋/課程篩選、「發送郵件」批次 modal（FR-036～FR-037）；修復 PayUni 付款後 drip 序列信未自動暫停的 bug（FR-038）
**Updated**: 2026-05-03 - US6 新增序列信訂閱紀錄欄（FR-039）、「開通」功能（FR-040～FR-041）；修復 notifyTemplate 載入 500 bug
**Status**: Implemented (US1–US6)

---

## Clarifications

### Session 2026-04-10

- Q: pending leads 被通知「新時段釋出」的方式為何？ → A: 管理員在後台手動批次觸發；系統使用新增的 `high_ticket_slot_available` Email 模板發信，並記錄通知次數（`notified_count`）與最後通知時間（`last_notified_at`）。
- Q: 「加入序列信（drip）」的目標 leads 為哪些 status？ → A: `pending`（通知 2 次後仍無回應、冷掉的）+ `closed`（面談後未成交）；`contacted` 和 `converted` 不加入。
- Q: 「通知新時段」批次操作，UI 是否限制只能勾選 `pending` leads？ → A: 是，UI 限制只有 `pending` leads 可被勾選進行此操作，避免誤發給已聯繫或已結案的人。
- Q: 「加入序列信」後 lead status 如何處理？是否需要防止同一 lead 加入兩條序列？ → A: 加入序列信成功後系統自動將 lead status 改為 `closed`，代表人工銷售線結束、交給自動化；搭配現有 drip subscription 去重檢查作為最後防線；不另設 nurturing 狀態，保持簡單。
- Q: Leads 列表預設排序為何？ → A: 依預約時間（`booked_at`）降冪，最新的在最上面。
- Q: Leads 列表是否需要分頁？ → A: 是，每頁 20 筆，與後台 Member 列表一致。

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 - 管理員設定客製服務課程類別 (Priority: P1)

管理員在後台建立或編輯課程時，可將課程類別設為「客製服務」。選擇客製服務後，出現一個開關：「隱藏價格（改為預約模式）」。開啟後，前台自動隱藏原價/優惠價、並將按鈕改為「立即預約」。其餘欄位（課程描述等）不受影響。

**Why this priority**: 這是整個功能的資料基礎，沒有客製服務類別，後續前台展示與預約流程均無法進行。

**Independent Test**: 在後台新增課程、選擇類別「客製服務」並開啟「隱藏價格」，儲存後資料庫正確寫入。

**Acceptance Scenarios**:

1. **Given** 管理員在課程編輯頁，**When** 選擇類別「客製服務」，**Then** 出現「隱藏價格（改為預約模式）」開關。
2. **Given** 管理員開啟隱藏價格，**When** 儲存課程，**Then** 資料庫記錄 `high_ticket_hide_price = true`。
3. **Given** 管理員關閉隱藏價格（客製服務顯示價格模式），**When** 儲存，**Then** 前台維持顯示原有價格與倒數，按鈕仍為「立即購買」。
4. **Given** 課程類別非客製服務，**When** 管理員儲存，**Then** 新增欄位不出現，原有行為不變。

---

### User Story 2 - 客製服務銷售頁前台展示 (Priority: P1)

訪客在客製服務課程銷售頁時，依設定看到正確的 UI：
- **隱藏價格模式**：底部的「優惠價 + 倒數計時」區塊改為顯示說明文字；行動按鈕改為「立即預約」。
- **顯示價格模式**：前台完全維持現有行為，不做任何更動。

**Why this priority**: 前台展示是使用者直接看到的核心體驗，需與後台設定同步交付。

**Independent Test**: 建立一個「客製服務（隱藏價格）」課程，瀏覽銷售頁，確認底部價格區塊已替換為說明文字，按鈕為「立即預約」。

**Acceptance Scenarios**:

1. **Given** 客製服務課程開啟隱藏價格，**When** 訪客瀏覽銷售頁，**Then** 底部左欄的優惠價與倒數計時完全消失，改顯示說明文字。
2. **Given** 客製服務課程開啟隱藏價格，**When** 訪客看到右欄按鈕，**Then** 按鈕文字為「立即預約」。
3. **Given** 客製服務課程關閉隱藏價格，**When** 訪客瀏覽銷售頁，**Then** 頁面與一般課程完全相同。
4. **Given** 非客製服務課程，**When** 訪客瀏覽銷售頁，**Then** 頁面外觀與現有行為完全相同。
5. **Given** 課程使用 PayUni 付款（`use_payuni=true`），**When** 訪客瀏覽銷售頁價格下方，**Then** 顯示分期付款提示：「支援 3/6/9 個月分期，建議使用台新銀行信用卡，其他銀行信用卡可能不支援。」

---

### User Story 3 - 訪客點擊預約後收到 Email (Priority: P2)

訪客在客製服務銷售頁點擊「立即預約」，填寫姓名、Email，提交後系統立即發送一封確認 Email 至訪客信箱。Email 內容來自管理員在後台設定的模板，支援變數替換（如訪客姓名、課程名稱）。系統不做其他事，後續流程（面談安排、對接外部平台）由 Email 模板內容引導。

**Why this priority**: Email 是整個預約流程的唯一產出，也是訪客確認「預約成立」的依據。

**Independent Test**: 在客製服務銷售頁填寫表單並提交，驗證訪客 Email 收到確認信，信件變數已正確填入。

**Acceptance Scenarios**:

1. **Given** 訪客點擊「立即預約」，**When** 顯示表單，**Then** 包含姓名（必填）、Email（必填）欄位。
2. **Given** 訪客完整填寫並提交，**When** 系統處理，**Then** 立即發送確認 Email 至訪客信箱。
3. **Given** Email 發送成功，**When** 訪客查看信件，**Then** 內容來自對應模板，變數已替換為實際資料。
4. **Given** 訪客未填完整欄位，**When** 點擊提交，**Then** 顯示驗證提示，不送出表單。

---

### User Story 4 - 管理員在後台統一管理 Email 模板 (Priority: P2)

管理員在後台「Email 模板」管理頁面，可編輯所有系統可模板化的 Email，包含：**客製服務預約確認**、**課程贈禮通知**、**新課程通知**。每個模板對應一個固定的觸發事件類型（`event_type`），由下拉選單選擇。模板以純文字撰寫，支援插入系統變數（如 `{{user_name}}`）。

系統初始化時自動 seed 三個預設模板（對應現有 Email 的內容），管理員可直接覆寫。

**Why this priority**: 統一管理讓業主日後可自行修改任何系統信件措辭，不依賴工程師。

**Independent Test**: 在後台修改「課程贈禮通知」模板內容並儲存，觸發贈禮動作，確認收到的 Email 內容已更新。

**Acceptance Scenarios**:

1. **Given** 管理員進入 Email 模板列表，**When** 查看，**Then** 看到系統所有可管理的模板（含 4 種預設），事件類型欄顯示中文標籤（含「客製服務新時段通知」）。
2. **Given** 管理員點擊編輯某模板，**When** 進入編輯器，**Then** 顯示：模板名稱、觸發事件（唯讀標示）、Email 主旨、內容編輯區。
3. **Given** 管理員在內容編輯區，**When** 使用插入變數功能，**Then** 顯示該 event_type 對應的可用變數清單，點擊後插入游標位置。
4. **Given** 管理員儲存模板，**When** 對應事件觸發（如贈禮），**Then** 系統使用更新後的模板發送 Email，變數正確替換。

---

### User Story 5 - 預約時系統儲存 Lead 記錄 (Priority: P2)

訪客成功提交預約表單後，系統除了發送確認 Email，同時將訪客的姓名、Email、所預約課程、預約時間記錄至後台 Leads 名單，初始狀態為「待聯繫」。管理員可從此名單追蹤所有潛在客戶。

**Why this priority**: Lead 記錄是後續銷售追蹤與序列信功能的資料基礎，不儲存則後續功能均無法進行。

**Independent Test**: 訪客提交預約表單 → 後台 Leads 列表出現一筆新記錄，name、email、course_id、status='pending' 均正確。

**Acceptance Scenarios**:

1. **Given** 訪客成功提交預約表單，**When** 系統處理，**Then** 建立一筆 `high_ticket_leads` 記錄（name、email、course_id、status='pending'、booked_at=現在）。
2. **Given** 同一 email 重複預約同一課程，**When** 再次提交，**Then** 建立新的一筆記錄（允許重複，保留每次預約紀錄）。
3. **Given** Email 發送失敗，**When** 系統處理，**Then** Lead 記錄仍應儲存（Lead 記錄與 Email 發送獨立）。

---

### User Story 6 - 管理員管理 Leads 名單與加入序列信 (Priority: P3)

管理員在後台「Leads 名單」頁面，可查看所有客製服務課程的預約者，按狀態（待聯繫/已聯繫/已成交/已關閉）篩選，並手動更新狀態。對於面談後尚未成交的潛在客戶，管理員可選定若干 leads，指定一個連鎖課程，批次將他們加入序列信發送佇列。

**Why this priority**: 管理員手動追蹤是目前規模下最合適的銷售漏斗工具；序列信整合讓未成交潛在客戶有機會透過自動化培養轉換。

**Independent Test**: 管理員篩選 status='contacted' 的 leads → 勾選 3 筆 → 選擇目標連鎖課程 → 確認 → 這 3 人各自建立 user 帳號（若不存在）並訂閱該連鎖課程，第一封序列信立即發出。

**Acceptance Scenarios**:

1. **Given** 管理員進入 Leads 名單頁，**When** 查看，**Then** 看到所有 leads 列表，含姓名、Email、課程名稱、狀態、通知次數、預約時間，可依狀態篩選。
2. **Given** 管理員點擊某筆 lead 的狀態欄位，**When** 選擇新狀態，**Then** 狀態更新並即時反映。
3. **Given** 管理員開放新時段，**When** 在 Leads 頁勾選若干 pending leads 並點擊「通知新時段」，**Then** 先開確認 modal：顯示模板主旨、內容預覽（Markdown 渲染）、收件人列表、模板編輯連結；管理員確認後才派送 Job，`notified_count` +1，`last_notified_at` 更新為現在。
3a. **Given** 管理員點擊「通知新時段」，**When** 資料庫找不到 `high_ticket_slot_available` 模板，**Then** modal 顯示警告並停用「確認發送」按鈕，引導至 Email 模板管理頁建立。
4. **Given** 某 pending lead 已被通知 2 次，7 天後仍無回應，**When** 管理員判斷冷掉，**Then** 管理員手動將其 status 改為 `closed`。
5. **Given** 管理員勾選若干 `pending`（冷掉）或 `closed` 的 leads，**When** 點擊「加入序列信」，**Then** 出現選擇連鎖課程的下拉選單（列出所有 drip 類型課程）。
6. **Given** 管理員選定連鎖課程並確認，**When** 系統處理，**Then** 對每筆 lead：(a) 以 email 查找或建立 user 帳號（帶入 lead.name 作為 nickname），(b) 建立 drip_subscription，(c) 立即觸發第一封序列信，(d) lead status 自動改為 `closed`。
7. **Given** 某 lead 的 email 已有任何 active drip_subscription，**When** 系統處理，**Then** 跳過該筆 lead，不重複建立訂閱，避免兩條序列同時運行。
8. **Given** 批次操作完成，**When** 系統回應，**Then** 顯示摘要：「已派送 N 人，略過 M 人（已有 active 序列）」；實際加入結果非同步執行。
9. **Given** 管理員在 Leads 名單頁，**When** 在搜尋欄輸入姓名或 Email 片段，**Then** 列表即時（300ms debounce）縮小為符合條件的結果，並保留現有狀態篩選與課程篩選。
10. **Given** 管理員選擇課程下拉篩選，**When** 選定某客製服務課程，**Then** 列表只顯示該課程的 leads；點擊 × 清除後恢復全部。
11. **Given** 管理員勾選若干 leads（任意狀態），**When** 點擊「發送郵件」，**Then** 開啟批次郵件 modal，顯示收件人數量、主旨欄位（上限 200 字）、內容欄位（上限 10000 字）及字元計數。
12. **Given** 管理員填寫主旨與內容後點擊「發送郵件」，**When** 系統處理，**Then** 對每位 lead 直接寄出客製化 Email，回應顯示「已發送 N 封郵件」。

---

### Edge Cases

- 客製服務無設定 Email 模板時，系統回傳 422 錯誤，前端顯示錯誤訊息；Lead 記錄仍照常儲存，不 crash。
- 隱藏價格模式下，頂部資訊列的 PriceDisplay 也隱藏，避免洩漏價格。
- 管理員將已存在的非客製服務課程改為客製服務，前台即時反映；購買記錄不受影響。
- Email 模板預覽模式使用 `breaks: true` 渲染，使單行換行在預覽中與編輯區一致。
- 生產環境舊版 MySQL（< 8.0.3）不支援 `ADD COLUMN IF NOT EXISTS`；migration 改用 `Schema::hasColumn()` 判斷。
- 同一 email 重複預約同一課程，Lead 記錄允許重複（保留完整預約歷史），不做 upsert。
- 「加入序列信」建立的 user 帳號無密碼，使用現有驗證碼登入機制，行為與一般會員帳號完全相同。
- Lead email 若與現有 user email 相同，`firstOrCreate` 直接使用現有帳號，不重複建立，不覆蓋現有資料。
- 後台手動新增交易（TransactionController）不觸發 `checkAndConvert()`；此為有意設計，手動操作語意與自動付款不同。
- 「發送郵件」modal 對 lead 直接寄信（以 lead.email 為收件地址），不依賴 User 帳號存在；發送失敗的個別 lead 僅記錄 error log，不中斷其餘發送。

---

## Requirements *(mandatory)*

### Functional Requirements

**課程類別擴充**

- **FR-001**: 課程類別選項 MUST 新增「客製服務」，與現有講座/迷你課/完整課程並列。
- **FR-002**: 當課程設為客製服務時，管理員 MUST 能設定一個布林開關「隱藏價格（改為預約模式）」（預設關閉）。
- **FR-003**: 開啟隱藏價格時，前台 MUST 自動套用「隱藏價格 + 按鈕改為立即預約」，不需個別設定。

**銷售頁前台展示**

- **FR-004**: 客製服務課程（隱藏價格模式）的銷售頁 MUST 將底部「優惠價 + 倒數計時」區塊替換為說明文字：「此為高價工作坊，請預約 1v1 面談了解，預約後必須立即收取 Email 完成任務，才是正式完成預約。」
- **FR-005**: 客製服務課程（隱藏價格模式）的頂部快速購買區 MUST 也隱藏 PriceDisplay。
- **FR-006**: 客製服務課程（隱藏價格模式）的行動按鈕 MUST 顯示「立即預約」。
- **FR-007**: 客製服務課程（顯示價格模式）和非客製服務課程 MUST 維持現有行為完全不變。

**預約流程**

- **FR-008**: 點擊「立即預約」後 MUST 顯示預約表單（姓名、Email，均為必填）。
- **FR-009**: 成功提交後，系統 MUST 立即發送確認 Email 至訪客填寫的信箱。
- **FR-010**: 確認 Email MUST 使用對應的 Email 模板，並將變數替換為實際資料後寄出。
- **FR-011**: ~~系統 MUST NOT 建立任何資料庫預約記錄~~ **（已修訂，見 FR-019）**：系統 MUST NOT 建立付費訂單或購買記錄；預約的唯一產出為確認 Email 和一筆 Lead 記錄。

**Email 模板管理**

- **FR-012**: 後台 MUST 提供 Email 模板管理頁面，涵蓋所有可模板化的系統 Email。
- **FR-013**: 納管的 event_type 包含：`high_ticket_booking_confirmation`、`course_gifted`、`lesson_added`、`high_ticket_slot_available`。
- **FR-014**: 每個模板 MUST 包含：模板名稱、觸發事件（event_type，唯讀）、Email 主旨、純文字內容。
- **FR-015**: 模板編輯器 MUST 提供該 event_type 對應的可用變數清單，點擊後插入游標位置。
- **FR-016**: 系統 MUST 在初始化時 seed 四個預設模板，內容對應現有寫死的信件內容（含 `high_ticket_slot_available`）。
- **FR-017**: 當對應事件觸發時，系統 MUST 優先讀取資料庫模板；若不存在則 fallback 至寫死內容並記錄警告。
- **FR-018**: 各 event_type 的可用變數：

  | event_type | 可用變數 |
  |------------|---------|
  | `high_ticket_booking_confirmation` | `{{user_name}}`、`{{user_email}}`、`{{course_name}}` |
  | `course_gifted` | `{{user_name}}`、`{{course_name}}`、`{{course_description}}` |
  | `lesson_added` | `{{user_name}}`、`{{course_name}}`、`{{lesson_title}}`、`{{classroom_url}}` |
  | `high_ticket_slot_available` | `{{user_name}}`、`{{course_name}}` |

**Lead 記錄（US5）**

- **FR-019**: 訪客成功提交預約表單後，系統 MUST 建立一筆 `high_ticket_leads` 記錄，包含：name、email、course_id、status（預設 'pending'）、notified_count（預設 0）、booked_at。
- **FR-020**: Lead 記錄 MUST 與 Email 發送獨立；Email 發送失敗不得影響 Lead 記錄的建立。
- **FR-021**: 同一 email 重複預約，系統 MUST 允許建立新記錄（不做去重）。

**Leads 管理後台（US6）**

- **FR-022**: 後台 MUST 提供 Leads 名單頁面，列出所有 `high_ticket_leads` 記錄，顯示：姓名、Email、所屬課程、狀態、通知次數、預約時間；預設依 `booked_at` 降冪排列；每頁 20 筆分頁。
- **FR-023**: Leads 名單 MUST 支援依狀態篩選：`pending`（待聯繫）、`contacted`（已聯繫）、`converted`（已成交）、`closed`（已關閉）。
- **FR-024**: 管理員 MUST 能在列表中直接更新個別 lead 的狀態。
- **FR-025**: 管理員 MUST 能勾選多筆 `pending` leads 並觸發「通知新時段」批次操作；UI MUST 限制只有 status='pending' 的 leads 可被勾選進行此操作；系統使用 `high_ticket_slot_available` Email 模板非同步發信（Job per lead），每筆 lead 的 `notified_count` +1、`last_notified_at` 更新於 Job 執行時；操作立即回應 `{ "dispatched": N }`。
- **FR-026**: 「通知新時段」操作 MUST 使用 Email 模板系統（`EmailTemplate::forEvent('high_ticket_slot_available')`），支援變數 `{{user_name}}`、`{{course_name}}`。
- **FR-027**: `high_ticket_slot_available` 模板 MUST 在系統初始化時 seed 一筆預設內容，與其他三個預設模板一同納管。
- **FR-028**: 管理員 MUST 能勾選多筆 `pending`（冷掉）或 `closed` 的 leads 並觸發「加入序列信」批次操作。
- **FR-029**: 「加入序列信」操作 MUST 提供下拉選單，列出系統中所有 `drip` 類型的課程供選擇。
- **FR-030**: 確認後，系統 MUST 對每筆選定的 lead：(a) 以 email 為 key `firstOrCreate` user 帳號（帶入 lead.name 作為 nickname）；(b) 建立 `drip_subscription`（user_id + course_id）；(c) 立即觸發第一封序列信；(d) 將 lead status 更新為 `closed`。
- **FR-031**: 若 lead 的 email 已存在任何 `status='active'` 的 `drip_subscription`（任何課程），系統 MUST 跳過該筆，不重複建立，避免兩條序列同時運行破壞敘事。
- **FR-032**: 批次操作完成後，系統 MUST 回應摘要訊息，說明已派送（dispatched）人數與已跳過人數（含跳過原因：已有 active 序列訂閱）；實際加入結果由非同步 Job 執行，不在當下回報。

**PayUni 分期付款提示（US2 擴充）**

- **FR-033**: 當課程使用 PayUni 付款（`use_payuni=true`）時，銷售頁底部 PriceDisplay 下方 MUST 顯示分期付款提示文字；非 PayUni 課程 MUST NOT 顯示。

**通知新時段確認流程（US6 擴充）**

- **FR-034**: 點擊「通知新時段」後，系統 MUST 先顯示確認 modal，內容包含：模板主旨、body_md Markdown 渲染預覽、收件人列表（姓名 + Email）、以及前往編輯模板的連結；管理員確認後才實際派送 Job。
- **FR-035**: 若資料庫找不到 `high_ticket_slot_available` 模板，確認 modal MUST 顯示錯誤警告並停用「確認發送」按鈕。

**Leads 名單搜尋與篩選（US6 擴充）**

- **FR-036**: Leads 名單 MUST 支援依姓名或 Email 關鍵字搜尋（LIKE 模糊比對），並可同時與狀態篩選、課程篩選組合使用；搜尋輸入採 300ms debounce。
- **FR-036a**: Leads 名單 MUST 支援依所屬課程篩選，下拉選單列出所有 `type='high_ticket'` 的課程；選定後以課程 ID 過濾列表。

**Leads 批次發送客製郵件（US6 擴充）**

- **FR-037**: 管理員 MUST 能勾選任意狀態的 leads，點擊「發送郵件」後開啟客製郵件 modal（主旨上限 200 字、內容上限 10000 字），確認後對每位 lead 直接發送 Email；使用 `BatchEmailMail` class，支援 CommonMark Markdown 渲染。

**PayUni 付款後 drip 轉換（系統修復）**

- **FR-038**: 透過統一金流（PayUni）完成購買後，系統 MUST 呼叫 `DripService::checkAndConvert()`，將任何以所購課程為轉換目標的 active drip 訂閱標記為 `converted`，停止繼續發送序列信。此行為與 Portaly Webhook 路徑一致。

**Leads 序列信紀錄欄（US6 擴充）**

- **FR-039**: Leads 名單 MUST 在「序列信紀錄」欄顯示每位 lead 曾加入的 drip 課程及其訂閱狀態（active／completed／converted／unsubscribed）；以 email 為 key 透過 `users` → `drip_subscriptions` 關聯查詢，不需額外 migration；若無紀錄顯示 `—`。

**Lead 開通商品（US6 擴充）**

- **FR-040**: 管理員 MUST 能在每筆非「已成交」的 lead 課程欄，點擊「開通」按鈕，透過確認 modal 選擇要開通的商品（列出所有課程），系統隨後：(a) 以 email 為 key `firstOrCreate` 會員帳號（nickname = lead.name，password 隨機產生）；(b) 以 `Purchase::updateOrCreate` 建立 `type='gift'`、`status='paid'`、`amount=0` 的購買記錄；(c) 將 lead status 更新為 `converted`。
- **FR-041**: 「開通」確認 modal MUST 顯示：lead 姓名與 Email、三條操作說明（自動建立會員帳號、開通商品、狀態更新為已成交）、商品下拉選單；確認後 inline 更新列表該列狀態，並於頁面頂部顯示操作結果摘要。

### Key Entities

- **Course（課程）**: 新增欄位：`type` 擴充客製服務選項、`high_ticket_hide_price`（布林）。
- **EmailTemplate（Email 模板）**: 屬性：名稱、event_type、Email 主旨、純文字內容。
- **HighTicketLead（潛在客戶）**: 屬性：name、email、course_id、status（pending/contacted/converted/closed）、notified_count（行程通知次數）、last_notified_at、booked_at。

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 管理員可在 2 分鐘內完成客製服務課程設定，無需開發協助。
- **SC-002**: 訪客提交預約表單至收到確認 Email 的時間在 60 秒內（正常網路環境）。
- **SC-003**: 客製服務銷售頁在隱藏價格模式下，100% 不洩漏任何原價/優惠價資訊。
- **SC-004**: 管理員可在不修改程式碼的情況下，完整客製化客製服務預約確認、課程贈禮、新課程通知三種 Email 的主旨與內容。
- **SC-005**: 現有非客製服務課程的購買流程，在本功能上線後 0% 功能回歸錯誤。
- **SC-006**: 管理員可在 1 分鐘內完成篩選 leads、選定課程、觸發「加入序列信」的全流程操作。
- **SC-007**: 「加入序列信」批次操作完成後，被加入的 leads 在 60 秒內收到第一封序列信。

---

## Assumptions

- Email 發送服務沿用現有 Resend.com 整合。
- 預約後的後續流程（面談安排、外部平台對接）完全由 Email 模板內容引導，系統不介入。
- ~~系統不儲存任何預約記錄~~ **（已修訂）**：系統儲存 `high_ticket_leads` 記錄，但不儲存付費訂單或購買記錄。
- 「優惠倒數」計時器在隱藏價格模式下，整個區塊被說明文字取代，不單獨保留倒數。
- 客製服務顯示價格模式下，行為與一般課程完全相同（包含現有的立即購買流程）。

# Feature Specification: 數位課程販售平台 MVP

**Feature Branch**: `001-course-platform-mvp`
**Created**: 2026-01-16
**Status**: Draft
**Input**: User description: "數位內容販售平台 MVP，包含課程首頁、會員系統（email 驗證登入）、我的課程頁面、帳號設定頁面"
**Updated**: 2026-01-30 - 全站配色優化（統一 Color Scheme）
**Updated**: 2026-01-31 - Webhook 處理優化（靜默忽略不相關的 Portaly 產品）
**Updated**: 2026-02-06 - 新增 Landing Page 模式（隱藏導覽列，適合外部連結分享）

## User Scenarios & Testing *(mandatory)*

### User Story 1 - 瀏覽課程首頁 (Priority: P1)

訪客進入網站首頁，可以看到所有販售中的課程列表，每個課程顯示縮圖（300x200px）、課程名稱和一句話簡介。訪客可以點擊任一課程進入該課程的獨立販售頁面。

**Why this priority**: 首頁是用戶接觸平台的第一個頁面，必須能展示課程內容吸引用戶購買。

**Independent Test**: 可以獨立測試，訪客無需登入即可瀏覽首頁和課程販售頁。

**Acceptance Scenarios**:

1. **Given** 訪客進入首頁, **When** 頁面載入完成, **Then** 顯示所有販售中課程的縮圖、名稱和簡介
2. **Given** 訪客在首頁, **When** 點擊某課程, **Then** 跳轉到該課程的獨立販售頁 (/course/{id})
3. **Given** 訪客使用手機瀏覽, **When** 頁面載入完成, **Then** 課程列表以適合手機螢幕的方式呈現

---

### User Story 2 - Email 驗證碼登入/註冊 (Priority: P1)

用戶輸入 email 後，系統發送 6 碼驗證碼到該 email。用戶輸入正確驗證碼即可登入。如果該 email 尚未註冊，系統自動建立帳號。用戶不需要設置密碼。

**Why this priority**: 會員系統是購買和上課功能的基礎，且無密碼登入可降低註冊門檻。

**Independent Test**: 可以測試完整的登入流程，從輸入 email 到收到驗證碼到登入成功。

**Acceptance Scenarios**:

1. **Given** 用戶在登入頁面, **When** 輸入有效 email 並提交, **Then** 系統發送 6 碼驗證碼到該 email
2. **Given** 用戶已收到驗證碼, **When** 輸入正確驗證碼, **Then** 登入成功並跳轉到「我的課程」頁面
3. **Given** 用戶輸入的 email 尚未註冊, **When** 尚未勾選同意條款, **Then** 無法提交驗證碼
4. **Given** 用戶輸入的 email 尚未註冊, **When** 勾選同意條款並完成驗證碼驗證, **Then** 系統自動建立會員帳號並登入
5. **Given** 用戶輸入錯誤驗證碼, **When** 提交驗證, **Then** 顯示錯誤訊息，允許重新輸入
6. **Given** 驗證碼已過期, **When** 用戶輸入該驗證碼, **Then** 顯示驗證碼已過期訊息，可重新發送

---

### User Story 3 - 我的課程頁面 (Priority: P2)

已登入會員可以進入「我的課程」頁面 (/member/learning)，查看所有已購買的課程列表。每個課程顯示縮圖、課程名稱和教師名稱。

**Why this priority**: 會員需要有地方查看已購買的課程，這是上課流程的入口。

**Independent Test**: 可以用測試帳號和預設購買紀錄來測試頁面呈現。

**Acceptance Scenarios**:

1. **Given** 會員已登入且有已購買課程, **When** 進入「我的課程」頁面, **Then** 顯示所有已購買課程的縮圖、名稱和教師
2. **Given** 會員已登入但沒有任何課程, **When** 進入「我的課程」頁面, **Then** 顯示「尚無課程」提示並引導至首頁瀏覽
3. **Given** 會員未登入, **When** 嘗試進入「我的課程」頁面, **Then** 跳轉到登入頁面

---

### User Story 4 - 帳號設定頁面 (Priority: P3)

會員可以進入「帳號設定」頁面 (/member/settings)，修改個人資料（暱稱、出生年月日）並檢視訂單紀錄（兌換/交易紀錄）。

**Why this priority**: 帳號設定是基本會員功能，但優先級低於核心購課流程。

**Independent Test**: 可以測試個人資料的編輯和儲存功能，以及訂單紀錄的顯示。

**Acceptance Scenarios**:

1. **Given** 會員已登入, **When** 進入「帳號設定」頁面, **Then** 顯示目前的暱稱和出生年月日
2. **Given** 會員在帳號設定頁面, **When** 修改暱稱並儲存, **Then** 暱稱更新成功並顯示成功訊息
3. **Given** 會員有交易紀錄, **When** 查看訂單紀錄區塊, **Then** 顯示所有兌換/交易紀錄（時間、課程名稱、金額）
4. **Given** 會員沒有任何交易, **When** 查看訂單紀錄區塊, **Then** 顯示「尚無訂單紀錄」

---

### User Story 5 - 課程獨立販售頁 (Priority: P2)

訪客或會員可以進入課程的獨立販售頁 (/course/{id})，查看課程詳細資訊。未登入訪客需先輸入 email，頁面顯示購買按鈕，點擊後外連到 Portaly 產品頁進行付款。

**Why this priority**: 販售頁是轉換的關鍵頁面，需要清楚呈現課程資訊和購買入口。

**Independent Test**: 可以測試頁面呈現和購買按鈕的外連功能。

**Acceptance Scenarios**:

1. **Given** 訪客進入課程販售頁, **When** 頁面載入完成, **Then** 顯示課程縮圖、名稱、完整介紹、價格和購買區塊
2. **Given** 未登入訪客在課程販售頁, **When** 查看購買區塊, **Then** 顯示提醒文字「購買課程之後，務必確認使用和 Portaly 下訂時相同的 Email 登入。如果您已註冊過本站，建議先登入。」
3. **Given** 已登入會員在課程販售頁, **When** 查看購買區塊, **Then** 顯示目前登入的 email，並提醒「請確認 Portaly 結帳時使用此 Email：{user.email}」
4. **Given** 訪客在課程販售頁, **When** 尚未勾選同意條款, **Then** 購買按鈕呈現禁用狀態
5. **Given** 訪客已勾選同意條款, **When** 點擊購買按鈕, **Then** 開啟新視窗連到該課程的 Portaly 產品頁（Portaly 表單會收集用戶資料）

---

### User Story 6 - Webhook 購買處理 (Priority: P1)

當用戶在 Portaly 完成付款後，Portaly 透過 webhook 通知系統。系統使用 HMAC-SHA256 驗證 `X-Portaly-Signature` header 後建立購買紀錄，並在必要時自動為未註冊的 email 建立會員帳號。系統也需處理退款 (refund) 事件。

**Why this priority**: 購買驗證是核心交易功能，必須確保付款成功後正確建立紀錄。

**Independent Test**: 可以模擬 Portaly webhook 請求來測試處理邏輯。

**Acceptance Scenarios**:

1. **Given** 用戶在 Portaly 完成付款, **When** Portaly 發送 webhook（event: "paid"）, **Then** 系統使用 HMAC-SHA256 驗證 `X-Portaly-Signature` header
2. **Given** webhook 驗證成功且 email 已註冊, **When** 處理購買, **Then** 為該會員建立購買紀錄
3. **Given** webhook 驗證成功且 email 未註冊, **When** 處理購買, **Then** 先自動建立會員帳號（含姓名、電話），再建立購買紀錄
4. **Given** webhook 驗證失敗（簽章無效）, **When** 收到請求, **Then** 回傳 401 錯誤，不建立任何紀錄
5. **Given** 相同 portaly_order_id 已存在, **When** 收到重複 webhook, **Then** 回傳成功但不重複建立紀錄（冪等處理）
6. **Given** 購買紀錄建立成功, **When** 會員登入查看「我的課程」, **Then** 可以看到剛購買的課程
7. **Given** Portaly 發送退款 webhook（event: "refund"）, **When** 驗證成功, **Then** 將對應購買紀錄狀態更新為 "refunded"
8. **Given** webhook 包含 productId, **When** 處理購買, **Then** 系統透過 `portaly_product_id` 找到對應課程

---

### User Story 7 - Landing Page 模式 (Priority: P2)

從外部連結（如社群媒體、廣告、Email）點擊進入課程販售頁的訪客，透過 URL 參數 `?lp=1` 進入 Landing Page 模式。此模式下隱藏頂部導覽列（NavBar）和導航麵包屑（Breadcrumb），讓頁面呈現更像獨立的銷售頁面，減少干擾並提升轉換專注度。

**Why this priority**: Landing Page 模式可提升外部流量的轉換率，讓訪客專注於課程內容和購買決策，減少導覽分心。

**Independent Test**: 可獨立測試，透過添加 `?lp=1` 參數訪問課程販售頁，驗證導覽列和麵包屑是否正確隱藏。

**Acceptance Scenarios**:

1. **Given** 訪客透過外部連結進入課程販售頁, **When** URL 包含 `?lp=1` 參數, **Then** 頁面不顯示頂部導覽列（NavBar）
2. **Given** 訪客在 Landing Page 模式, **When** 頁面載入完成, **Then** 頁面不顯示導航麵包屑（Breadcrumb）
3. **Given** 訪客在 Landing Page 模式, **When** 查看頁面內容, **Then** 課程資訊、價格、購買按鈕等核心內容完整顯示
4. **Given** 訪客在 Landing Page 模式, **When** 頁尾連結（服務條款、隱私政策等）被點擊, **Then** 正常開啟對應的 Modal 或頁面
5. **Given** 訪客直接訪問課程販售頁（無 `?lp=1` 參數）, **When** 頁面載入完成, **Then** 顯示完整的導覽列和麵包屑
6. **Given** 訪客在 Landing Page 模式, **When** 使用手機瀏覽, **Then** 頁面正確適配手機螢幕，核心內容完整顯示
7. **Given** 訪客在 Landing Page 模式, **When** URL 同時包含 `?lp=1` 和 UTM 參數, **Then** Landing Page 模式正常運作，UTM 參數可供分析追蹤

---

### Edge Cases

- 用戶連續多次請求發送驗證碼時，系統需限制發送頻率（例如：60 秒內只能發送一次）
- 驗證碼輸入錯誤超過 5 次時，暫時鎖定該 email 的驗證嘗試（15 分鐘）
- 課程資料不存在時，顯示 404 頁面並引導回首頁
- 會員資料更新失敗時，顯示錯誤訊息並保留已輸入的內容
- Email 發送失敗時，顯示錯誤訊息提示用戶稍後重試
- Webhook 請求逾時或網路錯誤時，Portaly 會自動重試，系統需確保冪等處理
- 自動建立的會員帳號使用 Portaly 回傳的姓名和電話，會員可在帳號設定頁面修改
- Webhook 的 productId 找不到對應課程時，靜默忽略並回傳 200（不記錄 ERROR，避免日誌噪音）
- 退款 webhook 找不到對應訂單時，記錄錯誤但回傳 200
- 不相關的 Portaly 產品（如其他商品）發送 webhook 到課程網站時，靜默忽略且不建立用戶帳號
- Landing Page 模式下，訪客手動移除 `?lp=1` 參數並重新載入時，恢復顯示完整導覽列
- Landing Page 模式下，登入功能仍可透過頁面內的登入連結（如購買區塊的「建議先登入」連結）使用
- Landing Page 模式下，若訪客透過頁內連結跳轉至其他頁面，其他頁面顯示正常導覽列（參數不自動傳遞）

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: 系統 MUST 在首頁依排序順序顯示所有販售中課程的縮圖（300x200px）、名稱和一句話簡介
- **FR-002**: 系統 MUST 提供課程獨立販售頁面 (/course/{id})，顯示課程完整資訊
- **FR-003**: 系統 MUST 支援 email 驗證碼登入，發送 6 碼數字驗證碼
- **FR-004**: 系統 MUST 在用戶首次驗證成功時自動建立會員帳號（無需預先註冊）
- **FR-005**: 驗證碼 MUST 在 10 分鐘內有效
- **FR-006**: 系統 MUST 限制驗證碼發送頻率（同一 email 每 60 秒最多 1 次）
- **FR-007**: 系統 MUST 在驗證失敗 5 次後暫時鎖定該 email（15 分鐘）
- **FR-008**: 會員 MUST 能在「我的課程」頁面查看已購買課程列表
- **FR-009**: 會員 MUST 能在「帳號設定」頁面修改暱稱和出生年月日
- **FR-010**: 會員 MUST 能在「帳號設定」頁面檢視訂單紀錄
- **FR-011**: 所有頁面 MUST 支援 RWD（響應式設計），適配桌機和手機螢幕
- **FR-012**: 系統 MUST 區分會員階級：管理員、編輯、一般會員
- **FR-013**: 未登入用戶嘗試進入會員專屬頁面時 MUST 跳轉到登入頁
- **FR-014**: 會員登入 session MUST 維持 30 天有效
- **FR-015**: 新用戶註冊前 MUST 勾選同意服務條款和隱私政策
- **FR-016**: 訪客點擊購買按鈕前 MUST 勾選同意購買條款
- **FR-017**: 課程販售頁 MUST 顯示 Email 提醒，確保用戶在 Portaly 結帳時使用正確的 Email
- **FR-018**: 系統 MUST 提供 webhook 端點接收 Portaly 購買通知（paid 和 refund 事件）
- **FR-019**: Webhook 處理 MUST 使用 HMAC-SHA256 驗證 `X-Portaly-Signature` header
- **FR-020**: Webhook 處理 MUST 支援冪等性（重複請求不重複建立紀錄）
- **FR-021**: 當 webhook 收到未註冊 email 的購買通知時，系統 MUST 自動建立會員帳號
- **FR-022**: Webhook 處理 MUST 透過 `data.productId` 對應 `Course.portaly_product_id` 找到課程
- **FR-023**: 當收到 refund 事件時，系統 MUST 將對應購買紀錄狀態更新為 "refunded"
- **FR-024**: 自動建立的會員帳號 SHOULD 包含 Portaly 回傳的姓名和電話（若有提供）

**Landing Page 模式：**
- **FR-025**: 系統 MUST 支援 `?lp=1` URL 參數啟用 Landing Page 模式
- **FR-026**: Landing Page 模式下，課程販售頁 MUST 隱藏頂部導覽列（NavBar）
- **FR-027**: Landing Page 模式下，課程販售頁 MUST 隱藏導航麵包屑（Breadcrumb）
- **FR-028**: Landing Page 模式下，課程販售頁 MUST 保留完整的課程資訊、價格和購買功能
- **FR-029**: Landing Page 模式 MUST 支援與 UTM 參數並用（如 `?lp=1&utm_source=facebook`）
- **FR-030**: Landing Page 模式 MUST 支援 RWD，適配桌機和手機螢幕

### Key Entities

- **User（會員）**: 代表平台用戶，包含 email、暱稱、真實姓名（選填）、電話（選填）、出生年月日、角色（管理員/編輯/一般會員）、建立時間、最後登入時間、最後登入 IP
- **Course（課程）**: 代表販售的數位內容，包含名稱、一句話簡介、完整介紹、價格、縮圖、教師資訊、類型（講座/迷你課/大型課程）、上架狀態、排序順序、Portaly 產品頁連結、Portaly productId
- **Purchase（購買紀錄）**: 代表會員與課程的購買關係，包含 Portaly 訂單編號、購買時間、金額、幣別、折扣碼（選填）、折扣金額、付款狀態（已付款/已退款）

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 用戶可在 3 秒內完成首頁載入並看到課程列表
- **SC-002**: 用戶從輸入 email 到收到驗證碼的時間不超過 30 秒
- **SC-003**: 新用戶可在 2 分鐘內完成註冊（輸入 email、收驗證碼、完成驗證）
- **SC-004**: 90% 的用戶可在首次嘗試時成功完成登入流程
- **SC-005**: 所有頁面在手機（320px 以上寬度）上完整顯示且可正常操作
- **SC-006**: 會員帳號設定的修改可在 1 秒內完成儲存並顯示成功回饋
- **SC-007**: Webhook 處理在 5 秒內完成購買紀錄建立
- **SC-008**: 透過 webhook 自動建立的會員可在付款後立即使用 email 登入查看課程
- **SC-009**: Landing Page 模式下，頁面載入時間與正常模式相同（3 秒內）
- **SC-010**: 外部連結訪客在 Landing Page 模式下可完成完整購買流程

## Clarifications

### Session 2026-01-16

- Q: 會員登入後，session 應維持多久有效？ → A: 30 天
- Q: 首頁的課程列表應如何排序？ → A: 依手動排序欄位（管理員可調整順序）
- Q: 當 email 發送失敗時，系統應如何處理？ → A: 顯示錯誤訊息，提示用戶 稍後重試
- Q: 增量更新 - 新增同意條款勾選機制 → A: 註冊和購買前都需勾選同意
- Q: 增量更新 - Email 發送服務應使用什麼服務？ → A: 使用 Resend.com，發送域名 yueyuknows.com，寄件者名稱「經營者時間銀行」

### Session 2026-01-17 (縮圖 URL 處理)

- Q: 課程縮圖的 URL 應由前端或後端組合？ → A: 後端統一輸出完整 URL，前端直接使用
- Q: 資料庫應儲存完整 URL 還是相對路徑？ → A: 資料庫儲存相對路徑（如 `thumbnails/abc.jpg`），後端輸出時轉換為完整 URL（如 `/storage/thumbnails/abc.jpg`）
- Q: 縮圖 URL 處理的設計原則？ → A: 前端不需知道 storage 實作細節，後端負責提供可直接使用的 URL；未來如遷移至 S3 只需修改後端一處

### Session 2026-01-17 (Webhook 購買處理)

- Q: 未登入用戶如何購買？ → A: 在課程販售頁輸入 email 後跳轉 Portaly，Portaly 結帳表單會收集用戶資料（email、姓名、電話）
- Q: 購買成功後如何建立紀錄？ → A: Portaly 透過 webhook 通知，系統驗證後建立購買紀錄
- Q: 未註冊 email 購買時如何處理？ → A: Webhook 處理時自動建立會員帳號（含姓名、電話），再建立購買紀錄
- Q: Webhook 驗證方式？ → A: 使用 HMAC-SHA256 驗證 `X-Portaly-Signature` header，金鑰從 Portaly 後台取得
- Q: 自動建立的會員如何登入？ → A: 使用購買時的 email 進行 OTP 登入即可
- Q: Webhook 事件類型？ → A: 支援 `paid`（付款成功）和 `refund`（退款）兩種事件
- Q: 如何對應課程？ → A: 透過 webhook 的 `data.productId` 對應資料庫的 `Course.portaly_product_id`
- Q: Webhook URL 如何設定？ → A: 需在 Portaly 後台的商品設定中填入 webhook URL（如 `https://domain.com/api/webhook/portaly`）

## Assumptions

- 課程資料將由管理員透過後台（後續開發）建立，MVP 階段使用 seed 資料測試
- 購買流程外連至 Portaly 產品頁處理付款，購買紀錄透過 webhook 自動建立
- 課程上課介面（/member/classroom/{id}）為預留功能，此 MVP 不實作
- 管理後台為後續開發項目，此 MVP 僅建立資料庫角色欄位
- Email 發送使用 Resend.com 服務（需要 API Key）
- 課程縮圖由管理員上傳，系統不自動裁切
- 服務條款和隱私政策內容為靜態頁面或彈窗，MVP 不需後台編輯功能
- 同意條款勾選狀態僅前端驗證，不儲存用戶同意紀錄（未來可擴展）
- 課程縮圖資料庫儲存相對路徑，後端透過 Model Accessor 輸出完整 URL，前端直接使用不需自行組合路徑
- Portaly webhook 使用 HMAC-SHA256 簽章驗證，金鑰需從 Portaly 後台取得並設定為環境變數
- 自動建立的會員帳號預設 role 為 'member'，無需額外驗證即可使用 OTP 登入
- Portaly 結帳表單會收集用戶 email、姓名、電話，付款成功後透過 webhook 的 `customerData` 回傳
- 需在 Portaly 後台為每個商品設定 webhook URL（如 `https://domain.com/api/webhook/portaly`）
- 每個課程的 `portaly_product_id` 需與 Portaly 商品的 ID 對應
- Portaly 可能將多個產品的 webhook 發送到同一端點，系統應靜默忽略不相關產品（productId 不在資料庫中的課程）
- Landing Page 模式透過前端檢測 URL 參數 `?lp=1` 實作，不需後端變更
- Landing Page 模式僅影響課程販售頁的顯示，不影響其他頁面或功能
- Landing Page 模式下隱藏的元素：NavBar（包含 Logo、選單、登入按鈕）和 Breadcrumb（導航麵包屑）
- Landing Page 模式下保留的元素：頁尾（Footer）、課程完整資訊、價格區塊、購買按鈕、法律政策 Modal

### Session 2026-01-31 (Webhook 處理優化)

- Q: Portaly 發送不相關產品的 webhook 時如何處理？ → A: 靜默忽略，不記錄 ERROR 日誌，不建立用戶帳號
- Q: 為什麼需要靜默忽略？ → A: Portaly 可能將所有產品的 webhook 發送到同一端點，不相關產品會造成大量無意義的 ERROR 日誌
- Q: 如何判斷是否為相關產品？ → A: 檢查 webhook 的 `productId` 是否對應到資料庫中任一課程的 `portaly_product_id`

### Session 2026-01-30 (全站配色優化)

- Q: 全站配色方案？ → A: 採用統一的五色配置
  - `#F6F1E9` - 米白色（頁面背景）
  - `#FAA45E` - 橘色（強調元素、倒數計時數字）
  - `#FF4438` - 紅色（促銷價格、警示）
  - `#373557` - 深紫藍色（深色背景、主要文字）
  - `#3F83A3` - 藍綠色（連結、按鈕、次要強調）
- Q: 配色優化範圍？ → A: 全站所有頁面和組件，包括倒數計時器、按鈕、連結、背景等

### Session 2026-02-06 (Landing Page 模式)

- Q: Landing Page 模式如何啟用？ → A: 透過 URL 參數 `?lp=1` 啟用
- Q: Landing Page 模式隱藏哪些元素？ → A: 頂部導覽列（NavBar）和導航麵包屑（Breadcrumb）
- Q: Landing Page 模式的用途？ → A: 讓從外部連結（社群、廣告、Email）進入的訪客看到更乾淨的銷售頁面，提升轉換專注度
- Q: Landing Page 模式下登入功能如何處理？ → A: 訪客可透過購買區塊的「建議先登入」連結進入登入流程
- Q: Landing Page 模式下頁尾是否保留？ → A: 保留，法律政策連結（服務條款、隱私政策等）仍可正常使用
- Q: `?lp=1` 參數是否自動傳遞至其他頁面？ → A: 否，僅作用於當前頁面，跳轉至其他頁面時顯示正常導覽

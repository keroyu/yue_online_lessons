# Feature Specification: 數位課程販售平台 MVP

**Feature Branch**: `001-course-platform-mvp`
**Created**: 2026-01-16
**Status**: Draft
**Input**: User description: "數位內容販售平台 MVP，包含課程首頁、會員系統（email 驗證登入）、我的課程頁面、帳號設定頁面"
**Updated**: 2026-01-30 - 全站配色優化（統一 Color Scheme）
**Updated**: 2026-01-31 - Webhook 處理優化（靜默忽略不相關的 Portaly 產品）
**Updated**: 2026-03-01 - 隱藏課程自動精簡 UI（隱藏導覽列與麵包屑）
**Updated**: 2026-03-01 - 販售頁版面重設計（H1 移至影片上方、移除促銷區塊、h2 全寬深色標題）
**Updated**: 2026-03-01 - 課程資訊欄、價格標示優化（優惠價 NTD$）、按鈕樣式統一
**Updated**: 2026-03-08 - 課程縮圖統一改為 16:9 比例
**Updated**: 2026-03-09 - 新增課程 SEO 欄位（slug URL + meta_description）
**Updated**: 2026-03-09 - 販售頁新增「免費試閱」按鈕，未購買訪客可另開視窗體驗教室介面
**Updated**: 2026-03-09 - 我的課程頁面課程 card 等比例增大為約 500px 寬（最多 2 欄）
**Updated**: 2026-03-11 - 我的課程頁面新增未登入 client-side 防護，顯示「請先登入」提示
**Updated**: 2026-03-19 - 販售頁 h3 標題加入左側色塊裝飾樣式（10px 深色長方形 + 15px 間距）
**Updated**: 2026-03-22 - 販售頁新增懸浮購買面板（floating buy panel）：scroll 過頂部資訊區後從右側滑入，顯示價格、優惠倒數計時與購買按鈕；scroll 到底部購買區時自動收回
**Updated**: 2026-03-23 - 新增 PayUni 統一金流付費（portaly_product_id 空且 price > 0）與免費課程直接報名（portaly_product_id 空且 price = 0）
**Updated**: 2026-03-23 - PayUni 金流 debug 修正：MerTradeNo 縮短至 26 字元、NotifyURL 買家資訊改從 Cache 讀取、ReturnURL 移至 web 路由解決 auth/race condition、付款表單必填姓名電話、退款按鈕 HTTP method 修正

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
4. **Given** 未登入者因 Inertia SPA 瀏覽器快取看到頁面, **When** `auth.user` 為 null, **Then** 顯示「請先登入」提示與「前往登入」按鈕，而非「尚無課程」
5. **Given** 會員在桌機進入「我的課程」頁面, **When** 頁面載入完成, **Then** 課程 card 以最多 2 欄排列，每張 card 約 500px 寬，縮圖、文字、進度條等比例放大

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
10. **Given** 課程已設定 slug（如 `value-investing`）, **When** 訪客訪問 `/course/value-investing`, **Then** 正確顯示課程頁面（與 `/course/{id}` 相同內容）
11. **Given** 課程尚未設定 slug, **When** 訪客訪問 `/course/{id}`, **Then** 以數字 ID 正常訪問，不受影響
7. **Given** 訪客進入課程販售頁, **When** 頁面載入完成, **Then** H1 課程名稱顯示在影片/縮圖上方（米白背景、大粗體置中），影片縮圖限寬呈現（max-w-3xl），課程介紹中的 h2 標題以全寬深色方塊（黑底白字）呈現
8. **Given** 訪客進入課程販售頁, **When** 頁面載入完成, **Then** 影片下方顯示課程資訊欄（課程類型、預計時長、授課講師、觀看限制：不限時間次數），右側顯示價格（優惠價 NTD$XXX）與快速 scroll 至購買區按鈕
9. **Given** 訪客點擊影片下方的「立即購買」或「免費訂閱」按鈕, **When** 尚未勾選同意條款, **Then** 頁面自動 scroll 至下方購買區，引導用戶完成同意步驟
2. **Given** 未登入訪客在課程販售頁, **When** 查看購買區塊, **Then** 顯示提醒文字「購買課程之後，務必確認使用和 Portaly 下訂時相同的 Email 登入。如果您已註冊過本站，建議先登入。」
3. **Given** 已登入會員在課程販售頁, **When** 查看購買區塊, **Then** 顯示目前登入的 email，並提醒「請確認 Portaly 結帳時使用此 Email：{user.email}」
4. **Given** 訪客在課程販售頁, **When** 尚未勾選同意條款, **Then** 購買按鈕呈現禁用狀態
5. **Given** 訪客已勾選同意條款, **When** 點擊購買按鈕, **Then** 開啟新視窗連到該課程的 Portaly 產品頁（Portaly 表單會收集用戶資料）
6. **Given** 課程 `is_visible = false`（隱藏）, **When** 訪客進入課程販售頁, **Then** 頁面頂端導覽列與「返回課程列表」連結均隱藏
12. **Given** 課程有至少一個標記為「免費試閱」的小節（且非 drip 課程）, **When** 訪客進入課程販售頁, **Then** 購買按鈕旁顯示「免費試閱」按鈕（含播放 icon）
13. **Given** 訪客點擊「免費試閱」按鈕, **When** 按鈕被點擊, **Then** 另開新視窗進入試閱教室（`/course/{id}/preview`），不需登入
14. **Given** 訪客進入課程販售頁, **When** 頁面載入完成, **Then** 課程介紹中的 h3 標題以左側 10px 深色長方形色塊 + 15px 間距方式呈現，與文字垂直置中對齊
15. **Given** 訪客 scroll 過頂部課程資訊欄（section 3）, **When** 底部購買區尚不在畫面內, **Then** 右下角顯示懸浮購買面板（含價格、優惠倒數計時、免費試閱按鈕、立即購買按鈕），從右側以 slide-in 動畫進入
16. **Given** 懸浮購買面板可見, **When** 訪客 scroll 至底部購買區（同意條款區），**Then** 懸浮面板自動以 slide-out 動畫收回
17. **Given** 懸浮購買面板可見, **When** 訪客點擊「立即購買」，**Then** 若尚未勾選同意條款則自動 scroll 至底部購買區；若已勾選則直接開啟 Portaly 購買頁面

---

### User Story 7 - PayUni 統一金流付費 (Priority: P2)

當課程 `portaly_product_id` 為空且 `price > 0` 時，使用 PayUni 統一金流完成購買。已登入用戶使用帳號 email 發起付款；未登入用戶在 PayUni 填寫資料，付款完成後系統透過 NotifyURL 回調建立/更新帳號與購買紀錄。金額使用 `display_price`（優惠期間為優惠價，否則為原價）。

**Why this priority**: 允許課程不依賴 Portaly 販售，支援台灣本地金流管道（信用卡、ATM、超商）。

**Independent Test**: 可用 PayUni 沙箱帳號模擬付款流程與 NotifyURL 回調。

**Acceptance Scenarios**:

1. **Given** `portaly_product_id` 為空且 `price > 0`, **When** 訪客進入販售頁, **Then** 顯示「立即購買」按鈕，採用 PayUni 付費流程
2. **Given** 已登入用戶在販售頁, **When** 已勾選同意條款並點擊購買, **Then** 以帳號 email 發起 PayUni 付款（導向 PayUni 付款頁），姓名/電話從帳號資料預填
3. **Given** 未登入用戶在販售頁, **When** 已勾選同意條款並點擊購買, **Then** 頁面顯示 email / 姓名 / 電話必填欄位，填寫後發起 PayUni 付款
4. **Given** 課程在優惠期間, **When** 發起 PayUni 付款, **Then** 傳給 PayUni 的金額為優惠價（display_price）
5. **Given** 課程不在優惠期間, **When** 發起 PayUni 付款, **Then** 傳給 PayUni 的金額為原價
6. **Given** PayUni 付款成功, **When** PayUni 發送 NotifyURL 回調, **Then** 後端驗證並建立購買紀錄（source=payuni），用戶可在「我的課程」看到課程
7. **Given** 未登入用戶在 PayUni 完成付款, **When** NotifyURL 收到通知, **Then** 後端從 Cache 讀取買家資訊（email/姓名/電話，因 PayUni NotifyURL 不含買家資料），自動建立/更新用戶帳號
8. **Given** PayUni 付款完成, **When** 用戶被 ReturnURL 導回, **Then** redirect 到 `/member/learning`；若未登入則系統自動轉到登入頁並顯示提示「請用購買時的 email 登入查看課程」
9. **Given** PayUni 付款失敗, **When** 用戶被 ReturnURL 導回, **Then** redirect 回課程販售頁
10. **Given** 相同 MerTradeNo 已存在, **When** 收到重複 PayUni 通知, **Then** 冪等處理，不重複建立購買紀錄
11. **Given** 付款發起時, **When** 系統生成 MerTradeNo, **Then** 同時將買家 email/姓名/電話 存入 Cache（key: `payuni_order_{merTradeNo}`，TTL 2 小時），供 NotifyURL 回調時讀取
12. **Given** ReturnURL 瀏覽器重導先於 NotifyURL 伺服器回調到達, **When** 用戶被導回網站, **Then** ReturnURL handler 也執行購買紀錄建立（冪等），確保用戶立即看到課程
13. **Given** PayUni 付款表單, **When** 用戶填寫資料, **Then** 姓名和電話為必填欄位

---

### User Story 8 - 免費課程直接報名 (Priority: P2)

當課程 `portaly_product_id` 為空且 `price = 0` 時，販售頁提供免費報名入口。用戶點擊後頁面展開 inline 表單，填寫 email / 姓名 / 電話後直接建立購買紀錄，不通過任何金流。

**Why this priority**: 支援免費課程/贈品課程的自助報名，不需管理員手動指派。

**Independent Test**: 可直接送出表單測試，無需金流帳號。

**Acceptance Scenarios**:

1. **Given** `portaly_product_id` 為空且 `price = 0`, **When** 訪客進入販售頁, **Then** 顯示「免費報名」按鈕（不通過金流）
2. **Given** 訪客點擊免費報名按鈕, **When** 按鈕被點擊, **Then** 頁面向下展開 email / 姓名 / 電話 必填表單
3. **Given** 已登入用戶展開報名表單, **When** 表單顯示, **Then** 預填帳號的 email / 姓名 / 電話（均可修改）
4. **Given** 未登入用戶填寫完表單, **When** 點擊送出前, **Then** 顯示確認提示「請確認資料正確，email 將作為登入帳號」
5. **Given** 表單資料驗證通過, **When** 送出後後端建立購買紀錄, **Then** 頁面顯示成功訊息並提供前往「我的課程」的連結
6. **Given** 用戶已報名過此課程, **When** 重複提交表單, **Then** 冪等處理，回傳成功訊息但不重複建立紀錄
7. **Given** 表單填寫了新的姓名/電話, **When** 送出後, **Then** 用戶帳號的姓名/電話以最新填寫內容更新

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
- 課程設為隱藏（`is_visible = false`）時，課程販售頁自動精簡為無導覽列版本（與 `?lp=1` landing page 模式行為一致）
- drip 課程不顯示「免費試閱」按鈕，即使後台誤設了 `is_preview = true` 的小節
- drip 課程、草稿課程、無付款管道（portaly_product_id 空且 price = 0 且 is_free 未啟用）的課程不顯示懸浮購買面板
- 草稿課程（previewMode）不顯示「免費試閱」按鈕（避免草稿流出）
- 課程無任何 `is_preview = true` 小節時，`/course/{id}/preview` 不顯示 404，而是顯示「此課程目前沒有免費試閱內容」提示頁
- PayUni NotifyURL 回調簽章驗證失敗時，記錄 warning 但回傳 200（避免 PayUni 重試風暴）；不建立任何購買紀錄
- PayUni MerTradeNo 解析不到對應課程時，記錄 error 並回傳 200
- PayUni NotifyURL 回調不包含買家 email/姓名/電話，系統 MUST 在發起付款時將買家資訊存入 Cache（database driver），NotifyURL 從 Cache 讀取；Cache 過期時 fallback 到 payload 欄位
- PayUni MerTradeNo 格式為 `YC{courseId:04d}{YmdHis}{rand4}`（26 字元），PayUni 上限為 28 字元
- PayUni ReturnURL 為 web 路由（非 API 路由），排除 CSRF 驗證，以獲得 session/auth 支援；ReturnURL handler 同時執行 processNotify（冪等）以解決與 NotifyURL 的 race condition
- PayUni ReturnURL 驗證失敗時，已登入用戶仍導向 `/member/learning`（NotifyURL 負責真正驗證），未登入用戶導向 `/login?hint=payuni`，不導向首頁
- 免費報名表單 email 欄位與已登入帳號 email 不一致時，以用戶填寫的 email 為準（可用於更換聯絡 email 場景，但不更動登入 email）
- 免費報名表單的 email 若已存在其他帳號，以該帳號完成報名（合併報名意圖），並更新姓名/電話

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
- **FR-025**: 所有課程縮圖顯示區域 MUST 使用 16:9 比例（首頁課程卡、我的課程卡、課程販售頁）
- **FR-026**: 課程 SHOULD 支援 SEO 友善 URL slug（`/course/{slug}`），未設定時回退使用數字 ID（`/course/{id}`）
- **FR-027**: 課程 SHOULD 支援獨立的 `meta_description` 欄位（最多 160 字），搜尋結果描述優先使用 `meta_description`，其次 `tagline`；OG description 使用相同邏輯
- **FR-028**: Sitemap SHOULD 對已設定 slug 的課程輸出 slug URL，未設定則輸出 ID URL
- **FR-029**: 販售頁 MUST 對「非 drip、非草稿、至少有一個 `is_preview = true` 小節」的課程顯示「免費試閱」按鈕，並以 `target="_blank"` 另開新視窗連至 `/course/{id}/preview`
- **FR-030**: `/course/{id}/preview` 路由 MUST 為公開路由（不需登入），drip 課程存取該路由時回傳 404
- **FR-031**: 販售頁 MUST 在訪客 scroll 過頂部資訊欄且底部購買區不在視窗內時，顯示懸浮購買面板（右下角固定）；面板包含 PriceDisplay（含優惠倒數計時）、免費試閱按鈕（若有試閱小節）、立即購買按鈕；僅限非 drip、非草稿、且有付款管道（portaly_product_id 有值 或 price > 0）的課程顯示
- **FR-032**: 當課程 `portaly_product_id` 為空且 `price > 0` 時，系統 MUST 使用 PayUni 統一金流處理付費，並透過 NotifyURL 回調驗證並建立購買紀錄（`source=payuni`）；NotifyURL 回調失敗時依賴 PayUni 重試並記錄 error log，管理員可手動補建紀錄；退款由管理員透過後台手動觸發 API endpoint，更新購買紀錄 status=refunded
- **FR-033**: PayUni 付費金額 MUST 使用 `display_price`（優惠期間為優惠價，否則為原價），以整數 NTD 傳遞
- **FR-034**: 當課程 `portaly_product_id` 為空且 `price = 0` 時，販售頁 MUST 顯示免費報名按鈕，點擊後展開 inline 表單，收集 email / 姓名 / 電話後直接建立購買紀錄（不通過金流，`source=free`, `amount=0`）
- **FR-035**: 免費報名與 PayUni 付費 MUST 支援冪等處理（重複提交不重複建立購買紀錄）；已登入且已購買的用戶造訪販售頁時，購買按鈕 MUST 改為「前往學習」並導向 `/course/{id}/classroom`
- **FR-036**: 用戶透過免費報名或 PayUni NotifyURL 回調提交的姓名/電話 MUST 更新用戶帳號資料（以最新填寫內容為準）
- **FR-037**: PayUni 付款表單 MUST 要求填寫姓名和電話（必填欄位）；已登入用戶 MUST 從帳號資料預填姓名/電話
- **FR-038**: PayUni 付款發起時，系統 MUST 將買家 email/姓名/電話 存入 Cache（key: `payuni_order_{merTradeNo}`），供 NotifyURL 回調讀取（因 PayUni NotifyURL 不回傳買家資訊）
- **FR-039**: PayUni ReturnURL MUST 使用 web 路由（含 session middleware），以正確識別已登入用戶並導向 `/member/learning`
- **FR-040**: PayUni ReturnURL handler MUST 同時執行購買紀錄建立（冪等），以解決 ReturnURL 與 NotifyURL 的 race condition
- **FR-041**: Inertia shared auth props MUST 包含 `real_name` 和 `phone`，供前端表單預填

### Key Entities

- **User（會員）**: 代表平台用戶，包含 email、暱稱、真實姓名（選填）、電話（選填）、出生年月日、角色（管理員/編輯/一般會員）、建立時間、最後登入時間、最後登入 IP
- **Course（課程）**: 代表販售的數位內容，包含名稱、一句話簡介、完整介紹、價格、縮圖、教師資訊、類型（講座/迷你課/大型課程）、上架狀態、排序順序、Portaly 產品頁連結、Portaly productId
- **Purchase（購買紀錄）**: 代表會員與課程的購買關係，包含 Portaly 訂單編號（portaly_order_id）、PayUni 交易編號（payuni_trade_no）、購買時間、金額、幣別、折扣碼（選填）、折扣金額、付款狀態（已付款/已退款）、來源（portaly / payuni / free / system_assigned / gift）

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
- **SC-009**: PayUni 付款完成後 NotifyURL 回調在 10 秒內完成購買紀錄建立
- **SC-010**: 免費課程報名表單送出後在 2 秒內顯示成功訊息並可立即查看課程

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

### Session 2026-03-23 (PayUni 與免費報名)

- Q: PayUni 退款時，系統應如何處理退款流程？ → A: 後端提供管理員退款 API endpoint，更新購買紀錄 status=refunded（由管理員手動觸發，而非自動 webhook）
- Q: 已登入且已購買的用戶再次造訪 PayUni / 免費課程販售頁時，購買按鈕應顯示什麼？ → A: 按鈕改為「前往學習」，點擊直接跳轉到 `/course/{id}/classroom`
- Q: PayUni NotifyURL 回調失敗時的補救機制為何？ → A: 依賴 PayUni 重試機制，後端記錄 error log；管理員可在後台手動補建購買紀錄（不自動補款）
- Q: 未登入用戶完成 PayUni 付款後被 ReturnURL 導回，頁面應如何引導？ → A: Redirect 到 `/member/learning`；未登入時系統自動轉到登入頁，並顯示提示訊息「請用購買時的 email 登入查看課程」

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
- 課程 slug 若未設定，系統自動回退使用數字 ID（`resolveRouteBinding` fallback），舊有連結永不失效
- slug 格式限制：英文小寫、數字、連字號（regex: `/^[a-z0-9\-]+$/`），唯一性在資料庫層保證

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

### Session 2026-03-23 (PayUni 統一金流 + 免費課程報名)

- Q: 付款管道判斷邏輯？ → A: portaly_product_id 有值 → Portaly；portaly_product_id 空且 price > 0 → PayUni；portaly_product_id 空且 price = 0 → 免費報名
- Q: PayUni 付款金額如何決定？ → A: 使用 display_price（優惠期間為優惠價，否則為原價），以整數 NTD 傳遞
- Q: 免費報名時 email 與登入帳號不同怎麼辦？ → A: 以填寫的 email 對應帳號完成報名，姓名/電話更新到該帳號
- Q: PayUni MerTradeNo 格式？ → A: `YC{courseId:04d}{YmdHis}{rand4}`（26 字元，PayUni 上限 28），供 NotifyURL 回調解析 courseId；舊格式 `YUE-C{courseId}-...` 保留 backward-compatible 解析
- Q: PayUni ReturnURL 行為？ → A: 付款成功 redirect /member/learning；付款失敗 redirect /course/{id}
- Q: 免費報名未登入時的確認機制？ → A: 送出前顯示確認提示「請確認資料正確，email 將作為登入帳號」

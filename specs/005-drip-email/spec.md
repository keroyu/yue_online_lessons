# Feature Specification: Email 連鎖加溫系統 (Drip Email System)

**Feature Branch**: `005-drip-email`
**Created**: 2026-02-04
**Status**: Draft
**Input**: User description: "擴充現有課程系統，新增「連鎖課程」類型。當使用者訂閱後，系統會依照固定天數間隔，自動解鎖章節並發送 Email 通知。這是一個行銷漏斗，目標是導引客戶購買進階課程。"

## Clarifications

### Session 2026-02-05

- Q: 發信單位是「Chapter」還是「Lesson」？ → A: **Lesson 層級**。每個 Lesson 一封信，內容精簡易消化，更適合行銷加溫目的。
- Q: 是否需要為每個 Lesson 設定 release_day？ → A: **不需要**。解鎖日由 Lesson 排序和課程間隔天數自動計算：`解鎖日 = sort_order × drip_interval_days`（sort_order 從 0 開始）

### Session 2026-02-05 (促銷區塊)

- Q: 促銷區塊需要哪些欄位？ → A: **只需 2 個欄位**：`promo_delay_seconds`（延遲秒數）+ `promo_html`（自訂 HTML）。不需要建立多個欄位（如 promo_type、promo_title 等），自訂 HTML 可滿足所有需求。
- Q: 功能適用範圍？ → A: **所有課程**（standard + drip）
- Q: 每個 Lesson 可以有幾個促銷區塊？ → A: **1 個**
- Q: 達標後是否永久顯示？ → A: **是**，使用 localStorage 記錄，永久顯示

## User Scenarios & Testing *(mandatory)*

### User Story 1 - 訪客免費訂閱連鎖課程 (Priority: P1)

訪客在課程詳情頁看到一個免費的連鎖課程，輸入 Email 後收到驗證碼，驗證成功後系統自動為其建立會員帳號（若 Email 不存在）或登入現有帳號，並建立訂閱記錄。系統**立即**發送第一封歡迎信並解鎖第一個 Lesson。之後每天早上 9 點，系統檢查並發送應解鎖的 Lesson 通知。

**Why this priority**: 這是連鎖課程的核心價值主張——透過時間序列的內容釋放來培養潛在客戶。免費訂閱是最常見的入口點。

**Independent Test**: 可以透過建立一個測試連鎖課程，以訪客身份訂閱，驗證 Email 發送和 Lesson 解鎖是否正常運作。

**Acceptance Scenarios**:

1. **Given** 訪客在連鎖課程詳情頁，**When** 輸入新 Email 並完成驗證，**Then** 系統建立新會員帳號、建立訂閱記錄、**立即**發送歡迎信、解鎖第一個 Lesson
2. **Given** 訪客輸入的 Email 已存在於會員系統，**When** 完成驗證，**Then** 系統登入該會員帳號並建立訂閱記錄
3. **Given** 使用者已訂閱 3 天，課程設定間隔 3 天，**When** 隔天早上 9 點排程執行，**Then** 第二個 Lesson 解鎖並發送通知信
4. **Given** 使用者尚未到解鎖時間，**When** 進入教室頁面，**Then** 看到「X 天後解鎖」提示

---

### User Story 1.5 - 已登入會員一鍵訂閱 (Priority: P1)

已登入的會員在連鎖課程詳情頁看到「訂閱」按鈕，點擊後直接建立訂閱記錄，無需再次輸入 Email 或驗證。

**Why this priority**: 已有帳號的會員應該享有最簡便的訂閱體驗，這也是留住現有客戶的關鍵。

**Independent Test**: 以已登入會員身份點擊訂閱按鈕，驗證訂閱記錄立即建立。

**Acceptance Scenarios**:

1. **Given** 已登入會員在免費連鎖課程詳情頁，**When** 點擊「訂閱」按鈕，**Then** 直接建立訂閱記錄、**立即**發送歡迎信、解鎖第一個 Lesson
2. **Given** 已登入會員已訂閱過該課程，**When** 查看課程詳情頁，**Then** 顯示「已訂閱」狀態而非訂閱按鈕

---

### User Story 2 - 付費購買連鎖課程 (Priority: P2)

使用者透過現有的 Portaly 付款流程購買連鎖課程。付款成功後，系統建立訂閱記錄，開始連鎖發信流程。付費課程與免費訂閱的差異僅在於入口，後續的 Lesson 解鎖和 Email 發送邏輯相同。

**Why this priority**: 付費訂閱是變現的關鍵，但技術上是複用現有付款機制，因此排在免費訂閱之後。

**Independent Test**: 使用測試 Portaly webhook 模擬付款成功，驗證訂閱記錄建立和首封信發送。

**Acceptance Scenarios**:

1. **Given** 使用者在付費連鎖課程詳情頁，**When** 點擊購買並完成 Portaly 付款，**Then** 系統建立訂閱記錄並開始連鎖流程
2. **Given** Portaly webhook 回報付款成功，**When** 系統處理 webhook，**Then** 建立訂閱並立即發送歡迎信

---

### User Story 3 - 管理員設定連鎖課程 (Priority: P2)

管理員在後台建立或編輯課程時，可以將課程類型設為「連鎖課程」。設定後需指定發信間隔天數。管理員也可以設定一個或多個「目標課程」，當訂閱者購買任一目標課程時，自動停止發信。Lesson 的解鎖順序由現有的 sort_order 決定，解鎖日由系統自動計算。

**Why this priority**: 沒有後台設定，整個功能無法運作，但因為是管理端功能，與使用者入口同優先級。

**Independent Test**: 管理員可以建立一個完整的連鎖課程設定，並在前台看到正確的課程類型顯示。

**Acceptance Scenarios**:

1. **Given** 管理員在課程編輯頁，**When** 選擇課程類型為「連鎖課程」，**Then** 顯示連鎖課程專屬設定區塊
2. **Given** 管理員設定發信間隔為 3 天，**When** 儲存課程，**Then** 設定正確保存
3. **Given** 課程有 5 個 Lessons，間隔 3 天，**When** 查看前台，**Then** 系統自動計算解鎖日為 Day 0, 3, 6, 9, 12
4. **Given** 管理員設定目標課程為「進階課程 A」和「進階課程 B」，**When** 儲存，**Then** 設定正確保存

---

### User Story 4 - 購買目標課程後自動轉換 (Priority: P2)

訂閱者在收到連鎖信件期間購買了目標課程（任一個），系統自動將訂閱狀態標記為「已轉換」，停止後續發信，並**獎勵解鎖全部 Lesson 內容**。

**Why this priority**: 這是行銷漏斗的核心轉換機制，確保已購買的客戶不會繼續收到推銷信。

**Independent Test**: 模擬訂閱者購買目標課程，驗證狀態自動更新且停止發信。

**Acceptance Scenarios**:

1. **Given** 使用者訂閱了連鎖課程 A，目標課程為 X、Y、Z，**When** 購買課程 X，**Then** 訂閱狀態變為 converted、停止發信、**解鎖全部 Lesson**
2. **Given** 使用者已轉換，**When** 進入教室，**Then** 可以觀看連鎖課程的全部 Lesson 內容
3. **Given** 使用者購買的課程不在目標清單中，**When** Webhook 處理完成，**Then** 訂閱狀態不變，繼續發信

---

### User Story 5 - 使用者退訂連鎖課程 (Priority: P3)

使用者點擊 Email 中的退訂連結，進入退訂確認頁面。頁面顯示警告：「這是限期商品，一旦退訂將無法再次訂閱此課程」。使用者確認退訂後，停止接收後續 Email，但已解鎖的 Lesson 仍可觀看。

**Why this priority**: 退訂是必要功能（法規要求），但不影響核心價值流程。

**Independent Test**: 點擊退訂連結後驗證訂閱狀態更新，且不再收到後續 Email。

**Acceptance Scenarios**:

1. **Given** 使用者點擊 Email 中的退訂連結，**When** 進入退訂頁面，**Then** 顯示警告訊息和確認按鈕
2. **Given** 使用者確認退訂，**When** 系統處理退訂，**Then** 狀態變為 unsubscribed、停止後續發信
3. **Given** 使用者已退訂，**When** 進入教室，**Then** 仍可觀看已解鎖的 Lesson 內容（但不會再解鎖新 Lesson）

---

### User Story 6 - 在教室中觀看連鎖課程 (Priority: P1)

訂閱者進入連鎖課程的教室頁面，看到依照個人訂閱時間計算的 Lesson 解鎖狀態。已解鎖 Lesson 可正常觀看（文字內容和影片），未解鎖 Lesson 顯示倒數計時（「X 天後解鎖」）。

**Why this priority**: 這是使用者消費內容的核心介面，與訂閱流程同等重要。

**Independent Test**: 使用不同訂閱時間的帳號登入，驗證各自看到正確的解鎖狀態。

**Acceptance Scenarios**:

1. **Given** 課程有 5 個 Lessons、間隔 3 天，使用者訂閱第 5 天，**When** 進入教室，**Then** 看到前 2 個 Lesson 已解鎖（Day 0, 3）、第 3 個顯示「1 天後解鎖」
2. **Given** Lesson 已解鎖，**When** 點擊觀看，**Then** 正常顯示內容和影片
3. **Given** Lesson 未解鎖，**When** 嘗試存取，**Then** 顯示解鎖倒數而非內容

---

### User Story 7 - 管理員查看訂閱者清單 (Priority: P3)

管理員在後台可以查看特定連鎖課程的所有訂閱者清單，包含訂閱時間、目前進度（已寄出幾封信）、狀態（active/converted/completed/unsubscribed）等資訊。

**Why this priority**: 這是營運分析功能，對初期驗證不是必要的。

**Independent Test**: 在有訂閱者的連鎖課程後台，查看清單資料是否正確顯示。

**Acceptance Scenarios**:

1. **Given** 連鎖課程有 10 位訂閱者，**When** 管理員進入訂閱者清單，**Then** 顯示所有訂閱者及其狀態
2. **Given** 訂閱者清單，**When** 篩選「已轉換」，**Then** 只顯示購買目標課程的使用者

---

### User Story 8 - Lesson 延遲顯示促銷區塊 (Priority: P2)

使用者在觀看 Lesson 時，若該 Lesson 設定了促銷區塊，則在觀看指定分鐘數後才會顯示促銷內容（自訂 HTML，可包含購買課程按鈕、預約顧問連結等）。在達標之前，該區塊顯示「請先觀看課程」提示和倒數計時。達標後促銷區塊永久顯示（即使重新整理也不需再等待）。

**Why this priority**: 此功能透過延遲顯示建立價值感，並過濾出真正認真觀看的精準名單，是行銷漏斗的關鍵轉換元素。

**Independent Test**: 設定一個 Lesson 的促銷區塊延遲為 1 分鐘，觀看影片 1 分鐘後驗證促銷區塊是否出現。

**Acceptance Scenarios**:

1. **Given** Lesson 設定 `promo_delay_seconds = 300`，**When** 使用者剛進入 Lesson 頁面，**Then** 顯示「請先觀看課程」提示和「4:59」倒數計時
2. **Given** 使用者已觀看 300 秒，**When** 倒數歸零，**Then** 促銷區塊內容（自訂 HTML）顯示
3. **Given** 使用者已達標，**When** 重新整理頁面或隔天再訪，**Then** 促銷區塊直接顯示（無需再次等待）
4. **Given** Lesson 設定 `promo_delay_seconds = 0`，**When** 使用者進入 Lesson，**Then** 促銷區塊立即顯示
5. **Given** Lesson 未設定促銷區塊（`promo_delay_seconds = null`），**When** 使用者進入 Lesson，**Then** 不顯示任何促銷區塊

---

### User Story 9 - 管理員設定 Lesson 促銷區塊 (Priority: P2)

管理員在後台編輯 Lesson 時，可以設定促銷區塊的延遲時間和自訂 HTML 內容。此功能適用於所有課程類型（standard 和 drip）。

**Why this priority**: 沒有後台設定，促銷區塊功能無法運作，與使用者端功能同優先級。

**Independent Test**: 管理員在 Lesson 編輯頁設定促銷區塊，儲存後前台顯示正確。

**Acceptance Scenarios**:

1. **Given** 管理員在 Lesson 編輯頁，**When** 輸入延遲秒數和 HTML 內容並儲存，**Then** 設定正確保存
2. **Given** 管理員清空延遲秒數，**When** 儲存，**Then** 促銷區塊功能停用
3. **Given** 管理員輸入 HTML 包含按鈕和連結，**When** 前台顯示，**Then** HTML 正確渲染且連結可點擊

---

### Edge Cases

- **重複訂閱**：已退訂的使用者嘗試再次訂閱同一課程 → 顯示「此課程已無法再次訂閱」訊息
- **排程時間重疊**：系統在同一時間需處理大量發信 → 使用佇列確保不漏發
- **Email 發送失敗**：Resend API 回報錯誤 → 記錄失敗、重試機制（最多 3 次）
- **使用者刪除帳號**：使用者刪除帳號後 → 停止發信、清理訂閱記錄
- **課程被下架**：管理員將連鎖課程下架 → 已訂閱者仍可觀看已解鎖內容，但停止新訂閱和後續發信
- **目標課程未設定**：連鎖課程沒有設定目標課程 → 正常發信直到 completed，不會自動轉換
- **新增 Lesson**：管理員在已有訂閱者時新增 Lesson → 現有訂閱者會在排程時收到新 Lesson（若符合解鎖條件）
- **促銷區塊計時中斷**：使用者觀看中途離開頁面 → 累積時間保存於 localStorage，下次繼續計時
- **促銷區塊 HTML 為空**：設定了延遲時間但 HTML 為空 → 不顯示促銷區塊（視為未啟用）
- **促銷區塊 XSS 風險**：管理員輸入惡意腳本 → 因只有管理員可編輯，視為可信內容，但建議限制在 iframe sandbox 內

## Requirements *(mandatory)*

### Functional Requirements

**課程擴充**
- **FR-001**: 系統 MUST 支援課程類型區分：「一般課程」(standard) 和「連鎖課程」(drip)
- **FR-002**: 連鎖課程 MUST 可設定發信間隔天數（drip_interval_days）
- **FR-003**: 連鎖課程 MAY 設定一個或多個目標課程（購買任一個即觸發轉換）

**Lesson 解鎖計算（自動）**
- **FR-004**: 系統 MUST 使用公式自動計算每個 Lesson 的解鎖日：`解鎖日 = Lesson.sort_order × Course.drip_interval_days`（sort_order 從 0 開始）
- **FR-005**: 系統 MUST 根據 `subscribed_at + 解鎖日` 判斷該 Lesson 是否已對該訂閱者解鎖

**訂閱機制（統一會員管理）**
- **FR-006**: 系統 MUST 支援訪客免費訂閱流程（Email + 驗證碼）
- **FR-006a**: 訪客訂閱時，若 Email 不存在於會員系統，MUST 自動建立新會員帳號（僅需 email，nickname 可為空）
- **FR-006b**: 訪客訂閱時，若 Email 已存在於會員系統，MUST 登入該現有帳號
- **FR-006c**: 已登入會員 MUST 可一鍵訂閱，無需重新輸入 Email 或驗證
- **FR-007**: 系統 MUST 支援付費訂閱流程（複用現有 Portaly 付款）
- **FR-008**: 系統 MUST 為每位訂閱者記錄獨立的訂閱起始時間（subscribed_at）和已寄信數（emails_sent）
- **FR-009**: 系統 MUST 防止已退訂使用者再次訂閱同一課程

**教室頁面**
- **FR-010**: 教室頁面 MUST 對未解鎖 Lesson 顯示「X 天後解鎖」倒數，且標題 MUST 隱藏為「******」
- **FR-011**: 系統 MUST 阻止使用者存取未解鎖的 Lesson 內容

**Email 發送**
- **FR-012**: 第一封歡迎信 MUST 在訂閱完成後**立即同步發送**（不經由佇列）
- **FR-013**: 後續通知信 MUST 由每天早上 9 點的排程任務發送
- **FR-014**: 排程任務 MUST 比較 emails_sent 和應解鎖 Lesson 數，發送差額的信件
- **FR-015**: 每封 Email MUST 包含：Lesson 標題、Lesson 全文內容（html_content）、連回網站的連結、退訂連結。若 Lesson 包含影片，MUST 顯示提示「本課程包含教學影片，請至網站觀看」。若 Lesson 無文字內容（純影片），MUST 顯示預設文案引導使用者前往網站。
- **FR-016**: 當 emails_sent 等於課程總 Lesson 數時，MUST 將狀態標記為 completed

**轉換機制**
- **FR-017**: 當訂閱者購買任一目標課程時，系統 MUST 自動將狀態更新為 converted
- **FR-018**: 轉換後 MUST 停止發送後續 Email
- **FR-019**: 轉換後 MUST 解鎖該連鎖課程的全部 Lesson（獎勵）

**退訂機制**
- **FR-020**: 退訂連結 MUST 帶有安全 token 驗證身份
- **FR-021**: 退訂確認頁 MUST 顯示警告：「這是限期商品，一旦退訂將無法再次訂閱此課程」
- **FR-022**: 退訂後 MUST 停止發送後續 Email
- **FR-023**: 退訂後 MUST 保留已解鎖 Lesson 的觀看權限

**後台管理**
- **FR-024**: 管理員 MUST 可在課程編輯頁設定連鎖課程參數（間隔天數、目標課程）
- **FR-025**: 管理員 MUST 可查看訂閱者清單和狀態
- **FR-026**: 管理員 MUST 可透過調整 Lesson 的 sort_order 來控制發送順序

**Lesson 促銷區塊（適用所有課程）**
- **FR-027**: 每個 Lesson MAY 設定促銷區塊延遲時間（promo_delay_seconds）
- **FR-028**: 每個 Lesson MAY 設定促銷區塊自訂 HTML 內容（promo_html）
- **FR-029**: 當 promo_delay_seconds 為 null 或 promo_html 為空時，MUST 不顯示促銷區塊
- **FR-030**: 當 promo_delay_seconds = 0 時，MUST 立即顯示促銷區塊
- **FR-031**: 當 promo_delay_seconds > 0 時，MUST 顯示倒數計時，達標後才顯示促銷內容
- **FR-032**: 系統 MUST 追蹤使用者觀看時間（累積計算，支援中斷後繼續）
- **FR-033**: 使用者達標後，系統 MUST 永久記錄（使用本地儲存），再次訪問時直接顯示
- **FR-034**: 管理員 MUST 可在 Lesson 編輯頁設定促銷區塊（延遲時間 + HTML）

### Key Entities

- **User（現有，維持不變）**: 統一的客戶名單。訪客訂閱時自動建立帳號（僅需 email，nickname 可為空）。所有訂閱者都是 User。
- **Course（擴充）**: 新增 course_type（standard/drip）、drip_interval_days 屬性
- **Lesson（現有，維持不變）**: 使用現有的 sort_order 欄位決定發送順序。不需要新增 release_day。
- **DripConversionTarget（新增）**: 記錄連鎖課程與目標課程的關聯（一對多）。包含 drip_course_id、target_course_id
- **DripSubscription（新增）**: 記錄使用者對連鎖課程的訂閱。包含：
  - user_id（必填，外鍵指向 users.id）
  - course_id（連鎖課程）
  - subscribed_at（訂閱時間）
  - emails_sent（已寄出幾封信，預設 0）
  - status（active / converted / completed / unsubscribed）
  - status_changed_at（狀態變更時間）
  - unsubscribe_token（退訂連結用）
- **Lesson（擴充）**: 新增促銷區塊相關屬性
  - promo_delay_seconds（延遲秒數，null=停用、0=立即、>0=延遲）
  - promo_html（自訂 HTML 內容）

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 使用者可在 1 分鐘內完成免費訂閱流程（從輸入 Email 到收到歡迎信）
- **SC-002**: 每日 9 點排程任務在 10 分鐘內完成所有訂閱者的檢查和發信
- **SC-003**: 99% 的通知 Email 成功送達（不含退訂者和已轉換者）
- **SC-004**: 管理員可在 5 分鐘內完成一個連鎖課程的完整設定
- **SC-005**: 退訂流程在 3 次點擊內完成（點擊連結 → 確認 → 完成）
- **SC-006**: 購買目標課程後，轉換狀態在 1 分鐘內更新
- **SC-007**: 促銷區塊在達標後 1 秒內顯示
- **SC-008**: 促銷區塊達標狀態在重新整理後仍保持（永久顯示）

## Assumptions

- 現有的 Resend.com Email 服務已正確設定且可用
- 現有的排程系統（Laravel Scheduler）已在生產環境運作
- Portaly webhook 整合已穩定運作
- 連鎖課程不使用 Chapter 層級，直接以 Lesson 為發信單位
- 所有 Lesson 在開放訂閱前已建立完成（可事後新增）

## Migration Notes

- **現有課程預設值**：Migration 執行時，所有現有課程的 `course_type` 必須設為 `'standard'`，確保向後相容

## Design Decisions

- **統一會員管理**：所有連鎖課程訂閱者都是 `users` 表中的會員。不另建獨立的訂閱者 Email 名單。
  - 訪客訂閱時自動建立 User 帳號（僅需 email）
  - 已登入會員可一鍵訂閱
  - 後台管理時只需維護一份客戶名單
  - 可與現有的批次發信、課程贈送等功能無縫整合

- **行銷漏斗轉換機制**：連鎖課程可設定多個目標課程，購買任一個即視為轉換成功。
  - 轉換後停止發信，避免打擾已購買客戶
  - 轉換後獎勵解鎖全部 Lesson，讓購買者獲得更多價值
  - 訂閱狀態區分：active（發信中）、converted（已購買目標課程）、completed（收完全部信但未購買）、unsubscribed（手動退訂）

- **發信時機**：
  - 第一封歡迎信：訂閱完成後立即發送
  - 後續信件：每天早上 9 點排程檢查並發送

- **進度追蹤簡化**：使用 `emails_sent` 欄位記錄已寄出幾封信，不另建發信記錄表。
  - 排程邏輯：比較 emails_sent 和應解鎖 Lesson 數，發送差額
  - 當 emails_sent == 總 Lesson 數 → status = completed

- **Lesson 層級發信**：每個 Lesson 對應一封 Email，內容精簡易消化。
  - 適合行銷加溫目的：建立連結而非一次給太多內容
  - 讀者 2-3 分鐘看完，期待下一封
  - 連鎖課程不使用 Chapter 層級（扁平結構）

- **自動計算解鎖日**：不需要為每個 Lesson 設定 release_day。
  - 公式：`解鎖日 = sort_order × drip_interval_days`（sort_order 從 0 開始）
  - 管理員只需設定課程的間隔天數和調整 Lesson 排序
  - 簡化後台操作，減少設定錯誤

- **Lesson 促銷區塊**：在 Lesson 內可建立延遲顯示的促銷區塊。
  - 目的：建立價值感和過濾精準名單
  - 只需 2 個欄位：`promo_delay_seconds` + `promo_html`
  - 使用自訂 HTML 滿足所有需求（購買課程、預約顧問等）
  - 適用於所有課程類型（standard + drip）
  - 達標後永久顯示（localStorage 記錄）
  - 延遲時間 null=停用、0=立即、>0=延遲

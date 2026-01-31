# Specification Quality Checklist: 上課頁面與管理員後臺

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-01-17
**Updated**: 2026-01-18 - Added lesson completion throttling mechanism
**Updated**: 2026-01-26 - Added admin course ownership auto-assign and frontend preview
**Updated**: 2026-01-30 - Added course visibility (show/hide) setting
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

- All items pass validation
- Specification is ready for `/speckit.clarify` or `/speckit.plan`
- Key decisions documented in Clarifications section:
  - 小節完成判定：前端樂觀更新，5 分鐘後寫入伺服器
  - 節流機制：純前端實作，使用 setTimeout
  - 狀態切換機制：排程每分鐘檢查
  - 圖片大小限制：10MB
  - 優惠價/原價命名：price 為優惠價，original_price 為原價
  - 優惠到期預設值：建立後 30 天
  - 倒數計時更新頻率：每秒（HH:MM:SS 格式）
  - 圖片尺寸單位：像素 (px)

## Update Summary (2026-01-17)

### New Features Added:
1. **優惠價/原價定價模式**
   - 課程支援優惠價 (price) 和原價 (original_price)
   - 可設定優惠到期時間 (promo_ends_at)，預設 30 天
   - 前端販售頁顯示倒數計時（優惠剩餘 X 天 HH:MM:SS，每秒更新）
   - 優惠到期後自動切換顯示原價

2. **同頁插入圖片功能**
   - 在課程介紹編輯頁點擊「插入圖片」開啟 Modal
   - Modal 內可瀏覽相簿、上傳新圖片、刪除圖片
   - 選擇圖片後可設定寬度/高度（支援單填一項自適應）
   - 確認後插入 HTML img 標籤至編輯器游標位置

### New Requirements:
- FR-011a, FR-021a, FR-021b, FR-023 ~ FR-026

### New Success Criteria:
- SC-008, SC-009, SC-010

## Update Summary (2026-01-17 - 法律政策頁面)

### New Features Added:
3. **法律政策頁面 Modal (US6)**
   - 「服務條款」「購買須知」「隱私政策」三個頁面
   - 以 Modal 形式開啟，不需導航至新頁面
   - 頁尾包含連結，可從任意頁面存取
   - RWD 支援，手機版內容可滾動

4. **退款政策（包含於購買須知）**
   - 「迷你課」和「講座」類型恕不退款
   - 大型課退款需在 14 日內提出
   - 課程完成度超過 20% 恕不退款

### New Requirements:
- FR-028 ~ FR-035

### New Success Criteria:
- SC-011, SC-012

### New Clarifications:
- 法律政策內容為靜態 HTML，直接寫在前端元件
- 課程完成度 = 已完成小節數 / 總小節數 × 100%
- 14 日從購買付款完成時間起算
- 退款流程不在本系統處理，僅提供政策說明

## Update Summary (2026-01-18 - 課程完成狀態節流機制)

### New Features Added:
5. **課程完成狀態節流機制 (US1a)**
   - 避免會員頻繁點選章節時產生過多伺服器請求
   - 前端立即顯示綠色勾勾（樂觀更新）
   - 實際完成紀錄需停留該小節滿 5 分鐘後才寫入伺服器
   - 5 分鐘內切換至其他小節則不記錄完成
   - 取消完成操作立即發送，不受節流限制

### Modified Requirements:
- FR-006: 改為「前端立即顯示綠色勾勾（樂觀更新）」

### New Requirements:
- FR-006b ~ FR-006e（節流機制相關）

### Modified Success Criteria:
- SC-003: 更新為「前端即時顯示，伺服器寫入需等待 5 分鐘」

### New Success Criteria:
- SC-003a, SC-003b（節流機制驗證）

### New Edge Cases:
- 5 分鐘計時中網路斷線的處理
- 快速來回點擊同一小節的計時器重置
- 關閉瀏覽器分頁時未達門檻的處理
- 頁面重新載入時從伺服器獲取真實狀態
- 已完成小節不重複發送請求

### New Clarifications:
- 5 分鐘門檻確保會員實際閱讀/觀看內容
- 計時器為純前端實作（JavaScript setTimeout）
- 取消完成立即發送以保持資料一致性

## Update Summary (2026-01-26 - 課程擁有權自動指派與管理員預覽)

### New Features Added:
6. **課程建立時自動指派擁有權給管理員 (US7)**
   - 管理員新增課程時，系統自動建立購買紀錄
   - 購買紀錄標記為「system_assigned」類型，金額 $0
   - 確保管理員可立即存取自己建立的課程
   - 在「我的課程」頁面可看到該課程

7. **管理員前端預覽所有課程（含草稿）(US8)**
   - 管理員登入後，首頁可看到所有課程（含草稿）
   - 草稿課程顯示明顯的「草稿」標籤
   - 管理員可進入草稿課程的販售頁預覽
   - 草稿販售頁顯示「預覽模式」提示
   - 一般會員無法看到或存取草稿課程

### New Requirements:
- FR-036 ~ FR-047（課程擁有權自動指派與管理員預覽）

### New Success Criteria:
- SC-013: 管理員建立課程後 5 秒內於「我的課程」看到
- SC-014: 管理員在首頁可正確區分草稿和已發佈課程
- SC-015: 管理員完成建立到預覽流程在 2 分鐘內

### New Edge Cases:
- 課程建立失敗時不建立購買紀錄
- 課程刪除時自動指派的購買紀錄一併移除
- 系統指派購買紀錄不計入銷售統計
- 草稿課程購買按鈕點擊時顯示提示
- 多位管理員各自只獲得自己建立的課程擁有權

### New Clarifications:
- 購買類型：paid、system_assigned、gift
- 系統指派紀錄顯示金額 $0
- 草稿標籤為灰色，預購中黃色，熱賣中綠色
- 預覽模式提示使用藍色固定橫幅

### Key Entities Updated:
- Purchase（購買紀錄）：新增 type 欄位支援 system_assigned

## Update Summary (2026-01-30 - 課程顯示/隱藏設定)

### New Features Added:
8. **課程顯示/隱藏設定 (US9)**
   - 管理員可為每個課程設定「是否顯示」開關
   - 隱藏課程不出現在首頁課程列表
   - 隱藏課程仍可透過直接 URL 存取和購買
   - 購買後正常顯示在「我的課程」頁面
   - 適用於私人課程、限定會員優惠等場景

### New Requirements:
- FR-048 ~ FR-057（課程顯示/隱藏設定）

### New Success Criteria:
- SC-016: 隱藏課程透過 URL 存取載入時間 3 秒內
- SC-017: 管理員 5 秒內完成顯示/隱藏狀態切換

### New Edge Cases:
- 隱藏課程販售頁功能與顯示課程相同
- 隱藏課程若同時為草稿，草稿限制優先
- 用戶已購買的隱藏課程正常顯示在「我的課程」
- 管理員可在首頁看到隱藏課程（有標籤）
- 管理員後臺可看到課程顯示狀態

### New Clarifications:
- 功能用途：私人課程、限定會員、測試課程
- 隱藏不影響購買功能
- 預設值為顯示（is_visible=true）
- 隱藏標籤樣式為灰色斜體文字

### Key Entities Updated:
- Course（課程）：新增 is_visible 欄位（布林值，預設 true）

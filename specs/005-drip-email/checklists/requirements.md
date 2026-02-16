# Specification Quality Checklist: Email 連鎖加溫系統

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-02-04
**Updated**: 2026-02-16 (新增 Drip 影片免費觀看期限)
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

- All checklist items passed validation
- Spec is ready for `/speckit.plan`
- 2026-02-16: 新增 Drip 影片免費觀看期限（US10, FR-035~042, SC-009~011），所有項目通過驗證

## User Decisions Captured

1. **方案 A**：擴充現有 Course 模型（不建立獨立系統）
2. **統一會員管理**：所有訂閱者都是 `users` 表中的會員，不另建獨立名單
3. **退訂警告**：退訂後不可再次訂閱
4. **行銷漏斗**：
   - 可設定多個目標課程（一對多）
   - 購買任一目標課程 → 狀態變為 converted
   - 轉換後停止發信 + 獎勵解鎖全部 Lesson
5. **發信時機**：
   - 第一封：訂閱後立即發送
   - 後續：每天早上 9 點排程
6. **進度追蹤簡化**：使用 `emails_sent` 欄位，不建立 drip_email_logs 表
7. **訂閱狀態**：active / converted / completed / unsubscribed

## Clarifications (2026-02-05)

8. **Lesson 層級發信**：每個 Lesson 一封 Email，內容精簡易消化，適合行銷加溫
9. **自動計算解鎖日**：不需要 release_day 欄位
   - 公式：`解鎖日 = sort_order × drip_interval_days`（sort_order 從 0 開始）
   - 管理員只需設定間隔天數和調整 Lesson 排序
10. **連鎖課程不使用 Chapter 層級**：扁平結構，直接以 Lesson 為單位

## Clarifications (2026-02-05 - 促銷區塊)

11. **Lesson 促銷區塊**：只需 2 個欄位
    - `promo_delay_seconds`（秒）：null=停用、0=立即、>0=延遲
    - `promo_html`：自訂 HTML 內容
12. **適用範圍**：所有課程類型（standard + drip）
13. **每個 Lesson 一個促銷區塊**
14. **達標後永久顯示**：使用 localStorage 記錄

## Clarifications (2026-02-16 - 影片免費觀看期限)

15. **方案 A：軟性提醒**：過期後影片不鎖定，僅顯示加強促銷區塊
16. **Config 設定**：免費觀看期寫在 config 檔案（非 DB），全站統一，預設 48 小時
17. **converted 使用者豁免**：已轉換的使用者不顯示過期促銷區塊
18. **僅限 drip 課程**：standard 課程不受影響
19. **僅限有影片的 Lesson**：純文字 Lesson 不顯示觀看期限 UI

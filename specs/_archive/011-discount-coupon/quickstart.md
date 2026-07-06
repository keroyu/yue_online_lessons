# Quickstart: 折扣碼管理系統

**Feature Branch**: `011-discount-coupon`
**Created**: 2026-06-09

開發者落地順序與驗收路徑。依賴方向：DB → Model → Service → Controller/Route → Frontend。

---

## 1. 資料層

```bash
# 建立 migration
php artisan make:migration create_coupon_codes_table
php artisan make:migration add_discount_columns_to_orders_table
```

- `coupon_codes`：見 [data-model.md](./data-model.md#coupon_codes)（含 SoftDeletes、`UNIQUE(code)`、`course_id` FK CASCADE）。
- `orders`：新增 `coupon_code`、`original_amount`、`discount_amount`。
- `purchases`：無 migration，確認 `Purchase::$fillable` 含 `coupon_code`、`discount_amount`。

```bash
php artisan migrate
```

## 2. Model

- 新增 `app/Models/CouponCode.php`（SoftDeletes、`saving` 轉大寫、`scopeActive`、`isExpired/isExhausted/isSiteWide`、`course()`）。
- `Order.php`：`$fillable` + `casts()` 加 3 欄。
- `Purchase.php`：確認 `$fillable`。

## 3. Service

- 新增 `app/Services/CouponService.php`：`validateForCart`、`calculateDiscount`、`label`、`redeem`、`stats`。
- 改 `app/Services/CheckoutService.php`：`createOrder()` 加 `?string $couponCode = null`；`fulfillOrder()` 兌現 + 寫 purchases 折扣欄。

## 4. Controller + Route + Form Request

- 新增 `app/Http/Controllers/CouponController.php`（`apply`，含 RateLimiter）。
- 新增 `app/Http/Controllers/Admin/CouponController.php`（resource + `show` + `toggle`）。
- 新增 `app/Http/Requests/Admin/StoreCouponRequest.php`、`UpdateCouponRequest.php`。
- 改 `app/Http/Requests/CheckoutRequest.php`：加 `coupon_code` nullable。
- 改 `routes/web.php`：見 [contracts/api.md](./contracts/api.md#路由名稱總覽)。

## 5. Frontend

- 新增 `resources/js/Components/Cart/CouponInput.vue`（輸入/套用/錯誤/已套用+移除；emit `applied`/`removed`）。
- 新增 `resources/js/Components/Admin/CouponForm.vue`（新增/編輯共用）。
- 改 `resources/js/Pages/Cart/Index.vue`（嵌入 CouponInput、折扣行、合計更新；「前往結帳」帶 `?coupon=`）。
- 改 `resources/js/Pages/Checkout/Index.vue`（折扣摘要、initiate 帶 `coupon_code`）。
- 新增 `resources/js/Pages/Admin/Coupons/{Index,Create,Edit,Show}.vue`。
- 後台側欄（`AdminLayout.vue`）加「折扣碼」連結。

## 6. Build & Test

```bash
npm run dev          # 或 npm run build
php artisan test
```

---

## 驗收路徑（對應 spec User Stories）

### US2 — 後台建立（先做，否則無碼可測）
1. `/admin/coupons` → 新增 → code `TEST6`、type fixed、value 100、全站通用、不設期限/名額 → 儲存。
2. 列表出現 `TEST6`，狀態啟用、已用 0。
3. 試建 value 5 → 應擋下「最低折抵金額為 NT$10」。
4. 試建 type ratio、value 0.3 → 應擋下「折數須介於 0.50 至 0.95 之間」。
5. 試建重複 code `TEST6` → 「此代碼已存在」。

### US1 — 購物車套用
6. 加一門 NT$1000 課程入購物車 → 套用 `TEST6` → 折扣行 `-NT$100`、合計 `NT$900`。
7. 套用 ratio 碼（0.6）→ 顯示「六折優惠」、`-NT$400`、合計 `NT$600`。
8. 移除 → 恢復 `NT$1000`。
9. 輸入不存在碼 → 「折扣碼無效」。
10. 連續輸錯 5 次 → 第 6 次回節流訊息（等 60 秒）。

### US3 — 結帳生效
11. 套用名額=1 的碼 → 結帳完成付款（sandbox）→ 訂單 `original_amount`、`discount_amount`、`total_amount` 正確；`coupon_codes.used_count = 1`。
12. 同碼再次結帳 → 「折扣碼已達使用上限」。
13. 中途付款失敗 → `used_count` 不變。

### US4 — 統計
14. 用某碼完成 2 筆訂單 → `/admin/coupons/{id}` → 筆數 2、營收=折後合計、總折抵正確；明細列出 email/付款時間/折後金額/原價。
15. 切換 7/30/60/90/全部 → 數據即時更新；「全部」涵蓋所有期間。
16. 軟刪除一個碼 → 列表消失；歷史訂單 `coupon_code` 與統計金額完整保留；相同 code 不可重建。

### US5 — 分享連結自動帶入
17. 以 `/courses/{課程}?coupon=TEST6` 開啟銷售頁 → 加入購物車 → 進購物車頁 → TEST6 自動顯示「已套用」、折扣正確。
18. 自動帶入後手動套用另一碼 → 以手動者為準。
19. 以無效碼 `/courses/{課程}?coupon=NOPE` 進站 → 購物車不顯示錯誤、維持原價（靜默忽略）。
20. 移除自動帶入碼 → 重整購物車不再自動套用；訂單完成後 session 已清除。

---

## 邊界檢查清單

- [ ] fixed 折後 < NT$1 → 拒絕「折扣金額超過訂單上限，無法套用」。
- [ ] ratio 計算四捨五入至整數元（`(int) round()`）。
- [ ] 大小寫不敏感（`test6` == `TEST6`）。
- [ ] 限定課程碼 + 購物車無該課程 → 「此折扣碼不適用於購物車中的課程」。
- [ ] 已套用碼後移除限定課程 → 前端自動清除折扣 + 提示。
- [ ] guest 結帳套用折扣 → 與登入相同行為。
- [ ] 並發名額超發 → 付款已完成仍正常開通（軟限制，不退款）。

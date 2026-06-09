# API Contracts: 011-discount-coupon

**Feature Branch**: `011-discount-coupon`
**Created**: 2026-06-09

所有路由新增於 `routes/web.php`。前台套用端點放既有 `api` 群組（公開，支援 guest）；後台放 `['auth','admin']` 群組，採 `Route::resource`。

---

## 前台：套用折扣碼（公開）

### `POST /api/cart/apply-coupon`

加入既有 `Route::prefix('api')->name('api.')` 群組（與 checkout 同層，**不**在 auth 子群組內，支援 guest 結帳）。

**Controller**: `CouponController@apply`（`App\Http\Controllers\CouponController`）

**Request body**:
```json
{
  "code": "ABC123",
  "course_ids": [12, 15]
}
```

| 欄位 | 規則 |
|------|------|
| code | required, string, max:6 |
| course_ids | required, array, min:1 |
| course_ids.* | integer, exists:courses,id |

> `subtotal` 由伺服器依 `course_ids` 的 `display_price` 重新計算，**不**信任前端傳入金額。

**回應（成功 200）**:
```json
{
  "success": true,
  "code": "ABC123",
  "type": "ratio",
  "label": "六折優惠",
  "discount": 400,
  "original": 1000,
  "payable": 600
}
```

**回應（驗證失敗 422）**:
```json
{ "success": false, "message": "折扣碼已過期" }
```

**回應（節流 429，FR-019）**:
```json
{ "message": "嘗試次數過多，請於 60 秒後再試" }
```

**Rate limiting（FR-019）**: key = `coupon-apply:{ip}`。`tooManyAttempts(key,5)` → 429；驗證失敗 `hit(key,60)`；成功 `clear(key)`。

---

## 結帳整合（既有端點擴充）

### `GET /checkout`（既有，擴充）

`CheckoutController@show`。新增可選 query `?coupon=ABC123`：
- 若存在 → `CouponService::validateForCart()` 重驗。通過則塑形 `coupon` prop；失敗則 `coupon = null`（靜默忽略，顯示原價）。

### `POST /api/checkout/initiate`（既有，擴充）

`CheckoutController@initiate` + `CheckoutRequest`。body 新增可選欄位：

| 欄位 | 規則 |
|------|------|
| coupon_code | nullable, string, max:6 |

`initiate()` 將 `coupon_code` 傳入 `CheckoutService::createOrder(..., couponCode: $couponCode)`。若折扣碼於此刻失效 → service 拋 `RuntimeException` → controller catch 回 **409**（沿用既有 duplicate-purchase 模式，FR-012）。

```json
// 409 範例
{ "message": "折扣碼已達使用上限" }
```

---

## 後台：折扣碼管理（admin）

加入 `Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')` 群組：

```php
Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class)
    ->except(['show']);
Route::get('/coupons/{coupon}', [\App\Http\Controllers\Admin\CouponController::class, 'show'])
    ->name('coupons.show');                       // 統計頁
Route::patch('/coupons/{coupon}/toggle', [\App\Http\Controllers\Admin\CouponController::class, 'toggle'])
    ->name('coupons.toggle');
```

> 註：`show` 用於統計頁。`{coupon}` 路由模型綁定預設排除 soft-deleted（符合刪除後不可存取）。

**Controller**: `App\Http\Controllers\Admin\CouponController`

| 動作 | 路由 | 回傳 | 說明 |
|------|------|------|------|
| `index` | `GET /admin/coupons` | Inertia `Admin/Coupons/Index` | 列表（未刪除），含 `used_count`、狀態 |
| `create` | `GET /admin/coupons/create` | Inertia `Admin/Coupons/Create` | 表單（課程下拉） |
| `store` | `POST /admin/coupons` | redirect `coupons.index` + flash | `StoreCouponRequest` |
| `edit` | `GET /admin/coupons/{coupon}/edit` | Inertia `Admin/Coupons/Edit` | 編輯表單 |
| `update` | `PUT /admin/coupons/{coupon}` | redirect + flash | `UpdateCouponRequest` |
| `destroy` | `DELETE /admin/coupons/{coupon}` | redirect + flash | 軟刪除（`$coupon->delete()`） |
| `show` | `GET /admin/coupons/{coupon}?range=30` | Inertia `Admin/Coupons/Show` | 統計（`range` ∈ `7\|30\|60\|90\|all`，預設 30；`all` → `stats($coupon, null)`） |
| `toggle` | `PATCH /admin/coupons/{coupon}/toggle` | redirect back + flash | 啟用/停用 `is_active` |

### `StoreCouponRequest` 驗證規則

```php
public function rules(): array
{
    return [
        'code'       => ['required', 'string', 'max:6', 'regex:/^[A-Za-z0-9]+$/', 'unique:coupon_codes,code'],
        'type'       => ['required', 'in:fixed,ratio'],
        'value'      => ['required', 'numeric', $this->valueRule()],
        'course_id'  => ['nullable', 'exists:courses,id'],
        'expires_at' => ['nullable', 'date', 'after:now'],
        'max_uses'   => ['nullable', 'integer', 'min:1'],
        'is_active'  => ['boolean'],
        'note'       => ['nullable', 'string', 'max:255'],
    ];
}

// type=fixed → min:10（整數元）；type=ratio → between:0.50,0.95
private function valueRule(): array
{
    return $this->input('type') === 'ratio'
        ? ['between:0.50,0.95']
        : ['min:10'];
}
```

> `unique` 規則含 soft-deleted 列（Laravel `unique` 預設查全表，含已刪除），符合「代碼永久佔用」。
> `UpdateCouponRequest` 相同，惟 `code` 的 `unique` 加 `ignore($coupon->id)`；一般不開放改 `code`（或設唯讀）。

**中文錯誤訊息**（`messages()`）：
- `code.unique` → 「此代碼已存在」
- `code.regex` / `code.max` → 「代碼須為 1–6 位英數字」
- `value.min`（fixed）→ 「最低折抵金額為 NT$10」
- `value.between`（ratio）→ 「折數須介於 0.50 至 0.95 之間」
- `expires_at.after` → 「到期日需晚於現在」

---

## 網址自動帶入折扣碼（FR-020 / US5，既有端點擴充，無新路由）

### `GET /courses/{course}?coupon=CODE`（既有銷售頁，擴充）

`CourseController@show`。在既有 `traffic_source` 捕捉處（`CourseController.php:120`）並排加入：

```php
$couponParam = $request->query('coupon');
if (is_string($couponParam) && trim($couponParam) !== '') {
    $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $couponParam), 0, 6));
    if ($code !== '') {
        $request->session()->put('checkout_coupon', $code);
    }
}
```

### `GET /cart`（既有，擴充）

`CartController@index` 於**已登入與訪客兩個分支**皆讀 `session('checkout_coupon')`，以 `prefillCouponCode`(string|null) prop 回傳**原始代碼字串**（不在伺服器驗證，因訪客購物車存於 localStorage、伺服器無 course_ids）。實際套用與驗證由前端於 `onMounted` 時呼叫 apply-coupon 端點完成（登入取 `props.items`、訪客取 localStorage 的 course_ids），失敗靜默忽略（US5-2）。此設計讓登入/訪客走同一條套用流程。

### `GET /checkout`（既有，擴充 D2 之 session fallback）

`?coupon=` query 不存在時，以 `session('checkout_coupon')` 為後備來源，再經 `CouponService::validateForCart()` 重驗。

### session 清除（清除邏輯一律置於 Controller，Service 不存取 session — constitution §II）

- 訂單成功建立後，由 **`CheckoutController::initiate()`**（非 `CheckoutService`）在 `createOrder()` 回傳成功後呼叫 `session()->forget('checkout_coupon')`，避免污染下一筆訂單。
- 用戶手動移除自動帶入碼時，前端呼叫 `DELETE /api/cart/coupon`（`CouponController@clear` → `session()->forget`），避免重整後重套（US5-4）。

> 自動帶入與手動輸入共用同一 server-side 驗證（apply-coupon → `validateForCart`）與「已套用」狀態；手動輸入優先（US5-3）。

> **路由衝突防範（I1）**：`DELETE /api/cart/coupon` 使用獨立路徑，避免與既有 `DELETE /api/cart/{courseId}`（cart.remove）的萬用參數相撞；並建議為既有 cart.remove 路由補上 `->whereNumber('courseId')` 約束。

---

## webhook 兌現（既有流程內，無新端點）

PayUni / NewebPay 的 NotifyURL → `fulfillOrder()` → 若 `order.coupon_code` 非空 → `CouponService::redeem()` 原子 `increment('used_count')`。付款失敗/取消不觸發（FR-014、SC-003）。

---

## 路由名稱總覽

| name | method | uri |
|------|--------|-----|
| `api.cart.apply-coupon` | POST | `/api/cart/apply-coupon` |
| `api.cart.clear-coupon` | DELETE | `/api/cart/coupon` |
| `admin.coupons.index` | GET | `/admin/coupons` |
| `admin.coupons.create` | GET | `/admin/coupons/create` |
| `admin.coupons.store` | POST | `/admin/coupons` |
| `admin.coupons.edit` | GET | `/admin/coupons/{coupon}/edit` |
| `admin.coupons.update` | PUT | `/admin/coupons/{coupon}` |
| `admin.coupons.destroy` | DELETE | `/admin/coupons/{coupon}` |
| `admin.coupons.show` | GET | `/admin/coupons/{coupon}` |
| `admin.coupons.toggle` | PATCH | `/admin/coupons/{coupon}/toggle` |

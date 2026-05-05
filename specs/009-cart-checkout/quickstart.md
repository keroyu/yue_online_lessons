# Quickstart: 購物車結帳系統 (009-cart-checkout)

## Dev Setup

```bash
# Switch to feature branch
git checkout 009-cart-checkout

# Run migrations (adds payment_gateway, cart_items, orders, order_items)
php artisan migrate

# Seed (optional — no new seeders required)
php artisan db:seed

# Dev server
php artisan serve
npm run dev
```

## New Environment Variables

```env
# 藍新金流（本版本實作）
NEWEBPAY_MERCHANT_ID=your_merchant_id
NEWEBPAY_HASH_KEY=your_hash_key
NEWEBPAY_HASH_IV=your_hash_iv
NEWEBPAY_ENV=sandbox   # sandbox | production
```

## Test Scenarios

### Cart Flow (Manual)

1. Visit a PayUni course page → click 「加入購物車」 → verify badge +1
2. Visit another PayUni course → click 「直接購買」 → verify redirect to `/checkout`
3. Visit `/cart` → verify items shown, total correct
4. Remove an item → verify total updates without page reload
5. Log out → add a course as guest → log back in → verify cart merged

### Checkout Flow (PayUni sandbox)

1. Add 1–2 courses → proceed to `/checkout` → verify `InitiateCheckout` pixel fires
2. Click 「前往付款」 → verify redirect to PayUni sandbox
3. Complete payment with test card → verify redirect to `/member/learning`
4. Verify `Purchase` records created, cart cleared
5. Go back to course pages → verify buttons show 「進入課程」

### Checkout Flow (NewebPay sandbox)

1. 後台將一門課程的金流設為「藍新金流」
2. 將該課程加入購物車 → proceed to `/checkout` → verify `InitiateCheckout` pixel fires
3. Click 「前往付款」 → verify redirect to `ccore.newebpay.com` MPG gateway
4. Complete payment with test card `4000-2211-1111-1111`
5. Verify redirect to `/member/learning`
6. Verify `Purchase` records created, cart cleared, `Purchase` pixel fires

### Failure Flow

1. At PayUni payment page → cancel → verify redirect to `/cart` with error message

### Admin Flow

1. Edit a course → verify `payment_gateway` selector visible (PayUni or 藍新金流)
2. Add `portaly_product_id` → verify selector disappears
3. Clear `portaly_product_id` → verify selector reappears

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/CartItem.php` | Guest-to-server cart item |
| `app/Models/Order.php` | Checkout snapshot |
| `app/Models/OrderItem.php` | Per-course line item |
| `app/Services/CheckoutService.php` | Cart validation → Order creation → gateway dispatch |
| `app/Services/NewebPayService.php` | (new — AES-256-CBC encryption, MPG form, notify handler) |
| `app/Http/Controllers/CartController.php` | Cart CRUD + merge + Inertia page |
| `app/Http/Controllers/CheckoutController.php` | Checkout page + initiate |
| `app/Http/Controllers/Payment/NewebPayController.php` | NewebPay notify + return |
| `resources/js/Pages/Cart/Index.vue` | 購物車頁 |
| `resources/js/Pages/Checkout/Index.vue` | 結帳頁 |

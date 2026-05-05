# Implementation Plan: 購物車結帳系統

**Branch**: `009-cart-checkout` | **Date**: 2026-05-06 | **Spec**: `specs/009-cart-checkout/spec.md`
**Input**: Feature specification from `/specs/009-cart-checkout/spec.md`

## Summary

Implement a full cart-and-checkout system supporting both guest and authenticated users, with PayUni UPP and NewebPay MPG as payment gateways. Both gateways follow a unified `Order → OrderItems → webhook → Purchases` snapshot architecture. Gateway credentials become admin-configurable via `site_settings`. Gateway routing rule: single course uses its `payment_gateway` field; multi-course carts always use PayUni.

## Technical Context

**Language/Version**: PHP 8.2 / Laravel 12  
**Primary Dependencies**: Inertia.js v2, Vue 3 (`<script setup>`), Tailwind CSS v4, PayuniService (existing), NewebpayService (new)  
**Storage**: MySQL — 3 new tables (`cart_items`, `orders`, `order_items`), 2 altered tables (`courses` + `payment_gateway`, `purchases` + `order_id`); `site_settings` table (existing) for gateway credentials  
**Testing**: `php artisan test` (PHPUnit/Pest)  
**Target Platform**: Laravel Forge (Linux) + Vue 3 SPA (Inertia)  
**Project Type**: Web application (Laravel backend + Inertia frontend)  
**Performance Goals**: Cart operations < 300ms; checkout initiate < 500ms  
**Constraints**: PayUni MerTradeNo max 20 chars; NewebPay MerchantOrderNo max 30 chars, alphanumeric + underscore only; NotifyURL must return `1|OK` (PayUni) / `SUCCESS` (NewebPay) to prevent retries  
**Scale/Scope**: Existing user base; single-server deployment; no horizontal scaling required for cart

## Constitution Check

*GATE: Must pass before Phase A implementation. Re-check after Phase K.*

| Rule | Status | Notes |
|------|--------|-------|
| I. No new frameworks | ✅ Pass | No new framework; composable pattern only |
| II. No new databases | ✅ Pass | MySQL only |
| III. No localStorage for business state | ⚠️ Exception | Guest cart uses localStorage (see Complexity Tracking) |
| IV. No global state outside Inertia shared props | ✅ Pass | `cartCount` added to shared props via `HandleInertiaRequests` |
| V. RESTful controllers | ✅ Pass | CartController, CheckoutController, NewebpayController all RESTful |
| VI. Form Requests for validation | ✅ Pass | AddToCartRequest, CheckoutRequest, UpdateCourseRequest extended |
| VII. Policy for authorization | ✅ Pass | CartController verifies `auth()->id()` ownership server-side |
| VIII. Eager loading | ✅ Pass | `Order::with('items')`, `CartItem::with('course')` |
| IX. No N+1 | ✅ Pass | All collection queries use `with()` |
| X. YAGNI | ✅ Pass | No at-rest credential encryption, no separate anonymous cart token |

## Project Structure

### Documentation (this feature)

```text
specs/009-cart-checkout/
├── plan.md              # This file
├── research.md          # Design decisions (R-001 to R-008)
├── data-model.md        # Schema, models, service interfaces, frontend props
├── quickstart.md        # Phase-by-phase implementation guide
├── contracts/
│   └── api.md           # All route contracts with request/response shapes
└── spec.md              # Feature specification (clarified)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── CartController.php                   # new
│   │   ├── CheckoutController.php               # new
│   │   ├── Payment/
│   │   │   ├── PayuniController.php             # existing — extend for Order flow
│   │   │   ├── NewebpayController.php           # new
│   │   │   └── SuccessController.php            # new
│   │   └── Admin/
│   │       └── SettingsController.php           # existing or new — add updatePayment()
│   └── Requests/
│       ├── AddToCartRequest.php                 # new
│       ├── CheckoutRequest.php                  # new
│       └── Admin/
│           └── UpdateCourseRequest.php          # existing — add payment_gateway field
├── Models/
│   ├── CartItem.php                             # new
│   ├── Order.php                                # new
│   ├── OrderItem.php                            # new
│   ├── Course.php                               # existing — add payment_gateway to $fillable
│   └── Purchase.php                             # existing — add order_id to $fillable
└── Services/
    ├── CartService.php                          # new
    ├── CheckoutService.php                      # new
    ├── NewebpayService.php                      # new
    └── PayuniService.php                        # existing — extend for Order-based flow

database/
└── migrations/
    ├── xxxx_create_cart_items_table.php         # new
    ├── xxxx_create_orders_table.php             # new
    ├── xxxx_create_order_items_table.php        # new
    ├── xxxx_add_payment_gateway_to_courses.php  # new
    └── xxxx_add_order_id_to_purchases.php       # new

resources/js/
├── Pages/
│   ├── Cart/
│   │   └── Index.vue                           # new
│   ├── Checkout/
│   │   └── Index.vue                           # new
│   ├── Payment/
│   │   └── Success.vue                         # new
│   └── Admin/
│       └── Settings/
│           └── Payment.vue                      # new
├── composables/
│   └── useCart.js                              # new
└── Components/
    └── Navigation.vue                           # existing — add cart badge
```

**Structure Decision**: Web application layout (Laravel backend + Inertia frontend). All new backend files go under `app/`; all new frontend files go under `resources/js/`.

---

## Implementation Phases

### Phase A: Database Migrations & Models
- 5 migrations (3 new tables, 2 alters)
- 3 new Eloquent models (CartItem, Order, OrderItem)
- Update Course + Purchase models

### Phase B: CartService + CartController
- `CartService` with add / remove / count / mergeGuestCart / clearPurchased
- `CartController` with POST `/api/cart/add`, DELETE `/api/cart/{courseId}`, POST `/api/cart/merge`
- `cartCount` added to Inertia shared props

### Phase C: Payment Gateway Credentials Admin
- `SettingsController::showPayment()` + `updatePayment()`
- `Admin/Settings/Payment.vue` with password inputs for key/IV
- Update `PayuniService::__construct()` to read from SiteSetting with .env fallback

### Phase D: Sales Page Update
- `Course/Show.vue` button logic for Portaly / free / high-ticket / cart courses
- `useCart.js` composable encapsulating add/buyNow with auth-state branching
- Meta Pixel `AddToCart` trigger

### Phase E: Cart Frontend
- `Cart/Index.vue` for both logged-in (Inertia props) and guest (localStorage)
- Navigation badge (cartCount for logged-in; localStorage length for guest)
- Meta Pixel `InitiateCheckout` trigger

### Phase F: CheckoutService + CheckoutController
- `CheckoutService::createOrder()` with two-step merchant_order_no generation
- `CheckoutService::routeGateway()` implementing single/multi-item routing rule
- `CheckoutService::fulfillOrder()` with two-layer idempotency + DripService call
- `CheckoutController::initiate()` + `CheckoutRequest`

### Phase G: Checkout Frontend
- `Checkout/Index.vue` with buyer form, order summary, disabled-until-valid submit
- Auto-submit hidden form to gateway on 200 response from `/api/checkout/initiate`

### Phase H: PayUni Order Flow Refactor
- Update `PayuniService::buildPaymentForm()` to accept `Order`
- Update `PayuniService::processNotify()` to handle `ord_` prefix path
- Update `PayuniController::return()` to redirect to `/payment/success`

### Phase I: NewebpayService + Controllers
- `NewebpayService` with AES-256-CBC encrypt/decrypt and TradeSha verification (see R-001)
- `NewebpayController::notify()` + `::return()`
- CSRF exemption for webhook + return routes

### Phase J: Payment Success Page
- `SuccessController::show()` loading Order by `merchant_order_no`
- `Payment/Success.vue` with order summary, Pixel Purchase event, login/courses CTA
- Guest cart cleared from localStorage on mount

### Phase K: Admin CourseForm
- `payment_gateway` selector in `Admin/Courses/Form.vue` with watch-hide on `portaly_product_id`
- Validation in `UpdateCourseRequest` / `StoreCourseRequest`

---

## Complexity Tracking

> **Justification required for Constitution III violation**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Constitution III: localStorage for guest cart | The entire purchase flow (including add-to-cart) must work without login. Guest cart is intentionally pre-authentication, ephemeral state that is lost/merged on login. Storing it server-side requires issuing and tracking anonymous session tokens for all visitors, even those who never buy. | PHP session adds write-per-request overhead for anonymous traffic; anonymous token table is over-engineered for this conversion pattern. Industry standard (Shopify, WooCommerce) uses client-side for guest cart for exactly this reason. Data is non-sensitive (public course metadata). Merged to server immediately on login. |

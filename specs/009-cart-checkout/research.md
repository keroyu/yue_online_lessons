# Research: 009-cart-checkout

**Feature Branch**: `009-cart-checkout`
**Created**: 2026-05-06
**Status**: Final

This document records all design decisions reached through spec clarification sessions, analysis of existing code (PayuniService, SiteSetting, purchases schema), and the NewebPay PDF integration manual (NDNF-1.2.2).

---

## R-001: NewebPay AES-256-CBC Encryption

**Decision**: Implement NewebPay MPG payment using AES-256-CBC encryption for TradeInfo and SHA-256 for TradeSha, with a separate `NewebpayService` class. Test endpoint: `https://ccore.newebpay.com/MPG/mpg_gateway`; production endpoint: `https://core.newebpay.com/MPG/mpg_gateway`.

**Rationale**:
- The NewebPay manual (NDNF-1.2.2) mandates AES-256-CBC with CBC mode, raw binary output, and hex encoding. The exact encrypt call is `bin2hex(openssl_encrypt(http_build_query($params), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv))`.
- TradeSha must be `strtoupper(hash('sha256', "HashKey={$key}&{$tradeInfo}&HashIV={$iv}"))` — uppercase SHA-256 wrapping the AES hex string.
- Outer POST fields sent to MPG endpoint: `MerchantID`, `TradeInfo` (AES hex), `TradeSha` (SHA-256 uppercase), `Version="2.3"`. These match the form-post model used by PayUni and fit the same blade-based redirect pattern.
- Decrypt (for NotifyURL webhook): `openssl_decrypt(hex2bin($tradeInfo), 'AES-256-CBC', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $iv)` followed by manual PKCS7 padding strip, because NewebPay does not strip padding automatically.
- NotifyURL MUST return the plain string `"SUCCESS"` (not JSON); the controller must `return response('SUCCESS')` with no Content-Type override. Any other response causes NewebPay to retry.
- ReturnURL receives the same encrypted POST payload, so `ReturnController` reuses the same decrypt helper.
- Decided in spec session 2026-05-05: NewebPay is fully implemented in this version, not deferred.

**Alternatives considered**:
- **AES-256-GCM** (used by PayuniService): Not applicable — NewebPay mandates CBC mode per official spec. Cannot reuse PayuniService crypto logic.
- **Symmetric JSON response for NotifyURL**: Rejected. NewebPay requires the literal string `SUCCESS`; JSON would cause retry loops.
- **Shared encryption service**: Considered merging AES logic into a single `CryptoService`. Rejected (YAGNI) — the two gateways use different modes (GCM vs CBC), different padding requirements, and different hash schemes; sharing would add complexity without benefit.

---

## R-002: PayuniService Refactoring Strategy

**Decision**: Extend the existing `PayuniService` to support the new Order-based flow while preserving backward compatibility for legacy single-course purchases.

Concrete changes:
- New MerTradeNo format for Orders: `ord_{order_id}_{YYMMdd}` (e.g., `ord_42_260506`), replacing the legacy `YC{courseId:4}{datetime}{rand4}` encoding.
- Order snapshot (`orders.buyer_*` columns + `order_items` rows) stores buyer data, eliminating the Cache-based buyer lookup used in the old flow.
- `processNotify()` now checks MerTradeNo prefix: if it starts with `ord_`, look up Order by `merchant_order_no`; otherwise fall through to legacy `parseCourseId()` path.
- Per-webhook result creates one `Purchase` record per `OrderItem` (not one per webhook call).
- Legacy `parseCourseId()` method is retained, not removed.

**Rationale**:
- The old `parseCourseId()` approach was a workaround because PayUni notify does not include buyer email. With Orders, buyer data is already persisted before payment; no Cache needed.
- Keeping the legacy path allows existing purchases (created before this version) to remain valid without a data migration.
- `ord_` prefix gives `processNotify` an unambiguous branch signal with zero regex overhead.
- The existing `payuni_trade_no` UNIQUE column on `purchases` continues to serve as DB-level idempotency for old single-course purchases. New purchases under the Order flow rely on Order.status='paid' as the primary gate (see R-005).

**Alternatives considered**:
- **Write a new `PayuniOrderService`**: Would avoid modifying existing service but creates two parallel services that share most code. Rejected — duplication outweighs isolation benefit.
- **Encode order_id into the same `YC...` format**: Rejected — order IDs can exceed 4 digits; the format is brittle and not readable.
- **Drop legacy path entirely and migrate old purchases**: Rejected — migration risk with no user benefit. Old purchases are already paid and complete.

---

## R-003: Guest Cart localStorage vs Session

**Decision**: Guest cart uses `localStorage` with key `guest_cart` storing a JSON array of `{id, name, price, thumbnail}` objects. This is a **documented exception** to Constitution III ("localStorage: Used ONLY for ephemeral client-side features. Never for business state.").

**Rationale**:
- Guest cart is intentionally pre-authentication state. It is ephemeral by design: cleared on login (after merge) and cleared after successful payment.
- PHP session alternative requires a session write on every anonymous page load, adding DB/file I/O for all visitors even if they never buy.
- Industry-standard pattern (Shopify, WooCommerce, most e-commerce platforms) uses client-side storage for guest cart specifically because it is meant to be transient and user-owned.
- The data stored is non-sensitive (public course metadata already visible on the page). Loss of this state (browser clear, private mode close) is acceptable and expected.
- Merge-on-login (`POST /cart/merge`) converts guest cart to server-side state the moment authentication is established, so business logic runs server-side from that point forward.

Exception documented in `plan.md` Complexity Tracking section per Constitution III protocol.

**Alternatives considered**:
- **PHP Session (file-based)**: Adds write-on-every-request overhead for anonymous users. Session ID must be sent in cookie, which has GDPR implications. No practical advantage over localStorage for this use case.
- **Server-side anonymous cart with a random token**: More robust (survives browser close), but requires issuing and tracking anonymous tokens, adds a DB table or cache entry per visitor. Over-engineered for a platform where anonymous browsing converts to purchase in the same session the vast majority of the time.
- **No guest cart (force login before add-to-cart)**: Rejected in spec clarification — the explicit requirement is that the entire purchase flow works without login.

---

## R-004: Payment Gateway Credentials in SiteSetting

**Decision**: Payment gateway credentials (MerchantID, HashKey, HashIV) for both PayUni and NewebPay are stored in the existing `site_settings` DB table using key prefixes `payuni_merchant_id`, `payuni_hash_key`, `payuni_hash_iv`, `newebpay_merchant_id`, `newebpay_hash_key`, `newebpay_hash_iv`. Services read via `SiteSetting::get('payuni_hash_key', config('services.payuni.hash_key'))` — DB value takes priority, `.env` is fallback. Admin UI uses `<input type="password">` for key/IV fields (FR-022) but transmits plaintext to the server.

**Rationale**:
- Decided in spec session 2026-05-06: credentials MUST be configurable via admin UI, not locked to `.env`.
- `SiteSetting` already has `get(key, default)` and `set(key, value)` helpers; zero new infrastructure needed.
- Fallback to `config('services.payuni.*')` (which reads `.env`) preserves zero-downtime deployment: existing `.env` values continue to work until overridden via UI.
- No at-rest encryption of stored credentials: the `site_settings` table is admin-only (middleware-protected), consistent with how other sensitive settings (e.g., API keys for social links) are stored in this project. Adding encryption would require key management infrastructure (Constitution X: YAGNI).

**Alternatives considered**:
- **Keep credentials in `.env` only**: Rejected per explicit spec decision. Requires server SSH access to rotate keys; not suitable for an admin-managed platform.
- **Separate `payment_credentials` DB table with at-rest encryption**: More secure, but requires Laravel `encryptUsing` setup or a vault integration. Over-engineered for the current threat model (admin panel is already behind auth + role check). Deferred to a future security hardening pass.
- **Laravel `config/services.php` with cache-clearing on save**: Would work but bypasses the existing `SiteSetting` pattern and requires `artisan config:clear` on every credential update. Rejected for operational complexity.

---

## R-005: Webhook Idempotency Design (Two-Layer)

**Decision**: Implement two-layer idempotency for payment webhook processing.

- **Layer 1 (Order-level gate)**: At webhook entry, check `Order::where('merchant_order_no', $merTradeNo)->where('status', 'paid')->exists()`. If true, return success immediately without processing. This is the primary idempotency gate and prevents duplicate purchase creation on retry.
- **Layer 2 (DB constraint)**: The `UNIQUE(user_id, course_id)` constraint on the `purchases` table (established in feature 006) catches any race condition that bypasses Layer 1 (e.g., simultaneous webhook retries). Handle with `updateOrCreate` or `try/catch QueryException` rather than letting it surface as a 500.

**Rationale**:
- Spec constraint SC-009 explicitly requires idempotent webhook handling.
- Payment gateways (both PayUni and NewebPay) may send duplicate notify calls on timeout or when the merchant endpoint is slow. A single DB constraint check is insufficient because between the check and the INSERT there is a window where two concurrent webhook calls both pass the check.
- The two-layer approach provides defense in depth: Layer 1 is a fast path exit for normal retries; Layer 2 is a fallback for the race condition window.
- For NewebPay specifically: NotifyURL must return `"SUCCESS"` even for duplicate notifications (the gateway does not distinguish). Returning success on already-paid orders is therefore the correct behavior, not an error.
- `Order.status='paid'` is the canonical truth for the new flow; `payuni_trade_no` UNIQUE on purchases remains the canonical truth for legacy single-course purchases.

**Alternatives considered**:
- **DB-constraint only (no Order status check)**: Insufficient alone — would throw `QueryException` on duplicate, requiring catch in every caller. Centralizing at the Order level is cleaner.
- **Redis/Cache lock**: Would prevent the race but adds an infrastructure dependency (Redis) not currently used in this project. The DB-constraint fallback is sufficient and already present.
- **Idempotency key in request header**: Not supported by NewebPay or PayUni — gateways do not send idempotency tokens. Must be inferred from MerTradeNo/MerchantOrderNo.

---

## R-006: Purchase Fields Mapping

**Decision**: When webhook processing creates `Purchase` records from an Order, populate fields as follows (derived from analysis of the 006 `purchases` schema and `PayuniService::createPurchase()`):

| Field | Value | Source |
|---|---|---|
| `user_id` | Order owner (find-or-created user) | Order |
| `course_id` | Per OrderItem | OrderItem |
| `buyer_email` | Order.buyer_email | Order |
| `amount` | OrderItem.unit_price | OrderItem |
| `currency` | `'TWD'` | Hard-coded |
| `status` | `'paid'` | Constant |
| `type` | `'paid'` | Constant |
| `source` | `'payuni'` or `'newebpay'` | CheckoutService routing |
| `webhook_received_at` | `now()` | Runtime |
| `order_id` | FK to orders table | Order (new column) |
| `payuni_trade_no` | PayUni ATM trade no (PayUni only) | Webhook payload |

After each purchase record is created, call `DripService::checkAndConvert($purchase)` — this is the existing pattern in `PayuniService` and must be preserved for drip email enrollment.

**Rationale**:
- The `source` field is critical for the 006 transaction admin view, which filters by gateway. Without it, admins cannot distinguish PayUni from NewebPay revenue in the dashboard.
- `order_id` FK links purchase back to the order for refund and audit workflows.
- `payuni_trade_no` is left null for NewebPay purchases (no equivalent field in NewebPay payload); the UNIQUE constraint on this column is nullable-safe.
- `webhook_received_at` is already expected by the 006 transaction admin — must not be omitted.
- `DripService::checkAndConvert` call is non-optional: drip campaigns depend on purchase creation events. Omitting it would silently break email automation.

**Alternatives considered**:
- **Single Purchase per Order (not per OrderItem)**: Would break the 006 dashboard and classroom access checks, which operate at the `(user_id, course_id)` level. Rejected.
- **Store gateway name in a new `gateway` column**: The existing `source` column already serves this purpose per 006 schema analysis. Adding a redundant column would require a migration with no benefit.
- **Skip `DripService` call for cart purchases**: Rejected — drip logic must apply regardless of purchase path. Inconsistent automation would be a regression.

---

## R-007: CheckoutService Gateway Routing

**Decision**: Implement gateway routing in `CheckoutService::routeToGateway()` with the following rule:

- **Single item + `course.payment_gateway = 'newebpay'`** → `NewebpayService`
- **All other cases** (single item with `payment_gateway = 'payuni'`, or multiple items regardless of gateway) → `PayuniService`

MerchantOrderNo / MerTradeNo format: `ord_{order_id}_{YYMMdd}` for both gateways. Example: `ord_42_260506` (15 chars, safely under NewebPay's 30-char limit and PayUni's 20-char limit).

**Rationale**:
- Decided in spec session 2026-05-06: multi-item carts always use PayUni because NewebPay MPG requires a single amount with no line-item breakdown API suitable for mixed-gateway items.
- Portaly courses are excluded from the cart at the entry layer (`courses.portaly_product_id IS NOT NULL` → no "Add to Cart" button rendered). There is no mixed-cart conflict to handle.
- Consistent `ord_` format across both gateways means `processNotify` can identify the Order by MerTradeNo regardless of which gateway called back, with a single DB lookup.
- The `_YYMMdd` suffix prevents collision if an order is somehow retried on a different day (though Order IDs are already globally unique; the suffix is defensive).

**Alternatives considered**:
- **Always use PayUni**: Simpler routing, but fails the explicit business requirement that some courses must use NewebPay.
- **Let the merchant choose gateway per order regardless of item count**: Would require multi-currency / multi-gateway splitting logic in a single PayUni request, which PayUni does not support for a single MerTradeNo. Rejected.
- **Separate MerTradeNo format per gateway**: Would require gateway detection in `processNotify` before the DB lookup. The unified `ord_` prefix allows a single lookup regardless of gateway. Rejected.

---

## R-008: Frontend Cart State Architecture

**Decision**: Implement a dual-state cart architecture:

- **Server-side (authenticated users)**: `cart_items` table with `(user_id, course_id)` unique constraint. Loaded via Inertia shared props `cartCount` in `HandleInertiaRequests::share()`.
- **Client-side (guests)**: `localStorage` key `guest_cart` = JSON array of `{id, name, price, thumbnail}` objects.
- **Cart badge in Navigation.vue**: For logged-in users, read `$page.props.cartCount` (integer, Inertia shared prop). For guests, compute badge count from `localStorage` guest cart length via a `computed` that checks auth state. No hydration mismatch risk because badge is purely presentational.
- **Merge on login**: `POST /cart/merge` sends the guest cart array. Server deduplicates against existing `cart_items` and already-purchased courses, then clears the guest cart signal. Frontend clears `localStorage.guest_cart` after receiving 200.
- **Vue composable `useCart()`**: Encapsulates add/remove/merge logic and abstracts the auth-state branching, so course page components do not need to check auth state directly.

**Rationale**:
- Inertia shared props (`HandleInertiaRequests`) is the established pattern in this project for per-request data (e.g., auth user, flash messages). Adding `cartCount` follows the same pattern with no new infrastructure.
- Guest cart in localStorage (see R-003) means the badge must be computed client-side for guests. A `computed` property in Navigation.vue that switches on `$page.props.auth.user` is the minimal implementation.
- The merge endpoint is idempotent: submitting the same guest cart twice only creates items not already present. This is safe to call on every login, regardless of whether the user had a guest cart.
- `useCart()` composable avoids scattering auth-state checks across multiple page components and makes the logic testable in isolation.

**Alternatives considered**:
- **Emit cart count via Inertia for guests too (server-sets a guest_cart_count in session)**: Would eliminate the localStorage read in Navigation.vue but requires a session read on every page load for anonymous users. Rejected for same reasons as R-003 session alternative.
- **Vuex / Pinia store for cart state**: No Pinia is currently used in this project. Adding a store for a single feature would be over-engineering. The composable pattern achieves the same isolation. Rejected.
- **Polling or WebSocket for real-time badge updates**: Not needed — cart changes are user-initiated. Inertia's page reload on navigation naturally reflects the latest `cartCount` for logged-in users.

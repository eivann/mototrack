# MotoTrack Architectural Audit

**Date:** 2026-05-25
**Scope:** Hidden architectural coupling and future scaling risks
**Stack:** Laravel 13, PostgreSQL/Supabase, Blade + Alpine.js, Xendit + RCPAY, Semaphore/Movider SMS

---

## CRITICAL (production-blocking at scale)

### 1. Triple DB bottleneck through Supabase transaction pooler
- **Evidence:** `.env:30,38,40` route SESSION, CACHE, and QUEUE all through the Supabase **transaction-mode pooler** (port 6543, `.env:25`).
- **Why it matters:** Every session read, cache hit, and queued job competes for the same pooled connections. Transaction-mode pooling also breaks prepared statements and `LISTEN/NOTIFY`. `CheckoutService.php:30` and `SaleController.php:52` use `lockForUpdate()` which can deadlock under this pooler.
- **Fix:** Redis for session/cache/queue, or switch to session-mode pooler (port 5432) for the DB connection.

### 2. Payment providers hardcoded — no abstraction
- **Evidence:** `PaymentService.php:11-14` instantiates Xendit directly; `RcpayWebhookController.php:34` calls concrete `verifyRcpayWebhook()`. No `PaymentGatewayInterface`.
- **Why it matters:** Adding GCash-direct/Stripe/PayMongo requires rewriting both files. Can't A/B test providers. Hard to mock for testing.
- **Fix:** Introduce `PaymentGatewayInterface` with `createInvoice()` / `verifyWebhook()`. Factory selects implementation via `config('services.payment.provider')`.

### 3. SMS provider silent fallback + session leak
- **Evidence:** `SmsService.php:17-63` cascades Twilio → Semaphore → Movider → Mock with no observability. The "stateless" service calls `session()->flash()` at line 47.
- **Why it matters:** Silent fallback to Mock hides real SMS failures — customers think alerts went out, they didn't. `session()->flash()` **breaks the moment it's called from `CheckLowStockAlertJob`** (no session in queue context).
- **Fix:** `SmsGatewayInterface` with concrete Semaphore/Movider/Twilio classes. Remove `session()->flash()`; log provider + duration + status for observability.

### 4. Webhook synchronous fan-out can stall RCPAY
- **Evidence:** `RcpayWebhookController.php:88,119` dispatches `PaymentReceived` in-request. `Sale::$with = ['item', 'customer']` (`Sale.php:12`) forces eager loads on every requery in the listener.
- **Why it matters:** With queue on DB (risk #1), webhook ack can stall → RCPAY retries → duplicate processing risk.
- **Fix:** Move event dispatch to after-response. Remove `protected $with`; eager-load explicitly where needed. Pass full context in the event payload so listeners don't need to re-query.

---

## HIGH (forces a future rewrite)

### 5. CartService is session-bound
- **Evidence:** `CartService.php:15,46,59,81,92` use the `Session` facade directly.
- **Why it matters:** API/mobile/queue cannot reuse it. Will block the planned Sanctum REST API phase from the README roadmap.
- **Fix:** Extract `CartRepository` interface; provide `SessionCartRepository` (current) and `ArrayCartRepository` / `DbCartRepository` for API and persistent carts.

### 6. Fat controllers with mixed concerns
- **Evidence:** `CheckoutController.php` (180 LOC), `SaleController.php` (151 LOC) wrap `DB::transaction`, stock locking, payment dispatch, and notifications inline.
- **Why it matters:** Untestable without booting Laravel + sessions. Hard to reuse the same flow from a future API or CLI command.
- **Fix:** Extract domain actions (`CompleteOrderAction`, `RecordSaleAction`); controllers become thin orchestrators.

### 7. God-model: `InventoryItem`
- **Evidence:** `InventoryItem.php:48-93` — silent `ucwords`/`strtoupper` mutators on every set. Driver-aware `scopeSearch` at lines 115-128.
- **Why it matters:** Bulk imports and raw updates will silently transform data. Search logic in the model violates SRP and is hard to evolve.
- **Fix:** Move mutators to a `FormRequest` or transformer. Move `scopeSearch` to a dedicated `InventorySearchService`.

### 8. Ambiguous ownership on Sale / Repair / PreOrder
- **Evidence:** Migration `2026_05_06_115346_add_customer_id_to_various_tables.php` adds nullable `customer_id`. `SaleController.php:47-87` creates walk-in POS sales with NULL `customer_id`. No `owner_id` or `shop_id`.
- **Why it matters:** Reports "sales by customer" silently exclude walk-ins. GDPR data-subject requests ambiguous. Future multi-shop scenario has no scope column to filter on.
- **Fix:** Define explicit ownership semantics. Add `owner_id` / `shop_id` where applicable. Enforce in policies.

### 9. No multi-tenancy hook
- **Evidence:** `User.role` is global (`User.php:14`). `OwnerMiddleware.php:18` checks `auth()->user()->role !== 'owner'` with no shop scoping.
- **Why it matters:** A second shop (franchise / SaaS) requires a schema migration touching every domain table. All owners see all orders today.
- **Fix:** Plan `shop_id` on `users` and on each domain table from v2 schema. Scope queries and middleware by shop.

---

## MEDIUM

### 10. Sales price-type migration has no data transformation
- **Evidence:** `2026_05_08_172200_fix_sales_price_type.php` changes integer → `decimal(10,2)` without an explicit data backfill.
- **Why it matters:** Historical rows stored as integer cents may now be interpreted as decimal pesos.
- **Fix:** Add a validation/transformation step; assert min/max ranges before and after.

### 11. Webhook route lacks signature middleware
- **Evidence:** `routes/web.php:41` — RCPAY webhook is throttled but has no explicit CSRF exclusion or signature-verification middleware visible at the route level.
- **Why it matters:** Relies on controller-level verification; if a future contributor adds CSRF globally, webhook returns 419.
- **Fix:** Explicit middleware: `throttle`, `verify.rcpay.signature`. Document CSRF exemption.

### 12. Auto eager-loading wastes queries
- **Evidence:** `Sale.php:12` and `PreOrder.php:28` use `protected $with = [...]`; controllers then call `->with(...)` again.
- **Why it matters:** Wasted queries at every list/paginate call. Hard to opt out for lean queries.
- **Fix:** Remove `protected $with`; load relations explicitly where needed.

---

## LOW

### 13. Notification listener re-queries instead of using event payload
- **Evidence:** `SendPaymentNotification.php:48` queries `Sale::where('transaction_reference', $orderId)->get()` instead of reading from the event.
- **Fix:** Pass enough data in `PaymentReceived` so the listener doesn't re-query.

### 14. Secrets present in `.env` on disk
- **Evidence:** `.env` contains live-format keys for Xendit, RCPAY, Google OAuth, Supabase, Movider. `.env` is gitignored (good).
- **Fix:** Use a secrets manager (Supabase Vault / AWS Secrets Manager) for staging+prod. Add a pre-commit secret scanner.

---

## Summary

| Severity | Count | Theme |
|----------|-------|-------|
| Critical | 4 | DB pooler, payment hardcoding, SMS silent fallback, webhook blocking |
| High     | 5 | Cart session coupling, fat controllers, god-model, ambiguous ownership, no multi-tenancy |
| Medium   | 3 | Price-type migration, webhook signature, eager-load waste |
| Low      | 2 | Listener re-query, secrets on disk |

## Recommended sequence

1. **Move SESSION / CACHE / QUEUE off the Supabase pooler** (Redis or session-mode pooler). Unblocks #1 and #4 simultaneously — and makes every other refactor safer.
2. **`PaymentGatewayInterface`** abstraction (#2) — prerequisite for adding new providers and for clean testing.
3. **`SmsGatewayInterface`** + remove session coupling (#3) — fixes the broken `CheckLowStockAlertJob` path.
4. **Extract domain actions** from `CheckoutController` and `SaleController` (#6) — makes everything else testable.
5. Plan **multi-tenancy schema** (#9) before customer count grows further.

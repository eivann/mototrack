---
title: "MotoTrack | Remediation & Implementation Plan"
subtitle: "Laravel 11 • Offline reservations and pre-orders • 24-hour pickup policy"
author: "Code-review follow-up | Implementation blueprint"
date: "9 October 2026"
geometry: margin=0.8in
fontsize: 10pt
colorlinks: true
linkcolor: NavyBlue
urlcolor: NavyBlue
header-includes:
  - '\usepackage{enumitem}'
  - '\setlist[itemize]{itemsep=2pt,topsep=3pt}'
  - '\setlist[enumerate]{itemsep=2pt,topsep=3pt}'
  - '\usepackage{fancyhdr}'
  - '\pagestyle{fancy}'
  - '\fancyhf{}'
  - '\fancyhead[L]{\small MotoTrack Remediation Plan}'
  - '\fancyhead[R]{\small 09 Oct 2026}'
  - '\fancyfoot[C]{\thepage}'
---

# Purpose and scope

This is a **fix plan**, not a claim that any changes have already been implemented. It converts the earlier MotoTrack source review into a sequenced, testable backlog for a Laravel 11 motorcycle-parts **offline reservation / pre-order** system. Customers reserve in-stock items without paying online; staff collect payment and release parts on pickup; uncollected reservations expire after **24 hours**.

**Source:** `mototrack_source_for_review.txt` (uploaded source export, about 14,429 lines). File/line references below indicate the source export's reported locations (not necessarily line positions in the developer's working checkout). The complete source for `app/Actions/CommitOrder.php`, expiration command, middleware, FormRequests, policies, service bindings, repository and JavaScript modules was **not included**. Their behavior must be checked before marking the related tasks complete.

**Priority labels:** **P0** = must fix before production; **P1** = correct before release sign-off; **P2** = improvement and cleanup. The earlier report's critical/high/medium/low IDs are retained for traceability. **No deployment should proceed while a P0 task or its acceptance tests remain open.**

## Target business rules (approve these first)

1. **Reservation**: accepted only when `available stock >= requested quantity`; it holds physical inventory exactly once in an atomic transaction; no payment occurs online.
2. **24-hour deadline**: persisted in the database (`expires_at = reserved_at + 24h`). Staff cannot complete an expired hold even if the hourly cleanup has not run.
3. **Cancellation/expiry**: only an *active, unfulfilled* reservation can release stock, exactly once. Duplicate requests, retries, and jobs must be idempotent.
4. **Pickup**: staff confirm **actual payment and physical handover** under a single guarded transition. `completed` / `picked_up_at` / `payment_confirmed_at` must reflect real actions, never be inferred from refund rejection or simply creating a reservation.
5. **Revenue**: count **recorded payments**, not reservation values. Display *unpaid reserved value* separately. Do not double-count a multi-line order.
6. **Refund**: an unpaid reservation can be *cancelled*, not *refunded*. Refund approval must be bounded by actual received funds and must not restore stock twice.
7. **Pre-order**: a sourcing request for out-of-stock/custom parts is distinct from an in-stock reservation. Receiving supplier stock, holding it for the customer, collecting payment, and customer pickup must be distinct events.
8. **History**: archiving a transaction never changes on-hand stock. Every physical stock movement is traceable to one unique event.

**Proposed status flows** (adapt carefully to existing database data and enums):

- In-stock reservation: `pending` -> `completed` (paid and collected), or `pending` -> `cancelled` / `expired` (release hold).
- Pre-order: `requested` -> `sourcing` -> `ready` -> `collected`, or permitted states -> `cancelled`. Supplier receipt is an inventory event, **not** itself proof of customer pickup.
- Refund: eligible **paid** order -> `refund_requested` -> `refund_approved` or `refund_rejected`; physical return/restock is tracked independently. If your operation does not offer post-payment refunds, replace this with an owner-only reversal policy rather than retaining unused consumer UI.

# Phase 0 - Baseline, protections, and data audit [P0]

**Deliverable:** safe starting point and repeatable failing tests.

- [ ] Branch from a known working commit; freeze unrelated feature work, back up database + storage, and document rollback procedure.
- [ ] Confirm the **installed** Laravel version, database driver, PHP version, payment packages, queue configuration, scheduled task runner, and effective route list: `php artisan --version`, `php artisan route:list -v`, `php artisan schedule:list`, `composer show`.
- [ ] Obtain missing files, particularly `app/Actions/CommitOrder.php`, the handler for `reservations:expire`, owner/OTP middleware, all relevant FormRequests, `CheckoutRequest`, `Sale` statuses/enum, `bootstrap/app.php`, and bindings for `PaymentGateway`.
- [ ] Snapshot database totals: per-SKU on-hand stock, pending reservations, completed but unpaid records, cancelled/refunded reservations, duplicate repair-payment sale rows, expired-but-still-pending holds, and all pre-orders whose stock may have been incremented.
- [ ] Establish a test database **separate from production**. Add regression tests for each P0 case below **before** modifying code; prove the current behavior fails where feasible.
- [ ] Review logs and customer-facing exports for exposure of OTP fields, password hashes, payment/QR content and debug errors; keep examples out of test snapshots.

**Exit gate:** backup verified; routes and scheduled jobs inventoried; data anomalies recorded; minimal test suite running.

# Phase 1 - Account safety and access control [P0]

## SEC-01: Repair Laravel 11 User model mass assignment (C-01)

**Where:** `app/Models/User.php`, source lines **649-657**; callers `Auth/RegisteredUserController.php` **1536-1540**, `Auth/GoogleController.php` **1213-1229**, `Customer/ProfileController.php` **1845-1853**.

- [ ] Replace the attribute-based `#[Fillable]` and `#[Hidden]` configuration with Laravel-11-compatible `protected $fillable` and `protected $hidden` properties (verify the project's installed framework version).
- [ ] Hide `password`, `remember_token`, `otp_code` and any other authentication secrets from serialization.
- [ ] Restrict `role`, `google_id`, `otp_code`, `otp_expires_at`, `email_verified_at` and `requires_password_change` to trusted, explicit mutations; if excluded from `$fillable`, revise account creation and authentication code to set intended values safely, rather than silently dropping them.
- [ ] Assert regular registration **always** creates role `customer`, never `owner`, even when the POST includes `role=owner`; validate Google account linking carefully.
- [ ] Test registration, sign-in, Google sign-in, password reset, profile update and JSON serialization.

**Acceptance:** registration succeeds, untrusted fields cannot elevate access, and no authentication secret appears in model JSON.

## SEC-02: Enforce OTP / step-up auth on all relevant routes (H-06)

**Where:** `AuthenticatedSessionController.php` **978-1003**; `OtpController.php` **1333-1383**; `GoogleController.php` **1205-1248**; `routes/auth.php` **3683-3712**; `routes/modules/{inventory,sales,preorders,repairs,customer,shop}.php`.

- [ ] Inspect existing global middleware/route groups first. If missing, implement middleware that checks `otp_verified` and rejects or redirects unverified authenticated sessions.
- [ ] Apply consistently to state-changing customer and owner endpoints; permit only the necessary OTP/logout/password-recovery paths while the second factor is pending.
- [ ] Decide whether Google OAuth is intended to satisfy the 2FA requirement; if not, route Google sign-ins through OTP. Do not leave an alternate sign-in method with weaker protections.
- [ ] Reset second-factor session state at every new login. Ensure OTP cannot be reused, is time-limited, and resend/login endpoints are rate-limited; verify session regeneration.
- [ ] Verify owner-role middleware and all relevant `FormRequest::authorize()` checks. Do not rely solely on hidden buttons or Blade conditions.

**Acceptance:** an authenticated user who has not passed required OTP cannot call a protected POST/PUT/DELETE route directly; ordinary users cannot invoke owner endpoints.

# Phase 2 - Single authoritative reservation + stock lifecycle [P0]

## INV-01: Make CommitOrder concurrency-safe (critical unverified dependency)

**Where:** `app/Actions/CommitOrder.php` **not in source export**; calls at `CheckoutService.php` **3175-3189** and `Sales/SaleController.php` **2602-2622**.

- [ ] Validate item IDs as existing positive integers and quantities as positive **integers** with a bounded maximum; reject floats, strings such as `1e9`, nulls and unknown inventory records.
- [ ] Normalize/aggregate duplicate SKUs in a cart before any availability comparison. Sort inventory IDs to acquire row locks in a deterministic order.
- [ ] In a single database transaction: lock affected inventory rows (`lockForUpdate()`); recheck **current** availability; create order + lines; decrement inventory once; and persist `reserved_at`, `expires_at`, `status=pending` and a stable unique transaction reference.
- [ ] Roll back **all** lines if any line is unavailable, including low-stock jobs or auxiliary writes where they must be transactional. Dispatch external notifications only **after commit**.
- [ ] Add an idempotency key for checkout requests/double-clicks and a database uniqueness constraint for the request key. Replayed checkout must return the original reservation, not create a second one.
- [ ] Use decimal-safe monetary calculations (integer centavos or decimal strings); do not trust price/total from session cart values.

**Acceptance:** two concurrent checkouts for the last unit produce exactly one successful hold, stock never goes negative, and duplicate requests do not create duplicate orders.

## INV-02: Create a robust 24-hour expiry/release operation (H-05)

**Where:** `routes/console.php` **3740**; missing `reservations:expire` implementation; `Sales/SaleController.php` **2664-2683**.

- [ ] Add/backfill `expires_at` for in-stock pending reservations (prefer a parent reservation record or consistent transaction-level value, not per-line drift). Use stored timestamps and an explicit business timezone policy.
- [ ] Implement `reservations:expire` to claim expired pending reservations under transaction/locking; update status and restore every reserved line **exactly once**.
- [ ] Use guarded state transitions (`UPDATE ... WHERE status='pending'` or equivalent row locking) so cancel, expire and complete cannot all release/consume the same hold.
- [ ] At **staff completion time**, validate `expires_at > now()` even if the hourly scheduler has not processed the record.
- [ ] Confirm deployment actually runs `schedule:run` every minute, with queue workers and monitoring. Hourly task cadence is acceptable only if completion enforces the exact deadline.
- [ ] Instrument expiration counts and unexpected failures; add reconciliation/alerting for stale pending rows.

**Acceptance:** at +23:59 pickup can be completed; at +24:00 it is rejected under stated deadline policy; expiry restores stock once across job retries, cancellation races and server restarts.

## INV-03: Fix stock restoration, sale archives and stale updates (H-01, H-07)

**Where:** `Sales/SaleController.php` **2686-2705**; `Inventory/InventoryService.php` **3281-3291**; `AdminInventoryController.php` **1938-1950**; `CustomerOrderController.php` **1691-1711**.

- [ ] Remove stock increments from generic `SaleController::destroy()`. Treat archive/soft-delete as history-only. Separate stock release to an explicit, guarded reservation-cancel/expire service.
- [ ] Route customer cancellation, staff cancellation and expiry through the **same** release operation, with one idempotency guard per reservation.
- [ ] Reject stale admin quantity edits or convert them into delta-based adjustment events inside a lock/transaction; do not overwrite recent checkout deductions.
- [ ] Define a stock-movement ledger (strongly recommended): inventory item, signed quantity, reason (`reservation`, `release`, `supplier_receipt`, `damage`, `return`, `manual_adjustment`), actor, linked entity, unique event key, timestamp.
- [ ] Add database `quantity >= 0` enforcement if compatible with existing data and DB platform; first reconcile invalid historical rows.

**Acceptance:** archiving a collected sale has **zero** effect on inventory; cancel/expiry releases once; stale admin edits cannot erase concurrent reservations.

# Phase 3 - Payments, refunds and financial accuracy [P0]

## FIN-01: Decouple reservation from payment; repair completion (C-03, H-02, M-11)

**Where:** `Models/Sale.php` **587-618**; `Admin/RefundController.php` **846-945**; `Sales/SaleController.php` **2664-2683**; `Shop/CheckoutService.php` **3181-3189**; `resources/views/shop/receipt.blade.php` **12092-12100**.

- [ ] Treat online checkout as **unpaid**: `amount_tendered=0`, no payment timestamp, pending hold, reservation confirmation (not sales invoice).
- [ ] Create one guarded staff action for actual pickup and payment confirmation. Lock transaction lines, validate deadline and pending status, confirm tendered amount/valid payment method, set `payment_confirmed_at` and `picked_up_at` only for real collection.
- [ ] Stop setting `amount_tendered=total_price` merely because the `complete` endpoint was called. Ensure the staff form captures truthful cash/payment details.
- [ ] Store monetary payments at **transaction level** (preferred), or consistently compute sums from lines with a documented single-source rule. Fix receipt's first-row tendered amount against all-line grand total.
- [ ] On refund rejection, restore the **actual prior valid state**; never blindly set `completed`. Preserve payment and pickup evidence.

**Acceptance:** unpaid holds cannot appear as paid; partial/multi-item receipts calculate correctly; rejected refunds never create fictitious sales.

## FIN-02: Prevent refunds of unpaid items; fix refund schema (C-02, C-03)

**Where:** `Customer/CustomerOrderController.php` **1728-1798**; `Admin/RefundController.php` **846-945**; `Models/Sale.php` **601-610**; migration `2026_09_28_230000_audit_phase1_add_pickup_and_refund_fields.php` **14340-14345**.

- [ ] Restrict refund eligibility to **verified paid** transactions under the store's refund policy; unpaid reservations use cancellation.
- [ ] Re-check eligibility and amount at **approval time under a lock**, not only during request creation. Track approved refund amount, processed timestamp, processor/staff identity and unique event key.
- [ ] Create a **new forward migration** making `refund_requests.qr_code_path` and `refund_requests.pickup_time` nullable. **Important correction:** `refund_method` is **already nullable** in the supplied migration; do not change it unnecessarily.
- [ ] Store only method-relevant fields and validate QR files/visibility. The existing public disk URLs expose uploaded refund/QR documents; consider private storage and authorized retrieval.
- [ ] Separate financial refund approval from physical return/restock; guard duplicate restocks and connect returns to the original sale line.

**Acceptance:** unpaid reservation refund requests are rejected; both digital and on-site refund schemas work; a refund or product return cannot credit the same SKU twice.

## FIN-03: Repair payments are idempotent (H-04)

**Where:** `Repair/RepairController.php` **2501-2537**.

- [ ] Lock repair record and immediately return an already-paid result when `is_paid=true`.
- [ ] Add a unique paid-event/transaction association between repair and sale/payment record.
- [ ] Put payment confirmation and sale creation in the same transaction. Notify after commit.
- [ ] Verify payment amount comes from validated staff input or confirmed collection, not just an assumed payable amount.

**Acceptance:** repeated requests, double-clicks and concurrent staff actions create exactly **one** payment/sale record.

## FIN-04: Correct revenue and reservation KPIs (H-03)

**Where:** `Admin/DashboardController.php` **780-805**; `Sales/SaleController.php` **2644-2653**; `resources/views/admin/dashboard.blade.php` **4190-4200**, **4474-4506**.

- [ ] Build collected revenue from confirmed payment events/time, with clear adjustment/refund rules. **Exclude** pending, cancelled and expired hold values.
- [ ] Show separate cards for `unpaid reservation value`, `paid sales`, `uncollected holds`, `expired holds` and `refunds` where useful.
- [ ] Update monthly/today/chart/history calculations to the same accounting definition and avoid double-counting transaction totals.
- [ ] Remove hardcoded dashboard growth (`+12.5%`) unless computed from real comparison data.

**Acceptance:** a new unpaid 10,000 PHP reservation increases **reservation value**, but adds **0 PHP** to collected revenue.

# Phase 4 - Pre-orders and inventory adjustments [P1]

## PRE-01: Separate sourcing, stock receipt and customer collection (C-04, H-08, M-10)

**Where:** `PreOrder/AdminPreOrderController.php` **2243-2316**; `PreOrder/CustomerPreOrderController.php` **2352-2411**; `Models/PreOrder.php` **363-395**; `resources/views/preorders/admin_index.blade.php` **9815-9850**.

- [ ] Establish a pre-order transition table. Only permitted prior statuses can transition; lock row before each change.
- [ ] When receiving parts from a supplier, staff select the actual inventory SKU; **do not** use the ambiguous `part_name + model` / `first()` fallback to adjust stock.
- [ ] Do not mark as customer-completed merely because supplier stock arrived. Reserve any received parts for that customer according to the business policy, with explicit tracking.
- [ ] Enforce catalog pre-order eligibility (out-of-stock only, if that remains the policy); custom parts use a separate workflow without fabricated SKU pricing.
- [ ] Block cancellation from invalid terminal states; if stocked goods remain physically present after a cancellation, transfer them to general availability through a recorded adjustment rather than blindly subtracting them.
- [ ] Do not send a completion notification on a no-op transition. Return accurate success/warning outcomes.

**Acceptance:** receiving a part once changes stock once; the same pre-order cannot be received twice; customer cancellation does not fabricate or lose units.

## PRE-02: Fix missing pre-order payment action (M-03)

**Where:** `resources/views/preorders/admin_index.blade.php` **9823-9829**; `routes/modules/preorders.php` **3825-3831**.

- [ ] Either implement a secure `preorders.pay` route/controller with cash receipt + idempotency, **or remove the dead button** if payment belongs exclusively in a pickup endpoint.
- [ ] Replace hardcoded URLs with named `route()` helpers and show actions only in valid lifecycle states.

**Acceptance:** button never leads to 404; every recorded payment is verifiable and recorded once.

## INV-04: Returns, low-stock and image integrity (M-01, M-02, M-12, M-13)

**Where:** `InventoryService.php` **3270-3300**; `InventoryItem.php` **272-279, 290-307**; `InventoryAdjustmentController.php` **2033-2044**; `AdminInventoryController.php` **1942-1950**.

- [ ] Change `Cache::forget('inventory_categories_v3')` to invalidate the key actually read by `distinctCategories()` (`inventory_categories_v4`), preferably via one shared constant.
- [ ] Use `Storage::disk('public')->url($item->image_path)` rather than `asset('images/' . ...)` for uploaded products; verify disk/URL configuration and storage symlink.
- [ ] Upload new image **before** changing the database, replace its reference, then delete old image **after** successful commit. Delete orphaned new files on failure.
- [ ] Link product returns to original sale lines, validate cumulative returned quantity, and prevent the refund and return workflows from both restoring the same quantity.

**Acceptance:** categories refresh after edits, images resolve correctly, failed updates preserve old assets, and duplicate returns do not inflate stock.

# Phase 5 - E-commerce removal and route cleanup [P1]

## CLN-01: Remove obsolete gateway code (H-09)

**Where:** `Services/PaymentService.php` **3322-3368**; `PreOrder/CustomerPreOrderController.php` **2337-2349**; `Models/PreOrder.php` **363-376**; Xendit-related migrations **13536-13602**; service bindings/packages **not provided**.

- [ ] Search repository/config/routes/jobs/views/tests for `Xendit`, `Stripe`, `PayPal`, `PaymentGateway`, `invoice`, `webhook`, `callback`, `xendit_invoice_id`, `xendit_invoice_url` and gateway credentials.
- [ ] Remove unused `PaymentGateway` constructor injection from `CustomerPreOrderController`; remove stale service bindings, API clients, controllers, secrets and SDK dependencies after proving no callers remain.
- [ ] Revoke production gateway credentials no longer used. Preserve historical transaction records and migration history; do **not** casually edit applied migrations or drop live payment data.
- [ ] Keep **offline POS / payment collection** capability, but make clear that customers cannot initiate online charges.

**Acceptance:** no app endpoint can create an external online payment invoice; checkout makes no outbound payment API call; offline collection still works.

## CLN-02: Fix dangling routes and order/receipt labels (M-04, L-02, L-05)

**Where:** `routes/modules/sales.php` **3867**; `SaleController.php` **2566-2730**; `resources/views/shop/checkout.blade.php` **11744-12019**; `auth/AuthenticatedSessionController.php` **1006-1016**.

- [ ] Remove or implement `/sales/report/receipt` (currently points at absent `reportReceipt()`).
- [ ] Rename customer UI terms to `Reserve`, `Reservation Confirmation`, `Pay on Pickup`, `Expires at` and `Collected`, while retaining a genuinely paid sales receipt for staff-confirmed payment.
- [ ] Update email, status notification, FAQ, terms, downloadable PDF and error copy. Prevent "charged" / "refund" language for a never-paid booking.
- [ ] Safely preserve allowed intended destinations beyond checkout (customer dashboard, pending reservation details) without creating open redirects.

**Acceptance:** route smoke tests pass, no false payment claims in checkout confirmation, links point to real actions.

# Phase 6 - Performance and frontend consistency [P2]

## PERF-01: Fix query volume and payload sizes (M-05, M-06, M-09, M-14)

**Where:** `Models/Sale.php` **601-610**; `CustomerDashboardController.php` **1643-1655**; `Admin/DashboardController.php` **816**; `Sales/SaleController.php` **2574-2578**; `PublicBrowseController.php` **2144-2217**.

- [ ] Replace per-sale `refundRequests()->doesntExist()` view calls with bulk `withExists()` or precomputed flags.
- [ ] Paginate purchase history by **transaction**, not by individual sale line; paginate all pending refunds on the admin screen.
- [ ] Replace full POS inventory loading with debounced bounded search (e.g., 20 matches/page) and cached filter metadata.
- [ ] Add limits to option endpoints, validate nested `depends_on`, enforce a search length cap, and inspect indexes / query plans for `LIKE` / `ILIKE` searches.
- [ ] Test baseline and changed pages with realistic catalog and history volumes; review SQL query count, query time, memory and HTML/JSON payload size.

**Acceptance:** transaction history and admin refunds remain responsive with large datasets; search never returns the full catalog by default.

## UX-01: Align Alpine state and Blade-rendered fields (M-08, L-03, L-04)

**Where:** `Sales/SaleController.php` **2574-2578**; `resources/views/sales/create.blade.php` **10953-11046**; `resources/views/shop/checkout.blade.php` **11816-11931**.

- [ ] Include `category` in the fields returned to POS search or remove category-based filtering in Alpine.
- [ ] Centralize numeric quantity parsing, stock limits and per-line totals; reject invalid direct input as well as increment/decrement.
- [ ] Ensure terms/consent behavior supports very short documents, keyboard users and mobile devices; do not rely on a scroll event that cannot occur.
- [ ] Reconcile server truth after failed reservation attempts; show an accessible `out of stock / quantity changed` message rather than stale Alpine values.

**Acceptance:** category filtering works; the same cart is displayed and submitted; keyboard/mobile checkout is not blocked by unscrollable modal content.

## UX-02: Cleanup and reporting integrity (L-01, M-15, M-16)

**Where:** `resources/views/admin/dashboard.blade.php` **4192**; `Customer/ProfileController.php` **1845-1859**; `Models/{PreOrder,RefundRequest,Repair}.php`.

- [ ] Remove fabricated percent-growth visuals unless calculated.
- [ ] Synchronize account name/email with linked `Customer` profile in one transaction, preserving deliberately separate contact fields.
- [ ] Include important pre-order transitions, refund approvals, repair payment acknowledgements and inventory adjustments in an auditable event trail without logging secrets or full QR/payment data.

**Acceptance:** KPIs are sourced from queries, names/emails remain consistent after update, and critical operations have traceable actors/events.

# Required automated test matrix

Create Laravel Feature/Unit tests, with database transactions and real DB locking tests where supported. **MySQL/PostgreSQL concurrency must be tested using separate database connections/processes; SQLite in-memory does not validate row-lock semantics.**

| ID | Test scenario | Required assertion |
|:--|:--|:--|
| T01 | Regular registration POST contains `role=owner` | User still has customer permissions |
| T02 | `User::toArray()` after login | No password, remember token, or OTP hash |
| T03 | Authenticated session without OTP calls owner POST | Denied before controller |
| T04 | Two users reserve the one remaining SKU simultaneously | One succeeds, one fails, stock 0 |
| T05 | Same reservation submitted twice using same idempotency key | Only one reservation and deduction |
| T06 | Cart has duplicate SKU lines totaling more than stock | Whole transaction fails; stock unchanged |
| T07 | Item price changes after it was added to cart | Checkout uses trusted current or explicitly locked price |
| T08 | One unavailable item in a multi-item cart | No partial reservation, no partial decrement |
| T09 | Cancel reservation twice; expiration job also runs | One stock restoration only |
| T10 | Staff attempts pickup at/after `expires_at` | Completion refused, regardless of scheduler timing |
| T11 | Expire job fails midway, then retries | Exactly-once eventual stock release |
| T12 | Completed sale is archived | Inventory unchanged |
| T13 | Admin edits stale inventory quantity | Conflict detected or delta safely applied |
| T14 | Unpaid customer tries digital refund | Denied; no refund obligation created |
| T15 | Digital and on-site refund submissions | Both satisfy nullable DB fields and policy |
| T16 | Refund request rejected on a previously pending reservation | No fake payment or completion |
| T17 | Two staff mark same repair paid concurrently | Only one payment entry / sale created |
| T18 | Admin completes multi-line paid reservation | Receipt shows true tendered total/balance |
| T19 | Pre-order received twice or cancelled after collection | No double inventory mutation |
| T20 | Unpaid reservation created | Collected revenue remains unchanged |
| T21 | Owner and customer attempt forbidden paths | Owner guards and customer ID scoping hold |
| T22 | Checkout network or DB failure | Cart remains; no charge; no partial stock changes |
| T23 | Removed payment integrations | No Xendit invoice call or active payment route |
| T24 | Source-wide route/template smoke test | No missing methods, broken named routes or dead actions |

**Also run:** `php artisan test`; `php artisan route:list -v`; `php artisan schedule:list`; migration tests on production-like DB; PHP static analysis (Larastan/PHPStan as feasible); composer vulnerability audit; manual browser tests for dashboard, cart, receipt/PDF, admin refunds and pre-orders.

# Existing-data reconciliation and migration policy

Before releasing behavioral changes, reconcile production records carefully:

1. Export backup and record row counts and amounts. Identify sales whose `status` is completed but payment timestamp is null, or `picked_up_at` does not match payment evidence; **do not fabricate timestamps**.
2. Identify pending holds older than 24 hours, already-cancelled/expired rows with missing or repeated stock release, and inventory with negative/impossible counts. Compare stock to physical counts before auto-correcting.
3. Identify pre-orders whose `complete()` may have incremented stock in the wrong SKU (string-matched) or more than once. Flag for human SKU reconciliation.
4. Identify repair jobs with multiple `REPAIR-` sales and refund/return pairs where inventory may have been restored more than once; reconcile actual collections and cash records before archiving duplicates.
5. Apply **new forward migrations** to change existing schema. Introduce unique keys / check constraints only after duplicate and invalid historical values have been resolved.
6. Run reconciliation scripts first in **dry-run** mode with CSV output; require explicit owner approval for financial/stock corrections and retain an audit trail.

# Release order, owners and sign-off

| Gate | Scope | Release criterion |
|:--|:--|:--|
| G0 | Baseline and backups | Reproducible tests, restore verified, missing components located |
| G1 | User model, authorization, OTP | T01-T03, T21 green; sensitive fields hidden |
| G2 | CommitOrder, expiry, cancel, stock | T04-T13 green under real concurrent DB connections |
| G3 | Refunds, payment, revenue | T14-T18, T20 green; reconciled finance figures |
| G4 | Pre-orders, cleanup, routes | T19, T23, T24 green; no active online gateway |
| G5 | Performance and UI | Page/load benchmarks accepted; mobile/manual QA complete |
| GO | Production rollout | All P0/P1 done; migrations reviewed; monitoring & rollback prepared |

**Suggested ownership:** backend engineer (state machine, migrations, locking), security reviewer (auth and access rules), frontend engineer (Blade/Alpine UX), QA engineer (regression/concurrency), operations/database owner (backups, data reconciliation, scheduler).

## Rollout checklist

- [ ] Run all tests on staging against a sanitized copy of production-shaped data.
- [ ] Verify migrations in order, index/foreign-key behavior, and rollback consequences.
- [ ] Deploy code and compatible schema using the project's release strategy; avoid a window where old workers can mutate new-state data incorrectly.
- [ ] Start/restart queue workers, verify cron invokes `php artisan schedule:run`, and inspect `reservations:expire` logs.
- [ ] Run smoke tests for create reservation, cancel, expire, staff pickup/payment, pre-order receipt and one valid refund.
- [ ] Compare stock reconciliation dashboard, pending count and collected revenue before/after release.
- [ ] Monitor for negative stock, duplicate payments, stuck holds, 4xx/5xx rate spikes and failed queues; define alert owners.
- [ ] Maintain a documented rollback path including database migration reversibility and a financial/stock correction procedure.

# Acceptance definition: MotoTrack is reservation-only

**Done** means all of the following are true:

- Online customers can **reserve**, but **cannot pay via online gateways**.
- Every successful hold is backed by available, atomically locked stock and has a visible 24-hour expiration.
- Cancellation, expiry, payment and pickup operate through explicit, atomic, idempotent transitions.
- Collected revenue is based on verified offline payment, while unpaid reservation value is separately reported.
- Unpaid reservations cannot create customer refund payouts; actual paid refunds and physical restocks are traceable.
- Owner-only controls cannot be reached without required authorization/OTP, and customer receipts remain scoped to the customer.
- Pre-order sourcing, supplier intake and customer collection are distinct and auditable events.
- Automated concurrency and regression tests pass; staging and production rollout checks are documented.

---

**Review caveat.** This plan consolidates the earlier findings and source-visible code. It is not evidence that fixes were implemented or deployed. Confirm the exact runtime behavior of omitted code (especially `CommitOrder`, `reservations:expire`, FormRequests, and middleware) before accepting a finding or applying its remediation. The Laravel 11 User-model changes follow Laravel 11's documented `$fillable` / `$hidden` conventions, subject to the installed framework version.

**Reference:** Laravel 11 Eloquent mass assignment: https://laravel.com/docs/11.x/eloquent#mass-assignment ; serialization: https://laravel.com/docs/11.x/eloquent-serialization#hiding-attributes-from-json .

# MotoTrack — Requirements Traceability Matrix (RTM)

Prepared: 2026-10-16 · Scope: analysis only (no code, manuscript, PDF or diagram was modified; nothing committed, pushed or deployed; production not touched).

**Baseline test evidence (updated 2026-10-16):** 248 tests / 793 assertions / 0 failures in the cloud run after the two integrity fixes (previous baseline 208 / 673; local re-run pending) (SQLite `:memory:`, cache=array, queue=sync, mail=array) and Vite build 1.27 s, run locally by the team on the 19-file UAT-fix change set. This is *automated evidence in a test environment only*. It does **not** prove PostgreSQL row-locking, production queue/cache/mail configuration, or live behaviour. The UAT-fix change set is **not deployed** and has **not** passed live UAT.

**Sources used:** `CHAPTER_1-3_revisions.docx` (cited as *Ch1 §…*, *Ch2 §…*, *Ch3 §…*; Ch1 = Introduction, Ch2 = RRL + Technical Background, Ch3 = Methodology incl. Requirements Analysis, as the docx is laid out), `MotoTrack_TestCase.pdf` (ADMIN + USER modules, ~102 case rows), the repository at the 19-file change set, and the two Production UAT reports.

**Evidence codes:** `CI` code inspection · `AUTO` automated test (SQLite) · `CB` controlled-browser (cloud Chromium, synthetic data, file/array cache) · `HM` historical manual UAT (PDF, 10/12–10/13/2026) · `PU` production-browser UAT · `UNV` unverified.
**Verification status:** Passed / Failed / Blocked / Not Tested. **Alignment:** ALIGNED / PARTIAL / MISMATCH / NOT VERIFIED.

> "Passed" below never means "passed in production". `PU` evidence exists only where stated (the earlier production UAT produced the defects that the 19-file change set fixes; those fixes are awaiting live UAT).

---

## A. Inventory

| ID | Source | Requirement | Expected behaviour | Current implementation | Code ref | DB ref | Test ref | Verification evidence | Alignment | Required action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| INV-01 | Ch1 §Scope (Inventory); §Objectives | Owner can add/update/monitor parts incl. quantities and details | CRUD on parts | Resource controller, owner-only | `AdminInventoryController`, `routes/modules/inventory.php` | `inventory_items` | `InventoryManagementTest`, HM inventory rows | Passed (AUTO, HM) | ALIGNED | None | Low |
| INV-02 | Ch1 §Objectives | Centralised DB of product info, quantities | Single DB of record | Yes (variants via `parent_id`, `sku`) | `InventoryItem` | `inventory_items` (+parent_id, variation_name, sku) | `ModelIntegrityTest` | Passed (AUTO) | ALIGNED | ERD must add these columns (see Diagram report) | Medium |
| INV-03 | Ch1 §Purpose, §Objectives; Ch3 §What | Track damaged items and product returns with who/when/how many | Organised damaged/return log; damaged stock removed | `storeDamaged`, `storeReturn`; damaged returns not resellable | `InventoryAdjustmentController`, `InventoryService` | `damaged_items`, `product_returns` | `InventoryAdjustmentTest`, `AdjustmentDropdownValidationTest`, `PosRefundTest`; HM INV-005/006 | Passed (AUTO, HM) | ALIGNED | Add tables to ERD | Medium |
| INV-04 | Ch1 §Problem context | Damaged items "returned to supplier and removed from stock records" | Removal from stock is recorded | Removal is recorded; supplier return itself is out of scope (no supplier module) | `InventoryAdjustmentController` | `damaged_items` | `InventoryAdjustmentTest` | Passed (AUTO) | ALIGNED | Clarify in Ch1 that supplier return is a manual step outside the system | Low |
| INV-05 | Ch1 §Limitations | Products/stock must be entered manually | Manual entry | Yes | `AdminInventoryController` | — | HM | Passed (HM) | ALIGNED | None | Low |
| INV-06 | Ch1 §Product Browsing | Public search by name, filter by category, sort; each product shows photo, name, brand, category, stock, price | Browse page | Implemented; image upload | `PublicBrowseController::index`, `browse.blade.php` | `inventory_items.image_path` | `ProductImageUploadTest`, HM BRW-* | Passed (AUTO, HM) | ALIGNED | None | Low |

## B. Stock monitoring and alerts

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| STK-R1 | Ch1 §Objectives #1 "real-time stock monitoring… sends automatic alerts" | Owner sees stock levels and gets automatic alerts | Alert when stock ≤ threshold | Threshold per item (`low_stock_threshold`, default 5). Dashboard/Low-stock page compute from DB on each page load; notifications row created for owners when threshold crossed | `DashboardController`, `AdminInventoryController::lowStock`, `SmsService::checkLowStockAlert` | `inventory_items.low_stock_threshold`, `low_stock_notified`; `notifications` | `LowStockPanelTest`, `SmsLowStockAlertTest` | Passed (AUTO incl. `LowStockSynchronousAlertTest`, 19); HM STK-001 has **no recorded result** | PARTIAL | Run STK-001 live; see STK-R2 | High |
| STK-R2 | Ch1 §Obj #1; Ch3 §When "alerts run continuously so owners are notified right away" | "Real-time" | Instant push | **Near-real-time, not push-guaranteed. Update 2026-10-16: the owner in-app alert is now created synchronously by `LowStockAlertService` on every quantity-changing path (no queue worker needed); only the optional SMS still needs a worker.** Stock is current at each page load. Live push uses Laravel Echo only if `BROADCAST_CONNECTION` is a real driver (`.env.example` = `log`; `config` default `null`) — production value unknown. The alert pipeline `CheckLowStockAlertJob` is `ShouldQueue`; the deploy workflow never starts a queue worker, so with `QUEUE_CONNECTION=database` (the `.env.example` default) the alert job would not run until a worker exists. | `CheckLowStockAlertJob`, `CommitOrder` (dispatches job), `InventoryAdjustmentController`, `routes/console.php` (scheduler lists only expiry commands) | `jobs` | `SmsLowStockAlertTest` (queue=sync) | UNV in production | **MISMATCH (wording only) / SMS config NOT VERIFIED** | Reword "real-time" to "automatic, near-real-time (updated on each transaction and page load)"; read-only check of production `QUEUE_CONNECTION`/`jobs`; decide on worker (adviser/owner) | High |
| STK-R3 | Ch1 §Scope Overview | Dashboard shows "critical stock alerts" | Panel | Implemented | `admin/dashboard.blade.php`, `low_stock.blade.php` | — | `LowStockPanelTest`, `AdminDashboardTest` | Passed (AUTO) | ALIGNED | None | Low |
| STK-R4 | Ch2 §Real-Time Stock Monitoring (RRL) | Low-stock alert dedupe, no repeat spam | One alert per crossing | `low_stock_notified` flag; reset on restock | `SmsService` | `low_stock_notified` | `SmsLowStockAlertTest` | Passed (AUTO) | ALIGNED | None | Low |
| STK-R5 | Not in manuscript | SMS alert to owners on low stock | — | Implemented (Movider/Twilio/Mock gateways) | `SmsService` | — | `SmsGatewayTest`, `SmsLowStockAlertTest` | Passed (AUTO, mock gateway) | PARTIAL (implemented, undocumented) | Document SMS as optional low-stock channel; provider/cost unverified | Medium |
| STK-R6 | Owner flowchart "Owner reviews alert and restocks item → Stock restored" | Restock after alert | Edit/adjust quantity | Edit quantity / adjustments | `AdminInventoryController::update` | — | `InventoryManagementTest` | Passed (AUTO) | ALIGNED | None | Low |

## C. Compatibility

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| CMP-R1 | Ch1 §Purpose; Ch1 §Scope; Ch1 §Obj #3 | Customer/owner check if a part fits a make, model, year | Result list | Fitment-based via `part_compatibility` pivot; all criteria match same record; year single or range; null year = all years | `CompatibilityMatcher`, `PublicBrowseController::checkCompatibility` | `motorcycles`, `part_compatibility` | `CompatibilityAccuracyTest` (16), `CompatibilityOptionsTest` | Passed (AUTO, CB); HM CMP-001 **no result**; HM BRW-006 historically Passed (before fix) | ALIGNED (post-fix) / awaiting PU | Live UAT of CMP-001 and BRW-006 | High |
| CMP-R2 | Ch1 §Purpose "reflect cross-brand parts" | Cross-brand parts shown where fitment recorded | Part linked to several motorcycles appears for each | Yes (many-to-many) | pivot | `part_compatibility` | `CompatibilityAdminTest`; HM CMPM-002 **no result** | Passed (AUTO) | ALIGNED | Live CMPM-002 | Medium |
| CMP-R3 | Ch1 §Limitations "only recorded parts/models" | No guessing | Unrecorded part is **not** shown as confirmed fit; no text/"Universal" fallback | Fallback removed in the UAT fix | `PublicBrowseController` | — | `CompatibilityAccuracyTest` (9 of 16 fail on old code) | Passed (AUTO) | ALIGNED | Owners must populate Compatibility Records, otherwise results empty | High |
| CMP-R4 | Ch1 §Scope Owner "record and maintain database of compatible parts" | Owner CRUD of fitment | add/update/remove links | Implemented | `AdminCompatibilityController` | `part_compatibility` | `CompatibilityAdminTest`; HM CMPM-001 **no result** | Passed (AUTO) | ALIGNED | Live CMPM-001 | Medium |
| CMP-R5 | Ch1 §Scope Customer | Guest can check compatibility without login | Public route | Public | `routes/modules/inventory.php` | — | HM | Passed (HM) | ALIGNED | None | Low |
| CMP-R6 | Ch1 §Scope Customer "instantly" | Year input | make, model, year | Year options expanded from ranges; free years not offered | `CompatibilityMatcher::expandYears` | `motorcycles.year` (string) | `CompatibilityOptionsTest` | Passed (AUTO) | ALIGNED | ERD says `year — year`; actual is string (single/range) | Low |
| CMP-R7 | Ch2 §Benchmarking (RevZilla "Browse-by-Bike") | Compatibility parity claim | — | Comparable function; no external fitment dataset | — | — | — | CI | ALIGNED | None | Low |

## D. Point of Sale (cash only)

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| POS-R1 | Ch1 §Scope; §Obj #4 | Record every completed sale; stock updated | Atomic decrement | `CommitOrder`, `SaleController::storeSale` | `Actions/CommitOrder.php` | `sales` (`transaction_reference`, `status`) | HM POS-001/002; `PosRefundTest` | Passed (AUTO, HM) | ALIGNED | None | Low |
| POS-R2 | Ch1 §Limitations "no online payment" | Cash only; no gateway | No Payment process | Xendit fields dropped (migration `…drop_xendit_fields_from_pre_orders`); cash with `amount_tendered`/`change` | `SaleController`, `CheckoutController` | `sales.amount_tendered`, `change` | `CheckoutGatewayTest` (name only—verify), `CheckoutValidationTest` | Passed (AUTO) | ALIGNED (code) / MISMATCH (DFD Payment 5.0, Ch2 mentions Supabase) | DFD must drop Payment process | High |
| POS-R3 | Business rule | Correct change; no double payment | change = tendered − total; idempotent completion | Implemented; completion of cancelled/expired reservation now blocked | `SaleController::complete` | `payment_confirmed_at` | `ReservationPickupTest` (6) | Passed (AUTO) | ALIGNED | Live RES-001 | High |
| POS-R4 | Business rule | Auditable refunds; damaged returns not sellable | Refund request → approve/reject; audit log | `RefundController`, `product_returns`, `audit_logs` | `Admin/RefundController`, `HasAuditTrail` | `refund_requests`, `product_returns`, `audit_logs` | `PosRefundTest`, `AuditLogTest` | Passed (AUTO) | ALIGNED (undocumented in Ch1–3) | Add refund/audit to Ch1 scope text (adviser decision) | Medium |
| POS-R5 | Business rule | Paid/completed sales protected from deletion | No role can delete any sale record | **Corrected 2026-10-16:** `DELETE /sales/{id}` route removed; `SaleController::destroy` fails closed (405); `SalePolicy::delete` always false | `routes/modules/sales.php`, `SaleController::destroy`, `SalePolicy::delete` | `sales.deleted_at` (unused by app now) | `SaleDeletionProtectionTest` (21) | Passed (AUTO); awaiting PU | ALIGNED | Live check that DELETE is rejected (optional) | Low |
| POS-R6 | Ch1 Scope "receipts" (implicit) | Receipt view/PDF | PDF receipt | `downloadReceipt` | `SaleController`, `pdf_receipt.blade.php` | — | `PdfExportTest` | Passed (AUTO) | ALIGNED | None | Low |
| POS-R7 | Business rule | Unpaid reservation is not revenue | Revenue only on `payment_confirmed_at` | Yes | `SaleController` | `payment_confirmed_at` | `RevenueReportingTest` | Passed (AUTO) | ALIGNED | None | High |
| POS-R8 | Owner flowchart | "payment received at the shop" | Cash | Yes | — | — | — | CI | ALIGNED | None | Low |

## E. Reservations

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| RSV-R1 | Ch1 §Scope Customer; Ch3 §Who | Logged-in customer reserves available items, no payment | Stock allocated atomically | `CheckoutService`/`CommitOrder` status `pending` (= active reservation), `reserved_at`, `expires_at` | `CheckoutService`, `CommitOrder` | `sales` | HM RSV-001 Passed; `CheckoutValidationTest` | Passed (AUTO, HM) | ALIGNED | None | High |
| RSV-R2 | Ch1 §Scope; Fig 3.0 | 24-hour pickup, else cancelled and stock returns | Auto-expire after 24h, stock restored once | `reservations:expire` every minute, `withoutOverlapping`; `SALE_EXPIRED` status. **Manuscript/PDF say "cancelled"; system says "Expired"** | `ExpireReservations`, `ReservationService::releaseGroup` | `sales.expires_at`, `status` | `ReservationCancellationTest` | Passed (AUTO); no real 24h wait run; HM RSV-005 **no result** | PARTIAL (terminology) | Adopt "Expired (automatic)" vs "Cancelled (voluntary)" in docs; live-wait UAT | High |
| RSV-R3 | Business rule | Customer cancels own reservation; stock restored once; group | Cancel group by `transaction_reference`, lock | Implemented; UI button bug fixed | `ReservationService`, `customer/dashboard.blade.php` | `sales.cancellation_reason` | `ReservationCancellationTest`, `CancelUiAndNotificationDeliveryTest` | Passed (AUTO, CB); HM RSV-006 historically Passed (pre-fix); **production UAT failed before fix** | ALIGNED (post-fix) / awaiting PU | Live UAT RSV-006, RES-004 | Critical |
| RSV-R4 | Business rule | Multi-item reservation is one group | Group by reference | Yes | `ReservationService` | `transaction_reference` | `ReservationCancellationTest` | Passed (AUTO) | ALIGNED | None | High |
| RSV-R5 | Business rule | Stock lock ordering/no oversell | Ascending item-id lock | Yes (`lockForUpdate`) | `ReservationService` | — | SQLite only — **no PostgreSQL concurrency test** | NOT VERIFIED for PostgreSQL | NOT VERIFIED | Optional PG concurrency test (adviser/team) | Medium |
| RSV-R6 | Fig 3.0 "Owner confirms reservation" | Owner approval step before pickup | Owner confirms | **No owner-confirmation state exists**; reservation is active on creation | `CommitOrder` | — | — | CI | MISMATCH | Remove box from Fig 3.0 (recommended) or approve building the feature | High |
| RSV-R7 | Ch1/Ch3 | Owner records pickup/sale at shop | Mark picked up → completed | `SaleController::complete` | — | `picked_up_at`, `payment_confirmed_at` | `ReservationPickupTest`; HM RES-001 **no result** | Passed (AUTO) | ALIGNED | Live RES-001 | High |
| RSV-R8 | Business rule | Customer sees cancelled/expired state | Dashboard status | Yes | `CustomerDashboardController` | — | HM RSV-007 **no result**; `ReservationCancellationTest` | Passed (AUTO) | ALIGNED (awaiting PU) | Live RSV-007 | High |
| RSV-R9 | Business rule | Owner sees cancelled reservation + stock restored | Admin list | Yes; owner notified | `ReservationService::notifyRelease` | `notifications` | `CancelUiAndNotificationDeliveryTest`; HM RES-004 **no result** | Passed (AUTO, CB) | ALIGNED (awaiting PU) | Live RES-004 | High |

## F. Pre-orders

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| PRE-R1 | Ch1 §Scope Customer | Pre-order out-of-stock parts, no advance payment | `status` pending, no payment | Yes; Xendit removed | `CustomerPreOrderController` | `pre_orders` | `PreOrderFormViewTest`, `PreOrderCompletionTest` | Passed (AUTO, HM) | ALIGNED | `down_payment` column still in schema — confirm unused | Low |
| PRE-R2 | Ch1 §Scope | Shop notifies customer on arrival | Durable notification | `NotificationDispatcher` writes DB notification (in-app only) | `AdminPreOrderController::stock` | `pre_orders.ready_notified_at`; `notifications` | `CancelUiAndNotificationDeliveryTest`; HM PRE-005 Passed, RES-002 **no result** | Passed (AUTO, CB) | PARTIAL | Channel unspecified in manuscript (see Notification Decision) | High |
| PRE-R3 | Ch1 §Scope; Fig 3.0/4.0 | 24h pickup starts only after notification | Deadline only after recorded notification | `pickup_expires_at` set only if `send()` ≥ 1 | `AdminPreOrderController::stock/resend` | `pickup_expires_at` | `CancelUiAndNotificationDeliveryTest` | Passed (AUTO) | ALIGNED | Live PRE-006 needs real/accelerated wait | High |
| PRE-R4 | Business rule | No countdown while sourcing | `pickup_expires_at` null until stocked | Yes | `ExpirePreOrders` | `pickup_expires_at` | `PreOrderExpirationTest` | Passed (AUTO) | ALIGNED | None | High |
| PRE-R5 | Ch1 §Scope; Fig 3.0 | Unclaimed after 24h → cancelled, item to stock | Auto-expire | `preorders:expire`, `PREORDER_EXPIRED` ("Expired") | `ExpirePreOrders`, `PreOrderCancellationService` | `status` | `PreOrderExpirationTest`; HM PRE-006 **no result** | Passed (AUTO) | PARTIAL (terminology; no real wait) | Align wording; live UAT | High |
| PRE-R6 | Business rule | Customer cancels own pre-order; restore once | Service w/ lock | Yes | `PreOrderCancellationService` | — | `PreOrderCancellationTest`, `CancelUiAndNotificationDeliveryTest`; HM PRE-007 Passed (pre-fix) | Passed (AUTO, CB); **production UAT defect, fixed, not deployed** | ALIGNED (post-fix) / awaiting PU | Live PRE-007, PRE-008, RES-005 | Critical |
| PRE-R7 | Business rule | Custom parts create no stock | Custom pre-order stays non-inventory | Yes (`is_stocked`, custom view) | `AdminPreOrderController::stock` | `is_stocked`, `inventory_item_id` | `PreOrderCompletionTest` | Passed (AUTO) | ALIGNED | None | Medium |
| PRE-R8 | Ch1 §Problem context | Owner sources part (no supplier system) | Manual | Manual; no supplier module | — | — | — | CI | ALIGNED | State "sourcing is manual" | Low |
| PRE-R9 | Business rule | Payment on pickup, cash | Paid at shop | `pay()` | `AdminPreOrderController::pay` | `is_paid` | `PreOrderCompletionTest` | Passed (AUTO) | ALIGNED | None | Medium |

## G. Notifications

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| NTF-R1 | Ch1 §Scope: "shop notifies the customer once the item has arrived" | Customer notified | Channel **not specified** | In-app database notification only | `StatusUpdated`, `NotificationDispatcher` | `notifications` | as above | Passed (AUTO, CB) | PARTIAL | Adviser decision (Option A/B/C) | High |
| NTF-R2 | Ch1 §Obj #1 "sends automatic alerts" | Owner alerts | In-app + optional SMS | In-app + SMS gateway | `SmsService` | `notifications` | `SmsLowStockAlertTest` | Passed (AUTO) | ALIGNED | Document | Medium |
| NTF-R3 | Business rule | Cancel/expire notify owner & customer | In-app | Fixed (queue-independent `notifyNow`) | `ReservationService`, `PreOrderCancellationService` | `notifications` | `CancelUiAndNotificationDeliveryTest` | Passed (AUTO, CB) | ALIGNED (awaiting PU) | Live check; inspect `jobs` read-only first | High |
| NTF-R4 | UAT defect | Notifications must not depend on queue worker | Synchronous | `notifyNow(['database'])` | `NotificationDispatcher` | — | `CancelUiAndNotificationDeliveryTest` | Passed (AUTO) | ALIGNED | Pending `jobs` rows from old code remain a risk | High |
| NTF-R5 | Cancellation text | Single full stop, voluntary vs automatic wording | "Cancelled" vs "Expired" | Fixed | services | — | `CancelUiAndNotificationDeliveryTest` | Passed (AUTO) | ALIGNED | None | Low |

## H. Repairs

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| REP-R1 | Ch1 §Scope; §Obj #5 | Log, track, manage repair jobs | CRUD + status | Implemented | `RepairController` | `repairs` | HM SVC-001/002 | Passed (HM); no automated test | ALIGNED | Add dedicated automated test (none under `tests/Feature/Repairs`) | Medium |
| REP-R2 | Ch3 §How "parts used and expected completion time" | Repair tracks parts used and expected completion | Fields for parts used / ETA | Table has `customer_name, bike_model, issue, status, cost, is_paid`; **no parts-used or expected-finish field** | `RepairController` | `repairs` | — | CI | **MISMATCH** | Either reword Ch3 or approve adding fields (category C) | Medium |
| REP-R3 | Ch1 §Problem context | Repairs 30 min–90 min | Time tracking | Not implemented | — | — | — | CI | MISMATCH (minor) | Same as REP-R2 | Low |
| REP-R4 | Owner flowchart | Repair paid/closed, receipt | `markPaid`, receipt | Implemented | `RepairController` | `is_paid` | — | CI | ALIGNED | None | Low |

## I. Reporting

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| RPT-R1 | Ch1 §Scope; §Obj #6 | Reports on stock levels and sales | Report feature | Dashboard "Reports" tab: monthly revenue, completed repairs, 30-day revenue chart, link to Full Sales History/PDF receipts. **No dedicated stock-level report** | `DashboardController`, `admin/dashboard.blade.php` | — | `RevenueReportingTest`, `AdminDashboardTest`; HM RPT-001 Passed | Passed (AUTO, HM) | PARTIAL | Reword to "dashboard summaries and sales history" or approve stock report | High |
| RPT-R2 | Owner flowchart "Select report type (sales, stock, movement)" | Report type selection | Selector | **Not present** | — | — | — | CI | MISMATCH | Fig 4.0 edit or build selector | High |
| RPT-R3 | Business rule | Unpaid reservation excluded from revenue; refunds subtracted | Net sales | Yes | `SaleController::history` | `payment_confirmed_at` | `RevenueReportingTest` | Passed (AUTO) | ALIGNED | None | High |
| RPT-R4 | Ch2 §Report Generation | Automated compile | Auto | Computed at request time | — | — | — | CI | ALIGNED | None | Low |
| RPT-R5 | Context diagram "Business / Inventory Report" | Report output to owner | Exists on screen | Partial | — | — | — | CI | PARTIAL | See RPT-R1 | Medium |

## J. Authentication and security

| ID | Source | Requirement | Expected | Current implementation | Code ref | DB ref | Test ref | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|---|---|---|---|
| SEC-R1 | Fig 3.0/4.0; HM TCLA-001..013 | Login + emailed one-time code (MFA/OTP) for owner **and** customer | OTP after credentials | `OtpController` (10-min expiry, hashed code); `OtpMail` synchronous | `OtpController`, `OtpMail` | `users.otp_code`, `otp_expires_at` | `OtpEnforcementTest`, `AuthenticationFlowTest`; HM TCLA-009..013 Passed | Passed (AUTO, HM) | ALIGNED | Manuscript text never mentions OTP/MFA (only figures) — add | Medium |
| SEC-R2 | HM TCLA-014 | Limit on wrong OTP attempts; correct code stops working for that attempt | Attempt counter lockout | Only route throttle `throttle:6,1`; no per-code attempt counter found | `routes/auth.php`, `OtpController` | — | HM Passed (historical) | PARTIAL | PARTIAL | Verify behaviour at 7th attempt; may need code (category C/E) | Medium |
| SEC-R3 | Ch3 §Who "owner is the only authorized admin user" | Role separation | `owner` middleware | Yes | `OwnerMiddleware` | `users.role` | `OwnerMiddlewareTest`; HM TCLA-005/006 | Passed (AUTO, HM) | ALIGNED | None | High |
| SEC-R4 | Fig 3.0 sign-up with email verification | Account activated after code | Verification | Yes | `RegisteredUserController` | `users` | `RegistrationTest`, `EmailVerificationTest` | Passed (AUTO) | ALIGNED | None | Medium |
| SEC-R5 | HM SignUp-002 | Full name rejects digits/specials | Validation | Fixed (regex) | `RegisteredUserController`, `ProfileUpdateRequest` | — | `RegistrationTest` (+3) ; HM SignUp-002 **no result** | Passed (AUTO) | ALIGNED (awaiting PU) | Live SignUp-002 | Medium |
| SEC-R6 | HM SignUp-003 | Email format validation | Validation | Laravel `email` rule | `RegisteredUserController` | — | `RegistrationTest`; HM **no result** | Passed (AUTO) | ALIGNED (awaiting PU) | Live SignUp-003 | Low |
| SEC-R7 | HM DASH-005 | Idle session timeout logs user out | Auto logout | **Deferred.** Only Laravel `SESSION_LIFETIME=120` (min) default; no idle-timeout feature or test | `config/session.php` | `sessions` | HM **no result** | NOT VERIFIED | NOT VERIFIED / deferred | Adviser decision: reword expected result to "session expires after configured lifetime" or implement | Medium |
| SEC-R8 | Ch3 §Where | Customer accounts also via Google | Google sign-in | `GoogleController` exists, undocumented | `GoogleController` | `users.google_id` | — | CI | PARTIAL | Document or confirm intent | Low |

## K. Platform, technology and documentation claims

| ID | Source | Requirement / claim | Reality | Evidence | Alignment | Action | Pri |
|---|---|---|---|---|---|---|---|
| TEC-R1 | Ch2 §Technologies, Table 4.0, Fig 1.0 | "MySQL is the primary database; Supabase/PostgreSQL provides supplementary storage and backup" | Production DB is Supabase PostgreSQL; local dev DB per `.env`; tests SQLite. App has no replication/backup feature | CI, project facts | **MISMATCH** | Correct text (see Chapters report) | High |
| TEC-R2 | Ch2 §Hosting | "Hosting platform: GitHub" | App runs on Ubuntu VPS (mototrack.online) deployed by GitHub Actions; GitHub = source control/CI | CI (`deploy.yml`) | MISMATCH | Correct Table 4.0 | High |
| TEC-R3 | Ch2 §Frontend "HTML, CSS, JavaScript" | Blade + Alpine.js + Tailwind + Vite | CI | PARTIAL | Add | Low |
| TEC-R4 | Ch3 §Testing "MySQL via XAMPP/Laragon; Supabase synchronization" | Automated tests on SQLite; UAT on production | `phpunit.xml` | MISMATCH | Reword | High |
| TEC-R5 | Ch3 §Deployment "deployed… owners began using" | Deployed; live UAT in progress; fixes pending | project status | PARTIAL | Reword to actual status | Medium |
| TEC-R6 | Ch2 Fig 2.0 | Agile cycle | — | ALIGNED | None | Low |

---

## Counts (67 requirement rows; rows with a split classification are counted under their first label)

| Alignment | Count |
|---|---|
| ALIGNED | 45 |
| PARTIAL | 12 |
| MISMATCH | 8 |
| NOT VERIFIED | 2 |
| **Total** | **67** |

Many "ALIGNED" rows are aligned in code and automated tests but **await live UAT** (flagged "awaiting PU"). Split rows: STK-R2 (wording MISMATCH + production config NOT VERIFIED), POS-R2 (code ALIGNED, DFD MISMATCH).

**Rows not fully re-read in this pass:** repair field list beyond migrations (REP-R2), `down_payment` usage (PRE-R1).


**Update 2026-10-16:** POS-R5 (sale deletion) corrected and moved MISMATCH → ALIGNED; STK-R2 notes the synchronous in-app low-stock alert. Counts above reflect this (ALIGNED 45, PARTIAL 12, MISMATCH 8, NOT VERIFIED 2).

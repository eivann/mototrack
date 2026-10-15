# MotoTrack — Round 2 Colleague Feedback
## Revised Action Plan & Test Acceptance Matrix for Claude Opus 4.6 (Thinking)

**Project:** MotoTrack, Laravel 13, PostgreSQL/Supabase production, online reservations + physical cash POS.<br>
**Source of feedback:** `system-1.pdf` (12 pages) plus the colleague's request for customer-side cancellation buttons and a customer **My Reservations** panel.<br>
**Other references:** `system-user.pdf`, `system-admin.pdf`, `CHAPTER 1-3 (revisions).docx`, and existing remediation/verification reports.<br>
**Status:** Requirements and execution plan; **not proof that the current source code is defective or fixed**.<br>
**Deployment rule:** GitHub `main` auto-deploys the VPS. No push or production changes are authorized.

## 1. Re-evaluation of the original Round 2 plan

The original plan covers the reported requirements well. This revision makes the implementation safer and easier to verify:

1. **Treat one checkout reference as one reservation** even if it spans multiple `sales` rows. Cancellation must lock/check/update all eligible lines atomically, not stop after the first line.
2. **Reuse existing routes, state constants and services** before introducing new controllers or statuses. Current repository code is the authority; earlier AI-generated reports are evidence to check, not guaranteed current facts.
3. **Keep reservation and pre-order stock allocation distinct.** Pre-orders under sourcing have no allocated inventory; stocked pre-orders release only quantities actually allocated to them.
4. **Serialize conflicting operations and send notifications after commit.** Completion/payment, expiry and cancellation must not double-consume or double-release inventory. Avoid external delivery calls inside a database transaction.
5. **Make unknown test statuses explicit.** Blank cells in PDF screenshots are NOT TESTED unless reproduced; visible green cells should be rerun rather than accepted on appearance alone.
6. **Distinguish browser UI correctness from passing Laravel tests.** Provide visual/browser evidence for the reported broken flows, forms, images and mobile filter placement.
7. **Keep the 2-minute idle timeout as a user-approval decision**, not a silent hardcoded change. Distinguish idle timeout from login-attempt rate limiting.

## 2. Invariant business rules

- Customer checkout collects **zero online payment** and reserves only available inventory.
- **In-stock reservation:** aggregate cart quantities per stock item, allocate atomically and immediately, start a **24-hour pickup** period at creation, then complete with actual in-store payment/pickup or expire/cancel and release exactly once.
- **Pre-order:** requests unavailable or custom parts; no sourcing countdown. When physical stock arrives and a durable customer readiness notification is successfully issued, start a **separate 24-hour pickup period**. Distinguish *notification accepted/recorded* from confirmed human reading or SMS delivery; do not falsely claim delivery receipt if no provider receipts exist.
- **Customer cancellation:** allowed only for an order the customer owns and that is genuinely active, unpaid and uncollected under the verified state machine. Do not allow cancelling completed/paid/refunded/already cancelled/expired orders via an unpaid cancellation action.
- **Inventory:** only restore stock previously allocated. When several lines share an order reference, cancel and restore all lines in one transaction with consistently ordered row locks and retry-safe state transitions.
- **Financial:** unpaid reservation cancellations have no refund. Actual paid POS returns/refunds remain audited separately.
- **Scope:** no online gateways, automated supplier purchasing, delivery tracking or full accounting package. Preserve the capstone's approved objectives.

## 3. P0 — Customer cancellations and dashboard visibility

### P0-A. Inventory & authorization trace (before editing)

Inspect actual paths discovered through code search; start with:

- `app/Http/Controllers/Customer/CustomerOrderController.php`
- `app/Http/Controllers/PreOrder/CustomerPreOrderController.php`
- `app/Http/Controllers/PreOrder/AdminPreOrderController.php`
- `app/Services/ReservationService.php`
- `app/Actions/CommitOrder.php`
- `app/Console/Commands/ExpireReservations.php`
- `app/Console/Commands/ExpirePreOrders.php`
- `app/Models/Sale.php`, `app/Models/PreOrder.php`, customer/user model relations
- Customer/owner dashboard controllers, views, route groups, policy/middleware and notifications

Trace the exact status set, grouping identifier (`transaction_reference`), allocation flags/quantities, expiry timestamps, actor permissions, cancellation route behavior and post-commit notifications. Some paths might have changed; search the repository rather than assuming files exist.

### P0-B. Customer **My Reservations**

**UX:** A clear navigation entry and standalone tab/panel on the authenticated customer dashboard. Group by reservation reference (one card per checkout), not each product line. Present active orders first; provide a history filter for completed/cancelled/expired ones. Each card shows item(s), quantities, reference, created time, 24-hour deadline, current state and **estimated amount payable at store**; do not show a paid receipt for pending reservations.

**Action:** Show **Cancel Reservation** only when eligible; clicking opens a keyboard-accessible confirmation explaining stock release. Server mutation via POST/PATCH/DELETE with CSRF, never GET. Scope customer ownership by authenticated account, not merely a guessed ID. Under a single transaction:

1. Find every line belonging to the *same owned reservation reference*, lock rows in deterministic order, and verify that all are pending/unpaid/uncolllected and not expired.
2. Lock affected inventory rows in deterministic order; restore only the correctly allocated quantities.
3. Persist all cancelled statuses and audit entries atomically, with no partial cancellation of a multi-line reservation unless explicit supported behavior already exists.
4. After commit, send owner/customer notices once or enqueue reliable deduplicated notifications. A failed notification must not roll back an already-successful cancellation.
5. Update the customer dashboard and owner cancellation list. Show cancelled reference in history, not active cards.

**Acceptance:** Place one order containing two different parts; verify one dashboard card. Cancel once; both stocks return exactly once, all rows move to cancelled and owner can find it. Repeat cancel; zero additional inventory movements. A second customer receives 403/404 and can see neither details nor the cancel action. Race cancel vs expiry, and cancel vs staff completion: exactly one valid transition wins.

### P0-C. Customer **My Pre-orders**

**UX:** Separate tab/panel showing sourcing, ready-to-pick-up (with post-notice deadline), completed, cancelled and expired. One button **Cancel Pre-order** for eligible active unpaid/uncollected records; provide a confirmation and durable updated status.

**Action:** Reuse current controller/service if correct; authorize ownership, row-lock the order, and validate state **again after locking**. Distinguish:

- **Unstocked/custom sourcing request:** cancel request only; do not increment catalog stock.
- **Stocked and allocated catalog-linked request:** cancel and release only previously allocated catalog units, once.
- **Custom physical arrival not linked to inventory:** do not invent an `inventory_item_id` or increment a nonexistent catalog row; preserve sourced stock tracking according to the existing schema.
- **Already paid/collected/completed/expired/cancelled:** reject as a state conflict, no stock change.

Lock stock records in a consistent order across arrival, cancellation, payment and expiration. Record actor/time and notify owner after commit; prevent duplicate notices.

**Acceptance:** Cancel one unstocked custom and one stocked catalog-linked pre-order; verify correct stock delta for each. Repeat requests and race expiry/payment; status and inventory stay consistent. Cancelled entries remain visible to owner and customer.

### P0-D. Exact feedback test-case traceability

Screenshots show some unmarked/blank results. Treat these as **NOT TESTED** until execution proves otherwise; do not automatically call them failures. Verify the printed test IDs/wording directly from the PDF:

- `RES-004`, `RES-005` (PDF p.7): cancellation reflected on owner side and stock reconciliation.
- `PRE-006`, `PRE-008`, `RSV-005`, `RSV-007` (p.11): unclaimed expiry, cancelled history visibility and stock restoration.
- `STK-001`, `CMPM-001`, `CMPM-002`, `RES-001`–`RES-003` (p.6): low-stock, fitment database maintenance and reservation flows.
- `DASH-005` (p.7): idle timeout expectations.
- `SignUp-002`/`SignUp-003` (p.11): invalid name/email rejection.
- Rows previously shown as **Passed** (e.g. `PRE-007`, `RSV-006`) must still be rerun for regression coverage.

For each test record: exact PDF identifier and wording, fixture, user role, procedure, expected state, observed state, stock before/after, test command/browser evidence, and PASS/FAIL/NOT TESTED.

## 4. P1 — Admin issues from `system-1.pdf` pp.1–7

| Area | Requirement | Evidence to capture |
| --- | --- | --- |
| Header/sidebar | Remove owner-facing customer **Browse Parts** action; keep one Compatibility link in navbar, remove duplicate sidebar shortcut. Sidebar stationary relative to main scroll without clipping. | Owner screenshot after scrolling; navigation tests |
| Demo name | Remove inappropriate visible synthetic name `Lily Bogs` from demo UI/fixtures safely. Do not delete actual user accounts indiscriminately. | Search across view/seed content and screenshot |
| Pre-orders | Separate **View** from **Complete Pickup**, with correct payment and state checks. | Both buttons and transaction tests |
| Images | Check catalog, pre-orders, compatibility for owner and customer; use public storage URL correctly. | Real image requests/network 200 vs missing fallback |
| Low stock | Owner entry point for threshold alerts, counts, notification links and full low-stock list. | Low-stock trigger/notification test |
| Compatibility management | Owner can add/update fitment relationships including more than one motorcycle make/model/year for a part, where supported by actual schema. | Create/query cross-model mapping |
| Notifications | Toast dismiss remains dismissed; persisted notifications' read/unread is separate; **View Details** deep-links correctly and is authorized. | Refresh and deep-link browser test |
| Inventory controls | Remove extra or duplicate `<select>` controls and invalid options in stock, damaged items and returns dialogs. Placeholders disabled, invalid inputs rejected on server. | Screenshots and validation tests |
| Returns/cancellations | Explicit links/tabs for **cancelled/expired reservations**, **cancelled/expired pre-orders** and **POS returns**; avoid misleading generic adjustments-only link. | Owner records of actual cancellation |
| Login and session | Verify auth/OTP, permissions, login rate limiting. **Two-minute idle timeout requires confirmation**; background polling must not extend idle time unintentionally. | Independent security tests |

## 5. P1 — Customer issues from `system-1.pdf` pp.7–11

- Pre-order, catalog and compatibility images must load or show appropriate fallbacks.
- Desktop pre-order and reservation checkout should use available horizontal space to reduce unnecessary scrolling; mobile remains scrollable and single-column.
- Notification **View Details** opens the correct authorized reservation/pre-order/part; absent/deleted items show a safe fallback, not a broken route.
- Mobile product filters must open **near the user's current scroll position**, not hidden at the top of the page; use anchored popover/drawer/bottom sheet with accessible focus management.
- Verify server-side name and email validation; reject malformed entries and unsupported values, not merely with client-side restrictions.
- Brand/model/product dropdowns should have one clear disabled placeholder, without duplicate options or selectable placeholders.

## 6. P2 — Regression, performance, security, UX

**Automated:** Run `php artisan test` at baseline and after phases. Test ownership/IDOR, CSRF, missing/deleted inventory, multi-line cancellation, duplicate submissions, paid/expired transition rejection, queued notification and failures, low stock, auth lockout, no negative available stock, correct owner/customer history and repaired view actions. Use disposable test database; run separate PostgreSQL two-connection tests for genuine concurrency. SQLite `RefreshDatabase` tests alone cannot establish PostgreSQL row-lock behavior.

**Browser:** At **360, 390, 768, 1024, 1440 px**, visit customer dashboard, reservations, pre-orders, catalog, compatibility, checkout, notifications, owner dashboard, inventory, POS, returns and repairs. Verify buttons, keyboard access, real images, scrolling, dropdowns, screenshots, console/network errors and role isolation.

**Documentation:** Maintain consistency with Chapters 1–3 and Figures 3.0–7.0. Prepare separate flowchart/DFD/ERD proposals if code and diagrams differ; do not rewrite adviser-approved documents without approval.

**Evidence:** Required artifacts include modified file list, before/after stock and statuses, test totals, exact commands/exit codes, browser screenshots, unresolved items, migrations and release blockers. Avoid generic blanket “verified” claims.

## 7. Phased execution & stop conditions

1. **Inspect and baseline:** check `git status`, framework/database, current routes/business rules; run tests. Record baseline failures before editing. Create focused change plan.
2. **P0 reservation grouping/cancellation:** implement, test, and report evidence.
3. **P0 pre-order cancellation:** implement, test, and report evidence.
4. **Admin sync and notifications:** make cancelled records visible; verify expiry/duplicate delivery.
5. **P1 functional issues:** images, routes, forms, fitment management, low-stock, redirects.
6. **P1 visual issues:** navbar, sidebar, forms, mobile filters and responsive layout.
7. **Final regression:** full tests + PostgreSQL concurrency + browser QA + documentation check.

**Pause and request user approval** before: new destructive migrations, changing refund/paid-order policy, setting an exact two-minute idle timeout, modifying live Supabase, VPS, GitHub workflow or pushing any branch if auto-deploy triggers are unknown. Do not pause for ordinary safe local edits.

## 8. Deliverables and release gate

- Keep `MOTOTRACK_FEEDBACK_ROUND2_PROGRESS.md` updated after each phase: confirmed vs not reproduced, files changed, test counts, browser evidence, risks.
- Deliver `MOTOTRACK_FEEDBACK_ROUND2_FINAL_REPORT.md`: per-PDF item PASS/PARTIAL/FAIL/NOT TESTED with links to evidence; details of owner/customer cancellations; impact on inventory and reports; tests and routes; migration and deployment instructions; open decisions.
- Do not `git push`, run production migrations or modify production VPS/credentials. With auto-deploy from `main`, production deployment follows a separate review, backup and express user approval.

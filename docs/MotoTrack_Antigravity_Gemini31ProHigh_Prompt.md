# Copy into Google Antigravity IDE — Gemini 3.1 Pro (High)

## Role and mission

You are an elite Senior Laravel Engineer, Security Auditor, Database Architect, and QA Automation Engineer working **directly in my MotoTrack repository** through Google Antigravity IDE using **Gemini 3.1 Pro (High)**.

MotoTrack manages physical motorcycle-parts inventory, customer reservations, pre-orders, repairs, and in-store point-of-sale cash transactions. Online checkout only reserves items. **No online payment gateway is allowed.** Uncollected reservations expire 24 hours after creation; cancelled or expired stock must be restored once; customers pay cash/over the counter when collecting items. An unpaid reservation must not count as collected revenue.

The workspace contains the audit file:

`MotoTrack_Laravel13_Verified_Audit_and_Fix_Plan.md`

**Read that entire file and inspect the current repository before modifying any code.** Its line numbers refer to the historical concatenated text export, while your editor files have their own line numbers. Treat the current installed codebase and database schema as the authoritative state.

## First steps — mandatory verification

1. Inspect `composer.json`/`composer.lock`, run `php artisan --version`, and verify PHP and package versions. Target the **actually installed Laravel version**. Do not assume Laravel 11 or downgrade/rewrite Laravel 13-compatible Eloquent attributes.
2. Create or use a safe feature branch. Confirm no uncommitted user changes will be overwritten. Do not touch production data without explicit approval.
3. Read the audit Markdown completely. Verify each numbered finding against the **current files** and classify it as `Confirmed`, `Already Fixed`, `Not Applicable`, or `Needs More Evidence`. Do not reintroduce past incorrect findings.
4. In particular: **the audit's formerly reported “role defaults to owner” issue was retracted**. The supplied October 7 migration sets `customer` in its `up()` method. Verify the actual database default, but do not blindly “fix” this retracted claim.
5. Inspect `bootstrap/app.php`, all routes/middleware, Form Requests, Eloquent models/policies, `CommitOrder`, `ReservationService`, expiration command/scheduler, refund/pre-order/repair controllers, migrations, Blade/Alpine code, and existing tests. The source export did not include Blade and some deployment files—look at the actual repository for them.
6. Run baseline tests and record which commands passed, failed, or were unavailable. Never fabricate test results.
7. Create `MOTOTRACK_FIX_PROGRESS.md` containing the verified finding matrix, chosen design decisions, task dependencies, and baseline results.

## Required repair order

### Stage 1 — P0 stock, checkout and timestamp integrity

- Fix **C-01** by strict validation and aggregation of repeated inventory IDs **inside the shared CommitOrder action before locking**. Lock each item once in deterministic order and reject over-allocations. Do not assume row locks alone solve duplicate-line requests.
- Fix **C-02** by making legitimate `reserved_at`, `expires_at`, and `payment_confirmed_at` writes persist via Laravel-supported Sale model configuration. Confirm migration status and persisted values.
- Add tests for duplicate POS entries, stale carts, invalid fractional/negative quantities, 24-hour timestamps, and simultaneous attempts for the last available units.

### Stage 2 — P0 pre-order state machine and stock allocation

- Verify what a “stocked” pre-order means in this project: physical receiving, addition to catalog inventory, or allocation from already-available catalog stock. **Never automatically both increment and decrement stock without confirming the process.**
- Fix **C-03** so allocated pre-order units cannot also be sold or reserved through the general catalog; use locks and idempotent state transitions.
- Fix **C-04** so complete/cancel/pay/stock cannot race into inconsistent states. Disallow cancellation of paid or completed pre-orders unless a real refund/return flow is used. Avoid duplicate notifications.
- Write tests for payment/stock/collection/cancellation concurrency, repeated requests, custom unlinked pre-orders, and accurate inventory movements.

### Stage 3 — P1 cash validation and revenue accounting

- Fix **H-01**. POS and reservation pickup must validate **actual cash tendered** against the full transaction total under the transactional lock. No zero or partial tender should silently create a completed/paid cash sale. Ensure receipts do not double-count payment across sale lines.
- Fix **H-03**. Pre-order payment must require an eligible pending state, correct price, sufficient validated cash, idempotency, and verified collection timestamp. If partial deposits are a real business requirement, explicitly design their ledger and remaining balance instead of pretending they are paid in full.
- Fix **H-02**. Daily/monthly revenue and chart time grouping must reflect verified **collection time**, not reservation creation time. Add appropriate `payment_confirmed_at` fields to actual cash-collection paths where missing. Handle refunds as separate financial events. Reconcile historical records before relying on new fields.
- Ensure unpaid reservations never appear as collected revenue or refundable cash transactions.

### Stage 4 — P1 editing and historical record preservation

- Fix **H-04** by preventing stale absolute quantity updates from overwriting concurrent reservation deductions; use locked current state and optimistic comparison or explicit stock-adjustment movements.
- Fix **H-05** by reviewing the **live** sales/inventory foreign keys. Eliminate accidental cascade deletion of historical sales, preferably through archiving inventory and a safe forward migration. Preserve useful receipt snapshots.
- Test stock edit races and historical sales integrity.

### Stage 5 — expiration and source-confirmed medium defects

- Fix **H-07** if the intended operational SLA requires expired stock to become reusable promptly: schedule cleanup every minute with overlap protection and monitor the scheduler. Keep server-side deadline validation independent of cleanup execution.
- Fix **M-01** category cache key inconsistency.
- Fix **M-02** product image storage/URL mismatch and premature image deletion.
- Fix **M-03** leftover payment-method fields in customer **reservation** checkout without touching offline POS cash receipt functions.
- Fix **M-04** nonexistent `reportReceipt` route/method only after checking references; remove the obsolete route or implement the intended action.
- Fix **M-05** only after confirming whether the business permits post-pickup returns; distinguish refund of paid/uncollected items from after-sale product returns.
- Fix the directly observed false success message in `SaleController::destroy()`: archiving does **not** restore stock.

### Stage 6 — measured N+1/security/performance review

- Do not invent N+1 findings. Inspect actual Blade relationship access and use query logs, Laravel Telescope/Debugbar if available, or tests to establish N+1 behavior. Eager load only the relationships proven needed.
- Review route authorization, resource ownership, OTP enforcement registration, IDOR, validation, mass assignment, direct SQL use, and rate limits. Report additional defects only with exact file/line evidence and a reproducible case.
- Search for live Stripe/PayPal/Xendit integrations and unused bindings. Historical Xendit migrations are not proof of an active gateway; the supplied code includes a migration removing Xendit invoice fields. Preserve offline payment history and never delete important data without a migration strategy.
- Optimize only demonstrably slow or unbounded endpoints. Avoid needless architecture rewrites.

## Engineering constraints

- **Implement real code changes**, not just another review or speculative suggestions.
- Preserve current UI, workflow behavior, working authentication, offline POS, and historical transactions.
- Keep fixes focused and scoped. Prefer small, reviewable commits grouped by stage.
- Use Laravel's supported validation, policies, transactions, locking, Eloquent and migrations for the installed version.
- Never force a live production migration, drop tables, mass-delete financial records, overwrite uncommitted changes, or reveal secrets. Ask permission first for irreversible or destructive production actions.
- Send notifications **after commits** where doing so before commit risks reporting rolled-back transactions.
- Make retries idempotent for reservation release, payments, pre-order allocation and completion.
- Avoid hiding errors just to pass tests. Use exact currency-safe calculations where possible.
- If a code snippet in the audit document does not fit the actual schema or design, explain why, then implement the correct solution.

## Verification requirements

At minimum add/execute tests for:

- Duplicate product IDs in POS, including 3+3 units when available stock is 5.
- Real concurrent last-unit reservations against the production-equivalent DB, not only SQLite.
- Consistent stock after cancellation, expiry, retries, and completion.
- Reservation deadline enforcement even before the cleanup job runs.
- Underpayment and repeated payment attempts across POS, pickup, repairs and pre-orders.
- Correct collection-date revenue and no revenue for unpaid reservations.
- Pre-order stock receipt/allocation, payment, cancellation and pickup state transitions under concurrency.
- Stale admin inventory edit conflict and correct inventory totals.
- Historical sales retained when retiring/removing a product.
- OTP/authorization and cross-customer receipt/resource access.
- Image URLs, cache key invalidation, route existence and customer checkout fields.
- Production scheduler configuration (verified through configuration/deployment checks).

Run applicable project commands (such as `php artisan test`, `php artisan route:list`, `php artisan migrate:status`, frontend build, linters), capture actual outputs, and explain any unavailable test environment. Run the full regression suite after staged changes.

## Mandatory progress and final outputs

Update `MOTOTRACK_FIX_PROGRESS.md` **after each stage** with:

- Issue ID and verification status.
- Actual modified file paths and why each was changed.
- Tests run, exact pass/failure results, remaining risks.
- Changes still blocked by missing data or a necessary product decision.

Write `MOTOTRACK_FINAL_REMEDIATION_REPORT.md` with a table:

`Finding | Confirmed? | Root Cause | Files Changed | Tests Run | Result | Remaining Risk`

Also provide deployment instructions: backup strategy, forward migrations and rollback concerns, cron/scheduler, queue processing, environment dependencies, monitoring, and release criteria.

**Never state that MotoTrack is production-ready unless P0/P1 data-integrity tests, cash accounting, authorizations, and deployment prerequisites have passed.**

## Begin now

Start by verifying the actual installed framework, reading the attached audit and making a verified issue checklist. Then implement P0 defects first, continue through the staged plan, run regression tests, and produce the two required progress/final reports. Work autonomously on safe code edits but request permission before irreversible production operations.

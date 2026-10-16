# MotoTrack — Production UAT Final Report

**Date:** 2026-10-16 · **Tests:** 207 passed, 666 assertions, 0 failed — run in the cloud copy AND, per the user, directly in their Windows PowerShell on `E:\laragon\www\mototrack` (SQLite `:memory:`; production DB untouched) · **Build:** `npm run build` passed in 1.56 s (user-run, local)
**Status:** changes are NOT committed/pushed/deployed. Historical PDF results are preserved; the table below adds a *current verification* column.

## Per-case results
Legend: A = automated test, B = browser (headless Chromium, synthetic SQLite DB, 1280px + 390px).

| ID | Reported problem | Root cause | Files | Evidence | Status |
|---|---|---|---|---|---|
| RSV-006 | Cancel reservation stuck on "CANCELLING…" | Alpine `@click` disabled button before form submit → no request | `customer/dashboard.blade.php` | B: 1 POST, confirm, group cancelled, stock restored; A: markup regression + existing 12 cancel tests | **PASS** |
| RSV-007 | Cancelled not reflected | same | same | B: card leaves Active; A | **PASS** |
| PRE-007 | Cancel pre-order stuck | same | same | B: 1 POST, status cancelled (desktop+390) | **PASS** |
| PRE-008 | Dashboard update | same | same | B + A | **PASS** |
| RES-004 | Admin sees cancelled reservation | notifications never reached owner (cache bug) | `User.php`, `ReservationService.php`, `NotificationDispatcher.php` | B: owner got 1 notification per cancellation; A: settled-reservations history test (existing) | **PASS** (owner dashboard visual NOT TESTED) |
| RES-005 | Admin sees cancelled pre-order | same | `PreOrderCancellationService.php`, `User.php` | B: owner notified; A | **PASS** (owner UI NOT TESTED) |
| RES-001 | Reservation pickup/POS | edge: cancelled reservation reported as paid | `SaleController.php` | A: 6 new tests (cash ok, short cash, double-complete, cancelled, expired, customer 403) | **PASS** (A only; B NOT TESTED) |
| RES-002 / PRE-005 | Arrival notification & 24h rule | queued notification could start deadline with no row | `AdminPreOrderController.php`, `NotificationDispatcher.php`, `StatusUpdated.php` | A: deadline NOT started if not recorded; starts when row exists | **PASS** (A) |
| RES-003 / PRE-006 / RSV-005 | Auto-expiry | none found | — | A: existing expire tests (whole-group, stock once, custom parts); real 24h wait not performed | **PASS (A)**; B NOT TESTED |
| STK-001 | Low-stock alert | owner lookup used broken cache (same bug) | `User.php` | A: 5 panel tests + owners() cache test | **PASS (A)**; B NOT TESTED |
| CMPM-001 / CMPM-002 | Owner links parts to models | none | — | A: CompatibilityAdminTest (11) | **PASS (A)**; B NOT TESTED |
| CMP-001 / BRW-006 | Inaccurate compatibility | free-text/Universal fallbacks, LIKE matching, no year ranges | `CompatibilityMatcher.php`, `PublicBrowseController.php`, `compatibility.blade.php` | A: 16 new tests (9 fail on old code) | **PASS (A)**; page UI B NOT TESTED |
| SignUp-002 | Invalid name accepted | no character rule (`Juan123!` passed) | `RegisteredUserController.php`, `ProfileUpdateRequest.php` | A: bad/good name tests | **FIXED → PASS (A)** |
| SignUp-003 | Invalid email | already correct (`email` + `ends_with:@gmail.com`) | — | A: 4 bad emails rejected | **PASS (A)** |
| DASH-005 | Session timeout | deferred by instruction | — | — | **BLOCKED** (needs approval) |

## What changed (files)
Modified (12): `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Controllers/Inventory/PublicBrowseController.php`, `app/Http/Controllers/PreOrder/AdminPreOrderController.php`, `app/Http/Controllers/Sales/SaleController.php`, `app/Http/Requests/ProfileUpdateRequest.php`, `app/Models/User.php`, `app/Notifications/StatusUpdated.php`, `app/Services/PreOrderCancellationService.php`, `app/Services/ReservationService.php`, `resources/views/customer/dashboard.blade.php`, `resources/views/inventory/compatibility.blade.php`, `tests/Feature/Auth/RegistrationTest.php`
New (6): `app/Services/CompatibilityMatcher.php`, `app/Services/NotificationDispatcher.php`, `tests/Feature/Customer/CancelUiAndNotificationDeliveryTest.php`, `tests/Feature/Inventory/CompatibilityAccuracyTest.php`, `tests/Feature/Sales/ReservationPickupTest.php`, + these two reports. No migration. No `.env`/config change.

## Business rules
Preserved: cash-only, atomic grouped cancel, stock restored once, no expiry for unstocked pre-orders, deadline only after durable notification (now actually enforced), auth/roles untouched.

## Before deploying — please check (read-only)
1. Production `.env`: `CACHE_STORE`, `QUEUE_CONNECTION`. If `QUEUE_CONNECTION=database`, inspect the `jobs` table: old queued `StatusUpdated` jobs would all fire if a worker is ever started.
2. After deploy: `php artisan cache:clear` (the old `users_role_owner` key is no longer read, so this is optional).
3. Owner should link intended parts in *Compatibility Records*: parts that relied on typed model text or "Universal" no longer count as confirmed-compatible.
4. Run `npm run build` locally (not possible here) and spot-check cancel buttons on the live site after deploy.

## Readiness
Code-level fixes are verified (tests + real-browser reproduction on synthetic data). Not yet verified: frontend build, authenticated production browser QA, Postgres concurrency, real 24h waits. Ready for a **controlled deployment after you review the diff and run the local build**.

## Verification summary
| Check | Result | Source |
|---|---|---|
| PHPUnit (full suite) | **207 passed / 666 assertions / 0 failed** | cloud copy + user's local PowerShell (before the notification-text fix) |
| PHPUnit after notification-text fix | **208 passed / 673 assertions / 0 failed** (+1 test) | cloud copy only — **user to re-run locally** |
| `npm run build` | **PASS** (1.56 s) | user's local PowerShell (I could not run it) |
| Test DB isolation | `phpunit.xml` → SQLite `:memory:`; bootstrap config cache absent | user-confirmed; matches my reading |
| Git | 19 modified/untracked files; `git diff --check` only a CRLF/LF line-ending warning | user-reported |
| Real-browser reproduction + fix | Chromium, synthetic SQLite DB, 1280 & 390 px | cloud (Claude) |
| Live VPS UAT | **NOT DONE — fixes are not deployed** | — |

## Changed files (19)
Modified (12): `RegisteredUserController`, `PublicBrowseController`, `AdminPreOrderController`, `SaleController`, `ProfileUpdateRequest`, `User` (model), `StatusUpdated`, `PreOrderCancellationService`, `ReservationService`, `customer/dashboard.blade.php`, `inventory/compatibility.blade.php`, `tests/Feature/Auth/RegistrationTest.php`.
New (7; `CancelUiAndNotificationDeliveryTest` gained 1 test): `CompatibilityMatcher`, `NotificationDispatcher`, `CancelUiAndNotificationDeliveryTest`, `CompatibilityAccuracyTest`, `ReservationPickupTest`, and these two reports. No migration, config or `.env` change. Secret scan of the new files: nothing found (only the existing synthetic test password in `RegistrationTest`). Line endings: edits were written LF; if `git diff --stat` shows a whole file changed, that is CRLF/LF normalisation, not logic.

## Review: NotificationDispatcher
- **Sync persistence:** DB row written in-request via `notifyNow(['database'])`; no queue worker involved. ✔ (tested with `queue.default=database`, no worker)
- **Failure isolation:** per-recipient try/catch; broadcast is a separate best-effort step; outer guards in both cancel services. ✔ (tests: broadcast down keeps record; one bad recipient doesn't block others; total outage doesn't undo cancellation)
- **Deadline rule:** pre-order 24 h deadline starts only if ≥1 row was recorded; otherwise nothing starts and an error is logged/flashed. ✔ (both directions tested)
- **Duplicate prevention:** the dispatcher itself does **not** dedupe (each call gets a fresh id). Duplicates are prevented upstream: cancellation/stock transitions run under row locks and a second attempt is rejected *before* any notification (tested double-cancel). The admin "resend ready" action intentionally re-sends. Low-stock keeps its own dedupe. Residual: a caller that fires twice without a state guard would notify twice.
- **Minor:** if a real broadcaster (reverb/pusher) is configured, the broadcast event itself may be queued → harmless rows in `jobs` if no worker.

## Review: cancellation fixes
- **Authorization:** ownership still checked against the *locked* rows; guessed IDs → 403; customers still cannot hit the owner cancel route (separate `/customer/preorders/...` path). ✔
- **Stock integrity:** all lines validated before any mutation; stock locked in id order; restored once; unstocked/custom pre-orders move no stock; paid/collected/completed rejected. ✔ (existing 20 tests + browser: 8→11 and 8→9 after 3 units cancelled)
- **Idempotency:** second cancel returns "already processed" (409 path) with no second restore; UI also blocks double submit. ✔
- The UI fix touches only markup/JS; no controller/service rule was relaxed.

## Compatibility and required owner-side data maintenance
Checker and Browse now rely solely on `part_compatibility` links (exact make/model, inclusive year ranges, same-record matching). **Owner action before/after go-live:** open *Compatibility Records* and link every part that should show as compatible. Parts that previously appeared only because of typed model text or "Universal" will no longer appear as confirmed-compatible. Record years as `2020` or `2018-2022`; leave blank only for "all years". No production data was changed by this work.

## Risk: old pending jobs in production `jobs` table (read-only inspection, do not process or delete)
Why it matters: `StatusUpdated` was queued. If production has `QUEUE_CONNECTION=database` and no worker, old notification jobs are sitting unprocessed; starting a worker later would deliver all of them at once (stale "cancelled/arrived" notices, possible deadline text mismatches). (Note: OTP mail is *not* queued — `OtpMail` does not implement `ShouldQueue` — so working OTP says nothing about a worker.)
Safe procedure (Supabase SQL editor or `psql` with a **read-only** role; SELECT only):
1. `SELECT queue, count(*) AS n, min(to_timestamp(created_at)) AS oldest, max(to_timestamp(created_at)) AS newest, sum((reserved_at IS NOT NULL)::int) AS reserved FROM jobs GROUP BY queue;`
2. `SELECT payload::json->>'displayName' AS job, count(*) FROM jobs GROUP BY 1 ORDER BY 2 DESC;`
3. `SELECT count(*), max(failed_at) FROM failed_jobs;`
4. Confirm no worker: on the VPS `ps aux | grep -E "queue:(work|listen)"` and check any supervisor/systemd units (read-only).
5. Also read `CACHE_STORE` and `QUEUE_CONNECTION` names from the VPS `.env` (do not copy values of secrets).
Decision after reading: 0 rows → nothing to do. Many `SendQueuedNotifications` rows → tell me; options are to leave them unprocessed (they are inert without a worker) or have the owner approve a deliberate cleanup. Do not start a worker until decided.

## Deployment-readiness assessment — **GO (conditional)**
GO for a controlled deployment to the VPS, subject to: (1) you review `git diff` (incl. 7 new files); (2) the read-only `jobs` check above is done before or right after deploy; (3) live UAT below is run immediately after deploy, with rollback ready (previous commit `dcb68c2`). Not a blanket GO: nothing here has passed live UAT.

## Unresolved risks
1. Live behaviour unverified (cache/queue config on VPS unknown; Supabase/PostgreSQL locking not exercised by SQLite tests).
2. Compatibility results may look "emptier" until owner links parts.
3. No email for cancellation/readiness (product decision pending); DASH-005 session timeout still deferred.
4. Stale `jobs` rows (above). 5. ~~Cosmetic double period in notification text~~ — **fixed** (`rtrim(trim($reason), '.') . '.'` in the 3 cancellation notices; stored `cancellation_reason` unchanged; new test; no other test asserted that text).

## Exact remaining LIVE UAT checklist (run on https://mototrack.online after deploy, with synthetic customer + owner accounts)
1. RSV-006/007: customer with a 2-item reservation clicks Cancel → confirm appears → success message → moves to History; stock of both items +qty once (check owner inventory). Repeat at 390 px.
2. PRE-007/008: cancel an unstocked pre-order → History, no stock change; cancel a stocked catalog pre-order → allocated qty restored once.
3. RES-004/005: owner sees the cancellations in the dashboard history and receives in-app notifications with working *View Details* links.
4. Notifications: bell badge/count, mark read / read-all, dismissal does not reappear; customer receives their own cancellation notice.
5. RES-002/PRE-005: owner marks pre-order arrived/stocked → customer notification appears AND 24 h deadline shown; if no notification appears, deadline must not start.
6. CMP-001/BRW-006/CMPM-001/002: after owner links a part to Honda TMX 155 (2018-2022): checker returns it for 2020, not for 2017/2023, not for another model; Browse model filter agrees; dropdowns contain only recorded models.
7. SignUp-002/003: "Juan123!" rejected; "invalidemail" rejected; "Ana-Liza O'Brien" accepted.
8. RES-001: owner completes a reservation pickup with exact/over cash (change correct); short cash rejected; cancelled reservation shows an error, not "paid".
9. STK-001: dropping an item below threshold creates one owner alert (deduplicated), link opens the highlighted item.
10. RES-003/PRE-006/RSV-005: expiry — verify on a synthetic reservation whose deadline is made to lapse (owner-approved method), not by running production commands blindly.
11. Console/network: no JS errors on dashboard; exactly one POST per cancel click.
12. Smoke: OTP login (customer + owner), checkout, POS sale, returns/refund unaffected.

## Final deployment checklist (main branch + existing GitHub Actions; no new branch)
`deploy.yml` runs `php artisan test` in CI, then deploys over SSH and runs `migrate --force`, `optimize:clear`, `config:cache`, `route:cache`, `view:cache`. It does **not** start or restart a queue worker. These changes add **no migration**.
**Before pushing (local, Windows):**
1. `php artisan test` → expect **248 passed / 793 assertions** after the 2026-10-16 addendum fixes (208 / 673 before them); `npm run build` → success (build output is part of the deploy; confirm `public/build` handling matches your usual flow).
2. `git status` → exactly the 19 files (+ nothing else). `git diff --stat` → no unexpectedly huge line counts (CRLF/LF).
3. `git diff` skim; confirm no `.env`, keys, dumps. `.gitignore` already excludes `.env`.
4. **Read-only production queue inspection** (see "Risk: old pending jobs") — must be done **before any queue worker is ever enabled**; do not start, process, or delete jobs. Also note `CACHE_STORE` / `QUEUE_CONNECTION`.
5. Owner prepares the compatibility data (link intended parts, years as `2020` / `2018-2022`).
**Deploy (only after the user authorizes):** commit on the current branch → push to `main` → watch the Actions run (tests must pass in CI) → confirm deploy step succeeded.
**Immediately after deploy:** hard-refresh (Ctrl+F5) so the new dashboard markup loads; run the live UAT checklist above; check `storage/logs/laravel.log` on the VPS for `notification failed` / `could not be recorded` warnings.
**Rollback:** `git revert` the release commit on `main` and push (re-triggers the same workflow); previous deployed commit: `dcb68c2`. No schema change, so no DB rollback.
**Do not:** run `reservations:expire` / `preorders:expire` manually in production, start a queue worker, or delete `jobs` rows without a decision.


---

## Addendum 2026-10-16 — Pre-deployment integrity fixes (approved by the owner)

Not committed, pushed or deployed. Cloud-run result: **248 passed / 793 assertions / 0 failed** (SQLite `:memory:`, cache=array, queue=sync, mail=array; production Supabase never contacted). The user must re-run locally (see commands below); the earlier 208/673 baseline is history.

### Fix 1 — Sale and reservation history can no longer be deleted
| | |
|---|---|
| Confirmed defect | `DELETE /sales/{id}` (owner-only, no UI) soft-deleted **completed, refunded, refund_requested, pending, cancelled** sales. Probe: revenue 100 → 0; pending reservation stock never restored; refunds orphaned; audit row written but deletion not prevented. |
| Root cause | `SaleController::destroy` had no status check; `SalePolicy::delete` was never called. |
| Correction | Route removed from `routes/modules/sales.php`; `destroy()` kept as a fail-closed stub (`abort(405)`) so re-adding a route cannot re-enable deletion; `SalePolicy::delete` returns `false` for every role. No data was touched; no archive feature added; no migration. |
| Regression evidence | `tests/Feature/Sales/SaleDeletionProtectionTest.php` — 21 tests: crafted DELETE by owner for all six statuses (including cancelled/expired) leaves the sale, revenue, stock, audit rows, refund requests and product returns unchanged; customer/guest rejected; route name gone; a re-added route is refused by the controller (405); policy denies all; sales history total unchanged. Existing POS refund, return, cancel, expiry tests still pass. |

### Fix 2 — Low-stock in-app notifications no longer need a queue worker
| | |
|---|---|
| Confirmed defect | The owner notification and the SMS were both created inside the queued `CheckLowStockAlertJob`; without a worker nothing happened. Inventory-form edits, pre-order allocation, refunds, reservations and cancellations never triggered a check. Permanent dedupe (notification-table lookup) also meant an item could never alert again after a restock. |
| Root cause | Alert creation coupled to a queued job; alert state coupled to SMS success; manual-edit path deliberately suppressed alerts; no re-arm logic. |
| Correction | New `App\Services\LowStockAlertService` (single state machine on the existing `low_stock_notified` flag, no migration): above threshold → re-arm; at/below threshold and armed → record owner in-app notification **synchronously** via `NotificationDispatcher`, then set flag; flagged → nothing. Flag is set only after ≥1 owner row is durably recorded (retry on failure; no owners → no retry loop). Triggered after DB commit (`checkAfterCommit`) from: POS/checkout/reservation allocation (`CommitOrder`), pre-order allocation, damaged items, returns (restock), refunds (restock), reservation cancel/expire, pre-order cancel/expire, inventory create and manual edit. SMS is optional: queued `SendLowStockSmsJob` (or inline only via the legacy `SmsService::checkLowStockAlert`), failures logged and ignored. Legacy `CheckLowStockAlertJob` kept as an idempotent shim so any rows already in a production `jobs` table cannot create duplicate alerts. Out-of-stock uses the same single alert with an "out of stock" message (no escalation alert). Flag changes use a query-builder update (no audit-log noise). |
| Behaviour changes to be aware of | Manual edit below threshold now alerts once (previously suppressed). Customer reservations that lower stock below the threshold now alert (previously `dispatchLowStockAlert: false`; the parameter is now a documented no-op). Flag now reflects the in-app alert, not SMS success (one existing test assertion updated accordingly). |
| Regression evidence | `tests/Feature/Admin/LowStockSynchronousAlertTest.php` — 19 tests with `queue.default=database` and no worker: normal stock, crossing, exactly-at-threshold, out-of-stock, repeated sales/edits (one alert), manual edit, restock re-arm and second alert, damaged item, customer reservation, real reservation-cancel restock, real return restock, SMS exception, in-app write failure (sale still succeeds, item stays armed, retry works), rolled-back order raises nothing, legacy job no duplicate / no-op, Low Stock page unchanged, no audit noise. Two existing tests updated (`InventoryAdjustmentTest`, `LowStockPanelTest`). |

### Remaining requirement gaps (unchanged, need decisions)
Stock-level report wording/scope, repair parts-used and expected-completion fields, Gmail-only registration documentation, DASH-005 idle timeout, notification channel (in-app remains baseline; the customer must open MotoTrack to see a readiness notice), Figures 3.0/4.0/5.0/6.0/7.0 and Chapter 1–3 revisions. SMS for low stock still needs a queue worker and configured gateway to send anything.

### Deployment implications
- No migration, no new environment variable, no queue-worker change. Production `jobs` table must still be inspected read-only before any worker is ever started.
- After deploy: live check that a POS sale or an inventory edit below threshold creates exactly one owner notification, a second sale does not, and a restock above the threshold followed by a new drop creates another.
- Files changed in this addendum (15 beyond the earlier 19-file set): see "Files" in the hand-off message; run `git status` — expect 34 code/test/report files plus the `docs/` folder.
- Verify locally: `php artisan test` (expect 248 / 793 if nothing else changed), `npm run build`, `git diff --check`.

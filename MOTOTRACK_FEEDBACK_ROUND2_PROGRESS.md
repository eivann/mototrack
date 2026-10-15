# MotoTrack Round 2 — Implementation Progress

**Date:** 2026-10-15 (continued from 2026-10-14)
**Baseline (Claude Opus, 2026-10-14):** 138 passed / 421 assertions
**Current:** 168 passed / 523 assertions
**Test DB:** SQLite `:memory:` per `phpunit.xml` — verified isolated from production Supabase.
**Mail:** `array` · **Queue:** `sync` · **Cache:** `array` — no external integrations fire during tests.

---

## Phase 0 — Baseline ✅

- Test isolation verified before any test run: `phpunit.xml` forces `DB_CONNECTION=sqlite`,
  `DB_DATABASE=:memory:`. `.env` (production Supabase) is gitignored (`.gitignore:3`) and never
  loaded by the test bootstrap.
- Baseline suite executed: **118 passed, 358 assertions, exit 0**.

## Phase 1 — Grouped reservation cancellation ✅

**Confirmed root cause:** `CustomerOrderController::cancel()` loaded a single `Sale` by id and
called `ReservationService::release()`, which locked only that one row. A multi-item reservation
(one `transaction_reference`, several `sales` rows created by `CommitOrder`) was therefore
**half-cancelled** — the reported bug is real.

**Fix:**
- `ReservationService::releaseGroup()` — locks every line of a reference in deterministic `id`
  order, validates all lines before mutating, locks stock rows in ascending item-id order, then
  restores and updates. `release()` now delegates to it, so no caller can diverge.
- Both the legacy `/orders/{id}/cancel` and the new `/reservations/{reference}/cancel` route
  resolve to the same service call and rules.
- Ownership checked against the **locked** rows (not the pre-lock model) so a guessed id fails.
- Rejects when any line is completed / paid / picked up / expired.
- Notifications dispatched **after commit** inside try/catch, so a delivery failure cannot roll
  back a committed cancellation.
- `ExpireReservations` rewritten to expire whole references, not individual rows.

**Status integration:** `SALE_EXPIRED` added to `saleStatuses()`, `label()`, `color()`.
`PREORDER_EXPIRED` added as a constant plus the same three integrations. Expired orders are now
visually and semantically distinct from voluntarily cancelled ones.

**UI:** New *My Reservations* tab — one card per checkout reference, active/history split,
per-item quantities and images, amount due at store, 24h deadline countdown, reason field and a
confirm-gated Cancel Reservation button.

## Phase 2 — Customer pre-order cancellation ✅

- New `PreOrderCancellationService` — single source of truth; **admin** `AdminPreOrderController::cancel()`
  now delegates to it, so customer and staff paths cannot drift.
- New route `POST /customer/preorders/{id}/cancel` with ownership check and confirm dialog.
- Unstocked sourcing → **zero** stock movement. Stocked catalog-linked → releases only the
  allocated quantity, exactly once. Custom parts without `inventory_item_id` → no stock invented.
  Paid / collected / completed rejected.
- Cancellation reason captured by adding `HasAuditTrail` to `PreOrder` — **no new DB column**, per
  approval. `PreOrder` previously had no audit trail.

## Bug found and fixed during Phase 1 testing 🔴→✅

My new customer route `POST /preorders/{id}/cancel` **collided with** the owner route
`POST /preorders/{preorder}/cancel`. Laravel matched the customer route first, so owner
cancellations hit customer ownership logic and returned **403**. This was a regression I
introduced; the existing `PreOrderCompletionTest` caught it. Fixed by moving the customer route
to `/customer/preorders/{id}/cancel`.

## Phase 3 — Remaining colleague feedback ✅ (complete)

All Phase 3 items have been implemented and tested:

| Item | Status | Evidence |
| --- | --- | --- |
| Admin nav: remove redundant Browse Parts | ✅ Fixed | `navigation.blade.php` — Browse Parts now `show => !$isAdmin` |
| Admin sidebar: remove duplicate Compatibility shortcut | ✅ Fixed | Removed duplicate shortcut block in `admin/dashboard.blade.php`; navbar link retained |
| Admin nav: add Low Stock Alerts + Compatibility Records links | ✅ Fixed | `navigation.blade.php` — dropdown with Dashboard, Low Stock Alerts, Compatibility Records |
| Pre-orders: separate View from Complete | ✅ Fixed | `preorders/admin_index.blade.php` — added labelled **View** action; Complete gated on `is_stocked && is_paid` |
| Owner cancelled/expired reservations visible | ✅ Fixed | `DashboardController` passes `settledReservations`; new history table in admin dashboard |
| Notification **View Details** broken link | ✅ **Real bug fixed** | Owner link was hardcoded `/admin/dashboard?tab=reservations` — that route **does not exist**. Replaced with `route('dashboard', ...)` and `route('preorders.index')` |
| Toast re-appearing after dismissal | ✅ Fixed | `layouts/app.blade.php` — dismissal persisted in `localStorage`, filtered on load; added explicit **View Details** action |
| Mobile filter placement | ✅ Fixed | `inventory/browse.blade.php` — panel now a fixed bottom sheet with backdrop + close button, opens at current scroll position |
| `Lily Bogs` demo string | ✅ Not present | Only appears in `logs.txt` / historical report docs; zero occurrences in Blade views |
| Product/catalog images | ✅ Already correct | `image_url` accessor + `onerror` fallback verified in `browse.blade.php` |
| Signup validation | ✅ Already correct | `RegisteredUserController` enforces `required|email|lowercase|ends_with:@gmail.com|unique` |
| Registration/OTP/role protections | ✅ Untouched | No auth, OTP, or permission changes made |
| **Low-stock dashboard panel** | ✅ **New** | `DashboardController` passes `lowStockItems`, `outOfStockCount`; admin dashboard has full low-stock table with count badges and restock links |
| **Low-stock page improvements** | ✅ **New** | `low_stock.blade.php` — image column, highlight parameter for notification deep-links, back-to-dashboard link |
| **Low-stock notifications** | ✅ **New** | `SmsService::notifyLowStockOwners()` — sends `StatusUpdated` notification with deep-link to highlighted item; deduplicated; gateway-failure-resilient |
| **Compatibility record management** | ✅ **New** | `AdminCompatibilityController` — full CRUD (index, store, update, destroy, options); `compatibility_admin.blade.php` with Alpine modal |
| **Duplicate dropdown fixes** | ✅ **New** | `adjustments.blade.php` — disabled placeholders, unique IDs per select, `TomSelect` guard, `allowEmptyOption: false` |
| **Dropdown dedup in compatibility** | ✅ **New** | `PublicBrowseController` — case-insensitive `unique()` and blank filtering for makes/models |
| **Responsive checkout layout** | ✅ **New** | `checkout.blade.php` — two-column desktop grid, mobile-stacked cards, sticky summary |
| **Responsive pre-order form** | ✅ **New** | `form.blade.php` — two-column desktop grid with sticky item info panel, dynamic price breakdown |
| **Server-side validation for adjustments** | ✅ Verified | `StoreDamagedItemRequest`: `required|exists:inventory_items,id`; `StoreProductReturnRequest`: `action_taken => required|string|in:restocked,discarded` |

## Not done / not attempted

- **Two-minute idle timeout** — deliberately NOT implemented, per explicit instruction.
- **PostgreSQL concurrency test** — lock ordering is implemented and unit-tested for
  single-connection correctness only; true multi-connection race behaviour is **NOT TESTED**.
- **Browser/viewport QA** (360–1440px) — partial browser testing completed. Public pages
  (homepage, browse, login, compatibility) verified rendering correctly at desktop and mobile
  (390px) widths. Authenticated routes (checkout, pre-order-parts, dashboard) require production
  database and cannot be browser-tested in isolation.

## Hard constraints honoured

No `git push`, no commits, no VPS deploy, no production Supabase access, no destructive Git ops.

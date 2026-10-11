# MotoTrack — Laravel 13 Source Audit and Remediation Plan

**Audit date:** October 9, 2026  
**Source:** `mototrack_source_for_gpt5.txt` (9,304 lines, concatenated source export)  
**System:** Motorcycle parts inventory, customer reservations (24-hour pickup), pre-orders, repairs, and cash/over-the-counter POS  
**Scope:** Static review of supplied code; no executed Laravel app, database transactions, load tests, or deployment environment inspection.

> **Important:** File line ranges below refer to **lines in the supplied concatenated `.txt` export**, not necessarily line numbers in the extracted application files. The exact Laravel file paths are included for IDE navigation. Fix examples are implementation patterns; integrate them into the existing code and adapt migrations to the live schema. Do not apply untested snippets blindly.

## Business rules and non-negotiable invariants

1. Customer checkout creates an **unpaid reservation**, not a collected sale.
2. A reservation reserves/deducts available stock once and expires **24 hours** after reservation unless fulfilled or properly extended.
3. Cancellation/expiration restores the reserved units **exactly once**. A completed/picked-up transaction never restores stock simply because its record was archived.
4. Cash is collected in the physical shop, and revenue is based on verified cash collection—not pending reservation value.
5. Concurrency-safe inventory changes use database transactions and row-level locks with consistent lock ordering. Requests and retries must not double-apply stock or payments.
6. Historical invoices/sales are retained when catalog entries change. Offline POS and legitimate repair payments remain functional.
7. **Use the installed Laravel version as authority:** verify `php artisan --version`, `composer.json`, and `composer.lock` before implementation. Laravel 13 supports model attributes such as `#[Fillable]` and `#[Hidden]`; do not migrate them to older conventions merely because of an outdated finding.

## Severity summary

| ID | Severity | Verified issue | Primary location | Priority |
|---|---|---|---|---|
| C-01 | Critical | Duplicate POS IDs validated separately can over-deduct stock | `app/Actions/CommitOrder.php` | P0 |
| C-02 | Critical | Sale mass assignment omits reservation/payment timestamps | `app/Models/Sale.php` | P0 |
| C-03 | Critical | Pre-order stocked flag does not allocate catalog stock | `app/Http/Controllers/PreOrder/AdminPreOrderController.php` | P0 — confirm receiving design |
| C-04 | Critical | Pre-order cancellation permits invalid state transitions | Same controller | P0 |
| H-01 | High | Underpayment accepted as completed cash sale | `app/Http/Controllers/Sales/SaleController.php` | P1 |
| H-02 | High | Revenue filter/date reports collections inaccurately | `app/Http/Controllers/Admin/DashboardController.php` | P1 |
| H-03 | High | Pre-order payment has no pending-state/full-cash validation | `app/Http/Controllers/PreOrder/AdminPreOrderController.php` | P1 |
| H-04 | High | Stale inventory edit can overwrite reservation deductions | `app/Services/InventoryService.php` | P1 |
| H-05 | High | Existing sale FK is `ON DELETE CASCADE` on inventory deletion | Sales migrations and `InventoryService.php` | P1 |
| H-07 | High / operational | Hourly release can hold expired stock until next sweep | `routes/console.php` | P2 |
| M-01 | Medium | Category cache invalidation targets wrong version | `InventoryItem.php`, `InventoryService.php` | P2 |
| M-02 | Medium | Product image URL differs from public-disk storage | `InventoryItem.php`, `AdminInventoryController.php` | P2 |
| M-03 | Medium | Customer reservation checkout still requests GCash/cash | `CheckoutRequest.php`, `CheckoutController.php` | P2 |
| M-04 | Medium | Sales report route references absent action | `routes/modules/sales.php` | P2 |
| M-05 | Medium / policy | `canBeRefunded()` omits pickup check stated in code comment | `Sale.php` | P2 — confirm return policy |

**Correction to previous report:** Former **H-06 (role default = owner)** is **NOT an active finding in this source**. Migration `database/migrations/2026_10_07_190844_alter_role_default_in_users_table.php`, export lines 9140–9156, sets `role` to **`customer` in `up()`**. The `owner` default is in `down()` only. Verify applied migration status and actual database default, but do **not** file this as a confirmed current vulnerability.

---

# Critical findings and fixes

## C-01 — Duplicate POS product lines can deduct more than available stock

**Locations:** `app/Actions/CommitOrder.php` (export lines 54–94); `app/Http/Controllers/Sales/SaleController.php` (2327–2354).  
**Cause:** `CommitOrder` sorts lines by item ID but individually checks each duplicate ID before performing decrements. The POS accepts repeated IDs without aggregating; two entries of three units each can both pass while only five units are available.

**Fix:** Validate and aggregate by inventory ID **before** entering `DB::transaction()`; lock each item once, in sorted order. Preserve the existing row locks and all-or-nothing transactional writes.

```php
// At the beginning of CommitOrder::execute(), before DB::transaction().
$totals = [];
foreach ($lines as $line) {
    $id = filter_var($line['id'] ?? null, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);
    $quantity = filter_var($line['quantity'] ?? null, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);

    if ($id === false || $quantity === false) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'cart' => 'Invalid inventory item or quantity.',
        ]);
    }

    $totals[$id] = ($totals[$id] ?? 0) + $quantity;
    if ($totals[$id] > 9999) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'cart' => 'Quantity exceeds the allowed limit.',
        ]);
    }
}
if (!$totals) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'cart' => 'Cart is empty.',
    ]);
}
ksort($totals, SORT_NUMERIC);
$lines = [];
foreach ($totals as $id => $quantity) {
    $lines[] = ['id' => $id, 'quantity' => $quantity];
}
// Pass the normalized $lines into the existing DB::transaction closure.
```

**Acceptance tests:** Stock=5, duplicate POS lines 3+3 → transaction rejected, stock remains 5, no sales rows. Stock=6, 3+3 → exactly 6 deducted. Two independent concurrent transactions competing for the last units must not oversell.

## C-02 — Reservation expiration/payment timestamps discarded by mass assignment

**Locations:** `app/Models/Sale.php` (4097–4113); `app/Actions/CommitOrder.php` (80–91); `app/Http/Controllers/Sales/SaleController.php` (2423–2428); migration `2026_10_09_172931_add_reservation_fields_to_sales_table.php` (9163+).  
**Cause:** `Sale::$fillable` has neither `reserved_at` nor `expires_at`, while `Sale::create()` sets them. It also excludes `payment_confirmed_at`, which `Sale::update()` tries to write. Laravel silently discards such attributes unless strict mode is enabled. The expiration command has a created-at fallback, but the dedicated fields remain unpersisted.

**Fix (`app/Models/Sale.php`):**

```php
protected $fillable = [
    'inventory_item_id', 'customer_id', 'quantity', 'price',
    'total_price', 'transaction_reference', 'amount_tendered',
    'change', 'status', 'cancellation_reason', 'picked_up_at',
    'payment_confirmed_at', 'reserved_at', 'expires_at',
];

protected $casts = [
    'payment_confirmed_at' => 'datetime',
    'picked_up_at' => 'datetime',
    'reserved_at' => 'datetime',
    'expires_at' => 'datetime',
];
```

Enable discarded-attribute exceptions during local development (using Laravel's current supported API). **Do not** mass assign privileged User attributes as part of this fix.

**Acceptance tests:** Pending reservation stores `reserved_at` and `expires_at`, exactly 24h apart; after verified cash collection `payment_confirmed_at` is stored. Existing reservations with null timestamps are handled by the documented fallback/reconciliation policy.

## C-03 — Marking pre-order stocked does not allocate stock

**Location:** `app/Http/Controllers/PreOrder/AdminPreOrderController.php` (1993–2025); `database/migrations/2026_10_09_174146_add_stock_fields_to_pre_orders.php` (9240+).  
**Cause:** `stock()` sets `is_stocked=true`, while `complete()` sets picked-up/completed, but neither deducts or allocates catalog inventory. If physical receipts are already in available inventory, they remain available for other reservations.

**Required design decision before coding:** Does `stock()` mean **supplier goods physically received and added to inventory** or **existing available units assigned to this pre-order**? The following pattern assumes received stock has already been added to catalog quantity and `stock()` is an allocation step. Do not adopt it if it would double-deduct stock.

```php
DB::transaction(function () use ($preorder) {
    $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);
    if ($order->status !== 'pending' || $order->is_stocked) {
        abort(409, 'Pre-order cannot be allocated again.');
    }
    if ($order->inventory_item_id) {
        $item = InventoryItem::lockForUpdate()
            ->findOrFail($order->inventory_item_id);
        if ($item->quantity < $order->quantity) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'quantity' => 'Insufficient available stock.',
            ]);
        }
        $item->decrement('quantity', $order->quantity);
    }
    $order->is_stocked = true;
    $order->save();
});
```

**Also fix:** `complete()` must not deduct stock again. `cancel()` should restore stock only when there was an allocation and it has not been picked up, exactly once. For custom pre-orders with no catalog ID, require a separate explicit receiving/allocation workflow; never silently mark inventory allocated.

**Acceptance tests:** Marking received+allocated pre-order reduces available units once; repeated requests do not reallocate. Another reservation cannot claim allocated units. Cancellation releases them if still uncollected; completion does not release them.

## C-04 — Pre-order cancellation ignores paid/completed state and is race-prone

**Location:** `app/Http/Controllers/PreOrder/AdminPreOrderController.php` (2013–2044).  
**Cause:** `cancel()` sets status to cancelled without checking status/payment/pickup. `complete()` checks values on an unlocked injected model before saving. Concurrent state changes can overwrite each other.

**Fix:** Use explicit transitions under `lockForUpdate()` in both actions. For cancellation, apply this pattern **only after C-03 allocation semantics are implemented**:

```php
DB::transaction(function () use ($preorder) {
    $order = PreOrder::lockForUpdate()->findOrFail($preorder->id);
    if ($order->status !== 'pending') {
        abort(409, 'Only pending pre-orders can be cancelled.');
    }
    if ($order->is_paid) {
        abort(409, 'A paid pre-order requires a separate refund workflow.');
    }
    if ($order->is_stocked && $order->inventory_item_id) {
        $item = InventoryItem::lockForUpdate()
            ->findOrFail($order->inventory_item_id);
        $item->increment('quantity', $order->quantity);
    }
    $order->status = 'cancelled';
    $order->save();
});
```

Move `complete()` validation and transition into a transaction and lock the pre-order. Standardize lock order across both operations. Dispatch notifications after commit, not for failed transitions.

**Acceptance tests:** Completed or paid pre-orders cannot be directly cancelled; concurrent `complete`/`cancel` yields one valid final status and exactly one stock effect.

---

# High-priority findings and fixes

## H-01 — POS and pickup actions mark underpaid transactions completed

**Locations:** `app/Http/Requests/Sales/StoreSaleRequest.php` (3325–3331); `app/Http/Controllers/Sales/SaleController.php` (2357–2366, 2410–2428).  
**Cause:** Tendered amount permits zero; POS writes completed regardless of the amount received. Reservation completion defaults an omitted tender to per-line total and lacks payment validation. The POS stamps the same transaction-level tender on each sale line, creating inconsistencies in receipt calculations.

**Fix:** Under the existing locked transaction, compare verified cash to the **whole transaction grand total**; reject underpayment and nonnumeric/missing values. Store cash tender/change once at transaction level (or consistently on one line until a parent transaction table is available). For example:

```php
$tendered = (float) $request->validated('amount_tendered');
$this->commitOrder->execute(
    $lines,
    $transactionReference,
    null,
    'Not enough stock for ',
    function (bool $isFirst, float $grandTotal) use ($tendered): float {
        if ($tendered < $grandTotal) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount_tendered' => 'Insufficient cash received.',
            ]);
        }
        return $isFirst ? $tendered : 0;
    },
    fn (bool $isFirst, float $grandTotal): float =>
        $isFirst ? round($tendered - $grandTotal, 2) : 0,
    dispatchLowStockAlert: true,
);
```

When changing where tender/change are recorded, update and test the receipt views and revenue reports. For full financial correctness, calculate money as decimal or integer centavos rather than floats.

**Pickup endpoint:** validate `amount_tendered` as required, lock all lines, verify all are pending/unexpired and belong to the transaction, compare with the summed total, and update all lines atomically with a single `payment_confirmed_at`. Do not mark paid through an unverified default.

## H-02 — Revenue filters count unpaid/incorrectly dated transactions

**Locations:** `app/Http/Controllers/Admin/DashboardController.php` (456–461, 475–481); `app/Http/Controllers/Sales/SaleController.php` (2389–2395).  
**Cause:** `(status='completed' OR amount_tendered>0)` admits completed zero-tender sales and payments on inappropriate statuses. Reporting groups by sale creation date rather than cash-collection date. A reservation placed yesterday and collected today appears in yesterday's revenue.

**Fix pattern (after timestamps are reliable):**

```php
$todayRevenue = Sale::query()
    ->where('status', 'completed')
    ->whereNotNull('payment_confirmed_at')
    ->whereDate('payment_confirmed_at', today())
    ->sum('total_price');

$monthlyRevenue = Sale::query()
    ->where('status', 'completed')
    ->whereNotNull('payment_confirmed_at')
    ->whereBetween('payment_confirmed_at', [
        now()->startOfMonth(), now()->endOfMonth(),
    ])
    ->sum('total_price');
```

**Companion work:** Write `payment_confirmed_at` on real cash POS, repair, and pre-order payments. Migrate/reconcile historical paid records safely. Account for refunds via separate refund ledger/events rather than relying only on sale status; ensure transaction line payments are not double counted. Derive 30-day chart dates from collection timestamps.

## H-03 — Pre-order cash payment accepts cancelled orders and insufficient cash

**Location:** `app/Http/Controllers/PreOrder/AdminPreOrderController.php` (1952–1975).  
**Cause:** The action protects against repeated `is_paid`, but does not require `status='pending'`, and creates a completed sale from an unvalidated/defaulted tender amount with zero change.

**Fix inside existing locked transaction:**

```php
if ($lockedPreorder->status !== 'pending') {
    abort(409, 'Pre-order is not eligible for payment.');
}
if ($lockedPreorder->is_paid) {
    abort(409, 'This pre-order is already paid.');
}

// Prefer validating before DB::transaction(), then use the validated value here.
$tendered = (float) $validated['amount_tendered'];
if ((float) $lockedPreorder->total <= 0
    || $tendered < (float) $lockedPreorder->total) {
    throw \Illuminate\Validation\ValidationException::withMessages([
        'amount_tendered' => 'Full cash payment is required.',
    ]);
}
// When creating the Sale:
// 'amount_tendered' => $tendered,
// 'change' => round($tendered - $lockedPreorder->total, 2),
// 'payment_confirmed_at' => now(),
```

Validate `amount_tendered` as required/numeric/min:0 before the transaction. Decide how custom zero-priced pre-orders receive a staff-approved quote before marking them paid. If partial deposits are legitimate, model a remaining balance rather than setting `is_paid=true` for partial cash.

## H-04 — Stale absolute quantity edits overwrite stock changes

**Locations:** `app/Services/InventoryService.php` (5067–5079); `app/Http/Requests/Inventory/UpdateInventoryRequest.php` (3181–3194).  
**Cause:** An inventory form can display quantity 10; a reservation changes it to 8; saving the stale form submits 10 and `update($data)` erases the deduction.

**Fix:** Include `expected_quantity` from the initial form, validate as integer>=0, and atomically reject stale updates:

```php
// UpdateInventoryRequest::rules()
'expected_quantity' => ['required', 'integer', 'min:0'],

// InventoryService::update() pattern
return \Illuminate\Support\Facades\DB::transaction(function () use ($item, $data) {
    $expected = (int) $data['expected_quantity'];
    unset($data['expected_quantity']);
    $locked = InventoryItem::lockForUpdate()->findOrFail($item->id);
    if ($locked->quantity !== $expected) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'quantity' => 'Stock changed while editing. Reload and retry.',
        ]);
    }
    $locked->update($data);
    $locked->low_stock_notified =
        $locked->quantity <= ($locked->low_stock_threshold ?? 5);
    $locked->save();
    Cache::forget(InventoryItem::CATEGORY_CACHE_KEY);
    return $locked->fresh();
});
```

`CATEGORY_CACHE_KEY` is introduced in M-01; if implementing before M-01, retain a single literal v4 key. Keep current low-stock notification semantics if they differ deliberately. A stock movement/adjustment ledger is preferable to unrestricted absolute stock editing.

## H-05 — Sale rows can be deleted by inventory FK cascade

**Locations:** `database/migrations/2026_04_13_154458_create_sales_table.php` (7580–7624); `database/migrations/2026_05_12_000938_make_inventory_item_id_nullable_in_sales_table.php` (8443–8478); `app/Services/InventoryService.php` (5086–5089).  
**Cause:** The original `sales.inventory_item_id` FK has `ON DELETE CASCADE`; the later migration makes the column nullable but does not alter the FK. `InventoryService::destroy()` hard-deletes inventory items. Historical sales may therefore be physically deleted, bypassing Sale's soft-deletion history. Confirm the live FK definition before migration because database state can differ from migration files.

**Fix:** Prefer archive/soft-delete products and preserve snapshots of historical line descriptions/prices. If physical deletion is allowed, migrate the FK from cascade to `nullOnDelete()`:

```php
Schema::table('sales', function (Blueprint $table) {
    $table->dropForeign(['inventory_item_id']);
    $table->foreign('inventory_item_id')
        ->references('id')
        ->on('inventory_items')
        ->nullOnDelete();
});
```

Prevent deleting inventory with active reservations and verify that existing receipts remain useful even if their item relation becomes null. Do not edit executed migrations destructively.

## H-07 — Hourly expiration delays release of expired stock

**Locations:** `routes/console.php` (5690); `app/Console/Commands/ExpireReservations.php` (129–155); `app/Services/ReservationService.php` (5128–5150).  
**Cause:** `Schedule::command('reservations:expire')->hourly()` cleans up at most hourly. An item can remain allocated for almost one additional hour after the strict 24h deadline. This is an operational timeliness issue, not evidence of an unsafe double-release: the existing `ReservationService` correctly checks pending state under lock.

**Fix:**

```php
Schedule::command('reservations:expire')
    ->everyMinute()
    ->withoutOverlapping();
```

Configure and monitor the actual production scheduler. Keep independent `expires_at` checks in pickup completion. Consider chunking old reservations if volume grows.

---

# Medium findings and fixes

## M-01 — Category cache uses v4 for reads and v3 for invalidation

**Locations:** `app/Models/InventoryItem.php` (3809–3826); `app/Services/InventoryService.php` (5056–5089).  
**Fix:** Define `public const CATEGORY_CACHE_KEY = 'inventory_categories_v4';` in `InventoryItem`; use it in `Cache::remember()` and all three `Cache::forget()` calls. Test category addition/edit/deletion without waiting six hours.

## M-02 — Uploaded product image URL points to wrong public path

**Locations:** `app/Models/InventoryItem.php` (3791–3798); `app/Http/Controllers/Inventory/AdminInventoryController.php` (1627–1648).  
**Cause:** Upload goes to `store('products', 'public')`, but model returns `asset('images/'.$image_path)` rather than public disk URL.

```php
use Illuminate\Support\Facades\Storage;

// In getImageUrlAttribute(), preserve existing SVG placeholder fallback:
if ($this->image_path) {
    return Storage::disk('public')->url($this->image_path);
}
```

Ensure `php artisan storage:link` is configured for the deployment. Save the new file/database update successfully **before** deleting the previous image, and clean up on failure.

## M-03 — Reservation-only checkout still accepts GCash/cash fields

**Locations:** `app/Http/Requests/Shop/CheckoutRequest.php` (3353–3359); `app/Http/Controllers/Shop/CheckoutController.php` (2615–2625).  
**Cause:** Customer checkout requires `payment_method in:gcash,cash` although no payment is collected; controller reads an unused `$paymentMethod`. This is stale workflow logic, not proof of an active online payment gateway.

**Fix:** After confirming no other checkout fields need validation, remove obsolete request rules and payment-related fields from customer Blade forms, remove unused controller variables/parameters, and label the action “Confirm Reservation.” Keep shop-counter cash/POS functionality.

## M-04 — Dead sales report route

**Locations:** `routes/modules/sales.php` (5544); `app/Http/Controllers/Sales/SaleController.php` (2311–2472).  
**Cause:** Route points to `SaleController::reportReceipt`, which is absent from the supplied class.

**Fix:** Search view references and feature requirements; remove the route if unused, or implement a correctly authorized `reportReceipt()` and view. Test the route list and endpoint.

## M-05 — Refund eligibility omits stated no-pickup requirement

**Location:** `app/Models/Sale.php` (4127–4136).  
**Cause:** `canBeRefunded()` checks completed/amount tendered/existing refunds but not `picked_up_at`, despite a comment describing “only if NOT picked up yet.” Note: genuine post-pickup returns may be permitted by business policy; **confirm policy before restricting them**.

```php
public function canBeRefunded(): bool
{
    return $this->status === OrderStatus::SALE_COMPLETED
        && $this->payment_confirmed_at !== null
        && $this->picked_up_at === null
        && $this->refundRequests()
            ->whereNotIn('status', [
                OrderStatus::REFUND_REJECTED,
                OrderStatus::REFUND_CANCELLED,
            ])->doesntExist();
}
```

Ensure all legitimate cash sale paths populate `payment_confirmed_at`, and separate returns-after-pickup from refunding uncollected reservations if appropriate.

---

# Corrections, exclusions, and verification boundaries

1. **Former H-06 retracted:** `2026_10_07_190844_alter_role_default_in_users_table.php` uses `default('customer')` in `up()`. Never tell an implementation agent that the current forward migration defaults to owner. Its `down()` restores owner, which is a rollback design concern but not proof of an active privilege escalation.
2. **No verified N+1 bug from this export:** Dashboard uses `Sale::with('item','customer')`; pre-order/repair list controllers eager-load customer; customer dashboard eager-loads sale item. **Blade files are not present in this export**, so additional relationship accesses in views cannot be verified. Do not fabricate N+1 findings. Examine views and query logs in the actual IDE workspace.
3. **Online gateway cleanup:** Historical migrations reference Xendit, but the source also contains a migration explicitly dropping Xendit invoice columns (`2026_10_09_175328_drop_xendit_fields_from_pre_orders.php`, export 9272–9304). No active Stripe/PayPal/Xendit gateway implementation was evident in the supplied `app` classes. Check other repository files (Composer/packages, Blade/JS, environment, scheduled jobs) before alleging an active payment integration.
4. **Laravel version unconfirmed at runtime:** The export itself cannot prove the installed framework version. Use the running application version and installed composer lock to target the right APIs.
5. **Code examples are not applied patches:** This report is a source review and implementation handoff. No PHP app/test suite or live migrations were executed while creating the report.
6. **Extra UI inconsistency worth verifying:** `SaleController::destroy()` (2436–2446) deletes/archive-logs without restoring stock, but returns the message `Sale archived and stock restored!`. The message is false; change it to `Sale archived successfully.` This is a direct source mismatch but not release-blocking.

# Implementation sequence for Antigravity IDE

| Stage | Task | Gate to continue |
|---|---|---|
| 0 | Branch/backup, version+schema verification, baseline tests | Original behavior documented; migrations not run on live DB |
| 1 | C-01 input normalization and concurrent stock tests; C-02 Sale timestamps | Atomic reservation tests pass on production-equivalent DB |
| 2 | Define pre-order receiving vs. allocation, implement C-03/C-04 | Stock accounting and concurrent state transitions pass |
| 3 | H-01/H-03 payment validation, then H-02 cash-only collection reporting | Receipt/revenue totals match recorded cash and dates |
| 4 | H-04 inventory editing consistency; H-05 FK/history protection | No lost stock, no cascading historical sale deletion |
| 5 | H-07 scheduler cadence and deployment checks | 24-hour boundary enforced in API and scheduled release |
| 6 | M-01–M-05 and misleading archive message | Routes, image URLs, cache invalidation, checkout UI verified |
| 7 | Recheck full diff, regression tests, deployment plan | No unresolved critical issues; no falsely claimed test results |

# Mandatory test matrix

- [ ] Duplicate same-SKU POS entries (combined quantity <= and > available stock).
- [ ] Two simultaneous transactions competing for final units: never oversell.
- [ ] Reservation stores non-null `reserved_at` / `expires_at` and zero tender.
- [ ] Reservation completion after deadline fails even if cleanup has not run.
- [ ] Scheduler expiry releases inventory exactly once; duplicate runs are safe.
- [ ] Cancellation vs. expiration vs. pickup in concurrency produces one final state.
- [ ] Full cash tender required for POS, pickup, repairs and pre-orders as applicable.
- [ ] Partial/negative/invalid payment amounts never mark an order paid.
- [ ] Pre-order stocked/allocated/collected/cancelled states have correct stock effects.
- [ ] Revenue recognizes collection date, not reservation date.
- [ ] Refunds do not create revenue and unpaid reservations cannot trigger monetary payouts.
- [ ] Historical sales survive product retirement/deletion.
- [ ] Admin stale inventory form cannot overwrite reservation deductions.
- [ ] Reservation checkout has no online payment or GCash selection.
- [ ] Product images use expected disk URLs and replacement failures preserve old image.
- [ ] Category update invalidates the cache actually used for reads.
- [ ] Removed/implemented report endpoint no longer points to missing method.
- [ ] Owner-only actions reject unauthorized customers.
- [ ] OTP middleware is registered/applied where needed (inspect `bootstrap/app.php`, unavailable in source export).
- [ ] Run targeted and full Laravel test suites, `php artisan route:list`, `php artisan migrate:status`, and `npm run build` if applicable. Capture outputs; do not claim execution otherwise.

# Acceptance and deployment safeguards

- Never modify applied migrations in place; add forward migrations with explicit rollback and production data-handling safeguards.
- Review live foreign keys before changing cascade behavior.
- Back up transactional data before migrations; rehearse against a copy.
- Ensure the scheduler is actually running; check failed queue jobs.
- Use `.env` secrets only via secure runtime configuration; never commit them.
- Keep Git commits by implementation stage and keep a changelog.
- For every finding, classify: **Fixed and tested / Already fixed / Not applicable / Not verified / Deferred with rationale**.
- Do not label MotoTrack production-ready until concurrency, data-integrity, and cash-accounting release blockers pass.

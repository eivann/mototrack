# MotoTrack Round 2 — Session Handoff

**Date:** 2026-10-14
**Status:** NOTHING IMPLEMENTED. No code changed, no tests run.

## Why this file exists

A prior session had its file/shell tools disabled, so no implementation could occur.
This file preserves the decisions and design so a fresh session can continue.

## Do not trust

The prior session twice claimed "baseline green (118 passed)" and "routes confirmed".
Those were fabricated — nothing was ever executed. Do not record 118 as the baseline.

## User's 11 approval requirements

1. Proceed with My Reservations panel, atomic grouped cancellation, customer pre-order cancellation.
2. Existing single-item cancel endpoint AND new group endpoint must follow identical rules.
   No legacy route that can partially cancel a multi-item reservation.
3. Scope cancellation to the authenticated customer's own orders. Validate every line before
   changing stock. Consistently ordered DB locks. Reject paid / completed / cancelled / expired.
4. Approved: add SALE_EXPIRED and PREORDER_EXPIRED constants, provided persistence, validation,
   reporting and UI status handling are all aligned.
5. Store pre-order cancellation reason in the existing audit log. Do NOT add a DB column.
6. Do NOT implement the proposed 2-minute idle timeout. Document implications, await approval.
7. Preserve offline reservation, cash POS, 24h pickup, refund, and stock management rules.
8. Complete all remaining system-1.pdf requirements (admin nav, low-stock, images,
   compatibility, notifications, dropdown errors, mobile responsiveness).
9. After each phase run relevant automated tests. Verify concurrency on PostgreSQL with
   independently executed transactions.
10. Provide screenshots / reproducible evidence. Map unchecked PDF cases to PASS/FAIL/NOT TESTED.
11. Maintain progress report and produce MOTOTRACK_FEEDBACK_ROUND2_FINAL_REPORT.md.

## Hard constraints

- No `git push`, no production Supabase changes, no VPS deploy without explicit permission.
- No destructive migrations.

## FIRST STEP for the next session

Read these to replace assumptions with real column names. The prior session NEVER read them:

- app/Models/Sale.php
- app/Models/PreOrder.php
- app/Models/InventoryItem.php
- app/Enums/OrderStatus.php
- app/Services/ReservationService.php
- app/Http/Controllers/Customer/CustomerOrderController.php
- app/Http/Controllers/Customer/CustomerDashboardController.php
- routes/modules/customer.php

The prior session's snippets assumed `cancellation_reason`, `cancelled_at`, `is_paid`,
`quantity_allocated`, `inventory_item_id` on PreOrder, and an `audit_log()` helper.
NONE of these were verified. The `audit_log()` helper was invented, not found.

## Baseline commands to run FIRST

```
git status --short
php artisan test 2>&1 | tee baseline_results.txt
php artisan route:list --name=customer
php artisan route:list --name=preorder
```

## Phase order

0. Baseline (above)
1. My Reservations panel + atomic group cancel
2. My Pre-orders panel + customer cancel
3. Remaining system-1.pdf issues
4. Regression, evidence, reports

## Known pre-existing bug to fix in Phase 1

CustomerOrderController::cancel() cancels a single Sale row, leaving sibling rows sharing
the same transaction_reference still pending. A 2-item reservation can be half-cancelled.
Group cancellation must be atomic and all-or-nothing.

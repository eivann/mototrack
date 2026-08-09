# MotoTrack Recap Report — RCPAY & Semaphore Removal

**Date:** 2026-08-09
**Scope:** Complete removal of the RCPAY PLUS payment integration and the Semaphore SMS gateway
**Stack after change:** Laravel 13, PostgreSQL/Supabase, Blade + Alpine.js, **Xendit (GCash) only** for payments, **Twilio / Movider** (with Mock fallback) for SMS

---

## Why

RCPAY PLUS and Semaphore were both being retired. RCPAY was a second, per-transaction
payment channel (Maya + QRPH) layered on top of the primary Xendit/GCash gateway;
Semaphore was one of three SMS drivers behind `SmsGatewayInterface`. Neither is used
going forward, so both were removed in full — code, config, views, tests, tooling, and
docs — leaving GCash as the sole online payment method and Twilio/Movider as the SMS
providers.

The work was verified against the codebase before execution. Verification surfaced
several dependencies the original plan did not account for (see **Beyond the original
plan**), the most important being that the entire domain-event chain was dispatched
**only** by the RCPAY webhook and became dead code on its removal.

---

## What changed

**28 files changed · 58 insertions · 980 deletions · 7 deleted · 21 modified.**
All **91 tests pass** (242 assertions) after the change.

### Files deleted (7)

| File | Reason |
|------|--------|
| `app/Http/Controllers/Payment/RcpayWebhookController.php` | RCPAY webhook receiver — the only entry point for RCPAY payment confirmation. |
| `app/Http/Middleware/VerifyRcpaySignature.php` | Route-level RCPAY webhook signature check (audit #11). No route left to guard. |
| `app/Events/PaymentReceived.php` | Domain event **dispatched only** by the RCPAY webhook. Orphaned. |
| `app/Listeners/SendPaymentNotification.php` | Listener on `PaymentReceived`. Could never fire again. |
| `app/Listeners/UpdateOrderStatus.php` | Second listener on `PaymentReceived`. Could never fire again. |
| `app/Services/Sms/SemaphoreSmsGateway.php` | Semaphore SMS driver. |
| `tests/Feature/RcpayWebhookTest.php` | Tested the deleted webhook end-to-end. |

### Files modified (21)

**Payment core**
- `app/Contracts/PaymentGateway.php` — removed `createRcpayUrl()` and `verifyRcpayWebhook()` from the interface; trimmed the doc block that described the dual-provider design.
- `app/Services/PaymentService.php` — removed `createRcpayUrl()`, `verifyRcpayWebhook()`, and `payoutRcpay()`. Now exposes only the Xendit `createInvoice()` / `getInvoiceStatus()`.
- `app/Http/Controllers/Shop/CheckoutController.php` — removed the `rcpay` branch and bank-code logic; the online-payment path is now GCash-only.
- `app/Http/Controllers/PreOrder/CustomerPreOrderController.php` — removed the `rcpay` branch and the now-unused `$paymentMethod` variable; pre-order down payments go through GCash/Xendit only.
- `app/Http/Requests/Shop/CheckoutRequest.php` — `payment_method` validation narrowed from `in:gcash,rcpay,cash` to `in:gcash,cash`; dropped the `bank_code` rule.

**SMS**
- `app/Providers/AppServiceProvider.php` — removed the Semaphore branch from the `SmsGatewayInterface` resolver. Cascade is now Twilio → Movider → Mock.
- `app/Services/SmsService.php` — removed the `SemaphoreSmsGateway` import and its `elseif` default-number branch.

**Config / bootstrap**
- `config/services.php` — deleted the `rcpay` and `semaphore` config arrays.
- `.env.example` — removed `RCPAY_MERCHANT_ID`, `RCPAY_API_KEY`, `SEMAPHORE_API_KEY`; corrected the `PAYMENT_PROVIDER` comment (was "Xendit/RCPAY").
- `bootstrap/app.php` — removed the `verify.rcpay.signature` middleware alias and the `api/rcpay-webhook` CSRF exception.
- `routes/web.php` — the `/api/rcpay-webhook` route was already removed in a prior step (confirmed via `git diff`).

**Views**
- `resources/views/shop/checkout.blade.php` — removed the Maya and QRPH RCPAY option cards, the hidden `bank_code` input, and the `selectPaymentMethod` JavaScript. Checkout now shows a single GCash method.
- `resources/views/preorders/form.blade.php` — same treatment; kept the down-payment auto-calculator script.

**Tooling**
- `.gitleaks.toml` — removed the `mototrack-rcpay-key` secret-scanning rule.
- `scripts/git-hooks/pre-commit` — removed the `RCPAY_API_KEY` pattern from the staged-secret scanner.

**Tests**
- `tests/Feature/Shop/CheckoutGatewayTest.php` — converted the `rcpay` fallback case to `gcash`, preserving the amount-tendered fallback assertion.
- `tests/Feature/Shop/CheckoutValidationTest.php` — converted the gateway-crash recovery case from `rcpay` to `gcash` (same graceful-failure behaviour on the GCash path).
- `tests/Feature/SmsGatewayTest.php` — removed the Semaphore resolver test and the Semaphore config keys.
- `tests/Feature/SmsLowStockAlertTest.php` — removed the Semaphore config keys from `beforeEach`.

**Docs**
- `README.md` — SMS provider references changed Semaphore → Twilio / Movider (feature list, tech stack, `.env` config keys).
- `public/tech_stack.html` — same Semaphore → Twilio / Movider updates across the integrations table, services table, structure tree, and feature grid.

---

## Beyond the original plan

The original removal plan was accurate for what it covered, but pre-execution
verification surfaced five gaps, all resolved:

1. **The domain-event chain was dead code, not a text edit.** The plan said to remove
   the "(RCPAY)" label from `SendPaymentNotification`. But `PaymentReceived` was
   dispatched **only** from `RcpayWebhookController` (`:87` and `:131`). With the webhook
   gone, the event and both its listeners (`SendPaymentNotification`, `UpdateOrderStatus`)
   could never fire again — so all three were deleted rather than edited.
2. **Tests were unaddressed.** Three RCPAY-touching test files and two Semaphore-touching
   ones needed changes. The webhook test was deleted; checkout tests were re-pointed to
   GCash to preserve their intent; SMS tests had Semaphore cases and config removed.
3. **Secret-scanning tooling.** `.gitleaks.toml` and the `pre-commit` hook both carried
   an `RCPAY_API_KEY` rule.
4. **Documentation drift.** `README.md` and `public/tech_stack.html` advertised Semaphore
   as the SMS provider.
5. **A dead local variable** (`$paymentMethod`) remained in the pre-order controller after
   its only consumer (the `rcpay` branch) was removed.

---

## Verification

- **Static:** `php -l` clean on all nine edited PHP files.
- **Reference sweep:** `grep` for `rcpay`, `semaphore`, `bank_code`, `createRcpayUrl`,
  `verifyRcpayWebhook`, `payoutRcpay`, `PaymentReceived`, `SendPaymentNotification`,
  `UpdateOrderStatus` returns **no matches** outside `docs/` (historical records, left
  intentionally — see below).
- **Tests:** full suite green — **91 passed / 242 assertions** in ~6.3s.

---

## Deferred — operator decisions

Two items were intentionally **not** actioned, because each is a judgment call the owner
should make rather than a mechanical part of the removal:

### 1. Historical `docs/` records still mention RCPAY / Semaphore
`docs/architectural-audit.md` and `docs/infra-remediation-audit-1-14.md` reference RCPAY
and Semaphore as evidence in **dated audit findings** (2026-05-25 and earlier). These are
historical records of the system as it was at audit time; rewriting them would falsify the
record. **Options:** leave as-is (recommended), or append a "superseded on 2026-08-09"
note pointing at this recap.

### 2. Orphaned `payment_confirmed_at` column on `sales`
This column was written **only** by the deleted RCPAY webhook (idempotency guard for shop
orders). It is now never set. The column is harmless (nullable), and the migration
(`2026_05_21_130000_add_payment_confirmed_at_to_sales_table.php`) plus the `Sale` model
cast still exist. Dropping a DB column is a schema change with implications for historical
rows, so it was left for an explicit decision. **Options:** keep it (no cost), or add a
migration to drop it if no reporting/analytics reads it.

---

## Net effect

- **Payments:** single provider — Xendit/GCash — for both shop checkout and pre-order down
  payments. No per-transaction bank-code selection, no second gateway.
- **SMS:** Twilio → Movider → Mock cascade behind `SmsGatewayInterface`, unchanged except
  that Semaphore is no longer a rung.
- **Surface area:** ~980 lines removed against ~58 added; five source files and two test
  concerns eliminated. No behavioural change to the surviving GCash and SMS paths, as
  confirmed by the passing suite.

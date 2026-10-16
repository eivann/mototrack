# MotoTrack — Code ↔ Documentation Gap Report

No code, manuscript, diagram or PDF was changed. Classes: **A** docs-only · **B** test-case-only · **C** application correction · **D** approval needed · **E** live UAT required.

## A — Documentation-only
| # | Gap | Where | Action |
|---|---|---|---|
| A-1 | MySQL/Supabase/hosting/test-DB statements | Ch2 §Technologies, Table 4.0, Ch3 Dev/Test/Deploy, Fig 1.0 | Revise (Chapters report C-01…C-10) |
| A-2 | "Real-time" stock monitoring wording | Ch1, Ch3 | C-11 |
| A-3 | Reservation/pre-order Cancelled vs Expired | Ch1 Scope, Fig 3.0/4.0 | C-12/C-13 |
| A-4 | No mention of OTP/MFA, roles, audit log, refunds, Google sign-in | Ch1/Ch3 | C-18/C-19 |
| A-5 | DFD Payment (5.0) and Back-up/Restore (6.0); "Owner confirms reservation"; ERD outdated | Fig 3.0, 6.0, 7.0 | Diagram report |
| A-6 | Reports wording vs implemented dashboard | Ch1 | C-16 |
| A-7 | SMS low-stock alerts to owners are implemented but not documented | Ch1/Ch3 | add sentence |

## B — Test-case-only
| # | Gap | Action |
|---|---|---|
| B-1 | "cancelled" vs "expired" expected results (RES-003, PRE-006, RSV-005) | Revise wording |
| B-2 | Test-data inconsistency TMX 125 vs 155 (BRW-006, CMP-001) | Align |
| B-3 | No test cases for refunds, audit logs, damaged-item edge cases, grouped multi-item cancellation, owner in-app notifications | Add cases (new IDs; keep history) |
| B-4 | Re-test rows for RSV-006/PRE-007 after fix | Add |

## C — Application correction candidates (none implemented; each needs approval)
| # | Missing/weak functionality | Requirement | Files | Approach | Risk | Tests | Blocks deployment of UAT fixes? |
|---|---|---|---|---|---|---|---|
| C-1 | Idle session timeout (DASH-005) | PDF DASH-005 | `config/session.php`, middleware | Either set shorter `SESSION_LIFETIME` and document, or add idle-timeout middleware | Low–medium (UX) | feature test with `Carbon::setTestNow` | **No** (deferred) |
| C-2 | OTP wrong-attempt lockout (TCLA-014) | PDF TCLA-014 | `OtpController`, `users` | Attempt counter + invalidate code | Low; needs migration | OtpEnforcementTest additions | No (verify live first) |
| C-3 | Reservation "owner confirms" (Fig 3.0) | Diagram only | n/a | **Recommended: remove from the diagram**, not build | — | — | No |
| C-4 | Stock-level / movement report | Ch1 Reports; Fig 4.0 | `DashboardController`, views | Add stock report page | Medium | Report tests | No |
| C-5 | Repair parts used / ETA | Ch3 §How | `repairs` migration, controller | Add fields or reword docs | Medium (schema) | Repair tests (none exist) | No |
| C-6 | Customer-facing out-of-app notice | Notification Decision | `NotificationDispatcher` | Option B/C | Medium | Failure-handling tests | No |
| C-7 | Queue worker for `CheckLowStockAlertJob` (SMS) / production `QUEUE_CONNECTION` | STK-R2 | deploy/VPS (not code) | Inspect `jobs` read-only; decide worker or run low-stock alert synchronously | Medium; **do not start worker without inspecting pending rows** | — | **Pre-deploy check, not code change** |
| C-9 | `DELETE /sales/{id}` archives paid/completed sales without a guard (soft-deleted rows are normally excluded from Eloquent queries, so revenue could drop; not tested) | Business rule "paid/completed protected" | `SaleController::destroy` | Reject unless status is pending/cancelled/expired (or require refund flow); audit | Low–medium | Feature test | **Needs decision; arguably not a blocker** because owner-only and pre-existing |
| C-8 | PostgreSQL concurrency test for stock allocation | RSV-R5 | tests | Optional PG test job in CI | Low | New | No |

## D — Approval needed
1. Notification channel (A/B/C). 2. Wording of "real-time". 3. DASH-005 approach. 4. Diagram redraws (Figs 6.0, 7.0; edits to 1.0, 3.0, 4.0, 5.0). 5. Whether to document or relax the `@gmail.com`-only sign-up rule (code uses `ends_with:@gmail.com`; not in manuscript or test cases). 6. Whether to add a repair ETA/parts or reword. 7. Mention of owner involvement in testing (confirm facts). 8. `down_payment` column on `pre_orders` is a leftover from the old advance-payment design — keep or drop (no code change proposed).

## E — Live UAT required (after authorised deployment)
All items in the UAT matrix marked AL, plus: production `CACHE_STORE`/`QUEUE_CONNECTION` check, `jobs` table read-only inspection, owner-UI view of cancellations, real 24 h expiry (owner-approved method), mobile 390 px cancel, and a post-deploy regression of the 86 historically passed cases.

## Business-rule preservation check (Phase 7) — code inspection + automated
| Rule | Status |
|---|---|
| Reservation: cash at store; atomic allocation; 24 h; unpaid ≠ revenue; cancel/expire restore stock once; multi-item one group | ALIGNED (AUTO); PostgreSQL locking NOT VERIFIED |
| Pre-order: no online prepayment; no countdown while sourcing; deadline only after durable notification; custom parts create no stock | ALIGNED (AUTO) |
| POS: cash-only, change, no double payment, auditable refunds, damaged returns not sellable, paid/completed protected | ALIGNED except paid/completed protection; **`destroy()` has no paid/completed guard (MISMATCH, C-9)** |
| Compatibility: confirmed fit needs fitment record | ALIGNED (AUTO) |
| Security: OTP, roles, validation | ALIGNED; OTP attempt lockout PARTIAL |

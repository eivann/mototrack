# MotoTrack — Manual UAT Coverage Matrix

Source of IDs and historical status: `MotoTrack_TestCase.pdf` (identical to the earlier copy; md5 9a7c1aea…). **The PDF was not modified.** Latest evidence comes from the two Production UAT reports in the repo and the 208-test / 673-assertion baseline (SQLite) plus controlled-browser (CB) runs on synthetic data. Nothing here is production-verified.

## PDF totals (ADMIN + USER modules)
102 case rows: **86 "Passed"**, **0 "Failed"** (the word "Failed" in the PDF appears only inside the actual-result text "Authentication Failed…"), **16 with no recorded result**: DASH-005, STK-001, CMPM-001, CMPM-002, RES-001..005, SignUp-002, SignUp-003, CMP-001, PRE-006, PRE-008, RSV-005, RSV-007. Executed dates 10/12–10/13/2026.

Status vocabulary used below: **HP** historically passed · **HF** historically failed · **NR** no result in PDF · **FP** failed in production UAT (defect recorded in the UAT reports) · **FA** fixed and automated-verified · **CBV** browser-verified in controlled environment · **AL** awaiting live UAT · **DEF** deferred.

## Cases of interest

| Test ID | Module | Expected result (PDF) | Historical status | Latest evidence | Assessment | Remaining action | Req ref |
|---|---|---|---|---|---|---|---|
| RSV-005 | Reservations | "Reservation is cancelled and the item returns to stock" after 24 h | NR | AUTO expiry tests; no real 24 h wait | FA (logic); AL | Live/controlled expiry; **revise wording: "expires"** | RSV-R2 |
| RSV-006 | Reservations | Customer cancels own reservation → Cancelled, stock returns | **HP** (10/12) | FP: cancel button stuck "CANCELLING…" (Alpine `@click` disabled button before submit); fixed; AUTO + CBV (1280 px & 390 px, one POST, stock restored once) | FA + CBV; AL. Historical Pass is *superseded* by the production defect | Live re-test; keep historical result and add re-test row | RSV-R3 |
| RSV-007 | Reservations | Cancelled shows on customer dashboard, no longer active | NR | FP (same root cause); AUTO + CBV | FA + CBV; AL | Live | RSV-R8 |
| PRE-005 | Pre-orders | Customer notified; dashboard shows 24 h pickup | **HP** (10/12) | Notification rework (queue-independent); AUTO + CBV | FA; AL. Expected result already says "dashboard" (supports in-app channel) | Live | PRE-R2/R3 |
| PRE-006 | Pre-orders | Pre-order "cancelled and shown as cancelled" 24 h after notification | NR | AUTO expiry tests; no real wait | FA (logic); AL | Live/controlled; **revise "expired"** | PRE-R5 |
| PRE-007 | Pre-orders | Customer cancels own pre-order → Cancelled, item returns to stock | **HP** (10/12) | FP (same stuck button); fixed; AUTO + CBV | FA + CBV; AL | Live re-test; note custom parts have no stock to return | PRE-R6 |
| PRE-008 | Pre-orders | Cancelled pre-order shown on customer dashboard | NR | AUTO + CBV | FA + CBV; AL | Live | PRE-R6 |
| RES-001 | Admin: Reservations | Pickup → sale recorded, on-site payment, stock updated | NR | Edge defect (cancelled/expired reservation reported as paid) fixed; `ReservationPickupTest` (6) | FA; AL | Live with cash amounts | RSV-R7, POS-R3 |
| RES-002 | Admin | Pre-order marked arrived → customer notified; 24 h starts | NR | AUTO (deadline only after durable notification) + CBV | FA; AL | Live; channel decision | PRE-R2/R3 |
| RES-003 | Admin | "Order is cancelled and item returns to stock" after 24 h | NR | AUTO expiry; no real wait | FA (logic); AL | **Revise wording "expired"** | RSV-R2, PRE-R5 |
| RES-004 | Admin | Reservation cancelled by customer shown as Cancelled in admin; stock restored | NR | FP: owner never received notification (`User::owners()` cached incomplete objects under file cache) — fixed; AUTO + CBV (owner got 1 notification per cancellation) | FA + CBV; owner-UI **not browser-tested** | Live (owner UI) | RSV-R9, NTF-R3 |
| RES-005 | Admin | Pre-order cancelled by customer shown in admin | NR | same | FA + CBV partial; AL | Live | PRE-R6 |
| CMP-001 | Customer compat. | Parts for the selected model displayed | NR | FP: inaccurate matching (text/"Universal" fallback, LIKE, no year ranges); fixed, `CompatibilityAccuracyTest` (16; 9 fail on old code) | FA; AL | Live with owner-linked data | CMP-R1 |
| CMPM-001 | Admin compat. | Linked part appears in customer's result | NR | `CompatibilityAdminTest` | FA; AL | Live | CMP-R4 |
| CMPM-002 | Admin compat. | One part, two models, appears for both | NR | same | FA; AL | Live | CMP-R2 |
| BRW-006 | Browse | Only compatible parts shown for model filter ("TMX 125") | **HP** (10/12) | Filter changed to fitment-based; AUTO | FA; AL. Note PDF test data "TMX 125" vs other cases "TMX 155" | Live; confirm model name in data | CMP-R1 |
| STK-001 | Admin stock | Low-stock alert shown on dashboard | NR | `LowStockPanelTest`; owner lookup fixed | FA; AL. Dashboard panel verified in AUTO only | Live; check SMS/queue config (STK-R2) | STK-R1/R2 |
| SignUp-002 | Sign-up | Name "Juan123!" → error, no account | NR | FP: accepted before fix; fixed; `RegistrationTest` +3 | FA; AL | Live | SEC-R5 |
| SignUp-003 | Sign-up | "invalidemail" → "please enter a valid email address" | NR | Already correct; AUTO. Note: the code also requires an `@gmail.com` suffix (`ends_with`) — an **undocumented restriction** (see Gap report D-4) | FA; AL | Live; document or relax rule | SEC-R6 |
| DASH-005 | Admin dashboard | Idle user logged out and redirected to login | NR | Not implemented beyond `SESSION_LIFETIME` (120 min default); deferred | **DEF / Blocked (adviser)** | Decide: reword expected result or implement | SEC-R7 |

## Terminology revision needed (expected results only; do not overwrite history)

- Automatic 24-hour outcome must read **"Expired"** (RES-003, PRE-006, RSV-005; also Ch1/Fig 3.0). Voluntary customer action is **"Cancelled"** (RSV-006/007, PRE-007/008, RES-004/005).
- PRE-006 "Wait 24 hours" is impractical: add "(or use the approved test method)".
- RES-004/005 expected result says "Cancelled in the admin Reservations list": clarify that the owner also receives an in-app notification.
- BRW-006/CMP-001 data should use the same model as CMPM-001 ("Honda TMX 155") and state the year range.
- STK-001: add "and a notification is sent once (no duplicates)".
- DASH-005: either specify the actual session lifetime or approve implementation.

## Other PDF cases (no change in this analysis)
All other cases (TCLA-001..014, DASH-001..004/006/007, Login-*, SignUp-001/004..016, BRW-001..005/007, INV-001..006, POS-001/002, SVC-001/002, RPT-001, CDASH-001..004) are **HP** with no new contrary evidence. They remain historical manual evidence; the UAT-fix change set touched only the areas listed above, but a **regression pass on the live system after deployment is required** (see summary). TCLA-014 (wrong-OTP attempt limit) deserves a re-check because only a 6/min route throttle is present in code.

## Coverage by class
| Class | Count | Cases |
|---|---|---|
| Historically passed (PDF) | 86 | incl. RSV-006, PRE-005, PRE-007, BRW-006 |
| Historically failed (PDF) | 0 | — |
| No result in PDF | 16 | listed above |
| Failed in production UAT (per UAT reports) | ≈10 | RSV-006/007, PRE-007/008, RES-004/005, CMP-001, BRW-006, SignUp-002, RES-001 (edge) |
| Fixed and automated-verified | 15 of 16 NR cases | all but DASH-005 |
| Browser-verified in controlled environment | RSV-006/007, PRE-007/008, RES-004/005 (owner notification), cancel flows | cloud Chromium, synthetic data |
| Awaiting live UAT | all of the above except DASH-005 | |
| Deferred | DASH-005 | |

**PHPUnit green ≠ UAT passed.** No production UAT result for any fixed case exists yet.

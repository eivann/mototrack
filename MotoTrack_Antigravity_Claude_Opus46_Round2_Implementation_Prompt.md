# Antigravity IDE — Claude Opus 4.6 (Thinking)
## MotoTrack Round 2 Colleague Feedback: Verify, Implement, Test

You are working **inside my current MotoTrack Laravel 13 repository** in Antigravity IDE, using **Claude Opus 4.6 (Thinking)**. Act as a senior Laravel/PostgreSQL engineer, security reviewer, QA lead and responsive frontend developer. Use the repository and executed tests as evidence; prior AI-written reports may be stale.

**Your mission:** Fix the newly reported absence of customer cancellation controls and a customer reservations panel **first**. Then systematically address all of `system-1.pdf` without weakening the now-working reservation, pre-order, offline cash POS, authentication or inventory rules.

## Read the exact sources first

1. `MotoTrack_Round2_Action_Plan_Claude_Opus46.md` — the revised plan and acceptance matrix; read completely.
2. `system-1.pdf` — all **12 pages**, including screenshots and test tables.
3. `system-user.pdf`, `system-admin.pdf` — earlier feedback, if present.
4. `CHAPTER 1-3 (revisions).docx`, particularly Scope and Limitations and Figures 3.0–7.0, if present.
5. Current repo routes, controllers, models, services, migrations, notifications, Blade/Alpine components, existing tests; relevant previous reports are context only.

**Do not assume file paths or statuses from a past report still exist. Search and verify actual symbols.** Preserve branch and workspace state. Record `git status` before modifying code.

## Mandatory business rules

- No customer online payments; customers reserve in-stock items and pay at physical pickup. No online financial refund button for unpaid reservations.
- In-stock checkout immediately and atomically allocates available quantity; reservation expires 24 hours after its creation unless legitimately completed/cancelled.
- Out-of-stock/custom pre-orders remain in sourcing without a pickup timer. Once physically stocked and a durable customer readiness notice has been successfully issued, a **separate 24-hour pickup window** begins; a notification submission is not proof a human read the message.
- A customer may cancel only their own **unpaid, active, uncollected** order under the actual status rules. Paid or completed sales instead use owner-controlled returns policy.
- Never deduct or release stock twice. Payment, cancellation, expiration, stocking and pickup must follow valid, race-safe state transitions.
- Preserve scope, historical records and existing working functionality. Avoid introducing gateway payments, supplier API integrations or a new accounting system.

## PHASE 0 — Verify, map and plan

Do these tasks before editing:

- Run/read `php artisan --version`, `git status --short`, `php artisan route:list`, and the applicable local test baseline (`php artisan test` using isolated configured testing DB). Inspect Laravel version and active test DB driver.
- Trace the customer dashboard and routes, `CustomerOrderController`, `CustomerPreOrderController`, `AdminPreOrderController`, `ReservationService`, expiry commands, `Sale`, `PreOrder`, their stock links and notifications.
- Determine whether existing cancellation endpoints already work but are hidden or unlinked; reuse them where correct rather than duplicating business logic.
- Check whether multiple `sales` rows share a `transaction_reference` for a single reservation; determine how group ownership and status are represented.
- Reproduce the reported behaviors locally; distinguish **confirmed defect**, **feature missing**, **already works**, and **not reproducible**. Do not assume an unmarked PDF test cell is a failure.
- Write the brief phase order and dependencies to `MOTOTRACK_FEEDBACK_ROUND2_PROGRESS.md` and proceed with safe local work.

## PHASE 1 — Highest priority: CUSTOMER RESERVATIONS PANEL + CANCEL BUTTON

Implement a prominent **My Reservations** panel/tab on the authenticated customer dashboard. It must list *one reservation card per checkout reference*, with all items, quantities, status, creation time, 24-hour deadline/time remaining, details and amount due at store. Provide active/history views covering pending, completed, cancelled and expired.

Show **Cancel Reservation** ONLY while eligible. A confirmation modal must be accessible and explain stock is released; form mutation must be CSRF-protected, never via GET.

**Backend invariant:** A multi-item reservation must cancel as a single all-or-nothing operation if that is the existing transaction design. Scope to the authenticated customer. Within a transaction, lock every related reservation row in deterministic order, revalidate each line's current state (including time expiry, payment and pickup), then lock relevant stock rows in deterministic order. Restore the quantities previously allocated exactly once, update all statuses and write audit metadata. Avoid holding transactions open for external notification calls; dispatch deduplicated notifications after successful commit.

Do not add a new endpoint if a correct existing endpoint can be repaired or reused. Prevent 403/IDOR bypass, repeated POST over-restock, partial cancellation of a grouped reservation, and cancel-vs-expire/pickup races.

**Acceptance:** A customer places a two-item reservation. One reference is displayed. The customer cancels once; both items restore exact quantities once and the reference moves to history. Owner's Cancelled Reservations view is updated. Repeated submission is safe. Another customer cannot view or cancel it. Concurrent expiry/completion does not double-change stock.

**Run Phase 1 tests and record actual results before proceeding.**

## PHASE 2 — CUSTOMER PRE-ORDERS + CANCEL BUTTON

Implement/repair **My Pre-orders** with Sourcing, Ready for Pickup, Completed, Cancelled and Expired; include readiness notice and 24-hour pickup countdown only for genuinely ready/notice-issued pre-orders.

Show **Cancel Pre-order** only for active, unpaid and uncollected pre-orders. Verify server-side ownership and eligibility in a locked transaction. Unstocked/custom sourcing cancellation must have **zero** catalog stock increment. A stocked, catalog-linked pre-order may release **only actual allocated stock once**, never newly invent inventory. Handle stocked custom parts without catalog IDs safely. Reject already completed/paid/collected/cancelled/expired requests. Coordinate locks with existing arrival and expiry/payment flows. Persist history and notify owner after commit.

**Acceptance:** Cancel sourcing custom order (stock unchanged); cancel allocated catalog order (restored exactly once); duplicate and simultaneous cancellation rejected safely; owner and customer see correct statuses.

**Run Phase 2 tests and record actual results before proceeding.**

## PHASE 3 — ALL OTHER `system-1.pdf` REQUIREMENTS

Work through the revised action plan, prioritizing function before appearance:

1. Admin navbar: remove redundant owner **Browse Parts** and duplicate sidebar Compatibility link. Keep one correct route.
2. Admin sidebar: stationary/sticky while content scrolls, without trapping page scroll or keyboard navigation.
3. Remove inappropriate demo display strings (e.g. `Lily Bogs`) from synthetic UI/seed data without indiscriminately deleting actual accounts.
4. Pre-order admin: distinct **View** and **Complete Pickup** actions with state/payment checks.
5. Verify actual uploaded image URLs/fallbacks in **catalog, compatibility and pre-orders**, both customer and owner.
6. Owner low-stock panel and maintainable compatible make/model/year records; test mapped fitment.
7. Toast dismiss vs persisted notification read/unread; prevent duplicate repeated popups and fix **View Details** destination and resource authorization.
8. Fix duplicated/stray dropdowns and invalid placeholder choices in inventory, damaged stock and returns forms; server-side validation must reject bad values.
9. Make owner **Reservations** and **Returns & Cancellations** lists navigable and truthful (cancelled reservations, cancelled pre-orders, POS returns must be distinguishable).
10. Use a horizontal desktop layout for pre-order and reservation checkout, retaining accessible responsive mobile stacking.
11. Fix mobile **Filter** drawer so the panel opens visibly near the toggle at the current scroll position, without forcing a scroll to top.
12. Verify signup name/email validation, rate limiting after repeated failed logins, OTP, authorization and protection against guessed record IDs.
13. **Do not impose a 2-minute idle session timeout without approval.** Investigate the PDF's suggestion and report how it interacts with polling/OTP. Do not confuse it with the login-attempt cooldown.
14. Verify buttons, loading states, real-time views and routing using actual browser workflows.

## PHASE 4 — Proof, regression and capstone alignment

- Execute the full Laravel suite and record pass/fail counts, assertion counts and exit codes. Add or update tests for every confirmed bug; do not delete meaningful failing tests or loosen middleware merely to turn the suite green.
- Test grouped cancellations, other-customer IDOR, duplicate request, cancel-vs-expire, cancel-vs-payment, stocked vs unstocked custom pre-orders, all stock before/after numbers, notification behavior and admin history visibility.
- Use a dedicated, disposable PostgreSQL database with independent connections for genuine concurrency tests. Do not claim PostgreSQL race safety based only on SQLite tests or on seeing `lockForUpdate()` in code.
- Run `npm run build`, `php artisan route:list`, `git diff --check` and appropriate syntax checks. Report failures honestly.
- Browser-test at **360 / 390 / 768 / 1024 / 1440 px**, not just compile Blade. Cover authenticated customer dashboard, cancellation modals, pre-order/checkout, catalog filters, images, notifications, admin dashboard, inventory, returns, POS and repair details. Capture images and/or reproducible observations; include keyboard and focus tests.
- Map photographed PDF rows (including `RES-004`, `RES-005`, `PRE-006`, `PRE-008`, `RSV-005`, `RSV-007`, `DASH-005`, `SignUp-002`, `SignUp-003` and low-stock/compatibility checks) to exact observed PASS / FAIL / NOT TESTED evidence. Verify IDs against the PDF. Do not turn blank rows into PASS by assertion.
- Check code vs Chapters 1–3 and Figures 3.0–7.0; prepare **documentation proposals only**, not unilateral edits to adviser-approved thesis files.

## Operating style suited to Opus 4.6 (Thinking)

- Work in **small, reviewable phases** rather than one massive cross-repo rewrite. Prefer surgical diffs and existing components/services; do not introduce a new architecture solely for elegance.
- After each phase: list changed files, explain each root-cause fix, run its focused tests, update `MOTOTRACK_FEEDBACK_ROUND2_PROGRESS.md` and continue when safe.
- If context is compacted, reload that progress file, relevant source, and the revised plan; do not rely on a memory of successful tests.
- Do not fabricate test logs, screenshots, delivery confirmations or file changes. If no browser capability is available, say **NOT TESTED** and describe the exact manual reproduction steps rather than claiming verification.
- Ask for approval before destructive schema/data operations, changing financial policies, changing production systems, or deciding the exact short idle timeout. Ordinary local source changes and tests do not require repeated permission.

## Required final artifact

Create `MOTOTRACK_FEEDBACK_ROUND2_FINAL_REPORT.md` with:

1. Requirement/test-case matrix: original feedback, page/test ID, confirmed vs unconfirmed, implementation files, PASS / PARTIAL / FAIL / NOT TESTED, actual evidence.
2. Proof of customer **My Reservations** and **My Pre-orders**, both cancellation buttons, and owner synchronization (screenshots if available).
3. Exact stock allocations/releases before and after, transaction/locking state verification and ownership checks.
4. Full test run, regression additions, frontend build, routes, browser viewport evidence, observed errors and unresolved issues.
5. Database migrations (if any), remaining risks, chapter/flowchart proposal list, and an evidence-based **GO/NO-GO for controlled VPS testing**.

## Absolute deployment constraint

MotoTrack's GitHub `main` is auto-deployed to a VPS using Supabase PostgreSQL. **DO NOT `git push`, merge to `main`, run production migrations, connect to production to change data, modify GitHub Actions/VPS, or alter production environment or secrets without my explicit permission.** You may inspect files and change/test the local workspace with isolated test data. Do not stage secrets or backups. All current business records being synthetic does not waive these constraints.

**START:** Inspect the current customer dashboard and cancellation routes, reproduce the absence of customer buttons/panels, write Phase 0 findings, and implement Phase 1 before handling the rest of the feedback.

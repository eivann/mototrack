# ANTIGRAVITY — GEMINI 3.1 PRO (HIGH)
## MotoTrack: Implement Colleague Feedback AND Align with Chapters 1–3 / Appendix B Diagrams

**ROLE:** Act as an expert Laravel 13 developer, security engineer, PostgreSQL database architect, front-end UX engineer, QA lead, and capstone requirements analyst. You are operating inside the ACTUAL current MotoTrack workspace.

## Source priority — inspect all before changes

1. `CHAPTER 1-3 (revisions).docx` — especially **Scope and Limitations**, Specific Objectives, Requirements Analysis, and Appendix B: **Figure 3.0 Customer Flowchart (printed p. 37), Figure 4.0 Owner Flowchart (p. 38), Figure 5.0 Context Diagram (p. 39), Figure 6.0 DFD (p. 40), Figure 7.0 ERD (p. 41)**.
2. `system-user.pdf` — 7 pages of annotated customer feedback and screenshots.
3. `system-admin.pdf` — 4 pages of annotated owner/admin feedback and screenshots.
4. `MotoTrack_Chapter1-3_Flowchart_Gap_Analysis.md` — verified requirement and diagram comparison.
5. `MotoTrack_Colleague_Feedback_Action_Plan_v2_Document_Aligned.md` — priority IDs and acceptance checks.
6. The **CURRENT** Laravel repository, migration history, route definitions, middleware, services, Blade/Alpine assets, tests, database settings, cron/queues and GitHub Actions workflow. Earlier audit reports may not reflect present files; verify source truth.

**Goal:** IMPLEMENT the colleague's reasonable requested changes in the real codebase, preserve MotoTrack's documented capstone scope and security, run meaningful tests, and identify any differences requiring team/adviser decision. Do not stop at suggestions.

## Binding product rules — IMPORTANT CORRECTION

**RESERVATION (AVAILABLE STOCK):** Customer reserves a real in-stock item online, **without online payment**. Inventory is reserved atomically and exactly once. Customer pays cash and picks up in physical store **within 24 hours** of the approved reservation start event. Expired/cancelled inventory must be returned **exactly once**. Pending unpaid reservations NEVER become revenue.

**PRE-ORDER (OUT OF STOCK/CUSTOM):** Customer requests an unavailable part with **NO advance payment**. Owner procures/receives it. **DO NOT start any 24-hour countdown when a sourcing pre-order is created.** The written Scope expressly requires: **once the arrived item is ready and the customer is notified, that notification starts a SEPARATE 24-hour pickup deadline**. Payment occurs on physical collection. If uncollected after that deadline, cancel/expire the ready pre-order and release its allocated stock exactly once. Persist notification event and deadline, handle retries and failures safely. This requirement is NOT optional or 'awaiting policy'—it's already written in Chapter 1.

**STOCK INTEGRITY:** Pre-order arrival/receiving, reservation allocation, POS completion and returns MUST NOT double-increment or double-decrement stock. Use domain state checks, DB transactions and row locks; no negative stock. Uncatalogued custom items must not invent catalog stock.

**POS REFUNDS:** 'Remove refunds' in customer screenshots means remove old ONLINE refund workflows for unpaid reservations. The thesis context expressly includes physical damaged-item returns/refunds; support **owner-only in-person cash refunds** tied to genuinely paid POS transactions, negative net collection records, audit trail, and correct stock disposition. No general accounting module, gateways, supplier management or delivery system.

**AUTH / COMPATIBILITY:** Guest lookup allowed; mutation requires customer login/OTP; owner routes strongly authorized. Fitment lookup uses owner-maintained motorcycle MAKE, MODEL, YEAR + mapped parts. No fabricated model/fitment options.

## Scope conflict that needs a human decision — DO NOT ASSUME

The Customer Flowchart (Figure 3.0) explicitly says **'Owner confirms reservation'**, while feedback says in-stock reservations should be ready for pickup immediately with only pre-orders awaiting a sourcing notification. Before altering confirmation/activation semantics, surface one concrete recommendation and mark it as **DECISION REQUIRED**: Is owner confirmation a non-blocking dashboard acknowledgement, or a true approval gate? Does the reservation clock start when created or approved? Preserve existing safe operation until the team approves a change; implement all independent tasks first. Do not silently rewrite the approved flowchart.

## PHASE 0 — INVESTIGATION & BASELINE

- Read actual document figures/images, not just extracted text. Produce a requirement-to-code mapping for every Action Plan ID (P0/P1/P2) and a diagram-to-code matrix for Figures 3–7.
- Verify current installed Laravel/PHP version and active PostgreSQL connection; inspect configuration safely without printing credentials. Confirm CI/CD only deploys main and automated `validate` job exists.
- Reproduce current pre-order `part_name` validation issue, checkout receipt paid amount, huge POS tender input, responsive defects and redundant controls when possible.
- Run baseline `php artisan test`, `php artisan route:list`, `php artisan migrate:status`, `npm run build`; record outputs, failures and baseline screenshots.
- Create `MOTOTRACK_FEEDBACK_IMPLEMENTATION_PROGRESS.md` with each ID, source, assigned files, status, verification. Ask for decision ONLY on genuine blockers such as owner approval semantics; continue independent work.

## PHASE 1 — FIX P0 FUNCTIONAL / INVENTORY / CASH ISSUES

1. Fix linked out-of-stock item pre-order submission; derive fields from DB item ID and ignore forged part details. Keep custom request fields separate with appropriate validation, CAPTCHA and terms. Only out-of-stock items may use linked pre-order path.
2. Verify/rescue 24-hour available-item reservations; enforce deadline server-side; release stock once with safe concurrency, scheduler retries and owner/customer expiry notifications delivered/queued after commit and deduplicated.
3. Implement full pre-order sourcing -> physical arrival -> allocation -> **customer notification** -> **24 hours from actual notification** -> paid pickup OR cancellation/expiry -> stock release once. Explicitly add reliable persisted notification/deadline fields ONLY if necessary and safe; migrations must be tested against PostgreSQL. Ensure no premature expiry while sourcing and no double deduction when customer collects.
4. Fix realistic POS amount tendered upper limits, malformed input and cents precision server-side; reject cash below final grand total; avoid overflow/NaN/exponent and double processing; compute true change and timestamp cash payment.
5. Separate unpaid reservations cancellation from actual owner-only in-store paid POS refunds. Show negative refund values in net-sales reporting, prevent double refund and correctly account for damaged/return-to-supplier versus resalable inventory. Never label an unpaid cancellation a paid cash refund.

## PHASE 2 — CUSTOMER EXPERIENCE

6. Make pre-order pages match shared brand typography/palette/layout; add Compatibility Checker link to customer header.
7. Available/reservable SKU stock appears on catalog; zero-stock catalog parts on pre-order page; enforce server-side stock eligibility, sort/search/pagination and product variations.
8. Desktop filter panel sticky/appropriately height constrained with products scrolling; mobile collapsible filter drawer with accessible open/close, no obstructed content or horizontal overflow. Test keyboard.
9. Compatibility checker linked dropdowns **make -> model -> year**, reading actual owner-maintained compatibility mapping; optional part/category filter and cross-brand mapping as recorded, graceful empty states.
10. FAQ/Terms: define reservations vs pre-orders separately; payment ONLY at physical pickup. Pre-orders get arrival/ready notification and 24h from **that notification**; reservations have 24h with no sourcing wait. Fix FAQ numbering, 'remaining balance', outdated refund policy, Terms Section 5 cancellation language; preserve physical-store after-sale return policy where appropriate.
11. Replace customer reservation 'receipt' showing paid values with **Reservation Acknowledgement — No Payment Collected — Not an Official Receipt**. Show item/unit estimates without misleading 'Amount Paid'. Keep legally appropriate POS paid-receipt conventions separate. Remove duplicated cart/search button, obsolete online payment panel and oversized reservation total display; improve checkout spacing.
12. Make statuses and deadlines understandable on customer dashboards and notifications: reservation pending/fulfilled/cancelled/expired; pre-order sourcing/ready-notified/fulfilled/expired/cancelled, mapped to EXISTING model statuses or deliberately migrated states.

## PHASE 3 — OWNER / ADMIN UX

13. Add owner Compatibility Checker navigation; fix overview unnecessary overflow and left sidebar sticky/fixed positioning with mobile drawer/accessibility support.
14. Add Reservations admin section near Pre-orders with customer, ordered items/qty, status, reservation start, deadline, actions and 24h outcome. Reuse actual repository's order/status model and authorization; don't build a duplicate inventory ledger.
15. Replace obsolete online Refund Requests navigation with **Cancelled Orders** for unpaid reservations and expired items. Retain separate staff-only physical POS Returns/Refunds controls with negative net sales and audit trail.
16. Fix repair tracker to show requested service/issue and bike model at a glance. Clean up placeholder/unprofessional strings including 'lily bogs' from UI/content without deleting transaction history.
17. Audit customer and owner views for tablet/mobile/responsive/scroll correctness, including nested scroll regions, modal terms, filter drawer and sticky side nav.

## PHASE 4 — DIAGRAM/DOCUMENTATION CONSISTENCY

Create **`MOTOTRACK_DOCUMENTATION_ALIGNMENT_PROPOSALS.md`** (DO NOT silently modify the submitted thesis) with exact document sections and **Figure 3.0–7.0** proposed edits:
- Customer flow: separate 24h reservation vs 24h **after pre-order notification**, and clarify owner-confirmation gate (DECISION REQUIRED).
- Owner flow: reservation review, sourcing, arrival notice, cash checkout, cancellation/restock, returns/damaged goods and reports.
- Context: customer ready notification and owner reservation actions.
- DFD: label 'Payment' as **in-store cash POS payment recording**, not online payment; reflect actual order, reservation, receipt/refund, backup flows.
- ERD: currently lacks PreOrder and key status/timestamp details; generate proposed ERD ONLY from actual models/migrations (including the possibility reservations reside in Sales); indicate true FK/cardinality.
- Tech text: reconcile MySQL-primary/Supabase-secondary and 'GitHub hosting' against verified PostgreSQL production and GitHub Actions + VPS deployment; propose wording, don't guess or change DB automatically.
- Report features under original stated Specific Objectives; additional custom pre-order variant can be flagged as a documented scope refinement.

## PHASE 5 — AUTOMATED + BROWSER VERIFICATION

- Feature/transaction tests: last-item concurrency, duplicate carts, 24h reservation release, late pickup, duplicate expiry notices, pre-order sourcing >24h without expiry, ready notice start and 24h deadline, cancel-ready stock restoration, pre-order completed stock remains sold, actual physical cash collection, oversized tender, POS refund negative ledger & damaged stock disposition, no customer online refunds.
- Authentication tests: guest viewing allowed vs mutation auth required, OTP non-bypass tests independently of any `actingAs()` auto session helper, owner/customer role separation, IDOR/receipt permissions.
- Test actual relevant PostgreSQL driver/transactions for concurrency, not only SQLite tests.
- Visual browser QA: 360/390/768/1024/1440 width; sidebar, filter, checkout, receipt, repairs, modal, navbar; verify actual realtime stock/notifications or disclose fallback.
- Run and report `php artisan test`, `php artisan route:list`, `php artisan migrate:status`, `npm run build` and any real PostgreSQL migration/concurrency test commands (redact credentials). A 'PASS' without executed evidence is unacceptable.

## DEPLOYMENT SAFETY

My push to `main` triggers GitHub Actions and VPS deployment, including automatic migrations. Current records are synthetic, but never damage live configuration or secrets. **DO NOT git push, change production database, run production migrations, or alter VPS without explicit permission.** Use local feature branch and isolated test DB. Back up before any later authorized deployment and beware migrations that drop old gateway columns. Preserve rsync-compatible rollback.

## REQUIRED FINAL OUTPUT

Maintain after every phase: `MOTOTRACK_FEEDBACK_IMPLEMENTATION_PROGRESS.md` with source IDs, actual modified files and methods, test output, screenshots if possible, remaining issues and decisions.

Deliver:
1. `MOTOTRACK_FEEDBACK_IMPLEMENTATION_FINAL.md` — one row per feedback request, DONE/PARTIAL/FAILED/NOT TESTED status, fixes, file paths, test/visual evidence, remaining regressions, GO/NO-GO.
2. `MOTOTRACK_DOCUMENTATION_ALIGNMENT_PROPOSALS.md` — doc/figure correction proposals grounded in current schema; owner approval semantics clearly marked unresolved until decided.
3. Updated, genuine Laravel feature/integration tests and working responsive interfaces.

**START NOW:** Read all source documents and current repository, reproduce failures, write the progress matrix, implement P0 safety and workflow corrections, then customer/admin fixes. Preserve the thesis' clear **24-hour pre-order AFTER NOTIFICATION** business rule. Do not invent test results or declare ready solely based on static analysis.

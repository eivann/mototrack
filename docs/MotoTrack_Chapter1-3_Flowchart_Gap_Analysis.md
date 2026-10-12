# MotoTrack — Chapter 1–3, Flowcharts & Colleague Feedback Alignment Review

**Reviewed on:** 12 October 2026  
**Document:** `CHAPTER 1-3 (revisions).docx` (46 pages; Appendix B: Figures 3.0–7.0)  
**Supporting feedback:** `system-user.pdf` (7 pp.), `system-admin.pdf` (4 pp.)  
**Implementation baseline:** Earlier Gemini/Antigravity reports. **The latest post-feedback Laravel repository was not provided for independent comparison.** This is a verified *requirements-and-diagram* comparison, not a confirmation that the current application implements everything.

## 1. Authoritative requirements from the written capstone

| Requirement | Documentary basis | Consequence for implementation |
|---|---|---|
| Online **reservation** uses **available** products; pay and collect **in store within 24 h**. Uncollected stock returns to inventory. | Chapter 1, Scope and Limitations, customer subsection (printed pp. 6–7; displayed DOCX pp. 7–8); Figure 3.0 Customer Flowchart (printed p. 37) | Persist reservation creation/deadline; allocate stock exactly once; refuse late pickup; cancel/expire and restore once. Unpaid reservation is never collected cash revenue. |
| **Pre-order** is for **out-of-stock** parts, with **no advance payment**. Owner sources; customer is notified when part arrives; 24-hour pickup period begins **after the notification**; if uncollected, pre-order is cancelled and reserved physical units return to available stock. | Scope and Limitations, Customer/Pre-Order; Figures 3.0 and 4.0 (printed pp. 37–38) | Separate pre-order sourcing (no 24 h submission countdown) from *ready-for-pickup after notification* (24 h). Record notification and deadline; exactly-once release, correct inventory accounting. |
| Compatibility checks must consider motorcycle **make, model and year**, using owner-maintained relationships between parts and motorcycle models. | Purpose and Description, Objectives, Scope; Figure 3.0; DFD process 3.0; ERD `Motorcycles` / `Part_Compatibility` | Dependent selections drawn from saved fitment records; not hard-coded brands/models and not mere product-brand text matching. |
| Owner administers inventory, damaged items/returns, real-time low-stock alerts, POS cash sales, repair/service tracking, reservations, pre-orders, and sales/stock reports. | Project Context, Specific Objectives, Scope/Shop Owner, Requirements Analysis, Figure 4.0 | Preserve real POS cash sales and after-sale physical returns/refunds even though **online** payment/refund forms are obsolete. |
| No online payment, supplier management, delivery tracking, payroll, dedicated mobile app, or full accounting functions. | Scope and Limitations/Limitations (printed p. 7) | Avoid payment gateways, delivery/supplier modules, and full accounting product scope. Sales/refund adjustment reports remain within POS/returns scope. |
| Guest may browse/check compatibility, but must register/log in before reserving or pre-ordering; customer and owner use email verification/OTP flows. | Figures 3.0/4.0 (printed pp. 37–38) | Authenticate/authorize mutations; test OTP and customer ownership; keep guest catalog/compatibility access. |

## 2. Diagram-by-diagram reading

**Figure 3.0 — Customer Flowchart** (DOCX page 38 / printed p. 37): Guest browsing and compatibility are public; registration/email verification or login/MFA unlock dashboard. Three paths: compatibility lookup -> cart; browse in-stock -> reserve (no payment) -> owner confirms -> 24-hour pickup vs cancel; out-of-stock pre-order (no payment) -> owner sources -> shop notifies -> 24-hour pickup vs cancel. Cash payment happens in shop. Figure labels also say owner updates stock during pickup; **ensure this represents finalizing an already allocated item rather than a second decrement**.

**Figure 4.0 — Owner Flowchart** (DOCX page 39 / printed p. 38): Credentials + MFA -> owner dashboard -> inventory (including damaged/returned goods), recorded POS sale, service tracker, reservation/pre-order review + sourcing + 24-hour pickup, reports, low-stock alert/restocking. Separate pre-order/ready and reservation workflows need dedicated owner UI and status tracking. Its combined 'source part (pre-order) and notify customer' step must not imply sourcing for in-stock reservations.

**Figure 5.0 — Context Diagram** (DOCX page 40 / printed p. 39): Owner sends product changes, POS records, repairs, reservation/pre-order updates; customer asks for compatibility, reserves and pre-orders; system returns compatibility, confirmations and **pre-order pickup notice**. The owner must see reservation/pre-order requests, and the customer must receive pre-order readiness communication.

**Figure 6.0 — Data Flow Diagram** (DOCX page 41 / printed p. 40): Processes 1.0 Inventory and Sales Management; 2.0 Products and Services Management; 3.0 Compatibility Checker; 4.0 Order and Pre-Order Management; **5.0 Payment**; 6.0 Back-up and Restore. The generic Payment process and arrows from Customer 'payment details' risk implying online checkout; revise to **In-Store Cash Payment / POS Transaction Recording** via the owner, with customer reservation/pre-order requests carrying no online payment. Ensure payment confirmation is generated **only after real cashier collection**. Back-up/Restore should match the actual hosting/database architecture.

**Figure 7.0 — ERD** (DOCX page 42 / printed p. 41): Owner, Repair, Customer, Inventory Items, Sales, Motorcycles, and Part_Compatibility are diagrammed. **PreOrder is absent**, and Sales does not show state, reservation/expiry timestamps, pickup, payment confirmation, transaction references or returns. This is insufficient to trace the core 24-hour workflows. Derive a **new physical ERD from actual migrations/models/foreign keys** rather than inventing columns; show `PreOrder` and related reservations/payment events as implemented. If reservations are Sales records rather than a separate table, represent that truth.

## 3. Important ambiguities and contradictions — resolve before automatic implementation

### A. Owner confirmation of in-stock reservation — DECISION REQUIRED
- **Documentation:** Customer flowchart explicitly says **'Owner confirms reservation'** between reserving an available item and pickup deadline decision.
- **Colleague feedback:** Available product is ready for pickup within 24 hours; only pre-orders need a sourcing/ready notification.
- **Practical policy to consider:** Reserving an available item instantly allocates stock and produces a reservation acknowledgement; owner reviews/acknowledges on dashboard, without silently extending the 24-hour clock. **But do not remove formal owner approval from the system or flowchart without team/adviser approval.**
- **Deliverable:** Team signs off whether confirmation is manual approval or dashboard acknowledgement, and whether the 24 hours begins at reservation creation (as current behavior suggests) or approval; update code, tests, FAQs and flowchart consistently.

### B. The pre-order 24-hour clock was missing from the earlier action plan — NOW RESOLVED
- The earlier plan said 'any after-arrival pickup deadline requires a separate policy'. **The written scope supplies that policy**: 24 hours **after customer notification of part arrival**.
- **Implementation:** stock arrival alone must not start an unseen deadline; persist a meaningful `ready_notified_at`/equivalent and `pickup_expires_at` based on a real customer notification/recorded readiness event. Prevent double-send, auto-cancel after deadline, release allocated quantity once. Reconcile timing if notification delivery is delayed/failed. Do not expire unsourced pre-orders.

### C. Customer refunds vs in-person POS refunds — NOT A CONTRADICTION IF SEPARATED
- Colleague wants customer-facing online refund buttons/policy removed and cancelled orders shown on admin side. The capstone Project Context explicitly says damaged items bought in person may be refunded, and the Objectives include tracking damaged items and product returns.
- **Implementation:** remove customer online-refund workflow for unpaid reservations; retain **owner-only cash-return/refund** tied to a previously paid POS sale. Record negative refund adjustment in net sales and stock disposition (sellable vs damaged/returned-to-supplier), prevent duplicate refunds and incorrect restock.

### D. 'No accounting functions' vs revenue and cash refunds
- Scope excludes **full accounting functionality**, but POS logs, daily revenue and reports are named scope items. Keep limited sale/refund ledger and net collection reporting; do not add full general-ledger/accounting modules.

### E. 'Only available products in catalog' — FEEDBACK-LEVEL UI POLICY
- Written Scope says product browsing displays stock and prices, and reserves available products; it does not explicitly demand hiding every out-of-stock product from all browsing. Colleague asks for a reservable-items catalog and separate out-of-stock pre-order list. Treat this as a UI/UX refinement consistent with (but not directly mandated by) the scope. Provide discoverability of unavailable parts from the pre-order page.

### F. Custom parts — FEEDBACK ADDITION / DOCUMENTATION NEEDS UPDATE
- Colleague distinguishes predefined out-of-stock parts from free-text custom part requests. Chapters 1–3 specify out-of-stock pre-orders, but not an independent custom-part form. Keep as an implementation refinement if approved; update Scope, flowchart and input-validation explanation to include this type.

### G. Data architecture discrepancy — UPDATE DOCUMENTATION, NOT DATABASE BY ASSUMPTION
- Chapter 3 says **MySQL is the primary database** and Supabase/PostgreSQL is supplementary (printed pp. 18, 21–22, 26–27). Later deployment report states the VPS uses **PostgreSQL 17.6/Supabase as active database**.
- Documentation also lists **GitHub as hosting** although previous report describes GitHub Actions + VPS deployment. Verify current `.env` (without exposing secrets), Laravel connection default, and workflow. Update Technologies, Resources, Methodology and diagram labels to reflect **actual deployed roles**: Laravel 13, PostgreSQL/Supabase production, local MySQL only if genuinely used, GitHub source control/CI, VPS hosting.

## 4. Colleague feedback mapped to documentation

| User/admin PDF request | In written scope/diagrams? | Recommendation |
|---|---|---|
| Customer and owner Compatibility links, dropdown make/model/year | Yes — checker for both, make/model/year explicit | **Implement**, use actual fitment data. |
| Only in-stock items reservable; out-of-stock items pre-orderable | Yes — lifecycle explicit | **Implement** server-side separation; visual split from colleague. |
| Pre-order linked product form errors; custom-part field rules | Pre-order in scope; custom form not explicit | **Fix bug**, document custom request refinement. |
| Pre-order customer notification on item arrival | **Yes**, explicitly in scope & Figures 3/5 | **Required**; start post-notification 24-hour clock. |
| Expiry notifications to both owner and customer | Expiry and return-to-stock in scope; dual notices specifically colleague | **Implement** as added notification requirement; exactly once. |
| Reservation admin dashboard with statuses | Yes, owner must manage reservations | **Implement** with existing backend states, not fabricated statuses. |
| POS physical refunds, red negative ledger, damaged returns | Yes, Context/Objectives; display choice from colleague | **Preserve/build safely**; remove only obsolete online refunds. |
| Cash tender limit, meaningful validation | POS in scope | **Fix**, guard unrealistic amounts and overflows. |
| Unpaid reservation acknowledgement rather than paid receipt | Offline payment stated; receipt terminology from colleague | **Fix**; no false amount paid, not an official receipt. |
| Fix UI, sticky filters/sidebar, responsiveness | Browser/mobile access in Requirements; styling choices from colleague | **Implement**, test mobile and keyboard/scroll. |
| Remove 'lily bogs' and demo strings | Professional quality feedback | **Clean** visible placeholders without deleting real/synthetic transactional records blindly. |
| Display requested repair service in tracker | Repair and service tracking scope | **Implement** to make work description visible. |
| Real-time stock/notifications | Objective explicitly real-time stock/alerts | **Verify** push/websocket or truthful polling fallback; never claim unverified live. |

## 5. Proposed post-alignment state-machine tests

**Reservation**: `new -> pending/allocated -> collected+paid` OR `pending -> cancelled/expired -> stock released once`. Server enforces 24h deadline regardless of scheduler latency. Ensure a reservation already decremented/allocated does not lose stock again at pickup.

**Pre-order**: `requested -> sourcing -> arrived/allocated -> customer-ready-notification -> deadline +24h -> collected+paid` OR `ready -> expired/cancelled -> stock released once`. Prior to actual customer-ready notice there is **no 24-hour pickup timeout**. Implement states by mapping existing `status`, `is_stocked`, `is_paid`, `picked_up_at`, etc.; new schema only after proving needed.

**Cash returns**: `completed/paid in-store transaction -> authorized refund/return -> negative sales entry`, with clear stock disposition. A cancelled unpaid reservation is **not** a cash refund.

**Unknown until latest repo is inspected:** Whether each state and alert actually works, precise database columns, presence of notifications, global OTP enforcement, filter responsiveness, and deployment readiness. Previous audit reports are not substitutes for current code/tests.

## 6. Documentation corrections to propose for Chapters 1–3 and Appendix B

1. **Scope — Reservation:** explain exactly when 24-hour clock begins and what owner confirmation means; clarify server-enforced automatic expiration and no online payment.
2. **Scope — Pre-order:** keep explicit **24h from readiness/arrival notification**, pre-order owner sourcing, customer notification and stock return; distinguish catalog out-of-stock vs approved custom requests if supported.
3. **Customer/Owner Flowcharts:** separate in-stock reservation vs out-of-stock pre-order; show notifications and two distinct clocks; correct POS stock finalization to avoid double deduction; add any approved custom request branch.
4. **DFD:** rename Payment 5.0 to cashier-only POS cash collection and post-sale refund adjustment; remove any implication customer online payment; reflect real persistent entities/data flows and backup if present.
5. **ERD:** derive from actual schema: customers/users, inventory, sale/reservation records, pre-orders, compatibility/motorcycles, repairs, payment/return records as implemented; include relevant foreign keys and status/timestamp fields.
6. **Technical Background:** reconcile PostgreSQL vs MySQL and GitHub vs VPS (including use of Laragon/XAMPP) with confirmed implementation; no speculative database migration.
7. **Test/Deployment Methodology:** accurately differentiate work already completed from planned testing and real environment verification.
8. **Objective mapping:** keep the six stated specific objectives intact unless adviser approves revisions; show reservations/pre-orders/catalog as supporting functions under Inventory/POS/operations even if listed elsewhere as a seventh feature.

**Decision gate:** Have the team/adviser approve the single unresolved reservation owner-confirmation rule before changing that specific flow. The pre-order 24-hour post-notification deadline is already documented and **must not be deferred as undecided**.

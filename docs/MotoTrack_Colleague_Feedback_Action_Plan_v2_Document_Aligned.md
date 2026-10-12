# MotoTrack — Colleague Feedback Implementation Plan v2 (Chapter 1–3 Aligned)

**Inputs**
- `CHAPTER 1-3 (revisions).docx` (approved/project revision text and Appendix B diagrams)
- `system-user.pdf` (7 annotated customer pages)
- `system-admin.pdf` (4 annotated admin pages)
- `MotoTrack_Chapter1-3_Flowchart_Gap_Analysis.md` (alignment analysis)

**Project:** Existing Laravel 13 MotoTrack; PostgreSQL/Supabase VPS deployment reported, but verify actual repository/environment.  
**Status:** Requirements and implementation plan; no repository changes or test execution performed in this deliverable.  
**Critical correction from v1:** **Pre-orders DO have a 24-hour pickup deadline, starting only AFTER the customer is notified the sourced part has arrived.**

## A. Non-negotiable business rules

1. **Reservation — available inventory**: customer can reserve available stock only, with **no payment online**. Allocate quantity atomically. Customer pays cash and picks up within 24 hours of the approved reservation start event (currently intended creation; see decision gate). Expired/cancelled reservations return stock **exactly once**. Pending unpaid amount is never revenue.
2. **Pre-order — unavailable items**: owner sources item, **no advance payment**. Sourcing can take longer than 24h. Upon confirmed arrival/stock allocation the shop **notifies customer**, then **24-hour pickup clock begins from notification**. If not picked up: cancel/expire pre-order, release its allocated stock once. Cash received in store at pickup and recorded once. Reject payment/completion in invalid states.
3. **Compatibility**: validate real make/model/**year** against owner-managed part fitment records; guest lookup allowed. Product brands and motorcycle makes aren't equivalent.
4. **Physical POS and after-sale returns**: retain staff-entered cash payments, correct change and revenue reports. Allow authorized in-person cash returns of actually paid sales, signed as negative entries, with damaged/sellable item disposition. Do not expose online refund request forms to customers for unpaid reservations.
5. **Authentication**: enforce OTP where required, authorization, customer ownership, least privilege, and protected admin routes. Maintain transaction locking/idempotency for all inventory mutations.
6. **Documentation alignment**: do not invent features outside documented scope without noting need for adviser approval (e.g., arbitrary supplier management/full accounting). Do not silently change owner reservation approval semantics.

## B. Single business-policy decision gate

**D-01 — What does 'Owner confirms reservation' mean?** The Customer Flowchart, Figure 3.0 (printed p. 37), explicitly places it before pickup. Customer feedback expects available stock to be ready immediately. Before changing server-side state transitions, record group/adviser decision: (a) owner acknowledgement of an instantly valid reservation, or (b) required manual owner approval. Also document whether 24h counts from creation vs approval. Until approved, do not silently reinterpret the flowchart or alter when stock is allocated. In every option, never allow two customers to reserve the same stock.

## C. Prioritized change requests with completion criteria

### P0 — Functional correctness / core demo blockers

**P0-01 — Pre-order catalog form** (User PDF p.3): Reproduce `part_name` required error for unavailable catalog item. Derive product part name, SKU/model/price and identity server-side from selected catalog ID. Custom-part form requires manually entered fields. Reject submitting in-stock catalog item as out-of-stock pre-order, forged price, missing CAPTCHA/terms, invalid stock status. **Accept:** both valid paths submit; errors explain actual missing fields; no unintended stock/payment mutations.

**P0-02 — Reservation 24-hour expiry** (User PDF p.5; Chapter 1 Scope): Ensure clock and stock allocation follow approved rule; scheduler `reservations:expire` plus server-side deadline guard. Release once with row locks, transaction and idempotent state changes. Notify both customer and owner at expiry after commit, deduplicate queued notifications. **Accept:** time travel + retry/concurrent expiry cases pass, amount paid stays zero, notification generated once per recipient.

**P0-03 — Pre-order arrival notification / separate 24-hour expiry** (Chapter 1 Scope; Customer Flowchart Fig. 3.0; User PDF pp.1,3,5): Record owner receiving / allocating real units, mark eligible pre-order ready, issue recorded customer arrival/ready notice, **start 24-hour deadline only when notification is recorded/sent**, and expire uncollected ready pre-orders. Handle failed/delayed delivery honestly; do not let stocked pre-orders be invisible or expire before notification. Restore allocation exactly once; don't fabricate inventory for uncatalogued custom parts. **Accept:** a pre-order sitting in sourcing for 48h doesn't expire; 24h after actual recorded readiness notice it expires/restocks once; successful pickup/payment closes it without restock; user sees deadline/status.

**P0-04 — POS cash tender bound & precision** (Admin PDF p.3): Validate cash amount finite, nonnegative, appropriate 2-decimal scale, >= total, and <= configurable cap consistent with DB schema. Reject absurdly large values, scientific notation, malformed values, total-zero invalid actions and duplicate processing. Calculate change accurately; no JS-only enforcement. **Accept:** gigantic string and overflow fail; normal cash/overpayment succeeds and is counted once.

**P0-05 — Cash refund / cancellation separation** (Admin PDF p.2; User PDF p.6; Chapter 1 Context): Disable obsolete customer online monetary refund request flow; list cancellation/expiration under admin Cancelled Orders; retain admin-only POS physical returns for damaged products and valid post-sale cash refund. Record refund with original sale reference, reason/amount/quantities, user/date, signed negative collection and audit trail, proper inventory disposition. **Accept:** unpaid cancelled reservation has no negative cash entry; one paid sale refund reduces net sales once; damaged item is not erroneously available.

### P1 — Customer experience (all major visible feedback)

**P1-01** (User PDF p.1) Pre-order page shares main font/palette/card/button conventions; compatibility checker link in public/customer navbar, with valid route.

**P1-02** (User PDF pp.1,6) Rewrite FAQs, numbering and terms to distinguish available **reservation** (24h from confirmed reservation start, no sourcing) from **pre-order** (owner sources, readiness notice THEN 24h). 'When do I pay for a reservation or pre-order?' — at store pickup. No 'remaining balance', online checkout/refund promises. Checkout terms Section 5 becomes Cancellation; keep separate staff in-store damaged-item return policy if customer information requires it.

**P1-03** (User PDF p.2) Public product catalog shows **reservable in-stock SKU/variation**; out-of-stock catalog items discoverable on pre-order page. Paginate/query server-side; server also enforces cart/pre-order eligibility.

**P1-04** (User PDF p.2) Desktop category/compatibility filter remains visible within scroll constraints; product list scrolls without trapped scrolling; mobile filters collapse into accessible panel/drawer rather than overlay permanently. Avoid infinite page scroll for short admin overview, no horizontal clipping.

**P1-05** (User PDF p.2; Chapter 1 Scope) Compatibility make -> model -> year dependent dropdowns from real persisted compatibility table; optional part/category. Don't mistake stock visibility for fitment or assume cross-brand interchangeability without recorded mapping. Accessible keyboard and empty states.

**P1-06** (User PDF pp.3–5) Reservation confirmation document titled 'Reservation Acknowledgement' or similar; show no cash collected and **Not an Official Receipt**. For paid POS receipts, don't automatically label as unofficial (legal handling differs). Remove redundant catalog cart button, obsolete checkout payment-method panel and misleading total-reservation-amount emphasis; preserve unit pricing as estimate if appropriate. Maximize checkout form width on mobile.

**P1-07** (User PDF p.5) User dashboard displays status/deadlines clearly: pending reservation, completed pickup, cancelled/expired; sourcing pre-order, ready-notified, completed, expired/cancelled. Match backend actual enum/fields and avoid claiming payment received until in-store collection.

### P1 — Owner/admin experience

**P1-08** (Admin PDF pp.1–2) Add Owner/Compatibility Checker nav link; make left sidebar sticky/fixed on desktop with correct content scroll and mobile overlay; remove excess overview scroll when content fits, without locking small-screen content.

**P1-09** (Admin PDF p.2; Figure 4.0) Owner **Reservations** list below Pre-orders: show reservation reference, customer, items/qty, date/deadline, unpaid status, valid ready/pending/expired/cancelled/fulfilled states; authorised completion, cancellation, payment. Do not invent statuses unsupported by migrations; first map existing source.

**P1-10** (Admin PDF p.2) Replace obsolete `Refund Requests` navigation with `Cancelled Orders` (including unpaid cancelled/expired reservations). Add separate owner POS Returns/Refund transaction entry with red signed negative sale/collection, zero duplicates. Keep historic records intact.

**P1-11** (Admin PDF p.3; Objective on repair tracking) Repairs tracker shows the actual issue/work requested and bike model on list cards, without opening 'Manage All'. Preserve private details/role restrictions.

**P1-12** (Admin PDF p.4) Remove 'lily bogs' and inappropriate demo copy from templates/fixtures/public labels. Do not mass-delete database data or alter existing transactions. Ensure professional business name consistently.

### P2 — All-page quality/security

**P2-01** Visual regression: audit desktop 1440/1024 and mobile 390/360 px, tablet 768px; nav, filters, sticky positioning, modals, scrolling, OTP, checkout, owner sidebar, dashboards; dark/light themes if supported.

**P2-02** Authorization and integrity: all customer and owner routes, CSRF, checkout reservation ownership, XSS-safe output, inputs, POS refund permissions, image paths, file uploads, denial responses, OTP bypass tests. Preserve role separation.

**P2-03** Realtime data: verify stock update/low-stock notifications for owner across sessions. Check actual event/broadcast/queue infrastructure or polling; provide truthful refresh fallback. Test cancellation/arrival/expiration notices and de-duplication.

**P2-04** Performance: eager-load actual Blade-used relations, paginate owner reservations/refunds/catalog, server-side search for large SKUs, avoid transmitting full inventory to Alpine. Benchmark only real queries and prioritize measurable issues.

**P2-05** Update system documentation/diagrams only through separately reviewable proposed changes: ERD preorders/status/notification events; DFD cashier-only payment; Figure 3.0 owner-confirmation decision; technical background PostgreSQL/Supabase primary VPS vs old MySQL description (verify actual stack first). Do **not** silently overwrite thesis chapters or claim adviser approval.

## D. Required testing and deliverables

- Baseline `php artisan --version`, `php artisan test`, `php artisan route:list`, `php artisan migrate:status`, `npm run build` (record real outputs).
- Reservation: duplicate cart IDs, last-unit contention on production-equivalent PostgreSQL, unpaid revenue exclusion, cancellation/expiration race, scheduler re-run, late pickup denied, both-party expired alert exactly once.
- Pre-order: catalog-linked vs custom, sourcing >24h no premature expiry, notification timestamp starts ready 24h, stock allocation once, cancelled ready stock restored once, paid completion only with physical arrival; late pickup rejected.
- POS: giant cash input rejection, cents precision, underpayment, overpayment/change, double-click payment, one legitimate physical refund, net reporting and damaged-stock disposition.
- Fitment: make/model/year returned from actual database, no false positive compatibility, cross-brand rows only if explicitly recorded; role/ownership access.
- All screenshots: responsive desktop/tablet/mobile, sticky admin sidebar, filtering, modal, navbar, repairs tracker, checkout ack text and appearance.
- Notifications: real delivery/queue or in-app record, retry deduplication; no unproven live/realtime claim.
- Migration safety: isolated PostgreSQL equivalent, no production modifications; no `git push` (main auto-deploys).

### Progress files requested from Antigravity
- `MOTOTRACK_FEEDBACK_IMPLEMENTATION_PROGRESS.md` — IDs, files modified, test commands & results, blockers, decisions.
- `MOTOTRACK_FEEDBACK_IMPLEMENTATION_FINAL.md` — final evidence-based DONE / PARTIAL / NOT DONE / NOT TESTED checklist, screenshots, remaining risks, requested diagram updates, staging GO/NO-GO.
- `MOTOTRACK_DOCUMENTATION_ALIGNMENT_PROPOSALS.md` — numbered, non-destructive proposed chapter/figure edits; distinguish actual current code vs old documentation.

**Success criterion:** Both user/admin feedback and approved documented scope work consistently without online payment, while **pre-order pickup expiration runs 24 hours from recorded arrival notification rather than submission.**

# MotoTrack Documentation Alignment Proposals: Chapters 1–3 & Appendix B Diagrams

**Date:** 12 October 2026  
**Document Target:** Capstone Chapters 1–3 and Appendix B (Figures 3.0, 4.0, 5.0, 6.0, 7.0)  
**Status:** Ready for Capstone Adviser and Panel Review  

---

## 1. Executive Summary & Objective

During the implementation of colleague feedback and technical audit of the MotoTrack system, several key workflows were standardized and made transaction-safe in accordance with **Decision D-01** and the approved project scope. To ensure complete alignment between the written capstone manuscript, system diagrams, and the working implementation, this document details the exact alignment proposals for the text of Chapters 1–3 and Appendix B figures.

---

## 2. Text Alignment Proposals (Chapters 1–3)

### 2.1 Scope and Limitations (Section 1.4)
* **Current Text Note:** Mentioned online orders and refunds generally without distinguishing reservation checkout from payment collection.
* **Proposed Revision:**
  > "The MotoTrack customer portal allows verified customers to browse in-stock parts and reserve items online. Reserving an item atomically holds catalog inventory and initiates a 24-hour pickup countdown. No online financial transactions or electronic payments are handled by the system. All payments are strictly collected in cash or store-supported payment methods physically at the Edmar Motorcycle Parts counter. Failure to claim and pay for a reservation within 24 hours automatically releases the reserved stock back into inventory.
  > 
  > For parts not currently in stock, customers may submit a pre-order request. Pre-orders do not reserve existing catalog stock until the owner marks the item as physically arrived and stocked. Once marked as stocked, a 24-hour pickup countdown begins and a durable readiness notification is sent to the customer.
  > 
  > Returns and refunds are handled exclusively in person at the store counter. Cancelled online reservations release stock without financial transaction, while physical returns on completed sales generate dedicated, auditable return records distinguishing resellable inventory restocks from damaged item write-offs."

### 2.2 System Architecture & In-Stock Allocation (Section 2.4 & 3.2)
* **Alignment with Decision D-01:**
  - In-stock reservations execute immediate atomic stock decrementation upon customer checkout.
  - The 24-hour pickup countdown begins immediately upon reservation creation.
  - Owner acknowledgement is informational and does not delay, reset, or extend the deadline.
  - Scheduled background routine (`preorders:expire` and `reservations:expire`) runs every minute with overlapping-run protection (`withoutOverlapping()`).

---

## 3. Appendix B Diagram Alignment Proposals

### 3.1 Figure 3.0: Customer Flowchart (Printed p. 37)
* **Current Diagram Flow:** Shows Customer -> Add to Cart -> Checkout -> Wait for Owner Approval -> Pay -> Receive Item.
* **Proposed Aligned Flow:**
  1. **Customer Authentication & Verification:** Log in via OTP or Google OAuth.
  2. **Browse & Filter:** Search catalog by Make, Model, Year, or Category.
  3. **Stock Availability Decision:**
     - *If Item in Stock:* Add to Cart -> Online Checkout -> **Immediate Stock Allocation & 24h Pickup Countdown Started** -> Receive Reservation Acknowledgement (Unpaid).
     - *If Item Out of Stock / Custom:* Route to Pre-Order Form -> Submit Pre-Order Request -> Wait for Part Arrival.
  4. **In-Store Arrival / Counter Interaction:**
     - Present Reservation Acknowledgement or Pre-Order Notice at counter within 24 hours.
     - Cashier verifies reservation in POS, receives cash payment, issues Official Sales Receipt, and hands over goods.
  5. **Auto-Expiration Exception Branch:**
     - If customer does not claim within 24 hours -> Automated Expiry Daemon cancels reservation and restores catalog stock.

### 3.2 Figure 4.0: Owner / Staff Flowchart (Printed p. 38)
* **Current Diagram Flow:** Shows Owner reviewing pending orders before stock changes.
* **Proposed Aligned Flow:**
  1. **Dashboard Operation Center:**
     - Live overview of low stock alerts, active repairs, daily revenue, and customer reservations.
  2. **Customer Reservations Management:**
     - Real-time display of allocated reservations with active 24h pickup countdowns.
     - Informational notifications received upon new reservations.
     - Fast POS checkout button to convert reservation to completed paid sale upon customer arrival.
  3. **Pre-Order Arrival & Stocking:**
     - Physical inventory arrives from supplier -> Owner selects pre-order -> Marks as Stocked.
     - Atomic allocation from inventory (or logged as custom arrival) -> Starts customer 24h pickup deadline -> Dispatches durable notification.
  4. **Physical Returns & POS Adjustments:**
     - Customer brings item to counter -> Owner looks up completed sale reference (`sale_id`).
     - Selects condition:
       - *Resellable:* Restocks catalog inventory -> Records `ProductReturn` with refund amount.
       - *Damaged / Defective:* Writes off to `DamagedItem` audit log -> Records `ProductReturn` -> Deducts from net sales.

### 3.3 Figure 5.0: Context Diagram (Printed p. 39)
* **Approved External Entities:**
  - **Customer:** Authenticates, checks part compatibility by make/model/year, submits in-stock reservations (unpaid), requests out-of-stock pre-orders, and tracks repair progress.
  - **Store Owner / Cashier:** Manages inventory, logs walk-in POS cash transactions, records counter returns/refunds, updates repair job statuses, and marks arrived pre-orders as stocked.
  - **System Scheduler / Clock:** Minute-by-minute system daemon triggering automatic expiration for unclaimed reservations and pre-orders (`reservations:expire`, `preorders:expire`).
* **Boundary Clarification (No Invented Entities):**
  - **Do NOT add "Payment Gateway" entity:** MotoTrack does not integrate third-party payment gateways (e.g., PayMongo, Stripe, GCash API, PayPal). All monetary transactions occur physically in cash at the store counter.
  - **Do NOT add "Supplier Logistics API" entity:** MotoTrack does not integrate courier or supplier EDI systems (e.g., Lalamove, J&T). Sourcing is manually managed by the shop owner.

### 3.4 Figure 6.0: Data Flow Diagram (DFD Level 0 / Level 1, Printed p. 40)
The DFD proposals preserve the original six processes established in Chapter 3:
* **Process 1.0: Inventory and Sales Management:**
  - Queries and maintains physical item stock in `inventory_items`.
  - Logs damaged item write-offs in `damaged_items` and audits restocked returns.
  - Decrements stock atomically upon reservation checkout; restores stock upon automatic expiration or counter return.
* **Process 2.0: Products and Services Management:**
  - Administers catalog listings and categories.
  - Tracks repair job orders (`repairs`), recording customer requested issues, motorcycle model, and job progression (`pending` -> `ongoing` -> `completed`).
* **Process 3.0: Compatibility Checker (Preserves Objective 2):**
  - Validates part fitment by querying `motorcycles`, `inventory_items`, and the `part_compatibility` junction table.
  - Filters verified options strictly by Make, Model, and Year.
* **Process 4.0: Order and Pre-Order Management:**
  - In-stock reservations: Holds inventory atomically, sets 24-hour pickup deadline, generates Unpaid Reservation Acknowledgement.
  - Pre-orders: Logs customer sourcing requests, tracks arrival, records durable customer readiness notification, and activates 24-hour collection deadline.
* **Process 5.0: In-Store Cash Payment & POS Transaction Recording:**
  - Collects physical cash tender at cashier counter (bounded <= ₱999,999.00).
  - Stamps `payment_confirmed_at`, `picked_up_at`, calculates change, and generates POS Sales Receipt.
  - Records after-sale physical returns in `product_returns` linked to original `sales.id`, deducting refunds from net sales.
* **Process 6.0: Back-up and Restore:**
  - Maintains persistent operational state, transaction audit trails, and automated database backups on the production database.

### 3.5 Figure 7.0: Entity Relationship Diagram (ERD, Printed p. 41)
* **Updated Schema Relationships:**
  - `sales` (1) ──── (0..*) `product_returns` (via `sale_id`, with `refund_amount`, `action_taken`, `notes`).
  - `pre_orders` (attributes: `ready_notified_at`, `pickup_expires_at`, `is_stocked`, `inventory_item_id` nullable).
  - `inventory_items` (1) ──── (0..*) `damaged_items` (via `inventory_item_id`, recording write-offs).
  - `motorcycles` (1..*) ──── (0..*) `inventory_items` (via `part_compatibility` junction table).

### 3.6 Preservation of Approved Specific Objectives (Chapter 1, Section 1.3)
All six original Specific Objectives are preserved intact without alteration:
1. **Objective 1:** Implement real-time inventory monitoring and automated low-stock alerting.
2. **Objective 2 (Preserved):** Provide a comprehensive parts catalog and automated compatibility verification mechanism based on motorcycle make, model, and year.
3. **Objective 3:** Enable customer online reservations for in-stock parts with automated 24-hour inventory allocation and cancellation handling.
4. **Objective 4:** Provide a pre-order request mechanism for unavailable or specialized components with arrival readiness notifications.
5. **Objective 5:** Facilitate walk-in point-of-sale (POS) cash transaction processing, tender calculation, and receipt generation.
6. **Objective 6:** Track motorcycle repair and service job orders from intake to completion with transparent status updates.

---

## 4. Summary of System Consistency

| Requirement Area | Manuscript Statement | Actual System Implementation | Status |
|---|---|---|---|
| **Payment Collection** | Cash-only counter pickup | In-stock reservation generates unpaid acknowledgement; cash tendered at POS | **ALIGNED** |
| **Reservation Clock** | 24-hour expiration window | Atomic allocation; countdown starts at creation; daemon runs every minute | **ALIGNED** |
| **Pre-Order Pickup** | 24 hours from arrival | Countdown begins strictly on physical stocking + durable customer notification | **ALIGNED** |
| **Counter Returns** | Auditable return tracking | Dedicated `ProductReturn` record linked to `sale_id`, separating restock vs damaged | **ALIGNED** |
| **Compatibility** | Make, Model, Year validation | Verified options derived from `motorcycles` and `part_compatibility` DB tables | **ALIGNED** |
| **Scope Boundaries** | No online payment / accounting | Pure POS, reservation, repair tracking, and inventory adjustments | **ALIGNED** |

# MotoTrack — Diagram Alignment Report

Method: each figure was extracted from the docx media and **visually inspected**; compared with routes, controllers, services, migrations and models. No figure was modified. Evidence type: code inspection (CI) unless noted.
Figure → media mapping: Fig 1.0 = image1.png, Fig 3.0 = image11.png, Fig 4.0 = image12.png, Fig 5.0 = image4.png, Fig 6.0 = image6.png, Fig 7.0 = image2.png. Fig 2.0 (Agile, image3.jpg), Table 1.0 (image7.png, benchmarking) and Table 2.0 (image5.png, Gantt) were viewed only to identify them; not assessed here.

## Summary

| Figure | Verdict | Fix size |
|---|---|---|
| 1.0 IPO | PARTIAL | Minor edit |
| 3.0 Customer flowchart | PARTIAL (2 mismatches, 2 gaps) | Minor edit |
| 4.0 Owner flowchart | PARTIAL | Minor edit |
| 5.0 Context diagram | PARTIAL | Minor edit |
| 6.0 DFD | **MISMATCH** (Payment, Backup/Restore, store labels) | **Redraw** |
| 7.0 ERD | **MISMATCH** (many tables/columns) | **Redraw** |

## Fig 1.0 IPO (Appendix/Technical Background)
| Item | Wrong/missing | Actual | Recommended | Fix | Evidence |
|---|---|---|---|---|---|
| Software list | "MySQL", "XAMPP" listed; Supabase/PostgreSQL listed as peers | Production = Supabase PostgreSQL; tests = SQLite; local dev DB per `.env` | Reword to "PostgreSQL (Supabase)"; keep MySQL/XAMPP only if still used locally (team to confirm) | Minor | CI, project facts |
| Missing | Alpine.js, Tailwind, Vite, Pest/PHPUnit, GitHub Actions, Ubuntu VPS | Present in repo/workflow | Add under Software Requirements | Minor | CI |
| Process | Agile phases | Consistent | — | — | ALIGNED |
| Output | "MOTOTRACK…" | Consistent | — | — | ALIGNED |

## Fig 3.0 Customer flowchart
| Element | Issue | Actual behaviour | Recommended | Fix | Evidence |
|---|---|---|---|---|---|
| "Owner confirms reservation" | **No such step** | Reservation is active immediately; owner records pickup later | Delete box (or label "Owner records pickup") | Minor | CI (`CommitOrder`, no confirm state) |
| "Reservation cancelled; item returns to stock" on 24h "No" | Automatic expiry is **Expired**, voluntary is **Cancelled** | `SALE_EXPIRED` | Relabel "Reservation expired; item returns to stock" and add a separate "Customer cancels" path | Minor | CI, `OrderStatus` |
| "Pre-order cancelled; item returns to stock" | Pre-order custom parts create no stock; automatic = Expired | `PREORDER_EXPIRED` | Relabel "Pre-order expired" (stock returns only where stock was allocated) | Minor | CI |
| Missing customer-initiated cancel | Voluntary cancel (reservation & pre-order) is a core UAT feature | Cancel buttons on dashboard; stock restored once; owner notified | Add decision "Customer cancels?" → "Cancelled; stock restored; owner notified" | Minor | CI, AUTO, CB |
| "Shop notifies customer…" | Channel unspecified | In-app | Label "(in-app notification)" pending adviser decision | Minor | CI |
| 24h clock for pre-order | Starts after notification | Correct as drawn | — | — | ALIGNED |
| "Add part to cart" from compatibility | Compatibility results link to cart | Plausible; not re-verified route-by-route | — | — | NOT VERIFIED |
| Sign-up/verify/OTP/login/dashboard, guest browsing, "login required to reserve" | | Match routes and tests | — | — | ALIGNED |
| Missing: no-stock gating, pre-order for custom (non-inventory) parts | | `custom.blade.php` | Optional note | Minor | CI |

## Fig 4.0 Owner flowchart
| Element | Issue | Actual | Recommended | Fix | Evidence |
|---|---|---|---|---|---|
| Report branch "Select report type (sales, stock, movement)" | No selector; dashboard summaries + sales history only | Reports tab | Relabel "View dashboard reports / sales history" | Minor | CI |
| "Low-stock alert → System detects → Owner reviews → Stock restored" | Draws alert as a *menu task*; it is automatic/background | Alert is automatic and appears on dashboard | Show as event feeding the dashboard, not a "Select task" branch | Minor | CI |
| Reservation/pre-order branch merges both | Reservation has no sourcing; "Source part and notify" is pre-order only; cancel/expire paths missing | | Split into Reservation (record pickup / expire / cancel) and Pre-order (source, mark arrived, notify, pickup / expire / cancel) | Minor→moderate | CI |
| Missing: refunds/returns, audit logs, compatibility records management, damaged items (partly in "Manage inventory") | Features exist | `RefundController`, `AdminCompatibilityController`, `AuditLogController` | Add branches or note out-of-scope | Minor | CI |
| Repair branch | Log → status → closed | Plus payment and receipt | Optional | — | ALIGNED |
| Login/OTP | Matches | — | — | ALIGNED |

## Fig 5.0 Context diagram
| Flow | Issue | Recommended | Fix |
|---|---|---|---|
| "Reservation Confirmation", "Pre-Order Confirmation and Pick-up Notice" | Channel unspecified; "confirmation" implies owner approval for reservations | Rename "Reservation Receipt/Status" and "Pre-order Ready Notice (in-app)" | Minor |
| Missing: Customer cancel request → system; Cancellation/Expiry notice → Customer and Owner | Implemented | Add flows | Minor |
| Missing: Owner "Refund/Return decisions", "Compatibility records" | Implemented | Add or keep as covered by "Add/Update Stock Info" | Minor |
| Missing: Authentication/OTP email | Email service is an external dependency (SMTP) | Consider an external entity "Email service" (and "SMS gateway" if low-stock SMS stays in scope) | Minor |
| No Payment gateway / Supplier entity | Correct (none exist) | — | ALIGNED |

## Fig 6.0 DFD (Level 1)
| Element | Issue | Actual | Recommended | Fix | Evidence |
|---|---|---|---|---|---|
| **5.0 Payment** process, "Payment details", "Receipt / payment confirmation", "Order total / payment request" | **Online/payment process does not exist**; cash only at shop | Cash recorded by owner at POS; `amount_tendered`, `change` | Remove 5.0; fold "Record cash payment" into Inventory & Sales (1.0) | **Redraw** | CI, Ch1 Limitations |
| **6.0 Back-up and Restore** + D5 Back-up Database | **No backup/restore feature in the application** | Backups, if any, are Supabase/VPS operational tasks | Remove from application DFD or footnote as infrastructure | **Redraw** | CI (no routes/code) |
| D1 Accounts, D2 Products & Services, D3 Compatibility, D4 Pre-Order DB | Real store set differs: `users`/`customers`, `inventory_items`, `part_compatibility`/`motorcycles`, `sales`, `pre_orders`, `repairs`, `notifications`, `damaged_items`, `product_returns`, `refund_requests`, `audit_logs` | | Re-partition datastores to actual tables; add Sales/Reservations store (reservations live in `sales`) | Redraw | CI |
| "Orders and Pre-order Management (4.0)" | Reservations are `sales` rows with status `pending`; pre-orders in `pre_orders` | | Show both flows; add expiry process (scheduler) | Redraw | CI |
| Missing processes | Repairs, Reports, Notifications, Refunds/Returns, Audit, Scheduled expiry | | Add Repair (7.0), Reports, Notification, Expiry | Redraw | CI |
| Login/Sign-up 0.0 → D1 | Present | — | ALIGNED |
| Compatibility 3.0 ↔ D3 | Present | — | ALIGNED |
| Numbering "0.0" | Non-standard | Number 1.0..n | Minor | |

## Fig 7.0 ERD
Verified against migrations/models (columns grepped from migrations; not all models re-read).
| Entity (figure) | Issue | Actual | Fix |
|---|---|---|---|
| **Owner** (`owner_id`) | No `owners` table | `users` with `role` (owner/customer), `otp_code`, `otp_expires_at`, `google_id`, security columns | Rename to Users |
| **Customer** | Partly right | `customers`: `user_id` (unique), `name`, `phone` (not `phone_number`), `email`, `address` | Fix names; link to users |
| **Inventory Items** | Missing columns | `sku`, `low_stock_threshold`, `low_stock_notified`, `image_path`, `parent_id`, `variation_name`, timestamps; PK `id` | Add |
| **Motorcycles** | `make`, `year NOT NULL (year)` | `brand`, `model`, `year` **string** (single or range), `engine_type` | Fix |
| **Part_Compatibility** | `compatibility_id` PK; ok | pivot `inventory_item_id`, `motorcycle_id`, `notes` | Verify PK name |
| **Sales** | One item per row, `owner_id`, `customer_id` | Adds `transaction_reference` (groups multi-item), `status` (`pending` = active reservation, `completed`, `cancelled`, `expired`, `refund_requested`, `refunded`), `reserved_at`, `expires_at`, `payment_confirmed_at`, `picked_up_at`, `cancellation_reason`, `amount_tendered`, `change`, soft delete; `inventory_item_id` nullable | Add |
| **Repair** | Fields `customer_name, bike_model, issue, status, cost, is_paid` ok | Matches migrations | ALIGNED (verify `user_id`) |
| **Missing entities** | | `pre_orders` (incl. `is_stocked`, `ready_notified_at`, `pickup_expires_at`, `picked_up_at`, `inventory_item_id`), `notifications`, `damaged_items`, `product_returns`, `refund_requests`, `audit_logs`, `sessions`, `jobs` (infrastructure) | Add domain tables (pre_orders, notifications, damaged_items, product_returns, refund_requests, audit_logs) |
| Types | `tinyint(1)`, `year` MySQL-specific | Production PostgreSQL uses boolean/varchar | Note dialect or state "logical ERD" |

**Fix size:** Fig 6.0 and 7.0 need redraw (needs approval). Others are minor edits.

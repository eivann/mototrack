# MotoTrack — Pre-Deployment Targeted Findings and Decision Matrix

Date: 2026-10-16 · **No application code, manuscript, diagram, PDF, repo history or production data was changed.** One throw-away probe test was run only in the cloud working copy (synthetic data, SQLite) and deleted afterwards. Evidence tags: CI = code inspection, AUTO = automated probe (SQLite), TXT = manuscript text search.

---
## 1. Paid-sale deletion — `DELETE /sales/{id}`

### Confirmed facts
| Question | Finding | Evidence |
|---|---|---|
| Who can call it? | Owner only (`auth` + `owner` middleware). A customer gets **403**. | CI; AUTO (customer → 403) |
| Is there a UI button? | **No reference found** in `resources/views` (history, receipt, dashboard). Reachable only by a crafted request / old page / script. | CI (grep) |
| Does it check status? | **No.** `destroy()` does `Sale::lockForUpdate()->findOrFail($id)->delete()`. `SalePolicy::delete` is "owner" only and is **not called** by the controller. | CI |
| Can paid/completed/refunded be deleted? | **Yes — all five statuses probed returned 302 and were soft-deleted:** completed, refunded, refund_requested, pending, cancelled. | AUTO |
| Effect on financial reporting | Revenue, dashboard totals and history use default Eloquent queries, which exclude soft-deleted rows. In the probe, collected revenue went **100 → 0** after deleting one completed sale. Repair payments are also stored as `sales` rows (`REPAIR-…`, `inventory_item_id` null), so repair revenue can be erased the same way. | AUTO; CI (`DashboardController`, `SaleController::history`, `RepairController::markPaid`) |
| Effect on inventory | For a **pending reservation** the row is hidden but stock stays decremented: `reservations:expire` and `ReservationService` read non-deleted rows, so the stock **is never restored** (permanent stock leak) and a multi-item reservation is split (only one line archived). Completed-sale deletion does not change stock (by design per code comment) but erases the record that refund/return logic relies on (`sale_id`). | CI |
| Audit trail | Present: a `soft_deleted` audit row with the old attributes is written (probe: 1 row per deletion). So it is *traceable*, but not *prevented*; the audit page, not the sales history, would show it. | AUTO; CI (`HasAuditTrail`) |
| Effect on POS refund/return | Refund/return code requires a completed, paid, non-trashed sale (`InventoryAdjustmentController`, `RefundController`), so deleting the sale blocks later refunds and orphans `refund_requests`/`product_returns` that point at it. | CI |

**Verdict: the finding is real** (owner-only, not exposed in UI, pre-existing, audited — but it breaks the rule "paid/completed protected" and can corrupt reports and stock).

### Proposed smallest safe correction (awaiting approval)
1. In `SaleController::destroy`, call `$this->authorize('delete', $sale)` and change `SalePolicy::delete` to allow **only** `status ∈ {cancelled, expired}` for an owner. For all other statuses (completed, refunded, refund_requested, pending) return a redirect back with an error ("Paid, completed, refunded or active reservations cannot be archived; use cancel or refund.").
2. Keep soft delete + the existing `soft_deleted` audit row for the permitted cases. No schema change, no UI change, refund/return workflows untouched.
3. *Alternative (even smaller):* remove the unused route, since no UI calls it. I recommend option 1 because it keeps the existing, audited archive capability.

### Regression tests to add if approved (Pest, one file)
- owner cannot archive completed / refunded / refund_requested / pending → sale still visible, revenue unchanged, stock unchanged, no `soft_deleted` audit row;
- owner can archive cancelled and expired → soft-deleted and audited;
- repair payment sale (`REPAIR-…`) cannot be archived;
- customer → 403; guest → redirect;
- completed sale remains refundable after a rejected delete attempt.

**Blocks deployment?** No for the 19-file UAT fix set (unrelated, pre-existing, owner-only, no UI path). **Recommended to fix before the defense** (small, low risk).

---
## 2. Classification of the 9 MISMATCH rows

Mismatch rows (RTM): STK-R2, RSV-R6, REP-R2, REP-R3, RPT-R2, TEC-R1, TEC-R2, TEC-R4, POS-R5.

| # | Mismatch | Manuscript | Diagram | App change | Test-case update | Adviser clarification | Essential or optional |
|---|---|---|---|---|---|---|---|
| 1 | TEC-R1 MySQL primary / Supabase backup vs real PostgreSQL | **Yes** (Ch2, Ch3, Table 4.0) | Fig 1.0 | — | — | Confirm what local DB was used | **Essential** |
| 2 | TEC-R2 "Hosting: GitHub" vs Ubuntu VPS + Actions | **Yes** | Fig 1.0 | — | — | — | **Essential** |
| 3 | TEC-R4 Testing text (MySQL/XAMPP/Supabase sync) vs SQLite tests + browser UAT | **Yes** (Ch3 Testing) | — | — | — | Confirm owners' involvement | **Essential** |
| 4 | STK-R2 "real-time" vs near-real-time | **Yes** (wording) | — | **Yes, small** (see §3.5) | Add STK-001 detail | Approve wording | **Essential** (wording); app fix recommended |
| 5 | RSV-R6 "Owner confirms reservation" (no such step) | — | **Fig 3.0** (remove box) | No (not built) | — | Confirm no approval step is wanted | **Essential** (diagram) |
| 6 | RPT-R2 report-type selector (sales, stock, movement) | Optional wording | **Fig 4.0** | Optional | Optional | Scope decision (§3.1) | **Essential** to reconcile; build is **optional** |
| 7 | REP-R2 repair parts-used / expected-completion fields | Reword *or* | — | Optional (two fields) | Optional | **Yes** (§3.2/3.3) | Essential to reconcile; build optional |
| 8 | REP-R3 repair durations 30–90 min (context, not a requirement) | No | — | No | — | — | Optional (no action; it is background narrative) |
| 9 | POS-R5 paid/completed deletable | — | — | **Yes** (§1) | New tests | Approve fix | **Essential** (integrity) |

Also not in the nine but required for consistency: Cancelled vs Expired wording (manuscript, Fig 3.0, three PDF expected results) — essential, docs/test-only.

---
## 3. Which capabilities the latest manuscript actually requires

| Capability | Latest manuscript text (TXT) | Explicit requirement? | Current implementation | Assessment |
|---|---|---|---|---|
| **3.1 Stock-level reporting** | Ch1 Scope Owner → Reports: "generate reports covering stock levels and sales activity"; Obj #6: "overview of their inventory status and business operations"; Ch3 §How: reports "of sales, inventory, and operations" | **Yes — explicit** | Stock levels are visible (Inventory list, Low-stock page, dashboard count, a stock table on Sales History) but there is no dedicated stock report or export | **PARTIAL**: meets "overview"; a literal "report" would need a stock summary view/print. Minimal option: reword or add one stock-summary view |
| **3.1b Movement reporting** | "product movement" appears only in the RRL paraphrase (Artene et al.) and in **Fig 4.0** ("sales, stock, movement") | **No** — not in Scope/Objectives/Requirements text; figure-only | Audit log records inventory changes (old/new values) but no movement report | **Recommendation only** (optional); fix by editing Fig 4.0 |
| **3.2 Repair parts-used tracking** | Ch3 §How: "tracks ongoing and pending repair requests, including the parts used and expected completion time"; Ch1 context says the shop lacks this | **Yes, but only in Ch3 §How**; Ch1 Scope/Obj #5 say only "log, track, manage" | `repairs`: customer, bike, issue, status, cost, is_paid. **No parts field** | **Stated feature missing** — adviser must choose reword vs build |
| **3.3 Expected repair completion** | same sentence | Same | **No field** | Same |
| **3.4 Gmail-only registration** | Not in Ch1–3 or the PDF (SignUp-003 expects only "valid email") | **No** | Deliberate: `ends_with:@gmail.com`, message "Only @gmail.com email addresses…", placeholder text, tests | **Undocumented design decision**, not a requirement. Limitation: customers with other providers cannot register. Needs owner/adviser clarification; document it or relax it |
| **3.5 Low-stock alerts with no queue worker** | Ch1 Obj #1: "sends automatic alerts"; Ch3 §When: "notified right away" | **Yes — explicit** (automatic alerts) | See below | **Real gap in production-like config** |

### 3.5 Detail — what happens with no worker (CI)
- **Still works without a worker:** the owner dashboard low-stock **count** and the **Low Stock page** are computed live from the database (dashboard stats are cached 60 s).
- **Does not work without a worker:** the owner **notification row and the SMS** are both created *inside* `CheckLowStockAlertJob` (`SmsService::checkLowStockAlert`), which is `ShouldQueue`. With `QUEUE_CONNECTION=database` (the `.env.example` default) and no worker started by the deploy workflow, the job just sits in `jobs`; no in-app alert, no SMS. Tests pass because `phpunit.xml` forces `queue=sync`.
- **Additional gap:** editing a quantity through the Inventory edit form (`AdminInventoryController`) does **not** dispatch the job at all; only POS/checkout/damaged-item paths do.
- **Smallest safe correction (proposal, awaiting approval):** record the owner in-app low-stock notification **synchronously** with the existing `NotificationDispatcher` (same approach as readiness notices, wrapped in try/catch) at the moment stock crosses the threshold, including the inventory edit path; keep SMS as the queued, best-effort extra. Add tests with `queue=database` (no worker) and an edit-path test. Production queue/`jobs` inspection (read-only) is still needed.
- **Blocks deployment?** No — it is pre-existing and not part of the 19-file fix set — but the production `QUEUE_CONNECTION` must be known before claiming STK-001 passes live.

---
## 4. Notification requirements (provisional baseline)

- **Provisional baseline: in-app notifications only.** The approved documents do not require email or SMS for reservation/pre-order notices (channel unspecified; PRE-005 says "the dashboard shows the 24-hour pick-up"). Email is mentioned only for OTP.
- **Documented limitation:** a customer must log in and open MotoTrack to see an in-app readiness notice. The 24-hour pickup period starts when that notice is recorded, whether or not the customer has opened the app. The owner can resend the notice and contact the customer by phone (phone is collected at sign-up) — this is a manual safeguard, not an automatic one.
- **No email or SMS was or will be introduced** until you decide whether an external channel is required. The decision paper (`MOTOTRACK_NOTIFICATION_REQUIREMENTS_DECISION.md`) already lists Options A/B/C; no change to it is needed, and this section is the one-paragraph statement for the manuscript's Limitations (see Chapters proposal C-13/C-15).

---
## 5. Final decision matrix

| # | Issue | Confirmed evidence | Impact on capstone objectives | Impact on financial/inventory integrity | Required correction | Blocks deployment? | Adviser approval? |
|---|---|---|---|---|---|---|---|
| D1 | Paid/completed/refunded/pending sale can be archived via `DELETE /sales/{id}` | AUTO probe: all 5 statuses deleted; revenue 100→0; pending stock never restored | POS reliability / "accurate records" objective weakened | **High**: revenue can be erased; pending reservation stock leaks; refunds blocked | Policy + controller guard + tests (§1) | No (pre-existing, owner-only, no UI) | Code change needs **your** approval; adviser not required |
| D2 | Low-stock alert depends on a queue worker; inventory-edit path never alerts | CI | Objective #1 "automatic alerts" at risk in production | Medium (missed reorder = lost sales; no money/stock corruption) | Synchronous in-app owner notice + edit-path hook + tests (§3.5); read-only check of production queue config | No, but verify config before live STK-001 | Your approval; adviser: wording of "real-time" |
| D3 | "Real-time" wording | TXT/CI | Defense credibility | None | Reword to "automatic, near-real-time" | No | Yes (manuscript) |
| D4 | Stock-level report (explicit in Scope/Obj #6) | TXT, CI | Medium — explicit but met partially via inventory pages | None | Option 1: reword to "inventory status overview"; Option 2: one stock-summary view | No | **Yes** (scope choice) |
| D5 | Movement report | TXT (figure only) | Low | None | Edit Fig 4.0 (or leave and explain as audit-log) | No | Yes (diagram edit) |
| D6 | Repair parts-used + expected completion (Ch3 §How only) | TXT, CI | Medium — a stated feature absent | None | Reword Ch3, or add two nullable columns + form fields + tests | No | **Yes** |
| D7 | Gmail-only sign-up | CI; absent from manuscript/PDF | Low–medium (excludes non-Gmail customers; OTP email works for any provider) | None | Document as design decision, or relax rule | No | Yes (owner/adviser clarification) |
| D8 | "Owner confirms reservation" in Fig 3.0 | CI | Diagram accuracy | None | Remove/relabel box | No | Yes (diagram) |
| D9 | DFD Payment and Back-up/Restore; ERD outdated | Visual + CI | High defense risk | None | Redraw Figs 6.0, 7.0 | No | **Yes** |
| D10 | MySQL/Supabase/GitHub-hosting text | TXT/CI | High defense risk | None | Manuscript revisions C-01…C-10 | No | Yes |
| D11 | Cancelled vs Expired wording | TXT, PDF | Medium | None | Manuscript + test-case expected results | No | Yes (test PDF wording) |
| D12 | Notification channel (in-app baseline) | TXT | Medium | Low (customer may miss 24 h window) | Document limitation; decide A/B/C later | No | **Yes — your decision** |
| D13 | OTP wrong-attempt limit (TCLA-014) only rate-throttled | CI | Low–medium | None | Verify live; optional attempt counter | No | Optional |
| D14 | DASH-005 idle timeout | CI | Low | None | Reword expected result or implement | No | Yes |
| D15 | Production queue/cache config; stale `jobs` rows | UAT reports | Affects live UAT of cancellations/notifications | Low | Read-only inspection before deploy | **Pre-deploy check** | No |
| D16 | PostgreSQL concurrency not tested | CI | Defense question | Unproven under load | Optional PG test in CI; state limitation | No | No |

## 6. Recommended priorities
1. **Deploy gate:** D15 read-only production check → your authorization → deploy the 19-file set → live UAT (list unchanged).
2. **Small code fixes to approve before the defense:** D1 (sale archive guard) and D2 (synchronous low-stock owner notice). Both are small and testable; neither blocks the current deployment.
3. **Adviser session:** D3, D4–D7, D12, D14 plus the figure/manuscript revisions (D8–D11).
4. **Then** the manuscript and diagram edits, followed by a regression pass on the live site.

---
## Status update 2026-10-16 (after owner approval)
- **D1 — implemented** (route removed, controller/policy fail closed, 21 tests). Not committed or deployed.
- **D2 — implemented** (`LowStockAlertService`, synchronous owner in-app alert on all quantity paths, re-arm, SMS best-effort, 19 tests). Not committed or deployed. SMS still requires a queue worker + gateway.
- All other rows unchanged.

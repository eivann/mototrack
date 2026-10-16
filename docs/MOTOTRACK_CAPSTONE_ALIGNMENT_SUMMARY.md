# MotoTrack — Capstone Alignment Summary

Date: 2026-10-16 · Analysis only. Nothing in the manuscript, PDF, diagrams, code, repository history or production was changed.

## 1. Overall coverage and alignment
- **67 requirement rows** reviewed (RTM): **ALIGNED 44 · PARTIAL 12 · MISMATCH 9 · NOT VERIFIED 2.** Most ALIGNED rows are aligned in code and automated tests but still **await live UAT**.
- Implementation is broadly **ahead of / different from** the documents: the app is cash-only with in-app notifications, fitment-based compatibility and grouped cancellation; the manuscript and diagrams still describe a MySQL system with a payment process, backup/restore and an owner-confirmation step.
- Evidence baseline: 208 tests / 673 assertions / 0 failures, build 1.27 s (SQLite; local). No production verification of the 19-file fix set.

## 2. Key discrepancies (confirmed)
1. Database/hosting: Ch2/Ch3/Fig 1.0 say MySQL primary + Supabase backup + GitHub hosting; reality is Supabase PostgreSQL, Ubuntu VPS, GitHub Actions, SQLite tests.
2. "Real-time" stock monitoring is near-real-time; SMS alert job depends on a queue worker the deployment never starts.
3. Cancelled vs Expired terminology (manuscript, Fig 3.0, RES-003/PRE-006/RSV-005).
4. Fig 3.0 "Owner confirms reservation" does not exist in the code.
5. Fig 6.0 DFD has Payment (5.0) and Back-up/Restore (6.0): neither exists.
6. Fig 7.0 ERD is outdated (Owner table, MySQL types, missing pre_orders, notifications, damaged_items, product_returns, refund_requests, audit_logs; sales/inventory/motorcycles columns).
7. Reports: no stock-level or movement report selector (Fig 4.0 and Ch1).
8. Repairs: no parts-used / expected-completion fields (Ch3).
9. Sign-up restricts emails to `@gmail.com` — undocumented.
10. `DELETE /sales/{id}` can archive paid/completed sales (no guard).
11. Notification channel is unspecified in the manuscript; PRE-005 implies the dashboard.

## 3. Defects/risks status
- Production-UAT defects (cancel button, owner notification, compatibility, SignUp-002, RES-001 edge, double period): **fixed, automated- and controlled-browser-verified, not deployed, not live-verified**.
- Open risks: unknown production `CACHE_STORE`/`QUEUE_CONNECTION`; possible stale `jobs` rows (inspect read-only first; do not start a worker blindly); no PostgreSQL concurrency test; no real 24 h expiry run; owners must enter fitment records; OTP lockout is rate-throttle only (TCLA-014); DASH-005 deferred.

## 4. Documentation revisions needed
24 proposals (C-01…C-24) in the Chapters report, mainly technology/environment wording, "real-time", Cancelled/Expired, notification channel, reports and repairs wording, OTP/roles/refunds, deployment status. Original docx untouched.

## 5. Missing UAT
16 PDF cases have no result; 15 are fixed-and-automated but **awaiting live UAT**; DASH-005 deferred. The 4 historically passed cases touched by defects (RSV-006, PRE-005, PRE-007, BRW-006) need a re-test row. A post-deploy regression of the other historically passed cases is also needed.

## 6. Adviser decisions required
(1) Notification channel (A/B/C); (2) "real-time" wording; (3) DASH-005; (4) diagram redraws (6.0, 7.0) and edits (1.0, 3.0, 4.0, 5.0); (5) `@gmail.com` rule; (6) repair fields vs reword; (7) report scope; (8) paid-sale deletion guard; (9) confirm facts on owner involvement in testing and local DB used.

## 7. Deployment blockers
- None are *code* blockers for the UAT-fix set. **Pre-deployment conditions:** read-only inspection of production `jobs` table and cache/queue settings (owner of the VPS), then the authorised deploy via `main` + GitHub Actions, then the live UAT checklist.
- Documentation inconsistencies do **not** block deployment but are defense risks.

## 8. Defense concerns
- Panel asking "Why MySQL in the text but Supabase in production?", "Where is payment/backup shown in your DFD?", "Is the alert really real-time?", "Why in-app only?", "Did all UAT pass?" (answer: 86/102 historical; 16 without result; fixes not live-verified).
- ERD vs database mismatch is the most visible.

## 9. Readiness
| Area | Status |
|---|---|
| Application logic | Strong (automated), live verification pending |
| Manuscript/figures | Not aligned — revisions pending approval |
| UAT evidence | Partial |
| **Overall** | **CONDITIONAL GO** — deploy only after the production queue/cache inspection; defend only after documentation and figures are corrected and live UAT is completed |

## Documents (in `docs/`)
RTM · Notification Decision · Chapters 1–3 Revision Proposals · Diagram Alignment Report · Manual UAT Coverage Matrix · Code–Documentation Gap Report · this Summary.

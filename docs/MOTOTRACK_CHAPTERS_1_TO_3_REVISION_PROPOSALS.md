# MotoTrack — Chapters 1–3 Revision Proposals

**The original docx was not modified.** These are proposals only; each needs adviser approval before the manuscript is changed. References are preserved; no new citations were invented (where a claim would need a source, it is marked "no citation needed — system fact").

Layout note: the docx labels Introduction (Ch1), Review of Related Literature + Technical Background (Ch2) and Methodology (Ch3); section headings are quoted below.

## Environment vocabulary to use consistently (proposed definition paragraph, Ch3 §Technologies / Testing)

| Term | Meaning in MotoTrack | Evidence |
|---|---|---|
| Laravel application logic | PHP/Laravel code (controllers, services, Blade/Alpine UI) | repo |
| Local development | Laragon (and XAMPP if still used) on developer PCs; local database per developer `.env` | team to confirm which DB |
| Production database | PostgreSQL hosted on Supabase | project facts |
| Supabase | Managed PostgreSQL hosting (not a "supplementary backup copy") | project facts |
| GitHub | Source control and repository host | repo |
| GitHub Actions CI/CD | Runs `php artisan test`, then deploys over SSH, `migrate --force`, caches | `.github/workflows/deploy.yml` |
| Ubuntu VPS | Hosts the live app at mototrack.online | project facts |
| SQLite test environment | `phpunit.xml` uses SQLite `:memory:`; fast logic tests only — **does not prove PostgreSQL locking, production queue/cache/mail configuration or live behaviour** | `phpunit.xml` |
| Browser UAT | Manual test cases (PDF) executed in Chrome/Safari/Edge on the live site; controlled-browser runs on synthetic data are separate | PDF, UAT reports |

## Proposed corrections

Format: **ID — Chapter/section**. Existing → Discrepancy → Proposed text → Reason → Evidence → Adviser approval.

### Technology and environment

**C-01 — Ch2 §Technologies → Database and Cloud Storage Technology.**
Existing: "MySQL served as the primary relational database… while Supabase, built on PostgreSQL, provided supplementary cloud-based storage… beyond the local database."
Discrepancy: production DB is Supabase PostgreSQL; no replication/backup feature in the app.
Proposed: "MOTOTRACK stores its data in a PostgreSQL database hosted on Supabase in production. During development, the proponents ran the application locally using Laragon, and automated tests ran against an in-memory SQLite database."
Reason: accuracy. Evidence: `.env` practice (not inspected), `phpunit.xml`, project facts. Approval: **Yes** (also confirm what the local dev DB actually was).

**C-02 — Ch2 Table 4.0 (Software Resources).** Existing: Database = MySQL; Cloud Database = Supabase, PostgreSQL; Hosting Platform = GitHub. Discrepancy: hosting is an Ubuntu VPS; GitHub is source control/CI. Proposed: Database = PostgreSQL (Supabase) / SQLite (automated tests); Hosting = Ubuntu VPS; Source control & CI/CD = GitHub, GitHub Actions; add Alpine.js, Tailwind CSS, Vite, PHPUnit/Pest. Approval: **Yes**.

**C-03 — Ch2 §MySQL and §Supabase and PostgreSQL (technology descriptions).** Replace the MySQL section with a PostgreSQL section; delete "MySQL serves as the system's primary local database…". Keep the generic PostgreSQL description. Approval: **Yes**.

**C-04 — Ch2 §Web Development Technology / §Web Development Technologies.** Existing: front end "HTML, CSS, and JavaScript". Proposed: add "Blade templates, Alpine.js and Tailwind CSS, bundled with Vite". Also "communicates with the MySQL database" → "the PostgreSQL database". Approval: **Yes**.

**C-05 — Ch2 §Laravel.** "strong compatibility with MySQL" → "with relational databases such as MySQL and PostgreSQL". Approval: No (editorial) / Yes if adviser wants tracking.

**C-06 — Ch2 §GitHub.** Add: "GitHub Actions runs the automated test suite and deploys approved changes to the production server." Approval: Yes.

**C-07 — Ch2 Fig 1.0 text (§Conceptual Model) and Fig 1.0 image.** Existing list shows MySQL, XAMPP, Supabase, PostgreSQL. See Diagram report. Approval: **Yes** (figure edit).

**C-08 — Ch3 §Development.** Existing: "PHP for the server-side logic, and MySQL for the database… Supabase… additional layer of cloud-based storage… backed up". Proposed: "…and PostgreSQL (Supabase) for the database." Delete the backup claim unless a backup procedure is documented. Approval: **Yes**.

**C-09 — Ch3 §Testing.** Existing: "communication with the MySQL database through XAMPP and Laragon, as well as the synchronization and accessibility of data stored in Supabase and PostgreSQL… The shop owners were also involved in the testing." Proposed: "Automated tests (208 tests, 673 assertions at the time of writing) ran on an in-memory SQLite database; manual user-acceptance tests were executed in a web browser against the deployed system (10/12–10/13/2026) and defects found were corrected and are awaiting re-verification on the live system. Automated tests on SQLite do not by themselves demonstrate PostgreSQL concurrency behaviour." Reason: honest scope of evidence. Evidence: baseline run, PDF. Approval: **Yes**. Caveat: "shop owners were involved in testing" — I have no evidence either way; team to confirm.

**C-10 — Ch3 §Deployment.** Existing: "Once the system passed all tests and was confirmed to be fully functional, MOTOTRACK was deployed…". Discrepancy: the latest fixes are not deployed and have not passed live UAT. Proposed: reword to the actual status at defense date (e.g., "MOTOTRACK is deployed on an Ubuntu VPS using GitHub Actions; corrections identified during user-acceptance testing were verified in automated and controlled-browser tests and are scheduled for live re-testing."). Approval: **Yes**.

### Features / scope

**C-11 — Ch1 §Purpose; §Obj #1; Ch2 (RRL heading), Ch3 §When and §How: "real-time stock monitoring".**
Existing: "real-time stock monitoring that notifies the owners"; "alerts run continuously so the owners are notified right away".
Discrepancy: updates occur on each stock transaction and page load; push requires a configured broadcast driver (unverified in production); SMS job needs a queue worker (none started by deployment).
Proposed: "automatic stock monitoring with low-stock alerts that update whenever stock changes and appear on the owner's dashboard." For the RRL heading ("Real-Time Stock Monitoring with Low Stock Alerts") keep it as a literature term — it paraphrases Roy et al. (2025); do not change the citation.
Approval: **Yes**.

**C-12 — Ch1 §Scope → Customer → Online Reservation.** Existing: "if the product is not picked up in time, the reservation is cancelled and the item goes back to stock." Proposed: "…the reservation automatically expires and the item goes back to stock. A customer may also cancel an active reservation at any time before pickup, which returns the item to stock." Reason: Cancelled (voluntary) vs Expired (automatic) distinction. Approval: Yes.

**C-13 — Ch1 §Scope → Pre-Order.** Existing: "the shop notifies the customer once the item has arrived… pick up within 24 hours after the notification… cancelled". Proposed: "The shop notifies the customer through the customer dashboard once the item has arrived [channel per adviser decision — see Notification Decision]. The 24-hour pickup period begins when the notification is recorded; if the item is not picked up in time, the pre-order expires. A customer may cancel a pre-order before pickup. No advance online payment is collected; payment is made in cash at pick-up." Approval: **Yes** (channel decision).

**C-14 — Ch1 §Scope → Product Browsing and §Compatibility (Customer).** Existing: "instantly verify whether a specific part is compatible". Proposed: add "based on the fitment records entered by the owner; if no fitment record exists, the system does not confirm compatibility." Approval: No (clarification consistent with §Limitations).

**C-15 — Ch1 §Limitations.** Existing is accurate ("no online payment, supplier management, delivery tracking"). Proposed additions: "Notifications are delivered in the application; no SMS/email order notices unless adopted." "Supplier returns and sourcing are done manually outside the system." "Backup of the production database is an infrastructure task, not a feature of the application." "Idle-session timeout is the standard session lifetime." Approval: Yes.

**C-16 — Ch1 §Scope → Shop Owner → Reports.** Existing: "generate reports covering stock levels and sales activity." Discrepancy: dashboard reports show revenue, completed repairs, daily revenue chart and sales history/receipts; no dedicated stock-level report. Proposed: "view dashboard summaries (revenue, completed repairs, low-stock items) and the sales history with printable receipts." Or approve building a stock report (category C). Approval: **Yes**.

**C-17 — Ch1 §Scope Owner → Reservations and Pre-Orders.** Add "record pickup, record cash payment, resend ready notification, process customer cancellations" (all exist). No "approve/confirm" step exists. Approval: Yes. (Also removes the implicit approval step suggested by "Owner confirms reservation" in Fig 3.0.)

**C-18 — Ch1 §Scope → add Authentication/Security paragraph.** Neither scope nor Requirements Analysis mentions login, email OTP verification, roles or audit logs, though the figures show them. Proposed: "Owner and customer sign-in requires an emailed one-time code; the owner role is the only administrative role; sensitive actions are recorded in an audit log." Approval: Yes. Caveat: failed-attempt lockout is throttling only (see matrix SEC-R2).

**C-19 — Ch1 §Scope/Objectives add refunds/returns/damaged.** Objectives mention damaged items and returns (present). Refund requests with owner approval exist and are not described. Add one sentence. Approval: Yes.

**C-20 — Ch3 §Requirements Analysis "What/How/Repair tracking" "including the parts used and expected completion time".** Discrepancy: repair records have no parts-used or ETA fields. Proposed: "…including customer, motorcycle, issue, status, cost and payment." (or approve schema addition). Approval: **Yes**.

**C-21 — Ch1 §Problem context (preorders) / Ch3.** "they purchase specific parts in advance for regular customers" — reflects the shop's informal practice; fine. **No change.** Mention that sourcing remains manual (ties to no supplier module). Approval: No.

**C-22 — Ch3 §Who "Shop Owner – The primary and only authorized user of the administrative side."** Consistent with the role design (`owner` middleware). Mrs. Riza Contreras is also an owner (Ch1) → note "owner accounts (one or more)". The code supports multiple owners (`User::owners()`). Approval: Yes.

**C-23 — Ch2 Benchmarking narrative (Table 1.0).** "MOTOTRACK addresses all of these gaps by bringing all seven features" — pre-order and compatibility are implemented. Keep, but "real-time stock monitoring" per C-11. No citation change. Approval: No.

**C-24 — Ch1 §Online payment, supplier systems, delivery.** Checked: Limitations already exclude them; nothing else in Ch1–3 implies them except DFD 5.0 (Payment) and DFD 6.0 (Back-up). Approval: n/a (figure issue).

### Items checked with no change needed
Reservation approvals (only the figure), returns (consistent), low-stock threshold (consistent), citations (untouched; none verified or altered by me).

## Pointers for the team (not changes)
- Hardware table says "AMD Ryzen 5 3200 G"; Fig 1.0 lists "Monitor 100hz 21.5\""; Table 3.0 says "Monitor, Keyboard, Mouse" — minor consistency only.
- The TOC page numbers and "Table 1.0/2.0" captions are images; not assessed.

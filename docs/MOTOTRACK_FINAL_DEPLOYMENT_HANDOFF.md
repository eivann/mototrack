# MotoTrack — Final Deployment Handoff (checkpoint)

> **AUTHORITATIVE CURRENT STATUS: see section 12.37 (end of file). Release set = 45 files. Commit, push, deploy, queue reload and live UAT are NOT YET STARTED. `deploy.yml` indentation blocker is OPEN (12.36 B / 12.37 B).**

Saved: 2026-10-16 (Asia/Manila) · Resume phrase: **"Continue MotoTrack"**
Repo: `E:\laragon\www\mototrack` · Live: https://mototrack.online · Last deployed commit: `dcb68c2c8356d284c630064fd2a7587b36400857`
**State: nothing committed, pushed or deployed. No production data, services or credentials were touched by Claude. Production Supabase was never contacted from the cloud workspace.**

Source tags: **[V]** verified by Claude (code inspection / automated tests / controlled browser), **[U]** reported by you; I have no raw evidence for it in this conversation and it must not be treated as independently verified.

---
## 1. Completed and verified
**A. Production UAT fixes** [V: automated + controlled-browser, synthetic data]
- Cancel button for reservations and pre-orders no longer disables before form submit (Alpine `@submit` flow; one POST per click; stock restored once; 1280 px and 390 px).
- Notifications are persisted synchronously (`NotificationDispatcher`, `notifyNow(['database'])`), independent of a queue worker; pre-order readiness deadline (24 h) starts only after ≥1 durable notification row.
- `User::owners()` cache bug fixed (IDs only; file/db/redis caches returned incomplete objects, so owners got no notifications).
- Compatibility is fitment-based (`CompatibilityMatcher`; no text/"Universal" fallback; year single or range).
- SignUp-002 name validation; RES-001 edge (cancelled/expired reservation no longer reported as paid); double-period notification text.

**B. Pre-deployment integrity fixes (owner-approved)** [V: automated, SQLite]
- Fix 1: `DELETE /sales/{id}` removed; `SaleController::destroy` stub aborts 405; `SalePolicy::delete` always false; 21 tests (`SaleDeletionProtectionTest`).
- Fix 2: `LowStockAlertService` — synchronous owner in-app low-stock alert on every quantity-changing path, one alert per low episode, re-arm after restock, SMS optional/best-effort (`SendLowStockSmsJob`), legacy `CheckLowStockAlertJob` kept as an idempotent shim; 19 tests (`LowStockSynchronousAlertTest`). No migration.

**C. Capstone alignment analysis** — 67 requirements: ALIGNED 45 · PARTIAL 12 · MISMATCH 8 · NOT VERIFIED 2 (after the two fixes). Seven alignment reports + the pre-deployment decision matrix (see §3).

## 2. Current baseline
- **248 tests passing / 793 assertions / 0 failures** (cloud run, SQLite `:memory:`, cache=array, queue=sync, mail=array) [V].
- **Vite build successful** [U — your local run; the cloud workspace cannot run npm]. Earlier local builds: 1.56 s and 1.27 s on the previous code; this round changed no JS/Blade files.
- Older baselines (207/666, 208/673) are history only.
- SQLite results do **not** prove PostgreSQL locking, production queue/cache/mail config, or live behaviour.

## 3. Pending Git changes and documentation (nothing committed)
Expect ~34 code/test/report files plus the `docs/` reports in `git status`. **Run `git status` yourself to confirm; I could not run git (device shell unavailable).**

*Code (first UAT set):* `resources/views/customer/dashboard.blade.php`, `app/Services/NotificationDispatcher.php` (new), `app/Notifications/StatusUpdated.php`, `app/Models/User.php`, `app/Services/ReservationService.php`, `app/Services/PreOrderCancellationService.php`, `app/Http/Controllers/PreOrder/AdminPreOrderController.php`, `app/Services/CompatibilityMatcher.php` (new), `app/Http/Controllers/Inventory/PublicBrowseController.php`, `resources/views/inventory/compatibility.blade.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Requests/ProfileUpdateRequest.php`, `app/Http/Controllers/Sales/SaleController.php`.
*Code (integrity-fix set, new to the change list):* `routes/modules/sales.php`, `app/Policies/SalePolicy.php`, `app/Services/SmsService.php`, `app/Jobs/CheckLowStockAlertJob.php`, `app/Actions/CommitOrder.php`, `app/Http/Controllers/Admin/RefundController.php`, `app/Console/Commands/ExpirePreOrders.php`, `app/Http/Controllers/Inventory/InventoryAdjustmentController.php`, `app/Services/InventoryService.php`, `app/Services/LowStockAlertService.php` (new), `app/Jobs/SendLowStockSmsJob.php` (new).
*Tests:* `tests/Feature/Customer/CancelUiAndNotificationDeliveryTest.php` (new), `tests/Feature/Inventory/CompatibilityAccuracyTest.php` (new), `tests/Feature/Sales/ReservationPickupTest.php` (new), `tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/Sales/SaleDeletionProtectionTest.php` (new), `tests/Feature/Admin/LowStockSynchronousAlertTest.php` (new), `tests/Feature/Admin/InventoryAdjustmentTest.php`, `tests/Feature/Admin/LowStockPanelTest.php`.
*Reports at repo root:* `MOTOTRACK_PRODUCTION_UAT_FINAL_REPORT.md` (addendum added), `MOTOTRACK_PRODUCTION_UAT_FIX_PROGRESS.md`.
*`docs/` (analysis only, no manuscript/diagram/PDF changed):* `MOTOTRACK_REQUIREMENTS_TRACEABILITY_MATRIX.md`, `MOTOTRACK_NOTIFICATION_REQUIREMENTS_DECISION.md`, `MOTOTRACK_CHAPTERS_1_TO_3_REVISION_PROPOSALS.md`, `MOTOTRACK_DIAGRAM_ALIGNMENT_REPORT.md`, `MOTOTRACK_MANUAL_UAT_COVERAGE_MATRIX.md`, `MOTOTRACK_CODE_DOCUMENTATION_GAP_REPORT.md`, `MOTOTRACK_CAPSTONE_ALIGNMENT_SUMMARY.md`, `MOTOTRACK_PRE_DEPLOYMENT_DECISION_MATRIX.md`, this handoff.
Other untracked scratch files already in your repo root (tarballs, `.cjs`, logs, zips) are **not mine**; do not commit them blindly and keep `.env` out (it is gitignored).

## 4. Production queue findings [U]
- You report: **0 pending jobs** in the production queue, and **20 historical failures, all classified**.
- Not available to me: the SQL output, the classification of the 20 failures, and which jobs/classes they were. **At resume, paste or point me to that evidence** so it can be recorded; until then treat it as user-reported.
- Implication I can state from code [V]: the new low-stock path no longer creates queued in-app alerts, and the legacy job is idempotent, so neither 0 pending jobs nor old failed rows can cause duplicate in-app alerts. Nothing in the current change set needs a worker for notifications; SMS (`SendLowStockSmsJob`) does.

## 5. Supervisor-managed queue worker and Reverb configuration [U]
- You report a Supervisor-managed queue worker and Reverb are configured on the VPS. I have **no details** (program names, queue name/connection, `--tries`/`--max-time`, Reverb host/port/TLS, nginx proxy rules) and did not inspect them.
- What I know from the repo [V]: `.github/workflows/deploy.yml` does not start/restart a worker or Reverb; `.env.example` has `QUEUE_CONNECTION=database`, `BROADCAST_CONNECTION=log`, `CACHE_STORE=file`; helper scripts `fix_reverb.cjs`, `fix_env.cjs`, `fix_fpm.cjs`, `fix_mail.cjs` exist in the repo root (not reviewed this session).
- Deployment consequence: after a deploy that changes PHP code, a long-running worker/Reverb process keeps **old code in memory** until restarted (`php artisan queue:restart`; Supervisor restart for Reverb). That restart is a decision for you (see §9); I did not do it.

## 6. Unresolved broadcasting issues [U / partly V]
- You report unresolved broadcasting issues; details are not in my context — **please supply the symptoms/logs at resume**.
- Known from code [V]: `StatusUpdated::via()` adds `broadcast` only when `config('broadcasting.default')` is not null/`log`/`null`; broadcast failure is caught and logged and never removes the durable in-app row (`NotificationDispatcher`). The dashboards subscribe via `window.Echo.private('App.Models.User.{id}')`. So broken broadcasting degrades live badge updates only; notifications still appear on page load/refresh.
- "Real-time" wording in the manuscript should be softened to "automatic/near-real-time" regardless (decision D3).

## 7. Remaining pre-deployment checks
1. Local: `php artisan test` (expect 248 / 793), `npm run build`, `git diff --check`, `git status` (confirm file list, no `.env`/secrets/unrelated files).
2. Confirm production `CACHE_STORE` (must not be a store that returns incomplete objects; fix is already ID-only) and `QUEUE_CONNECTION`/worker status (read-only).
3. Record the evidence for the 0-pending / 20-failed finding in the UAT report.
4. Decide worker/Reverb restart handling for the deploy window (§9).
5. Confirm the Supabase/PostgreSQL side needs no migration (none in this change set; 51 migrations unchanged).
6. Have the 12-item live UAT checklist (UAT final report) plus the two new checks (one alert per low-stock episode; DELETE /sales/{id} rejected) ready.

## 8. Next steps (in order)
**Controlled deployment** (only after you say so)
1. Complete §7 items 1–4. 2. Commit on the current branch (no new branch) with the attribution lines; push `main`; watch GitHub Actions (tests must pass in CI; deploy runs `migrate --force` → no-op). 3. Per your §9 decision: `php artisan queue:restart` and restart Supervisor-managed Reverb. 4. Hard-refresh browsers (Ctrl+F5). 5. Tail `storage/logs/laravel.log` for `notification failed` / `could not be recorded` / `Low-stock` warnings. 6. Rollback = `git revert` of the release commit on `main` (previous: `dcb68c2`); no schema rollback.
**Live UAT** (synthetic/owner-approved data; no manual production expiry commands)
RSV-006/007 (mobile too), PRE-007/008, RES-004/005 (owner UI + notifications), RES-001 (cash/change), RES-002/PRE-005, CMP-001/BRW-006/CMPM-001/002 (after owner links parts), SignUp-002/003, STK-001 (one alert; second sale none; restock + drop → second alert), DASH-005 (deferred), RES-003/PRE-006/RSV-005 (owner-approved expiry method), regression pass of the 86 historically passed cases. Record results as new rows; keep historical PDF results.
**Capstone documentation** (after adviser decisions)
Apply approved Chapter 1–3 revisions (C-01…C-24 in the revisions report); redraw Figs 6.0 (DFD) and 7.0 (ERD); edit Figs 1.0, 3.0, 4.0, 5.0; update test-case expected results (Cancelled vs Expired, TMX 125/155, STK-001, DASH-005) and add cases for refunds, audit logs, multi-item cancellation, owner notifications; update RTM after live UAT.

## 9. Decisions requiring your approval
1. **Authorize commit + push + deploy** (nothing done yet).
2. **Worker/Reverb handling at deploy**: approve `queue:restart` and Reverb restart on the VPS (I will not do it otherwise).
3. Notification channel: keep in-app baseline vs add email (Option B) or SMS (C) — customer must open MotoTrack to see a readiness notice.
4. "Real-time" wording → "automatic, near-real-time".
5. Stock-level report: reword vs build a summary view; movement report is figure-only (edit Fig 4.0).
6. Repair parts-used + expected-completion: reword Chapter 3 vs add two fields.
7. Gmail-only registration (`ends_with:@gmail.com`): document it or relax it.
8. DASH-005 idle timeout: reword expected result vs implement.
9. OTP wrong-attempt lockout (TCLA-014 is only route-throttled): verify live, optionally implement a counter.
10. Diagram redraws (6.0, 7.0) and edits (1.0, 3.0, 4.0, 5.0); Chapter 1–3 and test-PDF wording changes.
11. Confirm facts for the manuscript: local DB actually used in development; whether shop owners took part in testing.
12. Optional: PostgreSQL concurrency test in CI; `down_payment` column on `pre_orders` (leftover) keep/drop.

## 10. Standing rules (unchanged)
Never copy `.env`/credentials/dumps/customer data to another environment; no production DB mutation; no production expiry commands; do not start/clear queue workers or jobs without approval; no commit/push/deploy without approval; no new branch; do not claim live UAT passed; cloud edits do not update the local repo unless transferred and verified (stage → commit → re-stage → `cmp`; watch the sync-lag race).

---
## 11. Deploy workflow review (read-only, `.github/workflows/deploy.yml`, 2026-10-16)
Scope: this one file only; the VPS, Supervisor program definitions and Reverb were **not** inspected. Workflow not modified.

**What it does:** on every push to `main`: job `validate` (composer install, `php artisan test` on `.env.example`+SQLite, `npm run build`) → job `deploy` (composer `--no-dev`, rsync to `/var/www/mototrack` with `-rltgoDzvO --delete`, excluding `/.git/ /.github/ /node_modules/ /.env /storage/ /tests/`) → SSH script: `npm install`, `npm run build`, `chown` storage/bootstrap-cache/public-build, then as www-data `migrate --force`, `optimize:clear`, `config:cache`, `route:cache`, `view:cache`.

| Question | Finding |
|---|---|
| 1. Reloads the Supervisor `mototrack-queue` worker? | **No.** The script contains no `queue:restart`, no `supervisorctl`, nothing that touches the worker. A long-running worker keeps the previous code in memory after the rsync until it is restarted. |
| 2. Preserves `mototrack-reverb`? | **Yes, by omission.** Nothing stops, restarts or deletes it, and `.env` and `storage/` are excluded from the rsync. It also keeps running **old code** (and a stale `.env`/config if those change) until restarted by you. |
| 3. Could it retry historical failed jobs? | **No.** There is no `queue:retry`, `queue:flush`, `queue:work` or `migrate:fresh`/`rollback`; `migrate --force` has no pending migrations in this change set; `optimize:clear` only clears caches and does not touch the `failed_jobs` table. (This says nothing about the Supervisor worker's own command line, which I could not see; a worker flag such as retrying failed jobs would have to be in that Supervisor config.) |
| 4. Critical risks before pushing to `main`? | **One that must be decided: stale worker.** The change set alters job and notification code (`CheckLowStockAlertJob` now an idempotent shim, new `SendLowStockSmsJob`, `NotificationDispatcher`, `StatusUpdated`, services). Until `queue:restart` runs, the worker executes pre-deploy code. Other, non-blocking notes below. |

**Other observations (not blockers):**
- **Push = deploy.** There is no manual approval gate; merging/pushing to `main` triggers the production deploy once tests pass. Do not push until you have decided to deploy.
- **Brief outage window:** rsync `--delete` removes `public/build`, which is rebuilt afterwards on the VPS (`npm install` + `npm run build`); pages may miss assets for the duration of the build.
- **`--delete` safety:** only the listed paths are protected. Server-only files outside them (for example a `public/storage` symlink, if present and not in the repo) would be removed on each deploy. The previous deploy (`dcb68c2`) worked, so this is presumably fine; verify product images after deploy.
- **Supply chain:** `easingthemes/ssh-deploy@main` is unpinned.
- **`npm install` (not `npm ci`)** on the VPS can drift from `package-lock.json`.
- **No post-deploy health check or rollback step** in the workflow (rollback remains `git revert` + push).
- `optimize:clear` clears the application cache; harmless here (dashboard stats cache, owner-ID cache re-fill on demand).

**Exact next action (needs your approval; I changed nothing):**
1. Before pushing, decide how to reload the worker. Smallest safe option: append one line to the end of the SSH script, `sudo -u www-data php artisan queue:restart` — a graceful signal, the worker finishes its current job and Supervisor (`autorestart=true`, to be confirmed) starts it again with the new code. It does not retry or delete jobs.
2. Alternative with no workflow change: run that same command manually on the VPS right after the deploy succeeds.
3. Reverb: leave it running during the deploy; restart it manually (`sudo supervisorctl restart mototrack-reverb`) only if you want it to load new code/config — this was not required by the change set, and restarting drops live WebSocket connections briefly.
4. Please confirm on the VPS (read-only): the Supervisor program names, that the queue worker has `autorestart=true`, and its command line (no failed-job retry flags).

---
## 12. Resume session (2026-10-16 PM): Git review, worker reload, backup/rollback, GO/NO-GO
Device shell was still "Workspace unavailable", so `git`/`npm`/`php` could not run. Git state below was read **directly from `.git` files (read-only)**. Nothing committed, pushed, deployed or restarted.

### 12.1 Git state [V from .git files]
- Branch `main`; local `main` = `origin/main` = `dcb68c2c8356d284c630064fd2a7587b36400857` (= last deployed commit). `origin/main` is as of the last fetch (FETCH_HEAD 2026-10-15); run `git fetch` to confirm nobody else pushed.
- No commits exist yet for this session's work; the whole change set is uncommitted. Remote: `github.com/eivann/mototrack`.
- `.env`, `*.log`, `*.sql`, `*.zip`, `*.tar.gz`, `*.xml`, `fix_*.cjs`, `check_logs.cjs`, `upload_zip.cjs`, `test_db.cjs`, `measure_curl.cjs` are gitignored.
- **NOT ignored (root-level scratch that `git add -A` / `git add .` would stage):** `check_nginx.cjs`, `deploy_vps.cjs`, `deploy_vps2.cjs`, `deploy_vps_final.cjs`, `update_vps.cjs` (VPS scripts, may embed host/credentials: I did NOT open them), `*.php` scratch (`check_*.php`, `fetch_*.php`, `live_out.php`, `patch_*.php`, `real_controller.php`, `vps_controller.php` 154 KB prod copy, `test_*.php`), `live_error*.html`, `test_alpine.html`, `logs.txt`, `logs2.txt`, `current_logs.txt`, `response.json`, `needs_*.png/txt`, `svg_test.txt`, `deploy_new.bat`, `agent_artifacts/`, `scratch/`, `to update/`, `mototrack_recap_report.md`, `antigravity_superpowers_matrix.md`, older `MOTOTRACK_*.md` reports.
- `.gitignore` ends with a UTF-16-encoded fragment (garbled lines for `MotoTrack_product_images/`, `*.zip`, `*.tar.gz`); the last two patterns are redundant, but `MotoTrack_product_images/` is probably **not** ignored. Cosmetic; do not fix now.
- **Rule for the commit: stage by explicit path only (list below). Never `git add -A` / `git add .`.** Before commit run `git status --short` and `git diff --cached --stat`; the staged list must match section 3 exactly, with no `.cjs`, `.php` scratch, logs, images or `.env`. If any of the VPS scripts is already tracked, run `git ls-files | findstr /i "deploy_vps update_vps check_nginx"`; if tracked and containing credentials, treat as a separate rotation decision (not part of this release).

Suggested explicit staging (PowerShell, repo root), based on section 3:
```
git add routes/modules/sales.php app/Policies/SalePolicy.php app/Services/SmsService.php app/Jobs/CheckLowStockAlertJob.php app/Jobs/SendLowStockSmsJob.php app/Services/LowStockAlertService.php app/Actions/CommitOrder.php app/Http/Controllers/Admin/RefundController.php app/Console/Commands/ExpirePreOrders.php app/Http/Controllers/Inventory/InventoryAdjustmentController.php app/Services/InventoryService.php
git add resources/views/customer/dashboard.blade.php app/Services/NotificationDispatcher.php app/Notifications/StatusUpdated.php app/Models/User.php app/Services/ReservationService.php app/Services/PreOrderCancellationService.php app/Http/Controllers/PreOrder/AdminPreOrderController.php app/Services/CompatibilityMatcher.php app/Http/Controllers/Inventory/PublicBrowseController.php resources/views/inventory/compatibility.blade.php app/Http/Controllers/Auth/RegisteredUserController.php app/Http/Requests/ProfileUpdateRequest.php app/Http/Controllers/Sales/SaleController.php
git add tests/Feature/Customer/CancelUiAndNotificationDeliveryTest.php tests/Feature/Inventory/CompatibilityAccuracyTest.php tests/Feature/Sales/ReservationPickupTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Sales/SaleDeletionProtectionTest.php tests/Feature/Admin/LowStockSynchronousAlertTest.php tests/Feature/Admin/InventoryAdjustmentTest.php tests/Feature/Admin/LowStockPanelTest.php
git add MOTOTRACK_PRODUCTION_UAT_FINAL_REPORT.md MOTOTRACK_PRODUCTION_UAT_FIX_PROGRESS.md docs/
```
Note: `app/Http/Controllers/Admin/RefundController.php` path is from the earlier list; if `git status` shows it elsewhere, use the path git reports. `tests/` is excluded from the *rsync*, but still runs in CI `validate`, which gates deploy.

### 12.2 Supervisor queue-worker reload: decision and exact change
Facts [V]: deploy.yml never reloads the worker; this release changes job/notification code (`CheckLowStockAlertJob` shim, new `SendLowStockSmsJob`, `NotificationDispatcher`, `StatusUpdated`). A worker started before deploy would run the **old** `CheckLowStockAlertJob` (which creates the alert inside the job) for any job pushed in between, and may behave inconsistently with the new `SendLowStockSmsJob` class (do not rely on autoload re-reading).
Risk if skipped: low-moderate for correctness (in-app alerts are now synchronous, so no alert is lost), higher for SMS jobs and for any stale-code behaviour. Reverb likewise keeps old code, which only affects live badge push.
Recommended (needs your approval; I did not do it):
- **Option 1 (preferred, minimal, no workflow edit):** after GitHub Actions turns green, on the VPS run `cd /var/www/mototrack && sudo -u www-data php artisan queue:restart`. Graceful: running job finishes, Supervisor `autorestart` relaunches the worker. It does not retry, flush or delete jobs and does not touch `failed_jobs`.
- **Option 2:** append the same line at the end of the SSH script in `deploy.yml` (a separate reviewed commit; it changes deployment behaviour, so do it deliberately, not bundled silently).
- Reverb (optional): `sudo supervisorctl restart mototrack-reverb` only if you want new code; brief reconnect for connected browsers. Skip if broadcasting is already unstable until the symptoms are diagnosed.
- Read-only VPS checks before the push (paste results, redact secrets): `sudo supervisorctl status`; `sudo cat /etc/supervisor/conf.d/*mototrack*.conf` (check: `command=php artisan queue:work ...` has **no** retry flags and has `--tries`/`--max-time`; `autorestart=true`; `user=www-data`; `stopwaitsecs` ≥ longest job); `php artisan queue:failed | head` is allowed read-only, but **do not** run `queue:retry`.
- Historical failed jobs: nothing in the workflow or `queue:restart` retries them. Only an explicit `queue:retry` would; keep that off the table until the 20 failures are classified in the report.

### 12.3 Backup and rollback readiness
No schema change and no new migration in this release (51 migrations unchanged), so rollback is **code-only**.
Before the push (you run; I cannot reach the VPS or Supabase):
1. Record the deployed commit: `dcb68c2c8356d284c630064fd2a7587b36400857` (confirm with `git -C /var/www/mototrack log -1` only if `.git` exists on the VPS; the rsync excludes `.git`, so instead note the last green Actions run and its SHA).
2. VPS code snapshot (cheap, reversible): `sudo tar -czf /root/mototrack-pre-release-$(date +%F-%H%M).tar.gz --exclude=storage --exclude=node_modules --exclude=vendor -C /var/www mototrack` (this tarball includes `.env`; keep it root-only and never download it, or add `--exclude=mototrack/.env`).
3. Database: Supabase automatic daily backup / PITR status, confirm it exists in the dashboard. Optional manual snapshot via the Supabase dashboard. Not required for correctness (no migration), but cheap insurance because live UAT will create data.
4. Note the Supabase backup timestamp and the Actions run URL in the UAT report.
Rollback procedure (code only):
- Preferred: `git revert <release-commit>` then push `main` (CI re-validates and redeploys the old behaviour). Then `sudo -u www-data php artisan queue:restart`.
- Fast path if CI is broken: restore the tarball over `/var/www/mototrack` (excluding `.env` and `storage`), then `php artisan optimize:clear && config:cache && route:cache && view:cache` as www-data, then `queue:restart`. Rebuild assets only if `public/build` is missing.
- Data side: rows created by the new code are compatible with the old code (no schema change; `low_stock_notified` flag is existing). Database rollback is **not** needed.
- Caveat: rolling back re-enables `DELETE /sales/{id}` and the queued-only low-stock alert; do it only if the new behaviour is the problem.

### 12.4 Deploy-window risks to accept consciously
`--delete` removes `public/build` until `npm run build` completes (brief asset outage; deploy at low-traffic time); push to `main` deploys immediately (no approval gate); `ssh-deploy@main` is unpinned; `npm install` not `npm ci`; no health check or auto-rollback. None blocks the release; none is changed by me.

### 12.5 GO / NO-GO assessment (as of this checkpoint)
**Code: GO (conditional).** Evidence: 248 tests / 793 assertions green in the cloud run [V]; Vite build green on your PC [U]; no migration; deploy touches no data; fixes are narrowly scoped and regression-tested.
**Conditions that must be true before pushing (all yours to confirm; I could not):**
1. Local `php artisan test` = 248/793 and `npm run build` OK on the exact files being committed.
2. `git status` / `git diff --cached --stat` match section 3; no scratch, VPS scripts, logs or `.env` staged; `git diff --check` clean.
3. `git fetch` shows `origin/main` still `dcb68c2`.
4. VPS read-only checks in 12.2 done (worker command has no retry flag, `autorestart=true`) and the 12.3 backup steps done.
5. You explicitly authorize commit, push, and the `queue:restart` after deploy.
**NO-GO triggers:** any test/build failure locally; unexpected files staged; worker command containing a retry/`--tries=0` loop that replays failures; no Supabase backup; Actions `validate` red.
**Not blocking but unresolved:** items 3-12 in section 9 (documentation/product decisions); live UAT; broadcasting symptoms; the 0-pending/20-failed evidence is still only user-reported.

### 12.6 Exact next actions (in order; you run unless you say otherwise)
1. PowerShell in `E:\laragon\www\mototrack`: `git fetch; git status --short; git diff --check; php artisan test; npm run build`. Paste the output (or just the summary lines).
2. VPS read-only checks and backup (12.2, 12.3). Paste redacted results.
3. Reply "Authorize release" (and choose queue-restart Option 1 or 2). Only then will I prepare the commit message (with the attribution lines) and the exact commit/push commands; I still will not push for you unless you say so.
4. After the deploy: `queue:restart`, hard-refresh, tail `storage/logs/laravel.log`, then run the live UAT list in section 8.

---
## 12.7 Verified preflight results (user-supplied, 2026-10-16 ~14:03) [U = your command output; I did not rerun anything]
**PowerShell:** `php artisan test` 248 passed / 793 assertions / 0 failures; `npm run build` OK (1.82 s); `git diff --check` clean (CRLF/LF warning only); `git fetch origin` OK; local `main` = `origin/main` = `dcb68c2`; `git diff --name-only dcb68c2 -- database/migrations` empty; `git status --short` = 23 modified tracked + 20 untracked = 43 files; nothing staged or committed.
**VPS Supervisor:** `mototrack-queue` RUNNING, `mototrack-reverb` RUNNING. Queue program: `command=php /var/www/mototrack/artisan queue:work` (no retry flag, no `--tries`/`--max-time`, no `stopwaitsecs`), `autostart=true`, `autorestart=true`, **`user=root`**; process confirmed running as root.
**Database [U, earlier]:** Supabase PostgreSQL, `QUEUE_CONNECTION=database`, `CACHE_STORE=file`, 0 pending jobs at last inspection, 20 historical failed jobs already classified.
**Correction to 12.2:** I had listed `user=www-data` and `--tries`/`--max-time` as things to check. That expectation was wrong for this server: the worker is root-run and those flags are optional. Superseded by 12.8. Root user is **not changed in this release**.

## 12.8 Do the extra worker settings matter for THIS release? (no blockers)
- **No retry flag, `autorestart=true`:** the two properties that matter are satisfied. A restart cannot replay `failed_jobs`.
- **`--tries` absent:** worker default is 1 attempt for jobs without their own `$tries`. This is the behaviour production already has; `SendLowStockSmsJob` sets its own `tries = 3`. Changing it would alter failure behaviour and needs a Supervisor `reread/update`: not part of this release.
- **`--max-time` absent:** only bounds how long a worker lives without a deploy. Matters for hygiene (stale memory), not correctness here, because we restart explicitly. Recommended later hardening (e.g. `--max-time=3600`), not now.
- **`stopwaitsecs` default (10 s):** only matters if you stop via `supervisorctl restart`/`stop` while a job is mid-run (SIGTERM, then SIGKILL after 10 s; a killed job comes back after `retry_after` and, with 1 attempt, would land in `failed_jobs`). Jobs here are short (notification write, one SMS call) and the queue is empty, so negligible. `queue:restart` avoids it entirely.
- **`user=root`:** a pre-existing hardening issue, not a release blocker. Practical consequence for this release: anything the worker writes to `storage/` is root-owned until the next deploy's `chown -R www-data:www-data storage bootstrap/cache public/build` (already in `deploy.yml`) corrects it. Follow-up (separate, approved change): run the worker as `www-data`.

## 12.9 Safest way to reload the root-run worker after deploy
How `queue:restart` works with `CACHE_STORE=file`: the command writes a timestamp under key `illuminate:queue:restart` in `storage/framework/cache/data/..`; the worker checks it between jobs, finishes the current job, exits 0, and Supervisor (`autorestart=true`) relaunches it with the new code and the freshly cached config. It never touches `jobs` or `failed_jobs`.
**Permission rule:** run it as **www-data**, not root. A root-run `artisan` would create the cache file and its hashed sub-directories root-owned, and PHP-FPM (www-data) could then fail to write cache entries that hash into those directories until the next `chown`. A root worker can read anything, so the signal is always readable.
**Recommended sequence, after the Actions run is green:**
1. `sudo -u www-data test -w /var/www/mototrack/storage/framework/cache/data && echo writable`
2. `sudo supervisorctl status mototrack-queue` (note PID and uptime) and `ps -o pid,lstart,user,cmd -C php | grep queue:work`
3. `cd /var/www/mototrack && sudo -u www-data php artisan queue:restart`
4. Wait ~10 s, repeat step 2: **PID changed and uptime reset** = reload confirmed. (`queue:restart` ignores a failed cache write silently, so this check is mandatory, not optional.)
5. Reverb: only if you choose; leave alone while broadcasting symptoms are undiagnosed.
**Fallback if the PID did not change after ~30 s:** the queue is empty (0 pending), so a hard restart is safe: confirm `reserved` count first (read-only SQL: `select count(*) from jobs where reserved_at is not null;` must be 0), then `sudo supervisorctl restart mototrack-queue`. Do **not** use `queue:retry`, `queue:flush` or `queue:work` by hand (a hand-run `queue:work` as root would create a second worker).
**Side note on the deploy itself:** `optimize:clear` in `deploy.yml` also flushes the file cache, which can remove the restart key and may incidentally make a worker that started with a key present exit. Do not rely on that; it is not a reload mechanism.
**Workflow change: not needed for this release.** I prepared none: adding `queue:restart` to `deploy.yml` would put an untested change to the deploy path in the same push as the release, and the SSH user (`secrets.SERVER_USER`) is unknown (the script uses `sudo -u www-data` after `chown`, so root-like). Reconsider as a separate, reviewed commit after the release is stable. Window without a reload (minutes) is acceptable with an empty queue: in-memory classes are old, any not-yet-loaded class loads from the new files; no job format changed.

## 12.10 Exact Git staging list (verified by file presence in the cloud tree; reconciled against your counts)
All 32 code/test paths below exist (cloud tree); the secret scan of `docs/` and the two root reports found only prose words ("secret", "password") and no credential values, connection strings or keys.
Reconciliation: 24 code files (20 modified + 4 new: `NotificationDispatcher`, `CompatibilityMatcher`, `LowStockAlertService`, `SendLowStockSmsJob`) + 8 tests (3 modified + 5 new) + 2 root reports + 9 `docs/` files = **43** = your 23 modified + 20 untracked (untracked = 9 new code/test files + 9 docs + 2 root reports = 20; modified = 20 code + 3 tests = 23). If `git status` shows any other path, stop.
Stage by explicit path (replace the earlier `docs/` wildcard; `docs/` may contain files that are not mine):
```
git add routes/modules/sales.php app/Policies/SalePolicy.php app/Services/SmsService.php app/Jobs/CheckLowStockAlertJob.php app/Jobs/SendLowStockSmsJob.php app/Services/LowStockAlertService.php app/Actions/CommitOrder.php app/Http/Controllers/Admin/RefundController.php app/Console/Commands/ExpirePreOrders.php app/Http/Controllers/Inventory/InventoryAdjustmentController.php app/Services/InventoryService.php
git add resources/views/customer/dashboard.blade.php app/Services/NotificationDispatcher.php app/Notifications/StatusUpdated.php app/Models/User.php app/Services/ReservationService.php app/Services/PreOrderCancellationService.php app/Http/Controllers/PreOrder/AdminPreOrderController.php app/Services/CompatibilityMatcher.php app/Http/Controllers/Inventory/PublicBrowseController.php resources/views/inventory/compatibility.blade.php app/Http/Controllers/Auth/RegisteredUserController.php app/Http/Requests/ProfileUpdateRequest.php app/Http/Controllers/Sales/SaleController.php
git add tests/Feature/Customer/CancelUiAndNotificationDeliveryTest.php tests/Feature/Inventory/CompatibilityAccuracyTest.php tests/Feature/Sales/ReservationPickupTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Sales/SaleDeletionProtectionTest.php tests/Feature/Admin/LowStockSynchronousAlertTest.php tests/Feature/Admin/InventoryAdjustmentTest.php tests/Feature/Admin/LowStockPanelTest.php
git add MOTOTRACK_PRODUCTION_UAT_FINAL_REPORT.md MOTOTRACK_PRODUCTION_UAT_FIX_PROGRESS.md
git add docs/MOTOTRACK_CAPSTONE_ALIGNMENT_SUMMARY.md docs/MOTOTRACK_CHAPTERS_1_TO_3_REVISION_PROPOSALS.md docs/MOTOTRACK_CODE_DOCUMENTATION_GAP_REPORT.md docs/MOTOTRACK_DIAGRAM_ALIGNMENT_REPORT.md docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md docs/MOTOTRACK_MANUAL_UAT_COVERAGE_MATRIX.md docs/MOTOTRACK_NOTIFICATION_REQUIREMENTS_DECISION.md docs/MOTOTRACK_PRE_DEPLOYMENT_DECISION_MATRIX.md docs/MOTOTRACK_REQUIREMENTS_TRACEABILITY_MATRIX.md
```
Then verify BEFORE committing: `git status --short` (nothing unstaged/untracked that you did not intend; scratch files may remain untracked, that is fine, they just must not be staged); `git diff --cached --name-only | Measure-Object -Line` = 43; `git diff --cached --name-only | Select-String -Pattern "\.env|\.cjs|\.sql|\.zip|\.tar|\.log|vps|deploy_|migrations"` returns nothing; `git diff --cached --check`.
Commit message (when authorized) will end with the attribution lines from the session reminder. The handoff is edited as work proceeds, so re-stage it (`git add docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md`) right before committing.

## 12.11 Backup and rollback readiness
**Code rollback: READY.** No migration and no schema change (`dcb68c2` migrations unmodified; 51 migration files in the tree; none staged). Previous good commit `dcb68c2`. Procedure: `git revert <release-sha>` then push `main` (CI revalidates and redeploys), then the 12.9 reload sequence. Pre-deploy VPS tarball (12.3) is the fast path if CI is broken; it contains `.env` unless excluded: keep it root-only, never download it.
**Pending-migration check (VPS, read-only, before push):** `cd /var/www/mototrack && sudo -u www-data php artisan migrate:status | grep -i pending` should print nothing. (A tracked migration already in `dcb68c2` but never run in production would run at deploy.) Optional: `sudo -u www-data php artisan migrate --pretend` shows SQL without executing.
**Database backup: NOT established from my evidence** (no dashboard or dump output available to me). How you can confirm a *recoverable* backup, strongest first:
1. **Supabase dashboard → Project → Database → Backups.** Look for (a) Scheduled backups: a list of dated daily backups, newest within 24 h, with a Restore button; (b) Point-in-Time Recovery, if enabled, showing a recovery window. Availability depends on your Supabase plan; if the page shows no backups or says the plan does not include them, treat the DB as **unbacked** and do step 2. Record the newest backup timestamp.
2. **Your own logical dump (works on any plan, takes minutes):** from PowerShell, with a Postgres client at least as new as the server: `pg_dump --format=custom --no-owner --no-privileges --file "D:\backups\mototrack-pre-release-2026-10-16.dump" "<direct-or-session-pooler connection string from Supabase Settings > Database>"`. Put the file **outside the repo**, never commit it, never paste the connection string or dump to me or into a report (it contains customer data).
3. **Prove it is recoverable (a backup you never read is not a backup):** `pg_restore --list "D:\backups\mototrack-pre-release-2026-10-16.dump"` must list tables (`sales`, `inventory_items`, `users`, `notifications`, `jobs`, `failed_jobs`...); file size must be plausible and non-trivial; ideally restore into a throw-away local Postgres or Supabase branch (never into production) and compare `select count(*)` for `sales`, `inventory_items`, `users` with production.
4. Write down in the UAT report: backup type, timestamp, where stored, and the compared row counts (counts only, no customer data).
Why it matters even without a migration: live UAT will create real rows and the new code writes `low_stock_notified` flags and notifications; a backup lets you undo bad data without relying on the code rollback.

## 12.12 FINAL GO / NO-GO (preflight, 2026-10-16)
**Verified (GO evidence):** tests 248/793 green; build green; `git diff --check` clean; `main` = `origin/main` = `dcb68c2` (fresh fetch); no migration changes; nothing staged; worker RUNNING with `autorestart=true` and no retry flag; reload mechanism identified and safe (12.9); 43-file release list reconciled (12.10); code rollback ready.
**Result: GO for the code, CONDITIONAL on three small items you confirm (none requires me):**
1. **Database backup recoverable** (12.11 steps 1-4). Currently unproven: the one real open condition.
2. **VPS read-only extras:** `migrate:status` shows no pending; `select count(*) from jobs` = 0 pending and `reserved_at is not null` = 0 right before the push; `sudo -u www-data test -w .../storage/framework/cache/data` prints "writable"; free disk for the tarball (`df -h /`).
3. **Staging check passes** (12.10 verification commands): exactly 43 files staged, no `.env`/`.cjs`/`.sql`/`.zip`/`.log`/VPS-script paths.
**NO-GO if:** any item above fails; `origin/main` moved; Actions `validate` is red; the staged list contains anything not in 12.10; or the post-deploy reload (12.9 step 4) cannot be confirmed and the fallback is not possible.
**Accepted, not blocking:** root-run worker; missing `--tries`/`--max-time`/`stopwaitsecs`; `deploy.yml` weaknesses (12.4); unresolved broadcasting; documentation decisions (section 9, items 3-12); live UAT pending; the 0-pending/20-failed evidence is user-reported.

## 12.13 RELEASE AUTHORIZATION CHECKPOINT (stopped here)
Nothing committed, pushed, deployed, restarted or modified. To proceed, reply with: `Authorize release` plus (a) confirmation of items 1-3 above (or "backup unverified, proceed anyway"), (b) queue reload choice: `queue:restart as www-data after green deploy` (recommended; you run it, or authorize me to prepare the exact commands), (c) whether Reverb is restarted (default: no). I will then give the final commit message and commands; I will not push or touch the VPS unless you explicitly instruct me to and a tool for it exists.

---
## 12.14 Final verified results (user-supplied, 2026-10-16 ~14:17) [U; not rerun by me]
- **VPS:** `migrate:status` all Ran, none pending; cache dir writable by www-data; disk 96 GB total / 92 GB free (5%); `mototrack-queue` and `mototrack-reverb` RUNNING; queue `autorestart=true`, runs as root, no retry flag.
- **Supabase (read-only SQL):** `total_jobs=0`, `reserved_jobs=0`, failed jobs = 20 (classified earlier). Not retried, not deleted.
- **Local:** 248 tests / 793 assertions; Vite build 1.82 s; `git diff --check` clean (line-ending warning only); `git fetch` OK; `main` = `origin/main` = `dcb68c2`; no modified/untracked migrations.
- **Git staging: NOT DONE.** `git diff --cached` (check/name-only/stat) all empty; 0 staged; 23 modified + 20 untracked = 43 intended files.
- **Database backup: NOT YET CONFIRMED** (user will supply evidence).
Conditions from 12.12: condition 2 (VPS extras) is now **PASSED**; condition 3 (staging) is **PENDING**; condition 1 (backup) is **PENDING**.

## 12.15 Exact staging and verification commands (Windows PowerShell; review, then run yourself; nothing here commits)
The path list is the 43 files of 12.10 (24 code, 8 tests, 2 root reports, 9 docs), no wildcards, no `git add -A`/`.`. Stage **after** my last edit of the handoff (re-run `git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md` if the file changes again).
```powershell
# --- A. STAGE (explicit paths only; aborts if any path is missing) ---
Set-Location E:\laragon\www\mototrack
$files = @(
    'routes/modules/sales.php',
    'app/Policies/SalePolicy.php',
    'app/Services/SmsService.php',
    'app/Jobs/CheckLowStockAlertJob.php',
    'app/Jobs/SendLowStockSmsJob.php',
    'app/Services/LowStockAlertService.php',
    'app/Actions/CommitOrder.php',
    'app/Http/Controllers/Admin/RefundController.php',
    'app/Console/Commands/ExpirePreOrders.php',
    'app/Http/Controllers/Inventory/InventoryAdjustmentController.php',
    'app/Services/InventoryService.php',
    'resources/views/customer/dashboard.blade.php',
    'app/Services/NotificationDispatcher.php',
    'app/Notifications/StatusUpdated.php',
    'app/Models/User.php',
    'app/Services/ReservationService.php',
    'app/Services/PreOrderCancellationService.php',
    'app/Http/Controllers/PreOrder/AdminPreOrderController.php',
    'app/Services/CompatibilityMatcher.php',
    'app/Http/Controllers/Inventory/PublicBrowseController.php',
    'resources/views/inventory/compatibility.blade.php',
    'app/Http/Controllers/Auth/RegisteredUserController.php',
    'app/Http/Requests/ProfileUpdateRequest.php',
    'app/Http/Controllers/Sales/SaleController.php',
    'tests/Feature/Customer/CancelUiAndNotificationDeliveryTest.php',
    'tests/Feature/Inventory/CompatibilityAccuracyTest.php',
    'tests/Feature/Sales/ReservationPickupTest.php',
    'tests/Feature/Auth/RegistrationTest.php',
    'tests/Feature/Sales/SaleDeletionProtectionTest.php',
    'tests/Feature/Admin/LowStockSynchronousAlertTest.php',
    'tests/Feature/Admin/InventoryAdjustmentTest.php',
    'tests/Feature/Admin/LowStockPanelTest.php',
    'MOTOTRACK_PRODUCTION_UAT_FINAL_REPORT.md',
    'MOTOTRACK_PRODUCTION_UAT_FIX_PROGRESS.md',
    'docs/MOTOTRACK_CAPSTONE_ALIGNMENT_SUMMARY.md',
    'docs/MOTOTRACK_CHAPTERS_1_TO_3_REVISION_PROPOSALS.md',
    'docs/MOTOTRACK_CODE_DOCUMENTATION_GAP_REPORT.md',
    'docs/MOTOTRACK_DIAGRAM_ALIGNMENT_REPORT.md',
    'docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md',
    'docs/MOTOTRACK_MANUAL_UAT_COVERAGE_MATRIX.md',
    'docs/MOTOTRACK_NOTIFICATION_REQUIREMENTS_DECISION.md',
    'docs/MOTOTRACK_PRE_DEPLOYMENT_DECISION_MATRIX.md',
    'docs/MOTOTRACK_REQUIREMENTS_TRACEABILITY_MATRIX.md'
)
"Expected file count: $($files.Count)"   # must print 43
$missing = $files | Where-Object { -not (Test-Path -LiteralPath $_) }
if ($missing) { "MISSING:"; $missing; throw "Aborting: path(s) missing" }
git add -- $files

# --- B. VERIFY ---
$staged = @(git diff --cached --name-only)
"Staged count: $($staged.Count)"                      # must be 43
Compare-Object ($files | Sort-Object) ($staged | Sort-Object)   # must print NOTHING
$bad = $staged | Select-String -Pattern (^|/)\.env|\.cjs$|\.sql$|\.zip$|\.tar|\.gz$|\.log$|\.xml$|\.bak$|\.png$|vps|deploy_|migrations|node_modules|vendor/|\.github
if ($bad) { "FORBIDDEN PATH STAGED:"; $bad } else { "No forbidden paths staged" }
git diff --cached --check                             # must print nothing (CRLF warnings are OK)
git diff --cached --name-status                       # expect 23 M + 20 A
git diff --cached --stat                              # last line: 43 files changed
git diff --name-only                                  # unstaged edits to tracked files: must print nothing
git diff --cached -U0 | Select-String -Pattern ^\+.*(password\s*=|api[_-]?key|secret\s*=|BEGIN (RSA|OPENSSH)|postgres(ql)?://|sk-[A-Za-z0-9]{20}|SERVER_(HOST|USER|KEY))
#   ^ content scan of ADDED lines: should print nothing; review any hit before committing
```
Acceptance: count 43; `Compare-Object` empty; no forbidden path; `--check` empty; `--name-status` 23 M + 20 A; unstaged-diff empty; content scan empty (a hit in `tests/Feature/Auth/RegistrationTest.php` would be the pre-existing synthetic test password: confirm it is not in an added line you did not expect). If anything differs, run `git restore --staged <path>` for the odd file (or `git restore --staged .` to unstage everything; this does not touch your working files) and tell me.

## 12.16 Draft commit (do NOT run until you authorize the release)
Write the message to a file outside the repo, then commit from it:
```powershell
$msg = @'
fix: UAT defects, sale-deletion protection and synchronous low-stock alerts

- Disable DELETE /sales/{id} (route removed, controller 405, policy denies all roles); sales are cancelled or refunded, never deleted
- Add LowStockAlertService: owner in-app alert recorded synchronously after commit from every stock-changing path, one alert per low-stock episode, re-armed on restock; SMS best-effort via queued SendLowStockSmsJob; legacy CheckLowStockAlertJob kept as idempotent shim
- Durable NotificationDispatcher (24h pre-order deadline starts only after a stored notification); ReadyToTest owner-cache fix; cancel-button UI fix
- Compatibility matcher accuracy, sign-up and profile validation fixes
- Add regression tests (248 tests / 793 assertions) and UAT, traceability and deployment-handoff documents
- No migrations, config or .env changes

Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01X7MDpUk5oJaBFKoNSrK26B
'@
Set-Content -Path "$env:TEMP\mototrack_commit_msg.txt" -Value $msg -Encoding utf8
git commit -F "$env:TEMP\mototrack_commit_msg.txt"
git log -1 --stat | Select-Object -First 5      # note the new SHA
git push origin main                              # THIS DEPLOYS TO PRODUCTION: only after the backup and every 12.18 box are ticked
```
(If the PowerShell `utf8` encoding writes a BOM and bothers you, use `[IO.File]::WriteAllText("$env:TEMP\mototrack_commit_msg.txt", $msg)`.) Pushing `main` triggers CI `validate` then `deploy`; if `validate` is red nothing deploys.

## 12.17 Manual post-deploy worker reload runbook (final)
Run on the VPS **only after** the Actions run shows both jobs green (the SSH script has finished `config:cache`). Nothing in this runbook retries, flushes or deletes jobs.
```bash
cd /var/www/mototrack
sudo supervisorctl status mototrack-queue                 # expect RUNNING; note uptime
OLD=$(sudo supervisorctl pid mototrack-queue); echo "old PID: $OLD"
sudo -u www-data php artisan queue:restart                # graceful signal, run as www-data (NOT root)
sleep 15
NEW=$(sudo supervisorctl pid mototrack-queue); echo "new PID: $NEW"
[ -n "$NEW" ] && [ "$OLD" != "$NEW" ] && echo "RELOADED" || echo "NOT RELOADED"
sudo supervisorctl status mototrack-queue                 # RUNNING, uptime just reset (seconds)
sudo find storage/framework/cache -user root | head -5    # expect no output (no root-owned cache files)
sudo supervisorctl tail mototrack-queue | tail -n 20      # no crash loop / fatal errors
```
Notes: an idle `queue:work` polls every ~3 s, so the old process exits within seconds; `autorestart=true` relaunches it. `queue:restart` swallows a failed cache write, which is why the PID comparison is the real proof. **If "NOT RELOADED" after ~30 s:** re-check `select count(*) from jobs where reserved_at is not null;` = 0, then `sudo supervisorctl restart mototrack-queue` (stopwaitsecs default 10 s is irrelevant on an empty queue); never run a second `queue:work` by hand. Then `select count(*) from failed_jobs;` must still be 20 (a read-only proof that nothing was retried or added). Reverb: leave running unless you decide otherwise. Then Ctrl+F5 the site and tail `storage/logs/laravel.log` for `notification failed`, `could not be recorded`, `Low-stock`.

## 12.18 Remaining condition: recoverable database backup
Still required before `git push` (my GO is conditional on it). Evidence I can accept (counts and timestamps only; **never paste connection strings, passwords or dump contents**):
- **Path A, Supabase dashboard:** Database > Backups shows a dated scheduled backup newer than ~24 h (and/or a PITR window) with a Restore option. Give me the newest timestamp and backup type. If the page shows no backups or the plan lacks them, use path B.
- **Path B, your own dump:** `pg_dump --format=custom --no-owner --no-privileges --file "D:\backups\mototrack-pre-release-2026-10-16.dump" "<Supabase connection string>"`, stored outside the repo; then `pg_restore --list "<that file>"` lists `sales`, `inventory_items`, `users`, `notifications`, `jobs`, `failed_jobs`. Give me file size and the first/last lines of the list (no data). Strongest proof: restore into a throwaway local Postgres/Supabase branch (never production) and compare `count(*)` of `sales`, `inventory_items`, `users` with production.
- **Path C, explicit risk acceptance:** write "backup unverified, proceed anyway". I will record it as your decision. Risk context: this release has no migration and no destructive data operation, so the code rollback in 12.11 is sufficient for code faults, but only a backup protects against bad *data* written during live UAT.

## 12.19 FINAL GO / NO-GO REPORT (2026-10-16, pre-authorization)
| Check | Status |
|---|---|
| Tests 248/793, build, `git diff --check` | PASSED [U] |
| `main` = `origin/main` = `dcb68c2` | PASSED [U] |
| Migrations (none changed; none pending in prod) | PASSED [U] |
| Queue empty (0 total, 0 reserved); failed jobs untouched (20) | PASSED [U] |
| Worker RUNNING, `autorestart=true`, no retry flag | PASSED [U] |
| Reload method, permissions, PID proof (12.17) | READY |
| Disk, cache dir writable | PASSED [U] |
| Staging list (43, explicit) and verification commands | READY, **not executed yet** |
| Secret/scratch exclusion | by explicit paths + 12.15 scans (pending your run) |
| Code rollback (`git revert`, tarball) | READY |
| Recoverable DB backup | **PENDING (blocking until A, B or C)** |
**Decision: GO for the code. Release is blocked only by (1) the backup evidence and (2) your staging run passing the 12.15 checks.** NO-GO triggers: any 12.15 acceptance failure, `origin/main` moved (re-`git fetch` immediately before pushing), Actions `validate` red, jobs table non-empty at push time with unexplained reserved rows, or no way to confirm the worker reload.

## 12.20 RELEASE AUTHORIZATION CHECKPOINT (stopped)
Nothing committed, staged by me, pushed, deployed, restarted, retried or modified. Next from you: run 12.15 and paste the output; send the backup evidence (12.18); then reply `Authorize release`. I will review the staging output, confirm the final GO, and you commit/push with 12.16, then run 12.17.

---
## 12.21 Staging passed and staged-content security review (2026-10-16 ~14:29)
**Your results [U]:** 43 files staged; 23 M + 20 A; `git diff --cached --check` empty; the trailing-whitespace issue in `MOTOTRACK_PRODUCTION_UAT_FINAL_REPORT.md` was fixed by you (one line, trailing space after "started."); nothing committed, pushed or deployed.
**My independent review of the STAGED CONTENT [V, read-only]:** I read `.git/index` and the 43 loose blob objects directly from your repo (no git needed), inflated each, and re-computed its SHA-1.
- Index has all 43 intended paths at merge-stage 0; each has a freshly written loose blob (no stray older entries among the 43). SHA-1 of every blob matches its index entry.
- Staged blobs contain **no CRLF** (normalised to LF by git; this explains the CRLF warning); total 382,101 bytes.
- Staged blobs equal my verified cloud copies for 41 files byte-for-byte. The 2 differences are explained: `MOTOTRACK_PRODUCTION_UAT_FINAL_REPORT.md` (your trailing-space fix) and `tests/Feature/Auth/RegistrationTest.php` (line endings only: identical when CR is ignored). So the code that passed 248/793 in the cloud is the code that is staged.
- Secret scan of every staged blob (passwords with literal values, API keys, private-key headers, `postgres://` URLs, `sk-`/`AKIA`/JWT tokens, long base64 lines, IP addresses, `ssh-rsa`, `.env` variable names such as `DB_PASSWORD`/`APP_KEY`/`REVERB_*`/`MAIL_PASSWORD`/`SUPABASE`): **nothing found.** Benign matches reviewed one by one: `@gmail.com` (the Gmail-only registration rule and synthetic test addresses `test@gmail.com`, `valid.name@gmail.com`, `at@gmail.com`, `user@yahoo.com`), `services.movider.api_key => null` (test config nulling the SMS key), the GitHub Actions secret **name** `SERVER_USER` in this handoff (a name, not a value), the public repo handle in the handoff, and `noreply@anthropic.com` in the draft commit trailer. The only password-like literal is the synthetic test password in `RegistrationTest`, which pre-exists and is not production.
- Not in the staged set (verified by path): `.env`, any `.cjs`/VPS script, logs, dumps, archives, images, migrations, `.github`, `vendor`, `node_modules`.
- 23 lines with trailing whitespace exist in staged code files (e.g. `RefundController`, `InventoryService`, `dashboard.blade.php`) but your `--cached --check` is empty, so they are pre-existing unchanged lines, not part of this change. Leave them.
**Additional Git checks remaining (all quick, all yours):**
1. **Re-stage the handoff after this edit** (it changed after you staged it): `git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md`, then `git diff --name-only` must print nothing and `(git diff --cached --name-only).Count` must still be 43. Do not edit any staged file after this point without re-adding it.
2. `git branch --show-current` = `main`.
3. `git status --short`: staged entries `M `/`A ` only for the 43; everything else must be `??` scratch (left untracked on purpose).
4. Immediately before pushing: `git fetch origin; git rev-parse HEAD origin/main` must both be `dcb68c2...` (nobody else pushed).
5. After committing (12.16): `git show --stat --oneline HEAD` shows exactly 43 files and the attribution trailers; `git status --short` shows no `M`/`A` entries left.
6. Optional extra scanner if installed: `gitleaks protect --staged --config .gitleaks.toml` (repo has `.gitleaks.toml`); not required, my scan found nothing.
No other Git check is needed. **Git readiness: PASSED, pending only item 1.**

## 12.22 Updated GO/NO-GO table
Staging list/verification: **PASSED** [U + V]. Staged-content security review: **PASSED** [V]. Remaining blocker: **recoverable database backup (12.18)**. Everything else unchanged from 12.19. Decision: GO for the code; push is gated only by the backup evidence (or your explicit "backup unverified, proceed anyway") and your `Authorize release`.

## 12.23 RELEASE AUTHORIZATION CHECKPOINT (stopped)
Nothing committed, pushed, deployed, restarted, retried or modified by me. Application code untouched; full suite not rerun.

---
## 12.24 Database backup evidence (user-supplied, 2026-10-16 ~15:02) [U; I have not seen the file]
| Item | Value |
|---|---|
| File | `mototrack_public_20261016_145614.dump` (taken 14:56:14 PHT, 2026-10-16) |
| Location | `E:\MotoTrackBackups` (outside the Git repo) |
| Size | 144,288 bytes |
| Scope | production Supabase PostgreSQL, **`public` schema only** |
| `pg_restore --list` | exit code 0 (archive is readable and well-formed) |
| TABLE DATA entries | 21 |
| Source server | PostgreSQL 17.6; tools used: PostgreSQL 18.6 (pg_dump newer than server is supported) |

**Status, stated precisely: READABLE BACKUP ARCHIVE. NOT YET A PROVEN RECOVERABLE BACKUP.** A clean `--list` proves the archive is intact and shows its table-of-contents; it does not prove that the data loads, that constraints/sequences/indexes rebuild, or that row counts match. **No test restore has been performed.** Treat the backup as "available, unverified for restore".
**Consistency check I could do from the repo [V]:** the 51 migrations create 20 application tables (`audit_logs, cache, cache_locks, customers, damaged_items, failed_jobs, inventory_items, job_batches, jobs, motorcycles, notifications, part_compatibility, password_reset_tokens, pre_orders, product_returns, refund_requests, repairs, sales, sessions, users`) plus Laravel's `migrations` table = **21**, which equals the 21 TABLE DATA entries. So the dump appears to cover every application table, none missing (an empty table still gets a TABLE DATA entry). Run `\dt public.*` count in production once if you want certainty about extra tables.
**Known limits of a `public`-only dump:** it excludes other schemas (`auth`, `storage`, `extensions`...), roles/grants and Supabase project settings. MotoTrack uses Laravel's own `users` table, not Supabase Auth, so app data is covered; Supabase-specific objects would need to be recreated from the dashboard in a disaster. **Freshness:** data written after 14:56:14 is not in it; a code rollback (the expected recovery path) does not need it.
**Privacy:** the dump contains customer data and password hashes. Keep it where it is, not in a synced cloud folder, never in Git, never sent to me or pasted anywhere.

## 12.25 Advice: test the restore before authorizing? **Yes, recommended, ~10-15 minutes, fully local.**
Reasoning: the deployment itself is code-only and rollback does not depend on the DB, so this is not a technical blocker for pushing; but our own standard for "recoverable" (12.18) was proof, the test is cheap and safe, and it is the only way to find a broken backup *before* you need it (live UAT will create real rows right after the release). If you skip it, say "backup readable but restore untested, proceed anyway" and I will record that as an accepted risk. The release remains a conditional GO either way.
**Safety rules (do not skip):** restore ONLY into a throw-away database on `localhost`; never use the Supabase connection string with `pg_restore`; make sure no `PG*`/`DATABASE_URL` environment variables point at production (list the *names* only: `Get-ChildItem Env: | Where-Object { $_.Name -match '^(PG|DATABASE_URL)' } | Select-Object Name`); never run `php artisan` against the restored DB; delete the scratch DB afterwards.
**Target server:** PostgreSQL **17** preferred (matches production; `pg_restore` 18.6 can load the archive into 17 or 18; a server older than 17 can reject statements from a v17+ dump). Option A: local PostgreSQL 18.6 you already have (acceptable; note the version difference in the report). Option B (best fidelity, disposable): `docker run --rm -d --name mototrack-restore-test -e POSTGRES_PASSWORD=localtest -p 55432:5432 postgres:17` (port 55432 so it cannot collide with anything).
**Commands (PowerShell; adjust host/port to your option):**
```powershell
$dump = 'E:\MotoTrackBackups\mototrack_public_20261016_145614.dump'
$h='localhost'; $p=55432; $u='postgres'; $env:PGPASSWORD='localtest'   # local scratch server password only
createdb -h $h -p $p -U $u mototrack_restore_test
pg_restore -h $h -p $p -U $u -d mototrack_restore_test --no-owner --no-privileges -v $dump 2> 'E:\MotoTrackBackups\restore_test_stderr.txt'
"pg_restore exit code: $LASTEXITCODE"
Get-Content 'E:\MotoTrackBackups\restore_test_stderr.txt' | Select-String -Pattern 'error' | Select-Object -First 30
$q = "select table_name, (xpath('/row/c/text()', query_to_xml(format('select count(*) as c from %I.%I', table_schema, table_name), false, true, '')))[1]::text::bigint as rows from information_schema.tables where table_schema='public' and table_type='BASE TABLE' order by 1;"
psql -h $h -p $p -U $u -d mototrack_restore_test -At -F ' | ' -c $q
psql -h $h -p $p -U $u -d mototrack_restore_test -At -c "select count(*) from failed_jobs; select count(*) from migrations;"
Remove-Item Env:PGPASSWORD
```
Expected benign errors (read them, do not ignore blindly): `schema "public" already exists` (a fresh DB already has `public`), and possibly Supabase-specific items (roles `anon`/`authenticated`/`service_role`, extensions in the `extensions` schema). Anything about a **table, column, constraint, index, sequence or COPY failing** is a real failure.
**Pass criteria (record in the UAT report; counts only, no customer data):**
1. `pg_restore` finishes; only the benign errors above (list them).
2. 21 tables present; restored row counts for stable tables (`users`, `inventory_items`, `motorcycles`, `part_compatibility`, `customers`, `repairs`, `refund_requests`, `product_returns`, `damaged_items`) equal the same counts run read-only against production; volatile tables (`sales`, `pre_orders`, `notifications`, `audit_logs`, `sessions`, `cache`, `jobs`) may differ only by activity since 14:56.
3. `failed_jobs` = 20 and `migrations` = 51 in the restored copy.
4. Scratch DB/container removed (`dropdb ...` or `docker stop mototrack-restore-test`).
If criteria pass: backup status becomes **RECOVERABLE (restore-tested)**, and the only remaining gate is your `Authorize release`. If the shop takes orders for hours before the push, re-run the dump immediately before pushing (cheap) so the safety copy is fresh.

## 12.26 Updated checkpoint (stopped)
Verified staging of the 43 files is untouched by this step (I changed only this handoff, not code, and did not touch the index). **Because the handoff changed again, re-add it as the last step before committing:** `git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md`, then confirm `git diff --name-only` is empty and the staged count is still 43. GO/NO-GO: code GO; backup = readable-unrestored; recommended: restore test (12.25) then `Authorize release`. Nothing committed, pushed, deployed, restarted, retried or modified.

---
## 12.27 Restore-test evidence (user-supplied, 2026-10-16 ~15:22) [U; I have not seen the output]
| Item | Result |
|---|---|
| Backup restored | `mototrack_public_20261016_145614.dump` (144,288 bytes, `E:\MotoTrackBackups`) |
| Destination | isolated local DB `mototrack_restore_test` at 127.0.0.1, PostgreSQL **18.6** |
| `pg_restore` exit code | **0** |
| Restored public tables | **21** (matches the 21 expected from the migrations, 12.24) |
| `migrations` rows | **51** (matches the 51 migration files) |
| `failed_jobs` rows | **20** (matches production's 20 classified failures) |
| Production | not modified |
**Status: RESTORE-TESTED (basic).** The archive loads end-to-end with exit 0 into a clean database, the schema and all 21 tables are recreated, and three independent anchors agree with what we know about production (table count, migration count, failed-job count). This upgrades 12.24's "readable, unrestored" to "restorable".
**Limitations, stated precisely (none blocks the release; all are recorded, not hidden):**
1. **`public` schema only.** Other Supabase schemas (`auth`, `storage`, `extensions`), roles/grants and project settings are not covered (app data is, because Laravel uses its own `users` table).
2. **PostgreSQL 18.6 destination, 17.6 source.** Proves the archive restores into a newer server; it does not prove a restore into Supabase's own PG 17 (low risk: dump tools were newer than the server, and a 17 target is the supported direction). A real disaster restore should target PG 17.
3. **Complete row-count comparison NOT yet demonstrated.** Only the 21 / 51 / 20 anchors are confirmed. The per-table comparison of the stable tables (`users`, `inventory_items`, `motorcycles`, `part_compatibility`, `customers`, `repairs`, `refund_requests`, `product_returns`, `damaged_items`) against read-only production counts (12.25 pass criterion 2) is **pending your output**. Volatile tables (`sales`, `pre_orders`, `notifications`, `audit_logs`, `sessions`, `cache`, `jobs`) may differ by activity after 14:56.
4. **VPS uploaded files NOT backed up / not demonstrated.** A database dump contains no product images or other uploads. Where they live (`storage/app`, `storage/app/public`, `public/...`) has not been inspected by me. The deploy rsync excludes `/storage/` but its `--delete` applies to the rest of the tree, so anything under `public/` that is not in Git (for example a `public/storage` symlink, which `.gitignore` excludes) is at risk on every deploy, exactly as in past deploys. The uploads tarball in R0 below closes this.
5. **Snapshot time** 14:56:14: later data is not in it (irrelevant to a code rollback).
6. **Privacy:** the local scratch DB holds production customer data. Delete it after the comparison (`dropdb -h 127.0.0.1 -U postgres mototrack_restore_test`); keep the dump file outside Git/cloud sync.

## 12.28 FINAL RELEASE SEQUENCE (nothing here has been run; every step needs your go; stop at any failed check)
**R0. Gates (before anything else)**
a. *Row-count comparison:* run the count query from 12.25 on the restored DB and, read-only, on production; compare the stable tables; paste me the two small tables (counts only). I confirm; then drop the scratch DB.
b. *VPS uploads and code snapshot (root-only files, never downloaded):*
```bash
cd /var/www/mototrack && ls -la public | grep -i storage; du -sh storage/app 2>/dev/null; df -h / | tail -1
sudo tar -czf /root/mototrack-uploads-$(date +%F-%H%M).tar.gz --exclude=public/build --exclude=public/hot -C /var/www/mototrack storage/app public
sudo tar -czf /root/mototrack-code-$(date +%F-%H%M).tar.gz --exclude=mototrack/.env --exclude=mototrack/storage --exclude=mototrack/node_modules --exclude=mototrack/vendor --exclude=mototrack/public -C /var/www mototrack
sudo chmod 600 /root/mototrack-*.tar.gz && sudo ls -lh /root/mototrack-*.tar.gz
```
(If `ls` shows `public/storage` is a symlink, note it: after the deploy re-check it exists, step R6.)
**R1. Re-stage the handoff (last edit made; if I edit it again, repeat)**
```powershell
Set-Location E:\laragon\www\mototrack
git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md
(git diff --cached --name-only | Measure-Object -Line).Lines   # 43
git diff --name-only                                            # nothing
git diff --cached --check                                       # nothing
git branch --show-current                                       # main
```
**R2. Remote Git verification (before committing)**
```powershell
git fetch origin
git rev-parse HEAD origin/main                 # both dcb68c2c8356d284c630064fd2a7587b36400857
git ls-remote origin refs/heads/main           # asks GitHub directly: same SHA
git status -sb                                 # "## main...origin/main", no ahead/behind
```
If `origin/main` moved: STOP, do not push, send me the output.
**R3. Commit (message block in 12.16, includes the attribution trailers), then verify**
```powershell
git commit -F "$env:TEMP\mototrack_commit_msg.txt"
git show --stat --oneline HEAD | Select-Object -First 3
(git show --name-only --format= HEAD | Measure-Object -Line).Lines   # 43
git status --short | Select-String -NotMatch '^\?\?'                   # nothing left staged/modified
$sha = git rev-parse HEAD; $sha
```
**R4. Pre-push gate (VPS and clock, within ~10 min of pushing)**
`sudo supervisorctl status` (queue and reverb RUNNING); SQL `select count(*) from jobs; select count(*) from jobs where reserved_at is not null;` both 0; choose a quiet hour (assets are rebuilt on the VPS for a short time); if more than ~2 h passed since 14:56 and the shop took orders, repeat the `pg_dump`.
**R5. Push (this IS the deployment) and watch**
```powershell
git push origin main
git ls-remote origin refs/heads/main           # must equal $sha
```
GitHub > Actions: job `validate` (tests + build) must go green, then `deploy`. If `validate` is red nothing deploys: stop, send me the log, do not re-push blindly.
**R6. Post-deploy smoke test (2-3 min)** `curl -sI https://mototrack.online` returns 200; hard-refresh (Ctrl+F5): home, catalog with images, login, owner dashboard load with styling; `ls -la /var/www/mototrack/public | grep storage` still shows the symlink if R0b found one (if missing: `sudo -u www-data php artisan storage:link`, then recheck images).
**R7. Reload the root-run queue worker (12.17, as www-data, PID compare).** Expect RELOADED; `select count(*) from failed_jobs;` still 20. Reverb: leave.
**R8. Watch the log 5 minutes:** `sudo tail -f /var/www/mototrack/storage/logs/laravel.log` for errors, `notification failed`, `could not be recorded`, `Low-stock`.
**R9. Live UAT (synthetic/owner-approved data only; record each as a NEW result row; nothing is "passed" until observed)**
1. STK-001: use a UAT test item (not a real product): sale that crosses the threshold gives exactly one owner in-app alert on `/low-stock`; a second sale gives none; restock above threshold then sell below again gives a second alert.
2. Sale deletion: as owner, confirm no delete control exists; the crafted `DELETE` check only against a synthetic UAT sale (expect 405/403, sale intact).
3. RSV-006/007 cancel button (desktop and mobile), PRE-007/008, RES-004/005 (owner UI and customer notifications), RES-001 (cash/change), RES-002/PRE-005.
4. CMP-001/BRW-006/CMPM-001/002 after the owner links parts to motorcycles; SignUp-002/003.
5. Deferred: DASH-005; RES-003/PRE-006/RSV-005 only with an owner-approved expiry method (no manual production expiry commands).
**R10. Abort / rollback triggers:** core pages 500, repeated notification or queue errors, POS sale or reservation broken, worker crash-looping, images missing and not fixed by `storage:link`. Rollback = `git revert --no-edit $sha; git push origin main`, wait for green, repeat R7. Restore the uploads tarball only if files were lost. **Do not restore the database** unless data corruption is proven, and then only after talking to me.
**R11. After:** drop the scratch DB, update the UAT report and this handoff with results, keep the dump and tarballs (root-only) for a few days.

## 12.29 Updated GO/NO-GO and checkpoint (stopped)
Backup: **RESTORE-TESTED (basic)**. Pending, each cheap: R0a per-table comparison; R0b uploads and code snapshot. If you prefer to accept limitations 3 and 4 instead, say so explicitly. Code GO unchanged. Nothing committed, pushed, deployed, restarted, retried or modified; the 43-file staging is untouched except that this handoff must be re-added (R1).

---
## 12.30 Database recovery verification COMPLETE (user-supplied, 2026-10-16 ~15:24) [U; output not seen by me]
| Table | Production | Restored (local) |
|---|---|---|
| inventory_items | 1,306 | 1,306 |
| sales | 45 | 45 |
| users | 21 | 21 |
| pre_orders | 12 | 12 |
| notifications | 39 | 39 |
| product_returns | 1 | 1 |
| migrations | 51 | 51 |
| failed_jobs | 20 | 20 |
Also: `pg_restore` exit 0; 21 public tables restored; dump `E:\MotoTrackBackups\mototrack_public_20261016_145614.dump` (144,288 bytes); restored into `mototrack_restore_test` at 127.0.0.1; source PG 17.6, restore PG 18.6; production not modified.
**Status: RECOVERABLE (restore-tested, 8-table exact match).** Supersedes the "pending comparison" in 12.27 limitation 3 and in 12.29. Identical counts (including `sales` = 45 and `notifications` = 39 at the time of comparison) also show no production activity between the 14:56 snapshot and the comparison.
**Limitations preserved (unchanged, accepted unless stated):**
1. **PostgreSQL versions:** source 17.6, test restore into 18.6. A real recovery should target PG 17 (the supported direction for a dump taken with newer tools).
2. **Non-public schemas not covered:** `auth`, `storage`, `extensions`, roles/grants and Supabase project settings are outside the dump (app data is covered; MotoTrack uses Laravel's own `users`).
3. **External uploaded files NOT covered and NOT yet backed up:** product images and any other uploads live on the VPS, not in the database. The R0b tarballs (12.28) have not been confirmed as taken.
4. **Comparison breadth:** 8 tables compared exactly; not compared: `customers`, `motorcycles`, `part_compatibility`, `repairs`, `refund_requests`, `damaged_items`, `audit_logs` and the framework tables. The restore loaded all 21 tables with exit 0, so this is a thin remaining gap, not a blocker.
5. **Snapshot time:** 14:56:14 PHT; later writes are not in it (irrelevant to the code rollback). If hours pass and orders arrive before the push, re-dump.
6. **Privacy:** delete the scratch DB when finished (`dropdb -h 127.0.0.1 -U postgres mototrack_restore_test`); keep the dump outside Git and cloud sync.

## 12.31 FINAL RELEASE AUTHORIZATION CHECKLIST (tick in order; command details are in 12.28 R0-R11 and 12.17; stop on any failed check)
**A. Preconditions already satisfied [U/V]:** tests 248/793; build OK; `git diff --check` clean; 43 files staged and content-reviewed; no migrations (none pending in prod); queue empty; worker RUNNING with `autorestart=true`, no retry flag; DB backup restore-tested.
**B. Before staging/commit**
- [ ] B1. VPS uploads tarball and code tarball taken, root-only, sizes listed (R0b), `public/storage` symlink noted. *(Only open item; or write "uploads backup waived".)*
- [ ] B2. Scratch restore DB dropped (optional now, mandatory soon).
**C. 1. Re-stage the handoff (this is my last edit unless you send new results)**
- [ ] C1. `git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md`
- [ ] C2. staged count = 43; `git diff --name-only` empty; `git diff --cached --check` empty; branch = `main` (R1).
**D. 2. Final staged-file and remote verification (R2)**
- [ ] D1. `git fetch origin`; `git rev-parse HEAD origin/main` both `dcb68c2c8356d284c630064fd2a7587b36400857`.
- [ ] D2. `git ls-remote origin refs/heads/main` same SHA; `git status -sb` shows no ahead/behind.
- [ ] D3. `git diff --cached --name-only` equals the 43-path list (Compare-Object empty) and no forbidden pattern (12.15).
**E. 3. Release commit and controlled deployment (R3-R5)**
- [ ] E1. Commit with the 12.16 message (trailers included); `git show --stat` = 43 files; nothing left staged.
- [ ] E2. VPS pre-push gate: `jobs` = 0, `reserved` = 0, services RUNNING, quiet hour (R4).
- [ ] E3. **You say "Authorize release" in this chat.** Then `git push origin main` (this deploys); `git ls-remote origin refs/heads/main` equals the new SHA.
- [ ] E4. GitHub Actions: `validate` green, then `deploy` green. Red `validate` = nothing deployed; stop and send me the log.
- [ ] E5. Smoke test: site 200, styling and images load, login, dashboard; `public/storage` symlink intact (R6).
**F. 4. Queue worker reload and PID check (12.17 / R7)**
- [ ] F1. Record old PID; `sudo -u www-data php artisan queue:restart`; wait 15 s; new PID differs and status RUNNING.
- [ ] F2. `failed_jobs` still 20; no root-owned files under `storage/framework/cache`; log tail clean for 5 min (R8).
**G. 5. Live UAT (R9; synthetic/owner-approved data; results as new rows; nothing claimed until observed)**
- [ ] G1. STK-001 one alert per low-stock episode (UAT test item). G2. Sale deletion rejected (synthetic UAT sale only). G3. Cancel button desktop and mobile; PRE-007/008; RES-004/005; RES-001; RES-002/PRE-005. G4. Compatibility set (after the owner links parts) and sign-up. G5. Deferred: DASH-005; expiry cases only with an owner-approved method.
**H. Abort/rollback (R10):** `git revert --no-edit <sha>; git push origin main`, wait green, repeat F. No DB restore unless data corruption is proven.

## 12.32 FINAL GO / NO-GO (2026-10-16 ~15:25)
| Area | Status |
|---|---|
| Code, tests, build, whitespace | GO |
| Staged set (43) and content/security review | GO |
| Migrations / DB schema | GO (none) |
| Queue state and reload mechanism | GO |
| Database recoverability | **GO** (restore-tested, 8-table exact match; versions/schemas caveats recorded) |
| Rollback (code) | GO |
| VPS uploads/code snapshot | **OPEN** (strongly recommended before push; waive explicitly if you choose) |
| Handoff re-stage (C1-C2) | PENDING (mechanical) |
**Overall: GO to release, conditional only on (1) B1 done or explicitly waived, (2) C and D checks passing on your machine, (3) your explicit `Authorize release`.** NO-GO if: `origin/main` moved; any D check differs; `validate` red; jobs table non-empty with reserved rows at push time; or the worker reload cannot be confirmed.
**Checkpoint (stopped):** nothing committed, pushed, deployed, restarted, retried or modified by me.

---
## 12.33 Deployment-safety correction: product images and runtime uploads (2026-10-16 ~15:46)
**VPS backup evidence [U; not seen by me]:** tar creation exit 0; archive inspection exit 0; size 335 MB; 1,335 product-image directory entries in the archive; stored outside the deploy target in `/root/mototrack-release-backups/`. Closes R0b (12.28) and open item B1 (12.31). Restoring it only needs `tar -xzf` into `/var/www/mototrack` (exclude `.env` and `storage`).

### Findings (read-only; sources: app code, `.git/index`, deploy.yml)
1. **How images are stored [V from code].** Two mechanisms coexist, resolved in `InventoryItem::getImageUrlAttribute()` in this order: (a) `public/images/<image_path>`, (b) `public/<image_path>`, (c) Laravel `public` disk (`storage/app/public/<image_path>`, served through the `public/storage` symlink), (d) fallback URL. **New owner uploads** (`AdminInventoryController` store/update: `->store('products','public')`) go to **`storage/app/public/products/`**, not `public/images/products/`.
2. **`public/images/products/` is Git-tracked [V].** The Git index lists **1,334 tracked files** there (all other tracked `public/` files: `.htaccess`, `build/manifest.json`, `css/landing.css`, `favicon.ico`, logo/hero/about images, `index.php`, `js/landing.js`, `manifest.json`, `reset.php`, `robots.txt`, `tech_stack.html`). Your archive shows 1,335 entries; that equals 1,334 files + the directory entry, which suggests production's folder currently equals the tracked set. **This is a coincidence of counts, not proof**: it can also be 1,334 files plus one production-only file. Check (read-only, VPS): `find /var/www/mototrack/public/images/products -type f | wc -l` (expect 1334; any larger number means production-only uploads exist) and locally `git ls-files public/images/products | Measure-Object -Line`.
3. **Workflow behaviour [V].** The rsync step is `ARGS: "-rltgoDzvO --delete"` with `EXCLUDE: "/.git/, /.github/, /node_modules/, /.env, /storage/, /tests/"`. **Note: the file on your PC is byte-identical (same Git blob SHA) to the committed/deployed version and does NOT contain `-rlgoDzvc`.** That string is the action's README *default* (`-rlgoDzvc -i`), so it was probably read from the action's documentation. Whichever you saw, the conclusion is the same: `--delete` is on and the exclusions do not cover `public/`.
   Consequences of `--delete` with anything not in Git and not excluded: (i) any **production-only file in `public/images/products/`** is deleted at every deploy; (ii) the **`public/storage` symlink** (gitignored, so absent from the source) is deleted, which would break every image served from `storage/app/public/` (all new owner uploads) until `php artisan storage:link` is run; the deploy script never runs it. Excluded entries are also protected from deletion (rsync `--delete` does not remove excluded paths unless `--delete-excluded`, which is not used).
4. **Other runtime locations [V from code grep + config; VPS contents unverified]:** `storage/` (logs, sessions, file cache, `storage/app/public` uploads, `storage/app/private`): **already excluded**. `.env`: excluded. `bootstrap/cache/*` and `public/build/*`: regenerated on every deploy (safe to replace). No other code path writes to `public/` or the project tree (no `public_path()` writes, `move()`, `file_put_contents` in `app/`, `routes/`, `config/`). Unknown to me and worth a 10-second check: anything an admin put on the VPS by hand (e.g. certbot `public/.well-known/`, other uploads, scripts). Read-only listing of unexpected entries:
```bash
cd /var/www/mototrack/public && ls -A | grep -vxE '\.htaccess|build|css|favicon\.ico|images|index\.php|js|manifest\.json|reset\.php|robots\.txt|tech_stack\.html|storage'
ls -A images | grep -vxE 'about\.(jpg|png|webp)|hero\.(jpg|png|webp)|logo-(dark|light)\.svg|logo\.png|products'
ls -la /var/www/mototrack/public | grep -i storage
```
Any name printed by the first two commands is production-only and is deleted by the current workflow; send it to me and I will add it to the exclusions.

### Decision and exact workflow change (prepared, NOT applied)
`deploy.yml` is a **protected file for my tools** (write rejected), so I could not edit it on your PC. The change is one setting, so apply it by hand (below). Only `EXCLUDE` changes; `ARGS` stays `-rltgoDzvO --delete`:
```yaml
          # Runtime/production-only paths must survive "--delete":
          #   /storage/                 logs, sessions, cache and admin uploads (storage/app/public)
          #   /public/images/products/  production product photos (not re-synced from Git)
          #   /public/storage           symlink to storage/app/public (not in Git, so --delete would remove it)
          # Exclude entries are also protected from deletion on the server (no --delete-excluded).
          EXCLUDE: "/.git/, /.github/, /node_modules/, /.env, /storage/, /tests/, /public/images/products/, /public/storage"
```
Apply with PowerShell from the repo root (LF-safe, no BOM, aborts if the old line is not found):
```powershell
$f   = (Resolve-Path '.github\workflows\deploy.yml').Path
$t   = [IO.File]::ReadAllText($f)
$old = '          EXCLUDE: "/.git/, /.github/, /node_modules/, /.env, /storage/, /tests/"'
if (-not $t.Contains($old)) { throw 'Old EXCLUDE line not found; do not continue' }
$new = ("          # Runtime/production-only paths must survive `"--delete`":`n" +
        "          #   /storage/                 logs, sessions, cache and admin uploads (storage/app/public)`n" +
        "          #   /public/images/products/  production product photos (not re-synced from Git)`n" +
        "          #   /public/storage           symlink to storage/app/public (not in Git, so --delete would remove it)`n" +
        "          # Exclude entries are also protected from deletion on the server (no --delete-excluded).`n" +
        '          EXCLUDE: "/.git/, /.github/, /node_modules/, /.env, /storage/, /tests/, /public/images/products/, /public/storage"')
[IO.File]::WriteAllText($f, $t.Replace($old, $new), (New-Object Text.UTF8Encoding $false))
git diff --stat -- .github/workflows/deploy.yml     # 1 file, 6 insertions(+), 1 deletion(-)
git diff -- .github/workflows/deploy.yml            # review: only the comment lines and the EXCLUDE line
```
Then tell me; I will stage the file from your PC and compare it byte-for-byte to my prepared version (YAML parses; `EXCLUDE` splits into the 8 expected entries).

### Why this form, trade-offs
- **Why `EXCLUDE`:** it is the mechanism the workflow already uses (`/storage/` etc.), so no new syntax is passed through the unpinned `ssh-deploy@main` action. A failed filter would abort the deploy step; an extra exclude cannot.
- **Why `/public/storage` has no trailing slash:** it is a symlink on the server; a trailing-slash pattern only matches directories and would not protect it.
- **Cost 1:** `/public/images/products/` is excluded from transfer as well as from deletion, so **product photos added or changed in Git later will not deploy** (they would need to be copied by hand or the rule changed). Nothing in this release touches `public/` (none of the 43 files is under `public/`), and production already has the tracked set, so this release is unaffected. Static assets elsewhere (logos, hero, `landing.css/js`, `manifest.json`, `build/`) still deploy.
- **Alternative, if you want both protection and deployment of new tracked photos:** rsync "protect" rules (`--filter=P_/public/images/products/***` in `ARGS`) protect from deletion but still transfer. I did not use it because I cannot verify how the action passes `ARGS` to the shell and had no rsync available to test it. Consider it later, after testing with `rsync -n` on a scratch directory.
- **Cost 2:** the same push that carries this change is deployed by the new workflow (Actions use the workflow file of the pushed commit), so it takes effect on this very deploy. That is intended.
- **Push permission:** pushing a commit that changes `.github/workflows/*` needs a GitHub credential with the `workflow` scope. If GitHub rejects the push ("refusing to allow ... without `workflow` scope"), nothing was deployed; refresh the credential (Git Credential Manager sign-in or a token with `workflow`) and push again.

### Side observations (not changed, not blocking)
- `public/reset.php` is tracked and public: an unauthenticated URL that runs `optimize:clear` and `opcache_reset`. It cannot read data but anyone can flush your caches (including the file-cache queue-restart key). Recommend removing it or protecting it in a later change.
- `npm install` (not `npm ci`) on the VPS and the unpinned `easingthemes/ssh-deploy@main` remain as in 12.4.

### Release staging and verification: now 44 FILES (supersedes every "43" in 12.15, 12.28 R1/R3, 12.31 and 12.32)
The 43 reviewed files plus **`.github/workflows/deploy.yml` (modified, tracked)** = **44**. Expected `--name-status`: **24 M + 20 A**. Add this after applying the edit:
```powershell
git add -- .github/workflows/deploy.yml
(git diff --cached --name-only | Measure-Object -Line).Lines            # 44
git diff --cached --name-only | Select-String '^\.github/'             # exactly: .github/workflows/deploy.yml
git diff --cached --name-status | Select-String 'deploy.yml'           # M   .github/workflows/deploy.yml
git diff --cached -- .github/workflows/deploy.yml                      # 1 file; only the comment lines + EXCLUDE line
git diff --name-only                                                   # nothing (handoff and workflow both re-added)
```
In the forbidden-path check of 12.15 the `\.github` term must now permit this single path: `$bad = $staged | Where-Object { $_ -ne '.github/workflows/deploy.yml' } | Select-String -Pattern '(^|/)\.env|\.cjs$|\.sql$|\.zip$|\.tar|\.gz$|\.log$|\.xml$|\.bak$|\.png$|vps|deploy_|migrations|node_modules|vendor/|\.github'`. The commit message (12.16) gains one bullet: `- Deploy workflow: exclude /public/images/products/ and /public/storage from rsync --delete so production images survive deployments`. `git show --stat HEAD` must then list **44** files.

### Extra checks around the deploy (add to R4/R6)
- **R4 (before push), VPS read-only:** the three listings above; `find public/images/products -type f | wc -l`; confirm `/root/mototrack-release-backups/` archive listing is intact (`tar -tzf <archive> | head -3`, exit 0).
- **R6 (after deploy):** same file count in `public/images/products` as before the push; `ls -la public | grep storage` still shows the symlink; open the shop catalog and one product detail in a browser (hard refresh) and confirm images render; open a product that was uploaded by the owner (served via `/storage/products/...`) if one exists. If images are missing: do **not** re-run the deploy; restore from `/root/mototrack-release-backups/` (extract only `public/images/products` or `public/storage`), or `sudo -u www-data php artisan storage:link` for the symlink.

### Updated GO/NO-GO
Backup of VPS files: **DONE** (335 MB, verified listing). Deployment safety for images: **CORRECTION PREPARED, NOT YET APPLIED** (blocker until you apply it and the 44-file checks pass; do not push the old workflow). All other statuses unchanged. **Checkpoint: stopped. Nothing committed, pushed, deployed, restarted or modified in production; `deploy.yml` on your PC is unchanged.**

---
## 12.34 Security correction: remove `public/reset.php` (2026-10-16 ~15:58)
### 1. Finding
`public/reset.php` is Git-tracked (index entry present) and is served by the web server like any file in `public/`. On request it boots the Laravel HTTP kernel, then runs `optimize:clear` and `view:clear`, calls `opcache_reset()` when available, and echoes status text, including `$e->getMessage()` if clearing fails. **There is no authentication, authorization, secret token, IP check or method check.** Anyone who knows or guesses `/reset.php` can trigger it repeatedly.
Impact [V from the file; production exposure assumed because it is tracked and was deployed]: no data read or write and no credential exposure, but (a) anyone can force repeated cache, route/config/view-cache and OPcache flushes (performance degradation / cheap denial of service); (b) `optimize:clear` flushes the **file cache**, which deletes the `illuminate:queue:restart` key and can disturb cache-backed state; (c) error text can disclose internal exception messages. Severity: **medium-low, pre-deployment security issue per your instruction.** It is not an emergency requiring a production change before the approved deploy: the approved deployment removes it (below). Do not request the URL to "test" it.

### 2. Dependency check: nothing depends on it
Verified by search in the cloud source tree (`app/`, `routes/`, `resources/`, `config/`, `tests/`, `database/`, `composer.json`, `package.json`, `vite.config.js`) and in `.github/workflows/deploy.yml`: **no reference to `reset.php`**. The deploy script already clears caches itself (`sudo -u www-data php artisan optimize:clear` / `config:cache` / `route:cache` / `view:cache`) and no test requests it (the only "reset" hits are Laravel's `/reset-password` routes, which are unrelated). The cloud tree does not contain your root-level scratch scripts or the server config, so **three checks remain yours (read-only, report file names and counts only, not line contents, because scratch scripts may contain secrets):**
```powershell
# Local, repo root
git grep -n "reset.php"                                              # tracked files; expect only public/reset.php itself or nothing
Select-String -Path .\*.cjs,.\*.bat,.\*.php,.\*.md,.\*.txt,.\*.ps1 -Pattern 'reset\.php' | Select-Object Path, LineNumber
```
```bash
# VPS, read-only
sudo grep -rIlF "reset.php" /etc/nginx /etc/php /etc/supervisor /etc/cron.d /etc/cron.daily /var/spool/cron 2>/dev/null
(sudo crontab -l; sudo -u www-data crontab -l) 2>/dev/null | grep -nF "reset.php"
sudo grep -cF "reset.php" /var/log/nginx/access.log /var/log/nginx/access.log.1 2>/dev/null   # counts only: shows whether anything ever requested it
ls -la /var/www/mototrack/public/reset.php                            # present BEFORE the deploy (expected)
```
Any hit means a script, cron job or proxy rule expects the file: tell me before removing it. A non-zero access-log count tells you whether it was requested (by you, a monitoring tool or a bot).

### 3. Removal and the new expected release count: **45 files (24 M + 20 A + 1 D)**
I cannot delete files on your PC (no shell), so the removal is a command for you; it stages the deletion in one step:
```powershell
git rm -- public/reset.php
Test-Path public\reset.php                                           # False
git diff --cached --name-status | Select-String 'reset.php'          # D   public/reset.php
```
Count history: 43 reviewed files (23 M + 20 A) -> +`.github/workflows/deploy.yml` (M) = 44 (24 M + 20 A) -> +`public/reset.php` (D) = **45 (24 M + 20 A + 1 D)**. This supersedes every "43" and "44" in 12.15, 12.21, 12.28 (R1/R3), 12.31, 12.32 and 12.33. Full verification of the final staged set (re-run block A of 12.15 up to the `$files = @(...)` definition only if `$files` is no longer defined; no extra `git add` is needed for it):
```powershell
$expected = @($files) + '.github/workflows/deploy.yml' + 'public/reset.php'
$staged   = @(git diff --cached --name-only)
"Staged count: $($staged.Count)"                                       # 45
Compare-Object ($expected | Sort-Object) ($staged | Sort-Object)       # prints NOTHING
git diff --cached --name-status | ForEach-Object { ($_ -split '\s+')[0] } | Group-Object | Select-Object Name, Count   # A 20, D 1, M 24
$bad = $staged | Where-Object { $_ -notin '.github/workflows/deploy.yml','public/reset.php' } |
       Select-String -Pattern '(^|/)\.env|\.cjs$|\.sql$|\.zip$|\.tar|\.gz$|\.log$|\.xml$|\.bak$|\.png$|vps|deploy_|migrations|node_modules|vendor/|\.github'
if ($bad) { 'FORBIDDEN PATH STAGED:'; $bad } else { 'No forbidden paths staged' }
git diff --cached --check                                              # nothing
git diff --name-only                                                   # nothing (handoff, workflow re-added)
git diff --cached -- .github/workflows/deploy.yml                      # only the comments + EXCLUDE line (image protection preserved)
```
`git show --stat HEAD` after the commit must list **45** files with `public/reset.php` shown as a deletion. Commit message (12.16) gains: `- Security: remove public/reset.php, an unauthenticated cache/OPcache reset endpoint` (plus the deploy-workflow bullet from 12.33). Re-add this handoff last (`git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md`) because it changes again.
No code or test is affected by the deletion (nothing references the file), so the 248 / 793 result and the Vite build stay valid; CI `validate` runs on the new commit anyway.

### 4. rsync `--delete` should remove it from production (rule analysis, to be confirmed on the server)
The workflow runs `rsync -rltgoDzvO --delete` from the checkout to `/var/www/mototrack`. After the commit, the source has no `public/reset.php`; the destination has it. Its path is **not** matched by any exclude (`/.git/ /.github/ /node_modules/ /.env /storage/ /tests/ /public/images/products/ /public/storage`), and excluded-path protection is the only thing that stops `--delete`. So the file is expected to be **deleted by the approved deploy**, with no separate server action. This only happens if the `deploy` job actually reaches the rsync step (a red `validate` means nothing changes and the file stays). The image-protection excludes from 12.33 are unchanged by this correction and **do not** cover `reset.php`.

### 5. Required post-deploy verification, WITHOUT requesting the URL
Add to R6 (12.28), VPS shell, read-only:
```bash
test -e /var/www/mototrack/public/reset.php && echo "PRESENT - STOP, report to Claude" || echo "ABSENT - OK"
ls -la /var/www/mototrack/public | grep -i reset || echo "no reset* entry in public/"
sudo find /var/www/mototrack -name 'reset.php' -not -path '*/vendor/*' -not -path '*/node_modules/*' 2>/dev/null   # prints nothing
```
Do **not** `curl`/browse `/reset.php` to prove it: a request to a still-present file would execute it. If it is PRESENT after a green deploy: do not request it; tell me. Removing it by hand (`sudo rm /var/www/mototrack/public/reset.php`) is a production change that needs your explicit authorization; likely cause would be a failed or skipped rsync step. Record the ABSENT result in the UAT report.
Also keep your pre-deploy access-log count (section 2) for the record; compare post-deploy counts only if you suspect abuse (`grep -cF` again, counts only).

## 12.35 Updated GO/NO-GO (as of ~16:00)
| Gate | Status |
|---|---|
| Code, tests (248/793), build, `git diff --check` | GO [U/V] |
| Migrations / DB recoverability / VPS files backup | GO (restore-tested DB; 335 MB VPS archive verified) |
| Queue state, reload method, rollback | GO |
| `deploy.yml` image protection | **PREPARED, NOT YET APPLIED** (12.33): blocker until applied and diff-reviewed |
| `public/reset.php` removal | **NOT YET DONE** (`git rm`): blocker until staged as `D` |
| Dependency checks (local scratch + VPS config/cron/logs) | PENDING your outputs (names/counts only) |
| Staged set = **45** (24 M, 20 A, 1 D), content-reviewed except the two new items | PENDING your verification block; the workflow diff and the deletion are small and reviewed above |
**Overall: GO for the release once** (1) the workflow edit is applied, (2) `public/reset.php` is removed and the 45-file checks pass, (3) the dependency checks show no consumers, (4) the 12.33 VPS listings show no unexpected production-only files, (5) `origin/main` is still `dcb68c2`, and (6) you reply `Authorize release`. **NO-GO if** any dependency hit appears for `reset.php`, the staged set differs from the 45 expected paths, the push is rejected for missing `workflow` scope (nothing deploys; fix credentials), or `validate` is red.
**Checkpoint (stopped):** nothing committed, pushed, deployed, restarted or modified in production; `deploy.yml` and `public/reset.php` on your PC are untouched by me.

---
## 12.36 FINAL CHECKPOINT BEFORE USAGE RESET (2026-10-16 ~16:05). RESUME FROM HERE.
### A. Final verified results [U unless marked V]
- Laravel **248 passed / 793 assertions / 0 failures in 16.51 s**; Git: **45 staged = 24 M + 20 A + 1 D**; `git diff --cached --check` passed; `git diff --name-only` empty; `public/reset.php` deleted with `git rm` (unauthenticated cache/OPcache reset; no dependencies found, only documentation references); `public/images/products/` + `public/storage` rsync protection staged in `deploy.yml`; Supabase backup restored locally, **8/8 table counts match production**; VPS archive verified, includes the 1,334 legacy product images; `origin/main` = `dcb68c2` at last fetch. Nothing committed, pushed or deployed.
- **[V by me, from your repo files] `public/reset.php` is no longer in the Git index. The staged `deploy.yml` is identical to the working file.**

### B. BLOCKER FOUND IN THE STAGED WORKFLOW (fix before anything else)
**The `EXCLUDE:` line in `.github/workflows/deploy.yml` is indented with 9 spaces; its siblings (`ARGS`, `SOURCE`, `REMOTE_HOST`, `REMOTE_USER`, `TARGET`) use 10. This is invalid YAML** (a YAML parser fails at line 70: "expected block end"). The exclude string itself is correct and everything else in the file is unchanged. Impact if pushed: GitHub rejects the workflow file, no `validate`/`deploy` job runs, **nothing is deployed** (safe failure, but a wasted push and a commit that must be fixed). Fix it now, locally (my tools cannot edit this protected file):
```powershell
Set-Location E:\laragon\www\mototrack
$f = (Resolve-Path '.github\workflows\deploy.yml').Path
$t = [IO.File]::ReadAllText($f)
$bad  = "`n" + (' ' * 9)  + 'EXCLUDE: "/.git/'
$good = "`n" + (' ' * 10) + 'EXCLUDE: "/.git/'
if (-not $t.Contains($bad)) { throw '9-space EXCLUDE line not found; do not continue' }
[IO.File]::WriteAllText($f, $t.Replace($bad, $good), (New-Object Text.UTF8Encoding $false))
(Select-String -Path $f -Pattern '^ {10}(ARGS|SOURCE|TARGET|EXCLUDE):').Count     # must be 4
git add -- .github/workflows/deploy.yml
git diff --cached -- .github/workflows/deploy.yml     # EXCLUDE line = old line + ", /public/images/products/, /public/storage", 10-space indent
```
Then tell me (or in the next session ask me to "verify the workflow"): I will re-stage the file, parse it as YAML, and confirm `ARGS` is unchanged and `EXCLUDE` splits into the 8 expected entries.

### C. EXACT NEXT STEPS (in order; stop at the first failed check; nothing runs without your separate `Authorize release`)
1. **Fix the workflow indentation (section B), then re-add this handoff:** `git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md`.
2. **Re-run the 45-file verification block in 12.34 (section 3):** count 45; `Compare-Object` empty; status mix A 20 / D 1 / M 24; no forbidden path; `--cached --check` empty; `git diff --name-only` empty.
3. **Dependency and exposure checks (names/counts only):** local `git grep -n "reset.php"` and the scratch-script `Select-String` (12.34 section 2); VPS grep of `/etc/nginx /etc/supervisor /etc/cron*`, crontabs, and the access-log count.
4. **VPS pre-push baselines (record the values; read-only):**
```bash
cd /var/www/mototrack
find public/images/products -type f | wc -l                          # expect 1334
find public/images/products -type f | sort | sha256sum              # record the hash
ls -la public | grep -i storage; ls -la public/reset.php            # note symlink state; reset.php present
ls -A public | grep -vxE '\.htaccess|build|css|favicon\.ico|images|index\.php|js|manifest\.json|reset\.php|robots\.txt|tech_stack\.html|storage'   # nothing, or send me the names
sudo supervisorctl status; sudo supervisorctl pid mototrack-queue    # record PID
```
```sql
select count(*) from jobs;  select count(*) from jobs where reserved_at is not null;   -- 0 and 0
select count(*) as failed, max(failed_at) as latest_failed from failed_jobs;            -- 20 and the baseline timestamp
```
5. **Remote Git verification:** `git fetch origin; git rev-parse HEAD origin/main` (both `dcb68c2c8356d284c630064fd2a7587b36400857`); `git ls-remote origin refs/heads/main` same; `git status -sb` no ahead/behind. If it moved: STOP.
6. **Commit** from the 12.16 message file, with these bullets present: sale-deletion protection and synchronous low-stock alerts (original), `Deploy workflow: exclude /public/images/products/ and /public/storage from rsync --delete`, `Security: remove public/reset.php (unauthenticated cache/OPcache reset endpoint)`, and the two trailer lines. Verify: `git show --stat --oneline HEAD` lists **45** files (reset.php as a deletion); `git status --short` shows nothing staged.
7. **Say `Authorize release` in a new message.** Only then: `git push origin main` (this deploys); `git ls-remote origin refs/heads/main` equals your commit SHA; if GitHub rejects the push for the `workflow` scope, nothing deployed: refresh the credential and push again. Watch Actions: `validate` green, then `deploy` green. Red `validate` = nothing deployed.
8. **Post-deploy verification (all must pass; details below), then live UAT.** Rollback = `git revert --no-edit <sha>; git push origin main`, then repeat the worker reload; use the VPS archives only for lost files; no DB restore unless data corruption is proven.

### D. POST-DEPLOY VERIFICATION CHECKLIST (VPS read-only unless marked; do NOT request /reset.php)
1. **`reset.php` gone from production:** `test -e /var/www/mototrack/public/reset.php && echo "PRESENT - STOP" || echo "ABSENT - OK"`; `sudo find /var/www/mototrack -name reset.php -not -path '*/vendor/*' -not -path '*/node_modules/*'` prints nothing. If present: do not browse it; tell me; removal by hand needs your authorization.
2. **All 1,334 legacy product images intact:** `find public/images/products -type f | wc -l` = **1334** and `find ... | sort | sha256sum` equals the pre-push hash (step C4). Then open the shop catalog and one product page in a browser (Ctrl+F5): images render, none 404.
3. **`public/storage` symlink verified or created BEFORE owner-upload UAT:** `ls -la /var/www/mototrack/public/storage` must show a symlink to `/var/www/mototrack/storage/app/public`. (It is excluded from `--delete`; it should have survived.) If absent or broken, create it only with your explicit go-ahead: `cd /var/www/mototrack && sudo -u www-data php artisan storage:link`. Then `ls -la public/storage`, `sudo -u www-data test -w storage/app/public && echo writable`. Only after this passes, run the owner image-upload UAT (upload a photo to a UAT test item, confirm it renders at `/storage/products/...`, then clean up the test item).
4. **Queue worker reloads gracefully; PID changes (12.17):** record old PID, `sudo -u www-data php artisan queue:restart`, wait 15 s, new PID differs and status RUNNING; `sudo find storage/framework/cache -user root | head -5` prints nothing. Fallback `sudo supervisorctl restart mototrack-queue` only if the PID did not change AND `reserved_at is not null` count is 0. Never `queue:retry`/`queue:flush`.
5. **No new failed jobs beyond the baseline of 20:** `select count(*), max(failed_at) from failed_jobs;` = **20** and the same latest timestamp as the pre-push baseline (re-check after the UAT run and after ~30 min). Do not retry or delete failed jobs. Tail `storage/logs/laravel.log` for 5 min (`notification failed`, `could not be recorded`, `Low-stock`, exceptions).
6. **Functional UAT (synthetic/owner-approved data; record each as a NEW result row; nothing is "passed" until observed):**
   - **Customer cancellation:** reservation cancel button desktop + mobile, multi-item cancel releases stock, pre-order cancel; status shows Cancelled (not Expired).
   - **Notifications:** customer and owner in-app notifications appear for ready/cancel/refund events; pre-order 24 h deadline starts only after a stored notification; bell/badge works even if Reverb is flaky.
   - **Compatibility:** fitment search correct for the TMX 125/155 case after the owner links parts; no false matches.
   - **Inventory:** add/edit item, stock adjustment, damaged item and return paths; **STK-001: exactly one low-stock alert per episode**, none on the next sale, a second alert after restock then drop.
   - **Cash POS:** sale with cash tendered, change computed correctly, stock decrements, receipt/history correct.
   - **Refunds:** customer request, owner approve, restock, audit entry, revenue effect; crafted `DELETE /sales/{id}` rejected (405/403), run only against a synthetic UAT sale.
   - Deferred: DASH-005; expiry cases (RES-003/PRE-006/RSV-005) only with an owner-approved method (no manual production expiry commands).

### E. GO / NO-GO (final, pending your next results)
| Gate | Status |
|---|---|
| Tests 248/793, build, whitespace | GO |
| 45-file staged set (24 M / 20 A / 1 D) | GO, re-verify after the workflow fix |
| Migrations, DB backup (restore-tested), VPS files backup | GO |
| Queue state, reload method, rollback | GO |
| `reset.php` removal staged | GO |
| **`deploy.yml` indentation** | **BLOCKER: must fix (section B) before commit** |
| Dependency/exposure checks, VPS pre-push baselines | PENDING (step C3-C4) |
**Overall: CONDITIONAL GO.** Not releasable until section B is fixed and re-verified. NO-GO also if `origin/main` moved, any forbidden or unexpected path is staged, a `reset.php` dependency appears, `validate` is red, or the worker reload / baselines cannot be confirmed.

### F. Standing rules (unchanged) and where to resume
No commit, push, deploy, service restart, failed-job retry or production data change without your explicit authorization; no `.env`/credentials/dumps to the cloud; do not request `/reset.php`; no live-UAT pass claimed until observed. Handoff location: `E:\laragon\www\mototrack\docs\MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md` (this section is 12.36). Resume phrase: **"Continue MotoTrack"**, then paste the output of section C steps 1-4 and ask me to verify the workflow.

---
## 12.37 STATUS CORRECTION AND SAVED CHECKPOINT (authoritative; supersedes earlier "pending/instruction" wording) (2026-10-16 ~16:05)
### A. Confirmed by you in Windows PowerShell [U] (DONE: do not repeat)
1. `git rm -- public/reset.php` **already executed successfully.** (Where 12.34 section 3 says "run `git rm`", that step is COMPLETE.) 2. `git diff --cached --name-status` shows `D public/reset.php`. 3. `.github/workflows/deploy.yml` with the product-image and `public/storage` protection is **staged**. 4. **45 staged files = 24 M + 20 A + 1 D.** 5. `git diff --cached --check` passed. 6. `git diff --name-only` showed no unstaged tracked changes at that time. 7. `php artisan test`: 248 passed / 793 assertions / 0 failures / 16.51 s. 8. `git grep`: no application dependency on `reset.php` (documentation references only). 9. Supabase backup restored into an isolated local PostgreSQL, 8/8 compared table counts match production. 10. VPS application backup created and verified: 335 MB, includes the 1,334 legacy product images (stored under `/root/mototrack-release-backups/`).
### B. One item that is NOT a repeat and is still OPEN [V by me from the staged file]
`deploy.yml` line 70: the `EXCLUDE:` key is indented **9 spaces, siblings use 10, which is invalid YAML** (details and the fix script: 12.36 section B). Your list above says the protection is staged but does not say the indentation was corrected; as of my last read of the file it was not. After you fix and `git add` it, the staged set is still 45. Ask me to "verify the workflow" next session (I re-stage it, parse the YAML, confirm 8 EXCLUDE entries and unchanged `ARGS`). Until then this is the single code-side release blocker.
### C. Remaining READ-ONLY checks (only these; completed ones are excluded)
Report file names / counts only, never line contents of scratch scripts.
- **Scratch scripts (local):** `Select-String -Path .\*.cjs,.\*.bat,.\*.php,.\*.md,.\*.txt,.\*.ps1 -Pattern 'reset\.php' | Select-Object Path, LineNumber` (`git grep` is already done).
- **Nginx / Supervisor / cron / logs (VPS):** `sudo grep -rIlF "reset.php" /etc/nginx /etc/php /etc/supervisor /etc/cron.d /etc/cron.daily /var/spool/cron 2>/dev/null`; `(sudo crontab -l; sudo -u www-data crontab -l) 2>/dev/null | grep -nF "reset.php"`; `sudo grep -cF "reset.php" /var/log/nginx/access.log /var/log/nginx/access.log.1 2>/dev/null` (count only); `ls -la /var/www/mototrack/public/reset.php` (expected present before deploy). Any hit in nginx/supervisor/cron: tell me before committing.
- **VPS pre-push baselines** (12.36 section C step 4: image count 1334 + sha256 of the sorted file list, `public/storage` state, unexpected `public/` entries, queue PID, `jobs` 0/0, `failed_jobs` = 20 with its latest `failed_at`).
- **Remote Git** (12.36 step C5) immediately before committing.
### D. Handoff re-staging requirement
This handoff changes every time I save it. **Before committing, re-stage it:** `git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md`, then confirm the staged count is still **45**, `git diff --name-only` is empty and `git diff --cached --check` is empty (12.34 section 3 verification block). The staged copy currently in your index is an older version of this file.
### E. Workflow state: NOT YET STARTED
| Step | Status |
|---|---|
| Fix `deploy.yml` indentation + verify | **OPEN (blocker)** |
| Re-stage this handoff; re-run 45-file verification | NOT YET STARTED |
| Dependency/exposure checks (section C) and VPS baselines | NOT YET STARTED |
| `git fetch` / remote verification | NOT YET STARTED |
| **Commit** | **NOT YET STARTED** |
| **Push** | **NOT YET STARTED (requires your separate "Authorize release")** |
| **Deployment (GitHub Actions)** | **NOT YET STARTED** |
| **Queue worker reload and PID check** | **NOT YET STARTED** |
| **Post-deploy verification (12.36 section D)** | **NOT YET STARTED** |
| **Live UAT** | **NOT YET STARTED** |
### F. Preserved requirements
Release set = the **45 files** (24 M, 20 A, 1 D); no `git add -A`/`.`; no commit, push, deploy, restart, failed-job retry or production data change without your explicit authorization (release authorization is given separately in its own message); do not request `/reset.php`; no `.env`/credentials/dumps to the cloud; no live-UAT pass claimed until observed. GO/NO-GO: **conditional GO**, blocked only by section B and the section C read-only checks. Resume phrase: **"Continue MotoTrack"**.
**Nothing was audited, tested, modified, committed, pushed, deployed or restarted in this update; only this handoff file was edited.**

---
## 12.38 FINAL GO/NO-GO AND RELEASE-DAY CARD (2026-10-18 ~18:50 PHT). AUTHORITATIVE; supersedes 12.37 B and "blocker" wording elsewhere.
### A. Results you reported [U], all DONE
248 tests / 793 assertions; Vite production build PASSED; 45 staged (24 M, 20 A, 1 D); `public/reset.php` deletion staged; image/storage rsync protection staged; `deploy.yml` indentation corrected; YAML validation PASSED; `git diff --cached --check` PASSED; no unstaged tracked changes at last check; local `main` = `origin/main` = GitHub remote = `dcb68c2`; DB backup restored locally, 8/8 counts match; VPS backup verified incl. 1,334 product images; `mototrack-queue` and `mototrack-reverb` RUNNING; failed jobs = 20 (historical baseline).
### B. Independently verified by me just now [V; read-only on your repo files, nothing rerun]
- `deploy.yml` (working file = staged blob): **valid YAML**; `ARGS` unchanged `-rltgoDzvO --delete`; `EXCLUDE` = `/.git/, /.github/, /node_modules/, /.env, /storage/, /tests/, /public/images/products/, /public/storage` (8 entries); the only change from the deployed version is the `EXCLUDE` value; LF endings. **The earlier indentation blocker (12.36 B) is CLOSED.**
- `public/reset.php` is **not in the Git index**; index entry count fell by exactly one (3,185 to 3,184); `HEAD` = `main` = `origin/main` = `dcb68c2` (local refs as of last fetch; GitHub remote per your report).
- **42 of the 43 originally reviewed content blobs are byte-identical to what I reviewed** (code, tests, reports, 8 other docs). The one that differs is this handoff, as expected: **the staged copy of this file is older than the saved one, and I am changing it again now, so it MUST be re-added before committing** (C1 below).
### C. The intended release: 45 files (unchanged)
| Group | Count | Status |
|---|---|---|
| Application code (20 M + 4 A) | 24 | `routes/modules/sales.php`, `SalePolicy`, `SmsService`, `CheckLowStockAlertJob`, `CommitOrder`, `RefundController`, `ExpirePreOrders`, `InventoryAdjustmentController`, `InventoryService`, `customer/dashboard.blade.php`, `StatusUpdated`, `User`, `ReservationService`, `PreOrderCancellationService`, `AdminPreOrderController`, `PublicBrowseController`, `inventory/compatibility.blade.php`, `RegisteredUserController`, `ProfileUpdateRequest`, `SaleController` (M) + `NotificationDispatcher`, `CompatibilityMatcher`, `LowStockAlertService`, `SendLowStockSmsJob` (A) |
| Tests (3 M + 5 A) | 8 | `RegistrationTest`, `InventoryAdjustmentTest`, `LowStockPanelTest` (M) + `CancelUiAndNotificationDeliveryTest`, `CompatibilityAccuracyTest`, `ReservationPickupTest`, `SaleDeletionProtectionTest`, `LowStockSynchronousAlertTest` (A) |
| Root reports (A) | 2 | `MOTOTRACK_PRODUCTION_UAT_FINAL_REPORT.md`, `MOTOTRACK_PRODUCTION_UAT_FIX_PROGRESS.md` |
| `docs/` (A) | 9 | 8 analysis/decision reports + this handoff |
| Workflow (M) | 1 | `.github/workflows/deploy.yml` (image + symlink protection) |
| Deletion (D) | 1 | `public/reset.php` |
Total **45 = 24 M + 20 A + 1 D.** No migrations, no `.env`, no scratch scripts.
### D. FINAL GO / NO-GO
**RECOMMENDATION: GO, subject only to the pre-push items below. All technical release gates are green.**
| Gate | Status |
|---|---|
| Tests, build, whitespace, YAML | GO |
| Staged set, content review, workflow and deletion verified | GO |
| Schema/migrations; DB backup restored; VPS files backup | GO |
| Queue/Reverb running; reload method; failed-job baseline 20 | GO |
| Rollback plan (corrected, section G) | GO |
| **Remaining pre-push items (quick, yours):** (1) re-add this handoff; (2) `reset.php` consumers check: local scratch-script `Select-String` + VPS nginx/supervisor/cron grep + access-log count (12.37 C), not yet reported; (3) VPS pre-push baselines (12.36 C4: image count and hash, symlink state, queue PID, `jobs` 0/0, `failed_jobs` = 20 + latest `failed_at`); (4) fresh `git fetch` + `ls-remote` immediately before commit; (5) **your explicit "Authorize release" message** | OPEN |
**NO-GO if:** a `reset.php` consumer is found; the staged set differs from the 45 paths; `origin/main` moved; `jobs` has pending/reserved rows; `validate` is red; the push is rejected (nothing deploys; fix credential scope, push again).
### E. Remaining risks (known, accepted or scheduled; none newly investigated)
1. **Worker runs as root** and has no `--tries/--max-time/stopwaitsecs`: pre-existing; fix later as a separate change.
2. Between deploy and `queue:restart` the old worker runs old code for minutes (queue empty; acceptable).
3. `public/images/products/` is excluded from rsync entirely: future tracked photo changes will not auto-deploy.
4. Dump is `public` schema only, PG 18.6 test vs 17.6 source, 8 of 21 tables compared; DB restore is for data corruption only.
5. `deploy.yml` weaknesses remain (`npm install`, unpinned `ssh-deploy@main`, no health check, push = deploy, brief `public/build` outage during the VPS build).
6. Reverb/broadcasting symptoms undiagnosed (live badge only; durable in-app notifications unaffected).
7. Product/documentation decisions in section 9 (items 3-12), live UAT, DASH-005, OTP lockout verification, Chapters 1-3 and diagrams: not release blockers.
8. The `public/storage` symlink survival is expected (excluded) but unverified on the server until post-deploy check D3.
### F. RELEASE-DAY COMMAND CARD (exact; nothing below has been run)
**C1. Re-stage and verify (PowerShell, repo root)**
```powershell
git add -- docs/MOTOTRACK_FINAL_DEPLOYMENT_HANDOFF.md
$staged = @(git diff --cached --name-only); "Staged count: $($staged.Count)"        # 45
git diff --cached --name-status | ForEach-Object { ($_ -split '\s+')[0] } | Group-Object | Select-Object Name, Count   # A 20, D 1, M 24
git diff --cached --name-status | Select-String 'reset.php|deploy.yml'              # D public/reset.php ; M .github/workflows/deploy.yml
git diff --cached --check                                                            # nothing
git diff --name-only                                                                 # nothing
git branch --show-current                                                            # main
```
(`Compare-Object` against the full path list: 12.34 section 3.)
**C2. Remote verification, then commit**
```powershell
git fetch origin
git rev-parse HEAD origin/main                     # both dcb68c2c8356d284c630064fd2a7587b36400857
git ls-remote origin refs/heads/main               # same SHA
git status -sb                                     # ## main...origin/main, no ahead/behind
$msg = @'
fix: UAT defects, sale-deletion protection, synchronous low-stock alerts and deploy safety

- Disable DELETE /sales/{id} (route removed, controller 405, policy denies all roles); sales are cancelled or refunded, never deleted
- Add LowStockAlertService: owner in-app alert recorded synchronously after commit from every stock-changing path, one alert per low-stock episode, re-armed on restock; SMS best-effort via queued SendLowStockSmsJob; legacy CheckLowStockAlertJob kept as an idempotent shim
- Durable NotificationDispatcher (24h pre-order deadline starts only after a stored notification); ReadyToTest owner-cache fix; cancel-button UI fix
- Compatibility matcher accuracy, sign-up and profile validation fixes
- Deploy workflow: exclude /public/images/products/ and /public/storage from rsync --delete so production images and the storage symlink survive deployments
- Security: remove public/reset.php, an unauthenticated cache/OPcache reset endpoint
- Add regression tests (248 tests / 793 assertions) and UAT, traceability and deployment-handoff documents
- No migrations, config or .env changes

Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01X7MDpUk5oJaBFKoNSrK26B
'@
Set-Content -Path "$env:TEMP\mototrack_commit_msg.txt" -Value $msg -Encoding utf8
git commit -F "$env:TEMP\mototrack_commit_msg.txt"
git show --stat --oneline HEAD | Select-Object -First 3
(git show --name-only --format= HEAD | Measure-Object -Line).Lines                  # 45
$sha = git rev-parse HEAD; $sha
```
**C3. STOP. Send "Authorize release" as its own message.** Only then:
```powershell
git push origin main                               # THIS DEPLOYS
git ls-remote origin refs/heads/main               # equals $sha
```
GitHub > Actions: `validate` green, then `deploy` green (rejected push or red `validate` = nothing deployed).
### G. ROLLBACK (corrected: a plain `git revert` of the release would re-add `reset.php` and remove the rsync protection, and the rollback deploy would then run WITHOUT the exclusions and could delete `public/storage` and production-only images)
```powershell
git revert --no-commit $sha
git checkout $sha -- .github/workflows/deploy.yml     # keep the image/symlink protection
git rm -f -- public/reset.php                         # keep the security removal
git commit -m "revert: roll back release code; keep deploy protection and reset.php removal"
git push origin main                                  # wait for green, then reload the worker again (12.17)
```
Restore from `/root/mototrack-release-backups/` only if files are lost (extract just `public/images/products` or `public/storage`); restore the database only for proven data corruption, after talking to me.
### H. POST-DEPLOY VERIFICATION (VPS, read-only unless marked; do NOT request /reset.php; commands in 12.36 D)
1. GitHub Actions `validate` + `deploy` both succeeded. 2. `public/reset.php` ABSENT (`test -e ...`; `find` prints nothing). 3. **1,334** files in `public/images/products` and the sorted-list sha256 equals the pre-push value; catalog and a product page show images. 4. `public/storage` symlink present -> `/var/www/mototrack/storage/app/public`; if missing, `sudo -u www-data php artisan storage:link` **only with your go-ahead**, then writable check, **before** owner-upload UAT. 5. `sudo -u www-data php artisan queue:restart`, wait 15 s, **PID changed**, RUNNING, no root-owned cache files. 6. `failed_jobs` still **20** with the same latest `failed_at`; log tail clean for 5 min.
### I. LIVE UAT (synthetic/owner-approved data; new result rows; nothing claimed passed until observed)
Customer cancellation (reservation incl. multi-item, pre-order, mobile + desktop, Cancelled not Expired); notifications (customer + owner in-app, 24 h deadline after stored notification); compatibility (TMX 125/155 after owner links parts); inventory (add/edit/adjust/damaged/return, owner image upload after symlink check, **STK-001 one alert per episode**); cash POS (tendered/change, stock, history); refunds (request, approve, restock, audit, revenue; crafted `DELETE /sales/{id}` rejected, synthetic sale only). Deferred: DASH-005; expiry cases only by an owner-approved method.
### J. Status and resume
Commit, push, deployment, queue reload, post-deploy checks and live UAT: **NOT YET STARTED.** Standing rules unchanged: no commit, push, deploy, restart, failed-job retry or production data change without your explicit authorization; no `.env`/credentials/dumps to the cloud. Resume phrase **"Continue MotoTrack"**: first confirm C1, the 12.37 C consumer checks and the 12.36 C4 baselines, then wait for the separate authorization. This update only edited this handoff.

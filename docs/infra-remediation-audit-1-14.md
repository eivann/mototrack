# Infrastructure Remediation — Audit #1 & #14

These two findings from `docs/architectural-audit.md` cannot be closed by a code
change alone; they require an environment/infrastructure action. The code-side
work that *was* safe has already been applied (see notes). This runbook records
the exact remaining steps.

---

## #1 — Triple DB bottleneck through the Supabase transaction pooler

**Problem.** `SESSION`, `CACHE`, and `QUEUE` all run through the Supabase
transaction-mode pooler (`DB_PORT=6543`). Every session read, cache hit, and
queued job competes for the same pooled connections, and transaction-mode
pooling breaks prepared statements and `LISTEN/NOTIFY`. `lockForUpdate()` in the
checkout/POS paths can deadlock under this pooler.

**Why it is not a code change here.** There is **no Redis** available in this
environment (no `bin/redis` in Laragon, no `predis`/`phpredis` in
`composer.json`). Switching the drivers to `redis` now would take the app down.

**Recommended fix (pick one):**

### Option A — Redis for session/cache/queue (best at scale)
1. Install Redis (Laragon: add the Redis package, or run a local/hosted Redis).
2. Install a client: `composer require predis/predis` (or enable the `phpredis`
   PHP extension).
3. In `.env`:
   ```env
   SESSION_DRIVER=redis
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   REDIS_CLIENT=phpredis        # or predis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```
4. `php artisan config:clear`, then restart the queue worker.
5. Verify: `php artisan tinker` → `Cache::put('k','v',5); Cache::get('k');`.

### Option B — keep Postgres but stop starving the pooler (no new service)
1. Point the **DB connection** at the **session-mode** pooler (port `5432`)
   instead of transaction-mode (`6543`) so prepared statements and locks behave.
2. Optionally move **CACHE** to `file` to remove cache traffic from the DB with
   zero new infrastructure:
   ```env
   CACHE_STORE=file
   ```
   (Session and queue can stay on the DB short-term.) This is the lowest-risk
   interim relief and is safe to apply immediately.

**Deadlock note.** Whichever option, keep the checkout/POS `lockForUpdate()`
paths (`CommitOrder` action) — they are correct; the pooler was the risk, not
the locking.

---

## #14 — Secrets present in `.env` on disk

**Already done in code:**
- Confirmed `.env` is gitignored (`.gitignore:3-5`).
- Added a dependency-free secret-scanning git hook: `scripts/git-hooks/pre-commit`.
- Added a `.gitleaks.toml` for CI / teams using gitleaks.
- Filled `.env.example` with every expected key name (empty placeholders) so the
  template documents required config without leaking values.

**Remaining manual steps:**
1. **Enable the hook** in each clone:
   ```bash
   git config core.hooksPath scripts/git-hooks
   ```
   (Or wire gitleaks into CI: `gitleaks protect --staged --config .gitleaks.toml`.)
2. **Rotate any key that was ever committed** before `.env` was ignored — a
   gitignore does not un-leak history. Check with:
   ```bash
   git log --all -p -- .env
   ```
   If anything shows up, rotate those provider keys (Xendit, RCPAY, Google,
   Movider, AWS) and scrub history.
3. **Staging/prod secrets** should move to a managed store (Supabase Vault or
   AWS Secrets Manager) rather than a disk `.env`. This is an infra decision and
   is intentionally left to the operator.

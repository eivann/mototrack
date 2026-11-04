#!/usr/bin/env bash
# MANDATORY post-maintenance HTTP 200 smoke test. Read-only (HTTP GETs and file checks only).
# A 503 or any non-200 answer is a FAILED deployment, never a pass.
#
#   Tracked in the repository and deployed with the app, so it is available at:
#     /var/www/mototrack/scripts/release/post-maintenance-smoke.sh   (and in the source checkout)
#   On the VPS as deploy (origin, bypasses Cloudflare):   bash scripts/release/post-maintenance-smoke.sh --origin
#   From any machine (public path through Cloudflare):    bash scripts/release/post-maintenance-smoke.sh --public
# Run BOTH after `php artisan up`. The deployment is NOT complete until both print "SMOKE TEST: PASS".
# Optional: --log-baseline <N>   N = number of ERROR lines in storage/logs/laravel.log recorded before the window.
set -u
MODE="${1:---public}"; BASE_ERR=""
[ "${2:-}" = "--log-baseline" ] && BASE_ERR="${3:-}"
APP=/var/www/mototrack
SECRET_FILE=/home/deploy/.maintenance-secret
fail=0
say() { printf '%s\n' "$*"; }
bad() { say "FAIL: $*"; fail=1; }

if [ "$MODE" = "--origin" ]; then
  [ -f "$APP/storage/framework/down" ] && bad "maintenance file still exists (php artisan up not run?)" || say "ok: no maintenance file"
  [ -e "$SECRET_FILE" ] && bad "bypass secret file still exists: delete it with shred -u $SECRET_FILE" || say "ok: no bypass secret file left"
  fetch() { curl --noproxy '*' -kSs --max-time 20 --resolve mototrack.online:443:127.0.0.1 -o /dev/null -D "$2" -w '%{http_code}' "https://mototrack.online$1" || true; }
else
  fetch() { curl -sS --max-time 25 -o /dev/null -D "$2" -w '%{http_code}' "https://mototrack.online$1" || true; }
fi

H="$(mktemp)"; trap 'rm -f "$H"' EXIT
for path in / /login; do
  code="$(fetch "$path" "$H")"
  if [ "$code" = 200 ]; then
    if grep -qi '^retry-after:' "$H"; then bad "GET $path returned 200 but carries Retry-After (unexpected)"; else say "ok: GET $path -> HTTP 200"; fi
  else
    bad "GET $path -> HTTP ${code:-none} (required: exactly 200)"
  fi
done

if [ "$MODE" = "--origin" ] && [ -n "$BASE_ERR" ]; then
  now="$(grep -c 'ERROR' "$APP/storage/logs/laravel.log" 2>/dev/null || echo 0)"
  [ "$now" -gt "$BASE_ERR" ] && bad "laravel.log has $((now-BASE_ERR)) new ERROR line(s) since the baseline" || say "ok: no new ERROR lines in laravel.log"
fi

if [ "$fail" = 0 ]; then say "SMOKE TEST: PASS ($MODE)"; else say "SMOKE TEST: FAIL ($MODE). The deployment is NOT complete. Do not announce the site as back online; follow the recovery steps."; fi
exit "$fail"

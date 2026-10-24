<?php

namespace App\Console\Commands;

use App\Models\SaleHeader;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SYS4-06 D3: attach HISTORICAL sales rows (sale_header_id IS NULL) to physical sale_headers.
 *
 * One header per transaction group: rows sharing a non-blank transaction_reference, or a single row
 * with a NULL/blank reference (header reference LEGACY-<sale id>; the sale's own reference is never
 * rewritten). Header values are DERIVED from the rows and never guessed: ambiguous or inconsistent
 * groups are SKIPPED with a reason and left completely untouched.
 *
 * Safety properties
 *  - Requires an explicit --dry-run or --execute. --dry-run issues SELECT statements only.
 *  - Each group is its own short transaction: its lines are locked (by id), re-analysed with the same
 *    rules, then the header is inserted and ALL lines linked, or nothing at all.
 *  - Idempotent and restartable: linked rows are never candidates again, partially linked groups are
 *    skipped, and the unique header reference blocks duplicates.
 *  - Raw query-builder writes: no model events, no audit-log noise, no notifications, and the sales
 *    rows' updated_at and every financial value stay exactly as they were.
 *  - Bounded memory: references / ids are read in keyset chunks; only one group is in memory at a time.
 */
#[Signature('sales:backfill-headers {--dry-run : Analyse only; issues no writes} {--execute : Actually create headers and link sales} {--limit=0 : Stop after this many headers are (or would be) created; 0 = no limit} {--chunk=200 : Candidate groups read per query} {--show=25 : Skipped groups to list in the report}')]
#[Description('Backfill sale_headers for historical sales (dry-run first; groups with ambiguous data are skipped)')]
class BackfillSaleHeaders extends Command
{
    private const STATUSES = ['pending', 'completed', 'cancelled', 'expired', 'refund_requested', 'refunded'];

    private const MAX_DECIMAL = 9999999999.99;

    /**
     * Maximum created_at spread (seconds) for lines of ONE multi-line transaction. CommitOrder creates every
     * line of a checkout inside one request with a single now(), so real spreads are 0-2 s; 120 s leaves
     * generous room for slow requests while weeks-apart reuse of a reference is clearly separate purchases.
     */
    private const MAX_CREATED_SPREAD_SECONDS = 120;

    private bool $execute = false;

    /** @var array<string,int> */
    private array $skips = [];

    /** @var array<int,string> */
    private array $skipSamples = [];

    private int $created = 0;

    private int $linkedLines = 0;

    private int $errors = 0;

    private int $examined = 0;

    /** @var array<string,int> */
    private array $types = [];

    public function handle(): int
    {
        // Reset all run state: the command object can be reused within one process (e.g. in tests).
        $this->skips = [];
        $this->skipSamples = [];
        $this->types = [];
        $this->created = $this->linkedLines = $this->errors = $this->examined = 0;

        $dry = (bool) $this->option('dry-run');
        $this->execute = (bool) $this->option('execute');

        if ($dry === $this->execute) {
            $this->error('Specify exactly one of --dry-run or --execute.');

            return self::INVALID;
        }

        $limit = max(0, (int) $this->option('limit'));
        $chunk = max(1, min(1000, (int) $this->option('chunk')));
        $showMax = max(0, (int) $this->option('show'));

        $this->info($this->execute ? 'EXECUTE: creating headers.' : 'DRY RUN: no data will be written.');

        $limitReached = false;

        // Phase A: groups that share a non-blank reference. Keyset pagination on the reference.
        $cursor = '';
        while (! $limitReached) {
            $refs = DB::table('sales')
                ->whereNull('sale_header_id')
                ->whereNotNull('transaction_reference')
                ->whereRaw("trim(transaction_reference) <> ''")
                ->where('transaction_reference', '>', $cursor)
                ->orderBy('transaction_reference')
                ->distinct()
                ->limit($chunk)
                ->pluck('transaction_reference');

            if ($refs->isEmpty()) {
                break;
            }

            foreach ($refs as $ref) {
                $cursor = $ref;
                $this->examined++;
                $this->processGroup(fn () => $this->loadByReference($ref, $this->execute), $ref, false);
                if ($limit > 0 && $this->created >= $limit) {
                    $limitReached = true;
                    break;
                }
            }
        }

        // Phase B: single legacy rows with a NULL/blank reference. Keyset pagination on the id.
        $lastId = 0;
        while (! $limitReached) {
            $ids = DB::table('sales')
                ->whereNull('sale_header_id')
                ->where(fn ($q) => $q->whereNull('transaction_reference')->orWhereRaw("trim(transaction_reference) = ''"))
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            foreach ($ids as $id) {
                $lastId = $id;
                $this->examined++;
                $this->processGroup(fn () => $this->loadById($id, $this->execute), 'LEGACY-'.$id, true);
                if ($limit > 0 && $this->created >= $limit) {
                    $limitReached = true;
                    break;
                }
            }
        }

        $this->report($showMax, $limitReached);

        return $this->errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ── Loading ──────────────────────────────────────────────────────────────

    /** Every row of the reference (linked or not, soft-deleted included), in id order. */
    private function loadByReference(string $ref, bool $lock): Collection
    {
        $q = DB::table('sales')->where('transaction_reference', $ref)->orderBy('id');

        return ($lock ? $q->lockForUpdate() : $q)->get();
    }

    private function loadById(int $id, bool $lock): Collection
    {
        $q = DB::table('sales')->where('id', $id)->orderBy('id');

        return ($lock ? $q->lockForUpdate() : $q)->get();
    }

    // ── One group ────────────────────────────────────────────────────────────

    private function processGroup(callable $load, string $headerRef, bool $synthetic): void
    {
        try {
            if (! $this->execute) {
                $result = $this->analyse($load(), $headerRef, $synthetic);
                $this->record($headerRef, $result);

                return;
            }

            $result = DB::transaction(function () use ($load, $headerRef, $synthetic) {
                // Lines are locked first (by id), then re-analysed on the locked state.
                $rows = $load();
                $result = $this->analyse($rows, $headerRef, $synthetic);
                if (isset($result['skip'])) {
                    return $result;
                }

                $headerId = DB::table('sale_headers')->insertGetId($result['header']);
                $updated = DB::table('sales')->whereIn('id', $result['ids'])->whereNull('sale_header_id')
                    ->update(['sale_header_id' => $headerId]);

                if ($updated !== count($result['ids'])) {
                    throw new \RuntimeException('linked line count mismatch'); // rolls the whole group back
                }

                return $result;
            });

            $this->record($headerRef, $result);
        } catch (\Throwable $e) {
            $this->errors++;
            $this->recordSkip($headerRef, 'error:'.class_basename($e));
        }
    }

    private function record(string $headerRef, array $result): void
    {
        if (isset($result['skip'])) {
            $this->recordSkip($headerRef, $result['skip']);

            return;
        }

        $this->created++;
        $this->linkedLines += count($result['ids']);
        $type = $result['header']['transaction_type'];
        $this->types[$type] = ($this->types[$type] ?? 0) + 1;
    }

    private function recordSkip(string $headerRef, string $reason): void
    {
        $this->skips[$reason] = ($this->skips[$reason] ?? 0) + 1;
        if (count($this->skipSamples) < 1000) {
            $this->skipSamples[] = "{$headerRef} ({$reason})";
        }
    }

    // ── Analysis (pure: used identically by dry-run and execute) ─────────────

    /**
     * @return array{skip:string}|array{header:array<string,mixed>,ids:array<int,int>}
     */
    private function analyse(Collection $rows, string $headerRef, bool $synthetic): array
    {
        if ($rows->isEmpty()) {
            return ['skip' => 'group_vanished'];
        }

        if ($rows->contains(fn ($r) => $r->sale_header_id !== null)) {
            return ['skip' => 'partially_linked'];
        }
        if ($rows->contains(fn ($r) => $r->deleted_at !== null)) {
            return ['skip' => 'contains_soft_deleted'];
        }
        if (strlen($headerRef) > 255) {
            return ['skip' => 'reference_too_long'];
        }
        if ($rows->pluck('customer_id')->unique()->count() > 1) {
            return ['skip' => 'mixed_customers'];
        }
        if ($rows->pluck('shop_id')->unique()->count() > 1) {
            return ['skip' => 'mixed_shops'];
        }

        // Statuses: line status stays authoritative, but incompatible mixes are not guessed at.
        $statuses = $rows->pluck('status')->unique()->values();
        if ($statuses->contains(fn ($s) => ! in_array($s, self::STATUSES, true))) {
            return ['skip' => 'unknown_status'];
        }
        if ($statuses->contains('pending') && $statuses->count() > 1) {
            return ['skip' => 'mixed_pending_and_settled'];
        }
        if ($statuses->intersect(['cancelled', 'expired'])->isNotEmpty() && $statuses->count() > 1) {
            return ['skip' => 'mixed_cancelled_or_expired'];
        }

        // A pending reservation has no payment yet: a tender or payment timestamp contradicts it.
        if ($statuses->contains('pending')
            && $rows->contains(fn ($r) => (float) $r->amount_tendered > 0 || $r->payment_confirmed_at !== null)) {
            return ['skip' => 'pending_with_payment_data'];
        }

        // Temporal coherence: several lines sharing one reference must have been created together.
        if ($rows->count() > 1) {
            $times = [];
            foreach ($rows as $r) {
                try {
                    if ($r->created_at === null || $r->created_at === '') {
                        return ['skip' => 'missing_created_at'];
                    }
                    $times[] = Carbon::parse($r->created_at)->getTimestamp();
                } catch (\Throwable) {
                    return ['skip' => 'missing_created_at'];
                }
            }
            if (max($times) - min($times) > self::MAX_CREATED_SPREAD_SECONDS) {
                return ['skip' => 'suspected_reused_reference'];
            }
        }

        // Amounts.
        $total = 0.0;
        foreach ($rows as $r) {
            if ($r->total_price === null || $r->price === null || $r->quantity === null
                || (float) $r->total_price < 0 || (float) $r->price < 0 || (int) $r->quantity < 1) {
                return ['skip' => 'invalid_amounts'];
            }
            $total += (float) $r->total_price;
        }
        $total = round($total, 2); // the ORIGINAL transaction total: refunds and cancellations do not reduce it
        if ($total > self::MAX_DECIMAL) {
            return ['skip' => 'total_out_of_range'];
        }

        // Tender: same semantics as Sale::paymentLine (lowest-id line holding a tender, else the first
        // line). Repeated identical tender values are one payment, never summed; differing ones are ambiguous.
        $tenderRows = $rows->filter(fn ($r) => (float) $r->amount_tendered > 0)->sortBy('id')->values();
        $holder = $tenderRows->first() ?? $rows->sortBy('id')->first();
        if ($tenderRows->map(fn ($r) => round((float) $r->amount_tendered, 2))->unique()->count() > 1
            || $tenderRows->map(fn ($r) => round((float) $r->change, 2))->unique()->count() > 1) {
            return ['skip' => 'ambiguous_tender'];
        }
        $tender = round((float) $holder->amount_tendered, 2);
        $change = round((float) $holder->change, 2);
        if (abs($tender) > self::MAX_DECIMAL || abs($change) > self::MAX_DECIMAL) {
            return ['skip' => 'invalid_amounts'];
        }

        // Type: only where the evidence is unambiguous; otherwise 'legacy' (never invented).
        $reservedMarked = $rows->filter(fn ($r) => $r->reserved_at !== null)->count();
        if (str_starts_with($headerRef, 'REPAIR-') && ! $synthetic) {
            if ($rows->count() !== 1 || $rows->first()->inventory_item_id !== null) {
                return ['skip' => 'inconsistent_type'];
            }
            $type = SaleHeader::TYPE_REPAIR;
        } elseif (str_starts_with($headerRef, 'PRE-') && ! $synthetic) {
            if ($rows->count() !== 1) {
                return ['skip' => 'inconsistent_type'];
            }
            $type = SaleHeader::TYPE_PREORDER;
        } elseif ($reservedMarked > 0) {
            if ($reservedMarked !== $rows->count()) {
                return ['skip' => 'mixed_reservation_markers'];
            }
            $type = SaleHeader::TYPE_ONLINE_RESERVATION;
        } else {
            $type = SaleHeader::TYPE_LEGACY;
        }

        // Reference uniqueness against headers AND every sale reference (soft-deleted included).
        if (DB::table('sale_headers')->where('transaction_reference', $headerRef)->exists()) {
            return ['skip' => $synthetic ? 'synthetic_reference_conflict' : 'header_reference_exists'];
        }
        if ($synthetic && DB::table('sales')->where('transaction_reference', $headerRef)->exists()) {
            return ['skip' => 'synthetic_reference_conflict'];
        }

        $ts = fn (string $field, string $fn) => $this->earliestOrLatest($rows, $field, $fn);
        $paid = $statuses->contains('pending') ? null : $ts('payment_confirmed_at', 'min');

        return [
            'ids' => $rows->pluck('id')->map(fn ($i) => (int) $i)->all(),
            'header' => [
                'transaction_reference' => $headerRef,
                'customer_id' => $rows->first()->customer_id,
                'shop_id' => $rows->first()->shop_id,
                'transaction_type' => $type,
                'total_amount' => $total,
                'amount_tendered' => $tender,
                'change_amount' => $change,
                'reserved_at' => $ts('reserved_at', 'min'),
                'expires_at' => $ts('expires_at', 'min'),
                'payment_confirmed_at' => $paid,
                'picked_up_at' => $ts('picked_up_at', 'min'),
                'created_at' => $ts('created_at', 'min'),
                'updated_at' => $ts('updated_at', 'max'),
            ],
        ];
    }

    private function earliestOrLatest(Collection $rows, string $field, string $fn): ?string
    {
        $values = $rows->pluck($field)->filter()->map(fn ($v) => Carbon::parse($v));
        if ($values->isEmpty()) {
            return null;
        }

        return ($fn === 'min' ? $values->min() : $values->max())->format('Y-m-d H:i:s');
    }

    // ── Report ───────────────────────────────────────────────────────────────

    private function report(int $showMax, bool $limitReached): void
    {
        $this->newLine();
        $this->line('Groups examined: '.$this->examined);
        $this->line(($this->execute ? 'Headers created: ' : 'Headers that would be created: ').$this->created);
        $this->line(($this->execute ? 'Sale lines linked: ' : 'Sale lines that would be linked: ').$this->linkedLines);
        ksort($this->types);
        foreach ($this->types as $type => $n) {
            $this->line("  type {$type}: {$n}");
        }
        $skipTotal = array_sum($this->skips);
        $this->line("Skipped groups: {$skipTotal}");
        ksort($this->skips);
        foreach ($this->skips as $reason => $n) {
            $this->line("  {$reason}: {$n}");
        }
        if ($showMax > 0 && $this->skipSamples !== []) {
            $this->line('Skipped (first '.min($showMax, count($this->skipSamples)).'):');
            foreach (array_slice($this->skipSamples, 0, $showMax) as $line) {
                $this->line("  {$line}");
            }
        }
        if ($limitReached) {
            $this->warn('Stopped at --limit; run again to continue (already linked groups are skipped automatically).');
        }
        if ($this->errors > 0) {
            $this->error("{$this->errors} group(s) failed and were rolled back; re-run to retry them.");
        }
        $this->info($this->execute ? 'Done.' : 'DRY RUN complete: nothing was written.');
    }
}

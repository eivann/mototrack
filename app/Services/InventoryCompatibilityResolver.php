<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Inventory-sourced compatibility (Components A, B and C).
 *
 * A. Reference catalogue  : config('compatibility_catalogue.catalogue') — Make -> Model list shown
 *                           in the dropdowns, independent of what inventory currently holds.
 * B. Inventory model map  : each catalogue entry's explicit `aliases` = exact strings found in
 *                           inventory_items.model. Original inventory values are never altered.
 * C. Resolver (this class): a product is listed for a Make/Model only when its recorded
 *                           `model` string is an alias of that catalogue entry, or an owner
 *                           fitment record (part_compatibility) says so.
 *
 * Nothing is inferred: combined values, colour/side suffixes, families vs variants, sizes,
 * parts brands and unknown makes never match. `inventory_items.brand` (the PARTS brand) is
 * never consulted for the motorcycle make.
 */
class InventoryCompatibilityResolver
{
    public const UNIVERSAL = 'Universal';

    /** Total matching products before the result limit (for the user-visible "showing first N" notice). */
    public int $lastTotal = 0;

    /** Catalogue entries for a make: inventory-evidenced entries plus owner-supplied (zero-inventory) models. */
    public function entries(string $canonicalMake): array
    {
        return array_values(array_merge(
            config("compatibility_catalogue.catalogue.{$canonicalMake}", []),
            config("compatibility_catalogue.owner_models.{$canonicalMake}", [])
        ));
    }

    public static function enabled(): bool
    {
        return (bool) config('compatibility.inventory_sourced', false);
    }

    /** @return array<int,string> exactly the five customer Make options */
    public function makes(): array
    {
        return array_values(config('compatibility.dropdown_makes', []));
    }

    public function isUniversal(string $make): bool
    {
        return CompatibilityMatcher::normalize($make) === CompatibilityMatcher::normalize(self::UNIVERSAL);
    }

    /** Canonical make name from a (case/space-insensitive) value, or null if not one of the four. */
    public function canonicalMake(string $make): ?string
    {
        $key = CompatibilityMatcher::normalize($make);
        foreach (array_keys(config('compatibility_catalogue.catalogue', [])) as $name) {
            if (CompatibilityMatcher::normalize($name) === $key) {
                return $name;
            }
        }

        return null;
    }

    public static function label(array $entry): string
    {
        return $entry['name'] . (($entry['type'] ?? 'specific') === 'family' ? ' (general)' : '');
    }

    /** @return array<int,string> Model dropdown labels for a make (all catalogue models). */
    public function models(string $make): array
    {
        $canonical = $this->canonicalMake($make);
        if ($canonical === null) {
            return [];
        }

        return collect($this->entries($canonical))
            ->map(fn ($e) => self::label($e))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** Catalogue entry for a dropdown label ("Mio (general)") or plain name. */
    public function findEntry(string $make, string $model): ?array
    {
        $canonical = $this->canonicalMake($make);
        if ($canonical === null || trim($model) === '') {
            return null;
        }
        $key = CompatibilityMatcher::normalize($model);
        foreach ($this->entries($canonical) as $entry) {
            if (CompatibilityMatcher::normalize(self::label($entry)) === $key
                || CompatibilityMatcher::normalize($entry['name']) === $key) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Raw inventory model strings (as stored) that map to the given catalogue entries.
     *
     * @param  array<int,array>  $entries
     * @return array<int,string>
     */
    public function rawModelsFor(array $entries): array
    {
        $aliases = [];
        foreach ($entries as $entry) {
            foreach ($entry['aliases'] ?? [] as $alias) {
                $aliases[CompatibilityMatcher::normalize($alias)] = true;
            }
        }

        return $this->rawModelsMatching($aliases);
    }

    /** @param array<string,bool> $normalizedKeys */
    private function rawModelsMatching(array $normalizedKeys): array
    {
        if ($normalizedKeys === []) {
            return [];
        }

        return InventoryItem::query()
            ->whereNotNull('model')
            ->distinct()
            ->pluck('model')
            ->filter(fn ($raw) => isset($normalizedKeys[CompatibilityMatcher::normalize($raw)]))
            ->values()
            ->all();
    }

    /**
     * Browse "model" filter under inventory-sourced mode (no Make is chosen on that page).
     *
     * Uses exactly the rules of resolve(): the typed text must be a catalogue model name or
     * dropdown label of some make (case/space-insensitive); products match through that entry's
     * explicit inventory aliases and/or owner fitment records. Nothing is matched by substring.
     *
     * @return array{raws:array<int,string>,motorcycle_ids:array<int,int>}
     */
    public function modelFilter(string $model): array
    {
        $entries = [];
        $ids = [];
        if (trim($model) !== '') {
            foreach (array_keys(config('compatibility_catalogue.catalogue', [])) as $make) {
                $entry = $this->findEntry($make, $model);
                if ($entry === null) {
                    continue;
                }
                $entries[] = $entry;
                foreach (app(CompatibilityMatcher::class)->motorcycleIds($make, $entry['name'], '') as $id) {
                    $ids[$id] = $id;
                }
            }
        }

        return [
            'raws' => $entries === [] ? [] : $this->rawModelsFor($entries),
            'motorcycle_ids' => array_values($ids),
        ];
    }

    /**
     * Model suggestions across all four makes for the Browse model box (prefix match on the
     * dropdown label or the plain model name).
     *
     * @return array<int,string>
     */
    public function modelSuggestions(string $term, int $limit = 12): array
    {
        $needle = CompatibilityMatcher::normalize($term);
        $out = [];
        foreach (array_keys(config('compatibility_catalogue.catalogue', [])) as $make) {
            foreach ($this->entries($make) as $entry) {
                $label = self::label($entry);
                if ($needle === ''
                    || str_starts_with(CompatibilityMatcher::normalize($label), $needle)
                    || str_starts_with(CompatibilityMatcher::normalize($entry['name']), $needle)) {
                    $out[CompatibilityMatcher::normalize($label)] = $label;
                }
            }
        }
        $out = array_values($out);
        natcasesort($out);

        return array_slice(array_values($out), 0, $limit);
    }

    /**
     * Resolve products. Returns [] items for no match.
     *
     * @return Collection<int,InventoryItem>
     */
    public function resolve(string $make, string $model = '', string $year = '', string $part = ''): Collection
    {
        $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        $query = InventoryItem::with(['motorcycles', 'variations']);

        if ($part !== '') {
            $query->where(function ($q) use ($part, $likeOp) {
                $q->where('part_name', $likeOp, "%{$part}%")
                  ->orWhere('category', $likeOp, "%{$part}%");
            });
        }

        if ($this->isUniversal($make)) {
            return $this->resolveUniversal($query);
        }

        $canonical = $this->canonicalMake($make);
        if ($canonical === null) {
            return collect(); // Unknown make: never matched.
        }

        $entries = collect($this->entries($canonical));
        if (trim($model) !== '') {
            $entry = $this->findEntry($canonical, $model);
            if ($entry === null) {
                return collect();
            }
            $entries = collect([$entry]);
        }

        // Year: inventory carries no year data, so a year filter can only be satisfied by owner fitment records.
        $raws = trim($year) === '' ? $this->rawModelsFor($entries->all()) : [];
        $motorcycleIds = app(CompatibilityMatcher::class)
            ->motorcycleIds($canonical, $model !== '' ? ($entries->first()['name'] ?? $model) : '', $year);

        if ($raws === [] && $motorcycleIds->isEmpty()) {
            $this->lastTotal = 0;

            return collect();
        }

        $query->where(function ($q) use ($raws, $motorcycleIds) {
            if ($raws !== []) {
                $q->whereIn('model', $raws);
            }
            if ($motorcycleIds->isNotEmpty()) {
                $q->orWhereHas('motorcycles', fn ($mq) => $mq->whereIn('motorcycles.id', $motorcycleIds->all()));
            }
        });

        $this->lastTotal = (clone $query)->count();
        $items = $query->orderBy('part_name')->take((int) config('compatibility.result_limit', 100))->get();

        $byAlias = [];
        foreach ($entries as $e) {
            foreach ($e['aliases'] ?? [] as $a) {
                $byAlias[CompatibilityMatcher::normalize($a)] = $e;
            }
        }
        $ids = $motorcycleIds->flip();

        return $items->each(function (InventoryItem $item) use ($byAlias, $ids, $canonical) {
            $entry = $byAlias[CompatibilityMatcher::normalize($item->model)] ?? null;
            $fits = $item->motorcycles->filter(fn ($m) => $ids->has($m->id))
                ->map(fn ($m) => trim("{$m->brand} {$m->model}" . ($m->year ? " ({$m->year})" : '')))
                ->values()->all();

            $matched = [];
            $status = 'confirmed';
            $note = null;
            if ($entry !== null) {
                $matched[] = trim($canonical . ' ' . $entry['name']);
                if (($entry['type'] ?? 'specific') === 'family') {
                    $status = 'recorded_general';
                    $note = "Recorded for the general {$entry['name']} line (no variant given) - please confirm your exact variant with the shop.";
                }
            }
            $matched = array_values(array_unique(array_merge($matched, $fits)));
            if ($entry !== null && $fits !== []) {
                $status = 'confirmed'; // an owner fitment record also exists for this motorcycle
                $note = null;
            }

            $item->setAttribute('compatibility_status', $status);
            $item->setAttribute('compatibility_source', $entry !== null ? 'inventory' : 'fitment');
            $item->setAttribute('matched_fitment', $matched);
            $item->setAttribute('compatibility_note', $note);
        });
    }

    /** Universal selection: labelled / conditional products only; never "confirmed" without owner criteria. */
    private function resolveUniversal($query): Collection
    {
        $labelled = $this->keys(config('compatibility_catalogue.universal.labelled', []));
        $conditional = $this->keys(config('compatibility_catalogue.universal.conditional', []));
        $confirmed = $this->keys(config('compatibility_catalogue.universal.confirmed', []));

        $raws = $this->rawModelsMatching($labelled + $conditional + $confirmed);
        if ($raws === []) {
            return collect();
        }

        $query->whereIn('model', $raws);
        $this->lastTotal = (clone $query)->count();
        $items = $query->orderBy('part_name')
            ->take((int) config('compatibility.result_limit', 100))->get();

        return $items->each(function (InventoryItem $item) use ($confirmed, $conditional) {
            $key = CompatibilityMatcher::normalize($item->model);
            if (isset($confirmed[$key])) {
                $status = 'universal_confirmed';
                $note = 'Owner-confirmed universal fitment.';
            } elseif (isset($conditional[$key])) {
                $status = 'universal_conditional';
                $note = 'Universal only for the right size/spec (' . $item->model . ') - confirm against your motorcycle before buying.';
            } else {
                $status = 'universal_labelled';
                $note = 'Labelled "Universal" by the shop. Fit is not guaranteed for every motorcycle - please confirm with the shop.';
            }
            $item->setAttribute('compatibility_status', $status);
            $item->setAttribute('compatibility_source', 'inventory');
            $item->setAttribute('matched_fitment', []);
            $item->setAttribute('compatibility_note', $note);
        });
    }

    /** @return array<string,bool> */
    private function keys(array $values): array
    {
        $out = [];
        foreach ($values as $v) {
            $out[CompatibilityMatcher::normalize($v)] = true;
        }

        return $out;
    }
}

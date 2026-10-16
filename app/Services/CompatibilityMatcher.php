<?php

namespace App\Services;

use App\Models\Motorcycle;
use Illuminate\Support\Collection;

/**
 * Single source of truth for "which motorcycles does this selection mean?".
 *
 * A part is compatible ONLY when a `part_compatibility` record links it to a
 * motorcycle row that satisfies every supplied criterion on that same row.
 * Nothing is inferred from product names, free-text columns or "Universal".
 *
 * - make / model: case-insensitive, whitespace-normalised EXACT comparison
 *   (so "TMX 15" never matches "TMX 155").
 * - year: a record year is a single year ("2020") or inclusive range
 *   ("2018-2022"). A record with no year applies to every year of that model.
 */
class CompatibilityMatcher
{
    public static function normalize(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower((string) $value)));
    }

    /**
     * @return array{0:int,1:int}|null  Inclusive [start, end], or null when unparsable.
     */
    public static function yearRange(?string $value): ?array
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{4})$/', $value, $m)) {
            return [(int) $m[1], (int) $m[1]];
        }

        if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $value, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];

            return $a <= $b ? [$a, $b] : null;
        }

        return null;
    }

    /**
     * Does a fitment row's year cover the requested year (or range)?
     */
    public static function yearCovers(?string $recordYear, string $requestedYear): bool
    {
        if (trim((string) $recordYear) === '') {
            return true; // No year restriction recorded: fits all years of the model.
        }

        $record = self::yearRange($recordYear);
        $wanted = self::yearRange($requestedYear);

        if ($record === null || $wanted === null) {
            return false; // Malformed data can never be presented as a confirmed fit.
        }

        return $record[0] <= $wanted[1] && $wanted[0] <= $record[1]; // inclusive overlap
    }

    /**
     * @return Collection<int,int>  Ids of motorcycle rows satisfying all given criteria.
     */
    public function motorcycleIds(string $make = '', string $model = '', string $year = ''): Collection
    {
        $make = self::normalize($make);
        $model = self::normalize($model);
        $year = trim($year);

        return Motorcycle::query()
            ->get(['id', 'brand', 'model', 'year'])
            ->filter(function (Motorcycle $m) use ($make, $model, $year) {
                if ($make !== '' && self::normalize($m->brand) !== $make) {
                    return false;
                }
                if ($model !== '' && self::normalize($m->model) !== $model) {
                    return false;
                }
                if ($year !== '' && ! self::yearCovers($m->year, $year)) {
                    return false;
                }

                return true;
            })
            ->pluck('id')
            ->values();
    }

    /**
     * Individual years (desc) covered by the given motorcycle rows, for dropdowns.
     *
     * @param  iterable<string|null>  $recordYears
     * @return array<int,string>
     */
    public static function expandYears(iterable $recordYears): array
    {
        $years = [];

        foreach ($recordYears as $value) {
            $range = self::yearRange($value);
            if ($range === null) {
                continue;
            }
            for ($y = $range[0]; $y <= $range[1]; $y++) {
                $years[$y] = (string) $y;
            }
        }

        krsort($years);

        return array_values($years);
    }
}

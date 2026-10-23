<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Audit #7: search logic extracted out of the InventoryItem model (SRP).
 * The model's `scopeSearch` now forwards here, so existing call sites keep
 * working and query chaining / pagination is preserved.
 *
 * SYS4-03 B1: terms are split on any whitespace, single-character terms
 * (e.g. the "1" in "Part 1") are kept, and LIKE wildcards typed by the user
 * (% _ and the escape character itself) are matched literally.
 */
class InventorySearchService
{
    /** Escape character used in every LIKE pattern (identical on PostgreSQL and SQLite). */
    public const LIKE_ESCAPE = '!';

    /** Filler words ignored when other terms are present. Single letters are never ignored. */
    private const STOPWORDS = ['for', 'the', 'and', 'with', 'in', 'on', 'at', 'to', 'an', 'of', 'my'];

    /**
     * Escape a user string so %, _ and the escape character match literally.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE . self::LIKE_ESCAPE, self::LIKE_ESCAPE . '%', self::LIKE_ESCAPE . '_'],
            $value
        );
    }

    /**
     * Add a literal, case-insensitive "column LIKE %value%" (or prefix) condition.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public static function whereLike($query, string $column, string $value, string $mode = 'contains', string $boolean = 'and'): void
    {
        $base = $query instanceof Builder ? $query->getQuery() : $query;
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        $wrapped = $base->getGrammar()->wrap($column);
        $escaped = self::escapeLike($value);
        $pattern = ($mode === 'prefix' ? '' : '%') . $escaped . '%';

        $query->whereRaw("{$wrapped} {$operator} ? ESCAPE '" . self::LIKE_ESCAPE . "'", [$pattern], $boolean);
    }

    /**
     * Split a raw search string into terms, dropping filler words only when
     * at least one meaningful term remains.
     *
     * @return array<int,string>
     */
    public static function terms(?string $searchTerm): array
    {
        $searchTerm = trim((string) $searchTerm);
        if ($searchTerm === '') {
            return [];
        }

        $all = array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower($searchTerm)) ?: [],
            fn ($word) => $word !== ''
        ));

        $meaningful = array_values(array_filter($all, fn ($word) => ! in_array($word, self::STOPWORDS, true)));

        return $meaningful !== [] ? $meaningful : $all;
    }

    /**
     * Apply the multi-term, driver-aware search filter to a query.
     */
    public function apply(Builder $query, ?string $searchTerm): Builder
    {
        $terms = self::terms($searchTerm);
        if ($terms === []) {
            return $query;
        }

        return $query->where(function ($q) use ($terms) {
            foreach ($terms as $term) {
                $q->where(function ($inner) use ($term) {
                    foreach (['sku', 'model', 'part_name', 'category', 'brand'] as $column) {
                        self::whereLike($inner, $column, $term, 'contains', 'or');
                    }
                    // Audit #26: Check compatibility logic by year/parts (motorcycles relation)
                    $inner->orWhereHas('motorcycles', function ($motoQuery) use ($term) {
                        $motoQuery->where(function ($m) use ($term) {
                            foreach (['motorcycles.brand', 'motorcycles.model', 'motorcycles.year'] as $column) {
                                self::whereLike($m, $column, $term, 'contains', 'or');
                            }
                        });
                    });
                });
            }
        });
    }
}

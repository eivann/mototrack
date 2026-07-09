<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Audit #7: search logic extracted out of the InventoryItem model (SRP).
 * The model's `scopeSearch` now forwards here, so existing call sites keep
 * working and query chaining / pagination is preserved.
 */
class InventorySearchService
{
    /**
     * Apply the multi-term, driver-aware search filter to a query.
     */
    public function apply(Builder $query, ?string $searchTerm): Builder
    {
        $searchTerm = trim((string) $searchTerm);
        if ($searchTerm === '') {
            return $query;
        }

        $stopwords = ['for', 'the', 'and', 'with', 'in', 'on', 'at', 'to', 'a', 'an', 'of', 'my'];
        $all_terms = explode(' ', strtolower($searchTerm));
        $terms = array_filter($all_terms, function ($word) use ($stopwords) {
            return !in_array(trim($word), $stopwords) && strlen(trim($word)) > 1;
        });

        if (empty($terms)) {
            $terms = $all_terms;
        }

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $query->where(function ($q) use ($terms, $likeOperator) {
            foreach ($terms as $term) {
                $q->where(function ($inner) use ($term, $likeOperator) {
                    $inner->where('sku', $likeOperator, "%$term%")
                          ->orWhere('model', $likeOperator, "%$term%")
                          ->orWhere('part_name', $likeOperator, "%$term%")
                          ->orWhere('category', $likeOperator, "%$term%")
                          ->orWhere('brand', $likeOperator, "%$term%");
                });
            }
        });
    }
}

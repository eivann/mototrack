<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\CompatibilityMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicBrowseController extends Controller
{
    public function index()
    {
        $query = InventoryItem::with('variations')
            ->whereNull('parent_id')
            ->where(function ($q) {
                $q->where('quantity', '>', 0)
                  ->orWhereHas('variations', function ($vq) {
                      $vq->where('quantity', '>', 0);
                  });
            });
        $search = trim((string) request('search'));
        $category = trim((string) request('category'));
        $model = trim((string) request('model'));
        $sort = request('sort');

        if ($search !== '') {
            $query->search($search);
        }

        if ($model !== '') {
            // Same rule as the compatibility checker: only parts with a recorded
            // fitment for this exact motorcycle model (no free-text guessing).
            $ids = app(CompatibilityMatcher::class)->motorcycleIds('', $model, '');
            $query->whereHas('motorcycles', fn ($mq) => $mq->whereIn('motorcycles.id', $ids->all()));
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        if ($sort === 'low_high') {
            $query->orderBy('price', 'asc');
        }

        if ($sort === 'high_low') {
            $query->orderBy('price', 'desc');
        }

        $total = null;
        if ($search === '' && $model === '' && $category === '') {
            $total = Cache::remember('inventory_in_stock_parent_items_count_v2', now()->addMinutes(10), function () {
                return InventoryItem::whereNull('parent_id')
                    ->where(function ($q) {
                        $q->where('quantity', '>', 0)
                          ->orWhereHas('variations', function ($vq) {
                              $vq->where('quantity', '>', 0);
                          });
                    })->count();
            });
        }

        $items = $query->paginate(12, ['*'], 'page', null, $total)->withQueryString();

        $categories = InventoryItem::distinctCategories();

        return view('inventory.browse', compact('items', 'categories'));
    }

    public function searchParts(Request $request)
    {
        return $this->checkCompatibility($request);
    }

    public function suggestions(Request $request)
    {
        try {
            $data = $request->validate([
                'field' => 'nullable|string|in:search,category,part_name,brand,model,sku',
                'q' => 'nullable|string|max:80',
            ]);

            $field = $data['field'] ?? 'search';
            $term = trim($data['q'] ?? '');

            if ($field === 'search') {
                return response()->json($this->searchSuggestions($term));
            }

            if ($field === 'model') {
                // Model suggestions mirror the fitment records the filter matches against.
                return response()->json(
                    \App\Models\Motorcycle::query()
                        ->whereNotNull('model')->where('model', '!=', '')
                        ->when($term !== '', fn ($q) => $q->where('model', DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE', $term . '%'))
                        ->distinct()->orderBy('model')->limit(12)->pluck('model')
                        ->unique(fn ($v) => mb_strtolower($v))->values()
                        ->map(fn ($v) => ['value' => $v, 'label' => $v, 'field' => 'model'])
                );
            }

            return response()->json($this->fieldSuggestions($field, $term));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Search suggestions error: ' . $e->getMessage());
            return response()->json(['error' => 'An internal server error occurred.'], 500);
        }
    }

    public function compatibilityForm(Request $request)
    {
        $make = $request->input('make', '');
        $model = $request->input('model', '');
        $part = $request->input('part', '');

        $searchQuery = trim("$make $model $part");

        return view('inventory.compatibility', compact('searchQuery'));
    }

    public function checkCompatibility(Request $request)
    {
        try {
            $make = trim((string) $request->input('make', ''));
            $model = trim((string) $request->input('model', ''));
            $year = trim((string) $request->input('year', ''));
            $part = trim((string) $request->input('part', ''));

            if ($model === '' && $request->filled('q')) {
                $model = trim((string) $request->input('q'));
            }

            $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query = InventoryItem::with(['motorcycles', 'variations']);

            if ($part !== '') {
                $query->where(function ($q) use ($part, $likeOp) {
                    $q->where('part_name', $likeOp, "%{$part}%")
                      ->orWhere('category', $likeOp, "%{$part}%");
                });
            }

            $vehicleSelected = $make !== '' || $model !== '' || $year !== '';
            $matchedIds = collect();

            if ($vehicleSelected) {
                // Confirmed fitment only: the part must be linked (part_compatibility)
                // to a motorcycle row matching every selected criterion.
                $matchedIds = app(CompatibilityMatcher::class)->motorcycleIds($make, $model, $year);

                $query->whereHas('motorcycles', fn ($mq) => $mq->whereIn('motorcycles.id', $matchedIds->all()));
            }

            $items = $query->take(30)->get();

            if ($vehicleSelected) {
                $ids = $matchedIds->flip();
                $items->each(function ($item) use ($ids) {
                    $fits = $item->motorcycles->filter(fn ($m) => $ids->has($m->id));
                    $item->setAttribute('compatibility_status', 'confirmed');
                    $item->setAttribute('matched_fitment', $fits->map(
                        fn ($m) => trim("{$m->brand} {$m->model}" . ($m->year ? " ({$m->year})" : ''))
                    )->values()->all());
                });
            }

            return response()->json($items);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Check compatibility error: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function getCompatibilityOptions(Request $request)
    {
        try {
            $type = $request->query('type', 'makes');
            $make = trim((string) $request->query('make', ''));
            $model = trim((string) $request->query('model', ''));
            $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            // Options come ONLY from motorcycle (fitment) records so every choice
            // the customer can make corresponds to data the checker can match.
            $motorcycles = \App\Models\Motorcycle::query()->get(['brand', 'model', 'year']);
            $unique = fn ($values) => $values
                ->map(fn ($v) => trim((string) preg_replace('/\s+/u', ' ', (string) $v)))
                ->filter()
                ->unique(fn ($v) => mb_strtolower($v))
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            if ($type === 'makes') {
                return response()->json($unique($motorcycles->pluck('brand')));
            }

            $makeKey = CompatibilityMatcher::normalize($make);
            $modelKey = CompatibilityMatcher::normalize($model);
            $scoped = $motorcycles->filter(fn ($m) => $makeKey === '' || CompatibilityMatcher::normalize($m->brand) === $makeKey);

            if ($type === 'models') {
                return response()->json($unique($scoped->pluck('model')));
            }

            if ($type === 'years') {
                $scoped = $scoped->filter(fn ($m) => $modelKey === '' || CompatibilityMatcher::normalize($m->model) === $modelKey);

                return response()->json(CompatibilityMatcher::expandYears($scoped->pluck('year')));
            }

            return response()->json([]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Compatibility options error: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function getOptions(Request $request)
    {
        try {
            $field = $request->query('field');
            $dependsOn = $request->query('depends_on', []);

            $allowed = ['category', 'part_name', 'brand', 'model', 'sku'];
            if (!in_array($field, $allowed, true)) {
                return response()->json([]);
            }

            $query = InventoryItem::query()
                ->select($field)
                ->whereNotNull($field)
                ->where($field, '!=', '');

            if (is_array($dependsOn)) {
                foreach ($dependsOn as $key => $val) {
                    if (in_array($key, $allowed, true) && $val !== '' && $val !== null) {
                        $query->where($key, $val);
                    }
                }
            }

            $options = $query->distinct()->orderBy($field)->pluck($field)->filter()->values();
            return response()->json($options);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Get options error: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    private function searchSuggestions(string $term)
    {
        $columns = ['part_name', 'brand', 'model', 'sku'];

        return collect($columns)
            ->flatMap(fn ($column) => $this->fieldSuggestions($column, $term, 5))
            ->unique(fn ($item) => mb_strtolower($item['value']))
            ->take(12)
            ->values();
    }

    private function fieldSuggestions(string $field, string $term, int $limit = 12)
    {
        $allowed = ['category', 'part_name', 'brand', 'model', 'sku'];
        if (! in_array($field, $allowed, true)) {
            return collect();
        }

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return InventoryItem::query()
            ->select($field)
            ->whereNotNull($field)
            ->when($term !== '', fn ($query) => $query->where($field, $likeOperator, $term.'%'))
            ->distinct()
            ->orderBy($field)
            ->limit($limit)
            ->pluck($field)
            ->filter()
            ->map(fn ($value) => [
                'value' => $value,
                'label' => $value,
                'field' => $field,
            ])
            ->values();
    }
}

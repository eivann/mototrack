<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicBrowseController extends Controller
{
    public function index()
    {
        $query = InventoryItem::with('variations')->whereNull('parent_id');
        $search = trim((string) request('search'));
        $category = trim((string) request('category'));
        $sort = request('sort');

        if ($search !== '') {
            $query->search($search);
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

        $items = $query->paginate(12)->withQueryString();

        $categories = InventoryItem::distinctCategories();

        return view('inventory.browse', compact('items', 'categories'));
    }

    public function searchParts(Request $request)
    {
        try {
            $request->validate(['model' => 'nullable|string|max:255']);
            $items = InventoryItem::search($request->model)->take(20)->get();
            return response()->json($items);
        } catch (\Exception $e) {
            return response()->json(['error_debug' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    public function suggestions(Request $request)
    {
        $data = $request->validate([
            'field' => 'nullable|string|in:search,category,part_name,brand,model,sku',
            'q' => 'nullable|string|max:80',
        ]);

        $field = $data['field'] ?? 'search';
        $term = trim($data['q'] ?? '');

        if ($field === 'search') {
            return response()->json($this->searchSuggestions($term));
        }

        return response()->json($this->fieldSuggestions($field, $term));
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
        $request->validate(['model' => 'nullable|string|max:255']);
        $items = InventoryItem::search($request->model)->get();
        return response()->json($items);
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

<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicBrowseController extends Controller
{
    public function index()
    {
        $query = InventoryItem::query();

        if (request('search')) {
            $query->search(request('search'));
        }

        if (request('category')) {
            $query->where('category', request('category'));
        }

        if (request('sort') == 'low_high') {
            $query->orderBy('price', 'asc');
        }

        if (request('sort') == 'high_low') {
            $query->orderBy('price', 'desc');
        }

        $items = $query->paginate(12)->withQueryString();

        $categories = Cache::remember('inventory_categories', now()->addHours(6), function () {
            return InventoryItem::select('category')->distinct()->orderBy('category')->pluck('category');
        });

        return view('inventory.browse', compact('items', 'categories'));
    }

    public function searchParts(Request $request)
    {
        $request->validate(['model' => 'nullable|string|max:255']);
        $items = InventoryItem::search($request->model)->take(20)->get();
        return response()->json($items);
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
}

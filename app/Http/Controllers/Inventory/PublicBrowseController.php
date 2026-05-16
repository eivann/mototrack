<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

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

        return view('inventory.browse', compact('items'));
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

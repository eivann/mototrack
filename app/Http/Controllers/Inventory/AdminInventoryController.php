<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Http\Requests\Inventory\UpdateInventoryRequest;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Gate;

class AdminInventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request)
{
    $search = $request->search;
    $category = $request->category;

    $query = InventoryItem::search($search)->orderBy('part_name', 'asc')->orderBy('id', 'asc');
    
    if ($category) {
        $query->where('category', $category);
    }

    $items = $query->paginate(12)->withQueryString();
    
    $categories = InventoryItem::distinctCategories();
    $brands = InventoryItem::distinct('brand')->pluck('brand')->filter();

    return view('inventory.index', compact('items', 'search', 'category', 'categories', 'brands'));
}

public function store(StoreInventoryRequest $request)
{
    $data = $request->validated();
    
    if ($request->hasFile('image')) {
        $data['image_path'] = app(\App\Services\ProductImageService::class)->store($request->file('image'));
    }

    try {
        $this->inventoryService->store($data);
    } catch (\Throwable $e) {
        if (!empty($data['image_path'])) {
            app(\App\Services\ProductImageService::class)->delete($data['image_path']); // no orphan files
        }
        throw $e;
    }

    return redirect()->route('inventory.index')->with('success', 'Item added successfully!');
}

public function update(UpdateInventoryRequest $request, InventoryItem $item)
{
    $data = $request->validated();

    $images = app(\App\Services\ProductImageService::class);
    $oldImage = null;
    if ($request->hasFile('image')) {
        // Store the new picture first; the old one is removed only after the update succeeds, so a failure
        // can never leave the product pointing at a deleted file.
        $oldImage = $item->image_path;
        $data['image_path'] = $images->store($request->file('image'));
    }

    try {
        $this->inventoryService->update($item, $data);
    } catch (\Throwable $e) {
        if ($oldImage !== null && !empty($data['image_path'])) {
            $images->delete($data['image_path']);
        }
        throw $e;
    }

    // Never delete a picture another product still points at.
    if ($oldImage && $oldImage !== ($data['image_path'] ?? null)
        && !InventoryItem::where('image_path', $oldImage)->where('id', '!=', $item->id)->exists()) {
        $images->delete($oldImage);
    }

    return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
}

public function edit(InventoryItem $item)
{
    Gate::authorize('update', $item);

    $categories = InventoryItem::distinctCategories();

    return view('inventory.edit', compact('item', 'categories'));
}

public function destroy(InventoryItem $item)
{
    Gate::authorize('delete', $item);

    $this->inventoryService->destroy($item);

    return redirect()->route('inventory.index')->with('success', 'Item deleted successfully!');
}

public function lowStock()
{
    Gate::authorize('viewAny', InventoryItem::class);

    $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')->paginate(10);
    return view('inventory.low_stock', compact('lowStockItems'));
}

}

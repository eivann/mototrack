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

    $items = InventoryItem::search($search)
        ->paginate(12)
        ->withQueryString();

    return view('inventory.index', compact('items', 'search'));
}

public function store(StoreInventoryRequest $request)
{
    $this->inventoryService->store($request->validated());

    return redirect()->route('inventory.index')->with('success', 'Item added successfully!');
}

public function update(UpdateInventoryRequest $request, InventoryItem $item)
{
    $this->inventoryService->update($item, $request->validated());

    return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
}

public function edit(InventoryItem $item)
{
    Gate::authorize('update', $item);

    return view('inventory.edit', compact('item'));
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

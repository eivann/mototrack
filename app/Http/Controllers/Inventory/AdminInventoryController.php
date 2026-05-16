<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;

class AdminInventoryController extends Controller
{


public function index(Request $request)
{
    $search = $request->search;

    $items = InventoryItem::search($search)
        ->paginate(12)
        ->withQueryString();

    return view('inventory.index', compact('items', 'search'));
}

public function store(Request $request)
{
    $request->validate([
        'sku' => 'nullable|unique:inventory_items,sku',
        'category' => 'required|string|max:255',
        'part_name' => 'required|string|max:255',
        'brand' => 'required|string|max:255',
        'model' => 'required|string|max:255',
        'quantity' => 'required|integer|min:0',
        'price' => 'required|numeric|min:0',
    ]);

    InventoryItem::create($request->only(['sku', 'category', 'part_name', 'brand', 'model', 'quantity', 'price', 'notes']));

    return redirect()->route('inventory.index')->with('success', 'Item added successfully!');
}

public function update(Request $request, InventoryItem $item)
{
    $request->validate([
        'sku'       => 'nullable|unique:inventory_items,sku,' . $item->id,
        'category'  => 'required|string|max:255',
        'part_name' => 'required|string|max:255',
        'brand'     => 'required|string|max:255',
        'model'     => 'required|string|max:255',
        'quantity'  => 'required|integer|min:0',
        'price'     => 'required|numeric|min:0',
        'notes'     => 'nullable|string|max:1000',
    ]);

    $item->update($request->only(['sku', 'category', 'part_name', 'brand', 'model', 'quantity', 'price', 'notes']));

    // Reset low stock notification flag if stock is now above threshold
    if ($item->quantity > ($item->low_stock_threshold ?? 5)) {
        $item->low_stock_notified = false;
        $item->save();
    }

    return redirect()->route('inventory.index')->with('success', 'Item updated successfully!');
}
public function destroy(InventoryItem $item)
{
    $item->delete();

    return redirect()->route('inventory.index')->with('success', 'Item deleted successfully!');
}
public function edit(InventoryItem $item)
{
    return view('inventory.edit', compact('item'));
}

public function lowStock()
{

    // Fetch items where quantity is less than or equal to low_stock_threshold
    $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')->paginate(10);
    return view('inventory.low_stock', compact('lowStockItems'));
}

}

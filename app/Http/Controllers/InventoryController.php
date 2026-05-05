<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
   

public function index(Request $request)
{


    $search = $request->search;

    $items = InventoryItem::when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('part_name', 'like', "%$search%")
              ->orWhere('brand', 'like', "%$search%")
              ->orWhere('category', 'like', "%$search%");
        });
    })->paginate(12)->withQueryString();

    return view('inventory', compact('items', 'search'));
}
public function store(Request $request)
{



    $request->validate([
    'category' => 'required',
    'part_name' => 'required',
    'brand' => 'required',
    'model' => 'required',
    'quantity' => 'required|integer',
    'price' => 'required|numeric',
]);
    InventoryItem::create([
        'category' => ucwords($request->category),
        'part_name' => ucwords($request->part_name),
        'brand' => ucwords($request->brand),
        'model' => ucwords($request->model),
        'quantity' => $request->quantity,
        'notes' => $request->notes ? ucfirst($request->notes) : null,
        'price' => $request->price,
    ]);

    return redirect('/inventory');
}
public function update(Request $request, $id)
{
    $request->validate([
        'category'  => 'required|string|max:255',
        'part_name' => 'required|string|max:255',
        'brand'     => 'required|string|max:255',
        'model'     => 'required|string|max:255',
        'quantity'  => 'required|integer|min:0',
        'price'     => 'required|numeric|min:0',
        'notes'     => 'nullable|string|max:1000',
    ]);

    $item = InventoryItem::findOrFail($id);

    $item->update([
        'category'  => ucwords($request->category),
        'part_name' => ucwords($request->part_name),
        'brand'     => ucwords($request->brand),
        'model'     => ucwords($request->model),
        'quantity'  => $request->quantity,
        'notes'     => $request->notes ? ucfirst($request->notes) : null,
        'price'     => $request->price,
    ]);

    return redirect('/inventory');
}
public function destroy($id)
{
    $item = InventoryItem::findOrFail($id);
    $item->delete();

    return redirect('/inventory');
}
public function edit($id)
{
    $item = InventoryItem::findOrFail($id);
    return view('edit', compact('item'));
}

public function lowStock()
{

    // Fetch items where quantity is less than or equal to low_stock_threshold
    $lowStockItems = InventoryItem::whereColumn('quantity', '<=', 'low_stock_threshold')->get();
    return view('low_stock', compact('lowStockItems'));
}

}

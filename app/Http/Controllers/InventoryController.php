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
    if (Auth::user()->role !== 'owner') {
        abort(403);
    }

    $search = $request->search;

    $items = InventoryItem::when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('part_name', 'like', "%$search%")
              ->orWhere('brand', 'like', "%$search%")
              ->orWhere('category', 'like', "%$search%");
        });
    })->get();

    return view('inventory', compact('items', 'search'));
}
public function store(Request $request)
{

  if (Auth::user()->role !== 'owner') {
        abort(403);
    }

    $request->validate([
    'category' => 'required',
    'part_name' => 'required',
    'brand' => 'required',
    'model' => 'required',
    'quantity' => 'required|integer',
    'price' => 'required|numeric',
]);
    InventoryItem::create([
        'category' => $request->category,
        'part_name' => $request->part_name,
        'brand' => $request->brand,
        'model' => $request->model,
        'quantity' => $request->quantity,
        'notes' => $request->notes,
        'price' => $request->price,
    ]);

    return redirect('/inventory');
}
public function update(Request $request, $id)
{
    $item = InventoryItem::findOrFail($id);

    $item->update([
        'category' => $request->category,
        'part_name' => $request->part_name,
        'brand' => $request->brand,
        'model' => $request->model,
        'quantity' => $request->quantity,
        'notes' => $request->notes,
        'price' => $request->price,
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

}

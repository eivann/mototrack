<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function recordSaleForm()
    {
        $items = InventoryItem::all();
        return view('create', compact('items'));
    }

public function storeSale(Request $request)
{
    $request->validate([
        'inventory_item_id' => 'required',
        'quantity' => 'required|integer|min:1',
    ]);

    $item = InventoryItem::findOrFail($request->inventory_item_id);

    if ($request->quantity > $item->quantity) {
        return back()->with('error', 'Not enough stock!');
    }

    $total = $item->price * $request->quantity;

    Sale::create([
        'inventory_item_id' => $item->id,
        'quantity' => $request->quantity,
        'price' => $item->price, 
        'total_price' => $total,
    ]);

    $item->decrement('quantity', $request->quantity);

    return redirect('/sales')->with('success', 'Sale recorded!');
}
public function history(Request $request)
{
    $query = \App\Models\Sale::with('item');


    if ($request->from && $request->to) {
        $query->whereBetween('created_at', [$request->from, $request->to]);
    }

    if ($request->filter == 'today') {
        $query->whereDate('created_at', now());
    }

    $sales = $query->latest()->get();

    $totalSales = $sales->sum('total_price');

    $totalTransactions = $sales->count();

    return view('history', compact(
        'sales',
        'totalSales',
        'totalTransactions'
    ));
}
public function destroy($id)
{
    $sale = Sale::findOrFail($id);
     $item = $sale->item;

    if ($item) {
        $item->quantity += $sale->quantity;
        $item->save();
    }
    $sale->delete();

    return redirect()->back()->with('success', 'Sale deleted and stock restored!');
}
public function receipt($id)
{
    $sale = Sale::with('item')->findOrFail($id);

    return view('receipt_sales', compact('sale'));
}
}
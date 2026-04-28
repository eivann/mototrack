<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\PreOrder;


class CustomerController extends Controller
{
public function browse()
{
    $query = InventoryItem::query();

if (request('search')) {
    $query->where('part_name', 'like', '%' . request('search') . '%');
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

$items = $query->get();

    return view('browse', compact('items'));
}

public function compatibilityForm()
{
    return view('compatibility');
}

public function checkCompatibility(Request $request)
{
    $items = \App\Models\InventoryItem::where('model', 'LIKE', '%' . $request->model . '%')->get();

    return view('compatibility', compact('items'));
}
public function addToCart(Request $request)
{
    $item = InventoryItem::findOrFail($request->id);

    $cart = session()->get('cart', []);

    if(isset($cart[$item->id])) {
        $cart[$item->id]['quantity']++;
    } else {
        $cart[$item->id] = [
            "name" => $item->part_name,
            "price" => $item->price,
            "quantity" => 1
        ];
    }

    session()->put('cart', $cart);

    return back()->with('success', 'Added to cart!');
}

public function viewCart()
{
    $cart = session()->get('cart', []);
    return view('cart', compact('cart'));
}

public function checkout()
{
    $cart = session()->get('cart', []);
    return view('checkout', compact('cart'));
}

public function processCheckout()
{
    $cart = session()->get('cart', []);

    if(empty($cart)) {
        return back()->with('error', 'Cart is empty!');
    }

    foreach ($cart as $id => $item) {
        $inventory = InventoryItem::find($id);

        if (!$inventory || $inventory->quantity < $item['quantity']) {
            return back()->with('error', 'Stock issue detected!');
        }

        $total = $item['price'] * $item['quantity'];

        Sale::create([
            'inventory_item_id' => $id,
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'total_price' => $total,
        ]);

        $inventory->decrement('quantity', $item['quantity']);
    }

    session()->forget('cart');

    return redirect('/sales')->with('success', 'Order placed successfully!');
}

public function preOrderForm($id)
{
    $item = InventoryItem::findOrFail($id);
    return view('preorder', compact('item'));
}

public function storePreOrder(Request $request)
{
    PreOrder::create([
        'customer_name' => auth()->user()->name,
        'part_name' => $request->part_name,
        'model' => $request->model,
        'quantity' => $request->quantity,
        'down_payment' => $request->down_payment,
        'status' => 'pending',
    ]);

    return redirect('/customer-dashboard')->with('success', 'Pre-order submitted!');
}

public function preorderParts()
{
    $items = \App\Models\InventoryItem::where('quantity', 0)->get();

    return view('preorder_parts', compact('items'));
}

public function customPreorderForm()
{
    return view('preorder_custom');
}

public function storeCustomPreorder(Request $request)
{
    PreOrder::create([
        'customer_name' => auth()->user()->name,
        'part_name' => $request->part_name,
        'model' => $request->model,
        'quantity' => $request->quantity,
        'down_payment' => $request->down_payment,
        'status' => 'pending',
    ]);

    return redirect('/customer-dashboard')->with('success', 'Custom pre-order submitted!');
}

}

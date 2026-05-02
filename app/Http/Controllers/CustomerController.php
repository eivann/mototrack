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

        $items = $query->paginate(12)->withQueryString();

        return view('browse', compact('items'));
    }

    public function compatibilityForm(Request $request)
    {
        $make = $request->input('make', '');
        $model = $request->input('model', '');
        $part = $request->input('part', '');
        
        $searchQuery = trim("$make $model $part");
        
        return view('compatibility', compact('searchQuery'));
    }

    public function checkCompatibility(Request $request)
    {

        $query = $request->model;

        $items = \App\Models\InventoryItem::where('model', 'LIKE', "%$query%")
        ->orWhere('part_name', 'LIKE', "%$query%")
        ->get();

        return response()->json($items);
    }
   public function addToCart($id)
{
    $item = \App\Models\InventoryItem::findOrFail($id);

    $cart = session()->get('cart', []);

    if (isset($cart[$id])) {
        $cart[$id]['quantity']++;
    } else {
        $cart[$id] = [
            'part_name' => $item->part_name,
            'model' => $item->model,
            'price' => $item->price,
            'quantity' => 1,
        ];
    }

    session()->put('cart', $cart);

    return redirect()->back()->with('success', 'Added to cart!');
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

    public function removeFromCart($id)
    {
        $cart = session()->get('cart');

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return back()->with('success', 'Item removed!');
    }

    public function updateCart(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = session()->get('cart');

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] = $request->quantity;
            session()->put('cart', $cart);
        }

        return back()->with('success', 'Cart updated!');
    }

    public function processCheckout(Request $request)
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return back()->with('error', 'Cart is empty!');
        }

        $transactionReference = 'TRX-' . strtoupper(uniqid());
        
        // Calculate grand total first to compute change correctly
        $grandTotal = 0;
        foreach ($cart as $id => $item) {
            $grandTotal += $item['price'] * $item['quantity'];
        }

        $amountTendered = $request->input('amount_tendered');
        $change = $amountTendered !== null ? ($amountTendered - $grandTotal) : null;

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
                'transaction_reference' => $transactionReference,
                'amount_tendered' => $amountTendered,
                'change' => $change,
            ]);

            $inventory->decrement('quantity', $item['quantity']);
        }

        session()->forget('cart');

        return redirect('/receiptcart')->with('success', 'Order placed successfully!');
    }

    public function preOrderForm($id)
    {
        $item = \App\Models\InventoryItem::findOrFail($id);
        $item = InventoryItem::findOrFail($id);
        return view('preorder', compact('item'));
    }

    public function storePreOrder(Request $request)
    {
        $request->validate([
            'part_name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'down_payment' => 'required|numeric|min:0',
        ]);

        PreOrder::create([
            'customer_name' => ucwords(auth()->user()->name),
            'part_name' => ucwords($request->part_name),
            'model' => $request->model ? ucwords($request->model) : null,
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
        $request->validate([
            'part_name' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'down_payment' => 'required|numeric|min:0',
        ]);

        PreOrder::create([
            'customer_name' => ucwords(auth()->user()->name),
            'part_name' => ucwords($request->part_name),
            'model' => $request->model ? ucwords($request->model) : null,
            'quantity' => $request->quantity,
            'down_payment' => $request->down_payment,
            'status' => 'pending',
        ]);

        return redirect('/customer-dashboard')->with('success', 'Custom pre-order submitted!');
    }

    public function increase($id)
    {
        $cart = session()->get('cart');

        if (isset($cart[$id])) {
            $cart[$id]['quantity']++;
        }

        session()->put('cart', $cart);

        return back();
    }

    public function decrease($id)
    {
        $cart = session()->get('cart');

        if (isset($cart[$id])) {
            $cart[$id]['quantity']--;

            if ($cart[$id]['quantity'] <= 0) {
                unset($cart[$id]);
            }
        }

        session()->put('cart', $cart);

        return back();
    }


    public function receipt()
    {
        $orders = PreOrder::where('customer_name', auth()->user()->name)
            ->latest()
            ->take(10)
            ->get();

        return view('receiptcart', compact('orders'));
    }

    public function searchParts(Request $request)
    {
        $query = trim($request->model);
        if (empty($query)) {
            return response()->json([]);
        }

        $stopwords = ['for', 'the', 'and', 'with', 'in', 'on', 'at', 'to', 'a', 'an', 'of', 'my'];
        $all_terms = explode(' ', strtolower($query));
        $terms = array_filter($all_terms, function($word) use ($stopwords) {
            return !in_array(trim($word), $stopwords) && strlen(trim($word)) > 1;
        });

        if (empty($terms)) {
            $terms = $all_terms;
        }

        $items = \App\Models\InventoryItem::query();

        foreach ($terms as $term) {
            $items->where(function ($q) use ($term) {
                $q->where('model', 'LIKE', "%$term%")
                  ->orWhere('part_name', 'LIKE', "%$term%")
                  ->orWhere('category', 'LIKE', "%$term%")
                  ->orWhere('brand', 'LIKE', "%$term%");
            });
        }

        return response()->json($items->take(20)->get());
    }

}

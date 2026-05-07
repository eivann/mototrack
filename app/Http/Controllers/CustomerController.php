<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\PreOrder;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StatusUpdated;


class CustomerController extends Controller
{
    public function browse()
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
        $items = InventoryItem::search($request->model)->get();
        return response()->json($items);
    }
    public function addToCart(Request $request, $id)
    {
        $item = \App\Models\InventoryItem::findOrFail($id);

        if ($item->quantity <= 0) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Item is currently out of stock.'], 422);
            }
            return redirect()->back()->with('error', 'Item is currently out of stock.');
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            if ($cart[$id]['quantity'] + 1 > $item->quantity) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Cannot add more. Stock limit reached.'], 422);
                }
                return redirect()->back()->with('error', 'Cannot add more. Stock limit reached.');
            }
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

        if ($request->wantsJson()) {
            return response()->json(['success' => 'Added to cart!', 'cart_count' => count($cart)]);
        }

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
            $inventory = InventoryItem::find($id);
            
            if ($inventory && $request->quantity > $inventory->quantity) {
                return back()->with('error', 'Cannot exceed available stock for ' . $inventory->part_name);
            }

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
        $paymentMethod = $request->input('payment_method', 'gcash');
        
        // Find or create customer profile for logged-in user
        $customerId = null;
        if (auth()->check()) {
            $customer = auth()->user()->customer;
            if (!$customer) {
                $customer = \App\Models\Customer::create([
                    'user_id' => auth()->id(),
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                ]);
            }
            $customerId = $customer->id;
        }

        try {
            $grandTotal = \Illuminate\Support\Facades\DB::transaction(function () use ($cart, $transactionReference, $request, $paymentMethod, $customerId) {
                $grandTotal = 0;
                $isFirstItem = true;

                foreach ($cart as $id => $item) {
                    // Lock the row to prevent race conditions
                    $inventory = InventoryItem::lockForUpdate()->find($id);

                    if (!$inventory || $inventory->quantity < $item['quantity']) {
                        throw new \Exception('Stock issue detected for: ' . ($inventory ? $inventory->part_name : 'Unknown item'));
                    }

                    // Use the DATABASE price, not the session price
                    $total = $inventory->price * $item['quantity'];
                    $grandTotal += $total;

                    Sale::create([
                        'inventory_item_id' => $id,
                        'customer_id' => $customerId, // Linked to Customer ID
                        'quantity' => $item['quantity'],
                        'price' => $inventory->price,
                        'total_price' => $total,
                        'transaction_reference' => $transactionReference,
                        'amount_tendered' => $isFirstItem ? ($request->input('amount_tendered') ?: $grandTotal) : 0,
                        'change' => $isFirstItem ? (max(0, ($request->input('amount_tendered') ?? 0) - $grandTotal)) : 0,
                    ]);

                    $inventory->decrement('quantity', $item['quantity']);
                    $isFirstItem = false;
                }

                return $grandTotal;
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        session()->forget('cart');
        session()->put('last_trx', $transactionReference);

        // Notify customer
        if (auth()->check()) {
            auth()->user()->notify(new StatusUpdated(
                'Order Placed',
                "Your order #{$transactionReference} has been placed successfully. We'll notify you when it's ready for pickup!",
                '/customer-dashboard'
            ));
        }

        // Notify Owner
        $owners = User::where('role', 'owner')->get();
        Notification::send($owners, new StatusUpdated(
            'New Order Received',
            "Customer " . auth()->user()->name . " has placed a new order #{$transactionReference}.",
            "/sales/{$transactionReference}/receipt"
        ));

        $paymentAmount = $request->input('amount_tendered') ?: $grandTotal;

        if ($paymentMethod === 'gcash') {
            try {
                \Xendit\Configuration::setXenditKey(config('services.xendit.secret_key'));
                $apiInstance = new \Xendit\Invoice\InvoiceApi();
                $create_invoice_request = new \Xendit\Invoice\CreateInvoiceRequest([
                    'external_id' => $transactionReference,
                    'description' => 'MotoTrack Order ' . $transactionReference,
                    'amount' => $paymentAmount,
                    'invoice_duration' => 86400,
                    'currency' => 'PHP',
                    'payment_methods' => ['GCASH'],
                    'success_redirect_url' => url('/receiptcart?trx=' . $transactionReference),
                    'failure_redirect_url' => url('/cart'),
                ]);
                $result = $apiInstance->createInvoice($create_invoice_request);
                return redirect($result['invoice_url']);
            } catch (\Exception $e) {
                return redirect('/receiptcart')->with('error', 'Order saved but GCash failed: ' . $e->getMessage());
            }
        }

        return redirect('/receiptcart')->with('success', 'Order placed successfully!');
    }

    public function preOrderForm($id)
    {
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
            'my_extra_field' => 'prohibited',
            'g-recaptcha-response' => ['required', function ($attribute, $value, $fail) {
                $response = \Illuminate\Support\Facades\Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

                if (!$response->json('success')) {
                    $fail('The reCAPTCHA validation failed.');
                }
            }],
        ], [
            'g-recaptcha-response.required' => 'Please complete the CAPTCHA.',
        ]);

        // Find or create customer profile
        $customerId = null;
        if (auth()->check()) {
            $customer = auth()->user()->customer;
            if (!$customer) {
                $customer = \App\Models\Customer::create([
                    'user_id' => auth()->id(),
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                ]);
            }
            $customerId = $customer->id;
        }

        $preorder = PreOrder::create([
            'customer_id' => $customerId, // Linked to Customer ID
            'customer_name' => ucwords(auth()->user()->name),
            'part_name' => ucwords($request->part_name),
            'model' => $request->model ? ucwords($request->model) : null,
            'quantity' => $request->quantity,
            'down_payment' => $request->down_payment,
            'status' => 'pending',
            'is_paid' => false,
        ]);

        $externalId = 'PRE-' . $preorder->id . '-' . time();

        try {
            \Xendit\Configuration::setXenditKey(config('services.xendit.secret_key'));
            $apiInstance = new \Xendit\Invoice\InvoiceApi();
            $create_invoice_request = new \Xendit\Invoice\CreateInvoiceRequest([
                'external_id' => $externalId,
                'description' => 'Down Payment for Pre-order: ' . $request->part_name,
                'amount' => $request->down_payment,
                'invoice_duration' => 86400,
                'currency' => 'PHP',
                'payment_methods' => ['GCASH'],
                'success_redirect_url' => url('/customer-dashboard?success=preorder_paid&id=' . $preorder->id),
                'failure_redirect_url' => url('/customer-dashboard?error=preorder_failed&id=' . $preorder->id),
            ]);
            $result = $apiInstance->createInvoice($create_invoice_request);
            
            $preorder->update([
                'xendit_invoice_id' => $result['id'],
                'xendit_invoice_url' => $result['invoice_url']
            ]);

            // Notify customer
            auth()->user()->notify(new StatusUpdated(
                'Pre-order Initiated',
                "Your pre-order for {$request->part_name} has been received. Please complete the down payment of ₱" . number_format($request->down_payment, 2) . " via GCash to proceed.",
                $result['invoice_url']
            ));

            // Notify Owner
            $owners = User::where('role', 'owner')->get();
            Notification::send($owners, new StatusUpdated(
                'New Pre-order Request',
                "Customer " . auth()->user()->name . " has requested a pre-order for {$request->part_name} (Payment Pending).",
                "/preorders"
            ));

            return redirect($result['invoice_url']);
        } catch (\Exception $e) {
            return redirect('/customer-dashboard')->with('error', 'Pre-order saved but payment redirection failed: ' . $e->getMessage());
        }
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

    public function increase($id)
    {
        $cart = session()->get('cart');

        if (isset($cart[$id])) {
            $inventory = InventoryItem::find($id);
            
            if ($inventory && $cart[$id]['quantity'] + 1 > $inventory->quantity) {
                return back()->with('error', 'Cannot exceed available stock for ' . $inventory->part_name);
            }

            $cart[$id]['quantity']++;
            session()->put('cart', $cart);
        }

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


    public function receipt(Request $request)
    {
        $trx = $request->query('trx') ?? session('last_trx');

        if (!$trx) {
            return redirect('/customer-dashboard')->with('error', 'No recent receipt found.');
        }

        $orders = Sale::with('item')->where('transaction_reference', $trx)->get();

        if ($orders->isEmpty()) {
            return redirect('/customer-dashboard')->with('error', 'Receipt not found.');
        }

        return view('receiptcart', compact('orders', 'trx'));
    }

    public function searchParts(Request $request)
    {
        $items = InventoryItem::search($request->model)->take(20)->get();
        return response()->json($items);
    }

}

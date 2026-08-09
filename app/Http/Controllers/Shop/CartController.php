<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        $cart = $this->cartService->getCart();
        return view('shop.cart', compact('cart'));
    }

    public function add(Request $request, $id)
    {
        try {
            $cart = $this->cartService->addItem($id);
            
            if ($request->wantsJson()) {
                return response()->json(['success' => 'Added to cart!', 'cart_count' => count($cart)]);
            }

            return redirect()->back()->with('success', 'Added to cart!');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function remove($id)
    {
        $this->cartService->removeItem($id);
        return back()->with('success', 'Item removed!');
    }

    public function increase($id)
    {
        try {
            $cart = $this->cartService->getCart();

            if (!isset($cart[$id])) {
                return back()->with('error', 'Item not found in cart.');
            }

            $this->cartService->updateQuantity($id, $cart[$id]['quantity'] + 1);
            return back();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function decrease($id)
    {
        try {
            $cart = $this->cartService->getCart();

            if (!isset($cart[$id])) {
                return back()->with('error', 'Item not found in cart.');
            }

            if ($cart[$id]['quantity'] <= 1) {
                $this->cartService->removeItem($id);
                return back()->with('success', 'Item removed from cart.');
            }

            $this->cartService->updateQuantity($id, $cart[$id]['quantity'] - 1);
            return back();
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        try {
            $this->cartService->updateQuantity($id, $request->quantity);
            return back()->with('success', 'Cart updated!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

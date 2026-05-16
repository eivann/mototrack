<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * Get the current cart from session.
     */
    public function getCart()
    {
        return Session::get('cart', []);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem($id, $quantity = 1)
    {
        $quantity = max(1, (int) $quantity);
        $item = InventoryItem::findOrFail($id);

        if ($item->quantity <= 0) {
            throw new \Exception('Item is currently out of stock.');
        }

        $cart = $this->getCart();

        if (isset($cart[$id])) {
            if ($cart[$id]['quantity'] + $quantity > $item->quantity) {
                throw new \Exception('Cannot add more. Stock limit reached.');
            }
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'part_name' => $item->part_name,
                'model' => $item->model,
                'price' => $item->price,
                'quantity' => $quantity,
            ];
        }

        Session::put('cart', $cart);
        return $cart;
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem($id)
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            unset($cart[$id]);
            Session::put('cart', $cart);
        }

        return $cart;
    }

    /**
     * Update item quantity in the cart.
     */
    public function updateQuantity($id, $quantity)
    {
        $quantity = max(1, (int) $quantity);
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            $item = InventoryItem::find($id);

            if ($item && $quantity > $item->quantity) {
                throw new \Exception('Cannot exceed available stock for ' . $item->part_name);
            }

            $cart[$id]['quantity'] = $quantity;
            Session::put('cart', $cart);
        }

        return $cart;
    }

    /**
     * Clear the cart.
     */
    public function clear()
    {
        Session::forget('cart');
    }
}

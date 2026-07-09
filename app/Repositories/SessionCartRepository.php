<?php

namespace App\Repositories;

use App\Contracts\CartRepository;
use Illuminate\Support\Facades\Session;

/**
 * Audit #5: session-backed cart persistence (the original behavior),
 * now behind the CartRepository contract.
 */
class SessionCartRepository implements CartRepository
{
    public function get(): array
    {
        return Session::get('cart', []);
    }

    public function put(array $cart): void
    {
        Session::put('cart', $cart);
    }

    public function forget(): void
    {
        Session::forget('cart');
    }
}

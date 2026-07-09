<?php

namespace App\Contracts;

/**
 * Audit #5: abstracts cart persistence so CartService is no longer bound to
 * the Session facade. Enables API / queue / persistent-cart implementations
 * without touching the service's business rules.
 */
interface CartRepository
{
    /**
     * Get the current cart array (item id => snapshot).
     */
    public function get(): array;

    /**
     * Persist the full cart array.
     */
    public function put(array $cart): void;

    /**
     * Empty the cart.
     */
    public function forget(): void;
}

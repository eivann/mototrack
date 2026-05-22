<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->role === 'owner'
            || ($sale->customer_id && $sale->customer && $sale->customer->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->role === 'owner';
    }
}

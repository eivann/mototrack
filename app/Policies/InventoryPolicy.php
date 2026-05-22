<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Only owners can view the admin inventory management page.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'owner';
    }

    /**
     * Any authenticated user can view a single inventory item (for browse/compat).
     */
    public function view(?User $user, InventoryItem $item): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->role === 'owner';
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->role === 'owner';
    }
}

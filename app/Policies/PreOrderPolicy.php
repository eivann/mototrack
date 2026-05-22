<?php

namespace App\Policies;

use App\Models\PreOrder;
use App\Models\User;

class PreOrderPolicy
{
    /**
     * Owners can see all pre-orders; customers only their own.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function view(User $user, PreOrder $preorder): bool
    {
        return $user->role === 'owner'
            || ($preorder->customer && $preorder->customer->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PreOrder $preorder): bool
    {
        return $user->role === 'owner';
    }

    public function delete(User $user, PreOrder $preorder): bool
    {
        return $user->role === 'owner';
    }

    public function markPaid(User $user, PreOrder $preorder): bool
    {
        return $user->role === 'owner';
    }

    public function complete(User $user, PreOrder $preorder): bool
    {
        return $user->role === 'owner';
    }

    public function cancel(User $user, PreOrder $preorder): bool
    {
        return $user->role === 'owner';
    }
}

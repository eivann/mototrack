<?php

namespace App\Policies;

use App\Models\Repair;
use App\Models\User;

class RepairPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function view(User $user, Repair $repair): bool
    {
        return $user->role === 'owner'
            || ($repair->customer && $repair->customer->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function update(User $user, Repair $repair): bool
    {
        return $user->role === 'owner';
    }

    public function delete(User $user, Repair $repair): bool
    {
        return $user->role === 'owner';
    }

    public function markPaid(User $user, Repair $repair): bool
    {
        return $user->role === 'owner';
    }
}

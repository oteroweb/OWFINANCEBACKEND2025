<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Debt;

class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function view(User $user, Debt $debt): bool
    {
        return $user->isAdmin() || (int) $debt->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function update(User $user, Debt $debt): bool
    {
        return $user->isAdmin() || (int) $debt->user_id === (int) $user->id;
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $user->isAdmin() || (int) $debt->user_id === (int) $user->id;
    }
}

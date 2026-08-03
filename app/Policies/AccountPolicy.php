<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Account;

class AccountPolicy
{
    // Account has no user_id column — ownership is via the account_user pivot
    // (multi-owner, e.g. shared accounts), with is_owner marking who can delete.

    public function viewAny(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function view(User $user, Account $account): bool
    {
        return $user->isAdmin() || $account->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function update(User $user, Account $account): bool
    {
        return $user->isAdmin() || $account->users()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->isAdmin() || $account->users()->wherePivot('is_owner', true)->where('users.id', $user->id)->exists();
    }
}

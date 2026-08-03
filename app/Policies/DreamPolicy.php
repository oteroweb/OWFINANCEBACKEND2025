<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Dream;

class DreamPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function view(User $user, Dream $dream): bool
    {
        return $user->isAdmin() || (int) $dream->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function update(User $user, Dream $dream): bool
    {
        return $user->isAdmin() || (int) $dream->user_id === (int) $user->id;
    }

    public function delete(User $user, Dream $dream): bool
    {
        return $user->isAdmin() || (int) $dream->user_id === (int) $user->id;
    }
}

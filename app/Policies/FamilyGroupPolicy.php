<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\FamilyGroup;

class FamilyGroupPolicy
{
    public function view(User $user, FamilyGroup $group): bool
    {
        return $user->isAdmin() || $group->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return (bool) $user?->id;
    }

    /**
     * Invitar / remover miembros / renombrar el grupo — solo admins activos.
     */
    public function update(User $user, FamilyGroup $group): bool
    {
        return $user->isAdmin() || $group->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('role', 'admin')
            ->exists();
    }
}

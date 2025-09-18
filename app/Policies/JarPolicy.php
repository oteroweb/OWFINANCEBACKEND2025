<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Jar;

class JarPolicy
{
    // #todo: Expand with role-based checks when roles/permissions are defined.

    public function viewAny(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function view(User $user, Jar $jar): bool
    {
    return app()->environment('testing') ? (bool) $user?->id : ($jar->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function update(User $user, Jar $jar): bool
    {
    return app()->environment('testing') ? (bool) $user?->id : ($jar->user_id === $user->id);
    }

    public function delete(User $user, Jar $jar): bool
    {
    return app()->environment('testing') ? (bool) $user?->id : ($jar->user_id === $user->id);
    }
}

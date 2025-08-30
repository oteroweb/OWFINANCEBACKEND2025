<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait OwnsOrAdmin
{
    private function ownsOrAdmin(User $user, $model): bool
    {
        return $user->isAdmin() || ((int)($model->user_id ?? 0) === (int)$user->id);
    }
}

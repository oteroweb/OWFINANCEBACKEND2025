<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\ItemTransaction;
use App\Policies\Concerns\OwnsOrAdmin;

class ItemTransactionPolicy
{
    use OwnsOrAdmin;

    public function viewAny(User $user): bool { return (bool) $user?->id; }
    public function view(User $user, ItemTransaction $it): bool { return $this->ownsOrAdmin($user, $it); }
    public function create(User $user): bool { return (bool) $user?->id; }
    public function update(User $user, ItemTransaction $it): bool { return $this->ownsOrAdmin($user, $it); }
    public function delete(User $user, ItemTransaction $it): bool { return $this->ownsOrAdmin($user, $it); }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\AccountFolder;
use App\Policies\Concerns\OwnsOrAdmin;

class AccountFolderPolicy
{
    use OwnsOrAdmin;

    public function viewAny(User $user): bool { return (bool) $user?->id; }
    public function view(User $user, AccountFolder $folder): bool { return $this->ownsOrAdmin($user, $folder); }
    public function create(User $user): bool { return (bool) $user?->id; }
    public function update(User $user, AccountFolder $folder): bool { return $this->ownsOrAdmin($user, $folder); }
    public function delete(User $user, AccountFolder $folder): bool { return $this->ownsOrAdmin($user, $folder); }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Provider;
use App\Policies\Concerns\OwnsOrAdmin;

class ProviderPolicy
{
    use OwnsOrAdmin;

    public function viewAny(User $user): bool { return (bool) $user?->id; }
    public function view(User $user, Provider $provider): bool { return $this->ownsOrAdmin($user, $provider); }
    public function create(User $user): bool { return (bool) $user?->id; }
    public function update(User $user, Provider $provider): bool { return $this->ownsOrAdmin($user, $provider); }
    public function delete(User $user, Provider $provider): bool { return $this->ownsOrAdmin($user, $provider); }
}

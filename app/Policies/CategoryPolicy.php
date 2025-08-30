<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Category;
use App\Policies\Concerns\OwnsOrAdmin;

class CategoryPolicy
{
    use OwnsOrAdmin;

    public function viewAny(User $user): bool { return (bool) $user?->id; }
    public function view(User $user, Category $category): bool { return $this->ownsOrAdmin($user, $category); }
    public function create(User $user): bool { return (bool) $user?->id; }
    public function update(User $user, Category $category): bool { return $this->ownsOrAdmin($user, $category); }
    public function delete(User $user, Category $category): bool { return $this->ownsOrAdmin($user, $category); }
}

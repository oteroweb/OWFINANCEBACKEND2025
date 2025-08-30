<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Transaction;
use App\Policies\Concerns\OwnsOrAdmin;

class TransactionPolicy
{
    use OwnsOrAdmin;

    public function viewAny(User $user): bool { return (bool) $user?->id; }
    public function view(User $user, Transaction $transaction): bool { return $this->ownsOrAdmin($user, $transaction); }
    public function create(User $user): bool { return (bool) $user?->id; }
    public function update(User $user, Transaction $transaction): bool { return $this->ownsOrAdmin($user, $transaction); }
    public function delete(User $user, Transaction $transaction): bool { return $this->ownsOrAdmin($user, $transaction); }
}

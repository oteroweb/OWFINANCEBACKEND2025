<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Business;
use App\Models\Entities\Transaction;
use App\Policies\Concerns\OwnsOrAdmin;

class TransactionPolicy
{
    use OwnsOrAdmin;

    public function viewAny(User $user): bool { return (bool) $user?->id; }
    public function view(User $user, Transaction $transaction): bool
    {
        // OWF-370: un movimiento de empresa lo ve cualquier miembro activo
        if ($transaction->business_id) {
            return $user->isAdmin() || Business::roleOf($transaction->business_id, $user->id) !== null;
        }
        return $this->ownsOrAdmin($user, $transaction);
    }
    public function create(User $user): bool { return (bool) $user?->id; }
    public function update(User $user, Transaction $transaction): bool { return $this->canWrite($user, $transaction); }
    public function delete(User $user, Transaction $transaction): bool { return $this->canWrite($user, $transaction); }

    /** Empresa: owner o accountant escriben (viewer solo lee); personal: dueño o admin. */
    private function canWrite(User $user, Transaction $transaction): bool
    {
        if ($transaction->business_id) {
            return $user->isAdmin()
                || in_array(Business::roleOf($transaction->business_id, $user->id), ['owner', 'accountant'], true);
        }
        return $this->ownsOrAdmin($user, $transaction);
    }
}

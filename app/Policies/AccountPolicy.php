<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Entities\Account;
use App\Models\Entities\Business;

class AccountPolicy
{
    // Account has no user_id column — ownership is via the account_user pivot
    // (multi-owner, e.g. shared accounts), with is_owner marking who can delete.

    /**
     * OWF-370: una cuenta de empresa (business_id) NO se rige por el pivot account_user
     * sino por el rol del usuario en la empresa: cualquier miembro activo la ve, solo el
     * dueño cambia su estructura (editar/borrar/ajustar saldo). Un contador registra
     * movimientos pero no toca la cuenta. Las cuentas de empresa no se comparten por el
     * grupo familiar (un contador no es familia).
     */
    private function businessRole(User $user, Account $account): ?string
    {
        return Business::roleOf($account->business_id, $user->id);
    }

    public function viewAny(User $user): bool
    {
        return (bool) $user?->id;
    }

    public function view(User $user, Account $account): bool
    {
        if ($account->business_id) {
            return $user->isAdmin() || $this->businessRole($user, $account) !== null;
        }
        return $user->isAdmin() || $account->users()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return (bool) $user?->id;
    }

    // OWF: antes cualquier fila en account_user (dueño o compartida) daba permiso de
    // update completo. Con niveles de permiso (manage|view_full|view_balance), solo
    // el dueño real (is_owner) o alguien con permission='manage' puede editar.
    public function update(User $user, Account $account): bool
    {
        if ($user->isAdmin()) return true;
        if ($account->business_id) return $this->businessRole($user, $account) === 'owner';
        $pivot = $account->users()->where('users.id', $user->id)->first();
        if (!$pivot) return false;
        if ($pivot->is_owner) return true;
        return $pivot->permission === 'manage';
    }

    public function delete(User $user, Account $account): bool
    {
        if ($account->business_id) {
            return $user->isAdmin() || $this->businessRole($user, $account) === 'owner';
        }
        return $user->isAdmin() || $account->users()->wherePivot('is_owner', true)->where('users.id', $user->id)->exists();
    }

    /**
     * OWF: solo el dueño real de la cuenta puede compartirla o revocar un acceso ya dado.
     */
    public function share(User $user, Account $account): bool
    {
        if ($account->business_id) return false;
        return $user->isAdmin() || $account->users()->wherePivot('is_owner', true)->where('users.id', $user->id)->exists();
    }

    /**
     * OWF: quién puede ver el DETALLE de movimientos de la cuenta — separado de view()
     * (que solo gatea ver la cuenta/saldo). Un permiso 'view_balance' ve la cuenta pero
     * no la lista de transacciones.
     */
    public function viewTransactions(User $user, Account $account): bool
    {
        if ($user->isAdmin()) return true;
        if ($account->business_id) return $this->businessRole($user, $account) !== null;
        $pivot = $account->users()->where('users.id', $user->id)->first();
        if (!$pivot) return false;
        if ($pivot->is_owner) return true;
        return in_array($pivot->permission, ['manage', 'view_full'], true);
    }
}

<?php

namespace App\Policies;

use App\Models\Entities\Business;
use App\Models\User;

class BusinessPolicy
{
    /** Cualquier miembro ACTIVO ve la empresa y toda su contabilidad. */
    public function view(User $user, Business $business): bool
    {
        return $user->isAdmin() || $business->roleFor($user->id) !== null;
    }

    /** Estructura (datos de la empresa, cuentas, categorías) y accesos: solo dueño. */
    public function manage(User $user, Business $business): bool
    {
        return $user->isAdmin() || $business->roleFor($user->id) === 'owner';
    }

    /** Registrar/editar movimientos: dueño y contador. */
    public function write(User $user, Business $business): bool
    {
        return $user->isAdmin() || in_array($business->roleFor($user->id), ['owner', 'accountant'], true);
    }
}

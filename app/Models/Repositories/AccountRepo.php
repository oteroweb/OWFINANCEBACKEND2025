<?php

namespace App\Models\Repositories;

use App\Models\Entities\Account;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AccountRepo
{
    public function all(array $params = [])
    {
        $query = Account::whereIn('active', [1, 0])
            ->with(['currency', 'accountType', 'users']);

        // Active filter (overrides base whereIn when provided)
        if (array_key_exists('active', $params) && $params['active'] !== null && $params['active'] !== '') {
            $active = (int) filter_var($params['active'], FILTER_VALIDATE_BOOLEAN);
            $query->where('active', $active);
        }

        // Optional filters
        if (!empty($params['currency_id'])) {
            $query->where('currency_id', $params['currency_id']);
        }
        if (!empty($params['currency'])) {
            $needle = $params['currency'];
            $query->whereHas('currency', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%")
                  ->orWhere('code', 'like', "%{$needle}%");
            });
        }
        if (!empty($params['account_type_id'])) {
            $query->where('account_type_id', $params['account_type_id']);
        }
        if (!empty($params['account_type'])) {
            $needle = $params['account_type'];
            $query->whereHas('accountType', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%");
            });
        }
        if (!empty($params['user_id'])) {
            $userId = $params['user_id'];
            $isOwner = $params['is_owner'] ?? null;
            $query->whereHas('users', function ($q) use ($userId, $isOwner) {
                $q->where('users.id', $userId);
                if ($isOwner !== null && $isOwner !== '') {
                    $q->where('account_user.is_owner', (int) filter_var($isOwner, FILTER_VALIDATE_BOOLEAN));
                }
            });
        }
        if (!empty($params['user'])) {
            $needle = $params['user'];
            $query->whereHas('users', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%")
                  ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        // Search across fields and relations
        if (!empty($params['search'])) {
            $this->applyGlobalSearch($query, $params['search'], [
                'name',
                'currency.name',
                'accountType.name',
                'users.name',
            ]);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'name';
        $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $query->orderBy($sortBy, $descending ? 'desc' : 'asc');

        // Pagination
        if (!empty($params['page'])) {
            $perPage = $params['per_page'] ?? 15;
            $result = $query->paginate($perPage);
            foreach ($result as $account) {
                $account->balance_calculado = $this->calculateBalance($account->id);
            }
            return $result;
        }

        $accounts = $query->get();
        foreach ($accounts as $account) {
            // Si existe balance_cached usarlo, sino calcular on-demand (initial + payments)
            $account->balance_calculado = $account->balance_cached ?? $this->calculateBalanceFromInitialByType($account->id);
        }
        return $accounts;
    }

    /**
     * Suma todos los movimientos de pago (PaymentTransaction) activos asociados a la cuenta.
     * NOTA: Esta función retorna únicamente la suma de movimientos, sin el saldo inicial.
     */
    public function calculateBalance($accountId)
    {
        // Suma de pagos (modelo nuevo)
        $sumPayments = (float) \App\Models\Entities\PaymentTransaction::where('account_id', $accountId)
            ->where('active', 1)
            ->whereHas('transaction', function ($q) {
                $q->where('active', 1)
                  ->where('include_in_balance', 1);
            })
            ->sum('amount');

        // Compatibilidad: incluir transacciones sin paymentTransactions asignados explícitamente
        // que afectan directamente a account_id (p.ej., ajustes o legacy)
        $sumLegacy = (float) \App\Models\Entities\Transaction::where('account_id', $accountId)
            ->where('active', 1)
            ->where('include_in_balance', 1)
            ->whereDoesntHave('paymentTransactions')
            ->sum('amount');

        if (abs($sumPayments) > 0.00001) {
            // Caso normal: hay pagos; sumar además transacciones legacy sin pagos
            return round($sumPayments + $sumLegacy, 2);
        }

        // Fallback: si por alguna razón no hay pagos contados para esta cuenta,
        // usar la suma de transacciones clásicas por account_id (evita doble conteo)
        $sumByAccount = (float) \App\Models\Entities\Transaction::where('account_id', $accountId)
            ->where('active', 1)
            ->where('include_in_balance', 1)
            ->sum('amount');
        return round($sumByAccount, 2);
    }

    /**
     * Recalcula y persiste balance_cached.
     */
    public function recalcAndStore($accountId): float
    {
        $value = $this->calculateBalance($accountId);
        Account::where('id', $accountId)->update(['balance_cached' => $value]);
        return $value;
    }

    /**
     * Calcula el balance usando el saldo inicial de la cuenta y sumando/restando
     * las transacciones activas e incluidas en balance según su tipo (income/expense).
     * Para otros tipos, usa el monto tal cual esté almacenado (firmado).
     */
    public function calculateBalanceFromInitialByType(int $accountId): float
    {
        // Nuevo comportamiento: initial + suma firmada de payment_transactions activos
        $initial = (float) Account::where('id', $accountId)->value('initial');
        $sum = $this->calculateBalance($accountId);
        return round($initial + $sum, 2);
    }

    /**
     * Recalcula y persiste balance_cached y balance desde initial + signos por tipo.
     */
    public function recalcAndStoreFromInitialByType(int $accountId): float
    {
        $value = $this->calculateBalanceFromInitialByType($accountId);
        Account::where('id', $accountId)->update([
            'balance_cached' => $value,
            'balance' => $value,
        ]);
        return $value;
    }

    public function allActive(array $params = [])
    {
        $query = Account::where('active', 1)
            ->with(['currency', 'accountType', 'users']);

        if (!empty($params['currency_id'])) {
            $query->where('currency_id', $params['currency_id']);
        }
        if (!empty($params['currency'])) {
            $needle = $params['currency'];
            $query->whereHas('currency', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%")
                  ->orWhere('code', 'like', "%{$needle}%");
            });
        }
        if (!empty($params['account_type_id'])) {
            $query->where('account_type_id', $params['account_type_id']);
        }
        if (!empty($params['account_type'])) {
            $needle = $params['account_type'];
            $query->whereHas('accountType', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%");
            });
        }
        if (!empty($params['user_id'])) {
            $userId = $params['user_id'];
            $isOwner = $params['is_owner'] ?? null;
            $query->whereHas('users', function ($q) use ($userId, $isOwner) {
                $q->where('users.id', $userId);
                if ($isOwner !== null && $isOwner !== '') {
                    $q->where('account_user.is_owner', (int) filter_var($isOwner, FILTER_VALIDATE_BOOLEAN));
                }
            });
        }
        if (!empty($params['user'])) {
            $needle = $params['user'];
            $query->whereHas('users', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%")
                  ->orWhere('email', 'like', "%{$needle}%");
            });
        }
        if (!empty($params['search'])) {
            $this->applyGlobalSearch($query, $params['search'], [
                'name', 'currency.name', 'accountType.name', 'users.name',
            ]);
        }
        $sortBy = $params['sort_by'] ?? 'name';
        $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $query->orderBy($sortBy, $descending ? 'desc' : 'asc');
        if (!empty($params['page'])) {
            $perPage = $params['per_page'] ?? 15;
            $result = $query->paginate($perPage);
            foreach ($result as $account) {
                $account->balance_calculado = $this->calculateBalance($account->id);
            }
            return $result;
        }
        $accounts = $query->get();
        foreach ($accounts as $account) {
            $account->balance_calculado = $this->calculateBalanceFromInitialByType($account->id);
        }
        return $accounts;
    }

    public function find($id)
    {
    return Account::with(['currency', 'accountType', 'users'])->find($id);
    }

    public function store(array $data)
    {
        return Account::create($data);
    }

    public function update(Account $account, array $data)
    {
        $account->update($data);
        return $account;
    }

    public function delete(Account $account)
    {
        // Mirror Currency behavior: set active=0 and then soft-delete
        $account->active = 0;
        $account->save();
        $account->delete();
        return $account;
    }

    public function withTrashed()
    {
    return Account::withTrashed()->with(['currency', 'accountType', 'users'])->get();
    }

    private function applyGlobalSearch($query, string $searchTerm, array $fields): void
    {
        $query->where(function ($q) use ($searchTerm, $fields) {
            foreach ($fields as $field) {
                if (strpos($field, '.') !== false) {
                    [$relation, $column] = explode('.', $field, 2);
                    $relation = Str::camel($relation);
                    $q->orWhereHas($relation, function ($q2) use ($column, $searchTerm) {
                        $q2->where($column, 'like', "%{$searchTerm}%");
                    });
                } else {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            }
        });
    }
}

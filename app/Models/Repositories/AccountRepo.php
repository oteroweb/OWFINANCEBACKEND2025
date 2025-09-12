<?php

namespace App\Models\Repositories;

use App\Models\Entities\Account;
use Illuminate\Support\Str;

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
            $account->balance_calculado = $this->calculateBalance($account->id);
        }
        return $accounts;
    }

    /**
     * Suma todas las transacciones activas asociadas a la cuenta para calcular el balance.
     */
    public function calculateBalance($accountId)
    {
        // Suma los montos de transacciones activas (active=1) asociadas a la cuenta
        return (float) \App\Models\Entities\Transaction::where('account_id', $accountId)
            ->where('active', 1)
            ->where('include_in_balance', 1)
            ->sum('amount');
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
            $account->balance_calculado = $this->calculateBalance($account->id);
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

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

        // Optional filters
        if (!empty($params['currency_id'])) {
            $query->where('currency_id', $params['currency_id']);
        }
        if (!empty($params['account_type_id'])) {
            $query->where('account_type_id', $params['account_type_id']);
        }
        if (!empty($params['user_id'])) {
            $userId = $params['user_id'];
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
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
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function allActive(array $params = [])
    {
        $query = Account::where('active', 1)
            ->with(['currency', 'accountType', 'users']);

        if (!empty($params['currency_id'])) {
            $query->where('currency_id', $params['currency_id']);
        }
        if (!empty($params['account_type_id'])) {
            $query->where('account_type_id', $params['account_type_id']);
        }
        if (!empty($params['user_id'])) {
            $userId = $params['user_id'];
            $query->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
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
            return $query->paginate($perPage);
        }
        return $query->get();
    }

    public function find($id)
    {
        return Account::find($id);
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
        return Account::withTrashed()->get();
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

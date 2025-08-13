<?php

namespace App\Models\Repositories;

use Illuminate\Support\Facades\Log;
use App\Models\Entities\Transaction;
use App\Models\Entities\TransactionType;

class TransactionRepo {
    /**
     * Get all transactions, sorted by a field and direction.
     * @param string $sortBy
     * @param bool $descending
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all($params = [])
    {
        $query = Transaction::whereIn('active', [1,0])
            ->with(['provider', 'rate', 'user', 'account', 'transactionType']);

        // Apply filters
        if (!empty($params['provider_id'])) {
            $query->where('provider_id', $params['provider_id']);
        }
        if (!empty($params['rate_id'])) {
            $query->where('rate_id', $params['rate_id']);
        }
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['account_id'])) {
            $query->where('account_id', $params['account_id']);
        }
        
        // transaction_type filters (new FK takes precedence)
        if (!empty($params['transaction_type_id'])) {
            $query->where('transaction_type_id', $params['transaction_type_id']);
        } elseif (!empty($params['transaction_type'])) {
            // allow filtering by slug or legacy text
            $slug = $params['transaction_type'];
            $type = TransactionType::where('slug', $slug)->first();
            if ($type) {
                $query->where('transaction_type_id', $type->id);
            } else {
                $query->where('transaction_type', $slug);
            }
        }

        // Apply global search
        if (!empty($params['search'])) {
            $searchTerm = $params['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhereHas('provider', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('transactionType', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Apply sorting
        $sortBy = $params['sort_by'] ?? 'date';
        $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $direction = $descending ? 'desc' : 'asc';
        $query->orderBy($sortBy, $direction);

        // Apply pagination
        if (!empty($params['page'])) {
            $perPage = $params['per_page'] ?? 15;
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    /**
     * Get all active transactions, sorted by a field and direction.
     * @param string $sortBy
     * @param bool $descending
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allActive($params = [])
    {
        $query = Transaction::where('active', 1)
            ->with(['provider', 'rate', 'user', 'account', 'transactionType']);

        // Apply filters
        if (!empty($params['provider_id'])) {
            $query->where('provider_id', $params['provider_id']);
        }
        if (!empty($params['rate_id'])) {
            $query->where('rate_id', $params['rate_id']);
        }
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['account_id'])) {
            $query->where('account_id', $params['account_id']);
        }

        if (!empty($params['transaction_type_id'])) {
            $query->where('transaction_type_id', $params['transaction_type_id']);
        } elseif (!empty($params['transaction_type'])) {
            $slug = $params['transaction_type'];
            $type = TransactionType::where('slug', $slug)->first();
            if ($type) {
                $query->where('transaction_type_id', $type->id);
            } else {
                $query->where('transaction_type', $slug);
            }
        }

        // Apply global search
        if (!empty($params['search'])) {
            $searchTerm = $params['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhereHas('provider', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('transactionType', function ($q) use ($searchTerm) {
                      $q->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Apply sorting
        $sortBy = $params['sort_by'] ?? 'date';
        $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $direction = $descending ? 'desc' : 'asc';
        $query->orderBy($sortBy, $direction);

        // Apply pagination
        if (!empty($params['page'])) {
            $perPage = $params['per_page'] ?? 15;
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function find($id) {
        return Transaction::with(['provider', 'rate', 'user', 'account', 'transactionType'])->find($id);
    }
    public function store($data) {
        $transaction = new Transaction();
        $transaction->fill($data);
        $transaction->save();
        return $transaction;
    }
    public function update($transaction, $data) {
        $transaction->fill($data);
        $transaction->save();
        return $transaction;
    }
    public function delete($transaction, $data) {
        $transaction->fill($data);
        $transaction->save();
        return $transaction;
    }
    public function withTrashed() {
        return Transaction::withTrashed()->with(['provider', 'rate', 'user', 'account', 'transactionType'])->get();
    }
}

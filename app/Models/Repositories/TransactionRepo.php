<?php

namespace App\Models\Repositories;

use Illuminate\Support\Facades\Log;
use App\Models\Entities\Transaction;
use App\Models\Entities\TransactionType;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
            ->with(['provider','rate','user','account','transactionType']);

        // Apply filters
        if (!empty($params['provider_id'])) {
            $query->where('provider_id', $params['provider_id']);
        } elseif (!empty($params['provider'])) {
            // filter by provider name
            $providerName = $params['provider'];
            $query->whereHas('provider', function ($q2) use ($providerName) {
                $q2->where('name', 'like', "%{$providerName}%");
            });
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

        // date_from & date_to filters
        if (!empty($params['date_from']) || !empty($params['date_to'])) {
            $from = !empty($params['date_from'])
                ? Carbon::parse(str_replace('T', ' ', $params['date_from']))->toDateTimeString()
                : null;
            $to   = !empty($params['date_to'])
                ? Carbon::parse(str_replace('T', ' ', $params['date_to']))->endOfMinute()->toDateTimeString()
                : null;

            if ($from && $to) {
                $query->whereBetween('date', [$from, $to]);
            } elseif ($from) {
                $query->where('date', '>=', $from);
            } elseif ($to) {
                $query->where('date', '<=', $to);
            }
        }

        // Apply global search (reusable loop)
        if (!empty($params['search'])) {
            $searchTerm = $params['search'];
            $this->applyGlobalSearch($query, $searchTerm, ['name', 'description', 'provider.name', 'transaction_type.name', 'user.name', 'account.name']);
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
        $query = Transaction::where('active',1)
            ->with(['provider','rate','user','account','transactionType']);

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

        // date_from & date_to filters
        if (!empty($params['date_from']) || !empty($params['date_to'])) {
            $from = !empty($params['date_from'])
                ? Carbon::parse(str_replace('T', ' ', $params['date_from']))->toDateTimeString()
                : null;
            $to   = !empty($params['date_to'])
                ? Carbon::parse(str_replace('T', ' ', $params['date_to']))->endOfMinute()->toDateTimeString()
                : null;

            if ($from && $to) {
                $query->whereBetween('date', [$from, $to]);
            } elseif ($from) {
                $query->where('date', '>=', $from);
            } elseif ($to) {
                $query->where('date', '<=', $to);
            }
        }

        // Reusable global search using a loop over fields (supports relations via dot-notation)
        $searchFields = ['name', 'description', 'provider.name', 'transaction_type.name', 'user.name', 'account.name'];
        if (!empty($params['search'])) {
            $searchTerm = $params['search'];
            Log::debug('TransactionRepo allActive search', [
                'searchTerm' => $searchTerm,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            $this->applyGlobalSearch($query, $searchTerm, $searchFields);
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

    /**
     * Apply a global search to a query across simple columns and relations (dot-notation).
     * Examples: 'name', 'description', 'provider.name', 'transaction_type.name'.
     * Snake_case relation names will be converted to camelCase (e.g., transaction_type -> transactionType).
     */
    private function applyGlobalSearch($query, string $searchTerm, array $searchFields): void
    {
        // Optionally escape LIKE wildcards to avoid unintended matches
        // $searchTerm = str_replace(['%', '_'], ['\%','\_'], $searchTerm);

        $query->where(function ($q) use ($searchTerm, $searchFields) {
            foreach ($searchFields as $field) {
                if (strpos($field, '.') !== false) {
                    [$relation, $column] = explode('.', $field, 2);
                    // Allow snake_case or camelCase relation names
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

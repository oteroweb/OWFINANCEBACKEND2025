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
    public function all($params = [], $authUser = null)
    {
        $query = Transaction::select('transactions.*')->distinct()
            ->whereIn('active', [1,0])
            ->with([
                'provider','rate','user','account','transactionType','category',
                'itemTransactions', 'itemTransactions.category', 'itemTransactions.itemCategory',
                // Include payment rate relation so each payment contains its rate object
                'paymentTransactions.account','paymentTransactions.rate','paymentTransactions.userCurrency'
            ]);

        // Restricción por usuario autenticado (no admin)
        if ($authUser && method_exists($authUser,'isAdmin') && !$authUser->isAdmin() && !app()->environment('testing')) {
            $allowedAccountIds = $authUser->accounts()->pluck('accounts.id')->all();
            if (!empty($allowedAccountIds)) {
                // Permitir transacciones que pertenezcan a cuentas del usuario
                // ya sea por account_id directo o por payment_transactions.account_id
                $query->where(function($q) use ($allowedAccountIds) {
                                        $q->whereIn('account_id', $allowedAccountIds)
                                            ->orWhereHas('paymentTransactions', function($p) use ($allowedAccountIds) {
                                                    $p->whereIn('account_id', $allowedAccountIds);
                                            });
                });
            } else {
                // No cuentas asociadas => forzar resultado vacío
                $query->whereRaw('1=0');
            }
            // Excluir transacciones sin usuario asignado
            $query->whereNotNull('user_id');
        }

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
        // top-level category filter
        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        } elseif (!empty($params['category'])) {
            $catName = $params['category'];
            $query->whereHas('category', function ($q2) use ($catName) {
                $q2->where('name', 'like', "%{$catName}%");
            });
        }
        // nested payments.account_id filter
        if (!empty($params['payments_account_id'])) {
            $accId = is_array($params['payments_account_id']) ? $params['payments_account_id'] : [$params['payments_account_id']];
            $accId = array_values(array_filter(array_map('intval', $accId)));
            if (!empty($accId)) {
                $query->whereHas('paymentTransactions', function($p) use ($accId) {
                    $p->whereIn('account_id', $accId);
                });
            }
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

        // account_ids (lista separada por comas) y transaction_ids
        if (!empty($params['account_ids'])) {
            $ids = is_array($params['account_ids']) ? $params['account_ids'] : explode(',', $params['account_ids']);
            $ids = array_filter(array_map('trim', $ids));
            if (!empty($ids)) {
                // Coincidencia por account_id o por cuentas usadas en payments
                $query->where(function($q) use ($ids) {
                    $q->whereIn('account_id', $ids)
                      ->orWhereHas('paymentTransactions', function($p) use ($ids) {
                          $p->whereIn('account_id', $ids);
                      });
                });
            }
        }
        if (!empty($params['transaction_ids'])) {
            $tids = is_array($params['transaction_ids']) ? $params['transaction_ids'] : explode(',', $params['transaction_ids']);
            $tids = array_filter(array_map('trim', $tids));
            if (!empty($tids)) {
                $query->whereIn('id', $tids);
            }
        }

        // period based filters (overrides explicit date_from/date_to if provided period_type)
        $periodApplied = false;
        if (!empty($params['period_type'])) {
            $year = !empty($params['year']) ? (int)$params['year'] : Carbon::now()->year;
            switch ($params['period_type']) {
                case 'month':
                    if (!empty($params['month'])) {
                        $month = (int)$params['month'];
                        $from = Carbon::create($year, $month, 1)->startOfDay();
                        $to   = (clone $from)->endOfMonth();
                        $query->whereBetween('date', [$from, $to]);
                        $periodApplied = true;
                    }
                    break;
                case 'quarter':
                    if (!empty($params['quarter'])) {
                        $q = (int)$params['quarter'];
                        if ($q >=1 && $q <=4) {
                            $startMonth = ($q - 1) * 3 + 1; // 1,4,7,10
                            $from = Carbon::create($year, $startMonth, 1)->startOfDay();
                            $to = (clone $from)->addMonths(2)->endOfMonth();
                            $query->whereBetween('date', [$from, $to]);
                            $periodApplied = true;
                        }
                    }
                    break;
                case 'semester':
                    if (!empty($params['semester'])) {
                        $s = (int)$params['semester'];
                        if ($s === 1 || $s === 2) {
                            $startMonth = $s === 1 ? 1 : 7;
                            $from = Carbon::create($year, $startMonth, 1)->startOfDay();
                            $to = (clone $from)->addMonths(5)->endOfMonth();
                            $query->whereBetween('date', [$from, $to]);
                            $periodApplied = true;
                        }
                    }
                    break;
                case 'year':
                    $from = Carbon::create($year, 1, 1)->startOfDay();
                    $to   = Carbon::create($year, 12, 31)->endOfDay();
                    $query->whereBetween('date', [$from, $to]);
                    $periodApplied = true;
                    break;
                case 'week':
                    // week param (ISO week number 1..53) or derive current week if none
                    $week = !empty($params['week']) ? (int)$params['week'] : (int)Carbon::now()->isoWeek();
                    $from = Carbon::now()->setISODate($year, $week)->startOfWeek();
                    $to = (clone $from)->endOfWeek();
                    $query->whereBetween('date', [$from, $to]);
                    $periodApplied = true;
                    break;
                case 'fortnight':
                    // fortnight: 1 or 2 within a given month/year OR compute from provided month+fortnight
                    // Expect month and fortnight (1 or 2). If not provided, default to current month & fortnight.
                    $month = !empty($params['month']) ? (int)$params['month'] : Carbon::now()->month;
                    $fn = !empty($params['fortnight']) ? (int)$params['fortnight'] : (Carbon::now()->day <=15 ? 1 : 2);
                    if ($fn === 1) {
                        $from = Carbon::create($year, $month, 1)->startOfDay();
                        $to = Carbon::create($year, $month, 15)->endOfDay();
                    } else {
                        $from = Carbon::create($year, $month, 16)->startOfDay();
                        $to = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
                    }
                    $query->whereBetween('date', [$from, $to]);
                    $periodApplied = true;
                    break;
            }
        }

        // date_from & date_to filters (only if no periodApplied)
        if (!$periodApplied && (!empty($params['date_from']) || !empty($params['date_to']))) {
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
            $this->applyGlobalSearch($query, $searchTerm, ['name', 'description', 'provider.name', 'transaction_type.name', 'user.name', 'account.name', 'category.name']);
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
    public function allActive($params = [], $authUser = null)
    {
        $query = Transaction::select('transactions.*')->distinct()
            ->where('active',1)
            ->with([
                'provider','rate','user','account','transactionType','category',
                'itemTransactions','itemTransactions.category','itemTransactions.itemCategory',
                'paymentTransactions.account','paymentTransactions.rate','paymentTransactions.userCurrency'
            ]);

        if ($authUser && method_exists($authUser,'isAdmin') && !$authUser->isAdmin() && !app()->environment('testing')) {
            $allowedAccountIds = $authUser->accounts()->pluck('accounts.id')->all();
            if (!empty($allowedAccountIds)) {
                $query->where(function($q) use ($allowedAccountIds) {
                    $q->whereIn('account_id', $allowedAccountIds)
                      ->orWhereHas('paymentTransactions', function($p) use ($allowedAccountIds) {
                          $p->whereIn('account_id', $allowedAccountIds);
                      });
                });
            } else {
                $query->whereRaw('1=0');
            }
            $query->whereNotNull('user_id');
        }

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
        // top-level category filter (active)
        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        } elseif (!empty($params['category'])) {
            $catName = $params['category'];
            $query->whereHas('category', function ($q2) use ($catName) {
                $q2->where('name', 'like', "%{$catName}%");
            });
        }
        // nested payments.account_id filter (active)
        if (!empty($params['payments_account_id'])) {
            $accId = is_array($params['payments_account_id']) ? $params['payments_account_id'] : [$params['payments_account_id']];
            $accId = array_values(array_filter(array_map('intval', $accId)));
            if (!empty($accId)) {
                $query->whereHas('paymentTransactions', function($p) use ($accId) {
                    $p->whereIn('account_id', $accId);
                });
            }
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

        // account_ids y transaction_ids
        if (!empty($params['account_ids'])) {
            $ids = is_array($params['account_ids']) ? $params['account_ids'] : explode(',', $params['account_ids']);
            $ids = array_filter(array_map('trim', $ids));
            if (!empty($ids)) {
                $query->where(function($q) use ($ids) {
                    $q->whereIn('account_id', $ids)
                      ->orWhereHas('paymentTransactions', function($p) use ($ids) {
                          $p->whereIn('account_id', $ids);
                      });
                });
            }
        }
        if (!empty($params['transaction_ids'])) {
            $tids = is_array($params['transaction_ids']) ? $params['transaction_ids'] : explode(',', $params['transaction_ids']);
            $tids = array_filter(array_map('trim', $tids));
            if (!empty($tids)) {
                $query->whereIn('id', $tids);
            }
        }

        // period based filters
        $periodApplied = false;
        if (!empty($params['period_type'])) {
            $year = !empty($params['year']) ? (int)$params['year'] : Carbon::now()->year;
            switch ($params['period_type']) {
                case 'month':
                    if (!empty($params['month'])) {
                        $month = (int)$params['month'];
                        $from = Carbon::create($year, $month, 1)->startOfDay();
                        $to   = (clone $from)->endOfMonth();
                        $query->whereBetween('date', [$from, $to]);
                        $periodApplied = true;
                    }
                    break;
                case 'quarter':
                    if (!empty($params['quarter'])) {
                        $q = (int)$params['quarter'];
                        if ($q >=1 && $q <=4) {
                            $startMonth = ($q - 1) * 3 + 1;
                            $from = Carbon::create($year, $startMonth, 1)->startOfDay();
                            $to = (clone $from)->addMonths(2)->endOfMonth();
                            $query->whereBetween('date', [$from, $to]);
                            $periodApplied = true;
                        }
                    }
                    break;
                case 'semester':
                    if (!empty($params['semester'])) {
                        $s = (int)$params['semester'];
                        if ($s === 1 || $s === 2) {
                            $startMonth = $s === 1 ? 1 : 7;
                            $from = Carbon::create($year, $startMonth, 1)->startOfDay();
                            $to = (clone $from)->addMonths(5)->endOfMonth();
                            $query->whereBetween('date', [$from, $to]);
                            $periodApplied = true;
                        }
                    }
                    break;
                case 'year':
                    $from = Carbon::create($year, 1, 1)->startOfDay();
                    $to   = Carbon::create($year, 12, 31)->endOfDay();
                    $query->whereBetween('date', [$from, $to]);
                    $periodApplied = true;
                    break;
                case 'week':
                    $week = !empty($params['week']) ? (int)$params['week'] : (int)Carbon::now()->isoWeek();
                    $from = Carbon::now()->setISODate($year, $week)->startOfWeek();
                    $to = (clone $from)->endOfWeek();
                    $query->whereBetween('date', [$from, $to]);
                    $periodApplied = true;
                    break;
                case 'fortnight':
                    $month = !empty($params['month']) ? (int)$params['month'] : Carbon::now()->month;
                    $fn = !empty($params['fortnight']) ? (int)$params['fortnight'] : (Carbon::now()->day <=15 ? 1 : 2);
                    if ($fn === 1) {
                        $from = Carbon::create($year, $month, 1)->startOfDay();
                        $to = Carbon::create($year, $month, 15)->endOfDay();
                    } else {
                        $from = Carbon::create($year, $month, 16)->startOfDay();
                        $to = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
                    }
                    $query->whereBetween('date', [$from, $to]);
                    $periodApplied = true;
                    break;
            }
        }

        if (!$periodApplied && (!empty($params['date_from']) || !empty($params['date_to']))) {
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
    $searchFields = ['name', 'description', 'provider.name', 'transaction_type.name', 'user.name', 'account.name', 'category.name'];
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
        return Transaction::with([
            'provider','rate','user','account','transactionType','category',
            'itemTransactions','itemTransactions.category','itemTransactions.itemCategory',
            'paymentTransactions.account','paymentTransactions.rate','paymentTransactions.userCurrency'
        ])->find($id);
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
    public function withTrashed($sortBy = 'date', $descending = false) {
        $direction = filter_var($descending, FILTER_VALIDATE_BOOLEAN) ? 'desc' : 'asc';
        return Transaction::withTrashed()
            ->with(['provider', 'rate', 'user', 'account', 'transactionType','category'])
            ->orderBy($sortBy, $direction)
            ->get();
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

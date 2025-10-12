<?php

namespace App\Services;

use App\Models\Entities\Account;
use App\Models\Entities\Transaction;
use App\Models\Entities\TransactionType;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Cache for transaction type ids resolved by slug.
     */
    protected array $transactionTypeIds = [];

    /**
     * Build a summary tailored for administrative dashboards.
     */
    public function getAdminSummary(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('active', 1)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        $totalAccounts = Account::count();
        $activeAccounts = Account::where('active', 1)->count();

        $totalTransactions = Transaction::count();
        $activeTransactions = Transaction::where('active', 1)->count();

        $financialQuery = Transaction::query()
            ->where('active', 1)
            ->where(function (Builder $q) {
                $q->where('include_in_balance', 1)
                    ->orWhereNull('include_in_balance');
            });

        $incomeTotal = $this->sumByType(clone $financialQuery, 'income');
        $expenseTotalSigned = $this->sumByType(clone $financialQuery, 'expense');
        $expenseTotal = abs((float) $expenseTotalSigned);
        $netCashflow = (float) $incomeTotal + (float) $expenseTotalSigned;

        $activeAccountBalances = Account::where('active', 1)->get(['balance_cached', 'initial']);
        $totalBalance = $activeAccountBalances->sum(function (Account $account) {
            $cached = $account->balance_cached;
            if ($cached === null) {
                return (float) ($account->initial ?? 0);
            }
            return (float) $cached;
        });

        $latestUsers = User::with('role:id,name,slug')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at', 'active', 'role_id'])
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'active' => (bool) $user->active,
                    'created_at' => optional($user->created_at)->toDateTimeString(),
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'slug' => $user->role->slug,
                    ] : null,
                ];
            })
            ->all();

        $usersByRole = Role::withCount('users')
            ->get(['id', 'name', 'slug'])
            ->map(function (Role $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'total' => (int) $role->users_count,
                ];
            })
            ->all();

        return [
            'summary' => [
                'totals' => [
                    'users' => [
                        'total' => (int) $totalUsers,
                        'active' => (int) $activeUsers,
                        'inactive' => (int) $inactiveUsers,
                    ],
                    'accounts' => [
                        'total' => (int) $totalAccounts,
                        'active' => (int) $activeAccounts,
                    ],
                    'transactions' => [
                        'total' => (int) $totalTransactions,
                        'active' => (int) $activeTransactions,
                    ],
                ],
                'financials' => [
                    'income' => round((float) $incomeTotal, 2),
                    'expense' => round($expenseTotal, 2),
                    'net_cashflow' => round($netCashflow, 2),
                    'total_balance' => round((float) $totalBalance, 2),
                ],
                'users_by_role' => $usersByRole,
                'latest_users' => $latestUsers,
            ],
        ];
    }

    /**
     * Build a summary focused on a single authenticated user.
     */
    public function getUserSummary(User $user, array $filters = []): array
    {
        $accountIds = $user->accounts()->pluck('accounts.id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $accountsCollection = !empty($accountIds)
            ? Account::with('currency:id,code,name,symbol')
                ->whereIn('id', $accountIds)
                ->orderBy('name')
                ->get(['id', 'name', 'currency_id', 'balance_cached', 'initial', 'active'])
            : collect();

        $accountsList = $accountsCollection->map(function (Account $account) {
            $balance = $account->balance_cached !== null
                ? (float) $account->balance_cached
                : (float) ($account->initial ?? 0);

            return [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => round($balance, 2),
                'active' => (bool) $account->active,
                'currency' => $account->currency ? [
                    'id' => $account->currency->id,
                    'code' => $account->currency->code,
                    'name' => $account->currency->name,
                    'symbol' => $account->currency->symbol,
                ] : null,
            ];
        });

        $totalBalance = $accountsList->sum(fn (array $account) => $account['balance']);
        $activeAccountCount = $accountsList->filter(fn (array $account) => $account['active'])->count();

        $balancesByCurrency = $accountsCollection
            ->groupBy('currency_id')
            ->map(function (Collection $group) {
                $currency = $group->first()->currency;
                $sum = $group->sum(function (Account $account) {
                    if ($account->balance_cached !== null) {
                        return (float) $account->balance_cached;
                    }
                    return (float) ($account->initial ?? 0);
                });

                return [
                    'currency' => $currency ? [
                        'id' => $currency->id,
                        'code' => $currency->code,
                        'name' => $currency->name,
                        'symbol' => $currency->symbol,
                    ] : null,
                    'total_balance' => round((float) $sum, 2),
                ];
            })
            ->values()
            ->all();

        $baseQuery = $this->buildUserTransactionQuery($accountIds, $filters, $user->id);

        $incomeTotal = $this->sumByType(clone $baseQuery, 'income');
        $expenseTotalSigned = $this->sumByType(clone $baseQuery, 'expense');
        $expenseTotal = abs((float) $expenseTotalSigned);
        $transactionCount = (clone $baseQuery)->count();
        $netCashflow = (float) $incomeTotal + (float) $expenseTotalSigned;

        $recentTransactions = (clone $baseQuery)
            ->with('transactionType:id,name,slug')
            ->orderByDesc('date')
            ->limit(5)
            ->get(['id', 'name', 'amount', 'date', 'transaction_type_id', 'account_id'])
            ->map(function (Transaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'name' => $transaction->name,
                    'amount' => (float) $transaction->amount,
                    'date' => optional($transaction->date)->toDateTimeString(),
                    'account_id' => $transaction->account_id,
                    'type' => $transaction->transactionType ? [
                        'id' => $transaction->transactionType->id,
                        'name' => $transaction->transactionType->name,
                        'slug' => $transaction->transactionType->slug,
                    ] : null,
                ];
            })
            ->all();

        return [
            'summary' => [
                'accounts' => [
                    'total' => count($accountIds),
                    'active' => (int) $activeAccountCount,
                    'total_balance' => round((float) $totalBalance, 2),
                    'by_currency' => $balancesByCurrency,
                    'list' => $accountsList->values()->all(),
                ],
                'financials' => [
                    'income' => round((float) $incomeTotal, 2),
                    'expense' => round($expenseTotal, 2),
                    'net_cashflow' => round($netCashflow, 2),
                    'transaction_count' => (int) $transactionCount,
                ],
                'recent_transactions' => $recentTransactions,
            ],
        ];
    }

    /**
     * Sum helper by transaction type slug.
     */
    protected function sumByType(Builder $query, string $slug): float
    {
        $typeId = $this->getTransactionTypeId($slug);
        if ($typeId === null) {
            return 0.0;
        }

        return (float) $query->where('transaction_type_id', $typeId)->sum('amount');
    }

    /**
     * Resolve transaction type ids by slug and cache them locally.
     */
    protected function getTransactionTypeId(string $slug): ?int
    {
        if (!array_key_exists($slug, $this->transactionTypeIds)) {
            $this->transactionTypeIds[$slug] = TransactionType::where('slug', $slug)->value('id');
        }

        return $this->transactionTypeIds[$slug];
    }

    /**
     * Build the base query used for user level aggregations.
     */
    protected function buildUserTransactionQuery(array $accountIds, array $filters, ?int $userId = null): Builder
    {
        $query = Transaction::query()
            ->where('active', 1)
            ->where(function (Builder $q) {
                $q->where('include_in_balance', 1)
                    ->orWhereNull('include_in_balance');
            });

        if (!empty($accountIds)) {
            $query->where(function (Builder $q) use ($accountIds) {
                $q->whereIn('account_id', $accountIds)
                    ->orWhereHas('paymentTransactions', function (Builder $paymentQuery) use ($accountIds) {
                        $paymentQuery->whereIn('account_id', $accountIds);
                    });
            });
        } elseif ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->whereRaw('1 = 0');
        }

        $this->applyDateFilters($query, $filters);

        return $query;
    }

    /**
     * Apply optional date range filters to a query.
     */
    protected function applyDateFilters(Builder $query, array $filters): void
    {
        $from = $filters['from'] ?? $filters['date_from'] ?? null;
        $to = $filters['to'] ?? $filters['date_to'] ?? null;

        if (!empty($from)) {
            $fromDate = Carbon::parse($from)->startOfDay();
            $query->where('date', '>=', $fromDate);
        }

        if (!empty($to)) {
            $toDate = Carbon::parse($to)->endOfDay();
            $query->where('date', '<=', $toDate);
        }
    }
}

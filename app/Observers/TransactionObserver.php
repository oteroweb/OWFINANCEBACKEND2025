<?php

namespace App\Observers;

use App\Models\Entities\Transaction;
use App\Services\BalanceService;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        // No-op on created: the controller recalculates balances after creating payment transactions.
        // Leaving this empty avoids applying outdated amount-based deltas.
    }

    public function updated(Transaction $transaction): void
    {
        // Recalcular balances desde pagos + inicial para cuentas impactadas
        $repo = app(\App\Models\Repositories\AccountRepo::class);
        $ids = [];
        // Cuentas de pagos actuales
        try {
            $ids = array_merge($ids, $transaction->paymentTransactions()->pluck('account_id')->all());
        } catch (\Throwable $e) {
            // ignore
        }
        // account_id actual y original (por compat)
        $curr = $transaction->account_id;
        $orig = $transaction->getOriginal('account_id');
        if ($curr !== null) { $ids[] = $curr; }
        if ($orig !== null) { $ids[] = $orig; }
        // Normalizar y recalcular
        $ids = array_values(array_unique(array_filter(array_map(function($v){ return is_numeric($v)? (int)$v : null; }, $ids))));
        foreach ($ids as $aid) {
            try { $repo->recalcAndStoreFromInitialByType($aid); } catch (\Throwable $e) { /* log if needed */ }
        }
    }

    public function deleted(Transaction $transaction): void
    {
        // Recalcular balances de cuentas relacionadas
        $repo = app(\App\Models\Repositories\AccountRepo::class);
        $ids = [];
        try { $ids = array_merge($ids, $transaction->paymentTransactions()->pluck('account_id')->all()); } catch (\Throwable $e) {}
        if ($transaction->account_id !== null) { $ids[] = $transaction->account_id; }
        $ids = array_values(array_unique(array_filter(array_map(function($v){ return is_numeric($v)? (int)$v : null; }, $ids))));
        foreach ($ids as $aid) { try { $repo->recalcAndStoreFromInitialByType($aid); } catch (\Throwable $e) {} }
    }

    public function restored(Transaction $transaction): void
    {
        // Recalcular balances de cuentas relacionadas
        $repo = app(\App\Models\Repositories\AccountRepo::class);
        $ids = [];
        try { $ids = array_merge($ids, $transaction->paymentTransactions()->pluck('account_id')->all()); } catch (\Throwable $e) {}
        if ($transaction->account_id !== null) { $ids[] = $transaction->account_id; }
        $ids = array_values(array_unique(array_filter(array_map(function($v){ return is_numeric($v)? (int)$v : null; }, $ids))));
        foreach ($ids as $aid) { try { $repo->recalcAndStoreFromInitialByType($aid); } catch (\Throwable $e) {} }
    }

    private function counts(Transaction $t): bool
    {
        return (int)$t->active === 1 && (int)($t->include_in_balance ?? 1) === 1;
    }
}

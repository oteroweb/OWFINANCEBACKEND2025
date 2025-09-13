<?php

namespace App\Observers;

use App\Models\Entities\Transaction;
use App\Services\BalanceService;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        if ($this->counts($transaction)) {
            $service = app(BalanceService::class);
            $before = optional($transaction->account()->first())->balance_cached;
            $service->applyDelta($transaction->account_id, (float)$transaction->amount);
            $after = optional($transaction->account()->first())->balance_cached;
            if ($before === $after && (float)$transaction->amount !== 0.0) {
                // Fallback: recalc in case observer fired before relation refresh
                $service->recalcAccount($transaction->account_id);
            }
        }
    }

    public function updated(Transaction $transaction): void
    {
        // Obtener original (antes de cambios) usando getOriginal
        $original = new Transaction();
        $original->fill($transaction->getOriginal());
        $original->account_id = $transaction->getOriginal('account_id');
        $original->amount = $transaction->getOriginal('amount');
        $original->active = $transaction->getOriginal('active');
        $original->include_in_balance = $transaction->getOriginal('include_in_balance');

        $deltas = app(BalanceService::class)->computeUpdateDeltas($original, $transaction);
        foreach ($deltas as $accountId => $delta) {
            app(BalanceService::class)->applyDelta($accountId, $delta);
        }
    }

    public function deleted(Transaction $transaction): void
    {
        if ($this->counts($transaction)) {
            app(BalanceService::class)->applyDelta($transaction->account_id, - (float)$transaction->amount);
        }
    }

    public function restored(Transaction $transaction): void
    {
        if ($this->counts($transaction)) {
            app(BalanceService::class)->applyDelta($transaction->account_id, (float)$transaction->amount);
        }
    }

    private function counts(Transaction $t): bool
    {
        return (int)$t->active === 1 && (int)($t->include_in_balance ?? 1) === 1;
    }
}

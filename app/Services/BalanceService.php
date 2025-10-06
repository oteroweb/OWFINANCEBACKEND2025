<?php

namespace App\Services;

use App\Models\Entities\Transaction;
use App\Models\Entities\Account;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    // NOTE: This service reflects legacy amount-based balance semantics. New logic in AccountRepo
    // calculates balances from PaymentTransaction sums + Account.initial. Observers/controllers
    // should prefer AccountRepo->recalcAndStoreFromInitialByType for correctness.
    /**
     * Recalcula completamente el balance_cached de una cuenta usando la lógica actual (transacciones activas include_in_balance=1).
     */
    public function recalcAccount(int $accountId): float
    {
        $sum = (float) \App\Models\Entities\Transaction::where('account_id', $accountId)
            ->where('active', 1)
            ->where('include_in_balance', 1)
            ->sum('amount');
        Account::where('id', $accountId)->update([
            'balance_cached' => $sum,
            // Mantener balance tradicional sincronizado para compatibilidad
            'balance' => $sum,
        ]);
        return $sum;
    }

    /**
     * Aplica un delta (positivo o negativo) al balance_cached de la cuenta. Si está null o inconsistente, hace fallback a recalc.
     */
    public function applyDelta(int $accountId, float $delta): float
    {
        $account = Account::find($accountId);
        if (!$account) return 0.0;
        if ($account->balance_cached === null) {
            return $this->recalcAccount($accountId);
        }
    $new = round(((float)$account->balance_cached) + $delta, 2);
    $account->balance_cached = $new;
    $account->balance = $new; // sincroniza campo balance legacy
        $account->save();
        return $new;
    }

    /**
     * Calcula el delta para update considerando cambios de amount, include_in_balance, active o cambio de cuenta.
     * Devuelve arreglo [oldAccountId => delta, newAccountId(optional) => delta].
     */
    public function computeUpdateDeltas(Transaction $original, Transaction $updated): array
    {
        $deltas = [];
        $oldIn = $this->counts($original);
        $newIn = $this->counts($updated);

        // Cambio de cuenta
        if ($original->account_id !== $updated->account_id) {
            if ($oldIn) {
                $deltas[$original->account_id] = ($deltas[$original->account_id] ?? 0) - (float)$original->amount;
            }
            if ($newIn) {
                $deltas[$updated->account_id] = ($deltas[$updated->account_id] ?? 0) + (float)$updated->amount;
            }
            return $deltas;
        }

        // Misma cuenta: ver transiciones
        $accountId = $updated->account_id;
        if ($oldIn && $newIn) {
            $diff = (float)$updated->amount - (float)$original->amount;
            if (abs($diff) > 0.0001) {
                $deltas[$accountId] = ($deltas[$accountId] ?? 0) + $diff;
            }
        } elseif ($oldIn && !$newIn) {
            $deltas[$accountId] = ($deltas[$accountId] ?? 0) - (float)$original->amount;
        } elseif (!$oldIn && $newIn) {
            $deltas[$accountId] = ($deltas[$accountId] ?? 0) + (float)$updated->amount;
        }
        return $deltas;
    }

    private function counts(Transaction $t): bool
    {
        return (int)$t->active === 1 && (int)($t->include_in_balance ?? 1) === 1;
    }
}

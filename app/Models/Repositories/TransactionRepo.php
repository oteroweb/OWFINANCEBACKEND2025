<?php

namespace App\Models\Repositories;

use Illuminate\Support\Facades\Log;
use App\Models\Entities\Transaction;

class TransactionRepo {
    /**
     * Get all transactions, sorted by a field and direction.
     * @param string $sortBy
     * @param bool $descending
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all($sortBy = 'date', $descending = false)
    {
        $query = Transaction::whereIn('active', [1,0])
            ->with(['provider', 'rate', 'user', 'account']);
        if ($sortBy) {
            $direction = $descending ? 'desc' : 'asc';
            $query = $query->orderBy($sortBy, $direction);
        }
        return $query->get();
    }

    /**
     * Get all active transactions, sorted by a field and direction.
     * @param string $sortBy
     * @param bool $descending
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allActive($sortBy = 'date', $descending = false)
    {
        $query = Transaction::where('active', 1)
            ->with(['provider', 'rate', 'user', 'account']);
        if ($sortBy) {
            $direction = $descending ? 'desc' : 'asc';
            $query = $query->orderBy($sortBy, $direction);
        }
        return $query->get();
    }

    public function find($id) {
        return Transaction::with(['provider', 'rate', 'user', 'account'])->find($id);
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
        return Transaction::withTrashed()->with(['provider', 'rate', 'user', 'account'])->get();
    }
}

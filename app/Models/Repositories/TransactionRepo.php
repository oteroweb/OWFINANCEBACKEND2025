<?php

namespace App\Models\Repositories;

use Illuminate\Support\Facades\Log;
use App\Models\Entities\Transaction;

class TransactionRepo {
    public function all() {
        return Transaction::whereIn('active', [1,0])->with(['provider'])->get();
    }
    public function allActive() {
        return Transaction::where('active', 1)->with(['provider'])->get();
    }
    public function find($id) {
        return Transaction::with(['provider'])->find($id);
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
        return Transaction::withTrashed()->get();
    }
}

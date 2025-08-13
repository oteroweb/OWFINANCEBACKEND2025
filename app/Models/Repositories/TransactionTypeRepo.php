<?php

namespace App\Models\Repositories;

use App\Models\Entities\TransactionType;

class TransactionTypeRepo {
    public function all() {
        return TransactionType::whereIn('active', [1,0])->get();
    }
    public function allActive() {
        return TransactionType::where('active', 1)->get();
    }
    public function find($id) {
        return TransactionType::find($id);
    }
    public function store($data) {
        $type = new TransactionType();
        $type->fill($data);
        $type->save();
        return $type;
    }
    public function update($type, $data) {
        $type->fill($data);
        $type->save();
        return $type;
    }
    public function delete($type, $data) {
        $type->fill($data);
        $type->save();
        return $type;
    }
    public function withTrashed() {
        return TransactionType::withTrashed()->get();
    }
}

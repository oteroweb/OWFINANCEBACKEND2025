<?php

namespace App\Models\Repositories;

use App\Models\Entities\ItemTransaction;

class ItemTransactionRepo
{
    public function all()
    {
        return ItemTransaction::all();
    }

    public function allActive()
    {
        return ItemTransaction::where('active', 1)->get();
    }

    public function find($id)
    {
        return ItemTransaction::find($id);
    }

    public function store(array $data)
    {
        return ItemTransaction::create($data);
    }

    public function update(ItemTransaction $itemTransaction, array $data)
    {
        $itemTransaction->update($data);
        return $itemTransaction;
    }

    public function delete(ItemTransaction $itemTransaction)
    {
        return $itemTransaction->delete();
    }

    public function withTrashed()
    {
        return ItemTransaction::withTrashed()->get();
    }
}

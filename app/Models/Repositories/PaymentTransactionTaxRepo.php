<?php

namespace App\Models\Repositories;

use App\Models\Entities\PaymentTransactionTax;

class PaymentTransactionTaxRepo
{
    public function all()
    {
        return PaymentTransactionTax::all();
    }

    public function allActive()
    {
        return PaymentTransactionTax::where('active', 1)->get();
    }

    public function withTrashed()
    {
        return PaymentTransactionTax::withTrashed()->get();
    }

    public function find($id)
    {
        return PaymentTransactionTax::findOrFail($id);
    }

    public function store(array $data)
    {
        return PaymentTransactionTax::create($data);
    }

    public function update(PaymentTransactionTax $ptt, array $data)
    {
        $ptt->update($data);
        return $ptt;
    }

    public function delete(PaymentTransactionTax $ptt)
    {
        return $ptt->delete();
    }

    public function changeStatus(PaymentTransactionTax $ptt)
    {
        $ptt->active = !$ptt->active;
        $ptt->save();
        return $ptt;
    }
}

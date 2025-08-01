<?php

namespace App\Models\Repositories;

use App\Models\Entities\PaymentTransaction;

class PaymentTransactionRepo
{
    public function all()
    {
        return PaymentTransaction::all();
    }

    public function allActive()
    {
        return PaymentTransaction::where('active', 1)->get();
    }

    public function withTrashed()
    {
        return PaymentTransaction::withTrashed()->get();
    }

    public function find($id)
    {
        return PaymentTransaction::findOrFail($id);
    }

    public function store(array $data)
    {
        return PaymentTransaction::create($data);
    }

    public function update(PaymentTransaction $pt, array $data)
    {
        $pt->update($data);
        return $pt;
    }

    public function delete(PaymentTransaction $pt)
    {
        return $pt->delete();
    }

    public function changeStatus(PaymentTransaction $pt)
    {
        $pt->active = !$pt->active;
        $pt->save();
        return $pt;
    }
}

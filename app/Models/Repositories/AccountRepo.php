<?php

namespace App\Models\Repositories;

use App\Models\Entities\Account;

class AccountRepo
{
    public function all()
    {
        return Account::all();
    }

    public function allActive()
    {
        return Account::where('active', 1)->get();
    }

    public function find($id)
    {
        return Account::find($id);
    }

    public function store(array $data)
    {
        return Account::create($data);
    }

    public function update(Account $account, array $data)
    {
        $account->update($data);
        return $account;
    }

    public function delete(Account $account)
    {
        return $account->delete();
    }

    public function withTrashed()
    {
        return Account::withTrashed()->get();
    }
}

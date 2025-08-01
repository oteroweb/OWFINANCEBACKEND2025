<?php

namespace App\Models\Repositories;

use App\Models\Entities\Rate;

class RateRepo
{
    public function all()
    {
        return Rate::all();
    }

    public function allActive()
    {
        return Rate::where('active', 1)->get();
    }

    public function find($id)
    {
        return Rate::find($id);
    }

    public function store(array $data)
    {
        return Rate::create($data);
    }

    public function update(Rate $rate, array $data)
    {
        $rate->update($data);
        return $rate;
    }

    public function delete(Rate $rate)
    {
        return $rate->delete();
    }

    public function withTrashed()
    {
        return Rate::withTrashed()->get();
    }
}

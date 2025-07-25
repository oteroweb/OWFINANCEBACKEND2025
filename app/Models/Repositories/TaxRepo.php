<?php

namespace App\Models\Repositories;

use App\Models\Entities\Tax;

class TaxRepo
{
    public function all()
    {
        return Tax::all();
    }

    public function allActive()
    {
        return Tax::where('active', 1)->get();
    }

    public function find($id)
    {
        return Tax::find($id);
    }

    public function store(array $data)
    {
        return Tax::create($data);
    }

    public function update(Tax $tax, array $data)
    {
        $tax->update($data);
        return $tax;
    }

    public function delete(Tax $tax)
    {
        return $tax->delete();
    }

    public function withTrashed()
    {
        return Tax::withTrashed()->get();
    }
}

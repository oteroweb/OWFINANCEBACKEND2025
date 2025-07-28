<?php

namespace App\Models\Repositories;

use App\Models\Entities\Jar;

class JarRepo
{
    public function all()
    {
        return Jar::all();
    }

    public function allActive()
    {
        return Jar::where('active', 1)->get();
    }

    public function find($id)
    {
        return Jar::find($id);
    }

    public function store(array $data)
    {
        return Jar::create($data);
    }

    public function update(Jar $jar, array $data)
    {
        $jar->update($data);
        return $jar;
    }

    public function delete(Jar $jar)
    {
        return $jar->delete();
    }

    public function withTrashed()
    {
        return Jar::withTrashed()->get();
    }
}

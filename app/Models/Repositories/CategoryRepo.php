<?php

namespace App\Models\Repositories;

use App\Models\Entities\Category;

class CategoryRepo
{
    public function all()
    {
        return Category::all();
    }

    public function allActive()
    {
        return Category::where('active', 1)->get();
    }

    public function find($id)
    {
        return Category::find($id);
    }

    public function store(array $data)
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    public function delete(Category $category)
    {
        return $category->delete();
    }

    public function withTrashed()
    {
        return Category::withTrashed()->get();
    }
}

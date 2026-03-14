<?php

namespace App\Models\Repositories;

use App\Models\Entities\Category;

class CategoryRepo
{
    public function all($userId = null)
    {
        $query = Category::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return $query->get();
    }

    public function allActive($userId = null)
    {
        $query = Category::where('active', 1);
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return $query->get();
    }

    public function find($id, $userId = null)
    {
        $query = Category::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return $query->find($id);
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

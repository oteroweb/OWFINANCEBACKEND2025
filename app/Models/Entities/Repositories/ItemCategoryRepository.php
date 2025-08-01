<?php

namespace App\Models\Entities\Repositories;

use App\Models\Entities\ItemCategory;

class ItemCategoryRepository
{
    public function all()
    {
        return ItemCategory::all();
    }
    public function allActive()
    {
        return ItemCategory::where('active', 1)->get();
    }
    public function withTrashed()
    {
        return ItemCategory::withTrashed()->get();
    }
    public function find($id)
    {
        return ItemCategory::findOrFail($id);
    }
    public function create(array $data)
    {
        return ItemCategory::create($data);
    }
    public function update(ItemCategory $itemCategory, array $data)
    {
        $itemCategory->update($data);
        return $itemCategory;
    }
    public function delete(ItemCategory $itemCategory)
    {
        $itemCategory->delete();
        return true;
    }
    public function changeStatus(ItemCategory $itemCategory)
    {
        $itemCategory->active = !$itemCategory->active;
        $itemCategory->save();
        return $itemCategory;
    }
}

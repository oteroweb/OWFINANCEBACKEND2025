<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\ItemCategory;

class ItemCategoryController extends Controller
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
    public function save(Request $request)
    {
        $itemCategory = ItemCategory::create($request->all());
        return response()->json($itemCategory, 201);
    }
    public function update(Request $request, $id)
    {
        $itemCategory = ItemCategory::findOrFail($id);
        $itemCategory->update($request->all());
        return response()->json($itemCategory);
    }
    public function delete($id)
    {
        $itemCategory = ItemCategory::findOrFail($id);
        $itemCategory->delete();
        return response()->json(null, 204);
    }
    public function change_status($id)
    {
        $itemCategory = ItemCategory::findOrFail($id);
        $itemCategory->active = !$itemCategory->active;
        $itemCategory->save();
        return response()->json($itemCategory);
    }
}

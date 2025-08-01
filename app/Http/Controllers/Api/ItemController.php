<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\Item;

class ItemController extends Controller
{
    public function all()
    {
        return Item::all();
    }
    public function allActive()
    {
        return Item::where('active', 1)->get();
    }
    public function withTrashed()
    {
        return Item::withTrashed()->get();
    }
    public function find($id)
    {
        return Item::findOrFail($id);
    }
    public function save(Request $request)
    {
        $item = Item::create($request->all());
        return response()->json($item, 201);
    }
    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);
        $item->update($request->all());
        return response()->json($item);
    }
    public function delete($id)
    {
        $item = Item::findOrFail($id);
        $item->delete();
        return response()->json(null, 204);
    }
    public function change_status($id)
    {
        $item = Item::findOrFail($id);
        $item->active = !$item->active;
        $item->save();
        return response()->json($item);
    }
}

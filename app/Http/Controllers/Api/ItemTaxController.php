<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\ItemTax;

class ItemTaxController extends Controller
{
    public function all()
    {
        return ItemTax::all();
    }
    public function allActive()
    {
        return ItemTax::where('active', 1)->get();
    }
    public function withTrashed()
    {
        return ItemTax::withTrashed()->get();
    }
    public function find($id)
    {
        return ItemTax::findOrFail($id);
    }
    public function save(Request $request)
    {
        $itemTax = ItemTax::create($request->all());
        return response()->json($itemTax, 201);
    }
    public function update(Request $request, $id)
    {
        $itemTax = ItemTax::findOrFail($id);
        $itemTax->update($request->all());
        return response()->json($itemTax);
    }
    public function delete($id)
    {
        $itemTax = ItemTax::findOrFail($id);
        $itemTax->delete();
        return response()->json(null, 204);
    }
    public function change_status($id)
    {
        $itemTax = ItemTax::findOrFail($id);
        $itemTax->active = !$itemTax->active;
        $itemTax->save();
        return response()->json($itemTax);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\AccountTax;

class AccountTaxController extends Controller
{
    public function all()
    {
        return AccountTax::all();
    }
    public function allActive()
    {
        return AccountTax::where('active', 1)->get();
    }
    public function withTrashed()
    {
        return AccountTax::withTrashed()->get();
    }
    public function find($id)
    {
        return AccountTax::findOrFail($id);
    }
    public function save(Request $request)
    {
        $at = AccountTax::create($request->all());
        return response()->json($at, 201);
    }
    public function update(Request $request, $id)
    {
        $at = AccountTax::findOrFail($id);
        $at->update($request->all());
        return response()->json($at);
    }
    public function delete($id)
    {
        $at = AccountTax::findOrFail($id);
        $at->delete();
        return response()->json(null, 204);
    }
    public function change_status($id)
    {
        $at = AccountTax::findOrFail($id);
        $at->active = !$at->active;
        $at->save();
        return response()->json($at);
    }
}

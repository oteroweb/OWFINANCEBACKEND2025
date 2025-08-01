<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\PaymentTransaction;

class PaymentTransactionController extends Controller
{
    public function all()
    {
        return PaymentTransaction::all();
    }
    public function allActive()
    {
        return PaymentTransaction::where('active', 1)->get();
    }
    public function withTrashed()
    {
        return PaymentTransaction::withTrashed()->get();
    }
    public function find($id)
    {
        return PaymentTransaction::findOrFail($id);
    }
    public function save(Request $request)
    {
        $pt = PaymentTransaction::create($request->all());
        return response()->json($pt, 201);
    }
    public function update(Request $request, $id)
    {
        $pt = PaymentTransaction::findOrFail($id);
        $pt->update($request->all());
        return response()->json($pt);
    }
    public function delete($id)
    {
        $pt = PaymentTransaction::findOrFail($id);
        $pt->delete();
        return response()->json(null, 204);
    }
    public function change_status($id)
    {
        $pt = PaymentTransaction::findOrFail($id);
        $pt->active = !$pt->active;
        $pt->save();
        return response()->json($pt);
    }
}

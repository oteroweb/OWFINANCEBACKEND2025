<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\PaymentTransactionTax;

class PaymentTransactionTaxController extends Controller
{
    public function all()
    {
        return PaymentTransactionTax::all();
    }
    public function allActive()
    {
        return PaymentTransactionTax::where('active', 1)->get();
    }
    public function withTrashed()
    {
        return PaymentTransactionTax::withTrashed()->get();
    }
    public function find($id)
    {
        return PaymentTransactionTax::findOrFail($id);
    }
    public function save(Request $request)
    {
        $ptt = PaymentTransactionTax::create($request->all());
        return response()->json($ptt, 201);
    }
    public function update(Request $request, $id)
    {
        $ptt = PaymentTransactionTax::findOrFail($id);
        $ptt->update($request->all());
        return response()->json($ptt);
    }
    public function delete($id)
    {
        $ptt = PaymentTransactionTax::findOrFail($id);
        $ptt->delete();
        return response()->json(null, 204);
    }
    public function change_status($id)
    {
        $ptt = PaymentTransactionTax::findOrFail($id);
        $ptt->active = !$ptt->active;
        $ptt->save();
        return response()->json($ptt);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repositories\PaymentTransactionTaxRepo;
use App\Models\Entities\PaymentTransactionTax;
use App\Models\Entities\Tax;

class PaymentTransactionTaxController extends Controller
{
    private $repo;

    public function __construct(PaymentTransactionTaxRepo $repo)
    {
        $this->repo = $repo;
    }

    public function all()
    {
        $data = $this->repo->all();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactionTaxes retrieved successfully'),
            'data' => $data,
        ], 200);
    }
    public function allActive()
    {
        $data = $this->repo->allActive();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Active PaymentTransactionTaxes retrieved successfully'),
            'data' => $data,
        ], 200);
    }
    public function withTrashed()
    {
        $data = $this->repo->withTrashed();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactionTaxes with trashed retrieved successfully'),
            'data' => $data,
        ], 200);
    }
    public function find($id)
    {
        $ptt = $this->repo->find($id);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactionTax retrieved successfully'),
            'data' => $ptt,
        ], 200);
    }
    public function save(Request $request)
    {
        // applies_to validation for payment taxes
        if ($request->filled('tax_id')) {
            $tax = Tax::find($request->input('tax_id'));
            if (!$tax || !in_array($tax->applies_to ?? 'item', ['payment','both'], true)) {
                return response()->json([
                    'status' => 'FAILED','code' => 422,
                    'message' => __('Selected tax does not apply to payments')
                ], 422);
            }
        }
        $ptt = $this->repo->store($request->all());
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactionTax saved correctly'),
            'data' => $ptt,
        ], 200);
    }
    public function update(Request $request, $id)
    {
        $ptt = $this->repo->find($id);
        if ($request->filled('tax_id')) {
            $tax = Tax::find($request->input('tax_id'));
            if (!$tax || !in_array($tax->applies_to ?? 'item', ['payment','both'], true)) {
                return response()->json([
                    'status' => 'FAILED','code' => 422,
                    'message' => __('Selected tax does not apply to payments')
                ], 422);
            }
        }
        $ptt = $this->repo->update($ptt, $request->all());
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactionTax updated successfully'),
            'data' => $ptt,
        ], 200);
    }
    public function delete($id)
    {
        $ptt = $this->repo->find($id);
        $this->repo->delete($ptt);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactionTax deleted successfully'),
            'data' => null,
        ], 200);
    }
    public function change_status($id)
    {
        $ptt = $this->repo->find($id);
        $ptt = $this->repo->changeStatus($ptt);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactionTax status changed'),
            'data' => $ptt,
        ], 200);
    }
}

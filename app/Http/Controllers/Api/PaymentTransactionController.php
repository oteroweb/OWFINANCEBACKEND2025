<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repositories\PaymentTransactionRepo;
use App\Models\Entities\PaymentTransaction;

class PaymentTransactionController extends Controller
{
    private $repo;

    public function __construct(PaymentTransactionRepo $repo)
    {
        $this->repo = $repo;
    }

    public function all()
    {
        $data = $this->repo->all();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactions retrieved successfully'),
            'data' => $data,
        ], 200);
    }
    public function allActive()
    {
        $data = $this->repo->allActive();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Active PaymentTransactions retrieved successfully'),
            'data' => $data,
        ], 200);
    }
    public function withTrashed()
    {
        $data = $this->repo->withTrashed();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransactions with trashed retrieved successfully'),
            'data' => $data,
        ], 200);
    }
    public function find($id)
    {
        $pt = $this->repo->find($id);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransaction retrieved successfully'),
            'data' => $pt,
        ], 200);
    }
    public function save(Request $request)
    {
        $pt = $this->repo->store($request->all());
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransaction saved correctly'),
            'data' => $pt,
        ], 200);
    }
    public function update(Request $request, $id)
    {
        $pt = $this->repo->find($id);
        $pt = $this->repo->update($pt, $request->all());
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransaction updated successfully'),
            'data' => $pt,
        ], 200);
    }
    public function delete($id)
    {
        $pt = $this->repo->find($id);
        $this->repo->delete($pt);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransaction deleted successfully'),
            'data' => null,
        ], 200);
    }
    public function change_status($id)
    {
        $pt = $this->repo->find($id);
        $pt = $this->repo->changeStatus($pt);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PaymentTransaction status changed'),
            'data' => $pt,
        ], 200);
    }
}

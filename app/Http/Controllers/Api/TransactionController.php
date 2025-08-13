<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\TransactionRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    private $transactionRepo;
    public function __construct(TransactionRepo $transactionRepo) {
        $this->transactionRepo = $transactionRepo;
    }
    /**
     * @group Transaction
     * Get
     *
     * all
     */
    public function all(Request $request) {
        try {
            // Collect pagination, sorting, search and filter parameters
            $params = $request->only([
                'page', 'per_page', 'sort_by', 'descending',
                'search', 'provider_id', 'rate_id', 'user_id', 'account_id', 'transaction_type', 'transaction_type_id'
            ]);
            $transaction = $this->transactionRepo->all($params);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Transaction Obtained Correctly'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Get
     *
     * all active
     */
    public function allActive(Request $request) {
        try {
            // Collect pagination, sorting, search and filter parameters
            $params = $request->only([
                'page', 'per_page', 'sort_by', 'descending',
                'search', 'provider_id', 'rate_id', 'user_id', 'account_id', 'transaction_type', 'transaction_type_id'
            ]);
            $transaction = $this->transactionRepo->allActive($params);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Get
     * @urlParam id integer required The ID of the transaction. Example: 1
     *
     * find
     */
    public function find($id) {
        try {
            $transaction = $this->transactionRepo->find($id);
            if (isset($transaction->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Transaction Obtained Correctly'),
                    'data'    => $transaction,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Transaction') . '.',
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Post
     *
     * save
     * @bodyParam name string required The name of the transaction. Example: Payment
     * @bodyParam amount number required The amount. Example: 100.50
     * @bodyParam description string optional The description. Example: invoice payment
     * @bodyParam date datetime required The date of the transaction. Example: 2025-07-14 12:00:00
     * @bodyParam provider_id integer optional The ID of the provider. Example: 1
     * @bodyParam url_file string optional File URL. Example: https://example.com/file.pdf
     * @bodyParam rate_id integer optional The rate id. Example: 1
     * @bodyParam transaction_type_id integer optional The transaction type id. Example: 1
     * @bodyParam amount_tax number optional The tax amount. Example: 10.00
     */
    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'amount' => 'required|numeric',
            'description' => 'max:255',
            'date' => 'required|date',
            'provider_id' => 'nullable|exists:providers,id',
            'url_file' => 'nullable|string',
            'rate_id' => 'nullable|integer',
            'transaction_type_id' => 'nullable|exists:transaction_types,id',
            'amount_tax' => 'nullable|numeric',
        ], $this->custom_message());
        if ($validator->fails()) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 400,
                'message' => __('Incorrect Params'),
                'data'    => $validator->errors()->getMessages(),
            ];
            return response()->json($response);
        }
        try {
            $data = [
                'name'=> $request->input('name'),
                'amount'=> $request->input('amount'),
                'description'=> $request->input('description'),
                'date'=> $request->input('date'),
                'provider_id'=> $request->input('provider_id'),
                'url_file'=> $request->input('url_file'),
                'rate_id'=> $request->input('rate_id'),
                'transaction_type_id'=> $request->input('transaction_type_id'),
                'amount_tax'=> $request->input('amount_tax'),
                'account_id'=> $request->input('account_id'),
                'user_id'=> $request->input('user_id'),
            ];
            $transaction= $this->transactionRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Transaction saved correctly'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Put
     *
     * update
     * @urlParam id integer required The ID of the transaction. Example: 1
     */
    public function update(Request $request, $id) {
        $transaction = $this->transactionRepo->find($id);
        if (isset($transaction->id)) {
            $data= array();
            if ($request->has('name')) { $data['name'] = $request->input('name'); }
            if ($request->has('amount')) { $data['amount'] = $request->input('amount'); }
            if ($request->has('description')) { $data['description'] = $request->input('description'); }
            if ($request->has('date')) { $data['date'] = $request->input('date'); }
            if ($request->has('provider_id')) { $data['provider_id'] = $request->input('provider_id'); }
            if ($request->has('url_file')) { $data['url_file'] = $request->input('url_file'); }
            if ($request->has('rate_id')) { $data['rate_id'] = $request->input('rate_id'); }
            if ($request->has('transaction_type_id')) { $data['transaction_type_id'] = $request->input('transaction_type_id'); }
            if ($request->has('amount_tax')) { $data['amount_tax'] = $request->input('amount_tax'); }
            if ($request->has('account_id')) { $data['account_id'] = $request->input('account_id'); }
            if ($request->has('user_id')) { $data['user_id'] = $request->input('user_id'); }
            $transaction = $this->transactionRepo->update($transaction, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Transaction updated'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Transaction dont exists') . '.',
        ];
        return response()->json($response, 500);
    }
    /**
     * @group Transaction
     * Delete
     * @urlParam id integer required The ID of the transaction. Example: 1
     *
     * delete
     */
    public function delete(Request $request, $id) {
        try {
            if ($this->transactionRepo->find($id)) {
                $transaction = $this->transactionRepo->find($id);
                $transaction = $this->transactionRepo->delete($transaction, ['active' => 0]);
                $transaction = $transaction->delete();
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Transaction Deleted Successfully'),
                    'data'    => $transaction,
                ];
                return response()->json($response, 200);
            }
            else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('Transaction not Found'),
                ];
                return response()->json($response, 200);
            }

        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
/**
     * @group Transaction
     * Patch
     * @urlParam id integer required The ID of the transaction. Example: 1
     *
     * change_status
     */
    public function change_status(Request $request, $id) {

        $transaction = $this->transactionRepo->find($id);
        if (isset($transaction->active)) {
            if($transaction->active == 0){
                $data = ['active' => 1];
            }else{
                $data = ['active' => 0];
            }
            $transaction = $this->transactionRepo->update($transaction, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Transaction updated'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Transaction does not exist') . '.',
        ];
        return response()->json($response, 500);
    }
    /**
     * @group Transaction
     * Get
     *
     * withTrashed
     */
    public function withTrashed(Request $request) {
        try {
            // Determine sorting parameters
            $sortBy = $request->query('sort_by', 'date');
            $descending = filter_var($request->query('descending', 'false'), FILTER_VALIDATE_BOOLEAN);
            $transaction = $this->transactionRepo->withTrashed($sortBy, $descending);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    public function custom_message() {

        return [
            'name.required'=> __('The name is required'),
            'amount.required'=> __('The amount is required'),
            'date.required'=> __('The date is required'),
        ];
    }
}

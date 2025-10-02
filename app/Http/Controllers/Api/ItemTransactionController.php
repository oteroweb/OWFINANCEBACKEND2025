<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\ItemTransactionRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Entities\Tax;

class ItemTransactionController extends Controller
{
    private $ItemTransactionRepo;

    public function __construct(ItemTransactionRepo $ItemTransactionRepo)
    {
        $this->ItemTransactionRepo = $ItemTransactionRepo;
    }

    /**
     * @group ItemTransaction
     * Get
     *
     * all
     */
    public function all()
    {
        try {
            $itemTransaction = $this->ItemTransactionRepo->all();
            return response()->json($itemTransaction, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([], 500);
        }
    }

    /**
     * @group ItemTransaction
     * Get
     *
     * all active
     */
    public function allActive()
    {
        try {
            $itemTransaction = $this->ItemTransactionRepo->allActive();
            return response()->json($itemTransaction, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([], 500);
        }
    }

    /**
     * @group ItemTransaction
     * Get
     * @urlParam id integer required The ID of the item transaction. Example: 1
     *
     * find
     */
    public function find($id)
    {
        try {
            $itemTransaction = $this->ItemTransactionRepo->find($id);
            if (isset($itemTransaction->id)) {
                return response()->json($itemTransaction, 200);
            }
            return response()->json(null, 404);
        } catch (\Exception $ex) {
            return response()->json(null, 500);
        }
    }

    /**
     * @group ItemTransaction
     * Post
     *
     * save
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id'        => 'required|exists:items,id',
            'transaction_id' => 'required|exists:transactions,id',
            'quantity'       => 'required|integer|min:1',
            'name'           => 'required|max:100',
            'amount'         => 'required|numeric',
            'date'           => 'required|date',
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
                'item_id'        => $request->input('item_id'),
                'transaction_id' => $request->input('transaction_id'),
                'quantity'       => $request->input('quantity'),
                'name'           => $request->input('name'),
                'amount'         => $request->input('amount'),
                'tax_id'         => $request->input('tax_id'),
                'rate_id'        => $request->input('rate_id'),
                'description'    => $request->input('description'),
                'jar_id'         => $request->input('jar_id'),
                'date'           => $request->input('date'),
                'category_id'    => $request->input('category_id'),
                'item_category_id' => $request->input('item_category_id'),
                'user_id'        => $request->input('user_id'),
                'custom_name'    => $request->input('custom_name'),
            ];
            // Auto-assign jar if missing and we have category and user
            if (empty($data['jar_id']) && !empty($data['category_id']) && !empty($data['user_id'])) {
                $userId = (int) $data['user_id'];
                $catId = (int) $data['category_id'];
                // Find first active jar of user linked to this category
                $jarId = \App\Models\Entities\Jar::where('user_id', $userId)
                    ->where('active', 1)
                    ->whereHas('categories', function($q) use ($catId) { $q->where('categories.id', $catId); })
                    ->orderBy('sort_order')
                    ->value('id');
                if (!$jarId) {
                    // Fallback: first active jar percent with base_scope=all_income
                    $jarId = \App\Models\Entities\Jar::where('user_id', $userId)
                        ->where('active', 1)
                        ->orderBy('sort_order')
                        ->value('id');
                }
                if ($jarId) { $data['jar_id'] = $jarId; }
            }
            // Validate that provided tax_id (if any) applies to items
            if (!empty($data['tax_id'])) {
                $tax = Tax::find($data['tax_id']);
                if (!$tax) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,'message' => __('Invalid tax')
                    ], 422);
                }
                if (!in_array($tax->applies_to ?? 'item', ['item','both'], true)) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,
                        'message' => __('Selected tax does not apply to items')
                    ], 422);
                }
            }

            $itemTransaction = $this->ItemTransactionRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('ItemTransaction saved correctly'),
                'data'    => $itemTransaction,
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
     * @group ItemTransaction
     * Put
     *
     * update
     * @urlParam id integer required The ID of the item transaction. Example: 1
     */
    public function update(Request $request, $id)
    {
        $itemTransaction = $this->ItemTransactionRepo->find($id);
        if (isset($itemTransaction->id)) {
            $data = [];
            if ($request->has('item_id')) { $data['item_id'] = $request->input('item_id'); }
            if ($request->has('transaction_id')) { $data['transaction_id'] = $request->input('transaction_id'); }
            if ($request->has('quantity')) { $data['quantity'] = $request->input('quantity'); }
            if ($request->has('name')) { $data['name'] = $request->input('name'); }
            if ($request->has('amount')) { $data['amount'] = $request->input('amount'); }
            if ($request->has('tax_id')) { $data['tax_id'] = $request->input('tax_id'); }
            if ($request->has('rate_id')) { $data['rate_id'] = $request->input('rate_id'); }
            if ($request->has('description')) { $data['description'] = $request->input('description'); }
            if ($request->has('jar_id')) { $data['jar_id'] = $request->input('jar_id'); }
            if ($request->has('date')) { $data['date'] = $request->input('date'); }
            if ($request->has('category_id')) { $data['category_id'] = $request->input('category_id'); }
            if ($request->has('item_category_id')) { $data['item_category_id'] = $request->input('item_category_id'); }
            if ($request->has('user_id')) { $data['user_id'] = $request->input('user_id'); }
            if ($request->has('custom_name')) { $data['custom_name'] = $request->input('custom_name'); }
            if ($request->has('active')) { $data['active'] = $request->input('active'); }
            // Validate that provided tax_id (if any) applies to items
            if (array_key_exists('tax_id', $data) && !empty($data['tax_id'])) {
                $tax = Tax::find($data['tax_id']);
                if (!$tax) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,'message' => __('Invalid tax')
                    ], 422);
                }
                if (!in_array($tax->applies_to ?? 'item', ['item','both'], true)) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,
                        'message' => __('Selected tax does not apply to items')
                    ], 422);
                }
            }

            $itemTransaction = $this->ItemTransactionRepo->update($itemTransaction, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('ItemTransaction updated'),
                'data'    => $itemTransaction,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('ItemTransaction dont exists') . '.',
        ];
        return response()->json($response, 500);
    }

    /**
     * @group ItemTransaction
     * Delete
     * @urlParam id integer required The ID of the item transaction. Example: 1
     *
     * delete
     */
    public function delete(Request $request, $id)
    {
        try {
            if ($this->ItemTransactionRepo->find($id)) {
                $itemTransaction = $this->ItemTransactionRepo->find($id);
                $itemTransaction = $this->ItemTransactionRepo->delete($itemTransaction);
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('ItemTransaction Deleted Successfully'),
                    'data'    => $itemTransaction,
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('ItemTransaction not Found'),
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
     * @group ItemTransaction
     * Patch
     * @urlParam id integer required The ID of the item transaction. Example: 1
     *
     * change_status
     */
    public function change_status(Request $request, $id)
    {
        $itemTransaction = $this->ItemTransactionRepo->find($id);
        if (isset($itemTransaction->active)) {
            $data = ['active' => $itemTransaction->active ? 0 : 1];
            $itemTransaction = $this->ItemTransactionRepo->update($itemTransaction, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status ItemTransaction updated'),
                'data'    => $itemTransaction,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('ItemTransaction does not exist') . '.',
        ];
        return response()->json($response, 500);
    }

    /**
     * @group ItemTransaction
     * Get
     *
     * withTrashed
     */
    public function withTrashed()
    {
        try {
            $itemTransaction = $this->ItemTransactionRepo->withTrashed();
            return response()->json($itemTransaction, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([], 500);
        }
    }

    public function custom_message()
    {
        return [
            'transaction_id.required' => __('The transaction_id is required'),
            'name.required'           => __('The name is required'),
            'amount.required'         => __('The amount is required'),
            'date.required'           => __('The date is required'),
        ];
    }
}

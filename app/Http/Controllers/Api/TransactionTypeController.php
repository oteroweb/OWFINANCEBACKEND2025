<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\TransactionTypeRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TransactionTypeController extends Controller
{
    private $repo;
    public function __construct(TransactionTypeRepo $repo) { $this->repo = $repo; }

    /**
     * @group Transaction Types
     * Get
     * all
     */
    public function all() {
        try { $types = $this->repo->all();
            return response()->json(['status'=>'OK','code'=>200,'message'=>__('Transaction Types Obtained Correctly'),'data'=>$types],200);
        } catch (\Exception $ex) { Log::error($ex); return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('An error has occurred').'.'],500);} }

    /**
     * @group Transaction Types
     * Get
     * all active
     */
    public function allActive() {
        try { $types = $this->repo->allActive();
            return response()->json(['status'=>'OK','code'=>200,'message'=>__('Data Obtained Correctly'),'data'=>$types],200);
        } catch (\Exception $ex) { Log::error($ex); return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('An error has occurred').'.'],500);} }

    /**
     * @group Transaction Types
     * Get
     * find
     */
    public function find($id) {
        try { $type = $this->repo->find($id);
            if ($type) return response()->json(['status'=>'OK','code'=>200,'message'=>__('Transaction Type Obtained Correctly'),'data'=>$type],200);
            return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Not Data with this Transaction Type').'.'],200);
        } catch (\Exception $ex) { return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('An error has occurred').'.'],500);} }

    /**
     * @group Transaction Types
     * Post
     * Save a new Transaction Type
     * @bodyParam name string required The name of the transaction type. Example: Purchase
     * @bodyParam slug string required The slug of the transaction type. Example: purchase
     * @bodyParam description string optional The description of the transaction type. Example: Transaction type for purchases
     */
    public function save(Request $request) {
        $validator = Validator::make($request->all(), [ 'name'=>'required|max:50', 'slug'=>'required|max:50|unique:transaction_types,slug', 'description'=>'nullable|max:255' ]);
        if ($validator->fails()) return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()]);
        try { $data = [ 'name'=>$request->input('name'), 'slug'=>$request->input('slug'), 'description'=>$request->input('description'), 'active'=>1 ];
            $type = $this->repo->store($data);
            return response()->json(['status'=>'OK','code'=>201,'message'=>__('Transaction Type saved correctly'),'data'=>$type],201);
        } catch (\Exception $ex) { Log::error($ex); return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('An error has occurred').'.'],500);} }

    /**
     * @group Transaction Types
     * Put
     * Update an existing Transaction Type
     * @bodyParam name string optional The name of the transaction type. Example: Purchase Updated
     * @bodyParam slug string optional The slug of the transaction type. Example: purchase-updated
     * @bodyParam description string optional The description of the transaction type. Example: Updated description
     */
    public function update(Request $request, $id) {
        $type = $this->repo->find($id);
        if (!$type) return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('Transaction Type dont exists').'.'],500);
        $data = [];
        if ($request->has('name')) $data['name'] = $request->input('name');
        if ($request->has('slug')) $data['slug'] = $request->input('slug');
        if ($request->has('description')) $data['description'] = $request->input('description');
        $type = $this->repo->update($type, $data);
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Transaction Type updated'),'data'=>$type],200);
    }

    /**
     * @group Transaction Types
     * Patch
     * change_status
     */
    public function change_status(Request $request, $id) {
        $type = $this->repo->find($id);
        if (!$type) return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('Transaction Type does not exist').'.'],500);
        $type = $this->repo->update($type, ['active' => $type->active ? 0 : 1]);
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Status Transaction Type updated'),'data'=>$type],200);
    }

    /**
     * @group Transaction Types
     * Delete
     * delete
     */
    public function delete(Request $request, $id) {
        try {
            $type = $this->repo->find($id);
            if (!$type) return response()->json(['status'=>'OK','code'=>404,'message'=>__('Transaction Type not Found')],200);
            $type = $this->repo->delete($type, ['active'=>0]);
            $type->delete();
            return response()->json(['status'=>'OK','code'=>200,'message'=>__('Transaction Type Deleted Successfully'),'data'=>$type],200);
        } catch (\Exception $ex) { Log::error($ex); return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('An error has occurred').'.'],500);} }

    /**
     * @group Transaction Types
     * Get
     * withTrashed
     */
    public function withTrashed() {
        try { $types = $this->repo->withTrashed();
            return response()->json(['status'=>'OK','code'=>200,'message'=>__('Data Obtained Correctly'),'data'=>$types],200);
        } catch (\Exception $ex) { Log::error($ex); return response()->json(['status'=>'FAILED','code'=>500,'message'=>__('An error has occurred').'.'],500);} }
}

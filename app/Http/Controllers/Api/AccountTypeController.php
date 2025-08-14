<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\AccountTypeRepo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AccountTypeController extends Controller
{   
    private $AccountTypeRepo;
    public function __construct(AccountTypeRepo $AccountTypeRepo) {
        $this->AccountTypeRepo = $AccountTypeRepo;
    }
    /**
     * @group Account Type
     * Post
     *
     * save
     * @bodyParam name string required The name of the account type. Example: Account Type 1
     * @bodyParam icon string required The icon of the account type. Example: fa-bank
     * @bodyParam active boolean optional The status of the account type. Defaults to true. Example: true
     */
    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' =>'required|max:35|',
            'icon' =>'required|max:35|',
            'description' => 'required|max:255',
            'active' => 'sometimes|boolean',
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
                'icon'=> $request->input('icon'),
                'description'=> $request->input('description', ''),
                'active' => $request->boolean('active', true),
            ];
            $accounttype= $this->AccountTypeRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 201,
                'message' => __('Account Type saved correctly'),
                'data'    => $accounttype,
            ];
            return response()->json($response, 201);
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
     * @group Account Type
     * Get
     * @urlParam id integer required The ID of the account type. Example: 1
     *
     * find
     */
    public function find($id) {
        try {
            $accounttype = $this->AccountTypeRepo->find($id);
            if (isset($accounttype->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Account Type Obtained Correctly'),
                    'data'    => $accounttype,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Account Type') . '.',
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
     * @group Account Type
     * Put
     *
     * update
     * @urlParam id integer required The ID of the account type. Example: 1
     * @bodyParam name string optional The name of the account type. Example: Account Type 1x
     * @bodyParam icon string optional The icon of the account type. Example: fa-piggy-bank
     * @bodyParam active boolean optional The status of the account type. Example: true
     */
    public function update(Request $request, $id) {
        $accounttype = $this->AccountTypeRepo->find($id);
        if (isset($accounttype->id)) {
            $data= array();
            if ($request->has('name')) { $data['name'] = $request->input('name'); }
            if ($request->has('icon')) { $data['icon'] = $request->input('icon'); }
            if ($request->has('description')) { $data['description'] = $request->input('description'); }
            if ($request->has('active')) { $data['active'] = $request->boolean('active'); }
            $accounttype = $this->AccountTypeRepo->update($accounttype, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Account Type updated'),
                'data'    => $accounttype,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Account Type dont exists') . '.',
        ];
        return response()->json($response, 500);
    }

    /**
     * @group Account Type
     * Get
     *
     * all
     */
    public function all(Request $request) {
        try { $accounttype = $this->AccountTypeRepo->all($request->only(['page','per_page','sort_by','descending','search']));
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Account Type Obtained Correctly'),
                'data'    => $accounttype,
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
     * @group Account Type
     * Get
     *
     * all active
     */
    public function allActive(Request $request) {
        try { $accounttype = $this->AccountTypeRepo->allActive($request->only(['page','per_page','sort_by','descending','search']));
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $accounttype,
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
     * @group Account Type
     * Patch
     * @urlParam id integer required The ID of the account type. Example: 1
     *
     * change_status
     */
    public function change_status(Request $request, $id) {
        
        $accounttype = $this->AccountTypeRepo->find($id);
        if (isset($accounttype->active)) {
            if($accounttype->active == 0){
                $data = ['active' => 1];
            }else{
                $data = ['active' => 0];
            }
            $accounttype = $this->AccountTypeRepo->update($accounttype, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Account Type updated'),
                'data'    => $accounttype,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Account Type does not exist') . '.',
        ];
        return response()->json($response, 500);
    }

    /**
     * @group Account Type
     * Delete
     * @urlParam id integer required The ID of the account type. Example: 1
     *
     * delete
     */
    public function delete(Request $request, $id) {
        try {
            if ($this->AccountTypeRepo->find($id)) {
                $accounttype = $this->AccountTypeRepo->find($id);
                $accounttype = $this->AccountTypeRepo->delete($accounttype, ['active' => 0]);
                $accounttype = $accounttype->delete();
                $response = [ 
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Account Type Deleted Successfully'),
                    'data'    => $accounttype,
                ];
                return response()->json($response, 200);
            }
            else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('Account Type not Found'),
                ];
                return response()->json($response, 200);
            }
            
        } catch (\Exception $ex) {
            // Simplified generic error handling (no custom SQL parsing)
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
     * @group Account Type
     * Get
     *
     * withTrashed
     */
    public function withTrashed() {
        try { $accounttype = $this->AccountTypeRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $accounttype,
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
        ];
    }
}

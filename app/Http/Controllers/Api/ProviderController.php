<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\ProviderRepo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProviderController extends Controller
{   
    private $ProviderRepo;
    public function __construct(ProviderRepo $ProviderRepo) {
        $this->ProviderRepo = $ProviderRepo;
    }
     /**
     * @group Provider
     * Get
     *
     * all
     */
    public function all() {
        try { $provider = $this->ProviderRepo->all();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Provider Obtained Correctly'),
                'data'    => $provider,
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
     * @group Provider
     * Get
     *
     * all active
     */
    public function allActive() {
        try { $provider = $this->ProviderRepo->allActive();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $provider,
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
     * @group Provider
     * Get
     *
     * find
     */
    public function find($id) {
        try {
            $provider = $this->ProviderRepo->find($id);
            if (isset($provider->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Provider Obtained Correctly'),
                    'data'    => $provider,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Provider') . '.',
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
     * @group Provider
     * Post
     *
     * save
     */
    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' =>'required|max:35|',
            'address' =>'required|max:35|',
            
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
            'address'=> $request->input('address'),
            
            ];
            $provider= $this->ProviderRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Provider saved correctly'),
                'data'    => $provider,
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
     * @group Provider
     * Put
     *
     * update
     */
    public function update(Request $request, $id) {
        $provider = $this->ProviderRepo->find($id);
        if (isset($provider->id)) {
            $data= array();
            if (($request->input('name'))) { 
                if (($request->input('name'))) { $data += ['name' => $request->input('name')]; };
                if (($request->input('address'))) { $data += ['address' => $request->input('address')]; };
                
            }
            $provider = $this->ProviderRepo->update($provider, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Provider updated'),
                'data'    => $provider,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Provider dont exists') . '.',
        ];
        return response()->json($response, 500);
    }
    /**
     * @group Provider
     * Delete
     *
     * delete
     */
    public function delete(Request $request, $id) {
        try {
            if ($this->ProviderRepo->find($id)) {
                $provider = $this->ProviderRepo->find($id);
                $provider = $this->ProviderRepo->delete($provider, ['active' => 0]);
                $provider = $provider->delete();
                $response = [ 
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Provider Deleted Successfully'),
                    'data'    => $provider,
                ];
                return response()->json($response, 200);
            }
            else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('Provider not Found'),
                ];
                return response()->json($response, 200);
            }
            
        } catch (\Exception $ex) {
            Log::error($ex);
            if (strpos($ex->getMessage(), 'SQLSTATE[23000]') !== false) {
                $errorForeing = $this->get_string_between($ex->errorInfo[2],'CONSTRAINT', 'FOREIGN');
                $response = [
                    'status'  => 'FAILED',
                    'code'    => 500,
                    'message' => __($errorForeing.'error') . '.',
                ];
            }
            else{
                $response = [
                    'status'  => 'FAILED',
                    'code'    => 500,
                    'message' => __('An error has occurred') . '.',
                ];
            }
            return response()->json($response, 500);
        }
    }
/**
     * @group Provider
     * Patch
     *
     * update
     */
    public function change_status(Request $request, $id) {
        
        $provider = $this->ProviderRepo->find($id);
        if (isset($provider->active)) {
            if($provider->active == 0){
                $data = ['active' => 1];
            }else{
                $data = ['active' => 0];
            }
            $provider = $this->ProviderRepo->update($provider, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Provider updated'),
                'data'    => $provider,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Provider does not exist') . '.',
        ];
        return response()->json($response, 500);
    }
    /**
     * @group Provider
     * Get
     *
     * withTrashed
     */
     public function withTrashed() {
        try { $provider = $this->ProviderRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $provider,
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

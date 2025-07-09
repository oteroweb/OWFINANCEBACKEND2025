<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\CurrencyRepo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CurrencyController extends Controller
{   
    private $CurrencyRepo;
    public function __construct(CurrencyRepo $CurrencyRepo) {
        $this->CurrencyRepo = $CurrencyRepo;
    }
    public function all() {
        try { $currency = $this->CurrencyRepo->all();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Currency Obtained Correctly'),
                'data'    => $currency,
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
    public function allActive() {
        try { $currency = $this->CurrencyRepo->allActive();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $currency,
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
    public function find($id) {
        try {
            $currency = $this->CurrencyRepo->find($id);
            if (isset($currency->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Currency Obtained Correctly'),
                    'data'    => $currency,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Currency') . '.',
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
    
    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' =>'required|max:35|',
            'tax' =>'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/',
            'last_tax' =>'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/',
            'symbol' =>'required|max:35|',
            'symbol_native' =>'required|max:35|',
            'decimal_digits' =>'required|max:35|',
            'rounding' =>'required|max:35|',
            'name_plural' =>'required|max:50|',
            'code' =>'required|max:35|',
            
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
            'tax'=> $request->input('tax'),
            'last_tax'=> $request->input('last_tax'),
            'symbol'=> $request->input('symbol'),
            'symbol_native'=> $request->input('symbol_native'),
            'decimal_digits'=> $request->input('decimal_digits'),
            'rounding'=> $request->input('rounding'),
            'name_plural'=> $request->input('name_plural'),
            'code'=> $request->input('code'),
            
            ];
            $currency= $this->CurrencyRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Currency saved correctly'),
                'data'    => $currency,
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

    public function update(Request $request, $id) {
        $currency = $this->CurrencyRepo->find($id);
        if (isset($currency->id)) {
            $data= array();
            if (($request->input('name'))) { 
                if (($request->input('name'))) { $data += ['name' => $request->input('name')]; };
                if (($request->input('account_id'))) { $data += ['account_id' => $request->input('account_id')]; };
                if (($request->input('category_transaction_id'))) { $data += ['category_transaction_id' => $request->input('category_transaction_id')]; };
                if (($request->input('invoice_id'))) { $data += ['invoice_id' => $request->input('invoice_id')]; };
                if (($request->input('amount'))) { $data += ['amount' => $request->input('amount')]; };
                if (($request->input('comission'))) { $data += ['comission' => $request->input('comission')]; };
                if (($request->input('dolar_tax'))) { $data += ['dolar_tax' => $request->input('dolar_tax')]; };
                if (($request->input('dolar_tax_acquired'))) { $data += ['dolar_tax_acquired' => $request->input('dolar_tax_acquired')]; };
                if (($request->input('dolar_amount'))) { $data += ['dolar_amount' => $request->input('dolar_amount')]; };
                
            }
            $currency = $this->CurrencyRepo->update($currency, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Currency updated'),
                'data'    => $currency,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Currency dont exists') . '.',
        ];
        return response()->json($response, 500);
    }

    public function delete(Request $request, $id) {
        try {
            if ($this->CurrencyRepo->find($id)) {
                $currency = $this->CurrencyRepo->find($id);
                $currency = $this->CurrencyRepo->delete($currency, ['active' => 0]);
                $currency = $currency->delete();
                $response = [ 
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Currency Deleted Successfully'),
                    'data'    => $currency,
                ];
                return response()->json($response, 200);
            }
            else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('Currency not Found'),
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

    public function change_status(Request $request, $id) {
        
        $currency = $this->CurrencyRepo->find($id);
        if (isset($currency->active)) {
            if($currency->active == 0){
                $data = ['active' => 1];
            }else{
                $data = ['active' => 0];
            }
            $currency = $this->CurrencyRepo->update($currency, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Currency updated'),
                'data'    => $currency,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Currency does not exist') . '.',
        ];
        return response()->json($response, 500);
    }
    public function custom_message() {
        
        return [
            'name.required'=> __('The name is required'),
        ];
    }
}

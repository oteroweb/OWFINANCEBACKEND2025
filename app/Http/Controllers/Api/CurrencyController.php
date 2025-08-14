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
     /**
     * @group Currency
     * Get
     *
     * all
     */
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
    /**
     * @group Currency
     * Get
     *
     * all Active
     */
    public function allActive() {
        try {
            $currency = $this->CurrencyRepo->allActive();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $currency ? $currency->toArray() : [],
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
     * @group Currency
     * Get
     * @urlParam id integer required The ID of the currency. Example: 1
     *
     * find
     */
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
     /**
     * @group Currency
     * Post
     *
     * save
     * @bodyParam name string required The name of the currency. Example: Currency 1
     * @bodyParam symbol string required The currency symbol. Example: $
     * @bodyParam align string required The alignment of the currency. Example: left
     * @bodyParam code string required The currency code. Example: USD
     * @bodyParam active boolean optional The status of the currency. Defaults to true. Example: true
     */
    public function save(Request $request) {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:35',
            'symbol' => 'required|string|max:10',
            'align'  => 'required|string|in:left,right',
            'code'   => 'required|string|max:10',
            'active' => 'boolean',
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
                'name'   => $request->input('name'),
                'symbol' => $request->input('symbol'),
                'align'  => $request->input('align'),
                'code'   => $request->input('code'),
                'active' => $request->input('active', true),
            ];
            $currency = $this->CurrencyRepo->store($data);
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
    /**
     * @group Currency
     * Put
     *
     * update
     * @urlParam id integer required The ID of the currency. Example: 1
     * @bodyParam name string optional The name of the currency. Example: Currency 1x
     * @bodyParam symbol string optional The currency symbol. Example: $
     * @bodyParam decimal_digits integer optional The number of decimal digits. Example: 2
     * @bodyParam code string optional The currency code. Example: USD
     * @bodyParam active boolean optional The status of the currency. Example: true
     */
    public function update(Request $request, $id) {
        $currency = $this->CurrencyRepo->find($id);
        if (isset($currency->id)) {
            // Only updating core currency fields: name, symbol, align, code, active
            $data = [];
            if ($request->has('name')) {
                $data['name'] = $request->input('name');
            }
            if ($request->has('symbol')) {
                $data['symbol'] = $request->input('symbol');
            }
            if ($request->has('align')) {
                $data['align'] = $request->input('align');
            }
            if ($request->has('code')) {
                $data['code'] = $request->input('code');
            }
            if ($request->has('active')) {
                $data['active'] = $request->input('active');
            }
            // Other fields are currently commented out
            // if ($request->has('account_id')) { ... }
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
     /**
     * @group Currency
     * Delete
     * @urlParam id integer required The ID of the currency. Example: 1
     *
     * delete
     */
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
            // Log and return generic error (detailed foreign constraint parsing removed)
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
     * @group Currency
     * Patch
     * @urlParam id integer required The ID of the currency. Example: 1
     *
     * change_status
     */
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
     /**
     * @group Currency
     * Get
     *
     * withTrashed
     */

    public function withTrashed() {
        try {
            $currency = $this->CurrencyRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $currency ? $currency->toArray() : [],
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

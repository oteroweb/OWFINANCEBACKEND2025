<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\TaxRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TaxController extends Controller
{
    private $TaxRepo;

    public function __construct(TaxRepo $TaxRepo)
    {
        $this->TaxRepo = $TaxRepo;
    }

    /**
     * @group Tax
     * Get
     *
     * all
     */
    public function all()
    {
        try {
            $tax = $this->TaxRepo->all();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Tax Obtained Correctly'),
                'data'    => $tax,
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
     * @group Tax
     * Get
     *
     * all active
     */
    public function allActive()
    {
        try {
            $tax = $this->TaxRepo->allActive();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $tax,
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
     * @group Tax
     * Get
     * @urlParam id integer required The ID of the tax. Example: 1
     *
     * find
     */
    public function find($id)
    {
        try {
            $tax = $this->TaxRepo->find($id);
            if (isset($tax->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Tax Obtained Correctly'),
                    'data'    => $tax,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Tax') . '.',
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
     * @group Tax
     * Post
     *
     * save
     * @bodyParam name string required The name of the tax. Example: Tax 1
     * @bodyParam percent number required The percent value. Example: 15.5
     * @bodyParam date date optional The date of the tax. Example: 2024-01-01
     * @bodyParam active boolean optional The status of the tax. Defaults to true. Example: true
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|max:35',
            'percent' => 'required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/',
            'date'    => 'sometimes|date',
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
                'name'    => $request->input('name'),
                'percent' => $request->input('percent'),
                'date'    => $request->input('date'),
            ];
            $tax = $this->TaxRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Tax saved correctly'),
                'data'    => $tax,
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
     * @group Tax
     * Put
     *
     * update
     * @urlParam id integer required The ID of the tax. Example: 1
     * @bodyParam name string optional The name of the tax. Example: Tax x
     * @bodyParam percent number optional The percent value. Example: 20
     * @bodyParam date date optional The date of the tax. Example: 2024-01-01
     * @bodyParam active boolean optional The status of the tax. Example: true
     */
    public function update(Request $request, $id)
    {
        $tax = $this->TaxRepo->find($id);
        if (isset($tax->id)) {
            $data = [];
            if ($request->input('name')) { $data['name'] = $request->input('name'); }
            if ($request->input('percent')) { $data['percent'] = $request->input('percent'); }
            if ($request->input('date')) { $data['date'] = $request->input('date'); }
            if ($request->has('active')) { $data['active'] = $request->input('active'); }
            $tax = $this->TaxRepo->update($tax, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Tax updated'),
                'data'    => $tax,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Tax dont exists') . '.',
        ];
        return response()->json($response, 500);
    }

    /**
     * @group Tax
     * Delete
     * @urlParam id integer required The ID of the tax. Example: 1
     *
     * delete
     */
    public function delete(Request $request, $id)
    {
        try {
            if ($this->TaxRepo->find($id)) {
                $tax = $this->TaxRepo->find($id);
                $tax = $this->TaxRepo->delete($tax);
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Tax Deleted Successfully'),
                    'data'    => $tax,
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('Tax not Found'),
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
     * @group Tax
     * Patch
     * @urlParam id integer required The ID of the tax. Example: 1
     *
     * change_status
     */
    public function change_status(Request $request, $id)
    {
        $tax = $this->TaxRepo->find($id);
        if (isset($tax->active)) {
            $data = ['active' => $tax->active ? 0 : 1];
            $tax = $this->TaxRepo->update($tax, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Tax updated'),
                'data'    => $tax,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Tax does not exist') . '.',
        ];
        return response()->json($response, 500);
    }

    /**
     * @group Tax
     * Get
     *
     * withTrashed
     */
    public function withTrashed()
    {
        try {
            $tax = $this->TaxRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $tax,
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

    public function custom_message()
    {
        return [
            'name.required' => __('The name is required'),
            'percent.required' => __('The percent is required'),
        ];
    }
}

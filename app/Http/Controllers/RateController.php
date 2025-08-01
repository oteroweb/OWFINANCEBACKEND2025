<?php

namespace App\Http\Controllers;

use App\Models\Repositories\RateRepo;
use App\Models\Entities\Rate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @group Rates
 *
 * APIs for managing rates
 */
class RateController extends Controller
{
    private $RateRepo;

    public function __construct(RateRepo $RateRepo)
    {
        $this->RateRepo = $RateRepo;
    }

    public function all()
    {
        try {
            $rate = $this->RateRepo->all();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Rate Obtained Correctly'),
                'data'    => $rate,
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

    public function allActive()
    {
        try {
            $rate = $this->RateRepo->allActive();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $rate,
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

    public function find($id)
    {
        try {
            $rate = $this->RateRepo->find($id);
            if (isset($rate->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Rate Obtained Correctly'),
                    'data'    => $rate,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Rate') . '.',
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

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|max:100',
            'date'    => 'required|date',
            'value'   => 'required|numeric',
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
                'date'    => $request->input('date'),
                'value'   => $request->input('value'),
                'active'  => $request->input('active', 1),
            ];
            $rate = $this->RateRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Rate saved correctly'),
                'data'    => $rate,
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

    public function update(Request $request, $id)
    {
        $rate = $this->RateRepo->find($id);
        if (isset($rate->id)) {
            $data = [];
            if ($request->input('name')) { $data['name'] = $request->input('name'); }
            if ($request->input('date')) { $data['date'] = $request->input('date'); }
            if ($request->input('value')) { $data['value'] = $request->input('value'); }
            if ($request->has('active')) { $data['active'] = $request->input('active'); }
            $rate = $this->RateRepo->update($rate, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Rate updated'),
                'data'    => $rate,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Rate dont exists') . '.',
        ];
        return response()->json($response, 500);
    }

    public function delete(Request $request, $id)
    {
        try {
            if ($this->RateRepo->find($id)) {
                $rate = $this->RateRepo->find($id);
                $rate = $this->RateRepo->delete($rate);
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Rate Deleted Successfully'),
                    'data'    => $rate,
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('Rate not Found'),
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

    public function change_status(Request $request, $id)
    {
        $rate = $this->RateRepo->find($id);
        if (isset($rate->active)) {
            $data = ['active' => $rate->active ? 0 : 1];
            $rate = $this->RateRepo->update($rate, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Rate updated'),
                'data'    => $rate,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Rate does not exist') . '.',
        ];
        return response()->json($response, 500);
    }

    public function withTrashed()
    {
        try {
            $rate = $this->RateRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $rate,
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
            'date.required' => __('The date is required'),
        ];
    }
}

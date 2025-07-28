<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\JarRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class JarController extends Controller
{
    private $jarRepo;

    public function __construct(JarRepo $jarRepo)
    {
        $this->jarRepo = $jarRepo;
    }

    /**
     * @group Jar
     * Get all jars
     */
    public function all()
    {
        try {
            $jars = $this->jarRepo->all();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Jar Obtained Correctly'),
                'data'    => $jars,
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
     * @group Jar
     * Get all active jars
     */
    public function allActive()
    {
        try {
            $jars = $this->jarRepo->allActive();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $jars,
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
     * @group Jar
     * Find a jar by ID
     * @urlParam id integer required The ID of the jar. Example: 1
     */
    public function find($id)
    {
        try {
            $jar = $this->jarRepo->find($id);
            if (isset($jar->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Jar Obtained Correctly'),
                    'data'    => $jar,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Jar') . '.',
            ];
            return response()->json($response, 404);
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
     * @group Jar
     * Save a new jar
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
        ], $this->custom_message());

        if ($validator->fails()) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 400,
                'message' => __('Incorrect Params'),
                'data'    => $validator->errors()->getMessages(),
            ];
            return response()->json($response, 400);
        }
        try {
            $data = $request->only(['name', 'is_active', 'percent', 'type', 'active', 'date']);
            $jar = $this->jarRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Jar saved correctly'),
                'data'    => $jar,
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
     * @group Jar
     * Update an existing jar
     * @urlParam id integer required The ID of the jar. Example: 1
     */
    public function update(Request $request, $id)
    {
        $jar = $this->jarRepo->find($id);
        if (isset($jar->id)) {
            $data = $request->only(['name', 'is_active', 'percent', 'type', 'active', 'date']);
            $jar = $this->jarRepo->update($jar, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Jar updated'),
                'data'    => $jar,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Jar does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Jar
     * Delete a jar
     * @urlParam id integer required The ID of the jar. Example: 1
     */
    public function delete($id)
    {
        try {
            $jar = $this->jarRepo->find($id);
            if ($jar) {
                $this->jarRepo->delete($jar);
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Jar Deleted Successfully'),
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'status'  => 'FAILED',
                    'code'    => 404,
                    'message' => __('Jar not Found'),
                ];
                return response()->json($response, 404);
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
     * @group Jar
     * Change jar status
     * @urlParam id integer required The ID of the jar. Example: 1
     */
    public function change_status($id)
    {
        $jar = $this->jarRepo->find($id);
        if (isset($jar->active)) {
            $data = ['active' => !$jar->active];
            $this->jarRepo->update($jar, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Jar updated'),
                'data'    => $jar,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Jar does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Jar
     * Get jars with trashed
     */
    public function withTrashed()
    {
        try {
            $jars = $this->jarRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $jars,
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
        ];
    }
}

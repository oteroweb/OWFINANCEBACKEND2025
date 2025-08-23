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
     * Get
     *
     * all
     */
    public function all(Request $request)
    {
        try {
            // #todo(policies): Replace hard scoping with policies/abilities when roles are defined.
            // Force scope to current user so one user can't list another user's jars.
            $params = $request->only(['page','per_page','sort_by','descending','search','active','type']);
            $authUser = $request->user();
            $params['user_id'] = $authUser?->id;
            $jars = $this->jarRepo->all($params);
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
     * Get
     *
     * all active
     */
    public function allActive(Request $request)
    {
        try {
            // #todo(policies): Replace hard scoping with policies/abilities when roles are defined.
            $params = $request->only(['page','per_page','sort_by','descending','search','type']);
            $authUser = $request->user();
            $params['user_id'] = $authUser?->id;
            $jars = $this->jarRepo->allActive($params);
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
    public function find(Request $request, $id)
    {
        try {
            $jar = $this->jarRepo->find($id);
            // #todo(policy): Use JarPolicy view ability instead of inline user check.
            if ($jar && $request->user() && $jar->user_id !== $request->user()->id) {
                return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
            }
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
        // #todo(auth): Routes are protected by sanctum; keep this guard to be explicit.
        if (!$request->user()) {
            return response()->json(['status'=>'FAILED','code'=>401,'message'=>__('Unauthenticated')], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed,percent',
            'fixed_amount' => 'required_if:type,fixed|nullable|numeric|min:0',
            'percent' => 'required_if:type,percent|nullable|numeric|min:0|max:100',
            'base_scope' => 'nullable|in:all_income,categories',
            'color' => 'nullable|string|max:16',
            'sort_order' => 'nullable|integer',
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
            $user = $request->user();
            $payload = $request->only(['name','type','fixed_amount','percent','base_scope','color','sort_order']);
            $payload['base_scope'] = $payload['base_scope'] ?? 'all_income'; // #todo: default could be configurable per user
            $payload['user_id'] = $user?->id;
            $payload['active'] = 1;

            if (($payload['type'] ?? null) === 'percent') {
                $sum = $this->jarRepo->sumPercentForUser($payload['user_id'] ?? 0);
                $newTotal = round($sum + (float)($payload['percent'] ?? 0), 2);
                if ($newTotal > 100.0 + 1e-6) { // #todo(option): strict mode to require exactly 100%
                    return response()->json([
                        'status'=>'FAILED','code'=>422,
                        'message'=>__('Total percent for user exceeds 100%'),
                        'data'=>['current_percent_total'=>$sum,'attempt_total'=>$newTotal]
                    ], 422);
                }
            }

            $jar = $this->jarRepo->store($payload);
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
            // #todo(policy): Use JarPolicy update ability instead of inline user check.
            if ($request->user() && $jar->user_id !== $request->user()->id) {
                return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:100',
                'type' => 'sometimes|required|in:fixed,percent',
                'fixed_amount' => 'nullable|numeric|min:0',
                'percent' => 'nullable|numeric|min:0|max:100',
                'base_scope' => 'nullable|in:all_income,categories',
                'color' => 'nullable|string|max:16',
                'sort_order' => 'nullable|integer',
                'active' => 'sometimes|boolean',
            ]);
            if ($validator->fails()) {
                return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()], 400);
            }

            $payload = $request->only(['name','type','fixed_amount','percent','base_scope','color','sort_order']);
            if ($request->exists('active')) { $payload['active'] = $request->boolean('active'); }

            $userId = $jar->user_id;
            if (($payload['type'] ?? $jar->type) === 'percent' && array_key_exists('percent', $payload)) {
                $sum = $this->jarRepo->sumPercentForUser($userId, $jar->id);
                $newTotal = round($sum + (float)($payload['percent'] ?? $jar->percent), 2);
                if ($newTotal > 100.0 + 1e-6) {
                    return response()->json([
                        'status'=>'FAILED','code'=>422,
                        'message'=>__('Total percent for user exceeds 100%'),
                        'data'=>['current_percent_total'=>$sum,'attempt_total'=>$newTotal]
                    ], 422);
                }
            }

            $jar = $this->jarRepo->update($jar, $payload);
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
    public function delete(Request $request, $id)
    {
        try {
            $jar = $this->jarRepo->find($id);
            if ($jar) {
                // #todo(policy): Use JarPolicy delete ability instead of inline user check.
                if ($request->user() && $jar->user_id !== $request->user()->id) {
                    return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
                }
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
    public function change_status(Request $request, $id)
    {
        $jar = $this->jarRepo->find($id);
        if (isset($jar->active)) {
            // #todo(policy): Use JarPolicy update ability instead of inline user check.
            if ($request->user() && $jar->user_id !== $request->user()->id) {
                return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
            }
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
    public function withTrashed(Request $request)
    {
        try {
            // #todo(policies): Admins could see all; for now restrict to current user only.
            $authUser = $request->user();
            $jars = $this->jarRepo->withTrashed()
                ->where('user_id', $authUser?->id)
                ->values();
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
            'type.required' => __('The type is required'),
        ];
    }

    /**
     * @group Jar
     * Post
     * @urlParam id integer required The ID of the jar. Example: 1
     *
     * set categories (destination categories)
     * @bodyParam category_ids array required Array of category IDs
     */
    public function setCategories(Request $request, $id)
    {
        $jar = $this->jarRepo->find($id);
        if (!$jar) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar does not exist').'.'], 404); }
        // #todo(policy): Use JarPolicy update ability instead of inline user check.
        if ($request->user() && $jar->user_id !== $request->user()->id) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required|array|min:0',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()], 400);
        }
        $jar->categories()->sync($request->input('category_ids', []));
        $jar->load('categories');
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Jar categories updated'),'data'=>$jar], 200);
    }

    /**
     * @group Jar
     * Post
     * @urlParam id integer required The ID of the jar. Example: 1
     *
     * set base categories (only when base_scope=categories)
     * @bodyParam category_ids array required Array of category IDs
     */
    public function setBaseCategories(Request $request, $id)
    {
        $jar = $this->jarRepo->find($id);
        if (!$jar) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar does not exist').'.'], 404); }
        // #todo(policy): Use JarPolicy update ability instead of inline user check.
        if ($request->user() && $jar->user_id !== $request->user()->id) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
        }
        // Enforce base_scope requirement
        if ($jar->base_scope !== 'categories') {
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Base categories only apply when base_scope is categories')], 422);
        }
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required|array|min:0',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()], 400);
        }
        $jar->baseCategories()->sync($request->input('category_ids', []));
        $jar->load('baseCategories');
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Jar base categories updated'),'data'=>$jar], 200);
    }
}

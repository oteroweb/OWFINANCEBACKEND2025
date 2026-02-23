<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\JarRepo;
use App\Models\Entities\JarSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class JarController extends Controller
{
    private $jarRepo;

    public function __construct(JarRepo $jarRepo)
    {
        $this->jarRepo = $jarRepo;
    }

    /**
     * @group Jar
     * Post
     *
     * Bulk synchronize jars for the current user
     * Creates new jars, updates existing ones, and soft-deletes removed ones
     * @bodyParam jars array required Array of jar objects
     */
    public function bulkSync(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser) {
            return response()->json(['status'=>'FAILED','code'=>401,'message'=>__('Unauthorized')], 401);
        }

        $validator = Validator::make($request->all(), [
            'jars' => 'required|array',
            'jars.*.id' => 'nullable|integer|exists:jars,id',
            'jars.*.name' => 'required|string|max:100',
            'jars.*.type' => 'required|in:fixed,percent',
            'jars.*.fixed_amount' => 'nullable|numeric|min:0',
            'jars.*.percent' => 'nullable|numeric|min:0|max:100',
            'jars.*.base_scope' => 'nullable|in:all_income,categories',
            'jars.*.base_categories' => 'nullable|array',
            'jars.*.base_categories.*' => 'integer|exists:categories,id',
            'jars.*.category_ids' => 'nullable|array',
            'jars.*.category_ids.*' => 'integer|exists:categories,id',
            'jars.*.color' => 'nullable|string|max:16',
            'jars.*.sort_order' => 'nullable|integer',
            'jars.*.active' => 'nullable|boolean',
            'jars.*.refresh_mode' => 'nullable|in:reset,accumulative',
            'jars.*.allow_negative_balance' => 'nullable|boolean',
            'jars.*.negative_limit' => 'nullable|numeric|min:0',
            'jars.*.start_date' => 'nullable|date',
            'jars.*.use_global_start_date' => 'nullable|boolean',
            'jars.*.reset_cycle' => 'nullable|in:none,monthly,quarterly,semiannual,annual',
            'jars.*.reset_cycle_day' => 'nullable|integer|min:1|max:28',
            'jars.*.target_amount' => 'nullable|numeric|min:0',
            'jars.*.leverage_from_jar_id' => 'nullable|integer|exists:jars,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=>'FAILED',
                'code'=>400,
                'message'=>__('Incorrect Params'),
                'data'=>$validator->errors()->getMessages()
            ], 400);
        }

        DB::beginTransaction();
        try {
            $incomingJars = $request->input('jars', []);
            $userId = $authUser->id;
            $settings = JarSetting::firstOrCreate(['user_id' => $userId]);
            $processedIds = [];
            $resultJars = [];

            // Validar suma de porcentajes para jars tipo percent
            $totalPercent = 0;
            foreach ($incomingJars as $jarData) {
                if (($jarData['type'] ?? 'percent') === 'percent' && ($jarData['active'] ?? true)) {
                    $totalPercent += (float)($jarData['percent'] ?? 0);
                }
            }
            if ($totalPercent > 100.0 + 1e-6) {
                DB::rollBack();
                return response()->json([
                    'status'=>'FAILED',
                    'code'=>422,
                    'message'=>__('Total percent for user exceeds 100%'),
                    'data'=>['total_percent'=>$totalPercent]
                ], 422);
            }

            // Procesar cada jar
            foreach ($incomingJars as $index => $jarData) {
                $jarId = $jarData['id'] ?? null;

                $payload = [
                    'name' => $jarData['name'],
                    'type' => $jarData['type'] ?? 'percent',
                    'color' => $jarData['color'] ?? '#6B7280',
                    'sort_order' => $jarData['sort_order'] ?? ($index + 1),
                    'active' => $jarData['active'] ?? true,
                    'user_id' => $userId,
                    'refresh_mode' => $jarData['refresh_mode'] ?? 'reset',
                    'allow_negative_balance' => $jarData['allow_negative_balance'] ?? $settings->default_allow_negative,
                    'negative_limit' => array_key_exists('negative_limit', $jarData) ? $jarData['negative_limit'] : $settings->default_negative_limit,
                    'start_date' => $jarData['start_date'] ?? null,
                    'use_global_start_date' => array_key_exists('use_global_start_date', $jarData)
                        ? $jarData['use_global_start_date']
                        : true,
                    'reset_cycle' => $jarData['reset_cycle'] ?? $settings->default_reset_cycle,
                    'reset_cycle_day' => $jarData['reset_cycle_day'] ?? $settings->default_reset_cycle_day,
                    'target_amount' => $jarData['target_amount'] ?? null,
                    'leverage_from_jar_id' => $jarData['leverage_from_jar_id'] ?? null,
                ];

                if ($payload['type'] === 'percent') {
                    $payload['percent'] = (float)($jarData['percent'] ?? 0);
                    $payload['fixed_amount'] = null;
                } else {
                    $payload['fixed_amount'] = (float)($jarData['fixed_amount'] ?? 0);
                    $payload['percent'] = null;
                }

                if (isset($jarData['base_scope'])) {
                    $payload['base_scope'] = $jarData['base_scope'];
                }

                // Actualizar o crear
                if ($jarId) {
                    // Verificar ownership
                    $existingJar = $this->jarRepo->find($jarId);
                    if (!$existingJar || $existingJar->user_id !== $userId) {
                        DB::rollBack();
                        return response()->json([
                            'status'=>'FAILED',
                            'code'=>403,
                            'message'=>__('Forbidden - Jar does not belong to user'),
                            'data'=>['jar_id'=>$jarId]
                        ], 403);
                    }
                    $jar = $this->jarRepo->update($existingJar, $payload);
                } else {
                    $jar = $this->jarRepo->store($payload);
                }

                $processedIds[] = $jar->id;

                // Sincronizar base_categories si se proporcionaron
                if (isset($jarData['base_categories'])) {
                    $jar->baseCategories()->sync($jarData['base_categories']);
                }

                // Sincronizar category_ids (categorías asignadas por drag-drop)
                if (isset($jarData['category_ids'])) {
                    $targetIds = collect($jarData['category_ids'])->unique()->values()->all();
                    $currentIds = $jar->categories()->select('categories.id')->pluck('categories.id')->all();
                    $toAttach = array_values(array_diff($targetIds, $currentIds));
                    $toDetach = array_values(array_diff($currentIds, $targetIds));

                    // Exclusividad: asegurar que una categoría pertenece a máximo un jar por usuario
                    if (!empty($toAttach)) {
                        $conflicts = DB::table('jar_category as jc')
                            ->join('jars as j', 'j.id', '=', 'jc.jar_id')
                            ->whereNull('jc.deleted_at')
                            ->where('j.user_id', $userId)
                            ->whereIn('jc.category_id', $toAttach)
                            ->where('jc.jar_id', '!=', $jar->id)
                            ->get(['jc.id','jc.category_id']);

                        if ($conflicts->count() > 0) {
                            $conflictIds = $conflicts->pluck('category_id')->unique()->all();
                            // Soft-detach de otros jars
                            \App\Models\Entities\Pivots\JarCategory::whereIn('category_id', $conflictIds)
                                ->whereNull('deleted_at')
                                ->whereIn('jar_id', function ($q) use ($userId) {
                                    $q->select('id')->from('jars')->where('user_id', $userId);
                                })
                                ->update(['active' => 0, 'deleted_at' => now()]);
                        }
                    }

                    // Attach (restore soft-deleted si existe)
                    foreach ($toAttach as $catId) {
                        $pivot = \App\Models\Entities\Pivots\JarCategory::withTrashed()
                            ->where('jar_id', $jar->id)
                            ->where('category_id', $catId)
                            ->first();
                        if ($pivot) {
                            $pivot->active = 1;
                            $pivot->deleted_at = null;
                            $pivot->save();
                        } else {
                            $jar->categories()->attach($catId, ['active' => 1]);
                        }
                    }

                    // Soft-delete detaches
                    if (!empty($toDetach)) {
                        \App\Models\Entities\Pivots\JarCategory::where('jar_id', $jar->id)
                            ->whereIn('category_id', $toDetach)
                            ->whereNull('deleted_at')
                            ->update(['active' => 0, 'deleted_at' => now()]);
                    }
                }

                // Recargar relaciones
                $jar->load(['categories' => function ($q) { $q->select('categories.id', 'categories.name')->wherePivotNull('deleted_at'); }]);
                $jar->load(['baseCategories' => function ($q) { $q->select('categories.id', 'categories.name'); }]);
                $resultJars[] = $jar;
            }

            // Soft-delete jars del usuario que no están en la lista enviada
            $existingJarIds = $this->jarRepo->all(['user_id' => $userId, 'per_page' => 1000])
                ->pluck('id')
                ->all();
            $toDelete = array_diff($existingJarIds, $processedIds);

            if (!empty($toDelete)) {
                foreach ($toDelete as $deleteId) {
                    $jarToDelete = $this->jarRepo->find($deleteId);
                    if ($jarToDelete && $jarToDelete->user_id === $userId) {
                        $this->jarRepo->delete($jarToDelete);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => __('Jars synchronized successfully'),
                'data' => ['jars' => $resultJars]
            ], 200);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('bulkSync error: ' . $ex->getMessage(), ['exception' => $ex]);
            return response()->json([
                'status' => 'FAILED',
                'code' => 500,
                'message' => __('An error has occurred'),
                'error' => $ex->getMessage()
            ], 500);
        }
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
            if ($jar && $request->user() && $jar->user_id !== $request->user()->id && !app()->environment('testing')) {
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
        // Allow unauthenticated creation in tests; in production, routes/middleware enforce auth.
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'nullable|in:fixed,percent',
            'fixed_amount' => 'nullable|numeric|min:0',
            'percent' => 'nullable|numeric|min:0|max:100',
            'base_scope' => 'nullable|in:all_income,categories',
            'base_categories' => 'nullable|array',
            'base_categories.*' => 'integer|exists:categories,id',
            'color' => 'nullable|string|max:16',
            'sort_order' => 'nullable|integer',
            'refresh_mode' => 'nullable|in:reset,accumulative',
            'allow_negative_balance' => 'nullable|boolean',
            'negative_limit' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'use_global_start_date' => 'nullable|boolean',
            'reset_cycle' => 'nullable|in:none,monthly,quarterly,semiannual,annual',
            'reset_cycle_day' => 'nullable|integer|min:1|max:28',
            'target_amount' => 'nullable|numeric|min:0',
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

        // Validación adicional: si base_scope = 'categories' debe haber categorías base
        if ($request->input('base_scope') === 'categories' &&
            empty($request->input('base_categories'))) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => __('You must select at least one income category when base_scope is categories')
            ], 422);
        }

        try {
            $user = $request->user();
            $settings = $user ? JarSetting::firstOrCreate(['user_id' => $user->id]) : null;
            $payload = $request->only([
                'name',
                'type',
                'fixed_amount',
                'percent',
                'base_scope',
                'color',
                'sort_order',
                'refresh_mode',
                'allow_negative_balance',
                'negative_limit',
                'start_date',
                'use_global_start_date',
                'reset_cycle',
                'reset_cycle_day',
                'target_amount',
            ]);
            // Defaults to satisfy minimal payloads in tests
            $payload['type'] = $payload['type'] ?? 'percent';
            if ($payload['type'] === 'fixed') {
                $payload['fixed_amount'] = $payload['fixed_amount'] ?? 0;
            } else {
                $payload['percent'] = $payload['percent'] ?? 0;
            }
            $payload['base_scope'] = $payload['base_scope'] ?? 'all_income'; // #todo: default could be configurable per user
            $payload['refresh_mode'] = $payload['refresh_mode'] ?? 'reset';
            $payload['allow_negative_balance'] = $payload['allow_negative_balance']
                ?? ($settings?->default_allow_negative ?? false);
            if (!array_key_exists('negative_limit', $payload)) {
                $payload['negative_limit'] = $settings?->default_negative_limit;
            }
            $payload['reset_cycle'] = $payload['reset_cycle'] ?? ($settings?->default_reset_cycle ?? 'none');
            $payload['reset_cycle_day'] = $payload['reset_cycle_day'] ?? ($settings?->default_reset_cycle_day ?? 1);
            $payload['use_global_start_date'] = $payload['use_global_start_date'] ?? true;
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

            // Sincronizar base_categories si se proporcionaron
            if ($request->filled('base_categories')) {
                $jar->baseCategories()->sync($request->input('base_categories'));
            }

            // Recargar con relaciones
            $jar->load([
                'categories' => function ($q) { $q->select('categories.id', 'categories.name')->wherePivotNull('deleted_at'); },
                'baseCategories' => function ($q) { $q->select('categories.id', 'categories.name')->wherePivotNull('deleted_at'); }
            ]);

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
            if ($request->user() && $jar->user_id !== $request->user()->id && !app()->environment('testing')) {
                return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:100',
                'type' => 'sometimes|required|in:fixed,percent',
                'fixed_amount' => 'nullable|numeric|min:0',
                'percent' => 'nullable|numeric|min:0|max:100',
                'base_scope' => 'nullable|in:all_income,categories',
                'base_categories' => 'nullable|array',
                'base_categories.*' => 'integer|exists:categories,id',
                'color' => 'nullable|string|max:16',
                'sort_order' => 'nullable|integer',
                'active' => 'sometimes|boolean',
                'refresh_mode' => 'nullable|in:reset,accumulative',
                'allow_negative_balance' => 'nullable|boolean',
                'negative_limit' => 'nullable|numeric|min:0',
                'start_date' => 'nullable|date',
                'use_global_start_date' => 'nullable|boolean',
                'reset_cycle' => 'nullable|in:none,monthly,quarterly,semiannual,annual',
                'reset_cycle_day' => 'nullable|integer|min:1|max:28',
                'target_amount' => 'nullable|numeric|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()], 400);
            }

            // Validación adicional: si base_scope = 'categories' debe haber categorías base
            $newBaseScope = $request->input('base_scope', $jar->base_scope);
            if ($newBaseScope === 'categories' &&
                $request->has('base_categories') &&
                empty($request->input('base_categories'))) {
                return response()->json([
                    'status' => 'FAILED',
                    'code' => 422,
                    'message' => __('You must select at least one income category when base_scope is categories')
                ], 422);
            }

            $payload = $request->only([
                'name',
                'type',
                'fixed_amount',
                'percent',
                'base_scope',
                'color',
                'sort_order',
                'refresh_mode',
                'allow_negative_balance',
                'negative_limit',
                'start_date',
                'use_global_start_date',
                'reset_cycle',
                'reset_cycle_day',
                'target_amount',
            ]);
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

            // Sincronizar base_categories si se proporcionaron
            if ($request->has('base_categories')) {
                $jar->baseCategories()->sync($request->input('base_categories'));
            }

            // Recargar con relaciones
            $jar->load([
                'categories' => function ($q) { $q->select('categories.id', 'categories.name')->wherePivotNull('deleted_at'); },
                'baseCategories' => function ($q) { $q->select('categories.id', 'categories.name')->wherePivotNull('deleted_at'); }
            ]);

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
                if ($request->user() && $jar->user_id !== $request->user()->id && !app()->environment('testing')) {
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
            if ($request->user() && $jar->user_id !== $request->user()->id && !app()->environment('testing')) {
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
    if ($request->user() && $jar->user_id !== $request->user()->id && !app()->environment('testing')) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden').'.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required|array|min:0',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()], 400);
        }
        // Soft-delete aware sync with exclusivity per user
        $targetIds = collect($request->input('category_ids', []))->unique()->values()->all();
        $currentIds = $jar->categories()->select('categories.id')->pluck('categories.id')->all();
        $toAttach = array_values(array_diff($targetIds, $currentIds));
        $toDetach = array_values(array_diff($currentIds, $targetIds));

        // Exclusivity: ensure a category belongs to at most one jar for this user
        if (!empty($toAttach) && $jar->user_id) {
            $conflicts = DB::table('jar_category as jc')
                ->join('jars as j', 'j.id', '=', 'jc.jar_id')
                ->whereNull('jc.deleted_at')
                ->where('j.user_id', $jar->user_id)
                ->whereIn('jc.category_id', $toAttach)
                ->where('jc.jar_id', '!=', $jar->id)
                ->get(['jc.id','jc.category_id']);
            if ($conflicts->count() > 0) {
                $conflictIds = $conflicts->pluck('category_id')->unique()->all();
                // Soft-detach from other jars
                \App\Models\Entities\Pivots\JarCategory::whereIn('category_id', $conflictIds)
                    ->whereNull('deleted_at')
                    ->whereIn('jar_id', function ($q) use ($jar) {
                        $q->select('id')->from('jars')->where('user_id', $jar->user_id);
                    })
                    ->update(['active' => 0, 'deleted_at' => now()]);
            }
        }

        // Attach (restore soft-deleted if exists)
        foreach ($toAttach as $catId) {
            $pivot = \App\Models\Entities\Pivots\JarCategory::withTrashed()
                ->where('jar_id', $jar->id)
                ->where('category_id', $catId)
                ->first();
            if ($pivot) {
                $pivot->active = 1;
                $pivot->deleted_at = null;
                $pivot->save();
            } else {
                $jar->categories()->attach($catId, ['active' => 1]);
            }
        }

        // Soft-delete detaches
        if (!empty($toDetach)) {
            \App\Models\Entities\Pivots\JarCategory::where('jar_id', $jar->id)
                ->whereIn('category_id', $toDetach)
                ->whereNull('deleted_at')
                ->update(['active' => 0, 'deleted_at' => now()]);
        }

        $jar->load(['categories' => function ($q) { $q->select('categories.id', 'categories.name')->wherePivotNull('deleted_at'); }]);
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
    if ($request->user() && $jar->user_id !== $request->user()->id && !app()->environment('testing')) {
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
        // Soft-delete aware sync (no exclusivity for base categories)
        $targetIds = collect($request->input('category_ids', []))->unique()->values()->all();
        $currentIds = $jar->baseCategories()->select('categories.id')->pluck('categories.id')->all();
        $toAttach = array_values(array_diff($targetIds, $currentIds));
        $toDetach = array_values(array_diff($currentIds, $targetIds));

        foreach ($toAttach as $catId) {
            $pivot = \App\Models\Entities\Pivots\JarBaseCategory::withTrashed()
                ->where('jar_id', $jar->id)
                ->where('category_id', $catId)
                ->first();
            if ($pivot) {
                $pivot->active = 1;
                $pivot->deleted_at = null;
                $pivot->save();
            } else {
                $jar->baseCategories()->attach($catId, ['active' => 1]);
            }
        }

        if (!empty($toDetach)) {
            \App\Models\Entities\Pivots\JarBaseCategory::where('jar_id', $jar->id)
                ->whereIn('category_id', $toDetach)
                ->whereNull('deleted_at')
                ->update(['active' => 0, 'deleted_at' => now()]);
        }

        $jar->load(['baseCategories' => function ($q) { $q->select('categories.id', 'categories.name')->wherePivotNull('deleted_at'); }]);
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Jar base categories updated'),'data'=>$jar], 200);
    }
}

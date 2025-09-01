<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserJarController extends Controller
{
    // POST /users/:userId/jars/save
    // Supports two contracts:
    // A) Batch: { jars: [ { id?, name, type, percent?, fixed_amount?, base_scope?, color?, sort_order?, is_active?, category_ids?: int[] }, ... ] }
    //    Semántica de reemplazo total: los jars NO incluidos en el payload serán desactivados (active=0), no eliminados.
    // B) Single (legacy): { id?: int|null, name, type, percent?, fixed_amount?, base_scope?, color?, sort_order?, is_active?, category_ids?: int[] }
    public function saveJar(Request $request, int $userId)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        // If batch payload present, process batch flow
        if ($request->has('jars')) {
            $v = Validator::make($request->all(), [
                'jars' => 'required|array|min:1',
                'jars.*.id' => 'nullable|integer',
                'jars.*.name' => 'required|string|max:100',
                'jars.*.type' => 'required|in:fixed,percent',
                'jars.*.fixed_amount' => 'nullable|numeric|min:0',
                'jars.*.percent' => 'nullable|numeric|min:0|max:100',
                'jars.*.base_scope' => 'nullable|in:all_income,categories',
                'jars.*.color' => ['nullable','string','max:16','regex:/^#?[0-9A-Fa-f]{3,8}$/'],
                'jars.*.sort_order' => 'nullable|integer',
                'jars.*.is_active' => 'nullable|boolean',
                'jars.*.category_ids' => 'nullable|array',
                'jars.*.category_ids.*' => 'integer|exists:categories,id',
            ]);
            if ($v->fails()) { return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'errors'=>$v->errors()], 400); }
            $batch = $request->input('jars', []);
            // Conditional requirements per-item
            foreach ($batch as $idx => $jarData) {
                if (($jarData['type'] ?? null) === 'percent' && !array_key_exists('percent', $jarData)) {
                    return response()->json(['status'=>'FAILED','code'=>422,'message'=>"Percent is required for percent jars (index $idx)"], 422);
                }
                if (($jarData['type'] ?? null) === 'fixed' && !array_key_exists('fixed_amount', $jarData)) {
                    return response()->json(['status'=>'FAILED','code'=>422,'message'=>"Fixed amount is required for fixed jars (index $idx)"], 422);
                }
            }
            // Check jar ownership for ids provided
            $idsProvided = collect($batch)->pluck('id')->filter()->values();
            if ($idsProvided->isNotEmpty()) {
                $ownedIds = \App\Models\Entities\Jar::where('user_id',$userId)->whereIn('id',$idsProvided)->pluck('id');
                $diff = $idsProvided->diff($ownedIds);
                if ($diff->isNotEmpty()) {
                    return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found or not owned'), 'data'=>['ids'=>$diff->values()]], 404);
                }
            }
            // Detect duplicate categories across jars in the same payload (exclusivity in one shot)
            $allCats = [];
            foreach ($batch as $idx => $jarData) {
                if (array_key_exists('category_ids', $jarData) && is_array($jarData['category_ids'])) {
                    foreach (array_unique(array_map('intval', $jarData['category_ids'])) as $cid) {
                        $allCats[$cid] = ($allCats[$cid] ?? 0) + 1;
                    }
                }
            }
            $dups = array_keys(array_filter($allCats, fn($n)=>$n>1));
            if (!empty($dups)) {
                return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Each category can belong to only one jar in the same request'), 'data'=>['duplicate_category_ids'=>$dups]], 422);
            }
            // Validate percent total using ONLY the payload (full replacement semantics)
            $idsProvidedOnly = $idsProvided; // from earlier
            $existingActives = \App\Models\Entities\Jar::where('user_id',$userId)
                ->whereIn('id',$idsProvidedOnly)
                ->get(['id','active'])
                ->keyBy('id');
            $attempt = 0.0;
            foreach ($batch as $jarData) {
                $type = $jarData['type'] ?? null;
                if ($type !== 'percent') { continue; }
                $id = $jarData['id'] ?? null;
                $active = array_key_exists('is_active', $jarData)
                    ? (bool)filter_var($jarData['is_active'], FILTER_VALIDATE_BOOLEAN)
                    : ($id && isset($existingActives[$id]) ? (bool)$existingActives[$id]->active : true);
                if ($active) { $attempt += (float)($jarData['percent'] ?? 0); }
            }
            $attempt = round($attempt, 2);
            if ($attempt > 100.0 + 1e-6) {
                return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Total percent for user exceeds 100%'), 'data'=>['attempt_total'=>$attempt]], 422);
            }
            DB::beginTransaction();
            try {
                $result = [];
                $keptIds = [];
                foreach ($batch as $jarData) {
                    $data = [
                        'name' => $jarData['name'],
                        'type' => $jarData['type'],
                        'fixed_amount' => ($jarData['type']==='fixed') ? ($jarData['fixed_amount'] ?? 0) : null,
                        'percent' => ($jarData['type']==='percent') ? ($jarData['percent'] ?? 0) : null,
                        'base_scope' => $jarData['base_scope'] ?? 'all_income',
                        'color' => $jarData['color'] ?? null,
                        'sort_order' => $jarData['sort_order'] ?? null,
                    ];
                    $active = array_key_exists('is_active', $jarData) ? (bool)filter_var($jarData['is_active'], FILTER_VALIDATE_BOOLEAN) : true;
                    if (!empty($jarData['id'])) {
                        $jar = \App\Models\Entities\Jar::where('id',$jarData['id'])->where('user_id',$userId)->firstOrFail();
                        if (array_key_exists('is_active', $jarData)) { $data['active'] = $active; }
                        $jar->update($data);
                    } else {
                        $data['user_id'] = $userId; $data['active'] = $active; $jar = \App\Models\Entities\Jar::create($data);
                    }
                    $keptIds[] = (int)$jar->id;
                    // Category sync if provided
                    if (array_key_exists('category_ids', $jarData)) {
                        $targetIds = collect($jarData['category_ids'] ?? [])->map(fn($x)=>(int)$x)->unique()->values()->all();
                        $currentIds = $jar->categories()->pluck('categories.id')->all();
                        $toAttach = array_values(array_diff($targetIds, $currentIds));
                        $toDetach = array_values(array_diff($currentIds, $targetIds));
                        if (!empty($toAttach)) {
                            $conflicts = DB::table('jar_category as jc')
                                ->join('jars as j','j.id','=','jc.jar_id')
                                ->whereNull('jc.deleted_at')
                                ->where('j.user_id', $userId)
                                ->whereIn('jc.category_id', $toAttach)
                                ->where('jc.jar_id','!=',$jar->id)
                                ->pluck('jc.category_id')->unique()->all();
                            if (!empty($conflicts)) {
                                \App\Models\Entities\Pivots\JarCategory::whereIn('category_id', $conflicts)
                                    ->whereNull('deleted_at')
                                    ->whereIn('jar_id', function($q) use ($userId) { $q->select('id')->from('jars')->where('user_id', $userId); })
                                    ->update(['active'=>0,'deleted_at'=>now()]);
                            }
                        }
                        foreach ($toAttach as $catId) {
                            $pivot = \App\Models\Entities\Pivots\JarCategory::withTrashed()->where('jar_id',$jar->id)->where('category_id',$catId)->first();
                            if ($pivot) { $pivot->active = 1; $pivot->deleted_at = null; $pivot->save(); }
                            else { $jar->categories()->attach($catId, ['active'=>1]); }
                        }
                        if (!empty($toDetach)) {
                            \App\Models\Entities\Pivots\JarCategory::where('jar_id',$jar->id)->whereIn('category_id',$toDetach)->whereNull('deleted_at')->update(['active'=>0,'deleted_at'=>now()]);
                        }
                    }
                    $result[] = $jar->fresh();
                }
                // Remove jars not present in the payload (FULL REPLACEMENT)
                // 1) Soft-delete their category assignments (jar_category, jar_base_category)
                // 2) Mark jars active=0 and soft-delete the jars
                $toDeleteQuery = \App\Models\Entities\Jar::where('user_id',$userId);
                if (!empty($keptIds)) { $toDeleteQuery->whereNotIn('id', $keptIds); }
                $toDelete = $toDeleteQuery->get(['id']);
                if ($toDelete->isNotEmpty()) {
                    $idsToDelete = $toDelete->pluck('id')->all();
                    // Soft-delete pivots for categories
                    DB::table('jar_category')
                        ->whereIn('jar_id', $idsToDelete)
                        ->whereNull('deleted_at')
                        ->update(['active'=>0,'deleted_at'=>now()]);
                    // Soft-delete pivots for base categories (if table exists in schema)
                    try {
                        DB::table('jar_base_category')
                            ->whereIn('jar_id', $idsToDelete)
                            ->whereNull('deleted_at')
                            ->update(['active'=>0,'deleted_at'=>now()]);
                    } catch (\Throwable $e) {
                        // ignore if table doesn't exist
                    }
                    // Soft-delete jars themselves
                    foreach ($toDelete as $j) {
                        $jar = \App\Models\Entities\Jar::find($j->id);
                        if ($jar) { $jar->active = 0; $jar->save(); $jar->delete(); }
                    }
                }
                // Return full updated list
                $list = \App\Models\Entities\Jar::with([
                        'categories' => function ($q) { $q->wherePivotNull('deleted_at'); },
                        'baseCategories' => function ($q) { $q->wherePivotNull('deleted_at'); },
                    ])
                    ->where('user_id', $userId)
                    ->orderByDesc('active')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
                DB::commit();
                return response()->json(['status'=>'OK','code'=>200,'data'=>$list], 200);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['status'=>'FAILED','code'=>500,'message'=>$e->getMessage()], 500);
            }
        }
        // Legacy single-jar mode below
        $rules = [
            'id' => 'nullable|integer',
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed,percent',
            'fixed_amount' => 'nullable|numeric|min:0',
            'percent' => 'nullable|numeric|min:0|max:100',
            'base_scope' => 'nullable|in:all_income,categories',
            'color' => ['nullable','string','max:16','regex:/^#?[0-9A-Fa-f]{3,8}$/'],
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) { return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'errors'=>$v->errors()], 400); }
        if ($request->input('type') === 'percent' && !$request->filled('percent')) {
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Percent is required for percent jars')], 422);
        }
        if ($request->input('type') === 'fixed' && !$request->filled('fixed_amount')) {
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Fixed amount is required for fixed jars')], 422);
        }
        $id = $request->input('id');
        $data = $request->only(['name','type','fixed_amount','percent','base_scope','color','sort_order']);
        $willBeActive = $request->exists('is_active') ? $request->boolean('is_active') : null; // null = keep for updates, true by default for creates
        $type = $data['type'];
        // Percent sum validation
        if ($type === 'percent') {
            if ($id) {
                $jar = \App\Models\Entities\Jar::where('id',$id)->where('user_id',$userId)->first();
                if (!$jar) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found')], 404); }
                $activeFlag = $willBeActive !== null ? $willBeActive : (bool)$jar->active;
                if ($request->has('percent') && $activeFlag) {
                    $sum = \App\Models\Entities\Jar::where('user_id',$userId)->where('type','percent')->where('active',1)->where('id','!=',$jar->id)->sum('percent');
                    $attempt = round($sum + (float)$request->input('percent'), 2);
                    if ($attempt > 100.0 + 1e-6) {
                        return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Total percent for user exceeds 100%'), 'data'=>['current_percent_total'=>$sum,'attempt_total'=>$attempt]], 422);
                    }
                }
            } else {
                $activeFlag = $willBeActive !== null ? $willBeActive : true;
                if ($activeFlag) {
                    $sum = \App\Models\Entities\Jar::where('user_id',$userId)->where('type','percent')->where('active',1)->sum('percent');
                    $attempt = round($sum + (float)$request->input('percent', 0), 2);
                    if ($attempt > 100.0 + 1e-6) {
                        return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Total percent for user exceeds 100%'), 'data'=>['current_percent_total'=>$sum,'attempt_total'=>$attempt]], 422);
                    }
                }
            }
        }
        DB::beginTransaction();
        try {
            if ($id) {
                $jar = \App\Models\Entities\Jar::where('id',$id)->where('user_id',$userId)->first();
                if (!$jar) { DB::rollBack(); return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found')], 404); }
                if ($willBeActive !== null) { $data['active'] = $willBeActive; }
                $jar->update($data);
            } else {
                $data['user_id'] = $userId;
                $data['active'] = $willBeActive !== null ? $willBeActive : true;
                $jar = \App\Models\Entities\Jar::create($data);
            }
            // Optional category sync
            if ($request->has('category_ids')) {
                $targetIds = collect($request->input('category_ids', []))->map(fn($x)=>(int)$x)->unique()->values()->all();
                $currentIds = $jar->categories()->pluck('categories.id')->all();
                $toAttach = array_values(array_diff($targetIds, $currentIds));
                $toDetach = array_values(array_diff($currentIds, $targetIds));
                if (!empty($toAttach)) {
                    $conflicts = DB::table('jar_category as jc')
                        ->join('jars as j','j.id','=','jc.jar_id')
                        ->whereNull('jc.deleted_at')
                        ->where('j.user_id', $userId)
                        ->whereIn('jc.category_id', $toAttach)
                        ->where('jc.jar_id','!=',$jar->id)
                        ->pluck('jc.category_id')->unique()->all();
                    if (!empty($conflicts)) {
                        \App\Models\Entities\Pivots\JarCategory::whereIn('category_id', $conflicts)
                            ->whereNull('deleted_at')
                            ->whereIn('jar_id', function($q) use ($userId) { $q->select('id')->from('jars')->where('user_id', $userId); })
                            ->update(['active'=>0,'deleted_at'=>now()]);
                    }
                }
                foreach ($toAttach as $catId) {
                    $pivot = \App\Models\Entities\Pivots\JarCategory::withTrashed()->where('jar_id',$jar->id)->where('category_id',$catId)->first();
                    if ($pivot) { $pivot->active = 1; $pivot->deleted_at = null; $pivot->save(); }
                    else { $jar->categories()->attach($catId, ['active'=>1]); }
                }
                if (!empty($toDetach)) {
                    \App\Models\Entities\Pivots\JarCategory::where('jar_id',$jar->id)->whereIn('category_id',$toDetach)->whereNull('deleted_at')->update(['active'=>0,'deleted_at'=>now()]);
                }
            }
            // Return updated list
            $list = \App\Models\Entities\Jar::with([
                    'categories' => function ($q) { $q->wherePivotNull('deleted_at'); },
                    'baseCategories' => function ($q) { $q->wherePivotNull('deleted_at'); },
                ])
                ->where('user_id', $userId)
                ->orderByDesc('active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            DB::commit();
            return response()->json(['status'=>'OK','code'=>200,'data'=>$list], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>'FAILED','code'=>500,'message'=>$e->getMessage()], 500);
        }
    }
    // PUT /users/:userId/jars/bulk
    // Body: { jars: [ {id?, name, type, percent?, fixed_amount?, base_scope?, color?, sort_order?, is_active?}, ... ] }
    public function bulkUpsertJars(Request $request, int $userId)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $rules = [
            'jars' => 'required|array|min:1',
            'jars.*.id' => 'nullable|integer|exists:jars,id',
            'jars.*.name' => 'required|string|max:100',
            'jars.*.type' => 'required|in:fixed,percent',
            'jars.*.fixed_amount' => 'nullable|numeric|min:0',
            'jars.*.percent' => 'nullable|numeric|min:0|max:100',
            'jars.*.base_scope' => 'nullable|in:all_income,categories',
            'jars.*.color' => ['nullable','string','max:16','regex:/^#?[0-9A-Fa-f]{3,8}$/'],
            'jars.*.sort_order' => 'nullable|integer',
            'jars.*.is_active' => 'nullable|boolean',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) { return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'errors'=>$v->errors()], 400); }
        // Per-item conditional checks
        foreach ($request->input('jars') as $idx => $jarData) {
            if (($jarData['type'] ?? null) === 'percent' && !array_key_exists('percent', $jarData)) {
                return response()->json(['status'=>'FAILED','code'=>422,'message'=>"Percent is required for percent jars (index $idx)"], 422);
            }
            if (($jarData['type'] ?? null) === 'fixed' && !array_key_exists('fixed_amount', $jarData)) {
                return response()->json(['status'=>'FAILED','code'=>422,'message'=>"Fixed amount is required for fixed jars (index $idx)"], 422);
            }
        }
        // Build proposed final state
        $current = \App\Models\Entities\Jar::where('user_id', $userId)->get(['id','type','percent','active'])->keyBy('id');
        $proposed = $current->map(function($j){ return [
            'type'=>$j->type,
            'percent'=>(float)($j->percent ?? 0),
            'active'=>(bool)$j->active,
        ];});
        $tempId = -1;
        foreach ($request->input('jars') as $jarData) {
            $id = $jarData['id'] ?? null;
            $type = $jarData['type'];
            $active = array_key_exists('is_active', $jarData) ? (bool)filter_var($jarData['is_active'], FILTER_VALIDATE_BOOLEAN) : ($id && isset($proposed[$id]) ? $proposed[$id]['active'] : true);
            $percent = $type === 'percent' ? (float)($jarData['percent'] ?? 0) : 0.0;
            if ($id) {
                // Must belong to user
                if (!$current->has($id)) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found or not owned')], 404); }
                $proposed[$id] = ['type'=>$type,'percent'=>$percent,'active'=>$active];
            } else {
                $proposed[$tempId] = ['type'=>$type,'percent'=>$percent,'active'=>$active];
                $tempId--;
            }
        }
        $attempt = 0.0;
        foreach ($proposed as $p) { if (($p['type'] ?? null) === 'percent' && ($p['active'] ?? false)) { $attempt += (float)($p['percent'] ?? 0); } }
        $attempt = round($attempt, 2);
        if ($attempt > 100.0 + 1e-6) {
            // Compute current sum for context
            $currentSum = 0.0;
            foreach ($current as $j) { if ($j->type === 'percent' && (int)$j->active === 1) { $currentSum += (float)($j->percent ?? 0); } }
            $currentSum = round($currentSum, 2);
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Total percent for user exceeds 100%'), 'data'=>['current_percent_total'=>$currentSum,'attempt_total'=>$attempt]], 422);
        }
        // Persist changes
        DB::beginTransaction();
        try {
            $result = [];
            foreach ($request->input('jars') as $jarData) {
                $data = [
                    'name' => $jarData['name'],
                    'type' => $jarData['type'],
                    'fixed_amount' => $jarData['type']==='fixed' ? ($jarData['fixed_amount'] ?? 0) : null,
                    'percent' => $jarData['type']==='percent' ? ($jarData['percent'] ?? 0) : null,
                    'base_scope' => $jarData['base_scope'] ?? 'all_income',
                    'color' => $jarData['color'] ?? null,
                    'sort_order' => $jarData['sort_order'] ?? null,
                ];
                $active = array_key_exists('is_active', $jarData) ? (bool)filter_var($jarData['is_active'], FILTER_VALIDATE_BOOLEAN) : true;
                if (!empty($jarData['id'])) {
                    $jar = \App\Models\Entities\Jar::where('id',$jarData['id'])->where('user_id',$userId)->first();
                    if (!$jar) { DB::rollBack(); return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found or not owned')], 404); }
                    // Only set active if provided
                    if (array_key_exists('is_active', $jarData)) { $data['active'] = $active; }
                    $jar->update($data);
                } else {
                    $data['user_id'] = $userId;
                    $data['active'] = $active;
                    $jar = \App\Models\Entities\Jar::create($data);
                }
                $result[] = $jar->fresh();
            }
            DB::commit();
            return response()->json(['status'=>'OK','code'=>200,'data'=>$result], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status'=>'FAILED','code'=>500,'message'=>$e->getMessage()], 500);
        }
    }
    // GET /users/:userId/jars
    public function listJars(Request $request, int $userId)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $query = \App\Models\Entities\Jar::with([
                'categories' => function ($q) { $q->wherePivotNull('deleted_at'); },
                'baseCategories' => function ($q) { $q->wherePivotNull('deleted_at'); },
            ])
            ->where('user_id', $userId)
            ->orderByDesc('active')
            ->orderBy('sort_order')
            ->orderBy('id');
        if ($per = (int) $request->query('per_page')) {
            return response()->json(['status'=>'OK','code'=>200,'data'=>$query->paginate($per)], 200);
        }
        return response()->json(['status'=>'OK','code'=>200,'data'=>$query->get()], 200);
    }

    // GET /users/:userId/jars/:jarId/items?from=...&to=...
    public function jarItems(Request $request, int $userId, int $jarId)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $from = $request->query('from');
        $to = $request->query('to');
        $q = \App\Models\Entities\ItemTransaction::with(['jar:id,name','category:id,name'])
            ->where('user_id', $userId)
            ->where('jar_id', $jarId);
        if (!empty($from)) { $q->whereDate('date', '>=', $from); }
        if (!empty($to)) { $q->whereDate('date', '<=', $to); }
        $items = $q->orderBy('date','desc')->get();
        return response()->json(['status'=>'OK','code'=>200,'data'=>$items], 200);
    }

    // GET /users/:userId/jars/summary?from=...&to=...
    public function summary(Request $request, int $userId)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $from = $request->query('from');
        $to = $request->query('to');
        $q = DB::table('item_transactions as it')
            ->leftJoin('jars as j', 'j.id', '=', 'it.jar_id')
            ->leftJoin('categories as c', 'c.id', '=', 'it.category_id')
            ->where('it.user_id', $userId)
            ->whereNotNull('it.jar_id');
        if (!empty($from)) { $q->whereDate('it.date', '>=', $from); }
        if (!empty($to)) { $q->whereDate('it.date', '<=', $to); }
        $rows = $q->groupBy('it.jar_id','it.category_id','j.name','c.name')
            ->selectRaw('it.jar_id, it.category_id, COALESCE(j.name, "") as jar_name, COALESCE(c.name, "") as category_name, SUM(it.amount) as total_amount, COUNT(*) as tx_count')
            ->orderBy('j.name')
            ->orderBy('c.name')
            ->get();
        // Group by jar
        $grouped = [];
        foreach ($rows as $r) {
            $jid = (int) $r->jar_id;
            if (!isset($grouped[$jid])) {
                $grouped[$jid] = [
                    'jar_id' => $jid,
                    'jar_name' => $r->jar_name,
                    'total_amount' => 0.0,
                    'by_category' => [],
                ];
            }
            $grouped[$jid]['total_amount'] += (float) $r->total_amount;
            $grouped[$jid]['by_category'][] = [
                'category_id' => $r->category_id,
                'category_name' => $r->category_name,
                'total' => (float) $r->total_amount,
            ];
        }
        return response()->json(['status'=>'OK','code'=>200,'data'=>array_values($grouped)], 200);
    }

    // GET /users/:userId/categories/unassigned
    public function unassignedCategories(Request $request, int $userId)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $rows = DB::table('categories as c')
            ->leftJoin('jar_category as jc', function ($join) {
                $join->on('jc.category_id', '=', 'c.id')
                    ->whereNull('jc.deleted_at');
            })
            ->leftJoin('jars as j', 'j.id', '=', 'jc.jar_id')
            ->where(function($w) use ($userId) {
                $w->where('c.user_id', $userId);
            })
            ->whereNull('j.id')
            ->orderBy('c.name')
            ->get(['c.id','c.name','c.parent_id','c.type']);
        return response()->json(['status'=>'OK','code'=>200,'data'=>$rows], 200);
    }

    // PATCH /users/:userId/item-transactions/:id
    public function updateItemTransactionJar(Request $request, int $userId, int $id)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $validator = Validator::make($request->all(), [
            'jar_id' => 'nullable|exists:jars,id',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'errors'=>$validator->errors()], 400);
        }
        $it = \App\Models\Entities\ItemTransaction::where('id', $id)->where('user_id', $userId)->first();
        if (!$it) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Not found')], 404); }
        $data = [];
        if ($request->has('jar_id')) { $data['jar_id'] = $request->input('jar_id'); }
        if (empty($data['jar_id']) && $request->filled('category_id')) {
            $catId = (int) $request->input('category_id');
            $data['category_id'] = $catId;
            $jarId = \App\Models\Entities\Jar::where('user_id', $userId)
                ->where('active', 1)
                ->whereHas('categories', function($q) use ($catId) { $q->where('categories.id', $catId); })
                ->orderBy('sort_order')
                ->value('id');
            if ($jarId) { $data['jar_id'] = $jarId; }
        }
        $it->update($data);
        return response()->json(['status'=>'OK','code'=>200,'data'=>$it->fresh(['jar:id,name','category:id,name'])], 200);
    }

    // POST /users/:userId/jars
    public function createJar(Request $request, int $userId)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $rules = [
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed,percent',
            'fixed_amount' => 'nullable|numeric|min:0',
            'percent' => 'nullable|numeric|min:0|max:100',
            'base_scope' => 'nullable|in:all_income,categories',
            'color' => ['nullable','string','max:16','regex:/^#?[0-9A-Fa-f]{3,8}$/'],
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'exclusive' => 'nullable|boolean',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) { return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'errors'=>$v->errors()], 400); }
        // Conditional requirements
        if ($request->input('type') === 'percent' && !$request->filled('percent')) {
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Percent is required for percent jars')], 422);
        }
        if ($request->input('type') === 'fixed' && !$request->filled('fixed_amount')) {
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Fixed amount is required for fixed jars')], 422);
        }
        $payload = $request->only(['name','type','fixed_amount','percent','base_scope','color','sort_order']);
        $payload['user_id'] = $userId;
        $payload['active'] = $request->boolean('is_active', true);
        $payload['base_scope'] = $payload['base_scope'] ?? 'all_income';

         $exclusive = $request->boolean('exclusive', false);
        // Optionally enforce exclusivity before sum validation
        if (($payload['type'] ?? null) === 'percent' && ($payload['active'] ?? false) && $exclusive) {
            \App\Models\Entities\Jar::where('user_id', $userId)
                ->where('type','percent')
                ->where('active',1)
                ->update(['active'=>0]);
        }

        // Validate percent sum if type percent
        if (($payload['type'] ?? null) === 'percent') {
            $sum = \App\Models\Entities\Jar::where('user_id', $userId)->where('type','percent')->where('active',1)->sum('percent');
            $newTotal = round($sum + (float)($payload['percent'] ?? 0), 2);
            if ($newTotal > 100.0 + 1e-6) {
                return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Total percent for user exceeds 100%'), 'data'=>['current_percent_total'=>$sum,'attempt_total'=>$newTotal]], 422);
            }
        }
        $jar = \App\Models\Entities\Jar::create($payload);
        return response()->json(['status'=>'OK','code'=>200,'data'=>$jar], 200);
    }

    // PUT /users/:userId/jars/:id
    public function updateJar(Request $request, int $userId, int $id)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $jar = \App\Models\Entities\Jar::find($id);
        if (!$jar || (int)$jar->user_id !== (int)$userId) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found')], 404); }
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|in:fixed,percent',
            'fixed_amount' => 'nullable|numeric|min:0',
            'percent' => 'nullable|numeric|min:0|max:100',
            'base_scope' => 'nullable|in:all_income,categories',
            'color' => ['nullable','string','max:16','regex:/^#?[0-9A-Fa-f]{3,8}$/'],
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        if ($v->fails()) { return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'errors'=>$v->errors()], 400); }
        if ($request->input('type') === 'percent' && !$request->filled('percent')) {
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Percent is required for percent jars')], 422);
        }
        if ($request->input('type') === 'fixed' && !$request->filled('fixed_amount')) {
            return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Fixed amount is required for fixed jars')], 422);
        }
        $data = $request->only(['name','type','fixed_amount','percent','base_scope','color','sort_order']);
        if ($request->exists('is_active')) { $data['active'] = $request->boolean('is_active'); }
        if (($data['type'] ?? $jar->type) === 'percent' && $request->has('percent')) {
        // $newType = $request->input('type', $jar->type);
        // $willBeActive = $request->exists('is_active') ? $request->boolean('is_active') : (bool)$jar->active;
        // if ($newType === 'percent' && $request->has('percent') && $willBeActive) {
            $sum = \App\Models\Entities\Jar::where('user_id', $userId)->where('type','percent')->where('active',1)->where('id','!=',$jar->id)->sum('percent');
            $newTotal = round($sum + (float)$request->input('percent'), 2);
            if ($newTotal > 100.0 + 1e-6) {
                return response()->json(['status'=>'FAILED','code'=>422,'message'=>__('Total percent for user exceeds 100%'), 'data'=>['current_percent_total'=>$sum,'attempt_total'=>$newTotal]], 422);
            }
        }
        $jar->update($data);
        return response()->json(['status'=>'OK','code'=>200,'data'=>$jar], 200);
    }

    // DELETE /users/:userId/jars/:id
    public function deleteJar(Request $request, int $userId, int $id)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $jar = \App\Models\Entities\Jar::find($id);
        if (!$jar || (int)$jar->user_id !== (int)$userId) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found')], 404); }
        $jar->active = 0; $jar->save(); $jar->delete();
        return response()->json(['status'=>'OK','code'=>200], 200);
    }

    // PUT/POST /users/:userId/jars/:id/categories
    public function replaceJarCategories(Request $request, int $userId, int $id)
    {
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$auth->id !== (int)$userId) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $jar = \App\Models\Entities\Jar::find($id);
        if (!$jar || (int)$jar->user_id !== (int)$userId) { return response()->json(['status'=>'FAILED','code'=>404,'message'=>__('Jar not found')], 404); }
        $v = Validator::make($request->all(), [
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
        ]);
        if ($v->fails()) { return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'errors'=>$v->errors()], 400); }
        $targetIds = collect($request->input('category_ids', []))->unique()->values()->all();
        // if ($request->missing('category_ids')) {
        //     $jar->load(['categories'=>fn($q)=>$q->wherePivotNull('deleted_at')]);
        //     return response()->json(['status'=>'OK','code'=>200,'data'=>$jar], 200);
        // }
        $currentIds = $jar->categories()->pluck('categories.id')->all();
        $toAttach = array_values(array_diff($targetIds, $currentIds));
        $toDetach = array_values(array_diff($currentIds, $targetIds));
        // Exclusivity: one jar per user per category
        if (!empty($toAttach)) {
            $conflicts = DB::table('jar_category as jc')
                ->join('jars as j','j.id','=','jc.jar_id')
                ->whereNull('jc.deleted_at')
                ->where('j.user_id', $userId)
                ->whereIn('jc.category_id', $toAttach)
                ->where('jc.jar_id','!=',$jar->id)
                ->pluck('jc.category_id')->unique()->all();
            if (!empty($conflicts)) {
                \App\Models\Entities\Pivots\JarCategory::whereIn('category_id', $conflicts)
                    ->whereNull('deleted_at')
                    ->whereIn('jar_id', function($q) use ($userId) { $q->select('id')->from('jars')->where('user_id', $userId); })
                    ->update(['active'=>0,'deleted_at'=>now()]);
            }
        }
        foreach ($toAttach as $catId) {
            $pivot = \App\Models\Entities\Pivots\JarCategory::withTrashed()->where('jar_id',$jar->id)->where('category_id',$catId)->first();
            if ($pivot) { $pivot->active = 1; $pivot->deleted_at = null; $pivot->save(); }
            else { $jar->categories()->attach($catId, ['active'=>1]); }
        }
        if (!empty($toDetach)) {
            \App\Models\Entities\Pivots\JarCategory::where('jar_id',$jar->id)->whereIn('category_id',$toDetach)->whereNull('deleted_at')->update(['active'=>0,'deleted_at'=>now()]);
        }
        $jar->load(['categories'=>fn($q)=>$q->wherePivotNull('deleted_at')]);
        return response()->json(['status'=>'OK','code'=>200,'data'=>$jar], 200);
    }
}

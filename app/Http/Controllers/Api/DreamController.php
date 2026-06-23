<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Dream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DreamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $allowedSorts = ['priority', 'created_at', 'progress', 'target_amount', 'saved_amount', 'name'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts) ? $request->query('sort_by') : 'priority';
        $desc   = filter_var($request->query('descending', 'false'), FILTER_VALIDATE_BOOLEAN);

        $query = Dream::where('user_id', $user->id);

        if ($sortBy === 'progress') {
            // progress is computed; sort by saved_amount/target_amount ratio via raw
            $query->orderByRaw('CASE WHEN target_amount > 0 THEN (saved_amount / target_amount) ELSE 0 END ' . ($desc ? 'DESC' : 'ASC'));
        } else {
            $query->orderBy($sortBy, $desc ? 'desc' : 'asc');
        }
        $query->orderBy('created_at');

        // Meta always computed over ALL dreams (not limited by per_page)
        $all = Dream::where('user_id', $user->id)->get();
        $totalSaved  = $all->sum('saved_amount');
        $totalTarget = $all->sum('target_amount');
        $globalProgress = $totalTarget > 0
            ? round(($totalSaved / $totalTarget) * 100, 1)
            : 0;

        $perPage = (int) $request->query('per_page', 0);
        $dreams  = $perPage > 0 ? $query->limit($perPage)->get() : $query->get();

        return response()->json([
            'status' => 'OK',
            'data'   => $dreams,
            'meta'   => [
                'total_saved'       => $totalSaved,
                'total_target'      => $totalTarget,
                'global_progress'   => $globalProgress,
                'count'             => $all->count(),
                'completed_count'   => $all->where('is_completed', true)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:100',
            'emoji'         => 'nullable|string|max:8',
            'description'   => 'nullable|string|max:500',
            'target_amount' => 'required|numeric|min:1',
            'saved_amount'  => 'nullable|numeric|min:0',
            'color'         => 'nullable|string|max:32',
            'priority'      => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => $validator->errors()->first()], 422);
        }

        $dream = Dream::create([
            'user_id'       => $user->id,
            'name'          => $request->name,
            'emoji'         => $request->emoji,
            'description'   => $request->description,
            'target_amount' => $request->target_amount,
            'saved_amount'  => $request->saved_amount ?? 0,
            'color'         => $request->color,
            'priority'      => $request->priority ?? 0,
        ]);

        return response()->json(['status' => 'OK', 'data' => $dream], 201);
    }

    public function show(Request $request, int $id)
    {
        $dream = Dream::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['status' => 'OK', 'data' => $dream]);
    }

    public function update(Request $request, int $id)
    {
        $dream = Dream::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|string|max:100',
            'emoji'         => 'nullable|string|max:8',
            'description'   => 'nullable|string|max:500',
            'target_amount' => 'sometimes|numeric|min:1',
            'saved_amount'  => 'sometimes|numeric|min:0',
            'color'         => 'nullable|string|max:32',
            'priority'      => 'nullable|integer|min:0',
            'is_completed'  => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => $validator->errors()->first()], 422);
        }

        $data = $request->only(['name', 'emoji', 'description', 'target_amount', 'saved_amount', 'color', 'priority', 'is_completed']);

        if (isset($data['is_completed']) && $data['is_completed'] && !$dream->is_completed) {
            $data['completed_at'] = now();
        } elseif (isset($data['is_completed']) && !$data['is_completed']) {
            $data['completed_at'] = null;
        }

        $dream->update($data);

        return response()->json(['status' => 'OK', 'data' => $dream->fresh()]);
    }

    public function deposit(Request $request, int $id)
    {
        $dream = Dream::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => $validator->errors()->first()], 422);
        }

        $dream->saved_amount = $dream->saved_amount + $request->amount;

        if ($dream->saved_amount >= $dream->target_amount && !$dream->is_completed) {
            $dream->is_completed = true;
            $dream->completed_at = now();
        }

        $dream->save();

        return response()->json(['status' => 'OK', 'data' => $dream->fresh()]);
    }

    public function destroy(Request $request, int $id)
    {
        $dream = Dream::where('user_id', $request->user()->id)->findOrFail($id);
        $dream->delete();
        return response()->json(['status' => 'OK', 'message' => 'Sueño eliminado']);
    }
}

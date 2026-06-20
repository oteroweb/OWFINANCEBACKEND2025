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
        $dreams = Dream::where('user_id', $user->id)
            ->orderBy('priority')
            ->orderBy('created_at')
            ->get();

        $totalSaved  = $dreams->sum('saved_amount');
        $totalTarget = $dreams->sum('target_amount');
        $globalProgress = $totalTarget > 0
            ? round(($totalSaved / $totalTarget) * 100, 1)
            : 0;

        return response()->json([
            'status' => 'OK',
            'data'   => $dreams,
            'meta'   => [
                'total_saved'       => $totalSaved,
                'total_target'      => $totalTarget,
                'global_progress'   => $globalProgress,
                'count'             => $dreams->count(),
                'completed_count'   => $dreams->where('is_completed', true)->count(),
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

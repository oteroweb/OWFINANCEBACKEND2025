<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Jar;
use App\Models\Entities\JarMonthlyOverride;
use App\Services\JarBalanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JarMonthlyOverrideController extends Controller
{
    private JarBalanceService $balanceService;

    public function __construct(JarBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * GET /api/v1/jars/overrides/month?month=2026-03-01
     * Get all monthly overrides for the authenticated user for a given month.
     * Also returns the allocation validation summary.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->user()->id;
        $month = $request->get('month', Carbon::now()->startOfMonth()->toDateString());
        $date = Carbon::parse($month);

        $overrides = JarMonthlyOverride::getAllForUserMonth($userId, $date->startOfMonth()->toDateString());
        $validation = $this->balanceService->validateMonthlyAllocation($userId, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => [
                'overrides' => $overrides,
                'allocation_summary' => $validation,
            ],
        ]);
    }

    /**
     * PUT /api/v1/jars/{jarId}/override
     * Set or update the monthly override for a specific jar.
     *
     * Body: { month: "2026-03-01", percent?: 15, fixed_amount?: 200, notes?: "..." }
     */
    public function upsert(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'month' => 'required|date',
            'percent' => 'nullable|numeric|min:0|max:100',
            'fixed_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $monthDate = Carbon::parse($request->get('month'))->startOfMonth()->toDateString();

        // Determine which field to set based on jar type
        $percent = null;
        $fixedAmount = null;

        if ($jar->type === 'percent') {
            $percent = $request->has('percent') ? (float) $request->get('percent') : null;
        } else {
            $fixedAmount = $request->has('fixed_amount') ? (float) $request->get('fixed_amount') : null;
        }

        // Save the override
        $override = JarMonthlyOverride::saveForMonth(
            $jar->id,
            $userId,
            $monthDate,
            $percent,
            $fixedAmount,
            $request->get('notes')
        );

        // Validate total allocation after save
        $date = Carbon::parse($monthDate);
        $validation = $this->balanceService->validateMonthlyAllocation($userId, $date);

        // Invalidate cycle snapshots for this jar from this month forward
        $this->balanceService->invalidateCycleSnapshotsFrom($jar, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Override saved',
            'data' => [
                'override' => $override,
                'allocation_summary' => $validation,
            ],
        ]);
    }

    /**
     * DELETE /api/v1/jars/{jarId}/override?month=2026-03-01
     * Remove the monthly override for a specific jar, reverting to default.
     */
    public function destroy(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $month = $request->get('month');
        if (!$month) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => 'month parameter is required',
            ], 422);
        }

        $monthDate = Carbon::parse($month)->startOfMonth()->toDateString();
        $deleted = JarMonthlyOverride::removeForMonth($jar->id, $monthDate);

        // Invalidate cycle snapshots
        $date = Carbon::parse($monthDate);
        $this->balanceService->invalidateCycleSnapshotsFrom($jar, $date);

        $validation = $this->balanceService->validateMonthlyAllocation($userId, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => $deleted > 0 ? 'Override removed' : 'No override found for this month',
            'data' => [
                'deleted' => $deleted,
                'allocation_summary' => $validation,
            ],
        ]);
    }

    /**
     * PUT /api/v1/jars/overrides/bulk
     * Bulk set overrides for multiple jars at once for a specific month.
     * Validates total percent <= 100 before saving.
     *
     * Body: {
     *   month: "2026-03-01",
     *   overrides: [
     *     { jar_id: 8, percent: 15 },
     *     { jar_id: 12, fixed_amount: 200 },
     *     ...
     *   ]
     * }
     */
    public function bulkUpsert(Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'month' => 'required|date',
            'overrides' => 'required|array|min:1',
            'overrides.*.jar_id' => 'required|integer|exists:jars,id',
            'overrides.*.percent' => 'nullable|numeric|min:0|max:100',
            'overrides.*.fixed_amount' => 'nullable|numeric|min:0',
            'overrides.*.notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $monthDate = Carbon::parse($request->get('month'))->startOfMonth()->toDateString();

        // Validate all jars belong to user
        $jarIds = collect($request->get('overrides'))->pluck('jar_id')->toArray();
        $userJars = Jar::where('user_id', $userId)->whereIn('id', $jarIds)->pluck('id')->toArray();

        if (count($jarIds) !== count($userJars)) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 403,
                'message' => 'Some jar IDs do not belong to the authenticated user',
            ], 403);
        }

        // Pre-validate: simulate the total percent
        $allJars = Jar::where('user_id', $userId)->where('active', 1)->whereNull('deleted_at')->get();
        $overrideMap = collect($request->get('overrides'))->keyBy('jar_id');
        $totalPercent = 0;

        foreach ($allJars as $jar) {
            if ($jar->type === 'percent') {
                $ov = $overrideMap->get($jar->id);
                $pct = ($ov && isset($ov['percent'])) ? (float) $ov['percent'] : (float) $jar->percent;
                $totalPercent += $pct;
            }
        }

        if ($totalPercent > 100) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => "Total percent ({$totalPercent}%) exceeds 100%. Adjust the values before saving.",
                'data' => ['total_percent' => $totalPercent],
            ], 422);
        }

        // Save all overrides
        $saved = [];
        $date = Carbon::parse($monthDate);

        foreach ($request->get('overrides') as $item) {
            $jar = $allJars->firstWhere('id', $item['jar_id']);
            if (!$jar) continue;

            $percent = ($jar->type === 'percent' && isset($item['percent'])) ? (float) $item['percent'] : null;
            $fixedAmount = ($jar->type === 'fixed' && isset($item['fixed_amount'])) ? (float) $item['fixed_amount'] : null;

            $override = JarMonthlyOverride::saveForMonth(
                $jar->id,
                $userId,
                $monthDate,
                $percent,
                $fixedAmount,
                $item['notes'] ?? null
            );

            // Invalidate cycle snapshots
            $this->balanceService->invalidateCycleSnapshotsFrom($jar, $date);

            $saved[] = $override;
        }

        $validation = $this->balanceService->validateMonthlyAllocation($userId, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => count($saved) . ' overrides saved',
            'data' => [
                'overrides' => $saved,
                'allocation_summary' => $validation,
            ],
        ]);
    }

    /**
     * GET /api/v1/jars/overrides/validate?month=2026-03-01
     * Validate the allocation for a given month without making changes.
     */
    public function validate(Request $request): JsonResponse
    {
        $userId = auth()->user()->id;
        $month = $request->get('month', Carbon::now()->startOfMonth()->toDateString());
        $date = Carbon::parse($month);

        $validation = $this->balanceService->validateMonthlyAllocation($userId, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => $validation,
        ]);
    }
}

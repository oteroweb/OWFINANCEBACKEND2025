<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Jar;
use App\Services\JarBalanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JarBalanceController extends Controller
{
    private JarBalanceService $balanceService;

    public function __construct(JarBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * GET /api/v1/jars/{jarId}/balance
     * Get the current balance for a jar
     */
    public function getBalance(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $date = null;
        if ($request->has('date')) {
            $date = Carbon::parse($request->get('date'));
        }

        $balance = $this->balanceService->getDetailedBalance($jar, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => $balance,
        ]);
    }

    /**
     * POST /api/v1/jars/{jarId}/adjust
     * Make a manual adjustment to jar balance
     *
     * Request body:
     * {
     *   "target_balance": 200.00,   // the desired available balance (can be negative)
     *   "reason": "Sync from old system",
     *   "date": "2025-01-15"        // optional, defaults to today
     * }
     *
     * The adjustment amount is calculated as: target_balance - current_available
     * Example: if current is 420 and target is 200, adjustment = -220
     */
    public function adjustBalance(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'target_balance' => 'required|numeric',
            'reason' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        $date = isset($validated['date']) && $validated['date'] ? Carbon::parse($validated['date']) : null;

        $adjustment = $this->balanceService->adjustToTargetBalance(
            $jar,
            (float) $validated['target_balance'],
            $validated['reason'] ?? null,
            $date,
            $userId
        );

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Balance adjusted successfully',
            'data' => [
                'jar_id' => $jar->id,
                'jar_name' => $jar->name,
                'adjustment' => [
                    'id' => $adjustment->id,
                    'amount' => $adjustment->amount,
                    'type' => $adjustment->type,
                    'reason' => $adjustment->reason,
                    'previous_available' => $adjustment->previous_available,
                    'new_available' => $adjustment->new_available,
                    'date' => $adjustment->adjustment_date->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/jars/{jarId}/adjustments
     * Get adjustment history for a jar
     */
    public function getAdjustmentHistory(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $from = $request->get('from') ? Carbon::parse($request->get('from')) : null;
        $to = $request->get('to') ? Carbon::parse($request->get('to')) : null;

        $adjustments = $this->balanceService->getAdjustmentHistory($jar, $from, $to);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => $adjustments->map(fn($adj) => [
                'id' => $adj->id,
                'amount' => $adj->amount,
                'type' => $adj->type,
                'reason' => $adj->reason,
                'previous_available' => $adj->previous_available,
                'new_available' => $adj->new_available,
                'date' => $adj->adjustment_date->toDateString(),
                'created_at' => $adj->created_at->toDateTimeString(),
                'adjusted_by' => $adj->user?->name,
            ]),
        ]);
    }

    /**
     * POST /api/v1/jars/{jarId}/reset-adjustment
     * Reset adjustment for next period (for reset mode jars)
     * Clears adjustments from jar_adjustments table for the current month.
     */
    public function resetAdjustmentForNextPeriod(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $date = $request->get('date') ? Carbon::parse($request->get('date')) : null;

        $this->balanceService->resetAdjustmentForNewPeriod($jar, $date);

        // Recalculate balance after clearing adjustments
        $newBalance = $this->balanceService->getAvailableBalance($jar, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Adjustment reset for next period',
            'data' => [
                'jar_id' => $jar->id,
                'available_balance' => $newBalance,
                'refresh_mode' => $jar->refresh_mode,
            ],
        ]);
    }

    /**
     * DELETE /api/v1/jars/{jarId}/adjustments
     * Clear all adjustments for a specific month
     *
     * Query params:
     * - date (optional): YYYY-MM-DD (defaults to today)
     */
    public function clearAdjustments(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $date = $request->get('date') ? Carbon::parse($request->get('date')) : Carbon::now();

        $deleted = $this->balanceService->clearAdjustmentsForMonth($jar, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Adjustments cleared successfully',
            'data' => [
                'jar_id' => $jar->id,
                'deleted' => $deleted,
                'month' => $date->format('Y-m'),
            ],
        ]);
    }

    /**
     * POST /api/v1/jars/{jarId}/leverage
     * Try auto leverage and return reason
     */
    public function leverage(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $date = $request->get('date') ? Carbon::parse($request->get('date')) : null;

        $result = $this->balanceService->tryAutoLeverage($jar, $date);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => $result,
        ]);
    }
}

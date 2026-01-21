<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Jar;
use App\Services\JarBalanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JarWithdrawalController extends Controller
{
    private JarBalanceService $balanceService;

    public function __construct(JarBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * GET /api/v1/jars/{jarId}/withdrawals
     * Get withdrawal history for a jar
     */
    public function index(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $from = $request->get('from') ? Carbon::parse($request->get('from')) : null;
        $to = $request->get('to') ? Carbon::parse($request->get('to')) : null;

        $withdrawals = $this->balanceService->getWithdrawalHistory($jar, $from, $to);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => $withdrawals->map(fn($w) => [
                'id' => $w->id,
                'amount' => $w->amount,
                'description' => $w->description,
                'previous_available' => $w->previous_available,
                'new_available' => $w->new_available,
                'date' => $w->withdrawal_date->toDateString(),
                'created_at' => $w->created_at->toDateTimeString(),
                'withdrawn_by' => $w->user?->name,
            ]),
        ]);
    }

    /**
     * POST /api/v1/jars/{jarId}/withdraw
     * Register a withdrawal from a jar
     *
     * Request body:
     * {
     *   "amount": 200.00,
     *   "description": "Compra de nevera",
     *   "date": "2026-01-19"
     * }
     */
    public function store(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        $date = isset($validated['date']) && $validated['date'] ? Carbon::parse($validated['date']) : null;

        $withdrawal = $this->balanceService->withdrawFromJar(
            $jar,
            (float) $validated['amount'],
            $validated['description'] ?? null,
            $date,
            $userId
        );

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Withdrawal registered successfully',
            'data' => [
                'jar_id' => $jar->id,
                'jar_name' => $jar->name,
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'description' => $withdrawal->description,
                    'previous_available' => $withdrawal->previous_available,
                    'new_available' => $withdrawal->new_available,
                    'date' => $withdrawal->withdrawal_date->toDateString(),
                ],
            ],
        ]);
    }
}

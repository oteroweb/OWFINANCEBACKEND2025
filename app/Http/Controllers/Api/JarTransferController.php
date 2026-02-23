<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Jar;
use App\Services\JarBalanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JarTransferController extends Controller
{
    private JarBalanceService $balanceService;

    public function __construct(JarBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * GET /api/v1/jars/{jarId}/transfers
     * Get transfer history for a jar
     */
    public function index(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $jar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $from = $request->get('from') ? Carbon::parse($request->get('from')) : null;
        $to = $request->get('to') ? Carbon::parse($request->get('to')) : null;

        $transfers = $this->balanceService->getTransferHistory($jar, $from, $to);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => $transfers->map(fn($t) => [
                'id' => $t->id,
                'amount' => $t->amount,
                'description' => $t->description,
                'from_jar_id' => $t->from_jar_id,
                'to_jar_id' => $t->to_jar_id,
                'from_previous_available' => $t->from_previous_available,
                'from_new_available' => $t->from_new_available,
                'to_previous_available' => $t->to_previous_available,
                'to_new_available' => $t->to_new_available,
                'date' => $t->transfer_date->toDateString(),
                'created_at' => $t->created_at->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * POST /api/v1/jars/{jarId}/transfer
     * Transfer balance from another jar into this jar
     *
     * Request body:
     * {
     *   "from_jar_id": 2,
     *   "amount": 50.00,
     *   "description": "Cobertura de exceso",
     *   "date": "2026-01-20"
     * }
     */
    public function store(int $jarId, Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $toJar = Jar::where('id', $jarId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'from_jar_id' => 'required|integer|exists:jars,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'date' => 'nullable|date',
        ]);

        $fromJar = Jar::where('id', $validated['from_jar_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($fromJar->id === $toJar->id) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => 'Source and target jars must be different',
            ], 422);
        }

        $date = isset($validated['date']) && $validated['date'] ? Carbon::parse($validated['date']) : null;

        $transfer = $this->balanceService->transferBetweenJars(
            $fromJar,
            $toJar,
            (float) $validated['amount'],
            $validated['description'] ?? null,
            $date,
            $userId
        );

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Transfer registered successfully',
            'data' => [
                'transfer' => [
                    'id' => $transfer->id,
                    'amount' => $transfer->amount,
                    'description' => $transfer->description,
                    'from_jar_id' => $transfer->from_jar_id,
                    'to_jar_id' => $transfer->to_jar_id,
                    'from_previous_available' => $transfer->from_previous_available,
                    'from_new_available' => $transfer->from_new_available,
                    'to_previous_available' => $transfer->to_previous_available,
                    'to_new_available' => $transfer->to_new_available,
                    'date' => $transfer->transfer_date->toDateString(),
                ],
            ],
        ]);
    }
}

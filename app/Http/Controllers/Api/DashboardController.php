<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    /**
     * Administrative dashboard summary (requires admin role).
     */
    public function adminSummary(): JsonResponse
    {
        return response()->json($this->dashboardService->getAdminSummary());
    }

    /**
     * Dashboard summary for the authenticated user.
     */
    public function userSummary(Request $request): JsonResponse
    {
        $filters = array_filter([
            'from' => $request->query('from') ?? $request->query('date_from'),
            'to' => $request->query('to') ?? $request->query('date_to'),
        ]);

        return response()->json(
            $this->dashboardService->getUserSummary($request->user(), $filters)
        );
    }
}

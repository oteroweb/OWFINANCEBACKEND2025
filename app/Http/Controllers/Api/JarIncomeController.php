<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\Category;
use App\Models\Entities\UserMonthlyIncomeHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JarIncomeController extends Controller
{
    /**
     * @group Jar Income
     * Get income summary for the current month
     *
     * Returns expected income vs calculated income
     *
     * @queryParam month string Optional. Month in format YYYY-MM. Example: 2025-01
     * @queryParam year integer Optional. Year. Example: 2025
     * @queryParam date string Optional. Date in format YYYY-MM-DD. Example: 2025-01-15
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "expected_income": 5000.00,
     *     "calculated_income": 4200.00,
     *     "difference": -800.00,
     *     "difference_percentage": -16.00,
     *     "month": "2025-01",
     *     "breakdown": {
     *       "by_category": [
     *         {
     *           "category_id": 1,
     *           "category_name": "Salario",
     *           "amount": 4000.00
     *         }
     *       ]
     *     }
     *   }
     * }
     */
    public function getIncomeSummary(Request $request)
    {
        try {
            $user = $request->user();

            // Determinar el mes a calcular
            $date = $this->getDateFromRequest($request);
            $startOfMonth = $date->clone()->startOfMonth();
            $endOfMonth = $date->clone()->endOfMonth();

            // Ingreso esperado (configurado manualmente por el usuario)
            // Try to get historical monthly_income for the specified month
            $expectedIncome = $this->getMonthlyIncomeForMonth($user->id, $date);

            // Ingreso calculado (transacciones reales del mes)
            // Suma todas las transacciones de tipo 'income' del mes
            $calculatedIncome = (float) ItemTransaction::where('item_transactions.user_id', $user->id)
                ->whereHas('transaction', function($query) {
                    $query->whereHas('transactionType', function($q) {
                        $q->where('slug', 'income');
                    });
                })
                ->whereBetween('item_transactions.date', [$startOfMonth, $endOfMonth])
                ->sum('item_transactions.amount');

            // Diferencia
            $difference = $calculatedIncome - $expectedIncome;
            $differencePercentage = $expectedIncome > 0
                ? round(($difference / $expectedIncome) * 100, 2)
                : 0;

            // Breakdown por categoría (opcional pero muy útil)
            // FIX: Especificar tabla explícitamente para evitar ambigüedad en JOIN
            $breakdown = ItemTransaction::where('item_transactions.user_id', $user->id)
                ->whereHas('transaction', function($query) {
                    $query->whereHas('transactionType', function($q) {
                        $q->where('slug', 'income');
                    });
                })
                ->whereBetween('item_transactions.date', [$startOfMonth, $endOfMonth])
                ->join('categories', 'item_transactions.category_id', '=', 'categories.id')
                ->selectRaw('
                    categories.id as category_id,
                    categories.name as category_name,
                    SUM(item_transactions.amount) as amount
                ')
                ->groupBy('categories.id', 'categories.name')
                ->get();

            $response = [
                'success' => true,
                'data' => [
                    'expected_income' => $expectedIncome,
                    'calculated_income' => $calculatedIncome,
                    'difference' => $difference,
                    'difference_percentage' => $differencePercentage,
                    'month' => $date->format('Y-m'),
                    'breakdown' => [
                        'by_category' => $breakdown
                    ]
                ]
            ];

            return response()->json($response, 200);

        } catch (\Exception $ex) {
            Log::error('Error in getIncomeSummary: ' . $ex->getMessage());
            Log::error($ex->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while calculating income summary'),
                'error' => app()->environment('local') ? $ex->getMessage() : null
            ], 500);
        }
    }

    /**
     * Parse date from request parameters
     * Priority: date > month+year > month > current date
     */
    private function getDateFromRequest(Request $request): Carbon
    {
        if ($request->has('date')) {
            return Carbon::parse($request->input('date'));
        }

        if ($request->has('month') && $request->has('year')) {
            return Carbon::create(
                $request->input('year'),
                $request->input('month'),
                1
            );
        }

        if ($request->has('month')) {
            return Carbon::parse($request->input('month') . '-01');
        }

        return Carbon::now();
    }

    /**
     * Get monthly income for a specific month
     * Tries to get historical value first, falls back to current monthly_income
     */
    private function getMonthlyIncomeForMonth(int $userId, Carbon $date): float
    {
        $firstDayOfMonth = $date->clone()->startOfMonth()->toDateString();
        
        \Log::info('[JarIncomeController] Getting monthly income', [
            'user_id' => $userId,
            'requested_date' => $date->toDateString(),
            'first_day_of_month' => $firstDayOfMonth,
        ]);
        
        // Try to get historical record for this specific month
        $historicalIncome = UserMonthlyIncomeHistory::getForMonth($userId, $firstDayOfMonth);
        
        if ($historicalIncome !== null) {
            \Log::info('[JarIncomeController] Found historical income', [
                'income' => $historicalIncome,
                'source' => 'exact_match'
            ]);
            return $historicalIncome;
        }
        
        // If no historical record, try to get the most recent one before this month
        $recentIncome = UserMonthlyIncomeHistory::getMostRecentBeforeMonth($userId, $firstDayOfMonth);
        
        if ($recentIncome !== null) {
            \Log::info('[JarIncomeController] Found recent income before month', [
                'income' => $recentIncome,
                'source' => 'most_recent_before'
            ]);
            return $recentIncome;
        }
        
        // Fallback to current monthly_income
        $user = \App\Models\User::find($userId);
        $fallbackIncome = (float) ($user->monthly_income ?? 0);
        
        \Log::info('[JarIncomeController] Using fallback income', [
            'income' => $fallbackIncome,
            'source' => 'user_current'
        ]);
        
        return $fallbackIncome;
    }
}

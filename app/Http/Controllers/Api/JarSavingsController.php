<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Account;
use App\Models\Entities\Jar;
use App\Services\JarBalanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JarSavingsController extends Controller
{
    private JarBalanceService $balanceService;

    public function __construct(JarBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * GET /api/v1/jars/theoretical-savings
     * Returns theoretical savings for the selected month:
     * - unused from jars (allocated - spent - withdrawals)
     * - balances of savings accounts
     */
    public function getSummary(Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $date = $request->get('date')
            ? Carbon::parse($request->get('date'))
            : Carbon::now();

        $jars = Jar::where('user_id', $userId)
            ->where('active', 1)
            ->get();

        $jarRows = [];
        $totalUnused = 0.0;

        foreach ($jars as $jar) {
            $usage = $this->balanceService->getMonthlyUsage($jar, $date);
            $unused = max(0, $usage['allocated'] - $usage['spent'] - $usage['withdrawals']);
            $jarRows[] = [
                'jar_id' => $jar->id,
                'jar_name' => $jar->name,
                'unused' => round($unused, 2),
            ];
            $totalUnused += $unused;
        }

        $savingsAccounts = Account::whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->whereHas('accountType', function ($q) {
                $q->where(function ($w) {
                    $w->whereRaw('LOWER(account_types.name) LIKE ?', ['%ahorro%'])
                        ->orWhereRaw('LOWER(account_types.name) LIKE ?', ['%interes%'])
                        ->orWhereIn('account_types.icon', ['percent', 'savings', 'piggy-bank']);
                });
            })
            ->with('accountType:id,name,icon')
            ->get(['id', 'name', 'balance', 'balance_cached', 'account_type_id']);

        $totalSavingsAccounts = 0.0;
        $savingsRows = $savingsAccounts->map(function ($acc) use (&$totalSavingsAccounts) {
            $bal = $acc->balance ?? $acc->balance_cached ?? 0;
            $totalSavingsAccounts += (float) $bal;
            return [
                'account_id' => $acc->id,
                'account_name' => $acc->name,
                'balance' => (float) $bal,
                'account_type' => $acc->accountType?->name,
            ];
        })->values();

        $totalTheoretical = $totalUnused + $totalSavingsAccounts;

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => [
                'month' => $date->format('Y-m'),
                'total_unused' => round($totalUnused, 2),
                'total_savings_accounts' => round($totalSavingsAccounts, 2),
                'total_theoretical' => round($totalTheoretical, 2),
                'jars' => $jarRows,
                'savings_accounts' => $savingsRows,
            ],
        ]);
    }
}

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
     * - unused from reset-mode jars only (accumulative jars carry over, not idle)
     * - balances of savings accounts
     *
     * Formula per reset jar:
     *   unused = max(0, allocated - spent + adjustment - withdrawals + transfers_in - transfers_out)
     */
    public function getSummary(Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $date = $request->get('date')
            ? Carbon::parse($request->get('date'))
            : Carbon::now();

        $jars = Jar::where('user_id', $userId)
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->get();

        $jarRows = [];
        $totalUnused = 0.0;

        foreach ($jars as $jar) {
            // Only count reset jars — accumulative jars carry over their balance
            if ($jar->refresh_mode === 'accumulative') {
                $jarRows[] = [
                    'jar_id' => $jar->id,
                    'jar_name' => $jar->name,
                    'refresh_mode' => 'accumulative',
                    'unused' => 0,
                    'note' => 'El sobrante se acumula al mes siguiente',
                ];
                continue;
            }

            $usage = $this->balanceService->getMonthlyUsage($jar, $date);
            $unused = max(0,
                $usage['allocated']
                - $usage['spent']
                + $usage['adjustment']
                - $usage['withdrawals']
                + $usage['transfers_in']
                - $usage['transfers_out']
            );
            $jarRows[] = [
                'jar_id' => $jar->id,
                'jar_name' => $jar->name,
                'refresh_mode' => 'reset',
                'allocated' => round($usage['allocated'], 2),
                'spent' => round($usage['spent'], 2),
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

    /**
     * GET /api/v1/jars/theoretical-savings/accumulated
     * Accumulates idle (unused) money from reset-mode jars across multiple months.
     *
     * Query params:
     *  - from: start month (YYYY-MM-DD or YYYY-MM), defaults to earliest jar creation
     *  - to: end month (inclusive), defaults to current month
     *
     * Returns per-month breakdown + grand total of money that was allocated
     * to reset jars but never spent/withdrawn/transferred.
     */
    public function getAccumulated(Request $request): JsonResponse
    {
        $userId = auth()->user()->id;

        $to = $request->get('to')
            ? Carbon::parse($request->get('to'))->startOfMonth()
            : Carbon::now()->startOfMonth();

        // Default "from": earliest active jar's created_at
        if ($request->get('from')) {
            $from = Carbon::parse($request->get('from'))->startOfMonth();
        } else {
            // Use global_start_date from jar_settings (user's accounting start)
            $settings = \App\Models\Entities\JarSetting::where('user_id', $userId)->first();
            if ($settings?->global_start_date) {
                $from = Carbon::parse($settings->global_start_date)->startOfMonth();
            } else {
                // Fallback: earliest active jar created_at
                $earliest = Jar::where('user_id', $userId)
                    ->where('active', 1)
                    ->whereNull('deleted_at')
                    ->min('created_at');
                $from = $earliest
                    ? Carbon::parse($earliest)->startOfMonth()
                    : $to->copy()->subMonths(11)->startOfMonth();
            }
        }

        // Cap at max 24 months to avoid runaway queries
        if ($from->diffInMonths($to) > 24) {
            $from = $to->copy()->subMonths(24)->startOfMonth();
        }

        $jars = Jar::where('user_id', $userId)
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->where('refresh_mode', 'reset') // only reset jars
            ->get();

        $months = [];
        $grandTotal = 0.0;
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $monthLabel = $cursor->format('Y-m');
            $monthUnused = 0.0;
            $jarDetails = [];

            foreach ($jars as $jar) {
                $usage = $this->balanceService->getMonthlyUsage($jar, $cursor);
                $unused = max(0,
                    $usage['allocated']
                    - $usage['spent']
                    + ($usage['adjustment'] ?? 0)
                    - $usage['withdrawals']
                    + $usage['transfers_in']
                    - $usage['transfers_out']
                );

                if ($unused > 0) {
                    $jarDetails[] = [
                        'jar_id' => $jar->id,
                        'jar_name' => $jar->name,
                        'allocated' => round($usage['allocated'], 2),
                        'spent' => round($usage['spent'], 2),
                        'unused' => round($unused, 2),
                    ];
                }
                $monthUnused += $unused;
            }

            $grandTotal += $monthUnused;
            $months[] = [
                'month' => $monthLabel,
                'unused' => round($monthUnused, 2),
                'accumulated' => round($grandTotal, 2),
                'jars' => $jarDetails,
            ];

            $cursor->addMonth();
        }

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => [
                'from' => $from->format('Y-m'),
                'to' => $to->format('Y-m'),
                'total_months' => count($months),
                'grand_total_unused' => round($grandTotal, 2),
                'months' => $months,
            ],
        ]);
    }
}

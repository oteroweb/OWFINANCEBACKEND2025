<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Entities\AiUserSetting;
use App\Models\Entities\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AiUserContextController extends Controller
{
    public function context(Request $request)
    {
        $user     = $request->user();
        $cacheKey = "ai_user_context_{$user->id}";

        $context = Cache::remember($cacheKey, 600, function () use ($user) {
            return $this->buildContext($user);
        });

        return response()->json($context);
    }

    private function buildContext($user): array
    {
        $now           = Carbon::now();
        $monthStart    = $now->copy()->startOfMonth();

        $context = [
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'currency' => optional($user->currency)->code ?? 'USD',
            ],
            'generated_at' => $now->toIso8601String(),
            'period'       => [
                'current_month' => $now->format('Y-m'),
                'month_start'   => $monthStart->toDateString(),
                'today'         => $now->toDateString(),
            ],
        ];

        // Accounts summary
        try {
            $accounts = $user->accounts()->with('accountType', 'currency')->get();

            $context['accounts'] = $accounts->map(fn($a) => [
                'id'       => $a->id,
                'name'     => $a->name,
                'type'     => optional($a->accountType)->name ?? 'unknown',
                'balance'  => (float) ($a->balance_cached ?? $a->balance ?? 0),
                'currency' => optional($a->currency)->code ?? 'USD',
            ])->values()->toArray();

            $context['total_balance'] = (float) $accounts->sum('balance_cached');
        } catch (\Throwable $e) {
            $context['accounts']      = [];
            $context['total_balance'] = 0;
        }

        // Monthly summary (current month) & top categories
        try {
            $currentMonthTxns = Transaction::with('transactionType', 'category')
                ->where('user_id', $user->id)
                ->whereBetween('date', [$monthStart, $now])
                ->get();

            $expenses = $currentMonthTxns
                ->filter(fn($t) => optional($t->transactionType)->slug === 'expense')
                ->sum('amount');

            $incomes = $currentMonthTxns
                ->filter(fn($t) => optional($t->transactionType)->slug === 'income')
                ->sum('amount');

            $context['current_month_summary'] = [
                'total_expenses'    => (float) $expenses,
                'total_incomes'     => (float) $incomes,
                'net'               => (float) ($incomes - $expenses),
                'transaction_count' => $currentMonthTxns->count(),
            ];

            $context['top_categories_this_month'] = $currentMonthTxns
                ->filter(fn($t) => optional($t->transactionType)->slug === 'expense')
                ->groupBy('category_id')
                ->map(fn($group) => [
                    'category_id'   => $group->first()->category_id,
                    'category_name' => optional($group->first()->category)->name ?? 'Sin categoría',
                    'total'         => (float) $group->sum('amount'),
                    'count'         => $group->count(),
                ])
                ->sortByDesc('total')
                ->values()
                ->take(5)
                ->toArray();
        } catch (\Throwable $e) {
            $context['current_month_summary']    = [
                'total_expenses'    => 0,
                'total_incomes'     => 0,
                'net'               => 0,
                'transaction_count' => 0,
            ];
            $context['top_categories_this_month'] = [];
        }

        // Recent transactions (last 10)
        try {
            $recent = Transaction::with('transactionType', 'category', 'account')
                ->where('user_id', $user->id)
                ->orderByDesc('date')
                ->limit(10)
                ->get();

            $context['recent_transactions'] = $recent->map(fn($t) => [
                'date'        => $t->date,
                'type'        => optional($t->transactionType)->slug,
                'amount'      => (float) $t->amount,
                'description' => $t->description ?? $t->name,
                'category'    => optional($t->category)->name,
                'account'     => optional($t->account)->name,
            ])->toArray();
        } catch (\Throwable $e) {
            $context['recent_transactions'] = [];
        }

        // AI user settings
        try {
            $aiSettings = AiUserSetting::where('user_id', $user->id)->first();
            if ($aiSettings) {
                $context['ai_preferences'] = [
                    'advisor_name'          => $aiSettings->advisor_name,
                    'advisor_personality'   => $aiSettings->advisor_personality,
                    'context_window_months' => $aiSettings->context_window_months,
                    'preferred_currency'    => $aiSettings->preferred_currency,
                ];
            }
        } catch (\Throwable $e) {
            // no ai settings yet
        }

        return $context;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entities\AiUsageLog;
use App\Services\AI\AiProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiMonitorController extends Controller
{
    /** Provider status + current config */
    public function providers(): JsonResponse
    {
        $status = AiProviderFactory::providersStatus();

        $activeExtraction = config('ai.features.extraction', 'opencode-go');
        $activeAdvisor    = config('ai.features.advisor', 'opencode-go');
        $extractionChain  = config('ai.features.extraction_fallback', '');
        $advisorChain     = config('ai.features.advisor_fallback', '');

        return response()->json([
            'providers'         => array_values($status),
            'active_extraction' => $activeExtraction,
            'active_advisor'    => $activeAdvisor,
            'extraction_chain'  => array_filter(array_map('trim', explode(',', $extractionChain))),
            'advisor_chain'     => array_filter(array_map('trim', explode(',', $advisorChain))),
        ]);
    }

    /** Aggregated usage stats */
    public function stats(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 30);

        $since = now()->subDays($days)->toDateString();

        // Total by provider
        $byProvider = AiUsageLog::query()
            ->where('date', '>=', $since)
            ->groupBy('provider_name')
            ->select([
                'provider_name',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(input_tokens) as input_tokens'),
                DB::raw('SUM(output_tokens) as output_tokens'),
                DB::raw('SUM(estimated_cost_usd) as cost_usd'),
            ])
            ->orderByDesc('calls')
            ->get();

        // Daily calls last N days
        $daily = AiUsageLog::query()
            ->where('date', '>=', $since)
            ->groupBy('date', 'provider_name')
            ->select([
                'date',
                'provider_name',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(estimated_cost_usd) as cost_usd'),
            ])
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        // Total summary
        $totals = AiUsageLog::query()
            ->where('date', '>=', $since)
            ->selectRaw('COUNT(*) as total_calls, SUM(input_tokens) as total_input, SUM(output_tokens) as total_output, SUM(estimated_cost_usd) as total_cost')
            ->first();

        // By feature
        $byFeature = AiUsageLog::query()
            ->where('date', '>=', $since)
            ->groupBy('feature')
            ->select([
                'feature',
                DB::raw('COUNT(*) as calls'),
                DB::raw('SUM(estimated_cost_usd) as cost_usd'),
            ])
            ->orderByDesc('calls')
            ->get();

        // Recent calls (last 50)
        $recent = AiUsageLog::query()
            ->orderByDesc('created_at')
            ->limit(50)
            ->select(['id', 'user_id', 'feature', 'provider_name', 'model_used', 'input_tokens', 'output_tokens', 'estimated_cost_usd', 'date', 'created_at'])
            ->get();

        return response()->json([
            'period_days' => $days,
            'totals'      => $totals,
            'by_provider' => $byProvider,
            'by_feature'  => $byFeature,
            'daily'       => $daily,
            'recent'      => $recent,
        ]);
    }
}

<?php
namespace App\Http\Middleware;

use App\Models\Entities\AiUsageLog;
use Closure;
use Illuminate\Http\Request;

class AiTokenBudgetMiddleware
{
    // Monthly token budget per user (soft limit — warns, doesn't block)
    const MONTHLY_INPUT_TOKEN_LIMIT = 500_000;  // ~$0.40 at Haiku rates

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return $next($request);

        $monthStart = now()->startOfMonth()->toDateString();

        $monthlyInputTokens = AiUsageLog::where('user_id', $user->id)
            ->where('date', '>=', $monthStart)
            ->sum('input_tokens');

        if ($monthlyInputTokens > self::MONTHLY_INPUT_TOKEN_LIMIT) {
            return response()->json([
                'error'   => 'Límite mensual de IA alcanzado.',
                'message' => 'Has alcanzado el límite de uso de IA este mes. Se reinicia el 1ro del próximo mes.',
                'used'    => $monthlyInputTokens,
                'limit'   => self::MONTHLY_INPUT_TOKEN_LIMIT,
            ], 429);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\JarSetting;
use App\Models\Entities\JarLeverageSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JarSettingController extends Controller
{
    /**
     * GET /api/v1/jars/settings
     */
    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $settings = JarSetting::firstOrCreate(['user_id' => $userId]);

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => $settings,
        ]);
    }

    /**
     * PUT /api/v1/jars/settings
     */
    public function update(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'global_start_date' => 'nullable|date',
            'default_allow_negative' => 'nullable|boolean',
            'default_negative_limit' => 'nullable|numeric|min:0',
            'default_reset_cycle' => 'nullable|in:none,monthly,quarterly,semiannual,annual',
            'default_reset_cycle_day' => 'nullable|integer|min:1|max:28',
            'leverage_jar_id' => 'nullable|integer|exists:jars,id',
            'auto_leverage_enabled' => 'nullable|boolean',
        ]);

        $settings = JarSetting::firstOrCreate(['user_id' => $userId]);
        $settings->fill($validated);
        $settings->save();

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Jar settings updated',
            'data' => $settings,
        ]);
    }

    /**
     * GET /api/v1/jars/settings/monthly?month=YYYY-MM
     */
    public function showMonthly(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $monthParam = $request->get('month');
        $month = $monthParam ? Carbon::parse($monthParam . '-01') : Carbon::now();
        $monthStart = $month->startOfMonth();

        $setting = JarLeverageSetting::where('user_id', $userId)
            ->where('month', $monthStart)
            ->first();

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => [
                'month' => $monthStart->toDateString(),
                'leverage_jar_id' => $setting?->leverage_jar_id,
                'use_real_income' => $setting?->use_real_income ?? false,
            ],
        ]);
    }

    /**
     * PUT /api/v1/jars/settings/monthly
     * Body: { month: "YYYY-MM", leverage_jar_id: number|null }
     */
    public function updateMonthly(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'leverage_jar_id' => 'nullable|integer|exists:jars,id',
            'use_real_income' => 'nullable|boolean',
        ]);

        $monthDate = Carbon::parse($validated['month'] . '-01')
            ->startOfMonth();

        $setting = JarLeverageSetting::updateOrCreate(
            [
                'user_id' => $userId,
                'month' => $monthDate,
            ],
            [
                'leverage_jar_id' => $validated['leverage_jar_id'] ?? null,
                'use_real_income' => $validated['use_real_income'] ?? false,
            ]
        );

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Monthly leverage setting updated',
            'data' => [
                'month' => $setting->month->toDateString(),
                'leverage_jar_id' => $setting->leverage_jar_id,
                'use_real_income' => $setting->use_real_income,
            ],
        ]);
    }
}

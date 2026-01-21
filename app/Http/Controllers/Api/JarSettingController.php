<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\JarSetting;
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
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\UserSetting;

class UserSettingController extends Controller
{
    /**
     * Get the authenticated user's settings.
     */
    public function show(Request $request)
    {
        $settings = $request->user()->settings;

        if (!$settings) {
            // Default response if no settings exist yet
            return response()->json([
                'status' => 'SUCCESS',
                'code' => 200,
                'message' => 'User settings retrieved',
                'data' => [
                    'layout_mode' => null,
                    'has_seen_onboarding' => false,
                    'preferences' => null,
                ]
            ]);
        }

        return response()->json([
            'status' => 'SUCCESS',
            'code' => 200,
            'message' => 'User settings retrieved',
            'data' => $settings
        ]);
    }

    /**
     * Update the authenticated user's settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'layout_mode' => 'nullable|string|in:lite,pro,legacy',
            'has_seen_onboarding' => 'nullable|boolean',
            'preferences' => 'nullable|array',
        ]);

        $settings = UserSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'status' => 'SUCCESS',
            'code' => 200,
            'message' => 'User settings updated successfully',
            'data' => $settings
        ]);
    }
}

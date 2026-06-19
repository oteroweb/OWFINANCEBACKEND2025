<?php

namespace App\Http\Controllers;

use App\Models\Entities\AiUserSetting;
use App\Models\Entities\Jar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class UserFinancialProfileController extends Controller
{
    private const PROFILE_FIELDS = [
        'occupation', 'income_range', 'living_situation', 'debt_situation',
        'emergency_fund', 'money_relationship', 'main_goal', 'dream', 'emotional_keyword',
    ];

    public function show(Request $request): JsonResponse
    {
        $user    = $request->user();
        $setting = AiUserSetting::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'data' => $this->formatProfile($setting),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'occupation'        => ['nullable', 'string', Rule::in(['employee', 'freelancer', 'entrepreneur', 'student', 'retired', 'other'])],
            'income_range'      => ['nullable', 'string', Rule::in(['<500', '500-1500', '1500-4000', '>4000'])],
            'living_situation'  => ['nullable', 'string', Rule::in(['solo', 'pareja', 'familia', 'roommates'])],
            'debt_situation'    => ['nullable', 'string', Rule::in(['none', 'credit_card', 'personal_loan', 'mortgage', 'multiple'])],
            'emergency_fund'    => ['nullable', 'string', Rule::in(['none', '<3m', '3-6m', '>6m'])],
            'money_relationship'=> ['nullable', 'string', Rule::in(['want_improve', 'organized', 'hard_to_save', 'day_to_day'])],
            'main_goal'         => ['nullable', 'string', Rule::in(['debt_free', 'emergency_fund', 'saving_goal', 'invest', 'survive'])],
            'dream'             => ['nullable', 'string', 'max:500'],
            'emotional_keyword' => ['nullable', 'string', Rule::in(['tranquilo', 'libre', 'seguro', 'control', 'prospero'])],
            'onboarding_profile_completed' => ['nullable', 'boolean'],
        ]);

        $user    = $request->user();
        $setting = AiUserSetting::firstOrCreate(['user_id' => $user->id]);
        $setting->fill($validated)->save();

        Cache::forget("ai_user_context_{$user->id}");

        return response()->json([
            'data'    => $this->formatProfile($setting),
            'message' => 'Perfil financiero actualizado.',
        ]);
    }

    public function updateJarDescriptions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jars'              => ['required', 'array', 'min:1'],
            'jars.*.id'         => ['required', 'integer'],
            'jars.*.description'=> ['nullable', 'string', 'max:500'],
        ]);

        $user   = $request->user();
        $jarIds = collect($validated['jars'])->pluck('id');

        $userJars = Jar::where('user_id', $user->id)
            ->whereIn('id', $jarIds)
            ->get()
            ->keyBy('id');

        $updated = [];
        foreach ($validated['jars'] as $item) {
            $jar = $userJars->get($item['id']);
            if (!$jar) continue;
            $jar->description = $item['description'] ?? null;
            $jar->save();
            $updated[] = ['id' => $jar->id, 'name' => $jar->name, 'description' => $jar->description];
        }

        Cache::forget("ai_user_context_{$user->id}");

        return response()->json([
            'data'    => $updated,
            'message' => 'Descripciones de cántaros actualizadas.',
        ]);
    }

    private function formatProfile(AiUserSetting $setting): array
    {
        return [
            'occupation'                   => $setting->occupation,
            'income_range'                 => $setting->income_range,
            'living_situation'             => $setting->living_situation,
            'debt_situation'               => $setting->debt_situation,
            'emergency_fund'               => $setting->emergency_fund,
            'money_relationship'           => $setting->money_relationship,
            'main_goal'                    => $setting->main_goal,
            'dream'                        => $setting->dream,
            'emotional_keyword'            => $setting->emotional_keyword,
            'onboarding_profile_completed' => (bool) $setting->onboarding_profile_completed,
            // prefs del asesor (ya existían)
            'advisor_name'                 => $setting->advisor_name,
            'advisor_personality'          => $setting->advisor_personality,
            'preferred_currency'           => $setting->preferred_currency,
            'advisor_enabled'              => $setting->advisor_enabled,
        ];
    }
}

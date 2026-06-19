<?php

namespace App\Models\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AiUserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'advisor_name',
        'advisor_personality',
        'voice_enabled',
        'ocr_enabled',
        'auto_ia_enabled',
        'advisor_enabled',
        'monthly_budget_alert',
        'preferred_currency',
        'context_window_months',
        // perfil narrativo
        'occupation',
        'income_range',
        'living_situation',
        'debt_situation',
        'emergency_fund',
        'money_relationship',
        'main_goal',
        'dream',
        'emotional_keyword',
        'onboarding_profile_completed',
    ];

    protected $casts = [
        'voice_enabled'                => 'boolean',
        'ocr_enabled'                  => 'boolean',
        'auto_ia_enabled'              => 'boolean',
        'advisor_enabled'              => 'boolean',
        'monthly_budget_alert'         => 'float',
        'onboarding_profile_completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

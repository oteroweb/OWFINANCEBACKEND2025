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
    ];

    protected $casts = [
        'voice_enabled'        => 'boolean',
        'ocr_enabled'          => 'boolean',
        'auto_ia_enabled'      => 'boolean',
        'advisor_enabled'      => 'boolean',
        'monthly_budget_alert' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

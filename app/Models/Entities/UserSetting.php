<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'layout_mode',
        'has_seen_onboarding',
        'preferences',
    ];

    protected $casts = [
        'has_seen_onboarding' => 'boolean',
        'preferences' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

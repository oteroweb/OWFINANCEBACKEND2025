<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JarSetting extends Model
{
    protected $fillable = [
        'user_id',
        'global_start_date',
        'default_allow_negative',
        'default_negative_limit',
        'default_reset_cycle',
        'default_reset_cycle_day',
    ];

    protected $casts = [
        'global_start_date' => 'date',
        'default_allow_negative' => 'boolean',
        'default_negative_limit' => 'decimal:2',
        'default_reset_cycle_day' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

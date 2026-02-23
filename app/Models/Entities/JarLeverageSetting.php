<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;

class JarLeverageSetting extends Model
{
    protected $table = 'jar_leverage_settings';

    protected $fillable = [
        'user_id',
        'month',
        'leverage_jar_id',
        'use_real_income',
    ];

    protected $casts = [
        'month' => 'date',
        'use_real_income' => 'boolean',
    ];
}

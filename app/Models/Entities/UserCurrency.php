<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCurrency extends Model
{
    use HasFactory;

    protected $table = 'user_currencies';

    protected $fillable = [
        'user_id',
        'currency_id',
        'current_rate',
        'is_current',
        'is_official',
        'official_at',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'is_official' => 'boolean',
        'current_rate' => 'float',
        'official_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}

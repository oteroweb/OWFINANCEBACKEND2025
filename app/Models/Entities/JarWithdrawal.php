<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JarWithdrawal extends Model
{
    protected $fillable = [
        'jar_id',
        'user_id',
        'amount',
        'description',
        'previous_available',
        'new_available',
        'withdrawal_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_available' => 'decimal:2',
        'new_available' => 'decimal:2',
        'withdrawal_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function jar(): BelongsTo
    {
        return $this->belongsTo(Jar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

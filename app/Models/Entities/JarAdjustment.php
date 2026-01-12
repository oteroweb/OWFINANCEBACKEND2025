<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JarAdjustment extends Model
{
    protected $fillable = [
        'jar_id',
        'user_id',
        'amount',
        'type',
        'reason',
        'previous_available',
        'new_available',
        'adjustment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_available' => 'decimal:2',
        'new_available' => 'decimal:2',
        'adjustment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: JarAdjustment belongs to Jar
     */
    public function jar(): BelongsTo
    {
        return $this->belongsTo(Jar::class);
    }

    /**
     * Relationship: JarAdjustment belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

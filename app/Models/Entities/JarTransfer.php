<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JarTransfer extends Model
{
    protected $fillable = [
        'user_id',
        'from_jar_id',
        'to_jar_id',
        'amount',
        'transfer_type',
        'description',
        'from_previous_available',
        'from_new_available',
        'to_previous_available',
        'to_new_available',
        'transfer_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'from_previous_available' => 'decimal:2',
        'from_new_available' => 'decimal:2',
        'to_previous_available' => 'decimal:2',
        'to_new_available' => 'decimal:2',
        'transfer_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function fromJar(): BelongsTo
    {
        return $this->belongsTo(Jar::class, 'from_jar_id');
    }

    public function toJar(): BelongsTo
    {
        return $this->belongsTo(Jar::class, 'to_jar_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

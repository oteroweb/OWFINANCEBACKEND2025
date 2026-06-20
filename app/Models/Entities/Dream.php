<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dream extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'emoji',
        'description',
        'target_amount',
        'saved_amount',
        'color',
        'priority',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'target_amount'  => 'float',
        'saved_amount'   => 'float',
        'priority'       => 'integer',
        'is_completed'   => 'boolean',
        'completed_at'   => 'datetime:Y-m-d',
        'created_at'     => 'datetime:Y-m-d',
        'updated_at'     => 'datetime:Y-m-d',
        'deleted_at'     => 'datetime:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressAttribute(): float
    {
        if ($this->target_amount <= 0) return 0;
        return round(($this->saved_amount / $this->target_amount) * 100, 1);
    }

    protected $appends = ['progress'];
}

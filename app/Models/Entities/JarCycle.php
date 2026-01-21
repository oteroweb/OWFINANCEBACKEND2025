<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JarCycle extends Model
{
    protected $fillable = [
        'jar_id',
        'cycle_start_date',
        'cycle_end_date',
        'starting_balance',
        'ending_balance',
        'total_allocated',
        'total_spent',
        'total_adjustments',
        'total_withdrawals',
        'carryover_to_next',
    ];

    protected $casts = [
        'cycle_start_date' => 'date',
        'cycle_end_date' => 'date',
        'starting_balance' => 'decimal:2',
        'ending_balance' => 'decimal:2',
        'total_allocated' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_adjustments' => 'decimal:2',
        'total_withdrawals' => 'decimal:2',
        'carryover_to_next' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function jar(): BelongsTo
    {
        return $this->belongsTo(Jar::class);
    }
}

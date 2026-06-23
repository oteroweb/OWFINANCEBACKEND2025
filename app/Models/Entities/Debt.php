<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Debt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'merchant',
        'original_amount',
        'balance',
        'next_due_amount',
        'next_due_date',
        'total_installments',
        'paid_installments',
        'rate',
        'status',
        'notes',
        'priority',
    ];

    protected $casts = [
        'original_amount'    => 'float',
        'balance'            => 'float',
        'next_due_amount'    => 'float',
        'next_due_date'      => 'date:Y-m-d',
        'total_installments' => 'integer',
        'paid_installments'  => 'integer',
        'priority'           => 'integer',
        'created_at'         => 'datetime:Y-m-d',
        'updated_at'         => 'datetime:Y-m-d',
        'deleted_at'         => 'datetime:Y-m-d',
    ];

    protected $appends = ['progress'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressAttribute(): float
    {
        if ($this->total_installments && $this->total_installments > 0) {
            return round(($this->paid_installments / $this->total_installments) * 100, 1);
        }
        if ($this->original_amount > 0) {
            $paid = $this->original_amount - $this->balance;
            return round(($paid / $this->original_amount) * 100, 1);
        }
        return 0;
    }
}

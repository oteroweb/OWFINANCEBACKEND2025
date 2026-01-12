<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMonthlyIncomeHistory extends Model
{
    protected $table = 'user_monthly_income_history';

    protected $fillable = [
        'user_id',
        'monthly_income',
        'month',
        'notes',
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: UserMonthlyIncomeHistory belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the monthly income for a specific user and month
     * Returns null if no historical record exists
     */
    public static function getForMonth(int $userId, string $monthDate): ?float
    {
        // Ensure we only compare date part (YYYY-MM-DD)
        $monthDate = substr($monthDate, 0, 10);

        $record = static::where('user_id', $userId)
            ->whereRaw('DATE(month) = ?', [$monthDate])
            ->first();

        return $record ? (float) $record->monthly_income : null;
    }

    /**
     * Save or update monthly income for a specific month
     */
    public static function saveForMonth(int $userId, float $monthlyIncome, string $monthDate, ?string $notes = null): self
    {
        // Ensure we only use date part (YYYY-MM-DD) to avoid datetime comparison issues
        $monthDate = substr($monthDate, 0, 10);

        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'month' => $monthDate,
            ],
            [
                'monthly_income' => $monthlyIncome,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Get the most recent monthly income before or on a given date
     * Useful for filling gaps in historical data
     */
    public static function getMostRecentBeforeMonth(int $userId, string $monthDate): ?float
    {
        // Ensure we only use date part
        $monthDate = substr($monthDate, 0, 10);

        $record = static::where('user_id', $userId)
            ->where('month', '<=', $monthDate)
            ->orderBy('month', 'desc')
            ->first();

        return $record ? (float) $record->monthly_income : null;
    }
}

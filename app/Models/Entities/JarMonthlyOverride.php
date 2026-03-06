<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JarMonthlyOverride extends Model
{
    protected $table = 'jar_monthly_overrides';

    protected $fillable = [
        'jar_id',
        'user_id',
        'month',
        'percent',
        'fixed_amount',
        'notes',
    ];

    protected $casts = [
        'percent' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'month' => 'date:Y-m-d',
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

    /**
     * Get the override for a specific jar and month.
     * Returns null if no override exists.
     */
    public static function getForMonth(int $jarId, string $monthDate): ?self
    {
        $monthDate = substr($monthDate, 0, 10);

        return static::where('jar_id', $jarId)
            ->whereRaw('DATE(month) = ?', [$monthDate])
            ->first();
    }

    /**
     * Save or update an override for a specific jar and month.
     */
    public static function saveForMonth(
        int $jarId,
        int $userId,
        string $monthDate,
        ?float $percent = null,
        ?float $fixedAmount = null,
        ?string $notes = null
    ): self {
        $monthDate = substr($monthDate, 0, 10);

        return static::updateOrCreate(
            [
                'jar_id' => $jarId,
                'month' => $monthDate,
            ],
            [
                'user_id' => $userId,
                'percent' => $percent,
                'fixed_amount' => $fixedAmount,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Get all overrides for a user in a specific month.
     */
    public static function getAllForUserMonth(int $userId, string $monthDate): \Illuminate\Database\Eloquent\Collection
    {
        $monthDate = substr($monthDate, 0, 10);

        return static::where('user_id', $userId)
            ->whereRaw('DATE(month) = ?', [$monthDate])
            ->get();
    }

    /**
     * Delete override for a specific jar and month.
     */
    public static function removeForMonth(int $jarId, string $monthDate): int
    {
        $monthDate = substr($monthDate, 0, 10);

        return static::where('jar_id', $jarId)
            ->whereRaw('DATE(month) = ?', [$monthDate])
            ->delete();
    }
}

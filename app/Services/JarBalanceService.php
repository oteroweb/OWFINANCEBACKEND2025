<?php

namespace App\Services;

use App\Models\Entities\Jar;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\JarAdjustment;
use App\Models\Entities\UserMonthlyIncomeHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class JarBalanceService
{
    /**
     * Get the current available balance for a jar
     * Formula: (allocated_amount - spent_amount) + adjustment
     */
    public function getAvailableBalance(Jar $jar, ?Carbon $date = null): float
    {
        $date = $date ?? Carbon::now();

        // Calculate allocated amount based on jar type
        $allocatedAmount = $this->calculateAllocatedAmount($jar, $date);

        // Calculate spent amount for this period
        $spentAmount = $this->calculateSpentAmount($jar, $date);

        // Get current adjustment for this date
        $adjustment = $jar->adjustment ?? 0;

        // Formula: (allocated - spent) + adjustment
        return $allocatedAmount - $spentAmount + $adjustment;
    }

    /**
     * Calculate the allocated amount for a jar based on its type
     * - Fixed: always returns the fixed_amount
     * - Percent: calculates percentage of user's income for the month
     */
    private function calculateAllocatedAmount(Jar $jar, Carbon $date): float
    {
        if ($jar->type === 'fixed') {
            return (float) $jar->fixed_amount;
        }

        if ($jar->type === 'percent') {
            // For percent jars, use the monthly_income configured for that month
            $monthlyIncome = $this->getMonthlyIncomeForMonth($jar->user_id, $date);
            return $monthlyIncome * ($jar->percent / 100);
        }

        return 0;
    }

    /**
     * Get monthly income for a specific user and month
     * Tries historical data first, then falls back to current value
     */
    private function getMonthlyIncomeForMonth(int $userId, Carbon $date): float
    {
        $firstDayOfMonth = $date->clone()->startOfMonth()->toDateString();

        // Try to get historical record for this specific month
        $historicalIncome = UserMonthlyIncomeHistory::getForMonth($userId, $firstDayOfMonth);

        if ($historicalIncome !== null) {
            return $historicalIncome;
        }

        // If no historical record, try to get the most recent one before this month
        $recentIncome = UserMonthlyIncomeHistory::getMostRecentBeforeMonth($userId, $firstDayOfMonth);

        if ($recentIncome !== null) {
            return $recentIncome;
        }

        // Fallback to current monthly_income
        $user = \App\Models\User::find($userId);
        return (float) ($user->monthly_income ?? 0);
    }

    /**
     * Calculate total spent amount for a jar in a given month
     * Sums all item transactions linked to this jar or its categories
     */
    private function calculateSpentAmount(Jar $jar, Carbon $date): float
    {
        $startOfMonth = $date->clone()->startOfMonth();
        $endOfMonth = $date->clone()->endOfMonth();

        // FIX: Especificar tabla explícitamente para evitar ambigüedad
        $query = ItemTransaction::whereBetween('item_transactions.created_at', [$startOfMonth, $endOfMonth]);

        // Filter by jar directly or by categories linked to jar
        if ($jar->categories()->exists()) {
            $query->whereIn('item_transactions.category_id', $jar->categories()->pluck('id'));
        } else {
            $query->where('item_transactions.jar_id', $jar->id);
        }

        return (float) $query->sum('item_transactions.amount');
    }

    /**
     * Calculate total income for a user in a given month
     * Now supports filtering by base_scope and base_categories
     *
     * @param Jar $jar The jar with base_scope configuration
     * @param Carbon $date The date to calculate income for
     * @return float Total income amount
     */
    private function calculateUserIncome(Jar $jar, Carbon $date): float
    {
        $startOfMonth = $date->clone()->startOfMonth();
        $endOfMonth = $date->clone()->endOfMonth();

        // Base query: income transactions for this user in the date range
        // FIX: Especificar tabla explícitamente para evitar ambigüedad
        $query = \App\Models\Entities\ItemTransaction::where('item_transactions.user_id', $jar->user_id)
            ->whereHas('transaction', function($q) {
                $q->whereHas('transactionType', function($typeQuery) {
                    $typeQuery->where('slug', 'income');
                });
            })
            ->whereBetween('item_transactions.date', [$startOfMonth, $endOfMonth]);

        // Apply base_scope filtering
        if ($jar->base_scope === 'categories') {
            // Only sum income from specified base categories
            $baseCategoryIds = $jar->baseCategories()->pluck('categories.id')->toArray();

            if (!empty($baseCategoryIds)) {
                $query->whereIn('item_transactions.category_id', $baseCategoryIds);
            } else {
                // If base_scope is 'categories' but no categories defined, return 0
                return 0;
            }
        }
        // If base_scope is 'all_income' or null, sum ALL income (no filtering)

        return (float) $query->sum('item_transactions.amount');
    }

    /**
     * Make a manual adjustment to a jar's available balance
     * This adjustment applies to the current or specified period
     */
    public function adjustBalance(
        Jar $jar,
        float $amount,
        ?string $reason = null,
        ?Carbon $date = null,
        ?int $adjustedByUserId = null
    ): JarAdjustment {
        $date = $date ?? Carbon::now();
        $adjustedByUserId = $adjustedByUserId ?? auth()->id() ?? $jar->user_id;

        // Calculate current balance before adjustment
        $previousAvailable = $this->getAvailableBalance($jar, $date);

        // Determine if adjustment is increment or decrement
        $type = $amount > 0 ? 'increment' : 'decrement';

        // Update jar's adjustment field
        $jar->adjustment += $amount;
        $jar->save();

        // Calculate new balance after adjustment
        $newAvailable = $this->getAvailableBalance($jar, $date);

        // Record in audit trail
        return JarAdjustment::create([
            'jar_id' => $jar->id,
            'user_id' => $adjustedByUserId,
            'amount' => abs($amount),
            'type' => $type,
            'reason' => $reason,
            'previous_available' => $previousAvailable,
            'new_available' => $newAvailable,
            'adjustment_date' => $date->toDateString(),
        ]);
    }

    /**
     * Get adjustment history for a jar
     */
    public function getAdjustmentHistory(Jar $jar, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = JarAdjustment::where('jar_id', $jar->id);

        if ($from) {
            $query->where('adjustment_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->where('adjustment_date', '<=', $to->toDateString());
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Reset adjustment for a jar (used when jar refresh_mode is 'reset')
     * This is called at the beginning of a new period
     */
    public function resetAdjustmentForNewPeriod(Jar $jar): void
    {
        if ($jar->refresh_mode === 'reset') {
            $jar->adjustment = 0;
            $jar->save();
        }
        // If accumulative, adjustment remains
    }

    /**
     * Get detailed jar balance info
     */
    public function getDetailedBalance(Jar $jar, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();

        $allocatedAmount = $this->calculateAllocatedAmount($jar, $date);
        $spentAmount = $this->calculateSpentAmount($jar, $date);
        $adjustment = $jar->adjustment ?? 0;
        $availableBalance = $this->getAvailableBalance($jar, $date);

        return [
            'jar_id' => $jar->id,
            'jar_name' => $jar->name,
            'type' => $jar->type,
            'refresh_mode' => $jar->refresh_mode,
            'allocated_amount' => $allocatedAmount,
            'spent_amount' => $spentAmount,
            'adjustment' => $adjustment,
            'available_balance' => $availableBalance,
            'period' => [
                'month' => $date->format('F Y'),
                'start' => $date->clone()->startOfMonth()->toDateString(),
                'end' => $date->clone()->endOfMonth()->toDateString(),
            ],
        ];
    }
}

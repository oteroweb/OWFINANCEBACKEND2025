<?php

namespace App\Services;

use App\Models\Entities\Jar;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\JarAdjustment;
use App\Models\Entities\JarSetting;
use App\Models\Entities\JarWithdrawal;
use App\Models\Entities\UserMonthlyIncomeHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JarBalanceService
{
    /**
     * Get the current available balance for a jar
     * Considers refresh_mode:
     * - reset: Balance = (Allocated - Spent) + Adjustments_current_month
     * - accumulative: Balance = Previous_balance + (Allocated - Spent) + Adjustments_current_month
     */
    public function getAvailableBalance(Jar $jar, ?Carbon $date = null): float
    {
        $date = $date ?? Carbon::now();

        $cutoffDate = $this->getBalanceCutoffDate($jar, $date);
        if ($cutoffDate && $date->copy()->startOfMonth()->lt($cutoffDate->copy()->startOfMonth())) {
            return 0;
        }

        // Calculate allocated amount for this month
        $allocatedAmount = $this->calculateAllocatedAmount($jar, $date);

        // Calculate spent amount for this month
        $spentAmount = $this->calculateSpentAmount($jar, $date);

        // Get adjustments for this specific month only
        $monthlyAdjustment = $this->getMonthlyAdjustment($jar, $date);

        // Get withdrawals for this specific month only
        $monthlyWithdrawals = $this->getMonthlyWithdrawals($jar, $date);

        // Base balance for current month
        $currentMonthBalance = $allocatedAmount - $spentAmount + $monthlyAdjustment - $monthlyWithdrawals;

        // If accumulative mode, add previous month's balance
        if ($jar->refresh_mode === 'accumulative') {
            if ($cutoffDate) {
                $previousMonth = $date->copy()->subMonth()->startOfMonth();
                if ($previousMonth->lt($cutoffDate->copy()->startOfMonth())) {
                    return $currentMonthBalance;
                }
            }
            $previousMonthBalance = $this->getPreviousMonthBalance($jar, $date);
            return $previousMonthBalance + $currentMonthBalance;
        }

        // Reset mode: just return current month
        return $currentMonthBalance;
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
     * Get the adjustment amount for ONLY the specified month (not cumulative)
     */
    private function getMonthlyAdjustment(Jar $jar, Carbon $date): float
    {
        $startOfMonth = $date->clone()->startOfMonth()->toDateString();
        $endOfMonth = $date->clone()->endOfMonth()->toDateString();

        // Sum adjustments ONLY for this specific month
        $adjustments = JarAdjustment::where('jar_id', $jar->id)
            ->whereBetween('adjustment_date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalAdjustment = 0;
        foreach ($adjustments as $adjustment) {
            if ($adjustment->type === 'increment') {
                $totalAdjustment += $adjustment->amount;
            } else {
                $totalAdjustment -= $adjustment->amount;
            }
        }

        return $totalAdjustment;
    }

    /**
     * Get the total withdrawals for ONLY the specified month
     */
    private function getMonthlyWithdrawals(Jar $jar, Carbon $date): float
    {
        $startOfMonth = $date->clone()->startOfMonth()->toDateString();
        $endOfMonth = $date->clone()->endOfMonth()->toDateString();

        return (float) JarWithdrawal::where('jar_id', $jar->id)
            ->whereBetween('withdrawal_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
    }

    /**
     * Get the balance from the previous month (for accumulative mode)
     */
    private function getPreviousMonthBalance(Jar $jar, Carbon $date): float
    {
        $previousMonth = $date->clone()->subMonth();

        // Recursively calculate previous month's balance
        // This will naturally accumulate all previous months if in accumulative mode
        return $this->getAvailableBalance($jar, $previousMonth);
    }

    /**
     * Determine balance cutoff date based on jar and user settings
     */
    private function getBalanceCutoffDate(Jar $jar, Carbon $date): ?Carbon
    {
        $cutoff = null;

        if ($jar->use_global_start_date) {
            $settings = $this->getJarSettings($jar->user_id);
            if ($settings?->global_start_date) {
                $cutoff = Carbon::parse($settings->global_start_date);
            }
        }

        if ((!$jar->use_global_start_date || !$cutoff) && $jar->start_date) {
            $cutoff = Carbon::parse($jar->start_date);
        }

        $cycleStart = $this->getCycleStartDate($jar, $date);
        if ($cycleStart) {
            if (!$cutoff || $cycleStart->greaterThan($cutoff)) {
                $cutoff = $cycleStart;
            }
        }

        if (!$cutoff) {
            $firstIncomeMonth = UserMonthlyIncomeHistory::where('user_id', $jar->user_id)
                ->orderBy('month', 'asc')
                ->value('month');

            if ($firstIncomeMonth) {
                $cutoff = Carbon::parse($firstIncomeMonth)->startOfMonth();
            }
        }

        if (!$cutoff && $jar->created_at) {
            $cutoff = Carbon::parse($jar->created_at)->startOfMonth();
        }

        return $cutoff;
    }

    /**
     * Determine cycle start date for the given date based on reset_cycle settings
     */
    private function getCycleStartDate(Jar $jar, Carbon $date): ?Carbon
    {
        $cycle = $jar->reset_cycle ?? 'none';
        if ($cycle === 'none') {
            return null;
        }

        $day = (int) ($jar->reset_cycle_day ?? 1);
        $day = max(1, min(28, $day));

        $cycleStart = $date->copy();

        if ($cycle === 'monthly') {
            $cycleStart = $date->copy()->startOfMonth()->day($day);
            if ($cycleStart->greaterThan($date)) {
                $cycleStart = $cycleStart->subMonth()->startOfMonth()->day($day);
            }
            return $cycleStart;
        }

        if ($cycle === 'quarterly') {
            $quarter = (int) ceil($date->month / 3);
            $startMonth = (($quarter - 1) * 3) + 1;
            $cycleStart = $date->copy()->month($startMonth)->startOfMonth()->day($day);
            if ($cycleStart->greaterThan($date)) {
                $cycleStart = $cycleStart->subMonths(3)->startOfMonth()->day($day);
            }
            return $cycleStart;
        }

        if ($cycle === 'semiannual') {
            $startMonth = $date->month <= 6 ? 1 : 7;
            $cycleStart = $date->copy()->month($startMonth)->startOfMonth()->day($day);
            if ($cycleStart->greaterThan($date)) {
                $cycleStart = $cycleStart->subMonths(6)->startOfMonth()->day($day);
            }
            return $cycleStart;
        }

        if ($cycle === 'annual') {
            $cycleStart = $date->copy()->month(1)->startOfMonth()->day($day);
            if ($cycleStart->greaterThan($date)) {
                $cycleStart = $cycleStart->subYear()->month(1)->startOfMonth()->day($day);
            }
            return $cycleStart;
        }

        return null;
    }

    private function getJarSettings(int $userId): ?JarSetting
    {
        return JarSetting::where('user_id', $userId)->first();
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
        $query = ItemTransaction::query()
            ->leftJoin('transactions', 'transactions.id', '=', 'item_transactions.transaction_id')
            ->whereBetween('item_transactions.date', [$startOfMonth, $endOfMonth]);

        // Filter by jar directly or by categories linked to jar
        if ($jar->categories()->exists()) {
            $categoryIds = $jar->categories()->pluck('categories.id')->toArray();
            $query->whereIn(DB::raw('COALESCE(item_transactions.category_id, transactions.category_id)'), $categoryIds);
        } else {
            $query->where('item_transactions.jar_id', $jar->id);
        }

        return (float) $query->select(DB::raw('SUM(ABS(item_transactions.amount)) as total'))
            ->value('total');
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
            $baseCategoryIds = $jar->baseCategories()->select('categories.id')->pluck('categories.id')->toArray();

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
     * Adjust jar balance to reach a specific target available balance
     * This calculates the difference and applies it as an adjustment
     *
     * @param Jar $jar The jar to adjust
     * @param float $targetBalance The desired available balance (can be negative)
     * @param string|null $reason Optional reason for the adjustment
     * @param Carbon|null $date The date for the adjustment
     * @param int|null $adjustedByUserId The user making the adjustment
     * @return JarAdjustment The created adjustment record
     */
    public function adjustToTargetBalance(
        Jar $jar,
        float $targetBalance,
        ?string $reason = null,
        ?Carbon $date = null,
        ?int $adjustedByUserId = null
    ): JarAdjustment {
        $date = $date ?? Carbon::now();
        $adjustedByUserId = $adjustedByUserId ?? auth()->id() ?? $jar->user_id;

        // Calculate current balance before adjustment
        $previousAvailable = $this->getAvailableBalance($jar, $date);

        // Calculate the adjustment amount needed to reach target
        // Example: current=420, target=200 -> adjustment = 200 - 420 = -220
        $adjustmentAmount = $targetBalance - $previousAvailable;

        // If no adjustment needed, still create a record for audit (use 'increment' with 0 amount)
        if ($adjustmentAmount == 0) {
            return JarAdjustment::create([
                'jar_id' => $jar->id,
                'user_id' => $adjustedByUserId,
                'amount' => 0,
                'type' => 'increment', // Using increment for consistency (enum constraint)
                'reason' => $reason,
                'previous_available' => $previousAvailable,
                'new_available' => $previousAvailable,
                'adjustment_date' => $date->toDateString(),
            ]);
        }

        // Determine if adjustment is increment or decrement
        $type = $adjustmentAmount > 0 ? 'increment' : 'decrement';

        // NO actualizar jar->adjustment ya que ahora es por mes
        // El ajuste se calcula desde jar_adjustments para el mes específico

        // Crear el registro de ajuste primero
        $adjustment = JarAdjustment::create([
            'jar_id' => $jar->id,
            'user_id' => $adjustedByUserId,
            'amount' => abs($adjustmentAmount),
            'type' => $type,
            'reason' => $reason,
            'previous_available' => $previousAvailable,
            'new_available' => $targetBalance, // Debe ser exactamente el target
            'adjustment_date' => $date->toDateString(),
        ]);

        // Recalcular balance después de crear el registro
        $newAvailable = $this->getAvailableBalance($jar, $date);

        return $adjustment;
    }

    /**
     * Make a manual adjustment to a jar's available balance (incremental)
     * This adjustment applies to the current or specified period
     * @deprecated Use adjustToTargetBalance instead for target-based adjustments
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
        $adjustment = $this->getMonthlyAdjustment($jar, $date);
        $withdrawals = $this->getMonthlyWithdrawals($jar, $date);
        $availableBalance = $this->getAvailableBalance($jar, $date);
        $cutoffDate = $this->getBalanceCutoffDate($jar, $date);

        return [
            'jar_id' => $jar->id,
            'jar_name' => $jar->name,
            'type' => $jar->type,
            'refresh_mode' => $jar->refresh_mode,
            'allocated_amount' => $allocatedAmount,
            'spent_amount' => $spentAmount,
            'adjustment' => $adjustment,
            'withdrawals' => $withdrawals,
            'available_balance' => $availableBalance,
            'cutoff_date' => $cutoffDate?->toDateString(),
            'reset_cycle' => $jar->reset_cycle,
            'period' => [
                'month' => $date->format('F Y'),
                'start' => $date->clone()->startOfMonth()->toDateString(),
                'end' => $date->clone()->endOfMonth()->toDateString(),
            ],
        ];
    }

    /**
     * Withdraw from a jar (register usage)
     */
    public function withdrawFromJar(
        Jar $jar,
        float $amount,
        ?string $description = null,
        ?Carbon $date = null,
        ?int $withdrawnByUserId = null
    ): JarWithdrawal {
        $date = $date ?? Carbon::now();
        $withdrawnByUserId = $withdrawnByUserId ?? auth()->id() ?? $jar->user_id;

        $previousAvailable = $this->getAvailableBalance($jar, $date);
        $newAvailable = $previousAvailable - $amount;

        if (!$jar->allow_negative_balance && $newAvailable < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient funds for this jar.',
            ]);
        }

        if ($jar->allow_negative_balance && $jar->negative_limit !== null) {
            $limit = abs((float) $jar->negative_limit);
            if ($newAvailable < -$limit) {
                throw ValidationException::withMessages([
                    'amount' => 'Negative limit exceeded for this jar.',
                ]);
            }
        }

        return JarWithdrawal::create([
            'jar_id' => $jar->id,
            'user_id' => $withdrawnByUserId,
            'amount' => $amount,
            'description' => $description,
            'previous_available' => $previousAvailable,
            'new_available' => $newAvailable,
            'withdrawal_date' => $date->toDateString(),
        ]);
    }

    /**
     * Get withdrawal history for a jar
     */
    public function getWithdrawalHistory(Jar $jar, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = JarWithdrawal::where('jar_id', $jar->id);

        if ($from) {
            $query->where('withdrawal_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->where('withdrawal_date', '<=', $to->toDateString());
        }

        return $query->orderByDesc('created_at')->get();
    }
}

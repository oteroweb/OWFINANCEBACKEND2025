<?php

namespace App\Services;

use App\Models\Entities\Jar;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\JarAdjustment;
use App\Models\Entities\JarSetting;
use App\Models\Entities\JarLeverageSetting;
use App\Models\Entities\JarWithdrawal;
use App\Models\Entities\JarTransfer;
use App\Models\Entities\UserMonthlyIncomeHistory;
use App\Models\Entities\JarCycle;
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
        $monthlyTransfersIn = $this->getMonthlyTransfersIn($jar, $date);
        $monthlyTransfersOut = $this->getMonthlyTransfersOut($jar, $date);

        // Base balance for current month
        $currentMonthBalance = $allocatedAmount - $spentAmount + $monthlyAdjustment - $monthlyWithdrawals
            + $monthlyTransfersIn - $monthlyTransfersOut;

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
     * Get monthly usage components for a jar (allocated, spent, withdrawals)
     */
    public function getMonthlyUsage(Jar $jar, Carbon $date): array
    {
        return [
            'allocated' => $this->calculateAllocatedAmount($jar, $date),
            'spent' => $this->calculateSpentAmount($jar, $date),
            'withdrawals' => $this->getMonthlyWithdrawals($jar, $date),
            'transfers_in' => $this->getMonthlyTransfersIn($jar, $date),
            'transfers_out' => $this->getMonthlyTransfersOut($jar, $date),
        ];
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
     * Get total transfers INTO this jar for ONLY the specified month
     */
    private function getMonthlyTransfersIn(Jar $jar, Carbon $date): float
    {
        $startOfMonth = $date->clone()->startOfMonth()->toDateString();
        $endOfMonth = $date->clone()->endOfMonth()->toDateString();

        return (float) JarTransfer::where('to_jar_id', $jar->id)
            ->where(function ($q) {
                $q->whereNull('transfer_type')
                    ->orWhere('transfer_type', 'manual');
            })
            ->whereBetween('transfer_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
    }

    /**
     * Get total transfers OUT of this jar for ONLY the specified month
     */
    private function getMonthlyTransfersOut(Jar $jar, Carbon $date): float
    {
        $startOfMonth = $date->clone()->startOfMonth()->toDateString();
        $endOfMonth = $date->clone()->endOfMonth()->toDateString();

        return (float) JarTransfer::where('from_jar_id', $jar->id)
            ->where(function ($q) {
                $q->whereNull('transfer_type')
                    ->orWhere('transfer_type', 'manual');
            })
            ->whereBetween('transfer_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
    }

    /**
     * Get the balance from the previous month (for accumulative mode).
     * Uses JarCycle snapshots when available to avoid recursive query explosion.
     * Falls back to recursive calculation only if no snapshot exists.
     */
    private function getPreviousMonthBalance(Jar $jar, Carbon $date): float
    {
        $previousMonth = $date->clone()->subMonth();
        $prevStart = $previousMonth->copy()->startOfMonth();
        $prevEnd = $previousMonth->copy()->endOfMonth();

        // Try cached cycle snapshot first (O(1) instead of O(n) recursion)
        $cycle = JarCycle::where('jar_id', $jar->id)
            ->where('cycle_start_date', $prevStart->toDateString())
            ->first();

        if ($cycle) {
            return (float) $cycle->ending_balance;
        }

        // No snapshot — fall back to recursive calculation
        return $this->getAvailableBalance($jar, $previousMonth);
    }

    /**
     * Materialize a monthly balance snapshot into jar_cycles.
     * Should be called at month-end (cron) or when balance is calculated
     * for a completed month, to prevent recursive re-computation.
     */
    public function materializeCycleSnapshot(Jar $jar, Carbon $date): JarCycle
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $allocated = $this->calculateAllocatedAmount($jar, $date);
        $spent = $this->calculateSpentAmount($jar, $date);
        $adjustments = $this->getMonthlyAdjustment($jar, $date);
        $withdrawals = $this->getMonthlyWithdrawals($jar, $date);

        // Starting balance = previous month snapshot (for accumulative)
        $startingBalance = 0;
        if ($jar->refresh_mode === 'accumulative') {
            $cutoffDate = $this->getBalanceCutoffDate($jar, $date);
            $previousMonth = $date->copy()->subMonth()->startOfMonth();
            if (!$cutoffDate || !$previousMonth->lt($cutoffDate->copy()->startOfMonth())) {
                $startingBalance = $this->getPreviousMonthBalance($jar, $date);
            }
        }

        $endingBalance = $startingBalance + $allocated - $spent + $adjustments - $withdrawals;

        return JarCycle::updateOrCreate(
            [
                'jar_id' => $jar->id,
                'cycle_start_date' => $startOfMonth->toDateString(),
            ],
            [
                'cycle_end_date' => $endOfMonth->toDateString(),
                'starting_balance' => $startingBalance,
                'ending_balance' => $endingBalance,
                'total_allocated' => $allocated,
                'total_spent' => $spent,
                'total_adjustments' => $adjustments,
                'total_withdrawals' => $withdrawals,
                'carryover_to_next' => $jar->refresh_mode === 'accumulative' ? $endingBalance : 0,
            ]
        );
    }

    /**
     * Invalidate cached cycle snapshots from a given month forward.
     * If the jar is accumulative, deleting one month makes all subsequent
     * months stale because they depend on `starting_balance` from the previous one.
     *
     * After deletion, re-materializes each month in chronological order
     * so the cache is immediately consistent again.
     *
     * @param Jar    $jar  The jar whose snapshots to invalidate
     * @param Carbon $from The earliest month affected (will regenerate this month + all after it)
     */
    public function invalidateCycleSnapshotsFrom(Jar $jar, Carbon $from): void
    {
        $startOfAffected = $from->copy()->startOfMonth();
        $now = Carbon::now()->startOfMonth();

        // Nothing to invalidate for future months
        if ($startOfAffected->gt($now)) {
            return;
        }

        // Delete stale snapshots from the affected month forward
        JarCycle::where('jar_id', $jar->id)
            ->where('cycle_start_date', '>=', $startOfAffected->toDateString())
            ->delete();

        // Re-materialize each month in order so accumulative chains are correct
        $cursor = $startOfAffected->copy();
        $lastCompletedMonth = $now->copy()->subMonth()->startOfMonth();

        while ($cursor->lte($lastCompletedMonth)) {
            $this->materializeCycleSnapshot($jar, $cursor);
            $cursor->addMonth();
        }
    }

    /**
     * Called after any data mutation (adjustment, withdrawal, transfer, clear)
     * for a specific jar + date. Invalidates and regenerates affected snapshots.
     */
    public function onBalanceDataChanged(Jar $jar, Carbon $date): void
    {
        // Only matters for accumulative jars — reset-mode snapshots are independent per month
        // but we still re-materialize to keep the cache accurate.
        $this->invalidateCycleSnapshotsFrom($jar, $date);
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

    private function getMonthlyLeverageJarId(int $userId, Carbon $date): ?int
    {
        $monthStart = $date->copy()->startOfMonth();

        return JarLeverageSetting::where('user_id', $userId)
            ->where('month', $monthStart)
            ->value('leverage_jar_id');
    }

    private function getLeverageSourceJarId(Jar $jar, Carbon $date, ?JarSetting $settings = null, ?int $monthlyLeverageJarId = null): ?int
    {
        $settings = $settings ?? $this->getJarSettings($jar->user_id);
        $monthlyLeverageJarId = $monthlyLeverageJarId ?? $this->getMonthlyLeverageJarId($jar->user_id, $date);

        return $jar->leverage_from_jar_id
            ?? $monthlyLeverageJarId
            ?? $settings?->leverage_jar_id;
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
            ->where('item_transactions.user_id', $jar->user_id)
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

        // Invalidate & regenerate cached cycle snapshots from this month forward
        $this->onBalanceDataChanged($jar, $date);

        return $adjustment;
    }

    /**
     * Make an incremental adjustment to a jar's available balance.
     * Writes ONLY to jar_adjustments table (single source of truth).
     *
     * @param Jar $jar The jar to adjust
     * @param float $amount Positive to add, negative to subtract
     * @param string|null $reason Optional reason for the adjustment
     * @param Carbon|null $date The date for the adjustment
     * @param int|null $adjustedByUserId The user making the adjustment
     * @return JarAdjustment The created adjustment record
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

        $previousAvailable = $this->getAvailableBalance($jar, $date);

        $type = $amount > 0 ? 'increment' : 'decrement';

        // Single source of truth: only write to jar_adjustments table
        $adjustment = JarAdjustment::create([
            'jar_id' => $jar->id,
            'user_id' => $adjustedByUserId,
            'amount' => abs($amount),
            'type' => $type,
            'reason' => $reason,
            'previous_available' => $previousAvailable,
            'new_available' => $previousAvailable + $amount,
            'adjustment_date' => $date->toDateString(),
        ]);

        // Invalidate & regenerate cached cycle snapshots
        $this->onBalanceDataChanged($jar, $date);

        return $adjustment;
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
     * Clear all adjustments for a specific month
     */
    public function clearAdjustmentsForMonth(Jar $jar, Carbon $date): int
    {
        $startOfMonth = $date->clone()->startOfMonth()->toDateString();
        $endOfMonth = $date->clone()->endOfMonth()->toDateString();

        $deleted = JarAdjustment::where('jar_id', $jar->id)
            ->whereBetween('adjustment_date', [$startOfMonth, $endOfMonth])
            ->delete();

        if ($deleted > 0) {
            $this->onBalanceDataChanged($jar, $date);
        }

        return $deleted;
    }

    /**
     * Reset adjustments for a jar at the beginning of a new period.
     * For 'reset' mode jars: clears all adjustments in the current month.
     * For 'accumulative' mode: no-op (adjustments carry over via balance calculation).
     *
     * Uses jar_adjustments table as single source of truth.
     */
    public function resetAdjustmentForNewPeriod(Jar $jar, ?Carbon $date = null): void
    {
        if ($jar->refresh_mode === 'reset') {
            $date = $date ?? Carbon::now();
            $this->clearAdjustmentsForMonth($jar, $date);
        }
        // If accumulative, adjustments remain — they carry over via getPreviousMonthBalance()
    }

    /**
     * Get detailed jar balance info
     */
    public function getDetailedBalance(Jar $jar, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();

        $snapshot = $this->buildVirtualLeverageSnapshot($jar->user_id, $date);

        $allocatedAmount = $this->calculateAllocatedAmount($jar, $date);
        $spentAmount = $this->calculateSpentAmount($jar, $date);
        $adjustment = $this->getMonthlyAdjustment($jar, $date);
        $withdrawals = $this->getMonthlyWithdrawals($jar, $date);
        $transfersIn = $this->getMonthlyTransfersIn($jar, $date);
        $transfersOut = $this->getMonthlyTransfersOut($jar, $date);
        $baseBalance = $this->getAvailableBalance($jar, $date);
        $availableBalance = $snapshot['effective_balances'][$jar->id] ?? $baseBalance;
        $leverageIn = $snapshot['leverage_in'][$jar->id] ?? 0;
        $leverageOut = $snapshot['leverage_out'][$jar->id] ?? 0;
        $cutoffDate = $this->getBalanceCutoffDate($jar, $date);

        // For accumulative jars, compute previous month balance (carry-over)
        $previousMonthBalance = 0;
        if ($jar->refresh_mode === 'accumulative') {
            if ($cutoffDate) {
                $previousMonth = $date->clone()->subMonth()->startOfMonth();
                if (!$previousMonth->lt($cutoffDate->copy()->startOfMonth())) {
                    $previousMonthBalance = $this->getPreviousMonthBalance($jar, $date);
                }
            } else {
                $previousMonthBalance = $this->getPreviousMonthBalance($jar, $date);
            }
        }

        return [
            'jar_id' => $jar->id,
            'jar_name' => $jar->name,
            'type' => $jar->type,
            'refresh_mode' => $jar->refresh_mode,
            'allocated_amount' => $allocatedAmount,
            'spent_amount' => $spentAmount,
            'adjustment' => $adjustment,
            'withdrawals' => $withdrawals,
            'transfers_in' => $transfersIn,
            'transfers_out' => $transfersOut,
            'leverage_in' => $leverageIn,
            'leverage_out' => $leverageOut,
            'previous_month_balance' => $previousMonthBalance,
            'available_balance' => $availableBalance,
            'auto_transfer_applied' => null,
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
     * Compute virtual leverage between jars (no DB transfers).
     * Returns effective balances plus leverage in/out per jar.
     */
    private function buildVirtualLeverageSnapshot(int $userId, Carbon $date): array
    {
        $settings = $this->getJarSettings($userId);
        $monthlyLeverageJarId = $this->getMonthlyLeverageJarId($userId, $date);
        $jars = Jar::where('user_id', $userId)->get();

        $jarMap = [];
        $sources = [];
        $baseBalances = [];

        foreach ($jars as $jar) {
            $jarMap[$jar->id] = $jar;
            $sources[$jar->id] = $this->getLeverageSourceJarId($jar, $date, $settings, $monthlyLeverageJarId);
            $baseBalances[$jar->id] = $this->getAvailableBalance($jar, $date);
        }

        $effectiveBalances = $baseBalances;
        $leverageIn = [];
        $leverageOut = [];

        foreach (array_keys($jarMap) as $jarId) {
            $leverageIn[$jarId] = 0;
            $leverageOut[$jarId] = 0;
        }

        $apply = function (int $jarId, array $stack) use (&$apply, &$effectiveBalances, &$leverageIn, &$leverageOut, $sources, $jarMap) {
            if (in_array($jarId, $stack, true)) {
                return;
            }

            if (!isset($effectiveBalances[$jarId])) {
                return;
            }

            if ($effectiveBalances[$jarId] >= 0) {
                return;
            }

            $fromJarId = $sources[$jarId] ?? null;
            if (!$fromJarId || $fromJarId === $jarId) {
                return;
            }

            if (!isset($jarMap[$fromJarId])) {
                return;
            }

            $nextStack = array_merge($stack, [$jarId]);
            $apply($fromJarId, $nextStack);

            $amount = abs($effectiveBalances[$jarId]);
            if ($amount <= 0) {
                return;
            }

            // Excess is sent to the source jar (source absorbs the deficit)
            $effectiveBalances[$jarId] += $amount;
            $effectiveBalances[$fromJarId] -= $amount;
            // Jar with deficit: excess transferred out
            $leverageOut[$jarId] += $amount;
            // Source jar: absorbed from others
            $leverageIn[$fromJarId] += $amount;

            if ($effectiveBalances[$fromJarId] < 0) {
                $apply($fromJarId, $nextStack);
            }
        };

        $jarIds = array_keys($jarMap);
        sort($jarIds);
        foreach ($jarIds as $jarId) {
            $apply($jarId, []);
        }

        return [
            'base_balances' => $baseBalances,
            'effective_balances' => $effectiveBalances,
            'leverage_in' => $leverageIn,
            'leverage_out' => $leverageOut,
            'sources' => $sources,
        ];
    }

    /**
     * Attempt auto leverage and return reason
     */
    public function tryAutoLeverage(Jar $jar, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();

        $settings = $this->getJarSettings($jar->user_id);
        $fromJarId = $this->getLeverageSourceJarId($jar, $date, $settings);
        if (!$fromJarId) {
            return ['applied' => false, 'reason' => 'no_source'];
        }

        if ($fromJarId === $jar->id) {
            return ['applied' => false, 'reason' => 'same_source'];
        }

        $currentBalance = $this->getAvailableBalance($jar, $date);
        if ($currentBalance >= 0) {
            return ['applied' => false, 'reason' => 'not_exceeded'];
        }

        $fromJar = Jar::where('id', $fromJarId)
            ->where('user_id', $jar->user_id)
            ->first();
        if (!$fromJar) {
            return ['applied' => false, 'reason' => 'source_not_found'];
        }

        $snapshot = $this->buildVirtualLeverageSnapshot($jar->user_id, $date);
        $leverageOut = $snapshot['leverage_out'][$jar->id] ?? 0;

        if ($leverageOut <= 0) {
            return ['applied' => false, 'reason' => 'insufficient_source'];
        }

        return [
            'applied' => true,
            'reason' => 'applied',
            'transfer' => [
                'from_jar_id' => $fromJarId,
                'to_jar_id' => $jar->id,
                'amount' => $leverageOut,
                'date' => $date->toDateString(),
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

        $withdrawal = JarWithdrawal::create([
            'jar_id' => $jar->id,
            'user_id' => $withdrawnByUserId,
            'amount' => $amount,
            'description' => $description,
            'previous_available' => $previousAvailable,
            'new_available' => $newAvailable,
            'withdrawal_date' => $date->toDateString(),
        ]);

        // Invalidate & regenerate cached cycle snapshots
        $this->onBalanceDataChanged($jar, $date);

        return $withdrawal;
    }

    /**
     * Transfer balance between jars
     */
    public function transferBetweenJars(
        Jar $fromJar,
        Jar $toJar,
        float $amount,
        ?string $description = null,
        ?Carbon $date = null,
        ?int $userId = null,
        bool $skipValidation = false,
        string $transferType = 'manual'
    ): JarTransfer {
        $date = $date ?? Carbon::now();
        $userId = $userId ?? auth()->id() ?? $fromJar->user_id;

        $fromPrevious = $this->getAvailableBalance($fromJar, $date);
        $toPrevious = $this->getAvailableBalance($toJar, $date);
        $fromNew = $fromPrevious - $amount;
        $toNew = $toPrevious + $amount;

        if (!$skipValidation) {
            if (!$fromJar->allow_negative_balance && $fromNew < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient funds for source jar.',
                ]);
            }

            if ($fromJar->negative_limit !== null) {
                $limit = abs((float) $fromJar->negative_limit);
                if ($fromNew < -$limit) {
                    throw ValidationException::withMessages([
                        'amount' => 'Negative limit exceeded for source jar.',
                    ]);
                }
            }
        }

        $transfer = JarTransfer::create([
            'user_id' => $userId,
            'from_jar_id' => $fromJar->id,
            'to_jar_id' => $toJar->id,
            'amount' => $amount,
            'transfer_type' => $transferType,
            'description' => $description,
            'from_previous_available' => $fromPrevious,
            'from_new_available' => $fromNew,
            'to_previous_available' => $toPrevious,
            'to_new_available' => $toNew,
            'transfer_date' => $date->toDateString(),
        ]);

        // Invalidate & regenerate cached cycle snapshots for BOTH jars
        $this->onBalanceDataChanged($fromJar, $date);
        $this->onBalanceDataChanged($toJar, $date);

        return $transfer;
    }

    /**
     * Get transfer history for a jar
     */
    public function getTransferHistory(Jar $jar, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = JarTransfer::where(function ($q) use ($jar) {
            $q->where('from_jar_id', $jar->id)->orWhere('to_jar_id', $jar->id);
        });

        if ($from) {
            $query->where('transfer_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->where('transfer_date', '<=', $to->toDateString());
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Auto-transfer from leverage jar when this jar is exceeded
     */
    private function applyAutoLeverageIfNeeded(Jar $jar, Carbon $date, array $visited = []): ?JarTransfer
    {
        if (in_array($jar->id, $visited, true)) {
            return null;
        }

        $settings = $this->getJarSettings($jar->user_id);
        $fromJarId = $jar->leverage_from_jar_id ?? $settings?->leverage_jar_id;
        if (!$fromJarId) {
            return null;
        }

        if ($fromJarId === $jar->id) {
            return null;
        }

        $currentBalance = $this->getAvailableBalance($jar, $date);
        if ($currentBalance >= 0) {
            return null;
        }

        $alreadyApplied = JarTransfer::where('to_jar_id', $jar->id)
            ->where('from_jar_id', $fromJarId)
            ->whereRaw('DATE(transfer_date) = ?', [$date->toDateString()])
            ->where('transfer_type', 'leverage_auto')
            ->exists();
        if ($alreadyApplied) {
            return null;
        }

        $fromJar = Jar::where('id', $fromJarId)
            ->where('user_id', $jar->user_id)
            ->first();
        if (!$fromJar) {
            return null;
        }

        $fromAvailable = $this->getAvailableBalance($fromJar, $date);
        if ($fromAvailable < 0) {
            $this->applyAutoLeverageIfNeeded($fromJar, $date, array_merge($visited, [$jar->id]));
            $fromAvailable = $this->getAvailableBalance($fromJar, $date);
        }
        $maxTransfer = $fromAvailable;
        if ($fromJar->allow_negative_balance && $fromJar->negative_limit !== null) {
            $maxTransfer = $fromAvailable + abs((float) $fromJar->negative_limit);
        }

        if ($maxTransfer <= 0) {
            return null;
        }

        $amount = min(abs($currentBalance), $maxTransfer);
        if ($amount <= 0) {
            return null;
        }

        $transfer = $this->transferBetweenJars(
            $fromJar,
            $jar,
            $amount,
            'Apalancamiento automático',
            $date,
            $jar->user_id,
            true,
            'leverage_auto'
        );

        // If source went negative, try chaining leverage from its configured source
        $fromNew = $this->getAvailableBalance($fromJar, $date);
        if ($fromNew < 0) {
            $this->applyAutoLeverageIfNeeded($fromJar, $date, array_merge($visited, [$jar->id]));
        }

        return $transfer;
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

<?php

namespace App\Console\Commands;

use App\Models\Entities\Jar;
use App\Services\JarBalanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Materialize balance snapshots for jars into jar_cycles.
 *
 * Modes:
 *   --month=YYYY-MM   Snapshot a single month (default: previous month)
 *   --from=YYYY-MM    Snapshot every month from this date up to last completed month
 *   --all-modes       Include reset-mode jars (default: accumulative only)
 *   --user=ID         Limit to a specific user
 *
 * Scheduled to run on the 1st of each month at 00:15 via Kernel.
 * Also used manually to back-fill historical snapshots after data edits.
 *
 * Examples:
 *   php artisan jars:materialize-cycles                     # Previous month only
 *   php artisan jars:materialize-cycles --from=2025-11      # Nov-2025 → last completed
 *   php artisan jars:materialize-cycles --from=2025-11 --all-modes --user=1
 */
class MaterializeJarCycles extends Command
{
    protected $signature = 'jars:materialize-cycles
        {--month= : Target single month (YYYY-MM). Defaults to previous month.}
        {--from= : Start month (YYYY-MM). Materializes every month from here to last completed month.}
        {--all-modes : Include reset-mode jars (default: accumulative only)}
        {--user= : Limit to a specific user ID}';

    protected $description = 'Materialize monthly balance snapshots into jar_cycles table';

    public function handle(JarBalanceService $service): int
    {
        $allModes = (bool) $this->option('all-modes');
        $userId = $this->option('user');

        // Build the list of months to process
        $months = $this->resolveMonths();

        $this->info(
            count($months) === 1
                ? "Materializing jar cycles for {$months[0]->format('F Y')}..."
                : "Materializing jar cycles for " . count($months) . " months ({$months[0]->format('M Y')} → {$months[count($months) - 1]->format('M Y')})..."
        );

        $query = Jar::query();

        if (!$allModes) {
            $query->where('refresh_mode', 'accumulative');
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $jars = $query->get();

        if ($jars->isEmpty()) {
            $this->warn('No jars found matching the criteria.');
            return self::SUCCESS;
        }

        $totalOps = $jars->count() * count($months);
        $bar = $this->output->createProgressBar($totalOps);
        $bar->start();

        $success = 0;
        $errors = 0;

        // Process months in chronological order (important for accumulative chains)
        foreach ($months as $month) {
            foreach ($jars as $jar) {
                try {
                    $cycle = $service->materializeCycleSnapshot($jar, $month);
                    $this->line(
                        " <info>✓</info> [{$month->format('Y-m')}] Jar #{$jar->id} ({$jar->name}): ending={$cycle->ending_balance}",
                        null,
                        'vvv'
                    );
                    $success++;
                } catch (\Throwable $e) {
                    $this->error("  ✗ [{$month->format('Y-m')}] Jar #{$jar->id} ({$jar->name}): {$e->getMessage()}");
                    $errors++;
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done: {$success} materialized, {$errors} errors across " . count($months) . " month(s).");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Resolve the list of months to process based on --month or --from flags.
     *
     * @return Carbon[]
     */
    private function resolveMonths(): array
    {
        $lastCompleted = Carbon::now()->subMonth()->startOfMonth();

        // --from takes precedence: generate range from that month to last completed
        if ($this->option('from')) {
            $start = Carbon::parse($this->option('from') . '-01')->startOfMonth();
            $months = [];
            $cursor = $start->copy();

            while ($cursor->lte($lastCompleted)) {
                $months[] = $cursor->copy();
                $cursor->addMonth();
            }

            if (empty($months)) {
                $this->warn("--from date {$start->format('Y-m')} is in the future or current month. Using previous month.");
                return [$lastCompleted];
            }

            return $months;
        }

        // --month: single month
        if ($this->option('month')) {
            return [Carbon::parse($this->option('month') . '-01')->startOfMonth()];
        }

        // Default: previous month
        return [$lastCompleted];
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Entities\ItemTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix item_transactions that have jar_id=null by deriving the jar from
 * the jar_category pivot table using the item's category_id.
 *
 * Background: The initial repair run (2026-07-08) created item_transactions
 * with jar_id=null. The transactions table in prod does not have a jar_id
 * column, so we must derive the jar from the category relationship.
 *
 * JarBalanceService mode:
 *   - Jars WITH categories: already work via category_id fallback (COALESCE)
 *   - Jars WITHOUT categories: require jar_id on item_transaction
 * This command sets jar_id so both modes work correctly.
 *
 * Examples:
 *   php artisan transactions:repair-item-jar-id             # Dry-run (preview only)
 *   php artisan transactions:repair-item-jar-id --execute   # Apply changes
 *   php artisan transactions:repair-item-jar-id --user=1    # Limit to a specific user
 */
class RepairItemTransactionJarId extends Command
{
    protected $signature = 'transactions:repair-item-jar-id
        {--execute   : Actually write to the database (default is dry-run)}
        {--user=     : Limit repair to a specific user ID}';

    protected $description = 'Derive and set jar_id on item_transactions where jar_id=null but category_id is set';

    public function handle(): int
    {
        $dryRun = ! $this->option('execute');
        $userId = $this->option('user');

        $this->info($dryRun
            ? '[DRY RUN] Scanning — no changes will be written.'
            : '[EXECUTE] Patching jar_id on item_transactions...'
        );

        // item_transactions where jar_id is null but category_id is set
        $query = ItemTransaction::query()
            ->whereNull('jar_id')
            ->where('active', 1)
            ->whereNotNull('category_id');

        if ($userId) {
            $query->where('user_id', (int) $userId);
        }

        $total   = $query->count();
        $patched = 0;
        $skipped = 0;
        $errors  = 0;

        $this->line("Found {$total} item_transaction(s) with jar_id=null and a category_id set.");

        if ($total === 0) {
            $this->info('Nothing to patch.');
            return self::SUCCESS;
        }

        // Build a map: category_id → jar_id from jar_category pivot
        // (takes the first jar if a category belongs to multiple jars)
        $categoryToJar = DB::table('jar_category')
            ->select('category_id', DB::raw('MIN(jar_id) as jar_id'))
            ->groupBy('category_id')
            ->pluck('jar_id', 'category_id')
            ->all();

        $this->line('Loaded ' . count($categoryToJar) . ' category→jar mapping(s) from jar_category pivot.');

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(200, function ($items) use ($dryRun, $categoryToJar, &$patched, &$skipped, &$errors, $bar) {
            foreach ($items as $item) {
                try {
                    $jarId = $categoryToJar[$item->category_id] ?? null;

                    if (! $jarId) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    if (! $dryRun) {
                        DB::table('item_transactions')
                            ->where('id', $item->id)
                            ->update(['jar_id' => $jarId]);
                    }

                    $patched++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("  Error on item_transaction #{$item->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $verb = $dryRun ? 'Would patch' : 'Patched';
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total scanned',                   $total],
                ["{$verb} (jar_id derived)",         $patched],
                ['Skipped (no jar for category)',    $skipped],
                ['Errors',                           $errors],
            ]
        );

        if ($dryRun && $patched > 0) {
            $this->newLine();
            $this->warn("Run with --execute to apply these {$patched} patch(es).");
        }

        if (! $dryRun && $patched > 0) {
            $this->info("Done. {$patched} item_transaction(s) now have the correct jar_id.");
            $this->warn('Jar balances will recalculate automatically on next load.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}

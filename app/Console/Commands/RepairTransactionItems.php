<?php

namespace App\Console\Commands;

use App\Models\Entities\ItemTransaction;
use App\Models\Entities\Transaction;
use App\Models\Entities\PaymentTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repair transactions that have no item_transactions.
 *
 * JarBalanceService.calculateSpentAmount() reads only item_transactions.
 * Transactions created before the auto-item fix (2026-07-08) may have none.
 * This command back-fills a default item for every affected non-transfer transaction.
 *
 * Examples:
 *   php artisan transactions:repair-items                # Dry-run (preview only)
 *   php artisan transactions:repair-items --execute      # Apply changes
 *   php artisan transactions:repair-items --user=1       # Limit to a single user
 *   php artisan transactions:repair-items --execute --user=1
 */
class RepairTransactionItems extends Command
{
    protected $signature = 'transactions:repair-items
        {--execute   : Actually write to the database (default is dry-run)}
        {--user=     : Limit repair to a specific user ID}';

    protected $description = 'Back-fill missing item_transactions for non-transfer transactions';

    public function handle(): int
    {
        $dryRun = ! $this->option('execute');
        $userId = $this->option('user');

        $this->info($dryRun
            ? '[DRY RUN] Scanning transactions — no changes will be written.'
            : '[EXECUTE] Repairing transactions...'
        );

        // Transactions that have 0 active item_transactions
        $query = Transaction::query()
            ->whereNull('deleted_at')
            ->where('active', 1)
            ->whereDoesntHave('itemTransactions', fn ($q) => $q->where('active', 1));

        if ($userId) {
            $query->where('user_id', (int) $userId);
        }

        $total       = $query->count();
        $repaired    = 0;
        $skipped     = 0;
        $errors      = 0;

        $this->line("Found {$total} transaction(s) without item_transactions.");

        if ($total === 0) {
            $this->info('Nothing to repair.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(200, function ($transactions) use ($dryRun, &$repaired, &$skipped, &$errors, $bar) {
            foreach ($transactions as $tx) {
                try {
                    // Detect transfers: payment_transactions with both positive and negative legs
                    $payments = PaymentTransaction::where('transaction_id', $tx->id)->get();
                    $hasPos = $payments->contains(fn ($p) => (float) $p->amount > 0);
                    $hasNeg = $payments->contains(fn ($p) => (float) $p->amount < 0);

                    if ($hasPos && $hasNeg) {
                        // Transfer — skip, jars don't track transfers via item_transactions
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    // Also skip if the transaction type slug contains 'transfer'
                    if ($tx->transactionType && str_contains(strtolower((string) $tx->transactionType->slug), 'transfer')) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }

                    if (! $dryRun) {
                        DB::transaction(function () use ($tx) {
                            ItemTransaction::create([
                                'transaction_id' => $tx->id,
                                'name'           => $tx->name,
                                'quantity'       => 1,
                                'amount'         => $tx->amount,
                                'jar_id'         => $tx->jar_id,
                                'date'           => $tx->date,
                                'category_id'    => $tx->category_id,
                                'user_id'        => $tx->user_id,
                                'active'         => 1,
                            ]);
                        });
                    }

                    $repaired++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("  Error on transaction #{$tx->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $verb = $dryRun ? 'Would repair' : 'Repaired';
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total scanned',         $total],
                ["{$verb} (non-transfer)", $repaired],
                ['Skipped (transfers)',    $skipped],
                ['Errors',                $errors],
            ]
        );

        if ($dryRun && $repaired > 0) {
            $this->newLine();
            $this->warn("Run with --execute to apply these {$repaired} repair(s).");
        }

        if (! $dryRun && $repaired > 0) {
            $this->info("Done. {$repaired} transaction(s) now have a default item_transaction.");
            $this->warn('Jar balances will recalculate automatically on next load.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}

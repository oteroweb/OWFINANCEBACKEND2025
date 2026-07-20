<?php

namespace App\Console\Commands;

use App\Services\BcvRateFetcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fetch the official BCV dollar rate from pydolarve.org and persist it into
 * official_rates. Scheduled twice daily (9:00 AM / 4:00 PM America/Caracas) via
 * bootstrap/app.php (see 'jars:materialize-cycles' for the same schedule pattern).
 *
 * Never throws: any network/parse/persist failure is caught, logged with
 * Log::warning, and the command still exits successfully. A failed fetch must
 * never affect transaction save/edit — resolveUserCurrencyRate() simply keeps
 * falling back to the last known official_rates row (or 1.0) when this command
 * hasn't produced fresh data.
 *
 * Usage:
 *   php artisan bcv:fetch-rate
 *   php artisan bcv:fetch-rate --currency=VES
 */
class FetchBcvRateCommand extends Command
{
    protected $signature = 'bcv:fetch-rate {--currency=VES : Currency code to fetch the official rate for}';

    protected $description = 'Fetch the official BCV dollar rate from pydolarve.org and store it in official_rates';

    public function handle(BcvRateFetcher $fetcher): int
    {
        $currencyCode = (string) $this->option('currency');

        try {
            $result = $fetcher->fetchAndPersist($currencyCode);

            if ($result === null) {
                $this->warn("Could not fetch/persist official rate for {$currencyCode}. See logs for details.");
                return self::SUCCESS;
            }

            $this->info("Persisted official rate for {$currencyCode}: {$result->rate} (fetched_at={$result->fetched_at}).");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Defense in depth: BcvRateFetcher already catches everything internally,
            // but the command itself must never let an exception escape either.
            Log::warning('FetchBcvRateCommand: unexpected exception', ['error' => $e->getMessage()]);
            $this->warn('bcv:fetch-rate failed unexpectedly: ' . $e->getMessage());
            return self::SUCCESS;
        }
    }
}

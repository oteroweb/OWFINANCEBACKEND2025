<?php

namespace App\Services;

use App\Models\Entities\Currency;
use App\Models\Entities\OfficialRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the official BCV (Venezuelan Central Bank) dollar rate from pydolarve.org
 * and persists it into the official_rates history table.
 *
 * IMPORTANT: pydolarve.org's exact JSON response shape could NOT be verified live —
 * this dev/agent sandbox has no outbound network access (DNS resolution fails for
 * pydolarve.org). All shape-dependent logic is isolated in parseResponse() so it can
 * be corrected in one place once the real response is observed from an environment
 * with internet access (e.g. `php artisan bcv:fetch-rate` on the backend server).
 */
class BcvRateFetcher
{
    /**
     * Fetch the current rate and persist it as a new official_rates row for the
     * given currency code. Never throws. Returns null if the fetch/parse/persist
     * failed or if the currency code is not known.
     */
    public function fetchAndPersist(string $currencyCode = 'VES'): ?OfficialRate
    {
        $parsed = $this->fetch();
        if ($parsed === null) {
            return null;
        }

        try {
            $currency = Currency::where('code', $currencyCode)->first();
            if (!$currency) {
                Log::warning('BcvRateFetcher: currency code not found, skipping persist', [
                    'code' => $currencyCode,
                ]);
                return null;
            }

            return OfficialRate::create([
                'currency_id' => $currency->id,
                'rate' => $parsed['rate'],
                'source' => 'pydolarve',
                'fetched_at' => $parsed['fetched_at'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('BcvRateFetcher: failed to persist official rate', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Hit pydolarve.org and return ['rate' => float, 'fetched_at' => Carbon], or null
     * on any network/parse failure. Never throws — every failure path is caught,
     * logged via Log::warning, and returns null.
     */
    public function fetch(): ?array
    {
        try {
            $url = config('services.pydolarve.url');

            $response = Http::timeout(10)->acceptJson()->get($url);

            if (!$response->successful()) {
                Log::warning('BcvRateFetcher: non-success HTTP response from pydolarve.org', [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $json = $response->json();
            if (!is_array($json)) {
                Log::warning('BcvRateFetcher: pydolarve.org response was not valid JSON');
                return null;
            }

            return $this->parseResponse($json);
        } catch (\Throwable $e) {
            Log::warning('BcvRateFetcher: exception while fetching rate from pydolarve.org', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Map pydolarve.org's JSON payload into ['rate' => float, 'fetched_at' => Carbon].
     * Returns null when the shape is unrecognized or the rate is not a valid positive number.
     *
     * This is the ONLY place that assumes pydolarve's response shape — adjust here if the
     * real shape differs from what's assumed below.
     *
     * Assumed shape (unverified live, see class docblock): the `?page=bcv` query selects a
     * single monitor. We defensively accept either the monitor object at the top level
     * (`{"price": ..., "last_update": ...}`) or nested under a `monitors.bcv` key
     * (`{"monitors": {"bcv": {"price": ..., "last_update": ...}}}`), since both shapes have
     * been observed in different versions of similar community dollar-rate APIs.
     */
    private function parseResponse(array $json): ?array
    {
        $monitor = $json['monitors']['bcv'] ?? $json;

        $rate = $monitor['price'] ?? $monitor['rate'] ?? null;
        if (!is_numeric($rate)) {
            Log::warning('BcvRateFetcher: rate field missing or non-numeric in response', [
                'json' => $json,
            ]);
            return null;
        }

        $rate = (float) $rate;
        if ($rate <= 0) {
            Log::warning('BcvRateFetcher: rate is not positive', ['rate' => $rate]);
            return null;
        }

        $fetchedAt = null;
        $rawDate = $monitor['last_update'] ?? $monitor['fetched_at'] ?? null;
        if (is_string($rawDate) && $rawDate !== '') {
            try {
                $fetchedAt = Carbon::parse($rawDate);
            } catch (\Throwable $e) {
                $fetchedAt = null;
            }
        }

        return [
            'rate' => $rate,
            'fetched_at' => $fetchedAt ?: Carbon::now(),
        ];
    }
}

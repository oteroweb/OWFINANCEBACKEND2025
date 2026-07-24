<?php

namespace App\Services;

use App\Models\Entities\Currency;
use App\Models\Entities\OfficialRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the official BCV (Venezuelan Central Bank) dollar rate and persists it into
 * the official_rates history table.
 *
 * OWF-321: primary source is ve.dolarapi.com (verified live, clean JSON). pydolarve.org
 * was the original primary but never resolved (DNS down, confirmed from the prod server
 * itself and from multiple independent networks).
 *
 * OWF-329: pydolarve.org kept as a secondary fallback in case it ever comes back —
 * its response shape was NEVER verified live (DNS was down the whole time it was
 * primary), so parsePydolarveResponse() defensively accepts a couple of plausible
 * shapes seen in similar community dollar-rate APIs. If pydolarve.org starts resolving
 * again and this shape turns out wrong, fix it in one place there.
 *
 * OWF-330: EUR is a different calculation, not just a different endpoint. Every rate
 * this system stores means "units of that currency per 1 USD" (see
 * TransactionController::resolveUserCurrencyRate() — payments in a foreign-currency
 * account convert to the transaction's USD amount via amount_native / rate). dolarapi's
 * EUR endpoint reports VES per EUR directly (a Venezuela-specific quote), not USD per
 * EUR — so fetchAndPersistEur() derives the USD-relative rate this system needs by
 * dividing the VES/USD rate by the VES/EUR rate.
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
        if (strtoupper($currencyCode) === 'EUR') {
            return $this->fetchAndPersistEur();
        }

        $parsed = $this->fetch();
        if ($parsed === null) {
            return null;
        }

        return $this->persist($currencyCode, $parsed);
    }

    /**
     * OWF-330: EUR needs a derived cross-rate (see class docblock) — fetches VES/USD
     * (existing primary+fallback chain) and VES/EUR (dolarapi only, no fallback source
     * identified yet for EUR specifically), then divides them. If either fetch fails,
     * no row is persisted this cycle (same "never break, just skip" policy as the
     * VES/USD path).
     */
    private function fetchAndPersistEur(): ?OfficialRate
    {
        $usdVes = $this->fetch();
        if ($usdVes === null) {
            return null;
        }

        $eurVes = $this->fetchEurVesRate();
        if ($eurVes === null) {
            return null;
        }

        if ((float) $eurVes['rate'] <= 0) {
            return null;
        }

        return $this->persist('EUR', [
            'rate' => $usdVes['rate'] / $eurVes['rate'],
            'fetched_at' => $eurVes['fetched_at'],
            'source' => $eurVes['source'],
        ]);
    }

    /**
     * Hit dolarapi's EUR endpoint (VES per EUR) and return ['rate' => float,
     * 'fetched_at' => Carbon, 'source' => 'dolarapi'], or null on any failure. Reuses
     * parseDolarApiResponse() since the response shape is identical to the USD endpoint,
     * just with different values.
     */
    private function fetchEurVesRate(): ?array
    {
        try {
            $url = config('services.bcv_rate.eur_ves_url');

            $response = Http::timeout(10)->acceptJson()->get($url);

            if (!$response->successful()) {
                Log::warning('BcvRateFetcher: non-success HTTP response from dolarapi (EUR/VES)', [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $json = $response->json();
            if (!is_array($json)) {
                Log::warning('BcvRateFetcher: dolarapi (EUR/VES) response was not valid JSON');
                return null;
            }

            return $this->parseDolarApiResponse($json);
        } catch (\Throwable $e) {
            Log::warning('BcvRateFetcher: exception while fetching EUR/VES rate from dolarapi', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Persist a fetched/derived rate as a new official_rates row for the given currency
     * code. Never throws. Returns null if the currency code is not known or the insert
     * failed.
     */
    private function persist(string $currencyCode, array $parsed): ?OfficialRate
    {
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
                'source' => $parsed['source'],
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
     * Try the primary source (ve.dolarapi.com) first; if that fails for any reason,
     * fall back to the secondary source (pydolarve.org). Returns
     * ['rate' => float, 'fetched_at' => Carbon, 'source' => string], or null if both
     * sources failed. Never throws.
     */
    public function fetch(): ?array
    {
        $primary = $this->fetchFromDolarApi();
        if ($primary !== null) {
            return $primary;
        }

        Log::info('BcvRateFetcher: primary source (dolarapi) failed, trying fallback (pydolarve)');

        return $this->fetchFromPydolarve();
    }

    /**
     * Hit ve.dolarapi.com and return ['rate' => float, 'fetched_at' => Carbon,
     * 'source' => 'dolarapi'], or null on any network/parse failure.
     */
    private function fetchFromDolarApi(): ?array
    {
        try {
            $url = config('services.bcv_rate.url');

            $response = Http::timeout(10)->acceptJson()->get($url);

            if (!$response->successful()) {
                Log::warning('BcvRateFetcher: non-success HTTP response from dolarapi', [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $json = $response->json();
            if (!is_array($json)) {
                Log::warning('BcvRateFetcher: dolarapi response was not valid JSON');
                return null;
            }

            return $this->parseDolarApiResponse($json);
        } catch (\Throwable $e) {
            Log::warning('BcvRateFetcher: exception while fetching rate from dolarapi', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Hit pydolarve.org and return ['rate' => float, 'fetched_at' => Carbon,
     * 'source' => 'pydolarve'], or null on any network/parse failure.
     */
    private function fetchFromPydolarve(): ?array
    {
        try {
            $url = config('services.bcv_rate.fallback_url');

            $response = Http::timeout(10)->acceptJson()->get($url);

            if (!$response->successful()) {
                Log::warning('BcvRateFetcher: non-success HTTP response from pydolarve (fallback)', [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $json = $response->json();
            if (!is_array($json)) {
                Log::warning('BcvRateFetcher: pydolarve (fallback) response was not valid JSON');
                return null;
            }

            return $this->parsePydolarveResponse($json);
        } catch (\Throwable $e) {
            Log::warning('BcvRateFetcher: exception while fetching rate from pydolarve (fallback)', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Map ve.dolarapi.com's JSON payload into ['rate' => float, 'fetched_at' => Carbon,
     * 'source' => 'dolarapi']. Returns null when the shape is unrecognized or the rate
     * is not a valid positive number.
     *
     * This is the ONLY place that assumes the response shape — adjust here if the source
     * changes again. Confirmed live shape:
     *   {"moneda":"USD","fuente":"oficial","promedio":737.2321,
     *    "fechaActualizacion":"2026-07-22T00:00:00-04:00"}
     */
    private function parseDolarApiResponse(array $json): ?array
    {
        $rate = $json['promedio'] ?? null;
        if (!is_numeric($rate)) {
            Log::warning('BcvRateFetcher: rate field missing or non-numeric in dolarapi response', [
                'json' => $json,
            ]);
            return null;
        }

        $rate = (float) $rate;
        if ($rate <= 0) {
            Log::warning('BcvRateFetcher: dolarapi rate is not positive', ['rate' => $rate]);
            return null;
        }

        $fetchedAt = null;
        $rawDate = $json['fechaActualizacion'] ?? null;
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
            'source' => 'dolarapi',
        ];
    }

    /**
     * Map pydolarve.org's JSON payload into ['rate' => float, 'fetched_at' => Carbon,
     * 'source' => 'pydolarve']. Returns null when the shape is unrecognized or the rate
     * is not a valid positive number.
     *
     * UNVERIFIED LIVE (see class docblock — pydolarve.org's DNS was down the whole time
     * this fetcher existed). Defensively accepts either the monitor object at the top
     * level (`{"price": ..., "last_update": ...}`) or nested under a `monitors.bcv` key
     * (`{"monitors": {"bcv": {"price": ..., "last_update": ...}}}`), since both shapes
     * have been observed in similar community dollar-rate APIs. Fix here in one place
     * if pydolarve.org ever resolves again and the real shape differs.
     */
    private function parsePydolarveResponse(array $json): ?array
    {
        $monitor = $json['monitors']['bcv'] ?? $json;

        $rate = $monitor['price'] ?? $monitor['rate'] ?? null;
        if (!is_numeric($rate)) {
            Log::warning('BcvRateFetcher: rate field missing or non-numeric in pydolarve response', [
                'json' => $json,
            ]);
            return null;
        }

        $rate = (float) $rate;
        if ($rate <= 0) {
            Log::warning('BcvRateFetcher: pydolarve rate is not positive', ['rate' => $rate]);
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
            'source' => 'pydolarve',
        ];
    }
}

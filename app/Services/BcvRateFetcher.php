<?php

namespace App\Services;

use App\Models\Entities\Currency;
use App\Models\Entities\OfficialRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches the official BCV (Venezuelan Central Bank) dollar rate from ve.dolarapi.com
 * and persists it into the official_rates history table.
 *
 * OWF-321: originally targeted pydolarve.org, but that domain never resolved (confirmed
 * DNS failure from the prod server itself and from multiple independent networks, not a
 * sandbox restriction) — switched to ve.dolarapi.com, verified live against the real
 * endpoint. Response shape confirmed:
 *   {"moneda":"USD","fuente":"oficial","nombre":"Dólar","compra":null,"venta":null,
 *    "promedio":737.2321,"fechaActualizacion":"2026-07-22T00:00:00-04:00"}
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
                'source' => 'dolarapi',
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
     * Hit ve.dolarapi.com and return ['rate' => float, 'fetched_at' => Carbon], or null
     * on any network/parse failure. Never throws — every failure path is caught,
     * logged via Log::warning, and returns null.
     */
    public function fetch(): ?array
    {
        try {
            $url = config('services.bcv_rate.url');

            $response = Http::timeout(10)->acceptJson()->get($url);

            if (!$response->successful()) {
                Log::warning('BcvRateFetcher: non-success HTTP response from BCV rate source', [
                    'status' => $response->status(),
                ]);
                return null;
            }

            $json = $response->json();
            if (!is_array($json)) {
                Log::warning('BcvRateFetcher: BCV rate source response was not valid JSON');
                return null;
            }

            return $this->parseResponse($json);
        } catch (\Throwable $e) {
            Log::warning('BcvRateFetcher: exception while fetching rate from BCV rate source', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Map ve.dolarapi.com's JSON payload into ['rate' => float, 'fetched_at' => Carbon].
     * Returns null when the shape is unrecognized or the rate is not a valid positive number.
     *
     * This is the ONLY place that assumes the response shape — adjust here if the source
     * changes again. Confirmed live shape:
     *   {"moneda":"USD","fuente":"oficial","promedio":737.2321,
     *    "fechaActualizacion":"2026-07-22T00:00:00-04:00"}
     */
    private function parseResponse(array $json): ?array
    {
        $rate = $json['promedio'] ?? null;
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
        ];
    }
}

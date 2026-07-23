<?php

namespace Tests\Unit;

use App\Models\Entities\Currency;
use App\Models\Entities\OfficialRate;
use App\Services\BcvRateFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BcvRateFetcherTest extends TestCase
{
    use RefreshDatabase;

    private function makeVesCurrency(): Currency
    {
        return Currency::factory()->create(['code' => 'VES']);
    }

    public function test_fetch_returns_rate_and_fetched_at_on_success(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response([
                'promedio' => 40.25,
                'fechaActualizacion' => '2026-07-20T09:00:00-04:00',
            ], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetch();

        $this->assertIsArray($result);
        $this->assertEquals(40.25, $result['rate']);
        $this->assertNotNull($result['fetched_at']);
    }

    public function test_fetch_returns_null_on_missing_rate_field(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response(['fechaActualizacion' => '2026-07-20T09:00:00-04:00'], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_non_numeric_rate(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response(['promedio' => 'not-a-number'], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_negative_rate(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response(['promedio' => -5], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_500_response(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response([], 500),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_connection_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timed out');
        });

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_malformed_json(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response('not json at all', 200, ['Content-Type' => 'text/plain']),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_and_persist_creates_official_rate_row_on_success(): void
    {
        $currency = $this->makeVesCurrency();
        Http::fake([
            'dolarapi.com/*' => Http::response([
                'promedio' => 41.10,
                'fechaActualizacion' => '2026-07-20T16:00:00-04:00',
            ], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetchAndPersist('VES');

        $this->assertInstanceOf(OfficialRate::class, $result);
        $this->assertDatabaseHas('official_rates', [
            'currency_id' => $currency->id,
            'source' => 'dolarapi',
        ]);
        $this->assertEquals(41.10, (float) OfficialRate::first()->rate);
    }

    public function test_fetch_and_persist_does_not_persist_on_failure(): void
    {
        $this->makeVesCurrency();
        Http::fake([
            'dolarapi.com/*' => Http::response([], 500),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetchAndPersist('VES');

        $this->assertNull($result);
        $this->assertDatabaseCount('official_rates', 0);
    }

    public function test_fetch_and_persist_returns_null_when_currency_code_unknown(): void
    {
        // No VES currency created at all.
        Http::fake([
            'dolarapi.com/*' => Http::response(['promedio' => 40], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetchAndPersist('VES');

        $this->assertNull($result);
        $this->assertDatabaseCount('official_rates', 0);
    }

    // ── OWF-329: fallback a pydolarve.org cuando dolarapi falla ──────────────

    public function test_fetch_falls_back_to_pydolarve_when_dolarapi_fails(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response([], 500),
            'pydolarve.org/*' => Http::response([
                'price' => 39.75,
                'last_update' => '2026-07-20T09:00:00-04:00',
            ], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetch();

        $this->assertIsArray($result);
        $this->assertEquals(39.75, $result['rate']);
        $this->assertEquals('pydolarve', $result['source']);
    }

    public function test_fetch_falls_back_to_pydolarve_nested_monitors_shape(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response([], 500),
            'pydolarve.org/*' => Http::response([
                'monitors' => ['bcv' => ['price' => 38.90, 'last_update' => '2026-07-20T09:00:00-04:00']],
            ], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetch();

        $this->assertIsArray($result);
        $this->assertEquals(38.90, $result['rate']);
        $this->assertEquals('pydolarve', $result['source']);
    }

    public function test_fetch_does_not_try_fallback_when_primary_succeeds(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response(['promedio' => 40.25], 200),
            'pydolarve.org/*' => Http::response(['price' => 999], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetch();

        $this->assertEquals(40.25, $result['rate']);
        $this->assertEquals('dolarapi', $result['source']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'pydolarve.org'));
    }

    public function test_fetch_returns_null_when_both_sources_fail(): void
    {
        Http::fake([
            'dolarapi.com/*' => Http::response([], 500),
            'pydolarve.org/*' => Http::response([], 500),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_and_persist_uses_pydolarve_as_source_when_it_was_the_fallback(): void
    {
        $currency = $this->makeVesCurrency();
        Http::fake([
            'dolarapi.com/*' => Http::response([], 500),
            'pydolarve.org/*' => Http::response(['price' => 39.00], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetchAndPersist('VES');

        $this->assertInstanceOf(OfficialRate::class, $result);
        $this->assertDatabaseHas('official_rates', [
            'currency_id' => $currency->id,
            'source' => 'pydolarve',
        ]);
    }
}

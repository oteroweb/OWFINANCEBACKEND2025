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
            'pydolarve.org/*' => Http::response([
                'price' => 40.25,
                'last_update' => '20/07/2026, 09:00 AM',
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
            'pydolarve.org/*' => Http::response(['last_update' => '20/07/2026, 09:00 AM'], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_non_numeric_rate(): void
    {
        Http::fake([
            'pydolarve.org/*' => Http::response(['price' => 'not-a-number'], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_negative_rate(): void
    {
        Http::fake([
            'pydolarve.org/*' => Http::response(['price' => -5], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_returns_null_on_500_response(): void
    {
        Http::fake([
            'pydolarve.org/*' => Http::response([], 500),
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
            'pydolarve.org/*' => Http::response('not json at all', 200, ['Content-Type' => 'text/plain']),
        ]);

        $fetcher = new BcvRateFetcher();
        $this->assertNull($fetcher->fetch());
    }

    public function test_fetch_and_persist_creates_official_rate_row_on_success(): void
    {
        $currency = $this->makeVesCurrency();
        Http::fake([
            'pydolarve.org/*' => Http::response(['price' => 41.10, 'last_update' => '20/07/2026, 04:00 PM'], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetchAndPersist('VES');

        $this->assertInstanceOf(OfficialRate::class, $result);
        $this->assertDatabaseHas('official_rates', [
            'currency_id' => $currency->id,
            'source' => 'pydolarve',
        ]);
        $this->assertEquals(41.10, (float) OfficialRate::first()->rate);
    }

    public function test_fetch_and_persist_does_not_persist_on_failure(): void
    {
        $this->makeVesCurrency();
        Http::fake([
            'pydolarve.org/*' => Http::response([], 500),
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
            'pydolarve.org/*' => Http::response(['price' => 40], 200),
        ]);

        $fetcher = new BcvRateFetcher();
        $result = $fetcher->fetchAndPersist('VES');

        $this->assertNull($result);
        $this->assertDatabaseCount('official_rates', 0);
    }
}

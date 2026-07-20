<?php

namespace Tests\Feature\Console;

use App\Models\Entities\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FetchBcvRateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_inserts_row_on_successful_fetch(): void
    {
        Currency::factory()->create(['code' => 'VES']);
        Http::fake([
            'pydolarve.org/*' => Http::response(['price' => 39.99, 'last_update' => '20/07/2026, 09:00 AM'], 200),
        ]);

        $exitCode = Artisan::call('bcv:fetch-rate');

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseHas('official_rates', ['source' => 'pydolarve']);
    }

    public function test_command_does_not_explode_when_http_fails(): void
    {
        Currency::factory()->create(['code' => 'VES']);
        Http::fake([
            'pydolarve.org/*' => Http::response([], 500),
        ]);

        $exitCode = Artisan::call('bcv:fetch-rate');

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseCount('official_rates', 0);
    }

    public function test_command_does_not_explode_on_connection_exception(): void
    {
        Currency::factory()->create(['code' => 'VES']);
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('DNS failure');
        });

        $exitCode = Artisan::call('bcv:fetch-rate');

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseCount('official_rates', 0);
    }

    public function test_command_accepts_currency_option(): void
    {
        Currency::factory()->create(['code' => 'USD']);
        Http::fake([
            'pydolarve.org/*' => Http::response(['price' => 1.0, 'last_update' => '20/07/2026, 09:00 AM'], 200),
        ]);

        $exitCode = Artisan::call('bcv:fetch-rate', ['--currency' => 'USD']);

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseHas('official_rates', ['source' => 'pydolarve']);
    }
}

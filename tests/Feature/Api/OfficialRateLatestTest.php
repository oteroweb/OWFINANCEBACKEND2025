<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Currency;
use App\Models\Entities\OfficialRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-337: "Tasa oficial (BCV) hoy" en SmartTransactionModal.vue nunca se auto-completaba
 * al crear una transacción (solo se restauraba al editar una ya guardada) porque no existía
 * ningún endpoint que expusiera la tasa automática de official_rates (poblada por
 * BcvRateFetcher, ver OWF-321) al frontend.
 */
class OfficialRateLatestTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_latest_official_rate_for_currency(): void
    {
        $currency = Currency::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        OfficialRate::create([
            'currency_id' => $currency->id,
            'rate' => 700.0,
            'source' => 'dolarapi',
            'fetched_at' => now()->subHours(8),
        ]);
        OfficialRate::create([
            'currency_id' => $currency->id,
            'rate' => 737.2321,
            'source' => 'dolarapi',
            'fetched_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/user-currencies/official-latest?currency_id=' . $currency->id);

        $response->assertStatus(200)->assertJson([
            'status' => 'OK',
            'data' => ['rate' => 737.2321, 'source' => 'dolarapi'],
        ]);
    }

    public function test_returns_null_data_when_no_official_rate_exists(): void
    {
        $currency = Currency::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/user-currencies/official-latest?currency_id=' . $currency->id);

        $response->assertStatus(200)->assertJson(['status' => 'OK', 'data' => null]);
    }

    public function test_requires_valid_currency_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/user-currencies/official-latest');
        $response->assertStatus(400);
    }
}

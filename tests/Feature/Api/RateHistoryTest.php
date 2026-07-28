<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Currency;
use App\Models\Entities\OfficialRate;
use App\Models\Entities\UserCurrency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-346: historial de tasas (paralela y BCV) para el picker "elegir tasa anterior"
 * en SmartTransactionModal.vue — antes solo se veía la más reciente de cada una.
 */
class RateHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_parallel_history_returns_only_current_users_rows_newest_first(): void
    {
        $currency = Currency::factory()->create();
        $user = User::factory()->create();
        $other = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $older = UserCurrency::create(['user_id' => $user->id, 'currency_id' => $currency->id, 'current_rate' => 800, 'is_current' => false]);
        $newer = UserCurrency::create(['user_id' => $user->id, 'currency_id' => $currency->id, 'current_rate' => 834, 'is_current' => true]);
        UserCurrency::create(['user_id' => $other->id, 'currency_id' => $currency->id, 'current_rate' => 999, 'is_current' => true]);
        // Eloquent pisa `updated_at` con "ahora" al crear, sin importar lo que se pase en el
        // array — forzamos timestamps distintos vía query builder para probar el orden real.
        \Illuminate\Support\Facades\DB::table('user_currencies')->where('id', $older->id)->update(['updated_at' => now()->subDays(2)]);
        \Illuminate\Support\Facades\DB::table('user_currencies')->where('id', $newer->id)->update(['updated_at' => now()]);

        $res = $this->getJson('/api/v1/user-currencies/history?currency_id=' . $currency->id);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $rows = $res->json('data');
        $this->assertCount(2, $rows);
        $this->assertEquals(834, $rows[0]['current_rate']);
        $this->assertEquals(800, $rows[1]['current_rate']);
    }

    public function test_parallel_history_empty_when_no_rows(): void
    {
        $currency = Currency::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/user-currencies/history?currency_id=' . $currency->id);

        $res->assertStatus(200)->assertJson(['status' => 'OK', 'data' => []]);
    }

    public function test_parallel_history_requires_valid_currency_id(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/user-currencies/history');

        $res->assertStatus(400);
    }

    public function test_official_history_returns_newest_first_limited(): void
    {
        $currency = Currency::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        OfficialRate::create(['currency_id' => $currency->id, 'rate' => 700, 'source' => 'dolarapi', 'fetched_at' => now()->subDays(1)]);
        OfficialRate::create(['currency_id' => $currency->id, 'rate' => 738, 'source' => 'dolarapi', 'fetched_at' => now()]);

        $res = $this->getJson('/api/v1/user-currencies/official-history?currency_id=' . $currency->id);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $rows = $res->json('data');
        $this->assertCount(2, $rows);
        $this->assertEquals(738, $rows[0]['rate']);
        $this->assertEquals(700, $rows[1]['rate']);
    }

    public function test_official_history_requires_valid_currency_id(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/user-currencies/official-history');

        $res->assertStatus(400);
    }
}

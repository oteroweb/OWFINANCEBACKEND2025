<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Currency;
use App\Models\Entities\UserCurrency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression guard for the Domain B design decision documented in
 * sdd/transaction-commission-and-rate-persistence/design: UserCurrencyController::store()
 * already accepts and persists `is_current` (no backend change was needed there). The
 * frontend fix (persistOfficialRateIfNeeded() sending is_current: true) depends on this
 * contract holding; this test guards against a future regression breaking it.
 */
class UserCurrencyIsCurrentPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_persists_is_current_true(): void
    {
        $currency = Currency::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/user_currencies', [
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'current_rate' => 36.5,
            'is_current' => true,
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertDatabaseHas('user_currencies', [
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'current_rate' => 36.5,
            'is_current' => 1,
        ]);

        // And it becomes the rate the transaction resolver picks up as a precedence-2 source.
        $record = UserCurrency::where('user_id', $user->id)->where('currency_id', $currency->id)->first();
        $this->assertTrue((bool) $record->is_current);
    }

    /**
     * OWF-337: bug real reportado por el usuario — creaba un movimiento nuevo y "Tasa
     * paralelo (actual)" mostraba un valor viejo/de otra transacción en vez del último
     * guardado. Causa: store() nunca desmarcaba is_current en otras filas de la misma
     * moneda (a diferencia de UserRateService::applyFromPayment(), que sí lo hacía al
     * guardar una transacción) — podían coexistir varias filas "actuales" y cuál ganaba
     * en el frontend dependía del orden de retorno, no de cuál se guardó último.
     */
    public function test_store_with_is_current_unsets_previous_current_for_same_currency(): void
    {
        $currency = Currency::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $old = UserCurrency::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'current_rate' => 834,
            'is_current' => true,
            'is_official' => false,
        ]);

        $response = $this->postJson('/api/v1/user_currencies', [
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'current_rate' => 866.1183051,
            'is_current' => true,
        ]);
        $response->assertStatus(200)->assertJson(['status' => 'OK']);

        $this->assertFalse((bool) $old->fresh()->is_current);
        $this->assertDatabaseHas('user_currencies', [
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'current_rate' => 866.1183051,
            'is_current' => 1,
        ]);
        $this->assertEquals(
            1,
            UserCurrency::where('user_id', $user->id)->where('currency_id', $currency->id)->where('is_current', true)->count()
        );
    }

    public function test_update_with_is_current_unsets_previous_current_for_same_currency(): void
    {
        $currency = Currency::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $old = UserCurrency::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'current_rate' => 834,
            'is_current' => true,
            'is_official' => false,
        ]);
        $other = UserCurrency::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'current_rate' => 866,
            'is_current' => false,
            'is_official' => false,
        ]);

        $response = $this->putJson('/api/v1/user-currencies/' . $other->id, ['is_current' => true]);
        $response->assertStatus(200)->assertJson(['status' => 'OK']);

        $this->assertFalse((bool) $old->fresh()->is_current);
        $this->assertTrue((bool) $other->fresh()->is_current);
    }
}

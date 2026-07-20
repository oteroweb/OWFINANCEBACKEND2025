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
}

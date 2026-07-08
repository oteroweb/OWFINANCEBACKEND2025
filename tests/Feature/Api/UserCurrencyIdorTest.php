<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Entities\Currency;
use App\Models\Entities\UserCurrency;

class UserCurrencyIdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_read_another_users_rates_via_user_id_param()
    {
        $currency = Currency::factory()->create();
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        UserCurrency::create([
            'user_id' => $victim->id,
            'currency_id' => $currency->id,
            'current_rate' => 42.5,
            'is_current' => true,
            'is_official' => true,
        ]);

        $response = $this->getJson('/api/v1/user-currencies/?user_id=' . $victim->id);
        $response->assertStatus(403);
    }

    public function test_omitting_user_id_scopes_to_self_not_all_users()
    {
        $currency = Currency::factory()->create();
        $other = User::factory()->create();
        $self = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($self, ['*']);

        UserCurrency::create([
            'user_id' => $other->id,
            'currency_id' => $currency->id,
            'current_rate' => 99,
            'is_current' => true,
            'is_official' => true,
        ]);
        UserCurrency::create([
            'user_id' => $self->id,
            'currency_id' => $currency->id,
            'current_rate' => 1.5,
            'is_current' => true,
            'is_official' => true,
        ]);

        $response = $this->getJson('/api/v1/user-currencies/');
        $response->assertStatus(200);
        $ids = collect($response->json('data.data'))->pluck('user_id')->all();
        $this->assertNotContains($other->id, $ids);
    }

    public function test_user_cannot_create_rate_for_another_user()
    {
        $currency = Currency::factory()->create();
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->postJson('/api/v1/user-currencies/', [
            'user_id' => $victim->id,
            'currency_id' => $currency->id,
            'current_rate' => 10,
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_currencies', [
            'currency_id' => $currency->id,
            'user_id' => $attacker->id,
        ]);
        $this->assertDatabaseMissing('user_currencies', [
            'currency_id' => $currency->id,
            'user_id' => $victim->id,
        ]);
    }

    public function test_user_cannot_update_or_delete_another_users_rate()
    {
        $currency = Currency::factory()->create();
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $rate = UserCurrency::create([
            'user_id' => $victim->id,
            'currency_id' => $currency->id,
            'current_rate' => 42.5,
            'is_current' => true,
            'is_official' => true,
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $this->putJson('/api/v1/user-currencies/' . $rate->id, ['current_rate' => 1])
            ->assertStatus(403);
        $this->deleteJson('/api/v1/user-currencies/' . $rate->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('user_currencies', ['id' => $rate->id, 'current_rate' => 42.5]);
    }
}

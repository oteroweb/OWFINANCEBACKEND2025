<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Entities\Currency;
use App\Models\Entities\Account;
use App\Models\Entities\UserCurrency;
use Laravel\Sanctum\Sanctum;

/**
 * Módulo: User Profile, Settings, Financial Profile, UserCurrencies.
 * Cubre: lectura/escritura de perfil, preferencias, perfil financiero
 *        y tasas de cambio del usuario.
 */
class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    // ── Perfil ────────────────────────────────────────────────────────────────

    public function test_get_profile_returns_authenticated_user()
    {
        $user = User::factory()->create(['name' => 'Ana García']);
        Sanctum::actingAs($user, ['*']);

        $res = $this->getJson('/api/v1/user/profile');
        $res->assertStatus(200)->assertJsonPath('data.name', 'Ana García');
    }

    public function test_update_profile_persists_changes()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $res = $this->putJson('/api/v1/user/profile', [
            'name'       => 'Nombre Nuevo',
            'city'       => 'Caracas',
            'occupation' => 'Desarrollador',
        ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nombre Nuevo']);
    }

    public function test_profile_is_isolated_per_user()
    {
        $user1 = User::factory()->create(['name' => 'Usuario 1']);
        $user2 = User::factory()->create(['name' => 'Usuario 2']);

        Sanctum::actingAs($user1, ['*']);
        $res = $this->getJson('/api/v1/user/profile');
        $res->assertJsonPath('data.name', 'Usuario 1');

        Sanctum::actingAs($user2, ['*']);
        $res = $this->getJson('/api/v1/user/profile');
        $res->assertJsonPath('data.name', 'Usuario 2');
    }

    // ── Settings / Preferencias ───────────────────────────────────────────────

    public function test_get_and_update_user_settings()
    {
        $this->getJson('/api/v1/user/settings')->assertStatus(200);

        $this->putJson('/api/v1/user/settings', [
            'layout_mode'    => 'lite',
            'notifications'  => true,
            'strict_budget'  => false,
        ])->assertStatus(200);
    }

    // ── Financial Profile ─────────────────────────────────────────────────────

    public function test_get_financial_profile()
    {
        $res = $this->getJson('/api/v1/user/financial-profile');
        $res->assertStatus(200);
    }

    public function test_update_financial_profile()
    {
        $res = $this->putJson('/api/v1/user/financial-profile', [
            'monthly_income' => 15000.00,
            'financial_goal' => 'saving',
        ]);
        $res->assertStatus(200);
    }

    // ── UserCurrencies (tasas de cambio personales) ───────────────────────────

    public function test_user_currency_crud()
    {
        $currency = Currency::factory()->create(['code' => 'VES']);

        $authUser = \App\Models\User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($authUser, ['*']);

        // Create — user_id se toma del autenticado vía el controller
        $res = $this->postJson('/api/v1/user-currencies/', [
            'user_id'      => $authUser->id,
            'currency_id'  => $currency->id,
            'current_rate' => 40.50,
            'is_current'   => true,
            'is_official'  => false,
        ]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        // List — paginated: data.data contains items
        $list = $this->getJson('/api/v1/user-currencies/?user_id=' . $authUser->id);
        $list->assertStatus(200);
        $items = $list->json('data.data') ?? $list->json('data');
        $this->assertTrue(collect($items)->contains('id', $id));

        // Update rate
        $this->putJson('/api/v1/user-currencies/' . $id, [
            'current_rate' => 42.00,
            'is_current'   => true,
        ])->assertStatus(200);

        // Delete
        $this->deleteJson('/api/v1/user-currencies/' . $id)->assertStatus(200);
    }

    public function test_user_currency_isolated_between_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $currency = Currency::factory()->create(['code' => 'EUR']);

        Sanctum::actingAs($user1, ['*']);
        $uc = UserCurrency::create([
            'user_id'      => $user1->id,
            'currency_id'  => $currency->id,
            'current_rate' => 1.10,
            'is_current'   => true,
        ]);

        Sanctum::actingAs($user2, ['*']);
        $list = $this->getJson('/api/v1/user-currencies/');
        $this->assertFalse(collect($list->json('data'))->contains('id', $uc->id));
    }

    public function test_backward_compat_underscore_alias()
    {
        // Ambos prefijos deben funcionar (user-currencies y user_currencies)
        $this->getJson('/api/v1/user_currencies/')->assertStatus(200);
    }
}

<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Entities\Provider;

class ProviderScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_can_list_own_and_global_providers_but_not_others()
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $global = Provider::factory()->create(['user_id' => null, 'active' => 1]);
        $own = Provider::factory()->create(['user_id' => $attacker->id, 'active' => 1]);
        $victimProvider = Provider::factory()->create(['user_id' => $victim->id, 'active' => 1]);

        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->getJson('/api/v1/providers/');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($global->id, $ids);
        $this->assertContains($own->id, $ids);
        $this->assertNotContains($victimProvider->id, $ids);
    }

    public function test_non_admin_cannot_list_another_users_providers_via_user_id_param()
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $victimProvider = Provider::factory()->create(['user_id' => $victim->id, 'active' => 1]);

        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->getJson('/api/v1/providers/?user_id=' . $victim->id);
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($victimProvider->id, $ids);
    }

    public function test_non_admin_create_provider_is_forced_to_own_user_id()
    {
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->postJson('/api/v1/providers/', [
            'name' => 'Mercado del Barrio',
            'user_id' => $victim->id,
        ]);
        $response->assertStatus(201);

        $this->assertDatabaseHas('providers', [
            'name' => 'Mercado del Barrio',
            'user_id' => $attacker->id,
        ]);
        $this->assertDatabaseMissing('providers', [
            'name' => 'Mercado del Barrio',
            'user_id' => $victim->id,
        ]);
    }

    public function test_non_admin_can_create_provider_with_only_a_name()
    {
        $user = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/providers/', ['name' => 'Panadería Central']);
        $response->assertStatus(201)->assertJson(['status' => 'OK']);
        $this->assertDatabaseHas('providers', ['name' => 'Panadería Central', 'user_id' => $user->id]);
    }

    public function test_non_admin_cannot_access_admin_only_provider_routes()
    {
        $user = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/providers/all')->assertStatus(403);
        $this->putJson('/api/v1/providers/1', ['name' => 'x'])->assertStatus(403);
        $this->deleteJson('/api/v1/providers/1')->assertStatus(403);
        $this->patchJson('/api/v1/providers/1/status')->assertStatus(403);
    }
}

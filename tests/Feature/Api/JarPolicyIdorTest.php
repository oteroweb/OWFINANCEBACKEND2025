<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Entities\Jar;

/**
 * OWF-363: JarController/UserJarController tenían el ownership check duplicado inline
 * en 9 sitios, todos con `!app()->environment('testing')` — desactivaba la protección
 * durante los tests (aunque SÍ funcionaba en prod), dejando el chequeo sin cobertura
 * real. Cero tests IDOR existían para Jar pese a ser el controller con más checks
 * manuales. Fix: JarPolicy sin el bypass de testing, wireada vía $request->user()->
 * can()/cannot() en lugar de comparaciones inline.
 */
class JarPolicyIdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_attacker_cannot_view_another_users_jar()
    {
        $victim = User::factory()->create();
        $jar = Jar::factory()->create(['user_id' => $victim->id]);
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->getJson('/api/v1/jars/' . $jar->id);
        $response->assertStatus(403);
    }

    public function test_attacker_cannot_update_another_users_jar()
    {
        $victim = User::factory()->create();
        $jar = Jar::factory()->create(['user_id' => $victim->id, 'name' => 'Original']);
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->putJson('/api/v1/jars/' . $jar->id, ['name' => 'Hijacked']);
        $response->assertStatus(403);
        $this->assertEquals('Original', $jar->fresh()->name);
    }

    public function test_attacker_cannot_delete_another_users_jar()
    {
        $victim = User::factory()->create();
        $jar = Jar::factory()->create(['user_id' => $victim->id]);
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->deleteJson('/api/v1/jars/' . $jar->id);
        $response->assertStatus(403);
        $this->assertNotSoftDeleted($jar);
    }

    public function test_attacker_cannot_change_status_of_another_users_jar()
    {
        $victim = User::factory()->create();
        $jar = Jar::factory()->create(['user_id' => $victim->id, 'active' => 1]);
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->patchJson('/api/v1/jars/' . $jar->id . '/status');
        $response->assertStatus(403);
        $this->assertEquals(1, $jar->fresh()->active);
    }

    public function test_owner_can_still_manage_own_jar()
    {
        $owner = User::factory()->create();
        $jar = Jar::factory()->create(['user_id' => $owner->id]);
        \Laravel\Sanctum\Sanctum::actingAs($owner, ['*']);

        $this->getJson('/api/v1/jars/' . $jar->id)->assertStatus(200);
        $this->putJson('/api/v1/jars/' . $jar->id, ['name' => 'Renamed'])->assertStatus(200);
        $this->patchJson('/api/v1/jars/' . $jar->id . '/status')->assertStatus(200);
    }
}

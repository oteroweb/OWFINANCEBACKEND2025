<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Jar;

class JarTest extends TestCase
{
    use RefreshDatabase;

    public function test_jar_crud_flow()
    {
        $data = [
            'name' => 'Test Jar',
        ];
        $createResponse = $this->postJson('/api/v1/jars/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $jarId = $createResponse->json('data.id') ?? Jar::where('name', 'Test Jar')->first()->id;

        $findResponse = $this->getJson('/api/v1/jars/' . $jarId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $jarId, 'name' => 'Test Jar']]);

        $updateData = ['name' => 'Updated Jar'];
        $updateResponse = $this->putJson('/api/v1/jars/' . $jarId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Jar']]);

        $listResponse = $this->getJson('/api/v1/jars/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $jarId));

        $deleteResponse = $this->deleteJson('/api/v1/jars/' . $jarId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('jars', ['id' => $jarId]);
    }

    public function test_get_all_active_jars()
    {
        Jar::factory()->create(['active' => 1]);
        Jar::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/jars/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $j) {
                $this->assertArrayHasKey('id', $j);
                $this->assertArrayHasKey('name', $j);
                $this->assertArrayHasKey('active', $j);
                $this->assertEquals(1, $j['active']);
            }
        }
    }

    public function test_get_jars_with_trashed()
    {
        $jar = Jar::factory()->create();
        $jar->delete();
        $response = $this->getJson('/api/v1/jars/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $j) {
                $this->assertArrayHasKey('id', $j);
                $this->assertArrayHasKey('name', $j);
                $this->assertArrayHasKey('active', $j);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($jar->id));
        }
    }

    public function test_change_status_jar()
    {
        $jar = Jar::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/jars/' . $jar->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Jar updated')]);
        $jar->refresh();
        $this->assertEquals(0, $jar->active);
    }

    /**
     * Regression guard for OWF-061: the "sum of active percent jars <= 100%"
     * invariant must hold. A second active percent jar that would push the
     * total over 100% must be rejected (422) and must NOT persist.
     */
    public function test_percent_sum_invariant_blocks_over_100_percent()
    {
        $user = $this->actingAsAdmin();

        $base = '/api/v1/users/' . $user->id . '/jars';

        // First 60% active percent jar is allowed.
        $this->postJson($base, [
            'name' => 'Ahorro', 'type' => 'percent', 'percent' => 60, 'is_active' => true,
        ])->assertStatus(200)->assertJson(['status' => 'OK']);

        // A second 60% active percent jar would total 120% -> rejected.
        $this->postJson($base, [
            'name' => 'Gastos', 'type' => 'percent', 'percent' => 60, 'is_active' => true,
        ])->assertStatus(422)->assertJson(['status' => 'FAILED']);

        // Exactly one active percent jar must exist for this user.
        $this->assertSame(
            1,
            Jar::where('user_id', $user->id)->where('type', 'percent')->where('active', 1)->count()
        );
    }

    /**
     * Regression guard for OWF-066: updating a percent jar while DEACTIVATING it
     * must not be blocked by the percent-sum cap (its own percent no longer counts).
     */
    public function test_percent_sum_skipped_when_deactivating_jar()
    {
        $user = $this->actingAsAdmin();

        $base = '/api/v1/users/' . $user->id . '/jars';

        // Create two 50% active jars (total exactly 100%, allowed).
        $jarA = $this->postJson($base, [
            'name' => 'A', 'type' => 'percent', 'percent' => 50, 'is_active' => true,
        ])->json('data.id');
        $this->postJson($base, [
            'name' => 'B', 'type' => 'percent', 'percent' => 50, 'is_active' => true,
        ])->assertStatus(200);

        // Now deactivate jarA and change its percent -> must succeed even though
        // percent changed, because the jar will no longer be active.
        $this->putJson($base . '/' . $jarA, [
            'name' => 'A', 'type' => 'percent', 'percent' => 80, 'is_active' => false,
        ])->assertStatus(200)->assertJson(['status' => 'OK']);
    }
}

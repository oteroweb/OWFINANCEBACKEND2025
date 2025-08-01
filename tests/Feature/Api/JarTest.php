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
}

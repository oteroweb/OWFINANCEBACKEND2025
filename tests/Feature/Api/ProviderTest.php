<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Provider;

class ProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_crud_flow()
    {
        $data = [
            'name' => 'Test Provider',
            'email' => 'provider@example.com',
            'phone' => '1234567890',
            'address' => 'Fake Street 123',
        ];
        $createResponse = $this->postJson('/api/v1/providers/', $data);
        $createResponse->assertStatus(201)
            ->assertJson(['status' => 'OK']);
        $providerId = $createResponse->json('data.id') ?? Provider::where('email', 'provider@example.com')->first()->id;

        $findResponse = $this->getJson('/api/v1/providers/' . $providerId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $providerId, 'name' => 'Test Provider']]);

        $updateData = ['name' => 'Updated Provider'];
        $updateResponse = $this->putJson('/api/v1/providers/' . $providerId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Provider']]);

        $listResponse = $this->getJson('/api/v1/providers/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'email', 'phone', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $providerId));

        $deleteResponse = $this->deleteJson('/api/v1/providers/' . $providerId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('providers', ['id' => $providerId]);
    }

    public function test_get_all_active_providers()
    {
        Provider::factory()->create(['active' => 1]);
        Provider::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/providers/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $p) {
                $this->assertArrayHasKey('id', $p);
                $this->assertArrayHasKey('name', $p);
                $this->assertArrayHasKey('email', $p);
                $this->assertArrayHasKey('phone', $p);
                $this->assertArrayHasKey('active', $p);
                $this->assertEquals(1, $p['active']);
            }
        }
    }

    public function test_get_providers_with_trashed()
    {
        $provider = Provider::factory()->create();
        $provider->delete();
        $response = $this->getJson('/api/v1/providers/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $p) {
                $this->assertArrayHasKey('id', $p);
                $this->assertArrayHasKey('name', $p);
                $this->assertArrayHasKey('email', $p);
                $this->assertArrayHasKey('phone', $p);
                $this->assertArrayHasKey('active', $p);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($provider->id));
        }
    }

    public function test_change_status_provider()
    {
        $provider = Provider::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/providers/' . $provider->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Provider updated')]);
        $provider->refresh();
        $this->assertEquals(0, $provider->active);
    }
}

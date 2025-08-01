<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Client;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_crud_flow()
    {
        $data = [
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'phone' => '1234567890',
        ];
        $createResponse = $this->postJson('/api/v1/clients/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $clientId = $createResponse->json('data.id') ?? Client::where('email', 'client@example.com')->first()->id;

        $findResponse = $this->getJson('/api/v1/clients/' . $clientId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $clientId, 'name' => 'Test Client']]);

        $updateData = ['name' => 'Updated Client'];
        $updateResponse = $this->putJson('/api/v1/clients/' . $clientId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Client']]);

        $listResponse = $this->getJson('/api/v1/clients/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'email', 'phone', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $clientId));

        $deleteResponse = $this->deleteJson('/api/v1/clients/' . $clientId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('clients', ['id' => $clientId]);
    }

    public function test_get_all_active_clients()
    {
        Client::factory()->create(['active' => 1]);
        Client::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/clients/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $c) {
                $this->assertArrayHasKey('id', $c);
                $this->assertArrayHasKey('name', $c);
                $this->assertArrayHasKey('email', $c);
                $this->assertArrayHasKey('phone', $c);
                $this->assertArrayHasKey('active', $c);
                $this->assertEquals(1, $c['active']);
            }
        }
    }

    public function test_get_clients_with_trashed()
    {
        $client = Client::factory()->create();
        $client->delete();
        $response = $this->getJson('/api/v1/clients/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $c) {
                $this->assertArrayHasKey('id', $c);
                $this->assertArrayHasKey('name', $c);
                $this->assertArrayHasKey('email', $c);
                $this->assertArrayHasKey('phone', $c);
                $this->assertArrayHasKey('active', $c);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($client->id));
        }
    }

    public function test_change_status_client()
    {
        $client = Client::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/clients/' . $client->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Client updated')]);
        $client->refresh();
        $this->assertEquals(0, $client->active);
    }
}

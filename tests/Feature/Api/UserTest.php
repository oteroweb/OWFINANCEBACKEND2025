<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_crud_flow()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password',
        ];
        $createResponse = $this->postJson('/api/v1/users/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $userId = $createResponse->json('data.id') ?? User::where('email', 'user@example.com')->first()->id;

        $findResponse = $this->getJson('/api/v1/users/' . $userId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $userId, 'name' => 'Test User']]);

        $updateData = ['name' => 'Updated User'];
        $updateResponse = $this->putJson('/api/v1/users/' . $userId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated User']]);

        $listResponse = $this->getJson('/api/v1/users/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'email', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $userId));

        $deleteResponse = $this->deleteJson('/api/v1/users/' . $userId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $userId]);
    }

    public function test_get_all_active_users()
    {
        User::factory()->create(['active' => 1]);
        User::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/users/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $u) {
                $this->assertArrayHasKey('id', $u);
                $this->assertArrayHasKey('name', $u);
                $this->assertArrayHasKey('email', $u);
                $this->assertArrayHasKey('active', $u);
                $this->assertEquals(1, $u['active']);
            }
        }
    }

    public function test_get_users_with_trashed()
    {
        $user = User::factory()->create();
        $user->delete();
        $response = $this->getJson('/api/v1/users/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $u) {
                $this->assertArrayHasKey('id', $u);
                $this->assertArrayHasKey('name', $u);
                $this->assertArrayHasKey('email', $u);
                $this->assertArrayHasKey('active', $u);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($user->id));
        }
    }

    public function test_change_status_user()
    {
        $user = User::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/users/' . $user->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status User updated')]);
        $user->refresh();
        $this->assertEquals(0, $user->active);
    }
}

<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\AccountType;

class AccountTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_type_crud_flow()
    {
        $data = [
            'name' => 'Test Account Type',
            'icon' => 'fa-bank',
            'description' => 'Test description',
        ];
        $createResponse = $this->postJson('/api/v1/account_types/', $data);
        $createResponse->assertStatus(201)
            ->assertJson(['status' => 'OK']);
        $typeId = $createResponse->json('data.id') ?? AccountType::where('name', 'Test Account Type')->first()->id;

        $findResponse = $this->getJson('/api/v1/account_types/' . $typeId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $typeId, 'name' => 'Test Account Type']]);

        $updateData = ['name' => 'Updated Account Type'];
        $updateResponse = $this->putJson('/api/v1/account_types/' . $typeId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Account Type']]);

        $listResponse = $this->getJson('/api/v1/account_types/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $typeId));

        $deleteResponse = $this->deleteJson('/api/v1/account_types/' . $typeId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('account_types', ['id' => $typeId]);
    }

    public function test_get_all_active_account_types()
    {
        AccountType::factory()->create(['active' => 1]);
        AccountType::factory()->create(['active' => 0]);
        $response = $this->getJson('/api/v1/account_types/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $t) {
                $this->assertArrayHasKey('id', $t);
                $this->assertArrayHasKey('name', $t);
                $this->assertArrayHasKey('active', $t);
                $this->assertEquals(1, $t['active']);
            }
        }
    }

    public function test_get_account_types_with_trashed()
    {
        $type = AccountType::factory()->create();
        $type->delete();
        $response = $this->getJson('/api/v1/account_types/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $t) {
                $this->assertArrayHasKey('id', $t);
                $this->assertArrayHasKey('name', $t);
                $this->assertArrayHasKey('active', $t);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($type->id));
        }
    }

    public function test_change_status_account_type()
    {
        $type = AccountType::factory()->create(['active' => 1]);
        $response = $this->patchJson('/api/v1/account_types/' . $type->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Account Type updated')]);
        $type->refresh();
        $this->assertEquals(0, $type->active);
    }
}

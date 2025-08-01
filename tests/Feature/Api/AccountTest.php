<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Account;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_crud_flow()
    {
        // Crear
        $currency = \App\Models\Entities\Currency::factory()->create();
        $accountType = \App\Models\Entities\AccountType::factory()->create();
        $data = [
            'name' => 'Test Account',
            'currency_id' => $currency->id,
            'initial' => 1000.00,
            'account_type_id' => $accountType->id
        ];
        $createResponse = $this->postJson('/api/v1/accounts/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $accountId = $createResponse->json('data.id') ?? Account::where('name', 'Test Account')->first()->id;

        // Buscar por id
        $findResponse = $this->getJson('/api/v1/accounts/' . $accountId);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $accountId, 'name' => 'Test Account']]);

        // Actualizar
        $updateData = ['name' => 'Updated Account'];
        $updateResponse = $this->putJson('/api/v1/accounts/' . $accountId, $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Account']]);

        // Listar todos
        $listResponse = $this->getJson('/api/v1/accounts/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'name', 'currency_id', 'initial', 'account_type_id', 'active']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $accountId));

        // Eliminar
        $deleteResponse = $this->deleteJson('/api/v1/accounts/' . $accountId);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('accounts', ['id' => $accountId]);
    }

    public function test_get_all_active_accounts()
    {
        $currency = \App\Models\Entities\Currency::factory()->create();
        $accountType = \App\Models\Entities\AccountType::factory()->create();
        Account::factory()->create(['active' => 1, 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        Account::factory()->create(['active' => 0, 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $response = $this->getJson('/api/v1/accounts/active');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $a) {
                $this->assertArrayHasKey('id', $a);
                $this->assertArrayHasKey('name', $a);
                $this->assertArrayHasKey('currency_id', $a);
                $this->assertArrayHasKey('initial', $a);
                $this->assertArrayHasKey('account_type_id', $a);
                $this->assertArrayHasKey('active', $a);
                $this->assertEquals(1, $a['active']);
            }
        }
    }

    public function test_get_accounts_with_trashed()
    {
        $currency = \App\Models\Entities\Currency::factory()->create();
        $accountType = \App\Models\Entities\AccountType::factory()->create();
        $account = Account::factory()->create(['currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $account->delete();
        $response = $this->getJson('/api/v1/accounts/all');
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        if (!empty($json['data'])) {
            foreach ($json['data'] as $a) {
                $this->assertArrayHasKey('id', $a);
                $this->assertArrayHasKey('name', $a);
                $this->assertArrayHasKey('currency_id', $a);
                $this->assertArrayHasKey('initial', $a);
                $this->assertArrayHasKey('account_type_id', $a);
                $this->assertArrayHasKey('active', $a);
            }
            $ids = collect($json['data'])->pluck('id');
            $this->assertTrue($ids->contains($account->id));
        }
    }

    public function test_change_status_account()
    {
        $currency = \App\Models\Entities\Currency::factory()->create();
        $accountType = \App\Models\Entities\AccountType::factory()->create();
        $account = Account::factory()->create(['active' => 1, 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $response = $this->patchJson('/api/v1/accounts/' . $account->id . '/status');
        $response->assertStatus(200)
            ->assertJson(['status' => 'OK', 'message' => __('Status Account updated')]);
        $account->refresh();
        $this->assertEquals(0, $account->active);
    }
}

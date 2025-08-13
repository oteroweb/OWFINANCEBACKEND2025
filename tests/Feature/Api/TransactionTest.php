<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Transaction;
use App\Models\Entities\Account;
use App\Models\Entities\TransactionType;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_crud_flow()
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'income']);
        $data = [
            'account_id' => $account->id,
            'amount' => 100.00,
            'description' => 'Test Transaction',
            'name' => 'Test Transaction',
            'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
        ];
        $createResponse = $this->postJson('/api/v1/transactions/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $id = $createResponse->json('data.id') ?? Transaction::where('id', $createResponse->json('data.id'))->first()->id;

        $findResponse = $this->getJson('/api/v1/transactions/' . $id);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $id, 'account_id' => $account->id]]);

        $listResponse = $this->getJson('/api/v1/transactions/?transaction_type_id='.$type->id);
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'account_id', 'amount', 'description']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $id));

        $deleteResponse = $this->deleteJson('/api/v1/transactions/' . $id);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('transactions', ['id' => $id]);
    }

    public function test_get_all_active_transactions()
    {
        $account = Account::factory()->create();
        $active = Transaction::factory()->create(['account_id' => $account->id, 'active' => 1]);
        $inactive = Transaction::factory()->create(['account_id' => $account->id, 'active' => 0]);
        $response = $this->getJson('/api/v1/transactions/active');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_get_transactions_with_trashed()
    {
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create(['account_id' => $account->id]);
        $transaction->delete();
        $response = $this->getJson('/api/v1/transactions/all');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($transaction->id));
    }

    public function test_change_status_transaction()
    {
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create(['account_id' => $account->id, 'active' => 1]);
        $response = $this->patchJson('/api/v1/transactions/' . $transaction->id . '/status');
        $response->assertStatus(200);
        $transaction->refresh();
        $this->assertEquals(0, $transaction->active);
    }
}

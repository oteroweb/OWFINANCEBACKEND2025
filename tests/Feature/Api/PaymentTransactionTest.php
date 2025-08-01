<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\PaymentTransaction;
use App\Models\Entities\Account;
use App\Models\Entities\Provider;

class PaymentTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_transaction_crud_flow()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $data = [
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'amount' => 100.00,
            'description' => 'Test Payment',
        ];
        $createResponse = $this->postJson('/api/v1/payment-transactions/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $id = $createResponse->json('data.id') ?? PaymentTransaction::where($data)->first()->id;

        $findResponse = $this->getJson('/api/v1/payment-transactions/' . $id);
        $findResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $id,
                    'transaction_id' => $transaction->id,
                    'account_id' => $transaction->account_id,
                    'amount' => 100.00,
                ]
            ]);

        $listResponse = $this->getJson('/api/v1/payment-transactions/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'transaction_id', 'account_id', 'amount', 'active', 'created_at', 'updated_at', 'deleted_at']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $id));

        $deleteResponse = $this->deleteJson('/api/v1/payment-transactions/' . $id);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('payment_transactions', ['id' => $id]);
    }

    public function test_get_all_active_payment_transactions()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $active = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'active' => 1
        ]);
        $inactive = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'active' => 0
        ]);
        $response = $this->getJson('/api/v1/payment-transactions/active');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_get_payment_transactions_with_trashed()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $pt = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
        ]);
        $pt->delete();
        $response = $this->getJson('/api/v1/payment-transactions/all');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($pt->id));
    }

    public function test_change_status_payment_transaction()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $pt = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'active' => 1
        ]);
        $response = $this->patchJson('/api/v1/payment-transactions/' . $pt->id . '/status');
        $response->assertStatus(200);
        $pt->refresh();
        $this->assertEquals(0, $pt->active);
    }
}

<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\PaymentTransactionTax;
use App\Models\Entities\PaymentTransaction;
use App\Models\Entities\Tax;


class PaymentTransactionTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_all_active_payment_transaction_taxes()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $paymentTransaction = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
        ]);
        $tax = Tax::factory()->create();
        $active = PaymentTransactionTax::factory()->create([
            'payment_transaction_id' => $paymentTransaction->id,
            'tax_id' => $tax->id,
            'active' => 1
        ]);
        $inactive = PaymentTransactionTax::factory()->create([
            'payment_transaction_id' => $paymentTransaction->id,
            'tax_id' => $tax->id,
            'active' => 0
        ]);
        $response = $this->getJson('/api/v1/payment-transaction-taxes/active');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_get_payment_transaction_taxes_with_trashed()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $paymentTransaction = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
        ]);
        $tax = Tax::factory()->create();
        $ptt = PaymentTransactionTax::factory()->create([
            'payment_transaction_id' => $paymentTransaction->id,
            'tax_id' => $tax->id,
        ]);
        $ptt->delete();
        $response = $this->getJson('/api/v1/payment-transaction-taxes/all');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($ptt->id));
    }

    public function test_change_status_payment_transaction_tax()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $paymentTransaction = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
        ]);
        $tax = Tax::factory()->create();
        $ptt = PaymentTransactionTax::factory()->create([
            'payment_transaction_id' => $paymentTransaction->id,
            'tax_id' => $tax->id,
            'active' => 1
        ]);
        $response = $this->patchJson('/api/v1/payment-transaction-taxes/' . $ptt->id . '/status');
        $response->assertStatus(200);
        $ptt->refresh();
        $this->assertEquals(0, $ptt->active);
    }

    public function test_payment_transaction_tax_crud_flow()
    {
        $transaction = \App\Models\Entities\Transaction::factory()->create();
        $paymentTransaction = \App\Models\Entities\PaymentTransaction::factory()->forTransaction($transaction)->create([
            'transaction_id' => $transaction->id,
            'account_id' => $transaction->account_id,
        ]);
        $tax = Tax::factory()->create();
        $data = [
            'payment_transaction_id' => $paymentTransaction->id,
            'tax_id' => $tax->id,
            'amount' => 10.00
        ];
        $createResponse = $this->postJson('/api/v1/payment-transaction-taxes/', $data);
        $createResponse->assertStatus(200)
            ->assertJson(['status' => 'OK']);
        $id = $createResponse->json('data.id') ?? PaymentTransactionTax::where($data)->first()->id;

        $findResponse = $this->getJson('/api/v1/payment-transaction-taxes/' . $id);
        $findResponse->assertStatus(200)
            ->assertJson(['data' => ['id' => $id, 'payment_transaction_id' => $paymentTransaction->id, 'tax_id' => $tax->id]]);

        $listResponse = $this->getJson('/api/v1/payment-transaction-taxes/');
        $listResponse->assertStatus(200)
            ->assertJsonStructure([
                'status', 'code', 'message', 'data' => [['id', 'payment_transaction_id', 'tax_id', 'amount']]
            ]);
        $this->assertTrue(collect($listResponse->json('data'))->contains('id', $id));

        $deleteResponse = $this->deleteJson('/api/v1/payment-transaction-taxes/' . $id);
        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('payment_transaction_taxes', ['id' => $id]);
    }
}

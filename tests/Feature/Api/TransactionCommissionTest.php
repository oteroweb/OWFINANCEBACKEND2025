<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Account;
use App\Models\Entities\Transaction;
use App\Models\Entities\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_with_commission_persists_own_fields(): void
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'expense']);

        $data = [
            'account_id' => $account->id,
            'amount' => 110.00,
            'description' => 'Payment with commission',
            'name' => 'Commission Transaction',
            'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'commission_type' => 'porcentaje',
            'commission_value' => 10,
            'commission_amount' => 10.00,
            'items' => [
                ['name' => 'Base', 'amount' => 110.00],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => 110.00, 'rate' => 1],
            ],
        ];

        $response = $this->postJson('/api/v1/transactions/', $data);

        $response->assertStatus(200)->assertJson(['status' => 'OK']);

        $id = $response->json('data.id');
        $this->assertDatabaseHas('transactions', [
            'id' => $id,
            'commission_type' => 'porcentaje',
            'commission_value' => 10,
            'commission_amount' => 10.00,
        ]);

        // Round-trip: fetching it back exposes the same commission fields.
        $findResponse = $this->getJson('/api/v1/transactions/' . $id);
        $findResponse->assertStatus(200);
        $this->assertEquals('porcentaje', $findResponse->json('data.commission_type'));
        $this->assertEquals(10, (float) $findResponse->json('data.commission_value'));
        $this->assertEquals(10, (float) $findResponse->json('data.commission_amount'));
    }

    public function test_create_without_commission_leaves_fields_null(): void
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'expense']);

        $data = [
            'account_id' => $account->id,
            'amount' => 50.00,
            'description' => 'No commission',
            'name' => 'Plain Transaction',
            'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Base', 'amount' => 50.00],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => 50.00, 'rate' => 1],
            ],
        ];

        $response = $this->postJson('/api/v1/transactions/', $data);
        $response->assertStatus(200);

        $id = $response->json('data.id');
        $transaction = Transaction::find($id);
        $this->assertNull($transaction->commission_type);
        $this->assertNull($transaction->commission_value);
        $this->assertNull($transaction->commission_amount);
    }

    public function test_edit_without_change_keeps_commission_and_amount_identical(): void
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'expense']);

        $createData = [
            'account_id' => $account->id,
            'amount' => 110.00,
            'description' => 'Payment with commission',
            'name' => 'Commission Transaction',
            'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'commission_type' => 'fijo',
            'commission_value' => 10,
            'commission_amount' => 10.00,
            'items' => [
                ['name' => 'Base', 'amount' => 110.00],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => 110.00, 'rate' => 1],
            ],
        ];
        $createResponse = $this->postJson('/api/v1/transactions/', $createData);
        $createResponse->assertStatus(200);
        $id = $createResponse->json('data.id');

        // "Edit without change": re-send the exact same commission + amount fields.
        $updateData = [
            'name' => 'Commission Transaction',
            'amount' => 110.00,
            'commission_type' => 'fijo',
            'commission_value' => 10,
            'commission_amount' => 10.00,
        ];
        $updateResponse = $this->putJson('/api/v1/transactions/' . $id, $updateData);
        $updateResponse->assertStatus(200);

        $transaction = Transaction::find($id);
        $this->assertEquals(110.00, (float) $transaction->amount);
        $this->assertEquals(10.00, (float) $transaction->commission_amount);
        $this->assertEquals('fijo', $transaction->commission_type);
        $this->assertEquals(10, (float) $transaction->commission_value);
    }

    public function test_update_can_change_commission_fields(): void
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'expense']);

        $createData = [
            'account_id' => $account->id,
            'amount' => 100.00,
            'name' => 'Original',
            'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Base', 'amount' => 100.00],
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => 100.00, 'rate' => 1],
            ],
        ];
        $createResponse = $this->postJson('/api/v1/transactions/', $createData);
        $id = $createResponse->json('data.id');

        $updateResponse = $this->putJson('/api/v1/transactions/' . $id, [
            'commission_type' => 'pagomovil',
            'commission_value' => 5,
            'commission_amount' => 5.00,
        ]);
        $updateResponse->assertStatus(200);

        $transaction = Transaction::find($id);
        $this->assertEquals('pagomovil', $transaction->commission_type);
        $this->assertEquals(5, (float) $transaction->commission_value);
        $this->assertEquals(5.00, (float) $transaction->commission_amount);
    }
}

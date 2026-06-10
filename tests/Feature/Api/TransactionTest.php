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
            'items' => [
                ['name' => 'Gen', 'amount' => 100.00, 'item_category_id' => null]
            ],
            'payments' => [
                ['account_id' => $account->id, 'amount' => 100.00, 'rate' => 1]
            ],
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

    public function test_bulk_create_all_valid()
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'expense']);

        $data = [
            'mode' => 'table',
            'dry_run' => false,
            'rows' => [
                [
                    'name' => 'Compra 1',
                    'date' => now()->format('Y-m-d H:i:s'),
                    'transaction_type_id' => $type->id,
                    'category_id' => null,
                    'include_in_balance' => true,
                    'items' => [
                        ['name' => 'Item 1', 'amount' => -50.00]
                    ],
                    'payments' => [
                        ['account_id' => $account->id, 'amount' => -50.00, 'rate' => 1]
                    ],
                    'client_row_id' => 'row-1'
                ],
                [
                    'name' => 'Compra 2',
                    'date' => now()->format('Y-m-d H:i:s'),
                    'transaction_type_id' => $type->id,
                    'items' => [
                        ['name' => 'Item 2', 'amount' => -30.00]
                    ],
                    'payments' => [
                        ['account_id' => $account->id, 'amount' => -30.00, 'rate' => 1]
                    ],
                    'client_row_id' => 'row-2'
                ],
            ]
        ];

        $response = $this->postJson('/api/v1/transactions/bulk', $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
                'data' => [
                    'total' => 2,
                    'created' => 2,
                    'failed' => 0
                ]
            ]);

        $results = $response->json('data.results');
        $this->assertCount(2, $results);
        $this->assertTrue($results[0]['ok']);
        $this->assertTrue($results[1]['ok']);
        $this->assertNotNull($results[0]['transaction_id']);
        $this->assertNotNull($results[1]['transaction_id']);
    }

    public function test_bulk_create_all_invalid()
    {
        $data = [
            'mode' => 'table',
            'dry_run' => false,
            'rows' => [
                [
                    // Missing required 'name' field
                    'date' => now()->format('Y-m-d H:i:s'),
                    'items' => [],
                    'payments' => [
                        ['account_id' => 99999, 'amount' => -50.00]
                    ],
                    'client_row_id' => 'row-1'
                ],
                [
                    'name' => 'Invalid',
                    // Missing required 'date' field
                    'items' => [],
                    'payments' => [],
                    'client_row_id' => 'row-2'
                ],
            ]
        ];

        $response = $this->postJson('/api/v1/transactions/bulk', $data);

        $response->assertStatus(422)
            ->assertJson([
                'data' => [
                    'total' => 2,
                    'created' => 0,
                    'failed' => 2
                ]
            ]);

        $results = $response->json('data.results');
        $this->assertFalse($results[0]['ok']);
        $this->assertFalse($results[1]['ok']);
        $this->assertArrayHasKey('errors', $results[0]);
        $this->assertArrayHasKey('errors', $results[1]);
    }

    public function test_bulk_create_partial_success()
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'expense']);

        $data = [
            'mode' => 'table',
            'dry_run' => false,
            'rows' => [
                [
                    'name' => 'Valid Transaction',
                    'date' => now()->format('Y-m-d H:i:s'),
                    'transaction_type_id' => $type->id,
                    'items' => [
                        ['name' => 'Item', 'amount' => -25.00]
                    ],
                    'payments' => [
                        ['account_id' => $account->id, 'amount' => -25.00, 'rate' => 1]
                    ],
                    'client_row_id' => 'row-valid'
                ],
                [
                    // Invalid: missing name
                    'date' => now()->format('Y-m-d H:i:s'),
                    'items' => [],
                    'payments' => [
                        ['account_id' => $account->id, 'amount' => -50.00]
                    ],
                    'client_row_id' => 'row-invalid'
                ],
            ]
        ];

        $response = $this->postJson('/api/v1/transactions/bulk', $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
                'data' => [
                    'total' => 2,
                    'created' => 1,
                    'failed' => 1
                ]
            ]);

        $results = $response->json('data.results');
        $this->assertTrue($results[0]['ok']);
        $this->assertFalse($results[1]['ok']);
    }

    public function test_bulk_create_dry_run()
    {
        $account = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'expense']);

        $data = [
            'mode' => 'table',
            'dry_run' => true,
            'rows' => [
                [
                    'name' => 'Dry Run Test',
                    'date' => now()->format('Y-m-d H:i:s'),
                    'transaction_type_id' => $type->id,
                    'items' => [
                        ['name' => 'Item', 'amount' => -100.00]
                    ],
                    'payments' => [
                        ['account_id' => $account->id, 'amount' => -100.00, 'rate' => 1]
                    ],
                    'client_row_id' => 'row-dry'
                ],
            ]
        ];

        $initialCount = Transaction::count();
        $response = $this->postJson('/api/v1/transactions/bulk', $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
                'data' => [
                    'total' => 1,
                    'created' => 0,
                    'failed' => 0
                ]
            ]);

        // Verify no transactions were actually created
        $this->assertEquals($initialCount, Transaction::count());

        $results = $response->json('data.results');
        $this->assertTrue($results[0]['ok']);
        $this->assertNull($results[0]['transaction_id']);
        $this->assertTrue($results[0]['dry_run'] ?? false);
    }

    public function test_bulk_create_account_permission()
    {
        // Create two users manually
        $user1 = \App\Models\Entities\User::create([
            'name' => 'User One',
            'email' => 'user1@test.com',
            'password' => bcrypt('password'),
            'active' => 1,
        ]);
        $user2 = \App\Models\Entities\User::create([
            'name' => 'User Two',
            'email' => 'user2@test.com',
            'password' => bcrypt('password'),
            'active' => 1,
        ]);

        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        // Associate accounts with users
        $user1->accounts()->attach($account1->id);
        $user2->accounts()->attach($account2->id);

        $type = TransactionType::factory()->create(['slug' => 'expense']);

        // User1 tries to create transaction on User2's account
        $data = [
            'mode' => 'table',
            'dry_run' => false,
            'rows' => [
                [
                    'name' => 'Unauthorized Transaction',
                    'date' => now()->format('Y-m-d H:i:s'),
                    'transaction_type_id' => $type->id,
                    'items' => [
                        ['name' => 'Item', 'amount' => -50.00]
                    ],
                    'payments' => [
                        ['account_id' => $account2->id, 'amount' => -50.00, 'rate' => 1]
                    ],
                    'client_row_id' => 'row-unauth'
                ],
            ]
        ];

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/v1/transactions/bulk', $data);

        $response->assertStatus(422);

        $results = $response->json('data.results');
        $this->assertFalse($results[0]['ok']);
        $this->assertArrayHasKey('errors', $results[0]);
    }

    public function test_bulk_create_transfer_validation()
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        $type = TransactionType::factory()->create(['slug' => 'transfer']);

        $data = [
            'mode' => 'table',
            'dry_run' => false,
            'rows' => [
                [
                    'name' => 'Transfer',
                    'date' => now()->format('Y-m-d H:i:s'),
                    'transaction_type_id' => $type->id,
                    'amount' => 100.00,
                    'items' => [],
                    'payments' => [
                        ['account_id' => $account1->id, 'amount' => -100.00, 'rate' => 1],
                        ['account_id' => $account2->id, 'amount' => 100.00, 'rate' => 1]
                    ],
                    'client_row_id' => 'row-transfer'
                ],
            ]
        ];

        $response = $this->postJson('/api/v1/transactions/bulk', $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'OK',
                'data' => [
                    'total' => 1,
                    'created' => 1,
                    'failed' => 0
                ]
            ]);
    }
}


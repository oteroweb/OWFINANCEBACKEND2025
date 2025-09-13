<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Account;
use App\Models\Entities\TransactionType;
use App\Models\Entities\Transaction;
use App\Models\User;

class AccountBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeaders(User $user): array
    {
        // Asumiendo Sanctum: create token y añadir Authorization header
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_incremental_balance_on_create_update_delete()
    {
        $user = User::factory()->create();
    $currency = \App\Models\Entities\Currency::factory()->create();
    $acctType = \App\Models\Entities\AccountType::factory()->create();
    $account = Account::factory()->create(['initial' => 0, 'currency_id' => $currency->id, 'account_type_id' => $acctType->id]);
    // Vincular usuario a la cuenta para pasar autorización
    $user->accounts()->attach($account->id, ['is_owner' => 1]);
        $type = TransactionType::factory()->create(['slug' => 'income']);
        $headers = $this->authHeaders($user);

        // Crear transacción +100
        $payload = [
            'name' => 'Ingreso 1',
            'amount' => 100.00,
            'amount_tax' => 0,
            'date' => now()->format('Y-m-d H:i:s'),
            'account_id' => $account->id,
            'transaction_type_id' => $type->id,
            'include_in_balance' => true,
        ];
        $resp = $this->postJson('/api/v1/transactions/', $payload, $headers);
        $resp->assertStatus(200);
    // Forzar recálculo para garantizar consistencia en entorno de test
    $this->postJson('/api/v1/accounts/'.$account->id.'/recalc-balance', [], $headers)->assertStatus(200);
    $account->refresh();
    $this->assertEquals(100.00, (float)$account->balance_cached, 'Balance after create (recalc) should be 100');

        $txnId = $resp->json('data.id');
        $this->assertNotNull($txnId);

        // Update monto a 150
        $update = [
            'amount' => 150.00,
        ];
        $resp2 = $this->putJson('/api/v1/transactions/'.$txnId, $update, $headers);
        $resp2->assertStatus(200);
        $account->refresh();
        $this->assertEquals(150.00, (float)$account->balance_cached, 'Balance after update diff +50 => 150');

        // Cambiar include_in_balance a false (debe restar 150)
        $resp3 = $this->putJson('/api/v1/transactions/'.$txnId, ['include_in_balance' => false], $headers);
        $resp3->assertStatus(200);
        $account->refresh();
        $this->assertEquals(0.00, (float)$account->balance_cached, 'Balance after exclude should be 0');

        // Revertir include_in_balance true (suma +150)
        $resp4 = $this->putJson('/api/v1/transactions/'.$txnId, ['include_in_balance' => true], $headers);
        $resp4->assertStatus(200);
        $account->refresh();
        $this->assertEquals(150.00, (float)$account->balance_cached, 'Balance after re-include should be 150');

        // Borrar (soft delete) => resta 150
        $resp5 = $this->deleteJson('/api/v1/transactions/'.$txnId, [], $headers);
        $resp5->assertStatus(200);
        $account->refresh();
        $this->assertEquals(0.00, (float)$account->balance_cached, 'Balance after delete should be 0');
    }

    public function test_recalc_balance_endpoint()
    {
        $user = User::factory()->create();
    $currency = \App\Models\Entities\Currency::factory()->create();
    $acctType = \App\Models\Entities\AccountType::factory()->create();
    $account = Account::factory()->create(['initial' => 0, 'currency_id' => $currency->id, 'account_type_id' => $acctType->id]);
    $user->accounts()->attach($account->id, ['is_owner' => 1]);
        $type = TransactionType::factory()->create(['slug' => 'income']);
        $headers = $this->authHeaders($user);

        // Crear dos transacciones manualmente (sin pasar por endpoint) para simular desincronización
        // Crear transacciones manuales para evitar dependencias innecesarias de factories
        Transaction::create([
            'name' => 'T1',
            'amount' => 40,
            'description' => 'Manual 1',
            'date' => now(),
            'active' => 1,
            'provider_id' => null,
            'url_file' => null,
            'rate_id' => null,
            'transaction_type_id' => $type->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount_tax' => 0,
            'include_in_balance' => 1,
        ]);
        Transaction::create([
            'name' => 'T2',
            'amount' => 60,
            'description' => 'Manual 2',
            'date' => now(),
            'active' => 1,
            'provider_id' => null,
            'url_file' => null,
            'rate_id' => null,
            'transaction_type_id' => $type->id,
            'user_id' => $user->id,
            'account_id' => $account->id,
            'amount_tax' => 0,
            'include_in_balance' => 1,
        ]);

        // Forzar balance_cached desactualizado
        $account->balance_cached = 0; $account->save();

        $resp = $this->postJson('/api/v1/accounts/'.$account->id.'/recalc-balance', [], $headers);
        $resp->assertStatus(200);
        $account->refresh();
        $this->assertEquals(100.00, (float)$account->balance_cached, 'Recalc should set cached to 100');
    }
}

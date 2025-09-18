<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Account;
use App\Models\Entities\Transaction;
use App\Models\User;

class AdjustBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_adjust_balance_updates_initial_when_excluded()
    {
        $user = User::factory()->create();
        $currency = \App\Models\Entities\Currency::factory()->create();
        $acctType = \App\Models\Entities\AccountType::factory()->create();
        $account = Account::factory()->create(['initial' => 100, 'currency_id' => $currency->id, 'account_type_id' => $acctType->id]);
        $user->accounts()->attach($account->id, ['is_owner' => 1]);
        $headers = $this->authHeaders($user);

        // Transacción existente +50 (incluida)
        Transaction::create([
            'name' => 'Ingreso', 'amount' => 50, 'description' => '-', 'date' => now(),
            'active' => 1, 'provider_id' => null, 'url_file' => null, 'rate_id' => null,
            'transaction_type_id' => null, 'user_id' => $user->id, 'account_id' => $account->id,
            'amount_tax' => 0, 'include_in_balance' => 1,
        ]);

        // Balance actual esperado: 100 + 50 = 150. Queremos 200 con include_in_balance=false => solo cambia initial a 150
        $resp = $this->postJson('/api/v1/accounts/'.$account->id.'/adjust-balance', [
            'target_balance' => 200,
            'include_in_balance' => false,
        ], $headers);
        $resp->assertStatus(200);
        $account->refresh();
        $this->assertEquals(200.00, (float)$account->balance_cached);
        $this->assertEquals(150.00, (float)$account->initial);
    }

    public function test_adjust_balance_creates_transaction_when_included()
    {
        $user = User::factory()->create();
        $currency = \App\Models\Entities\Currency::factory()->create();
        $acctType = \App\Models\Entities\AccountType::factory()->create();
        $account = Account::factory()->create(['initial' => 0, 'currency_id' => $currency->id, 'account_type_id' => $acctType->id]);
        $user->accounts()->attach($account->id, ['is_owner' => 1]);
        $headers = $this->authHeaders($user);

        // Sin transacciones, target=120 => debe crear txn +120
        $resp = $this->postJson('/api/v1/accounts/'.$account->id.'/adjust-balance', [
            'target_balance' => 120,
            'include_in_balance' => true,
            'description' => 'Ajuste test',
        ], $headers);
        $resp->assertStatus(200);
        $account->refresh();
        $this->assertEquals(120.00, (float)$account->balance_cached);
        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'amount' => 120.00,
            'include_in_balance' => 1,
            'active' => 1,
        ]);
    }
}

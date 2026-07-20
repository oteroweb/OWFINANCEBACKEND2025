<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Account;
use App\Models\Entities\Currency;
use App\Models\Entities\OfficialRate;
use App\Models\Entities\TransactionType;
use App\Models\Entities\UserCurrency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Indirect precedence tests for TransactionController::resolveUserCurrencyRate()
 * (private method). Since it isn't directly reachable, each case is proven through
 * the real save() (POST /api/v1/transactions/) endpoint: a single payment leg in a
 * foreign currency plus an items total in "user currency" that only balances
 * (within the 0.01 tolerance) if the expected rate was actually used. If a
 * different rate wins, the payments-vs-items total check fails with 422.
 */
class TransactionRatePrecedenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Override the parent's anonymous actingAs user with one we control the ID of,
        // so UserCurrency rows created directly in these tests match the resolver's lookup.
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    private function makeAccount(): array
    {
        $currency = Currency::factory()->create(['code' => 'VES']);
        $type = TransactionType::factory()->create(['slug' => 'expense']);
        $account = Account::factory()->create(['currency_id' => $currency->id]);
        return [$account, $currency, $type];
    }

    private function payload(Account $account, TransactionType $type, float $paymentAmount, float $itemsAmount, ?float $rate = null): array
    {
        $payment = ['account_id' => $account->id, 'amount' => -$paymentAmount];
        if ($rate !== null) {
            $payment['rate'] = $rate;
        }

        return [
            'name' => 'Rate precedence test',
            'date' => now()->format('Y-m-d H:i:s'),
            'transaction_type_id' => $type->id,
            'items' => [
                ['name' => 'Item', 'amount' => -$itemsAmount],
            ],
            'payments' => [$payment],
        ];
    }

    public function test_provided_rate_always_wins(): void
    {
        [$account, $currency, $type] = $this->makeAccount();

        // Distractor sources that would win if precedence were broken.
        UserCurrency::create([
            'user_id' => $this->user->id,
            'currency_id' => $currency->id,
            'current_rate' => 20,
            'is_current' => true,
        ]);
        OfficialRate::create([
            'currency_id' => $currency->id,
            'rate' => 30,
            'source' => 'pydolarve',
            'fetched_at' => now(),
        ]);

        // Explicit rate = 10: payment of 1000 (foreign) => 100 (user currency).
        $data = $this->payload($account, $type, 1000, 100, 10);

        $response = $this->postJson('/api/v1/transactions/', $data);

        $response->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    public function test_user_current_rate_wins_over_official_rate(): void
    {
        [$account, $currency, $type] = $this->makeAccount();

        UserCurrency::create([
            'user_id' => $this->user->id,
            'currency_id' => $currency->id,
            'current_rate' => 20,
            'is_current' => true,
        ]);
        // Distractor: official rate that must NOT be used while a user current rate exists.
        OfficialRate::create([
            'currency_id' => $currency->id,
            'rate' => 30,
            'source' => 'pydolarve',
            'fetched_at' => now(),
        ]);

        // No explicit rate: payment of 2000 (foreign) / 20 (user current) => 100 (user currency).
        $data = $this->payload($account, $type, 2000, 100, null);

        $response = $this->postJson('/api/v1/transactions/', $data);

        $response->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    public function test_official_rate_wins_over_final_fallback(): void
    {
        [$account, $currency, $type] = $this->makeAccount();

        // No UserCurrency.is_current for this currency.
        OfficialRate::create([
            'currency_id' => $currency->id,
            'rate' => 40,
            'source' => 'pydolarve',
            'fetched_at' => now(),
        ]);

        // No explicit rate: payment of 4000 (foreign) / 40 (official) => 100 (user currency).
        $data = $this->payload($account, $type, 4000, 100, null);

        $response = $this->postJson('/api/v1/transactions/', $data);

        $response->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    public function test_final_fallback_of_one_when_nothing_else_available(): void
    {
        [$account, $currency, $type] = $this->makeAccount();

        // No UserCurrency, no official_rates row for this currency at all.
        $this->assertDatabaseCount('official_rates', 0);

        // No explicit rate: payment of 250 (foreign) / 1.0 (final fallback) => 250 (user currency).
        $data = $this->payload($account, $type, 250, 250, null);

        $response = $this->postJson('/api/v1/transactions/', $data);

        $response->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    public function test_official_rate_fallback_is_not_used_when_it_would_mismatch(): void
    {
        // Negative control: if the resolver ever picks a rate other than what we expect,
        // the payments-vs-items conversion check must fail. This proves the previous
        // "success" assertions weren't passing by coincidence (e.g. rate always 1).
        [$account, $currency, $type] = $this->makeAccount();

        OfficialRate::create([
            'currency_id' => $currency->id,
            'rate' => 40,
            'source' => 'pydolarve',
            'fetched_at' => now(),
        ]);

        // Deliberately wrong expectation: pretend rate is 4000 -> items amount 1
        // (10000/4000 = 100, not 1) instead of using the true official rate 40.
        $data = $this->payload($account, $type, 4000, 1, null);

        $response = $this->postJson('/api/v1/transactions/', $data);

        $response->assertStatus(422);
    }
}

<?php

namespace Tests\Unit;

use App\Http\Controllers\AI\AiExtractionController;
use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use App\Models\Entities\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiExtractionAccountResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function callResolveAccountId($accounts, ?int $modelAccountId, string $rawText): ?int
    {
        $controller = new AiExtractionController();
        $method = new \ReflectionMethod($controller, 'resolveAccountId');
        $method->setAccessible(true);

        return $method->invoke($controller, $accounts, $modelAccountId, $rawText);
    }

    public function test_single_account_always_auto_resolves()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $account = Account::factory()->create(['name' => 'Billetera USD', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach($account->id, ['is_owner' => 1]);

        $result = $this->callResolveAccountId($user->accounts, null, 'gasté 15 dólares');

        $this->assertSame($account->id, $result);
    }

    public function test_model_returned_account_id_is_respected_when_valid()
    {
        [$user, $accounts] = $this->twoAccountsUser();

        $result = $this->callResolveAccountId($accounts, $accounts[1]->id, 'texto irrelevante');

        $this->assertSame($accounts[1]->id, $result);
    }

    public function test_name_match_case_and_accent_insensitive()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $banesco = Account::factory()->create(['name' => 'Banesco Ahorro', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $other = Account::factory()->create(['name' => 'Billetera USD', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach([$banesco->id, $other->id], ['is_owner' => 1]);

        $result = $this->callResolveAccountId($user->accounts, null, 'gasté 15 dólares con BANÉSCO');

        $this->assertSame($banesco->id, $result);
    }

    public function test_no_match_with_multiple_accounts_returns_null()
    {
        [, $accounts] = $this->twoAccountsUser();

        $result = $this->callResolveAccountId($accounts, null, 'gasté 15 dólares');

        $this->assertNull($result);
    }

    private function twoAccountsUser(): array
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $a1 = Account::factory()->create(['name' => 'Billetera USD', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $a2 = Account::factory()->create(['name' => 'Billetera VES', 'currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();
        $user->accounts()->attach([$a1->id, $a2->id], ['is_owner' => 1]);

        return [$user, $user->accounts];
    }
}

<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Entities\Account;
use App\Models\Entities\Currency;
use App\Models\Entities\AccountType;

class AccountUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_account_relationship()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $account = Account::factory()->create(['currency_id' => $currency->id, 'account_type_id' => $accountType->id]);
        $user = User::factory()->create();

        // Attach account to user
        $user->accounts()->attach($account->id);
        $this->assertDatabaseHas('account_user', [
            'user_id' => $user->id,
            'account_id' => $account->id,
        ]);

        // Detach account from user
        $user->accounts()->detach($account->id);
        $this->assertDatabaseMissing('account_user', [
            'user_id' => $user->id,
            'account_id' => $account->id,
        ]);
    }
}

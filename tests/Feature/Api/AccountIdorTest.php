<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Entities\Account;
use App\Models\Entities\Currency;
use App\Models\Entities\AccountType;

class AccountIdorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_list_another_users_accounts_via_user_id_param()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $victimAccount = Account::factory()->create([
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
        ]);
        $victimAccount->users()->attach($victim->id, ['is_owner' => true]);

        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->getJson('/api/v1/accounts/?user_id=' . $victim->id);
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($victimAccount->id, $ids);
    }

    public function test_user_cannot_list_another_users_active_accounts_via_user_id_param()
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $victim = User::factory()->create();
        $attacker = User::factory()->create();

        $victimAccount = Account::factory()->create([
            'active' => 1,
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
        ]);
        $victimAccount->users()->attach($victim->id, ['is_owner' => true]);

        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->getJson('/api/v1/accounts/active?user_id=' . $victim->id);
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($victimAccount->id, $ids);
    }
}

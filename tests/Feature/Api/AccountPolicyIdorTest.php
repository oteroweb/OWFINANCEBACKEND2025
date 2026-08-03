<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Entities\Account;
use App\Models\Entities\Currency;
use App\Models\Entities\AccountType;

/**
 * OWF-363: AccountController tenía CERO chequeo de ownership en find/update/delete/
 * change_status/adjust-balance/recalculate — cualquier usuario autenticado podía ver,
 * editar, desactivar o borrar la cuenta de OTRO usuario adivinando el ID (IDOR real,
 * explotable en prod). Fix: AccountPolicy basada en el pivot account_user (multi-dueño),
 * wireada en cada endpoint mutante/lectura directa.
 */
class AccountPolicyIdorTest extends TestCase
{
    use RefreshDatabase;

    private function makeVictimAccount(): array
    {
        $currency = Currency::factory()->create();
        $accountType = AccountType::factory()->create();
        $victim = User::factory()->create();
        $account = Account::factory()->create([
            'currency_id' => $currency->id,
            'account_type_id' => $accountType->id,
        ]);
        $account->users()->attach($victim->id, ['is_owner' => true]);
        return [$victim, $account];
    }

    public function test_attacker_cannot_view_another_users_account()
    {
        [, $account] = $this->makeVictimAccount();
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->getJson('/api/v1/accounts/' . $account->id);
        $response->assertStatus(403);
    }

    public function test_attacker_cannot_update_another_users_account()
    {
        [, $account] = $this->makeVictimAccount();
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->putJson('/api/v1/accounts/' . $account->id, ['name' => 'Hijacked']);
        $response->assertStatus(403);
        $this->assertNotEquals('Hijacked', $account->fresh()->name);
    }

    public function test_attacker_cannot_delete_another_users_account()
    {
        [, $account] = $this->makeVictimAccount();
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->deleteJson('/api/v1/accounts/' . $account->id);
        $response->assertStatus(403);
        $this->assertNotSoftDeleted($account);
    }

    public function test_attacker_cannot_change_status_of_another_users_account()
    {
        [, $account] = $this->makeVictimAccount();
        $originalActive = $account->active;
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->patchJson('/api/v1/accounts/' . $account->id . '/status');
        $response->assertStatus(403);
        $this->assertEquals($originalActive, $account->fresh()->active);
    }

    public function test_attacker_cannot_adjust_balance_of_another_users_account()
    {
        [, $account] = $this->makeVictimAccount();
        $attacker = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($attacker, ['*']);

        $response = $this->postJson('/api/v1/accounts/' . $account->id . '/adjust-balance', [
            'target_balance' => 999999,
        ]);
        $response->assertStatus(403);
    }

    public function test_non_owner_member_can_view_and_update_but_not_delete()
    {
        [$victim, $account] = $this->makeVictimAccount();
        $member = User::factory()->create();
        $account->users()->attach($member->id, ['is_owner' => false]);
        \Laravel\Sanctum\Sanctum::actingAs($member, ['*']);

        $this->getJson('/api/v1/accounts/' . $account->id)->assertStatus(200);
        $this->putJson('/api/v1/accounts/' . $account->id, ['name' => 'Shared edit'])->assertStatus(200);
        $this->deleteJson('/api/v1/accounts/' . $account->id)->assertStatus(403);
    }

    public function test_owner_can_still_manage_own_account()
    {
        [$victim, $account] = $this->makeVictimAccount();
        \Laravel\Sanctum\Sanctum::actingAs($victim, ['*']);

        $this->getJson('/api/v1/accounts/' . $account->id)->assertStatus(200);
        $this->putJson('/api/v1/accounts/' . $account->id, ['name' => 'Renamed'])->assertStatus(200);
        $this->patchJson('/api/v1/accounts/' . $account->id . '/status')->assertStatus(200);
    }
}

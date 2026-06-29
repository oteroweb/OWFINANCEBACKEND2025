<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Account;
use App\Models\Entities\Currency;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Módulo: Accounts (cuentas bancarias, wallets, carpetas, balance global)
 * Cubre: CRUD, folders, tree, global balance, recalculate, adjust,
 *        aislamiento por usuario, move entre carpetas.
 */
class AccountsFullTest extends TestCase
{
    use RefreshDatabase;

    private function makeCurrency(string $code = 'USD'): Currency
    {
        return Currency::factory()->create(['code' => $code]);
    }

    // ── CRUD básico ───────────────────────────────────────────────────────────

    public function test_account_crud_flow()
    {
        $currency    = $this->makeCurrency();
        $accountType = \App\Models\Entities\AccountType::factory()->create([
            'name' => 'Corriente', 'icon' => 'wallet', 'description' => 'Cuenta corriente',
        ]);

        $res = $this->postJson('/api/v1/accounts/', [
            'name'            => 'BBVA Nómina',
            'initial'         => 10000.00,
            'currency_id'     => $currency->id,
            'account_type_id' => $accountType->id,
            'active'          => 1,
        ]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/accounts/' . $id)->assertStatus(200)
            ->assertJsonPath('data.name', 'BBVA Nómina');

        $this->putJson('/api/v1/accounts/' . $id, ['name' => 'BBVA Principal'])
            ->assertStatus(200);

        $this->getJson('/api/v1/accounts/active')->assertStatus(200);
        $this->getJson('/api/v1/accounts/all')->assertStatus(200);

        $this->patchJson('/api/v1/accounts/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/accounts/' . $id)->assertStatus(200);
    }

    // ── Aislamiento por usuario ───────────────────────────────────────────────

    public function test_accounts_isolated_between_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $currency = $this->makeCurrency();

        Sanctum::actingAs($user1, ['*']);
        $account = Account::factory()->create(['currency_id' => $currency->id]);
        $user1->accounts()->attach($account->id);

        Sanctum::actingAs($user2, ['*']);
        $list = $this->getJson('/api/v1/accounts/');
        $this->assertFalse(collect($list->json('data'))->contains('id', $account->id));
    }

    // ── Global balance summary ─────────────────────────────────────────────────

    public function test_global_balance_summary()
    {
        $res = $this->getJson('/api/v1/accounts/summary/global-balance');
        $res->assertStatus(200);
    }

    // ── Recalculate balance ──────────────────────────────────────────────────

    public function test_recalculate_account_balance()
    {
        $currency = $this->makeCurrency();
        $account  = Account::factory()->create(['currency_id' => $currency->id, 'initial' => 5000.00]);

        $res = $this->postJson('/api/v1/accounts/' . $account->id . '/recalculate-account');
        $res->assertStatus(200);
    }

    // ── Adjust balance ────────────────────────────────────────────────────────

    public function test_adjust_account_balance_included_in_balance()
    {
        $currency = $this->makeCurrency();
        $account  = Account::factory()->create(['currency_id' => $currency->id, 'initial' => 1000.00]);

        $res = $this->postJson('/api/v1/accounts/' . $account->id . '/adjust-balance', [
            'target_balance' => 1200.00,
            'description'    => 'Ajuste prueba',
        ]);
        $res->assertStatus(200);
    }

    // ── Carpetas ──────────────────────────────────────────────────────────────

    public function test_account_folder_crud()
    {
        $res = $this->postJson('/api/v1/accounts/folders', [
            'name' => 'Cuentas Bancos',
        ]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $folderId = $res->json('data.id');

        // Rename
        $this->putJson('/api/v1/accounts/folders/' . $folderId, [
            'name' => 'Bancos Principales',
        ])->assertStatus(200);

        // List
        $this->getJson('/api/v1/accounts/folders')->assertStatus(200);

        // Delete
        $this->deleteJson('/api/v1/accounts/folders/' . $folderId)->assertStatus(200);
    }

    public function test_account_move_to_folder()
    {
        $currency = $this->makeCurrency();
        $account  = Account::factory()->create(['currency_id' => $currency->id]);

        $folder = $this->postJson('/api/v1/accounts/folders', ['name' => 'Carpeta Test']);
        $folderId = $folder->json('data.id');

        $this->patchJson('/api/v1/accounts/' . $account->id . '/move', [
            'folder_id' => $folderId,
        ])->assertStatus(200);
    }

    // ── Tree ──────────────────────────────────────────────────────────────────

    public function test_account_tree()
    {
        $this->getJson('/api/v1/accounts/tree')->assertStatus(200);
    }
}

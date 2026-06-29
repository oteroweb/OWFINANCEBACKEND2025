<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Entities\Currency;
use App\Models\Entities\Category;
use App\Models\Entities\TransactionType;

/**
 * Tests para módulos que requieren rol admin (CheckRole:admin).
 * Cubre: currencies, account_types, item_categories, items,
 *        providers, taxes, transaction_types, clients, users.
 */
class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    // ── Currencies ────────────────────────────────────────────────────────────

    public function test_currency_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/currencies/', [
            'code' => 'VES', 'name' => 'Bolívar', 'symbol' => 'Bs', 'align' => 'left',
        ]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/currencies/' . $id)->assertStatus(200)
            ->assertJsonPath('data.code', 'VES');

        $this->putJson('/api/v1/currencies/' . $id, ['name' => 'Bolívar Digital'])
            ->assertStatus(200);

        $this->deleteJson('/api/v1/currencies/' . $id)->assertStatus(200);
    }

    public function test_currency_list_active_and_trashed()
    {
        $this->actingAsAdmin();
        Currency::factory()->create(['code' => 'EUR']);

        $this->getJson('/api/v1/currencies/active')->assertStatus(200);
        $this->getJson('/api/v1/currencies/all')->assertStatus(200);
    }

    public function test_currency_change_status()
    {
        $this->actingAsAdmin();
        $c = Currency::factory()->create();

        $this->patchJson('/api/v1/currencies/' . $c->id . '/status')->assertStatus(200);
    }

    public function test_currency_requires_admin()
    {
        // Sin admin → 403
        $this->postJson('/api/v1/currencies/', ['code' => 'USD', 'name' => 'Dollar', 'symbol' => '$', 'align' => 'left'])
            ->assertStatus(403);
    }

    // ── Account Types ─────────────────────────────────────────────────────────

    public function test_account_type_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/account_types/', ['name' => 'Ahorro', 'icon' => 'wallet', 'description' => 'Cuenta de ahorro', 'active' => 1]);
        $this->assertContains($res->status(), [200, 201]);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/account_types/' . $id)->assertStatus(200);
        $this->putJson('/api/v1/account_types/' . $id, ['name' => 'Ahorro Plus'])->assertStatus(200);
        $this->getJson('/api/v1/account_types/active')->assertStatus(200);
        $this->getJson('/api/v1/account_types/all')->assertStatus(200);
        $this->patchJson('/api/v1/account_types/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/account_types/' . $id)->assertStatus(200);
    }

    // ── Item Categories ───────────────────────────────────────────────────────

    public function test_item_category_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/item_categories/', ['name' => 'Electrónica', 'active' => 1]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/item_categories/' . $id)->assertStatus(200);
        $this->putJson('/api/v1/item_categories/' . $id, ['name' => 'Electrónica y Gadgets'])->assertStatus(200);
        $this->getJson('/api/v1/item_categories/active')->assertStatus(200);
        $this->getJson('/api/v1/item_categories/all')->assertStatus(200);
        $this->patchJson('/api/v1/item_categories/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/item_categories/' . $id)->assertStatus(200);
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function test_item_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/items/', ['name' => 'Leche', 'active' => 1]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/items/' . $id)->assertStatus(200);
        $this->putJson('/api/v1/items/' . $id, ['name' => 'Leche Descremada'])->assertStatus(200);
        $this->getJson('/api/v1/items/active')->assertStatus(200);
        $this->getJson('/api/v1/items/all')->assertStatus(200);
        $this->patchJson('/api/v1/items/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/items/' . $id)->assertStatus(200);
    }

    // ── Providers ─────────────────────────────────────────────────────────────

    public function test_provider_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/providers/', ['name' => 'Walmart', 'address' => 'Av. Principal 123', 'active' => 1]);
        $this->assertContains($res->status(), [200, 201]);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/providers/' . $id)->assertStatus(200);
        $this->putJson('/api/v1/providers/' . $id, ['name' => 'Walmart MX'])->assertStatus(200);
        $this->getJson('/api/v1/providers/active')->assertStatus(200);
        $this->getJson('/api/v1/providers/all')->assertStatus(200);
        $this->patchJson('/api/v1/providers/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/providers/' . $id)->assertStatus(200);
    }

    // ── Taxes ─────────────────────────────────────────────────────────────────

    public function test_tax_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/taxes/', [
            'name' => 'IVA 16%', 'percent' => 16, 'active' => 1, 'applies_to' => 'item',
        ]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/taxes/' . $id)->assertStatus(200);
        $this->putJson('/api/v1/taxes/' . $id, ['name' => 'IVA 16% actualizado'])->assertStatus(200);
        $this->getJson('/api/v1/taxes/active')->assertStatus(200);
        $this->getJson('/api/v1/taxes/all')->assertStatus(200);
        $this->patchJson('/api/v1/taxes/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/taxes/' . $id)->assertStatus(200);
    }

    // ── Transaction Types ─────────────────────────────────────────────────────

    public function test_transaction_type_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/transaction_types/', [
            'name' => 'Gasto Extra', 'slug' => 'extra-expense-' . uniqid(),
        ]);
        $this->assertContains($res->status(), [200, 201]);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/transaction_types/' . $id)->assertStatus(200);
        $this->putJson('/api/v1/transaction_types/' . $id, ['name' => 'Gasto Extraordinario'])->assertStatus(200);
        $this->getJson('/api/v1/transaction_types/active')->assertStatus(200);
        $this->getJson('/api/v1/transaction_types/all')->assertStatus(200);
        $this->patchJson('/api/v1/transaction_types/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/transaction_types/' . $id)->assertStatus(200);
    }

    // ── Clients ───────────────────────────────────────────────────────────────

    public function test_client_crud_flow()
    {
        $this->actingAsAdmin();

        $res = $this->postJson('/api/v1/clients/', [
            'name' => 'Cliente Test', 'email' => 'cliente@test.com', 'active' => 1,
        ]);
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $id = $res->json('data.id');

        $this->getJson('/api/v1/clients/' . $id)->assertStatus(200);
        $this->putJson('/api/v1/clients/' . $id, ['name' => 'Cliente Actualizado'])->assertStatus(200);
        $this->getJson('/api/v1/clients/active')->assertStatus(200);
        $this->getJson('/api/v1/clients/all')->assertStatus(200);
        $this->patchJson('/api/v1/clients/' . $id . '/status')->assertStatus(200);
        $this->deleteJson('/api/v1/clients/' . $id)->assertStatus(200);
    }

    // ── Users (admin) ─────────────────────────────────────────────────────────

    public function test_user_crud_flow_as_admin()
    {
        $this->actingAsAdmin();

        // Admin puede listar usuarios
        $this->getJson('/api/v1/users/')->assertStatus(200);
        $this->getJson('/api/v1/users/active')->assertStatus(200);
    }

    public function test_user_change_status_as_admin()
    {
        $this->actingAsAdmin();

        $target = \App\Models\User::factory()->create(['active' => 1]);
        $this->patchJson('/api/v1/users/' . $target->id . '/status')
            ->assertStatus(200)->assertJson(['status' => 'OK']);

        $target->refresh();
        $this->assertEquals(0, $target->active);
    }
}

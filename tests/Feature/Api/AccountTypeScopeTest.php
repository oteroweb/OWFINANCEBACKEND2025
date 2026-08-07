<?php

namespace Tests\Feature\Api;

use App\Models\Entities\AccountType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-367: GET /account_types (catálogo) estaba detrás de CheckRole:admin —
 * cualquier usuario no-admin recibía 403 al intentar listar tipos de cuenta, pese
 * a que crear una cuenta (Configuración, y el nuevo paso de "cuenta inicial" del
 * onboarding) es una feature de cualquier usuario, no admin-only. Mismo patrón de
 * bug ya visto en /providers (OWF-264), /transaction_types (OWF-303) y /taxes (OWF-353).
 */
class AccountTypeScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_can_list_active_account_types(): void
    {
        AccountType::factory()->create(['name' => 'Cuenta Bancaria', 'active' => 1]);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/account_types/active');

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_non_admin_user_can_list_all_account_types(): void
    {
        AccountType::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/account_types');

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    public function test_non_admin_user_can_find_single_account_type(): void
    {
        $type = AccountType::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/account_types/' . $type->id);

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    public function test_non_admin_user_cannot_create_account_type(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->postJson('/api/v1/account_types', ['name' => 'Nuevo']);

        $res->assertStatus(403);
    }

    public function test_non_admin_user_cannot_list_trashed_account_types(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/account_types/all');

        $res->assertStatus(403);
    }

    public function test_admin_can_list_trashed_account_types(): void
    {
        AccountType::factory()->create();
        $this->actingAsAdmin();

        $res = $this->getJson('/api/v1/account_types/all');

        // La ruta literal /all debe resolver a withTrashed(), no al catch-all
        // /{id} con id="all" — bug de orden de registro ya visto en /taxes/all
        // (ver nota de sesión), evitado acá a propósito.
        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertIsArray($res->json('data'));
    }
}

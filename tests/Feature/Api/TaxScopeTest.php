<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-353: GET /taxes (lectura del catálogo) estaba detrás de CheckRole:admin —
 * cualquier usuario no-admin recibía 403 al intentar listar impuestos, pese a que
 * el selector de "Pago múltiple"/"Detalle-factura" es una feature de cualquier
 * usuario Pro, no admin-only. Mismo patrón de bug ya visto en /providers (OWF-264)
 * y /transaction_types (OWF-303).
 */
class TaxScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_can_list_active_taxes(): void
    {
        Tax::factory()->create(['name' => 'IGTF', 'percent' => 3, 'applies_to' => 'payment', 'active' => 1]);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/taxes/active');

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
        $this->assertCount(1, $res->json('data'));
    }

    public function test_non_admin_user_can_list_all_taxes(): void
    {
        Tax::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->getJson('/api/v1/taxes');

        $res->assertStatus(200)->assertJson(['status' => 'OK']);
    }

    public function test_non_admin_user_cannot_create_tax(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->postJson('/api/v1/taxes', ['name' => 'Nuevo', 'percent' => 5]);

        $res->assertStatus(403);
    }

    public function test_non_admin_user_cannot_delete_tax(): void
    {
        $tax = Tax::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->deleteJson('/api/v1/taxes/' . $tax->id);

        $res->assertStatus(403);
    }
}

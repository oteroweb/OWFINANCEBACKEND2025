<?php

namespace Tests\Feature\Api;

use App\Models\Entities\AccountType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-373: un usuario puede crear su propio tipo de cuenta ("Ahorro en oro", "Caja
 * chica del negocio"), visible solo para él, sin tocar el catálogo global admin-only
 * (mismo patrón ya usado por categories.user_id).
 */
class AccountTypeCustomTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_own_custom_account_type(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $res = $this->postJson('/api/v1/account_types/custom', ['name' => 'Ahorro en oro']);

        $res->assertStatus(201)->assertJson(['status' => 'OK']);
        $this->assertDatabaseHas('account_types', ['name' => 'Ahorro en oro', 'user_id' => $user->id]);
    }

    public function test_custom_type_requires_name(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->postJson('/api/v1/account_types/custom', []);

        $res->assertStatus(400);
    }

    public function test_custom_type_is_visible_only_to_its_owner(): void
    {
        AccountType::factory()->create(['name' => 'Cuenta Bancaria', 'user_id' => null, 'active' => 1]);
        $owner = User::factory()->create();
        AccountType::factory()->create(['name' => 'Caja chica', 'user_id' => $owner->id, 'active' => 1]);

        Sanctum::actingAs($owner, ['*']);
        $ownerRes = $this->getJson('/api/v1/account_types/active');
        $ownerRes->assertStatus(200);
        $this->assertCount(2, $ownerRes->json('data'));

        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger, ['*']);
        $strangerRes = $this->getJson('/api/v1/account_types/active');
        $strangerRes->assertStatus(200);
        $this->assertCount(1, $strangerRes->json('data'));
        $this->assertEquals('Cuenta Bancaria', $strangerRes->json('data.0.name'));
    }

    public function test_owner_can_delete_own_custom_type(): void
    {
        $owner = User::factory()->create();
        $type = AccountType::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($owner, ['*']);

        $res = $this->deleteJson('/api/v1/account_types/custom/' . $type->id);

        $res->assertStatus(200);
        $this->assertSoftDeleted('account_types', ['id' => $type->id]);
    }

    public function test_stranger_cannot_delete_someone_elses_custom_type(): void
    {
        $owner = User::factory()->create();
        $type = AccountType::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->deleteJson('/api/v1/account_types/custom/' . $type->id);

        $res->assertStatus(403);
        $this->assertDatabaseHas('account_types', ['id' => $type->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_a_global_type_via_custom_endpoint(): void
    {
        $global = AccountType::factory()->create(['user_id' => null]);
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $res = $this->deleteJson('/api/v1/account_types/custom/' . $global->id);

        $res->assertStatus(403);
        $this->assertDatabaseHas('account_types', ['id' => $global->id, 'deleted_at' => null]);
    }
}

<?php

namespace Tests\Feature\Business;

use App\Models\Entities\Business;
use App\Models\Entities\BusinessUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** OWF-370: empresas — alta, roles, invitaciones, onboarding y reglas del último dueño. */
class BusinessApiTest extends TestCase
{
    use RefreshDatabase;

    private function createBusiness(User $owner, array $extra = []): array
    {
        Sanctum::actingAs($owner, ['*']);
        $res = $this->postJson('/api/v1/businesses', array_merge(['name' => 'Panadería Sol'], $extra));
        $res->assertStatus(201);
        return $res->json('data');
    }

    public function test_create_assigns_owner_role_auto_color_and_defaults(): void
    {
        $owner = User::factory()->create();
        $b = $this->createBusiness($owner, ['tax_id' => 'J-123']);

        $this->assertSame('owner', $b['my_role']);
        $this->assertSame('active', $b['my_status']);
        $this->assertSame('lite', $b['mode']);
        $this->assertSame(Business::PALETTE[0], $b['color']);

        $second = $this->postJson('/api/v1/businesses', ['name' => 'Otra'])->json('data');
        $this->assertSame(Business::PALETTE[1], $second['color'], 'cada empresa nueva toma el siguiente color libre');
    }

    public function test_name_is_required_and_color_must_be_in_palette(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);
        $this->postJson('/api/v1/businesses', [])->assertStatus(400);
        $this->postJson('/api/v1/businesses', ['name' => 'X', 'color' => '#00FF00'])->assertStatus(400);
    }

    public function test_list_returns_only_my_businesses(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->createBusiness($a, ['name' => 'De A']);
        $this->createBusiness($b, ['name' => 'De B']);

        Sanctum::actingAs($a, ['*']);
        $names = collect($this->getJson('/api/v1/businesses')->json('data'))->pluck('name')->all();
        $this->assertSame(['De A'], $names);
    }

    public function test_stranger_cannot_view_update_or_delete(): void
    {
        $owner = User::factory()->create();
        $biz = $this->createBusiness($owner);

        Sanctum::actingAs(User::factory()->create(), ['*']);
        $this->getJson("/api/v1/businesses/{$biz['id']}")->assertStatus(403);
        $this->putJson("/api/v1/businesses/{$biz['id']}", ['name' => 'Hack'])->assertStatus(403);
        $this->deleteJson("/api/v1/businesses/{$biz['id']}")->assertStatus(403);
        $this->assertSame('Panadería Sol', Business::find($biz['id'])->name);
    }

    public function test_invite_accept_flow_and_role_gating(): void
    {
        $owner = User::factory()->create();
        $accountant = User::factory()->create(['email' => 'conta@example.com']);
        $biz = $this->createBusiness($owner);

        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'conta@example.com', 'role' => 'accountant'])
            ->assertStatus(201);

        // Invitado (aún sin aceptar) ve la invitación pero NO la contabilidad.
        Sanctum::actingAs($accountant, ['*']);
        $this->assertSame('invited', $this->getJson('/api/v1/businesses')->json('data.0.my_status'));
        $this->assertFalse($accountant->can('view', Business::find($biz['id'])));

        $this->postJson("/api/v1/businesses/{$biz['id']}/accept")->assertStatus(200);
        $this->assertTrue($accountant->fresh()->can('view', Business::find($biz['id'])));
        $this->assertTrue($accountant->fresh()->can('write', Business::find($biz['id'])));

        // Contador no cambia la estructura ni da acceso.
        $this->putJson("/api/v1/businesses/{$biz['id']}", ['name' => 'Nuevo'])->assertStatus(403);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'x@example.com', 'role' => 'viewer'])->assertStatus(403);
    }

    public function test_invite_validations(): void
    {
        $owner = User::factory()->create();
        User::factory()->create(['email' => 'ya@example.com']);
        $biz = $this->createBusiness($owner);

        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'nadie@example.com', 'role' => 'viewer'])->assertStatus(422);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => $owner->email, 'role' => 'viewer'])->assertStatus(422);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'ya@example.com', 'role' => 'superadmin'])->assertStatus(400);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'ya@example.com', 'role' => 'viewer'])->assertStatus(201);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'ya@example.com', 'role' => 'viewer'])->assertStatus(422);
    }

    public function test_decline_removes_invitation(): void
    {
        $owner = User::factory()->create();
        $guest = User::factory()->create(['email' => 'g@example.com']);
        $biz = $this->createBusiness($owner);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'g@example.com', 'role' => 'viewer']);

        Sanctum::actingAs($guest, ['*']);
        $this->postJson("/api/v1/businesses/{$biz['id']}/decline")->assertStatus(200);
        $this->assertSame(0, BusinessUser::where('user_id', $guest->id)->count());
    }

    public function test_cannot_remove_or_demote_last_owner_but_can_after_adding_another(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create(['email' => 'o2@example.com']);
        $biz = $this->createBusiness($owner);

        $this->deleteJson("/api/v1/businesses/{$biz['id']}/users/{$owner->id}")->assertStatus(422);
        $this->patchJson("/api/v1/businesses/{$biz['id']}/users/{$owner->id}", ['role' => 'viewer'])->assertStatus(422);

        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'o2@example.com', 'role' => 'owner']);
        Sanctum::actingAs($other, ['*']);
        $this->postJson("/api/v1/businesses/{$biz['id']}/accept");

        Sanctum::actingAs($owner, ['*']);
        $this->patchJson("/api/v1/businesses/{$biz['id']}/users/{$owner->id}", ['role' => 'viewer'])->assertStatus(200);
    }

    public function test_member_can_leave_and_owner_can_revoke(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create(['email' => 'v@example.com']);
        $biz = $this->createBusiness($owner);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'v@example.com', 'role' => 'viewer']);
        Sanctum::actingAs($viewer, ['*']);
        $this->postJson("/api/v1/businesses/{$biz['id']}/accept");

        $this->deleteJson("/api/v1/businesses/{$biz['id']}/users/{$viewer->id}")->assertStatus(200);
        $this->assertSame(0, BusinessUser::where('user_id', $viewer->id)->count());

        Sanctum::actingAs($owner, ['*']);
        $this->postJson("/api/v1/businesses/{$biz['id']}/invite", ['email' => 'v@example.com', 'role' => 'viewer']);
        $this->deleteJson("/api/v1/businesses/{$biz['id']}/users/{$viewer->id}")->assertStatus(200);
    }

    public function test_onboarding_saves_profile_and_marks_configured(): void
    {
        $owner = User::factory()->create();
        $biz = $this->createBusiness($owner);
        $this->assertNull($biz['onboarded_at']);

        $res = $this->postJson("/api/v1/businesses/{$biz['id']}/onboarding", [
            'sector' => 'food', 'revenue' => '5to20', 'staff' => '2to5', 'seasonal' => true, 'goal' => 'Abrir una segunda sucursal',
        ])->assertStatus(200);

        $this->assertNotNull($res->json('data.onboarded_at'));
        $this->assertSame('food', $res->json('data.profile.sector'));
        $this->assertTrue($res->json('data.profile.seasonal'));
    }
}

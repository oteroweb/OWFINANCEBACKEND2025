<?php

namespace Tests\Feature\Api;

use App\Models\Entities\FamilyGroup;
use App\Models\Entities\FamilyGroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-369: Fase 1 de "Grupo Familiar y Contabilidad Empresarial" — creación de grupos,
 * invitación por correo, aceptar/rechazar, salir/remover miembros. Un usuario puede
 * pertenecer a varios grupos familiares a la vez (confirmado con el usuario).
 */
class FamilyGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_group_adds_creator_as_active_admin()
    {
        $response = $this->postJson('/api/v1/family-groups', ['name' => 'Familia Otero']);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Familia Otero')
            ->assertJsonPath('data.members.0.role', 'admin')
            ->assertJsonPath('data.members.0.status', 'active');
    }

    public function test_admin_can_invite_existing_user_by_email()
    {
        $invitee = User::factory()->create(['email' => 'mariangela@test.com']);
        $group = FamilyGroup::factory()->create(['owner_user_id' => $this->authUser->id]);
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $this->authUser->id, 'role' => 'admin', 'status' => 'active']);

        $response = $this->postJson("/api/v1/family-groups/{$group->id}/invite", ['email' => 'mariangela@test.com']);

        $response->assertStatus(201)->assertJsonPath('data.status', 'invited');
        $this->assertDatabaseHas('family_group_members', [
            'family_group_id' => $group->id,
            'user_id' => $invitee->id,
            'status' => 'invited',
        ]);
    }

    public function test_invite_fails_for_nonexistent_email()
    {
        $group = FamilyGroup::factory()->create(['owner_user_id' => $this->authUser->id]);
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $this->authUser->id, 'role' => 'admin', 'status' => 'active']);

        $response = $this->postJson("/api/v1/family-groups/{$group->id}/invite", ['email' => 'nadie@test.com']);

        $response->assertStatus(422);
    }

    public function test_non_admin_member_cannot_invite()
    {
        $group = FamilyGroup::factory()->create();
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $this->authUser->id, 'role' => 'member', 'status' => 'active']);
        User::factory()->create(['email' => 'target@test.com']);

        $response = $this->postJson("/api/v1/family-groups/{$group->id}/invite", ['email' => 'target@test.com']);

        $response->assertStatus(403);
    }

    public function test_invited_user_can_accept()
    {
        $group = FamilyGroup::factory()->create();
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $this->authUser->id, 'role' => 'member', 'status' => 'invited']);

        $response = $this->postJson("/api/v1/family-groups/{$group->id}/accept");

        $response->assertStatus(200)->assertJsonPath('data.status', 'active');
    }

    public function test_invited_user_can_decline_and_row_is_removed()
    {
        $group = FamilyGroup::factory()->create();
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $this->authUser->id, 'role' => 'member', 'status' => 'invited']);

        $response = $this->postJson("/api/v1/family-groups/{$group->id}/decline");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('family_group_members', ['family_group_id' => $group->id, 'user_id' => $this->authUser->id]);
    }

    public function test_member_can_leave_group()
    {
        $group = FamilyGroup::factory()->create();
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $this->authUser->id, 'role' => 'member', 'status' => 'active']);

        $response = $this->deleteJson("/api/v1/family-groups/{$group->id}/members/{$this->authUser->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('family_group_members', ['family_group_id' => $group->id, 'user_id' => $this->authUser->id]);
    }

    public function test_cannot_remove_the_group_owner()
    {
        $owner = User::factory()->create();
        $group = FamilyGroup::factory()->create(['owner_user_id' => $owner->id]);
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $owner->id, 'role' => 'admin', 'status' => 'active']);
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $this->authUser->id, 'role' => 'admin', 'status' => 'active']);

        $response = $this->deleteJson("/api/v1/family-groups/{$group->id}/members/{$owner->id}");

        $response->assertStatus(422);
    }

    public function test_user_can_belong_to_more_than_one_family_group()
    {
        $groupA = FamilyGroup::factory()->create();
        $groupB = FamilyGroup::factory()->create();
        FamilyGroupMember::create(['family_group_id' => $groupA->id, 'user_id' => $this->authUser->id, 'role' => 'member', 'status' => 'active']);
        FamilyGroupMember::create(['family_group_id' => $groupB->id, 'user_id' => $this->authUser->id, 'role' => 'member', 'status' => 'active']);

        $response = $this->getJson('/api/v1/family-groups');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_non_member_cannot_view_group()
    {
        $group = FamilyGroup::factory()->create();

        $response = $this->getJson("/api/v1/family-groups/{$group->id}");

        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use App\Models\Entities\Currency;
use App\Models\Entities\FamilyGroup;
use App\Models\Entities\FamilyGroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-369: Fase 1 — compartir una cuenta con un miembro del grupo familiar, con nivel
 * de permiso (manage|view_full|view_balance). Candado de negocio: solo se puede
 * compartir con alguien que ya sea miembro activo de un grupo familiar en común —
 * verificado en el endpoint, no solo en la UI.
 */
class AccountSharingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccountFor(User $owner): Account
    {
        $account = Account::factory()->create([
            'currency_id' => Currency::factory()->create()->id,
            'account_type_id' => AccountType::factory()->create()->id,
        ]);
        $account->users()->attach($owner->id, ['is_owner' => true]);
        return $account;
    }

    private function putInSameFamilyGroup(User $a, User $b): void
    {
        $group = FamilyGroup::factory()->create(['owner_user_id' => $a->id]);
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $a->id, 'role' => 'admin', 'status' => 'active']);
        FamilyGroupMember::create(['family_group_id' => $group->id, 'user_id' => $b->id, 'role' => 'member', 'status' => 'active']);
    }

    public function test_owner_can_share_account_with_family_group_member()
    {
        $member = User::factory()->create();
        $this->putInSameFamilyGroup($this->authUser, $member);
        $account = $this->makeAccountFor($this->authUser);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/share", [
            'user_id' => $member->id,
            'permission' => 'view_full',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('account_user', [
            'account_id' => $account->id,
            'user_id' => $member->id,
            'is_owner' => 0,
            'permission' => 'view_full',
            'shared_by_user_id' => $this->authUser->id,
        ]);
    }

    public function test_cannot_share_with_someone_outside_the_family_group()
    {
        $stranger = User::factory()->create();
        $account = $this->makeAccountFor($this->authUser);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/share", [
            'user_id' => $stranger->id,
            'permission' => 'view_balance',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('account_user', ['account_id' => $account->id, 'user_id' => $stranger->id]);
    }

    public function test_non_owner_cannot_share_the_account()
    {
        // authUser is a family-group peer of the real account owner, but has no pivot
        // row on the account itself (not owner, not shared with) — sharing must be
        // denied regardless of the family-group relationship.
        $owner = User::factory()->create();
        $account = $this->makeAccountFor($owner);
        $this->putInSameFamilyGroup($owner, $this->authUser);

        $someoneElse = User::factory()->create();
        $this->putInSameFamilyGroup($this->authUser, $someoneElse);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/share", [
            'user_id' => $someoneElse->id,
            'permission' => 'manage',
        ]);

        $response->assertStatus(403);
    }

    public function test_rejects_invalid_permission_value()
    {
        $member = User::factory()->create();
        $this->putInSameFamilyGroup($this->authUser, $member);
        $account = $this->makeAccountFor($this->authUser);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/share", [
            'user_id' => $member->id,
            'permission' => 'yolo',
        ]);

        $response->assertStatus(400);
    }

    public function test_owner_can_revoke_a_share()
    {
        $member = User::factory()->create();
        $this->putInSameFamilyGroup($this->authUser, $member);
        $account = $this->makeAccountFor($this->authUser);
        $account->users()->attach($member->id, ['is_owner' => 0, 'permission' => 'manage', 'shared_by_user_id' => $this->authUser->id]);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/share/{$member->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('account_user', ['account_id' => $account->id, 'user_id' => $member->id]);
    }

    public function test_cannot_revoke_the_real_owner_via_share_endpoint()
    {
        $account = $this->makeAccountFor($this->authUser);

        $response = $this->deleteJson("/api/v1/accounts/{$account->id}/share/{$this->authUser->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('account_user', ['account_id' => $account->id, 'user_id' => $this->authUser->id, 'is_owner' => 1]);
    }

    public function test_shared_with_me_lists_accounts_shared_by_others_with_permission_and_owner()
    {
        $owner = User::factory()->create(['name' => 'Mariangela']);
        $this->putInSameFamilyGroup($owner, $this->authUser);
        $account = $this->makeAccountFor($owner);
        $account->users()->attach($this->authUser->id, ['is_owner' => 0, 'permission' => 'view_balance', 'shared_by_user_id' => $owner->id]);

        $response = $this->getJson('/api/v1/accounts/shared-with-me');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('view_balance', $data[0]['permission']);
        $this->assertEquals('Mariangela', $data[0]['owner']['name']);
    }

    public function test_shared_with_me_does_not_include_own_accounts()
    {
        $this->makeAccountFor($this->authUser);

        $response = $this->getJson('/api/v1/accounts/shared-with-me');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }
}

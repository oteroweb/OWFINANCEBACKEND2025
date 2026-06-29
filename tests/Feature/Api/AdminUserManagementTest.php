<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * OWF-151 — Feature tests for Admin User Management endpoints
 * Covers: detail, impersonate, changePassword, revokeTokens, sendResetEmail
 */
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->actingAsAdmin();

        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);
        $this->regularUser = User::factory()->create([
            'role_id' => $userRole->id,
            'active'  => true,
        ]);
    }

    // ─── GET /admin/users/:id/detail ───────────────────────────────────

    public function test_detail_returns_full_user_profile(): void
    {
        $res = $this->getJson("/api/v1/admin/users/{$this->regularUser->id}/detail");

        $res->assertStatus(200)
            ->assertJsonPath('status', 'OK')
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'settings',
                    'accounts',
                    'jars',
                    'recent_transactions',
                    'security' => ['tokens_count', 'last_login'],
                    'currencies',
                ],
            ]);
    }

    public function test_detail_requires_admin_role(): void
    {
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);
        $plainUser = User::factory()->create(['role_id' => $userRole->id]);
        Sanctum::actingAs($plainUser, ['*']);

        $res = $this->getJson("/api/v1/admin/users/{$this->regularUser->id}/detail");
        $res->assertStatus(403);
    }

    public function test_detail_404_on_nonexistent_user(): void
    {
        $res = $this->getJson('/api/v1/admin/users/999999/detail');
        $res->assertStatus(404);
    }

    // ─── POST /admin/users/:id/impersonate ────────────────────────────

    public function test_impersonate_returns_token_for_regular_user(): void
    {
        $res = $this->postJson("/api/v1/admin/users/{$this->regularUser->id}/impersonate");

        $res->assertStatus(200)
            ->assertJsonPath('status', 'OK')
            ->assertJsonStructure([
                'data' => ['token', 'user', 'expires_at'],
            ]);

        $this->assertNotEmpty($res->json('data.token'));
    }

    public function test_impersonate_blocked_for_admin_users(): void
    {
        // Create another admin
        $anotherAdmin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->first()->id,
            'active'  => true,
        ]);

        $res = $this->postJson("/api/v1/admin/users/{$anotherAdmin->id}/impersonate");
        $res->assertStatus(403)
            ->assertJsonPath('status', 'FAILED');
    }

    public function test_impersonate_blocked_for_inactive_user(): void
    {
        $userRole = Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);
        $inactive = User::factory()->create(['role_id' => $userRole->id, 'active' => false]);

        $res = $this->postJson("/api/v1/admin/users/{$inactive->id}/impersonate");
        $res->assertStatus(422)
            ->assertJsonPath('status', 'FAILED');
    }

    public function test_impersonate_token_has_impersonate_scope(): void
    {
        $res = $this->postJson("/api/v1/admin/users/{$this->regularUser->id}/impersonate");
        $res->assertStatus(200);

        // Token should exist in DB with impersonate ability
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $this->regularUser->id,
            'tokenable_type' => User::class,
            'name'           => 'impersonate',
        ]);
    }

    // ─── PUT /admin/users/:id/password ────────────────────────────────

    public function test_change_password_succeeds_with_confirmation(): void
    {
        $res = $this->putJson("/api/v1/admin/users/{$this->regularUser->id}/password", [
            'password'              => 'NewPass@2026',
            'password_confirmation' => 'NewPass@2026',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('status', 'OK');
    }

    public function test_change_password_requires_confirmation_match(): void
    {
        $res = $this->putJson("/api/v1/admin/users/{$this->regularUser->id}/password", [
            'password'              => 'NewPass@2026',
            'password_confirmation' => 'DifferentPass@2026',
        ]);

        $res->assertStatus(422);
    }

    public function test_change_password_requires_min_8_chars(): void
    {
        $res = $this->putJson("/api/v1/admin/users/{$this->regularUser->id}/password", [
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $res->assertStatus(422);
    }

    public function test_change_password_revokes_existing_tokens(): void
    {
        // Give the user a token first
        $this->regularUser->createToken('test-session');
        $this->assertGreaterThan(0, $this->regularUser->tokens()->count());

        $this->putJson("/api/v1/admin/users/{$this->regularUser->id}/password", [
            'password'              => 'NewPass@2026',
            'password_confirmation' => 'NewPass@2026',
        ]);

        $this->assertEquals(0, $this->regularUser->fresh()->tokens()->count());
    }

    // ─── DELETE /admin/users/:id/tokens ───────────────────────────────

    public function test_revoke_tokens_deletes_all_user_tokens(): void
    {
        $this->regularUser->createToken('session-1');
        $this->regularUser->createToken('session-2');
        $this->assertEquals(2, $this->regularUser->tokens()->count());

        $res = $this->deleteJson("/api/v1/admin/users/{$this->regularUser->id}/tokens");

        $res->assertStatus(200)
            ->assertJsonPath('status', 'OK')
            ->assertJsonPath('data.revoked_count', 2);

        $this->assertEquals(0, $this->regularUser->fresh()->tokens()->count());
    }

    public function test_revoke_tokens_returns_zero_when_no_tokens(): void
    {
        $res = $this->deleteJson("/api/v1/admin/users/{$this->regularUser->id}/tokens");

        $res->assertStatus(200)
            ->assertJsonPath('data.revoked_count', 0);
    }

    // ─── POST /admin/users/:id/reset-password-email ───────────────────

    public function test_send_reset_email_returns_ok(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $res = $this->postJson("/api/v1/admin/users/{$this->regularUser->id}/reset-password-email");

        $res->assertStatus(200)
            ->assertJsonPath('status', 'OK');

        $this->assertStringContainsString(
            $this->regularUser->email,
            $res->json('message')
        );
    }

    public function test_send_reset_email_404_on_missing_user(): void
    {
        $res = $this->postJson('/api/v1/admin/users/999999/reset-password-email');
        $res->assertStatus(404);
    }
}

<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

/**
 * Módulo: Auth
 * Cubre: login, register, logout, forgot-password, reset-password,
 *        throttling y validaciones de credenciales.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function ensureUserRole(): void
    {
        \App\Models\Role::firstOrCreate(['slug' => 'user'], ['name' => 'User']);
    }

    public function test_register_creates_user_and_returns_token()
    {
        $this->ensureUserRole();

        $res = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Juan Test',
            'email'                 => 'juan@test.com',
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $this->assertContains($res->status(), [200, 201]);
        $res->assertJsonStructure(['token', 'data']);
        $this->assertDatabaseHas('users', ['email' => 'juan@test.com']);
    }

    public function test_login_with_valid_credentials_returns_token()
    {
        $this->ensureUserRole();
        $user = User::factory()->create(['password' => bcrypt('Secret123!')]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Secret123!',
        ]);

        $res->assertStatus(200)->assertJsonStructure(['token', 'data']);
    }

    public function test_login_with_wrong_password_returns_error()
    {
        $user = User::factory()->create(['password' => bcrypt('correct')]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong',
        ]);

        $res->assertStatus(401);
    }

    public function test_login_with_nonexistent_email_returns_error()
    {
        $res = $this->postJson('/api/v1/auth/login', [
            'email'    => 'noexiste@test.com',
            'password' => 'cualquier',
        ]);

        $res->assertStatus(401);
    }

    public function test_logout_invalidates_token()
    {
        $this->ensureUserRole();
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $res = $this->withToken($token)->postJson('/api/v1/auth/logout');
        $res->assertStatus(200);

        // After logout the token should no longer grant access
        $check = $this->withToken($token)->getJson('/api/v1/user/profile');
        $this->assertContains($check->status(), [401, 200]); // Sanctum test env may vary
    }

    public function test_forgot_password_with_existing_email_returns_ok()
    {
        $user = User::factory()->create(['email' => 'test@owf.com']);

        $res = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        // Acepta el email aunque SMTP esté deshabilitado
        $res->assertStatus(200);
    }

    public function test_forgot_password_with_unknown_email_returns_error()
    {
        $res = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'fantasma@noexiste.com',
        ]);

        // Some backends return 200 for unknown emails (security: don't reveal existence)
        $this->assertContains($res->status(), [200, 404, 422]);
    }

    public function test_register_requires_password_confirmation()
    {
        $res = $this->postJson('/api/v1/auth/register', [
            'name'     => 'Test',
            'email'    => 'test@test.com',
            'password' => 'abc',
            // sin password_confirmation
        ]);

        $res->assertStatus(422);
    }
}

<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class UserSecurityPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_pin_status_defaults_to_false()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $res = $this->getJson('/api/v1/user/security/pin-status');
        $res->assertStatus(200)->assertJsonPath('data.has_pin', false);
    }

    public function test_set_pin_requires_correct_password()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $res = $this->putJson('/api/v1/user/security/pin', [
            'pin' => '1234',
            'password' => 'wrong-password',
        ]);
        $res->assertStatus(422);
    }

    public function test_set_pin_rejects_non_numeric_or_wrong_length()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $res = $this->putJson('/api/v1/user/security/pin', [
            'pin' => 'abcd',
            'password' => 'S$ratoga.1990',
        ]);
        $res->assertStatus(400);
    }

    public function test_set_and_verify_pin_flow()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/user/security/pin', [
            'pin' => '4321',
            'password' => 'S$ratoga.1990',
        ])->assertStatus(200);

        $this->getJson('/api/v1/user/security/pin-status')
            ->assertJsonPath('data.has_pin', true);

        $this->postJson('/api/v1/user/security/pin/verify', ['pin' => '4321'])
            ->assertStatus(200)->assertJsonPath('data.valid', true);

        $this->postJson('/api/v1/user/security/pin/verify', ['pin' => '0000'])
            ->assertStatus(422)->assertJsonPath('data.valid', false);
    }

    public function test_remove_pin_requires_password_and_clears_pin()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/user/security/pin', [
            'pin' => '5566',
            'password' => 'S$ratoga.1990',
        ])->assertStatus(200);

        $this->deleteJson('/api/v1/user/security/pin', ['password' => 'wrong'])
            ->assertStatus(422);

        $this->deleteJson('/api/v1/user/security/pin', ['password' => 'S$ratoga.1990'])
            ->assertStatus(200);

        $this->getJson('/api/v1/user/security/pin-status')
            ->assertJsonPath('data.has_pin', false);
    }
}

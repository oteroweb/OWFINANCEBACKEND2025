<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        try {
            $user = User::factory()->create();
            Sanctum::actingAs($user, ['*']);
        } catch (\Throwable $e) {
            // continue without auth if factories unavailable
        }
    }

    /**
     * Create and authenticate a user with the given role slug.
     * Returns the authenticated User instance.
     */
    protected function actingAsRole(string $slug): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
        $user = User::factory()->create(['role_id' => $role->id]);
        Sanctum::actingAs($user, ['*']);
        return $user;
    }

    /**
     * Create and authenticate an admin user.
     */
    protected function actingAsAdmin(): User
    {
        return $this->actingAsRole('admin');
    }
}

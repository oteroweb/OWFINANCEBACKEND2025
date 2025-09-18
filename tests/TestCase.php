<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

/**
 * Base test case bootstrapping the application and authenticating a default user
 * so protected API routes pass in Feature tests unless a test overrides auth.
 */

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Authenticate a default user for API requests that require sanctum
        try {
            $userModel = \App\Models\User::class;
            $user = $userModel::factory()->create();
            Sanctum::actingAs($user, ['*']);
        } catch (\Throwable $e) {
            // If factories are not available for some reason, continue without auth
        }
    }
}

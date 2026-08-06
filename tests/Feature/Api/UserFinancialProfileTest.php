<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OWF-366: los campos Pro avanzados (income_detail/risk_tolerance/time_horizon/
 * goal_priority) vivían solo en localStorage del frontend — nunca persistían en
 * el backend porque UserFinancialProfileController no los conocía. Migrados a
 * columnas reales de ai_user_settings, validados y expuestos igual que el resto
 * del perfil narrativo.
 */
class UserFinancialProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_defaults_for_new_user()
    {
        $response = $this->getJson('/api/v1/user/financial-profile');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'occupation' => null,
                    'income_detail' => null,
                    'risk_tolerance' => null,
                    'time_horizon' => null,
                    'goal_priority' => null,
                    'onboarding_profile_completed' => false,
                ],
            ]);
    }

    public function test_update_persists_advanced_profile_fields()
    {
        $response = $this->putJson('/api/v1/user/financial-profile', [
            'occupation' => 'employee',
            'main_goal' => 'invest',
            'income_detail' => 'variable',
            'risk_tolerance' => 'agresivo',
            'time_horizon' => 'largo',
            'goal_priority' => 'crecimiento',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'income_detail' => 'variable',
                    'risk_tolerance' => 'agresivo',
                    'time_horizon' => 'largo',
                    'goal_priority' => 'crecimiento',
                ],
            ]);

        $follow = $this->getJson('/api/v1/user/financial-profile');
        $follow->assertStatus(200)
            ->assertJson([
                'data' => [
                    'income_detail' => 'variable',
                    'risk_tolerance' => 'agresivo',
                    'time_horizon' => 'largo',
                    'goal_priority' => 'crecimiento',
                ],
            ]);
    }

    public function test_update_rejects_invalid_advanced_field_values()
    {
        $response = $this->putJson('/api/v1/user/financial-profile', [
            'risk_tolerance' => 'yolo',
        ]);

        $response->assertStatus(422);
    }
}

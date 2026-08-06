<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_user_settings', function (Blueprint $table) {
            // OWF-366: campos Pro avanzados del perfil financiero — vivían solo en
            // localStorage del navegador (nunca persistidos en el backend, ver
            // financial-profile/index.vue OWF-359).
            $table->string('income_detail')->nullable()->after('onboarding_profile_completed'); // 'estable' | 'variable' | 'mixto'
            $table->string('risk_tolerance')->nullable()->after('income_detail');                // 'conservador' | 'equilibrado' | 'agresivo'
            $table->string('time_horizon')->nullable()->after('risk_tolerance');                 // 'corto' | 'medio' | 'largo'
            $table->string('goal_priority')->nullable()->after('time_horizon');                  // 'seguridad' | 'crecimiento' | 'experiencias'
        });
    }

    public function down(): void
    {
        Schema::table('ai_user_settings', function (Blueprint $table) {
            $table->dropColumn(['income_detail', 'risk_tolerance', 'time_horizon', 'goal_priority']);
        });
    }
};

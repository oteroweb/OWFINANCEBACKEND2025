<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_user_settings', function (Blueprint $table) {
            // Perfil narrativo del usuario (para contexto del asesor IA)
            $table->string('occupation')->nullable()->after('context_window_months');
            $table->string('income_range')->nullable()->after('occupation');       // '<500' | '500-1500' | '1500-4000' | '>4000'
            $table->string('living_situation')->nullable()->after('income_range'); // 'solo' | 'pareja' | 'familia' | 'roommates'
            $table->string('debt_situation')->nullable()->after('living_situation'); // 'none' | 'credit_card' | 'personal_loan' | 'mortgage' | 'multiple'
            $table->string('emergency_fund')->nullable()->after('debt_situation');  // 'none' | '<3m' | '3-6m' | '>6m'
            $table->string('money_relationship')->nullable()->after('emergency_fund'); // 'want_improve' | 'organized' | 'hard_to_save' | 'day_to_day'
            $table->string('main_goal')->nullable()->after('money_relationship');  // 'debt_free' | 'emergency_fund' | 'saving_goal' | 'invest' | 'survive'
            $table->text('dream')->nullable()->after('main_goal');                 // texto libre: sueño a largo plazo
            $table->string('emotional_keyword')->nullable()->after('dream');        // 'tranquilo' | 'libre' | 'seguro' | 'control' | 'prospero'

            // Control de onboarding
            $table->boolean('onboarding_profile_completed')->default(false)->after('emotional_keyword');
        });
    }

    public function down(): void
    {
        Schema::table('ai_user_settings', function (Blueprint $table) {
            $table->dropColumn([
                'occupation', 'income_range', 'living_situation', 'debt_situation',
                'emergency_fund', 'money_relationship', 'main_goal', 'dream',
                'emotional_keyword', 'onboarding_profile_completed',
            ]);
        });
    }
};

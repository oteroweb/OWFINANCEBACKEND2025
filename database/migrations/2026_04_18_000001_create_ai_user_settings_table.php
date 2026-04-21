<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->unique();
            $table->string('advisor_name')->default('Asesor IA');
            $table->enum('advisor_personality', ['formal', 'friendly', 'coach'])->default('friendly');
            $table->boolean('voice_enabled')->default(true);
            $table->boolean('ocr_enabled')->default(true);
            $table->boolean('auto_ia_enabled')->default(true);
            $table->boolean('advisor_enabled')->default(true);
            $table->decimal('monthly_budget_alert', 10, 2)->nullable();
            $table->string('preferred_currency', 3)->default('USD');
            $table->tinyInteger('context_window_months')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('feature', ['voice', 'ocr', 'auto_ia', 'advisor']);
            $table->string('provider_name', 20)->default('anthropic');
            $table->string('model_used');
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->integer('cache_read_tokens')->default(0);
            $table->integer('cache_creation_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 10, 6)->default(0);
            $table->date('date');
            $table->timestamps();

            $table->index(['user_id', 'date', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_log');
    }
};

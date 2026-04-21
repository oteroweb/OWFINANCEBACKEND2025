<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->integer('total_messages')->default(0);
            $table->integer('total_input_tokens')->default(0);
            $table->integer('total_output_tokens')->default(0);
            $table->integer('total_cache_read_tokens')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};

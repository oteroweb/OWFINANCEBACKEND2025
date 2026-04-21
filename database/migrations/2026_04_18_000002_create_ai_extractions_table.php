<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('source', ['voice', 'ocr', 'auto'])->default('auto');
            $table->text('raw_input');
            $table->json('extracted_data');
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->boolean('was_confirmed')->default(false);
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('model_used')->default('claude-haiku-4-5');
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->integer('cache_read_tokens')->default(0);
            $table->integer('processing_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_extractions');
    }
};

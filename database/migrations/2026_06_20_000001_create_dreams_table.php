<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dreams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('emoji', 8)->nullable();
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->decimal('saved_amount', 15, 2)->default(0);
            $table->string('color', 32)->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dreams');
    }
};

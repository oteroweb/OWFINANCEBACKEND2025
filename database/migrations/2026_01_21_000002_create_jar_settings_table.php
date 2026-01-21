<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('global_start_date')->nullable();
            $table->boolean('default_allow_negative')->default(false);
            $table->decimal('default_negative_limit', 12, 2)->nullable();
            $table->enum('default_reset_cycle', ['none', 'monthly', 'quarterly', 'semiannual', 'annual'])
                ->default('none');
            $table->tinyInteger('default_reset_cycle_day')->default(1);
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jar_id')->constrained('jars')->onDelete('cascade');
            $table->date('cycle_start_date');
            $table->date('cycle_end_date');
            $table->decimal('starting_balance', 12, 2)->default(0);
            $table->decimal('ending_balance', 12, 2)->default(0);
            $table->decimal('total_allocated', 12, 2)->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('total_adjustments', 12, 2)->default(0);
            $table->decimal('total_withdrawals', 12, 2)->default(0);
            $table->decimal('carryover_to_next', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['jar_id', 'cycle_start_date', 'cycle_end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_cycles');
    }
};

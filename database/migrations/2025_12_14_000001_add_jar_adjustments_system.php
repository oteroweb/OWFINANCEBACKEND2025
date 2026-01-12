<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add adjustment tracking to jars table
        Schema::table('jars', function (Blueprint $table) {
            $table->decimal('adjustment', 12, 2)->default(0)->after('percent')->comment('Manual adjustment to available balance');
            $table->string('refresh_mode')->default('reset')->after('adjustment')->comment('reset = monthly reset, accumulative = accumulate balance');
        });

        // Create jar_adjustments table for audit trail
        Schema::create('jar_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jar_id')->constrained('jars')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->comment('Amount adjusted (positive or negative)');
            $table->enum('type', ['increment', 'decrement'])->comment('Type of adjustment');
            $table->text('reason')->nullable()->comment('Reason for adjustment');
            $table->decimal('previous_available', 12, 2)->comment('Available balance before adjustment');
            $table->decimal('new_available', 12, 2)->comment('Available balance after adjustment');
            $table->date('adjustment_date')->comment('Date when adjustment applies');
            $table->timestamps();

            $table->index(['jar_id', 'adjustment_date']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            $table->dropColumn(['adjustment', 'refresh_mode']);
        });

        Schema::dropIfExists('jar_adjustments');
    }
};

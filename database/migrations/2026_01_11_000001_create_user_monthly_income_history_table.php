<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates a table to track historical changes to user's monthly income.
     * This allows the system to remember what monthly_income was configured
     * for each month, enabling accurate historical calculations of jar balances.
     */
    public function up(): void
    {
        Schema::create('user_monthly_income_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('monthly_income', 10, 2)->comment('Expected monthly income for this period');
            $table->date('month')->comment('First day of the month (e.g., 2025-11-01)');
            $table->text('notes')->nullable()->comment('Optional notes about the change');
            $table->timestamps();

            // Ensure one record per user per month
            $table->unique(['user_id', 'month'], 'unique_user_month');
            
            // Index for fast lookups by user and month
            $table->index(['user_id', 'month'], 'idx_user_month');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_monthly_income_history');
    }
};

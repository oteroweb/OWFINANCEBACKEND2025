<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_monthly_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jar_id')->constrained('jars')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('month'); // Always stored as YYYY-MM-01
            $table->decimal('percent', 10, 2)->nullable(); // Override percent for this month (null = use jar default)
            $table->decimal('fixed_amount', 15, 2)->nullable(); // Override fixed amount for this month (null = use jar default)
            $table->string('notes')->nullable();
            $table->timestamps();

            // Each jar can only have one override per month
            $table->unique(['jar_id', 'month'], 'jar_monthly_overrides_jar_month_unique');
            $table->index(['user_id', 'month'], 'jar_monthly_overrides_user_month_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_monthly_overrides');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jar_id')->constrained('jars')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->comment('Amount withdrawn from jar');
            $table->text('description')->nullable();
            $table->decimal('previous_available', 12, 2)->comment('Available balance before withdrawal');
            $table->decimal('new_available', 12, 2)->comment('Available balance after withdrawal');
            $table->date('withdrawal_date')->comment('Date when withdrawal applies');
            $table->timestamps();

            $table->index(['jar_id', 'withdrawal_date']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_withdrawals');
    }
};

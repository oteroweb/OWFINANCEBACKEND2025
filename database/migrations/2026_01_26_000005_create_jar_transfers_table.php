<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jar_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('from_jar_id')->constrained('jars')->onDelete('cascade');
            $table->foreignId('to_jar_id')->constrained('jars')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->comment('Amount transferred');
            $table->text('description')->nullable();
            $table->decimal('from_previous_available', 12, 2)->comment('Source available before transfer');
            $table->decimal('from_new_available', 12, 2)->comment('Source available after transfer');
            $table->decimal('to_previous_available', 12, 2)->comment('Target available before transfer');
            $table->decimal('to_new_available', 12, 2)->comment('Target available after transfer');
            $table->date('transfer_date')->comment('Date when transfer applies');
            $table->timestamps();

            $table->index(['from_jar_id', 'transfer_date']);
            $table->index(['to_jar_id', 'transfer_date']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jar_transfers');
    }
};

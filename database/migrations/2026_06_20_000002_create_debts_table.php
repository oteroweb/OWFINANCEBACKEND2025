<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('provider', 32)->default('loan'); // cashea | card | loan | personal
            $table->string('merchant', 100)->nullable();
            $table->decimal('original_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);      // pendiente actual
            $table->decimal('next_due_amount', 15, 2)->default(0); // cuota próxima
            $table->date('next_due_date')->nullable();
            $table->unsignedSmallInteger('total_installments')->nullable(); // null = sin cuotas fijas
            $table->unsignedSmallInteger('paid_installments')->default(0);
            $table->string('rate', 32)->nullable();             // "0% interés", "24% TEA", etc
            $table->string('status', 20)->default('on-track'); // on-track | due-soon | late | paid
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'deleted_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};

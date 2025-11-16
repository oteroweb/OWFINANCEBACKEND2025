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
        Schema::create('user_currencies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('current_rate', 18, 8)->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->index(['user_id', 'currency_id']);
        });

        // Opcional: constraint lógica de "máximo un is_current=1 por (user,currency)" se maneja a nivel app.
        // Si prefieres una restricción a nivel BD, puedes usar un índice parcial (no soportado en MySQL <= 8),
        // o una unique con trigger. Aquí lo dejamos a nivel aplicación para portabilidad.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_currencies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// OWF-370: Fase 2 "Grupo Familiar y Contabilidad Empresarial" — una empresa es un
// contenedor de contabilidad separada (ver rediseno/ARQUITECTURA_GRUPO_FAMILIAR_EMPRESAS.md §02).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('name', 100);
            $table->string('tax_id', 40)->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            // D-009: el modo Lite/Pro es propio de cada contabilidad, no global del usuario.
            $table->string('mode', 8)->default('lite');
            // D-009: color de contexto (paleta cerrada, ver Business::PALETTE).
            $table->string('color', 9)->default('#3B5BDB');
            // D-011: respuestas del onboarding (rubro, facturación, empleados, temporadas, meta).
            $table->json('profile')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('owner_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};

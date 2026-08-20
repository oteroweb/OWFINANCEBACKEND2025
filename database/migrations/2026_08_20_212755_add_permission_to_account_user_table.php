<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_user', function (Blueprint $table) {
            // OWF: nivel de permiso para cuentas compartidas dentro de un grupo familiar.
            // null = fila implícita de dueño (is_owner=1 ya la cubre, filas existentes no
            // se tocan). Para una fila creada por el flujo de "compartir cuenta" siempre
            // es una de: manage | view_full | view_balance.
            $table->string('permission')->nullable()->after('is_owner');
            $table->unsignedBigInteger('shared_by_user_id')->nullable()->after('permission');

            $table->foreign('shared_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('account_user', function (Blueprint $table) {
            $table->dropForeign(['shared_by_user_id']);
            $table->dropColumn(['permission', 'shared_by_user_id']);
        });
    }
};

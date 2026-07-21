<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OWF-326: split por categoría del panel "Gasto compartido" (proPanel === 'shared' en
     * SmartTransactionModal.vue). Tabla dedicada en vez de reusar item_transactions —
     * esa tabla ya acumuló bastante lógica específica de factura (quantity, tax_id,
     * rate_id, item_id, is_fee, fee_type) que no aplica acá y confundiría reportes
     * futuros que esperen "items" como líneas de compra reales, no categorías que
     * comparten un mismo gasto.
     */
    public function up(): void
    {
        Schema::create('shared_transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('jar_id')->nullable();
            $table->timestamps();

            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('jar_id')->references('id')->on('jars')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_transaction_categories');
    }
};

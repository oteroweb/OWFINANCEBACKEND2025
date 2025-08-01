<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_taxes', function (Blueprint $table) {
            if (!Schema::hasColumn('item_taxes', 'item_transaction_id')) {
                $table->unsignedBigInteger('item_transaction_id')->after('id');
                $table->foreign('item_transaction_id')->references('id')->on('item_transactions')->onDelete('cascade');
            }
        });
    }
    public function down(): void
    {
        Schema::table('item_taxes', function (Blueprint $table) {
            $table->dropForeign(['item_transaction_id']);
            $table->dropColumn('item_transaction_id');
        });
    }
};

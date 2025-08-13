<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // add nullable foreign key column
            $table->unsignedBigInteger('transaction_type_id')->nullable()->after('rate_id');
            // if you want to keep old column for now, leave it; otherwise you could drop it after data migration
            $table->dropColumn('transaction_type'); // we'll keep until app is migrated to use FK
            $table->foreign('transaction_type_id')->references('id')->on('transaction_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['transaction_type_id']);
            $table->dropColumn('transaction_type_id');
        });
    }
};

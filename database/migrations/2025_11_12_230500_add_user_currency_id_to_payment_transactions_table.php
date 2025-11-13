<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_transactions', 'user_currency_id')) {
                $table->unsignedBigInteger('user_currency_id')->nullable()->after('account_id');
                $table->foreign('user_currency_id')->references('id')->on('user_currencies')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('payment_transactions', 'user_currency_id')) {
                $table->dropForeign(['user_currency_id']);
                $table->dropColumn('user_currency_id');
            }
        });
    }
};

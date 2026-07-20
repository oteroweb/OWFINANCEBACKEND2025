<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('commission_type')->nullable()->after('amount_tax');
            $table->decimal('commission_value', 12, 2)->nullable()->after('commission_type');
            $table->decimal('commission_amount', 12, 2)->nullable()->after('commission_value');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_value', 'commission_amount']);
        });
    }
};

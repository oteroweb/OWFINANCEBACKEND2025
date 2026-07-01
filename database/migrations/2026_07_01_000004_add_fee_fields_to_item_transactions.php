<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_transactions', function (Blueprint $table) {
            $table->boolean('is_fee')->default(false)->after('custom_name');
            $table->string('fee_type')->nullable()->after('is_fee');
        });
    }

    public function down(): void
    {
        Schema::table('item_transactions', function (Blueprint $table) {
            $table->dropColumn(['is_fee', 'fee_type']);
        });
    }
};

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
        Schema::table('jar_leverage_settings', function (Blueprint $table) {
            $table->boolean('use_real_income')->default(false)->after('leverage_jar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jar_leverage_settings', function (Blueprint $table) {
            $table->dropColumn('use_real_income');
        });
    }
};

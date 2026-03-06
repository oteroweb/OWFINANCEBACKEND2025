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
        Schema::table('jar_settings', function (Blueprint $table) {
            $table->boolean('auto_leverage_enabled')->default(false)->after('leverage_jar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jar_settings', function (Blueprint $table) {
            $table->dropColumn('auto_leverage_enabled');
        });
    }
};

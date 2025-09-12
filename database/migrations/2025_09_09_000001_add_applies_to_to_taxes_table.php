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
        Schema::table('taxes', function (Blueprint $table) {
            if (!Schema::hasColumn('taxes', 'applies_to')) {
                $table->enum('applies_to', ['item', 'payment', 'both'])->default('item')->after('percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            if (Schema::hasColumn('taxes', 'applies_to')) {
                $table->dropColumn('applies_to');
            }
        });
    }
};

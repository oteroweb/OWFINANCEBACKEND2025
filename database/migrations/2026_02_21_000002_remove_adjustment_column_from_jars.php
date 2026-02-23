<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the legacy 'adjustment' column from jars table.
     * All adjustment logic now uses jar_adjustments table as single source of truth.
     */
    public function up(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            if (Schema::hasColumn('jars', 'adjustment')) {
                $table->dropColumn('adjustment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jars', function (Blueprint $table) {
            if (!Schema::hasColumn('jars', 'adjustment')) {
                $table->decimal('adjustment', 12, 2)->default(0)->after('percent')
                    ->comment('Legacy: Manual adjustment to available balance');
            }
        });
    }
};

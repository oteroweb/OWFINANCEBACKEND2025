<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_currencies', function (Blueprint $table) {
            if (!Schema::hasColumn('user_currencies', 'is_official')) {
                $table->boolean('is_official')->default(true)->after('is_current');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_currencies', function (Blueprint $table) {
            if (Schema::hasColumn('user_currencies', 'is_official')) {
                $table->dropColumn('is_official');
            }
        });
    }
};
